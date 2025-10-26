<?php
session_start();
require 'connection.php';

// Get logged-in client email
$client_email = $_SESSION['email'] ?? null;

// Fetch completed one-time bookings
$sql = "SELECT * FROM bookings 
WHERE status = 'Completed' 
AND booking_type = 'One-Time' ";

if ($client_email) {
    $sql .= "AND email = '" . $conn->real_escape_string($client_email) . "' ";
}
$sql .= "ORDER BY service_date DESC, service_time DESC";

$result = $conn->query($sql);

// Store bookings by service type
$bookings_by_type = [
    'Checkout Cleaning' => [],
    'In-House Cleaning' => [],
    'Refresh Cleaning' => [],
    'Deep Cleaning' => []
];

// Helper function to calculate final price
function calculateFinalPrice($booking) {
    $duration = floatval($booking['duration'] ?? 1);
    $materialsStr = $booking['materials_provided'] ?? '';

    // Extract numeric price per hour
    preg_match('/(\d+(\.\d+)?)\s*AED/i', $materialsStr, $matches);
    $pricePerHour = isset($matches[1]) ? floatval($matches[1]) : 0;

    $finalPrice = $pricePerHour * $duration;

    // Double price if materials are provided (check "Yes" anywhere)
    if (stripos($materialsStr, 'yes') !== false) {
        $finalPrice *= 2;
    }

    return $finalPrice;
}

// Populate bookings and calculate final price
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $service_type = $row['service_type'] ?? 'Other';
        if (isset($bookings_by_type[$service_type])) {
            $row['final_price'] = calculateFinalPrice($row); // <-- key fix
            $bookings_by_type[$service_type][] = $row;
        }
    }
}

// Helper functions
function formatDate($date) {
    return empty($date) ? 'N/A' : date('F d, Y', strtotime($date));
}

function formatTime($time) {
    return empty($time) ? 'N/A' : date('g:i A', strtotime($time));
}

function generateRefNo($id, $service_type, $date) {
    $prefix = '';
    switch($service_type) {
        case 'Checkout Cleaning': $prefix = 'CC'; break;
        case 'In-House Cleaning': $prefix = 'IH'; break;
        case 'Refresh Cleaning': $prefix = 'RC'; break;
        case 'Deep Cleaning': $prefix = 'DC'; break;
        default: $prefix = 'SV';
    }
    $dateStr = date('ym', strtotime($date));
    return "ALZ-{$prefix}-{$dateStr}-" . str_pad($id, 4, '0', STR_PAD_LEFT);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ALAZIMA - One-Time Feedback/Ratings</title>
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="client_db.css"> 
<link rel="stylesheet" href="HIS_design.css">
<link rel="stylesheet" href="FR_design.css">
<link rel="stylesheet" href="FR_design2.css">

<style>
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
                        <li class="menu__item"><a href="FR_one-time.php" class="menu__link active">One-Time Service</a></li>
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
                <i class='bx bx-star'></i> One-Time Service Feedback/Ratings
            </h2>
            <p class="page-description">
            List of all your Completed one-time cleaning service bookings where you can leave or edit your feedback and ratings.
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
                        Feedback and Ratings can only be given for <strong>Completed</strong> bookings.
                        </li>

                        <li>
                            Please rate both the service quality and the assigned employees.
                            <ul>
                                <li> Be honest and specific — your feedback helps us improve our services. </li>
                                <li> Once submitted, you can still edit your feedback if needed. </li>
                            </ul>
                                <li>For any major concerns, kindly contact us via <strong>WhatsApp</strong> using the <strong>Chat</strong> button. (<i class='bx bxl-whatsapp'></i>)</li>
                        </li>
                        <li>
                        Thank you for trusting us and our services! 
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
                <button class="tab-button" onclick="openTab(event, 'ratings-summary')">
                    <i class='bx bx-stats'></i> Ratings Summary
                </button>
                <button class="tab-button" onclick="openTab(event, 'issues-concern')">
                    <i class='bx bx-error-alt'></i> Issues and Concern
                </button>
            </div>
        </div>

        <?php 
        // Function to render booking cards
      
function renderBookingCard($booking) {
    // Generate reference number if not exists
    $refNo = generateRefNo($booking['id'], $booking['service_type'], $booking['service_date']);

    // Check if has rating
    $hasRating = isset($booking['rating_stars']) && !empty($booking['rating_stars']);

    // Check if has issue
    $hasIssue = isset($booking['issue_type']) && !empty($booking['issue_type']);

    // Final price (ensure it exists in booking array)
    $finalPrice = isset($booking['final_price']) ? $booking['final_price'] : 0;

    $searchTerms = $refNo . ' ' . formatDate($booking['service_date']) . ' ' . formatTime($booking['service_time']) . ' ' .
                   htmlspecialchars($booking['address'] ?? '') . ' ' . htmlspecialchars($booking['client_type'] ?? '') .
                   ' COMPLETED' . ($hasRating ? ' RATED' : '');
    ?>
    <div class="appointment-list-item" 
        data-date="<?= htmlspecialchars($booking['service_date']) ?>" 
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
        data-rating-feedback="<?= htmlspecialchars($booking['rating_feedback'] ?? '') ?>"
        data-status="COMPLETED"
        data-ref-no="<?= htmlspecialchars($refNo) ?>"
        data-has-issue="<?= $hasIssue ? 'true' : 'false' ?>">

        <div class="button-group-top">
            <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))">
                <i class='bx bx-show'></i> View Details
            </a>

            <?php if ($hasRating): ?>
            <button type="button" class="action-btn feedback-btn" onclick="showViewRatingModal(this.closest('.appointment-list-item'))">
                <i class='bx bx-star'></i> View Rating
            </button>
            <?php else: ?>
    <a href="FR_one-time_form.php?id=<?= $booking['id']; ?>&action=leave" class="action-btn feedback-btn">
        <i class='bx bx-star'></i> Rate Now
    </a>
