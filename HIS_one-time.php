<?php
session_start();
require 'connection.php';

// ✅ Make sure the client is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

$user_email = $_SESSION['email'];

// ✅ Define service categories for organizing bookings
$serviceTypes = [
    'Checkout Cleaning' => [],
    'In-House Cleaning' => [],
    'Refresh Cleaning' => [],
    'Deep Cleaning' => []
];

// ✅ Fetch only this client's One-Time bookings
$sql = "SELECT * FROM bookings WHERE booking_type = 'One-Time' AND email = ? ORDER BY service_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

// ✅ Build the result array
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Safely handle missing or invalid service_type
        $service_type = $row['service_type'] ?? 'Checkout Cleaning';
        if (!isset($serviceTypes[$service_type])) {
            $service_type = 'Checkout Cleaning';
        }

        // ✅ Compute estimated price based on duration and materials_provided
        $estimated_price = 0;
        $materials_provided = $row['materials_provided'] ?? '';
        $duration = floatval($row['duration'] ?? 0);

        if (preg_match('/(\d+(?:\.\d+)?)\s*AED\s*\/\s*hr/i', $materials_provided, $matches)) {
            $hourly_rate = floatval($matches[1]);
            $estimated_price = $hourly_rate * $duration;
        }

        // ✅ Push the booking into its respective service type array
        $serviceTypes[$service_type][] = [
            'booking_id' => $row['id'],
            'reference_no' => 'ALZ-' . str_pad($row['id'], 6, '0', STR_PAD_LEFT),
            'booking_date' => $row['service_date'] ?? '',
            'booking_time' => $row['service_time'] ?? '',
            'duration' => $row['duration'] ?? '0',
            'address' => $row['address'] ?? '',
            'client_type' => $row['client_type'] ?? 'N/A',
            'status' => strtoupper($row['status'] ?? 'PENDING'),
            'service_type' => $row['booking_type'] ?? 'One-Time',
            'property_layout' => $row['property_type'] ?? '',
            'materials_required' => $row['materials_provided'] ?? 'No - 35 AED / hr', // ✅ FIXED: Now uses materials_provided
            'materials_description' => $row['materials_needed'] ?? '', // ✅ FIXED: Now uses materials_needed
            'additional_request' => $row['comments'] ?? '', // ✅ FIXED: Now uses comments
            'image_1' => $row['media1'] ?? '',
            'image_2' => $row['media2'] ?? '',
            'image_3' => $row['media3'] ?? '',
            'estimated_price' => $estimated_price,
            'final_price' => $row['final_price'] ?? 0,
            'driver_name' => $row['drivers'] ?? '',
            'cleaners_names' => $row['cleaners'] ?? ''
        ];
    }
} else {
    echo "<p style='text-align:center;color:gray;'>No One-Time bookings found.</p>";
}

$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ALAZIMA - One-Time History</title>
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="client_db.css">
<link rel="stylesheet" href="HIS_design.css">

<style>
/* Temporary CSS for the new Cancel Modal */
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

