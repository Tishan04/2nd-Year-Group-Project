<?php
require_once __DIR__ . "/../../config/session.php";
requireRole(["district_admin"]);

require_once __DIR__ . "/../../config/database.php";

$pageTitle = "Manage Volunteers | ResQ Lanka";
$pageCSS = "../../css/manage_volunteers.css";
$activePage = "volunteers";

// Always submit actions and return to the real Manage Users page.
$manageUsersUrl = "/2nd-Year-Group-Project-git/ResQLanka/views/users/manage_users.php";

$district = trim($_SESSION["district"] ?? "");
$adminId = (int)($_SESSION["user_id"] ?? 0);

$database = new Database();
$conn = $database->connect();

function escape($value): string
{
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION["csrf_token"];

$message = "";
$messageType = "success";


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedToken = $_POST["csrf_token"] ?? "";

    if (!hash_equals($csrfToken, $postedToken)) {
        $message = "Your session verification failed. Please refresh the page and try again.";
        $messageType = "error";
    } elseif ($district === "") {
        $message = "Your district could not be identified from your login session.";
        $messageType = "error";
    } else {
        $action = $_POST["action"] ?? "";
        $applicationId = filter_input(INPUT_POST, "application_id", FILTER_VALIDATE_INT);

        if (!$applicationId || $applicationId < 1) {
            $message = "Invalid application selected.";
            $messageType = "error";
        } else {
            try {
                if ($action === "review_application") {
                    $decision = $_POST["decision"] ?? "";

                    if (!in_array($decision, ["approve", "reject"], true)) {
                        throw new RuntimeException("Invalid review decision.");
                    }

                    $newStatus = ($decision === "approve") ? "Approved" : "Rejected";

                    $updateSql = "
                        UPDATE volunteer_application app
                        INNER JOIN users u
                            ON app.user_id = u.user_id
                        INNER JOIN volunteer_assignment va
                            ON app.assignment_id = va.assignment_id
                        SET app.status = :new_status
                        WHERE app.application_id = :application_id
                          AND app.status = 'Pending'
                          AND LOWER(TRIM(u.district)) = LOWER(TRIM(:district))
                    ";

                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->execute([
                        ":new_status" => $newStatus,
                        ":application_id" => $applicationId,
                        ":district" => $district
                    ]);

                    if ($updateStmt->rowCount() > 0) {
                        $message = ($decision === "approve")
                            ? "Application approved. The volunteer is now marked as Assigned."
                            : "Application declined successfully.";
                    } else {
                        $message = "The pending application was not found in your district, or it has already been reviewed.";
                        $messageType = "error";
                    }

                } elseif ($action === "mark_completed") {
                    $completeSql = "
                        UPDATE volunteer_application app
                        INNER JOIN users u
                            ON app.user_id = u.user_id
                        SET app.status = 'Completed'
                        WHERE app.application_id = :application_id
                          AND app.status IN ('Approved', 'In Progress')
                          AND LOWER(TRIM(u.district)) = LOWER(TRIM(:district))
                    ";

                    $completeStmt = $conn->prepare($completeSql);
                    $completeStmt->execute([
                        ":application_id" => $applicationId,
                        ":district" => $district
                    ]);

                    if ($completeStmt->rowCount() > 0) {
                        $message = "Assignment marked as completed. The volunteer's completed assignment count has increased.";
                    } else {
                        $message = "No active approved assignment was found for this volunteer.";
                        $messageType = "error";
                    }

                } else {
                    $message = "Unknown action.";
                    $messageType = "error";
                }

            } catch (RuntimeException $e) {
                $message = $e->getMessage();
                $messageType = "error";
            } catch (PDOException $e) {
                $message = "Unable to update the application. Please check the database structure and try again.";
                $messageType = "error";
            }
        }
    }
}

