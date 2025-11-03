<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid Client ID'); window.location.href='clients.php';</script>";
    exit;
}

$clientId = intval($_GET['id']);
$updateStmt = $conn->prepare("UPDATE clients SET archived = 0 WHERE id = ?");
$updateStmt->bind_param("i", $clientId);

if ($updateStmt->execute()) {
    echo "<script>alert('Client successfully restored.'); window.location.href='clients.php';</script>";
} else {
    echo "<script>alert('Failed to restore client.'); window.location.href='clients.php';</script>";
}

$updateStmt->close();
$conn->close();
?>
