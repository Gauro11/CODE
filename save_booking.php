<?php
session_start();
require 'connection.php'; // make sure this connects to your DB

// ✅ Check if a client is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'clients') {
    echo "<script>alert('You must log in as a client first.'); window.location.href='login.php';</script>";
    exit;
}

$client_id = $_SESSION['user_id'];

// ✅ Fetch client details
$clientQuery = $conn->prepare("SELECT first_name, last_name, email, contact_number FROM clients WHERE id = ?");
$clientQuery->bind_param("i", $client_id);
$clientQuery->execute();
$clientResult = $clientQuery->get_result();
$client = $clientResult->fetch_assoc();

if (!$client) {
    echo "<script>alert('Client not found.'); window.location.href='login.php';</script>";
    exit;
}

$fullName = $client['first_name'] . ' ' . $client['last_name'];
$email = $client['email'];
$phone = $client['contact_number'];

// ✅ Get booking form data
$serviceType = $_POST['serviceType'] ?? '';
$clientType = $_POST['clientType'] ?? '';
$bookingDate = $_POST['bookingDate'] ?? '';
$bookingTime = $_POST['bookingTime'] ?? '';
$duration = $_POST['duration'] ?? '';
$address = $_POST['address'] ?? '';
$propertyLayout = $_POST['propertyLayout'] ?? '';
$cleaningMaterials = $_POST['cleaningMaterials'] ?? '';
$additionalRequest = $_POST['additionalRequest'] ?? '';

// ✅ Handle uploads
$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$mediaPaths = [null, null, null];
if (!empty($_FILES['mediaUpload']['name'][0])) {
    foreach ($_FILES['mediaUpload']['name'] as $key => $filename) {
        if (!empty($filename)) {
            $targetFile = $uploadDir . time() . "_" . basename($filename);
            $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi', 'mkv'];

            if (in_array($fileType, $allowed)) {
                if (move_uploaded_file($_FILES['mediaUpload']['tmp_name'][$key], $targetFile)) {
                    $mediaPaths[$key] = "uploads/" . basename($targetFile);
                }
            }
        }
    }
}

// ✅ Save to database
$stmt = $conn->prepare("INSERT INTO bookings 
    (full_name, email, phone, service_type, client_type, service_date, service_time, duration, property_type, materials_provided, address, comments, media1, media2, media3) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "sssssssssssssss",
    $fullName, $email, $phone,
    $serviceType, $clientType, $bookingDate, $bookingTime,
    $duration, $propertyLayout, $cleaningMaterials, $address, $additionalRequest,
    $mediaPaths[0], $mediaPaths[1], $mediaPaths[2]
);

if ($stmt->execute()) {
    echo "<script>alert('✅ Booking saved successfully, $fullName!'); window.location.href='BA_one-time.php';</script>";
} else {
    echo "❌ Database insert error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
