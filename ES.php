<?php
// ES.php
include 'connection.php';
session_start();

// ✅ Ensure user is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

// Get current date and view parameters
$current_date = date('Y-m-d');
$view = isset($_GET['view']) ? $_GET['view'] : 'week'; // 'week' or 'month'
$selected_date = isset($_GET['date']) ? $_GET['date'] : $current_date;
$selected_employee = isset($_GET['employee']) ? $_GET['employee'] : 'all';

// Calculate week start and end
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($selected_date)));
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($selected_date)));

// Calculate month start and end
$month_start = date('Y-m-01', strtotime($selected_date));
$month_end = date('Y-m-t', strtotime($selected_date));

// Fetch all active employees
$employees_sql = "SELECT id, first_name, last_name, position, status 
                  FROM employees 
                  WHERE status = 'Active'
                  ORDER BY position, first_name, last_name";
$employees_result = $conn->query($employees_sql);
if (!$employees_result) {
    die("Error fetching employees: " . $conn->error);
}

// Generate recurring booking occurrences
function generateRecurringOccurrences($booking, $start_date, $end_date) {
    $occurrences = [];
    $frequency = $booking['frequency'];
    $preferred_day = $booking['preferred_day'];
    $booking_start = $booking['start_date'];
    $booking_end = $booking['end_date'] ? $booking['end_date'] : $end_date;
    
    $current = max($booking_start, $start_date);
    $end = min($booking_end, $end_date);
    
    $current_time = strtotime($current);
    $end_time = strtotime($end);
    
    while ($current_time <= $end_time) {
        $day_of_week = date('l', $current_time);
        $should_include = false;
        
        switch ($frequency) {
            case 'Daily':
                $should_include = true;
                break;
            case 'Weekly':
                $should_include = ($day_of_week == $preferred_day);
                break;
            case 'Bi-Weekly':
                if ($day_of_week == $preferred_day) {
                    $weeks_diff = floor((strtotime($current) - strtotime($booking_start)) / (7 * 24 * 60 * 60));
                    $should_include = ($weeks_diff % 2 == 0);
                }
                break;
            case 'Monthly':
                $should_include = (date('j', $current_time) == date('j', strtotime($booking_start)));
                break;
        }
        
        if ($should_include && $current_time >= strtotime($booking_start)) {
            $occurrence = $booking;
            $occurrence['service_date'] = date('Y-m-d', $current_time);
            $occurrence['is_recurring'] = true;
            $occurrences[] = $occurrence;
        }
        
        $current_time = strtotime('+1 day', $current_time);
    }
    
    return $occurrences;
}

// Function to get bookings for date range
function getBookingsForRange($conn, $start_date, $end_date, $employee_name = null) {
    $bookings = [];
    
    $employee_condition = "";
    if ($employee_name && $employee_name != 'all') {
        $employee_name = $conn->real_escape_string($employee_name);
        $employee_condition = "AND (FIND_IN_SET('$employee_name', cleaners) > 0 
                                   OR FIND_IN_SET('$employee_name', drivers) > 0)";
    }
    
    // Get One-Time bookings
    $onetime_sql = "SELECT id, address, full_name, service_type, service_date, service_time, 
                           status, booking_type, cleaners, drivers, duration
                    FROM bookings
                    WHERE booking_type = 'One-Time'
                    AND service_date BETWEEN '$start_date' AND '$end_date'
                    $employee_condition
                    ORDER BY service_date, service_time";
    
    $result = $conn->query($onetime_sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    }
    
    // Get Recurring bookings
    $recurring_sql = "SELECT id, address, full_name, service_type, start_date, end_date, 
                             service_time, status, booking_type, frequency, preferred_day,
                             cleaners, drivers, duration
                      FROM bookings
                      WHERE booking_type = 'Recurring'
                      AND start_date <= '$end_date'
                      AND (end_date IS NULL OR end_date >= '$start_date')
                      $employee_condition
                      ORDER BY start_date, service_time";
    
    $result = $conn->query($recurring_sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $occurrences = generateRecurringOccurrences($row, $start_date, $end_date);
            $bookings = array_merge($bookings, $occurrences);
        }
    }
    
    return $bookings;
}

// Get bookings for selected range
if ($view == 'week') {
    $range_start = $week_start;
    $range_end = $week_end;
} else {
    $range_start = $month_start;
    $range_end = $month_end;
}

$all_bookings = getBookingsForRange($conn, $range_start, $range_end, $selected_employee);

