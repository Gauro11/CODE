<?php
include 'connection.php';
session_start();

// ✅ ENABLE DEBUG MODE - Set to true to see diagnostic information
$DEBUG_MODE = false; // Change to false after checking

// ✅ Ensure employee is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='employee_login.html';</script>";
    exit;
}

// Get logged-in employee's ID
$employeeEmail = $_SESSION['email'];
$employeeQuery = "SELECT id, first_name, last_name, position FROM employees WHERE email = ?";
$stmt = $conn->prepare($employeeQuery);
$stmt->bind_param("s", $employeeEmail);
$stmt->execute();
$employeeResult = $stmt->get_result();
$employee = $employeeResult->fetch_assoc();

if (!$employee) {
    echo "<script>alert('Employee not found.'); window.location.href='employee_login.html';</script>";
    exit;
}

$employeeId = $employee['id'];
$employeeName = $employee['first_name'] . ' ' . $employee['last_name'];
$employeePosition = $employee['position'] ?? 'N/A';

// ==================== DEBUG SECTION ====================
if ($DEBUG_MODE) {
    echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px; border: 2px solid #333; border-radius: 10px; font-family: monospace;'>";
    echo "<h2 style='color: #d32f2f;'>🔍 DEBUG MODE ENABLED</h2>";
    echo "<p><strong>Logged-in Employee ID:</strong> " . $employeeId . "</p>";
    echo "<p><strong>Employee Email:</strong> " . $employeeEmail . "</p>";
    echo "<p><strong>Employee Name:</strong> " . $employeeName . "</p>";
    echo "<p><strong>Employee Position:</strong> " . $employeePosition . "</p>";
    echo "<hr style='margin: 20px 0;'>";
    
    // Check all bookings that might contain this employee
    $debugQuery = "
        SELECT 
            id, 
            service_date, 
            service_type, 
            booking_type,
            cleaners, 
            drivers,
            status
        FROM bookings 
        WHERE cleaners IS NOT NULL OR drivers IS NOT NULL
        ORDER BY service_date DESC
        LIMIT 10
    ";
    $debugResult = $conn->query($debugQuery);
    
    echo "<h3>📋 Sample Bookings (Last 10 with assignments):</h3>";
    echo "<table style='width: 100%; border-collapse: collapse; background: white;'>";
    echo "<tr style='background: #333; color: white;'>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Booking ID</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Date</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Type</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Cleaners (Raw)</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Drivers (Raw)</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Match?</th>";
    echo "</tr>";
    
    $foundMatch = false;
    while ($row = $debugResult->fetch_assoc()) {
        // Check if employee name appears in cleaners or drivers
        $cleanersLower = strtolower($row['cleaners'] ?? '');
        $driversLower = strtolower($row['drivers'] ?? '');
        $employeeNameLower = strtolower($employeeName);
        
        $isMatch = (strpos($cleanersLower, $employeeNameLower) !== false) || 
                   (strpos($driversLower, $employeeNameLower) !== false);
        
        if ($isMatch) $foundMatch = true;
        
        $rowColor = $isMatch ? 'background: #c8e6c9;' : '';
        echo "<tr style='$rowColor'>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['service_date'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['booking_type'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>[" . ($row['cleaners'] ?? 'NULL') . "]</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>[" . ($row['drivers'] ?? 'NULL') . "]</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>" . ($isMatch ? '✅ YES' : '❌ NO') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (!$foundMatch) {
        echo "<p style='color: #d32f2f; font-weight: bold; margin-top: 20px;'>⚠️ WARNING: No bookings found for: $employeeName</p>";
    } else {
        echo "<p style='color: #4caf50; font-weight: bold; margin-top: 20px;'>✅ Found matching bookings! (highlighted in green)</p>";
    }
    
    // Additional debug: Show query results
    echo "<hr style='margin: 20px 0;'>";
    echo "<h3>🔎 Query Debug Info:</h3>";
    echo "<p><strong>Today's Date:</strong> $today</p>";
    echo "<p><strong>7 Days Later:</strong> $sevenDaysLater</p>";
    
    // Test the actual queries being used
    $testQuery = "
        SELECT id, service_date, booking_type, status, cleaners, drivers
        FROM bookings 
        WHERE service_date >= '$today'
        AND (
            cleaners LIKE '%$employeeName%'
            OR drivers LIKE '%$employeeName%'
        )
        ORDER BY service_date ASC
        LIMIT 5
    ";
    $testResult = $conn->query($testQuery);
    
    echo "<p><strong>Upcoming appointments query returned:</strong> " . $testResult->num_rows . " rows</p>";
    
    if ($testResult->num_rows > 0) {
        echo "<table style='width: 100%; border-collapse: collapse; background: white; margin-top: 10px;'>";
        echo "<tr style='background: #4CAF50; color: white;'>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>ID</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Date</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Type</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Status</th>";
        echo "</tr>";
        while($row = $testResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['service_date'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['booking_type'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: #d32f2f;'>❌ No upcoming appointments found (all matched bookings are in the past or filtered out)</p>";
    }
    
    echo "</div>";
}

// ==================== DASHBOARD STATISTICS ====================
$today = date('Y-m-d');
$sevenDaysLater = date('Y-m-d', strtotime('+7 days'));

// MODIFIED QUERIES - Now searching by FULL NAME instead of ID
$todayQuery = "
    SELECT COUNT(*) as total 
    FROM bookings 
    WHERE service_date = ? 
    AND (
        cleaners LIKE CONCAT('%', ?, '%')
        OR drivers LIKE CONCAT('%', ?, '%')
    )
";
$stmt = $conn->prepare($todayQuery);
$stmt->bind_param("sss", $today, $employeeName, $employeeName);
$stmt->execute();
$todayCount = $stmt->get_result()->fetch_assoc()['total'];

$upcomingQuery = "
    SELECT COUNT(*) as total 
    FROM bookings 
    WHERE service_date BETWEEN ? AND ?
    AND status = 'Confirmed'
    AND (
        cleaners LIKE CONCAT('%', ?, '%')
        OR drivers LIKE CONCAT('%', ?, '%')
    )
";
$stmt = $conn->prepare($upcomingQuery);
$stmt->bind_param("ssss", $today, $sevenDaysLater, $employeeName, $employeeName);
$stmt->execute();
$upcomingCount = $stmt->get_result()->fetch_assoc()['total'];

$pendingQuery = "
    SELECT COUNT(*) as total 
    FROM bookings 
    WHERE status = 'Pending'
    AND (
        cleaners LIKE CONCAT('%', ?, '%')
        OR drivers LIKE CONCAT('%', ?, '%')
    )
";
$stmt = $conn->prepare($pendingQuery);
$stmt->bind_param("ss", $employeeName, $employeeName);
$stmt->execute();
$pendingCount = $stmt->get_result()->fetch_assoc()['total'];

// ==================== FETCH UPCOMING ONE-TIME APPOINTMENTS ====================
$oneTimeQuery = "
    SELECT * FROM bookings 
    WHERE booking_type = 'One-Time'
    AND service_date >= ?
    AND (
        cleaners LIKE CONCAT('%', ?, '%')
        OR drivers LIKE CONCAT('%', ?, '%')
    )
    ORDER BY service_date ASC, service_time ASC
    LIMIT 5
";
$stmt = $conn->prepare($oneTimeQuery);
$stmt->bind_param("sss", $today, $employeeName, $employeeName);
$stmt->execute();
$oneTimeResult = $stmt->get_result();

// ==================== FETCH UPCOMING RECURRING APPOINTMENTS ====================
$recurringQuery = "
    SELECT * FROM bookings 
    WHERE booking_type = 'Recurring'
    AND service_date >= ?
    AND (
        cleaners LIKE CONCAT('%', ?, '%')
        OR drivers LIKE CONCAT('%', ?, '%')
    )
    ORDER BY service_date ASC, service_time ASC
    LIMIT 5
";
$stmt = $conn->prepare($recurringQuery);
$stmt->bind_param("sss", $today, $employeeName, $employeeName);
$stmt->execute();
$recurringResult = $stmt->get_result();

// Helper function to format reference number
function formatRefNo($id, $serviceType, $date) {
    $serviceCode = '';
    if (strpos(strtolower($serviceType), 'deep') !== false) $serviceCode = 'DC';
    elseif (strpos(strtolower($serviceType), 'general') !== false) $serviceCode = 'GC';
    elseif (strpos(strtolower($serviceType), 'move') !== false) $serviceCode = 'MC';
    else $serviceCode = 'OT';
    
    $yearMonth = date('ym', strtotime($date));
    return "ALZ-{$serviceCode}-{$yearMonth}-" . str_pad($id, 4, '0', STR_PAD_LEFT);
}

// Helper function to calculate price
function calculatePrice($materialsProvided, $duration) {
    preg_match('/(\d+(\.\d+)?)/', $materialsProvided, $matches);
    $rate = isset($matches[1]) ? (float)$matches[1] : 0;
    $hours = (float)$duration;
    return $rate * $hours;
}

// Helper function to generate status badge
function getStatusBadge($status) {
    $statusLower = strtolower($status);
    $badgeClass = '';
    
    switch($statusLower) {
        case 'pending':
            $badgeClass = 'pending';
            break;
        case 'confirmed':
            $badgeClass = 'confirmed';
            break;
        case 'ongoing':
            $badgeClass = 'ongoing';
            break;
        case 'completed':
            $badgeClass = 'completed';
            break;
        case 'cancelled':
            $badgeClass = 'cancelled';
            break;
        default:
            $badgeClass = 'pending';
    }
    
    return '<span class="status-badge ' . $badgeClass . '">' . htmlspecialchars($status) . '</span>';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ALAZIMA - Employee Dashboard</title>
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="client_db.css">
<style>
.view-all-container {
text-align: center;
margin-top: 20px;
}
.view-all-link {
display: inline-block;
padding: 10px 20px;
background-color: #f1f1f1;
color: #333;
text-decoration: none;
font-weight: bold;
border-radius: 8px;
transition: background-color 0.3s, color 0.3s;
border: 1px solid #ddd;
}
.view-all-link:hover {
background-color: #e0e0e0;
color: #000;
}
.dashboard__content {
overflow-y: auto;
height: 100%;
}
.dashboard__summary {
overflow-y: auto;
max-height: 500px;
padding-right: 15px;
}
.dashboard__summary::-webkit-scrollbar { width: 10px; }
.dashboard__summary::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 5px; }
.dashboard__summary::-webkit-scrollbar-thumb { background: #888; border-radius: 5px; }
.dashboard__summary::-webkit-scrollbar-thumb:hover { background: #555; }
#backToTopBtn {
display: none;
position: fixed;
bottom: 25px;
right: 25px;
z-index: 99;
border: none;
outline: none;
background-color: #3f51b5;
color: white;
cursor: pointer;
padding: 12px 18px;
border-radius: 50px;
font-size: 1rem;
font-weight: bold;
text-transform: uppercase;
letter-spacing: 0.5px;
box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
transition: background-color 0.3s, transform 0.2s, opacity 0.3s;
opacity: 0;
text-decoration: none;
align-items: center;
justify-content: center;
}
#backToTopBtn:hover {
background-color: #5363b9;
transform: translateY(-3px);
}
#backToTopBtn i {
margin-right: 8px;
font-size: 1.2em;
}
.summary-cards-container {
display: flex;
flex-direction: row;
flex-wrap: wrap;
gap: 20px;
padding-bottom: 20px;
justify-content: center;
}
.summary-card {
border-radius: 15px;
box-shadow: 0 4px 15px rgba(0,0,0,0.1);
padding: 25px;
flex: 1;
min-width: 280px;
max-width: 350px;
background-color: #FFFFFF;
border-top: 8px solid;
position: relative;
overflow: hidden;
display: flex;
flex-direction: column;
justify-content: space-between;
min-height: 200px;
color: #333;
}
.summary-card.service-summary {
    border-top-color: #6A5ACD; 
}
.summary-card.pending-feedback {
    border-top-color: #FF9800;
}
.summary-card.quick-actions {
    border-top-color: #4CAF50;
}
.summary-card .card-icon {
position: absolute;
bottom: -20px;
right: -20px;
font-size: 8em;
color: rgba(0, 0, 0, 0.08);
z-index: 1;
}
.summary-card h3 {
margin-top: 0;
margin-bottom: 10px;
font-size: 1.5rem;
font-weight: bold;
color: #333;
}
.summary-card.service-summary h3 {
    color: #333; 
    border-bottom: 2px solid #6A5ACD; 
    padding-bottom: 5px; 
    margin-bottom: 15px; 
}
.service-summary table {
width: 100%;
border-collapse: collapse;
margin-top: 10px;
}
.service-summary table td {
padding: 6px 0;
text-align: left;
color: #555;
font-size: 1.2em;
}
.service-summary table td.count {
    text-align: right;
    font-weight: bold;
    color: #6A5ACD;
}
.pending-feedback .feedback-link {
display: flex;
align-items: center;
justify-content: center;
background-color: #FF9800;
color: white;
text-decoration: none;
border-radius: 5px;
padding: 12px 20px;
transition: background-color 0.3s, transform 0.2s;
margin-top: 10px;
}
.pending-feedback .feedback-link:hover {
background-color: #e68900;
transform: translateY(-2px);
}
.pending-feedback .feedback-link i {
margin-right: 8px;
}
.quick-actions a {
display: flex;
align-items: center;
justify-content: flex-start;
background-color: #4CAF50;
padding: 10px 15px;
border-radius: 5px;
text-decoration: none;
color: white;
font-size: 0.95em;
transition: background-color 0.3s, transform 0.2s;
margin-bottom: 10px;
}
.quick-actions a:hover {
background-color: #45a049;
transform: translateY(-2px);
}
.quick-actions a i {
margin-right: 10px;
font-size: 1.2rem;
color: white;
}
.quick-actions ul {
list-style: none;
padding: 0;
margin: 0;
}
.dashboard__container {
background-color: #ffffff;
border-radius: 15px;
box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
padding: 30px;
margin-top: 30px;
margin-bottom: 20px;
}
.dashboard__container.upcoming-container {
    border-top: 8px solid #007bff; 
}
.container-title {
    font-size: 1.5em;
    color: #333;
    margin-bottom: 20px; 
    text-align: left;
    display: flex; 
    align-items: center; 
    gap: 12px; 
}
.container-title i {
    font-size: 1.5em; 
    color: #007bff; 
}
.container-title strong {
    font-weight: bold;
}
.appointment-list-item {
background-color: #ffffff;
border-radius: 12px;
box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
padding: 18px;
margin-bottom: 15px;
transition: transform 0.3s, box-shadow 0.3s;
border: 1px solid #ddd;
position: relative;
}
.appointment-list-item:hover {
transform: translateY(-3px);
box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
}
.appointment-details {
display: grid;
grid-template-columns: repeat(2, 1fr);
gap: 5px 20px; 
margin-bottom: 15px;
}
.appointment-details p {
display: flex;
align-items: center;
gap: 10px;
margin: 0; 
font-size: 0.95em;
color: #555;
word-break: break-word;
}
.appointment-details p i {
    color: #007bff;
}
.button-group-top {
position: absolute;
top: 15px;
right: 15px;
display: flex;
gap: 8px;
align-items: center;
}
.action-btn {
padding: 8px 12px;
font-weight: bold;
cursor: pointer;
border-radius: 6px;
text-align: center;
transition: all 0.3s;
text-decoration: none;
display: flex;
align-items: center;
gap: 5px;
font-size: 0.9em;
}
.view-details-btn {
    background-color: transparent;
    border: 2px solid #007bff; 
    color: #007bff; 
}
.view-details-btn:hover {
    background-color: #007bff;
    color: white;
}
.call-btn {
    background-color: #0056b3; 
    border: 2px solid #0056b3;
    color: white;
}
.call-btn:hover {
    background-color: #0062cc; 
    border-color: #0062cc;
}
.dropdown-menu-container {
    position: relative;
}
.more-options-btn {
    background: #f1f1f1;
    color: #333;
    border: 1px solid #ddd;
    padding: 8px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1.1em;
    line-height: 1;
    transition: background 0.2s;
}
.more-options-btn:hover {
    background: #e0e0e0;
}
.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: #ffffff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    z-index: 10;
    min-width: 150px;
    padding: 5px 0;
    display: none;
    list-style: none;
}
.dropdown-menu.show {
    display: block;
}
.dropdown-menu li {
    padding: 0;
}
.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    text-decoration: none;
    color: #333;
    font-size: 0.9em;
    transition: background-color 0.2s;
    width: 100%;
    box-sizing: border-box;
    font-weight: 500;
}
.dropdown-menu a:hover {
    background-color: #f7f7f7;
}
.dropdown-menu a i {
    font-size: 1.1em;
}
.dropdown-menu .edit-link i {
    color: #4CAF50;
}
.dropdown-menu .cancel-link i {
    color: #B32133;
}
.appointment-list-item .divider {
border: 0;
height: 1px;
background-color: #ccc;
margin: 15px 0;
width: 100%;
}
.appointment-details .full-width-detail {
grid-column: 1 / -1;
}
.appointment-details .duration-detail {
grid-column: 2;
justify-self: start;
}
.appointment-details .service-type-detail {
grid-column: 2;
}
.appointment-details .price-detail {
grid-column: 2;
justify-self: end;
font-weight: bold;
color: #333; 
font-size: 1.2em;
}
.appointment-details .price-detail span.aed-color {
  color: #333; 
}
.appointment-details .status-detail {
grid-column: 1 / -1;
}
.appointment-details .ref-no-detail {
    color: #333;
}
.appointment-details .ref-no-value {
    color: #B32133;
    font-weight: bold;
}
.appointment-details p.recurring-details {
grid-column: 2;
margin: 0;
justify-self: start;
align-items: center;
gap: 10px;
}
.appointment-details p.recurring-details:nth-of-type(2) {
grid-column: 1;
}
.appointment-details p.recurring-details:nth-of-type(3) {
grid-column: 2;
justify-self: start;
}
.no-data-message {
    text-align: center;
    padding: 40px 20px;
    color: #999;
    font-size: 1.1em;
}
.no-data-message i {
    font-size: 3em;
    margin-bottom: 15px;
    display: block;
    color: #ccc;
}
</style>
</head>
<body>
<header class="header" id="header">
<nav class="nav container">
<a href="?content=dashboard" class="nav__logo">
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
<li class="menu__item">
<a href="?content=dashboard" class="menu__link" data-content="dashboard">
<i class='bx bx-home-alt-2'></i> Dashboard
</a>
</li>
<li class="menu__item has-dropdown">
    <a href="#" class="menu__link" data-content="appointments-parent">
        <i class='bx bx-calendar-check'></i> My Appointments <i class='bx bx-chevron-down arrow-icon'></i>
    </a>
    <ul class="dropdown__menu">
        <li class="menu__item">
            <a href="EMP_appointments_today.php" class="menu__link" data-content="today-appointments">Today's Appointments</a>
        </li>
        <li class="menu__item">
            <a href="EMP_appointments_history.php" class="menu__link" data-content="appointment-history">History</a>
        </li>
    </ul>
</li>
<li class="menu__item">
<a href="EMP_ratings_feedback.php" class="menu__link" data-content="ratings">
<i class='bx bx-star'></i> Ratings/Feedback
</a>
</li>

<!-- <li class="menu__item">
<a href="?content=profile" class="menu__link" data-content="profile">
<i class='bx bx-user'></i> My Profile -->
</a>
</li>
<li class="menu__item"><a href="?content=logout" class="menu__link" data-content="logout"><i class='bx bx-log-out'></i> Logout</a></li>
</ul>
</aside>
<main class="dashboard__content">
<section id="dashboard" class="content__section active">
<h2 class="section__title">Welcome back, <?php echo htmlspecialchars($employeeName); ?>!</h2>
<p class="welcome__message">Here's a quick overview of your upcoming tasks and work information. You can check your schedule, request time off, or update your profile from the menu.</p>
<div class="summary-cards-container">
<div class="summary-card service-summary">
<div class="card-content">
<h3>Today's Tasks</h3>
<table>
<tr>
<td>Total Appointments Today:</td>
<td class="count"><?php echo $todayCount; ?></td>
</tr>
<tr>
<td>Upcoming in 7 Days:</td>
<td class="count"><?php echo $upcomingCount; ?></td>
</tr>
<tr>
<td>Pending Start:</td>
<td class="count"><?php echo $pendingCount; ?></td>
</tr>
</table>
</div>
<i class='bx bx-clipboard card-icon'></i>
</div>
<div class="summary-card pending-feedback">
<div class="card-content">
<!-- <h3>Pending Time Off</h3>
<p>You have 0 pending Time Off requests.</p>
<a href="#" class="feedback-link"><i class='bx bx-time-five'></i> View Request Status</a>
</div> -->
<i class='bx bx-bell card-icon'></i>
</div>
<div class="summary-card quick-actions">
<div class="card-content">
<h3>Quick Links</h3>
<ul>
<!-- <li><a href="EMP_timeoff_request.php"><i class='bx bx-calendar-exclamation'></i> Request Time Off</a></li> -->
<li><a href="EMP_appointments_history.php"><i class='bx bx-list-check'></i> View Appointment History</a></li>

</ul>
</div>
<i class='bx bx-cog card-icon'></i>
</div>
</div>

<!-- ONE-TIME APPOINTMENTS -->
<div class="dashboard__container upcoming-container one-time-container">
    <div class="container-title">
        <i class='bx bx-calendar'></i> My Upcoming <strong>One-Time</strong> Tasks
    </div>
    <div class="appointment-list-container">
        <?php if($oneTimeResult->num_rows > 0): ?>
            <?php while($booking = $oneTimeResult->fetch_assoc()): 
                $refNo = formatRefNo($booking['id'], $booking['service_type'], $booking['service_date']);
                $price = calculatePrice($booking['materials_provided'], $booking['duration']);
            ?>
            <div class="appointment-list-item">
                <div class="button-group-top">
                    <!-- <a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="action-btn view-details-btn"><i class='bx bx-show'></i> View</a> -->
                    <a href="tel:<?php echo $booking['phone']; ?>" class="action-btn call-btn"><i class='bx bx-phone'></i> Call Client</a>
                    <div class="dropdown-menu-container">
                        <button class="more-options-btn"><i class='bx bx-dots-vertical-rounded'></i></button>
                        <ul class="dropdown-menu">
                            <li><a href="#" class="edit-link"><i class='bx bx-play-circle'></i> Start Job</a></li>
                            <li><a href="#" class="cancel-link"><i class='bx bx-check-circle'></i> Complete Job</a></li>
                        </ul>
                    </div>
                </div>
                <div class="appointment-details">
                    <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value"><?php echo $refNo; ?></span></p>
                    <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['service_date'])); ?></p>
                    <p><i class='bx bx-time'></i> <strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['service_time'])); ?></p>
                    <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> <?php echo $booking['duration']; ?> hours</p>
                    <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> <?php echo htmlspecialchars($booking['address']); ?></p>
                    <hr class="divider full-width-detail">
                    <p><i class='bx bx-buildings'></i> <strong>Client Type:</strong> <?php echo htmlspecialchars($booking['client_type']); ?></p>
                    <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> <?php echo htmlspecialchars($booking['service_type']); ?></p>
                    <p class="full-width-detail status-detail"><i class='bx bx-info-circle'></i> <strong>Status:</strong> <?php echo getStatusBadge($booking['status']); ?></p>
                    <p class="price-detail">Client Pays: <span class="aed-color">AED <?php echo number_format($price, 2); ?></span></p>

                    <!-- ADD THESE LINES -->
    <?php if(!empty($booking['cleaners'])): ?>
    <p class="full-width-detail"><i class='bx bx-user-check'></i> <strong>Cleaners:</strong> <?php echo htmlspecialchars($booking['cleaners']); ?></p>
    <?php endif; ?>
    <?php if(!empty($booking['drivers'])): ?>
    <p class="full-width-detail"><i class='bx bx-car'></i> <strong>Drivers:</strong> <?php echo htmlspecialchars($booking['drivers']); ?></p>
    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data-message">
                <i class='bx bx-calendar-x'></i>
                <p>No upcoming one-time appointments assigned to you.</p>
            </div>
        <?php endif; ?>
        
        <?php if($oneTimeResult->num_rows > 0): ?>
        <div class="view-all-container">
            <a href="EMP_appointments_today.php" class="view-all-link">See More...</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- RECURRING APPOINTMENTS -->
