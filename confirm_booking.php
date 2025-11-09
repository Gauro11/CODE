<?php
// confirm_booking.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "alazima";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    $sql = "UPDATE bookings SET status = 'Confirmed' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    
    $stmt->close();
}

$conn->close();
?>