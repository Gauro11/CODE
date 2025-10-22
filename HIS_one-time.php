<?php
// PHP code for HIS_one-time.php would typically start here if it were a real dynamic file.
// Since this is a static representation, we start with the HTML/CSS/JS.
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ALAZIMA - One-Time History</title>
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="client_db.css"> <link rel="stylesheet" href="HIS_design.css">

<style>
/* Temporary CSS for the new Cancel Modal to ensure it matches the image.
    Huwag kalimutang ilipat ito sa HIS_design.css! 
*/
.cancel-appointment-modal {
    display: none; /* Default state: hidden */
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.6); /* Dark overlay */
    justify-content: center; /* Center horizontally */
    align-items: center; /* Center vertically */
}

.cancel-modal-content {
    background-color: #fefefe;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    max-width: 450px; /* Sukat batay sa screenshot */
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
    color: #B32133; /* Red color (Default for the Cancel confirmation) */
    margin-bottom: 5px;
}

.cancel-modal-content h3 {
    font-size: 1.5em;
    font-weight: 700;
    color: #333;
    margin-bottom: 10px;
    border-bottom: none; /* Inalis ang border para tumugma sa image */
}

.cancel-modal-content p {
    font-size: 1em;
    color: #555;
    margin-bottom: 25px;
    line-height: 1.5;
}

.cancel-modal-content strong#cancel-ref-number,
.cancel-modal-content strong#cancelled-ref-number { /* Idinagdag ang #cancelled-ref-number */
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

/* Red button (Yes, Cancel) */
.cancel-modal-content .primary-cancel-btn {
    background-color: #B32133;
    color: white;
    border: 2px solid #B32133;
    box-shadow: none; /* Para mas mukhang flat batay sa image */
}
.cancel-modal-content .primary-cancel-btn:hover {
    background-color: #9c1d2d;
    border-color: #9c1d2d;
}

/* White button (Keep) */
.cancel-modal-content .secondary-keep-btn {
    background-color: #fff;
    color: #555;
    border: 2px solid #ccc;
    box-shadow: none; /* Para mas mukhang flat batay sa image */
}
.cancel-modal-content .secondary-keep-btn:hover {
    background-color: #f4f4f4;
    border-color: #bbb;
}

/* Styling for the new Status Filter Dropdown (Requested) */
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

/* --- ADDED CSS FOR INFO HOVER POP-UP --- */
.history-header-container {
    display: flex; /* Para magkatabi ang title at button */
    justify-content: space-between; /* Itulak ang button sa dulo */
    align-items: flex-start; /* Ayusin ang vertical alignment */
    gap: 20px;
}

