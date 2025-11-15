<?php
include 'connection.php'; // ✅ DB Connection

// ✅ Function to get employees by position (ALL employees, ignore status field)
function getEmployeesByPosition($conn, $position) {
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM employees WHERE position = ? AND archived = 0 ORDER BY first_name, last_name");
    $stmt->bind_param("s", $position);
    $stmt->execute();
    $result = $stmt->get_result();
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    $stmt->close();
    return $employees;
}

// ✅ NEW HELPER: Convert duration string to hours
function durationToHours($duration) {
    preg_match('/(\d+\.?\d*)/', $duration, $matches);
    return isset($matches[1]) ? floatval($matches[1]) : 0;
}

// ✅ NEW HELPER: Check if two time ranges overlap
function timeRangesOverlap($start1, $end1, $start2, $end2) {
    $s1 = strtotime($start1);
    $e1 = strtotime($end1);
    $s2 = strtotime($start2);
    $e2 = strtotime($end2);
    
    // Two ranges overlap if: start1 < end2 AND start2 < end1
    return ($s1 < $e2) && ($s2 < $e1);
}

// ✅ NEW HELPER: Check if time falls in UAE prayer/lunch break (1:00 PM - 2:00 PM)
function hasBreakTimeConflict($start_time, $duration) {
    $start = strtotime($start_time);
    $duration_hours = durationToHours($duration);
    $end = $start + ($duration_hours * 3600);
    
    // Break time: 1:00 PM (13:00) to 2:00 PM (14:00)
    $break_start = strtotime('13:00:00');
    $break_end = strtotime('14:00:00');
    
    // Check if work overlaps with break time
    return ($start < $break_end) && ($end > $break_start);
}

// ✅ Function to get employee IDs from names
function getEmployeeIdsByNames($conn, $names) {
    if (empty($names)) return [];
    
    $name_array = explode(',', $names);
    $name_array = array_map('trim', $name_array);
    $name_array = array_filter($name_array);
    
    if (empty($name_array)) return [];
    
    $ids = [];
    foreach ($name_array as $full_name) {
        $name_parts = explode(' ', $full_name, 2);
        if (count($name_parts) == 2) {
            $first_name = $name_parts[0];
            $last_name = $name_parts[1];
            
            $stmt = $conn->prepare("SELECT id FROM employees WHERE first_name = ? AND last_name = ?");
            $stmt->bind_param("ss", $first_name, $last_name);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $ids[] = $row['id'];
            }
            $stmt->close();
        }
    }
    
    return $ids;
}

// ✅ UPDATED Function - Check conflicts ONLY based on bookings table (ignore employee status)

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
            SELECT cleaners, drivers, service_time, duration, id
            FROM bookings 
            WHERE booking_type = 'One-Time'
            AND service_date = ? 
            AND id != ?
            AND status NOT IN ('Cancelled', 'Completed')
        ");
        $stmt->bind_param("si", $service_date, $current_booking_id);
    } else {
        $stmt = $conn->prepare("
            SELECT cleaners, drivers, service_time, duration, id
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
                    $conflicts[$name] = $conflict_time . ' (One-Time #' . $row['id'] . ')';
                }
            }
            
            if (!empty($row['drivers'])) {
                $drivers = explode(',', $row['drivers']);
                foreach ($drivers as $driver) {
                    $name = trim($driver);
                    $unavailable[] = $name;
                    $conflicts[$name] = $conflict_time . ' (One-Time #' . $row['id'] . ')';
                }
            }
        }
    }
    $stmt->close();
    
    // ===== STEP 2: Check RECURRING bookings that fall on this date =====
    $day_of_week = date('l', strtotime($service_date));
    
    if ($current_booking_id) {
        $stmt = $conn->prepare("
            SELECT cleaners, drivers, service_time, duration, id, preferred_day
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
            SELECT cleaners, drivers, service_time, duration, id, preferred_day
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
                    $conflicts[$name] = $conflict_time . ' (Recurring #' . $row['id'] . ' - Every ' . $row['preferred_day'] . ')';
                }
            }
            
            if (!empty($row['drivers'])) {
                $drivers = explode(',', $row['drivers']);
                foreach ($drivers as $driver) {
                    $name = trim($driver);
                    $unavailable[] = $name;
                    $conflicts[$name] = $conflict_time . ' (Recurring #' . $row['id'] . ' - Every ' . $row['preferred_day'] . ')';
                }
            }
        }
    }
    $stmt->close();
    
    return [
        'unavailable_staff' => array_unique($unavailable),
        'conflicts' => $conflicts
    ];
}


// ✅ Function to get employee full names from IDs
function getEmployeeFullNames($conn, $ids) {
    if (empty($ids)) return '';
    
    $id_array = array_filter($ids);
    if (empty($id_array)) return '';
    
    $placeholders = str_repeat('?,', count($id_array) - 1) . '?';
    
    $stmt = $conn->prepare("SELECT first_name, last_name FROM employees WHERE id IN ($placeholders)");
    $types = str_repeat('i', count($id_array));
    $stmt->bind_param($types, ...$id_array);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $names = [];
    while ($row = $result->fetch_assoc()) {
        $names[] = $row['first_name'] . ' ' . $row['last_name'];
    }
    $stmt->close();
    
    return !empty($names) ? implode(', ', $names) : '';
}

// ✅ Function to display employee names
function getEmployeeNames($conn, $names) {
    if (empty($names)) return '-';
    return $names;
}

// ✅ NEW: Get groups filtered by area/location
function getGroupsByArea($conn, $address) {
    // Extract area from address (you can customize this logic)
    // For now, we'll just do a simple substring match
    $groups_query = "SELECT eg.*, 
        eg.preferred_area,
        CONCAT(e1.first_name, ' ', e1.last_name) as cleaner1_name,
        CONCAT(e2.first_name, ' ', e2.last_name) as cleaner2_name,
        CONCAT(e3.first_name, ' ', e3.last_name) as cleaner3_name,
        CONCAT(e4.first_name, ' ', e4.last_name) as cleaner4_name,
        CONCAT(e5.first_name, ' ', e5.last_name) as cleaner5_name,
        CONCAT(d.first_name, ' ', d.last_name) as driver_name
    FROM employee_groups eg
    LEFT JOIN employees e1 ON eg.cleaner1_id = e1.id
    LEFT JOIN employees e2 ON eg.cleaner2_id = e2.id
    LEFT JOIN employees e3 ON eg.cleaner3_id = e3.id
    LEFT JOIN employees e4 ON eg.cleaner4_id = e4.id
    LEFT JOIN employees e5 ON eg.cleaner5_id = e5.id
    LEFT JOIN employees d ON eg.driver_id = d.id
    ORDER BY 
        CASE 
            WHEN eg.preferred_area IS NOT NULL AND ? LIKE CONCAT('%', eg.preferred_area, '%') THEN 0
            ELSE 1
        END,
        eg.group_name";
    
    $stmt = $conn->prepare($groups_query);
    $stmt->bind_param("s", $address);
    $stmt->execute();
    return $stmt->get_result();
}

// ✅ Fetch cleaners and drivers (NO status filtering)
$cleaners = getEmployeesByPosition($conn, 'Cleaner');
$drivers = getEmployeesByPosition($conn, 'Driver');

// ✅ REPLACE the entire "Handle Staff Assignment" section with this:

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_staff'])) {
    $booking_id = $_POST['booking_id'];
    $group_id = isset($_POST['group_id']) ? $_POST['group_id'] : null;
    
    if (!$group_id) {
        echo "<script>alert('⚠️ Please select a group!'); window.history.back();</script>";
        exit;
    }
    
    // Fetch group members from database
    $group_stmt = $conn->prepare("SELECT cleaner1_id, cleaner2_id, cleaner3_id, cleaner4_id, cleaner5_id, driver_id FROM employee_groups WHERE id = ?");
    $group_stmt->bind_param("i", $group_id);
    $group_stmt->execute();
    $group_result = $group_stmt->get_result();
    $group_data = $group_result->fetch_assoc();
    $group_stmt->close();
    
    if (!$group_data) {
        echo "<script>alert('❌ Group not found!'); window.history.back();</script>";
        exit;
    }
    
    // Collect cleaner IDs (filter out nulls)
    $selected_cleaner_ids = array_filter([
        $group_data['cleaner1_id'],
        $group_data['cleaner2_id'],
        $group_data['cleaner3_id'],
        $group_data['cleaner4_id'],
        $group_data['cleaner5_id']
    ]);
    
    // Collect driver ID
    $selected_driver_ids = array_filter([$group_data['driver_id']]);
    
    // Validate minimum requirements
    if (count($selected_cleaner_ids) < 5) {
        echo "<script>alert('⚠️ This group does not have 5 cleaners assigned!'); window.history.back();</script>";
        exit;
    }
    
    if (count($selected_driver_ids) !== 1) {
        echo "<script>alert('⚠️ This group does not have a driver assigned!'); window.history.back();</script>";
        exit;
    }
    
    // Get booking details
    $check_stmt = $conn->prepare("SELECT service_date, service_time, duration, cleaners, drivers FROM bookings WHERE id = ?");
    $check_stmt->bind_param("i", $booking_id);
    $check_stmt->execute();
    $booking_result = $check_stmt->get_result();
    $booking_data = $booking_result->fetch_assoc();
    $check_stmt->close();
    
    // Check UAE prayer/lunch break conflict
    if (hasBreakTimeConflict($booking_data['service_time'], $booking_data['duration'])) {
        $break_warning = "⚠️ WARNING: This booking overlaps with UAE Prayer/Lunch Break (1:00 PM - 2:00 PM).\\n\\nEmployees should not work during this time.";
    }
    
    // Get cleaner and driver names
    $cleaner_names = getEmployeeFullNames($conn, $selected_cleaner_ids);
    $driver_names = getEmployeeFullNames($conn, $selected_driver_ids);
    
    // ✅ IMPROVED: Check conflicts for BOTH one-time AND recurring bookings
    $availability_result = getUnavailableStaff(
        $conn, 
        $booking_data['service_date'], 
        $booking_data['service_time'], 
        $booking_data['duration'], 
        $booking_id
    );
    
    $unavailable = $availability_result['unavailable_staff'];
    $conflicts = $availability_result['conflicts'];
    
    $selected_names = array_merge(
        $cleaner_names ? explode(', ', $cleaner_names) : [],
        $driver_names ? explode(', ', $driver_names) : []
    );
    
    $conflicting_staff = array_intersect($selected_names, $unavailable);
    
    if (!empty($conflicting_staff)) {
        $duration_hours = durationToHours($booking_data['duration']);
        $end_time = date('H:i', strtotime($booking_data['service_time']) + ($duration_hours * 3600));
        
        // Build detailed conflict message
        $conflict_details = [];
        foreach ($conflicting_staff as $staff) {
            $conflict_details[] = "• $staff: {$conflicts[$staff]}";
        }
        $conflict_info = implode("\\n", $conflict_details);
        
        echo "<script>
            alert('⚠️ DOUBLE BOOKING DETECTED!\\n\\n" . count($conflicting_staff) . " staff member(s) are already assigned:\\n\\n$conflict_info\\n\\nYour booking: {$booking_data['service_date']} {$booking_data['service_time']} - {$end_time} ({$booking_data['duration']})\\n\\nThis includes conflicts with BOTH one-time and recurring bookings.\\n\\nPlease select different staff or adjust the booking time.');
            window.history.back();
        </script>";
        exit;
    }
    
    // Update bookings table with staff assignments
    $stmt = $conn->prepare("UPDATE bookings SET cleaners = ?, drivers = ?, status = 'Confirmed' WHERE id = ?");
    $stmt->bind_param("ssi", $cleaner_names, $driver_names, $booking_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        echo "<script>alert('✅ Staff assigned successfully!\\n\\nNo conflicts detected with existing bookings.'); window.location.href='AP_one-time.php';</script>";
        exit;
    } else {
        $stmt->close();
        echo "<script>alert('❌ Error assigning staff: " . $conn->error . "');</script>";
    }
}

