<?php
session_start(); // ALWAYS start the session at the very beginning

require 'connection.php'; // Make sure this path is correct
require 'vendor/autoload.php'; // Make sure this path is correct

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables for messages and errors
$errors = [];
$success_message = '';
$email = ''; // For the login form's email input to retain value on error
$reset_email_value = ''; // To retain value for reset email input

// Check for success messages from previous redirects (e.g., from rp1_admin.php)
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear it after displaying
}

// Check for login-specific errors from previous redirects
// We now store errors more specifically per input field
if (isset($_SESSION['admin_login_errors'])) { // Changed session key for admin errors
    $errors = $_SESSION['admin_login_errors'];
    unset($_SESSION['admin_login_errors']); // Clear after use
}

// Retain email values if there were errors
if (isset($_SESSION['old_admin_email'])) { // Changed session key for old admin email
    $email = $_SESSION['old_admin_email'];
    unset($_SESSION['old_admin_email']);
}
if (isset($_SESSION['old_admin_reset_email'])) { // Changed session key for old admin reset email
    $reset_email_value = $_SESSION['old_admin_reset_email'];
    unset($_SESSION['old_admin_reset_email']);
}


// --- Logic for ADMIN LOGIN Form Submission ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'login') {
    $login_email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $login_password = isset($_POST['password']) ? $_POST['password'] : '';

    // Store email in session to repopulate if there's an error
    $_SESSION['old_admin_email'] = $login_email; // Changed session key

    // Basic validation for login
    if (empty($login_email)) {
        $errors['email'] = "Email address is required";
    } elseif (!filter_var($login_email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }

    if (empty($login_password)) {
        $errors['password'] = "Password is required";
    }

    if (empty($errors)) {
        // Prepare and execute the login query from the 'admins' table
        $stmt = $conn->prepare("SELECT id, password FROM admins WHERE email = ?"); // Changed table to 'admins'
        $stmt->bind_param("s", $login_email);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($user_id, $hashed_password);
        $stmt->fetch();

        if ($stmt->num_rows === 1) {
            // Email found, now verify password
            if (password_verify($login_password, $hashed_password)) {
                // Login successful
                $_SESSION['admin_id'] = $user_id;       // Changed session key
                $_SESSION['admin_email'] = $login_email; // Changed session key
                // Unset old_admin_email if login is successful
                unset($_SESSION['old_admin_email']);
                unset($_SESSION['admin_login_errors']); // Clear any pending admin login errors
                header("Location:  admin_dashboard.php"); // Redirect to admin dashboard
                exit;
            } else {
                // Password incorrect
                $errors['password'] = "Incorrect password";
            }
        } else {
            // Email not found
            $errors['email'] = "Email not found";
        }
        $stmt->close();
    }
    // If there are errors, store them in session to display on redirect
    if (!empty($errors)) {
        $_SESSION['admin_login_errors'] = $errors; // Changed session key
        // Redirect back to ensure messages are displayed correctly after POST
        header("Location: admin_login.php");
        exit;
    }
}