.info-button {
    background-color: transparent;
    color: #004a80; /* Blue color for distinction */
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

/* HOVER CONTAINER AND TOOLTIP STYLES */
.info-button-container {
    position: relative; /* Base for absolute positioning ng tooltip */
    display: inline-block;
    flex-shrink: 0;
}

.info-tooltip-content {
    /* DEFAULT STATE: Hiding the tooltip */
    visibility: hidden;
    opacity: 0;
    transition: opacity 0.3s, visibility 0.3s;
    
    /* Positioning and Styling */
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    padding: 20px;
    max-width: 550px; /* BINAGO KO ITO: Nilakihan ang width */
    width: max-content;
    
    /* Absolute Positioning */
    position: absolute;
    z-index: 1000;
    top: 100%; /* Ilagay sa ibaba ng button */
    right: 0; /* Align sa kanan ng button container */
    margin-top: 10px; /* Spacing mula sa button */
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
    list-style: disc; /* Ginawang bulleted list */
    padding-left: 20px;
    margin: 0 0 20px 0;
}

.info-tooltip-content li {
    font-size: 1em;
    color: #333;
    margin-bottom: 10px;
    line-height: 1.6;
    border-left: none; /* Inalis ang lines na nasa gilid */
    padding-left: 0;
}

/* Target the UL within the LI to remove its bullets and padding */
.info-tooltip-content li ul {
    list-style: none; /* Inalis ang bullets sa sub-list */
    padding-left: 0; /* Inalis ang padding sa sub-list */
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
    font-weight: 700; /* Tiyakin na naka-bold */
}

.info-tooltip-content li.completed-info {
    border-left: none; /* Inalis ang lines na nasa gilid */
}

/* HOVER EFFECT - Ito ang magpapakita ng pop-up */
.info-button-container:hover .info-tooltip-content {
    visibility: visible;
    opacity: 1;
}

/* Arrow (optional) */
.info-tooltip-content::after {
    content: "";
    position: absolute;
    bottom: 100%; /* Top edge of the tooltip */
    right: 20px; /* Position ng arrow */
    border-width: 8px;
    border-style: solid;
    border-color: transparent transparent #fff transparent; /* White arrow */
    filter: drop-shadow(0 -2px 1px rgba(0, 0, 0, 0.05)); /* Subtle shadow */
}

/* --- END ADDED CSS --- */

/* --- STAFF DETAILS - PROFESSIONAL LOOK with BACKGROUND COLOR --- */
.staff-details-container {
    /* Bagong Background Color (Very Light Blue/Gray) */
    background-color: #f7f9fc; /* Light, subtle background */
    
    margin-top: 15px;
    padding: 12px; /* Dinagdagan ang padding para sa mas malaking espasyo */
    border-radius: 8px; /* Nagdagdag ng rounded corners */
    border: 1px solid #e0e6ed; /* Subtle border */
    font-size: 0.95em;
    color: #333;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); /* Very light shadow */
}

.staff-details-container h4 {
    font-size: 1.1em;
    font-weight: bold;
    color: #004085; 
    margin-bottom: 10px; /* Dinagdagan ang space sa ilalim ng heading */
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 5px;
    padding-bottom: 5px;
    border-bottom: 1px solid #e0e6ed; /* Added a clean separator line */
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
    color: #0035b3; /* Darker accent color for icons */
}

.staff-details-container p strong {
    font-weight: 600; /* Bolder label */
    margin-right: 5px;
    min-width: 65px; /* Para magpantay ang label */
}

/* --- END Staff Details CSS --- */
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
                            You can edit or cancel your booking while it’s still <strong>Pending</strong>.
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
        <div id="checkout-cleaning" class="tab-content" style="display: block;">
            
            <div class="filter-controls-tab">
                
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'checkout-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <select class="status-filter-dropdown" onchange="sortAppointmentsByStatus('checkout-cleaning-list', this.value)">
                    <option value="default" selected>Sort by Status</option>
                    <option value="PENDING">Pending</option>
                    <option value="CONFIRMED">Confirmed</option>
                    <option value="ONGOING">Ongoing</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CANCELLED">Cancelled</option>
                    <option value="NO SHOW">No Show</option>
                </select>
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'checkout-cleaning-list')">
                </div>
                
            </div>

            <div class="appointment-list-container" id="checkout-cleaning-list">
                <div class="no-appointments-message" data-service-name="Checkout Cleaning"></div> 
                <div class="appointment-list-item" 
                    data-date="2024-10-05" 
                    data-time="09:00"
                    data-status="PENDING"
                    data-search-terms="ALZ-CC-2410-0016 October 05, 2024 9:00 AM 707 Downtown, Dubai Apartment PENDING"
                    data-property-layout="1 Bedroom, 1 Bathroom"
                    data-materials-required="Yes"
                    data-materials-description="All-purpose cleaner, glass cleaner, floor mop/bucket."
                    data-additional-request="Focus on cleaning the windows in the living area."
                    data-image-1="https://alazima.com/files/ALZ-CC-2410-0016_img1.jpg"
                    data-image-2=""
                    data-image-3=""
                >
                    <div class="button-group-top">
                        <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="EDIT_one-time.php?booking_id=1" class="action-btn feedback-btn">
    <i class='bx bx-edit'></i> Edit
</a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="cancel-link" onclick="showCancelModal('ALZ-CC-2410-0016')"><i class='bx bx-x-circle' style="color: #B32133;"></i> Cancel</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="appointment-details">
                        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-CC-2410-0016</span></p>
                        <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> October 05, 2024</p>
                        <p><i class='bx bx-time'></i> <strong>Time:</strong> 9:00 AM</p>
                        <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 4 hours</p>
                        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 707 Downtown, Dubai</p>
                        <hr class="divider full-width-detail">
                        <p><i class='bx bx-building-house'></i> <strong>Client Type:</strong> Apartment</p>
                        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> Checkout Cleaning</p>
                        <p class="full-width-detail status-detail">
                            <strong>Status:</strong>
                            <span class="status-tag pending"><i class='bx bx-hourglass'></i> PENDING</span>
                        </p>
                        <p class="price-detail">Estimated Price: <span class="aed-color">AED 400</span></p>
                    </div>
                </div>
                <div class="appointment-list-item" 
                    data-date="2024-09-28" 
                    data-time="11:00"
                    data-status="COMPLETED"
                    data-search-terms="ALZ-CC-2409-0015 September 28, 2024 11:00 AM 505 Business Bay, Dubai Apartment COMPLETED"
                    data-property-layout="2 Bedrooms, 2 Bathrooms"
                    data-materials-required="No"
                    data-materials-description="N/A"
                    data-additional-request="Please use the client's vacuum cleaner. Ensure the kitchen cabinets are spotless."
                    data-image-1="https://alazima.com/files/ALZ-CC-2409-0015_img1.jpg"
                    data-image-2="https://alazima.com/files/ALZ-CC-2409-0015_img2.mp4"
                    data-image-3=""
                >
                    <div class="button-group-top">
                        <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="FR_one-time.php" class="action-btn feedback-btn"><i class='bx bx-star'></i> Leave Feedback</a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="report-link" onclick="showReportModal(this)"><i class='bx bx-error-alt'></i> Report Issue</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="appointment-details">
                        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-CC-2409-0015</span></p>
                        <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> September 28, 2024</p>
                        <p><i class='bx bx-time'></i> <strong>Time:</strong> 11:00 AM</p>
                        <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 5 hours</p>
                        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 505 Business Bay, Dubai</p>
                        <hr class="divider full-width-detail">
                        <p><i class='bx bx-building-house'></i> <strong>Client Type:</strong> Apartment</p>
                        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> Checkout Cleaning</p>
                        <p class="full-width-detail status-detail">
                            <strong>Status:</strong>
                            <span class="status-tag completed"><i class='bx bx-check-circle'></i> COMPLETED</span>
                        </p>
                        <div class="staff-details-container full-width-detail">
                            <h4><i class='bx bx-id-card'></i> Assigned Team</h4>
                            <p><i class='bx bx-car'></i> <strong>Driver:</strong> Mohamed A.</p>
                            <p><i class='bx bx-group'></i> <strong>Cleaners:</strong> Jenny Ramos, Sarah Kim, Mia Laoag, Chloe Jasmine Cruz, Ella Buenaflor, Rhea Generics, Joy Mae Tindugan, Anne Paraseo Santos</p>
                        </div>
                        <p class="price-detail">Final Price: <span class="aed-color">AED 500</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div id="in-house-cleaning" class="tab-content" style="display: none;">
            
            <div class="filter-controls-tab">
                
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'in-house-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <select class="status-filter-dropdown" onchange="sortAppointmentsByStatus('in-house-cleaning-list', this.value)">
                    <option value="default" selected>Sort by Status</option>
                    <option value="PENDING">Pending</option>
                    <option value="CONFIRMED">Confirmed</option>
                    <option value="ONGOING">Ongoing</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CANCELLED">Cancelled</option>
                    <option value="NO SHOW">No Show</option>
                </select>
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'in-house-cleaning-list')">
                </div>
                
            </div>

            <div class="appointment-list-container" id="in-house-cleaning-list">
                <div class="no-appointments-message" data-service-name="In-House Cleaning"></div>
                <div class="appointment-list-item" 
                    data-date="2024-10-10" 
                    data-time="11:00"
                    data-status="PENDING"
                    data-search-terms="ALZ-IH-2410-0018 October 10, 2024 11:00 AM 404 Dubai Marina, Dubai Apartment PENDING (Sample)"
                    data-property-layout="1 Bedroom, 1 Bathroom"
                    data-materials-required="No"
                    data-materials-description="N/A"
                    data-additional-request="New client, standard cleaning."
                    data-image-1=""
                    data-image-2=""
                    data-image-3=""
                >
                    <div class="button-group-top">
                        <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="EDIT_one-time.php?booking_id=1" class="action-btn feedback-btn">
    <i class='bx bx-edit'></i> Edit
