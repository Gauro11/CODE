<?php
include 'connection.php';
session_start();

// Ensure employee is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='landing_page2.html';</script>";
    exit;
}

$employeeEmail = $_SESSION['email'];

// Get employee info
$stmt = $conn->prepare("SELECT first_name, last_name FROM employees WHERE email = ?");
$stmt->bind_param("s", $employeeEmail);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) {
    echo "<script>alert('Employee not found.'); window.location.href='landing_page2.html';</script>";
    exit;
}

$employeeName = $employee['first_name'] . ' ' . $employee['last_name'];
$today = date('Y-m-d');

// ========== FUNCTION: Generate recurring occurrences ==========
function generateRecurringOccurrences($booking, $targetDate) {
    $occurrences = [];
    $frequency = $booking['frequency'];
    $preferredDay = $booking['preferred_day'];
    $startDate = $booking['start_date'];
    $endDate = $booking['end_date'] ? $booking['end_date'] : date('Y-m-d', strtotime('+1 year'));
    
    // Check if target date is within the booking range
    if (strtotime($targetDate) < strtotime($startDate) || strtotime($targetDate) > strtotime($endDate)) {
        return $occurrences;
    }
    
    $currentTime = strtotime($targetDate);
    $dayOfWeek = date('l', $currentTime);
    $shouldInclude = false;
    
    switch ($frequency) {
        case 'Daily':
            $shouldInclude = true;
            break;
        case 'Weekly':
            $shouldInclude = ($dayOfWeek == $preferredDay);
            break;
        case 'Bi-Weekly':
            if ($dayOfWeek == $preferredDay) {
                $weeksDiff = floor((strtotime($targetDate) - strtotime($startDate)) / (7 * 24 * 60 * 60));
                $shouldInclude = ($weeksDiff % 2 == 0);
            }
            break;
        case 'Monthly':
            $shouldInclude = (date('j', $currentTime) == date('j', strtotime($startDate)));
            break;
    }
    
    if ($shouldInclude) {
        $occurrence = $booking;
        $occurrence['service_date'] = $targetDate;
        $occurrence['is_recurring'] = true;
        $occurrences[] = $occurrence;
    }
    
    return $occurrences;
}

// ========== FETCH ONE-TIME APPOINTMENTS ==========
$oneTimeQuery = "
SELECT *
FROM bookings
WHERE booking_type = 'One-Time'
AND service_date = ?
AND status = 'Confirmed'
AND (
    cleaners LIKE CONCAT('%', ?, '%')
    OR drivers LIKE CONCAT('%', ?, '%')
)
ORDER BY service_time ASC
";

$stmt = $conn->prepare($oneTimeQuery);
$stmt->bind_param("sss", $today, $employeeName, $employeeName);
$stmt->execute();
$oneTimeResult = $stmt->get_result();

$appointments = [];

// Add one-time appointments
while ($row = $oneTimeResult->fetch_assoc()) {
    $row['is_recurring'] = false;
    $appointments[] = $row;
}

// ========== FETCH RECURRING APPOINTMENTS ==========
$recurringQuery = "
SELECT *
FROM bookings
WHERE booking_type = 'Recurring'
AND start_date <= ?
AND (end_date IS NULL OR end_date >= ?)
AND status = 'Active'
AND (
    cleaners LIKE CONCAT('%', ?, '%')
    OR drivers LIKE CONCAT('%', ?, '%')
)
ORDER BY service_time ASC
";

$stmt = $conn->prepare($recurringQuery);
$stmt->bind_param("ssss", $today, $today, $employeeName, $employeeName);
$stmt->execute();
$recurringResult = $stmt->get_result();

// Generate occurrences for recurring appointments that fall on today
while ($row = $recurringResult->fetch_assoc()) {
    $occurrences = generateRecurringOccurrences($row, $today);
    $appointments = array_merge($appointments, $occurrences);
}

