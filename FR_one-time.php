<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug_issues.log'); // Creates log file in same folder

session_start();
require 'connection.php';

// ✅ Ensure user is logged in
$client_email = $_SESSION['email'] ?? null;
if (!$client_email) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

   $booking_id = $_POST['report-booking-id'];
$issue_type = $_POST['issueType'];
$issue_description = $_POST['issueDetails'];


    date_default_timezone_set('Asia/Manila');
    $issue_report_date = date('Y-m-d');
    $issue_report_time = date('H:i:s');

    // Handle images
    $photo1 = null; $photo2 = null; $photo3 = null;

   if (!empty($_FILES['attachment1']['name'])) {
    $photo1 = "uploads/" . time() . "_1_" . basename($_FILES['attachment1']['name']);
    move_uploaded_file($_FILES['attachment1']['tmp_name'], $photo1);
}

  if (!empty($_FILES['attachment2']['name'])) {
    $photo1 = "uploads/" . time() . "_1_" . basename($_FILES['attachment2']['name']);
    move_uploaded_file($_FILES['attachment2']['tmp_name'], $photo1);
}

   if (!empty($_FILES['attachment3']['name'])) {
    $photo1 = "uploads/" . time() . "_1_" . basename($_FILES['attachment3']['name']);
    move_uploaded_file($_FILES['attachment3']['tmp_name'], $photo1);
}


    $query = "UPDATE bookings SET 
        issue_type='$issue_type',
        issue_description='$issue_description',
        issue_report_date='$issue_report_date',
        issue_report_time='$issue_report_time',
        issue_photo1='$photo1',
        issue_photo2='$photo2',
        issue_photo3='$photo3'
        WHERE id='$booking_id'";

    if ($conn->query($query)) {
        echo "success";
    } else {
        echo "error: " . $conn->error;
    }
}
// =============================
// HANDLE REPORT ISSUE SUBMISSION
// =============================
// =============================
// HANDLE REPORT ISSUE SUBMISSION
// =============================
if (isset($_POST['report-booking-id'])) {
    // Force display errors on screen for debugging
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    echo "<!DOCTYPE html><html><head><title>Debug Output</title></head><body>";
    echo "<h1>🔍 DEBUG OUTPUT</h1>";
    echo "<div style='background:#000; color:#0f0; padding:20px; font-family:monospace;'>";
    
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    echo "<h2>Extracted Values:</h2>";
    $booking_id = intval($_POST['report-booking-id']);
    $issue_type = $_POST['issueType'] ?? '';
    $issue_description = $_POST['issueDetails'] ?? '';
    $submission_date = $_POST['submissionDate'] ?? date('Y-m-d');
    $submission_time = $_POST['submissionTime'] ?? date('H:i:s');
    
    echo "Booking ID: " . $booking_id . "<br>";
    echo "Issue Type: '" . htmlspecialchars($issue_type) . "' (Length: " . strlen($issue_type) . ")<br>";
    echo "Issue Description: '" . htmlspecialchars(substr($issue_description, 0, 100)) . "...' (Length: " . strlen($issue_description) . ")<br>";
    echo "Submission Date: " . $submission_date . "<br>";
    echo "Submission Time: " . $submission_time . "<br>";
    
    // Check if booking exists
    echo "<h2>Checking if Booking Exists:</h2>";
    $check = $conn->query("SELECT id, service_type, status, email FROM bookings WHERE id = $booking_id");
    if ($check && $check->num_rows > 0) {
        $existing = $check->fetch_assoc();
        echo "✅ BOOKING FOUND:<br>";
        echo "ID: " . $existing['id'] . "<br>";
        echo "Service: " . $existing['service_type'] . "<br>";
        echo "Status: " . $existing['status'] . "<br>";
        echo "Email: " . $existing['email'] . "<br>";
    } else {
        echo "❌ BOOKING NOT FOUND!<br>";
    }
    
    // Handle file uploads (simplified for debug)
    $upload_dir = 'uploads/issues/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $photo1 = null;
    $photo2 = null;
    $photo3 = null;
    
    if (isset($_FILES['attachment1']) && $_FILES['attachment1']['error'] == 0) {
        $filename1 = time() . '_1_' . basename($_FILES['attachment1']['name']);
        if (move_uploaded_file($_FILES['attachment1']['tmp_name'], $upload_dir . $filename1)) {
            $photo1 = $filename1;
        }
    }
    
    if (isset($_FILES['attachment2']) && $_FILES['attachment2']['error'] == 0) {
        $filename2 = time() . '_2_' . basename($_FILES['attachment2']['name']);
        if (move_uploaded_file($_FILES['attachment2']['tmp_name'], $upload_dir . $filename2)) {
            $photo2 = $filename2;
        }
    }
    
    if (isset($_FILES['attachment3']) && $_FILES['attachment3']['error'] == 0) {
        $filename3 = time() . '_3_' . basename($_FILES['attachment3']['name']);
        if (move_uploaded_file($_FILES['attachment3']['tmp_name'], $upload_dir . $filename3)) {
            $photo3 = $filename3;
        }
    }
    
    echo "<h2>Preparing SQL Statement:</h2>";
    $stmt = $conn->prepare("UPDATE bookings SET 
            issue_type = ?,
            issue_description = ?,
            issue_report_date = ?,
            issue_report_time = ?,
            issue_photo1 = ?,
            issue_photo2 = ?,
            issue_photo3 = ?
            WHERE id = ?");
    
    if (!$stmt) {
        echo "❌ PREPARE FAILED: " . $conn->error . "<br>";
        die();
    }
    
    echo "✅ Statement prepared<br>";
    
    $stmt->bind_param("sssssssi", 
        $issue_type, 
        $issue_description, 
        $submission_date, 
        $submission_time,
        $photo1,
        $photo2,
        $photo3,
        $booking_id
    );
    
    echo "<h2>Executing Query:</h2>";
    echo "SQL: UPDATE bookings SET issue_type='$issue_type', issue_description='$issue_description', ... WHERE id=$booking_id<br>";
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        echo "✅ QUERY EXECUTED!<br>";
        echo "Affected Rows: " . $affected . "<br>";
        
        if ($affected > 0) {
            echo "<h2 style='color:#0f0;'>✅ SUCCESS! Data was saved!</h2>";
            
            // Verify what was saved
            $verify = $conn->query("SELECT issue_type, issue_description, issue_report_date FROM bookings WHERE id = $booking_id");
            if ($verify && $verify->num_rows > 0) {
                $saved = $verify->fetch_assoc();
                echo "<h3>Data now in database:</h3>";
                echo "issue_type: '" . htmlspecialchars($saved['issue_type']) . "'<br>";
                echo "issue_description: '" . htmlspecialchars(substr($saved['issue_description'], 0, 100)) . "...'<br>";
                echo "issue_report_date: " . $saved['issue_report_date'] . "<br>";
            }
        } else {
            echo "<h2 style='color:#f00;'>⚠️ WARNING: No rows were affected!</h2>";
            echo "This means either:<br>";
            echo "1. The booking ID doesn't exist<br>";
            echo "2. The data is identical to what's already there<br>";
        }
    } else {
        echo "❌ EXECUTE FAILED: " . $stmt->error . "<br>";
    }
    
    $stmt->close();
    
    echo "</div>";
    echo "<p><a href='FR_one-time.php'>← Go Back</a></p>";
    echo "</body></html>";
    exit;
}

// =============================
// FETCH COMPLETED ONE-TIME BOOKINGS
// =============================
$sql = "SELECT * FROM bookings 
        WHERE status = 'Completed' 
        AND booking_type = 'One-Time' ";

if ($client_email) {
    $sql .= "AND email = '" . $conn->real_escape_string($client_email) . "' ";
}
$sql .= "ORDER BY service_date DESC, service_time DESC";

$result = $conn->query($sql);

// =============================
// STORE BOOKINGS BY SERVICE TYPE
// =============================
$bookings_by_type = [
    'Checkout Cleaning' => [],
    'In-House Cleaning' => [],
    'Refresh Cleaning' => [],
    'Deep Cleaning' => []
];

