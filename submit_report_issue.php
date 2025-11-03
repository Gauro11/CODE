<?php
session_start();
require 'connection.php';

if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

$client_email = $_SESSION['email'];

// ✅ Booking ID required
if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid booking.'); window.location.href='completed_services.php';</script>";
    exit;
}

$booking_id = $_GET['id'];

// ✅ Verify booking belongs to logged-in client
$sql = $conn->prepare("SELECT * FROM bookings WHERE id=? AND email=?");
$sql->bind_param("is", $booking_id, $client_email);
$sql->execute();
$result = $sql->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('Booking not found.'); window.location.href='completed_services.php';</script>";
    exit;
}

$booking = $result->fetch_assoc();

// ✅ Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issue = $_POST['issue_type'] ?? null;
    $desc = $_POST['issue_description'] ?? null;

    // ✅ Validate
    if (!$issue || !$desc) {
        echo "<script>alert('Please complete all fields.');</script>";
    } else {

        // ✅ Prepare date & time
        date_default_timezone_set("Asia/Manila");
        $date = date("Y-m-d");
        $time = date("H:i:s");

        // ✅ Update DB
        $update = $conn->prepare("
            UPDATE bookings 
            SET issue_type=?, issue_description=?, issue_report_date=?, issue_report_time=?
            WHERE id=? AND email=?
        ");
        $update->bind_param("ssssds", $issue, $desc, $date, $time, $booking_id, $client_email);

        if ($update->execute()) {
            echo "<script>alert('Issue reported successfully.'); window.location.href='completed_services.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error reporting issue.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Report Issue</title>
</head>
<body>

<h2>Report an Issue for Booking Ref: <?php echo $booking_id; ?></h2>

<form method="POST">
    <label>Issue Type:</label>
    <select name="issue_type" required>
        <option value="">--Select--</option>
        <option>Incomplete Cleaning</option>
        <option>Staff Problem</option>
        <option>Late Arrival</option>
        <option>Damaged Property</option>
    </select>

    <br><br>
    
    <label>Description:</label><br>
    <textarea name="issue_description" required></textarea>

    <br><br>
    <button type="submit">Submit Issue</button>
</form>

</body>
</html>