// --- Logic for ADMIN FORGOT PASSWORD Form Submission ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'forgot_password') {
    $reset_email = trim(isset($_POST['resetEmail']) ? $_POST['resetEmail'] : '');

    // Store reset email in session to repopulate if there's an error
    $_SESSION['old_admin_reset_email'] = $reset_email; // Changed session key

    // Basic validation for reset email
    if (empty($reset_email)) {
        $errors['reset_email'] = "Email address is required";
    } elseif (!filter_var($reset_email, FILTER_VALIDATE_EMAIL)) {
        $errors['reset_email'] = "Invalid email format";
    }

    if (empty($errors['reset_email'])) { // Only proceed if the email format is valid
        // Check if email exists in the 'admins' table
        $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?"); // Changed table to 'admins'
        $stmt->bind_param("s", $reset_email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            // Email found, proceed to generate OTP
            $otp = rand(100000, 999999);
            $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));

            // IMPORTANT: Update the 'admins' table for reset token
            $stmt2 = $conn->prepare("UPDATE admins SET reset_token = ?, reset_token_expires_at = ? WHERE email = ?"); // Changed table to 'admins'
            $stmt2->bind_param("sss", $otp, $expires_at, $reset_email);
            $stmt2->execute();
            $stmt2->close(); // Close the second statement

            // Send OTP via PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'azimamaids.services@gmail.com'; // your Gmail
                $mail->Password = 'zpdr mtmq fcui slvq';         // your App Password (use environment variables in production!)
                $mail->SMTPSecure = 'tls'; // Use TLS, not SSL
                $mail->Port = 587;

                $mail->setFrom('azimamaids.services@gmail.com', 'ALAZIMA Cleaning Services Admin'); // Changed sender name
                $mail->addAddress($reset_email);
                $mail->isHTML(true);
                $mail->Subject = 'Your Admin Password Reset Code'; // Changed subject
                $mail->Body = "
                    <h3>Admin Password Reset Code for ALAZIMA Cleaning Services</h3>
                    <p>Hello,</p>
                    <p>You have requested a password reset for your **Admin** account. Your 6-digit verification code is:</p>
                    <p style='font-size: 24px; font-weight: bold; color: #00487E;'>$otp</p>
                    <p>This code will expire in 15 minutes. If you did not request a password reset, please ignore this email.</p>
                    <p>Thank you,<br>ALAZIMA Cleaning Services Admin Team</p>
                ";

                $mail->send();

                $_SESSION['admin_reset_email'] = $reset_email; // Store email for the next page
                $_SESSION['success_message'] = 'Please check your inbox or spam folder'; // Removed period
                header("Location: rp1_admin.php"); // Redirect to the admin reset password page
                exit;

            } catch (Exception $e) {
                // Store the error message in a session variable
                $errors['reset_email'] = "Failed to send reset email. Please try again later";
            }
        } else {
            // Email not found in our records for reset
            $errors['reset_email'] = "Email not found";
        }
        $stmt->close(); // Close the database statement
    }

    // If there are errors (either validation or DB/mailer), store and redirect
    if (!empty($errors['reset_email'])) {
        $_SESSION['admin_login_errors']['reset_email'] = $errors['reset_email']; // Changed session key
        header("Location: admin_login.php");
        exit;
    }
}
$conn->close(); // Close the database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ALAZIMA Cleaning Services L.L.C - Admin Login</title>
    <link rel="icon" href="site_icon.png" type="image/png">

    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="landing_page.css">