/* Keep POST requests from being repeated when the page is refreshed. */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $_SESSION["volunteer_page_message"] = $message;
    $_SESSION["volunteer_page_message_type"] = $messageType;
    header("Location: " . $manageUsersUrl);
    exit();
}

if (isset($_SESSION["volunteer_page_message"])) {
    $message = $_SESSION["volunteer_page_message"];
    $messageType = $_SESSION["volunteer_page_message_type"] ?? "success";
    unset($_SESSION["volunteer_page_message"], $_SESSION["volunteer_page_message_type"]);
}

$volunteers = [];
$recentApplications = [];

$totalVolunteers = 0;
$availableCount = 0;
$assignedCount = 0;
$pendingApplicationCount = 0;
$loadError = "";

if ($district === "") {
    $loadError = "Your district is missing from your login session. Please sign in again or contact the system administrator.";
} else {
    try {
        
        $volunteerSql = "
            SELECT
                u.user_id,
                u.first_name,
                u.last_name,
                CONCAT(u.first_name, ' ', u.last_name) AS volunteer_name,
                u.email,
                u.phone,
                u.address,
                u.district,
                u.occupation,
                u.status AS account_status,

                COALESCE(
                    (
                        SELECT app1.skills_experience
                        FROM volunteer_application app1
                        WHERE app1.user_id = u.user_id
                        ORDER BY app1.applied_at DESC, app1.application_id DESC
                        LIMIT 1
                    ),
                    NULLIF(u.occupation, ''),
                    'Not provided'
                ) AS skills,

                COALESCE(
                    (
                        SELECT app2.availability
                        FROM volunteer_application app2
                        WHERE app2.user_id = u.user_id
                        ORDER BY app2.applied_at DESC, app2.application_id DESC
                        LIMIT 1
                    ),
                    'Not provided'
                ) AS availability,

                (
                    SELECT COUNT(*)
                    FROM volunteer_application completed_app
                    WHERE completed_app.user_id = u.user_id
                      AND completed_app.status = 'Completed'
                ) AS completed_assignments,

                (
                    SELECT COUNT(*)
                    FROM volunteer_application pending_app
                    WHERE pending_app.user_id = u.user_id
                      AND pending_app.status = 'Pending'
                ) AS pending_count,

                (
                    SELECT COUNT(*)
                    FROM volunteer_application active_app
                    WHERE active_app.user_id = u.user_id
                      AND active_app.status IN ('Approved', 'In Progress')
                ) AS active_count,

                (
                    SELECT active_app2.application_id
                    FROM volunteer_application active_app2
                    WHERE active_app2.user_id = u.user_id
                      AND active_app2.status IN ('Approved', 'In Progress')
                    ORDER BY active_app2.applied_at DESC, active_app2.application_id DESC
                    LIMIT 1
                ) AS active_application_id

            FROM users u
            WHERE u.role = 'registered_user'
              AND u.status = 'active'
              AND LOWER(TRIM(u.district)) = LOWER(TRIM(:district))
            ORDER BY u.first_name ASC, u.last_name ASC
        ";

        $volunteerStmt = $conn->prepare($volunteerSql);
        $volunteerStmt->execute([":district" => $district]);
        $volunteers = $volunteerStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($volunteers as &$volunteer) {
            $pending = (int)$volunteer["pending_count"];
            $active = (int)$volunteer["active_count"];

            $pendingApplicationCount += $pending;

            if ($active > 0) {
                $volunteer["volunteer_status"] = "Assigned";
                $volunteer["status_class"] = "assigned-status";
                $assignedCount++;
            } elseif ($pending > 0) {
                $volunteer["volunteer_status"] = "Pending Review";
                $volunteer["status_class"] = "pending-status";
            } else {
                $volunteer["volunteer_status"] = "Available";
                $volunteer["status_class"] = "available-status";
                $availableCount++;
            }
        }
        unset($volunteer);

        $totalVolunteers = count($volunteers);

        
        $applicationsSql = "
            SELECT
                app.application_id,
                app.user_id,
                app.assignment_id,
                app.contact_number,
                app.availability,
                app.can_travel,
                app.comfortable_with_fuel,
                app.skills_experience,
                app.additional_note,
                app.agreed_to_terms,
                app.status AS application_status,
                app.applied_at,

                u.first_name,
                u.last_name,
                u.email,
                u.phone AS user_phone,
                u.address,
                u.district,

                va.title AS assignment_title,
                va.assignment_category,
                va.description AS assignment_description,
                va.location AS assignment_location,
                va.start_date,
                va.end_date,
                va.severity,
                va.volunteer_requirements,
                va.additional_info

            FROM volunteer_application app
            INNER JOIN users u
                ON app.user_id = u.user_id
            INNER JOIN volunteer_assignment va
                ON app.assignment_id = va.assignment_id
            WHERE LOWER(TRIM(u.district)) = LOWER(TRIM(:district))
              AND u.role = 'registered_user'
            ORDER BY app.applied_at DESC, app.application_id DESC
            LIMIT 8
        ";

        $applicationsStmt = $conn->prepare($applicationsSql);
        $applicationsStmt->execute([":district" => $district]);
        $recentApplications = $applicationsStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $loadError = "Unable to load volunteer information. Please check that the users, volunteer_application, and volunteer_assignment tables contain the expected columns.";
    }
}