// Helper function to calculate final price
function calculateFinalPrice($booking) {
    $duration = floatval($booking['duration'] ?? 1);
    $materialsStr = $booking['materials_provided'] ?? '';

    preg_match('/(\d+(\.\d+)?)\s*AED/i', $materialsStr, $matches);
    $pricePerHour = isset($matches[1]) ? floatval($matches[1]) : 0;

    $finalPrice = $pricePerHour * $duration;

    if (stripos($materialsStr, 'yes') !== false) {
        $finalPrice *= 1; // optional: modify if needed
    }

    return $finalPrice;
}

// Populate bookings array and calculate price
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $service_type = $row['service_type'] ?? 'Other';
        if (isset($bookings_by_type[$service_type])) {
            $row['final_price'] = calculateFinalPrice($row);
            $bookings_by_type[$service_type][] = $row;
        }
    }
}

// =============================
// HELPER FUNCTIONS
// =============================
function formatDate($date) {
    return empty($date) ? 'N/A' : date('F d, Y', strtotime($date));
}

function formatTime($time) {
    return empty($time) ? 'N/A' : date('g:i A', strtotime($time));
}

function generateRefNo($booking_id, $date) {
    $dateStr = date('ymd', strtotime($date));
    $sequence = str_pad($booking_id, 4, '0', STR_PAD_LEFT);

    return "ALZ-OT-{$dateStr}-{$sequence}";
}




?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ALAZIMA - One-Time Feedback/Ratings</title>
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="client_db.css"> 
<link rel="stylesheet" href="HIS_design.css">
<link rel="stylesheet" href="FR_design.css">
<link rel="stylesheet" href="FR_design2.css">

<style>
.service-type-filter-dropdown {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 0.8em;
    cursor: pointer;
    background-color: #fff;
    width: 170px;
    height: 40px;
    flex-shrink: 0;
}

.staff-details-container {
    background-color: #f7f9fc;
    margin-top: 15px;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #e0e6ed;
    font-size: 0.95em;
    color: #333;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.staff-details-container h4 {
    font-size: 1.1em;
    font-weight: bold;
    color: #004085; 
    margin-bottom: 10px;
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 5px;
    padding-bottom: 5px;
    border-bottom: 1px solid #e0e6ed;
}

.staff-details-container p {
    margin-bottom: 5px;
    display: flex;
    align-items: flex-start;
    line-height: 1.4;
}

.staff-details-container p i {
    font-size: 1.2em;
    margin-right: 8px;
    color: #0035b3;
}

.staff-details-container p strong {
    font-weight: 600;
    margin-right: 5px;
    min-width: 65px;
}
.issue-list-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.issue-card {
    background: #ffffff;
    border: 1px solid #e7e7e7;
    border-radius: 10px;
    padding: 18px 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    transition: 0.2s ease-in-out;
}

.issue-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.issue-card h4 {
    margin: 0;
    color: #333;
    font-size: 1.2em;
    font-weight: 600;
}

.issue-card p {
    margin: 6px 0;
    color: #555;
    font-size: 0.95em;
}

.issue-btn {
    margin-top: 10px;
    background-color: #ff6b00;
    color: #fff;
    border: none;
    padding: 9px 15px;
    border-radius: 6px;
    font-size: 0.9em;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: 0.2s;
}

.issue-btn:hover {
    background-color: #ff5500;
}
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.45);
    backdrop-filter: blur(2px);
}

.modal-content {
    background: #fff;
    width: 420px;
    margin: 10% auto;
    padding: 25px;
    border-radius: 10px;
    animation: fadeIn .25s ease;
}

.modal-content h3 {
    margin-top: 0;
}

.submit-btn {
    background-color: #ff6b00;
    color: #fff;
    padding: 10px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 10px;
}

.cancel-btn {
    background: #777;
    color: #fff;
    padding: 10px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-left: 8px;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-15px); }
    to { opacity: 1; transform: translateY(0); }
}
.report-modal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.report-modal-content {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    width: 500px;
    max-width: 95%;
    display: flex;
    flex-direction: column;
    gap: 15px; /* space between inputs */
    position: relative;
}

.attachment-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.custom-file-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
}

.custom-file-button {
    padding: 5px 10px;
    background-color: #eee;
    border: 1px solid #ccc;
    cursor: pointer;
}

.custom-file-text {
    font-size: 0.9em;
    color: #555;
}

/* make sure submit button is properly inside modal */
.form-submit-wrapper {
    margin-top: 15px;
    display: flex;
    justify-content: flex-end;
}

