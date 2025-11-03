<?php
header('Content-Type: application/json');


$servername = "localhost";
$username = "u665838367_alazimaa";
$password = '6$HvZ#Vd'; // safer

$dbname = "u665838367_alazima";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    $sql = "SELECT rating_stars, rating_comment, full_name, created_at 
            FROM bookings 
            WHERE rating_stars IS NOT NULL 
              AND rating_stars > 0
              AND rating_comment IS NOT NULL
              AND rating_comment <> ''
            ORDER BY created_at DESC
            LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'reviews' => $reviews
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
