<?php
session_start();
require 'connection.php';

// Get logged-in client email
$client_email = $_SESSION['email'] ?? null;

// Fetch completed recurring sessions (you'll need a sessions table)
// For now, we'll fetch recurring bookings with completed sessions
$sql = "SELECT * FROM bookings 
WHERE booking_type = 'Recurring' ";


if ($client_email) {
    $sql .= "AND email = '" . $conn->real_escape_string($client_email) . "' ";
}
$sql .= "ORDER BY service_date DESC, service_time DESC";

$result = $conn->query($sql);

// Store bookings by frequency
$bookings_by_frequency = [
    'Weekly' => [],
    'Bi-Weekly' => [],
    'Monthly' => []
];

// Populate bookings
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $frequency = $row['frequency'] ?? 'Weekly';
        
        // Normalize frequency
        if (strtolower($frequency) === 'biweekly' || strtolower($frequency) === 'bi-weekly') {
            $frequency = 'Bi-Weekly';
        }
        
        if (!isset($bookings_by_frequency[$frequency])) {
            $frequency = 'Weekly';
        }
        
        // Calculate estimated price
        $estimated_price = 0;
        $materials_provided = $row['materials_provided'] ?? '';
        $duration = floatval($row['duration'] ?? 0);
        if (preg_match('/(\d+(?:\.\d+)?)\s*AED\s*\/\s*hr/i', $materials_provided, $matches)) {
            $hourly_rate = floatval($matches[1]);
            $estimated_price = $hourly_rate * $duration;
        }
        
        // Check if has rating
        $hasRating = isset($row['rating_stars']) && !empty($row['rating_stars']);
        
        $row['estimated_price'] = $estimated_price;
        $row['has_rating'] = $hasRating;
        
        $bookings_by_frequency[$frequency][] = $row;
    }
}

// Helper functions
function formatDate($date) {
    return empty($date) ? 'N/A' : date('F d, Y', strtotime($date));
}

function formatTime($time) {
    return empty($time) ? 'N/A' : date('g:i A', strtotime($time));
}

function generateRecurringRefNo($id, $frequency, $date) {
    $prefix = '';
    $freq_lower = strtolower($frequency);
    
    if ($freq_lower === 'weekly') {
        $prefix = 'WK';
    } elseif ($freq_lower === 'bi-weekly' || $freq_lower === 'biweekly') {
        $prefix = 'BWK';
    } elseif ($freq_lower === 'monthly') {
        $prefix = 'MTH';
    } else {
        $prefix = 'REC';
    }
    
    $dateStr = date('ym', strtotime($date));
    return "ALZ-{$prefix}-{$dateStr}-" . str_pad($id, 4, '0', STR_PAD_LEFT);
}