include __DIR__ . "/../layouts/header.php";
include __DIR__ . "/../layouts/navbar.php";
?>

<div class="app-layout">

    <?php include __DIR__ . "/../layouts/district_admin_sidebar.php"; ?>

    <main class="main-content">

        <section class="page-heading">
            <div>
                <h2>Manage Volunteers</h2>
                <p>View and manage volunteers in your district</p>
            </div>

            <div class="district-badge">
                <i class="fa-solid fa-location-dot"></i>
                District <?= escape($district !== "" ? $district : "Not set") ?>
            </div>
        </section>

        <?php if ($message !== ""): ?>
            <div class="message <?= $messageType === "error" ? "error-message" : "success-message" ?>">
                <i class="fa-solid <?= $messageType === "error" ? "fa-circle-exclamation" : "fa-circle-check" ?>"></i>
                <span><?= escape($message) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($loadError !== ""): ?>
            <div class="message error-message">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= escape($loadError) ?></span>
            </div>
        <?php endif; ?>

        <section class="stats-grid">

            <article class="stat-card blue">
                <div class="stat-icon"><i class="fa-solid fa-globe"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?= escape($totalVolunteers) ?></span>
                    <span class="stat-label">Total Volunteers</span>
                </div>
            </article>

            <article class="stat-card green">
                <div class="stat-icon"><i class="fa-regular fa-circle-check"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?= escape($availableCount) ?></span>
                    <span class="stat-label">Available Volunteers</span>
                </div>
            </article>

            <article class="stat-card orange">
                <div class="stat-icon"><i class="fa-regular fa-clipboard"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?= escape($assignedCount) ?></span>
                    <span class="stat-label">Assigned to Tasks</span>
                </div>
            </article>

            <article class="stat-card red">
                <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="stat-info">
                    <span class="stat-number"><?= escape($pendingApplicationCount) ?></span>
                    <span class="stat-label">Pending Applications</span>
                </div>
            </article>

        </section>

        <section class="filter-section">
            <button type="button" class="filter-button active" data-filter="all">
                <span></span>All Volunteers
            </button>
            <button type="button" class="filter-button available" data-filter="available">
                <span></span>Available
            </button>
            <button type="button" class="filter-button assigned" data-filter="assigned">
                <span></span>Assigned
            </button>
            <button type="button" class="filter-button pending" data-filter="pending">
                <span></span>Pending
            </button>
        </section>

        <section class="directory-card">

            <div class="section-title">
                <h3>Volunteer Directory <span>(<?= escape($totalVolunteers) ?>)</span></h3>
            </div>

            <div class="table-wrapper">
                <table class="volunteer-table">
                    <thead>
                        <tr>
                            <th>Volunteer Name</th>
                            <th>Location</th>
                            <th>Skills / Occupation</th>
                            <th>Availability</th>
                            <th>Status</th>
                            <th>Completed Assignments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (!empty($volunteers)): ?>
                        <?php foreach ($volunteers as $volunteer): ?>
                            <?php
                            $filterStatus = "available";
                            if ($volunteer["volunteer_status"] === "Assigned") {
                                $filterStatus = "assigned";
                            } elseif ($volunteer["volunteer_status"] === "Pending Review") {
                                $filterStatus = "pending";
                            }
                            ?>
                            <tr data-volunteer-status="<?= escape($filterStatus) ?>">
                                <td>
                                    <strong><?= escape($volunteer["volunteer_name"]) ?></strong>
                                    <small><?= escape($volunteer["email"]) ?></small>
                                </td>

                                <td>
                                    <span class="location">
                                        <i class="fa-solid fa-location-dot"></i>
                                        <?= escape($volunteer["address"] ?: $volunteer["district"]) ?>
                                    </span>
                                </td>

                                <td class="skills-cell"><?= escape($volunteer["skills"]) ?></td>
                                <td><?= escape($volunteer["availability"]) ?></td>

                                <td>
                                    <span class="status <?= escape($volunteer["status_class"]) ?>">
                                        <span></span><?= escape($volunteer["volunteer_status"]) ?>
                                    </span>
                                </td>

                                <td class="completed-count">
                                    <?= escape((int)$volunteer["completed_assignments"]) ?>
                                </td>

                                <td>
                                    <div class="action-buttons">
                                        <button
                                            type="button"
                                            class="view-button"
                                            data-volunteer="<?= escape(json_encode($volunteer, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>"
                                            onclick="openVolunteerDetails(this)"
                                        >
                                            View Details
                                        </button>

                                        <?php if (!empty($volunteer["active_application_id"])): ?>
                                            <form method="POST" action="<?= escape($manageUsersUrl) ?>" class="inline-form" onsubmit="return confirm('Mark this volunteer assignment as completed?');">
                                                <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                                                <input type="hidden" name="action" value="mark_completed">
                                                <input type="hidden" name="application_id" value="<?= escape($volunteer["active_application_id"]) ?>">
                                                <button type="submit" class="complete-button">
                                                    <i class="fa-regular fa-circle-check"></i>
                                                    Mark as Completed
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="no-active-task">No active task</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                No active registered users were found in <?= escape($district) ?>.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="applications-card">
            <div class="section-title">
                <h3>Recent Volunteer Applications</h3>
            </div>

            <div class="applications-table-wrapper">
                <table class="applications-table">
                    <thead>
                        <tr>
                            <th>Applicant Name</th>
                            <th>Location</th>
                            <th>Submitted</th>
                            <th>Preferred Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (!empty($recentApplications)): ?>
                        <?php foreach ($recentApplications as $application): ?>
                            <tr>
                                <td>
                                    <strong><?= escape(trim($application["first_name"] . " " . $application["last_name"])) ?></strong>
                                </td>
                                <td>
                                    <span class="location">
                                        <i class="fa-solid fa-location-dot"></i>
                                        <?= escape($application["address"] ?: $application["district"]) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="submitted">
                                        <i class="fa-regular fa-clock"></i>
                                        <?= escape(date("M j, Y g:i A", strtotime($application["applied_at"]))) ?>
                                    </span>
                                </td>
                                <td><?= escape($application["assignment_category"]) ?></td>
                                <td>
                                    <?php
                                    $appStatus = $application["application_status"];
                                    $appStatusClass = "application-" . strtolower(str_replace(" ", "-", $appStatus));
                                    ?>
                                    <span class="application-status <?= escape($appStatusClass) ?>">
                                        <?= escape($appStatus) ?>
                                    </span>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="review-button"
                                        data-application="<?= escape(json_encode($application, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>"
                                        onclick="openApplicationReview(this)"
                                    >
                                        <?= $appStatus === "Pending" ? "Review" : "View" ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">There are no volunteer applications in this district yet.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>