button[type="submit"] {
    padding: 8px 16px;
    background-color: #007BFF;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}



</style>
</head>
    
<body>
    <header class="header" id="header">
        <nav class="nav container">
            <a href="client_dashboard.php?content=dashboard" class="nav__logo">
                <img src="LOGO.png" alt="ALAZIMA Cleaning Services LLC Logo" onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
            </a>
            <button class="nav__toggle" id="nav-toggle" aria-label="Toggle navigation menu">
                <i class='bx bx-menu'></i>
            </button>
        </nav>
    </header>

    <div class="dashboard__wrapper">
        <aside class="dashboard__sidebar">
            <ul class="sidebar__menu">
                <li class="menu__item"><a href="client_dashboard.php?content=dashboard" class="menu__link" data-content="dashboard"><i class='bx bx-home-alt-2'></i> Dashboard</a></li>
                
                <li class="menu__item has-dropdown">
                    <a href="#" class="menu__link" data-content="book-appointment-parent"><i class='bx bx-calendar'></i> Book Appointment <i class='bx bx-chevron-down arrow-icon'></i></a>
                    <ul class="dropdown__menu">
                        <li class="menu__item"><a href="BA_one-time.php" class="menu__link">One-Time Service</a></li>
                        <li class="menu__item"><a href="BA_recurring.php" class="menu__link">Recurring Service</a></li>
                    </ul>
                </li>
                
                <li class="menu__item has-dropdown">
                    <a href="#" class="menu__link" data-content="history-parent"><i class='bx bx-history'></i> History <i class='bx bx-chevron-down arrow-icon'></i></a>
                    <ul class="dropdown__menu">
                        <li class="menu__item"><a href="HIS_one-time.php" class="menu__link">One-Time Service</a></li>
                        <li class="menu__item"><a href="HIS_recurring.php" class="menu__link">Recurring Service</a></li>
                    </ul>
                </li>
                
                <li class="menu__item has-dropdown open">
                    <a href="#" class="menu__link active-parent" data-content="feedback-parent"><i class='bx bx-star'></i> Feedback/Ratings <i class='bx bx-chevron-down arrow-icon'></i></a>
                    <ul class="dropdown__menu">
                        <li class="menu__item"><a href="FR_one-time.php" class="menu__link active">One-Time Service</a></li>
                        <li class="menu__item"><a href="FR_recurring.php" class="menu__link">Recurring Service</a></li>
                    </ul>
                </li>
                
                <li class="menu__item"><a href="client_profile.php" class="menu__link" data-content="profile"><i class='bx bx-user'></i> My Profile</a></li>
                <li class="menu__item"><a href="javascript:void(0)" class="menu__link" data-content="logout" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
            </ul>
        </aside>

    <main class="dashboard__content">
        
        <div class="history-header-container">
        <div class="header-text-group">
            <h2 class="main-title">
                <i class='bx bx-star'></i> One-Time Service Feedback/Ratings
            </h2>
            <p class="page-description">
            List of all your Completed one-time cleaning service bookings where you can leave or edit your feedback and ratings.
            </p>
        </div>

        <div class="info-button-container">
                <button class="info-button">
                    <i class='bx bx-info-circle'></i> Info
                </button>
                
                <div class="info-tooltip-content">
                    <h3><i class='bx bx-info-circle'></i> Guidelines</h3>
                    
                    <ul>
                        <li>
                        Feedback and Ratings can only be given for <strong>Completed</strong> bookings.
                        </li>

                        <li>
                            Please rate both the service quality and the assigned employees.
                            <ul>
                                <li> Be honest and specific — your feedback helps us improve our services. </li>
                                <li> Once submitted, you can still edit your feedback if needed. </li>
                            </ul>
                                <li>For any major concerns, kindly contact us via <strong>WhatsApp</strong> using the <strong>Chat</strong> button. (<i class='bx bxl-whatsapp'></i>)</li>
                        </li>
                        <li>
                        Thank you for trusting us and our services! 
                        </li>
                    </ul>
                    
                    <p style="font-size: 1em; color: #333; margin-top: 15px; line-height: 1.6;">
                        Please refer to the <a href="waiver.html" target="_blank" style="color: #004a80; font-weight: 600; text-decoration: underline;">Service Waiver</a> for full guidelines and additional terms.
                    </p>

                </div>
                </div>
            </div>
        
        <div class="horizontal-tabs-container">
            <div class="service-tabs-bar">
                <button class="tab-button active" onclick="openTab(event, 'checkout-cleaning')">
                    <i class='bx bx-check-shield'></i> Checkout Cleaning
                </button>
                <button class="tab-button" onclick="openTab(event, 'in-house-cleaning')">
                    <i class='bx bx-home-heart'></i> In-House Cleaning
                </button>
                <button class="tab-button" onclick="openTab(event, 'refresh-cleaning')">
                    <i class='bx bx-wind'></i> Refresh Cleaning
                </button>
                <button class="tab-button" onclick="openTab(event, 'deep-cleaning')">
                    <i class='bx bx-water'></i> Deep Cleaning
                </button>
                <!-- <button class="tab-button" onclick="openTab(event, 'ratings-summary')">
                    <i class='bx bx-stats'></i> Ratings Summary
                </button> -->
                <button class="tab-button" onclick="openTab(event, 'issues-concern')">
                    <i class='bx bx-error-alt'></i> Issues and Concern
                </button>
            </div>
        </div>

        <?php 
        // Function to render booking cards
      
function renderBookingCard($booking) {
    // Generate reference number if not exists
   $refNo = generateRefNo($booking['id'], $booking['service_date']);


    // Check if has rating
    $hasRating = isset($booking['rating_stars']) && !empty($booking['rating_stars']);

    // Check if has issue
    $hasIssue = isset($booking['issue_type']) && !empty($booking['issue_type']);

    // Final price (ensure it exists in booking array)
    $finalPrice = isset($booking['final_price']) ? $booking['final_price'] : 0;

    $searchTerms = $refNo . ' ' . formatDate($booking['service_date']) . ' ' . formatTime($booking['service_time']) . ' ' .
                   htmlspecialchars($booking['address'] ?? '') . ' ' . htmlspecialchars($booking['client_type'] ?? '') .
                   ' COMPLETED' . ($hasRating ? ' RATED' : '');
    ?>
    <div class="appointment-list-item" 
    data-issue-type="<?= htmlspecialchars($booking['issue_type'] ?? '') ?>"
data-issue-description="<?= htmlspecialchars($booking['issue_description'] ?? '') ?>"
data-submission-date="<?= htmlspecialchars($booking['issue_report_date'] ?? '') ?>"
data-submission-time="<?= htmlspecialchars($booking['issue_report_time'] ?? '') ?>"
data-photo1="<?= htmlspecialchars($booking['issue_photo1'] ?? '') ?>"
data-photo2="<?= htmlspecialchars($booking['issue_photo2'] ?? '') ?>"
data-photo3="<?= htmlspecialchars($booking['issue_photo3'] ?? '') ?>"
    data-booking-id="<?= htmlspecialchars($booking['id']) ?>"
    data-date="<?= htmlspecialchars($booking['service_date']) ?>" 
    data-time="<?= htmlspecialchars($booking['service_time']) ?>"
    data-duration="<?= htmlspecialchars($booking['duration'] ?? 'N/A') ?>"
    data-client-type="<?= htmlspecialchars($booking['client_type'] ?? 'N/A') ?>"
    data-service-type="<?= htmlspecialchars($booking['service_type'] ?? 'N/A') ?>"
    data-address="<?= htmlspecialchars($booking['address'] ?? 'N/A') ?>"
    data-search-terms="<?= htmlspecialchars($searchTerms) ?>"
    data-property-layout="<?= htmlspecialchars($booking['property_type'] ?? 'N/A') ?>"
    data-materials-required="<?= htmlspecialchars($booking['materials_provided'] ?? 'No') ?>"
    data-materials-description="<?= htmlspecialchars($booking['materials_needed'] ?? 'N/A') ?>"
    data-additional-request="<?= htmlspecialchars($booking['comments'] ?? 'None') ?>"
    data-image-1="<?= htmlspecialchars($booking['media1'] ?? '') ?>"
    data-image-2="<?= htmlspecialchars($booking['media2'] ?? '') ?>"
    data-image-3="<?= htmlspecialchars($booking['media3'] ?? '') ?>"
    data-has-feedback="<?= $hasRating ? 'true' : 'false' ?>"
    data-rating-stars="<?= htmlspecialchars($booking['rating_stars'] ?? '0') ?>" 
    data-rating-feedback="<?= htmlspecialchars($booking['rating_comment'] ?? 'No feedback provided.') ?>"
    data-status="COMPLETED"
    data-ref-no="<?= htmlspecialchars($refNo) ?>"
    data-has-issue="<?= $hasIssue ? 'true' : 'false' ?>">
    
    <div class="button-group-top">
        <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))">
            <i class='bx bx-show'></i> View Details
        </a>

        <?php if ($hasRating): ?>
        <button type="button" class="action-btn feedback-btn" onclick="showViewRatingModal(this.closest('.appointment-list-item'))">
            <i class='bx bx-star'></i> View Rating
        </button>
        <?php else: ?>
        <a href="FR_one-time_form.php?id=<?= $booking['id']; ?>&action=leave" class="action-btn feedback-btn">
            <i class='bx bx-star'></i> Rate Now
        </a>
        <?php endif; ?>



            

           

          <div class="dropdown-menu-container">
    <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
    <ul class="dropdown-menu">
        <?php if ($hasRating): ?>
        
        <?php endif; ?>
        <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
       <?php 
$hasIssue = isset($booking['issue_type']) && !empty($booking['issue_type']);
if ($hasIssue): 
?>
<li>
    <a href="#" 
       class="report-link" 
       onclick="event.preventDefault(); showReportModal(this); return false;"
       data-booking-id="<?= htmlspecialchars($booking['id']) ?>"
       data-ref-no="<?= htmlspecialchars($refNo) ?>"
       data-date="<?= htmlspecialchars($booking['service_date']) ?>"
       data-time="<?= htmlspecialchars($booking['service_time']) ?>"
       data-has-issue="true"
       data-issue-type="<?= htmlspecialchars($booking['issue_type']) ?>"
       data-issue-description="<?= htmlspecialchars($booking['issue_description']) ?>">
       <i class='bx bx-edit-alt'></i> Edit Report
    </a>
</li>
        <?php else: ?>
        <li>
            <a href="#" 
               class="report-link" 
               onclick="event.preventDefault(); showReportModal(this); return false;"
               data-booking-id="<?= htmlspecialchars($booking['id']) ?>"
               data-ref-no="<?= htmlspecialchars($refNo) ?>"
               data-date="<?= htmlspecialchars($booking['service_date']) ?>"
               data-time="<?= htmlspecialchars($booking['service_time']) ?>">
               <i class='bx bx-error-alt'></i> Report Issue
            </a>
        </li>
        <?php endif; ?>
    </ul>