</a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="cancel-link" onclick="showCancelModal('ALZ-IH-2410-0018')"><i class='bx bx-x-circle' style="color: #B32133;"></i> Cancel</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="appointment-details">
                        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-IH-2410-0018</span></p>
                        <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> October 10, 2024</p>
                        <p><i class='bx bx-time'></i> <strong>Time:</strong> 11:00 AM</p>
                        <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 3 hours</p>
                        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 404 Dubai Marina, Dubai</p>
                        <hr class="divider full-width-detail">
                        <p><i class='bx bx-building-house'></i> <strong>Client Type:</strong> Apartment</p>
                        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> In-House Cleaning</p>
                        <p class="full-width-detail status-detail">
                            <strong>Status:</strong>
                            <span class="status-tag pending"><i class='bx bx-hourglass'></i> PENDING</span>
                        </p>
                        <p class="price-detail">Estimated Price: <span class="aed-color">AED 300</span></p>
                    </div>
                </div>
                <div class="appointment-list-item" 
                    data-date="2024-09-29" 
                    data-time="13:00"
                    data-status="ONGOING"
                    data-search-terms="ALZ-IH-2409-0014 September 29, 2024 1:00 PM 501 The Greens, Dubai Apartment ONGOING"
                    data-property-layout="3 Bedrooms, 4 Bathrooms, Maid's Room"
                    data-materials-required="No"
                    data-materials-description="N/A"
                    data-additional-request="Deep cleaning of carpets is needed in the living room."
                    data-image-1="https://alazima.com/files/ALZ-IH-2409-0014_img1.jpg"
                    data-image-2=""
                    data-image-3=""
                >
                    <div class="button-group-top">
                        <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="https://wa.me/971529009188" target="_blank" class="action-btn whatsapp-chat-btn"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a>
                    </div>
                    <div class="appointment-details">
                        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-IH-2409-0014</span></p>
                        <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> September 29, 2024</p>
                        <p><i class='bx bx-time'></i> <strong>Time:</strong> 1:00 PM</p>
                        <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 3 hours</p>
                        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 501 The Greens, Dubai</p>
                        <hr class="divider full-width-detail">
                        <p><i class='bx bx-building-house'></i> <strong>Client Type:</strong> Apartment</p>
                        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> In-House Cleaning</p>
                        <p class="full-width-detail status-detail">
                            <strong>Status:</strong>
                             <span class="status-tag ongoing"><i class='bx bx-loader-circle'></i> ONGOING</span>
                        </p>
                        <div class="staff-details-container full-width-detail">
                            <h4><i class='bx bx-id-card'></i> Assigned Team</h4>
                            <p><i class='bx bx-car'></i> <strong>Driver:</strong> Mohamed A.</p>
                            <p><i class='bx bx-group'></i> <strong>Cleaners:</strong> Jenny Ramos, Sarah Kim, Mia Laoag, Chloe Jasmine Cruz, Ella Buenaflor, Rhea Generics, Joy Mae Tindugan, Anne Paraseo Santos</p>
                        </div>
                        <p class="price-detail">Estimated Price: <span class="aed-color">AED 300</span></p>
                    </div>
                </div>
                <div class="appointment-list-item" 
                    data-date="2024-09-20" 
                    data-time="14:00"
                    data-status="COMPLETED"
                    data-search-terms="ALZ-IH-2409-0012 September 20, 2024 2:00 PM 606 JLT, Dubai Apartment COMPLETED"
                    data-property-layout="Studio, 1 Bathroom"
                    data-materials-required="No"
                    data-materials-description="N/A"
                    data-additional-request="Standard bi-weekly clean."
                    data-image-1="https://alazima.com/files/ALZ-IH-2409-0012_img1.jpg"
                    data-image-2=""
                    data-image-3=""
                >
                    <div class="button-group-top">
                        <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="FR_one-time.php" class="action-btn feedback-btn"><i class='bx bx-star'></i> Leave Feedback</a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="report-link" onclick="showReportModal(this)"><i class='bx bx-error-alt'></i> Report Issue</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="appointment-details">
                        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-IH-2409-0012</span></p>
                        <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> September 20, 2024</p>
                        <p><i class='bx bx-time'></i> <strong>Time:</strong> 2:00 PM</p>
                        <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 3 hours</p>
                        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 606 JLT, Dubai</p>
                        <hr class="divider full-width-detail">
                        <p><i class='bx bx-building-house'></i> <strong>Client Type:</strong> Apartment</p>
                        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> In-House Cleaning</p>
                        <p class="full-width-detail status-detail">
                            <strong>Status:</strong>
                            <span class="status-tag completed"><i class='bx bx-check-circle'></i> COMPLETED</span>
                        </p>
                        <div class="staff-details-container full-width-detail">
                            <h4><i class='bx bx-id-card'></i> Assigned Team</h4>
                            <p><i class='bx bx-car'></i> <strong>Driver:</strong> Mohamed A.</p>
                            <p><i class='bx bx-group'></i> <strong>Cleaners:</strong> Jenny Ramos, Sarah Kim, Mia Laoag, Chloe Jasmine Cruz, Ella Buenaflor, Rhea Generics, Joy Mae Tindugan, Anne Paraseo Santos</p>
                        </div>
                        <p class="price-detail">Final Price: <span class="aed-color">AED 300</span></p>
                    </div>
                </div>
                <div class="appointment-list-item" 
                    data-date="2024-09-01" 
                    data-time="09:00"
                    data-status="COMPLETED"
                    data-search-terms="ALZ-IH-2409-0001 September 01, 2024 9:00 AM 101 Downtown, Dubai Villa COMPLETED"
                    data-property-layout="4 Bedrooms, 5 Bathrooms (Villa)"
                    data-materials-required="Yes"
                    data-materials-description="Heavy-duty floor cleaner, 4 trash bags, long-reach duster."
                    data-additional-request="None."
                    data-image-1="https://alazima.com/files/ALZ-IH-2409-0001_img1.jpg"
                    data-image-2="https://alazima.com/files/ALZ-IH-2409-0001_img2.jpg"
                    data-image-3="https://alazima.com/files/ALZ-IH-2409-0001_img3.jpg"
                >
                    <div class="button-group-top">
                        <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="FR_one-time.php" class="action-btn feedback-btn"><i class='bx bx-star'></i> Leave Feedback</a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="report-link" onclick="showReportModal(this)"><i class='bx bx-error-alt'></i> Report Issue</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="appointment-details">
                        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-IH-2409-0001</span></p>
                        <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> September 01, 2024</p>
                        <p><i class='bx bx-time'></i> <strong>Time:</strong> 9:00 AM</p>
                        <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 4 hours</p>
                        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 101 Downtown, Dubai</p>
                        <hr class="divider full-width-detail">
                        <p><i class='bx bx-building-house'></i> <strong>Client Type:</strong> Villa</p>
                        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> In-House Cleaning</p>
                        <p class="full-width-detail status-detail">
                            <strong>Status:</strong>
                            <span class="status-tag completed"><i class='bx bx-check-circle'></i> COMPLETED</span>
                        </p>
                        <div class="staff-details-container full-width-detail">
                            <h4><i class='bx bx-id-card'></i> Assigned Team</h4>
                            <p><i class='bx bx-car'></i> <strong>Driver:</strong> Mohamed A.</p>
                            <p><i class='bx bx-group'></i> <strong>Cleaners:</strong> Jenny Ramos, Sarah Kim, Mia Laoag, Chloe Jasmine Cruz, Ella Buenaflor, Rhea Generics, Joy Mae Tindugan, Anne Paraseo Santos</p>
                        </div>
                        <p class="price-detail">Final Price: <span class="aed-color">AED 400</span></p>
                    </div>
                </div>
                <div class="appointment-list-item" 
                    data-date="2024-09-15" 
                    data-time="10:00"
                    data-status="NO SHOW"
                    data-search-terms="ALZ-IH-2409-0013 September 15, 2024 10:00 AM 303 Business Bay, Dubai Apartment NO SHOW"
                    data-property-layout="2 Bedrooms, 2 Bathrooms"
                    data-materials-required="Yes"
                    data-materials-description="Microfiber cloths, bathroom scrubber, floor disinfectant."
                    data-additional-request="Client will be 30 mins late. Please start in the kitchen."
                    data-image-1=""
                    data-image-2=""
                    data-image-3=""
                >
                    <div class="button-group-top">
                        <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                    </div>
                    <div class="appointment-details">
                        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-IH-2409-0013</span></p>
                        <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> September 15, 2024</p>
                        <p><i class='bx bx-time'></i> <strong>Time:</strong> 10:00 AM</p>
                        <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 3 hours</p>
                        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 303 Business Bay, Dubai</p>
                        <hr class="divider full-width-detail">
                        <p><i class='bx bx-building-house'></i> <strong>Client Type:</strong> Apartment</p>
                        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> In-House Cleaning</p>
                        <p class="full-width-detail status-detail">
                            <strong>Status:</strong>
                            <span class="status-tag no-show"><i class='bx bx-user-minus'></i> NO SHOW</span>
                        </p>
                        <div class="staff-details-container full-width-detail">
                            <h4><i class='bx bx-id-card'></i> Assigned Team</h4>
                            <p><i class='bx bx-car'></i> <strong>Driver:</strong> Mohamed A.</p>
                            <p><i class='bx bx-group'></i> <strong>Cleaners:</strong> Jenny Ramos, Sarah Kim, Mia Laoag, Chloe Jasmine Cruz, Ella Buenaflor, Rhea Generics, Joy Mae Tindugan, Anne Paraseo Santos</p>
                        </div>
                        <p class="price-detail">Estimated Price: <span class="aed-color">AED 300</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div id="refresh-cleaning" class="tab-content" style="display: none;">
            
            <div class="filter-controls-tab">
                
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'refresh-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <select class="status-filter-dropdown" onchange="sortAppointmentsByStatus('refresh-cleaning-list', this.value)">
                    <option value="default" selected>Sort by Status</option>
                    <option value="PENDING">Pending</option>
                    <option value="CONFIRMED">Confirmed</option>
                    <option value="ONGOING">Ongoing</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CANCELLED">Cancelled</option>
                    <option value="NO SHOW">No Show</option>
                </select>
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'refresh-cleaning-list')">
                </div>
                
            </div>

            <div class="appointment-list-container" id="refresh-cleaning-list">
                <div class="no-appointments-message" data-service-name="Refresh Cleaning"></div>
                <div class="appointment-list-item" 
                    data-date="2024-10-15" 
                    data-time="10:00"
                    data-status="PENDING"
                    data-search-terms="ALZ-RC-2410-0019 October 15, 2024 10:00 AM 909 Business Bay, Dubai Apartment PENDING (Sample)"
                    data-property-layout="2 Bedrooms, 2 Bathrooms"
                    data-materials-required="Yes"
                    data-materials-description="Standard cleaning kit (sponges, wipes, surface sprays)."
                    data-additional-request="Focus on quick wipe-down and vacuuming."
                    data-image-1=""
                    data-image-2=""
                    data-image-3=""
                >
                    <div class="button-group-top">
                        <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="EDIT_one-time.php?booking_id=1" class="action-btn feedback-btn">
    <i class='bx bx-edit'></i> Edit
