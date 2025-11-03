<?php
include 'connection.php';
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='landing_page2.html';</script>";
    exit;
}

// Handle resolve button
if (isset($_POST['resolve']) && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];

    $updateQuery = "UPDATE bookings SET 
        issue_type = NULL,
        issue_description = NULL,
        issue_report_time = NULL,
        issue_report_date = NULL,
        submission_date = NULL,
        submission_time = NULL,
        issue_photo1 = NULL,
        issue_photo2 = NULL,
        issue_photo3 = NULL
        WHERE id = ?";

    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('i', $booking_id);
    if ($stmt->execute()) {
        echo "<script>alert('Issue resolved successfully!'); window.location.href='concern.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error resolving issue.'); window.location.href='concern.php';</script>";
        exit;
    }
}

// Search and sort
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

$query = "SELECT * FROM bookings 
          WHERE ((issue_type IS NOT NULL AND issue_type != '') 
             OR (issue_description IS NOT NULL AND issue_description != ''))";

if ($search) {
    $query .= " AND (full_name LIKE ? OR email LIKE ?)";
}

$allowedSorts = ['service_date', 'full_name'];
if ($sort && in_array($sort, $allowedSorts)) {
    $query .= " ORDER BY $sort ASC";
} else {
    $query .= " ORDER BY issue_report_date DESC, service_date DESC";
}

if ($search) {
    $stmt = $conn->prepare($query);
    $searchTerm = "%$search%";
    $stmt->bind_param('ss', $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking Issues - Admin</title>
<link rel="stylesheet" href="admin_dashboard.css">
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="admin_db.css">
<style>
/* Main content styling */
.content-container { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); margin: 20px; }
.search-sort { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
.search-sort input, .search-sort select { padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; }
.search-sort button { padding: 8px 16px; border: none; border-radius: 6px; background: #007bff; color: white; cursor: pointer; }

/* Card-style list for issues */
.issue-list { display: flex; flex-direction: column; gap: 15px; }
.issue-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 15px 20px; display: grid; 
    grid-template-columns: 1fr 2fr 1.2fr 1.2fr 1fr 1fr 1.5fr auto; align-items: start; transition: transform 0.2s; gap: 5px; }
.issue-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.15); }
.issue-card div { padding: 5px 10px; word-wrap: break-word; }
.issue-card .issue-action button { background: orange; border: none; padding: 6px 12px; border-radius: 6px; color: white; cursor: pointer; transition: background 0.2s; }
.issue-card .issue-action button:hover { background: darkorange; }

/* Photos */
.issue-photos { display: flex; gap: 5px; flex-wrap: wrap; }
.issue-photos img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #ccc; cursor: pointer; transition: transform 0.2s; }
.issue-photos img:hover { transform: scale(1.1); border-color: #007bff; }

/* Header for card list */
.issue-card-header { font-weight: bold; color: #555; display: grid; grid-template-columns: 1fr 2fr 1.2fr 1.2fr 1fr 1fr 1.5fr auto; padding: 10px 20px; border-bottom: 1px solid #ddd; }

/* Sidebar dropdown fix */
.has-dropdown .dropdown__menu { display: none; }
.has-dropdown.open .dropdown__menu { display: block; }
</style>
</head>
<body>

<header class="header" id="header">
<nav class="nav container">
    <a href="admin_dashboard.php?content=dashboard" class="nav__logo">
        <img src="LOGO.png" alt="ALAZIMA Logo" onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
    </a>
    <button class="nav__toggle" id="nav-toggle"><i class='bx bx-menu'></i></button>
</nav>
</header>

<div class="dashboard__wrapper">

    <!-- Sidebar -->
    <aside class="dashboard__sidebar">
        <ul class="sidebar__menu">
            <li class="menu__item"><a href="admin_dashboard.php?content=dashboard" class="menu__link">Dashboard</a></li>
            <li class="menu__item has-dropdown">
                <a href="#" class="menu__link"><i class='bx bx-user-circle'></i> User Management <i class='bx bx-chevron-down arrow-icon'></i></a>
                <ul class="dropdown__menu">
                    <li class="menu__item"><a href="clients.php?content=manage-clients" class="menu__link">Clients</a></li>
                    <li class="menu__item"><a href="UM_employees.php?content=manage-employees" class="menu__link">Employees</a></li>
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
            <li class="menu__item"><a href="ES.php" class="menu__link">Employee Scheduling</a></li>
            <li class="menu__item"><a href="FR.php" class="menu__link">Feedback & Ratings</a></li>
            <li class="menu__item"><a href="Reports.php" class="menu__link">Reports</a></li>
            <li class="menu__item"><a href="concern.php" class="menu__link active">Issues & Concerns</a></li>
            <li class="menu__item"><a href="admin_profile.php" class="menu__link">Profile</a></li>
            <li class="menu__item"><a href="javascript:void(0)" class="menu__link" onclick="showLogoutModal()">Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="dashboard__content">
        <section class="content__section active">
            <div class="content-container">
                <h2>Issues & Concerns</h2>

                <form method="GET" class="search-sort">
                    <input type="text" name="search" placeholder="Search by client name or email" value="<?= htmlspecialchars($search) ?>">
                    <select name="sort">
                        <option value="">Sort by</option>
                        <option value="full_name" <?= $sort=='full_name'?'selected':'' ?>>Client Name</option>
                        <option value="service_date" <?= $sort=='service_date'?'selected':'' ?>>Service Date</option>
                    </select>
                    <button type="submit">Apply</button>
                </form>

                <div class="issue-list">
                    <div class="issue-card-header">
                        <div>Num#</div>
                        <div>Client & Issue</div>
                        <div>Service Date</div>
                        <div>Address</div>
                        <div>Cleaners</div>
                        <div>Drivers</div>
                        <div>Photos</div>
                        <div>Action</div>
                    </div>

                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <div class="issue-card">
                                <div><?= htmlspecialchars($row['phone']) ?></div>
                                <div>
                                    <?= htmlspecialchars($row['full_name']) ?><br>
                                    <small><?= htmlspecialchars($row['issue_type']) ?>: <?= htmlspecialchars($row['issue_description']) ?></small>
                                </div>
                                <div><?= htmlspecialchars($row['service_date']) ?> <?= htmlspecialchars($row['service_time']) ?></div>
                                <div><?= htmlspecialchars($row['address']) ?></div>
                                <div><?= htmlspecialchars($row['cleaners']) ?></div>
                                <div><?= htmlspecialchars($row['drivers']) ?></div>
                                <div class="issue-photos">
                                    <?php
                                    $photos = ['issue_photo1', 'issue_photo2', 'issue_photo3'];
                                    foreach($photos as $photo){
                                        if($row[$photo]){
                                            $path = 'uploads/issues/' . $row[$photo];
                                            echo "<a href='".htmlspecialchars($path)."' target='_blank'>
                                                    <img src='".htmlspecialchars($path)."' alt='Issue Photo'>
                                                  </a>";
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="issue-action">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="resolve">Resolve</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="issue-card" style="text-align:center; grid-column: span 8;">No bookings with issues found</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
// Sidebar dropdown functionality
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

// Logout modal (optional)
function showLogoutModal() {
    if (!document.getElementById('logoutModal')) return;
    document.getElementById('logoutModal').classList.add('show');
}
</script>

</body>
</html>
