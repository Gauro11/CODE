<?php
include 'connection.php';
session_start();

// ✅ Ensure user is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

// ✅ Validate client ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid Client ID'); window.location.href='clients.php';</script>";
    exit;
}

$clientId = intval($_GET['id']);

// ✅ Archive client
$updateStmt = $conn->prepare("UPDATE clients SET archived = 1 WHERE id = ?");
$updateStmt->bind_param("i", $clientId);

if ($updateStmt->execute()) {
    echo "<script>
            alert('Client successfully archived.');
            window.location.href='clients.php';
          </script>";
} else {
    echo "<script>
            alert('Failed to archive client.');
            window.location.href='clients.php';
          </script>";
}

$updateStmt->close();
$conn->close();
?>
