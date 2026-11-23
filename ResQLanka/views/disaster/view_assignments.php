<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$activePage = "disasters";
require_once "../../config/database.php";

$fullName = $_SESSION["name"] ?? "Volunteer";
$tier = $_SESSION["tier"] ?? "Bronze";
$points = $_SESSION["points"] ?? 0;
$userId = $_SESSION["user_id"] ?? null;

function escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}


function getAssignmentCover($type)
{
    $covers = [
        'Recue' => ['image' => '../../images/rescue.jpeg'],
        'Relief Distribution' => ['image' => '../../images/distribution.jpg'],
        'Medical Assistance' => ['image' => '../../images/medical.jpeg'],
        'Food Distribution' => ['image' => '../../images/food.jpeg'],
        'Clean-up' => ['image' => '../../images/cleanup.jpeg'],
        'Shelter Support' => ['image' => '../../images/shelter.jpeg'],
        'Other' => ['image' => '../../images/otherservice.jpeg']
    ];
    return $covers[$type] ?? $covers['Other'];
}

function getSeverityClass($priority)
{
    $class = [
        'Critical' => ['severity_class' => 'critical'],
        'Moderate' => ['severity_class' => 'moderate'],
        'Minor'    => ['severity_class' => 'minor'],
        'Other'    => ['severity_class' => 'default'] 
    ];
    
    
    return $class[ucfirst(strtolower($priority))] ?? $class['Other'];
}


function timeAgo($timestamp) {
    $timeAgo = strtotime($timestamp);
    $currentTime = time();
    $timeDifference = $currentTime - $timeAgo;
    
    $seconds = $timeDifference;
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 5) {
        return "Just now";
    } elseif ($minutes <= 60) {
        return $minutes == 1 ? "1m ago" : "$minutes mins ago";
    } elseif ($hours <= 24) {
        return $hours == 1 ? "1h ago" : "$hours hrs ago";
    } elseif ($days <= 7) {
        return $days == 1 ? "Yesterday" : "$days days ago";
    } elseif ($weeks <= 4.3) {
        return $weeks == 1 ? "1 week ago" : "$weeks weeks ago";
    } elseif ($months <= 12) {
        return $months == 1 ? "1 month ago" : "$months months ago";
    } else {
        return $years == 1 ? "1 year ago" : "$years years ago";
    }
}


$disasterId = filter_input(INPUT_GET, "disaster_id", FILTER_VALIDATE_INT);

if (!$disasterId) {
    header("Location: active_disasters.php");
    exit;
}