<?php endif; ?>

            

           

            <div class="dropdown-menu-container">
                <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                <ul class="dropdown-menu">
                    <?php if ($hasRating): ?>
                    <li><a href="FR_one-time_form.php?ref=<?= urlencode($refNo) ?>&action=edit" class="edit-rating-link"><i class='bx bx-edit'></i> Edit Rating</a></li>
                    <?php endif; ?>
                    <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                    <?php if ($hasIssue): ?>
                    <li><a href="javascript:void(0)" class="report-link view-issue-link" onclick="viewReportedIssue(this.closest('.appointment-list-item'))"><i class='bx bx-error-alt'></i> View Reported Issue</a></li>
                    <?php else: ?>
                    <li><a href="javascript:void(0)" class="report-link" onclick="showReportModal(this)"><i class='bx bx-error-alt'></i> Report Issue</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="appointment-details">
            <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value"><?= htmlspecialchars($refNo) ?></span></p>
            <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> <?= formatDate($booking['service_date']) ?></p>
            <p><i class='bx bx-time'></i> <strong>Time:</strong> <?= formatTime($booking['service_time']) ?></p>
            <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> <?= htmlspecialchars($booking['duration'] ?? 'N/A') ?></p>
            <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> <?= htmlspecialchars($booking['address'] ?? 'N/A') ?></p>
            <hr class="divider full-width-detail">
            <p><i class='bx bx-building-house'></i> <strong>Client Type:</strong> <?= htmlspecialchars($booking['client_type'] ?? 'N/A') ?></p>
            <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> <?= htmlspecialchars($booking['service_type'] ?? 'N/A') ?></p>
            <p class="full-width-detail status-detail">
                <strong>Status:</strong>
                <span class="status-tag completed"><i class='bx bx-check-circle'></i> COMPLETED</span>
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
    Final Price: <span class="aed-color">AED <?= number_format($booking['final_price'], 2) ?></span>
