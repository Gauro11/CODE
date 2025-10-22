<?php
// HIS_sessions.php

// 1. Kuhanin ang Reference Number (Ref No.) mula sa URL parameter
$plan_reference_no = $_GET['ref'] ?? 'N/A'; 

// **************************************************************************
// * PALITAN ANG LAHAT NG DUMMY DATA ARRAYS NG IYONG TOTOONG DATABASE QUERIES *
// **************************************************************************

// --- DUMMY DATA: PLAN DETAILS (KUHIN ITO MULA SA IYONG PLANS TABLE) ---
$plan_details_all = [
    "ALZ-WK-2410-0016" => [
        'start_date' => 'October 05, 2024',
        'end_date' => 'N/A (Active)',
        'time' => '9:00 AM',
        'duration' => '4 hours', // GAGAMITIN ITO PARA SA SESSION TABLE
        'frequency' => 'Weekly', // Frequency value
        'no_of_sessions' => '4 of 12', // BAGONG DUMMY DATA PARA SA NO. OF SESSIONS
        'address' => '707 Downtown, Dubai',
        'client_type' => 'Residential',
        'service_type' => 'General Cleaning',
        'status' => 'ACTIVE', 
        'price' => 400,
        // ⭐ BAGONG DUMMY DATA ⭐
        'property_layout' => '2 Bedroom Apartment',
        'cleaning_materials_required' => 'Yes',
        'materials_needed' => 'Specific multi-purpose cleaner and mop',
        'additional_request' => 'Please focus on the kitchen cabinets.',
        'attachment_count' => 3, 
        // ⭐ BAGONG ATTACHMENTS LISTAHAN ⭐
        'attachments_list' => [
            ['name' => 'LivingRoom_Before.jpg', 'type' => 'Image', 'url' => 'assets/files/ALZ-WK-2410-0016/LivingRoom_Before.jpg'], // Added dummy URL
            ['name' => 'Client_Request_Video.mp4', 'type' => 'Video', 'url' => 'assets/files/ALZ-WK-2410-0016/Client_Request_Video.mp4'],
            ['name' => 'Kitchen_Instructions.jpg', 'type' => 'Image', 'url' => 'assets/files/ALZ-WK-2410-0016/Kitchen_Instructions.jpg'],
        ],
    ],
    "ALZ-BWK-2409-0012" => [
        'start_date' => 'September 20, 24',
        'end_date' => 'October 15, 2024',
        'time' => '2:00 PM',
        'duration' => '3 hours', // GAGAMITIN ITO PARA SA SESSION TABLE
        'frequency' => 'Bi-Weekly', // Frequency value
        'no_of_sessions' => '2 of 2', // BAGONG DUMMY DATA PARA SA NO. OF SESSIONS
        'address' => '606 JLT, Dubai',
        'client_type' => 'Residential',
        'service_type' => 'General Cleaning',
        'status' => 'COMPLETED', 
        'price' => 300,
        // ⭐ BAGONG DUMMY DATA ⭐
        'property_layout' => 'Studio',
        'cleaning_materials_required' => 'No',
        'materials_needed' => 'N/A',
        'additional_request' => 'N/A',
        'attachment_count' => 0, 
        'attachments_list' => [],
    ],
    "ALZ-MTH-2411-0021" => [
        'start_date' => 'November 01, 2024',
        'end_date' => 'N/A (Pending)',
        'time' => '3:00 PM',
        'duration' => '3 hours', // GAGAMITIN ITO PARA SA SESSION TABLE
        'frequency' => 'Monthly', // Frequency value
        'no_of_sessions' => '0 of 6', // BAGONG DUMMY DATA PARA SA NO. OF SESSIONS
        'address' => '505 JVC, Dubai',
        'client_type' => 'Residential',
        'service_type' => 'General Cleaning',
        'status' => 'PENDING', 
        'price' => 300,
        // ⭐ BAGONG DUMMY DATA ⭐
        'property_layout' => 'Villa (3 Bed)',
        'cleaning_materials_required' => 'Yes',
        'materials_needed' => 'Standard cleaning set.',
        'additional_request' => 'First time client, please be extra careful with decorations.',
        'attachment_count' => 1, 
        'attachments_list' => [
            ['name' => 'Floor_Plan_Sketch.pdf', 'type' => 'Document', 'url' => 'assets/files/ALZ-MTH-2411-0021/Floor_Plan_Sketch.pdf'],
        ],
    ],
];

