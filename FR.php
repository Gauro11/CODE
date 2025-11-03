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

// Base query for feedback
$query = "SELECT * FROM bookings WHERE rating_stars IS NOT NULL";
if ($search) {
    $query .= " AND (full_name LIKE ? OR service_type LIKE ?)";
}

$stmt = $conn->prepare($query);
if ($search) {
    $searchTerm = "%$search%";
    $stmt->bind_param('ss', $searchTerm, $searchTerm);
}
$stmt->execute();
$result = $stmt->get_result();

// Organize by service type
$feedback_by_service = [];
while ($row = $result->fetch_assoc()) {
    $service = $row['service_type'];
    if (!isset($feedback_by_service[$service])) $feedback_by_service[$service] = [];
    $feedback_by_service[$service][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Feedback & Ratings</title>
<link rel="stylesheet" href="admin_dashboard.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="admin_db.css">
<style>
.content-container { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); margin: 20px; }
.search-sort { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
.search-sort input, .search-sort select { padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; }
.search-sort button { padding: 8px 16px; border: none; border-radius: 6px; background: #007bff; color: white; cursor: pointer; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px 12px; border-bottom: 1px solid #ddd; text-align: left; }
th { background: #f4f4f4; }
tbody tr:nth-child(even){ background:#f9f9f9; }
tbody tr:hover{ background:#e6f0ff; }
.rating-badge { display:inline-block; padding:4px 10px; border-radius:12px; color:#fff; font-weight:600; text-align:center; font-size:13px; }
.rating-1{ background:#dc3545; } 
.rating-2{ background:#fd7e14; } 
.rating-3{ background:#ffc107; color:#000; } 
.rating-4{ background:#28a745; } 
.rating-5{ background:#007bff; }
.sentiment-positive{ background:#28a745; color:#fff; padding:4px 8px; border-radius:8px; }
.sentiment-negative{ background:#dc3545; color:#fff; padding:4px 8px; border-radius:8px; }
.sentiment-neutral{ background:#ffc107; color:#000; padding:4px 8px; border-radius:8px; }
/* Sidebar dropdown fix */
.has-dropdown .dropdown__menu { display: none; }
.has-dropdown.open .dropdown__menu { display: block; }

<style>
.dashboard__wrapper {
    display: flex;
    height: calc(100vh - 60px); /* full height minus header */
    overflow: hidden;
}



.dashboard__sidebar .sidebar__menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.dashboard__content {
    flex: 1;
    background: #f0f2f5;
    overflow-y: auto; /* main content scrollable independently */
    padding: 20px;
}

.content-container {
    max-width: 1000px;
    margin: 0 auto;
}
/* Table fixes */
table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed; /* force equal column widths */
}

th, td {
    padding: 10px 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
    word-wrap: break-word; /* wrap long text */
    overflow: hidden;
    white-space: normal; /* allow multi-line */
}

th:nth-child(1) { width: 20%; }  /* Client Name */
th:nth-child(2) { width: 10%; }  /* Rating */
th:nth-child(3) { width: 50%; }  /* Comment */
th:nth-child(4) { width: 20%; }  /* Sentiment */

</style>

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
                    <li class="menu__item"><a href="clients.php" class="menu__link">Clients</a></li>
                    <li class="menu__item"><a href="UM_employees.php" class="menu__link">Employees</a></li>
                    <li class="menu__item"><a href="UM_admins.php" class="menu__link">Admins</a></li>
                    <li class="menu__item"><a href="archived_clients.php" class="menu__link">Archive</a></li>
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
            <li class="menu__item"><a href="FR.php" class="menu__link active"><i class='bx bx-star'></i> Feedback & Ratings</a></li>
            <li class="menu__item"><a href="Reports.php" class="menu__link"><i class='bx bx-file-text'></i> Reports</a></li>
               <li class="menu__item"><a href="concern.php?content=profile" class="menu__link" data-content="profile"><i class='bx bx-user'></i> Issues&Concerns</a></li>
            <li class="menu__item"><a href="admin_profile.php" class="menu__link"><i class='bx bx-user'></i> Profile</a></li>
            <li class="menu__item"><a href="javascript:void(0)" class="menu__link" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard__content">
        <section class="content__section active">
            <div class="content-container">
                <h2>Feedback & Ratings</h2>

                <form method="GET" class="search-sort">
                    <input type="text" name="search" placeholder="Search client or service" value="<?= htmlspecialchars($search) ?>">
                    <select name="sort">
                        <option value="">Sort by</option>
                        <option value="full_name" <?= $sort=='full_name'?'selected':'' ?>>Client Name</option>
                        <option value="service_type" <?= $sort=='service_type'?'selected':'' ?>>Service</option>
                        <option value="rating_stars" <?= $sort=='rating_stars'?'selected':'' ?>>Rating</option>
                    </select>
                    <button type="submit">Apply</button>
                </form>

                <?php foreach($feedback_by_service as $service => $feedbacks): ?>
                    <h3><?= htmlspecialchars($service) ?></h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Sentiment</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($feedbacks as $f): ?>
                            <tr>
                                <td><?= htmlspecialchars($f['full_name']) ?></td>
                                <td><span class="rating-badge rating-<?= $f['rating_stars'] ?>"><?= $f['rating_stars'] ?></span></td>
                                <td><?= htmlspecialchars($f['rating_comment']) ?></td>
                                <td>
                                    <?php
                                    $s = strtolower($f['sentiment']);
                                    $class = 'sentiment-neutral';
                                    if($s==='positive') $class='sentiment-positive';
                                    elseif($s==='negative') $class='sentiment-negative';
                                    ?>
                                    <span class="<?= $class ?>"><?= htmlspecialchars($f['sentiment']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>

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
