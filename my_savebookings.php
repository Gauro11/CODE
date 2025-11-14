<?php
session_start();
require 'connection.php';

// ✅ Ensure client is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'clients') {
    echo "<script>alert('You must log in as a client first.'); window.location.href='landing_page2.html';</script>";
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
    echo "<script>alert('Client not found.'); window.location.href='landing_page2.html';</script>";
    exit;
}

$fullName = $client['first_name'] . ' ' . $client['last_name'];
$email = $client['email'];
$phone = $client['contact_number'];

// ✅ Get form fields safely
$serviceType       = $_POST['serviceType'] ?? '';
$clientType        = $_POST['clientType'] ?? '';
$frequency         = $_POST['frequency'] ?? '';
$preferredDay      = $_POST['preferredDay'] ?? '';
$startDate         = $_POST['startDate'] ?? '';
$endDate           = $_POST['endDate'] ?? '';
$estimatedSessions = isset($_POST['estimated_sessions']) && $_POST['estimated_sessions'] !== '' ? intval($_POST['estimated_sessions']) : null;
$bookingTime       = $_POST['bookingTime'] ?? '';
$duration          = $_POST['duration'] ?? '';
$address           = $_POST['address'] ?? '';
$propertyLayout    = $_POST['propertyLayout'] ?? '';
$cleaningMaterials = $_POST['cleaningMaterials'] ?? '';
$materialsNeeded   = $_POST['materialsNeeded'] ?? '';
$additionalRequest = $_POST['additionalRequest'] ?? '';

// ✅ Initialize remaining_sessions (same as estimated_sessions initially)
$remaining_sessions = $estimatedSessions;

// ✅ Format cleaning materials field
if (strtolower($cleaningMaterials) === 'yes') {
    $cleaningMaterials = 'Yes - 40 AED / hr';
} else {
    $cleaningMaterials = 'No - 35 AED / hr';
}

// ✅ ========== RESTRICTED TIME VALIDATION ==========
function isTimeRestrictedPHP($timeString) {
    $timeParts = explode(':', $timeString);
    $hours = (int)$timeParts[0];
    $minutes = (int)$timeParts[1];
    $timeInMinutes = $hours * 60 + $minutes;
    
    // 1:00 PM - 2:00 PM (Prayer/Lunch)
    $prayerLunchStart = 13 * 60; // 780 minutes
    $prayerLunchEnd = 14 * 60;   // 840 minutes
    
    // 5:00 PM - 5:30 PM (Short Break)
    $shortBreakStart = 17 * 60;      // 1020 minutes
    $shortBreakEnd = 17 * 60 + 30;   // 1050 minutes
    
    if ($timeInMinutes >= $prayerLunchStart && $timeInMinutes < $prayerLunchEnd) {
        return "1:00 PM - 2:00 PM is reserved for Prayer/Lunch Break";
    }
    
    if ($timeInMinutes >= $shortBreakStart && $timeInMinutes < $shortBreakEnd) {
        return "5:00 PM - 5:30 PM is reserved for Short Break";
    }
    
    return false;
}

// Check if start time is restricted
$restrictionError = isTimeRestrictedPHP($bookingTime);
if ($restrictionError) {
    echo "<script>
            alert('⛔ Cannot book at this time!\\n\\n$restrictionError\\n\\nPlease choose a different time slot.');
            window.history.back();
          </script>";
    exit;
}

// ✅ ========== AVAILABILITY CHECK ==========
// Calculate end time based on duration (including break if applicable)
function calculateEndTime($startTime, $duration) {
    $start = new DateTime($startTime);
    
    // Check if break is needed (1:00 PM - 2:00 PM)
    $startHour = (int)$start->format('H');
    $startMinute = (int)$start->format('i');
    $startMinutes = $startHour * 60 + $startMinute;
    
    $breakStart = 13 * 60; // 1:00 PM
    $breakEnd = 14 * 60;   // 2:00 PM
    $endMinutes = $startMinutes + ($duration * 60);
    
    // Check if work period includes break time
    $hasBreak = ($startMinutes < $breakEnd && $endMinutes > $breakStart);
    $totalDuration = $hasBreak ? $duration + 1 : $duration;
    
    $start->modify("+{$totalDuration} hours");
    return $start->format('H:i:s');
}