// Handle AJAX request for booking details
if (isset($_GET['action']) && $_GET['action'] == 'get_booking_details' && isset($_GET['booking_id'])) {
    $booking_id = intval($_GET['booking_id']);
    $sql = "SELECT * FROM bookings WHERE id = $booking_id";
    $result = $conn->query($sql);
    
    if ($result && $booking = $result->fetch_assoc()) {
        header('Content-Type: application/json');
        echo json_encode($booking);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Booking not found']);
        exit;
    }
}
// Create schedule grid for week view
$schedule_grid = [];
if ($view == 'week') {
    // Generate days of the week
    $current = strtotime($week_start);
    $days = [];
    for ($i = 0; $i < 7; $i++) {
        $days[] = date('Y-m-d', $current);
        $current = strtotime('+1 day', $current);
    }
    
    // Generate time slots (6 AM to 10 PM, 1-hour slots)
    $time_slots = [];
    for ($hour = 6; $hour <= 22; $hour++) {
        $time_slots[] = sprintf('%02d:00:00', $hour);
    }
    
    // Initialize grid
    foreach ($time_slots as $time) {
        $schedule_grid[$time] = [];
        foreach ($days as $day) {
            $schedule_grid[$time][$day] = [];
        }
    }
    
    // Place bookings in grid
    foreach ($all_bookings as $booking) {
        $date = $booking['service_date'];
        $time = $booking['service_time'];
        
        // Find the appropriate time slot (round to nearest hour)
        $booking_hour = date('H:00:00', strtotime($time));
        
        if (isset($schedule_grid[$booking_hour][$date])) {
            $schedule_grid[$booking_hour][$date][] = $booking;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Scheduling</title>
<link rel="stylesheet" href="admin_dashboard.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="admin_db.css">
<style>
/* Content container */
/* .content-container { 
    background: #fff; 
    border-radius: 12px; 
    padding: 20px; 
    box-shadow: 0 3px 10px rgba(0,0,0,0.1); 
    margin: 20px; 
} */
/* Booking Details Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(5px);
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal__content {
    background: white;
    border-radius: 15px;
    max-width: 700px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    animation: slideUp 0.3s;
}

@keyframes slideUp {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal__header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 25px;
    border-radius: 15px 15px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal__title {
    margin: 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal__close {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s;
}

.modal__close:hover {
    background: rgba(255,255,255,0.3);
}

.modal__body {
    padding: 25px;
}

.detail-section {
    margin-bottom: 25px;
}

.section-title {
    font-size: 16px;
    font-weight: 600;
    color: #004a80;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 8px;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.detail-label {
    font-size: 12px;
    color: #666;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: 14px;
    color: #333;
    font-weight: 500;
}

.detail-value.large {
    font-size: 16px;
    color: #004a80;
}

.detail-value.address {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    border-left: 3px solid #007bff;
}

.employee-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.employee-chip {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.media-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
}

.media-item {
    width: 100%;
    height: 150px;
    border-radius: 8px;
    object-fit: cover;
    cursor: pointer;
    border: 2px solid #e0e0e0;
    transition: transform 0.3s, box-shadow 0.3s;
}

.media-item:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.no-media {
    color: #999;
    font-style: italic;
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.rating-display {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stars {
    color: #ffc107;
    font-size: 20px;
}

.loading-spinner {
    text-align: center;
    padding: 40px;
}

.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
/* View controls */
.view-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    flex-wrap: wrap;
    gap: 15px;
}

.view-tabs {
    display: flex;
    gap: 10px;
}

.view-tab {
    padding: 8px 20px;
    border: 2px solid #007bff;
    background: white;
    color: #007bff;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
}

.view-tab.active {
    background: #007bff;
    color: white;
}

.view-tab:hover {
    background: #0056b3;
    border-color: #0056b3;
    color: white;
}

.date-navigation {
    display: flex;
    align-items: center;
    gap: 15px;
}

.nav-btn {
    padding: 8px 15px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s;
}

.nav-btn:hover {
    background: #0056b3;
}

.date-display {
    font-size: 16px;
    font-weight: 600;
    color: #004a80;
    min-width: 250px;
    text-align: center;
}

.employee-filter {
    display: flex;
    align-items: center;
    gap: 10px;
}

.employee-filter select {
    padding: 8px 15px;
    border: 2px solid #007bff;
    border-radius: 5px;
    font-size: 14px;
    cursor: pointer;
    background: white;
    color: #004a80;
}

/* Schedule Grid - Class Timetable Style */
.schedule-container {
    overflow-x: auto;
    margin-top: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.schedule-grid {
    display: table;
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
    background: white;
}

.schedule-header {
    display: table-row;
    background: linear-gradient( #007bff);
}

.schedule-header-cell {
    display: table-cell;
    padding: 15px 10px;
    text-align: center;
    color: white;
    font-weight: 600;
    border: 1px solid rgba(255,255,255,0.2);
    vertical-align: middle;
}

.time-column {
    width: 80px;
    font-size: 14px;
}

.schedule-row {
    display: table-row;
}

.schedule-row:nth-child(even) {
    background-color: #f9f9f9;
}

.schedule-cell {
    display: table-cell;
    padding: 8px;
    border: 1px solid #ddd;
    vertical-align: top;
    min-height: 60px;
    position: relative;
}

.time-cell {
    background-color: #f4f4f4;
    font-weight: 600;
    color: #004a80;
    text-align: center;
    vertical-align: middle;
    font-size: 13px;
}

.schedule-item {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 6px;
    padding: 8px;
    margin-bottom: 5px;
    color: white;
    font-size: 12px;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
}

.schedule-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.schedule-item.onetime {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.schedule-item.recurring {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.schedule-item.confirmed {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.schedule-item.ongoing {
    background: linear-gradient(135deg, #dbd81fff 0%, #e2d10fff 100%);
}

.schedule-item.completed {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}

.schedule-item.session-complete,
.schedule-item.session-completed,
.schedule-item.sessioncomplete {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important;
}

.schedule-item.pending {
    background: linear-gradient(gray);
}

.schedule-item.active {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.schedule-item.cancelled {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}
.schedule-item.no-show {
    background: linear-gradient(135deg, #795548 0%, #4e342e 100%);
    color: #fff;
}


.schedule-item-time {
    font-weight: 700;
    font-size: 11px;
    margin-bottom: 4px;
    opacity: 0.9;
}

.schedule-item-client {
    font-weight: 600;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.schedule-item-service {
    font-size: 11px;
    opacity: 0.85;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.schedule-item-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(255,255,255,0.3);
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 9px;
    font-weight: 600;
}

.empty-cell {
    color: #ccc;
    text-align: center;
    padding: 20px;
    font-style: italic;
    font-size: 12px;
}

/* List View for Month */
.month-list {
    display: grid;
    gap: 15px;
    margin-top: 20px;
}

.day-section {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.day-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 20px;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.day-body {
    padding: 15px;
}

.day-bookings {
    display: grid;
    gap: 10px;
}

.booking-card {
    background: #f8f9fa;
    border-left: 4px solid #007bff;
    padding: 12px;
    border-radius: 5px;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 15px;
    align-items: center;
    transition: transform 0.2s;
}

.booking-card:hover {
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.booking-time {
    font-weight: 700;
    color: #004a80;
    font-size: 14px;
    min-width: 70px;
}

.booking-details {
    flex: 1;
}

.booking-client {
    font-weight: 600;
    margin-bottom: 3px;
}

.booking-service {
    font-size: 13px;
    color: #666;
}

.booking-employees {
    font-size: 12px;
    color: #888;
    margin-top: 3px;
}

.booking-meta {
    display: flex;
    gap: 8px;
    align-items: center;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    color: #fff;
    font-weight: 500;
}

/* Add/Update these classes for No Show status */
.status-No\Show {
    background-color: #795548 !important; /* brown */
    color: #fff !important;
}

.schedule-item.no-show {
    background: linear-gradient(135deg, #795548 0%, #4e342e 100%) !important;
    color: #fff !important;
}


.status-Pending { background-color: gray; }
.status-Confirmed { background-color: #007bff; }
.status-Ongoing { background-color:  #e2d10fff ; }
.status-Completed { background-color: #28a745; }
.status-Session-Complete,
.status-session-complete,
.status-SessionComplete { 
    background-color: #28a745 !important; 
}
.status-Cancelled { background-color: #dc3545; }
.status-No\Show,
.status-NoShow,
.status-No-Show {
    background-color: #795548 !important; /* brown */
    color: #fff !important;
}
.status-Active { background-color: #007bff; }

.type-badge {
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
}

.type-badge.onetime {
    background: #f093fb;
    color: white;
}

.type-badge.recurring {
    background: #4facfe;
    color: white;
}

/* Summary stats */
.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    padding: 15px;
    border-radius: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-align: center;
}

.stat-number {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 12px;
    opacity: 0.9;
}
 .dashboard__sidebar {
    min-width: 250px;
    width: 250px;
    flex-shrink: 0;
}

/* Sidebar dropdown */
.has-dropdown .dropdown__menu { display: none; }
.has-dropdown.open .dropdown__menu { display: block; }

/* Legend */
.schedule-legend {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
}

@media print {
    .view-controls, .nav-btn, .employee-filter { display: none; }
}

@media (max-width: 768px) {
    .view-controls {
        flex-direction: column;
    }
    
    .schedule-container {
        font-size: 11px;
    }
}
.section-divider {
    border: 0;
    height: 2px;
    background: #ddd;
    margin: 10px 0 20px;
}
</style>
</head>
<body>

<header class="header" id="header">
<nav class="nav container">
    <a href="admin_dashboard.php?content=dashboard" class="nav__logo">
        <img src="LOGO.png" alt="ALAZIMA Logo" 
             onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
    </a>
    <button class="nav__toggle" id="nav-toggle"><i class='bx bx-menu'></i></button>
</nav>
</header>

<div class="dashboard__wrapper">
    <aside class="dashboard__sidebar">
        <ul class="sidebar__menu">
            <li class="menu__item"><a href="admin_dashboard.php?content=dashboard" class="menu__link"><i class='bx bx-home-alt-2'></i> Dashboard</a></li>
            
            <li class="menu__item has-dropdown">
                <a href="#" class="menu__link -parent"><i class='bx bx-user-circle'></i> User Management <i class='bx bx-chevron-down arrow-icon'></i></a>
                <ul class="dropdown__menu">
                    <li class="menu__item"><a href="clients.php?content=manage-clients" class="menu__link">Clients</a></li>
                    <li class="menu__item"><a href="UM_employees.php?content=manage-employees" class="menu__link ">Employees</a></li>
                    <li class="menu__item"><a href="UM_admins.php?content=manage-admins" class="menu__link">Admins</a></li>
                    <li class="menu__item"><a href="archived_clients.php?content=manage-archive" class="menu__link">Archive</a></li>
                </ul>       
            </li>
            
            <li class="menu__item has-dropdown">
                <a href="#" class="menu__link"><i class='bx bx-calendar-check'></i> Appointment Management <i class='bx bx-chevron-down arrow-icon'></i></a>
                <ul class="dropdown__menu">
                    <li class="menu__item"><a href="AP_one-time.php" class="menu__link">One-time Service</a></li>
                    <li class="menu__item"><a href="AP_recurring.php" class="menu__link">Recurring Service</a></li>
                </ul>
            </li>
            
            <li class="menu__item"><a href="ES.php" class="menu__link active"><i class='bx bx-time'></i> Employee Scheduling</a></li>
            <li class="menu__item"><a href="manage_groups.php" class="menu__link "><i class='bx bx-group'></i> Manage Groups</a></li>
            <li class="menu__item"><a href="admin_feedback_dashboard.php" class="menu__link"><i class='bx bx-star'></i> Feedback Overview</a></li>
            <li class="menu__item"><a href="Reports.php" class="menu__link"><i class='bx bx-file'></i> Reports</a></li>
            <li class="menu__item"><a href="concern.php?content=profile" class="menu__link"><i class='bx bx-info-circle'></i> Issues & Concerns</a></li>
            <li class="menu__item"><a href="admin_profile.php" class="menu__link"><i class='bx bx-user'></i> Profile</a></li>
            <li class="menu__item"><a href="javascript:void(0)" class="menu__link" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard__content">
        <section class="content__section active">
            <div class="content-container">
                <h1><i class='bx bx-calendar-week'></i> Employee Scheduling</h1>
                <hr class="section-divider">

                <!-- View Controls -->
                <div class="view-controls">
                    <div class="view-tabs">
                        <a href="ES.php?view=week&date=<?= $selected_date ?>&employee=<?= $selected_employee ?>" 
                           class="view-tab <?= $view == 'week' ? 'active' : '' ?>">
                            <i class='bx bx-calendar-week'></i> Weekly Schedule
                        </a>
                        <a href="ES.php?view=month&date=<?= $selected_date ?>&employee=<?= $selected_employee ?>" 
                           class="view-tab <?= $view == 'month' ? 'active' : '' ?>">
                            <i class='bx bx-calendar'></i> Monthly List
                        </a>
                    </div>

                    <div class="employee-filter">
                        <!-- <label><i class='bx bx-user'></i> Filter:</label>
                        <select id="employeeSelect" onchange="filterEmployee()">
                            <option value="all" <?= $selected_employee == 'all' ? 'selected' : '' ?>>All Employees</option> -->
                            <?php 
                            mysqli_data_seek($employees_result, 0);
                            while ($emp = $employees_result->fetch_assoc()): 
                                $emp_name = $emp['first_name'] . ' ' . $emp['last_name'];
                            ?>
                                <option value="<?= htmlspecialchars($emp_name) ?>" 
                                        <?= $selected_employee == $emp_name ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp_name) ?> (<?= $emp['position'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="date-navigation">
                        <button class="nav-btn" onclick="navigateDate('prev')">
                            <i class='bx bx-chevron-left'></i>
                        </button>
                        <div class="date-display">
                            <?php 
                            if ($view == 'week') {
                                echo date('M d', strtotime($week_start)) . ' - ' . date('M d, Y', strtotime($week_end));
                            } else {
                                echo date('F Y', strtotime($selected_date));
                            }
                            ?>
                        </div>
                        <button class="nav-btn" onclick="navigateDate('next')">
                            <i class='bx bx-chevron-right'></i>
                        </button>
                        <button class="nav-btn" onclick="navigateDate('today')">
                            <i class='bx bx-calendar-check'></i> Today
                        </button>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <!-- <div class="summary-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?= count($all_bookings) ?></div>
                        <div class="stat-label">Total Appointments</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <div class="stat-number">
                            <?= count(array_filter($all_bookings, function($b) { 
                                return $b['booking_type'] == 'One-Time'; 
                            })) ?>
                        </div>
                        <div class="stat-label">One-Time</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <div class="stat-number">
                            <?= count(array_filter($all_bookings, function($b) { 
                                return isset($b['is_recurring']) && $b['is_recurring']; 
                            })) ?>
                        </div>
                        <div class="stat-label">Recurring</div>
                    </div>
                </div> -->

                <?php if ($view == 'week'): ?>
                    <!-- Legend -->
                    <div class="schedule-legend">
                        <div class="legend-item">
                            <!-- <div class="legend-color" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);"></div>
                            <span>One-Time Service</span> -->
                        </div>
                        <div class="legend-item">
                            <!-- <div class="legend-color" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);"></div>
                            <span>Recurring Service</span> -->
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: linear-gradient(gray);"></div>
                            <span>Pending</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: linear-gradient(135deg, #dbd81fff 0%, #e2d10fff 100%);"></div>
                            <span>ongoing</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);"></div>
                            <span>Confirmed</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);"></div>
                            <span>Completed</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);"></div>
                            <span>Session-Complete</span>
                        </div>
                    </div>

                    <!-- Weekly Schedule Grid (Class Timetable Style) -->
                    <div class="schedule-container">
                        <div class="schedule-grid">
                            <!-- Header Row -->
                            <div class="schedule-header">
                                <div class="schedule-header-cell time-column">Time</div>
                                <?php
                                $current = strtotime($week_start);
                                for ($i = 0; $i < 7; $i++):
                                    $day_date = date('Y-m-d', $current);
                                    $is_today = ($day_date == $current_date);
                                ?>
                                    <div class="schedule-header-cell" style="<?= $is_today ? 'background:  #E87722;' : '' ?>">
                                        <div><?= date('l', $current) ?></div>
                                        <div style="font-size: 12px; opacity: 0.9;"><?= date('M d', $current) ?></div>
                                    </div>
                                <?php
                                    $current = strtotime('+1 day', $current);
                                endfor;
                                ?>
                            </div>

                            <!-- Time Slot Rows -->
                            <?php foreach ($schedule_grid as $time => $days): ?>
                                <div class="schedule-row">
                                    <div class="schedule-cell time-cell">
                                        <?= date('g A', strtotime($time)) ?>
                                    </div>
                                    <?php foreach ($days as $day => $bookings): ?>
                                        <div class="schedule-cell">
                                            <?php if (count($bookings) > 0): ?>
                                                <?php foreach ($bookings as $booking): 
                                                    $status_class = strtolower(str_replace(' ', '-', $booking['status']));
                                                    $type_class = isset($booking['is_recurring']) && $booking['is_recurring'] ? 'recurring' : 'onetime';
                                                    
                                                    // Get assigned employees
                                                    $assigned = [];
                                                    if (!empty($booking['cleaners'])) {
                                                        $assigned = array_merge($assigned, explode(',', $booking['cleaners']));
                                                    }
                                                    if (!empty($booking['drivers'])) {
                                                        $assigned = array_merge($assigned, explode(',', $booking['drivers']));
                                                    }
                                                    $assigned = array_map('trim', $assigned);
                                                ?>
                                                    <div class="schedule-item <?= $type_class ?> <?= $status_class ?>" 
                                                         onclick="showBookingDetails(<?= $booking['id'] ?>)"
                                                         title="<?= htmlspecialchars($booking['full_name'] . ' - ' . $booking['service_type']) ?>">
                                                        <div class="schedule-item-badge">
                                                            <?= isset($booking['is_recurring']) && $booking['is_recurring'] ? '' : '' ?>
                                                        </div>
                                                        <div class="schedule-item-time">
                                                            <?= date('g:i A', strtotime($booking['service_time'])) ?>
                                                        </div>
                                                        <div class="schedule-item-client">
                                                            <?= htmlspecialchars($booking['full_name']) ?>
                                                        </div>
                                                        <div class="schedule-item-service">
                                                            <?= htmlspecialchars($booking['service_type']) ?>
                                                        </div>
                                                        <?php if ($selected_employee == 'all' && count($assigned) > 0): ?>
                                                            <div style="font-size: 10px; margin-top: 3px; opacity: 0.8;">
                                                                <i class='bx bx-user'></i> <?= htmlspecialchars(implode(', ', array_slice($assigned, 0, 2))) ?>
                                                                <?= count($assigned) > 2 ? '...' : '' ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Monthly List View -->
                    <div class="month-list">
                        <?php
                        // Group bookings by date
                        $bookings_by_date = [];
                        foreach ($all_bookings as $booking) {
                            $date = $booking['service_date'];
                            if (!isset($bookings_by_date[$date])) {
                                $bookings_by_date[$date] = [];
                            }
                            $bookings_by_date[$date][] = $booking;
                        }
                        
                        // Sort by date
                        ksort($bookings_by_date);
                        
                        if (count($bookings_by_date) > 0):
                            foreach ($bookings_by_date as $date => $bookings):
                        ?>
                            <div class="day-section">
                                <div class="day-header">
                                    <div>
                                        <strong><?= date('l, F j, Y', strtotime($date)) ?></strong>
                                    </div>
                                    <div>
                                        <i class='bx bx-calendar'></i> <?= count($bookings) ?> appointment<?= count($bookings) > 1 ? 's' : '' ?>
                                    </div>
                                </div>
                                <div class="day-body">
                                    <div class="day-bookings">
                                        <?php foreach ($bookings as $booking): 
                                            // Get assigned employees
                                            $assigned = [];
                                            if (!empty($booking['cleaners'])) {
                                                $cleaners = explode(',', $booking['cleaners']);
                                                foreach ($cleaners as $c) {
                                                    $assigned[] = trim($c) . ' (Cleaner)';
                                                }
                                            }
                                            if (!empty($booking['drivers'])) {
                                                $drivers = explode(',', $booking['drivers']);
                                                foreach ($drivers as $d) {
                                                    $assigned[] = trim($d) . ' (Driver)';
                                                }
                                            }
                                        ?>
                                            <div class="booking-card">
                                                <div class="booking-time">
                                                    <i class='bx bx-time'></i>
                                                    <?= date('g:i A', strtotime($booking['service_time'])) ?>
                                                </div>
                                                <div class="booking-details">
                                                    <div class="booking-client">
                                                        <i class='bx bx-user'></i> <?= htmlspecialchars($booking['full_name']) ?>
                                                    </div>
                                                    <div class="booking-service">
                                                        <i class='bx bx-briefcase'></i> <?= htmlspecialchars($booking['service_type']) ?>
                                                        <?php if (!empty($booking['duration'])): ?>
                                                            • <?= htmlspecialchars($booking['duration']) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (count($assigned) > 0): ?>
                                                        <div class="booking-employees">
                                                            <i class='bx bx-group'></i> <?= htmlspecialchars(implode(', ', $assigned)) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="booking-service" style="margin-top: 3px;">
                                                        <i class='bx bx-map'></i> <?= htmlspecialchars(substr($booking['address'], 0, 60)) ?><?= strlen($booking['address']) > 60 ? '...' : '' ?>
                                                    </div>
                                                </div>
                                                <div class="booking-meta">
                                                    <?php if (isset($booking['is_recurring']) && $booking['is_recurring']): ?>
                                                        <span class="type-badge recurring">Recurring</span>
                                                    <?php else: ?>
                                                        <span class="type-badge onetime">One-Time</span>
                                                    <?php endif; ?>
                                                    <span class="status-badge status-<?= str_replace(' ', '-', $booking['status']) ?>">
                                                        <?= htmlspecialchars($booking['status']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <div class="day-section">
                                <div class="day-body">
                                    <div style="text-align: center; padding: 40px; color: #999;">
                                        <i class='bx bx-calendar-x' style="font-size: 64px; opacity: 0.5;"></i>
                                        <p>No appointments scheduled for this month</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
<!-- Booking Details Modal -->
<div id="bookingModal" class="modal">
    <div class="modal__content">
        <div class="modal__header">
            <h3 class="modal__title">
                <i class='bx bx-info-circle'></i>
                Booking Details
            </h3>
            <button class="modal__close" onclick="closeBookingModal()">
                <i class='bx bx-x'></i>
            </button>
        </div>
        <div class="modal__body" id="bookingDetailsContent">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Loading booking details...</p>
            </div>
        </div>
    </div>
</div>

<div id="logoutModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Are you sure you want to log out?</h3>
        <div class="modal__actions">
            <button id="cancelLogout" class="btn btn--secondary">Cancel</button>
            <button id="confirmLogout" class="btn btn--primary">Log Out</button>
        </div>
    </div>
</div>

<script>
// Date navigation
function navigateDate(direction) {
    const currentDate = '<?= $selected_date ?>';
    const view = '<?= $view ?>';
    const employee = '<?= $selected_employee ?>';
    let newDate;
    
    if (direction === 'today') {
        newDate = '<?= $current_date ?>';
    } else {
        const date = new Date(currentDate);
        
        if (view === 'week') {
            if (direction === 'prev') {
                date.setDate(date.getDate() - 7);
            } else {
                date.setDate(date.getDate() + 7);
            }
        } else {
            if (direction === 'prev') {
                date.setMonth(date.getMonth() - 1);
            } else {
                date.setMonth(date.getMonth() + 1);
            }
        }
        
        newDate = date.toISOString().split('T')[0];
    }
    
    window.location.href = `ES.php?view=${view}&date=${newDate}&employee=${employee}`;
}

// Filter by employee
function filterEmployee() {
    const employee = document.getElementById('employeeSelect').value;
    const view = '<?= $view ?>';
    const date = '<?= $selected_date ?>';
    window.location.href = `ES.php?view=${view}&date=${date}&employee=${encodeURIComponent(employee)}`;
}

// Show booking details (placeholder - you can expand this)
// Show booking details
function showBookingDetails(bookingId) {
    const modal = document.getElementById('bookingModal');
    const content = document.getElementById('bookingDetailsContent');
    
    // Show modal with loading state
    modal.classList.add('show');
    content.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading booking details...</p>
        </div>
    `;
    
    // Fetch booking details via AJAX
    fetch(`ES.php?action=get_booking_details&booking_id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #dc3545;">
                        <i class='bx bx-error-circle' style="font-size: 64px;"></i>
                        <p>${data.error}</p>
                    </div>
                `;
                return;
            }
            
            // Build the details HTML
            let html = `
                <!-- Client Information -->
                <div class="detail-section">
                    <div class="section-title">
                        <i class='bx bx-user'></i> Client Information
                    </div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Full Name</div>
                            <div class="detail-value large">${data.full_name || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value">${data.email || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Phone</div>
                            <div class="detail-value">${data.phone || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Client Type</div>
                            <div class="detail-value">${data.client_type || 'N/A'}</div>
                        </div>
                    </div>
                </div>

                <!-- Service Details -->
                <div class="detail-section">
                    <div class="section-title">
                        <i class='bx bx-briefcase'></i> Service Details
                    </div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Service Type</div>
                            <div class="detail-value large">${data.service_type || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Booking Type</div>
                            <div class="detail-value">
                                <span class="type-badge ${data.booking_type === 'One-Time' ? 'onetime' : 'recurring'}">
                                    ${data.booking_type || 'N/A'}
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Duration</div>
                            <div class="detail-value">${data.duration || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Property Type</div>
                            <div class="detail-value">${data.property_type || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Materials Provided</div>
                            <div class="detail-value">${data.materials_provided || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                <span class="status-badge status-${(data.status || '').replace(' ', '-')}">
                                    ${data.status || 'N/A'}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schedule Information -->
                <div class="detail-section">
                    <div class="section-title">
                        <i class='bx bx-calendar'></i> Schedule Information
                    </div>
                    <div class="detail-grid">
            `;
            
            if (data.booking_type === 'One-Time') {
                html += `
                        <div class="detail-item">
                            <div class="detail-label">Service Date</div>
                            <div class="detail-value large">${formatDate(data.service_date)}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Service Time</div>
                            <div class="detail-value large">${formatTime(data.service_time)}</div>
                        </div>
                `;
            } else {
                html += `
                        <div class="detail-item">
                            <div class="detail-label">Frequency</div>
                            <div class="detail-value">${data.frequency || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Preferred Day</div>
                            <div class="detail-value">${data.preferred_day || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Start Date</div>
                            <div class="detail-value">${formatDate(data.start_date)}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">End Date</div>
                            <div class="detail-value">${data.end_date ? formatDate(data.end_date) : 'Ongoing'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Service Time</div>
                            <div class="detail-value">${formatTime(data.service_time)}</div>
                        </div>
                `;
            }
            
            html += `
                    </div>
                </div>

                <!-- Address -->
                <div class="detail-section">
                    <div class="section-title">
                        <i class='bx bx-map'></i> Service Address
                    </div>
                    <div class="detail-value address">
                        ${data.address || 'N/A'}
                    </div>
                </div>

                <!-- Assigned Employees -->
                <div class="detail-section">
                    <div class="section-title">
                        <i class='bx bx-group'></i> Assigned Employees
                    </div>
            `;
            
            const cleaners = data.cleaners ? data.cleaners.split(',').map(c => c.trim()) : [];
            const drivers = data.drivers ? data.drivers.split(',').map(d => d.trim()) : [];
            
            if (cleaners.length > 0 || drivers.length > 0) {
                html += '<div class="employee-chips">';
                cleaners.forEach(cleaner => {
                    html += `<div class="employee-chip"><i class='bx bx-user'></i> ${cleaner} (Cleaner)</div>`;
                });
                drivers.forEach(driver => {
                    html += `<div class="employee-chip"><i class='bx bx-car'></i> ${driver} (Driver)</div>`;
                });
                html += '</div>';
            } else {
                html += '<p class="no-media">No employees assigned yet</p>';
            }
            
            html += '</div>';
            
            // Comments
            if (data.comments) {
                html += `
                    <div class="detail-section">
                        <div class="section-title">
                            <i class='bx bx-message-detail'></i> Comments
                        </div>
                        <div class="detail-value address">
                            ${data.comments}
                        </div>
                    </div>
                `;
            }
            
            // Materials Needed
            if (data.materials_needed) {
                html += `
                    <div class="detail-section">
                        <div class="section-title">
                            <i class='bx bx-package'></i> Materials Needed
                        </div>
                        <div class="detail-value address">
                            ${data.materials_needed}
                        </div>
                    </div>
                `;
            }
            
            // Media Gallery
            const media = [];
            if (data.media1) media.push(data.media1);
            if (data.media2) media.push(data.media2);
            if (data.media3) media.push(data.media3);
            
            if (media.length > 0) {
                html += `
                    <div class="detail-section">
                        <div class="section-title">
                            <i class='bx bx-image'></i> Photos
                        </div>
                        <div class="media-gallery">
                `;
                media.forEach(img => {
                    html += `<img src="${img}" class="media-item" onclick="window.open('${img}', '_blank')" alt="Booking photo">`;
                });
                html += '</div></div>';
            }
            
            // Rating
            if (data.rating_stars) {
                html += `
                    <div class="detail-section">
                        <div class="section-title">
                            <i class='bx bx-star'></i> Rating & Feedback
                        </div>
                        <div class="rating-display">
                            <div class="stars">
                `;
                for (let i = 0; i < 5; i++) {
                    html += i < data.rating_stars ? '<i class="bx bxs-star"></i>' : '<i class="bx bx-star"></i>';
                }
                html += `
                            </div>
                            <span>${data.rating_stars}/5</span>
                        </div>
                `;
                if (data.rating_comment) {
                    html += `<div class="detail-value address" style="margin-top: 10px;">${data.rating_comment}</div>`;
                }
                html += '</div>';
            }
            
            // Issue Report
            if (data.issue_type) {
                html += `
                    <div class="detail-section">
                        <div class="section-title">
                            <i class='bx bx-error'></i> Reported Issue
                        </div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Issue Type</div>
                                <div class="detail-value">${data.issue_type}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Report Date</div>
                                <div class="detail-value">${formatDate(data.issue_report_date)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Report Time</div>
                                <div class="detail-value">${formatTime(data.issue_report_time)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Sentiment</div>
                                <div class="detail-value">${data.sentiment || 'N/A'}</div>
                            </div>
                        </div>
                `;
                if (data.issue_description) {
                    html += `<div class="detail-value address" style="margin-top: 10px;">${data.issue_description}</div>`;
                }
                
                // Issue photos
                const issuePhotos = [];
                if (data.issue_photo1) issuePhotos.push(data.issue_photo1);
                if (data.issue_photo2) issuePhotos.push(data.issue_photo2);
                if (data.issue_photo3) issuePhotos.push(data.issue_photo3);
                
                if (issuePhotos.length > 0) {
                    html += '<div class="media-gallery" style="margin-top: 10px;">';
                    issuePhotos.forEach(img => {
                        html += `<img src="${img}" class="media-item" onclick="window.open('${img}', '_blank')" alt="Issue photo">`;
                    });
                    html += '</div>';
                }
                
                html += '</div>';
            }
            
            // Booking Information
           
            
            if (data.submission_date) {
                html += `
                        <div class="detail-item">
                            <div class="detail-label">Submission Date</div>
                            <div class="detail-value">${formatDate(data.submission_date)}</div>
                        </div>
                `;
            }
            
            if (data.submission_time) {
                html += `
                        <div class="detail-item">
                            <div class="detail-label">Submission Time</div>
                            <div class="detail-value">${formatTime(data.submission_time)}</div>
                        </div>
                `;
            }
            
            html += `
                    </div>
                </div>
            `;
            
            content.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #dc3545;">
                    <i class='bx bx-error-circle' style="font-size: 64px;"></i>
                    <p>Error loading booking details</p>
                </div>
            `;
        });
}

function closeBookingModal() {
    document.getElementById('bookingModal').classList.remove('show');
}

// Helper functions
function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
}

function formatTime(timeStr) {
    if (!timeStr) return 'N/A';
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

function formatDateTime(dateTimeStr) {
    if (!dateTimeStr) return 'N/A';
    const date = new Date(dateTimeStr);
    return date.toLocaleString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const bookingModal = document.getElementById('bookingModal');
    const logoutModal = document.getElementById('logoutModal');
    
    if (event.target == bookingModal) {
        closeBookingModal();
    }
    if (event.target == logoutModal) {
        logoutModal.classList.remove('show');
    }
}

// Logout modal
function showLogoutModal() {
    document.getElementById('logoutModal').classList.add('show');
}

document.getElementById('cancelLogout')?.addEventListener('click', function() {
    document.getElementById('logoutModal').classList.remove('show');
});

document.getElementById('confirmLogout')?.addEventListener('click', function() {
    window.location.href = "landing_page2.html";
});

// Sidebar dropdown toggle
(function(){
    const nav = document.querySelector('.sidebar__menu');
    if (!nav) return;
    const dropdownParents = nav.querySelectorAll('.has-dropdown');
    dropdownParents.forEach(parent => {
        const parentLink = parent.querySelector('.menu__link');
        if (!parentLink) return;
        parentLink.addEventListener('click', function(e){
            e.preventDefault();
            parent.classList.toggle('open');
        });
    });
})();
</script>

</body>
</html>