// ✅ Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['booking_id'];
    $status = $_POST['status'];

    $update = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $update->bind_param("si", $status, $id);
    $update->execute();
    $update->close();

    echo "<script>alert('Status updated successfully!'); window.location='AP_one-time.php';</script>";
    exit;
}

// ✅ Handle Booking Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking'])) {
    $id = $_POST['booking_id'];
    $service_type = $_POST['service_type'];
    $service_date = $_POST['service_date'];
    $service_time = $_POST['service_time'];
    $duration = $_POST['duration'];
    $property_type = $_POST['property_type'];
    $materials_provided = $_POST['materials_provided'];
    $address = $_POST['address'];
    $comments = $_POST['comments'];

    $update = $conn->prepare("UPDATE bookings SET service_type=?, service_date=?, service_time=?, duration=?, property_type=?, materials_provided=?, address=?, comments=? WHERE id=?");
    $update->bind_param("ssssssssi", $service_type, $service_date, $service_time, $duration, $property_type, $materials_provided, $address, $comments, $id);
    $update->execute();
    $update->close();

    echo "<script>alert('Booking updated successfully!'); window.location='AP_one-time.php';</script>";
    exit;
}

// ✅ Handle Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $id = $_POST['booking_id'];
    
    $update = $conn->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
    $update->bind_param("i", $id);
    $update->execute();
    $update->close();

    echo "<script>alert('Booking cancelled successfully!'); window.location='AP_one-time.php';</script>";
    exit;
}

// ✅ Get Status Filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'All';

// ✅ Fetch One-Time Bookings with Filter
if ($status_filter === 'All') {
    $sql = "SELECT * FROM bookings WHERE booking_type = 'One-Time' ORDER BY service_date DESC, service_time DESC";
    $result = $conn->query($sql);
} else {
    $sql = "SELECT * FROM bookings WHERE booking_type = 'One-Time' AND status = ? ORDER BY service_date DESC, service_time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status_filter);
    $stmt->execute();
    $result = $stmt->get_result();
}

// ✅ Status Color Map
$status_colors = [
    'Pending' => '#adb5bd',
    'Confirmed' => '#007bff',
    'Ongoing' => '#ffc107',
    'Completed' => '#28a745',
    'Cancelled' => '#dc3545',
    'No Show' => '#a0522d'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Management - One-Time Bookings</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="admin_db.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        .dashboard__sidebar {
    min-width: 250px;
    width: 250px;
    flex-shrink: 0;
}

.dashboard__wrapper {
    display: flex;
    min-height: 100vh;
}

