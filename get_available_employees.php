<?php
include 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_available_employees'])) {
    $excludeGroupId = isset($_POST['exclude_group_id']) ? intval($_POST['exclude_group_id']) : null;
    
    // Get available cleaners
    if ($excludeGroupId) {
        $stmt = $conn->prepare("
            SELECT DISTINCT e.id, e.first_name, e.last_name 
            FROM employees e
            WHERE e.position = 'Cleaner' 
            AND e.archived = 0
            AND (
                e.id NOT IN (
                    SELECT cleaner1_id FROM employee_groups WHERE cleaner1_id IS NOT NULL AND id != ?
                    UNION SELECT cleaner2_id FROM employee_groups WHERE cleaner2_id IS NOT NULL AND id != ?
                    UNION SELECT cleaner3_id FROM employee_groups WHERE cleaner3_id IS NOT NULL AND id != ?
                    UNION SELECT cleaner4_id FROM employee_groups WHERE cleaner4_id IS NOT NULL AND id != ?
                    UNION SELECT cleaner5_id FROM employee_groups WHERE cleaner5_id IS NOT NULL AND id != ?
                )
            )
            ORDER BY e.first_name, e.last_name
        ");
        $stmt->bind_param("iiiii", $excludeGroupId, $excludeGroupId, $excludeGroupId, $excludeGroupId, $excludeGroupId);
    } else {
        $stmt = $conn->prepare("
            SELECT DISTINCT e.id, e.first_name, e.last_name 
            FROM employees e
            WHERE e.position = 'Cleaner' 
            AND e.archived = 0
            AND e.id NOT IN (
                SELECT cleaner1_id FROM employee_groups WHERE cleaner1_id IS NOT NULL
                UNION SELECT cleaner2_id FROM employee_groups WHERE cleaner2_id IS NOT NULL
                UNION SELECT cleaner3_id FROM employee_groups WHERE cleaner3_id IS NOT NULL
                UNION SELECT cleaner4_id FROM employee_groups WHERE cleaner4_id IS NOT NULL
                UNION SELECT cleaner5_id FROM employee_groups WHERE cleaner5_id IS NOT NULL
            )
            ORDER BY e.first_name, e.last_name
        ");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $cleaners = [];
    while ($row = $result->fetch_assoc()) {
        $cleaners[] = $row;
    }
    $stmt->close();
    
    // Get available drivers
    if ($excludeGroupId) {
        $stmt = $conn->prepare("
            SELECT DISTINCT e.id, e.first_name, e.last_name 
            FROM employees e
            WHERE e.position = 'Driver' 
            AND e.archived = 0
            AND (
                e.id NOT IN (
                    SELECT driver_id FROM employee_groups WHERE driver_id IS NOT NULL AND id != ?
                )
            )
            ORDER BY e.first_name, e.last_name
        ");
        $stmt->bind_param("i", $excludeGroupId);
    } else {
        $stmt = $conn->prepare("
            SELECT DISTINCT e.id, e.first_name, e.last_name 
            FROM employees e
            WHERE e.position = 'Driver' 
            AND e.archived = 0
            AND e.id NOT IN (
                SELECT driver_id FROM employee_groups WHERE driver_id IS NOT NULL
            )
            ORDER BY e.first_name, e.last_name
        ");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $drivers = [];
    while ($row = $result->fetch_assoc()) {
        $drivers[] = $row;
    }
    $stmt->close();
    
    echo json_encode([
        'cleaners' => $cleaners,
        'drivers' => $drivers
    ]);
    
    $conn->close();
    exit;
}
?>