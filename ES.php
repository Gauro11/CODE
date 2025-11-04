<?php
// ES.php
include 'connection.php';
session_start();

// ✅ Ensure user is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

// Fetch all employees
$employees_sql = "SELECT id, first_name, last_name, position, status 
                  FROM employees 
                  ORDER BY first_name, last_name";
$employees_result = $conn->query($employees_sql);
if (!$employees_result) {
    die("Error fetching employees: " . $conn->error);
}

// Function to get bookings assigned to an employee
function getBookings($conn, $employee_name) {
    $employee_name = $conn->real_escape_string($employee_name);
    $bookings_sql = "SELECT address, full_name, service_type, service_date, service_time, status
                     FROM bookings
                     WHERE FIND_IN_SET('$employee_name', cleaners) > 0
                        OR FIND_IN_SET('$employee_name', drivers) > 0
                     ORDER BY service_date, service_time";
    $result = $conn->query($bookings_sql);
    $bookings = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    }
    return $bookings;
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
.content-container { 
    background: #fff; 
    border-radius: 12px; 
    padding: 20px; 
    box-shadow: 0 3px 10px rgba(0,0,0,0.1); 
    margin: 20px; 
}

/* Table styling */
table { 
    width: 100%; 
    border-collapse: collapse; 
    margin-top: 10px; 
}
th, td { 
    padding: 12px 15px; 
    border-bottom: 1px solid #ddd; 
    text-align: left; 
}
th { 
    background-color: #f4f4f4; 
    font-weight: 600; 
}
tbody tr:nth-child(even) { 
    background-color: #f9f9f9; 
}
tbody tr:hover { 
    background-color: #e6f0ff; 
}

/* Employee section heading */
.content-container h2 {
    margin-top: 30px;
    margin-bottom: 10px;
    color: #004a80;
    font-size: 20px;
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
}

/* Status badges */
/* Status badges for all statuses */
.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    color: #fff;
    font-weight: 500;
    text-align: center;
}

/* Assign colors */
.status-Pending     { background-color: #ffc107; }  /* yellow */
.status-Confirmed   { background-color: #007bff; }  /* blue */
.status-Ongoing     { background-color: #17a2b8; }  /* teal */
.status-Completed   { background-color: #28a745; }  /* green */
.status-Cancelled   { background-color: #dc3545; }  /* red */
.status-No\ Show    { background-color: #6c757d; }  /* gray */
.status-Active      { background-color: #198754; }  /* dark green */
.status-Paused      { background-color: #fd7e14; }  /* orange */


/* Sidebar dropdown fix */
.has-dropdown .dropdown__menu { display: none; }
.has-dropdown.open .dropdown__menu { display: block; }
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
                <a href="#" class="menu__link active-parent"><i class='bx bx-user-circle'></i> User Management <i class='bx bx-chevron-down arrow-icon'></i></a>
                <ul class="dropdown__menu">
                    <li class="menu__item"><a href="clients.php?content=manage-clients" class="menu__link">Clients</a></li>
                    <li class="menu__item"><a href="UM_employees.php?content=manage-employees" class="menu__link active">Employees</a></li>
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
             <li class="menu__item"><a href="admin_feedback_dashboard.php" class="menu__link "><i class='bx bx-star'></i> Feedback Overview</a></li>
            <li class="menu__item"><a href="FR.php" class="menu__link"><i class='bx bx-star'></i> Feedback & Ratings</a></li>
            <li class="menu__item"><a href="Reports.php" class="menu__link"><i class='bx bx-file-text'></i> Reports</a></li>
               <li class="menu__item"><a href="concern.php?content=profile" class="menu__link" data-content="profile"><i class='bx bx-user'></i> Issues&Concerns</a></li>
            <li class="menu__item"><a href="admin_profile.php" class="menu__link"><i class='bx bx-user'></i> Profile</a></li>
            <li class="menu__item"><a href="javascript:void(0)" class="menu__link" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard__content">
        <section class="content__section active">
            <div class="content-container">
                <h1>Employee Scheduling</h1>

                <?php while ($employee = $employees_result->fetch_assoc()): 
                    $full_name = $employee['first_name'] . ' ' . $employee['last_name'];
                    $bookings = getBookings($conn, $full_name);
                ?>
                    <h2><?php echo htmlspecialchars($full_name); ?> (<?php echo $employee['position']; ?>)</h2>
                    <?php if (count($bookings) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Address</th>
                                    <th>Client Name</th>
                                    <th>Service Type</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $b): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($b['address']) ?></td>
                                        <td><?= htmlspecialchars($b['full_name']) ?></td>
                                        <td><?= htmlspecialchars($b['service_type']) ?></td>
                                        <td><?= htmlspecialchars($b['service_date']) ?></td>
                                        <td><?= htmlspecialchars($b['service_time']) ?></td>
                                        <td><span class="status-badge status-<?= $b['status'] ?>"><?= $b['status'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No bookings assigned.</p>
                    <?php endif; ?>
                <?php endwhile; ?>
            </div>
        </section>
    </main>
</div><div id="logoutModal" class="modal">
<div class="modal__content">
<h3 class="modal__title">Are you sure you want to log out?</h3>
<div class="modal__actions">
<button id="cancelLogout" class="btn btn--secondary">Cancel</button>
<button id="confirmLogout" class="btn btn--primary">Log Out</button>


<script>
    
const navLinks = document.querySelectorAll('.sidebar__menu .menu__link');
const logoutLink = document.querySelector('.sidebar__menu .menu__link[data-content="logout"]');
const logoutModal = document.getElementById('logoutModal');
const cancelLogoutBtn = document.getElementById('cancelLogout');
const confirmLogoutBtn = document.getElementById('confirmLogout');

// Handle logout modal
function showLogoutModal() {
    if (logoutModal) logoutModal.classList.add('show');
}

if (cancelLogoutBtn && logoutModal) {
    cancelLogoutBtn.addEventListener('click', function() {
        logoutModal.classList.remove('show');
    });
}

if (confirmLogoutBtn) {
    confirmLogoutBtn.addEventListener('click', function() {
        window.location.href = "landing_page2.html";
    });
}
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