</a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="cancel-link" onclick="showCancelModal('ALZ-RC-2410-0019')"><i class='bx bx-x-circle' style="color: #B32133;"></i> Cancel</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="appointment-details">
                        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-RC-2410-0019</span></p>
                        <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> October 15, 2024</p>
                        <p><i class='bx bx-time'></i> <strong>Time:</strong> 10:00 AM</p>
                        <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 2 hours</p>
                        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 909 Business Bay, Dubai</p>
                        <hr class="divider full-width-detail">
                        <p><i class='bx bx-building-house'></i> <strong>Client Type:</strong> Apartment</p>
                        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> Refresh Cleaning</p>
                        <p class="full-width-detail status-detail">
                            <strong>Status:</strong>
                            <span class="status-tag pending"><i class='bx bx-hourglass'></i> PENDING</span>
                        </p>
                        <p class="price-detail">Estimated Price: <span class="aed-color">AED 200</span></p>
                    </div>
                </div>
                <div class="appointment-list-item" 
                    data-date="2024-10-10" 
                    data-time="13:00"
                    data-status="CONFIRMED"
                    data-search-terms="ALZ-RC-2410-0017 October 10, 2024 1:00 PM 808 Downtown, Dubai Apartment CONFIRMED"
                    data-property-layout="1 Bedroom, 1 Bathroom"
                    data-materials-required="Yes"
                    data-materials-description="Dusting spray, paper towels, and window squeegee."
                    data-additional-request="Quick wipe down of surfaces, very urgent for guest arrival."
                    data-image-1=""
                    data-image-2=""
                    data-image-3=""
                >
                    <div class="button-group-top">
                        <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="https://wa.me/971529009188" target="_blank" class="action-btn whatsapp-chat-btn"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a>
                    </div>
                    <div class="appointment-details">
                        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-RC-2410-0017</span></p>
                        <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> October 10, 2024</p>
                        <p><i class='bx bx-time'></i> <strong>Time:</strong> 1:00 PM</p>
                        <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 2 hours</p>
                        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 808 Downtown, Dubai</p>
                        <hr class="divider full-width-detail">
                        <p><i class='bx bx-building-house'></i> <strong>Client Type:</strong> Apartment</p>
                        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> Refresh Cleaning</p>
                        <p class="full-width-detail status-detail">
                            <strong>Status:</strong>
                            <span class="status-tag confirmed"><i class='bx bx-calendar-check'></i> CONFIRMED</span>
                        </p>
                        <div class="staff-details-container full-width-detail">
                            <h4><i class='bx bx-id-card'></i> Assigned Team</h4>
                            <p><i class='bx bx-car'></i> <strong>Driver:</strong> Mohamed A.</p>
                            <p><i class='bx bx-group'></i> <strong>Cleaners:</strong> Jenny Ramos, Sarah Kim, Mia Laoag, Chloe Jasmine Cruz, Ella Buenaflor, Rhea Generics, Joy Mae Tindugan, Anne Paraseo Santos</p>
                        </div>
                        <p class="price-detail">Estimated Price: <span class="aed-color">AED 200</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div id="deep-cleaning" class="tab-content" style="display: none;">
            
            <div class="filter-controls-tab">
                
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'deep-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <select class="status-filter-dropdown" onchange="sortAppointmentsByStatus('deep-cleaning-list', this.value)">
                    <option value="default" selected>Sort by Status</option>
                    <option value="PENDING">Pending</option>
                    <option value="CONFIRMED">Confirmed</option>
                    <option value="ONGOING">Ongoing</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CANCELLED">Cancelled</option>
                    <option value="NO SHOW">No Show</option>
                </select>
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'deep-cleaning-list')">
                </div>
                
            </div>

            <div class="appointment-list-container" id="deep-cleaning-list">
                 <div class="no-appointments-message" data-service-name="Deep Cleaning"></div>
                <div class="appointment-list-item" 
                    data-date="2024-10-20" 
                    data-time="09:00"
                    data-status="PENDING"
                    data-search-terms="ALZ-DC-2410-0020 October 20, 2024 9:00 AM 101 JBR, Dubai Villa PENDING (Sample)"
                    data-property-layout="4 Bedrooms, 5 Bathrooms (Villa)"
                    data-materials-required="Yes"
                    data-materials-description="Steam cleaner, heavy-duty floor cleaner, post-construction debris bags."
                    data-additional-request="Deep clean after moving out."
                    data-image-1=""
                    data-image-2=""
                    data-image-3=""
                >
                    <div class="button-group-top">
                        <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="EDIT_one-time.php?booking_id=1" class="action-btn feedback-btn">
    <i class='bx bx-edit'></i> Edit
