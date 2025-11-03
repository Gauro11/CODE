<?php
session_start();
require 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit;
}

if (!isset($_POST['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required.']);
    exit;
}

$booking_id = intval($_POST['booking_id']);
$user_email = $_SESSION['email'];

$check_sql = "SELECT id, status FROM bookings WHERE id = ? AND email = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $booking_id, $user_email);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found.']);
    $check_stmt->close();
    $conn->close();
    exit;
}

$booking = $result->fetch_assoc();
$check_stmt->close();

if (strtoupper($booking['status']) !== 'PENDING') {
    echo json_encode(['success' => false, 'message' => 'Only pending bookings can be cancelled.']);
    $conn->close();
    exit;
}

$update_sql = "UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND email = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("is", $booking_id, $user_email);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cancelled successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel.']);
}

$update_stmt->close();
$conn->close();
?>