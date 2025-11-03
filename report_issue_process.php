<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from output
session_start();
require 'connection.php';

// Set JSON header for AJAX response
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get field values
    $booking_id = $_POST['report-booking-id'] ?? null;
    $issue_type = $_POST['issueType'] ?? null;
    $issue_description = $_POST['issueDetails'] ?? null;
    $submission_date = $_POST['submissionDate'] ?? null;
    $submission_time = $_POST['submissionTime'] ?? null;
    $issue_date = $_POST['issueDate'] ?? null;
    $issue_time = $_POST['issueTime'] ?? null;

    // Validate
    if (!$booking_id || !$issue_type || !$issue_description) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Check if booking exists
    $check = $conn->query("SELECT id FROM bookings WHERE id = '$booking_id'");
    if ($check->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    // Handle file uploads
    $photo1 = null;
    $photo2 = null;
    $photo3 = null;
    
    $upload_dir = 'uploads/issues/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (isset($_FILES['attachment1']) && $_FILES['attachment1']['error'] == 0) {
        $filename1 = time() . '_1_' . basename($_FILES['attachment1']['name']);
        if (move_uploaded_file($_FILES['attachment1']['tmp_name'], $upload_dir . $filename1)) {
            $photo1 = $filename1;
        }
    }
    
    if (isset($_FILES['attachment2']) && $_FILES['attachment2']['error'] == 0) {
        $filename2 = time() . '_2_' . basename($_FILES['attachment2']['name']);
        if (move_uploaded_file($_FILES['attachment2']['tmp_name'], $upload_dir . $filename2)) {
            $photo2 = $filename2;
        }
    }
    
    if (isset($_FILES['attachment3']) && $_FILES['attachment3']['error'] == 0) {
        $filename3 = time() . '_3_' . basename($_FILES['attachment3']['name']);
        if (move_uploaded_file($_FILES['attachment3']['tmp_name'], $upload_dir . $filename3)) {
            $photo3 = $filename3;
        }
    }

    // Prepare update query
    $stmt = $conn->prepare("UPDATE bookings SET 
        issue_type = ?, 
        issue_description = ?, 
        submission_date = ?, 
        submission_time = ?,
        issue_report_date = ?,
        issue_report_time = ?,
        issue_photo1 = ?,
        issue_photo2 = ?,
        issue_photo3 = ?
        WHERE id = ?
    ");

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("sssssssssi",
        $issue_type,
        $issue_description,
        $submission_date,
        $submission_time,
        $issue_date,
        $issue_time,
        $photo1,
        $photo2,
        $photo3,
        $booking_id
    );

    // Execute and return result
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Issue reported successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update record']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}