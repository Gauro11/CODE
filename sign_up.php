<?php
session_start();
require_once 'connection.php';

// Initialize error array
$errors = [];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve and sanitize form data
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $contactNumber = trim($_POST['contactNumber'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rawPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Validation
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
        $errors['birthday'] = "Invalid date format.";
    } else {
        // // Check if user is at least 18 years old
        // $birthDate = new DateTime($birthday);
        // $today = new DateTime();
        // $age = $today->diff($birthDate)->y;
        // if ($age < 18) {
        //     $errors['birthday'] = "You must be at least 18 years old to register.";
        // }
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
        $errors['password'] = "Password must contain at least one special character.";
    }

    if (empty($confirmPassword)) {
        $errors['confirmPassword'] = "Please confirm your password.";
    } elseif ($rawPassword !== $confirmPassword) {
        $errors['confirmPassword'] = "Passwords do not match.";
    }

    // Insert into database if no errors
    if (empty($errors)) {
        $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO clients (first_name, last_name, birthday, contact_number, email, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $firstName, $lastName, $birthday, $contactNumber, $email, $hashedPassword);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: landing_page2.html?success=registration_complete");
            exit();
        } else {
            $stmt->close();
            $conn->close();
            header("Location: landing_page2.html?error=database_error&tab=signup");
            exit();
        }
    }
    
    // If there are errors, redirect back with error parameter
    $conn->close();
    
    // Get the first error to display
    $errorKey = key($errors);
    $errorMessage = urlencode($errors[$errorKey]);
    
    // Redirect with specific error
    if ($errorKey === 'email') {
        header("Location: landing_page2.html?error=email_error&message=" . $errorMessage . "&tab=signup");
    } elseif ($errorKey === 'contactNumber') {
        header("Location: landing_page2.html?error=contact_error&message=" . $errorMessage . "&tab=signup");
    } elseif ($errorKey === 'password' || $errorKey === 'confirmPassword') {
        header("Location: landing_page2.html?error=password_error&message=" . $errorMessage . "&tab=signup");
    } elseif ($errorKey === 'firstName' || $errorKey === 'lastName') {
        header("Location: landing_page2.html?error=name_error&message=" . $errorMessage . "&tab=signup");
    } elseif ($errorKey === 'birthday') {
        header("Location: landing_page2.html?error=birthday_error&message=" . $errorMessage . "&tab=signup");
    } else {
        header("Location: landing_page2.html?error=validation_failed&message=" . $errorMessage . "&tab=signup");
    }
    exit();
}

$conn->close();
?>