// Kumuha ng plan details at i-default ang attachment count
$plan_details = $plan_details_all[$plan_reference_no] ?? [];
$plan_duration = $plan_details['duration'] ?? 'N/A';
$attachment_count = $plan_details['attachment_count'] ?? 0; // Kinuha ang count
$attachments_list = $plan_details['attachments_list'] ?? []; // Kinuha ang listahan
// ... (rest of PHP logic remains the same)

// Logic para sa Plan Status Tag
$status_class_map = [
    'ACTIVE' => 'overall-active',
    'PAUSED' => 'overall-paused',
    'TERMINATED' => 'overall-terminated',
    'COMPLETED' => 'overall-completed',
    'PENDING' => 'overall-pending',
];
$plan_status_tag_class = $status_class_map[$plan_details['status'] ?? 'N/A'] ?? 'overall-pending';

// Conditional visibility ng buttons
$is_active_or_pending = in_array($plan_details['status'], ['ACTIVE', 'PENDING']);
$is_active = $plan_details['status'] === 'ACTIVE';

// ⭐⭐⭐ CODE PARA SA WHATSAPP LINK NA MAY REFERENCE NO. ⭐⭐⭐
$whatsapp_number = '971529009188'; 
$initial_message = "Hi, I am inquiring about my Recurring Plan with Reference Number: " . $plan_reference_no . ".";
$encoded_message = urlencode($initial_message);
$whatsapp_link = "https://wa.me/{$whatsapp_number}?text={$encoded_message}";
// ⭐⭐⭐ END NG WHATSAPP CODE ⭐⭐⭐

// --- DUMMY DATA: SESSIONS LIST (Hindi binago) ---
$all_sessions_data = [
    "ALZ-WK-2410-0016" => [
        // Dito ko idadagdag ang Staff details para maging dynamic
        // Nagdagdag ng 'actual_duration' para sa computation: ginamit ang Plan Duration muna.
        ['id' => 101, 'session_no' => 1, 'date' => '2024-10-05', 'time' => '9:00 AM', 'status' => 'COMPLETED', 'price' => 400, 'staffs' => 'Maria A., Jessa P.', 'actual_duration' => '4 hours'],
        ['id' => 102, 'session_no' => 2, 'date' => '2024-10-12', 'time' => '9:00 AM', 'status' => 'RESCHEDULED', 'price' => 400, 'staffs' => 'TBD', 'actual_duration' => '4 hours'],
        ['id' => 103, 'session_no' => 3, 'date' => '2024-10-19', 'time' => '9:00 AM', 'status' => 'UPCOMING', 'price' => 400, 'staffs' => 'TBD', 'actual_duration' => '4 hours'],
    ],
    "ALZ-BWK-2409-0012" => [
        ['id' => 201, 'session_no' => 1, 'date' => '2024-09-20', 'time' => '2:00 PM', 'status' => 'COMPLETED', 'price' => 300, 'staffs' => 'Jocelyn M.', 'actual_duration' => '3 hours'],
        ['id' => 202, 'session_no' => 2, 'date' => '2024-10-04', 'time' => '2:00 PM', 'status' => 'COMPLETED', 'price' => 300, 'staffs' => 'Jocelyn M.', 'actual_duration' => '3 hours'],
    ],
];
$sessions = $all_sessions_data[$plan_reference_no] ?? [];