</div>
        </div>

        <div class="appointment-details">
            <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value"><?= htmlspecialchars($refNo) ?></span></p>
            <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> <?= formatDate($booking['service_date']) ?></p>
            <p><i class='bx bx-time'></i> <strong>Time:</strong> <?= formatTime($booking['service_time']) ?></p>
            <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> <?= htmlspecialchars($booking['duration'] ?? 'N/A') ?></p>
            <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> <?= htmlspecialchars($booking['address'] ?? 'N/A') ?></p>
            <hr class="divider full-width-detail">
            <p><i class='bx bx-building-house'></i> <strong>Client Type:</strong> <?= htmlspecialchars($booking['client_type'] ?? 'N/A') ?></p>
            <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> <?= htmlspecialchars($booking['service_type'] ?? 'N/A') ?></p>
            <p class="full-width-detail status-detail">
                <strong>Status:</strong>
                <span class="status-tag completed"><i class='bx bx-check-circle'></i> COMPLETED</span>
                <?php if ($hasRating): ?>
                <span class="status-tag rated-style" style="background-color: #fce899; color: #9c6c00; border: 1px solid #f9d857;">
                    <i class='bx bx-star'></i> RATED
                </span>
                <?php else: ?>
                <span class="status-tag pending"><i class='bx bx-hourglass'></i> NOT YET RATED</span>
                <?php endif; ?>
            </p>

            <?php if (!empty($booking['assigned_driver']) || !empty($booking['assigned_cleaners'])): ?>
            <div class="staff-details-container full-width-detail">
                <h4><i class='bx bx-id-card'></i> Assigned Team</h4>
                <?php if (!empty($booking['assigned_driver'])): ?>
                <p><i class='bx bx-car'></i> <strong>Driver:</strong> <?= htmlspecialchars($booking['assigned_driver']) ?></p>
                <?php endif; ?>
                <?php if (!empty($booking['assigned_cleaners'])): ?>
                <p><i class='bx bx-group'></i> <strong>Cleaners:</strong> <?= htmlspecialchars($booking['assigned_cleaners']) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <p class="price-detail">
    Final Price: <span class="aed-color">AED <?= number_format($booking['final_price'], ) ?></span>
</p>


        </div>
    </div>
    <?php
} // End of function
?>



        <!-- Checkout Cleaning Tab -->
        <div id="checkout-cleaning" class="tab-content" style="display: block;">
            
            <div class="filter-controls-tab">
                
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'checkout-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'checkout-cleaning-list')">
                </div>
                
            </div>

            <div class="appointment-list-container" id="checkout-cleaning-list">
                <div class="no-appointments-message" data-service-name="Checkout Cleaning"></div> 
                
                <?php 
                if (count($bookings_by_type['Checkout Cleaning']) > 0) {
                    foreach ($bookings_by_type['Checkout Cleaning'] as $booking) {
                        renderBookingCard($booking);
                    }
                }
                ?>
                
            </div>
        </div>

        <!-- In-House Cleaning Tab -->
        <div id="in-house-cleaning" class="tab-content" style="display: none;">
            <div class="filter-controls-tab">
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'in-house-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'in-house-cleaning-list')">
                </div>
            </div>

            <div class="appointment-list-container" id="in-house-cleaning-list">
                <div class="no-appointments-message" data-service-name="In-House Cleaning"></div>
                
                <?php 
                if (count($bookings_by_type['In-House Cleaning']) > 0) {
                    foreach ($bookings_by_type['In-House Cleaning'] as $booking) {
                        renderBookingCard($booking);
                    }
                }
                ?>
            </div>
        </div>

        <!-- Refresh Cleaning Tab -->
        <div id="refresh-cleaning" class="tab-content" style="display: none;">
            <div class="filter-controls-tab">
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'refresh-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'refresh-cleaning-list')">
                </div>
            </div>

            <div class="appointment-list-container" id="refresh-cleaning-list">
                <div class="no-appointments-message" data-service-name="Refresh Cleaning"></div>
                
                <?php 
                if (count($bookings_by_type['Refresh Cleaning']) > 0) {
                    foreach ($bookings_by_type['Refresh Cleaning'] as $booking) {
                        renderBookingCard($booking);
                    }
                }
                ?>
            </div>
        </div>

        <!-- Deep Cleaning Tab -->
        <div id="deep-cleaning" class="tab-content" style="display: none;">
            <div class="filter-controls-tab">
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'deep-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'deep-cleaning-list')">
                </div>
            </div>

            <div class="appointment-list-container" id="deep-cleaning-list">
                 <div class="no-appointments-message" data-service-name="Deep Cleaning"></div>
                
                <?php 
                if (count($bookings_by_type['Deep Cleaning']) > 0) {
                    foreach ($bookings_by_type['Deep Cleaning'] as $booking) {
                        renderBookingCard($booking);
                    }
                }
                ?>
            </div>
        </div>

        <!-- Ratings Summary Tab -->
        <div id="ratings-summary" class="tab-content" style="display: none;">
            <div class="summary-container">
                <h3><i class='bx bx-bar-chart-alt-2'></i> Overall Ratings Summary</h3>
                
                <p style="font-size: 1.0em; color: #555; margin-bottom: 25px;">
                    This section provides a consolidated overview of all your submitted ratings for One-Time Services.
                </p>

                <div class="summary-content-wrapper">
                    <div class="summary-card">
                        <div class="average-rating-display">
                            <i class='bx bxs-star'></i>
                            <span class="rating-value">0.0 / 5.0</span>
                        </div>

                        <p class="average-rating-label">
                            Average Rating Across All Services
                        </p>
                        
                        <div class="stats-grid-container">
                            <div class="stat-tile rated-services-tile">
                                <div class="tile-icon-group">
                                    <i class='bx bx-check-circle'></i>
                                </div>
                                <span class="tile-value rated-count">0</span> <span class="tile-label">Rated Services</span>
                            </div>

                            <div class="stat-tile highest-rating-tile">
                                <div class="tile-icon-group">
                                    <i class='bx bxs-medal'></i>
                                </div>
                                <span class="tile-value highest-rating">0<small> Stars</small></span>
                                <span class="tile-label">Highest Rating</span>
                            </div>

                            <div class="stat-tile awaiting-rating-tile">
                                <div class="tile-icon-group">
                                    <i class='bx bx-hourglass'></i>
                                </div>
                                <span class="tile-value awaiting-rating">0</span>
                                <span class="tile-label">Awaiting Rating</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="breakdown-container">
                        <h4>Ratings Breakdown by Service Type</h4>
                        <ul class="ratings-breakdown-list">
                            <li>
                                <strong>Checkout Cleaning:</strong> 
                                <span class="no-ratings">No Ratings Yet</span>
                            </li>
                            <li>
                                <strong>In-House Cleaning:</strong> 
                                <span class="no-ratings">No Ratings Yet</span>
                            </li>
                            <li>
                                <strong>Refresh Cleaning:</strong> 
                                <span class="no-ratings">No Ratings Yet</span>
                            </li>
                            <li>
                                <strong>Deep Cleaning:</strong> 
                                <span class="no-ratings">No Ratings Yet</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="detailed-ratings-list-container">
                    <h4>All Submitted Ratings (0 Total)</h4>
                    
                    <div class="filter-controls-tab" style="margin-bottom: 20px;">
                        <select class="service-type-filter-dropdown" onchange="filterRatingsSummary(null, this.value)">
                            <option value="all" selected>All Service Types</option>
                            <option value="Checkout Cleaning">Checkout Cleaning</option>
                            <option value="In-House Cleaning">In-House Cleaning</option>
                            <option value="Refresh Cleaning">Refresh Cleaning</option>
                            <option value="Deep Cleaning">Deep Cleaning</option>
                        </select>
                        
                        <div class="search-container">
                            <i class='bx bx-search'></i>
                            <input type="text" placeholder="Search Ref No, Service or Feedback..." onkeyup="filterRatingsSummary(this.value, null)">
                        </div>
                    </div>
                    
                    <!-- Dynamic ratings list will be loaded here -->
                </div>
            </div>
        </div>

       <!-- Issues and Concern Tab -->
