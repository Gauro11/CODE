<?php
session_start();
require 'connection.php';

// Check if admin is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

// Fetch total feedbacks
$total_query = "SELECT COUNT(*) as total FROM bookings WHERE rating_comment IS NOT NULL AND rating_comment != ''";
$total_result = $conn->query($total_query);
$total_feedbacks = $total_result->fetch_assoc()['total'];

// Fetch sentiment breakdown
$sentiment_query = "SELECT 
    sentiment,
    COUNT(*) as count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM bookings WHERE rating_comment IS NOT NULL AND rating_comment != '')), 1) as percentage
FROM bookings 
WHERE rating_comment IS NOT NULL AND rating_comment != ''
GROUP BY sentiment";
$sentiment_result = $conn->query($sentiment_query);

$sentiments = ['Positive' => 0, 'Neutral' => 0, 'Negative' => 0];
$sentiment_percentages = ['Positive' => 0, 'Neutral' => 0, 'Negative' => 0];

while ($row = $sentiment_result->fetch_assoc()) {
    $sentiments[$row['sentiment']] = $row['count'];
    $sentiment_percentages[$row['sentiment']] = $row['percentage'];
}

// Fetch service-based sentiment summary
$service_query = "SELECT 
    service_type,
    sentiment,
    COUNT(*) as count,
    AVG(rating_stars) as avg_rating
FROM bookings 
WHERE rating_comment IS NOT NULL AND rating_comment != ''
GROUP BY service_type, sentiment
ORDER BY service_type, sentiment";
$service_result = $conn->query($service_query);

$service_data = [];
while ($row = $service_result->fetch_assoc()) {
    $service = $row['service_type'];
    if (!isset($service_data[$service])) {
        $service_data[$service] = [
            'Positive' => 0,
            'Neutral' => 0,
            'Negative' => 0,
            'total' => 0,
            'avg_rating' => 0
        ];
    }
    $service_data[$service][$row['sentiment']] = $row['count'];
    $service_data[$service]['total'] += $row['count'];
    $service_data[$service]['avg_rating'] = round($row['avg_rating'], 1);
}

// Calculate service status
function getServiceStatus($data) {
    if ($data['total'] == 0) return ['status' => 'No Data', 'color' => '#6c757d', 'icon' => '📊'];
    
    $positive_percent = ($data['Positive'] / $data['total']) * 100;
    $negative_percent = ($data['Negative'] / $data['total']) * 100;
    
    if ($positive_percent >= 70) {
        return ['status' => 'Mostly Positive', 'color' => '#28a745', 'icon' => '😊'];
    } elseif ($positive_percent >= 50) {
        return ['status' => 'Mixed', 'color' => '#ffc107', 'icon' => '😐'];
    } else {
        return ['status' => 'Needs Improvement', 'color' => '#dc3545', 'icon' => '😔'];
    }
}

// Fetch recent feedbacks
$recent_query = "SELECT 
    b.id,
    b.service_type,
    b.service_date,
    b.rating_stars,
    b.rating_comment,
    b.sentiment,
    b.email