$endTime = calculateEndTime($bookingTime, (int)$duration);

// ✅ Validate that booking doesn't exceed 8:00 PM
$endTimeParts = explode(':', $endTime);
$endHour = (int)$endTimeParts[0];
$endMinute = (int)$endTimeParts[1];
$endTimeInMinutes = $endHour * 60 + $endMinute;
$maxEndTime = 20 * 60; // 8:00 PM

if ($endTimeInMinutes > $maxEndTime) {
    echo "<script>
            alert('⛔ Booking cannot extend beyond 8:00 PM!\\n\\nYour selected time and duration would end at " . date('g:i A', strtotime($endTime)) . "\\n\\nPlease choose a shorter duration or earlier start time.');
            window.history.back();
          </script>";
    exit;
}

// Calculate all occurrence dates for this recurring booking
function getRecurringDates($startDate, $endDate, $frequency, $preferredDay) {
    $dates = [];
    $current = new DateTime($startDate);
    $end = new DateTime($endDate);
    $biWeeklyCounter = 0;
    
    while ($current <= $end) {
        $dayOfWeek = $current->format('l'); // Monday, Tuesday, etc.
        
        if ($frequency === 'Weekly' && $dayOfWeek === $preferredDay) {
            $dates[] = $current->format('Y-m-d');
        } elseif ($frequency === 'Daily') {
            $dates[] = $current->format('Y-m-d');
        } elseif ($frequency === 'Bi-Weekly' && $dayOfWeek === $preferredDay) {
            // For bi-weekly, only add every other occurrence
            if ($biWeeklyCounter % 2 == 0) {
                $dates[] = $current->format('Y-m-d');
            }
            $biWeeklyCounter++;
        }
        
        $current->modify('+1 day');
    }
    
    return $dates;
}

$recurringDates = getRecurringDates($startDate, $endDate, $frequency, $preferredDay);

// Step 1: Count TOTAL employees in the system (Active/Available only)
$totalEmployeesQuery = "SELECT COUNT(*) as total_employees 
                        FROM employees 
                        WHERE status = 'Available' AND archived = 0";
$totalEmployeesResult = $conn->query($totalEmployeesQuery);
$totalEmployees = $totalEmployeesResult->fetch_assoc()['total_employees'];