<div class="dashboard__container upcoming-container recurring-container">
    <div class="container-title">
        <i class='bx bx-repeat'></i> My Upcoming <strong>Recurring</strong> Tasks
    </div>
    <div class="appointment-list-container">
        <?php if($recurringResult->num_rows > 0): ?>
            <?php while($booking = $recurringResult->fetch_assoc()): 
                $refNo = formatRefNo($booking['id'], $booking['service_type'], $booking['service_date']);
                $price = calculatePrice($booking['materials_provided'], $booking['duration']);
                $dayName = date('l', strtotime($booking['service_date']));
            ?>
            <div class="appointment-list-item">
                <div class="button-group-top">
                    <a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="action-btn view-details-btn"><i class='bx bx-show'></i> View</a>
                    <a href="tel:<?php echo $booking['phone']; ?>" class="action-btn call-btn"><i class='bx bx-phone'></i> Call Client</a>
                    <div class="dropdown-menu-container">
                        <button class="more-options-btn"><i class='bx bx-dots-vertical-rounded'></i></button>
                        <ul class="dropdown-menu">
                            <li><a href="#" class="edit-link"><i class='bx bx-play-circle'></i> Start Job</a></li>
                            <li><a href="#" class="cancel-link"><i class='bx bx-check-circle'></i> Complete Job</a></li>
                        </ul>
                    </div>
                </div>
                <div class="appointment-details">
                    <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value"><?php echo $refNo; ?></span></p>
                    <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['service_date'])); ?></p>
                    <p><i class='bx bx-calendar-week'></i> <strong>Day:</strong> <?php echo $dayName; ?></p>
                    <p class="recurring-details"><i class='bx bx-repeat'></i> <strong>Frequency:</strong> <?php echo htmlspecialchars($booking['frequency']); ?></p>
                    <p><i class='bx bx-time'></i> <strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['service_time'])); ?></p>
                    <p class="recurring-details"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> <?php echo $booking['duration']; ?> hours</p>
                    <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> <?php echo htmlspecialchars($booking['address']); ?></p>
                    <hr class="divider full-width-detail">
                    <p><i class='bx bx-user'></i> <strong>Client Type:</strong> <?php echo htmlspecialchars($booking['client_type']); ?></p>
                    <p class="recurring-details"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> <?php echo htmlspecialchars($booking['service_type']); ?></p>
                    <p class="full-width-detail"><i class='bx bx-info-circle'></i> <strong>Status:</strong> <?php echo htmlspecialchars($booking['status']); ?></p>
                    <p class="price-detail">Client Pays: <span class="aed-color">AED <?php echo number_format($price, 2); ?></span></p>

                    <!-- ADD THESE LINES -->
    <?php if(!empty($booking['cleaners'])): ?>
    <p class="full-width-detail"><i class='bx bx-user-check'></i> <strong>Cleaners:</strong> <?php echo htmlspecialchars($booking['cleaners']); ?></p>
    <?php endif; ?>
    <?php if(!empty($booking['drivers'])): ?>
    <p class="full-width-detail"><i class='bx bx-car'></i> <strong>Drivers:</strong> <?php echo htmlspecialchars($booking['drivers']); ?></p>
    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data-message">
                <i class='bx bx-calendar-x'></i>
                <p>No upcoming recurring appointments assigned to you.</p>
            </div>
        <?php endif; ?>
        
        <?php if($recurringResult->num_rows > 0): ?>
        <div class="view-all-container">
            <a href="EMP_appointments_history.php" class="view-all-link">See More...</a>
        </div>
        <?php endif; ?>
    </div>
