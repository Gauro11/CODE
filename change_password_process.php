<?php
session_start();
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmNewPassword = $_POST['confirmNewPassword'];

    // Validate inputs
    if (empty($email) || empty($currentPassword) || empty($newPassword) || empty($confirmNewPassword)) {
        header("Location: landing_page2.html?error=password_change_failed");
        exit;
    }

    // Check if new passwords match
    if ($newPassword !== $confirmNewPassword) {
        header("Location: landing_page2.html?error=password_change_failed");
        exit;
    }

    $userFound = false;
    $userId = null;
    $hashedPasswordFromDB = null;
    $tableName = null;

    // Check in clients table
    $stmt = $conn->prepare("SELECT id, password FROM clients WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $userId = $user['id'];
        $hashedPasswordFromDB = $user['password'];
        $tableName = 'clients';
        $userFound = true;
    }
    $stmt->close();

    // If not found in clients, check employees table
    if (!$userFound) {
        $stmt = $conn->prepare("SELECT id, password FROM employees WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $userId = $user['id'];
            $hashedPasswordFromDB = $user['password'];
            $tableName = 'employees';
            $userFound = true;
        }
        $stmt->close();
    }

    // If not found in employees, check admins table
    if (!$userFound) {
        $stmt = $conn->prepare("SELECT id, password FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $userId = $user['id'];
            $hashedPasswordFromDB = $user['password'];
            $tableName = 'admins';
            $userFound = true;
        }
        $stmt->close();
    }

    // If user not found in any table
    if (!$userFound) {
        header("Location: landing_page2.html?error=email_not_found");
        exit;
    }

    // Verify current password
    if (!password_verify($currentPassword, $hashedPasswordFromDB)) {
        header("Location: landing_page2.html?error=wrong_password");
        exit;
    }

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password in the correct table
    $updateStmt = $conn->prepare("UPDATE $tableName SET password = ? WHERE id = ?");
    $updateStmt->bind_param("si", $hashedPassword, $userId);

    if ($updateStmt->execute()) {
        header("Location: landing_page2.html?success=password_changed");
    } else {
        header("Location: landing_page2.html?error=password_change_failed");
    }

    $updateStmt->close();
    $conn->close();
}
?>