<style>
    :root {
        --header-height: 4.5rem; /* Updated to match Reset Password header */
        --body-font: 'Arial', sans-serif;
        --body-color: #E5F2FB;
        --text-color: #333;
        --container-color: #ffffff;
        --title-color1: #333;
        --z-tooltip: 10;
    }
    
    body {
        font-family: var(--body-font);
        background-color: var(--body-color);
        color: var(--text-color);
        margin: 0;
        line-height: 1.6;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* Header adjustments: Hide navigation specific elements, align logo */
    .header .nav {
        /* Keep existing nav styles that might be in landing_page.css */
        justify-content: flex-start; /* Align logo to the start, as there are no other items to space-between */
        height: var(--header-height);
        display: flex; /* Ensure it's a flex container */
        align-items: center; /* Vertically center items */
        padding: 0 1rem; /* Adjust padding as needed for responsiveness */
    }

    .nav__menu,
    .nav__toggle,
    .nav__buttons {
        display: none; /* Hide all navigation links, toggle button, and action buttons */
    }

    .nav__logo img {
        max-height: 2.5rem; /* Adjust based on your logo's desired size */
        width: auto;
        display: block;
    }
    
    .main-content {
        padding-top: var(--header-height);
        flex-grow: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding-bottom: 2rem;
        box-sizing: border-box;
    }
    
    /* Basic styling for the login form container */
    .login-form-container {
        background-color: #ffffff;
        border-radius: 1rem;
        border: 1px solid #c0c0c0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        padding: 1.5rem 3rem; /* Adjusted padding */
        max-width: 500px;
        width: 90%;
        text-align: center;
        margin: 2rem auto;
        box-sizing: border-box;
    
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
    }
    
    .login-form-container h2 {
        font-size: 2em;
        color: #00487E;
        margin-bottom: 1.5rem;
        text-transform: uppercase;
        font-weight: 800;
        letter-spacing: 1px;
        width: 100%;
    }
    
    /* Footer styling */
    .footer {
        background-color: #004a80;
        color: #ffffff;
        text-align: center;
        padding: 1.5rem 1rem;
        font-size: 0.9rem;
        margin-top: auto;
    }
    
    /* Responsive adjustments for the form container */
    @media screen and (max-width: 768px) {
        .login-form-container {
            padding: 1.5rem;
        }
        .login-form-container h2 {
            font-size: 1.8em;
        }
    }
    
    @media screen and (max-width: 480px) {
        .login-form-container {
            padding: 1rem;
        }
        .login-form-container h2 {
            font-size: 1.5em;
        }
    }
    
    /* ************************************************* */
    /* STYLES FOR LOGIN FORM ELEMENTS */
    /* ************************************************* */
    
    .login-tabs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
        margin-bottom: 1.5rem; /* INCREASED: Was 0.5rem. Adjust as needed. */
        border: 1px solid #c0c0c0;
        border-radius: 0.5rem;
        overflow: hidden;
        width: 100%;
        z-index: 1;
    }
    
    .login-tab-button {
        background-color: #d6d6d6;
        color: #555;
        padding: 0.8rem 0;
        font-size: 1rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s ease, color 0.3s ease;
        white-space: nowrap;
    }
    
    .login-tab-button.active {
        background-color: #00487E;
        color: #ffffff;
    }
    
    /* NEW: Wrapper for forms to enable grid overlapping */
    .forms-wrapper {
        display: grid;
        grid-template-areas: "form-content";
        width: 100%;
        min-height: 180px; /* Keep fine-tuning this as needed, or increase slightly if adding more space. */
        box-sizing: border-box;
    }
    
    /* Forms themselves */
    .login-form {
        grid-area: form-content;
        text-align: center;
        width: 100%; /* Now this means 100% of forms-wrapper */
        box-sizing: border-box;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
        position: static;
        z-index: 0;
        /* Added padding here to align contents with the overall container padding */
        padding: 0 0rem; /* No horizontal padding on the form itself, inputs will have margin */
    }
    
    .login-form.active-form {
        opacity: 1;
        pointer-events: all;
        z-index: 1;
    }
    
    .form-group {
        text-align: left;
        position: relative;
        margin-bottom: 1rem; /* INCREASED: Was 0.3rem. Adjust as needed. */
        padding: 0 0rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.3rem;
        font-size: 0.95rem;
        color: #333; /* This ensures the label text is dark */
        font-weight: 700; /* This ensures the label text is bold */
    }

    .form-group label .required {
        color: #FF6347; /* Set this back to red for the asterisk only */
    }
    /* Input fields and buttons should now occupy the full width, so we need to add horizontal padding to them if needed */
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 0.6rem 1rem;
        border: 1px solid #a0a0a0;
        border-radius: 0.5rem;
        font-size: 1rem;
        color: #555;
        box-sizing: border-box;
        outline: none; /* Remove default browser outline */
        transition: border-color 0.3s ease, box-shadow 0.3s ease; /* Add transition for smooth effect */
        background-color: #ffffff;
    }

    .form-group input[type="email"]:focus,
    .form-group input[type="password"]:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.1rem rgba(0, 123, 255, 0.25);
    }
    
    /* Submit Button Styling */
    .submit-button {
        background-color: #FF6347;
        color: #ffffff;
        padding: 0.6rem 2rem;
        font-size: 1rem;
        border: 1px solid #000000;
        border-radius: 0.5rem;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        box-shadow: 0 2px 3px rgba(0, 0, 0, 0.3);
        display: inline-block;
        margin-top: .5rem; /* This is the general setting, we'll override it for reset form */
        width: auto;
    }

    #resetPasswordForm .submit-button {
        margin-top: 3rem; /* Increased margin for the reset button to move it down */
    }
    
    /* Password show/hide icon */
    .password-input-container {
        position: relative;
        width: 100%;
    }
    
    .password-input-container input {
        width: 100%;
        padding-right: 2.5rem;
    }
    
    .password-toggle {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #888;
        font-size: 1.2rem;
        transition: color 0.2s ease;
    }
    
    .password-toggle:hover {
        color: #333;
    }
    
    /* Password Guide Styling - Updated for smooth animation */
    .password-guide {
        text-align: left;
        margin-top: 0.5rem;
        padding: 0.8rem;
        border: 1px solid #c0c0c0; /* Adjusted border color to be slightly darker, matching images */
        border-radius: 0.5rem;
        background-color: #f9f9f9; /* Changed to pure white, matching images */
        font-size: 0.8rem;
        color: #555; /* Keep main text color here */
        opacity: 0;
        max-height: 0;
        overflow: hidden;
        transition: opacity 0.3s ease-out, max-height 0.4s ease-out, padding 0.4s ease-out;
        width: 100%;
        box-sizing: border-box;
    }
    
    /* State when the guide is shown */
    .password-guide.show {
        opacity: 1;
        max-height: 200px; /* Adjust as needed if content is taller */
        padding: 0.8rem;
    }
    .password-guide p {
        margin-top: 0;
        margin-bottom: 0.5rem; /* Slightly increased margin below "Password must contain:" to match spacing in image */
        font-weight: 700; /* Made slightly bolder to match image */
        color: #333; /* Explicitly set darker color for the heading to match image */
    }
    
    .password-guide ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .password-guide ul li {
        display: flex;
        align-items: center;
        margin-bottom: 0.3rem; /* Slightly increased margin between list items to match image */
        color: #555; /* Adjusted list item text color to match image */
        font-size: 0.9rem; /* Slightly increased font size for list items to match image */
    }

    .password-guide ul li i {
        margin-right: 0.5rem;
        font-size: 1rem; /* Slightly larger icon size for visibility, matching images */
        transition: color 0.3s ease;
    }

    /* Validation feedback colors for icons */
    .password-guide ul li.valid i {
        color: #28a744; /* Green check, matching images */
    }

    .password-guide ul li.invalid i {
        color: #dc3545; /* Red X, matching images */
    }

    #resetPasswordForm p {
        margin-bottom: 1.5rem; /* Adjust as needed for spacing below the text */
        font-size: 1rem; /* Slightly larger text */
        color: #333; /* Darker gray color */
        font-weight: 600; /* A bit bolder */
        text-align: center; /* Ensure it's centered if it's not already */
    }

    /* Error message styling */
    .error-message {
        color: #dc3545;
        font-size: 0.9rem;
        margin-top: 0.25rem; /* Adjusted to be slightly closer to input */
        margin-bottom: 0.5rem; /* Add space below it, to match spacing from the password guide */
        text-align: left; /* Align error message with label/input */
        width: 100%;
        display: block; /* Ensure it takes full width */
    }
    /* Specific error styling for input fields */
    .form-group input.error-border {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.1rem rgba(220, 53, 69, 0.25) !important;
    }
    .success-message {
        color: #28a744;
        font-size: 0.9rem;
        margin-bottom: 1rem;
        text-align: center;
        width: 100%;
        display: block;
    }
