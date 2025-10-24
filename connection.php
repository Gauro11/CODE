<?php
$host = 'localhost';
$db = 'alazima';     // ito na ang actual database name mo
$user = 'root';
$pass = '';          // default XAMPP password is blank

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
