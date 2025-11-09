<?php
include 'connection.php';
session_start();

// ✅ Ensure employee is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='landing_page2.html';</script>";
    exit;
}

// ✅ Get employee info
$employeeEmail = $_SESSION['email'];

$stmt = $conn->prepare("SELECT id, first_name, last_name, position FROM employees WHERE email = ?");
$stmt->bind_param("s", $employeeEmail);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee) {
    echo "<script>alert('Employee not found.'); window.location.href='landing_page2.html';</script>";
    exit;
}

$employeeId = $employee['id'];
$employeeName = ($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '');
$position = $employee['position'] ?? 'N/A';

/* ✅ Fetch FULL booking details with rating */
$query = "
SELECT 
    sr.*,
    b.*
FROM staff_ratings sr
INNER JOIN bookings b ON sr.booking_id = b.id
WHERE sr.employee_id = ?
ORDER BY sr.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$ratings = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Ratings</title>
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
    background: #007bff;
    color: white;
    padding: 10px;
    text-align: left;
}

.table td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

.no-data {
    text-align: center;
    font-weight: bold;
    color: red;
    margin-top: 20px;
}
</style>
</head>

<body>

<header class="header" id="header">
<nav class="nav container">
    <a href="employee_dashboard.php" class="nav__logo">
        <img src="LOGO.png" alt="ALAZIMA Cleaning Services LLC Logo"
             onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
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
            <a href="employee_dashboard.php" class="menu__link">
                <i class='bx bx-home-alt-2'></i> Dashboard
            </a>
        </li>

        <li class="menu__item has-dropdown open">
            <a href="#" class="menu__link">
                <i class='bx bx-calendar-check'></i> My Appointments <i class='bx bx-chevron-down arrow-icon'></i>
            </a>

            <ul class="dropdown__menu" style="display:block;">
                <li class="menu__item">
                    <a href="EMP_appointments_today.php" class="menu__link">Today's Appointments</a>
                </li>

                <!-- ✅ THIS ONE IS ACTIVE ON HISTORY PAGE -->
                <li class="menu__item">
                    <a href="EMP_appointments_history.php" class="menu__link active">History</a>
                </li>
            </ul>
        </li>

        <!-- ✅ Notice: no 'active' class here -->
        <li class="menu__item">
            <a href="EMP_ratings_feedback.php" class="menu__link">
                <i class='bx bx-star'></i> Ratings/Feedback
            </a>
        </li>

        <li class="menu__item">
            <a href="employee_schedule.php" class="menu__link">
                <i class='bx bx-calendar-week'></i> Schedule
            </a>
        </li>

        <li class="menu__item">
            <a href="landing_page2.html" class="menu__link">
                <i class='bx bx-log-out'></i> Logout
            </a>
        </li>
    </ul>
</aside>


<main class="dashboard__content">
<section class="content__section active">
    <div class="content-container">

        
        <p><strong>Name:</strong> <?= htmlspecialchars($employeeName) ?><br>
           <strong>Position:</strong> <?= htmlspecialchars($position) ?></p>

        <?php if ($ratings->num_rows > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    
                    <th>Client Name</th>
                    <th>Address</th>
                    <th>Service Date</th>
                    <th>Service Time</th>
                    <th>Booking Type</th>
                    <th>Materials Provided</th>
                    <th>Staff Type</th>
                    
                </tr>
            </thead>

            <tbody>
                <?php while ($row = $ratings->fetch_assoc()): ?>
                <tr>
                    
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['address'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['service_date']) ?></td>
                    <td><?= htmlspecialchars($row['service_time'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['booking_type'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['materials_provided'] ?? 'Not Set') ?></td>
                    <td><?= ucfirst(htmlspecialchars($row['staff_type'])) ?></td>
                    
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php else: ?>
            <p class="no-data">No ratings yet.</p>
        <?php endif; ?>

    </div>
</section>
</main>

</div>

</body>
</html>
