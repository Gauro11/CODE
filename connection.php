<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u665838367_alazimaa');
define('DB_PASS', '6$HvZ#Vd');
define('DB_NAME', 'u665838367_alazima');

// Create database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8
mysqli_set_charset($conn, "utf8");
?>