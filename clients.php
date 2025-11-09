<?php
include 'connection.php';
session_start();

// ✅ Ensure user is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

// Search and sort
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

// Base query (exclude archived by default)
$query = "SELECT * FROM clients WHERE archived = 0 AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";

// Sorting
$allowedSorts = ['first_name', 'last_name'];
if ($sort && in_array($sort, $allowedSorts)) {
    $query .= " ORDER BY $sort ASC";
}

$stmt = $conn->prepare($query);
$searchTerm = "%$search%";
$stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Management - Clients</title>
<link rel="stylesheet" href="admin_dashboard.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="admin_db.css">

<style>

.search-sort { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
.search-sort input, .search-sort select { padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; }
.search-sort button { padding: 8px 16px; border: none; border-radius: 6px; background: #007bff; color: white; cursor: pointer; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px 12px; border-bottom: 1px solid #ddd; text-align: left; }
th { background: #f4f4f4; }
.actions button { padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; color: #fff; }
.edit { background: #007bff; }
.archive { background: #dc3545; }
.edit:hover { background: #0056b3; }
.archive:hover { background: #a71d2a; }
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
                    <li class="menu__item"><a href="clients.php?content=manage-clients" class="menu__link active">Clients</a></li>
                    <li class="menu__item"><a href="UM_employees.php?content=manage-employees" class="menu__link">Employees</a></li>
                    <li class="menu__item"><a href="UM_admins.php?content=manage-admins" class="menu__link">Admins</a></li>
                     <li class="menu__item"><a href="archived_clients.php?content=manage-archive" class="menu__link" data-content="manage-archive">Archive</a></li>
                </ul>       
            </li>
            
            <li class="menu__item has-dropdown">
                <a href="#" class="menu__link"><i class='bx bx-calendar-check'></i> Appointment Management <i class='bx bx-chevron-down arrow-icon'></i></a>
                <ul class="dropdown__menu">
                    <li class="menu__item"><a href="AP_one-time.php" class="menu__link">One-time Service</a></li>
                    <li class="menu__item"><a href="AP_recurring.php" class="menu__link">Recurring Service</a></li>
                </ul>
            </li>
            
            <li class="menu__item"><a href="ES.php" class="menu__link"><i class='bx bx-time'></i> Employee Scheduling</a></li>
            <li class="menu__item"><a href="manage_groups.php" class="menu__link "><i class='bx bx-group'></i> Manage Groups</a></li>
             <li class="menu__item"><a href="admin_feedback_dashboard.php" class="menu__link "><i class='bx bx-star'></i> Feedback Overview</a></li>
            <!-- <li class="menu__item"><a href="FR.php" class="menu__link"><i class='bx bx-star'></i> Feedback & Ratings</a></li> -->
            <li class="menu__item"><a href="Reports.php" class="menu__link"><i class='bx bx-file'></i> Reports</a></li>
               <li class="menu__item"><a href="concern.php?content=profile" class="menu__link" data-content="profile"><i class='bx bx-info-circle'></i> Issues&Concerns</a></li>
            <li class="menu__item"><a href="admin_profile.php" class="menu__link"><i class='bx bx-user'></i> Profile</a></li>
            <li class="menu__item"><a href="javascript:void(0)" class="menu__link" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard__content">
        <section class="content__section active">
            <div class="content-container">
                <h2>User Management - Clients</h2>

                <form method="GET" class="search-sort">
                    <input type="text" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>">
                    <select name="sort">
                        <option value="">Sort by</option>
                        <option value="first_name" <?= $sort=='first_name'?'selected':'' ?>>First Name</option>
                        <option value="last_name" <?= $sort=='last_name'?'selected':'' ?>>Last Name</option>
                    </select>
                    <button type="submit">Apply</button>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Birthday</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                                    <td><?= htmlspecialchars($row['last_name']) ?></td>
                                    <td><?= htmlspecialchars($row['birthday']) ?></td>
                                    <td><?= htmlspecialchars($row['contact_number']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td class="actions">
                                        <button class="edit" onclick="editUser(<?= $row['id'] ?>)">Edit</button>
                                        <button class="archive" onclick="archiveUser(<?= $row['id'] ?>)">Archive</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;">No clients found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
<div id="logoutModal" class="modal">
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
// Edit / Archive
function editUser(id) { window.location.href='edit_client.php?id='+id; }
function archiveUser(id) {
    if(confirm('Are you sure you want to archive this client?')) {
        window.location.href='archive_client.php?id='+id;
    }
}

// Sidebar dropdown
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
