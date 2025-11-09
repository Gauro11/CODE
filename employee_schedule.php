<?php
// employee_schedule.php
include 'connection.php';
session_start();

// ✅ Ensure employee is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

// Get employee information from database
$employee_email = $_SESSION['email'];
$employee_sql = "SELECT id, first_name, last_name, position FROM employees WHERE email = ?";
$stmt = $conn->prepare($employee_sql);
$stmt->bind_param("s", $employee_email);
$stmt->execute();
$employee_result = $stmt->get_result();

if ($employee_result->num_rows == 0) {
    echo "<script>alert('Employee not found.'); window.location.href='login.php';</script>";
    exit;
}

$employee = $employee_result->fetch_assoc();
$employee_name = $employee['first_name'] . ' ' . $employee['last_name'];
$employee_position = $employee['position'];

// Get current date and view parameters
$current_date = date('Y-m-d');
$view = isset($_GET['view']) ? $_GET['view'] : 'week'; // 'week' or 'month'
$selected_date = isset($_GET['date']) ? $_GET['date'] : $current_date;

// Calculate week start and end
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($selected_date)));
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($selected_date)));

// Calculate month start and end
$month_start = date('Y-m-01', strtotime($selected_date));
$month_end = date('Y-m-t', strtotime($selected_date));

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

// Function to check if employee is assigned to booking
function isEmployeeAssigned($booking, $employee_name) {
    $cleaners = !empty($booking['cleaners']) ? array_map('trim', explode(',', $booking['cleaners'])) : [];
    $drivers = !empty($booking['drivers']) ? array_map('trim', explode(',', $booking['drivers'])) : [];
    
    return in_array($employee_name, $cleaners) || in_array($employee_name, $drivers);
}

