<?php
include 'connection.php';
session_start();

// ✅ Ensure admin is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='landing_page2.html';</script>";
    exit;
}

// ==================== MONTHLY PROFIT CALCULATION ====================
$dateQuery = "SELECT MIN(service_date) as first_date, MAX(service_date) as last_date FROM bookings WHERE materials_provided IS NOT NULL";
$dateResult = $conn->query($dateQuery)->fetch_assoc();

$firstDate = $dateResult['first_date'] ?? date('Y-m-01');
$lastDate = $dateResult['last_date'] ?? date('Y-m-01');

$monthlyProfits = [];
if ($firstDate && $lastDate) {
    $period = new DatePeriod(
        new DateTime(date('Y-m-01', strtotime($firstDate))),
        new DateInterval('P1M'),
        new DateTime(date('Y-m-01', strtotime($lastDate . ' +1 month')))
    );
    
    foreach($period as $dt){
        $monthlyProfits[$dt->format('Y-m')] = 0;
    }
}

$bookingQuery = "SELECT materials_provided, duration, service_date FROM bookings WHERE materials_provided IS NOT NULL";
$bookingResult = $conn->query($bookingQuery);

if ($bookingResult) {
    while($row = $bookingResult->fetch_assoc()){
        $month = date('Y-m', strtotime($row['service_date']));
        
        preg_match('/(\d+(\.\d+)?)/', $row['materials_provided'], $matches);
        $rate = isset($matches[1]) ? (float)$matches[1] : 0;

        $duration = isset($row['duration']) ? (float)$row['duration'] : 1;
        $profit = $rate * $duration;

        if (isset($monthlyProfits[$month])) {
            $monthlyProfits[$month] += $profit;
        } else {
            $monthlyProfits[$month] = $profit;
        }
    }
}

// ==================== STAFF RATINGS DATA ====================
$ratingsQuery = "
    SELECT 
        sr.id,
        sr.booking_id,
        sr.employee_id,
        sr.staff_type,
        sr.rating,
        sr.created_at,
        e.first_name,
        e.last_name,
        e.position,
        b.full_name as client_name,
        b.service_date,
        b.service_type
    FROM staff_ratings sr
    LEFT JOIN employees e ON sr.employee_id = e.id
    LEFT JOIN bookings b ON sr.booking_id = b.id
    ORDER BY sr.created_at DESC
";
$ratingsResult = $conn->query($ratingsQuery);

// Calculate average rating per employee
$employeeRatingsQuery = "
    SELECT 
        e.id,
        e.first_name,
        e.last_name,
        e.position,
        COUNT(sr.id) as total_ratings,
        AVG(sr.rating) as avg_rating,
        MIN(sr.rating) as min_rating,
        MAX(sr.rating) as max_rating
    FROM employees e
    INNER JOIN staff_ratings sr ON e.id = sr.employee_id
    WHERE e.archived = 0 OR e.archived IS NULL
    GROUP BY e.id, e.first_name, e.last_name, e.position
    ORDER BY avg_rating DESC, total_ratings DESC
