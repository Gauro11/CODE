<?php
session_start();
require 'connection.php';

// ✅ Ensure user is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

$client_email = $_SESSION['email'];

// ✅ Booking ID required
if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid booking request.'); window.location.href='mybookings.php';</script>";
    exit;
}

$booking_id = intval($_GET['id']);

// ✅ Fetch booking that belongs to logged-in user
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id=? AND email=? LIMIT 1");
$stmt->bind_param("is", $booking_id, $client_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Booking not found or does not belong to you.'); window.location.href='FR_one-time.php';</script>";
    exit;
}

$booking = $result->fetch_assoc();

// ✅ Handle rating submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['rating_stars']) || !isset($_POST['rating_comment'])) {
        echo "<script>alert('Please provide both rating and comment.');</script>";
    } else {
        $stars = intval($_POST['rating_stars']);
        $comment = trim($_POST['rating_comment']);

        // ✅ Run Python Sentiment Analysis
        $escapedComment = escapeshellarg($comment);
        $sentiment = trim(shell_exec("python sentiment.py $escapedComment"));

        // ✅ Fallback if python didn’t return anything
        if (!$sentiment) {
            $sentiment = "Unknown";
        }

        // ✅ Save rating + comment + sentiment
        $update = $conn->prepare("UPDATE bookings 
            SET rating_stars=?, rating_comment=?, sentiment=?
            WHERE id=? AND email=?");

        $update->bind_param("issis", $stars, $comment, $sentiment, $booking_id, $client_email);

        if ($update->execute()) {
            echo "<script>alert('Thank you! Sentiment: {$sentiment}'); window.location.href='FR_one-time.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error saving review. Try again.');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate Your Service</title>
    <link rel="stylesheet" href="client_db.css">
    <link rel="stylesheet" href="HIS_design.css">

    <style>
        body { background-color: #f4f7ff; font-family: Arial; }
        .form-container {
            width: 100%;
            max-width: 500px;
            margin: 70px auto;
            border-radius: 8px;
            padding: 25px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,.1);
        }
        .form-header { text-align: center; color: #004A80; font-size: 1.8em; margin-bottom: 20px; }
        .job-details { font-size: 1em; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #ddd; }
        .job-details strong { color: #333; }
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; margin-bottom: 10px; }
        .star-rating input { display: none; }
        .star-rating label {
            font-size: 40px; color: #ccc; cursor: pointer; padding: 0 5px; transition: color 0.2s;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #FFC107; }
        textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; margin: 8px 0; min-height: 90px; }
        .btn-submit {
            width: 100%; background-color: #E87722; padding: 12px; border: none; color: #fff;
            font-size: 1.1em; border-radius: 5px; cursor: pointer; margin-top: 10px;
        }
        .btn-submit:hover { background-color: #d06a1d; }
    </style>
</head>
<body>

<div class="form-container">
    <h2 class="form-header">Rate Your Service</h2>

    <div class="job-details">
        <p><strong>Service:</strong> <?= $booking['service_type'] ?></p>
        <p><strong>Date:</strong> <?= $booking['service_date'] ?></p>
        <p><strong>Time:</strong> <?= $booking['service_time'] ?></p>
    </div>

    <form method="POST">
        <h4>Rate (1–5 Stars)</h4>
        <div class="star-rating">
            <?php for ($i=5; $i>=1; $i--): ?>
                <input type="radio" id="star<?= $i ?>" name="rating_stars" value="<?= $i ?>" required>
                <label for="star<?= $i ?>">★</label>
            <?php endfor; ?>
        </div>

        <h4>Write Feedback</h4>
        <textarea name="rating_comment" placeholder="Write your feedback here..."></textarea>

        <button type="submit" class="btn-submit">Submit Feedback</button>
    </form>
</div>

</body>
</html>
