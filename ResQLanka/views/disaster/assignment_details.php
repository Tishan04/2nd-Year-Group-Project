<?php

session_start();

require_once "../../config/database.php";

$database = new Database();
$conn = $database->connect();


/* =========================================================
   ESCAPE FUNCTION
========================================================= */

function escape($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   MESSAGES
========================================================= */

$successMessage = "";
$errorMessage = "";


/* =========================================================
   UPDATE VOLUNTEER NUMBER
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_volunteers"])
) {

    $assignmentId = $_POST["assignment_id"] ?? "";
    $volunteersNeeded = $_POST["volunteers_needed"] ?? "";


    /* -----------------------------------------
       VALIDATE ASSIGNMENT ID
    ----------------------------------------- */

    if (empty($assignmentId)) {

        $errorMessage = "Invalid assignment ID.";

    }


    /* -----------------------------------------
       VALIDATE NUMBER OF VOLUNTEERS
    ----------------------------------------- */

    elseif (
        $volunteersNeeded === "" ||
        !ctype_digit((string)$volunteersNeeded) ||
        (int)$volunteersNeeded < 0
    ) {

        $errorMessage =
            "Please enter a valid number of volunteers.";

    }


    /* -----------------------------------------
       UPDATE DATABASE
    ----------------------------------------- */

    else {

        try {

            $updateSql = "
                UPDATE VOLUNTEER_ASSIGNMENT
                SET volunteers_needed = :volunteers_needed
                WHERE assignment_id = :assignment_id
            ";

            $updateStmt = $conn->prepare($updateSql);

            $updateStmt->execute([
                ":volunteers_needed" => (int)$volunteersNeeded,
                ":assignment_id" => (int)$assignmentId
            ]);


            if ($updateStmt->rowCount() > 0) {

                $successMessage =
                    "Number of volunteers updated successfully.";

            } else {

                /*
                 * rowCount() can be 0 when the user enters
                 * the same value that is already stored.
                 */
                $checkSql = "
                    SELECT assignment_id
                    FROM VOLUNTEER_ASSIGNMENT
                    WHERE assignment_id = :assignment_id
                ";

                $checkStmt = $conn->prepare($checkSql);

                $checkStmt->execute([
                    ":assignment_id" => (int)$assignmentId
                ]);

                if ($checkStmt->fetch()) {

                    $successMessage =
                        "Volunteer number is already set to "
                        . (int)$volunteersNeeded
                        . ".";

                } else {

                    $errorMessage =
                        "Assignment was not found.";

                }

            }

        } catch (PDOException $e) {

            $errorMessage =
                "Unable to update volunteer number: "
                . $e->getMessage();

        }

    }
}


/* =========================================================
   DELETE ASSIGNMENT
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_assignment"])
) {

    $assignmentId = $_POST["assignment_id"] ?? "";


    if (!empty($assignmentId)) {

        try {

            $deleteSql = "
                DELETE FROM VOLUNTEER_ASSIGNMENT
                WHERE assignment_id = :assignment_id
            ";

            $deleteStmt = $conn->prepare($deleteSql);

            $deleteStmt->execute([
                ":assignment_id" => (int)$assignmentId
            ]);


            if ($deleteStmt->rowCount() > 0) {

                $successMessage =
                    "Volunteer assignment deleted successfully.";

            } else {

                $errorMessage =
                    "Assignment was not found.";

            }

        } catch (PDOException $e) {

            $errorMessage =
                "Unable to delete assignment: "
                . $e->getMessage();

        }

    } else {

        $errorMessage =
            "Invalid assignment ID.";

    }
}


/* =========================================================
   GET ALL VOLUNTEER ASSIGNMENTS
========================================================= */

$assignments = [];