// -------------------------------------------------------------
// ⭐ NEW PHP FUNCTION TO CALCULATE END TIME ⭐
// -------------------------------------------------------------
/**
 * Calculates the end time based on the start time and duration string.
 * This is DUMMY logic and should be replaced with actual DB data if possible.
 *
 * @param string $startTime Example: '9:00 AM'
 * @param string $duration Example: '4 hours'
 * @return string End time Example: '1:00 PM' or 'N/A'
 */
function calculateEndTime($startTime, $duration) {
    if (empty($startTime) || empty($duration)) {
        return 'N/A';
    }

    // Extract numerical value and unit (e.g., 4 and hours)
    if (preg_match('/(\d+)\s*(hour|minute)s?/', strtolower($duration), $matches)) {
        $value = (int)$matches[1];
        $unit = $matches[2];
        
        // Create DateTime object from start time
        $datetime = DateTime::createFromFormat('g:i A', $startTime);
        
        if ($datetime) {
            // Modify the time
            $interval = new DateInterval("PT{$value}H"); // Default to hours
            if ($unit === 'minute') {
                $interval = new DateInterval("PT{$value}M");
            }
            
            $datetime->add($interval);
            
            // Format to 12-hour time with AM/PM
            return $datetime->format('g:i A');
        }
    }
    
    return 'N/A'; // Fallback if parsing fails
}
// -------------------------------------------------------------

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions for Plan: <?php echo htmlspecialchars($plan_reference_no); ?></title>
    <link rel="icon" href="site_icon.png" type="image/png">
    <link rel="stylesheet" href="client_db.css">
    <link rel="stylesheet" href="HIS_design.css"> 
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        /* ADDED STYLE PARA SA CLICKABLE ATTACHMENT LINKS SA LOOB NG detail-text-box */
        .attachments-container-link a {
            color: #2196F3;
            font-weight: 500;
            text-decoration: none;
            margin-right: 10px; /* Space between links */
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 0;
            white-space: nowrap;
        }

        .attachments-container-link a:hover {
            text-decoration: underline;
        }
        /* End of New Attachment Link Style */
        
        /* ... (Rest of existing CSS styles) */
        /* ================================================== */
        /* Plan Details Styling */
        /* ================================================== */
        .history-header-container, .sessions-container { 
            padding: 20px; 
            margin-bottom: 30px; 
            background-color: #fff; 
            border-radius: 8px; 
            border: 1px solid #e0e0e0; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
        }
        
        /* ⭐ SESSIONS CONTAINER SPECIFIC STYLES ⭐ */
        .sessions-container {
            padding: 0; 
        }
        
        .header-details-row {
            display: flex;
            justify-content: space-between; 
            align-items: flex-start; 
            margin-bottom: 15px;
            border-bottom: 1px solid #eee; 
            padding-bottom: 15px;
        }
        
        .header-right-wrapper {
            display: flex;
            align-items: flex-start; 
            gap: 30px; 
        }
        
        .header-status-price {
            display: flex;
            gap: 40px; 
            align-items: flex-start;
        }
        
        .header-status-price .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .header-status-price strong {
            display: block;
            font-size: 0.95em; 
            color: #777;
            margin-bottom: 2px;
            font-weight: 500;
        }
        .header-status-price span {
            display: block;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 1.0em; 
        }

        .plan-details-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px 20px; 
            margin-bottom: 5px; 
        }

        .additional-details-grid {
            display: flex; 
            flex-wrap: wrap;
            gap: 15px 20px; 
            padding-top: 20px;
            margin-bottom: 10px;
            opacity: 1;
            max-height: 1000px; 
            overflow: hidden;
            transition: max-height 0.4s ease-out, opacity 0.4s ease-out;
        }

        .additional-details-grid.hidden {
            opacity: 0;
            max-height: 0;
            padding-top: 0;
            margin-bottom: 0;
        }
        
        .show-more-toggle-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 10px 0 20px;
        }
        .show-more-toggle-container hr {
            width: 100%;
            border: 0;
            height: 1px;
            background-color: #ddd;
            margin: 0 0 10px;
        }
        
        .show-more-btn {
            background: none;
            border: 1px solid #2196F3; 
            color: #2196F3; 
            padding: 8px 15px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .show-more-btn:hover {
            background-color: #e3f2fd; 
            border-color: #64b5f6;
        }
        
        /* General Grid Item Styling */
        .plan-details-grid div, .additional-details-grid div {
            flex: 1 1 calc(33.33% - 20px); 
            max-width: calc(33.33% - 20px);
            font-size: 0.95em;
        }
        .plan-details-grid .full-width-detail {
            flex: 1 1 100%;
            max-width: 100%;
        }
        .additional-details-grid .half-width-detail {
            flex: 1 1 calc(50% - 20px);
            max-width: calc(50% - 20px);
        }
        @media (max-width: 768px) {
            .plan-details-grid div, .additional-details-grid div {
                flex: 1 1 calc(50% - 20px);
                max-width: calc(50% - 20px);
            }
        }

        /* General Detail Labels */
        .plan-details-grid strong, .additional-details-grid strong {
            display: block;
            font-size: 0.85em;
            color: #777;
            margin-bottom: 2px;
            font-weight: 500;
        }
        .plan-details-grid span, .additional-details-grid span {
            display: block;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .detail-text-box {
            display: block;
            background-color: #f2f2f2; 
            padding: 8px 12px;
            border-radius: 4px;
            font-style: italic;
            font-weight: 500 !important; 
            color: #555 !important;
        }
        
        /* Status Tag Styles */
        .overall-plan-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em; 
            font-weight: 700;
            white-space: nowrap;
        }
        
        .price-tag-design {
            background-color: #ffe0b2; 
            color: #e65100; 
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em; 
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .overall-active { background-color: #e8f9e8; color: #008a00; }
        .overall-paused { background-color: #fff8e1; color: #ff8f00; }
        .overall-terminated { background-color: #ffe8e8; color: #d32f2f; }
        .overall-completed { background-color: #e0f2f1; color: #00796b; }
        .overall-pending { background-color: #e0e5ea; color: #495057; border: 1px solid #c4ccd5; }

        .header-title-wrapper {
            display: flex;
            flex-direction: column;
            gap: 5px; 
        }
        .ref-display-large {
            font-size: 1.1em;
            font-weight: 700;
            color: #444;
            margin-bottom: 5px; 
        }
        .ref-display-large span {
            color: #B32133;
        }
        
        /* Sessions List Header and Table Styling */
        .page-subheader {
            font-size: 1.2em;
            margin-top: 30px; 
            margin-bottom: 15px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 5px;
        }

        .sessions-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95em;
            overflow: hidden;
        }

        .sessions-table th, .sessions-table td {
            padding: 15px 20px; 
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .sessions-table th {
            background-color: #f8f8f8;
            color: #555;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .sessions-table th:first-child,
        .sessions-table td:first-child {
            text-align: center;
        }

        .sessions-table tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        .sessions-table tbody tr:last-child td {
            border-bottom: none;
        }

        .session-status {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }
        
        /* Session Status Colors (reuse the overall design concept) */
        .completed { color: #00796b; } 
        .upcoming { color: #495057; }
        .rescheduled { color: #ff8f00; }
        .cancelled { color: #d32f2f; }
        
        /* General Action Button Styling */
        .action-btn {
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }

        .action-btn.view-details-btn {
            background-color: #B32133; 
            color: white;
            padding: 5px 10px;
        }
        .action-btn.view-details-btn:hover {
            background-color: #8c1a29; 
        }
        
        /* ⭐ NEW STYLE FOR MULTIPLE ACTION BUTTONS (Modified) ⭐ */
        .actions-cell {
            display: flex;
            gap: 8px;
            white-space: nowrap; 
        }

        .actions-cell .action-btn {
            padding: 5px; /* Binawasan ang padding para sa icons lang */
            font-size: 1.2em; /* Dinagdagan ang font size para sa icon */
        }

        .actions-cell .rate-btn {
            /* DARK YELLOW/GOLD */
            background-color: #FFC107; 
            color: white;
        }

        .actions-cell .rate-btn:hover {
            background-color: #E0A800;
        }

        .actions-cell .report-btn {
            background-color: #DC3545; /* Reddish for reporting */
            color: white;
        }

        .actions-cell .report-btn:hover {
            background-color: #C82333;
        }
        /* ⭐ END OF NEW STYLE (Modified) ⭐ */

        /* Style for Back to History (Top, simple button) */
        .action-btn.back-to-history {
            background: none;
            border: 1px solid #ddd;
            color: #555;
            padding: 8px 15px;
            border-radius: 4px;
            margin-bottom: 20px; 
            margin-top: -10px; 
            align-self: flex-start;
        }
        .action-btn.back-to-history:hover {
            background-color: #f9f9f9;
        }
        
        /* Mobile adjustments for header */
        @media (max-width: 992px) { 
            .header-details-row {
                flex-direction: column;
                align-items: stretch;
            }
            .header-right-wrapper {
                margin-top: 15px;
                width: 100%;
                justify-content: space-between;
            }
        }
        @media (max-width: 600px) {
            .sessions-table th, .sessions-table td {
                padding: 10px 15px;
            }
            .sessions-table {
                display: block;
                overflow-x: auto; 
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>

    <main class="dashboard__content">
        
        <a href="HIS_recurring.php" class="action-btn back-to-history">
            <i class='bx bx-arrow-back'></i> Back to History
        </a>
        
        <div class="history-header-container">
            
            <div class="header-details-row">
                <div class="header-title-wrapper">
                    <h2 class="main-title">
                        <i class='bx bx-list-ul'></i> Sessions for Recurring Plan
                    </h2>
                    <p class="ref-display-large">
                        Plan Ref: <span style="color: #B32133;"><?php echo htmlspecialchars($plan_reference_no); ?></span>
                    </p>
                </div>

                <div class="header-right-wrapper">
                    <div class="primary-actions-top">
                        </div>

                    <?php if (!empty($plan_details)): ?>
                    <div class="header-status-price">
                        <div class="detail-item">
                            <strong>Plan Status</strong>
                            <span class="overall-plan-tag <?php echo $plan_status_tag_class; ?>">
                                <i class='bx 
                                    <?php 
                                        if ($plan_details['status'] === 'ACTIVE') echo 'bx-play-circle'; 
                                        else if ($plan_details['status'] === 'COMPLETED') echo 'bx-check-double';
                                        else if ($plan_details['status'] === 'PAUSED') echo 'bx-pause-circle';
                                        else if ($plan_details['status'] === 'TERMINATED') echo 'bx-x-circle';
                                        else echo 'bx-time-five';
                                    ?>
                                '></i> 
                                <?php echo htmlspecialchars($plan_details['status']); ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <strong>Estimated Price</strong>
                            <span class="price-tag-design">
                                <i class='bx bx-purchase-tag-alt'></i> AED <?php echo number_format($plan_details['price'], 0); ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($plan_details)): ?>
                <div class="plan-details-grid">
                    
                    <div class="half-width-detail">
                        <strong>Service Type</strong>
                        <span><i class='bx bx-wrench'></i> <?php echo htmlspecialchars($plan_details['service_type']); ?></span>
                    </div>
                    <div class="half-width-detail">
                        <strong>Client Type</strong>
                        <span><i class='bx bx-building-house'></i> <?php echo htmlspecialchars($plan_details['client_type']); ?></span>
                    </div>
                    <div></div> 

                    <div>
                        <strong>Start Date</strong>
                        <span><i class='bx bx-calendar-check'></i> <?php echo htmlspecialchars($plan_details['start_date']); ?></span>
                    </div>
                    <div>
                        <strong>End Date</strong>
                        <span><i class='bx bx-calendar-check'></i> <?php echo htmlspecialchars($plan_details['end_date']); ?></span>
                    </div>
                    <div>
                        <strong>No. of Sessions</strong>
                        <span><i class='bx bx-repeat'></i> <?php echo htmlspecialchars($plan_details['no_of_sessions'] ?? 'N/A'); ?></span>
                    </div>
                    
                    <div>
                        <strong>Time of Service</strong>
                        <span><i class='bx bx-time'></i> <?php echo htmlspecialchars($plan_details['time']); ?></span>
                    </div>
                    <div>
                        <strong>Duration</strong>
                        <span><i class='bx bx-hourglass'></i> <?php echo htmlspecialchars($plan_details['duration']); ?></span>
                    </div>
                    <div>
                        <strong>Frequency</strong>
                        <span><i class='bx bx-sync'></i> <?php echo htmlspecialchars($plan_details['frequency']); ?></span>
                    </div>
                    
                    <div class="full-width-detail">
                        <strong>Service Address</strong>
                        <span><i class='bx bx-map-pin'></i> <?php echo htmlspecialchars($plan_details['address']); ?></span>
                    </div>
                </div>

                <div class="show-more-toggle-container">
                    <hr>
                    <button id="show-more-btn" class="show-more-btn" onclick="toggleAdditionalDetails()">
                        <i class='bx bx-chevron-down'></i> Show More Details
                    </button>
                </div>
                
                <div id="additional-details" class="additional-details-grid hidden">
                    
                    <div class="half-width-detail">
                        <strong>Property Layout</strong>
                        <span><i class='bx bx-bed'></i> <?php echo htmlspecialchars($plan_details['property_layout'] ?? 'N/A'); ?></span>
                    </div>
                    
                    <div class="half-width-detail">
                        <strong>Attachments (Max 3 Videos/Images)</strong>
                        
                        <?php if ($attachment_count > 0): ?>
                            <span class="detail-text-box attachments-container-link" style="font-style: normal; font-weight: 500;">
                                <i class='bx bx-paperclip' style="margin-right: 5px; color: #555;"></i> 
                                <?php foreach ($attachments_list as $attachment): 
                                    $file_icon = 'bx-file';
                                    if ($attachment['type'] === 'Image') $file_icon = 'bx-image';
                                    if ($attachment['type'] === 'Video') $file_icon = 'bx-video';
                                    if ($attachment['type'] === 'Document') $file_icon = 'bx-file-pdf';
                                ?>
                                    <a href="<?php echo htmlspecialchars($attachment['url'] ?? '#'); ?>" target="_blank" title="View <?php echo htmlspecialchars($attachment['name']); ?>">
                                        <i class='bx <?php echo $file_icon; ?>' style="font-size: 1.1em;"></i>
                                        <?php echo htmlspecialchars($attachment['name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </span>
                        <?php else: ?>
                            <span><i class='bx bx-paperclip'></i> No Files Attached</span>
                        <?php endif; ?>
                        </div>
                    <div class="half-width-detail">
                        <strong>Does the client require cleaning materials (yes or no)</strong>
                        <span><i class='bx bx-check-circle' style="color: <?php echo $plan_details['cleaning_materials_required'] === 'Yes' ? '#008a00' : '#d32f2f'; ?>;"></i> <?php echo htmlspecialchars($plan_details['cleaning_materials_required'] ?? 'N/A'); ?></span>
                    </div>
                    
                    <div class="half-width-detail">
                        <strong>If yes, what materials are needed?</strong>
                        <span class="detail-text-box"><?php echo htmlspecialchars($plan_details['materials_needed'] ?? 'N/A'); ?></span>
                    </div>
                    
                    <div class="half-width-detail">
                        <strong>Additional Request</strong>
                        <span class="detail-text-box"><?php echo htmlspecialchars($plan_details['additional_request'] ?? 'N/A'); ?></span>
                    </div>

                </div>
            <?php endif; ?>
        </div>
        
        <div class="sessions-container">
            <h3 class="page-subheader" style="margin-top: 0; padding-top: 15px; padding-left: 20px;">
                <i class='bx bx-calendar-alt'></i> List of Sessions
            </h3>
            
            <div class="session-list-wrapper">
                
                <table class="sessions-table">
                    <thead>
                        <tr>
                            <th>Session #</th>
                            <th>Date</th>
                            <th>Start Time</th> 
                            <th>End Time</th> <th>Duration</th> 
                            <th>Staffs</th> 
                            <th>Status</th>
                            <th>Price (AED)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Check kung may sessions data
                        if (empty($sessions)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 30px;"> Walang sessions na nakita para sa plan na ito.
                                </td>
                            </tr>
                        <?php 
                        else: 
                            // Loop sa sessions data
                            foreach ($sessions as $session):
                                $status_class = strtolower(str_replace(' ', '-', $session['status']));
                                $status_icon = '';
                                if ($session['status'] === 'COMPLETED') $status_icon = 'bx-check-circle';
                                else if ($session['status'] === 'UPCOMING') $status_icon = 'bx-calendar';
                                else if ($session['status'] === 'RESCHEDULED') $status_icon = 'bx-revision';
                                else if ($session['status'] === 'CANCELLED') $status_icon = 'bx-x-circle';
                                else $status_icon = 'bx-circle';
                                
                                // ⭐ GINAMIT ANG NEW FUNCTION DITO ⭐
                                $session_end_time = calculateEndTime($session['time'], $session['actual_duration'] ?? $plan_duration);
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($session['session_no']); ?></td>
                                <td><?php echo htmlspecialchars($session['date']); ?></td>
                                <td><?php echo htmlspecialchars($session['time']); ?></td>
                                <td><?php echo htmlspecialchars($session_end_time); ?></td> <td><?php echo htmlspecialchars($session['actual_duration'] ?? $plan_duration); ?></td> 
                                <td><?php echo htmlspecialchars($session['staffs'] ?? 'TBD'); ?></td> 
                                <td class="session-status <?php echo $status_class; ?>">
                                    <i class='bx <?php echo $status_icon; ?>'></i> 
                                    <?php echo htmlspecialchars($session['status']); ?>
                                </td>
                                <td>AED <?php echo number_format($session['price'], 0); ?></td>
                                <td class="actions-cell">
                                    <a href='RATE_session.php?id=<?php echo htmlspecialchars($session['id']); ?>' class='action-btn rate-btn' title='Rate Session'>
                                        <i class='bx bxs-star'></i>
                                    </a>
                                    <a href='REPORT_issue.php?id=<?php echo htmlspecialchars($session['id']); ?>' class='action-btn report-btn' title='Report Issue'>
                                        <i class='bx bxs-error-alt'></i>
                                    </a>
                                </td>
                            </tr>
                        <?php 
                            endforeach;
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        </main>

    <script>
        // JAVASCRIPT FUNCTION PARA I-TOGGLE ANG DETAILS
        function toggleAdditionalDetails() {
            const detailsDiv = document.getElementById('additional-details');
            const button = document.getElementById('show-more-btn');

            detailsDiv.classList.toggle('hidden');

            if (detailsDiv.classList.contains('hidden')) {
                button.innerHTML = "<i class='bx bx-chevron-down'></i> Show More Details";
            } else {
                button.innerHTML = "<i class='bx bx-chevron-up'></i> Show Less Details";
            }
        }
    </script>
</body>
</html>