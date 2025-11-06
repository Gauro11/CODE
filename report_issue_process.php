<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();
require 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$booking_id = $_POST['report-booking-id'] ?? null;
$issue_type = $_POST['issueType'] ?? null;
$issue_description = $_POST['issueDetails'] ?? null;
$submission_date = $_POST['submissionDate'] ?? null;
$submission_time = $_POST['submissionTime'] ?? null;
$issue_date = $_POST['issueDate'] ?? null;
$issue_time = $_POST['issueTime'] ?? null;

if (!$booking_id || !$issue_type || !$issue_description) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// ✅ Check booking exists
$check = $conn->query("SELECT issue_photo1, issue_photo2, issue_photo3 FROM bookings WHERE id = '$booking_id'");
if ($check->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit;
}

$existing = $check->fetch_assoc();
$photo1 = $existing['issue_photo1'];
$photo2 = $existing['issue_photo2'];
$photo3 = $existing['issue_photo3'];

$upload_dir = 'uploads/issues/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ✅ Replace only if new file uploaded
if (!empty($_FILES['attachment1']['name'])) {
    $filename1 = time() . '_1_' . basename($_FILES['attachment1']['name']);
    move_uploaded_file($_FILES['attachment1']['tmp_name'], $upload_dir . $filename1);
    $photo1 = $filename1;
}

if (!empty($_FILES['attachment2']['name'])) {
    $filename2 = time() . '_2_' . basename($_FILES['attachment2']['name']);
    move_uploaded_file($_FILES['attachment2']['tmp_name'], $upload_dir . $filename2);
    $photo2 = $filename2;
}

if (!empty($_FILES['attachment3']['name'])) {
    $filename3 = time() . '_3_' . basename($_FILES['attachment3']['name']);
    move_uploaded_file($_FILES['attachment3']['tmp_name'], $upload_dir . $filename3);
    $photo3 = $filename3;
}

// ✅ UPDATE query
$stmt = $conn->prepare("UPDATE bookings SET 
    issue_type=?, 
    issue_description=?, 
    submission_date=?, 
    submission_time=?,
    issue_report_date=?,
    issue_report_time=?,
    issue_photo1=?,
    issue_photo2=?,
    issue_photo3=?
    WHERE id=?
");

$stmt->bind_param(
    "sssssssssi",
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

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Issue successfully submitted/updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
