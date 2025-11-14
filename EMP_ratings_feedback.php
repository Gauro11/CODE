<?php
include 'connection.php';
session_start();

// Ensure employee is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='landing_page2.html';</script>";
    exit;
}

// Get logged-in employee's information
$employeeEmail = $_SESSION['email'];
$employeeQuery = "SELECT id, first_name, last_name, position FROM employees WHERE email = ?";
$stmt = $conn->prepare($employeeQuery);
$stmt->bind_param("s", $employeeEmail);
$stmt->execute();
$employeeResult = $stmt->get_result();
$employee = $employeeResult->fetch_assoc();

if (!$employee) {
    echo "<script>alert('Employee not found.'); window.location.href='landing_page2.html';</script>";
    exit;
}

$employeeId = $employee['id'];
$employeeName = $employee['first_name'] . ' ' . $employee['last_name'];
$employeeFirstName = $employee['first_name'];
$employeeLastName = $employee['last_name'];
$employeePosition = $employee['position'] ?? 'N/A';

// ✅ FIXED QUERY - No more duplicates!
$ratingsQuery = "
    SELECT 
        sr.id,
        sr.booking_id,
        sr.staff_type,
        sr.rating,
        sr.created_at,
        b.service_date,
        b.service_time,
        b.service_type,
        b.booking_type,
        b.full_name,
        b.address,
        b.status,
        b.cleaners,
        b.drivers
    FROM staff_ratings sr
    INNER JOIN bookings b ON sr.booking_id = b.id
    WHERE sr.employee_id = ?
    ORDER BY sr.created_at DESC
";

$stmt = $conn->prepare($ratingsQuery);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$ratingsResult = $stmt->get_result();

// Calculate statistics
$totalRatings = 0;
$sumRatings = 0;
$ratingCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$ratingsData = [];

while ($row = $ratingsResult->fetch_assoc()) {
    $ratingsData[] = $row;
    $totalRatings++;
    $sumRatings += $row['rating'];
    $ratingCounts[$row['rating']]++;
}

$averageRating = $totalRatings > 0 ? round($sumRatings / $totalRatings, 1) : 0;

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

// Helper function to render stars
function renderStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="bx bxs-star"></i>';
        } else {
            $stars .= '<i class="bx bx-star"></i>';
        }
    }
    return $stars;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ALAZIMA - My Ratings & Feedback</title>
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="client_db.css">
<style>
/* FORCE DROPDOWN TO WORK */
.dropdown__menu {
    list-style: none;
    padding: 0 !important;
    margin: 0 !important;
    max-height: 0 !important;
    overflow: hidden !important;
    transition: max-height 0.3s ease-out !important;
    background-color: #f7f7f7 !important;
}

.has-dropdown.active-dropdown .dropdown__menu {
    max-height: 300px !important;
    padding: 5px 0 !important;
}

.has-dropdown.active-dropdown .arrow-icon {
    transform: rotate(180deg) !important;
}

.arrow-icon {
    transition: transform 0.3s ease !important;
    margin-left: auto;
}

.dropdown__menu .menu__link {
    padding-left: 50px !important;
    font-size: 0.9em !important;
}