</a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="cancel-link" onclick="showCancelModal('ALZ-DC-2410-0020')"><i class='bx bx-x-circle' style="color: #B32133;"></i> Cancel</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="appointment-details">
                        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-DC-2410-0020</span></p>
                        <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> October 20, 2024</p>
                        <p><i class='bx bx-time'></i> <strong>Time:</strong> 9:00 AM</p>
                        <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 6 hours</p>
                        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 101 JBR, Dubai</p>
                        <hr class="divider full-width-detail">
                        <p><i class='bx bx-home-heart'></i> <strong>Client Type:</strong> Villa</p>
                        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> Deep Cleaning</p>
                        <p class="full-width-detail status-detail">
                            <strong>Status:</strong>
                            <span class="status-tag pending"><i class='bx bx-hourglass'></i> PENDING</span>
                        </p>
                        <p class="price-detail">Estimated Price: <span class="aed-color">AED 600</span></p>
                    </div>
                </div>
                <div class="appointment-list-item" 
                    data-date="2024-09-25" 
                    data-time="08:00"
                    data-status="COMPLETED"
                    data-search-terms="ALZ-DC-2409-0010 September 25, 2024 8:00 AM 101 Marina View, Dubai Villa COMPLETED"
                    data-property-layout="5 Bedrooms, 6 Bathrooms (Villa)"
                    data-materials-required="Yes"
                    data-materials-description="Steam cleaner, heavy-duty floor cleaner, post-construction debris bags."
                    data-additional-request="Full post-construction cleaning required. Please bring heavy-duty equipment."
                    data-image-1="https://alazima.com/files/ALZ-DC-2409-0010_img1.jpg"
                    data-image-2="https://alazima.com/files/ALZ-DC-2409-0010_vid2.mp4"
                    data-image-3="https://alazima.com/files/ALZ-DC-2409-0010_img3.jpg"
                >
                    <div class="button-group-top">
                        <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="FR_one-time.php" class="action-btn feedback-btn"><i class='bx bx-star'></i> Leave Feedback</a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="report-link" onclick="showReportModal(this)"><i class='bx bx-error-alt'></i> Report Issue</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="appointment-details">
                        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-DC-2409-0010</span></p>
                        <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> September 25, 2024</p>
                        <p><i class='bx bx-time'></i> <strong>Time:</strong> 8:00 AM</p>
                        <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 6 hours</p>
                        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 101 Marina View, Dubai</p>
                        <hr class="divider full-width-detail">
                        <p><i class='bx bx-home-heart'></i> <strong>Client Type:</strong> Villa</p>
                        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> Deep Cleaning</p>
                        <p class="full-width-detail status-detail">
                            <strong>Status:</strong>
                            <span class="status-tag completed"><i class='bx bx-check-circle'></i> COMPLETED</span>
                        </p>
                        <div class="staff-details-container full-width-detail">
                            <h4><i class='bx bx-id-card'></i> Assigned Team</h4>
                            <p><i class='bx bx-car'></i> <strong>Driver:</strong> Mohamed A.</p>
                            <p><i class='bx bx-group'></i> <strong>Cleaners:</strong> Jenny Ramos, Sarah Kim, Mia Laoag, Chloe Jasmine Cruz, Ella Buenaflor, Rhea Generics, Joy Mae Tindugan, Anne Paraseo Santos</p>
                        </div>
                        <p class="price-detail">Final Price: <span class="aed-color">AED 600</span></p>
                    </div>
                </div>
                <div class="appointment-list-item" 
                    data-date="2023-12-20" 
                    data-time="13:00"
                    data-status="CANCELLED"
                    data-search-terms="ALZ-DC-2312-0005 December 20, 2023 1:00 PM 202 Palm Jumeirah, Dubai Apartment CANCELLED"
                    data-property-layout="2 Bedrooms, 2 Bathrooms"
                    data-materials-required="Yes"
                    data-materials-description="Standard cleaning kit (sponges, wipes, surface sprays)."
                    data-additional-request="Cancelled by Admin due to unexpected road closure in the area."
                    data-image-1=""
                    data-image-2=""
                    data-image-3=""
                >
                    <div class="button-group-top">
                        <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                    </div>
                    <div class="appointment-details">
                        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-DC-2312-0005</span></p>
                        <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> December 20, 2023</p>
                        <p><i class='bx bx-time'></i> <strong>Time:</strong> 1:00 PM</p>
                        <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 4 hours</p>
                        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 202 Palm Jumeirah, Dubai</p>
                        <hr class="divider full-width-detail">
                        <p><i class='bx bx-building-house'></i> <strong>Client Type:</strong> Apartment</p>
                        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> Deep Cleaning</p>
                        <p class="full-width-detail status-detail">
                            <strong>Status:</strong>
                            <span class="status-tag cancelled"><i class='bx bx-x-circle'></i> CANCELLED</span>
                        </p>
                        <div class="staff-details-container full-width-detail">
                            <h4><i class='bx bx-id-card'></i> Assigned Team</h4>
                            <p><i class='bx bx-car'></i> <strong>Driver:</strong> Mohamed A.</p>
                            <p><i class='bx bx-group'></i> <strong>Cleaners:</strong> Jenny Ramos, Sarah Kim, Mia Laoag, Chloe Jasmine Cruz, Ella Buenaflor, Rhea Generics, Joy Mae Tindugan, Anne Paraseo Santos</p>
                        </div>
                        <p class="price-detail">Estimated Price: <span class="aed-color">AED 400</span></p>
                    </div>
                </div>
            </div>
        </div>
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
        <button onclick="applyCustomRange(this.getAttribute('data-list-id'))" data-list-id="checkout-cleaning-list" class="primary-btn">Apply</button>
        <button onclick="closeModal('datePickerModal')" class="secondary-btn">Cancel</button>
    </div>
    