FROM bookings b
WHERE b.rating_comment IS NOT NULL AND b.rating_comment != ''
ORDER BY b.service_date DESC
LIMIT 10";
$recent_result = $conn->query($recent_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Dashboard - Admin</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="admin_db.css">
    <style>
        .content-container {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin: 20px;
        }

        .dashboard-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .dashboard-header h2 {
            font-size: 2em;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            color: #666;
            font-size: 1em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 20px;
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .icon {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-card .label {
            font-size: 13px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .section-box h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sentiment-bars {
            margin-top: 15px;
        }

        .sentiment-bar {
            margin-bottom: 20px;
        }

        .sentiment-bar-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .sentiment-bar-track {
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }

        .sentiment-bar-fill {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-weight: bold;
            font-size: 0.9em;
            transition: width 0.5s ease;
        }

        .positive-bar { background: linear-gradient(90deg, #28a745, #34d058); }
        .neutral-bar { background: linear-gradient(90deg, #ffc107, #ffdb4d); color: #333; }
        .negative-bar { background: linear-gradient(90deg, #dc3545, #ff5a6e); }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .service-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .service-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            transform: translateY(-3px);
        }

        .service-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .service-name {
            font-size: 1.1em;
            font-weight: bold;
            color: #333;
        }

        .service-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 1em;
            color: #ffc107;
            font-weight: bold;
        }

        .service-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85em;
            margin-bottom: 15px;
        }

        .sentiment-mini-bars {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .mini-bar {
            flex: 1;
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .mini-bar-value {
            font-size: 1.4em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .mini-bar-label {
            font-size: 0.75em;
            color: #666;
            text-transform: uppercase;
        }

        .feedback-item {
            background: white;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .feedback-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .feedback-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9em;
            color: #666;
            flex-wrap: wrap;
        }

        .feedback-stars {
            color: #ffc107;
        }

        .feedback-sentiment {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .sentiment-positive {
            background: #d4edda;
            color: #155724;
        }

        .sentiment-neutral {
            background: #fff3cd;
            color: #856404;
        }

        .sentiment-negative {
            background: #f8d7da;
            color: #721c24;
        }

        .feedback-comment {
            color: #495057;
            line-height: 1.6;
            font-size: 0.95em;
            font-style: italic;
        }

        .feedback-customer {
            margin-top: 8px;
            font-size: 0.85em;
            color: #999;
        }

        /* Sidebar dropdown fix */
        .has-dropdown .dropdown__menu {
            display: none;
        }

        .has-dropdown.open .dropdown__menu {
            display: block;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .service-grid {
                grid-template-columns: 1fr;
            }

            .feedback-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
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
            
            <li class="menu__item"><a href="ES.php" class="menu__link"><i class='bx bx-time'></i> Employee Scheduling</a></li>
            <li class="menu__item"><a href="admin_feedback_dashboard.php" class="menu__link active"><i class='bx bx-star'></i> Feedback Overview</a></li>
<!-- <li class="menu__item"><a href="FR.php" class="menu__link"><i class='bx bx-star'></i> Feedback & Ratings</a></li> -->
             
            
            <li class="menu__item"><a href="Reports.php" class="menu__link"><i class='bx bx-file-text'></i> Reports</a></li>
            <li class="menu__item"><a href="concern.php?content=profile" class="menu__link"><i class='bx bx-user'></i> Issues & Concerns</a></li>
            <li class="menu__item"><a href="admin_profile.php" class="menu__link"><i class='bx bx-user'></i> Profile</a></li>
            <li class="menu__item"><a href="javascript:void(0)" class="menu__link" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard__content">
        <section class="content__section active">
            <div class="content-container">
                <!-- Header -->
                <div class="dashboard-header">
                    <h2> Feedback Dashboard</h2>
                    <p>Monitor customer satisfaction and service performance</p>
                </div>

                <!-- Statistics Overview -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon">💬</div>
                        <div class="value"><?= $total_feedbacks ?></div>
                        <div class="label">Total Feedbacks</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">😊</div>
                        <div class="value"><?= $sentiments['Positive'] ?></div>
                        <div class="label">Positive</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">😐</div>
                        <div class="value"><?= $sentiments['Neutral'] ?></div>
                        <div class="label">Neutral</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">😔</div>
                        <div class="value"><?= $sentiments['Negative'] ?></div>
                        <div class="label">Negative</div>
                    </div>
                </div>

                <!-- Sentiment Breakdown -->
                <div class="section-box">
                    <h3>📈 Sentiment Breakdown</h3>
                    <div class="sentiment-bars">
                        <div class="sentiment-bar">
                            <div class="sentiment-bar-header">
                                <span>😊 Positive</span>
                                <span><?= $sentiment_percentages['Positive'] ?>%</span>
                            </div>
                            <div class="sentiment-bar-track">
                                <div class="sentiment-bar-fill positive-bar" style="width: <?= $sentiment_percentages['Positive'] ?>%">
                                    <?= $sentiments['Positive'] ?> feedbacks
                                </div>
                            </div>
                        </div>

                        <div class="sentiment-bar">
                            <div class="sentiment-bar-header">
                                <span>😐 Neutral</span>
                                <span><?= $sentiment_percentages['Neutral'] ?>%</span>
                            </div>
                            <div class="sentiment-bar-track">
                                <div class="sentiment-bar-fill neutral-bar" style="width: <?= $sentiment_percentages['Neutral'] ?>%">
                                    <?= $sentiments['Neutral'] ?> feedbacks
                                </div>
                            </div>
                        </div>

                        <div class="sentiment-bar">
                            <div class="sentiment-bar-header">
                                <span>😔 Negative</span>
                                <span><?= $sentiment_percentages['Negative'] ?>%</span>
                            </div>
                            <div class="sentiment-bar-track">
                                <div class="sentiment-bar-fill negative-bar" style="width: <?= $sentiment_percentages['Negative'] ?>%">
                                    <?= $sentiments['Negative'] ?> feedbacks
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service-based Sentiment Summary -->
                <div class="section-box">
                    <h3>🧹 Service-based Sentiment Summary</h3>
                    <div class="service-grid">
                        <?php foreach ($service_data as $service => $data): 
                            $status = getServiceStatus($data);
                        ?>
                        <div class="service-card">
                            <div class="service-card-header">
                                <div class="service-name"><?= htmlspecialchars($service) ?></div>
                                <div class="service-rating">
                                    ⭐ <?= $data['avg_rating'] ?>
                                </div>
                            </div>
                            
                            <div class="service-status" style="background: <?= $status['color'] ?>15; color: <?= $status['color'] ?>; border: 2px solid <?= $status['color'] ?>;">
                                <span><?= $status['icon'] ?></span>
                                <span><?= $status['status'] ?></span>
                            </div>

                            <div class="sentiment-mini-bars">
                                <div class="mini-bar">
                                    <div class="mini-bar-value" style="color: #28a745;"><?= $data['Positive'] ?></div>
                                    <div class="mini-bar-label">Positive</div>
                                </div>
                                <div class="mini-bar">
                                    <div class="mini-bar-value" style="color: #ffc107;"><?= $data['Neutral'] ?></div>
                                    <div class="mini-bar-label">Neutral</div>
                                </div>
                                <div class="mini-bar">
                                    <div class="mini-bar-value" style="color: #dc3545;"><?= $data['Negative'] ?></div>
                                    <div class="mini-bar-label">Negative</div>
                                </div>
                            </div>

                            <div style="margin-top: 10px; font-size: 0.85em; color: #666; text-align: center; padding-top: 10px; border-top: 1px solid #e9ecef;">
                                Total: <strong><?= $data['total'] ?></strong> feedbacks
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Feedbacks -->
                <div class="section-box">
                    <h3>💬 Recent Feedbacks</h3>
                    <?php while ($feedback = $recent_result->fetch_assoc()): ?>
                    <div class="feedback-item">
                        <div class="feedback-header">
                            <div class="feedback-meta">
                                <span><strong><?= htmlspecialchars($feedback['service_type']) ?></strong></span>
                                <span>📅 <?= date('M d, Y', strtotime($feedback['service_date'])) ?></span>
                                <span class="feedback-stars">
                                    <?= str_repeat('⭐', $feedback['rating_stars']) ?>
                                </span>
                            </div>
                            <span class="feedback-sentiment sentiment-<?= strtolower($feedback['sentiment']) ?>">
                                <?= $feedback['sentiment'] ?>
                            </span>
                        </div>
                        <div class="feedback-comment">
                            "<?= htmlspecialchars($feedback['rating_comment']) ?>"
                        </div>
                        <div class="feedback-customer">
                            Customer: <?= htmlspecialchars($feedback['email']) ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Are you sure you want to log out?</h3>
        <div class="modal__actions">
            <button id="cancelLogout" class="btn btn--secondary">Cancel</button>
            <button id="confirmLogout" class="btn btn--primary">Log Out</button>
        </div>
    </div>
</div>

<script>
// Handle logout modal
function showLogoutModal() {
    const logoutModal = document.getElementById('logoutModal');
    if (logoutModal) logoutModal.classList.add('show');
}

const cancelLogoutBtn = document.getElementById('cancelLogout');
const confirmLogoutBtn = document.getElementById('confirmLogout');
const logoutModal = document.getElementById('logoutModal');

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

// Mobile nav toggle
const navToggle = document.getElementById('nav-toggle');
const sidebar = document.querySelector('.dashboard__sidebar');

if (navToggle) {
    navToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
    });
}
</script>

</body>
</html>