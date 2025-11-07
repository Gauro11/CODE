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
            cursor: pointer;
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

        /* VADER Modal Styles */
        .vader-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            align-items: center;
            justify-content: center;
        }

        .vader-modal.show {
            display: flex;
        }

        .vader-modal-content {
            background-color: #fff;
            border-radius: 16px;
            padding: 30px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .vader-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .vader-modal-header h3 {
            font-size: 1.8em;
            color: #333;
            margin: 0;
        }

        .vader-close-btn {
            background: none;
            border: none;
            font-size: 28px;
            color: #999;
            cursor: pointer;
            transition: color 0.3s;
        }

        .vader-close-btn:hover {
            color: #333;
        }

        .feedback-context {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .feedback-context-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .feedback-context-text {
            font-style: italic;
            color: #555;
            line-height: 1.6;
        }

        .vader-container {
            padding: 20px 0;
        }

        .vader-title {
            font-size: 1.5em;
            color: #667eea;
            margin-bottom: 20px;
            text-align: center;
        }

        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .analysis-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s;
        }

        .analysis-card:hover {
            transform: translateY(-5px);
        }

        .analysis-value {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .analysis-label {
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .compound-score {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .compound-score h4 {
            color: #333;
            margin-bottom: 15px;
        }

        .score-bar-container {
            margin: 20px 0;
        }

        .score-marker {
            width: 20px;
            height: 20px;
            background: #667eea;
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .score-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.85em;
            color: #666;
        }

        .score-value {
            font-weight: bold;
            color: #667eea;
            font-size: 1.2em;
        }

        .sentiment-result {
            margin-top: 25px;
        }

        .sentiment-box {
            padding: 25px;
            border-radius: 12px;
            text-align: center;
        }

        .sentiment-emoji {
            font-size: 4em;
            margin-bottom: 10px;
        }

        .sentiment-label {
            font-size: 1.5em;
            font-weight: bold;
        }

        .word-badges {
            margin-top: 20px;
        }

        .word-badges h4 {
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .badge-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .word-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .word-badge.positive {
            background: #d4edda;
            color: #155724;
        }

        .word-badge.negative {
            background: #f8d7da;
            color: #721c24;
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

            .analysis-grid {
                grid-template-columns: 1fr;
            }

            .vader-modal-content {
                padding: 20px;
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
                    <h2>Feedback Dashboard</h2>
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
                    <h3>💬 Recent Feedbacks (Click to see VADER Analysis)</h3>
                    <?php while ($feedback = $recent_result->fetch_assoc()): ?>
                    <div class="feedback-item" onclick="showVaderAnalysis(this)" 
                         data-comment="<?= htmlspecialchars($feedback['rating_comment']) ?>"
                         data-service="<?= htmlspecialchars($feedback['service_type']) ?>"
                         data-date="<?= date('M d, Y', strtotime($feedback['service_date'])) ?>"
                         data-stars="<?= $feedback['rating_stars'] ?>"
                         data-sentiment="<?= $feedback['sentiment'] ?>"
                         data-email="<?= htmlspecialchars($feedback['email']) ?>">
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

<!-- VADER Analysis Modal -->
<div id="vaderModal" class="vader-modal">
    <div class="vader-modal-content">
        <div class="vader-modal-header">
            <h3>📊 VADER Sentiment Analysis</h3>
            <button class="vader-close-btn" onclick="closeVaderModal()">&times;</button>
        </div>
        
        <div class="feedback-context">
            <div class="feedback-context-title">Feedback Details:</div>
            <div id="feedbackContext"></div>
            <div class="feedback-context-text" id="feedbackText"></div>
        </div>
        
        <div class="vader-container">
            <div id="analysisResult" class="analysis-results"></div>
        </div>
    </div>
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
// VADER Sentiment Analysis Function
function analyzeSentiment(text) {
    const lowerText = text.toLowerCase();
    
    // Enhanced word lists for better detection
    const positiveWords = [
        'excellent', 'amazing', 'wonderful', 'great', 'good', 'love', 'best', 
        'awesome', 'perfect', 'beautiful', 'happy', 'clean', 'professional', 
        'friendly', 'helpful', 'recommend', 'fantastic', 'outstanding', 'superb', 
        'brilliant', 'exceptional', 'impressive', 'satisfied', 'pleased', 'delighted',
        'efficient', 'thorough', 'punctual', 'polite', 'courteous', 'neat', 
        'meticulous', 'reliable', 'trustworthy', 'quality'
    ];
    
    const negativeWords = [
        'terrible', 'awful', 'horrible', 'bad', 'poor', 'worst', 'hate', 
        'disappointing', 'dirty', 'rude', 'unprofessional', 'late', 'slow', 
        'problem', 'issue', 'messy', 'unclean', 'careless', 'sloppy', 'lazy',
        'incomplete', 'unsatisfactory', 'unacceptable', 'disappointed', 'frustrating',
        'inadequate', 'subpar', 'inferior', 'deficient', 'lacking'
    ];
    
    let positiveCount = 0;
    let negativeCount = 0;
    let positiveWordsFound = [];
    let negativeWordsFound = [];
    
    // Count positive words
    positiveWords.forEach(word => {
        const regex = new RegExp('\\b' + word + '\\b', 'gi');
        const matches = lowerText.match(regex);
        if (matches) {
            positiveCount += matches.length;
            if (!positiveWordsFound.includes(word)) {
                positiveWordsFound.push(word);
            }
        }
    });
    
    // Count negative words
    negativeWords.forEach(word => {
        const regex = new RegExp('\\b' + word + '\\b', 'gi');
        const matches = lowerText.match(regex);
        if (matches) {
            negativeCount += matches.length;
            if (!negativeWordsFound.includes(word)) {
                negativeWordsFound.push(word);
            }
        }
    });
    
    // Calculate total words
    const totalWords = text.split(/\s+/).filter(word => word.length > 0).length;
    
    // Calculate valence score
    const valenceScore = (positiveCount * 0.5) - (negativeCount * 0.5);
    
    // Calculate compound score using VADER-like formula
    const compound = valenceScore / Math.sqrt(Math.pow(valenceScore, 2) + 15);
    
    // Determine sentiment based on compound score
    let sentiment, sentimentClass, emoji, sentimentColor;
    if (compound >= 0.05) {
        sentiment = 'Positive';
        sentimentClass = 'sentiment-positive';
        emoji = '😊';
        sentimentColor = '#155724';
    } else if (compound <= -0.05) {
        sentiment = 'Negative';
        sentimentClass = 'sentiment-negative';
        emoji = '😔';
        sentimentColor = '#721c24';
    } else {
        sentiment = 'Neutral';
        sentimentClass = 'sentiment-neutral';
        emoji = '😐';
        sentimentColor = '#856404';
    }
    
    // Calculate position for marker (0 to 100%)
    const markerPosition = ((compound + 1) / 2 * 100);
    
    // Build analysis HTML
    let html = `
        <div style="margin-bottom: 20px;">
            <h4 style="color: #333; margin-bottom: 10px; font-size: 1.1em;">Analysis Results:</h4>
            <div class="analysis-grid">
                <div class="analysis-card positive">
                    <div class="analysis-value" style="color: #155724;">${positiveCount}</div>
                    <div class="analysis-label" style="color: #155724;">Positive Words</div>
                </div>
                <div class="analysis-card negative">
                    <div class="analysis-value" style="color: #721c24;">${negativeCount}</div>
                    <div class="analysis-label" style="color: #721c24;">Negative Words</div>
                </div>
                <div class="analysis-card neutral">
                    <div class="analysis-value" style="color: #0c5460;">${totalWords}</div>
                    <div class="analysis-label" style="color: #0c5460;">Total Words</div>
                </div>
            </div>
        </div>
        
        <div class="compound-score">
            <h4>Compound Score:</h4>
            <div class="score-bar-container">
                <div class="score-bar">
                    <div style="position: relative; width: 100%; height: 50px;">
                        <!-- Score track with sections -->
                        <div style="display: flex; width: 100%; height: 30px; border-radius: 15px; overflow: hidden;">
                            <!-- Negative zone: -1.0 to -0.05 (47.5% of width) -->
                            <div style="width: 47.5%; background: linear-gradient(to right, #dc3545, #e67e87); position: relative;">
                                <span style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); font-size: 0.7em; color: white; font-weight: bold;">-0.05</span>
                            </div>
                            <!-- Neutral zone: -0.05 to 0.05 (5% of width) -->
                            <div style="width: 5%; background: #ffc107; position: relative;">
                                <span style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); font-size: 0.6em; color: #333; font-weight: bold;">0</span>
                            </div>
                            <!-- Positive zone: 0.05 to 1.0 (47.5% of width) -->
                            <div style="width: 47.5%; background: linear-gradient(to right, #8bc34a, #28a745); position: relative;">
                                <span style="position: absolute; left: 5px; top: 50%; transform: translateY(-50%); font-size: 0.7em; color: white; font-weight: bold;">+0.05</span>
                            </div>
                        </div>
                        <!-- Marker -->
                        <div class="score-marker" style="position: absolute; left: ${markerPosition}%; top: 15px; transform: translateX(-50%);"></div>
                    </div>
                </div>
                <div class="score-labels" style="margin-top: 10px;">
                    <span>-1.0 (Negative)</span>
                    <span class="score-value">${compound.toFixed(3)}</span>
                    <span>+1.0 (Positive)</span>
                </div>
                <div style="text-align: center; margin-top: 8px; font-size: 0.8em; color: #666;">
                    <strong>Neutral Zone:</strong> -0.05 to +0.05
                </div>
            </div>
        </div>
        
        <div class="sentiment-result">
            <h4 style="color: #333; margin-bottom: 10px; font-size: 1.1em;">Overall Sentiment:</h4>
            <div class="sentiment-box" style="background: ${sentiment === 'Positive' ? 'linear-gradient(135deg, #d4edda, #c3e6cb)' : sentiment === 'Negative' ? 'linear-gradient(135deg, #f8d7da, #f5c6cb)' : 'linear-gradient(135deg, #fff3cd, #ffeaa7)'};">
                <div class="sentiment-emoji">${emoji}</div>
                <div class="sentiment-label" style="color: ${sentimentColor};">${sentiment}</div>
            </div>
        </div>
        
        ${positiveWordsFound.length > 0 ? `
        <div class="word-badges">
            <h4 style="color: #155724;">✓ Positive words detected:</h4>
            <div class="badge-container">
                ${positiveWordsFound.map(word => `<span class="word-badge positive">${word}</span>`).join('')}
            </div>
        </div>
        ` : ''}
        
        ${negativeWordsFound.length > 0 ? `
        <div class="word-badges">
            <h4 style="color: #721c24;">✗ Negative words detected:</h4>
            <div class="badge-container">
                ${negativeWordsFound.map(word => `<span class="word-badge negative">${word}</span>`).join('')}
            </div>
        </div>
        ` : ''}
    `;
    
    return html;
}

// Show VADER Analysis Modal
function showVaderAnalysis(element) {
    const comment = element.getAttribute('data-comment');
    const service = element.getAttribute('data-service');
    const date = element.getAttribute('data-date');
    const stars = element.getAttribute('data-stars');
    const sentiment = element.getAttribute('data-sentiment');
    const email = element.getAttribute('data-email');
    
    // Update feedback context
    const contextHTML = `
        <div style="margin-bottom: 10px;">
            <strong>Service:</strong> ${service} | 
            <strong>Date:</strong> ${date} | 
            <strong>Rating:</strong> ${stars}⭐ | 
            <strong>Customer:</strong> ${email}
        </div>
    `;
    document.getElementById('feedbackContext').innerHTML = contextHTML;
    document.getElementById('feedbackText').innerHTML = `"${comment}"`;
    
    // Analyze sentiment and display results
    const analysisHTML = analyzeSentiment(comment);
    document.getElementById('analysisResult').innerHTML = analysisHTML;
    
    // Show modal
    document.getElementById('vaderModal').classList.add('show');
}

// Close VADER Modal
function closeVaderModal() {
    document.getElementById('vaderModal').classList.remove('show');
}

// Close modal when clicking outside
document.getElementById('vaderModal').addEventListener('click', function(e) {
    if (e.target.id === 'vaderModal') {
        closeVaderModal();
    }
});

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