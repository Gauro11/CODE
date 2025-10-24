<?php
$servername = "localhost";
$username = "root"; // default for XAMPP
$password = ""; // default for XAMPP
$dbname = "alazima";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to create table
$sql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    service_type VARCHAR(100),
    client_type VARCHAR(100),
    service_date DATE,
    service_time TIME,
    duration VARCHAR(50),
    property_type VARCHAR(100),
    materials_provided VARCHAR(50),
    address TEXT,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'bookings' created successfully in database 'alazima'.";
} else {
    echo "❌ Error creating table: " . $conn->error;
}

$conn->close();
?>
