<?php
header('Content-Type: application/json');
error_reporting(0); // hide warnings/notices

// ✅ USE EXISTING DATABASE CONNECTION
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Use booking ID instead of reference_no
$id = intval($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit;
}

// Prepare the statement
$stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare SQL statement']);
    exit;
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel appointment']);
}

$stmt->close();
$conn->close();

// ✅ No closing PHP tag needed