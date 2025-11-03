<?php
include 'connection.php';
session_start();

if(!isset($_GET['id'])) {
    header("Location: UM_employees.php");
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("UPDATE employees SET archived = 1 WHERE id = ?");
$stmt->bind_param("i", $id);

if($stmt->execute()) {
    echo "<script>alert('Employee archived successfully'); window.location.href='UM_employees.php';</script>";
} else {
    echo "<script>alert('Failed to archive employee'); window.location.href='UM_employees.php';</script>";
}

$stmt->close();
$conn->close();
?>
