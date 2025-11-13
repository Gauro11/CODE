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

            echo "<script>alert('Thank you for your feedback!'); window.location.href='FR_one-time.php';</script>";
            exit;
        } else {
          echo "<script>alert('Error saving review. Please try again.');</script>";
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
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .container {
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }
        
        .header h1 {
            font-size: 2em;
            color: #007bff;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .header p {
            color: #6c757d;
            font-size: 0.95em;
        }
        
        .booking-info {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }
        
        .booking-info p {
            margin: 8px 0;
            color: #495057;
            line-height: 1.6;
        }
        
        .booking-info strong {
            color: #212529;
            font-weight: 600;
            min-width: 80px;
            display: inline-block;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.1em;
            color: #007bff;
            margin-bottom: 15px;
            font-weight: 600;
            padding-bottom: 8px;
            border-bottom: 1px solid #e9ecef;
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
            color: #dee2e6;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
        }
        
        .staff-card {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 18px;
            margin-bottom: 12px;
            transition: all 0.2s ease;
        }
        
        .staff-card:hover {
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
        }
        
        .staff-name {
            font-size: 1.05em;
            color: #212529;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .staff-position {
            font-size: 0.9em;
            color: #6c757d;
            margin-bottom: 12px;
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
            color: #dee2e6;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .small-star-rating input:checked ~ label,
        .small-star-rating label:hover,
        .small-star-rating label:hover ~ label {
            color: #ffc107;
        }
        
        .feedback-wrapper {
            position: relative;
        }
        
        textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 15px;
            font-family: inherit;
            min-height: 120px;
            resize: vertical;
            transition: all 0.2s ease;
        }
        
        textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.05em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 20px;
        }
        
        .btn-submit:hover {
            background: #0056b3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
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
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        
        .no-staff {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-content {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease;
        }
        
        .modal-header {
            padding: 20px 25px;
            background: #007bff;
            color: white;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.4em;
            font-weight: 600;
        }
        
        .close {
            color: white;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            transition: all 0.2s ease;
        }
        
        .close:hover,
        .close:focus {
            transform: scale(1.2);
        }
        
        .modal-body {
            padding: 25px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .sentiment-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
            margin: 10px 0;
        }
        
        .sentiment-badge.positive {
            background: #d4edda;
            color: #155724;
        }
        
        .sentiment-badge.negative {
            background: #f8d7da;
            color: #721c24;
        }
        
        .sentiment-badge.neutral {
            background: #fff3cd;
            color: #856404;
        }
        
        .analysis-section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #007bff;
        }
        
        .analysis-section h3 {
            color: #007bff;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        
        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 15px 0;
        }
        
        .analysis-card {
            background: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        
        .analysis-value {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .analysis-label {
            font-size: 0.85em;
            color: #6c757d;
        }
        
        .score-display {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 6px;
            margin: 15px 0;
        }
        
        .score-number {
            font-size: 48px;
            font-weight: bold;
            color: #007bff;
            margin: 10px 0;
        }
        
        .score-description {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .card { padding: 25px; }
            .header h1 { font-size: 1.6em; }
            .star-rating label { font-size: 40px; }
            .small-star-rating label { font-size: 28px; }
            .modal-content { 
                width: 95%; 
                margin: 10% auto;
            }
            .analysis-grid { 
                grid-template-columns: 1fr; 
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
       <a href="FR_recurring.php" 
   style="position: absolute; top: 10px; left: 10px; 
          background-color: #E87722; color: white; 
          padding: 6px 12px; border-radius: 5px; 
          text-decoration: none; font-weight: 500; font-size: 14px;">
    ← Back
</a>

   <div class="header" style="display: flex; align-items: center; justify-content: center; position: relative; padding: 10px 0;">
   

  
    <div style="text-align: center;">
        <h1 style="margin: 0;">Service Rating Form</h1>
        <p style="margin: 0; font-size: 13px;">Please share your experience with our service</p>
    </div>

</div>




        <div class="booking-info">
            <p><strong>Service:</strong> <?= htmlspecialchars($booking['service_type']) ?></p>
            <p><strong>Date:</strong> <?= htmlspecialchars($booking['start_date']) ?></p>
            <p><strong>Time:</strong> <?= htmlspecialchars($booking['service_time']) ?></p>
        </div>

        <?php if (empty($cleaners) && empty($drivers) && (!empty($booking['cleaners']) || !empty($booking['drivers']))): ?>
        <div class="alert">
            <strong>Notice:</strong> Some assigned staff could not be found in the system.
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="section">
                <div class="section-title">Overall Service Rating</div>
                <div class="star-rating">
                    <?php for ($i=5; $i>=1; $i--): ?>
                        <input type="radio" id="star<?= $i ?>" name="rating_stars" value="<?= $i ?>" required>
                        <label for="star<?= $i ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <?php if (!empty($cleaners)): ?>
            <div class="section">
                <div class="section-title">Rate Your Cleaners</div>
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
                <div class="section-title">Cleaners</div>
                <div class="no-staff">Staff information unavailable</div>
            </div>
            <?php endif; ?>

            <?php if (!empty($drivers)): ?>
            <div class="section">
                <div class="section-title">Rate Your Drivers</div>
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
                <div class="section-title">Drivers</div>
                <div class="no-staff">Staff information unavailable</div>
            </div>
            <?php endif; ?>

            <div class="section">
                <div class="section-title">Written Feedback</div>
                <div class="feedback-wrapper">
                    <textarea 
                        name="rating_comment" 
                        id="feedbackText"
                        placeholder="Please share your detailed feedback about the service..."
                        required
                    ></textarea>
                    
                    <button type="submit" class="btn-submit">Submit Feedback</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal -->
<div id="sentimentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Sentiment Analysis</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="modalContent"></div>
        </div>
    </div>
</div>

<script>
const feedbackText = document.getElementById('feedbackText');
const modal = document.getElementById('sentimentModal');
const closeBtn = document.getElementsByClassName('close')[0];
const modalContent = document.getElementById('modalContent');

let typingTimer;
const typingDelay = 1500;

feedbackText?.addEventListener('input', function() {
    clearTimeout(typingTimer);
    const text = this.value.trim();
    
    if (text.length < 10) {
        return;
    }
    
    typingTimer = setTimeout(() => {
        analyzeSentiment(text);
        modal.style.display = 'block';
    }, typingDelay);
});

closeBtn.onclick = function() {
    modal.style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

function analyzeSentiment(text) {
    const lowerText = text.toLowerCase();
    
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
    
    positiveWords.forEach(word => {
        const regex = new RegExp('\\b' + word + '\\b', 'gi');
        const matches = lowerText.match(regex);
        if (matches) positiveCount += matches.length;
    });
    
    negativeWords.forEach(word => {
        const regex = new RegExp('\\b' + word + '\\b', 'gi');
        const matches = lowerText.match(regex);
        if (matches) negativeCount += matches.length;
    });
    
    const totalWords = text.split(/\s+/).filter(word => word.length > 0).length;
    const valenceScore = (positiveCount * 0.5) - (negativeCount * 0.5);
    const compound = valenceScore / Math.sqrt(Math.pow(valenceScore, 2) + 15);
    
    let sentiment, badgeClass;
    if (compound >= 0.05) {
        sentiment = 'Positive';
        badgeClass = 'positive';
    } else if (compound <= -0.05) {
        sentiment = 'Negative';
        badgeClass = 'negative';
    } else {
        sentiment = 'Neutral';
        badgeClass = 'neutral';
    }
    
    modalContent.innerHTML = `
        <div style="text-align: center; margin-bottom: 20px;">
            <span class="sentiment-badge ${badgeClass}">${sentiment} Feedback</span>
        </div>
        
        <div class="analysis-section">
            <h3>Word Analysis</h3>
            <div class="analysis-grid">
                <div class="analysis-card">
                    <div class="analysis-value">${positiveCount}</div>
                    <div class="analysis-label">Positive Words</div>
                </div>
                <div class="analysis-card">
                    <div class="analysis-value">${negativeCount}</div>
                    <div class="analysis-label">Negative Words</div>
                </div>
                <div class="analysis-card">
                    <div class="analysis-value">${totalWords}</div>
                    <div class="analysis-label">Total Words</div>
                </div>
            </div>
        </div>
        
        <div class="analysis-section">
            <h3>Sentiment Score</h3>
            <div class="score-display">
                <div class="score-number">${compound.toFixed(3)}</div>
                <div class="score-description">
                    Range: -1.0 (Most Negative) to +1.0 (Most Positive)
                </div>
            </div>
        </div>
    `;
}
</script>

</body>
</html>