</div>
</div>

<div class="modal" id="detailsModal" onclick="if(event.target.id === 'detailsModal') closeModal('detailsModal')">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('detailsModal')">&times;</span> 

        <h3><i class='bx bx-file-text'></i> Appointment Details</h3>
        
        <div id="modal-details-content">
        </div>
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
            
            <button onclick="closeModal('reportSuccessModal')" class="primary-btn report-confirm-btn">
                Got It
            </button>
        </div>
    </div>
</div>

<div id="cancelSuccessModal" class="cancel-appointment-modal" onclick="if(event.target.id === 'cancelSuccessModal') closeModal('cancelSuccessModal')">
    <div class="cancel-modal-content">
        <span class="close-btn-x" onclick="closeModal('cancelSuccessModal')">&times;</span>
        
        <i class='bx bx-check-circle cancel-icon' style="color: #00A86B;"></i> <h3 style="border-bottom: none;">Appointment Cancelled!</h3>
        
        <p id="cancel-success-message">
            The appointment for Ref: 
            <strong id="cancelled-ref-number"></strong> has been successfully cancelled.
            <br>
            A notification has been sent to your registered email address.
        </p>
        
        <div class="modal__actions">
            <button onclick="closeModal('cancelSuccessModal')" class="primary-cancel-btn" style="background-color: #00A86B; border-color: #00A86B;">
                Got It
            </button>
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
/**
 * Function to display the Cancel Confirmation Modal.
 * It sets the reference number in the modal content.
 * @param {string} refNo - The reference number of the appointment to cancel.
 */