// Check each recurring date for conflicts
$conflictDates = [];
foreach ($recurringDates as $checkDate) {
    // Check against ONE-TIME bookings (with service_date)
    $assignedEmployeesQuery = "SELECT cleaners, drivers
                               FROM bookings 
                               WHERE service_date = ? 
                               AND (
                                   (service_time <= ? AND ADDTIME(service_time, SEC_TO_TIME(duration * 3600)) > ?) OR
                                   (service_time < ? AND ADDTIME(service_time, SEC_TO_TIME(duration * 3600)) >= ?)
                               )
                               AND status NOT IN ('Cancelled', 'Completed')";

    $checkStmt = $conn->prepare($assignedEmployeesQuery);
    $checkStmt->bind_param("sssss", $checkDate, $bookingTime, $endTime, $endTime, $bookingTime);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    // Collect unique employee names
    $assignedEmployeeNames = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['cleaners'])) {
            $cleaners = array_map('trim', explode(',', $row['cleaners']));
            $assignedEmployeeNames = array_merge($assignedEmployeeNames, $cleaners);
        }
        
        if (!empty($row['drivers'])) {
            $drivers = array_map('trim', explode(',', $row['drivers']));
            $assignedEmployeeNames = array_merge($assignedEmployeeNames, $drivers);
        }
    }
    $checkStmt->close();

    // Check against OTHER RECURRING bookings
    $dayOfWeek = date('l', strtotime($checkDate)); // Get day name
    
    $checkRecurringQuery = "SELECT cleaners, drivers
                           FROM bookings 
                           WHERE booking_type = 'Recurring'
                           AND start_date <= ? 
                           AND end_date >= ?
                           AND preferred_day = ?
                           AND (
                               (service_time <= ? AND ADDTIME(service_time, SEC_TO_TIME(duration * 3600)) > ?) OR
                               (service_time < ? AND ADDTIME(service_time, SEC_TO_TIME(duration * 3600)) >= ?)
                           )
                           AND status NOT IN ('Cancelled', 'Completed')";

    $checkStmt = $conn->prepare($checkRecurringQuery);
    $checkStmt->bind_param("sssssss", $checkDate, $checkDate, $dayOfWeek, $bookingTime, $endTime, $endTime, $bookingTime);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    // Add recurring booking employees
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['cleaners'])) {
            $cleaners = array_map('trim', explode(',', $row['cleaners']));
            $assignedEmployeeNames = array_merge($assignedEmployeeNames, $cleaners);
        }
        
        if (!empty($row['drivers'])) {
            $drivers = array_map('trim', explode(',', $row['drivers']));
            $assignedEmployeeNames = array_merge($assignedEmployeeNames, $drivers);
        }
    }
    $checkStmt->close();

    // Remove duplicates and check if all employees are busy
    $assignedEmployeeNames = array_unique($assignedEmployeeNames);
    $assignedCount = count($assignedEmployeeNames);

    if ($assignedCount >= $totalEmployees) {
        $conflictDates[] = $checkDate;
    }
}

// If there are conflicts, alert the user
if (!empty($conflictDates)) {
    $conflictList = implode(', ', $conflictDates);
    echo "<script>
            alert('⚠️ All cleaners and drivers are busy on the following dates:\\n$conflictList\\n\\nCurrently: $totalEmployees/$totalEmployees employees assigned\\nPlease adjust your booking time or dates.');
            window.history.back();
          </script>";
    exit;
}

// ✅ ========== END AVAILABILITY CHECK ==========

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

// ✅ Default values
$booking_type = 'Recurring';
$status       = 'Pending';

// ✅ Insert recurring booking WITH remaining_sessions
$remaining_sessions = 0; // ✅ START AT 0 (will increment up)

// Keep the INSERT query the same
$query = "INSERT INTO bookings 
    (full_name, email, phone, service_type, client_type, 
     service_time, duration, property_type, materials_provided, materials_needed, 
     address, comments, 
     media1, media2, media3, 
     booking_type, frequency, preferred_day, start_date, end_date, 
     estimated_sessions, remaining_sessions, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);

// 23 parameters total
$stmt->bind_param(
    "sssssssssssssssssssssss",
    $fullName,
    $email,
    $phone,
    $serviceType,
    $clientType,
    $bookingTime,
    $duration,
    $propertyLayout,
    $cleaningMaterials,
    $materialsNeeded,
    $address,
    $additionalRequest,
    $mediaPaths[0],
    $mediaPaths[1],
    $mediaPaths[2],
    $booking_type,
    $frequency,
    $preferredDay,
    $startDate,
    $endDate,
    $estimatedSessions,
    $remaining_sessions,    // ✅ This will be 0
    $status
);

if ($stmt->execute()) {
    $sessionText = $estimatedSessions ? " ($estimatedSessions sessions)" : "";
    $availableDates = count($recurringDates);
    echo "<script>alert('✅ Recurring booking saved successfully, $fullName!$sessionText\\n\\nBooking dates: $availableDates sessions scheduled'); window.location.href='client_dashboard.php?content=dashboard';</script>";
} else {
    echo "❌ Error saving booking: " . htmlspecialchars($stmt->error);
}

$stmt->close();
$conn->close();
?>