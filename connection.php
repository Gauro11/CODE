<?php
$host = 'localhost';
$db = 'alazima';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'DB Connection failed: ' . $conn->connect_error]));
}