$sql = "

    SELECT

        va.assignment_id,
        va.disaster_id,
        va.title,
        va.assignment_category,
        va.description,
        va.location,
        va.start_date,
        va.end_date,
        va.application_deadline,
        va.severity,
        va.duration,
        va.volunteers_needed,
        va.volunteer_requirements,
        va.additional_info,
        va.created_at,

        d.title AS disaster_title,
        d.disaster_type,

        CASE

            WHEN CURDATE() < va.start_date
                THEN 'Upcoming'

            WHEN CURDATE() > va.end_date
                THEN 'Completed'

            ELSE 'Ongoing'

        END AS current_status

    FROM VOLUNTEER_ASSIGNMENT va

    LEFT JOIN DISASTER d
        ON va.disaster_id = d.disaster_id

    ORDER BY va.created_at DESC

";


try {

    $stmt = $conn->query($sql);

    $assignments =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $errorMessage =
        "Unable to load volunteer assignments: "
        . $e->getMessage();

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Volunteer Assignments | ResQ Lanka
    </title>


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    >


    <!-- POPPINS -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- PAGE CSS -->

    <link
        rel="stylesheet"
        href="../../css/assignment_details.css"
    >

</head>


<body>


<?php include "../layouts/header.php"; ?>


<?php include "../layouts/navbar.php"; ?>


<div class="app-layout">


    <?php include "../layouts/district_admin_sidebar.php"; ?>


    <main class="main-content">


        <!-- =====================================================
             PAGE HEADING
        ====================================================== -->

        <div class="page-heading">

            <div>

                <div class="breadcrumb">

                    <span>
                        Manage Disasters
                    </span>

                    <i class="fa-solid fa-chevron-right"></i>

                    <strong>
                        Volunteer Assignments
                    </strong>

                </div>


                <h2>
                    Volunteer Assignments
                </h2>


                <p>
                    View and manage all volunteer assignments
                    created for disaster response.
                </p>

            </div>


            <!-- CREATE ASSIGNMENT -->

            <a
                href="volunteer_assignments.php"
                class="create-button"
            >

                <i class="fa-solid fa-plus"></i>

                Create Assignment

            </a>

        </div>



        <!-- =====================================================
             SUCCESS MESSAGE
        ====================================================== -->

        <?php if (!empty($successMessage)): ?>

            <div class="message success-message">

                <i class="fa-solid fa-circle-check"></i>

                <span>
                    <?= escape($successMessage) ?>
                </span>

            </div>

        <?php endif; ?>



        <!-- =====================================================
             ERROR MESSAGE
        ====================================================== -->

        <?php if (!empty($errorMessage)): ?>

            <div class="message error-message">

                <i class="fa-solid fa-circle-exclamation"></i>

                <span>
                    <?= escape($errorMessage) ?>
                </span>

            </div>

        <?php endif; ?>



        <!-- =====================================================
             ASSIGNMENT TABLE
        ====================================================== -->

        <section class="assignment-card">


            <!-- CARD HEADER -->

            <div class="card-header">

                <div>

                    <h3>
                        All Volunteer Assignments
                    </h3>

                    <p>
                        All assignments are displayed together.
                    </p>

                </div>


                <div class="assignment-count">

                    <i class="fa-solid fa-clipboard-list"></i>

                    <span>
                        <?= count($assignments) ?>
                    </span>

                    Assignments

                </div>

            </div>



            <div class="table-wrapper">

                <table class="assignment-table">


                    <thead>

                        <tr>

                            <th>
                                Volunteer Assignment
                            </th>

                            <th>
                                Location
                            </th>

                            <th>
                                No. of Volunteers
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Priority
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (!empty($assignments)): ?>


                        <?php foreach ($assignments as $assignment): ?>


                            <tr>


                                <!-- =================================================
                                     ASSIGNMENT
                                ================================================== -->

                                <td>

                                    <div class="assignment-title">

                                        <div class="assignment-icon">

                                            <i class="fa-solid fa-people-group"></i>

                                        </div>


                                        <div>

                                            <strong>

                                                <?= escape(
                                                    $assignment["title"]
                                                ) ?>

                                            </strong>


                                            <span>

                                                <?= escape(
                                                    $assignment["assignment_category"]
                                                ) ?>

                                            </span>

                                        </div>

                                    </div>

                                </td>



                                <!-- =================================================
                                     LOCATION
                                ================================================== -->

                                <td>

                                    <div class="location-cell">

                                        <i class="fa-solid fa-location-dot"></i>

                                        <span>

                                            <?= escape(
                                                $assignment["location"]
                                            ) ?>

                                        </span>

                                    </div>

                                </td>



                                <!-- =================================================
                                     NUMBER OF VOLUNTEERS
                                ================================================== -->

                                <td>

                                    <div class="volunteer-number">

                                        <i class="fa-solid fa-user-group"></i>

                                        <strong>

                                            <?= escape(
                                                $assignment["volunteers_needed"]
                                            ) ?>

                                        </strong>

                                    </div>

                                </td>



                                <!-- =================================================
                                     STATUS
                                ================================================== -->

                                <td>

                                    <?php

                                    $status = strtolower(
                                        trim(
                                            $assignment["current_status"]
                                        )
                                    );

                                    ?>


                                    <?php if ($status === "completed"): ?>

                                        <span
                                            class="status-badge completed"
                                        >

                                            <i class="fa-solid fa-circle-check"></i>

                                            Completed

                                        </span>


                                    <?php elseif ($status === "upcoming"): ?>

                                        <span
                                            class="status-badge upcoming"
                                        >

                                            <i class="fa-solid fa-circle-xmark"></i>

                                            Upcoming

                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="status-badge ongoing"
                                        >

                                            <i class="fa-solid fa-circle"></i>

                                            Ongoing

                                        </span>

                                    <?php endif; ?>

                                </td>



                                <!-- =================================================
                                     PRIORITY
                                ================================================== -->

                                <td>

                                    <?php

                                    $priority = strtolower(
                                        trim(
                                            $assignment["severity"]
                                        )
                                    );

                                    ?>


                                    <?php if ($priority === "high"): ?>

                                        <span
                                            class="priority-badge high"
                                        >
                                            High
                                        </span>


                                    <?php elseif ($priority === "medium"): ?>

                                        <span
                                            class="priority-badge medium"
                                        >
                                            Medium
                                        </span>


                                    <?php elseif ($priority === "low"): ?>

                                        <span
                                            class="priority-badge low"
                                        >
                                            Low
                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="priority-badge default"
                                        >

                                            <?= escape(
                                                $assignment["severity"]
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                </td>



                                <!-- =================================================
                                     ACTION BUTTONS
                                ================================================== -->

                                <td>

                                    <div class="action-buttons">


                                        <!-- VIEW -->

                                        <button
                                            type="button"
                                            class="view-button"
                                            onclick="showDetails(

                                                <?= htmlspecialchars(
                                                    json_encode(
                                                        $assignment,
                                                        JSON_HEX_TAG |
                                                        JSON_HEX_APOS |
                                                        JSON_HEX_QUOT |
                                                        JSON_HEX_AMP
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            )"
                                        >

                                            <i class="fa-regular fa-eye"></i>

                                            View

                                        </button>



                                        <!-- EDIT -->

                                        <button
                                            type="button"
                                            class="edit-button"
                                            onclick="openEditModal(

                                                <?= (int)$assignment["assignment_id"] ?>,

                                                '<?= escape(
                                                    $assignment["title"]
                                                ) ?>',

                                                <?= (int)$assignment["volunteers_needed"] ?>

                                            )"
                                        >

                                            <i class="fa-regular fa-pen-to-square"></i>

                                            Edit

                                        </button>



                                        <!-- DELETE -->

                                        <form
                                            method="POST"
                                            action=""
                                            class="delete-form"
                                            onsubmit="
                                                return confirm(
                                                    'Are you sure you want to delete this volunteer assignment?'
                                                );
                                            "
                                        >

                                            <input
                                                type="hidden"
                                                name="assignment_id"
                                                value="<?= escape(
                                                    $assignment["assignment_id"]
                                                ) ?>"
                                            >


                                            <button
                                                type="submit"
                                                name="delete_assignment"
                                                class="delete-button"
                                            >

                                                <i class="fa-regular fa-trash-can"></i>

                                                Delete

                                            </button>

                                        </form>


                                    </div>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="6"
                                class="empty-state"
                            >

                                <div>

                                    <i class="fa-regular fa-folder-open"></i>

                                    <h3>
                                        No Volunteer Assignments
                                    </h3>

                                    <p>
                                        There are currently no volunteer
                                        assignments in the system.
                                    </p>

                                    <a
                                        href="volunteer_assignments.php"
                                        class="empty-create-button"
                                    >

                                        <i class="fa-solid fa-plus"></i>

                                        Create Assignment

                                    </a>

                                </div>

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </section>


    </main>

