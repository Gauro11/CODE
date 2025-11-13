<?php
include 'connection.php';

// Helper functions
function durationToHours($duration) {
    preg_match('/(\d+\.?\d*)/', $duration, $matches);
    return isset($matches[1]) ? floatval($matches[1]) : 0;
}

function timeRangesOverlap($start1, $end1, $start2, $end2) {
    $s1 = strtotime($start1);
    $e1 = strtotime($end1);
    $s2 = strtotime($start2);
    $e2 = strtotime($end2);
    return ($s1 < $e2) && ($s2 < $e1);
}

function getUnavailableStaff($conn, $service_date, $service_time, $duration, $current_booking_id = null) {
    $duration_hours = durationToHours($duration);
    $new_start = strtotime($service_time);
    $new_end = $new_start + ($duration_hours * 3600);
    $new_end_time = date('H:i:s', $new_end);
    
    $unavailable = [];
    $conflicts = [];
    
    // ===== STEP 1: Check ONE-TIME bookings on the same date =====
    if ($current_booking_id) {
        $stmt = $conn->prepare("
            SELECT cleaners, drivers, service_time, duration, id, booking_type
            FROM bookings 
            WHERE booking_type = 'One-Time'
            AND service_date = ? 
            AND id != ?
            AND status NOT IN ('Cancelled', 'Completed')
        ");
        $stmt->bind_param("si", $service_date, $current_booking_id);
    } else {
        $stmt = $conn->prepare("
            SELECT cleaners, drivers, service_time, duration, id, booking_type
            FROM bookings 
            WHERE booking_type = 'One-Time'
            AND service_date = ? 
            AND status NOT IN ('Cancelled', 'Completed')
        ");
        $stmt->bind_param("s", $service_date);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $existing_start = $row['service_time'];
        $existing_duration_hours = durationToHours($row['duration']);
        $existing_end = strtotime($existing_start) + ($existing_duration_hours * 3600);
        $existing_end_time = date('H:i:s', $existing_end);
        
        if (timeRangesOverlap($service_time, $new_end_time, $existing_start, $existing_end_time)) {
            $conflict_time = date('H:i', strtotime($existing_start)) . ' - ' . date('H:i', $existing_end);
            
            if (!empty($row['cleaners'])) {
                $cleaners = explode(',', $row['cleaners']);
                foreach ($cleaners as $cleaner) {
                    $name = trim($cleaner);
                    $unavailable[] = $name;
                    $conflicts[$name] = $conflict_time . ' (One-Time Booking #' . $row['id'] . ')';
                }
            }
            
            if (!empty($row['drivers'])) {
                $drivers = explode(',', $row['drivers']);
                foreach ($drivers as $driver) {
                    $name = trim($driver);
                    $unavailable[] = $name;
                    $conflicts[$name] = $conflict_time . ' (One-Time Booking #' . $row['id'] . ')';
                }
            }
        }
    }
    $stmt->close();
    
    // ===== STEP 2: Check RECURRING bookings that fall on this date =====
    // Get the day of week for the service date (e.g., "Monday", "Tuesday")
    $day_of_week = date('l', strtotime($service_date));
    
    if ($current_booking_id) {
        $stmt = $conn->prepare("
            SELECT cleaners, drivers, service_time, duration, id, booking_type, preferred_day
            FROM bookings 
            WHERE booking_type = 'Recurring'
            AND preferred_day = ?
            AND start_date <= ?
            AND (end_date >= ? OR end_date IS NULL)
            AND id != ?
            AND status NOT IN ('Cancelled', 'Completed')
        ");
        $stmt->bind_param("sssi", $day_of_week, $service_date, $service_date, $current_booking_id);
    } else {
        $stmt = $conn->prepare("
            SELECT cleaners, drivers, service_time, duration, id, booking_type, preferred_day
            FROM bookings 
            WHERE booking_type = 'Recurring'
            AND preferred_day = ?
            AND start_date <= ?
            AND (end_date >= ? OR end_date IS NULL)
            AND status NOT IN ('Cancelled', 'Completed')
        ");
        $stmt->bind_param("sss", $day_of_week, $service_date, $service_date);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $existing_start = $row['service_time'];
        $existing_duration_hours = durationToHours($row['duration']);
        $existing_end = strtotime($existing_start) + ($existing_duration_hours * 3600);
        $existing_end_time = date('H:i:s', $existing_end);
        
        if (timeRangesOverlap($service_time, $new_end_time, $existing_start, $existing_end_time)) {
            $conflict_time = date('H:i', strtotime($existing_start)) . ' - ' . date('H:i', $existing_end);
            
            if (!empty($row['cleaners'])) {
                $cleaners = explode(',', $row['cleaners']);
                foreach ($cleaners as $cleaner) {
                    $name = trim($cleaner);
                    $unavailable[] = $name;
                    $conflicts[$name] = $conflict_time . ' (Recurring Booking #' . $row['id'] . ' - Every ' . $row['preferred_day'] . ')';
                }
            }
            
            if (!empty($row['drivers'])) {
                $drivers = explode(',', $row['drivers']);
                foreach ($drivers as $driver) {
                    $name = trim($driver);
                    $unavailable[] = $name;
                    $conflicts[$name] = $conflict_time . ' (Recurring Booking #' . $row['id'] . ' - Every ' . $row['preferred_day'] . ')';
                }
            }
        }
    }
    $stmt->close();
    
    return [
        'unavailable_staff' => array_values(array_unique($unavailable)),
        'conflicts' => $conflicts
    ];
}

// Main execution
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $service_date = $_GET['service_date'] ?? '';
    $service_time = $_GET['service_time'] ?? '';
    $duration = $_GET['duration'] ?? '';
    $booking_id = $_GET['booking_id'] ?? null;
    
    if (empty($service_date) || empty($service_time) || empty($duration)) {
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }
    
    $result = getUnavailableStaff($conn, $service_date, $service_time, $duration, $booking_id);
    
    header('Content-Type: application/json');
    echo json_encode($result);
    $conn->close();
}
?>