// Function to get bookings for date range (filtered by employee)
function getBookingsForEmployee($conn, $start_date, $end_date, $employee_name) {
    $bookings = [];
    
    // Escape employee name for SQL
    $employee_name_escaped = $conn->real_escape_string($employee_name);
    
    // Get One-Time bookings where employee is assigned
    $onetime_sql = "SELECT id, address, full_name, service_type, service_date, service_time, 
                           status, booking_type, cleaners, drivers, duration
                    FROM bookings
                    WHERE booking_type = 'One-Time'
                    AND service_date BETWEEN '$start_date' AND '$end_date'
                    AND (FIND_IN_SET('$employee_name_escaped', cleaners) > 0 
                         OR FIND_IN_SET('$employee_name_escaped', drivers) > 0)
                    ORDER BY service_date, service_time";
    
    $result = $conn->query($onetime_sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    }
    
    // Get Recurring bookings where employee is assigned
    $recurring_sql = "SELECT id, address, full_name, service_type, start_date, end_date, 
                             service_time, status, booking_type, frequency, preferred_day,
                             cleaners, drivers, duration
                      FROM bookings
                      WHERE booking_type = 'Recurring'
                      AND start_date <= '$end_date'
                      AND (end_date IS NULL OR end_date >= '$start_date')
                      AND (FIND_IN_SET('$employee_name_escaped', cleaners) > 0 
                           OR FIND_IN_SET('$employee_name_escaped', drivers) > 0)
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

$my_bookings = getBookingsForEmployee($conn, $range_start, $range_end, $employee_name);

// Handle AJAX request for booking details
if (isset($_GET['action']) && $_GET['action'] == 'get_booking_details' && isset($_GET['booking_id'])) {
    $booking_id = intval($_GET['booking_id']);
    $sql = "SELECT * FROM bookings WHERE id = $booking_id";
    $result = $conn->query($sql);
    
    if ($result && $booking = $result->fetch_assoc()) {
        // Verify employee is assigned to this booking
        if (isEmployeeAssigned($booking, $employee_name)) {
            header('Content-Type: application/json');
            echo json_encode($booking);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
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
    foreach ($my_bookings as $booking) {
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
<title>My Schedule</title>
<link rel="stylesheet" href="admin_dashboard.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="admin_db.css">
<style>
/* Reuse all the same styles from ES.php */
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

.schedule-item.confirmed {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.schedule-item.ongoing {
    background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
}

.schedule-item.active {
    background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
}
.schedule-item.completed {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}

.schedule-item.pending {
    background: linear-gradient(gray);
}

.schedule-item.cancelled {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.schedule-item.no-show {
    background: linear-gradient(135deg, #795548 0%, #4e342e 100%);
}
.schedule-item.paused {
    background: linear-gradient(135deg, #b6e626ff 0%, #d7d126ff 100%);
}
.schedule-item.session-complete {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
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
    cursor: pointer;
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

.status-Pending { background-color: gray; }
.status-Confirmed { background-color: #007bff; }
.status-Active { background-color: #17a2b8; }
.status-Completed { background-color: #28a745; }
.status-Session-Complete { background-color: #28a745; }
.status-Cancelled { background-color: #dc3545; }
.status-No-Show { background-color: #795548; }
.status-Paused { background-color: #e0e716ff; }

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

.employee-info-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.employee-info-card h2 {
    margin: 0 0 5px 0;
    font-size: 24px;
}

.employee-info-card p {
    margin: 0;
    opacity: 0.9;
}

.section-divider {
    border: 0;
    height: 2px;
    background: #ddd;
    margin: 10px 0 20px;
}

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

@media (max-width: 768px) {
    .view-controls {
        flex-direction: column;
    }
    
    .schedule-container {
        font-size: 11px;
    }
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

</style>
</head>
<body>

<header class="header" id="header">
<nav class="nav container">
    <a href="employee_dashboard.php" class="nav__logo">
        <img src="LOGO.png" alt="ALAZIMA Logo" 
             onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
    </a>
    <button class="nav__toggle" id="nav-toggle"><i class='bx bx-menu'></i></button>
</nav>
</header>

<div class="dashboard__wrapper">
   <aside class="dashboard__sidebar">
    <ul class="sidebar__menu">
        <li class="menu__item">
            <a href="employee_dashboard.php" class="menu__link"><i class='bx bx-home-alt-2'></i> Dashboard</a>
        </li>
        <li class="menu__item has-dropdown ">
            <a href="#" class="menu__link"><i class='bx bx-calendar-check'></i> My Appointments <i class='bx bx-chevron-down arrow-icon'></i></a>
            <ul class="dropdown__menu" style="display:block;">
                <li class="menu__item"><a href="EMP_appointments_today.php" class="menu__link ">Today's Appointments</a></li>
                <li class="menu__item"><a href="EMP_appointments_history.php" class="menu__link">History</a></li>
            </ul>
        </li>
        <li class="menu__item"><a href="EMP_ratings_feedback.php" class="menu__link"><i class='bx bx-star'></i> Ratings/Feedback</a></li>
        <li class="menu__item"><a href="employee_schedule.php" class="menu__link active"><i class='bx bx-calendar-week'></i> Schedule</a></li>
        
        <li class="menu__item"><a href="landing_page2.html" class="menu__link"><i class='bx bx-log-out'></i> Logout</a></li>
        
    </ul>
</aside>

    <main class="dashboard__content">
        <section class="content__section active">
            <div class="content-container">
                <!-- Employee Info Card -->
                

                <h1><i class='bx bx-calendar-week'></i> My Schedule</h1>
                <hr class="section-divider">

                <!-- View Controls -->
                <div class="view-controls">
                    <div class="view-tabs">
                        <a href="employee_schedule.php?view=week&date=<?= $selected_date ?>" 
                           class="view-tab <?= $view == 'week' ? 'active' : '' ?>">
                            <i class='bx bx-calendar-week'></i> Weekly Schedule
                        </a>
                        <a href="employee_schedule.php?view=month&date=<?= $selected_date ?>" 
                           class="view-tab <?= $view == 'month' ? 'active' : '' ?>">
                            <i class='bx bx-calendar'></i> Monthly List
                        </a>
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

               
                <?php if ($view == 'week'): ?>
                    <!-- Legend -->
                    <div class="schedule-legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background: linear-gradient(gray);"></div>
                            <span>Pending</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);"></div>
                            <span>Active</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: linear-gradient(135deg, #d3dd1aff 0%, #e1eb1aff 100%);"></div>
                            <span>Paused</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);"></div>
                            <span>Completed</span>
                        </div>
                         <div class="legend-item">
                            <div class="legend-color" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);"></div>
                            <span>Session Complete</span>
                        </div>
                    </div>

                    <!-- Weekly Schedule Grid -->
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
                                                ?>
                                                    <div class="schedule-item <?= $status_class ?>" 
                                                         onclick="showBookingDetails(<?= $booking['id'] ?>)"
                                                         title="<?= htmlspecialchars($booking['full_name'] . ' - ' . $booking['service_type']) ?>">
                                                        <div class="schedule-item-time">
                                                            <?= date('g:i A', strtotime($booking['service_time'])) ?>
                                                        </div>
                                                        <div class="schedule-item-client">
                                                            <?= htmlspecialchars($booking['full_name']) ?>
                                                        </div>
                                                        <div class="schedule-item-service">
                                                            <?= htmlspecialchars($booking['service_type']) ?>
                                                        </div>
                                                        <?php if (!empty($booking['duration'])): ?>
                                                            <div style="font-size: 10px; margin-top: 3px; opacity: 0.8;">
                                                                <i class='bx bx-time'></i> <?= htmlspecialchars($booking['duration']) ?>
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
                        foreach ($my_bookings as $booking) {
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
                                        <?php foreach ($bookings as $booking): ?>
                                            <div class="booking-card" onclick="showBookingDetails(<?= $booking['id'] ?>)">
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
                                                    <div class="booking-service" style="margin-top: 3px;">
                                                        <i class='bx bx-map'></i> <?= htmlspecialchars(substr($booking['address'], 0, 60)) ?><?= strlen($booking['address']) > 60 ? '...' : '' ?>
                                                    </div>
                                                </div>
                                                <div class="booking-meta">
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

<!-- Logout Modal -->
<div id="logoutModal" class="modal">
    <div class="modal__content">
        <div class="modal__header">
            <h3 class="modal__title">Confirm Logout</h3>
            <button class="modal__close" onclick="closeLogoutModal()">
                <i class='bx bx-x'></i>
            </button>
        </div>
        <div class="modal__body">
            <p style="text-align: center; padding: 20px;">Are you sure you want to log out?</p>
            <div style="display: flex; gap: 10px; justify-content: center; padding: 0 20px 20px;">
                <button onclick="closeLogoutModal()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>
                <button onclick="confirmLogout()" style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;">Log Out</button>
            </div>
        </div>
    </div>
</div>

<script>
// Date navigation
function navigateDate(direction) {
    const currentDate = '<?= $selected_date ?>';
    const view = '<?= $view ?>';
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
    
    window.location.href = `employee_schedule.php?view=${view}&date=${newDate}`;
}

// Show booking details
function showBookingDetails(bookingId) {
    const modal = document.getElementById('bookingModal');
    const content = document.getElementById('bookingDetailsContent');
    
    modal.classList.add('show');
    content.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading booking details...</p>
        </div>
    `;
    
    fetch(`employee_schedule.php?action=get_booking_details&booking_id=${bookingId}`)
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
                            <div class="detail-label">Phone</div>
                            <div class="detail-value">${data.phone || 'N/A'}</div>
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
                            <div class="detail-label">Duration</div>
                            <div class="detail-value">${data.duration || 'N/A'}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Property Type</div>
                            <div class="detail-value">${data.property_type || 'N/A'}</div>
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
            
            // Parse cleaners and drivers
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
                html += '<p style="color: #999; font-style: italic; text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">No employees assigned yet</p>';
            }
            
            html += '</div>';
            
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
                    html += `<img src="${img}" class="media-item" onclick="window.open('${img}', '_blank')" alt="Booking photo" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; cursor: pointer;">`;
                });
                html += '</div></div>';
            }
            
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

function showLogoutModal() {
    document.getElementById('logoutModal').classList.add('show');
}

function closeLogoutModal() {
    document.getElementById('logoutModal').classList.remove('show');
}

function confirmLogout() {
    window.location.href = "logout.php";
}

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

// Close modal when clicking outside
window.onclick = function(event) {
    const bookingModal = document.getElementById('bookingModal');
    const logoutModal = document.getElementById('logoutModal');
    
    if (event.target == bookingModal) {
        closeBookingModal();
    }
    if (event.target == logoutModal) {
        closeLogoutModal();
    }
}

// Loading spinner animation
const style = document.createElement('style');
style.textContent = `
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
    .media-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
    }
`;
document.head.appendChild(style);

// Sidebar dropdown toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdownParents = document.querySelectorAll('.has-dropdown');
    
    dropdownParents.forEach(parent => {
        const parentLink = parent.querySelector('.menu__link');
        
        if (parentLink) {
            parentLink.addEventListener('click', function(e) {
                e.preventDefault();
                parent.classList.toggle('open');
            });
        }
    });
});
</script>

</body>
</html>