.dashboard__content {
    flex: 1;
    overflow-x: auto;
}
        /* ===== STATUS FILTER TABS ===== */
        .status-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .status-tab {
            padding: 10px 20px;
            background: #f8f9fa;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }

        .status-tab:hover {
            background: #e9ecef;
            border-color: #007bff;
        }

        .status-tab.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .status-tab.all { border-color: #6c757d; }
        .status-tab.all.active { background: #6c757d; border-color: #6c757d; }
        
        .status-tab.pending { border-color: #adb5bd; }
        .status-tab.pending.active { background: #adb5bd; border-color: #adb5bd; }
        
        .status-tab.confirmed { border-color: #007bff; }
        .status-tab.confirmed.active { background: #007bff; border-color: #007bff; }
        
        .status-tab.ongoing { border-color: #ffc107; }
        .status-tab.ongoing.active { background: #ffc107; color: #333; border-color: #ffc107; }
        
        .status-tab.completed { border-color: #28a745; }
        .status-tab.completed.active { background: #28a745; border-color: #28a745; }
        
        .status-tab.cancelled { border-color: #dc3545; }
        .status-tab.cancelled.active { background: #dc3545; border-color: #dc3545; }
        
        .status-tab.no-show { border-color: #a0522d; }
        .status-tab.no-show.active { background: #a0522d; border-color: #a0522d; }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 14px;
            text-align: left;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        tr:nth-child(even) { background-color: #f8f9fa; }
        tr:hover { background-color: #eef6ff; }

        .status-badge {
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        select {
            padding: 6px 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button, .btn {
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 6px 12px;
            cursor: pointer;
            margin-right: 5px;
            font-size: 13px;
        }

        button:hover, .btn:hover { background: #0056b3; }

        .btn-view {
            background: #28a745;
            padding: 8px 14px;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
        }

        .btn-view:hover { background: #218838; }

        .btn-call {
            background: #17a2b8;
            padding: 8px 14px;
        }

        .btn-call:hover { background: #138496; }

        .btn-edit {
            background: #ffc107;
            color: #333;
            padding: 8px 14px;
        }

        .btn-edit:hover { background: #e0a800; }

        .btn-cancel {
            background: #dc3545;
            padding: 8px 14px;
        }

        .btn-cancel:hover { background: #c82333; }

        .btn-reschedule {
            background: #6f42c1;
            padding: 8px 14px;
        }

        .btn-reschedule:hover { background: #5a32a3; }

        .btn-report {
            background: #fd7e14;
            padding: 8px 14px;
        }

        .btn-report:hover { background: #e56b00; }

        .btn-invoice {
            background: #20c997;
            padding: 8px 14px;
        }

        .btn-invoice:hover { background: #1aa179; }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .no-data { text-align: center; color: #777; padding: 20px; }

        .has-dropdown .dropdown__menu { display: none; }
        .has-dropdown.open .dropdown__menu { display: block; }

        /* ===== MODAL STYLES ===== */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            overflow-y: auto;
            padding: 20px;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px 40px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .modal-content h3 {
            font-size: 1.8em;
            font-weight: 700;
            color: #007bff;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 12px;
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 32px;
            font-weight: bold;
            color: #dc3545;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s;
        }

        .close-btn:hover {
            background-color: #f8f9fa;
            color: #c82333;
        }

        /* Grid Layout for Details */
        #modal-details-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }

        #modal-details-content p {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }

        #modal-details-content p.full-width-detail {
            grid-column: 1 / -1;
        }

        #modal-details-content p strong {
            font-weight: 700;
            color: #555;
            margin-bottom: 5px;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #modal-details-content p strong i {
            font-size: 1.3em;
            color: #007bff;
        }

        #modal-details-content p span,
        #modal-details-content p:not(:has(strong)) {
            color: #333;
            font-size: 1em;
            line-height: 1.5;
        }

        .ref-no-detail {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            border-left: none !important;
            font-size: 1.1em;
            padding: 15px !important;
        }

        .ref-no-detail strong {
            color: white !important;
        }

        .ref-no-value {
            font-size: 1.3em;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .status-detail .status-tag {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            color: white;
            font-size: 0.95em;
            margin-top: 5px;
        }

        .status-tag.pending { background-color: #adb5bd; }
        .status-tag.confirmed { background-color: #007bff; }
        .status-tag.ongoing { background-color: #ffc107; color: #333; }
        .status-tag.completed { background-color: #28a745; }
        .status-tag.cancelled { background-color: #dc3545; }
        .status-tag.no-show { background-color: #a0522d; }

        .divider {
            border: none;
            border-top: 2px solid #e0e0e0;
            margin: 15px 0;
        }

        /* Images Section */
        .images-section {
            grid-column: 1 / -1;
            margin-top: 10px;
        }

        .images-section h4 {
            font-size: 1.2em;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .images-section h4 i {
            color: #007bff;
            font-size: 1.3em;
        }

        .images-grid {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .image-item {
            width: 180px;
            height: 180px;
            border-radius: 8px;
            overflow: hidden;
            border: 3px solid #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .image-item:hover {
            transform: scale(1.05);
            border-color: #007bff;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-images {
            color: #999;
            font-style: italic;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            border-radius: 8px;
        }

        /* Image Lightbox */
        .lightbox {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }

        .lightbox.show {
            display: flex;
        }

        .lightbox img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(255, 255, 255, 0.1);
        }

        .lightbox-close {
            position: absolute;
            top: 30px;
            right: 40px;
            color: white;
            font-size: 50px;
            cursor: pointer;
            font-weight: bold;
            transition: color 0.3s;
        }

        .lightbox-close:hover {
            color: #dc3545;
        }

        /* Staff Details */
        .staff-details-container {
            background-color: #f0f8ff;
            margin-top: 15px;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #007bff;
            grid-column: 1 / -1;
        }

        .staff-details-container h4 {
            font-size: 1.1em;
            font-weight: bold;
            color: #004085;
            margin-bottom: 12px;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #b3d7ff;
        }

        .staff-details-container h4 i {
            color: #007bff;
            font-size: 1.3em;
        }

        .staff-details-container p {
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
            line-height: 1.5;
            background: white;
            padding: 10px;
            border-radius: 6px;
        }

        .staff-details-container p i {
            font-size: 1.2em;
            margin-right: 10px;
            color: #0056b3;
        }

        .staff-details-container p strong {
            font-weight: 600;
            margin-right: 5px;
            min-width: 70px;
            color: #333;
        }

        /* Edit Form Styles */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            #modal-details-content {
                grid-template-columns: 1fr;
            }

            .modal-content {
                padding: 20px;
            }

            .status-tabs {
                gap: 5px;
            }

            .status-tab {
                padding: 8px 12px;
                font-size: 12px;
            }
        }
        
    </style>
</head>
<body>

<!-- HEADER -->
<header class="header" id="header">
    <nav class="nav container">
        <a href="admin_dashboard.php?content=dashboard" class="nav__logo">
            <img src="LOGO.png" alt="ALAZIMA Logo"
                onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
        </a>
        <button class="nav__toggle" id="nav-toggle"><i class='bx bx-menu'></i></button>
    </nav>
</header>

<!-- DASHBOARD WRAPPER -->
<div class="dashboard__wrapper">
    <!-- SIDEBAR -->
   <aside class="dashboard__sidebar">
    <ul class="sidebar__menu">
        <li class="menu__item"><a href="admin_dashboard.php?content=dashboard" class="menu__link"><i class='bx bx-home-alt-2'></i> Dashboard</a></li>

        <li class="menu__item has-dropdown">
            <a href="#" class="menu__link"><i class='bx bx-user-circle'></i> User Management <i class='bx bx-chevron-down arrow-icon'></i></a>
            <ul class="dropdown__menu">
                <li class="menu__item"><a href="clients.php" class="menu__link">Clients</a></li>
                <li class="menu__item"><a href="UM_employees.php" class="menu__link">Employees</a></li>
                <li class="menu__item"><a href="UM_admins.php" class="menu__link">Admins</a></li>
                 <li class="menu__item"><a href="archived_clients.php?content=manage-archive" class="menu__link" data-content="manage-archive">Archive</a></li>
            </ul>
        </li>

        <li class="menu__item has-dropdown open">
            <a href="#" class="menu__link active-parent"><i class='bx bx-calendar-check'></i> Appointment Management <i class='bx bx-chevron-down arrow-icon'></i></a>
            <ul class="dropdown__menu">
                <li class="menu__item"><a href="AP_one-time.php" class="menu__link active">One-time Service</a></li>
                <li class="menu__item"><a href="AP_recurring.php" class="menu__link">Recurring Service</a></li>
            </ul>
        </li>

        <li class="menu__item"><a href="ES.php" class="menu__link"><i class='bx bx-time'></i> Employee Scheduling</a></li>
        <li class="menu__item"><a href="manage_groups.php" class="menu__link "><i class='bx bx-group'></i> Manage Groups</a></li>
         <li class="menu__item"><a href="admin_feedback_dashboard.php" class="menu__link "><i class='bx bx-star'></i> Feedback Overview</a></li>
        <!-- <li class="menu__item"><a href="FR.php" class="menu__link"><i class='bx bx-star'></i> Feedback & Ratings</a></li> -->
        <li class="menu__item"><a href="Reports.php" class="menu__link"><i class='bx bx-file'></i> Reports</a></li>
           <li class="menu__item"><a href="concern.php?content=profile" class="menu__link" data-content="profile"><i class='bx bx-info-circle'></i> Issues&Concerns</a></li>
        <li class="menu__item"><a href="admin_profile.php" class="menu__link"><i class='bx bx-user'></i> Profile</a></li>
        <li class="menu__item"><a href="javascript:void(0)" class="menu__link" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
    </ul>
</aside>

<!-- MAIN CONTENT -->
<main class="dashboard__content">
    <section class="content__section active">
        <div class="content-container">
            <h2><i class='bx bx-calendar-check'></i> Appointment Management - One-Time Bookings</h2>
            
            <!-- SEARCH BAR (Frontend Only) -->
            <div class="search-container">
                <div class="search-form">
                    <div class="search-input-wrapper">
                        <i class='bx bx-search'></i>
                        <input 
                            type="text" 
                            id="searchInput"
                            class="search-input" 
                            placeholder="Search by name, or service type..." 
                            onkeyup="searchTable()"
                        >
                        <button class="clear-search" id="clearSearchBtn" style="display: none;" onclick="clearSearch()">
                            <i class='bx bx-x'></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Search Results Info -->
            <div id="searchResultsInfo" style="display: none; padding: 10px; background: #e7f3ff; border-radius: 6px; margin-bottom: 15px;">
                <i class='bx bx-info-circle'></i> 
                <span id="resultsCount"></span>
            </div>
            
            <!-- STATUS FILTER TABS -->
            <div class="status-tabs">
                <a href="AP_one-time.php?status=All<?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="status-tab all <?= $status_filter === 'All' ? 'active' : '' ?>">
                    <i class='bx bx-list-ul'></i> All
                </a>
                <a href="AP_one-time.php?status=Pending<?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="status-tab pending <?= $status_filter === 'Pending' ? 'active' : '' ?>">
                    <i class='bx bx-time'></i> Pending
                </a>
                <a href="AP_one-time.php?status=Confirmed<?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="status-tab confirmed <?= $status_filter === 'Confirmed' ? 'active' : '' ?>">
                    <i class='bx bx-check-circle'></i> Confirmed
                </a>
                <a href="AP_one-time.php?status=Ongoing<?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="status-tab ongoing <?= $status_filter === 'Ongoing' ? 'active' : '' ?>">
                    <i class='bx bx-loader-alt'></i> Ongoing
                </a>
                <a href="AP_one-time.php?status=Completed<?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="status-tab completed <?= $status_filter === 'Completed' ? 'active' : '' ?>">
                    <i class='bx bx-check-double'></i> Completed
                </a>
                <a href="AP_one-time.php?status=Cancelled<?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="status-tab cancelled <?= $status_filter === 'Cancelled' ? 'active' : '' ?>">
                    <i class='bx bx-x-circle'></i> Cancelled
                </a>
                <a href="AP_one-time.php?status=No Show<?= !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="status-tab no-show <?= $status_filter === 'No Show' ? 'active' : '' ?>">
                    <i class='bx bx-user-x'></i> No Show
                </a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Service Type</th>
                        <th>Service Date</th>
                        <th>Time</th>
                        <th>Assigned Cleaners</th>
                        <th>Assigned Drivers</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): 
        $status = $row['status'] ?? 'Pending';
        $color = $status_colors[$status] ?? '#adb5bd';
        $rowData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
    ?>
    <tr>
        <td><?= htmlspecialchars($row['full_name']) ?></td>
        <td><?= htmlspecialchars($row['phone']) ?></td>
        <td><?= htmlspecialchars($row['service_type']) ?></td>
        <td><?= htmlspecialchars($row['service_date']) ?></td>
        <td><?= date("h:i A", strtotime($row['service_time'])) ?></td>
        <td class="staff-names">
            <i class='bx bx-spray-can'></i>
            <?= getEmployeeNames($conn, $row['cleaners']) ?>
        </td>
        <td class="staff-names">
            <i class='bx bx-car'></i>
            <?= getEmployeeNames($conn, $row['drivers']) ?>
        </td>
        <td><span class="status-badge" style="background: <?= $color ?>;"><?= htmlspecialchars($status) ?></span></td>

        <td style="position: relative;">
            <div class="dropdown">
                <button class="dropdown-btn" onclick="toggleDropdown(this)">
                    <i class='bx bx-dots-horizontal-rounded'></i>
                </button>
                <div class="dropdown-content">
                    <?php if ($status === 'Pending'): ?>
                        <button class="btn btn-call" onclick="callClient('<?= htmlspecialchars($row['phone']) ?>')">
                            <i class='bx bx-phone'></i> Call
                        </button>
                        <button class="btn btn-assign" onclick='openAssignModal(<?= $rowData ?>)'>
                            <i class='bx bx-user-plus'></i> Assign Staff
                        </button>
                        <button class="btn btn-cancel" onclick="confirmCancel(<?= $row['id'] ?>)">
                            <i class='bx bx-x'></i> Cancel
                        </button>

                    <?php elseif ($status === 'Confirmed'): ?>
                        <button class="btn btn-call" onclick="callClient('<?= htmlspecialchars($row['phone']) ?>')">
                            <i class='bx bx-phone'></i> Call
                        </button>
                        <button class="btn btn-assign" onclick='openAssignModal(<?= $rowData ?>)'>
                            <i class='bx bx-user-plus'></i> Assign Staff
                        </button>
                        <button class="btn btn-cancel" onclick="confirmCancel(<?= $row['id'] ?>)">
                            <i class='bx bx-x'></i> Cancel
                        </button>

                    <?php elseif ($status === 'Ongoing'): ?>
                        <button class="btn btn-call" onclick="callClient('<?= htmlspecialchars($row['phone']) ?>')">
                            <i class='bx bx-phone'></i> Call
                        </button>
                       

                    <?php elseif ($status === 'Completed'): ?>
                        <button class="btn btn-call" onclick="callClient('<?= htmlspecialchars($row['phone']) ?>')">
                            <i class='bx bx-phone'></i> Call
                        </button>
                    <?php endif; ?>

                    <!-- Update Status Dropdown -->
                    <form method="POST" action="" style="margin-top: 8px;">
                        <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                        <select name="status" style="padding:6px; width:100%;">
                            <?php foreach ($status_colors as $s => $c): ?>
                                <option value="<?= $s ?>" <?= ($status === $s) ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_status" style="padding:6px 10px; width:100%; margin-top:4px;">Update</button>
                    </form>
                </div>
            </div>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="9" class="no-data">No bookings found for this status.</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<style>
/* Search Container Styles */
.search-container {
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.search-form {
    display: flex;
    gap: 10px;
    align-items: center;
}

.search-input-wrapper {
    position: relative;
    flex: 1;
    display: flex;
    align-items: center;
}

.search-input-wrapper > .bx-search {
    position: absolute;
    left: 12px;
    font-size: 20px;
    color: #6c757d;
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 12px 40px 12px 40px;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.clear-search {
    position: absolute;
    right: 12px;
    color: #6c757d;
    cursor: pointer;
    font-size: 20px;
    transition: color 0.2s;
    text-decoration: none;
    background: none;
    border: none;
    padding: 5px;
}

.clear-search:hover {
    color: #dc3545;
}

.search-btn {
    padding: 12px 24px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background 0.3s ease;
    white-space: nowrap;
}

.search-btn:hover {
    background: #45a049;
}

.search-btn i {
    font-size: 18px;
}



/* No results message */
.no-results-row {
    background-color: #fff3cd !important;
}

.no-results-row td {
    text-align: center;
    padding: 30px !important;
    color: #856404;
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 768px) {
    .search-form {
        flex-direction: column;
    }
    
    .search-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Frontend-only table search function
function searchTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase().trim();
    const table = document.querySelector('table tbody');
    const rows = table.getElementsByTagName('tr');
    const clearBtn = document.getElementById('clearSearchBtn');
    const resultsInfo = document.getElementById('searchResultsInfo');
    const resultsCount = document.getElementById('resultsCount');
    
    let visibleCount = 0;
    let totalCount = 0;
    
    // Show/hide clear button
    clearBtn.style.display = filter ? 'block' : 'none';
    
    // Loop through all table rows
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        
        // Skip "no data" rows
        if (row.classList.contains('no-data') || row.querySelector('.no-data')) {
            continue;
        }
        
        totalCount++;
        
        // Get text content from relevant columns (name, phone, service type)
        const cells = row.getElementsByTagName('td');
        if (cells.length > 0) {
            const name = cells[0].textContent || cells[0].innerText;
            const phone = cells[1].textContent || cells[1].innerText;
            const serviceType = cells[2].textContent || cells[2].innerText;
            
            // Combine all searchable text
            const searchText = (name + phone + serviceType).toLowerCase();
            
            // Show/hide row based on match
            if (searchText.includes(filter) || filter === '') {
                row.style.display = '';
                visibleCount++;
                
                // Optional: Highlight matching text
                if (filter) {
                    highlightText(cells[0], filter);
                    highlightText(cells[1], filter);
                    highlightText(cells[2], filter);
                }
            } else {
                row.style.display = 'none';
            }
        }
    }
    
    // Show results info
    if (filter) {
        resultsInfo.style.display = 'block';
        resultsCount.textContent = `Found ${visibleCount} of ${totalCount} bookings`;
        
        if (visibleCount === 0) {
            // Show "no results" message
            const noResultsRow = table.querySelector('.no-results-row');
            if (!noResultsRow) {
                const newRow = table.insertRow(0);
                newRow.className = 'no-results-row';
                newRow.innerHTML = '<td colspan="9"><i class="bx bx-search-alt"></i> No bookings match your search</td>';
            }
        } else {
            // Remove "no results" message if it exists
            const noResultsRow = table.querySelector('.no-results-row');
            if (noResultsRow) {
                noResultsRow.remove();
            }
        }
    } else {
        resultsInfo.style.display = 'none';
        // Remove "no results" message
        const noResultsRow = table.querySelector('.no-results-row');
        if (noResultsRow) {
            noResultsRow.remove();
        }
    }
}

// Clear search function
function clearSearch() {
    document.getElementById('searchInput').value = '';
    searchTable();
    document.getElementById('searchInput').focus();
}

// Highlight matching text (optional visual enhancement)
function highlightText(cell, searchTerm) {
    if (!searchTerm) {
        // Remove all highlights
        cell.innerHTML = cell.textContent;
        return;
    }
    
    const text = cell.textContent;
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    const highlightedText = text.replace(regex, '<span class="highlight">$1</span>');
    
    // Only update if there's a match
    if (text !== highlightedText) {
        cell.innerHTML = highlightedText;
    }
}

// Optional: Search on Enter key
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchTable();
            }
        });
    }
});
</script>



<!-- ASSIGN STAFF MODAL --><!-- ASSIGN STAFF MODAL --><!-- ASSIGN STAFF MODAL WITH GROUPS -->
 <!-- ASSIGN STAFF MODAL WITH INTELLIGENT SCHEDULING -->
<div id="assignModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <span class="close-btn" onclick="closeAssignModal()">&times;</span>
        <h3><i class='bx bx-user-plus'></i> Assign Staff to Booking</h3>
        
        <form method="POST" action="" id="assignStaffForm">
            <input type="hidden" name="booking_id" id="assign_booking_id">
            <input type="hidden" id="assign_duration_hidden" value="">
            <input type="hidden" name="group_id" id="selected_group_id"> 
            
            <!-- Booking Info Display -->
            <div class="booking-info" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <p style="margin: 8px 0;"><strong><i class='bx bx-user'></i> Client:</strong> <span id="assign_client_name"></span></p>
                        <p style="margin: 8px 0;"><strong><i class='bx bx-briefcase'></i> Service:</strong> <span id="assign_service_type"></span></p>
                        <p style="margin: 8px 0;"><strong><i class='bx bx-calendar'></i> Date:</strong> <span id="assign_service_date"></span></p>
                    </div>
                    <div>
                        <p style="margin: 8px 0;"><strong><i class='bx bx-time'></i> Time:</strong> <span id="assign_service_time"></span></p>
                        <p style="margin: 8px 0;"><strong><i class='bx bx-timer'></i> Duration:</strong> <span id="assign_duration"></span></p>
                        <p style="margin: 8px 0;"><strong><i class='bx bx-map'></i> Area:</strong> <span id="assign_address"></span></p>
                    </div>
                </div>
            </div>

            <!-- Prayer Break Warning -->
            <div id="prayer_break_warning" style="display: none; background:white ; border: ; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
                <!-- <p style="color: #856404; margin: 0; font-weight: 600;">
                    <i class='bx bx-error'></i> ⚠️ UAE Prayer/Lunch Break (1:00 PM - 2:00 PM): This booking overlaps with mandatory break time!
                </p> -->
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #dee2e6;">

            <!-- GROUP SELECTION -->
            <div class="form-group">
                <label>
                    <i class='bx bx-group'></i> Select Pre-defined Group
                    <span style="font-size: 0.9em; color: #6c757d;"></span>
                    <a href="manage_groups.php" target="_blank" style="margin-left: 10px; color: #007bff; font-size: 0.9em;">
                        <i class='bx bx-cog'></i> Manage Groups
                    </a>
                </label>
                <select id="group_select" class="group-select" onchange="loadGroupMembers()">
                    <option value="">-- Select a Group --</option>
                    <?php
                    // Fetch groups from database with area information
                    $groups_query = "SELECT eg.*, 
                        CONCAT(e1.first_name, ' ', e1.last_name) as cleaner1_name,
                        CONCAT(e2.first_name, ' ', e2.last_name) as cleaner2_name,
                        CONCAT(e3.first_name, ' ', e3.last_name) as cleaner3_name,
                        CONCAT(e4.first_name, ' ', e4.last_name) as cleaner4_name,
                        CONCAT(e5.first_name, ' ', e5.last_name) as cleaner5_name,
                        CONCAT(d.first_name, ' ', d.last_name) as driver_name
                    FROM employee_groups eg
                    LEFT JOIN employees e1 ON eg.cleaner1_id = e1.id
                    LEFT JOIN employees e2 ON eg.cleaner2_id = e2.id
                    LEFT JOIN employees e3 ON eg.cleaner3_id = e3.id
                    LEFT JOIN employees e4 ON eg.cleaner4_id = e4.id
                    LEFT JOIN employees e5 ON eg.cleaner5_id = e5.id
                    LEFT JOIN employees d ON eg.driver_id = d.id
                    ORDER BY eg.group_name";
                    $groups_result = $conn->query($groups_query);
                    
                    if ($groups_result) {
                        while ($group = $groups_result->fetch_assoc()) {
                            $group_json = htmlspecialchars(json_encode($group), ENT_QUOTES, 'UTF-8');
                            $area_label = !empty($group['preferred_area']) ? " [{$group['preferred_area']}]" : "";
                            echo "<option value='{$group['id']}' data-group='{$group_json}'>{$group['group_name']}{$area_label}</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            
            <!-- Group Preview -->
            <div id="selected_group_preview" style="display: none; background: #e7f3ff; padding: 15px; border-radius: 8px; margin-top: 15px;">
                <h4 style="color: #007bff; margin-bottom: 10px;">
                    <i class='bx bx-check-circle'></i> Selected Group Members
                </h4>
                
                <!-- Conflict Warning -->
                <div id="group_conflict_warning" style="display: none; background: #fff3cd; border: 1px solid #ffc107; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
                    <p style="color: #856404; margin: 0; font-weight: 600;">
                        <i class='bx bx-error'></i> <span id="conflict_message"></span>
                    </p>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <p style="font-weight: 600; margin-bottom: 8px;">
                            <i class='bx bx-spray-can'></i> Cleaners:
                        </p>
                        <ul id="group_cleaners_list" style="list-style: none; padding-left: 20px;">
                        </ul>
                    </div>
                    <div>
                        <p style="font-weight: 600; margin-bottom: 8px;">
                            <i class='bx bx-car'></i> Driver:
                        </p>
                        <ul id="group_driver_list" style="list-style: none; padding-left: 20px;">
                        </ul>
                    </div>
                </div>
                
                <!-- Assignment Summary -->
                <div id="assignment_summary" style="margin-top: 15px; padding: 10px; background: #d4edda; border-radius: 6px;">
                    <p style="margin: 0; color: #155724; font-weight: 600;">
                        <i class='bx bx-info-circle'></i> This group can be assigned to multiple bookings on the same day, as long as times don't overlap.
                    </p>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-cancel" onclick="closeAssignModal()">Cancel</button>
                <button type="submit" name="assign_staff" class="btn btn-view" id="assignStaffBtn">
                    <i class='bx bx-check'></i> Assign Staff
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Group Select Styles */
.group-select {
    width: 100%;
    padding: 12px;
    border: 2px solid #007bff;
    border-radius: 8px;
    font-size: 15px;
    background: white;
    cursor: pointer;
    transition: all 0.3s;
}

.group-select:hover {
    border-color: #0056b3;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
}

.group-select:focus {
    outline: none;
    border-color: #0056b3;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

#selected_group_preview ul {
    list-style: none;
    padding-left: 0;
}

#selected_group_preview ul li {
    padding: 5px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

#selected_group_preview ul li i {
    color: #28a745;
    font-size: 1.2em;
}

#selected_group_preview ul li.unavailable-member {
    color: #dc3545;
}

#selected_group_preview ul li.unavailable-member i {
    color: #dc3545;
}

#selected_group_preview ul li .conflict-time {
    margin-left: auto;
    font-size: 0.85em;
    color: #dc3545;
    font-weight: 600;
}

/* Assign Modal Specific Styles */
#assignModal .booking-info p {
    background: transparent !important;
    border: none !important;
    padding: 0 !important;
    margin: 8px 0 !important;
    display: block !important;
}

#assignModal .form-group {
    margin-bottom: 20px;
}

#assignModal .form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
    font-size: 1rem;
}

.unavailable-badge {
    background: #dc3545;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75em;
    margin-left: auto;
    font-weight: 600;
}
</style>

<script>
// Check if booking overlaps with UAE prayer/lunch break (1:00 PM - 2:00 PM)
function checkPrayerBreak(startTime, duration) {
    const start = new Date(`2000-01-01 ${startTime}`);
    const durationMatch = duration.match(/(\d+\.?\d*)/);
    const hours = durationMatch ? parseFloat(durationMatch[1]) : 0;
    const end = new Date(start.getTime() + hours * 3600000);
    
    const breakStart = new Date('2000-01-01 13:00:00');
    const breakEnd = new Date('2000-01-01 14:00:00');
    
    return (start < breakEnd && end > breakStart);
}

function loadGroupMembers() {
    const select = document.getElementById('group_select');
    const selectedOption = select.options[select.selectedIndex];
    const preview = document.getElementById('selected_group_preview');
    const conflictWarning = document.getElementById('group_conflict_warning');
     document.getElementById('selected_group_id').value = selectedOption.value || '';
    if (!selectedOption.value) {
        preview.style.display = 'none';
        return;
    }
    
    const groupData = JSON.parse(selectedOption.getAttribute('data-group'));
    console.log('🔍 Group data loaded:', groupData);
    
    preview.style.display = 'block';
    conflictWarning.style.display = 'none';
    
    const cleanersList = document.getElementById('group_cleaners_list');
    const driversList = document.getElementById('group_driver_list');
    cleanersList.innerHTML = '';
    driversList.innerHTML = '';
    
    // Get booking details for conflict checking
    const serviceDate = document.getElementById('assign_service_date').textContent;
    const serviceTime = document.getElementById('assign_service_time').textContent;
    const duration = document.getElementById('assign_duration').textContent;
    const bookingId = document.getElementById('assign_booking_id').value;
    
    // Fetch unavailable staff
    fetch(`check_staff_availability.php?service_date=${serviceDate}&service_time=${serviceTime}&duration=${encodeURIComponent(duration)}&booking_id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            console.log('📊 Availability data:', data);
            
            const unavailableStaff = data.unavailable_staff || [];
            const conflicts = data.conflicts || {};
            let unavailableCount = 0;
            
            // Process cleaners
            for (let i = 1; i <= 5; i++) {
                const cleanerName = groupData[`cleaner${i}_name`];
                const cleanerId = groupData[`cleaner${i}_id`];
                
                if (cleanerName && cleanerName !== 'null null' && cleanerId) {
                    const li = document.createElement('li');
                    
                    if (unavailableStaff.includes(cleanerName)) {
                        li.className = 'unavailable-member';
                        const conflictTime = conflicts[cleanerName] || 'Unknown time';
                        li.innerHTML = `
                            <i class='bx bx-x-circle'></i> 
                            ${cleanerName}
                            <span class="conflict-time">${conflictTime}</span>
                        `;
                        unavailableCount++;
                    } else {
                        li.innerHTML = `<i class='bx bx-check-circle'></i> ${cleanerName}`;
                    }
                    
                    cleanersList.appendChild(li);
                }
            }
            
            // Process driver
            const driverName = groupData.driver_name;
            const driverId = groupData.driver_id;
            
            if (driverName && driverName !== 'null null' && driverId) {
                const li = document.createElement('li');
                
                if (unavailableStaff.includes(driverName)) {
                    li.className = 'unavailable-member';
                    const conflictTime = conflicts[driverName] || 'Unknown time';
                    li.innerHTML = `
                        <i class='bx bx-x-circle'></i> 
                        ${driverName}
                        <span class="conflict-time">${conflictTime}</span>
                    `;
                    unavailableCount++;
                } else {
                    li.innerHTML = `<i class='bx bx-check-circle'></i> ${driverName}`;
                }
                
                driversList.appendChild(li);
            }
            
            // Show conflict warning if any members are unavailable
            if (unavailableCount > 0) {
                conflictWarning.style.display = 'block';
                document.getElementById('conflict_message').innerHTML = `
                    <strong>${unavailableCount} member(s have conflicting bookings at this time.</strong><br>
                    They are already assigned during this time slot. Please choose a different group or time.
                `;
                document.getElementById('assignStaffBtn').disabled = true;
                document.getElementById('assignStaffBtn').style.opacity = '0.5';
                document.getElementById('assignStaffBtn').style.cursor = 'not-allowed';
            } else {
                document.getElementById('assignStaffBtn').disabled = false;
                document.getElementById('assignStaffBtn').style.opacity = '1';
                document.getElementById('assignStaffBtn').style.cursor = 'pointer';
            }
        })
        .catch(error => {
            console.error('❌ Error checking availability:', error);
        });
}

// Form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('assignStaffForm');
    if (form) {
        console.log('✅ Form handler attached');
        
        form.onsubmit = function(e) {
            const groupSelect = document.getElementById('group_select');
            
            if (!groupSelect.value) {
                e.preventDefault();
                alert('⚠️ Please select a group!');
                return false;
            }
            
            if (document.getElementById('assignStaffBtn').disabled) {
                e.preventDefault();
                alert('⚠️ Cannot assign this group due to time conflicts!');
                return false;
            }
            
            console.log('✅ Form validation passed, submitting...');
            return true;
        };
    }
});

function openAssignModal(rowData) {
    console.log('🔓 Opening modal for booking:', rowData);
    const modal = document.getElementById('assignModal');
    
    document.getElementById('assign_booking_id').value = rowData.id;
    document.getElementById('assign_client_name').textContent = rowData.full_name;
    document.getElementById('assign_service_type').textContent = rowData.service_type;
    document.getElementById('assign_service_date').textContent = rowData.service_date;
    document.getElementById('assign_service_time').textContent = rowData.service_time;
    document.getElementById('assign_duration').textContent = rowData.duration || 'N/A';
    document.getElementById('assign_address').textContent = rowData.address || 'N/A';
    document.getElementById('assign_duration_hidden').value = rowData.duration || '';
    
    // Check prayer break
    if (rowData.service_time && rowData.duration) {
        const hasPrayerConflict = checkPrayerBreak(rowData.service_time, rowData.duration);
        document.getElementById('prayer_break_warning').style.display = hasPrayerConflict ? 'block' : 'none';
    }
    
    // Clear selection
    document.getElementById('group_select').value = '';
    document.getElementById('selected_group_id').value = '';
    document.getElementById('selected_group_preview').style.display = 'none';
    document.getElementById('group_conflict_warning').style.display = 'none';
    
    modal.classList.add('show');
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.remove('show');
}
</script>


<script>
// Update staff count display
// function updateStaffCount() {
//     const cleanersChecked = document.querySelectorAll('.cleaner-checkbox:checked:not([disabled])').length;
//     const driversChecked = document.querySelectorAll('.driver-checkbox:checked:not([disabled])').length;
    
//     const cleanersCountEl = document.getElementById('cleaners-count');
//     const driversCountEl = document.getElementById('drivers-count');
    
//     // Update cleaners count
//     cleanersCountEl.textContent = `Selected: ${cleanersChecked}`;
//     if (cleanersChecked >= 5) {
//         cleanersCountEl.classList.remove('invalid');
//         cleanersCountEl.style.background = '#d4edda';
//         cleanersCountEl.style.color = '#155724';
//     } else {
//         cleanersCountEl.classList.add('invalid');
//         cleanersCountEl.style.background = '#f8d7da';
//         cleanersCountEl.style.color = '#721c24';
//     }
    
//     // Update drivers count
//     driversCountEl.textContent = `Selected: ${driversChecked}`;
//     if (driversChecked === 1) {
//         driversCountEl.classList.remove('invalid');
//         driversCountEl.style.background = '#d4edda';
//         driversCountEl.style.color = '#155724';
//     } else {
//         driversCountEl.classList.add('invalid');
//         driversCountEl.style.background = '#f8d7da';
//         driversCountEl.style.color = '#721c24';
//     }
// }

// // Enforce only one driver selection
// function enforceOneDriver(checkbox) {
//     if (checkbox.checked) {
//         // Uncheck all other driver checkboxes
//         const allDriverCheckboxes = document.querySelectorAll('.driver-checkbox:not([disabled])');
//         allDriverCheckboxes.forEach(cb => {
//             if (cb !== checkbox) {
//                 cb.checked = false;
//             }
//         });
//     }
//     updateStaffCount();
// }

// // Validate staff assignment before submission
// function validateStaffAssignment(event) {
//     const cleanersChecked = document.querySelectorAll('.cleaner-checkbox:checked:not([disabled])').length;
//     const driversChecked = document.querySelectorAll('.driver-checkbox:checked:not([disabled])').length;
    
//     const cleanersError = document.getElementById('cleaners-error');
//     const driversError = document.getElementById('drivers-error');
    
//     let isValid = true;
    
//     // Validate cleaners (minimum 5)
//     if (cleanersChecked < 5) {
//         cleanersError.style.display = 'flex';
//         isValid = false;
//     } else {
//         cleanersError.style.display = 'none';
//     }
    
//     // Validate drivers (exactly 1)
//     if (driversChecked !== 1) {
//         driversError.style.display = 'flex';
//         isValid = false;
//     } else {
//         driversError.style.display = 'none';
//     }
    
//     if (!isValid) {
//         event.preventDefault();
        
//         // Show alert with specific requirements
//         let errorMessage = 'Please fix the following:\n\n';
//         if (cleanersChecked < 5) {
//             errorMessage += `• You need to select at least 5 cleaners (currently selected: ${cleanersChecked})\n`;
//         }
//         if (driversChecked !== 1) {
//             errorMessage += `• You need to select exactly 1 driver (currently selected: ${driversChecked})\n`;
//         }
        
//         alert(errorMessage);
//         return false;
//     }
    
//     // Confirm before submitting
//     const confirmMessage = `Are you sure you want to assign:\n• ${cleanersChecked} cleaners\n• ${driversChecked} driver\n\nto this booking?`;
//     if (!confirm(confirmMessage)) {
//         event.preventDefault();
//         return false;
//     }
    
//     return true;
// }

// // Initialize count display when modal opens
// document.addEventListener('DOMContentLoaded', function() {
//     // Add event listener to update counts when modal opens
//     const assignModal = document.getElementById('assignModal');
//     if (assignModal) {
//         const observer = new MutationObserver(function(mutations) {
//             mutations.forEach(function(mutation) {
//                 if (mutation.attributeName === 'class') {
//                     if (assignModal.classList.contains('show')) {
//                         updateStaffCount();
//                     }
//                 }
//             });
//         });
//         observer.observe(assignModal, { attributes: true });
//     }
// });
// </script>
// <div id="logoutModal" class="modal">
// <div class="modal__content">
// <h3 class="modal__title">Are you sure you want to log out?</h3>
// <div class="modal__actions">
// <button id="cancelLogout" class="btn btn--secondary">Cancel</button>
// <button id="confirmLogout" class="btn btn--primary">Log Out</button>

// <script>
    
// const navLinks = document.querySelectorAll('.sidebar__menu .menu__link');
// const logoutLink = document.querySelector('.sidebar__menu .menu__link[data-content="logout"]');
// const logoutModal = document.getElementById('logoutModal');
// const cancelLogoutBtn = document.getElementById('cancelLogout');
// const confirmLogoutBtn = document.getElementById('confirmLogout');

// // Handle logout modal
// function showLogoutModal() {
//     if (logoutModal) logoutModal.classList.add('show');
// }

// if (cancelLogoutBtn && logoutModal) {
//     cancelLogoutBtn.addEventListener('click', function() {
//         logoutModal.classList.remove('show');
//     });
// }

// if (confirmLogoutBtn) {
//     confirmLogoutBtn.addEventListener('click', function() {
//         window.location.href = "landing_page2.html";
//     });
// }
// // Open Assign Staff Modal
// function openAssignModal(rowData) {
//     console.log('Opening assign modal with data:', rowData);
//     const modal = document.getElementById('assignModal');
    
//     // Populate booking info
//     document.getElementById('assign_booking_id').value = rowData.id;
//     document.getElementById('assign_client_name').textContent = rowData.full_name;
//     document.getElementById('assign_service_type').textContent = rowData.service_type;
//     document.getElementById('assign_service_date').textContent = rowData.service_date;
//     document.getElementById('assign_service_time').textContent = rowData.service_time;
    
//     // Store date and time for AJAX call
//     document.getElementById('assign_service_date_hidden').value = rowData.service_date;
//     document.getElementById('assign_service_time_hidden').value = rowData.service_time;
    
//     // Fetch unavailable staff via AJAX
//     fetch('get_unavailable_staff.php', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/x-www-form-urlencoded',
//         },
//         body: `service_date=${rowData.service_date}&service_time=${rowData.service_time}&booking_id=${rowData.id}`
//     })
//     .then(response => response.json())
//     .then(unavailableStaff => {
//         console.log('Unavailable staff:', unavailableStaff);
        
//         // Reset all checkboxes first
//         document.querySelectorAll('.checkbox-label').forEach(label => {
//             const checkbox = label.querySelector('input[type="checkbox"]');
//             const badge = label.querySelector('.unavailable-badge');
            
//             checkbox.disabled = false;
//             checkbox.checked = false;
//             label.classList.remove('disabled');
//             badge.style.display = 'none';
//         });
        
//         // Mark unavailable staff
//         unavailableStaff.forEach(name => {
//             document.querySelectorAll('.checkbox-label').forEach(label => {
//                 if (label.dataset.employeeName === name) {
//                     const checkbox = label.querySelector('input[type="checkbox"]');
//                     const badge = label.querySelector('.unavailable-badge');
                    
//                     checkbox.disabled = true;
//                     label.classList.add('disabled');
//                     badge.style.display = 'inline-block';
//                 }
//             });
//         });
        
//         // Pre-select existing cleaners
//         const existingCleaners = rowData.cleaners ? rowData.cleaners.split(',').map(name => name.trim()) : [];
//         document.querySelectorAll('.cleaner-checkbox').forEach(checkbox => {
//             const label = checkbox.closest('.checkbox-label');
//             const employeeName = label.dataset.employeeName;
//             if (existingCleaners.includes(employeeName) && !checkbox.disabled) {
//                 checkbox.checked = true;
//             }
//         });
        
//         // Pre-select existing drivers
//         const existingDrivers = rowData.drivers ? rowData.drivers.split(',').map(name => name.trim()) : [];
//         document.querySelectorAll('.driver-checkbox').forEach(checkbox => {
//             const label = checkbox.closest('.checkbox-label');
//             const employeeName = label.dataset.employeeName;
//             if (existingDrivers.includes(employeeName) && !checkbox.disabled) {
//                 checkbox.checked = true;
//             }
//         });
//     })
  
    
//     modal.classList.add('show');
// }

// // Close Assign Staff Modal
// function closeAssignModal() {
//     document.getElementById('assignModal').classList.remove('show');
// }
// </script>




<!-- ✅ Dropdown Styles -->
<style>
.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 22px;
    color: #333;
    padding: 5px;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: 30px;
    background-color: #fff;
    min-width: 180px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-radius: 8px;
    padding: 10px;
    z-index: 9999; /* ✅ makes it appear on top */
}

.dropdown-content button {
    display: block;
    width: 100%;
    background: none;
    border: none;
    padding: 8px 10px;
    text-align: left;
    font-size: 14px;
    cursor: pointer;
    color: #333;
    border-radius: 6px;
}

.dropdown-content button:hover {
    background-color: #f3f3f3;
}

.dropdown-content i {
    margin-right: 6px;
}
/* ✅ Make sure dropdown always appears on top of everything */
.dropdown {
  position: relative;
  z-index: 10000;
}

/* ✅ Allow dropdown content to "escape" table overflow */
.dashboard__content,
.content-container,
table,
td,
tr {
  overflow: visible !important;
}

/* ✅ Optional: make it easier to click */
.dropdown-content {
  position: absolute;
  top: 100%;
  right: 0;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  padding: 8px;
  min-width: 180px;
  z-index: 99999; /* ensures it's above modals and tables */
}

/* ✅ (Optional) subtle animation */
.dropdown-content {
  opacity: 0;
  transform: translateY(5px);
  transition: all 0.2s ease;
}

.dropdown-content.show {
  opacity: 1;
  transform: translateY(0);
  display: block;
}
/* ✅ Improved Dropdown Style */
.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 22px;
    color: #333;
    padding: 5px;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: 35px;
    background-color: #fff;
    min-width: 200px;
    max-height: 320px; /* ✅ scrollable if too tall */
    overflow-y: auto;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    padding: 10px;
    z-index: 99999;
    animation: dropdownFade 0.15s ease-in-out;
}

/* ✅ Smooth open animation */
@keyframes dropdownFade {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ✅ Buttons inside dropdown */
.dropdown-content button {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    padding: 10px;
    text-align: left;
    font-size: 14px;
    cursor: pointer;
    color: #333;
    border-radius: 8px;
    margin-bottom: 6px;
    transition: all 0.2s ease-in-out;
}

.dropdown-content button:hover {
    background-color: #007bff;
    color: #fff;
    transform: translateX(3px);
}

.dropdown-content i {
    font-size: 16px;
}

/* ✅ "Update Status" select + button styling */
.dropdown-content select,
.dropdown-content button[type="submit"] {
    width: 100%;
    border-radius: 8px;
    margin-top: 6px;
}

.dropdown-content select {
    border: 1px solid #ccc;
    padding: 8px;
    font-size: 14px;
    background: #fff;
}

.dropdown-content button[type="submit"] {
    background: #007bff;
    color: white;
    border: none;
    padding: 8px 10px;
    font-size: 14px;
    cursor: pointer;
}

.dropdown-content button[type="submit"]:hover {
    background: #0056b3;
}

</style>



                </table>
            </div>
        </section>
    </main>
</div>

<!-- VIEW DETAIL MODAL -->
<div id="detailModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('detailModal')">&times;</span>
        <h3><i class='bx bx-file-text'></i> Appointment Details</h3>
        <div id="modal-details-content">
            <!-- Content will be injected by JavaScript -->
        </div>
    </div>
</div>

<!-- EDIT BOOKING MODAL -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
        <h3><i class='bx bx-edit'></i> Edit Booking Details</h3>
        <form method="POST" action="">
            <input type="hidden" name="booking_id" id="edit_booking_id">
            
            <div class="form-group">
                <label>Service Type</label>
                <select name="service_type" id="edit_service_type" required>
                    <option value="Refresh Cleaning">Refresh Cleaning</option>
                    <option value="Deep Cleaning">Deep Cleaning</option>
                    <option value="In-house Cleaning">In-house Cleaning</option>
                    <option value="Checkout Cleaning">Checkout Cleaning</option>
                </select>
            </div>

            <div class="form-group">
                <label>Service Date</label>
                <input type="date" name="service_date" id="edit_service_date" required>
            </div>

            <div class="form-group">
                <label>Service Time</label>
                <input type="time" name="service_time" id="edit_service_time" required>
            </div>

            <div class="form-group">
                <label>Duration (hours)</label>
                <input type="text" name="duration" id="edit_duration" required>
            </div>

            <div class="form-group">
                <label>Property Type</label>
                <select name="property_type" id="edit_property_type" required>
                    <option value="Studio">Studio</option>
                    <option value="1 Bedroom">1 Bedroom</option>
                    <option value="2 Bedrooms">2 Bedrooms</option>
                    <option value="3 Bedrooms">3 Bedrooms</option>
                    <option value="4+ Bedrooms">4+ Bedrooms</option>
                    <option value="Villa">Villa</option>
                    <option value="Office">Office</option>
                </select>
            </div>

            <div class="form-group">
                <label>Materials Provided</label>
                <input type="text" name="materials_provided" id="edit_materials_provided">
            </div>

            <div class="form-group">
                <label>Address</label>
                <textarea name="address" id="edit_address" required></textarea>
            </div>

            <div class="form-group">
                <label>Comments</label>
                <textarea name="comments" id="edit_comments"></textarea>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" name="update_booking" class="btn btn-view">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- RESCHEDULE MODAL -->
<div id="rescheduleModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('rescheduleModal')">&times;</span>
        <h3><i class='bx bx-calendar-edit'></i> Reschedule Appointment</h3>
        <form method="POST" action="">
            <input type="hidden" name="booking_id" id="reschedule_booking_id">
            
            <div class="form-group">
                <label>New Service Date</label>
                <input type="date" name="service_date" id="reschedule_service_date" required>
            </div>

            <div class="form-group">
                <label>New Service Time</label>
                <input type="time" name="service_time" id="reschedule_service_time" required>
            </div>

            <div class="form-group">
                <label>Reason for Rescheduling</label>
                <textarea name="comments" id="reschedule_comments" rows="3"></textarea>
            </div>

            <input type="hidden" name="service_type" id="reschedule_service_type">
            <input type="hidden" name="duration" id="reschedule_duration">
            <input type="hidden" name="property_type" id="reschedule_property_type">
            <input type="hidden" name="materials_provided" id="reschedule_materials_provided">
            <input type="hidden" name="address" id="reschedule_address">

            <div class="form-actions">
                <button type="button" class="btn btn-cancel" onclick="closeModal('rescheduleModal')">Cancel</button>
                <button type="submit" name="update_booking" class="btn btn-view">Reschedule</button>
            </div>
        </form>
    </div>
</div>

<!-- COMPLETION REPORT MODAL -->
<div id="completionReportModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('completionReportModal')">&times;</span>
        <h3><i class='bx bx-file'></i> Completion Report</h3>
        <div id="completion-report-content">
            <!-- Content will be injected by JavaScript -->
        </div>
    </div>
</div>

<!-- INVOICE MODAL -->
<div id="invoiceModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('invoiceModal')">&times;</span>
        <h3><i class='bx bx-receipt'></i> Invoice - Final Edit</h3>
        <div id="invoice-content">
            <!-- Content will be injected by JavaScript -->
        </div>
    </div>
</div>

<!-- IMAGE LIGHTBOX -->
<div id="lightbox" class="lightbox" onclick="closeLightbox()">
    <span class="lightbox-close">×</span>
    <img id="lightboxImage" src="" alt="Full view">
</div>

<script>
// Sidebar Dropdown Toggle
(function(){
  const nav = document.querySelector('.sidebar__menu');
  if (!nav) return;
  nav.querySelectorAll('.has-dropdown > .menu__link').forEach(link => {
      link.addEventListener('click', e => {
          e.preventDefault();
          link.parentElement.classList.toggle('open');
      });
  });
  
})();

// View Details Modal
function openModal(data) {
    const modal = document.getElementById('detailModal');
    const modalBody = document.getElementById('modal-details-content');
    
    const refNo = 'ALZ-' + String(data.id).padStart(6, '0');
    
    const formattedDate = data.service_date ? new Date(data.service_date).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'long', day: 'numeric' 
    }) : 'N/A';
    
    const formattedTime = data.service_time || 'N/A';
    const statusClass = (data.status || 'pending').toLowerCase().replace(' ', '-');
    
    let staffHTML = '';
    if (data.driver_name || data.cleaners_names) {
        staffHTML = `
            <div class="staff-details-container">
                <h4><i class='bx bx-id-card'></i> Assigned Team</h4>
                ${data.driver_name ? `<p><i class='bx bx-car'></i> <strong>Driver:</strong> ${data.driver_name}</p>` : ''}
                ${data.cleaners_names ? `<p><i class='bx bx-group'></i> <strong>Cleaners:</strong> ${data.cleaners_names}</p>` : ''}
            </div>
        `;
    }

    let imagesHTML = '';
    const images = [data.media1, data.media2, data.media3].filter(img => img && img.trim() !== '');
    
    if (images.length > 0) {
        imagesHTML = `
            <div class="images-section">
                <h4><i class='bx bx-image'></i> Uploaded Images</h4>
                <div class="images-grid">
                    ${images.map(img => `
                        <div class="image-item" onclick="openLightbox('${img}', event)">
                            <img src="${img}" alt="Service Image" onerror="this.src='https://via.placeholder.com/180?text=No+Image'">
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    } else {
        imagesHTML = `
            <div class="images-section">
                <h4><i class='bx bx-image'></i> Uploaded Images</h4>
                <p class="no-images">No images uploaded for this booking</p>
            </div>
        `;
    }

    let rate = 0;
    if (data.materials_provided) {
        const match = data.materials_provided.match(/(\d+(\.\d+)?)/);
        if (match) {
            rate = parseFloat(match[1]);
        }
    }

    const duration = parseFloat(data.duration) || 0;
    const total = rate * duration;
    const totalFormatted = total.toFixed(2) + ' AED';

    modalBody.innerHTML = `
        <p class="full-width-detail ref-no-detail">
            <strong>Reference No:</strong>
            <span class="ref-no-value">${refNo}</span>
        </p>
        
        <p class="full-width-detail">
            <strong><i class='bx bx-calendar-check'></i> Service Date:</strong>
            <span>${formattedDate}</span>
        </p>
        
        <p>
            <strong><i class='bx bx-time'></i> Service Time:</strong>
            <span>${formattedTime}</span>
        </p>
        
        <p>
            <strong><i class='bx bx-stopwatch'></i> Duration:</strong>
            <span>${data.duration || 'N/A'} hours</span>
        </p>
        
        <p>
            <strong><i class='bx bx-user'></i> Full Name:</strong>
            <span>${data.full_name || 'N/A'}</span>
        </p>
        
        <p>
            <strong><i class='bx bx-envelope'></i> Email:</strong>
            <span>${data.email || 'N/A'}</span>
        </p>
        
        <p>
            <strong><i class='bx bx-phone'></i> Phone:</strong>
            <span>${data.phone || 'N/A'}</span>
        </p>
        
        <p>
            <strong><i class='bx bx-building-house'></i> Client Type:</strong>
            <span>${data.client_type || 'N/A'}</span>
        </p>
        
        <p class="full-width-detail">
            <strong><i class='bx bx-map-alt'></i> Address:</strong>
            <span>${data.address || 'N/A'}</span>
        </p>
        
        <hr class="divider full-width-detail">
        
        <p>
            <strong><i class='bx bx-wrench'></i> Service Type:</strong>
            <span>${data.service_type || 'N/A'}</span>
        </p>
        
        <p>
            <strong><i class='bx bx-home'></i> Property Type:</strong>
            <span>${data.property_type || 'N/A'}</span>
        </p>
        
        <p>
            <strong><i class='bx bx-package'></i> Materials Provided:</strong>
            <span>${data.materials_provided || 'N/A'}</span>
        </p>

        <p>
            <strong><i class='bx bx-calculator'></i> Estimated Total:</strong>
            <span style="font-weight:bold; color:#28a745;">${totalFormatted}</span>
        </p>
        
        <p class="full-width-detail">
            <strong><i class='bx bx-comment-detail'></i> Comments:</strong>
            <span>${data.comments || 'No comments'}</span>
        </p>
        
        <p class="full-width-detail status-detail">
            <strong><i class='bx bx-info-circle'></i> Status:</strong>
            <span class="status-tag ${statusClass}">${data.status || 'Pending'}</span>
        </p>
        
        <p>
            <strong><i class='bx bx-calendar-plus'></i> Created At:</strong>
            <span>${data.created_at || 'N/A'}</span>
        </p>
        
        ${data.rating_stars ? `
        <p>
            <strong><i class='bx bx-star'></i> Rating:</strong>
            <span>${'⭐'.repeat(data.rating_stars)}</span>
        </p>` : ''}
        
        ${data.rating_comment ? `
        <p class="full-width-detail">
            <strong><i class='bx bx-message-detail'></i> Rating Comment:</strong>
            <span>${data.rating_comment}</span>
        </p>` : ''}
        
        ${data.issue_type ? `
        <p class="full-width-detail">
            <strong><i class='bx bx-error-alt'></i> Issue Type:</strong>
            <span>${data.issue_type}</span>
        </p>` : ''}
        
        ${staffHTML}
        ${imagesHTML}
    `;
    
    modal.classList.add('show');
}
function toggleDropdown(btn) {
    // Close other dropdowns
    document.querySelectorAll('.dropdown-content.show').forEach(el => {
        if (el !== btn.nextElementSibling) el.classList.remove('show');
    });

    // Toggle current dropdown
    const content = btn.nextElementSibling;
    content.classList.toggle('show');
}

// ✅ Close dropdown when clicking outside
window.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-content').forEach(el => el.classList.remove('show'));
    }
});

// ✅ Close dropdown when clicking any button inside
document.addEventListener('click', function(e) {
    if (e.target.closest('.dropdown-content button')) {
        const dropdown = e.target.closest('.dropdown-content');
        dropdown.classList.remove('show');
    }
});



// Edit Booking Modal
function openEditModal(data) {
    const modal = document.getElementById('editModal');
    
    document.getElementById('edit_booking_id').value = data.id;
    document.getElementById('edit_service_type').value = data.service_type || '';
    document.getElementById('edit_service_date').value = data.service_date || '';
    document.getElementById('edit_service_time').value = data.service_time || '';
    document.getElementById('edit_duration').value = data.duration || '';
    document.getElementById('edit_property_type').value = data.property_type || '';
    document.getElementById('edit_materials_provided').value = data.materials_provided || '';
    document.getElementById('edit_address').value = data.address || '';
    document.getElementById('edit_comments').value = data.comments || '';
    
    modal.classList.add('show');
}

// Reschedule Modal
function openRescheduleModal(data) {
    const modal = document.getElementById('rescheduleModal');
    
    document.getElementById('reschedule_booking_id').value = data.id;
    document.getElementById('reschedule_service_date').value = data.service_date || '';
    document.getElementById('reschedule_service_time').value = data.service_time || '';
    document.getElementById('reschedule_service_type').value = data.service_type || '';
    document.getElementById('reschedule_duration').value = data.duration || '';
    document.getElementById('reschedule_property_type').value = data.property_type || '';
    document.getElementById('reschedule_materials_provided').value = data.materials_provided || '';
    document.getElementById('reschedule_address').value = data.address || '';
    
    modal.classList.add('show');
}

// Completion Report Modal
function openCompletionReport(data) {
    const modal = document.getElementById('completionReportModal');
    const content = document.getElementById('completion-report-content');
    
    const refNo = 'ALZ-' + String(data.id).padStart(6, '0');
    
    content.innerHTML = `
        <div style="padding: 20px;">
            <h4 style="color: #28a745; margin-bottom: 20px;"><i class='bx bx-check-circle'></i> Service Completed Successfully</h4>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <p><strong>Reference Number:</strong> ${refNo}</p>
                <p><strong>Client Name:</strong> ${data.full_name || 'N/A'}</p>
                <p><strong>Service Date:</strong> ${data.service_date || 'N/A'}</p>
                <p><strong>Service Type:</strong> ${data.service_type || 'N/A'}</p>
                <p><strong>Duration:</strong> ${data.duration || 'N/A'} hours</p>
            </div>

            ${data.rating_stars ? `
            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <p><strong>Client Rating:</strong> ${'⭐'.repeat(data.rating_stars)}</p>
                ${data.rating_comment ? `<p><strong>Feedback:</strong> ${data.rating_comment}</p>` : ''}
            </div>` : '<p style="color: #999; font-style: italic;">Client has not provided feedback yet.</p>'}

            <div style="margin-top: 20px;">
                <button class="btn btn-view" onclick="window.print()">
                    <i class='bx bx-printer'></i> Print Report
                </button>
                <button class="btn btn-cancel" onclick="closeModal('completionReportModal')">Close</button>
            </div>
        </div>
    `;
    
    modal.classList.add('show');
}

// Invoice Modal
function openInvoiceModal(data) {
    const modal = document.getElementById('invoiceModal');
    const content = document.getElementById('invoice-content');
    
    const refNo = 'ALZ-' + String(data.id).padStart(6, '0');
    
    let rate = 0;
    if (data.materials_provided) {
        const match = data.materials_provided.match(/(\d+(\.\d+)?)/);
        if (match) {
            rate = parseFloat(match[1]);
        }
    }

    const duration = parseFloat(data.duration) || 0;
    const subtotal = rate * duration;
    const vat = subtotal * 0.05; // 5% VAT
    const total = subtotal + vat;
    
    content.innerHTML = `
        <div style="padding: 20px; max-width: 800px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: #007bff; margin-bottom: 5px;">ALAZIMA CLEANING SERVICES</h2>
                <p style="color: #666;">Invoice</p>
                <p style="font-weight: bold; font-size: 1.2em;">Reference: ${refNo}</p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <div>
                    <h4 style="color: #333; margin-bottom: 10px;">Bill To:</h4>
                    <p><strong>${data.full_name || 'N/A'}</strong></p>
                    <p>${data.email || 'N/A'}</p>
                    <p>${data.phone || 'N/A'}</p>
                    <p>${data.address || 'N/A'}</p>
                </div>
                <div>
                    <h4 style="color: #333; margin-bottom: 10px;">Service Details:</h4>
                    <p><strong>Date:</strong> ${data.service_date || 'N/A'}</p>
                    <p><strong>Time:</strong> ${data.service_time || 'N/A'}</p>
                    <p><strong>Service:</strong> ${data.service_type || 'N/A'}</p>
                    <p><strong>Property:</strong> ${data.property_type || 'N/A'}</p>
                </div>
            </div>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="background: #007bff; color: white;">
                        <th style="padding: 12px; text-align: left;">Description</th>
                        <th style="padding: 12px; text-align: center;">Duration</th>
                        <th style="padding: 12px; text-align: right;">Rate</th>
                        <th style="padding: 12px; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 12px;">${data.service_type || 'N/A'}</td>
                        <td style="padding: 12px; text-align: center;" contenteditable="true" id="invoice_duration">${duration}</td>
                        <td style="padding: 12px; text-align: right;" contenteditable="true" id="invoice_rate">${rate.toFixed(2)}</td>
                        <td style="padding: 12px; text-align: right;" id="invoice_amount">${subtotal.toFixed(2)} AED</td>
                    </tr>
                </tbody>
            </table>

            <div style="text-align: right; margin-bottom: 20px;">
                <p><strong>Subtotal:</strong> <span id="invoice_subtotal">${subtotal.toFixed(2)} AED</span></p>
                <p><strong>VAT (5%):</strong> <span id="invoice_vat">${vat.toFixed(2)} AED</span></p>
                <p style="font-size: 1.3em; color: #28a745;"><strong>Total:</strong> <span id="invoice_total">${total.toFixed(2)} AED</span></p>
            </div>

            <div style="margin-top: 30px;">
                <button class="btn btn-view" onclick="updateInvoiceCalculation()">
                    <i class='bx bx-calculator'></i> Recalculate
                </button>
                <button class="btn btn-view" onclick="window.print()">
                    <i class='bx bx-printer'></i> Print Invoice
                </button>
                <button class="btn btn-cancel" onclick="closeModal('invoiceModal')">Close</button>
            </div>
        </div>
    `;
    
    modal.classList.add('show');
}

// Update Invoice Calculation
function updateInvoiceCalculation() {
    const duration = parseFloat(document.getElementById('invoice_duration').innerText) || 0;
    const rate = parseFloat(document.getElementById('invoice_rate').innerText) || 0;
    
    const subtotal = duration * rate;
    const vat = subtotal * 0.05;
    const total = subtotal + vat;
    
    document.getElementById('invoice_amount').innerText = subtotal.toFixed(2) + ' AED';
    document.getElementById('invoice_subtotal').innerText = subtotal.toFixed(2) + ' AED';
    document.getElementById('invoice_vat').innerText = vat.toFixed(2) + ' AED';
    document.getElementById('invoice_total').innerText = total.toFixed(2) + ' AED';
}

// Call Client Function
function callClient(phone) {
    if (phone && phone.trim() !== '') {
        window.location.href = 'tel:' + phone;
    } else {
        alert('Phone number not available');
    }
}

// Confirm Cancel
function confirmCancel(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const input1 = document.createElement('input');
        input1.type = 'hidden';
        input1.name = 'booking_id';
        input1.value = bookingId;
        
        const input2 = document.createElement('input');
        input2.type = 'hidden';
        input2.name = 'cancel_booking';
        input2.value = '1';
        
        form.appendChild(input1);
        form.appendChild(input2);
        document.body.appendChild(form);
        form.submit();
    }
}

// Close Modal
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

// Open Image Lightbox
function openLightbox(imageSrc, event) {
    if (event) event.stopPropagation();
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    lightboxImage.src = imageSrc;
    lightbox.classList.add('show');
}

// Close Image Lightbox
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('show');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = ['detailModal', 'editModal', 'rescheduleModal', 'completionReportModal', 'invoiceModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            closeModal(modalId);
        }
    });
}
</script>

</body>
</html>

<?php $conn->close(); ?>