</div>



<!-- ============================================================
     VIEW DETAILS MODAL
============================================================= -->

<div
    id="detailsModal"
    class="modal-overlay"
    onclick="closeDetails(event)"
>


    <div
        class="details-modal"
        onclick="event.stopPropagation()"
    >


        <div class="modal-header">

            <div>

                <span>
                    VOLUNTEER ASSIGNMENT
                </span>

                <h3 id="modalTitle">
                    Assignment Details
                </h3>

            </div>


            <button
                type="button"
                class="modal-close"
                onclick="closeDetails()"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>



        <div class="modal-body">


            <div class="detail-grid">


                <div class="detail-item">

                    <span>
                        Related Disaster
                    </span>

                    <strong id="modalDisaster">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Assignment Category
                    </span>

                    <strong id="modalCategory">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Location
                    </span>

                    <strong id="modalLocation">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Volunteers Needed
                    </span>

                    <strong id="modalVolunteers">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Start Date
                    </span>

                    <strong id="modalStart">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        End Date
                    </span>

                    <strong id="modalEnd">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Duration
                    </span>

                    <strong id="modalDuration">
                        -
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Priority
                    </span>

                    <strong id="modalSeverity">
                        -
                    </strong>

                </div>


                <div class="detail-item full-width">

                    <span>
                        Application Deadline
                    </span>

                    <strong id="modalApplicationDeadline">
                        -
                    </strong>

                </div>


                <div class="detail-item full-width">

                    <span>
                        Description
                    </span>

                    <p id="modalDescription">
                        -
                    </p>

                </div>


                <div class="detail-item full-width">

                    <span>
                        Volunteer Requirements
                    </span>

                    <p id="modalRequirements">
                        -
                    </p>

                </div>


                <div class="detail-item full-width">

                    <span>
                        Additional Information
                    </span>

                    <p id="modalAdditional">
                        -
                    </p>

                </div>


            </div>

        </div>

    </div>

