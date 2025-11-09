<?php
require 'connection.php';

function generateCleaningSessions($booking_id) {
    global $conn;
    
    // Get booking details
    $sql = "SELECT * FROM bookings WHERE id = ? AND booking_type = 'Recurring'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    // Delete existing sessions for this booking
    $delete_sql = "DELETE FROM cleaning_sessions WHERE booking_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $booking_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Parse dates
    $start_date = new DateTime($booking['service_date']);
    $end_date = new DateTime($booking['end_date']);
    $preferred_day = $booking['preferred_day'];
    $service_time = $booking['service_time'];
    $frequency = $booking['frequency'];
    
    // Get day of week number (0 = Sunday, 6 = Saturday)
    $day_map = [
        'Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3,
        'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6
    ];
    $target_day = $day_map[$preferred_day];
    
    // Calculate interval based on frequency
    $interval = 7; // Default Weekly
    if ($frequency === 'Bi-Weekly') {
        $interval = 14;
    } elseif ($frequency === 'Monthly') {
        $interval = 30;
    }
    
    // Generate sessions
    $session_number = 1;
    $current_date = clone $start_date;
    
    // Adjust to first occurrence of preferred day
    while ($current_date->format('w') != $target_day) {
        $current_date->modify('+1 day');
    }
    
    $insert_sql = "INSERT INTO cleaning_sessions (booking_id, session_date, session_time, session_number, status) 
                   VALUES (?, ?, ?, ?, 'Scheduled')";
    $insert_stmt = $conn->prepare($insert_sql);
    
    while ($current_date <= $end_date) {
        $session_date = $current_date->format('Y-m-d');
        
        $insert_stmt->bind_param("issi", $booking_id, $session_date, $service_time, $session_number);
        $insert_stmt->execute();
        
        $session_number++;
        
        // Move to next session date
        if ($frequency === 'Monthly') {
            $current_date->modify('+1 month');
            // Adjust back to preferred day if needed
            while ($current_date->format('w') != $target_day) {
                $current_date->modify('+1 day');
            }
        } else {
            $current_date->modify("+{$interval} days");
        }
    }
    
    $insert_stmt->close();
    
    // Update estimated_sessions in bookings table
    $update_sql = "UPDATE bookings SET estimated_sessions = ?, remaining_sessions = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("iii", $session_number, $session_number, $booking_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    return $session_number - 1;
}

// Usage example (call this when booking status changes to 'Confirmed')
// generateCleaningSessions($booking_id);
?>