.action-btn.edit-plan-btn{
    padding: 8px 12px; font-weight: bold; cursor: pointer; border-radius: 6px; text-align: center; transition: all 0.3s; text-decoration: none; display: flex; align-items: center; gap: 5px; font-size: 0.9em; background-color: #0056b3; border: 2px solid #0056b3; color: white;
}
.action-btn.edit-plan-btn:hover { background-color: #0062cc; border-color: #0062cc; }
.action-btn.sessions-btn {
    padding: 8px 12px; font-weight: bold; cursor: pointer; border-radius: 6px; text-align: center; transition: all 0.3s; text-decoration: none; display: flex; align-items: center; gap: 5px; font-size: 0.9em; background-color: #008080; border: 2px solid #008080; color: white;
}
.action-btn.sessions-btn:hover { background-color: #009999; border-color: #009999; }
.dropdown-menu .whatsapp-chat-link i { color: #25D366; }
.appointment-details .ref-no-detail { margin-bottom: 15px; }
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

.staff-details-container {
    background-color: #f7f9fc;
    margin-top: 15px;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #e0e6ed;
    font-size: 0.95em;
    color: #333;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.staff-details-container h4 {
    font-size: 1.1em;
    font-weight: bold;
    color: #004085; 
    margin-bottom: 10px;
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 5px;
    padding-bottom: 5px;
    border-bottom: 1px solid #e0e6ed;
}

.staff-details-container p {
    margin-bottom: 5px;
    display: flex;
    align-items: flex-start;
    line-height: 1.4;
}

.staff-details-container p i {
    font-size: 1.2em;
    margin-right: 8px;
    color: #0035b3;
}

.staff-details-container p strong {
    font-weight: 600;
    margin-right: 5px;
    min-width: 65px;
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
                        <li class="menu__item"><a href="BA_one-time.php" class="menu__link">One-Time Service</a></li>
                        <li class="menu__item"><a href="BA_recurring.php" class="menu__link">Recurring Service</a></li>
                    </ul>
                </li>
                
                <li class="menu__item has-dropdown open">
                    <a href="#" class="menu__link active-parent" data-content="history-parent"><i class='bx bx-history'></i> History <i class='bx bx-chevron-down arrow-icon'></i></a>
                    <ul class="dropdown__menu">
                        <li class="menu__item"><a href="HIS_one-time.php" class="menu__link active">One-Time Service</a></li>
                        <li class="menu__item"><a href="HIS_recurring.php" class="menu__link">Recurring Service</a></li>
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
                    <i class='bx bx-history'></i> One-Time Service History
                </h2>
                <p class="page-description">
                    Here's a detailed list of all your one-time cleaning service bookings, including completed and cancelled jobs.
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
                            Once your booking is <strong>Confirmed</strong>, you can no longer edit or cancel it directly.
                            <ul>
                                <li>Contact us via <strong>WhatsApp</strong> using the <strong>Chat</strong> button (<i class='bx bxl-whatsapp'></i>) for any changes or concerns.</li>
                            </ul>
                        </li>
                        <li>
                            Feedback and ratings can only be submitted once the appointment is <strong>Completed</strong>.
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
                <button class="tab-button active" onclick="openTab(event, 'checkout-cleaning')">
                    <i class='bx bx-check-shield'></i> Checkout Cleaning
                </button>
                <button class="tab-button" onclick="openTab(event, 'in-house-cleaning')">
                    <i class='bx bx-home-heart'></i> In-House Cleaning
                </button>
                <button class="tab-button" onclick="openTab(event, 'refresh-cleaning')">
                    <i class='bx bx-wind'></i> Refresh Cleaning
                </button>
                <button class="tab-button" onclick="openTab(event, 'deep-cleaning')">
                    <i class='bx bx-water'></i> Deep Cleaning
                </button>
            </div>
        </div>

        <?php
        // Function to render appointment item
        function renderAppointmentItem($booking) {
            $formatted_date = date('F d, Y', strtotime($booking['booking_date']));
            $formatted_time = date('g:i A', strtotime($booking['booking_time']));
            
            $search_terms = implode(' ', [
                $booking['reference_no'],
                $formatted_date,
                $formatted_time,
                $booking['address'],
                $booking['client_type'],
                $booking['status']
            ]);
            
            // Status icons
            $status_icons = [
                'PENDING' => '<i class="bx bx-hourglass"></i>',
                'CONFIRMED' => '<i class="bx bx-calendar-check"></i>',
                'ONGOING' => '<i class="bx bx-loader-circle"></i>',
                'COMPLETED' => '<i class="bx bx-check-circle"></i>',
                'CANCELLED' => '<i class="bx bx-x-circle"></i>',
                'NO SHOW' => '<i class="bx bx-user-minus"></i>'
            ];
            
            $status_class = strtolower(str_replace(' ', '-', $booking['status']));
            $status_icon = $status_icons[$booking['status']] ?? '';
            
            // Generate buttons based on status
           $buttons = '';

switch ($booking['status']) {
    case 'PENDING':
        $buttons = '
            <a href="javascript:void(0)" class="action-btn view-details-btn" 
               onclick="showDetailsModal(this.closest(\'.appointment-list-item\'))">
               <i class="bx bx-show"></i> View Details</a>

            <a href="EDIT_one-time.php?booking_id=' . $booking['booking_id'] . '" 
               class="action-btn feedback-btn">
               <i class="bx bx-edit"></i> Edit</a>

            <div class="dropdown-menu-container">
                <button class="more-options-btn" onclick="toggleDropdown(this)">
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link">
                            <i class="bx bxl-whatsapp"></i> Chat on WhatsApp
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="cancel-link" 
                          onclick="showCancelModal(\'' . $booking['reference_no'] . '\', ' . $booking['booking_id'] . ')">
                           <i class="bx bx-x-circle" style="color: #B32133;"></i> Cancel
                        </a>
                    </li>
                </ul>
            </div>
        ';
        break;

    case 'CONFIRMED':
    case 'ONGOING':
        $buttons = '
            <a href="javascript:void(0)" class="action-btn view-details-btn" 
               onclick="showDetailsModal(this.closest(\'.appointment-list-item\'))">
               <i class="bx bx-show"></i> View Details</a>

            <a href="https://wa.me/971529009188" target="_blank" 
               class="action-btn whatsapp-chat-btn">
               <i class="bx bxl-whatsapp"></i> Chat on WhatsApp
            </a>
        ';
        break;

    case 'COMPLETED':
        $buttons = '
            <a href="javascript:void(0)" class="action-btn view-details-btn" 
               onclick="showDetailsModal(this.closest(\'.appointment-list-item\'))">
               <i class="bx bx-show"></i> View Details</a>

            <a href="FR_one-time.php?booking_id=' . $booking['booking_id'] . '" 
               class="action-btn feedback-btn">
               <i class="bx bx-star"></i> Leave Feedback</a>

            <div class="dropdown-menu-container">
                <button class="more-options-btn" onclick="toggleDropdown(this)">
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link">
                            <i class="bx bxl-whatsapp"></i> Chat on WhatsApp
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="report-link" 
                           onclick="showReportModal(this)">
                           <i class="bx bx-error-alt"></i> Report Issue
                        </a>
                    </li>
                </ul>
            </div>
        ';
        break;

    default:
        $buttons = '
            <a href="javascript:void(0)" class="action-btn view-details-btn" 
               onclick="showDetailsModal(this.closest(\'.appointment-list-item\'))">
               <i class="bx bx-show"></i> View Details</a>

            <a href="EDIT_one-time.php?booking_id=' . $booking['booking_id'] . '" 
               class="action-btn feedback-btn">
               <i class="bx bx-edit"></i> Edit</a>

            <div class="dropdown-menu-container">
                <button class="more-options-btn" onclick="toggleDropdown(this)">
                    <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="https://wa.me/971529009188" target="_blank" class="whatsapp-chat-link">
                            <i class="bx bxl-whatsapp"></i> Chat on WhatsApp
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="report-link" 
                           onclick="showReportModal(this)">
                           <i class="bx bx-error-alt"></i> Report Issue
                        </a>
                    </li>
                </ul>
            </div>
        ';
        break;
}

            
            // Staff details (if available)
            $staff_details = '';
            if ($booking['driver_name'] || $booking['cleaners_names']) {
                $staff_details = '
                    <div class="staff-details-container full-width-detail">
                        <h4><i class=\'bx bx-id-card\'></i> Assigned Team</h4>';
                
                if ($booking['driver_name']) {
                    $staff_details .= '<p><i class=\'bx bx-car\'></i> <strong>Driver:</strong> '.$booking['driver_name'].'</p>';
                }
                
                if ($booking['cleaners_names']) {
                    $staff_details .= '<p><i class=\'bx bx-group\'></i> <strong>Cleaners:</strong> '.$booking['cleaners_names'].'</p>';
                }
                
                $staff_details .= '</div>';
            }
            
            // Price display
            $price_label = $booking['status'] === 'COMPLETED' ? 'Final Price' : 'Estimated Price';
            $price = $booking['status'] === 'COMPLETED' && $booking['final_price'] ? $booking['final_price'] : $booking['estimated_price'];
            
            echo '
            <div class="appointment-list-item" 
                data-date="'.$booking['booking_date'].'" 
                data-time="'.$booking['booking_time'].'"
                data-status="'.$booking['status'].'"
                data-search-terms="'.$search_terms.'"
                data-property-layout="'.htmlspecialchars($booking['property_layout']).'"
                data-materials-required="'.$booking['materials_required'].'"
                data-materials-description="'.htmlspecialchars($booking['materials_description']).'"
                data-additional-request="'.htmlspecialchars($booking['additional_request']).'"
                data-image-1="'.($booking['image_1'] ?? '').'"
                data-image-2="'.($booking['image_2'] ?? '').'"
                data-image-3="'.($booking['image_3'] ?? '').'">
                
                <div class="button-group-top">
                    '.$buttons.'
                </div>
                
                <div class="appointment-details">
                    <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">'.$booking['reference_no'].'</span></p>
                    <p class="full-width-detail"><i class=\'bx bx-calendar-check\'></i> <strong>Date:</strong> '.$formatted_date.'</p>
                    <p><i class=\'bx bx-time\'></i> <strong>Time:</strong> '.$formatted_time.'</p>
                    <p class="duration-detail"><i class=\'bx bx-stopwatch\'></i> <strong>Duration:</strong> '.$booking['duration'].' hours</p>
                    <p class="full-width-detail"><i class=\'bx bx-map-alt\'></i> <strong>Address:</strong> '.$booking['address'].'</p>
                    <hr class="divider full-width-detail">
                    <p><i class=\'bx bx-building-house\'></i> <strong>Client Type:</strong> '.$booking['client_type'].'</p>
                    <p class="service-type-detail"><i class=\'bx bx-wrench\'></i> <strong>Service Type:</strong> '.$booking['service_type'].'</p>
                    <p class="full-width-detail status-detail">
                        <strong>Status:</strong>
                        <span class="status-tag '.$status_class.'">'.$status_icon.' '.$booking['status'].'</span>
                    </p>
                    '.$staff_details.'
                    <p class="price-detail">'.$price_label.': <span class="aed-color">AED '.$price.'</span></p>
                </div>
            </div>';
        }
        
        // Render each service type tab
        $tab_ids = [
            'Checkout Cleaning' => 'checkout-cleaning',
            'In-House Cleaning' => 'in-house-cleaning',
            'Refresh Cleaning' => 'refresh-cleaning',
            'Deep Cleaning' => 'deep-cleaning'
        ];
        
        $first_tab = true;
        foreach ($tab_ids as $service_name => $tab_id) {
            $display = $first_tab ? 'block' : 'none';
            $list_id = $tab_id . '-list';
            
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
                        <option value="default" selected>Sort by Status</option>
                        <option value="PENDING">Pending</option>
                        <option value="CONFIRMED">Confirmed</option>
                        <option value="ONGOING">Ongoing</option>
                        <option value="COMPLETED">Completed</option>
                        <option value="CANCELLED">Cancelled</option>
                        <option value="NO SHOW">No Show</option>
                    </select>
                    
                    <div class="search-container">
                        <i class=\'bx bx-search\'></i>
                        <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, \''.$list_id.'\')">
                    </div>
                </div>
            ';
            
            echo '<div class="appointment-list-container" id="'.$list_id.'">';
            echo '<div class="no-appointments-message" data-service-name="'.$service_name.'"></div>';
            
            // Render appointments for this service type
            if (isset($serviceTypes[$service_name]) && count($serviceTypes[$service_name]) > 0) {
                foreach ($serviceTypes[$service_name] as $booking) {
                    renderAppointmentItem($booking);
                }
            }
            
            echo '</div></div>';
            $first_tab = false;
        }
        ?>

    </main>
</div> 

<a href="#header" id="backToTopBtn" title="Back to Top"><i class='bx bx-up-arrow-alt'></i> Back to Top</a>

<!-- Modals (keeping existing modal code) -->
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
            <button onclick="applyCustomRange(this.getAttribute('data-list-id'))" data-list-id="checkout-cleaning-list" class="primary-btn">Apply</button>
            <button onclick="closeModal('datePickerModal')" class="secondary-btn">Cancel</button>
        </div>
    </div>
</div>

<div class="modal" id="detailsModal" onclick="if(event.target.id === 'detailsModal') closeModal('detailsModal')">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('detailsModal')">&times;</span> 
        <h3><i class='bx bx-file-text'></i> Appointment Details</h3>
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

<div id="requiredFieldsModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Please fill out all required fields.</h3>
        <div class="modal__actions">
            <button class="btn btn--primary" id="confirmRequiredFields" onclick="closeModal('requiredFieldsModal')">OK</button>
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

<script>

let currentBookingId = null;

function showCancelModal(refNo, bookingId) {
    currentBookingId = bookingId;
    document.getElementById('cancel-ref-number').innerText = refNo;
    const confirmCancelBtn = document.getElementById('confirmCancel');
    confirmCancelBtn.onclick = function() {
        cancelAppointment(refNo);
    };
    document.getElementById('cancelModal').style.display = 'flex'; 
}

function cancelAppointment(refNo) {
    const confirmBtn = document.getElementById('confirmCancel');
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Cancelling...';
    
    const formData = new FormData();
    formData.append('booking_id', currentBookingId);
    
    fetch('cancel_booking.php', {
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

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}


function sortAppointmentsByStatus(containerId, filterStatus = 'default') {
    const container = document.getElementById(containerId);
    if (!container) return;

    const statusOrder = {
        'PENDING': 1,
        'CONFIRMED': 2,
        'ONGOING': 3,
        'COMPLETED': 4,
        'CANCELLED': 5,
        'NO SHOW': 6
    };

    const items = Array.from(container.querySelectorAll('.appointment-list-item'));
    const noAppointmentsMessage = container.querySelector('.no-appointments-message');

    let visibleCount = 0;
    const filter = filterStatus.toUpperCase(); // normalize filter

    items.forEach(item => {
        const itemStatus = (item.getAttribute('data-status') || '').toUpperCase(); // normalize item status
        const isVisible = (filter === 'DEFAULT' || filter === itemStatus);
        item.style.display = isVisible ? 'grid' : 'none';
        if (isVisible) visibleCount++;
    });

    if (filter === 'DEFAULT') {
        const itemsToSort = items.filter(item => item.style.display !== 'none');
        itemsToSort.sort((a, b) => {
            const statusA = (a.getAttribute('data-status') || '').toUpperCase();
            const statusB = (b.getAttribute('data-status') || '').toUpperCase();
            const orderA = statusOrder[statusA] || 999;
            const orderB = statusOrder[statusB] || 999;
            return orderA - orderB;
        });
        if (noAppointmentsMessage) container.appendChild(noAppointmentsMessage);
        itemsToSort.forEach(item => container.appendChild(item));
    }

    if (noAppointmentsMessage) {
        if (visibleCount === 0) {
            noAppointmentsMessage.style.display = 'block';
            const serviceName = noAppointmentsMessage.getAttribute('data-service-name');
            if (filter !== 'DEFAULT') {
                noAppointmentsMessage.innerHTML = `No ${filter.charAt(0) + filter.slice(1).toLowerCase()} appointments found for ${serviceName}.`;
            } else {
                noAppointmentsMessage.innerHTML = `You have no appointments on record for ${serviceName}.`;
            }
        } else {
            noAppointmentsMessage.style.display = 'none';
        }
    }
}


function initializeStatusSortingOnLoad() {
    const containerIds = [
        'checkout-cleaning-list',
        'in-house-cleaning-list',
        'refresh-cleaning-list',
        'deep-cleaning-list'
    ];

    containerIds.forEach(id => {
        sortAppointmentsByStatus(id, 'default');
    });
}

document.addEventListener('DOMContentLoaded', initializeStatusSortingOnLoad);
</script> 
<script src="client_db.js"></script>
<script src="HIS_function.js"></script>
</body>
</html>