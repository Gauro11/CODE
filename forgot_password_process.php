<?php
session_start();
require_once 'connection.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure you have PHPMailer installed via Composer

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: landing_page2.html?error=invalid_email&tab=forgot');
        exit();
    }
    
    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes')); // OTP expires in 15 minutes
    
    // Check if email exists in employees table
    $stmt = $conn->prepare("SELECT id, first_name FROM employees WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $found = false;
    $user_type = '';
    $user_name = '';
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $found = true;
        $user_type = 'employee';
        $user_name = $user['first_name'];
        
        // Update OTP in employees table
        $update_stmt = $conn->prepare("UPDATE employees SET otp = ?, otp_expiry = ? WHERE email = ?");
        $update_stmt->bind_param("sss", $otp, $otp_expiry, $email);
        $update_stmt->execute();
        $update_stmt->close();
    }
    $stmt->close();
    
    // If not found in employees, check clients table
    if (!$found) {
        $stmt = $conn->prepare("SELECT id, first_name FROM clients WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $found = true;
            $user_type = 'client';
            $user_name = $user['first_name'];
            
            // Update OTP in clients table
            $update_stmt = $conn->prepare("UPDATE clients SET otp = ?, otp_expiry = ? WHERE email = ?");
            $update_stmt->bind_param("sss", $otp, $otp_expiry, $email);
            $update_stmt->execute();
            $update_stmt->close();
        }
        $stmt->close();
    }
    
    if (!$found) {
        header('Location: landing_page2.html?error=email_not_found&tab=forgot');
        exit();
    }
    
    // Send OTP via email
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
       $mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'milbertgaringa5@gmail.com';
$mail->Password   = 'pxbi vbqm huta esuv';  // MUST be App Password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587;

        
        // Recipients
        $mail->setFrom('your-email@gmail.com', 'ALAZIMA Cleaning Services');
        $mail->addAddress($email, $user_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP - ALAZIMA';
        $mail->Body    = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #003d66 0%, #005a8c 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                    .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
                    .otp-box { background: white; border: 2px solid #0077b3; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                    .otp-code { font-size: 32px; font-weight: bold; color: #003d66; letter-spacing: 8px; }
                    .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 15px 0; }
                    .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Password Reset Request</h1>
                    </div>
                    <div class='content'>
                        <p>Hello $user_name,</p>
                        <p>We received a request to reset your password. Please use the following One-Time Password (OTP) to proceed:</p>
                        
                        <div class='otp-box'>
                            <div class='otp-code'>$otp</div>
                        </div>
                        
                        <div class='warning'>
                            <strong>⚠️ Important:</strong>
                            <ul style='margin: 10px 0; padding-left: 20px;'>
                                <li>This OTP is valid for <strong>15 minutes</strong></li>
                                <li>Do not share this code with anyone</li>
                                <li>If you didn't request this, please ignore this email</li>
                            </ul>
                        </div>
                        
                        <p>If you have any questions, please contact our support team.</p>
                        <p>Best regards,<br><strong>ALAZIMA Cleaning Services Team</strong></p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2025 ALAZIMA Cleaning Services. All Rights Reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = "Hello $user_name,\n\nYour password reset OTP is: $otp\n\nThis code is valid for 15 minutes.\n\nBest regards,\nALAZIMA Cleaning Services";
        
        $mail->send();
        
        // Store email in session for verification
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_user_type'] = $user_type;
        
        header('Location: landing_page2.html?success=otp_sent&tab=verify');
        exit();
        
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        header('Location: landing_page2.html?error=email_send_failed&tab=forgot');
        exit();
    }
    
} else {
    header('Location: landing_page2.html');
    exit();
}
?>