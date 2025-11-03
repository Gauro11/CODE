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
    echo "<script>alert('Invalid booking request.'); window.location.href='FR_one-time.php';</script>";
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

// ✅ Parse cleaner and driver NAMES (stored as comma-separated names)
$cleaner_names = !empty($booking['cleaners']) ? array_map('trim', explode(',', $booking['cleaners'])) : [];
$driver_names = !empty($booking['drivers']) ? array_map('trim', explode(',', $booking['drivers'])) : [];

// ✅ Fetch cleaner details by matching full names
$cleaners = [];
foreach ($cleaner_names as $full_name) {
    if (empty($full_name)) continue;
    
    // Split name into parts (first name and last name)
    $name_parts = explode(' ', $full_name, 2);
    $first_name = $name_parts[0];
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
    
    // Try to find employee by full name match
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
        // Try partial match if exact match fails
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

// ✅ Fetch driver details by matching full names
$drivers = [];
foreach ($driver_names as $full_name) {
    if (empty($full_name)) continue;
    
    // Split name into parts
    $name_parts = explode(' ', $full_name, 2);
    $first_name = $name_parts[0];
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
    
    // Try to find employee by full name match
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
        // Try partial match if exact match fails
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

// ✅ Handle rating submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['rating_stars']) || !isset($_POST['rating_comment'])) {
        echo "<script>alert('Please provide both rating and comment.');</script>";
    } else {
        $stars = intval($_POST['rating_stars']);
        $comment = trim($_POST['rating_comment']);

        // ✅ Collect cleaner ratings
        $cleaner_ratings = [];
        foreach ($cleaners as $cleaner) {
            $employee_id = $cleaner['id'];
            if (isset($_POST["cleaner_rating_$employee_id"])) {
                $cleaner_ratings[$employee_id] = intval($_POST["cleaner_rating_$employee_id"]);
            }
        }

        // ✅ Collect driver ratings
        $driver_ratings = [];
        foreach ($drivers as $driver) {
            $employee_id = $driver['id'];
            if (isset($_POST["driver_rating_$employee_id"])) {
                $driver_ratings[$employee_id] = intval($_POST["driver_rating_$employee_id"]);
            }
        }

        // ✅ Run Python Sentiment Analysis
        $escapedComment = escapeshellarg($comment);
        $sentiment = trim(shell_exec("py sentiment.py $escapedComment"));

        // ✅ Fallback if python didn't return anything
        if (!$sentiment) {
            $sentiment = "Unknown";
        }

        // ✅ Save rating + comment + sentiment
        $update = $conn->prepare("UPDATE bookings 
            SET rating_stars=?, rating_comment=?, sentiment=?
            WHERE id=? AND email=?");

        $update->bind_param("issis", $stars, $comment, $sentiment, $booking_id, $client_email);

        if ($update->execute()) {
            // ✅ Store individual cleaner ratings
            foreach ($cleaner_ratings as $employee_id => $rating) {
                $stmt_cleaner = $conn->prepare("INSERT INTO staff_ratings (booking_id, employee_id, staff_type, rating, created_at) 
                    VALUES (?, ?, 'cleaner', ?, NOW())
                    ON DUPLICATE KEY UPDATE rating=?, created_at=NOW()");
                $stmt_cleaner->bind_param("iiii", $booking_id, $employee_id, $rating, $rating);
                $stmt_cleaner->execute();
            }

            // ✅ Store individual driver ratings
            foreach ($driver_ratings as $employee_id => $rating) {
                $stmt_driver = $conn->prepare("INSERT INTO staff_ratings (booking_id, employee_id, staff_type, rating, created_at) 
                    VALUES (?, ?, 'driver', ?, NOW())
                    ON DUPLICATE KEY UPDATE rating=?, created_at=NOW()");
                $stmt_driver->bind_param("iiii", $booking_id, $employee_id, $rating, $rating);
                $stmt_driver->execute();
            }

            echo "<script>alert('Thank you! Your feedback has been submitted. Sentiment: {$sentiment}'); window.location.href='FR_one-time.php';</script>";
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
            max-width: 600px;
            margin: 70px auto;
            border-radius: 8px;
            padding: 25px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,.1);
        }
        .form-header { text-align: center; color: #004A80; font-size: 1.8em; margin-bottom: 20px; }
        .job-details { font-size: 1em; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #ddd; }
        .job-details strong { color: #333; }
        
        .rating-section { margin: 25px 0; padding: 20px; background: #f9f9f9; border-radius: 5px; }
        .rating-section h4 { color: #004A80; margin-bottom: 15px; }
        
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; margin-bottom: 10px; }
        .star-rating input { display: none; }
        .star-rating label {
            font-size: 40px; color: #ccc; cursor: pointer; padding: 0 5px; transition: color 0.2s;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #FFC107; }
        
        .staff-rating { margin: 15px 0; padding: 15px; background: #fff; border-radius: 5px; border: 1px solid #e0e0e0; }
        .staff-rating h5 { margin: 0 0 10px 0; color: #333; font-size: 1.1em; }
        .staff-position { font-size: 0.9em; color: #777; margin-bottom: 8px; }
        
        .small-star-rating { display: flex; flex-direction: row-reverse; justify-content: center; margin-bottom: 5px; }
        .small-star-rating input { display: none; }
        .small-star-rating label {
            font-size: 28px; color: #ccc; cursor: pointer; padding: 0 3px; transition: color 0.2s;
        }
        .small-star-rating input:checked ~ label,
        .small-star-rating label:hover,
        .small-star-rating label:hover ~ label { color: #FFC107; }
        
        textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; margin: 8px 0; min-height: 90px; box-sizing: border-box; }
        .btn-submit {
            width: 100%; background-color: #E87722; padding: 12px; border: none; color: #fff;
            font-size: 1.1em; border-radius: 5px; cursor: pointer; margin-top: 10px;
        }
        .btn-submit:hover { background-color: #d06a1d; }
        
        .no-staff { color: #777; font-style: italic; text-align: center; padding: 20px; background: #fff; border-radius: 5px; }
        .debug-info { background: #fffacd; padding: 10px; margin: 10px 0; border-radius: 5px; font-size: 0.9em; }
    </style>
</head>
<body>

<div class="form-container">
    <h2 class="form-header">Rate Your Service</h2>

    <div class="job-details">
        <p><strong>Service:</strong> <?= htmlspecialchars($booking['service_type']) ?></p>
        <p><strong>Date:</strong> <?= htmlspecialchars($booking['service_date']) ?></p>
        <p><strong>Time:</strong> <?= htmlspecialchars($booking['service_time']) ?></p>
    </div>

    <!-- 🔍 DEBUG INFO -->
    <?php if (empty($cleaners) && empty($drivers) && (!empty($booking['cleaners']) || !empty($booking['drivers']))): ?>
    <div class="debug-info">
        <strong>⚠️ Name Matching Issue:</strong><br>
        The names stored in the booking don't match any employees in the database.<br>
        <strong>Cleaners:</strong> <?= htmlspecialchars($booking['cleaners'] ?? 'None') ?><br>
        <strong>Drivers:</strong> <?= htmlspecialchars($booking['drivers'] ?? 'None') ?><br>
        <em>Please check if these names exactly match the first_name + last_name in the employees table.</em>
    </div>
    <?php endif; ?>

    <form method="POST">
        <!-- Overall Service Rating -->
        <div class="rating-section">
            <h4>Overall Service Rating</h4>
            <div class="star-rating">
                <?php for ($i=5; $i>=1; $i--): ?>
                    <input type="radio" id="star<?= $i ?>" name="rating_stars" value="<?= $i ?>" required>
                    <label for="star<?= $i ?>">★</label>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Cleaner Ratings -->
        <?php if (!empty($cleaners)): ?>
        <div class="rating-section">
            <h4>Rate Your Cleaners</h4>
            <?php foreach ($cleaners as $cleaner): ?>
            <div class="staff-rating">
                <h5><?= htmlspecialchars($cleaner['first_name'] . ' ' . $cleaner['last_name']) ?></h5>
                <?php if (!empty($cleaner['position'])): ?>
                <div class="staff-position">Position: <?= htmlspecialchars($cleaner['position']) ?></div>
                <?php endif; ?>
                <div class="small-star-rating">
                    <?php for ($i=5; $i>=1; $i--): ?>
                        <input type="radio" id="cleaner_star<?= $cleaner['id'] ?>_<?= $i ?>" 
                               name="cleaner_rating_<?= $cleaner['id'] ?>" value="<?= $i ?>" required>
                        <label for="cleaner_star<?= $cleaner['id'] ?>_<?= $i ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php elseif (!empty($booking['cleaners'])): ?>
        <div class="rating-section">
            <h4>Rate Your Cleaners</h4>
            <div class="no-staff">⚠️ Cleaners could not be found in employee database</div>
        </div>
        <?php endif; ?>

        <!-- Driver Ratings -->
        <?php if (!empty($drivers)): ?>
        <div class="rating-section">
            <h4>Rate Your Drivers</h4>
            <?php foreach ($drivers as $driver): ?>
            <div class="staff-rating">
                <h5><?= htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']) ?></h5>
                <?php if (!empty($driver['position'])): ?>
                <div class="staff-position">Position: <?= htmlspecialchars($driver['position']) ?></div>
                <?php endif; ?>
                <div class="small-star-rating">
                    <?php for ($i=5; $i>=1; $i--): ?>
                        <input type="radio" id="driver_star<?= $driver['id'] ?>_<?= $i ?>" 
                               name="driver_rating_<?= $driver['id'] ?>" value="<?= $i ?>" required>
                        <label for="driver_star<?= $driver['id'] ?>_<?= $i ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php elseif (!empty($booking['drivers'])): ?>
        <div class="rating-section">
            <h4>Rate Your Drivers</h4>
            <div class="no-staff">⚠️ Drivers could not be found in employee database</div>
        </div>
        <?php endif; ?>

        <!-- Written Feedback -->
        <div class="rating-section">
            <h4>Write Feedback</h4>
            <textarea name="rating_comment" placeholder="Write your overall feedback here..." required></textarea>
        </div>

        <button type="submit" class="btn-submit">Submit Feedback</button>
    </form>
</div>

</body>
</html>