</p>


        </div>
    </div>
    <?php
} // End of function
?>



        <!-- Checkout Cleaning Tab -->
        <div id="checkout-cleaning" class="tab-content" style="display: block;">
            
            <div class="filter-controls-tab">
                
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'checkout-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'checkout-cleaning-list')">
                </div>
                
            </div>

            <div class="appointment-list-container" id="checkout-cleaning-list">
                <div class="no-appointments-message" data-service-name="Checkout Cleaning"></div> 
                
                <?php 
                if (count($bookings_by_type['Checkout Cleaning']) > 0) {
                    foreach ($bookings_by_type['Checkout Cleaning'] as $booking) {
                        renderBookingCard($booking);
                    }
                }
                ?>
                
            </div>
        </div>

        <!-- In-House Cleaning Tab -->
        <div id="in-house-cleaning" class="tab-content" style="display: none;">
            <div class="filter-controls-tab">
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'in-house-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'in-house-cleaning-list')">
                </div>
            </div>

            <div class="appointment-list-container" id="in-house-cleaning-list">
                <div class="no-appointments-message" data-service-name="In-House Cleaning"></div>
                
                <?php 
                if (count($bookings_by_type['In-House Cleaning']) > 0) {
                    foreach ($bookings_by_type['In-House Cleaning'] as $booking) {
                        renderBookingCard($booking);
                    }
                }
                ?>
            </div>
        </div>

        <!-- Refresh Cleaning Tab -->
        <div id="refresh-cleaning" class="tab-content" style="display: none;">
            <div class="filter-controls-tab">
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'refresh-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'refresh-cleaning-list')">
                </div>
            </div>

            <div class="appointment-list-container" id="refresh-cleaning-list">
                <div class="no-appointments-message" data-service-name="Refresh Cleaning"></div>
                
                <?php 
                if (count($bookings_by_type['Refresh Cleaning']) > 0) {
                    foreach ($bookings_by_type['Refresh Cleaning'] as $booking) {
                        renderBookingCard($booking);
                    }
                }
                ?>
            </div>
        </div>

        <!-- Deep Cleaning Tab -->
        <div id="deep-cleaning" class="tab-content" style="display: none;">
            <div class="filter-controls-tab">
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'deep-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'deep-cleaning-list')">
                </div>
            </div>

            <div class="appointment-list-container" id="deep-cleaning-list">
                 <div class="no-appointments-message" data-service-name="Deep Cleaning"></div>
                
                <?php 
                if (count($bookings_by_type['Deep Cleaning']) > 0) {
                    foreach ($bookings_by_type['Deep Cleaning'] as $booking) {
                        renderBookingCard($booking);
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
                    This section provides a consolidated overview of all your submitted ratings for One-Time Services.
                </p>

                <div class="summary-content-wrapper">
                    <div class="summary-card">
                        <div class="average-rating-display">
                            <i class='bx bxs-star'></i>
                            <span class="rating-value">0.0 / 5.0</span>
                        </div>

                        <p class="average-rating-label">
                            Average Rating Across All Services
                        </p>
                        
                        <div class="stats-grid-container">
                            <div class="stat-tile rated-services-tile">
                                <div class="tile-icon-group">
                                    <i class='bx bx-check-circle'></i>
                                </div>
                                <span class="tile-value rated-count">0</span> <span class="tile-label">Rated Services</span>
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
                        <h4>Ratings Breakdown by Service Type</h4>
                        <ul class="ratings-breakdown-list">
                            <li>
                                <strong>Checkout Cleaning:</strong> 
                                <span class="no-ratings">No Ratings Yet</span>
                            </li>
                            <li>
                                <strong>In-House Cleaning:</strong> 
                                <span class="no-ratings">No Ratings Yet</span>
                            </li>
                            <li>
                                <strong>Refresh Cleaning:</strong> 
                                <span class="no-ratings">No Ratings Yet</span>
                            </li>
                            <li>
                                <strong>Deep Cleaning:</strong> 
                                <span class="no-ratings">No Ratings Yet</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="detailed-ratings-list-container">
                    <h4>All Submitted Ratings (0 Total)</h4>
                    
                    <div class="filter-controls-tab" style="margin-bottom: 20px;">
                        <select class="service-type-filter-dropdown" onchange="filterRatingsSummary(null, this.value)">
                            <option value="all" selected>All Service Types</option>
                            <option value="Checkout Cleaning">Checkout Cleaning</option>
                            <option value="In-House Cleaning">In-House Cleaning</option>
                            <option value="Refresh Cleaning">Refresh Cleaning</option>
                            <option value="Deep Cleaning">Deep Cleaning</option>
                        </select>
                        
                        <div class="search-container">
                            <i class='bx bx-search'></i>
                            <input type="text" placeholder="Search Ref No, Service or Feedback..." onkeyup="filterRatingsSummary(this.value, null)">
                        </div>
                    </div>
                    
                    <!-- Dynamic ratings list will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Issues and Concern Tab -->
        <div id="issues-concern" class="tab-content" style="display: none;">
            <div class="summary-container">
                <h3><i class='bx bx-error-alt'></i> Submitted Issues and Concerns</h3>
                
                <p class="page-description" style="font-size: 1.0em; color: #555; margin-bottom: 25px;">
                    This section lists all the issues and concerns you have reported for your completed one-time services.
                </p>
                
                <div class="filter-controls-tab" style="margin-bottom: 20px;">
                    <select class="service-type-filter-dropdown" onchange="filterIssues(null, this.value)">
                        <option value="all" selected>All Service Types</option>
                        <option value="Checkout Cleaning">Checkout Cleaning</option>
                        <option value="In-House Cleaning">In-House Cleaning</option>
                        <option value="Refresh Cleaning">Refresh Cleaning</option>
                        <option value="Deep Cleaning">Deep Cleaning</option>
                    </select>
                    
                    <div class="search-container">
                        <i class='bx bx-search'></i>
                        <input type="text" placeholder="Search Reference No or Issue Type..." onkeyup="filterIssues(this.value, null)">
                    </div>
                </div>
                
                <div class="issue-list-container">
                    <div class="no-issues-message" style="text-align: center; padding: 30px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; color: #777;">
                        <i class='bx bx-folder-open' style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                        No reported issues found.
                    </div>
                    <!-- Dynamic issues list will be loaded here -->
                </div>
            </div>
        </div>
        
    </main>
</div> 

<a href="#header" id="backToTopBtn" title="Back to Top"><i class='bx bx-up-arrow-alt'></i> Back to Top</a>

<!-- Logout Modal -->
<div id="logoutModal" class="modal">
<div class="modal__content">
<h3 class="modal__title">Are you sure you want to log out? </h3>
<div class="modal__actions">
<button id="cancelLogout" class="btn btn--secondary">Cancel</button>
<button id="confirmLogout" class="btn btn--primary">Log Out</button>
</div>
</div>
</div>

<!-- Date Picker Modal -->
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

<!-- Details Modal -->
<div class="modal" id="detailsModal" onclick="if(event.target.id === 'detailsModal') closeModal('detailsModal')">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('detailsModal')">&times;</span> 
        <h3><i class='bx bx-file-text'></i> Appointment Details</h3>
        <div id="modal-details-content"></div>
    </div>
</div>

<!-- Report Issue Modal -->
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

<!-- Report Success Modal -->
<div class="report-modal" id="reportSuccessModal" onclick="if(event.target.id === 'reportSuccessModal') closeModal('reportSuccessModal')">
    <div class="report-modal-content" style="max-width: 400px; text-align: center;">
        <div style="padding: 20px;">
            <i class='bx bx-check-circle' style="font-size: 4em; color: #00A86B; margin-bottom: 10px;"></i>
            <h3 style="border-bottom: none; margin-bottom: 10px;">Report Submitted!</h3>
            
            <p id="success-message" style="color: #555; font-size: 1em;">
                Your issue report for Ref: <span id="submitted-ref-number" style="color: #B32133; font-weight: 700;"></span> has been received and is under review.
            </p>
            
            <button onclick="closeModal('reportSuccessModal')" class="primary-btn report-confirm-btn">
                Got It
            </button>
        </div>
    </div>
</div>

<!-- View Rating Modal -->
<div class="report-modal" id="viewRatingModal" onclick="if(event.target.id === 'viewRatingModal') closeModal('viewRatingModal')">
    <div class="report-modal-content" style="max-width: 650px;">
        <span class="close-btn" onclick="closeModal('viewRatingModal')">&times;</span> 

        <div style="padding: 25px; text-align: left;">
            <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 20px;"><i class='bx bx-star'></i> Service Rating Details</h3>
            
            <p style="margin-bottom: 5px;"><strong>Reference No:</strong> <span id="viewRefNo" style="color: #B32133; font-weight: 700;"></span></p>
            
            <div id="viewAppointmentDetails" style="margin-top: 10px; margin-bottom: 15px;"></div>

            <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;"> 
            
            <div class="rating-info-container" style="display: flex; flex-direction: column; gap: 15px;">
                <div class="rating-stars-section">
                    <p style="font-size: 1.1em; margin-bottom: 5px;">
                        <strong>Rating:</strong>
                        <span id="viewStarsContainer" style="color: #FFC107; margin-left: 10px;"></span> 
                    </p>
                </div>
                
                <div class="feedback-section">
                    <p style="font-size: 1.1em; margin-bottom: 5px;"><strong>Feedback:</strong></p>
                    <div id="viewFeedback" style="border: 1px solid #ddd; padding: 15px; border-radius: 6px; background-color: #f9f9f9; color: #333; line-height: 1.5;"></div>
                </div>
            </div>
            
            <div style="text-align: right; margin-top: 30px;">
                <a id="editRatingLinkInModal" href="#" class="primary-btn" style="background-color: #004A80;">Edit Rating</a>
            </div>
        </div>
    </div>
</div>

<!-- View Reported Issue Modal -->
<div class="report-modal" id="viewReportedIssueModal" onclick="if(event.target.id === 'viewReportedIssueModal') closeModal('viewReportedIssueModal')">
    <div class="report-modal-content" style="max-width: 600px;">
        <span class="report-close-btn" onclick="closeModal('viewReportedIssueModal')">&times;</span> 

        <h3 class="modal-title-issue-details">Reported Issue Details</h3>
        <div class="issue-ref-header">
            <p>
                <strong>Reference No:</strong> 
                <span id="view-issue-ref-number" class="ref-no-value-large"></span>
            </p>
        </div>
        
        <div class="issue-details-grid">
            <div class="detail-group">
                <label>Date of Service</label>
                <p id="view-issue-incident-date" class="detail-value">N/A</p>
            </div>
            <div class="detail-group">
                <label>Scheduled Time</label>
                <p id="view-issue-scheduled-time" class="detail-value">N/A</p>
            </div>
            
            <div class="detail-group">
                <label>Date Reported</label>
                <p id="view-issue-report-date" class="detail-value">N/A</p>
            </div>
            <div class="detail-group">
                <label>Time Reported</label>
                <p id="view-issue-report-time" class="detail-value">N/A</p>
            </div>
            
            <hr class="grid-separator"> 
        </div>

        <div class="issue-type-section">
            <label>Type of Issue</label>
            <p id="view-issue-type" class="issue-type-tag">N/A</p>
        </div>
        
        <div class="issue-description-section">
            <label>Description</label>
            <p id="view-issue-description" class="description-content">No description provided.</p>
        </div>
        
        <div class="issue-attachments-section">
            <label>Attachments (up to 3 files)</label>
            <div id="view-issue-attachments" class="attachments-list"></div>
        </div>
        
        <div class="issue-status-section">
            <label>Current Status</label>
            <span id="view-issue-status" class="status-tag">In Progress</span>
        </div>
        
        <div class="issue-modal-footer"></div>
    </div>
</div>

<!-- Required Fields Modal -->
<div id="requiredFieldsModal" class="modal">
<div class="modal__content">
<h3 class="modal__title">Please fill out all required fields.</h3>
<div class="modal__actions">
<button class="btn btn--primary" id="confirmRequiredFields" onclick="closeModal('requiredFieldsModal')">OK</button>
</div>
</div>
</div>

<script src="FR_function.js"></script> 
<script src="FR_function2.js"></script> 
<script src="client_db.js"></script> 	 	 	
<script src="HIS_function.js"></script> 

</body>
</html>