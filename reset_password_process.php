<?php
session_start();
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if OTP was verified
    if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
        header('Location: landing_page2.html?error=unauthorized&tab=forgot');
        exit();
    }
    
    // Check if session variables exist
    if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_user_type'])) {
        header('Location: landing_page2.html?error=session_expired&tab=forgot');
        exit();
    }
    
    $email = $_SESSION['reset_email'];
    $user_type = $_SESSION['reset_user_type'];
    $new_password = $_POST['newPassword'];
    $confirm_password = $_POST['confirmNewPassword'];
    
    // Validate passwords match
    if ($new_password !== $confirm_password) {
        header('Location: landing_page2.html?error=passwords_mismatch&tab=reset');
        exit();
    }
    
    // Validate password strength
    if (strlen($new_password) < 8 || 
        !preg_match('/[A-Z]/', $new_password) || 
        !preg_match('/[a-z]/', $new_password) || 
        !preg_match('/[0-9]/', $new_password) || 
        !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $new_password)) {
        header('Location: landing_page2.html?error=weak_password&tab=reset');
        exit();
    }
    
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password based on user type
    if ($user_type === 'employee') {
        $stmt = $conn->prepare("UPDATE employees SET password = ?, otp = NULL, otp_expiry = NULL WHERE email = ?");
    } else {
        $stmt = $conn->prepare("UPDATE clients SET password = ?, otp = NULL, otp_expiry = NULL WHERE email = ?");
    }
    
    $stmt->bind_param("ss", $hashed_password, $email);
    
    if ($stmt->execute()) {
        // Clear session variables
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_user_type']);
        unset($_SESSION['otp_verified']);
        
        $stmt->close();
        header('Location: landing_page2.html?success=password_reset');
        exit();
    } else {
        $stmt->close();
        header('Location: landing_page2.html?error=reset_failed&tab=reset');
        exit();
    }
    
} else {
    header('Location: landing_page2.html');
    exit();
}
?>