</div>



<!-- ============================================================
     EDIT VOLUNTEER NUMBER MODAL
============================================================= -->

<div
    id="editModal"
    class="modal-overlay"
    onclick="closeEditModal(event)"
>


    <div
        class="edit-modal"
        onclick="event.stopPropagation()"
    >


        <!-- EDIT HEADER -->

        <div class="modal-header edit-modal-header">

            <div>

                <span>
                    EDIT ASSIGNMENT
                </span>

                <h3>
                    Update Volunteers Needed
                </h3>

            </div>


            <button
                type="button"
                class="modal-close"
                onclick="closeEditModal()"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>



        <!-- EDIT BODY -->

        <div class="edit-modal-body">


            <div class="edit-assignment-info">

                <i class="fa-solid fa-people-group"></i>

                <div>

                    <span>
                        Volunteer Assignment
                    </span>

                    <strong id="editAssignmentTitle">
                        -
                    </strong>

                </div>

            </div>



            <!-- UPDATE FORM -->

            <form
                method="POST"
                action=""
                class="edit-form"
            >


                <input
                    type="hidden"
                    name="assignment_id"
                    id="editAssignmentId"
                >


                <input
                    type="hidden"
                    name="update_volunteers"
                    value="1"
                >


                <div class="edit-form-group">

                    <label for="volunteersNeeded">

                        Number of Volunteers Needed

                    </label>


                    <div class="number-input-wrapper">

                        <i class="fa-solid fa-user-group"></i>

                        <input
                            type="number"
                            name="volunteers_needed"
                            id="volunteersNeeded"
                            min="0"
                            step="1"
                            required
                        >

                    </div>


                    <small>
                        Enter the new number of volunteers required
                        for this assignment.
                    </small>

                </div>



                <div class="edit-modal-actions">

                    <button
                        type="button"
                        class="cancel-edit-button"
                        onclick="closeEditModal()"
                    >

                        Cancel

                    </button>


                    <button
                        type="submit"
                        class="update-button"
                    >

                        <i class="fa-solid fa-check"></i>

                        Update Volunteers

                    </button>

                </div>


            </form>


        </div>

    </div>

