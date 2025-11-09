<?php
session_start();
require 'connection.php';

$user_email = $_SESSION['email'] ?? '';

$serviceTypes = [
    'Weekly' => [],
    'Bi-Weekly' => [],
    'Monthly' => []
];

$sql = "SELECT * FROM bookings WHERE booking_type='Recurring' AND email=? ORDER BY service_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {
        
        // 🔍 DEBUG: Check what's in the database row
        echo "<!-- DEBUG Row ID: " . $row['id'] . " -->";
        echo "<!-- DEBUG drivers column: '" . ($row['drivers'] ?? 'NULL') . "' -->";
        echo "<!-- DEBUG cleaners column: '" . ($row['cleaners'] ?? 'NULL') . "' -->";

        $frequency = $row['frequency'] ?? 'Weekly';
        if (!isset($serviceTypes[$frequency])) {
            $frequency = 'Weekly';
        }

        $estimated_price = 0;
        $materials_provided = $row['materials_provided'] ?? '';
        $duration = floatval($row['duration'] ?? 0);

       if (preg_match('/(\d+(?:\.\d+)?)/', $materials_provided, $matches)) {
    $hourly_rate = floatval($matches[1]);
    $estimated_price = $hourly_rate * $duration;
}


        // Generate unique recurring reference
        $freq_code = $frequency === 'Weekly' ? 'WK' :
                     ($frequency === 'Bi-Weekly' ? 'BWK' : 'MTH');

        $reference_no = 'ALZ-' . $freq_code . '-' . date('ym', strtotime($row['service_date'])) . '-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT);

        $serviceTypes[$frequency][] = [
            'booking_id' => $row['id'],
            'reference_no' => $reference_no,
            'full_name' => $row['full_name'] ?? '',
            'phone' => $row['phone'] ?? '',
            'start_date' => $row['start_date'] ?? $row['service_date'],  // ✅ Use start_date, fallback to service_date
            'end_date' => $row['end_date'] ?? null,  // ✅ Use end_date column
            'booking_time' => $row['service_time'],
            'duration' => $row['duration'] ?? '0',
            'frequency' => $frequency,
            'preferred_day' => $row['preferred_day'] ?? '',
            'sessions_completed' => $row['sessions_completed'] ?? 0,
            'total_sessions' => $row['total_sessions'] ?? 0,
            'address' => $row['address'] ?? '',
            'client_type' => $row['client_type'] ?? 'N/A',
            'status' => strtoupper($row['status'] ?? 'PENDING'),

            'service_type' => $row['service_type'] ?? 'General Cleaning',
            'property_layout' => $row['property_type'] ?? '',
            'materials_required' => $row['materials_provided'] ?? 'No',
            'materials_description' => $row['materials_needed'] ?? '',
            'additional_request' => $row['comments'] ?? '',
            'image_1' => $row['media1'] ?? '',
            'image_2' => $row['media2'] ?? '',
            'image_3' => $row['media3'] ?? '',
            'estimated_price' => $estimated_price,
            'final_price' => $row['final_price'] ?? 0,

            // ✅ Fetch staff from database
            'driver_name' => $row['drivers'] ?? '',
            'cleaners_names' => $row['cleaners'] ?? ''
        ];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ALAZIMA - Recurring History</title>
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="client_db.css"> 
<link rel="stylesheet" href="HIS_design.css">

<style>
.cancel-appointment-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.6);
    justify-content: center;
    align-items: center;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.6);
    justify-content: center;
    align-items: center;
}

.cancel-modal-content {
    background-color: #fefefe;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    max-width: 450px;
    width: 90%;
    text-align: center;
    position: relative;
}

.cancel-modal-content .close-btn-x {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    position: absolute;
    top: 10px;
    right: 20px;
    cursor: pointer;
}

.cancel-modal-content .close-btn-x:hover,
.cancel-modal-content .close-btn-x:focus {
    color: #333;
    text-decoration: none;
}

.cancel-modal-content .cancel-icon {
    font-size: 5em; 
    color: #B32133;
    margin-bottom: 5px;
}

.cancel-modal-content h3 {
    font-size: 1.5em;
    font-weight: 700;
    color: #333;
    margin-bottom: 10px;
    border-bottom: none;
}

.cancel-modal-content p {
    font-size: 1em;
    color: #555;
    margin-bottom: 25px;
    line-height: 1.5;
}

.cancel-modal-content strong#cancel-ref-number,
.cancel-modal-content strong#cancelled-ref-number {
    color: #B32133;
    font-weight: 700;
    display: block; 
    margin: 5px 0 15px 0;
    font-size: 1.1em;
}

.cancel-modal-content .modal__actions {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    width: 100%;
}

.cancel-modal-content .modal__actions button {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s, opacity 0.3s;
    flex-grow: 1; 
}

.cancel-modal-content .primary-cancel-btn {
    background-color: #B32133;
    color: white;
    border: 2px solid #B32133;
    box-shadow: none;
}
.cancel-modal-content .primary-cancel-btn:hover {
    background-color: #9c1d2d;
    border-color: #9c1d2d;
}

