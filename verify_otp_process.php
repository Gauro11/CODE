<?php
session_start();
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'];
    
    // Check if session variables exist
    if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_user_type'])) {
        header('Location: landing_page2.html?error=session_expired&tab=forgot');
        exit();
    }
    
    $email = $_SESSION['reset_email'];
    $user_type = $_SESSION['reset_user_type'];
    
    // Validate OTP format
    if (!preg_match('/^\d{6}$/', $otp)) {
        header('Location: landing_page2.html?error=invalid_otp&tab=verify');
        exit();
    }
    
    $current_time = date('Y-m-d H:i:s');
    
    // Check OTP based on user type
    if ($user_type === 'employee') {
        $stmt = $conn->prepare("SELECT id FROM employees WHERE email = ? AND otp = ? AND otp_expiry > ?");
    } else {
        $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ? AND otp = ? AND otp_expiry > ?");
    }
    
    $stmt->bind_param("sss", $email, $otp, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // OTP is valid
        $_SESSION['otp_verified'] = true;
        header('Location: landing_page2.html?success=otp_verified&tab=reset');
    } else {
        // Check if OTP exists but expired
        if ($user_type === 'employee') {
            $check_stmt = $conn->prepare("SELECT id FROM employees WHERE email = ? AND otp = ?");
        } else {
            $check_stmt = $conn->prepare("SELECT id FROM clients WHERE email = ? AND otp = ?");
        }
        
        $check_stmt->bind_param("ss", $email, $otp);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            header('Location: landing_page2.html?error=otp_expired&tab=verify');
        } else {
            header('Location: landing_page2.html?error=incorrect_otp&tab=verify');
        }
        $check_stmt->close();
    }
    
    $stmt->close();
    exit();
    
} else {
    header('Location: landing_page2.html');
    exit();
}
?>