function showCancelModal(refNo) {
    document.getElementById('cancel-ref-number').innerText = refNo;
    const confirmCancelBtn = document.getElementById('confirmCancel');
    
    // Temporarily remove any previous click listener to prevent multiple submissions
    confirmCancelBtn.onclick = null;
    
    // Add new click listener that performs the cancellation (e.g., redirect or AJAX call)
    confirmCancelBtn.onclick = function() {
        console.log("Cancelling appointment: " + refNo);
        
        // 1. <strong>Close the initial confirmation modal</strong>
        closeModal('cancelModal');
        
        // 2. <strong>Display the custom success modal instead of the default alert()</strong>
        document.getElementById('cancelled-ref-number').innerText = refNo;
        // Tiyakin na ang tamang style ang gamit:
        document.getElementById('cancelSuccessModal').style.display = 'flex';
        
        // In a real application, you would perform an AJAX call here,
        // and only show the success modal if the server confirms cancellation.
    };

    // Set display to 'flex' to make the modal visible
    document.getElementById('cancelModal').style.display = 'flex'; 
}


/**
 * Closes any modal based on its ID.
 * @param {string} modalId - The ID of the modal to close.
 */
function closeModal(modalId) {
    let modal = document.getElementById(modalId);
    if (modal) {
        // Ang lahat ng modal ay dapat na i-set sa 'none' upang itago.
        modal.style.display = 'none';
    }
}
    