<div id="issues-concern" class="tab-content" style="display: none;">
    <div class="summary-container">
        <h3><i class='bx bx-error-alt'></i> Submitted Issues and Concerns</h3>
        
        <p class="page-description" style="font-size: 1.0em; color: #555; margin-bottom: 25px;">
            This section lists all the issues and concerns you have reported for your completed one-time services.
        </p>
        
        <!-- FILTERS -->
        <div class="filter-controls-tab" style="margin-bottom: 20px;">
            <select class="service-type-filter-dropdown" onchange="filterIssues(null, this.value)">
                <option value="all" selected>All Service Types</option>
                <option value="Checkout Cleaning">Checkout Cleaning</option>
                <option value="In-House Cleaning">In-House Cleaning</option>
                <option value="Refresh Cleaning">Refresh Cleaning</option>
                <option value="Deep Cleaning">Deep Cleaning</option>
            </select>
            
            <div class="search-container">
                <i class='bx bx-search'></i>
                <input type="text" placeholder="Search Reference No or Issue Type..." onkeyup="filterIssues(this.value, null)">
            </div>
        </div>

        <!-- ISSUE LIST - Display already reported issues from database -->
        <div class="issue-list-container">
            <?php
            // Fetch bookings with reported issues only
            $client_email = $_SESSION['email'];
            $sql_issues = "SELECT * FROM bookings 
                    WHERE status = 'Completed' 
                    AND booking_type = 'One-Time'
                    AND email = '" . $conn->real_escape_string($client_email) . "'
                    AND issue_type IS NOT NULL AND issue_type != ''
                    ORDER BY service_date DESC";

            $res_issues = $conn->query($sql_issues);

            if ($res_issues && $res_issues->num_rows > 0) {
                while ($row = $res_issues->fetch_assoc()) {
                    $refNo = generateRefNo($row['id'], $row['service_type'], $row['service_date']);
            ?>
                <div class="issue-card" 
     data-booking-id="<?= htmlspecialchars($row['id']) ?>"
     data-service-type="<?php echo htmlspecialchars($row['service_type']); ?>"
     data-ref-no="<?= htmlspecialchars($refNo) ?>"
     data-service-date="<?= htmlspecialchars($row['service_date']) ?>"
     data-service-time="<?= htmlspecialchars($row['service_time']) ?>"
     data-submission-date="<?= htmlspecialchars($row['submission_date'] ?? 'N/A') ?>"
     data-submission-time="<?= htmlspecialchars($row['submission_time'] ?? 'N/A') ?>"
     data-issue-type="<?= htmlspecialchars($row['issue_type']) ?>"
     data-issue-description="<?= htmlspecialchars($row['issue_description']) ?>"
     data-photo1="<?= htmlspecialchars($row['issue_photo1'] ?? '') ?>"
     data-photo2="<?= htmlspecialchars($row['issue_photo2'] ?? '') ?>"
     data-photo3="<?= htmlspecialchars($row['issue_photo3'] ?? '') ?>"
     data-search-terms="<?php echo htmlspecialchars($refNo . ' ' . $row['issue_type']); ?>">
    
    <h4><?php echo htmlspecialchars($row['service_type']); ?></h4>
    <p><strong>Ref No:</strong> <?php echo htmlspecialchars($refNo); ?></p>
    <p><strong>Date:</strong> <?php echo formatDate($row['service_date']); ?> at <?php echo formatTime($row['service_time']); ?></p>
    <p><strong>Issue Type:</strong> <span class="issue-type-badge" style="background-color: #ffeaa7; color: #d63031; padding: 3px 8px; border-radius: 4px; font-size: 0.9em;"><?php echo htmlspecialchars($row['issue_type']); ?></span></p>
    
    <button type="button" class="issue-btn"
        onclick="viewIssueDetails(this)"
        data-booking-id="<?= htmlspecialchars($row['id']) ?>">
        <i class='bx bx-error'></i> View Details
    </button>
</div>
            <?php
                }
            } else {
            ?>
                <div class="no-issues-message" style="text-align: center; padding: 30px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; color: #777;">
                    <i class='bx bx-folder-open' style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                    <p style="font-size: 1.1em; margin: 10px 0;">No reported issues found.</p>
                    <p style="font-size: 0.95em; color: #999;">Issues can be reported from the dropdown menu on each completed booking.</p>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<!-- View Issue Details Modal -->
<div class="report-modal" id="viewIssueDetailsModal" onclick="if(event.target.id === 'viewIssueDetailsModal') closeModal('viewIssueDetailsModal')">
    <div class="report-modal-content" style="max-width: 600px;">
        <span class="report-close-btn" onclick="closeModal('viewIssueDetailsModal')">&times;</span> 
        <h3>Reported Issue Details</h3>
        
        <div class="issue-details-content">
            <p class="report-ref-display">
                <strong>Reference No:</strong> <span id="view-issue-ref" class="report-ref-value"></span>
            </p>
            
            <div class="issue-details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                <div>
                    <label style="font-weight: bold; color: #555; font-size: 0.9em;">Service Date</label>
                    <p id="view-issue-service-date" style="margin: 5px 0;">N/A</p>
                </div>
                <div>
                    <label style="font-weight: bold; color: #555; font-size: 0.9em;">Service Time</label>
                    <p id="view-issue-service-time" style="margin: 5px 0;">N/A</p>
                </div>
                <div>
                    <label style="font-weight: bold; color: #555; font-size: 0.9em;">Date Reported</label>
                    <p id="view-issue-submission-date" style="margin: 5px 0;">N/A</p>
                </div>
                <div>
                    <label style="font-weight: bold; color: #555; font-size: 0.9em;">Time Reported</label>
                    <p id="view-issue-submission-time" style="margin: 5px 0;">N/A</p>
                </div>
            </div>
            
            <hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
            
            <div style="margin: 15px 0;">
                <label style="font-weight: bold; color: #555; font-size: 0.9em;">Issue Type</label>
                <p id="view-issue-type-detail" style="margin: 5px 0; padding: 8px; background: #ffeaa7; color: #d63031; border-radius: 4px; display: inline-block;">N/A</p>
            </div>
            
            <div style="margin: 15px 0;">
                <label style="font-weight: bold; color: #555; font-size: 0.9em;">Description</label>
                <div id="view-issue-description-detail" style="margin: 5px 0; padding: 12px; background: #f7f7f7; border-left: 3px solid #007bff; border-radius: 4px; line-height: 1.6;">
                    No description provided.
                </div>
            </div>
            
            <div style="margin: 15px 0;">
                <label style="font-weight: bold; color: #555; font-size: 0.9em;">Attachments</label>
                <div id="view-issue-attachments-detail" style="margin: 10px 0;">
                    <!-- Attachments will be populated here -->
                </div>
            </div>
            
            <hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
            
            <div style="text-align: center; padding: 15px; background: #fff3cd; border-radius: 6px; border: 1px solid #ffc107;">
                <i class='bx bx-info-circle' style="font-size: 1.5em; color: #856404;"></i>
                <p style="margin: 10px 0 0 0; color: #856404; font-size: 0.95em;">
                    <strong>Status:</strong> Under Review - Our team will contact you shortly.
                </p>
            </div>
        </div>
    </div>
</div>




<!-- End of Issues and Concern Tab -->
        
    </main>
</div>


<!-- End of Issues and Concern Tab -->
        
    </main>
</div> 

<a href="#header" id="backToTopBtn" title="Back to Top"><i class='bx bx-up-arrow-alt'></i> Back to Top</a>

<a href="#header" id="backToTopBtn" title="Back to Top"><i class='bx bx-up-arrow-alt'></i> Back to Top</a>

<!-- Logout Modal -->
<div id="logoutModal" class="modal">
<div class="modal__content">
<h3 class="modal__title">Are you sure you want to log out? </h3>
<div class="modal__actions">
<button id="cancelLogout" class="btn btn--secondary">Cancel</button>
<button id="confirmLogout" class="btn btn--primary">Log Out</button>
</div>
</div>
</div>

<!-- Date Picker Modal -->
<div class="modal date-picker-modal" id="datePickerModal" onclick="if(event.target.id === 'datePickerModal') closeModal('datePickerModal')">
<div class="modal-content date-picker-content">
<h3>Select Custom Date Range</h3>
 
<label for="startDate">Start Date:</label>
<input type="date" id="startDate"> 
<label for="endDate">End Date:</label>
<input type="date" id="endDate">

    <div id="dateRangeError" class="error-message"></div>
 
    <div class="button-group-wrapper" style="margin-top: 20px;">
        <button onclick="applyCustomRange(this.getAttribute('data-list-id'))" data-list-id="checkout-cleaning-list" class="primary-btn">Apply</button>
        <button onclick="closeModal('datePickerModal')" class="secondary-btn">Cancel</button>
    </div>
</div>
</div>

<!-- Details Modal -->
<div class="modal" id="detailsModal" onclick="if(event.target.id === 'detailsModal') closeModal('detailsModal')">
     <div class="modal-content" style="width:600px;">
        <span class="close-btn" onclick="closeModal('detailsModal')">&times;</span> 
        <h3><i class='bx bx-file-text'></i> Appointment Details</h3>
        <div id="modal-details-content"></div>
    </div>
</div>

<!-- Report Issue Modal -->
<!-- Report Issue Modal -->
<div class="report-modal" id="reportIssueModal" onclick="if(event.target.id === 'reportIssueModal') closeModal('reportIssueModal')">
    <div class="report-modal-content">
        <span class="report-close-btn" onclick="closeModal('reportIssueModal')">&times;</span> 
        <h3>Report an Issue</h3>
        
        <p class="report-ref-display">
            Reference No: <span id="report-ref-number" class="report-ref-value"></span>
        </p>

        <form class="report-form" method="POST" action="report_issue_process.php" enctype="multipart/form-data">
            <input type="hidden" name="report-booking-id" id="reportBookingId">
            <input type="hidden" id="submissionDate" name="submissionDate">
            <input type="hidden" id="submissionTime" name="submissionTime">

            <div class="report-date-time-group">
                <div>
                    <label for="issueDate">Date:</label>
                    <input type="date" id="issueDate" name="issueDate" readonly required>
                </div>
                <div>
                    <label for="issueTime">Time:</label>
                    <input type="time" id="issueTime" name="issueTime" readonly required>
                </div>
            </div>

            <label for="issueType">Type of Issue:</label>
            <select id="issueType" name="issueType" onchange="clearError(this)" required>
                <option value="" disabled selected>Select Issue Category</option>
                <option value="Property Damage">Property Damage</option>
                <option value="Unsatisfied with Quality of Cleaning">Unsatisfied with Quality of Cleaning</option>
                <option value="Staff was Late/No Show">Staff was Late/No Show</option>
                <option value="Staff Behavior/Professionalism">Staff Behavior/Professionalism</option>
                <option value="Billing/Payment Issue">Billing/Payment Issue</option>
                <option value="Other">Other</option>
            </select>
            <div id="issueTypeError" class="error-message">Please complete this required field</div>

            <label for="issueDetails">Issue Description:</label>
            <textarea id="issueDetails" name="issueDetails" placeholder="Please provide detailed description of the issue." oninput="clearError(this)" required></textarea>
            <div id="issueDetailsError" class="error-message">Please complete this required field</div>

            <label>Attachment (up to 3 files - Photos/Videos):</label>
            <div class="attachment-group">
                <div class="custom-file-input-wrapper">
                    <input type="file" id="attachment1" name="attachment1" accept="image/*,video/*" onchange="updateFileName(this)">
                    <div class="custom-file-button">Choose File</div>
                    <div class="custom-file-text" id="file-name-1">No file chosen</div>
                </div>
                <div class="custom-file-input-wrapper">
                    <input type="file" id="attachment2" name="attachment2" accept="image/*,video/*" onchange="updateFileName(this)">
                    <div class="custom-file-button">Choose File</div>
                    <div class="custom-file-text" id="file-name-2">No file chosen</div>
                </div>
                <div class="custom-file-input-wrapper">
                    <input type="file" id="attachment3" name="attachment3" accept="image/*,video/*" onchange="updateFileName(this)">
                    <div class="custom-file-button">Choose File</div>
                    <div class="custom-file-text" id="file-name-3">No file chosen</div>
                </div>
            </div>

            <div class="form-submit-wrapper">
                <button type="submit">Submit Report</button>
            </div>
        </form>
    </div>
</div>


<!-- Report Success Modal -->
<div class="report-modal" id="reportSuccessModal" onclick="if(event.target.id === 'reportSuccessModal') closeModal('reportSuccessModal')">
    <div class="report-modal-content" style="max-width: 400px; text-align: center;">
        <div style="padding: 20px;">
            <i class='bx bx-check-circle' style="font-size: 4em; color: #00A86B; margin-bottom: 10px;"></i>
            <h3 style="border-bottom: none; margin-bottom: 10px;">Report Submitted!</h3>
            
            <p id="success-message" style="color: #555; font-size: 1em;">
                Your issue report for Ref: <span id="submitted-ref-number" style="color: #B32133; font-weight: 700;"></span> has been received and is under review.
            </p>
            
            <button onclick="closeModal('reportSuccessModal'); location.reload();" class="primary-btn report-confirm-btn">
                Got It
            </button>
        </div>
    </div>
</div><script>
console.log("🚀 Script loaded");

document.addEventListener('DOMContentLoaded', function() {
    attachFormSubmitHandler();
});

// Function to update report button after successful submission
function updateReportButtonToEdit(bookingId, issueType, issueDescription) {
    console.log("🔄 Updating report button for booking:", bookingId);
    
    // Find ALL report links with this booking ID across ALL tabs
    const reportLinks = document.querySelectorAll(`a.report-link[data-booking-id="${bookingId}"]`);
    
    console.log(`📍 Found ${reportLinks.length} report link(s) to update`);
    
    reportLinks.forEach((link, index) => {
        console.log(`Updating link ${index + 1}...`);
        
        // Find parent card to update its data attributes too
        const parentCard = link.closest('.appointment-list-item');
        if (parentCard) {
            parentCard.setAttribute('data-has-issue', 'true');
            parentCard.setAttribute('data-issue-type', issueType);
            parentCard.setAttribute('data-issue-description', issueDescription);
        }
        
        // Update link data attributes
        link.setAttribute('data-has-issue', 'true');
        link.setAttribute('data-issue-type', issueType);
        link.setAttribute('data-issue-description', issueDescription);
        
        // ⭐ CHANGED: Keep the same onclick to allow editing
        // DON'T add view-issue-link class
        // DON'T change onclick to viewReportedIssue
        
        // Update icon (keep same icon)
        const icon = link.querySelector('i');
        if (icon) {
            icon.className = 'bx bx-edit-alt'; // ⭐ Change to edit icon
        }
        
        // ⭐ CHANGED: Update text to "Edit Report"
        link.innerHTML = '<i class="bx bx-edit-alt"></i> Edit Report';
        
        console.log(`✅ Link ${index + 1} updated to "Edit Report"`);
    });
    
    console.log("✅ All report buttons updated across all tabs");
}

// Show report modal function
function showReportModal(button) {
    console.log("=== showReportModal called ===");

    // Get booking ID
    let bookingId = button.getAttribute("data-booking-id");
    if (!bookingId) {
        const card = button.closest('.appointment-list-item, .issue-card');
        if (card) bookingId = card.getAttribute("data-booking-id");
    }

    if (!bookingId) {
        alert("Error: Booking ID missing.");
        return;
    }
    

    // Set hidden input
    const hiddenInput = document.getElementById("reportBookingId");
    if (!hiddenInput) {
        console.error("❌ Hidden input not found!");
        alert("Error: Form configuration error");
        return;
    }
    hiddenInput.value = bookingId;
    console.log("✅ Set booking ID:", bookingId);

    // Get reference data
    let refNo = button.getAttribute("data-ref-no") || "";
    let date = button.getAttribute("data-date") || "";
    let time = button.getAttribute("data-time") || "";

    const card = button.closest('.appointment-list-item, .issue-card');
    if (card) {
        if (!refNo) refNo = card.getAttribute("data-ref-no") || "N/A";
        if (!date) date = card.getAttribute("data-date") || "";
        if (!time) time = card.getAttribute("data-time") || "";
    }

    document.getElementById("report-ref-number").textContent = refNo;
    document.getElementById("issueDate").value = date;
    document.getElementById("issueTime").value = time;

    // Submission datetime
    const now = new Date();
    document.getElementById("submissionDate").value = now.toISOString().slice(0, 10);
    document.getElementById("submissionTime").value = now.toTimeString().slice(0, 8);

    // ⭐ Check if button has data-has-issue="true" OR if parent card has issue data
    const submitBtn = document.querySelector('.report-form button[type="submit"]');
    let hasIssue = false;

    // Check button's data-has-issue attribute
    if (button.getAttribute('data-has-issue') === 'true') {
        hasIssue = true;
    }

    // OR check parent card
    if (!hasIssue && card) {
        const cardHasIssue = card.getAttribute('data-has-issue');
        const issueType = card.getAttribute("data-issue-type");
        const issueDesc = card.getAttribute("data-issue-description");
        
        if (cardHasIssue === 'true' || issueType || issueDesc) {
            hasIssue = true;
        }
    }

    if (hasIssue) {
        console.log("🔁 Edit Report Mode - Prefilling existing data");
        submitBtn.textContent = "Update Report";

        if (card) {
            const issueTypeVal = card.getAttribute("data-issue-type") || button.getAttribute('data-issue-type') || "";
            const issueDescVal = card.getAttribute("data-issue-description") || button.getAttribute('data-issue-description') || "";
            
            document.getElementById("issueType").value = issueTypeVal;
            document.getElementById("issueDetails").value = issueDescVal;
            
            const issueReportDate = card.getAttribute("data-submission-date");
            const issueReportTime = card.getAttribute("data-submission-time");
            
            if (issueReportDate) document.getElementById("issueDate").value = issueReportDate;
            if (issueReportTime) document.getElementById("issueTime").value = issueReportTime;
        }
    } else {
        console.log("🆕 New Report Mode");
        submitBtn.textContent = "Submit Report";

        document.getElementById("issueType").value = "";
        document.getElementById("issueDetails").value = "";
        
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => input.value = '');
        document.querySelectorAll('.custom-file-text').forEach(el => {
            el.textContent = 'No file chosen';
        });
    }

    document.getElementById("reportIssueModal").style.display = "flex";
    console.log("✅ Modal opened");
}

