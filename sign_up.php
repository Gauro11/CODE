<?php
session_start();

// --- 1. Connect to the database ---
// $servername = "localhost";
// $username = "u665838367_alazimaa";
// $password = '6$HvZ#Vd'; // safer

// $dbname = "u665838367_alazima";

// $conn = new mysqli($servername, $username, $password, $dbname);
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }
$servername = "localhost";
$username = "root";
$password = ""; // empty
$dbname = "alazima";

$conn = new mysqli($servername, $username, $password, $dbname);


// --- 2. Initialize error array ---
$errors = [];

// --- 3. Process form submission ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve and sanitize form data
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $contactNumber = trim($_POST['contactNumber'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rawPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // --- 4. Validation ---
    if (empty($firstName)) {
        $errors['firstName'] = "First name is required.";
    } elseif (!preg_match("/^[a-zA-Z\s\-]+$/", $firstName)) {
        $errors['firstName'] = "First name can only contain letters, spaces, and hyphens.";
    }

    if (empty($lastName)) {
        $errors['lastName'] = "Last name is required.";
    } elseif (!preg_match("/^[a-zA-Z\s\-]+$/", $lastName)) {
        $errors['lastName'] = "Last name can only contain letters, spaces, and hyphens.";
    }

    if (empty($birthday)) {
        $errors['birthday'] = "Birthday is required.";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $birthday)) {
        $errors['birthday'] = "Invalid date format. Use YYYY-MM-DD.";
    }

    if (empty($contactNumber) || $contactNumber === '+971') {
        $errors['contactNumber'] = "Contact number is required.";
    } elseif (!preg_match("/^\+971[0-9]{9}$/", $contactNumber)) {
        $errors['contactNumber'] = "Please enter a valid UAE phone number (+971XXXXXXXXX).";
    } else {
        // Check duplicate contact number
        $stmt = $conn->prepare("SELECT id FROM clients WHERE contact_number = ?");
        $stmt->bind_param("s", $contactNumber);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['contactNumber'] = "This contact number is already registered.";
        }
        $stmt->close();
    }

    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    } else {
        // Check duplicate email
        $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['email'] = "This email is already registered.";
        }
        $stmt->close();
    }

    // Password validation
    if (empty($rawPassword)) {
        $errors['password'] = "Password is required.";
    } elseif (strlen($rawPassword) < 8) {
        $errors['password'] = "Password must be at least 8 characters long.";
    } elseif (!preg_match("/[A-Z]/", $rawPassword)) {
        $errors['password'] = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match("/[a-z]/", $rawPassword)) {
        $errors['password'] = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match("/[0-9]/", $rawPassword)) {
        $errors['password'] = "Password must contain at least one number.";
    } elseif (!preg_match("/[!@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?]/", $rawPassword)) {
        $errors['password'] = "Password must contain at least one symbol.";
    }

    if (empty($confirmPassword)) {
        $errors['confirmPassword'] = "Confirm password is required.";
    } elseif ($rawPassword !== $confirmPassword) {
        $errors['confirmPassword'] = "Passwords do not match.";
    }

    // --- 5. Insert into database if no errors ---
    if (empty($errors)) {
        $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO clients (first_name, last_name, birthday, contact_number, email, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $firstName, $lastName, $birthday, $contactNumber, $email, $hashedPassword);

        if ($stmt->execute()) {
            // Registration success: redirect to login
           // Registration success: go back to landing page
echo "<script>
    window.location.href = 'landing_page2.html';
</script>";
exit();


        } else {
            $errors['db'] = "Registration failed: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ALAZIMA Cleaning Services L.L.C - Sign Up</title>
    <link rel="icon" href="site_icon.png" type="image/png">

    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="landing_page.css">
    <style>
        /* Add specific styles for sign_up.php here if needed,
           but for now, the main goal is to reuse landing_page.css for header */

        :root {
            /* Variables from landing_page.css should be considered here or linked directly */
            --header-height: 3.5rem; /* Example value, ensure it matches your landing_page.css */
            --body-font: 'Arial', sans-serif; /* Example value, ensure it matches your landing_page.css */
            --body-color: #E5F2FB;; /* Light grey background for the page */
            --text-color: #333; /* Default text color */
            --container-color: #ffffff; /* White for containers */
            --title-color1: #333; /* Example dark color for titles */
            --z-tooltip: 10; /* Example z-index */
        }

        body {
            font-family: var(--body-font);
            background-color: var(--body-color);
            color: var(--text-color);
            margin: 0; /* Ensure no default body margin */
            line-height: 1.6;
            min-height: 100vh; /* Ensure body takes at least full viewport height */
            display: flex; /* Make body a flex container */
            flex-direction: column; /* Stack children vertically */
        }

        /* Ensure the main content pushes below the fixed header */
        .main-content {
            padding-top: var(--header-height); /* Adjust based on your header's height */
            /* Ensure the content takes up at least the remaining height of the viewport */
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding-bottom: 2rem; /* Add some padding at the bottom */
            box-sizing: border-box; /* Crucial for correct height calculation */
        }

        /* Basic styling for the registration form container to match image_a71081.png */
        .registration-form-container {
            background-color: #ffffff;
            border-radius: 1rem;
            border: 1px solid #c0c0c0; /* Lighter grey border for the container */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 1.8rem 3rem;
            max-width: 700px;
            width: 90%;
            text-align: center;
            margin: 2rem auto;
            box-sizing: border-box;
        }

        .registration-form-container h2 {
            font-size: 2em;
            color: #00487E;
            margin-bottom: 2rem;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 1px;
        }

        /* Footer styling, assuming it's similar to the landing page */
        .footer {
            background-color: #004a80;
            color: #ffffff;
            text-align: center;
            padding: 1.5rem 1rem;
            font-size: 0.9rem;
        }

        /* Responsive adjustments for the form container */
        @media screen and (max-width: 768px) {
            .registration-form-container {
                padding: 2rem;
            }
            .registration-form-container h2 {
                font-size: 1.8em;
            }
        }

        @media screen and (max-width: 480px) {
            .registration-form-container {
                padding: 1.5rem;
            }
            .registration-form-container h2 {
                font-size: 1.5em;
            }
        }


        .registration-form {
            text-align: center;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem 2rem;
            margin-bottom: 2rem;
            justify-content: center;
            align-items: start;
        }

        .form-group {
            text-align: left;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            color: #333;
            font-weight: 600;
        }

        .form-group label .required {
            color: #FF6347;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="tel"],
        .form-group input[type="date"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #a0a0a0;
            border-radius: 0.5rem;
            font-size: 1rem;
            color: #555;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.3s ease;
            background-color: #ffffff;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus,
        .form-group input[type="tel"]:focus,
        .form-group input[type="date"]:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.1rem rgba(0, 123, 255, 0.25);
        }

        .form-group input[type="date"] {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            position: relative;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        /* Error message styling */
        .error-message {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            text-align: left;
            display: block;
        }

        .form-group input.error-border {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.1rem rgba(220, 53, 69, 0.25) !important;
        }


        /* Submit Button Styling */
        .submit-button {
            background-color: #FF6347;
            color: #ffffff;
            padding: 0.75rem 2rem;
            font-size: 1rem;
            border: 1px solid #000000;
            border-radius: 0.5rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.3);
            display: inline-block;
        }

        .submit-button:hover {
            background-color: #e0543e;
            transform: translateY(-2px);
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.3);
        }

        .submit-button:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(255, 99, 71, 0.4);
        }

        /* Responsive adjustments for the form elements */
        @media screen and (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        /* New styles for password show/hide */
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

        /* Styles for Password Requirements Guide - Updated for smooth animation */
        .password-requirements {
        text-align: left;
        margin-top: 1rem;
        padding: 1rem;
        border: 1px solid #ddd;
        border-radius: 0.5rem;
        background-color: #f9f9f9;
        font-size: 0.85rem;
        color: #555;

        opacity: 0;
        max-height: 0;
        overflow: hidden;
        transition: opacity 0.3s ease-out, max-height 0.4s ease-out, padding 0.4s ease-out;
        width: 100%;
        box-sizing: border-box;
        }

        .password-requirements.show {
            opacity: 1;
            max-height: 300px;
            padding: 1rem;
        }

        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .password-requirements p {
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .password-requirements li {
            display: flex;
            align-items: center;
            margin-bottom: 0.3rem;
            color: #555;
        }

        .password-requirements li i {
            margin-right: 0.5rem;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        /* Validation feedback colors - ONLY FOR THE ICON */
        .password-requirements li i.bx-x {
            color: #dc3545;
        }

        .password-requirements li.valid i.bx-x,
        .password-requirements li.valid i.bx-check {
            color: #28a744;
        }
    </style>
</head>
<body>
    <header class="header" id="header">
        <nav class="nav container">
            <a href="landing_page.html" class="nav__logo">
                <img src="LOGO.png" alt="ALAZIMA Cleaning Services LLC Logo">
            </a>

            <div class="nav__menu" id="nav-menu">
                <ul class="nav__list">
                    <li class="nav__item">
                        <a href="landing_page.html#home" class="nav__link">Home</a>
                    </li>
                    <li class="nav__item">
                        <a href="landing_page.html#about" class="nav__link">About</a>
                    </li>
                    <li class="nav__item">
                        <a href="landing_page.html#cleaningservices" class="nav__link">Services</a>
                    </li>
                    <li class="nav__item">
                        <a href="landing_page.html#contact" class="nav__link">Contact</a>
                    </li>
                    <li class="nav__item">
                        <a href="landing_page.html#reviews" class="nav__link">Reviews</a>
                    </li>
                </ul>

                <div class="nav__close" id="nav-close">
                    <i class='bx bx-x'></i>
                </div>
            </div>

            <div class="nav__buttons">
                <button class="button--header button--sign-up" onclick="window.location.href='sign_up.php'">SIGN UP</button>
                <button class="button--header button--login" onclick="window.location.href='client_login.php'">LOGIN</button>

                <div class="nav__toggle" id="nav-toggle">
                    <i class='bx bx-menu'></i>
                </div>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <section class="registration-form-container">
            <h2>REGISTRATION FORM</h2>
            <form action="sign_up.php" method="POST" class="registration-form" novalidate>
                <?php if (isset($errors['db'])): ?>
                    <p class="error-message" style="text-align: center; margin-bottom: 1rem;"><?php echo htmlspecialchars($errors['db']); ?></p>
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="firstName"> <span class="required">*</span>First name:</label>
                        <input type="text" id="firstName" name="firstName" placeholder="Enter your first name"
                               value="<?php echo htmlspecialchars($firstName); ?>"
                               class="<?php echo isset($errors['firstName']) ? 'error-border' : ''; ?>" required>
                        <?php if (isset($errors['firstName'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($errors['firstName']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="lastName"> <span class="required">*</span>Last name:</label>
                        <input type="text" id="lastName" name="lastName" placeholder="Enter your last name"
                               value="<?php echo htmlspecialchars($lastName); ?>"
                               class="<?php echo isset($errors['lastName']) ? 'error-border' : ''; ?>" required>
                        <?php if (isset($errors['lastName'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($lastName); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="birthday"> <span class="required">*</span>Birthday:</label>
                        <input type="date" id="birthday" name="birthday" placeholder="MM/DD/YYYY"
                               value="<?php echo htmlspecialchars($birthday); ?>"
                               class="<?php echo isset($errors['birthday']) ? 'error-border' : ''; ?>" required>
                        <?php if (isset($errors['birthday'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($errors['birthday']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="contactNumber"> <span class="required">*</span>Contact Number:</label>
                        <input type="tel" id="contactNumber" name="contactNumber"
                               value="<?php echo htmlspecialchars($contactNumber); ?>"
                               placeholder="e.g. +971501234567"
                               pattern="^\+971[0-9]{9}$"
                               title="Please enter a valid UAE phone number in the format +971XXXXXXXXX (e.g., +971501234567)."
                               inputmode="tel"
                               maxlength="13"
                               class="<?php echo isset($errors['contactNumber']) ? 'error-border' : ''; ?>" required>
                        <?php if (isset($errors['contactNumber'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($errors['contactNumber']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group full-width">
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
                        <div class="password-requirements" id="passwordRequirements">
                            <ul>
                                <li id="lengthCheck"> <i class='bx bx-x'></i> At least 8 characters</li>
                                <li id="uppercaseCheck"> <i class='bx bx-x'></i> At least one uppercase letter (A-Z)</li>
                                <li id="lowercaseCheck"><i class='bx bx-x'></i> At least one lowercase letter (a-z)</li>
                                <li id="numberCheck"> <i class='bx bx-x'></i> At least one number (0-9)</li>
                                <li id="symbolCheck"> <i class='bx bx-x'></i> At least one symbol (!@#$%&* etc.)</li>
                            </ul>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword"> <span class="required">*</span>Confirm Password:</label>
                        <div class="password-input-container">
                            <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Re-enter your password"
                                   class="<?php echo isset($errors['confirmPassword']) ? 'error-border' : ''; ?>" required>
                            <i class='bx bx-show-alt password-toggle' data-target="confirmPassword"></i>
                        </div>
                        <?php if (isset($errors['confirmPassword'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($errors['confirmPassword']); ?></span>
                        <?php endif; ?>
                        <div class="password-requirements" id="confirmPasswordMatch">
                            <ul>
                                <li id="matchCheck"> <i class='bx bx-x'></i> Retype to confirm password</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <button type="submit" class="submit-button">Submit</button>
            </form>
        </section>
    </main>

    <footer class="footer">
        <p>&copy; 2025 ALAZIMA Cleaning Service. All Rights Reserved.</p>
    </footer>

    <script>
        /*=============== SHOW MENU (Mobile Navigation) ===============*/
        const navMenu = document.getElementById('nav-menu'),
              navToggle = document.getElementById('nav-toggle'),
              navClose = document.getElementById('nav-close')

        if(navToggle){
            navToggle.addEventListener('click', () =>{
                navMenu.classList.add('show-menu')
            })
        }

        if(navClose){
            navClose.addEventListener('click', () =>{
                navMenu.classList.remove('show-menu')
            })
        }

        const navLink = document.querySelectorAll('.nav__link')

        const linkAction = () =>{
            const navMenu = document.getElementById('nav-menu')
            navMenu.classList.remove('show-menu')
        }
        navLink.forEach(n => n.addEventListener('click', linkAction))

        /*=============== PHONE NUMBER INPUT RESTRICTION (UPDATED FOR +971 PREFIX - NO SPACES/DASHES) ===============*/
        document.addEventListener('DOMContentLoaded', () => {
            const contactNumberInput = document.getElementById('contactNumber');
            const prefix = '+971';
            const MAX_LENGTH = 13; // +971 (4 chars) + 9 digits = 13

            if (contactNumberInput) {
                // Ensure initial value is correct on page load
                if (!contactNumberInput.value.startsWith(prefix) || contactNumberInput.value.length < prefix.length) {
                    contactNumberInput.value = prefix;
                }

                // Move cursor to the end of the prefix on focus or click
                const setCursorPosition = () => {
                    // Use setTimeout to ensure the DOM has rendered the value first
                    setTimeout(() => {
                        if (contactNumberInput.selectionStart < prefix.length) {
                            contactNumberInput.selectionStart = contactNumberInput.selectionEnd = prefix.length;
                        }
                    }, 0); // Short delay
                };

                contactNumberInput.addEventListener('focus', setCursorPosition);
                contactNumberInput.addEventListener('click', setCursorPosition);

                // Handle input to ensure prefix and only digits after, with NO SPACES
                contactNumberInput.addEventListener('input', function(event) {
                    let value = this.value;

                    // Ensure it always starts with the prefix
                    if (!value.startsWith(prefix)) {
                        value = prefix + value.replace(/^\+/, '').replace(/[^0-9]/g, '');
                    }

                    // Extract the part after the prefix and remove ALL non-digits (including spaces)
                    let afterPrefix = value.substring(prefix.length).replace(/[^0-9]/g, '');

                    // Reconstruct the value without spaces
                    this.value = prefix + afterPrefix;

                    // Enforce maxlength
                    if (this.value.length > MAX_LENGTH) {
                        this.value = this.value.substring(0, MAX_LENGTH);
                    }

                    // Keep cursor at the end or valid position
                    setCursorPosition();
                });

                // Handle keydown to prevent deleting prefix or typing non-digits/spaces before/at prefix
                contactNumberInput.addEventListener('keydown', function(event) {
                    const cursorStart = this.selectionStart;
                    const isControlKey = event.metaKey || event.ctrlKey;

                    // Allow specific keys (arrows, backspace, delete, tab, home, end, copy/paste/cut shortcuts)
                    if (['ArrowLeft', 'ArrowRight', 'Home', 'End', 'Tab'].includes(event.key) || isControlKey || event.key.startsWith('F')) {
                        return;
                    }

                    // Prevent Backspace/Delete if cursor is within or before prefix
                    if (event.key === 'Backspace' && cursorStart <= prefix.length) {
                        event.preventDefault();
                        // If backspacing exactly at the end of prefix, reposition cursor
                        if (cursorStart === prefix.length) {
                            this.setSelectionRange(prefix.length, prefix.length);
                        }
                        return;
                    }

                    // Prevent typing non-digits or spaces
                    if (event.key.length === 1 && !/^[0-9]$/.test(event.key)) {
                        event.preventDefault();
                    }
                });

                // Handle paste to ensure prefix and only digits after, with NO SPACES
                contactNumberInput.addEventListener('paste', function(event) {
                    event.preventDefault(); // Prevent default paste behavior
                    const pasteData = event.clipboardData.getData('text');
                    let currentContent = this.value;

                    // Clean pasted data: remove all non-digits (including spaces)
                    let cleanedPaste = pasteData.replace(/[^0-9]/g, '');

                    // If the pasted data itself starts with '+' and the cleaned version lost it, re-add if applicable
                    if (pasteData.startsWith('+') && !cleanedPaste.startsWith('+')) {
                        cleanedPaste = '+' + cleanedPaste;
                    }

                    // Append cleaned pasted data after the prefix, ensuring no spaces are introduced
                    let newValue = prefix + (currentContent.substring(prefix.length) + cleanedPaste).replace(/[^0-9]/g, '');

                    // Enforce maxlength
                    if (newValue.length > MAX_LENGTH) {
                        newValue = newValue.substring(0, MAX_LENGTH);
                    }

                    this.value = newValue;
                    setCursorPosition(); // Move cursor to end
                });
            }
        });

        /*=============== NAME INPUT RESTRICTION ===============*/
        function restrictNameInput(event) {
            const input = event.target;
            let value = input.value;
            const cleanedValue = value.replace(/[^a-zA-Z\s\-]/g, '');
            if (input.value !== cleanedValue) {
                input.value = cleanedValue;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const firstNameInput = document.getElementById('firstName');
            const lastNameInput = document.getElementById('lastName');

            if (firstNameInput) {
                firstNameInput.addEventListener('input', restrictNameInput);
                firstNameInput.addEventListener('paste', (event) => {
                    event.preventDefault();
                    const pasteData = event.clipboardData.getData('text');
                    firstNameInput.value = (firstNameInput.value + pasteData);
                    restrictNameInput({ target: firstNameInput });
                });
            }

            if (lastNameInput) {
                lastNameInput.addEventListener('input', restrictNameInput);
                lastNameInput.addEventListener('paste', (event) => {
                    event.preventDefault();
                    const pasteData = event.clipboardData.getData('text');
                    lastNameInput.value = (lastNameInput.value + pasteData);
                    restrictNameInput({ target: lastNameInput });
                });
            }
        });

        /*=============== PASSWORD SHOW/HIDE (UPDATED) ===============*/
        document.addEventListener('DOMContentLoaded', () => {
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
        });

        /*=============== PASSWORD VALIDATION GUIDE (UPDATED FOR SMOOTH ANIMATION) ===============*/
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmPassword');

            const passwordRequirementsDiv = document.getElementById('passwordRequirements');
            const confirmPasswordMatchDiv = document.getElementById('confirmPasswordMatch');

            const lengthCheck = document.getElementById('lengthCheck');
            const uppercaseCheck = document.getElementById('uppercaseCheck');
            const lowercaseCheck = document.getElementById('lowercaseCheck');
            const numberCheck = document.getElementById('numberCheck');
            const symbolCheck = document.getElementById('symbolCheck');
            const matchCheck = document.getElementById('matchCheck');

            function updateRequirement(element, isValid) {
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

            function validatePasswordStrength() {
                const password = passwordInput.value;

                updateRequirement(lengthCheck, password.length >= 8);
                updateRequirement(uppercaseCheck, /[A-Z]/.test(password));
                updateRequirement(lowercaseCheck, /[a-z]/.test(password));
                updateRequirement(numberCheck, /[0-9]/.test(password));
                updateRequirement(symbolCheck, /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password));

                checkPasswordMatch();
            }

            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const passwordsMatch = password === confirmPassword && confirmPassword !== '';

                updateRequirement(matchCheck, passwordsMatch);
            }

            if (passwordInput && passwordRequirementsDiv) {
                passwordInput.addEventListener('focus', () => {
                    passwordRequirementsDiv.classList.add('show');
                    validatePasswordStrength();
                });

                passwordInput.addEventListener('blur', () => {
                    // Only hide if password is empty and no errors were displayed on blur
                    // Or keep it simple: always hide on blur for now as per previous logic.
                    // If you want it to stay open on blur when there are validation errors,
                    // we'd need to adjust this.
                    if (passwordInput.value === '') { // Only hide if input is empty
                        passwordRequirementsDiv.classList.remove('show');
                    }
                });


                passwordInput.addEventListener('input', validatePasswordStrength);
            }

            if (confirmPasswordInput && confirmPasswordMatchDiv) {
                confirmPasswordInput.addEventListener('focus', () => {
                    confirmPasswordMatchDiv.classList.add('show');
                    checkPasswordMatch();
                });

                confirmPasswordInput.addEventListener('blur', () => {
                    if (confirmPasswordInput.value === '') { // Only hide if input is empty
                        confirmPasswordMatchDiv.classList.remove('show');
                    }
                });

                confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            }

            // This block will ensure password guides are shown if there are errors after a POST submission
            <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($errors)): ?>
                if (passwordInput && passwordRequirementsDiv) {
                    passwordRequirementsDiv.classList.add('show');
                    validatePasswordStrength();
                }
                if (confirmPasswordInput && confirmPasswordMatchDiv) {
                    confirmPasswordMatchDiv.classList.add('show');
                    checkPasswordMatch();
                }
            <?php endif; ?>

            const birthdayInput = document.getElementById('birthday');
            if (birthdayInput) {
                birthdayInput.addEventListener('focus', function() {
                    this.removeAttribute('placeholder');
                });

                birthdayInput.addEventListener('blur', function() {
                    if (!this.value) {
                        this.setAttribute('placeholder', 'MM/DD/YYYY');
                    }
                });

                // Set placeholder initially if value is empty
                if (!birthdayInput.value) {
                    birthdayInput.setAttribute('placeholder', 'MM/DD/YYYY');
                }
            }
        });
    </script>

</body>
</html>