// --- START: Status Sorting Logic (Requested Change) ---

/**
 * Sorts appointment items within a container based on a predefined status order,
 * or filters them if a specific status is chosen.
 * Order: PENDING, CONFIRMED, ONGOING, COMPLETED, CANCELLED, NO SHOW
 * @param {string} containerId - The ID of the appointment list container (e.g., 'checkout-cleaning-list').
 * @param {string} filterStatus - The status to filter by (or 'default' to sort all).
 */
function sortAppointmentsByStatus(containerId, filterStatus = 'default') {
    const container = document.getElementById(containerId);
    if (!container) return;

    // The desired order of statuses for default sorting
    const statusOrder = {
        'PENDING': 1,
        'CONFIRMED': 2,
        'ONGOING': 3,
        'COMPLETED': 4,
        'CANCELLED': 5,
        'NO SHOW': 6
    };

    // Get all appointment items
    const items = Array.from(container.querySelectorAll('.appointment-list-item'));
    const noAppointmentsMessage = container.querySelector('.no-appointments-message');

    // 1. Apply Filtering (Visibility)
    let visibleCount = 0;
    items.forEach(item => {
        const itemStatus = item.getAttribute('data-status');
        
        // Show item if filterStatus is 'default' (meaning show all), or if it matches the item's status
        const isVisible = (filterStatus === 'default' || filterStatus === itemStatus);
        
        item.style.display = isVisible ? 'grid' : 'none';
        
        if (isVisible) {
            visibleCount++;
        }
    });
    
    // 2. Apply Default Sorting (only on visible items)
    if (filterStatus === 'default') {
        // Create a separate array of currently visible items for sorting
        const itemsToSort = items.filter(item => item.style.display !== 'none');
        
        itemsToSort.sort((a, b) => {
            const statusA = a.getAttribute('data-status');
            const statusB = b.getAttribute('data-status');
    
            const orderA = statusOrder[statusA] || 999; // Use a high number for unknown statuses
            const orderB = statusOrder[statusB] || 999; 
    
            // Return 0 if both statuses are unknown/equal, otherwise the difference in order
            return orderA - orderB;
        });

        // Re-append the sorted items to the container
        // Note: The structure requires appending to put them in order.
        if (noAppointmentsMessage) {
            container.appendChild(noAppointmentsMessage); // Keep the message on top
        }
        itemsToSort.forEach(item => {
            container.appendChild(item);
        });
    }


    // 3. Handle No Appointments Message
    if (noAppointmentsMessage) {
        if (visibleCount === 0) {
            noAppointmentsMessage.style.display = 'block';
            if (filterStatus !== 'default') {
                noAppointmentsMessage.innerHTML = `No ${filterStatus} appointments found for this service.`;
            } else {
                noAppointmentsMessage.innerHTML = `You have no appointments on record for this service.`;
            }
        } else {
            noAppointmentsMessage.style.display = 'none';
        }
    }

}


/**
 * Initializes default sorting for all relevant containers when the script loads.
 * This ensures the list is sorted by status on first load.
 */
function initializeStatusSortingOnLoad() {
    const containerIds = [
        'checkout-cleaning-list',
        'in-house-cleaning-list',
        'refresh-cleaning-list',
        'deep-cleaning-list'
    ];

    containerIds.forEach(id => {
        // Initial call uses 'default' to apply sorting to all visible items
        sortAppointmentsByStatus(id, 'default');
    });
}

// Call the initialization function when the page loads
document.addEventListener('DOMContentLoaded', initializeStatusSortingOnLoad);

// --- END: Status Sorting Logic (Requested Change) ---
</script> 
<script src="client_db.js"></script> 	 	 	
<script src="HIS_function.js"></script> 	
</body>
</html>