</div>
</section>

<section id="profile" class="content__section">
<h2 class="section__title">My Profile</h2>
<div class="profile__edit-form">
<form id="profileForm">
<div class="form-row">
<div class="form-group">
<label for="firstName">First name:</label>
<input type="text" id="firstName" name="firstName" required>
</div>
<div class="form-group">
<label for="lastName">Last name:</label>
<input type="text" id="lastName" name="lastName" required>
</div>
</div>
<div class="form-row">
<div class="form-group">
<label for="birthday">Birthday:</label>
<input type="date" id="birthday" name="birthday" required>
</div>
<div class="form-group">
<label for="contactNumber">Contact Number:</label>
<input type="tel" id="contactNumber" name="contactNumber" required placeholder="+971" pattern="^\+971[0-9]{9}$" title="Please enter a valid UAE number starting with +971 followed by 9 digits.">
</div>
</div>
<div class="form-group full-width">
<label for="email">Email Address: </label>
<input type="email" id="email" name="email" required>
</div>
<div class="form__actions">
<button type="button" class="btn btn--primary" id="editProfileBtn">Edit</button>
<button type="button" class="btn btn--secondary" id="cancelEditBtn">Cancel</button>
</div>
</form>
</div>
</section>
</main>
</div>
<a href="#header" id="backToTopBtn" title="Back to Top"><i class='bx bx-up-arrow-alt'></i> Back to Top</a>
<div id="logoutModal" class="modal">
<div class="modal__content">
<h3 class="modal__title">Are you sure you want to log out?</h3>
<div class="modal__actions">
<button id="cancelLogout" class="btn btn--secondary">Cancel</button>
<button id="confirmLogout" class="btn btn--primary">Log Out</button>
</div>
</div>
</div>
<div class="modal" id="profileSaveModal">
<div class="modal__content">
<h3 class="modal__title">Profile Saved</h3>
<p>Your profile has been updated successfully!</p>
<div class="modal__actions">
<button class="btn btn--primary" id="confirmProfileSave">OK</button>
</div>
</div>
</div>
<div id="requiredFieldsModal" class="modal">
<div class="modal__content">
<h3 class="modal__title">Please fill out all required fields.</h3>
<div class="modal__actions">
<button class="btn btn--primary" id="confirmRequiredFields">OK</button>
</div>
</div>
</div>
<div id="cancelModal" class="modal">
<div class="modal__content">
<h3 class="modal__title">Discard Changes?</h3>
<p>Your unsaved changes will be lost. Continue?</p>
<div class="modal__actions">
<button id="noCancel" class="btn btn--secondary">No</button>
<button id="yesCancel" class="btn btn--primary">Yes</button>
</div>
</div>
</div>
<script src="client_db.js"></script>
<script>
// Toggle function for the ellipsis menu
document.querySelectorAll('.more-options-btn').forEach(button => {
    button.addEventListener('click', function() {
        const menu = this.closest('.dropdown-menu-container').querySelector('.dropdown-menu');
        menu.classList.toggle('show');
    });
});

// Close the dropdown if the user clicks outside of it
window.addEventListener('click', function(e) {
    document.querySelectorAll('.dropdown-menu-container').forEach(container => {
        const button = container.querySelector('.more-options-btn');
        const menu = container.querySelector('.dropdown-menu');
        
        if (!button.contains(e.target) && menu.classList.contains('show')) {
            if (!menu.contains(e.target)) {
                menu.classList.remove('show');
            }
        }
    });
});

// Back to Top button
let mybutton = document.getElementById("backToTopBtn");
window.onscroll = function() {
scrollFunction();
};
function scrollFunction() {
if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
mybutton.style.display = "flex";
setTimeout(() => { mybutton.style.opacity = "1"; }, 10);
} else {
mybutton.style.opacity = "0";
setTimeout(() => { mybutton.style.display = "none"; }, 300);
}
}
mybutton.addEventListener('click', function(e) {
e.preventDefault();
window.scrollTo({
top: 0,
behavior: 'smooth'
});
});
</script>
</body>
</html>