</style>
</head>
<body>
    <header class="header" id="header">
        <nav class="nav container">
            <a href="landing_page.html" class="nav__logo">
                <img src="LOGO.png" alt="ALAZIMA Cleaning Services LLC Logo" onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
            </a>
            <div class="nav__menu" id="nav-menu" style="display: none;"></div>
            <div class="nav__buttons" style="display: none;"></div>
            <div class="nav__toggle" id="nav-toggle" style="display: none;"></div>
        </nav>
    </header>

    <main class="main-content">
        <section class="login-form-container">
        <a href="landing_page2.html" 
   style="display: inline-block; margin-bottom: 0.3rem; color: #00487E; text-decoration: none; font-weight: bold; align-self: flex-start;">
    ← Back to Menu
</a>




            <h2>WELCOME <br>(ADMIN)</h2> <div class="login-tabs">
                <button type="button" class="login-tab-button active" id="loginTab">Log In</button>
                <button type="button" class="login-tab-button" id="resetPasswordTab">Reset Password</button>
            </div>

            <div class="forms-wrapper">
                <form action="admin_login.php" method="POST" class="login-form" id="loginForm" novalidate>
                    <input type="hidden" name="action" value="login">
                    <?php if (!empty($success_message)): ?>
                        <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
                    <?php endif; ?>
                    <?php if (isset($errors['login']) && empty($errors['email']) && empty($errors['password'])): ?>
                        <p class="error-message"><?php echo htmlspecialchars($errors['login']); ?></p>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="email"> <span class="required">*</span>Email Address:</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email address"
                               value="<?php echo htmlspecialchars($email); ?>"
                               class="<?php echo isset($errors['email']) ? 'error-border' : ''; ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($errors['email']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="password"> <span class="required">*</span>Password:</label>
                        <div class="password-input-container">
                            <input type="password" id="password" name="password" placeholder="Enter your password"
                                   class="<?php echo isset($errors['password']) ? 'error-border' : ''; ?>" required>
                            <i class='bx bx-show-alt password-toggle' data-target="password"></i>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($errors['password']); ?></span>
                        <?php endif; ?>
                        <div class="password-guide" id="passwordGuide">
                            <p>Password must contain:</p>
                            <ul>
                                <li id="length"><i class='bx bx-x'></i> <span>At least 8 characters</span></li>
                                <li id="uppercase"><i class='bx bx-x'></i> <span>At least one uppercase letter (A-Z)</span></li>
                                <li id="lowercase"><i class='bx bx-x'></i> <span>At least one lowercase letter (a-z)</span></li>
                                <li id="number"><i class='bx bx-x'></i> <span>At least one number (0-9)</span></li>
                                <li id="special"><i class='bx bx-x'></i> <span>At least one symbol (!@#$%^&* etc.)</span></li>
                            </ul>
                        </div>
                    </div>
                    <button type="submit" class="submit-button">LOG IN</button>
                </form>

                <form action="admin_login.php" method="POST" class="login-form" id="resetPasswordForm" novalidate>
                    <input type="hidden" name="action" value="forgot_password">
                    
                    <div class="form-group">
                        <label for="resetEmail"> <span class="required">*</span>Email Address:</label>
                        <input type="email" id="resetEmail" name="resetEmail" placeholder="Enter your email address"
                               value="<?php echo htmlspecialchars($reset_email_value); ?>"
                               class="<?php echo isset($errors['reset_email']) ? 'error-border' : ''; ?>" required>
                        <?php if (isset($errors['reset_email'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($errors['reset_email']); ?></span>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="submit-button">Send Reset Link</button>
                </form>
            </div>
            </section>
    </main>

    <footer class="footer">
        <p>&copy; 2025 ALAZIMA Cleaning Service. All Rights Reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            /*=============== PASSWORD SHOW/HIDE ===============*/
            document.querySelectorAll('.password-toggle').forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const targetId = this.dataset.target;
                    const passwordInput = document.getElementById(targetId);

                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        this.classList.remove('bx-show-alt');
                        this.classList.add('bx-hide');
                    } else {
                        passwordInput.type = 'password';
                        this.classList.remove('bx-hide');
                        this.classList.add('bx-show-alt');
                    }
                });
            });

            /*=============== PASSWORD GUIDE FUNCTIONALITY ===============*/
            const passwordInput = document.getElementById('password');
            const passwordGuide = document.getElementById('passwordGuide');
            const lengthCheck = document.getElementById('length');
            const uppercaseCheck = document.getElementById('uppercase');
            const lowercaseCheck = document.getElementById('lowercase');
            const numberCheck = document.getElementById('number');
            const specialCheck = document.getElementById('special');

            if (passwordInput && passwordGuide) {
                // Show guide on focus
                passwordInput.addEventListener('focus', () => {
                    passwordGuide.classList.add('show');
                    validatePassword();
                });

                // Hide guide on blur if input is empty, otherwise keep it for visual feedback
                passwordInput.addEventListener('blur', () => {
                    if (passwordInput.value === '') {
                        passwordGuide.classList.remove('show');
                    }
                });

                // Validate on keyup
                passwordInput.addEventListener('keyup', validatePassword);

                function validatePassword() {
                    const value = passwordInput.value;

                    // Minimum 8 characters
                    updateValidationStatus(lengthCheck, value.length >= 8);

                    // At least one uppercase letter
                    updateValidationStatus(uppercaseCheck, /[A-Z]/.test(value));

                    // At least one lowercase letter
                    updateValidationStatus(lowercaseCheck, /[a-z]/.test(value));

                    // At least one number
                    updateValidationStatus(numberCheck, /[0-9]/.test(value));

                    // At least one special character (!@#$%^&*()_+-=[]{};':"|,.<>/?~)
                    updateValidationStatus(specialCheck, /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(value));
                }

                function updateValidationStatus(element, isValid) {
                    const icon = element.querySelector('i');
                    element.classList.toggle('valid', isValid);
                    element.classList.toggle('invalid', !isValid);

                    if (isValid) {
                        icon.classList.remove('bx-x');
                        icon.classList.add('bx-check');
                    } else {
                        icon.classList.remove('bx-check');
                        icon.classList.add('bx-x');
                    }
                }
            }
            
            /*=============== LOGIN/RESET PASSWORD TAB FUNCTIONALITY ===============*/
            const loginTab = document.getElementById('loginTab');
            const resetPasswordTab = document.getElementById('resetPasswordTab');
            const loginForm = document.getElementById('loginForm');
            const resetPasswordForm = document.getElementById('resetPasswordForm');

            // PHP variables to determine which tab should be active on page load
            const hasLoginErrors = <?php echo json_encode(isset($errors['email']) || isset($errors['password'])); ?>;
            const hasResetEmailErrors = <?php echo json_encode(isset($errors['reset_email'])); ?>;
            const hasSuccessMessage = <?php echo json_encode(!empty($success_message)); ?>;

            // Activate tab based on PHP errors or success message
            // Priority: Reset errors -> Success message -> Login errors (default)
            // MODIFIED LINE: Removed 'hasSuccessMessage' from the condition for activating resetPasswordTab
            if (hasResetEmailErrors) { 
                resetPasswordTab.classList.add('active');
                loginTab.classList.remove('active');
                resetPasswordForm.classList.add('active-form');
                loginForm.classList.remove('active-form');
            } else {
                loginTab.classList.add('active');
                resetPasswordTab.classList.remove('active');
                loginForm.classList.add('active-form');
                resetPasswordForm.classList.remove('active-form');
            }

            loginTab.addEventListener('click', () => {
                loginTab.classList.add('active');
                resetPasswordTab.classList.remove('active');
                
                loginForm.classList.add('active-form');
                resetPasswordForm.classList.remove('active-form');
                clearFormAndMessages(resetPasswordForm);
            });

            resetPasswordTab.addEventListener('click', () => {
                resetPasswordTab.classList.add('active');
                loginTab.classList.remove('active');
                
                resetPasswordForm.classList.add('active-form');
                loginForm.classList.remove('active-form');
                clearFormAndMessages(loginForm);
            });

            // Function to clear error messages, error borders, and input values within a form
            function clearFormAndMessages(formElement) {
                formElement.querySelectorAll('.error-message').forEach(el => el.remove());
                formElement.querySelectorAll('.error-border').forEach(el => el.classList.remove('error-border'));
                formElement.querySelectorAll('input').forEach(input => {
                    if (input.type === 'text' || input.type === 'email' || input.type === 'password') {
                        input.value = '';
                    }
                });

                const successMsg = document.querySelector('.success-message');
                if (successMsg) successMsg.remove();
            }
        });

        // This block handles redirection and alerts from password reset success
        window.addEventListener("DOMContentLoaded", function () {
            const urlParams = new URLSearchParams(window.location.search);
            const resetSuccess = urlParams.get("reset");

            if (resetSuccess === "success") {
              // Ensure the reset password tab is NOT active, but the login tab is.
              // We're already handling this with PHP variables for initial load,
              // but this ensures the alert shows AFTER the page has potentially
              // re-rendered with the correct tab active based on PHP.
              // Remove the 'tab-1' logic as it's not present in this HTML structure.
              alert("Password changed successfully. Please log in.");
            }
          });
    </script>

</body>
</html>