// Function to render recurring booking cards
// Function to render recurring booking cards
function renderRecurringBookingCard($booking) {
    // Generate reference number using RECURRING function
    $refNo = generateRecurringRefNo($booking['id'], $booking['frequency'] ?? 'Weekly', $booking['service_date']);

    // Check if has rating
    $hasRating = isset($booking['rating_stars']) && !empty($booking['rating_stars']);

    // Check if has issue
    $hasIssue = isset($booking['issue_type']) && !empty($booking['issue_type']);

    // Estimated price (for recurring it's already calculated)
    $estimatedPrice = isset($booking['estimated_price']) ? $booking['estimated_price'] : 0;

    $status = $booking['status'] ?? 'Pending';
    $statusClass = 'overall-' . strtolower(str_replace(' ', '-', $status));
    $statusIcons = [
        'Pending' => '<i class="bx bx-time-five"></i>',
        'ACTIVE' => '<i class="bx bx-play-circle"></i>',
        'PAUSED' => '<i class="bx bx-pause-circle"></i>',
        'COMPLETED' => '<i class="bx bx-check-double"></i>',
        'CANCELLED' => '<i class="bx bx-x-circle"></i>'
    ];
    $statusIcon = $statusIcons[$status] ?? '<i class="bx bx-help-circle"></i>';
    
    $searchTerms = $refNo . ' ' . formatDate($booking['service_date']) . ' ' . formatTime($booking['service_time']) . ' ' .
                   htmlspecialchars($booking['address'] ?? '') . ' ' . htmlspecialchars($booking['client_type'] ?? '') .
                   ' ' . $status . ($hasRating ? ' RATED' : '');
    ?>
    <div class="appointment-list-item" 
    data-booking-id="<?= htmlspecialchars($booking['id']) ?>"
    data-date="<?= htmlspecialchars($booking['service_date']) ?>" 
    data-start-date="<?= htmlspecialchars($booking['start_date'] ?? $booking['service_date']) ?>"
    data-end-date="<?= htmlspecialchars($booking['end_date'] ?? '2025-12-31') ?>" 
    data-time="<?= htmlspecialchars($booking['service_time']) ?>"
    data-duration="<?= htmlspecialchars($booking['duration'] ?? 'N/A') ?>"
    data-client-type="<?= htmlspecialchars($booking['client_type'] ?? 'N/A') ?>"
    data-service-type="<?= htmlspecialchars($booking['service_type'] ?? 'N/A') ?>"
    data-address="<?= htmlspecialchars($booking['address'] ?? 'N/A') ?>"
    data-search-terms="<?= htmlspecialchars($searchTerms) ?>"
    data-property-layout="<?= htmlspecialchars($booking['property_type'] ?? 'N/A') ?>"
    data-materials-required="<?= htmlspecialchars($booking['materials_provided'] ?? 'No') ?>"
    data-materials-description="<?= htmlspecialchars($booking['materials_needed'] ?? 'N/A') ?>"
    data-additional-request="<?= htmlspecialchars($booking['comments'] ?? 'None') ?>"
    data-image-1="<?= htmlspecialchars($booking['media1'] ?? '') ?>"
    data-image-2="<?= htmlspecialchars($booking['media2'] ?? '') ?>"
    data-image-3="<?= htmlspecialchars($booking['media3'] ?? '') ?>"
    data-has-feedback="<?= $hasRating ? 'true' : 'false' ?>"
    data-rating-stars="<?= htmlspecialchars($booking['rating_stars'] ?? '0') ?>" 
    data-rating-feedback="<?= htmlspecialchars($booking['rating_comment'] ?? 'No feedback provided.') ?>"
    data-plan-status="<?= htmlspecialchars($status) ?>"
    data-ref-no="<?= htmlspecialchars($refNo) ?>"
    data-frequency="<?= htmlspecialchars($booking['frequency'] ?? 'N/A') ?>"
    data-sessions="<?= htmlspecialchars($booking['estimated_sessions'] ?? 0) ?>"
    data-sessions-completed="<?= htmlspecialchars($booking['sessions_completed'] ?? 0) ?>"
    data-total-sessions="<?= htmlspecialchars($booking['total_sessions'] ?? 0) ?>"
    data-has-issue="<?= $hasIssue ? 'true' : 'false' ?>"
    data-issue-type="<?= htmlspecialchars($booking['issue_type'] ?? '') ?>"
    data-issue-description="<?= htmlspecialchars($booking['issue_description'] ?? '') ?>"
    data-submission-date="<?= htmlspecialchars($booking['issue_report_date'] ?? '') ?>"
    data-submission-time="<?= htmlspecialchars($booking['issue_report_time'] ?? '') ?>"
    data-photo1="<?= htmlspecialchars($booking['issue_photo1'] ?? '') ?>"
    data-photo2="<?= htmlspecialchars($booking['issue_photo2'] ?? '') ?>"
    data-photo3="<?= htmlspecialchars($booking['issue_photo3'] ?? '') ?>">
    
    <div class="button-group-top">
        <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showRecurringDetailsModal(this.closest('.appointment-list-item'))">
            <i class='bx bx-show'></i> View Details
        </a>

        <?php if ($hasRating): ?>
        <button type="button" class="action-btn feedback-btn" onclick="showViewRatingModal(this.closest('.appointment-list-item'))">
            <i class='bx bx-star'></i> View Rating
        </button>
        <?php else: ?>
        <a href="FR_recurring_form.php?id=<?= $booking['id']; ?>&action=leave" class="action-btn feedback-btn">
            <i class='bx bx-star'></i> Rate Plan
        </a>
        <?php endif; ?>

        <div class="dropdown-menu-container">
            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
            <ul class="dropdown-menu">
                <?php if ($hasRating): ?>
                
                <?php endif; ?>
                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
              
                
                <?php if ($hasIssue): ?>
                <li><a href="javascript:void(0)" class="report-link view-issue-link" onclick="viewReportedIssue(this.closest('.appointment-list-item'))"><i class='bx bx-error-alt'></i> View Reported Issue</a></li>
                <?php else: ?>
                <li>
                    <a href="#" 
                       class="report-link" 
                       onclick="event.preventDefault(); showReportModal(this); return false;"
                       data-booking-id="<?= htmlspecialchars($booking['id']) ?>"
                       data-ref-no="<?= htmlspecialchars($refNo) ?>"
                       data-date="<?= htmlspecialchars($booking['service_date']) ?>"
                       data-time="<?= htmlspecialchars($booking['service_time']) ?>">
                       <i class='bx bx-error-alt'></i> Report Issue
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="appointment-details">
        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value"><?= htmlspecialchars($refNo) ?></span></p>
        <p><i class='bx bx-calendar-check'></i> <strong>Start Date:</strong> <?= formatDate($booking['start_date']) ?></p>
        <p class="end-date-detail"><i class='bx bx-calendar-check'></i> <strong>End Date:</strong> <?= formatDate($booking['end_date'] ?? '') ?></p>
        <p><i class='bx bx-time'></i> <strong>Time:</strong> <?= formatTime($booking['service_time']) ?></p>
        <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> <?= htmlspecialchars($booking['duration'] ?? 'N/A') ?> hours</p>
        <p class="frequency-detail"><i class='bx bx-sync'></i> <strong>Frequency:</strong> <?= htmlspecialchars($booking['frequency'] ?? 'N/A') ?></p>
        <p class="sessions-detail"><i class='bx bx-list-ol'></i> <strong>Sessions:</strong> <?= htmlspecialchars($booking['estimated_sessions'] ?? 0) ?> </p>
        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> <?= htmlspecialchars($booking['address'] ?? 'N/A') ?></p>
        <hr class="divider full-width-detail">
        <p><i class='bx bx-building-house'></i> <strong>Client Type:</strong> <?= htmlspecialchars($booking['client_type'] ?? 'N/A') ?></p>
        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> <?= htmlspecialchars($booking['service_type'] ?? 'N/A') ?></p>
        
        <p class="full-width-detail status-detail">
            <strong>Plan Status:</strong>
            <span class="overall-plan-tag <?= $statusClass ?>"><?= $statusIcon ?> <?= htmlspecialchars($status) ?></span>
            <?php if ($hasRating): ?>
            <span class="status-tag rated-style" style="background-color: #fce899; color: #9c6c00; border: 1px solid #f9d857;">
                <i class='bx bx-star'></i> RATED
            </span>
            <?php else: ?>
            <span class="status-tag pending"><i class='bx bx-hourglass'></i> NOT YET RATED</span>
            <?php endif; ?>
        </p>

        <?php if (!empty($booking['assigned_driver']) || !empty($booking['assigned_cleaners'])): ?>
        <div class="staff-details-container full-width-detail">
            <h4><i class='bx bx-id-card'></i> Assigned Team</h4>
            <?php if (!empty($booking['assigned_driver'])): ?>
            <p><i class='bx bx-car'></i> <strong>Driver:</strong> <?= htmlspecialchars($booking['assigned_driver']) ?></p>
            <?php endif; ?>
            <?php if (!empty($booking['assigned_cleaners'])): ?>
            <p><i class='bx bx-group'></i> <strong>Cleaners:</strong> <?= htmlspecialchars($booking['assigned_cleaners']) ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <p class="price-detail">
            Estimated Price: <span class="aed-color">AED <?= number_format($estimatedPrice, ) ?></span>
        </p>
    </div>
    </div>
    <?php
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ALAZIMA - Recurring Feedback/Ratings</title>
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="client_db.css"> 
<link rel="stylesheet" href="HIS_design.css">
<link rel="stylesheet" href="FR_design.css">
<link rel="stylesheet" href="FR_design2.css">

<style>
    /* ===== Force ALL modals to center on screen ===== */
    
.modal {
    position: fixed !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    z-index: 99999 !important;
    margin: 0 !important;
}


/* Dark background overlay */
.modal-overlay {
    position: fixed !important;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.45);
    z-index: 99998;
}

