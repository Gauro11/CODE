<?php
session_start();
require 'connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_email = $_SESSION['email'];

// Get POST data
$booking_id = $_POST['booking_id'] ?? '';
$full_name = $_POST['full_name'] ?? '';
$phone = $_POST['phone'] ?? '';
$service_type = $_POST['service_type'] ?? '';
$client_type = $_POST['client_type'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$service_time = $_POST['service_time'] ?? '';
$duration = $_POST['duration'] ?? '';
$frequency = $_POST['frequency'] ?? '';
$preferred_day = $_POST['preferred_day'] ?? '';
$property_layout = $_POST['property_layout'] ?? '';
$address = $_POST['address'] ?? '';
$materials_needed = $_POST['materials_needed'] ?? '';
$comments = $_POST['comments'] ?? '';

// Validate required fields
if (empty($booking_id) || empty($full_name) || empty($phone) || empty($start_date) || empty($service_time)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Verify that the booking belongs to the logged-in user
$verify_sql = "SELECT id FROM bookings WHERE id = ? AND email = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("is", $booking_id, $user_email);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found or unauthorized']);
    exit;
}

// Update the booking
$update_sql = "UPDATE bookings SET 
    full_name = ?,
    phone = ?,
    service_type = ?,
    client_type = ?,
    service_date = ?,
    service_time = ?,
    duration = ?,
    frequency = ?,
    preferred_day = ?,
    property_type = ?,
    address = ?,
    materials_needed = ?,
    comments = ?
    WHERE id = ? AND email = ?";

$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param(
    "ssssssssssssis",
    $full_name,
    $phone,
    $service_type,
    $client_type,
    $start_date,
    $service_time,
    $duration,
    $frequency,
    $preferred_day,
    $property_layout,
    $address,
    $materials_needed,
    $comments,
    $booking_id,
    $user_email
);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Booking updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$update_stmt->close();
$conn->close();
?>