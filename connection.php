<?php
$host = 'localhost';
$db = 'alazima';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'DB Connection failed: ' . $conn->connect_error]));
}

// $servername = "localhost";
// $username = "u665838367_alazimaa";
// $password = '6$HvZ#Vd'; // safer

// $dbname = "u665838367_alazima";

// $conn = new mysqli($servername, $username, $password, $dbname);
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }