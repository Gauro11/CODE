<?php
session_start();
require 'connection.php';
require_once 'sentiment_analyzer.php';

if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

$client_email = $_SESSION['email'];

if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid booking request.'); window.location.href='FR_one-time.php';</script>";
    exit;
}

$booking_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM bookings WHERE id=? AND email=? LIMIT 1");
$stmt->bind_param("is", $booking_id, $client_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Booking not found or does not belong to you.'); window.location.href='FR_one-time.php';</script>";
    exit;
}

$booking = $result->fetch_assoc();

$cleaner_names = !empty($booking['cleaners']) ? array_map('trim', explode(',', $booking['cleaners'])) : [];
$driver_names = !empty($booking['drivers']) ? array_map('trim', explode(',', $booking['drivers'])) : [];

// Fetch cleaners
$cleaners = [];
foreach ($cleaner_names as $full_name) {
    if (empty($full_name)) continue;
    
    $stmt_cleaner = $conn->prepare("SELECT id, first_name, last_name, position 
        FROM employees 
        WHERE CONCAT(first_name, ' ', last_name) = ? AND archived=0 
        LIMIT 1");
    $stmt_cleaner->bind_param("s", $full_name);
    $stmt_cleaner->execute();
    $result_cleaner = $stmt_cleaner->get_result();
    
    if ($result_cleaner->num_rows > 0) {
        $cleaners[] = $result_cleaner->fetch_assoc();
    } else {
        $like_name = "%$full_name%";
        $stmt_cleaner2 = $conn->prepare("SELECT id, first_name, last_name, position 
            FROM employees 
            WHERE CONCAT(first_name, ' ', last_name) LIKE ? AND archived=0 
            LIMIT 1");
        $stmt_cleaner2->bind_param("s", $like_name);
        $stmt_cleaner2->execute();
        $result_cleaner2 = $stmt_cleaner2->get_result();
        if ($result_cleaner2->num_rows > 0) {
            $cleaners[] = $result_cleaner2->fetch_assoc();
        }
    }
}

// Fetch drivers
$drivers = [];
foreach ($driver_names as $full_name) {
    if (empty($full_name)) continue;
    
    $stmt_driver = $conn->prepare("SELECT id, first_name, last_name, position 
        FROM employees 
        WHERE CONCAT(first_name, ' ', last_name) = ? AND archived=0 
        LIMIT 1");
    $stmt_driver->bind_param("s", $full_name);
    $stmt_driver->execute();
    $result_driver = $stmt_driver->get_result();
    
    if ($result_driver->num_rows > 0) {
        $drivers[] = $result_driver->fetch_assoc();
    } else {
        $like_name = "%$full_name%";
        $stmt_driver2 = $conn->prepare("SELECT id, first_name, last_name, position 
            FROM employees 
            WHERE CONCAT(first_name, ' ', last_name) LIKE ? AND archived=0 
            LIMIT 1");
        $stmt_driver2->bind_param("s", $like_name);
        $stmt_driver2->execute();
        $result_driver2 = $stmt_driver2->get_result();
        if ($result_driver2->num_rows > 0) {
            $drivers[] = $result_driver2->fetch_assoc();
        }
    }
}

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['rating_stars']) || !isset($_POST['rating_comment'])) {
        echo "<script>alert('Please provide both rating and comment.');</script>";
    } else {
        $stars = intval($_POST['rating_stars']);
        $comment = trim($_POST['rating_comment']);

        // Collect cleaner ratings
        $cleaner_ratings = [];
        foreach ($cleaners as $cleaner) {
            $employee_id = $cleaner['id'];
            if (isset($_POST["cleaner_rating_$employee_id"])) {
                $cleaner_ratings[$employee_id] = intval($_POST["cleaner_rating_$employee_id"]);
            }
        }

        // Collect driver ratings
        $driver_ratings = [];
        foreach ($drivers as $driver) {
            $employee_id = $driver['id'];
            if (isset($_POST["driver_rating_$employee_id"])) {
                $driver_ratings[$employee_id] = intval($_POST["driver_rating_$employee_id"]);
            }
        }

        // Run sentiment analysis
        $analyzer = new SentimentAnalyzer();
        $sentiment = $analyzer->analyze($comment);

        if (empty($sentiment)) {
            $sentiment = "Neutral";
        }

        // Save rating + comment + sentiment
        $update = $conn->prepare("UPDATE bookings 
            SET rating_stars=?, rating_comment=?, sentiment=?
            WHERE id=? AND email=?");
        $update->bind_param("issis", $stars, $comment, $sentiment, $booking_id, $client_email);

        if ($update->execute()) {
            // Store cleaner ratings
            foreach ($cleaner_ratings as $employee_id => $rating) {
                $stmt_cleaner = $conn->prepare("INSERT INTO staff_ratings (booking_id, employee_id, staff_type, rating, created_at) 
                    VALUES (?, ?, 'cleaner', ?, NOW())
                    ON DUPLICATE KEY UPDATE rating=?, created_at=NOW()");
                $stmt_cleaner->bind_param("iiii", $booking_id, $employee_id, $rating, $rating);
                $stmt_cleaner->execute();
            }

            // Store driver ratings
            foreach ($driver_ratings as $employee_id => $rating) {
                $stmt_driver = $conn->prepare("INSERT INTO staff_ratings (booking_id, employee_id, staff_type, rating, created_at) 
                    VALUES (?, ?, 'driver', ?, NOW())
                    ON DUPLICATE KEY UPDATE rating=?, created_at=NOW()");
                $stmt_driver->bind_param("iiii", $booking_id, $employee_id, $rating, $rating);
                $stmt_driver->execute();
            }

            $emoji = $sentiment === 'Positive' ? '😊' : ($sentiment === 'Negative' ? '😔' : '😐');
            echo "<script>alert('✅ Thank you for your feedback! {$emoji}'); window.location.href='FR_one-time.php';</script>";
            exit;
        } else {
          echo "<script>alert('❌ Error saving review. Please try again.');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Service</title>
    <link rel="stylesheet" href="client_db.css">
    <link rel="stylesheet" href="HIS_design.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        .bg-decoration {
            position: fixed;
            opacity: 0.15;
            pointer-events: none;
            z-index: 1;
            animation: float 20s infinite ease-in-out;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-40px) rotate(5deg); }
        }
        
        .bg-decoration:nth-child(1) { top: 10%; left: 5%; font-size: 60px; animation-delay: 0s; }
        .bg-decoration:nth-child(2) { top: 60%; right: 8%; font-size: 50px; animation-delay: 3s; }
        .bg-decoration:nth-child(3) { bottom: 15%; left: 10%; font-size: 55px; animation-delay: 6s; }
        
        .container {
            max-width: 650px;
            margin: 40px auto;
            padding: 20px;
            position: relative;
            z-index: 10;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }
        
        .header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .header h1 {
            font-size: 2.2em;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 0.95em;
        }
        
        .booking-info {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        
        .booking-info p {
            margin: 8px 0;
            color: #495057;
            line-height: 1.6;
        }
        
        .booking-info strong {
            color: #212529;
            font-weight: 600;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.2em;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        
        .star-rating input { display: none; }
        
        .star-rating label {
            font-size: 50px;
            color: #e0e0e0;
            cursor: pointer;
            transition: all 0.2s ease;
            filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.1));
        }
        
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
            transform: scale(1.1);
        }
        
        .staff-card {
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .staff-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }
        
        .staff-name {
            font-size: 1.1em;
            color: #333;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .staff-position {
            font-size: 0.9em;
            color: #6c757d;
            margin-bottom: 12px;
            font-style: italic;
        }
        
        .small-star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 6px;
        }
        
        .small-star-rating input { display: none; }
        
        .small-star-rating label {
            font-size: 32px;
            color: #e0e0e0;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .small-star-rating input:checked ~ label,
        .small-star-rating label:hover,
        .small-star-rating label:hover ~ label {
            color: #ffc107;
            transform: scale(1.08);
        }
        
        .feedback-wrapper {
            position: relative;
        }
        
        textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            min-height: 120px;
            resize: vertical;
            transition: all 0.3s ease;
        }
        
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .sentiment-indicator {
            margin-top: 12px;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.9em;
            text-align: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            font-weight: 500;
        }
        
        .sentiment-indicator.show { opacity: 1; }
        
        .sentiment-positive {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .sentiment-negative {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .sentiment-neutral {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            margin-top: 20px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        .alert {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-left: 4px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        
        .no-staff {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        /* VADER Analysis Section */
        #vaderAnalysis {
            margin-top: 20px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .vader-container {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            padding: 25px;
            border: 2px solid #667eea;
        }
        
        .vader-title {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.3em;
            text-align: center;
        }
        
        .vader-steps {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .vader-steps h4 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        
        .vader-step {
            margin-bottom: 15px;
        }
        
        .vader-step strong {
            color: #667eea;
            display: block;
            margin-bottom: 5px;
        }
        
        .vader-step p {
            margin: 5px 0;
            color: #666;
            font-size: 0.95em;
            line-height: 1.6;
        }
        
        .vader-formula {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            margin: 10px 0;
        }
        
        .vader-step ul {
            margin: 10px 0 0 20px;
            color: #666;
            font-size: 0.95em;
        }
        
        .vader-step ul li {
            margin: 5px 0;
        }
        
        .analysis-results {
            background: white;
            border-radius: 8px;
            padding: 20px;
        }
        
        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .analysis-card {
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .analysis-card.positive {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
        }
        
        .analysis-card.negative {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        }
        
        .analysis-card.neutral {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
        }
        
        .analysis-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .analysis-label {
            font-size: 0.9em;
        }
        
        .compound-score {
            margin-bottom: 20px;
        }
        
        .compound-score h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        
        .score-bar-container {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        
        .score-bar {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 8px;
        }
        
        .score-track {
            flex: 1;
            height: 30px;
            background: linear-gradient(to right, #dc3545 0%, #ffc107 50%, #28a745 100%);
            border-radius: 15px;
            position: relative;
            overflow: hidden;
        }
        
        .score-marker {
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            background: white;
            border: 3px solid #333;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            transition: left 0.3s ease;
        }
        
        .score-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.85em;
            color: #666;
        }
        
        .score-value {
            font-weight: bold;
            color: #333;
            font-size: 1.2em;
        }
        
        .sentiment-result {
            margin-bottom: 20px;
        }
        
        .sentiment-box {
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .sentiment-emoji {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .sentiment-label {
            font-size: 24px;
            font-weight: bold;
        }
        
        .word-badges {
            margin-bottom: 15px;
        }
        
        .word-badges h4 {
            margin-bottom: 8px;
            font-size: 1em;
        }
        
        .badge-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .word-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9em;
        }
        
        .word-badge.positive {
            background: #d4edda;
            color: #155724;
        }
        
        .word-badge.negative {
            background: #f8d7da;
            color: #721c24;
        }
        
        .empty-analysis {
            text-align: center;
            padding: 20px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .card { padding: 25px; }
            .header h1 { font-size: 1.8em; }
            .star-rating label { font-size: 40px; }
            .small-star-rating label { font-size: 28px; }
            .vader-container { padding: 20px; }
            .vader-title { font-size: 1.1em; }
            .analysis-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="bg-decoration">⭐</div>
<div class="bg-decoration">✨</div>
<div class="bg-decoration">😊</div>

<div class="container">
    <div class="card">
        <div class="header">
            <h1>Rate Your Service</h1>
            
        </div>

        <div class="booking-info">
            <p><strong>Service:</strong> <?= htmlspecialchars($booking['service_type']) ?></p>
            <p><strong>Date:</strong> <?= htmlspecialchars($booking['service_date']) ?></p>
            <p><strong>Time:</strong> <?= htmlspecialchars($booking['service_time']) ?></p>
        </div>

        <?php if (empty($cleaners) && empty($drivers) && (!empty($booking['cleaners']) || !empty($booking['drivers']))): ?>
        <div class="alert">
            <strong>⚠️ Notice:</strong> Some assigned staff could not be found in the system.
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="section">
                <div class="section-title">⭐ Overall Service Rating</div>
                <div class="star-rating">
                    <?php for ($i=5; $i>=1; $i--): ?>
                        <input type="radio" id="star<?= $i ?>" name="rating_stars" value="<?= $i ?>" required>
                        <label for="star<?= $i ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <?php if (!empty($cleaners)): ?>
            <div class="section">
                <div class="section-title"> Rate Your Cleaners</div>
                <?php foreach ($cleaners as $cleaner): ?>
                <div class="staff-card">
                    <div class="staff-name"><?= htmlspecialchars($cleaner['first_name'] . ' ' . $cleaner['last_name']) ?></div>
                    <?php if (!empty($cleaner['position'])): ?>
                    <div class="staff-position"><?= htmlspecialchars($cleaner['position']) ?></div>
                    <?php endif; ?>
                    <div class="small-star-rating">
                        <?php for ($i=5; $i>=1; $i--): ?>
                            <input type="radio" id="cleaner_<?= $cleaner['id'] ?>_<?= $i ?>" 
                                   name="cleaner_rating_<?= $cleaner['id'] ?>" value="<?= $i ?>" required>
                            <label for="cleaner_<?= $cleaner['id'] ?>_<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php elseif (!empty($booking['cleaners'])): ?>
            <div class="section">
                <div class="section-title"> Cleaners</div>
                <div class="no-staff">⚠️ Staff information unavailable</div>
            </div>
            <?php endif; ?>

            <?php if (!empty($drivers)): ?>
            <div class="section">
                <div class="section-title"> Rate Your Drivers</div>
                <?php foreach ($drivers as $driver): ?>
                <div class="staff-card">
                    <div class="staff-name"><?= htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']) ?></div>
                    <?php if (!empty($driver['position'])): ?>
                    <div class="staff-position"><?= htmlspecialchars($driver['position']) ?></div>
                    <?php endif; ?>
                    <div class="small-star-rating">
                        <?php for ($i=5; $i>=1; $i--): ?>
                            <input type="radio" id="driver_<?= $driver['id'] ?>_<?= $i ?>" 
                                   name="driver_rating_<?= $driver['id'] ?>" value="<?= $i ?>" required>
                            <label for="driver_<?= $driver['id'] ?>_<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php elseif (!empty($booking['drivers'])): ?>
            <div class="section">
                <div class="section-title"> Drivers</div>
                <div class="no-staff">⚠️ Staff information unavailable</div>
            </div>
            <?php endif; ?>

            <div class="section">
                <div class="section-title">💬Written Feedback</div>
                <div class="feedback-wrapper">
                    <textarea 
                        name="rating_comment" 
                        id="feedbackText"
                        placeholder="Share your experience with us... How was the service? What did you like or dislike?"
                        required
                    ></textarea>
                    <div id="sentimentIndicator" class="sentiment-indicator"></div>
                     <button type="submit" class="btn-submit">Submit Feedback</button>
                    
                    <!-- VADER Analysis Section -->
                    <div id="vaderAnalysis" style="display: none;">
                        <!-- <div class="vader-container"> -->
                            <!-- <h3 class="vader-title">📊 VADER Sentiment Analysis</h3> -->
                            
                            <!-- <div class="vader-steps">
                                <h4>Simplified Steps in VADER Sentiment Analysis</h4>
                                
                                <div class="vader-step">
                                    <strong>5. Result Classification</strong>
                                    <p>The final sentiment is labeled as:</p>
                                    <ul>
                                        <li><strong>Positive</strong> if the compound score is ≥ 0.05</li>
                                        <li><strong>Negative</strong> if the compound score is ≤ -0.05</li>
                                        <li><strong>Neutral</strong> if the score is between -0.05 and 0.05</li>
                                    </ul>
                                </div>
                                    <strong>1. Text Cleaning</strong>
                                    <p>All feedback is converted to lowercase and unnecessary symbols or punctuation are removed to make the text uniform.</p>
                                </div>
                                
                                <div class="vader-step">
                                    <strong>2. Word Scoring</strong>
                                    <p>VADER checks each word in the feedback using its built-in dictionary, where every word already has an assigned emotion score (positive, negative, or neutral).</p>
                                </div>
                                
                                <div class="vader-step">
                                    <strong>3. Context Adjustment</strong>
                                    <p>The system adjusts scores depending on nearby words or punctuation (for example, <em>"not bad"</em> becomes slightly positive and <em>"very good!"</em> becomes stronger in positivity).</p>
                                </div>
                                
                                <div class="vader-step">
                                    <strong>4. Sentiment Calculation</strong>
                                    <p>All individual scores are added together to produce a total valence score, which is then normalized using VADER's compound formula:</p>
                                    <div class="vader-formula">
                                        <div style="font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.8;">
                                            <div>Compound = Sum of valence scores</div>
                                            <div style="border-top: 2px solid #333; padding-top: 5px; margin-top: 5px;">
                                                √((Sum of valence scores)² + 15)
                                            </div>
                                        </div>
                                    </div>
                                    <p>This formula converts the total sentiment value into a range between <strong>-1</strong> (most negative) and <strong>+1</strong> (most positive).</p>
                                </div>
                                
                              <div class="vader-step">
                                    <strong>5. Result Classification</strong>
                                    <p>The final sentiment is labeled as:</p>
                                    <ul>
                                        <li><strong>Positive</strong> if the compound score is ≥ 0.05</li>
                                        <li><strong>Negative</strong> if the compound score is ≤ -0.05</li>
                                        <li><strong>Neutral</strong> if the score is between -0.05 and 0.05</li>
                                    </ul>
                                </div>
                            </div> -->
                            
                            <!-- <div id="analysisResult" class="analysis-results">
                                <div class="empty-analysis">
                                    Start typing to see real-time analysis...
                                </div>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>

           
        </form>
    </div>
</div>

<script>
const feedbackText = document.getElementById('feedbackText');
const sentimentIndicator = document.getElementById('sentimentIndicator');
const vaderAnalysis = document.getElementById('vaderAnalysis');
const analysisResult = document.getElementById('analysisResult');

let typingTimer;
const typingDelay = 1000;

feedbackText?.addEventListener('input', function() {
    clearTimeout(typingTimer);
    const text = this.value.trim();
    
    if (text.length < 10) {
        sentimentIndicator.classList.remove('show');
        vaderAnalysis.style.display = 'none';
        return;
    }
    
    // Show the VADER analysis section
    vaderAnalysis.style.display = 'block';
    
    typingTimer = setTimeout(() => {
        analyzeSentiment(text);
    }, typingDelay);
});

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
    
    // Update sentiment indicator
    sentimentIndicator.classList.remove('sentiment-positive', 'sentiment-negative', 'sentiment-neutral');
    sentimentIndicator.textContent = `${emoji} Your feedback sounds ${sentiment.toLowerCase()}!`;
    sentimentIndicator.className = `sentiment-indicator ${sentimentClass} show`;
    
    // Calculate position for marker (0 to 100%)
    const markerPosition = ((compound + 1) / 2 * 100);
    
    // Update detailed analysis
    analysisResult.innerHTML = `
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
}
</script>

</body>
</html>