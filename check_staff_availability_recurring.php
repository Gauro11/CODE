<?php
include 'connection.php';

header('Content-Type: application/json');

$preferred_day = $_GET['preferred_day'] ?? '';
$service_time = $_GET['service_time'] ?? '';
$duration = $_GET['duration'] ?? '';
$booking_id = $_GET['booking_id'] ?? null;

// Helper function
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

// Include the getUnavailableStaffRecurring function here
function getUnavailableStaffRecurring($conn, $preferred_day, $service_time, $duration, $current_booking_id = null) {
    $duration_hours = durationToHours($duration);
    $new_start = strtotime($service_time);
    $new_end = $new_start + ($duration_hours * 3600);
    $new_end_time = date('H:i:s', $new_end);
    
    $unavailable = [];
    $conflicts = [];
    
    // CHECK OTHER RECURRING bookings
    if ($current_booking_id) {
        $stmt = $conn->prepare("
            SELECT cleaners, drivers, service_time, duration, id
            FROM bookings 
            WHERE booking_type = 'Recurring'
            AND preferred_day = ? 
            AND id != ?
            AND status NOT IN ('Cancelled', 'Completed')
        ");
        $stmt->bind_param("si", $preferred_day, $current_booking_id);
    } else {
        $stmt = $conn->prepare("
            SELECT cleaners, drivers, service_time, duration, id
            FROM bookings 
            WHERE booking_type = 'Recurring'
            AND preferred_day = ? 
            AND status NOT IN ('Cancelled', 'Completed')
        ");
        $stmt->bind_param("s", $preferred_day);
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
                    if (!in_array($name, $unavailable)) {
                        $unavailable[] = $name;
                        $conflicts[$name] = $conflict_time . ' (Recurring #' . $row['id'] . ')';
                    }
                }
            }
            
            if (!empty($row['drivers'])) {
                $drivers = explode(',', $row['drivers']);
                foreach ($drivers as $driver) {
                    $name = trim($driver);
                    if (!in_array($name, $unavailable)) {
                        $unavailable[] = $name;
                        $conflicts[$name] = $conflict_time . ' (Recurring #' . $row['id'] . ')';
                    }
                }
            }
        }
    }
    $stmt->close();
    
    // CHECK ONE-TIME bookings on same day of week
    $day_map = [
        'Sunday' => 1,
        'Monday' => 2,
        'Tuesday' => 3,
        'Wednesday' => 4,
        'Thursday' => 5,
        'Friday' => 6,
        'Saturday' => 7
    ];
    
    $day_number = $day_map[$preferred_day] ?? null;
    
    if ($day_number) {
        $stmt = $conn->prepare("
            SELECT cleaners, drivers, service_time, duration, id, service_date
            FROM bookings 
            WHERE booking_type = 'One-Time'
            AND DAYOFWEEK(service_date) = ?
            AND service_date >= CURDATE()
            AND status NOT IN ('Cancelled', 'Completed', 'No Show')
        ");
        $stmt->bind_param("i", $day_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $existing_start = $row['service_time'];
            $existing_duration_hours = durationToHours($row['duration']);
            $existing_end = strtotime($existing_start) + ($existing_duration_hours * 3600);
            $existing_end_time = date('H:i:s', $existing_end);
            
            if (timeRangesOverlap($service_time, $new_end_time, $existing_start, $existing_end_time)) {
                $conflict_time = date('H:i', strtotime($existing_start)) . ' - ' . date('H:i', $existing_end);
                $conflict_date = date('M d, Y', strtotime($row['service_date']));
                
                if (!empty($row['cleaners'])) {
                    $cleaners = explode(',', $row['cleaners']);
                    foreach ($cleaners as $cleaner) {
                        $name = trim($cleaner);
                        if (!in_array($name, $unavailable)) {
                            $unavailable[] = $name;
                            $conflicts[$name] = $conflict_time . ' (One-Time on ' . $conflict_date . ')';
                        }
                    }
                }
                
                if (!empty($row['drivers'])) {
                    $drivers = explode(',', $row['drivers']);
                    foreach ($drivers as $driver) {
                        $name = trim($driver);
                        if (!in_array($name, $unavailable)) {
                            $unavailable[] = $name;
                            $conflicts[$name] = $conflict_time . ' (One-Time on ' . $conflict_date . ')';
                        }
                    }
                }
            }
        }
        $stmt->close();
    }
    
    return [
        'unavailable_staff' => $unavailable,
        'conflicts' => $conflicts
    ];
}

$result = getUnavailableStaffRecurring($conn, $preferred_day, $service_time, $duration, $booking_id);
echo json_encode($result);
?>