.ratings-header {
    background: linear-gradient(#007bff);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.ratings-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
    font-weight: bold;
}

.stat-card p {
    margin: 0;
    font-size: 0.9em;
    opacity: 0.9;
}

.rating-breakdown {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.breakdown-row {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
}

.breakdown-stars {
    display: flex;
    gap: 3px;
    color: #FFD700;
    font-size: 1.2em;
    width: 120px;
}

.breakdown-bar {
    flex: 1;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
}

.breakdown-bar-fill {
    height: 100%;
    background: linear-gradient(#007bff);
    transition: width 0.3s ease;
}

.breakdown-count {
    min-width: 40px;
    text-align: right;
    font-weight: bold;
    color: #666;
}

.ratings-list {
    display: grid;
    gap: 20px;
}

.rating-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.rating-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.rating-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.rating-stars {
    display: flex;
    gap: 5px;
    font-size: 1.5em;
    color: #FFD700;
}

.rating-stars .bx-star {
    color: #ddd;
}

.rating-date {
    color: #999;
    font-size: 0.9em;
}

.booking-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #555;
}

.info-item i {
    color: #667eea;
    font-size: 1.2em;
}

.info-item strong {
    color: #333;
}

.staff-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: bold;
    text-transform: uppercase;
}

.staff-badge.cleaner {
    background: #e3f2fd;
    color: #1976d2;
}

.staff-badge.driver {
    background: #f3e5f5;
    color: #7b1fa2;
}

.ref-number {
    color: #B32133;
    font-weight: bold;
    font-size: 1.1em;
}

.no-ratings {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.no-ratings i {
    font-size: 5em;
    color: #ddd;
    margin-bottom: 20px;
}

.no-ratings h3 {
    color: #666;
    margin-bottom: 10px;
}

.filter-section {
    background: white;
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.filter-group {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-group label {
    font-weight: bold;
    color: #333;
}

.filter-group select {
    padding: 8px 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 1em;
    cursor: pointer;
    transition: border-color 0.3s;
}

.filter-group select:focus {
    outline: none;
    border-color: #667eea;
}
</style>
</head>
<body>
<header class="header" id="header">
<nav class="nav container">
<a href="employee_dashboard.php" class="nav__logo">
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
<a href="employee_dashboard.php" class="menu__link">
<i class='bx bx-home-alt-2'></i> Dashboard
</a>
</li>
<li class="menu__item has-dropdown">
    <a href="#" class="menu__link">
        <i class='bx bx-calendar-check'></i> My Appointments <i class='bx bx-chevron-down arrow-icon'></i>
    </a>
    <ul class="dropdown__menu">
        <li class="menu__item">
            <a href="EMP_appointments_today.php" class="menu__link">Today's Appointments</a>
        </li>
        <li class="menu__item">
            <a href="EMP_appointments_history.php" class="menu__link">History</a>
        </li>
    </ul>
</li>
<li class="menu__item">
<a href="EMP_ratings_feedback.php" class="menu__link active">
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
<div class="ratings-header">
<h1><i class='bx bx-star'></i> My Ratings & Feedback</h1>
<div class="ratings-stats">
<div class="stat-card">
<h3><?php echo $averageRating; ?></h3>
<p>Average Rating</p>
</div>
<div class="stat-card">
<h3><?php echo $totalRatings; ?></h3>
<p>Total Ratings</p>
</div>
<div class="stat-card">
<h3><?php echo $totalRatings > 0 ? number_format(($ratingCounts[5] / $totalRatings) * 100, 1) : 0; ?>%</h3>
<p>5-Star Ratings</p>
</div>
</div>
</div>

<?php if ($totalRatings > 0): ?>
<div class="rating-breakdown">
<h3 style="margin-bottom: 20px; color: #333;"><i class='bx bx-bar-chart-alt-2'></i> Rating Distribution</h3>
<?php for ($star = 5; $star >= 1; $star--): ?>
<div class="breakdown-row">
<div class="breakdown-stars">
<?php echo renderStars($star); ?>
</div>
<div class="breakdown-bar">
<div class="breakdown-bar-fill" style="width: <?php echo $totalRatings > 0 ? ($ratingCounts[$star] / $totalRatings) * 100 : 0; ?>%"></div>
</div>
<div class="breakdown-count"><?php echo $ratingCounts[$star]; ?></div>
</div>
<?php endfor; ?>
</div>

<div class="filter-section">
<div class="filter-group">
<label><i class='bx bx-filter'></i> Filter by:</label>
<select id="ratingFilter">
<option value="all">All Ratings</option>
<option value="5">5 Stars</option>
<option value="4">4 Stars</option>
<option value="3">3 Stars</option>
<option value="2">2 Stars</option>
<option value="1">1 Star</option>
</select>
</div>
</div>

<div class="ratings-list">
<?php foreach ($ratingsData as $rating): 
    $refNo = formatRefNo($rating['booking_id'], $rating['service_type'], $rating['service_date']);
?>
<div class="rating-card" data-rating="<?php echo $rating['rating']; ?>" data-type="<?php echo $rating['staff_type']; ?>">
<div class="rating-card-header">
<div>
<div class="rating-stars"><?php echo renderStars($rating['rating']); ?></div>
<span class="staff-badge <?php echo $rating['staff_type']; ?>"><?php echo ucfirst($rating['staff_type']); ?></span>
</div>
<div class="rating-date">
<i class='bx bx-time'></i> <?php echo date('M d, Y', strtotime($rating['created_at'])); ?>
</div>
</div>

<div class="booking-info">
<div class="info-item">
<i class='bx bx-hash'></i>
<span><strong>Ref:</strong> <span class="ref-number"><?php echo $refNo; ?></span></span>
</div>
<div class="info-item">
<i class='bx bx-calendar'></i>
<span><strong>Service Date:</strong> <?php echo date('M d, Y', strtotime($rating['service_date'])); ?></span>
</div>
<div class="info-item">
<i class='bx bx-time'></i>
<span><strong>Time:</strong> <?php echo date('g:i A', strtotime($rating['service_time'])); ?></span>
</div>
<div class="info-item">
<i class='bx bx-wrench'></i>
<span><strong>Service:</strong> <?php echo htmlspecialchars($rating['service_type']); ?></span>
</div>
<div class="info-item">
<i class='bx bx-user'></i>
<span><strong>Client:</strong> <?php echo htmlspecialchars($rating['full_name']); ?></span>
</div>
<div class="info-item">
<i class='bx bx-map'></i>
<span><strong>Location:</strong> <?php echo htmlspecialchars($rating['address']); ?></span>
</div>
</div>
</div>
<?php endforeach; ?>
</div>

<?php else: ?>
<div class="no-ratings">
<i class='bx bx-star'></i>
<h3>No Ratings Yet</h3>
<p>You haven't received any ratings yet. Complete more jobs to receive feedback from clients!</p>
</div>
<?php endif; ?>

</main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- SIDEBAR DROPDOWN TOGGLE ---
    const dropdownToggles = document.querySelectorAll('.has-dropdown > .menu__link');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            
            const parentItem = toggle.closest('.has-dropdown');
            const wasActive = parentItem.classList.contains('active-dropdown');
            
            // Close all dropdowns
            document.querySelectorAll('.has-dropdown').forEach(item => {
                item.classList.remove('active-dropdown');
            });
            
            // Open this one if it wasn't active
            if (!wasActive) {
                parentItem.classList.add('active-dropdown');
            }
        });
    });

    // --- FILTER FUNCTIONALITY ---
    const ratingFilter = document.getElementById('ratingFilter');
    
    if (ratingFilter) {
        ratingFilter.addEventListener('change', filterRatings);
    }
    
    function filterRatings() {
        const selectedRating = ratingFilter.value;
        const cards = document.querySelectorAll('.rating-card');
        
        cards.forEach(card => {
            const cardRating = card.getAttribute('data-rating');
            
            if (selectedRating === 'all' || cardRating === selectedRating) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // --- MOBILE MENU TOGGLE ---
    const navToggle = document.getElementById('nav-toggle');
    const sidebar = document.querySelector('.dashboard__sidebar');

    if (navToggle && sidebar) {
        navToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }
});
</script>
</body>
</html>