/* Prevent modal from sticking to left */
.modal-content {
    margin: auto !important;
}

.service-type-filter-dropdown {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 0.8em;
    cursor: pointer;
    background-color: #fff;
    width: 170px;
    height: 40px;
    flex-shrink: 0;
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
.modal {
    position: fixed !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    z-index: 99999 !important;
}
.modal-overlay {
    position: fixed !important;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    z-index: 99998;
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
                
                <li class="menu__item has-dropdown">
                    <a href="#" class="menu__link" data-content="history-parent"><i class='bx bx-history'></i> History <i class='bx bx-chevron-down arrow-icon'></i></a>
                    <ul class="dropdown__menu">
                        <li class="menu__item"><a href="HIS_one-time.php" class="menu__link">One-Time Service</a></li>
                        <li class="menu__item"><a href="HIS_recurring.php" class="menu__link">Recurring Service</a></li>
                    </ul>
                </li>
                
                <li class="menu__item has-dropdown open">
                    <a href="#" class="menu__link active-parent" data-content="feedback-parent"><i class='bx bx-star'></i> Feedback/Ratings <i class='bx bx-chevron-down arrow-icon'></i></a>
                    <ul class="dropdown__menu">
                        <li class="menu__item"><a href="FR_one-time.php" class="menu__link">One-Time Service</a></li>
                        <li class="menu__item"><a href="FR_recurring.php" class="menu__link active">Recurring Service</a></li>
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
                    <i class='bx bx-star'></i> Recurring Service Feedback/Ratings
                </h2>
                <p class="page-description">
                    List of all your recurring cleaning service plans where you can leave or edit feedback and ratings for completed sessions.
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
                            Feedback and Ratings can be given for your recurring service plans.
                        </li>
                        <li>
                            Please rate both the service quality and the assigned employees.
                            <ul>
                                <li>Be honest and specific — your feedback helps us improve our services.</li>
                                <li>Once submitted, you can still edit your feedback if needed.</li>
                            </ul>
                        </li>
                        <li>For any major concerns, kindly contact us via <strong>WhatsApp</strong> using the <strong>Chat</strong> button. (<i class='bx bxl-whatsapp'></i>)</li>
                        <li>Thank you for trusting us and our services!</li>
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
                 <!-- <button class="tab-button" onclick="openTab(event, 'issues-concern')">
                    <i class='bx bx-error-alt'></i> Issues and Concern
                </button> -->
            </div>
        </div>

        

        <!-- Weekly Cleaning Tab -->
        <div id="weekly-cleaning" class="tab-content" style="display: block;">
            <div class="filter-controls-tab">
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'weekly-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'weekly-cleaning-list')">
                </div>
            </div>

            <div class="appointment-list-container" id="weekly-cleaning-list">
                <div class="no-appointments-message" data-service-name="Weekly Cleaning"></div> 
                
                <?php 
                if (count($bookings_by_frequency['Weekly']) > 0) {
                    foreach ($bookings_by_frequency['Weekly'] as $booking) {
                        renderRecurringBookingCard($booking);
                    }
                }
                ?>
            </div>
        </div>

        <!-- Bi-Weekly Cleaning Tab -->
        <div id="biweekly-cleaning" class="tab-content" style="display: none;">
            <div class="filter-controls-tab">
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'biweekly-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'biweekly-cleaning-list')">
                </div>
            </div>

            <div class="appointment-list-container" id="biweekly-cleaning-list">
                <div class="no-appointments-message" data-service-name="Bi-Weekly Cleaning"></div>
                
                <?php 
                if (count($bookings_by_frequency['Bi-Weekly']) > 0) {
                    foreach ($bookings_by_frequency['Bi-Weekly'] as $booking) {
                        renderRecurringBookingCard($booking);
                    }
                }
                ?>
            </div>
        </div>

        <!-- Monthly Cleaning Tab -->
        <div id="monthly-cleaning" class="tab-content" style="display: none;">
            <div class="filter-controls-tab">
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'monthly-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'monthly-cleaning-list')">
                </div>
            </div>

            <div class="appointment-list-container" id="monthly-cleaning-list">
                <div class="no-appointments-message" data-service-name="Monthly Cleaning"></div>
                
                <?php 
                if (count($bookings_by_frequency['Monthly']) > 0) {
                    foreach ($bookings_by_frequency['Monthly'] as $booking) {
                        renderRecurringBookingCard($booking);
                    }
                }
                ?>
            </div>
        </div>

        <!-- Ratings Summary Tab -->
        <div id="ratings-summary" class="tab-content" style="display: none;">
            <div class="summary-container">
                <h3><i class='bx bx-bar-chart-alt-2'></i> Overall Ratings Summary</h3>
                
                <p style="font-size: 1.0em; color: #555; margin-bottom: 25px;">
                    This section provides a consolidated overview of all your submitted ratings for Recurring Services.
                </p>

                <div class="summary-content-wrapper">
                    <div class="summary-card">
                        <div class="average-rating-display">
                            <i class='bx bxs-star'></i>
                            <span class="rating-value">0.0 / 5.0</span>
                        </div>

                        <p class="average-rating-label">
                            Average Rating Across All Plans
                        </p>
                        
                        <div class="stats-grid-container">
                            <div class="stat-tile rated-services-tile">
                                <div class="tile-icon-group">
                                    <i class='bx bx-check-circle'></i>
                                </div>
                                <span class="tile-value rated-count">0</span> <span class="tile-label">Rated Plans</span>
                            </div>

                            <div class="stat-tile highest-rating-tile">
                                <div class="tile-icon-group">
                                    <i class='bx bxs-medal'></i>
                                </div>
                                <span class="tile-value highest-rating">0<small> Stars</small></span>
                                <span class="tile-label">Highest Rating</span>
                            </div>

                            <div class="stat-tile awaiting-rating-tile">
                                <div class="tile-icon-group">
                                    <i class='bx bx-hourglass'></i>
                                </div>
                                <span class="tile-value awaiting-rating">0</span>
                                <span class="tile-label">Awaiting Rating</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="breakdown-container">
                        <h4>Ratings Breakdown by Frequency</h4>
                        <ul class="ratings-breakdown-list">
                            <li>
                                <strong>Weekly Cleaning:</strong> 
                                <span class="no-ratings">No Ratings Yet</span>
                            </li>
                            <li>
                                <strong>Bi-Weekly Cleaning:</strong> 
                                <span class="no-ratings">No Ratings Yet</span>
                            </li>
                            <li>
                                <strong>Monthly Cleaning:</strong> 
                                <span class="no-ratings">No Ratings Yet</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="detailed-ratings-list-container">
                    <h4>All Submitted Ratings (0 Total)</h4>
                    
                    <div class="filter-controls-tab" style="margin-bottom: 20px;">
                        <select class="service-type-filter-dropdown" onchange="filterRatingsSummary(null, this.value)">
                            <option value="all" selected>All Frequencies</option>
                            <option value="Weekly">Weekly</option>        </div>

        <!-- Bi-Weekly Cleaning Tab -->
        <div id="biweekly-cleaning" class="tab-content" style="display: none;">
            <div class="filter-controls-tab">
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'biweekly-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'biweekly-cleaning-list')">
                </div>
            </div>

            <div class="appointment-list-container" id="biweekly-cleaning-list">
                <div class="no-appointments-message" data-service-name="Bi-Weekly Cleaning"></div>

                <?php
                if (count($bookings_by_frequency['Bi-Weekly']) > 0) {
                    foreach ($bookings_by_frequency['Bi-Weekly'] as $booking) {
                        renderRecurringBookingCard($booking);
                    }
                }
                ?>
            </div>
        </div>

        <!-- Monthly Cleaning Tab -->
        <div id="monthly-cleaning" class="tab-content" style="display: none;">
            <div class="filter-controls-tab">
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'monthly-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'monthly-cleaning-list')">
                </div>
            </div>

            <div class="appointment-list-container" id="monthly-cleaning-list">
                <div class="no-appointments-message" data-service-name="Monthly Cleaning"></div>

                <?php
                if (count($bookings_by_frequency['Monthly']) > 0) {
                    foreach ($bookings_by_frequency['Monthly'] as $booking) {
                        renderRecurringBookingCard($booking);
                    }
                }
                ?>
            </div>
        </div>

        <!-- Ratings Summary Tab -->
        <div id="ratings-summary" class="tab-content" style="display: none;">
            <h3 class="summary-title"><i class='bx bx-stats'></i> Overall Ratings Summary</h3>
            <div class="ratings-summary-container">
                <?php 
                $allStars = [];
                foreach ($bookings_by_frequency as $freq => $items) {
                    foreach ($items as $b) {
                        if (!empty($b['rating_stars'])) {
                            $allStars[] = intval($b['rating_stars']);
                        }
                    }
                }

                if (count($allStars) > 0) {
                    $avg = array_sum($allStars) / count($allStars);
                    ?>
                    <p><strong>Total Rated Plans:</strong> <?= count($allStars) ?></p>
                    <p><strong>Average Rating:</strong> ⭐ <?= round($avg, 1) ?> / 5.0</p>
                    <?php
                } else {
                    echo "<p>No ratings submitted yet.</p>";
                }
                ?>
            </div>
        </div>
    </main>
</div>

<!-- View Details Modal -->
<div id="recurringDetailsModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <span class="close-modal" onclick="closeDetailsModal()">&times;</span>
        <div id="detailsModalContent"></div>
    </div>
</div>

<!-- View Rating Modal -->
<div id="viewRatingModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <span class="close-modal" onclick="closeViewRatingModal()">&times;</span>
        <div id="ratingModalContent"></div>
    </div>
</div>

<!-- Report Modal -->
<div id="reportModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <span class="close-modal" onclick="closeReportModal()">&times;</span>
        <h3><i class='bx bx-error-circle'></i> Report Issue</h3>
        <textarea id="reportMessage" placeholder="Describe the issue..." rows="4"></textarea>
        <button onclick="submitReport()" class="submit-report-btn">Submit</button>
    </div>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="modal-overlay" style="display:none;">
    <div class="modal-content small-modal">
        <h3>Are you sure you want to logout?</h3>
        <button onclick="window.location.href='logout.php'" class="yes-logout">Yes</button>
        <button onclick="closeLogoutModal()" class="no-cancel">Cancel</button>
    </div>
</div>
<div class="modal" id="detailsModal" onclick="if(event.target.id === 'detailsModal') closeModal('detailsModal')">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('detailsModal')">&times;</span> 
        <h3><i class='bx bx-list-ul'></i> Booking Details</h3>
        <div id="modal-details-content"></div>
    </div>
</div>

<script>

    
/* TAB SWITCHING */
function openTab(evt, tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = "none");
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));

    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.classList.add('active');
}

