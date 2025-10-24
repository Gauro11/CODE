<?php
session_start();
require 'connection.php';

// ✅ Ensure client is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'clients') {
    echo "<script>alert('You must log in as a client first.'); window.location.href='login.php';</script>";
    exit;
}

$client_id = $_SESSION['user_id'];

// ✅ Fetch client info
$stmt = $conn->prepare("SELECT first_name, last_name, email, contact_number FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$client) {
    echo "<script>alert('Client not found.'); window.location.href='login.php';</script>";
    exit;
}

$fullName = $client['first_name'] . ' ' . $client['last_name'];
$email = $client['email'];
$phone = $client['contact_number'];

// ✅ Get form fields safely
$serviceType = $_POST['serviceType'] ?? '';
$clientType = $_POST['clientType'] ?? '';
$frequency = $_POST['frequency'] ?? '';
$preferredDay = $_POST['preferredDay'] ?? '';
$startDate = $_POST['startDate'] ?? '';
$endDate = $_POST['endDate'] ?? '';
$bookingTime = $_POST['bookingTime'] ?? '';
$duration = $_POST['duration'] ?? '';
$address = $_POST['address'] ?? '';
$propertyLayout = $_POST['propertyLayout'] ?? '';
$cleaningMaterials = $_POST['cleaningMaterials'] ?? '';
$materialsNeeded = $_POST['materialsNeeded'] ?? '';
$additionalRequest = $_POST['additionalRequest'] ?? '';

// ✅ Format cleaning materials field
if (strtolower($cleaningMaterials) === 'yes') {
    $cleaningMaterials = 'Yes - 40 AED / hr';
} else {
    $cleaningMaterials = 'No - 0 AED / hr';
}

// ✅ Handle file uploads
$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$mediaPaths = [null, null, null];
if (!empty($_FILES['mediaUpload']['name'][0])) {
    foreach ($_FILES['mediaUpload']['name'] as $i => $fileName) {
        if (!empty($fileName)) {
            $targetFile = $uploadDir . time() . "_" . basename($fileName);
            $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi'];

            if (in_array($ext, $allowed)) {
                if (move_uploaded_file($_FILES['mediaUpload']['tmp_name'][$i], $targetFile)) {
                    $mediaPaths[$i] = "uploads/" . basename($targetFile);
                }
            }
        }
    }
}

// ✅ Insert into DB (no “price” column)
$query = "INSERT INTO bookings 
    (full_name, email, phone, service_type, client_type, frequency, preferred_day, start_date, end_date, 
     service_time, duration, property_type, materials_provided, materials_needed, address, comments, 
     media1, media2, media3, booking_type)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Recurring')";

$stmt = $conn->prepare($query);
$stmt->bind_param(
    "sssssssssssssssssss",
    $fullName, $email, $phone,
    $serviceType, $clientType, $frequency, $preferredDay,
    $startDate, $endDate, $bookingTime, $duration,
    $propertyLayout, $cleaningMaterials, $materialsNeeded,
    $address, $additionalRequest,
    $mediaPaths[0], $mediaPaths[1], $mediaPaths[2]
);

if ($stmt->execute()) {
    echo "<script>alert('✅ Recurring booking saved successfully, $fullName!'); window.location.href='client_dashboard.php?content=dashboard';</script>";
} else {
    echo "❌ Error saving booking: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
