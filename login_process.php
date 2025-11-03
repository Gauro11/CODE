<?php
session_start();
require 'connection.php';

// Get the root URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host . '/'; // Adjust if your files are in a subfolder

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $tables = [
        'admins' => 'admin_dashboard.php',
        'employees' => 'employee_dashboard.php',
        'clients' => 'client_dashboard.php'
    ];

    $userFound = false;

    foreach ($tables as $table => $redirectPage) {
        $stmt = $conn->prepare("SELECT id, email, password FROM $table WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $table; // store table name as type
                $_SESSION['email'] = $user['email'];

                // Redirect to dashboard
                header("Location: " . $base_url . $redirectPage);
                exit;
            } else {
                // Wrong password → redirect back to landing_page2.html with error
                header("Location: " . $base_url . "landing_page2.html?error=Invalid+password");
                exit;
            }
        }
    }

    // User not found in any table → redirect back with error
    header("Location: " . $base_url . "landing_page2.html?error=User+not+found");
    exit;
}
?>
