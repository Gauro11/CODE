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

// ✅ Get form fields
$serviceType       = $_POST['serviceType'] ?? '';
$clientType        = $_POST['clientType'] ?? '';
$bookingDate       = $_POST['bookingDate'] ?? '';
$bookingTime       = $_POST['bookingTime'] ?? '';
$duration          = $_POST['duration'] ?? 1;
$address           = $_POST['address'] ?? '';
$propertyLayout    = $_POST['propertyLayout'] ?? '';
$cleaningMaterials = $_POST['cleaningMaterials'] ?? 'No - 35 AED / hr';
$additionalRequest = $_POST['additionalRequest'] ?? '';

// ✅ Determine rate based on cleaning materials
$rate = 0;
if (strpos($cleaningMaterials, '40 AED') !== false) {
    $rate = 40;
} elseif (strpos($cleaningMaterials, '35 AED') !== false) {
    $rate = 35;
}

// ✅ Compute total cost (not saved, just displayed)
$total_cost = $rate * (int)$duration;

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

// ✅ Set default values for NOT NULL fields
$booking_type     = 'One-Time';
$frequency        = '';
$preferred_day    = '';
$start_date       = '';
$end_date         = '';
$materials_needed = '';
$status           = 'Pending'; // ✅ Default booking status

// ✅ Prepare query
$query = "INSERT INTO bookings (
    full_name, email, phone, service_type, client_type, service_date, service_time,
    duration, property_type, materials_provided, address, comments,
    media1, media2, media3, booking_type, frequency, preferred_day, start_date,
    end_date, materials_needed, status
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
)";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die('SQL Prepare Error: ' . $conn->error);
}

// ✅ Bind parameters (22)
$stmt->bind_param(
    "ssssssssssssssssssssss",
    $fullName,
    $email,
    $phone,
    $serviceType,
    $clientType,
    $bookingDate,
    $bookingTime,
    $duration,
    $propertyLayout,
    $cleaningMaterials,
    $address,
    $additionalRequest,
    $mediaPaths[0],
    $mediaPaths[1],
    $mediaPaths[2],
    $booking_type,
    $frequency,
    $preferred_day,
    $start_date,
    $end_date,
    $materials_needed,
    $status
);

// ✅ Execute
if ($stmt->execute()) {
    echo "<script>alert('✅ Booking saved successfully! Total: AED $total_cost'); window.location.href='client_dashboard.php?content=dashboard';</script>";
} else {
    echo '❌ Database error: ' . htmlspecialchars($stmt->error);
}

$stmt->close();
$conn->close();
?>