// Sort all appointments by time
usort($appointments, function($a, $b) {
    return strtotime($a['service_time']) - strtotime($b['service_time']);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Today's Appointments</title>
<link rel="stylesheet" href="employee_dashboard.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="client_db.css">
<style>
.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
.table th {
    background:  #007bff;
    color: white;
    padding: 10px;
    text-align: left;
}
.table td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
    vertical-align: top;
}
.no-data {
    text-align: center;
    font-weight: bold;
    color: #999;
    margin-top: 40px;
    padding: 40px;
}
.no-data i {
    font-size: 64px;
    display: block;
    margin-bottom: 15px;
    opacity: 0.5;
}
.print-btn {
    margin-bottom: 15px;
    padding: 8px 15px;
    background:  #007bff;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.print-btn:hover {
    background:  #007bff;
}
.highlight {
    font-weight: bold;
    color: #d9534f;
}
.recurring-badge {
    display: inline-block;
    background: #4facfe;
    color: white;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 5px;
}
.onetime-badge {
    display: inline-block;
    background: #f093fb;
    color: white;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 5px;
}

@media print {
    .print-btn, .dashboard__sidebar, .header, .nav__toggle {
        display: none !important;
    }
    .dashboard__content {
        margin-left: 0 !important;
    }
}
</style>
<script>
function printPage() {
    window.print();
}
</script>
</head>
<body>

<header class="header" id="header">
<nav class="nav container">
    <a href="employee_dashboard.php" class="nav__logo">
        <img src="LOGO.png" alt="ALAZIMA Logo" onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
    </a>
    <button class="nav__toggle" id="nav-toggle">
        <i class='bx bx-menu'></i>
    </button>
</nav>
</header>

<div class="dashboard__wrapper">

<aside class="dashboard__sidebar">
    <ul class="sidebar__menu">
        <li class="menu__item">
            <a href="employee_dashboard.php" class="menu__link"><i class='bx bx-home-alt-2'></i> Dashboard</a>
        </li>
        <li class="menu__item has-dropdown open">
            <a href="#" class="menu__link"><i class='bx bx-calendar-check'></i> My Appointments <i class='bx bx-chevron-down arrow-icon'></i></a>
            <ul class="dropdown__menu" style="display:block;">
                <li class="menu__item"><a href="EMP_appointments_today.php" class="menu__link active">Today's Appointments</a></li>
                <li class="menu__item"><a href="EMP_appointments_history.php" class="menu__link">History</a></li>
            </ul>
        </li>
        <li class="menu__item"><a href="EMP_ratings_feedback.php" class="menu__link"><i class='bx bx-star'></i> Ratings/Feedback</a></li>
        <li class="menu__item"><a href="employee_schedule.php" class="menu__link"><i class='bx bx-calendar-week'></i> Schedule</a></li>
        <li class="menu__item"><a href="landing_page2.html" class="menu__link"><i class='bx bx-log-out'></i> Logout</a></li>
    </ul>
</aside>

<main class="dashboard__content">
<section class="content__section active">
    <div class="content-container">
        <h2><i class='bx bx-calendar-check'></i> Today's Confirmed Appointments for <?= htmlspecialchars($employeeName) ?></h2>
        <p style="color: #666; margin-bottom: 20px;">
            <i class='bx bx-calendar'></i> <?= date('l, F j, Y') ?>
        </p>
        <button class="print-btn" onclick="printPage()">
            <i class='bx bx-printer'></i> Print / Save as PDF
        </button>

        <?php if (count($appointments) > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Booking Type</th>
                    <th>Client</th>
                    <th>Service Type</th>
                    <th>Client Type</th>
                    <th>Area / Address</th>
                    <th>Cleaners Assigned</th>
                    <th>Driver Assigned</th>
                    <th>Time</th>
                    <th>Duration</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $row): 
                    $cleaners = !empty($row['cleaners']) ? explode(",", $row['cleaners']) : [];
                    $drivers = !empty($row['drivers']) ? explode(",", $row['drivers']) : [];
                ?>
                <tr>
                    <td>
                        <?php if (isset($row['is_recurring']) && $row['is_recurring']): ?>
                            <span class="recurring-badge"><i class='bx bx-repeat'></i> Recurring</span>
                        <?php else: ?>
                            <span class="onetime-badge"><i class='bx bx-calendar-check'></i> One-Time</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['service_type']) ?></td>
                    <td><?= htmlspecialchars($row['client_type']) ?></td>
                    <td><?= htmlspecialchars($row['address']) ?></td>
                    <td>
                        <?php if (count($cleaners) > 0): ?>
                            <?php foreach($cleaners as $c): 
                                $cleanerName = trim($c);
                                if (empty($cleanerName)) continue;
                            ?>
                                <?= htmlspecialchars($cleanerName) === $employeeName ? 
                                    "<span class='highlight'>" . htmlspecialchars($cleanerName) . "</span>" : 
                                    htmlspecialchars($cleanerName) 
                                ?><br>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <em style="color: #999;">None</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (count($drivers) > 0): ?>
                            <?php foreach($drivers as $d): 
                                $driverName = trim($d);
                                if (empty($driverName)) continue;
                            ?>
                                <?= htmlspecialchars($driverName) === $employeeName ? 
                                    "<span class='highlight'>" . htmlspecialchars($driverName) . "</span>" : 
                                    htmlspecialchars($driverName) 
                                ?><br>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <em style="color: #999;">None</em>
                        <?php endif; ?>
                    </td>
                    <td><?= date("g:i A", strtotime($row['service_time'])) ?></td>
                    <td><?= htmlspecialchars($row['duration']) ?> hrs</td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <strong>Summary:</strong> 
            <?php 
            $oneTimeCount = count(array_filter($appointments, function($a) { return !$a['is_recurring']; }));
            $recurringCount = count(array_filter($appointments, function($a) { return isset($a['is_recurring']) && $a['is_recurring']; }));
            ?>
            Total: <?= count($appointments) ?> appointments 
            (<?= $oneTimeCount ?> one-time, <?= $recurringCount ?> recurring)
        </div>
        
        <?php else: ?>
            <div class="no-data">
                <i class='bx bx-calendar-x'></i>
                <p>No confirmed appointments assigned to you today.</p>
                <p style="font-size: 14px; color: #999; margin-top: 10px;">
                    Check back later or view your upcoming schedule.
                </p>
            </div>
        <?php endif; ?>
    </div>
</section>
</main>

</div>

<script>
// Sidebar dropdown toggle
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