";
$employeeRatingsResult = $conn->query($employeeRatingsQuery);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports - Admin Dashboard</title>
<link rel="stylesheet" href="admin_dashboard.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="admin_db.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.content-container { 
    background: #fff; 
    border-radius: 12px; 
    padding: 20px; 
    box-shadow: 0 3px 10px rgba(0,0,0,0.1); 
    margin: 20px; 
}
.report-section {
    margin-bottom: 40px;
}
.chart-container {
    position: relative;
    height: 400px;
    margin: 30px 0;
}
table { 
    width: 100%; 
    border-collapse: collapse; 
    margin-top: 15px; 
}
th, td { 
    padding: 10px 12px; 
    border-bottom: 1px solid #ddd; 
    text-align: left; 
}
th { 
    background: #f4f4f4; 
    font-weight: 600;
}
tbody tr:nth-child(even) { 
    background: #f9f9f9; 
}
tbody tr:hover { 
    background: #e6f0ff; 
}
.rating-stars {
    color: #ffc107;
    font-size: 1.1em;
}
.rating-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: 600;
}
.rating-5 { background: #d4edda; color: #155724; }
.rating-4 { background: #d1ecf1; color: #0c5460; }
.rating-3 { background: #fff3cd; color: #856404; }
.rating-2 { background: #f8d7da; color: #721c24; }
.rating-1 { background: #f8d7da; color: #721c24; }
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
                <a href="#" class="menu__link"><i class='bx bx-user-circle'></i> User Management <i class='bx bx-chevron-down arrow-icon'></i></a>
                <ul class="dropdown__menu">
                    <li class="menu__item"><a href="clients.php" class="menu__link">Clients</a></li>
                    <li class="menu__item"><a href="UM_employees.php" class="menu__link">Employees</a></li>
                    <li class="menu__item"><a href="UM_admins.php" class="menu__link">Admins</a></li>
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
            <li class="menu__item"><a href="ES.php" class="menu__link"><i class='bx bx-time'></i> Employee Scheduling</a></li>
             <li class="menu__item"><a href="admin_feedback_dashboard.php" class="menu__link "><i class='bx bx-star'></i> Feedback Overview</a></li>
            <li class="menu__item"><a href="FR.php" class="menu__link"><i class='bx bx-star'></i> Feedback & Ratings</a></li>
            <li class="menu__item"><a href="Reports.php" class="menu__link active"><i class='bx bx-file-text'></i> Reports</a></li>
               <li class="menu__item"><a href="concern.php?content=profile" class="menu__link" data-content="profile"><i class='bx bx-user'></i> Issues&Concerns</a></li>
            <li class="menu__item"><a href="admin_profile.php" class="menu__link"><i class='bx bx-user'></i> Profile</a></li>
            <li class="menu__item"><a href="javascript:void(0)" class="menu__link" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard__content">
        
        <!-- Monthly Profit Report -->
        <div class="content-container report-section">
            <h2><i class='bx bx-line-chart'></i> Monthly Income Report</h2>
            <p>Income based on materials provided and booking duration.</p>

            <div class="chart-container">
                <canvas id="profitChart"></canvas>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Income (AED)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($monthlyProfits as $month => $profit): ?>
                        <tr>
                            <td><?php echo date("F Y", strtotime($month . "-01")); ?></td>
                            <td><?php echo number_format($profit, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($monthlyProfits)): ?>
                        <tr><td colspan="2" style="text-align:center;">No income data found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Employee Ratings Summary -->
        <!-- <div class="content-container report-section">
            <h2><i class='bx bx-trophy'></i> Employee Performance Summary</h2>
            <p>Average ratings and performance metrics per employee.</p>

            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Employee Name</th>
                        <th>Position</th>
                        <th>Total Ratings</th>
                        <th>Average Rating</th>
                        <th>Lowest</th>
                        <th>Highest</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    if($employeeRatingsResult && $employeeRatingsResult->num_rows > 0):
                        while($emp = $employeeRatingsResult->fetch_assoc()): 
                            $avgRating = round($emp['avg_rating'], 1);
                            
                            $performance = 'Average';
                            $performanceClass = 'rating-3';
                            if($avgRating >= 4.5) {
                                $performance = 'Excellent';
                                $performanceClass = 'rating-5';
                            } elseif($avgRating >= 4.0) {
                                $performance = 'Good';
                                $performanceClass = 'rating-4';
                            } elseif($avgRating < 3.0) {
                                $performance = 'Poor';
                                $performanceClass = 'rating-2';
                            }
                    ?>
                        <tr>
                            <td><strong><?php echo $rank++; ?></strong></td>
                            <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($emp['position'] ?? 'N/A'); ?></td>
                            <td><?php echo $emp['total_ratings']; ?></td>
                            <td><span class="rating-stars">★</span> <?php echo $avgRating; ?>/5.0</td>
                            <td><?php echo $emp['min_rating']; ?></td>
                            <td><?php echo $emp['max_rating']; ?></td>
                            <td>
                                <span class="rating-badge <?php echo $performanceClass; ?>">
                                    <?php echo $performance; ?>
                                </span>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr><td colspan="8" style="text-align:center;">No employee ratings found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div> -->

        <!-- All Staff Ratings (Detailed) -->
        <div class="content-container report-section">
            <h2><i class='bx bx-star'></i> All Staff Ratings</h2>
            <p>Complete list of all ratings received by staff members.</p>

            <table>
                <thead>
                    <tr>
                        
                        <th>Date</th>
                        <th>Employee Name</th>
                        <th>Position</th>
                        
                        <th>Client</th>
                        <th>Service Date</th>
                        <th>Service Type</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if($ratingsResult && $ratingsResult->num_rows > 0):
                        while($rating = $ratingsResult->fetch_assoc()): 
                            $ratingClass = 'rating-' . $rating['rating'];
                    ?>
                        <tr>
                            
                            <td><?php echo date('M d, Y', strtotime($rating['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($rating['first_name'] . ' ' . $rating['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($rating['position'] ?? 'N/A'); ?></td>
                            
                            <td><?php echo htmlspecialchars($rating['client_name'] ?? 'N/A'); ?></td>
                            <td><?php echo $rating['service_date'] ? date('M d, Y', strtotime($rating['service_date'])) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($rating['service_type'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="rating-badge <?php echo $ratingClass; ?>">
                                    <span class="rating-stars">★</span> <?php echo $rating['rating']; ?>/5
                                </span>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr><td colspan="9" style="text-align:center;">No ratings data found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

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

// Chart.js - Monthly Profit Chart
const profitData = <?php echo json_encode($monthlyProfits); ?>;
const months = Object.keys(profitData).map(m => {
    const date = new Date(m + '-01');
    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
});
const profits = Object.values(profitData);

const ctx = document.getElementById('profitChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Monthly Income (AED)',
            data: profits,
            backgroundColor: 'rgba(0, 74, 128, 0.1)',
            borderColor: 'rgba(0, 74, 128, 1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointHoverRadius: 7,
            pointBackgroundColor: 'rgba(0, 74, 128, 1)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Income: AED ' + context.parsed.y.toFixed(2);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'AED ' + value.toFixed(0);
                    }
                }
            }
        }
    }
});
</script>
</body>
</html>