</div>



<!-- ============================================================
     JAVASCRIPT
============================================================= -->

<script>


/* =========================================================
   VIEW DETAILS
========================================================= */

function showDetails(assignment)
{

    document.getElementById("modalTitle").textContent =
        assignment.title || "-";


    document.getElementById("modalDisaster").textContent =
        assignment.disaster_title || "-";


    document.getElementById("modalCategory").textContent =
        assignment.assignment_category || "-";


    document.getElementById("modalLocation").textContent =
        assignment.location || "-";


    document.getElementById("modalVolunteers").textContent =
        assignment.volunteers_needed || "-";


    document.getElementById("modalStart").textContent =
        assignment.start_date || "-";


    document.getElementById("modalEnd").textContent =
        assignment.end_date || "-";


    document.getElementById("modalDuration").textContent =
        assignment.duration || "-";


    document.getElementById("modalSeverity").textContent =
        assignment.severity || "-";


    document.getElementById("modalApplicationDeadline").textContent =
        assignment.application_deadline || "-";


    document.getElementById("modalDescription").textContent =
        assignment.description || "-";


    document.getElementById("modalRequirements").textContent =
        assignment.volunteer_requirements || "-";


    document.getElementById("modalAdditional").textContent =
        assignment.additional_info || "-";


    document.getElementById("detailsModal")
        .classList.add("show");

}



/* =========================================================
   CLOSE DETAILS MODAL
========================================================= */

function closeDetails(event)
{

    if (
        !event ||
        event.target.id === "detailsModal"
    ) {

        document.getElementById("detailsModal")
            .classList.remove("show");

    }

}



/* =========================================================
   OPEN EDIT MODAL
========================================================= */

function openEditModal(
    assignmentId,
    assignmentTitle,
    volunteersNeeded
)
{

    document.getElementById("editAssignmentId").value =
        assignmentId;


    document.getElementById("editAssignmentTitle").textContent =
        assignmentTitle;


    document.getElementById("volunteersNeeded").value =
        volunteersNeeded;


    document.getElementById("editModal")
        .classList.add("show");


    document.getElementById("volunteersNeeded")
        .focus();

}



/* =========================================================
   CLOSE EDIT MODAL
========================================================= */

function closeEditModal(event)
{

    if (
        !event ||
        event.target.id === "editModal"
    ) {

        document.getElementById("editModal")
            .classList.remove("show");

    }

}



/* =========================================================
   ESCAPE KEY
========================================================= */

document.addEventListener(
    "keydown",
    function(event)
    {

        if (event.key === "Escape") {

            document.getElementById("detailsModal")
                .classList.remove("show");


            document.getElementById("editModal")
                .classList.remove("show");

        }

    }
);

</script>



<?php include "../layouts/footer.php"; ?>


</body>

</html>