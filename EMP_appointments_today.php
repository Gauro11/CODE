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

// Fetch all confirmed bookings where this employee is assigned
$query = "
SELECT *
FROM bookings
WHERE status = 'Confirmed'
AND (
    cleaners LIKE CONCAT('%', ?, '%')
    OR drivers LIKE CONCAT('%', ?, '%')
)
ORDER BY service_date ASC, service_time ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $employeeName, $employeeName);
$stmt->execute();
$result = $stmt->get_result();
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
.content-container {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin: 20px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}
.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
.table th {
    background: #4CAF50;
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
    color: red;
    margin-top: 20px;
}
.print-btn {
    margin-bottom: 15px;
    padding: 8px 15px;
    background: #4CAF50;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.print-btn:hover {
    background: #45a049;
}
.highlight {
    font-weight: bold;
    color: #d9534f; /* red color to highlight employee */
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
        <li class="menu__item"><a href="landing_page2.html" class="menu__link"><i class='bx bx-log-out'></i> Logout</a></li>
    </ul>
</aside>

<main class="dashboard__content">
<section class="content__section active">
    <div class="content-container">
        <h2>📅 Today's Confirmed Appointments for <?= htmlspecialchars($employeeName) ?></h2>
        <button class="print-btn" onclick="printPage()">🖨️ Print / Save as PDF</button>

        <?php if ($result->num_rows > 0): ?>
        <table class="table">
            <thead>
                <tr>
                   
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
                <?php while ($row = $result->fetch_assoc()): 
                    $cleaners = explode(",", $row['cleaners']);
                    $drivers = explode(",", $row['drivers']);
                ?>
                <tr>
                   
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['service_type']) ?></td>
                    <td><?= htmlspecialchars($row['client_type']) ?></td>
                    <td><?= htmlspecialchars($row['address']) ?></td>
                    <td>
                        <?php foreach($cleaners as $c): ?>
                            <?= htmlspecialchars(trim($c)) === $employeeName ? "<span class='highlight'>$c</span>" : htmlspecialchars(trim($c)) ?>,<br>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?php foreach($drivers as $d): ?>
                            <?= htmlspecialchars(trim($d)) === $employeeName ? "<span class='highlight'>$d</span>" : htmlspecialchars(trim($d)) ?>,<br>
                        <?php endforeach; ?>
                    </td>
                    <td><?= date("g:i A", strtotime($row['service_time'])) ?></td>
                    <td><?= htmlspecialchars($row['duration']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="no-data">No confirmed appointments assigned to you today.</p>
        <?php endif; ?>
    </div>
</section>
</main>

</div>
</body>
</html>