.cancel-modal-content .secondary-keep-btn {
    background-color: #fff;
    color: #555;
    border: 2px solid #ccc;
    box-shadow: none;
}
.cancel-modal-content .secondary-keep-btn:hover {
    background-color: #f4f4f4;
    border-color: #bbb;
}

.appointment-list-item {
    position: relative;
    padding-top: 10px;
    padding-bottom: 15px;
    padding-left: 15px;
    padding-right: 15px;
    display: flex; 
    flex-wrap: wrap; 
    align-items: flex-start;
}

.overall-plan-tag {
    display: inline-flex; 
    align-items: center; 
    gap: 6px; 
    padding: 5px 10px; 
    border-radius: 4px; 
    font-size: 0.9em; 
    font-weight: 700; 
    margin-bottom: 8px; 
    white-space: nowrap; 
}

.overall-active { background-color: #e8f9e8; color: #008a00; }
.overall-paused { background-color: #fff8e1; color: #ff8f00; }
.overall-terminated,
.overall-cancelled { background-color: #ffe8e8; color: #d32f2f; } 
.overall-completed { background-color: #e0f2f1; color: #00796b; }
.overall-pending { background-color: #e0e5ea; color: #495057; border: 1px solid #c4ccd5; }
.overall-unknown,
.status-tag.unknown { 
    background-color: #f0f0f0;
    color: #555;
}

.button-group-top {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    gap: 5px;
}

.appointment-details {
    margin-top: 5px;
    width: 100%; 
    flex-grow: 1;
}

.status-filter-dropdown {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1em;
    cursor: pointer;
    background-color: #fff;
    width: 170px;
    height: 40px;
    flex-shrink: 0;
}

.action-btn.edit-plan-btn{
    padding: 8px 12px; font-weight: bold; cursor: pointer; border-radius: 6px; text-align: center; transition: all 0.3s; text-decoration: none; display: flex; align-items: center; gap: 5px; font-size: 0.9em; background-color: #0056b3; border: 2px solid #0056b3; color: white;
}
.action-btn.edit-plan-btn:hover { background-color: #0062cc; border-color: #0062cc; }

.dropdown-menu .whatsapp-chat-link i { color: #25D366; }
.appointment-details .ref-no-detail { margin-bottom: 15px; }

.history-header-container {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
}

.info-button {
    background-color: transparent;
    color: #004a80;
    border: 2px solid #004a80;
    padding: 8px 15px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s, color 0.3s;
    display: flex;
    align-items: center;
    gap: 5px;
    height: 40px;
    flex-shrink: 0;
}

.info-button:hover {
    background-color: #004a80;
    color: white;
}

.info-button-container {
    position: relative;
    display: inline-block;
    flex-shrink: 0;
}

.info-tooltip-content {
    visibility: hidden;
    opacity: 0;
    transition: opacity 0.3s, visibility 0.3s;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    padding: 20px;
    max-width: 550px;
    width: max-content;
    position: absolute;
    z-index: 1000;
    top: 100%;
    right: 0;
    margin-top: 10px;
    text-align: left;
}

.info-tooltip-content h3 {
    font-size: 1.5em;
    font-weight: 700;
    color: #004a80;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 2px solid #eee;
    padding-bottom: 10px;
}

.info-tooltip-content ul {
    list-style: disc;
    padding-left: 20px;
    margin: 0 0 20px 0;
}

.info-tooltip-content li {
    font-size: 1em;
    color: #333;
    margin-bottom: 10px;
    line-height: 1.6;
    border-left: none;
    padding-left: 0;
}

.info-tooltip-content li ul {
    list-style: none;
    padding-left: 0;
    margin-top: 5px;
    margin-bottom: 5px;
}

.info-tooltip-content li ul li {
    font-size: 0.95em;
    margin-bottom: 5px;
    color: #555;
    line-height: 1.5;
}

.info-tooltip-content li strong {
    color: #B32133;
    font-weight: 700;
}

.info-tooltip-content li.completed-info {
    border-left: none;
}

.info-button-container:hover .info-tooltip-content {
    visibility: visible;
    opacity: 1;
}

.info-tooltip-content::after {
    content: "";
    position: absolute;
    bottom: 100%;
    right: 20px;
    border-width: 8px;
    border-style: solid;
    border-color: transparent transparent #fff transparent;
    filter: drop-shadow(0 -2px 1px rgba(0, 0, 0, 0.05));
}

/* Edit Modal Styles */
.action-btn.edit-plan-btn {
    padding: 8px 12px;
    font-weight: bold;
    cursor: pointer;
    border-radius: 6px;
    text-align: center;
    transition: all 0.3s;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9em;
    background-color: #0056b3;
    border: 2px solid #0056b3;
    color: white;
}

.action-btn.edit-plan-btn:hover {
    background-color: #0062cc;
    border-color: #0062cc;
}
.dropdown-menu-container {
    position: relative;
    display: inline-block;
}

.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background-color: white;
    min-width: 200px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    border-radius: 8px;
    padding: 8px 0;
    margin-top: 5px;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-menu li {
    list-style: none;
}

.dropdown-menu li a {
    color: #333;
    padding: 12px 16px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.3s;
}

.dropdown-menu li a:hover {
    background-color: #f1f1f1;
}

.dropdown-menu li a i {
    font-size: 1.2em;
}

.more-options-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 5px;
    font-size: 1.5em;
    color: #666;
    transition: color 0.3s;
}

.more-options-btn:hover {
    color: #333;
}

.cancel-link i {
    color: #B32133 !important;
}

.whatsapp-chat-link i {
    color: #25D366 !important;
}
.dropdown-menu-container {
    position: relative;
    display: inline-block;
}

.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background-color: white;
    min-width: 200px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    border-radius: 8px;
    padding: 8px 0;
    margin-top: 5px;
}

.dropdown-menu.show {
    display: block;
}
</style>
</head>
    
<body>
    <header class="header" id="header">
        <nav class="nav container">
            <a href="client_dashboard.php?content=dashboard" class="nav__logo">
                <img src="LOGO.png" alt="ALAZIMA Cleaning Services LLC Logo" onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
            </a>
            <button class="nav__toggle" id="nav-toggle" aria-label="Toggle navigation menu">
                <i class='bx bx-menu'></i>
            </button>
        </nav>
    </header>

    <div class="dashboard__wrapper">
        <aside class="dashboard__sidebar">
            <ul class="sidebar__menu">
                <li class="menu__item"><a href="client_dashboard.php?content=dashboard" class="menu__link" data-content="dashboard"><i class='bx bx-home-alt-2'></i> Dashboard</a></li>
                
                <li class="menu__item has-dropdown">
                    <a href="#" class="menu__link" data-content="book-appointment-parent"><i class='bx bx-calendar'></i> Book Appointment <i class='bx bx-chevron-down arrow-icon'></i></a>
                    <ul class="dropdown__menu">
                        <li class="menu__item"><a href="BA_one-time.php" class="menu__link">One-Time Booking</a></li>
                        <li class="menu__item"><a href="BA_recurring.php" class="menu__link">Recurring Booking</a></li>
                    </ul>
                </li>
                <li class="menu__item has-dropdown open">
                    <a href="#" class="menu__link active-parent" data-content="history-parent"><i class='bx bx-history'></i> History  <i class='bx bx-chevron-down arrow-icon'></i></a>
                    <ul class="dropdown__menu">
                        <li class="menu__item"><a href="HIS_one-time.php" class="menu__link">One-Time Service</a></li>
                        <li class="menu__item"><a href="HIS_recurring.php" class="menu__link active">Recurring Bookings</a></li>
                    </ul>
                </li>

                <li class="menu__item has-dropdown">
                    <a href="#" class="menu__link" data-content="feedback-parent"><i class='bx bx-star'></i> Feedback/Ratings <i class='bx bx-chevron-down arrow-icon'></i></a>
                    <ul class="dropdown__menu">
                        <li class="menu__item"><a href="FR_one-time.php" class="menu__link">One-Time Service</a></li>
                        <li class="menu__item"><a href="FR_recurring.php" class="menu__link">Recurring Service</a></li>
                    </ul>
                </li>
                
                <li class="menu__item"><a href="client_profile.php" class="menu__link" data-content="profile"><i class='bx bx-user'></i> My Profile</a></li>
                <li class="menu__item"><a href="javascript:void(0)" class="menu__link" data-content="logout" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
            </ul>
        </aside>

    <main class="dashboard__content">
        
        <div class="history-header-container">
            <div class="header-text-group">
                <h2 class="main-title">
                    <i class='bx bx-history'></i> Recurring Service History
                </h2>
                <p class="page-description">
                    Here's a detailed list of all your recurring cleaning service bookings, organized by frequency.
                </p>
            </div>
            <div class="info-button-container">
                <button class="info-button">
                    <i class='bx bx-info-circle'></i> Info
                </button>
                
                <div class="info-tooltip-content">
                    <h3><i class='bx bx-info-circle'></i> Guidelines</h3>
                    
                    <ul>
                        <li>
                            You can edit or cancel your booking while it's still <strong>Pending</strong>.
                            <ul>
                                <li>To make changes, click the <strong>Edit</strong> button to update your form.</li>
                                <li>To cancel, select <strong>Cancel</strong> from the drop-down menu.</li>
                            </ul>
                        </li>
                        <li>
                            Once your booking is <strong>Active</strong>, you can no longer edit or cancel it directly.
                            <ul>
                                <li>Contact us via <strong>WhatsApp</strong> using the <strong>Chat</strong> button (<i class='bx bxl-whatsapp'></i>) for any changes or concerns.</li>
                            </ul>
                        </li>
                        <li>
                            Feedback and ratings can be submitted after every <strong>Completed</strong> session.
                        </li>
                    </ul>
                    
                    <p style="font-size: 1em; color: #333; margin-top: 15px; line-height: 1.6;">
                        Please refer to the <a href="waiver.html" target="_blank" style="color: #004a80; font-weight: 600; text-decoration: underline;">Service Waiver</a> for full guidelines and additional terms.
                    </p>

                </div>
            </div>
        </div>
        
        
        <div class="horizontal-tabs-container">
            <div class="service-tabs-bar">
                <button class="tab-button active" onclick="openTab(event, 'weekly-cleaning')">
                    <i class='bx bx-calendar-check'></i> Weekly Cleaning
                </button>
                <button class="tab-button" onclick="openTab(event, 'biweekly-cleaning')">
                    <i class='bx bx-calendar-week'></i> Bi-Weekly Cleaning
                </button>
                <button class="tab-button" onclick="openTab(event, 'monthly-cleaning')">
                    <i class='bx bx-calendar-event'></i> Monthly Cleaning
                </button>
            </div>
        </div>
        
        <?php
        // Function to render recurring appointment item
          function renderRecurringAppointmentItem
     ($booking) {
    $plan_status = $booking['status'] ?? 'Pending';
    $formatted_start_date = date('F d, Y', strtotime($booking['start_date']));
    $formatted_time = date('g:i A', strtotime($booking['booking_time']));

    $end_date_display = 'N/A';
    if (!empty($booking['end_date'])) {
        $end_date_display = date('F d, Y', strtotime($booking['end_date']));
    } else {
        if ($plan_status === 'ACTIVE') {
            $end_date_display = 'N/A (Active)';
        } elseif ($plan_status === 'Pending') {
            $end_date_display = 'N/A (Pending)';
        } elseif ($plan_status === 'PAUSED') {
            $end_date_display = 'N/A (Paused)';
        }
    }

    $search_terms = implode(' ', [
        $booking['reference_no'] ?? '',
        $formatted_start_date,
        $formatted_time,
        $booking['address'] ?? '',
        $booking['client_type'] ?? '',
        $plan_status
    ]);

    $status_class = 'overall-' . strtolower(str_replace(' ', '-', $plan_status));
    $status_icons = [
        'Pending' => '<i class="bx bx-time-five"></i>',
        'ACTIVE' => '<i class="bx bx-play-circle"></i>',
        'PAUSED' => '<i class="bx bx-pause-circle"></i>',
        'COMPLETED' => '<i class="bx bx-check-double"></i>',
        'CANCELLED' => '<i class="bx bx-x-circle"></i>'
    ];
    $status_icon = $status_icons[$plan_status] ?? '';

    // Generate action buttons based on status
    $buttons = '';
    if ($plan_status === 'Pending') {
        $buttons = '
            <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showRecurringDetailsModal(this.closest(\'.appointment-list-item\'))"><i class="bx bx-show"></i> View Details</a>
            <a href="EDIT_recurring.php?booking_id='.$booking['booking_id'].'" class="action-btn edit-plan-btn"><i class="bx bx-edit"></i> Edit</a>
            <div class="dropdown-menu-container">
            
                <button class="more-options-btn" onclick="toggleDropdown(this)"><i class="bx bx-dots-vertical-rounded"></i></button>
                <ul class="dropdown-menu">
                    <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-chat-link"><i class="bx bxl-whatsapp"></i> Chat on WhatsApp</a></li>
                    <li><a href="javascript:void(0)" class="cancel-link" onclick="showCancelModal(\''.$booking['reference_no'].'\', '.$booking['booking_id'].')"><i class="bx bx-x-circle" style="color: #B32133;"></i> Cancel</a></li>
                </ul>
            </div>
        ';
    } else {
        $buttons = '
            <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showRecurringDetailsModal(this.closest(\'.appointment-list-item\'))"><i class="bx bx-show"></i> View Details</a>
            <div class="dropdown-menu-container">
                <button class="more-options-btn" onclick="toggleDropdown(this)"><i class="bx bx-dots-vertical-rounded"></i></button>
                <ul class="dropdown-menu">
                    <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-chat-link"><i class="bx bxl-whatsapp"></i> Chat on WhatsApp</a></li>
                    <li><a href="javascript:void(0)" class="report-link" onclick="showReportModal(this)"><i class="bx bx-error-alt"></i> Report Issue</a></li>
                     <li><a href="javascript:void(0)" class="cancel-link" onclick="showCancelModal(\''.$booking['reference_no'].'\', '.$booking['booking_id'].')"><i class="bx bx-x-circle" style="color: #B32133;"></i> Cancel</a></li>
                </ul>
                </ul>
            </div>
        ';
    }

    
$staff_details = '';
    if (!empty($booking['driver_name']) || !empty($booking['cleaners_names'])) {
        $staff_details .= '<hr class="divider full-width-detail">';
        if (!empty($booking['driver_name'])) {
            $staff_details .= '<p class="full-width-detail"><i class=\'bx bx-car\'></i> <strong>Driver:</strong> '.htmlspecialchars($booking['driver_name']).'</p>';
        }
        if (!empty($booking['cleaners_names'])) {
            $staff_details .= '<p class="full-width-detail"><i class=\'bx bx-group\'></i> <strong>Cleaners:</strong> '.htmlspecialchars($booking['cleaners_names']).'</p>';
        }
    }
    $estimated_sessions = $booking['estimated_sessions'] ?? 0;
if (!empty($booking['start_date']) && !empty($booking['end_date']) && !empty($booking['frequency'])) {
    $start = new DateTime($booking['start_date']);
    $end = new DateTime($booking['end_date']);
    $frequency = $booking['frequency'];
    
    if ($frequency === 'Weekly') {
        $interval = 7;
        $current = clone $start;
        while ($current <= $end) {
            $estimated_sessions++;
            $current->modify('+' . $interval . ' days');
        }
    } elseif ($frequency === 'Bi-Weekly') {
        $interval = 14;
        $current = clone $start;
        while ($current <= $end) {
            $estimated_sessions++;
            $current->modify('+' . $interval . ' days');
        }
    } elseif ($frequency === 'Monthly') {
        $startDay = (int)$start->format('d');
        $current = clone $start;
        
        // Count first session
        if ($current <= $end) {
            $estimated_sessions = 1;
        }
        
        // Loop through months
        while (true) {
            $current->modify('+1 month');
            
            // Handle cases where day doesn't exist in month (e.g., Jan 31 -> Feb 28)
            $tempDay = (int)$current->format('d');
            if ($tempDay !== $startDay) {
                $current->setDate(
                    (int)$current->format('Y'),
                    (int)$current->format('m'),
                    min($startDay, (int)$current->format('t'))
                );
            }
            
            if ($current > $end) {
                break;
            }
            
            $estimated_sessions++;
        }
    }
}
    

    $price_label = ($plan_status === 'COMPLETED') ? 'Final Price' : 'Estimated Price';
    $price = ($plan_status === 'COMPLETED' && !empty($booking['final_price'])) ? $booking['final_price'] : $booking['estimated_price'];

    echo '
   <div class="appointment-list-item" 
   data-start-date="'.$booking['start_date'].'"  
    data-end-date="'.($booking['end_date'] ?? '2025-12-31').'" 
    data-time="'.$booking['booking_time'].'"
    data-duration="'.$booking['duration'].'"
    data-frequency="'.$frequency.'"
    data-sessions="'.$estimated_sessions.'"
    data-plan-status="'.$plan_status.'"
    data-search-terms="'.$search_terms.'"
    data-property-layout="'.htmlspecialchars($booking['property_layout'] ?? '').'"
    data-materials-required="'.($booking['materials_required'] ?? '').'"
    data-materials-description="'.htmlspecialchars($booking['materials_description'] ?? '').'"
    data-additional-request="'.htmlspecialchars($booking['additional_request'] ?? '').'"
    data-image-1="'.($booking['image_1'] ?? '').'"
    data-image-2="'.($booking['image_2'] ?? '').'"
    data-image-3="'.($booking['image_3'] ?? '').'">
        
        <div class="button-group-top">
            '.$buttons.'
        </div>
        
        <div class="appointment-details">
            <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">'.($booking['reference_no'] ?? '').'</span></p>
            <p><i class="bx bx-calendar-check"></i> <strong>Start Date:</strong> '.$formatted_start_date.'</p>
            <p class="end-date-detail"><i class="bx bx-calendar-check"></i> <strong>End Date:</strong> '.$end_date_display.'</p>
            <p><i class="bx bx-time"></i> <strong>Time:</strong> '.$formatted_time.'</p>
             <p class="duration-detail"><i class=\'bx bx-stopwatch\'></i> <strong>Duration:</strong> '.$booking['duration'].' hours</p>
            <p class="frequency-detail"><i class="bx bx-sync"></i> <strong>Frequency:</strong> '.($booking['frequency'] ?? '').'</p>
         <p class="sessions-detail"><i class="bx bx-list-ol"></i> <strong>No. of Sessions:</strong> <span class="sessions-count">'.$estimated_sessions.'</span></p>
            
            <p class="full-width-detail"><i class="bx bx-map-alt"></i> <strong>Address:</strong> '.($booking['address'] ?? '').'</p>
            <hr class="divider full-width-detail">
           <p class="full-width-detail"><i class="bx bx-map-alt"></i> <strong>Address:</strong> '.($booking['address'] ?? '').'</p>
<hr class="divider full-width-detail">
<p><i class="bx bx-building-house"></i> <strong>Client Type:</strong> '.($booking['client_type'] ?? '').'</p>
<p class="service-type-detail"><i class="bx bx-wrench"></i> <strong>Service Type:</strong> '.($booking['service_type'] ?? '').'</p>

'.$staff_details.'

<p class="full-width-detail status-detail">
    <strong>Plan Status:</strong>
    <span class="overall-plan-tag '.$status_class.'">'.$status_icon.' '.$plan_status.'</span>
</p>
<p class="price-detail">'.$price_label.': <span class="aed-color">AED '.$price.'</span></p>
        </div>
    </div>';
}

        
        // Render each frequency tab
        $tab_ids = [
            'Weekly' => 'weekly-cleaning',
            'Bi-Weekly' => 'biweekly-cleaning',
            'Monthly' => 'monthly-cleaning'
        ];
        
        $tab_names = [
            'Weekly' => 'Weekly Cleaning',
            'Bi-Weekly' => 'Bi-Weekly Cleaning',
            'Monthly' => 'Monthly Cleaning'
        ];
        
        $first_tab = true;
        foreach ($tab_ids as $frequency => $tab_id) {
            $display = $first_tab ? 'block' : 'none';
            $list_id = $tab_id . '-list';
            $service_name = $tab_names[$frequency];
            
            echo '<div id="'.$tab_id.'" class="tab-content" style="display: '.$display.';">';
            echo '
                <div class="filter-controls-tab">
                    <select class="date-filter-dropdown" onchange="handleFilterChange(this, \''.$list_id.'\')">
                        <option value="last7days">Last 7 Days</option>
                        <option value="last30days">Last 30 Days</option>
                        <option value="this_year">This Year</option>
                        <option value="all" selected>All Time</option>
                        <option value="customrange">Custom Range</option>
                    </select>
                    
                    <select class="status-filter-dropdown" onchange="sortAppointmentsByStatus(\''.$list_id.'\', this.value)">
                        <option value="default">Sort by Status</option>
                        <option value="PENDING">Pending</option>
                        <option value="ACTIVE">Active</option>
                        <option value="PAUSED">Paused</option>
                        <option value="COMPLETED">Completed</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>
                    <div class="search-container">
                        <i class=\'bx bx-search\'></i>
                        <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, \''.$list_id.'\')">
                    </div>
                </div>

                <div class="appointment-list-container" id="'.$list_id.'">
                    <div class="no-appointments-message" data-service-name="'.$service_name.'"></div>';
            
            // Render appointments for this frequency
            if (isset($serviceTypes[$frequency]) && count($serviceTypes[$frequency]) > 0) {
                foreach ($serviceTypes[$frequency] as $booking) {
                    renderRecurringAppointmentItem($booking);
                }
            }
            
            echo '</div></div>';
            $first_tab = false;
        }
        ?>

    </main>
</div> 

<a href="#header" id="backToTopBtn" title="Back to Top"><i class='bx bx-up-arrow-alt'></i> Back to Top</a>

<div id="logoutModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Are you sure you want to log out?</h3>
        <div class="modal__actions">
            <button id="cancelLogout" class="btn btn--secondary">Cancel</button>
            <button id="confirmLogout" class="btn btn--primary">Log Out</button>
        </div>
    </div>
</div>

<div class="modal date-picker-modal" id="datePickerModal" onclick="if(event.target.id === 'datePickerModal') closeModal('datePickerModal')">
    <div class="modal-content date-picker-content">
        <h3>Select Custom Date Range</h3>
        <label for="startDate">Start Date:</label>
        <input type="date" id="startDate"> 
        <label for="endDate">End Date:</label>
        <input type="date" id="endDate">
        <div id="dateRangeError" class="error-message"></div>
        <div class="button-group-wrapper" style="margin-top: 20px;">
            <button onclick="applyCustomRange(this.getAttribute('data-list-id'))" data-list-id="weekly-cleaning-list" class="primary-btn">Apply</button>
            <button onclick="closeModal('datePickerModal')" class="secondary-btn">Cancel</button>
        </div>
    </div>
</div>

<div class="modal" id="detailsModal" onclick="if(event.target.id === 'detailsModal') closeModal('detailsModal')">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('detailsModal')">&times;</span> 
        <h3><i class='bx bx-list-ul'></i> Booking Details</h3>
        <div id="modal-details-content"></div>
    </div>
</div>

<div class="report-modal" id="reportIssueModal" onclick="if(event.target.id === 'reportIssueModal') closeModal('reportIssueModal')">
    <div class="report-modal-content">
        <span class="report-close-btn" onclick="closeModal('reportIssueModal')">&times;</span> 
        <h3>Report an Issue</h3>
        <p class="report-ref-display">
            Reference No: <span id="report-ref-number" class="report-ref-value"></span>
        </p>
        <form class="report-form" onsubmit="return submitReport();">
            <input type="hidden" id="submissionDate" name="submissionDate">
            <input type="hidden" id="submissionTime" name="submissionTime">
            <div class="report-date-time-group">
                <div>
                    <label for="issueDate">Date:</label>
                    <input type="date" id="issueDate" name="issueDate" readonly required>
                </div>
                <div>
                    <label for="issueTime">Time:</label>
                    <input type="time" id="issueTime" name="issueTime" readonly required>
                </div>
            </div>
            <label for="issueType">Type of Issue:</label>
            <select id="issueType" onchange="clearError(this)">
                <option value="" disabled selected>Select Issue Category</option>
                <option value="damage">Property Damage</option>
                <option value="unsatisfied">Unsatisfied with Quality of Cleaning</option>
                <option value="late">Staff was Late/No Show</option>
                <option value="staff_behavior">Staff Behavior/Professionalism</option>
                <option value="billing">Billing/Payment Issue</option>
                <option value="other">Other</option>
            </select>
            <div id="issueTypeError" class="error-message">Please complete this required field</div>
            <label for="issueDetails">Issue Description:</label>
            <textarea id="issueDetails" placeholder="Please provide detailed description of the issue." oninput="clearError(this)"></textarea>
            <div id="issueDetailsError" class="error-message">Please complete this required field</div>
            <label>Attachment (up to 3 files - Photos/Videos):</label>
            <div class="attachment-group">
                <div class="custom-file-input-wrapper">
                    <input type="file" id="attachment1" name="attachment1" accept="image/*,video/*" onchange="updateFileName(this)">
                    <div class="custom-file-button">Choose File</div>
                    <div class="custom-file-text" id="file-name-1">No file chosen</div>
                </div>
                <div class="custom-file-input-wrapper">
                    <input type="file" id="attachment2" name="attachment2" accept="image/*,video/*" onchange="updateFileName(this)">
                    <div class="custom-file-button">Choose File</div>
                    <div class="custom-file-text" id="file-name-2">No file chosen</div>
                </div>
                <div class="custom-file-input-wrapper">
                    <input type="file" id="attachment3" name="attachment3" accept="image/*,video/*" onchange="updateFileName(this)">
                    <div class="custom-file-button">Choose File</div>
                    <div class="custom-file-text" id="file-name-3">No file chosen</div>
                </div>
            </div>
            <button type="submit">Submit Report</button>
            <div style="clear: both;"></div> 
        </form>
    </div>
</div>

<div class="report-modal" id="reportSuccessModal" onclick="if(event.target.id === 'reportSuccessModal') closeModal('reportSuccessModal')">
    <div class="report-modal-content" style="max-width: 400px; text-align: center;">
        <div style="padding: 20px;">
            <i class='bx bx-check-circle' style="font-size: 4em; color: #00A86B; margin-bottom: 10px;"></i>
            <h3 style="border-bottom: none; margin-bottom: 10px;">Report Submitted!</h3>
            <p id="success-message" style="color: #555; font-size: 1em;">
                Your issue report for Ref: <span id="submitted-ref-number" style="color: #B32133; font-weight: 700;"></span> has been received and is under review.
            </p>
            <button onclick="closeModal('reportSuccessModal')" class="primary-btn report-confirm-btn">Got It</button>
        </div>
    </div>
</div>

<div id="requiredFieldsModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Please fill out all required fields.</h3>
        <div class="modal__actions">
            <button class="btn btn--primary" id="confirmRequiredFields" onclick="closeModal('requiredFieldsModal')">OK</button>
        </div>
    </div>
</div>

<div id="cancelSuccessModal" class="cancel-appointment-modal" onclick="if(event.target.id === 'cancelSuccessModal') closeModal('cancelSuccessModal')">
    <div class="cancel-modal-content">
        <span class="close-btn-x" onclick="closeModal('cancelSuccessModal')">&times;</span>
        <i class='bx bx-check-circle cancel-icon' style="color: #00A86B;"></i>
        <h3 style="border-bottom: none;">Appointment Cancelled!</h3>
        <p id="cancel-success-message">
            The appointment for Ref: 
            <strong id="cancelled-ref-number"></strong> has been successfully cancelled.
            <br>
            A notification has been sent to your registered email address.
        </p>
        <div class="modal__actions">
            <button onclick="closeModal('cancelSuccessModal')" class="primary-cancel-btn" style="background-color: #00A86B; border-color: #00A86B;">Got It</button>
        </div>
    </div>
</div>

<div id="cancelModal" class="cancel-appointment-modal" onclick="if(event.target.id === 'cancelModal') closeModal('cancelModal')">
    <div class="cancel-modal-content">
        <span class="close-btn-x" onclick="closeModal('cancelModal')">&times;</span>
        <i class='bx bx-x-circle cancel-icon'></i>
        <h3>Cancel Appointment</h3>
        <p>
            Are you sure you want to cancel appointment for:
            <strong id="cancel-ref-number">ALZ-CC-2410-0016</strong>
            <br>
            This action cannot be undone.
        </p>
        <div class="modal__actions">
            <button id="keepAppointment" class="secondary-keep-btn" onclick="closeModal('cancelModal')">Keep</button>
            <button id="confirmCancel" class="primary-cancel-btn">Yes, Cancel</button>
        </div>
    </div>
</div>

<!-- Update Success Modal -->
<div id="updateSuccessModal" class="cancel-appointment-modal">
    <div class="cancel-modal-content">
        <span class="close-btn-x" onclick="closeModal('updateSuccessModal')">&times;</span>
        <i class='bx bx-check-circle cancel-icon' style="color: #00A86B;"></i>
        <h3 style="border-bottom: none;">Booking Updated!</h3>
        <p>Your booking has been successfully updated.</p>
        <div class="modal__actions">
            <button onclick="location.reload()" class="primary-cancel-btn" style="background-color: #00A86B; border-color: #00A86B;">OK</button>
        </div>
    </div>
</div>

<script src="client_db.js"></script>
<script src="HIS_function.js"></script>

<script>
function openEditModal(button) {
    try {
        const bookingData = JSON.parse(button.getAttribute('data-booking'));
        console.log('Booking Data:', bookingData); // Debug log
        
        // Populate form fields
        document.getElementById('edit_booking_id').value = bookingData.booking_id;
        document.getElementById('edit_reference_no').value = bookingData.reference_no;
        document.getElementById('edit_status').value = bookingData.status;
        document.getElementById('edit_full_name').value = bookingData.full_name;
        document.getElementById('edit_email').value = '<?php echo $_SESSION['email'] ?? ''; ?>';
        document.getElementById('edit_phone').value = bookingData.phone;
        document.getElementById('edit_service_type').value = bookingData.service_type;
        document.getElementById('edit_client_type').value = bookingData.client_type;
        document.getElementById('edit_start_date').value = bookingData.start_date;
        document.getElementById('edit_service_time').value = bookingData.booking_time;
        document.getElementById('edit_duration').value = bookingData.duration;
        document.getElementById('edit_frequency').value = bookingData.frequency;
        document.getElementById('edit_preferred_day').value = bookingData.preferred_day || '';
        document.getElementById('edit_property_layout').value = bookingData.property_layout;
        document.getElementById('edit_address').value = bookingData.address;
        document.getElementById('edit_materials_needed').value = bookingData.materials_required;
        document.getElementById('edit_comments').value = bookingData.materials_description;
        
        // Display images if available
        const imagesPreview = document.getElementById('edit_images_preview');
        imagesPreview.innerHTML = '';
        if (bookingData.image_1) {
            imagesPreview.innerHTML += `<img src="${bookingData.image_1}" alt="Image 1">`;
        }
        if (bookingData.image_2) {
            imagesPreview.innerHTML += `<img src="${bookingData.image_2}" alt="Image 2">`;
        }
        if (bookingData.image_3) {
            imagesPreview.innerHTML += `<img src="${bookingData.image_3}" alt="Image 3">`;
        }
        
        // Show modal
        const modal = document.getElementById('editBookingModal');
        modal.style.display = 'flex';
        console.log('Modal displayed'); // Debug log
    } catch (error) {
        console.error('Error opening edit modal:', error);
        alert('Error loading booking data. Please try again.');
    }
}



function saveBookingChanges(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('editBookingForm'));
    
    fetch('update_recurring_booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('editBookingModal');
            document.getElementById('updateSuccessModal').style.display = 'flex';
        } else {
            alert('Error updating booking: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the booking.');
    });
    
    return false;
}

function toggleDropdown(button) {
    // Close all other dropdowns first
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (menu !== button.nextElementSibling) {
            menu.classList.remove('show');
        }
    });
    
    // Toggle current dropdown
    const dropdownMenu = button.nextElementSibling;
    dropdownMenu.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown-menu-container')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});



let currentBookingId = null;

function showCancelModal(refNo, bookingId) {
    currentBookingId = bookingId;
    document.getElementById('cancel-ref-number').innerText = refNo;
    const confirmCancelBtn = document.getElementById('confirmCancel');
    confirmCancelBtn.onclick = function() {
        cancelRecurringAppointment(refNo);
    };
    document.getElementById('cancelModal').style.display = 'flex'; 
}

function cancelRecurringAppointment(refNo) {
    const confirmBtn = document.getElementById('confirmCancel');
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Cancelling...';
    
    const formData = new FormData();
    formData.append('booking_id', currentBookingId);
    
    fetch('cancel_recurring_booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Yes, Cancel';
        
        if (data.success) {
            closeModal('cancelModal');
            document.getElementById('cancelled-ref-number').innerText = refNo;
            document.getElementById('cancelSuccessModal').style.display = 'flex';
            setTimeout(() => location.reload(), 2000);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Yes, Cancel';
        alert('Error occurred.');
    });
}

function sortAppointmentsByStatus(containerId, filterValue = 'default') {
    const container = document.getElementById(containerId);
    if (!container) return;

    const statusSortOrder = {
        'PENDING': 1,
        'ACTIVE': 2,
        'PAUSED': 3,
        'COMPLETED': 4,
        'CANCELLED': 5
    };

    const items = Array.from(container.querySelectorAll('.appointment-list-item'));
    const noAppointmentsMessage = container.querySelector('.no-appointments-message');
    const serviceName = noAppointmentsMessage ? noAppointmentsMessage.getAttribute('data-service-name') : 'appointments';

    let visibleCount = 0;
    const filter = filterValue.toUpperCase();

    items.forEach(item => {
        const itemPlanStatus = (item.getAttribute('data-plan-status') || '').toUpperCase();
        let isVisible = false;

        if (filter === 'DEFAULT') {
            isVisible = true;
        } else if (itemPlanStatus === filter) {
            isVisible = true;
        }

        item.style.display = isVisible ? 'flex' : 'none';
        if (isVisible) visibleCount++;
    });

    if (filter === 'DEFAULT') {
        const itemsToSort = items.filter(item => item.style.display !== 'none');
        itemsToSort.sort((a, b) => {
            const orderA = statusSortOrder[(a.getAttribute('data-plan-status') || '').toUpperCase()] || 999;
            const orderB = statusSortOrder[(b.getAttribute('data-plan-status') || '').toUpperCase()] || 999;
            return orderA - orderB;
        });
        if (noAppointmentsMessage) container.prepend(noAppointmentsMessage);
        itemsToSort.forEach(item => container.appendChild(item));
    }

    if (noAppointmentsMessage) {
        if (visibleCount === 0) {
            noAppointmentsMessage.style.display = 'block';
            if (filter !== 'DEFAULT') {
                let displayName = filter.charAt(0) + filter.slice(1).toLowerCase();
                noAppointmentsMessage.innerHTML = `No ${displayName} ${serviceName} plans found.`;
            } else {
                noAppointmentsMessage.innerHTML = `You have no ${serviceName} plans on record.`;
            }
        } else {
            noAppointmentsMessage.style.display = 'none';
        }
    }
}

function initializeStatusSortingOnLoad() {
    const containerIds = [
        'weekly-cleaning-list',
        'biweekly-cleaning-list',
        'monthly-cleaning-list'
    ];

    containerIds.forEach(id => {
        sortAppointmentsByStatus(id, 'default');
    });
}


document.addEventListener('DOMContentLoaded', initializeStatusSortingOnLoad);
</script>
</body>
</html>