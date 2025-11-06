<?php
session_start();
require 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['report-booking-id'] ?? null;
    $issue_type = $_POST['issueType'] ?? null;
    $issue_description = $_POST['issueDetails'] ?? null;
    $issue_date = $_POST['issueDate'] ?? null;
    $issue_time = $_POST['issueTime'] ?? null;
    $submission_date = $_POST['submissionDate'] ?? date('Y-m-d');
    $submission_time = $_POST['submissionTime'] ?? date('H:i:s');

    // Validate required fields
    if (!$booking_id || !$issue_type || !$issue_description) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    // Directory to store uploaded files
    $uploadDir = 'uploads/issues/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Function to handle a single file upload
    function handleFileUpload($file, $index, $uploadDir) {
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = microtime(true) . "_{$index}." . $ext;
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                return $fileName;
            }
        }
        return null;
    }

    // Upload files
    $issue_photo1 = handleFileUpload($_FILES['attachment1'] ?? null, 1, $uploadDir);
    $issue_photo2 = handleFileUpload($_FILES['attachment2'] ?? null, 2, $uploadDir);
    $issue_photo3 = handleFileUpload($_FILES['attachment3'] ?? null, 3, $uploadDir);

    // Update the booking with issue report
    $sql = "UPDATE bookings SET 
            issue_type = ?, 
            issue_description = ?, 
            issue_report_date = ?, 
            issue_report_time = ?,
            submission_date = ?,
            submission_time = ?,
            issue_photo1 = ?,
            issue_photo2 = ?,
            issue_photo3 = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssssi", 
        $issue_type, 
        $issue_description, 
        $issue_date, 
        $issue_time,
        $submission_date,
        $submission_time,
        $issue_photo1,
        $issue_photo2,
        $issue_photo3,
        $booking_id
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Report submitted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit report: ' . $conn->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
