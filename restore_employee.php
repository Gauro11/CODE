<?php
include 'connection.php';
session_start();

// Check login
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

// Validate ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid Employee ID'); window.location.href='UM_employees.php';</script>";
    exit;
}

$employeeId = intval($_GET['id']);

// Update the employee
$updateStmt = $conn->prepare("UPDATE employees SET archived = 0 WHERE id = ?");
$updateStmt->bind_param("i", $employeeId);

if ($updateStmt->execute()) {
    echo "<script>alert('Employee successfully restored.'); window.location.href='UM_employees.php';</script>";
} else {
    echo "<script>alert('Failed to restore employee.'); window.location.href='UM_employees.php';</script>";
}

$updateStmt->close();
$conn->close();
?>