try {
    $database = new Database();
    $conn = $database->connect();

    $sql = "
        SELECT va.*,
            d.title AS disaster_title,
            d.disaster_type,
            d.description AS disaster_description,
            d.location AS disaster_location,
            d.priority,
            d.status AS disaster_status,
            d.duration AS disaster_duration
        FROM VOLUNTEER_ASSIGNMENT va
        INNER JOIN DISASTER d
            ON d.disaster_id = va.disaster_id
        WHERE va.disaster_id = :disaster_id
        
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":disaster_id" => $disasterId
    ]);

    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $disasterStmt = $conn->prepare("
    SELECT *
    FROM disaster 
    WHERE disaster_id = ?
");

$disasterStmt->execute([$disasterId]);
$disaster = $disasterStmt->fetch(PDO::FETCH_ASSOC);

    $errors = [];
    $success = false;

    
    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $contactNumber = trim($_POST["contact_number"] ?? "");
        $availability = trim($_POST["availability"] ?? "");
        $canTravel = trim($_POST["can_travel"] ?? "");
        $comfortableWithFuel = trim($_POST["comfortable_with_fuel"] ?? "");

        $skills = $_POST["skills_experience"] ?? [];
        if (!is_array($skills)) {
            $skills = [$skills];
        }

        $skills = array_values(array_filter(array_map("trim", $skills)));
        $skillsExperience = implode(", ", $skills);

        $additionalNote = trim($_POST["additional_note"] ?? "");
        $agreedToTerms = isset($_POST["agreed_to_terms"]) ? 1 : 0;

        if ($contactNumber === "") {
            $errors[] = "Please enter your contact number.";
        }

        if ($availability === "") {
            $errors[] = "Please select your availability.";
        }

        if ($canTravel === "") {
            $errors[] = "Please select whether you can travel to this location.";
        }

        if ($comfortableWithFuel === "") {
            $errors[] = "Please select whether you are comfortable with fuel-related conditions.";
        }

        if ($skillsExperience === "") {
            $errors[] = "Please select at least one relevant skill or experience.";
        }

        if ($agreedToTerms !== 1) {
            $errors[] = "You must agree to follow the coordinator instructions.";
        }

        if (empty($errors)) {

        
            $insertSql = "
                INSERT INTO VOLUNTEER_APPLICATION
                (
                    assignment_id,
                    user_id,
                    contact_number,
                    availability,
                    can_travel,
                    comfortable_with_fuel,
                    skills_experience,
                    additional_note,
                    agreed_to_terms,
                    status
                )
                VALUES
                (
                    :assignment_id,
                    :user_id,
                    :contact_number,
                    :availability,
                    :can_travel,
                    :comfortable_with_fuel,
                    :skills_experience,
                    :additional_note,
                    :agreed_to_terms,
                    'Pending'
                )
            ";

            $insertStmt = $conn->prepare($insertSql);

            $insertStmt->execute([
                ":assignment_id" => $assignmentId,
                ":user_id" => $userId,
                ":contact_number" => $contactNumber,
                ":availability" => $availability,
                ":can_travel" => $canTravel,
                ":comfortable_with_fuel" => $comfortableWithFuel,
                ":skills_experience" => $skillsExperience,
                ":additional_note" => $additionalNote !== "" ? $additionalNote : null,
                ":agreed_to_terms" => $agreedToTerms
            ]);

            $success = true;
        }
    }

} catch (PDOException $e) {
    $pageError = "Unable to load this assignment. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= isset($disaster) ? escape($disaster["title"]) : "View Assignments" ?>
        | ResQ Lanka
    </title>

    <link rel="stylesheet" href="../../css/view_assignments.css">
</head>

<body>

<?php include "../layouts/header.php"; ?>

<?php include "../layouts/navbar.php"; ?>

<div class="app-layout">


<?php include "../layouts/sidebar.php"; ?>

<main class="main-content">

    <?php if (isset($pageError)): ?>

        <section class="error-page">
            <div class="error-page-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <h2>Assignment Not Available</h2>
            <p><?= escape($pageError) ?></p>

            <a href="active_disasters.php" class="back-button">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Active Disasters
            </a>
        </section>

    <?php else: ?>

        <div class="breadcrumb">
            <a href="active_disasters.php">Active Disasters</a>
            <i class="fa-solid fa-chevron-right"></i>
            <span>View Assignments</span>
        </div>

        <?php if ($success): ?>

            <section class="success-card">
                <div class="success-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div>
                    <h2>Application Submitted Successfully</h2>
                    <p>
                        Your application has been saved and its current status is
                        <strong>Pending</strong>.
                    </p>
                </div>

                <a href="active_disasters.php" class="back-button">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Active Disasters
                </a>
            </section>

        <?php endif; ?>

        <?php if (!empty($errors)): ?>

            <div class="message error-message">
                <i class="fa-solid fa-circle-exclamation"></i>

                <div>
                    <strong>Please correct the following:</strong>

                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= escape($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

        <?php endif; ?>

        <section class="detail-heading">

            <div>
                <span class="section-label">ACTIVE DISASTER</span>

                <h2>
                    <?= escape($disaster["title"]) ?>
                </h2>

            </div>

            <span class="severity-badge <?= escape(($disaster["priority"])) ?>">
                <?= escape(strtoupper($disaster["priority"])) ?>
            </span>

        </section>

        <div class="details-layout">
            
            <section class="assignment-details-card">
                <span class="section-label">ASSIGNMENTS</span>
                <?php if (!$assignments):?>
                    <h2>No Assignments to show.</h2>
                <?php endif; ?>
                <?php foreach ($assignments as $assignment): 
                    $severity = getSeverityClass($assignment['severity'] ?? 'Other');
                    $coverData = getAssignmentCover($assignment['assignment_category'] ?? 'Other');
                    $image = $coverData['image'];?>
                   
                    <article class="assignment-card disaster-card border-<?= escape($severity['severity_class']) ?>">
                
                        <img src="<?= escape($image) ?>" alt="Disaster Area" class="disaster-img">

                        <div class="disaster-info-main">
                            <div class="disaster-top-row">
                                <span class="status-badge badge-<?= escape($severity['severity_class']) ?>">
                                    <?= escape($assignment['severity']) ?>
                                </span>
                                <a 
                                    href="apply_assignment.php?assignment_id=<?= escape($assignment['assignment_id'])?>"
                                    class="view-details btn-<?= escape($severity['severity_class']) ?>"
                                >
                                    View Assignment <i class="fa-solid fa-caret-down"></i>
                                </a>
                            </div>
                            
                            <h3 class="disaster-title"><?= escape($assignment['title']) ?></h3>
                            
                            <div class="disaster-location">
                                <i class="fa-solid fa-location-dot"></i> <?= escape($assignment['location']) ?>
                            </div>
                            
                            <p class="disaster-desc"><?= escape($assignment['description']) ?></p>
                            
                            <div class="disaster-meta-footer">
                                <span><i class="fa-regular fa-clock"></i> <?= escape(timeAgo($assignment['created_at'])) ?></span>
                                <span><i class="fa-solid fa-users"></i> <?= escape($assignment['volunteers_needed']) ?> volunteers requested</span>
                            </div>
                        </div>

                    </article>
                <?php endforeach; ?>
            </section>

        </div>

    <?php endif; ?>

    <section class="community-strip">


            <div class="community-message">

                <div class="community-main-icon">

                    <i class="fa-solid fa-shield-halved"></i>

                </div>


                <div>

                    <h3>
                        Every Action Counts.
                    </h3>

                    <p>
                        Prepared communities save lives.
                    </p>

                </div>

            </div>


            <div class="community-actions">


                <div class="community-item">

                    <i class="fa-solid fa-suitcase-rolling"></i>

                    <span>
                        Be Prepared
                    </span>

                </div>


                <div class="community-divider"></div>


                <div class="community-item">

                    <i class="fa-solid fa-users"></i>

                    <span>
                        Help Others
                    </span>

                </div>


                <div class="community-divider"></div>


                <div class="community-item">

                    <i class="fa-solid fa-hand-holding-heart"></i>

                    <span>
                        Save Lives
                    </span>

                </div>


            </div>

        </section>
</main>

</div>


<?php include "../layouts/footer.php"; ?>

</body>
</html>