// Form submit handler with AJAX
function attachFormSubmitHandler() {
    const reportForm = document.querySelector('.report-form');
    
    if (reportForm) {
        console.log("✅ Form validation attached");
        
        reportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            console.log("=== FORM SUBMITTING (AJAX) ===");
            
            const bookingIdInput = document.querySelector('input[name="report-booking-id"]');
            const issueType = document.getElementById('issueType');
            const issueDetails = document.getElementById('issueDetails');
            
            // Validate
            let isValid = true;
            
            if (!bookingIdInput || !bookingIdInput.value.trim()) {
                console.error("❌ Booking ID is empty!");
                alert("❌ Error: Booking ID is missing!");
                return false;
            }
            
            console.log("📋 Booking ID being submitted:", bookingIdInput.value);
            
            if (!issueType.value) {
                document.getElementById('issueTypeError').style.display = 'block';
                isValid = false;
            }
            
            if (!issueDetails.value.trim()) {
                document.getElementById('issueDetailsError').style.display = 'block';
                isValid = false;
            }
            
            if (!isValid) {
                return false;
            }
            
            console.log("✅ Validation passed, submitting via AJAX...");
            
            // Store values for button update
            const bookingId = bookingIdInput.value;
            const issueTypeValue = issueType.value;
            const issueDetailsValue = issueDetails.value;
            const refNumber = document.getElementById('report-ref-number').textContent;
            
            // Create FormData
            const formData = new FormData(reportForm);
            
            // Show loading
            const submitButton = reportForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';
            
            // Send AJAX request
            fetch('report_issue_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log("📥 Server response:", data);
                
                // Re-enable button
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
                
                if (data.success) {
                    console.log("✅ Report submitted successfully!");
                    
                    // ⭐ UPDATE THE BUTTON IMMEDIATELY
                    updateReportButtonToEdit(bookingId, issueTypeValue, issueDetailsValue);
                    
                    // Close report modal
                    closeModal('reportIssueModal');
                    
                    // Update success modal
                    document.getElementById('submitted-ref-number').textContent = refNumber;
                    
                    // Show success modal
                    document.getElementById('reportSuccessModal').style.display = 'flex';
                } else {
                    alert('Error: ' + (data.message || 'Failed to submit report'));
                }
            })
            .catch(error => {
                console.error("❌ AJAX Error:", error);
                
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
                
                alert('Network error. Please check your connection and try again.');
            });
            
            return false;
        });
    } else {
        console.error("❌ Form not found!");
    }
}

