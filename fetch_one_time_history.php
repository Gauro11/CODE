<?php
require 'connection.php';

$serviceTypes = [
    'Checkout Cleaning' => [],
    'In-House Cleaning' => [],
    'Refresh Cleaning' => [],
    'Deep Cleaning' => []
];

// Fetch One-Time bookings
$sql = "SELECT * FROM bookings WHERE booking_type='One-Time' ORDER BY service_date DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Assign all to 'Checkout Cleaning' as default (you can change logic later)
        $type = 'Checkout Cleaning';

        $serviceTypes[$type][] = [
            'booking_id' => $row['id'],
            'reference_no' => $row['id'], // No reference_no in your table, use id as fallback
            'booking_date' => $row['service_date'],
            'booking_time' => $row['service_time'],
            'duration' => $row['duration'],
            'address' => $row['address'],
            'client_type' => $row['client_type'],
            'status' => 'PENDING', // You don't have status in table, set default
            'service_type' => $row['booking_type'],
            'property_layout' => $row['property_type'],
            'materials_required' => $row['materials_needed'],
            'materials_description' => $row['comments'],
            'additional_request' => '', // No column for this
            'image_1' => $row['media1'],
            'image_2' => $row['media2'],
            'image_3' => $row['media3'],
            'estimated_price' => 0, // No column
            'final_price' => 0,     // No column
            'driver_name' => '',    // No column
            'cleaners_names' => ''  // No column
        ];
    }
}

$conn->close();
?>