</div>

<!-- Volunteer details modal -->
<div class="modal-overlay" id="volunteerModal" onclick="closeModalOnBackdrop(event, 'volunteerModal')">
    <section class="management-modal" role="dialog" aria-modal="true" aria-labelledby="volunteerModalTitle">
        <div class="modal-header">
            <div>
                <span>VOLUNTEER INFORMATION</span>
                <h3 id="volunteerModalTitle">Volunteer Details</h3>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('volunteerModal')" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="details-grid">
                <div><span>Full Name</span><strong id="detailName">-</strong></div>
                <div><span>Email</span><strong id="detailEmail">-</strong></div>
                <div><span>Contact Number</span><strong id="detailPhone">-</strong></div>
                <div><span>District</span><strong id="detailDistrict">-</strong></div>
                <div><span>Address</span><strong id="detailAddress">-</strong></div>
                <div><span>Occupation / Skills</span><strong id="detailSkills">-</strong></div>
                <div><span>Availability</span><strong id="detailAvailability">-</strong></div>
                <div><span>Volunteer Status</span><strong id="detailStatus">-</strong></div>
                <div><span>Completed Assignments</span><strong id="detailCompleted">0</strong></div>
            </div>
        </div>
    </section>
</div>

<!-- Application review modal -->
<div class="modal-overlay" id="applicationModal" onclick="closeModalOnBackdrop(event, 'applicationModal')">
    <section class="management-modal application-modal" role="dialog" aria-modal="true" aria-labelledby="applicationModalTitle">
        <div class="modal-header">
            <div>
                <span>VOLUNTEER APPLICATION</span>
                <h3 id="applicationModalTitle">Application Details</h3>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('applicationModal')" aria-label="Close">&times;</button>
        </div>

        <div class="modal-body">
            <div class="details-grid">
                <div><span>Applicant</span><strong id="applicantName">-</strong></div>
                <div><span>Contact Number</span><strong id="applicantPhone">-</strong></div>
                <div><span>Email</span><strong id="applicantEmail">-</strong></div>
                <div><span>Location</span><strong id="applicantLocation">-</strong></div>
                <div><span>Assignment</span><strong id="appAssignment">-</strong></div>
                <div><span>Preferred Role</span><strong id="appRole">-</strong></div>
                <div><span>Availability</span><strong id="appAvailability">-</strong></div>
                <div><span>Can Travel?</span><strong id="appTravel">-</strong></div>
                <div><span>Comfortable with Fuel?</span><strong id="appFuel">-</strong></div>
                <div><span>Application Date</span><strong id="appDate">-</strong></div>
                <div class="full-width"><span>Skills and Experience</span><p id="appSkills">-</p></div>
                <div class="full-width"><span>Additional Note</span><p id="appNote">-</p></div>
                <div class="full-width"><span>Assignment Location</span><p id="appAssignmentLocation">-</p></div>
                <div class="full-width"><span>Assignment Description</span><p id="appDescription">-</p></div>
                <div class="full-width"><span>Volunteer Requirements</span><p id="appRequirements">-</p></div>
            </div>

            <div class="review-actions" id="reviewActions">
                <form method="POST" action="<?= escape($manageUsersUrl) ?>" class="review-decision-form" onsubmit="return confirm('Approve this volunteer application?');">
                    <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                    <input type="hidden" name="action" value="review_application">
                    <input type="hidden" name="application_id" id="reviewApplicationId" value="">
                    <input type="hidden" name="decision" value="approve">
                    <button type="submit" class="approve-button">
                        <i class="fa-solid fa-check"></i> Approve Application
                    </button>
                </form>

                <form method="POST" action="<?= escape($manageUsersUrl) ?>" class="review-decision-form" onsubmit="return confirm('Decline this volunteer application?');">
                    <input type="hidden" name="csrf_token" value="<?= escape($csrfToken) ?>">
                    <input type="hidden" name="action" value="review_application">
                    <input type="hidden" name="application_id" id="declineApplicationId" value="">
                    <input type="hidden" name="decision" value="reject">
                    <button type="submit" class="decline-button">
                        <i class="fa-solid fa-xmark"></i> Decline Application
                    </button>
                </form>
            </div>

            <p class="already-reviewed" id="alreadyReviewed" hidden>
                This application has already been reviewed. No further decision is available.
            </p>
        </div>
    </section>