/* DROPDOWN FILTER, SEARCH, DATE LOGIC (REUSED FROM YOUR OTHER PAGES) */
function filterHistory(input, dateFilter, containerId) {
    let filter = input.value.toLowerCase();
    let cards = document.querySelectorAll(`#${containerId} .appointment-list-item`);

    let hasVisible = false;
    cards.forEach(card => {
        let terms = card.dataset.searchTerms.toLowerCase();
        if (terms.includes(filter)) {
            card.style.display = "block";
            hasVisible = true;
        } else {
            card.style.display = "none";
        }
    });

    let msg = document.querySelector(`#${containerId} .no-appointments-message`);
    msg.style.display = hasVisible ? "none" : "block";
}

/* DROPDOWN MENU ON CARD */
function toggleDropdown(btn) {
    document.querySelectorAll('.dropdown-menu.show').forEach(m => {
        if (m !== btn.nextElementSibling) m.classList.remove('show');
    });
    btn.nextElementSibling.classList.toggle('show');
}
window.addEventListener('click', e => {
    if (!e.target.closest('.dropdown-menu-container')) {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
    }
});

/* OPEN DETAILS MODAL */


/* RATING VIEW MODAL */
function showViewRatingModal(card) {
    let stars = card.dataset.ratingStars;
    let feedback = card.dataset.ratingFeedback;

    document.getElementById('ratingModalContent').innerHTML = `
        <h3><i class='bx bx-star'></i> View Rating</h3>
        <p><strong>Rating:</strong> ${stars} ⭐</p>
        <p><strong>Feedback:</strong><br>${feedback}</p>
    `;
    document.getElementById('viewRatingModal').style.display = 'flex';
}

/* REPORT */
function showReportModal() {
    document.getElementById('reportModal').style.display = 'flex';
}
function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}

/* LOGOUT */
function showLogoutModal() {
    document.getElementById('logoutModal').style.display = 'flex';
}
function closeLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

/* CLOSE MODALS */
function closeDetailsModal(){document.getElementById('recurringDetailsModal').style.display='none';}
function closeViewRatingModal(){document.getElementById('viewRatingModal').style.display='none';}

</script>
<script src="HIS_function.js"></script> 

</body>
</html>