// Helper functions
function clearError(element) {
    const errorId = element.id + 'Error';
    const errorElement = document.getElementById(errorId);
    if (errorElement) {
        errorElement.style.display = 'none';
    }
}

function updateFileName(input) {
    const fileNameId = 'file-name-' + input.id.slice(-1);
    const fileNameDisplay = document.getElementById(fileNameId);
    
    if (input.files && input.files[0]) {
        fileNameDisplay.textContent = input.files[0].name;
    } else {
        fileNameDisplay.textContent = 'No file chosen';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
    
    if (modalId === 'reportIssueModal') {
        const form = document.querySelector('.report-form');
        if (form) form.reset();
        document.querySelectorAll('.custom-file-text').forEach(el => {
            el.textContent = 'No file chosen';
        });
        document.querySelectorAll('.error-message').forEach(el => {
            el.style.display = 'none';
        });
    }
    
    // DON'T reload page - let the button update show
    if (modalId === 'reportSuccessModal') {
        console.log("✅ Success modal closed, button should now show 'Edit Report'");
    }
}
// Add this function to your JavaScript code (in FR_function2.js or inline script)

function viewIssueDetails(button) {
    console.log("🔍 View Issue Details clicked");
    
    // Get the issue card element
    const issueCard = button.closest('.issue-card');
    
    if (!issueCard) {
        console.error("❌ Issue card not found");
        alert("Error: Could not load issue details");
        return;
    }
    
    // Extract data from the card
    const refNo = issueCard.getAttribute('data-ref-no') || 'N/A';
    const serviceDate = issueCard.getAttribute('data-service-date') || 'N/A';
    const serviceTime = issueCard.getAttribute('data-service-time') || 'N/A';
    const submissionDate = issueCard.getAttribute('data-submission-date') || 'N/A';
    const submissionTime = issueCard.getAttribute('data-submission-time') || 'N/A';
    const issueType = issueCard.getAttribute('data-issue-type') || 'N/A';
    const issueDescription = issueCard.getAttribute('data-issue-description') || 'No description provided.';
    const photo1 = issueCard.getAttribute('data-photo1') || '';
    const photo2 = issueCard.getAttribute('data-photo2') || '';
    const photo3 = issueCard.getAttribute('data-photo3') || '';
    
    console.log("📋 Issue Data:", { refNo, issueType, issueDescription });
    
    // Populate the modal with data
    document.getElementById('view-issue-ref').textContent = refNo;
    document.getElementById('view-issue-service-date').textContent = formatDate(serviceDate);
    document.getElementById('view-issue-service-time').textContent = formatTime(serviceTime);
    document.getElementById('view-issue-submission-date').textContent = formatDate(submissionDate);
    document.getElementById('view-issue-submission-time').textContent = formatTime(submissionTime);
    document.getElementById('view-issue-type-detail').textContent = issueType;
    document.getElementById('view-issue-description-detail').textContent = issueDescription;
    
    // Handle attachments
    const attachmentsContainer = document.getElementById('view-issue-attachments-detail');
    attachmentsContainer.innerHTML = ''; // Clear previous content
    
    const photos = [photo1, photo2, photo3].filter(p => p && p.trim() !== '');
    
    if (photos.length > 0) {
        photos.forEach((photo, index) => {
            const imgWrapper = document.createElement('div');
            imgWrapper.style.cssText = 'display: inline-block; margin: 5px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;';
            
            const img = document.createElement('img');
            img.src = 'uploads/issues/' + photo;
            img.alt = `Attachment ${index + 1}`;
            img.style.cssText = 'max-width: 150px; max-height: 150px; display: block; cursor: pointer;';
            img.onclick = function() {
                window.open(this.src, '_blank');
            };
            
            imgWrapper.appendChild(img);
            attachmentsContainer.appendChild(imgWrapper);
        });
    } else {
        attachmentsContainer.innerHTML = '<p style="color: #999; font-style: italic;">No attachments uploaded</p>';
    }
    
    // Show the modal
    document.getElementById('viewIssueDetailsModal').style.display = 'flex';
    console.log("✅ Modal opened");
}

// Helper function to format date (if not already defined)
function formatDate(dateString) {
    if (!dateString || dateString === 'N/A') return 'N/A';
    
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Helper function to format time (if not already defined)
function formatTime(timeString) {
    if (!timeString || timeString === 'N/A') return 'N/A';
    
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}
</script>



<!-- View Issue Details Modal -->
<div class="report-modal" id="viewIssueDetailsModal" onclick="if(event.target.id === 'viewIssueDetailsModal') closeModal('viewIssueDetailsModal')">
    <div class="report-modal-content" style="max-width: 600px;">
        <span class="report-close-btn" onclick="closeModal('viewIssueDetailsModal')">&times;</span> 
        <h3>Reported Issue Details</h3>
        
        <div class="issue-details-content">
            <p class="report-ref-display">
                <strong>Reference No:</strong> <span id="view-issue-ref" class="report-ref-value"></span>
            </p>
            
            <div class="issue-details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                <div>
                    <label style="font-weight: bold; color: #555; font-size: 0.9em;">Service Date</label>
                    <p id="view-issue-service-date" style="margin: 5px 0;">N/A</p>
                </div>
                <div>
                    <label style="font-weight: bold; color: #555; font-size: 0.9em;">Service Time</label>
                    <p id="view-issue-service-time" style="margin: 5px 0;">N/A</p>
                </div>
                <div>
                    <label style="font-weight: bold; color: #555; font-size: 0.9em;">Date Reported</label>
                    <p id="view-issue-submission-date" style="margin: 5px 0;">N/A</p>
                </div>
                <div>
                    <label style="font-weight: bold; color: #555; font-size: 0.9em;">Time Reported</label>
                    <p id="view-issue-submission-time" style="margin: 5px 0;">N/A</p>
                </div>
            </div>
            
            <hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
            
            <div style="margin: 15px 0;">
                <label style="font-weight: bold; color: #555; font-size: 0.9em;">Issue Type</label>
                <p id="view-issue-type-detail" style="margin: 5px 0; padding: 8px; background: #ffeaa7; color: #d63031; border-radius: 4px; display: inline-block;">N/A</p>
            </div>
            
            <div style="margin: 15px 0;">
                <label style="font-weight: bold; color: #555; font-size: 0.9em;">Description</label>
                <div id="view-issue-description-detail" style="margin: 5px 0; padding: 12px; background: #f7f7f7; border-left: 3px solid #007bff; border-radius: 4px; line-height: 1.6;">
                    No description provided.
                </div>
            </div>
            
            <div style="margin: 15px 0;">
                <label style="font-weight: bold; color: #555; font-size: 0.9em;">Attachments</label>
                <div id="view-issue-attachments-detail" style="margin: 10px 0;">
                    <!-- Attachments will be populated here -->
                </div>
            </div>
            
            <hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
            
            <div style="text-align: center; padding: 15px; background: #fff3cd; border-radius: 6px; border: 1px solid #ffc107;">
                <i class='bx bx-info-circle' style="font-size: 1.5em; color: #856404;"></i>
                <p style="margin: 10px 0 0 0; color: #856404; font-size: 0.95em;">
                    <strong>Status:</strong> Under Review - Our team will contact you shortly.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- View Rating Modal -->
<div class="report-modal" id="viewRatingModal" onclick="if(event.target.id === 'viewRatingModal') closeModal('viewRatingModal')">
    <div class="report-modal-content" style="max-width: 650px;">
        <span class="close-btn" onclick="closeModal('viewRatingModal')">&times;</span> 

        <div style="padding: 25px; text-align: left;">
            <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 20px;"><i class='bx bx-star'></i> Service Rating Details</h3>
            
            <p style="margin-bottom: 5px;"><strong>Reference No:</strong> <span id="viewRefNo" style="color: #B32133; font-weight: 700;"></span></p>
            
            <div id="viewAppointmentDetails" style="margin-top: 10px; margin-bottom: 15px;"></div>

            <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;"> 
            
            <div class="rating-info-container" style="display: flex; flex-direction: column; gap: 15px;">
                <div class="rating-stars-section">
                    <p style="font-size: 1.1em; margin-bottom: 5px;">
                        <strong>Rating:</strong>
                        <span id="viewStarsContainer" style="color: #FFC107; margin-left: 10px;"></span> 
                    </p>
                </div>
                
                <div class="feedback-section">
    <p style="font-size: 1.1em; margin-bottom: 5px;"><strong>Feedback:</strong></p>
    <div id="viewFeedback" 
         style="border: 1px solid #ddd; padding: 15px; border-radius: 6px; background-color: #f9f9f9; color: #333; line-height: 1.5;">
        <em>No feedback provided yet.</em>
    </div>
    
</div>
</div>
            </div>
            
          <a id="editRatingLinkInModal"
   href="FR_one-time_form.php?id=<?= $booking['id'] ?>&edit_rating=1"
   class="primary-btn"
   style="background-color: white;">
   Edit Rating
</a>



            </div>
        </div>
    </div>
</div>

<!-- View Reported Issue Modal -->
<div class="report-modal" id="viewReportedIssueModal" onclick="if(event.target.id === 'viewReportedIssueModal') closeModal('viewReportedIssueModal')">
    <div class="report-modal-content" style="max-width: 600px;">
        <span class="report-close-btn" onclick="closeModal('viewReportedIssueModal')">&times;</span> 

        <h3 class="modal-title-issue-details">Reported Issue Details</h3>
        <div class="issue-ref-header">
            <p>
                <strong>Reference No:</strong> 
                <span id="view-issue-ref-number" class="ref-no-value-large"></span>
            </p>
        </div>
        
        <div class="issue-details-grid">
            <div class="detail-group">
                <label>Date of Service</label>
                <p id="view-issue-incident-date" class="detail-value">N/A</p>
            </div>
            <div class="detail-group">
                <label>Scheduled Time</label>
                <p id="view-issue-scheduled-time" class="detail-value">N/A</p>
            </div>
            
            <div class="detail-group">
                <label>Date Reported</label>
                <p id="view-issue-report-date" class="detail-value">N/A</p>
            </div>
            <div class="detail-group">
                <label>Time Reported</label>
                <p id="view-issue-report-time" class="detail-value">N/A</p>
            </div>
            
            <hr class="grid-separator"> 
        </div>

        <div class="issue-type-section">
            <label>Type of Issue</label>
            <p id="view-issue-type" class="issue-type-tag">N/A</p>
        </div>
        
        <div class="issue-description-section">
            <label>Description</label>
            <p id="view-issue-description" class="description-content">No description provided.</p>
        </div>
        
        <div class="issue-attachments-section">
            <label>Attachments (up to 3 files)</label>
            <div id="view-issue-attachments" class="attachments-list"></div>
        </div>
        
        <div class="issue-status-section">
            <label>Current Status</label>
            <span id="view-issue-status" class="status-tag">In Progress</span>
        </div>
        
        <div class="issue-modal-footer"></div>
    </div>
</div>

<!-- Required Fields Modal -->
<div id="requiredFieldsModal" class="modal">
<div class="modal__content">
<h3 class="modal__title">Please fill out all required fields.</h3>
<div class="modal__actions">
<button class="btn btn--primary" id="confirmRequiredFields" onclick="closeModal('requiredFieldsModal')">OK</button>
</div>
</div>
</div>

<script src="FR_function.js"></script> 
<script src="FR_function2.js"></script> 
<script src="client_db.js"></script> 	 	 	
<script src="HIS_function.js"></script> 

</body>
</html>