</div>

<script>
function setText(id, value) {
    document.getElementById(id).textContent =
        value === null || value === undefined || value === "" ? "-" : value;
}

function openVolunteerDetails(button) {
    const volunteer = JSON.parse(button.dataset.volunteer);

    setText("volunteerModalTitle", volunteer.volunteer_name);
    setText("detailName", volunteer.volunteer_name);
    setText("detailEmail", volunteer.email);
    setText("detailPhone", volunteer.phone);
    setText("detailDistrict", volunteer.district);
    setText("detailAddress", volunteer.address);
    setText("detailSkills", volunteer.skills);
    setText("detailAvailability", volunteer.availability);
    setText("detailStatus", volunteer.volunteer_status);
    setText("detailCompleted", volunteer.completed_assignments);

    document.getElementById("volunteerModal").classList.add("show");
}

function openApplicationReview(button) {
    const app = JSON.parse(button.dataset.application);
    const fullName = [app.first_name, app.last_name].filter(Boolean).join(" ");

    setText("applicationModalTitle", "Application from " + fullName);
    setText("applicantName", fullName);
    setText("applicantPhone", app.contact_number || app.user_phone);
    setText("applicantEmail", app.email);
    setText("applicantLocation", app.address || app.district);
    setText("appAssignment", app.assignment_title);
    setText("appRole", app.assignment_category);
    setText("appAvailability", app.availability);
    setText("appTravel", app.can_travel);
    setText("appFuel", app.comfortable_with_fuel);
    setText("appDate", app.applied_at);
    setText("appSkills", app.skills_experience);
    setText("appNote", app.additional_note);
    setText("appAssignmentLocation", app.assignment_location);
    setText("appDescription", app.assignment_description);
    setText("appRequirements", app.volunteer_requirements);

    document.getElementById("reviewApplicationId").value = app.application_id;
    document.getElementById("declineApplicationId").value = app.application_id;

    const isPending = app.application_status === "Pending";
    document.getElementById("reviewActions").hidden = !isPending;
    document.getElementById("alreadyReviewed").hidden = isPending;

    document.getElementById("applicationModal").classList.add("show");
}

function closeModal(id) {
    document.getElementById(id).classList.remove("show");
}

function closeModalOnBackdrop(event, id) {
    if (event.target.id === id) {
        closeModal(id);
    }
}

document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        closeModal("volunteerModal");
        closeModal("applicationModal");
    }
});

/* Filter the volunteer directory by the computed volunteer status. */
document.querySelectorAll(".filter-button").forEach(function(button) {
    button.addEventListener("click", function() {
        document.querySelectorAll(".filter-button").forEach(function(item) {
            item.classList.remove("active");
        });

        button.classList.add("active");

        const filter = button.dataset.filter;

        document.querySelectorAll(".volunteer-table tbody tr[data-volunteer-status]").forEach(function(row) {
            row.hidden = filter !== "all" && row.dataset.volunteerStatus !== filter;
        });
    });
});
</script>

<?php include __DIR__ . "/../layouts/footer.php"; ?>
