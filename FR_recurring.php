<?php
// PHP code for HIS_recurring.php would typically start here if it were a real dynamic file.
// Since this is a static representation, we start with the HTML/CSS/JS.
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
/* ---------------------------------------------------------------------- */
/* --- NEW/MODIFIED STYLES BASED ON REQUESTED LAYOUT (image_7901fd.png) --- */
/* ---------------------------------------------------------------------- */

/* 1. Remove Top Padding and Adjust Relative Position for Compact Layout */
.appointment-list-item {
    position: relative; /* Keep relative for absolute children */
    padding-top: 10px; /* Reduced from 15px/50px to almost nothing, only space for top/bottom margin/padding */
    padding-bottom: 15px; /* Keep bottom padding */
    padding-left: 15px;
    padding-right: 15px;
    display: flex; 
    flex-wrap: wrap; 
    align-items: flex-start;
}

/* 2. Style for the Overall Plan Status Tag */
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
/* Status Colors (Existing, just kept for reference) */
.overall-active { background-color: #e8f9e8; color: #008a00; }
.overall-paused { background-color: #fff8e1; color: #ff8f00; }

/* MODIFIED: Sinama ang .overall-cancelled para may tamang kulay ang Cancelled plans */
.overall-terminated,
.overall-cancelled { background-color: #ffe8e8; color: #d32f2f; } 

.overall-completed { background-color: #e0f2f1; color: #00796b; }
.overall-pending { background-color: #e0e5ea; color: #495057; border: 1px solid #c4ccd5; }

/* NEW: Style para sa UNKNOWN/walang laman na status (para hindi lang default gray) */
.overall-unknown,
.status-tag.unknown { 
    background-color: #f0f0f0; /* Light Gray for neutral UNKNOWN */
    color: #555; /* Dark text for neutral UNKNOWN */
}
/* 3. Keep button group top-right positioned */
.button-group-top {
    position: absolute;
    top: 10px; /* Keep buttons near the top */
    right: 10px;
    display: flex;
    gap: 5px;
}

/* 4. Tweak spacing for Appointment Details */
.appointment-details {
    margin-top: 5px; /* Added slight top margin to push down a bit from Ref No/Buttons */
    width: 100%; 
    flex-grow: 1;
}

/* 5. Style for the new Status Filter Dropdown */
.status-filter-dropdown {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1em;
    cursor: pointer;
    background-color: #fff;
    width: 170px; /* Set a fixed width */
    height: 40px;
    flex-shrink: 0;
}
/* ---------------------------------------------------------------------- */
/* --- END OF MODIFIED STYLES --- */
/* ---------------------------------------------------------------------- */

/* Existing styles for buttons/etc. */
.action-btn.edit-plan-btn{
    padding: 8px 12px; font-weight: bold; cursor: pointer; border-radius: 6px; text-align: center; transition: all 0.3s; text-decoration: none; display: flex; align-items: center; gap: 5px; font-size: 0.9em; background-color: #0056b3; border: 2px solid #0056b3; color: white;
}
.action-btn.edit-plan-btn:hover { background-color: #0062cc; border-color: #0062cc; }
.action-btn.sessions-btn {
    padding: 8px 12px; font-weight: bold; cursor: pointer; border-radius: 6px; text-align: center; transition: all 0.3s; text-decoration: none; display: flex; align-items: center; gap: 5px; font-size: 0.9em; background-color: #008080; border: 2px solid #008080; color: white;
}
.action-btn.sessions-btn:hover { background-color: #009999; border-color: #009999; }
.dropdown-menu .whatsapp-chat-link i { color: #25D366; }
.appointment-details .ref-no-detail { margin-bottom: 15px; } /* Keep gap below Ref No */



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

.info-tooltip-content li <strong>strong</strong> {
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
            <i class='bx bx-star'></i> Recurring Service Feedback/Ratings
            </h2>
            <p class="page-description">
            List of your recurring cleaning plans. You can view individual session details and provide feedback accordingly.
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
                        You can leave a feedback for <strong>Completed</strong>  sessions only under your active, paused, cancelled, or terminated plans.
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
        
        <div id="weekly-cleaning" class="tab-content" style="display: block;">
            
            <div class="filter-controls-tab">
                
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'weekly-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <select class="status-filter-dropdown" onchange="sortAppointmentsByStatus('weekly-cleaning-list', this.value)">
                    <option value="default">Sort by Status</option>
                    <option value="ACTIVE">Active</option>
                    <option value="PAUSED">Paused</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'weekly-cleaning-list')">
                </div>
                
            </div>

            <div class="appointment-list-container" id="weekly-cleaning-list">
                <div class="no-appointments-message" data-service-name="Weekly Cleaning"></div> 
                
               
                <div class="appointment-list-item" 
                    data-date="2024-10-05" 
                    data-end-date="2025-12-31" 
                    data-time="09:00"
                    data-plan-status="ACTIVE"
                    data-search-terms="ALZ-WK-2410-0016 October 05, 2024 9:00 AM 707 Downtown, Dubai Residential ACTIVE"
                    data-property-layout="1 Bedroom, 1 Bathroom"
                    data-materials-required="Yes"
                    data-materials-description="All-purpose cleaner, glass cleaner, floor mop/bucket."
                    data-additional-request="Focus on cleaning the windows in the living area."
                    data-image-1="https://alazima.com/files/ALZ-CC-2410-0016_img1.jpg"
                    data-image-2=""
                    data-image-3=""
                >
                    <div class="button-group-top">
                    <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showRecurringDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="javascript:void(0)" class="action-btn sessions-btn">
                            <i class='bx bx-list-ul'></i> Sessions
                        </a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-chat-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="report-link" onclick="showReportModal(this)"><i class='bx bx-error-alt'></i> Report Issue</a></li>
                            </ul>
                        </div>
                    </div>
                   
                </div>
            </div>
        </div>
        
        <div id="biweekly-cleaning" class="tab-content" style="display: none;">
            
            <div class="filter-controls-tab">
                
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'biweekly-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <select class="status-filter-dropdown" onchange="sortAppointmentsByStatus('biweekly-cleaning-list', this.value)">
                    <option value="default">Sort by Status</option>
                    <option value="ACTIVE">Active</option>
                    <option value="PAUSED">Paused</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'biweekly-cleaning-list')">
                </div>
                
            </div>

            <div class="appointment-list-container" id="biweekly-cleaning-list">
                <div class="no-appointments-message" data-service-name="Bi-Weekly Cleaning"></div>
                
                <div class="appointment-list-item" 
                    data-date="2024-09-20" 
                    data-end-date="2024-10-15"
                    data-time="14:00"
                    data-plan-status="COMPLETED"
                    data-search-terms="ALZ-BWK-2409-0012 September 20, 2024 2:00 PM 606 JLT, Dubai Residential COMPLETED"
                    data-property-layout="Studio, 1 Bathroom"
                    data-materials-required="No"
                    data-materials-description="N/A"
                    data-additional-request="Standard bi-weekly clean."
                    data-image-1="https://alazima.com/files/ALZ-IH-2409-0012_img1.jpg"
                    data-image-2=""
                    data-image-3=""
                >
                    <div class="button-group-top">
                    <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showRecurringDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="javascript:void(0)" class="action-btn sessions-btn">
                            <i class='bx bx-list-ul'></i> Sessions
                        </a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-chat-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="report-link" onclick="showReportModal(this)"><i class='bx bx-error-alt'></i> Report Issue</a></li>
                            </ul>
                        </div>
                    </div>
                    
                </div>
                
                <div class="appointment-list-item" 
                    data-date="2024-10-10" 
                    data-end-date="2025-12-31" 
                    data-time="11:00"
                    data-plan-status="ACTIVE"
                    data-search-terms="ALZ-BWK-2410-0018 October 10, 2024 11:00 AM 404 Dubai Marina, Dubai Residential ACTIVE (Sample)"
                    data-property-layout="1 Bedroom, 1 Bathroom"
                    data-materials-required="No"
                    data-materials-description="N/A"
                    data-additional-request="New client, standard cleaning."
                    data-image-1=""
                    data-image-2=""
                    data-image-3=""
                >
                    <div class="button-group-top">
                    <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showRecurringDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="javascript:void(0)" class="action-btn sessions-btn">
                            <i class='bx bx-list-ul'></i> Sessions
                        </a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-chat-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="report-link" onclick="showReportModal(this)"><i class='bx bx-error-alt'></i> Report Issue</a></li>
                            </ul>
                        </div>
                    </div>
                    
                </div>
                
                <div class="appointment-list-item" 
                    data-date="2024-09-15" 
                    data-end-date="2025-12-31" 
                    data-time="10:00"
                    data-plan-status="PAUSED"
                    data-search-terms="ALZ-BWK-2409-0013 September 15, 2024 10:00 AM 303 Business Bay, Dubai Residential PAUSED"
                    data-property-layout="2 Bedrooms, 2 Bathrooms"
                    data-materials-required="Yes"
                    data-materials-description="Microfiber cloths, bathroom scrubber, floor disinfectant."
                    data-additional-request="Client will be 30 mins late. Please start in the kitchen."
                    data-image-1=""
                    data-image-2=""
                    data-image-3=""
                >
                    <div class="button-group-top">
                    <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showRecurringDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="javascript:void(0)" class="action-btn sessions-btn">
                            <i class='bx bx-list-ul'></i> Sessions
                        </a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-chat-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="report-link" onclick="showReportModal(this)"><i class='bx bx-error-alt'></i> Report Issue</a></li>
                            </ul>
                        </div>
                    </div>
                   >
                </div>
                
              
                
                <div class="appointment-list-item" 
                    data-date="2024-09-01" 
                    data-end-date="2025-12-31" 
                    data-time="09:00"
                    data-plan-status="ACTIVE"
                    data-search-terms="ALZ-BWK-2409-0001 September 01, 2024 9:00 AM 101 Downtown, Dubai Residential ACTIVE"
                    data-property-layout="4 Bedrooms, 5 Bathrooms (Villa)"
                    data-materials-required="Yes"
                    data-materials-description="Heavy-duty floor cleaner, 4 trash bags, long-reach duster."
                    data-additional-request="None."
                    data-image-1="https://alazima.com/files/ALZ-IH-2409-0001_img1.jpg"
                    data-image-2="https://alazima.com/files/ALZ-IH-2409-0001_img2.jpg"
                    data-image-3="https://alazima.com/files/ALZ-IH-2409-0001_img3.jpg"
                >
                    <div class="button-group-top">
                    <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showRecurringDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="javascript:void(0)" class="action-btn sessions-btn">
                            <i class='bx bx-list-ul'></i> Sessions
                        </a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-chat-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="report-link" onclick="showReportModal(this)"><i class='bx bx-error-alt'></i> Report Issue</a></li>
                            </ul>
                        </div>
                    </div>
                   
                </div>
            </div>
        </div>
        
        <div id="monthly-cleaning" class="tab-content" style="display: none;">
            
            <div class="filter-controls-tab">
                
                <select class="date-filter-dropdown" onchange="handleFilterChange(this, 'monthly-cleaning-list')">
                    <option value="last7days">Last 7 Days</option>
                    <option value="last30days">Last 30 Days</option>
                    <option value="this_year">This Year</option>
                    <option value="all" selected>All Time</option>
                    <option value="customrange">Custom Range</option>
                </select>
                
                <select class="status-filter-dropdown" onchange="sortAppointmentsByStatus('monthly-cleaning-list', this.value)">
                    <option value="default">Sort by Status</option>
                    <option value="ACTIVE">Active</option>
                    <option value="PAUSED">Paused</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'monthly-cleaning-list')">
                </div>
                
            </div>

            <div class="appointment-list-container" id="monthly-cleaning-list">
                <div class="no-appointments-message" data-service-name="Monthly Cleaning"></div>
                
                <div class="appointment-list-item" 
                    data-date="2024-09-25" 
                    data-end-date="2024-12-25" 
                    data-time="08:00"
                    data-plan-status="COMPLETED"
                    data-search-terms="ALZ-MTH-2409-0010 September 25, 2024 8:00 AM 101 Marina View, Dubai Residential COMPLETED"
                    data-property-layout="5 Bedrooms, 6 Bathrooms (Villa)"
                    data-materials-required="Yes"
                    data-materials-description="Steam cleaner, heavy-duty floor cleaner, post-construction debris bags."
                    data-additional-request="Full post-construction cleaning required. Please bring heavy-duty equipment."
                    data-image-1="https://alazima.com/files/ALZ-DC-2409-0010_img1.jpg"
                    data-image-2="https://alazima.com/files/ALZ-DC-2409-0010_vid2.mp4"
                    data-image-3="https://alazima.com/files/ALZ-DC-2409-0010_img3.jpg"
                >
                    <div class="button-group-top">
                    <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showRecurringDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="javascript:void(0)" class="action-btn sessions-btn">
                            <i class='bx bx-list-ul'></i> Sessions
                        </a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-chat-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="report-link" onclick="showReportModal(this)"><i class='bx bx-error-alt'></i> Report Issue</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="appointment-details">
                        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-MTH-2409-0010</span></p>
                        <p><i class='bx bx-calendar-check'></i> <strong>Start Date:</strong> September 25, 2024</p> <p class="end-date-detail"><i class='bx bx-calendar-check'></i> <strong>End Date:</strong> December 25, 2024</p>
                        <p><i class='bx bx-time'></i> <strong>Time:</strong> 8:00 AM</p> <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 6 hours</p>
                        <p class="frequency-detail"><i class='bx bx-sync'></i> <strong>Frequency:</strong> Monthly</p> <p class="sessions-detail"><i class='bx bx-list-ol'></i> <strong>No. of Sessions:</strong> <span class="sessions-count">4 of 4</span></p>
                        
                        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 101 Marina View, Dubai</p>
                        <hr class="divider full-width-detail">
                        <p><i class='bx bx-home-heart'></i> <strong>Client Type:</strong> Residential</p>
                        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> General Cleaning</p>

                        <p class="full-width-detail status-detail">
                            <strong>Plan Status:</strong>
                            <span class="overall-plan-tag overall-completed"><i class='bx bx-check-double'></i> COMPLETED</span>
                        </p>
                        <p class="price-detail">Final Price: <span class="aed-color">AED 600</span></p>
                    </div>
                </div>
                
                 <div class="appointment-list-item" 
                    data-date="2023-12-01" 
                    data-end-date="2024-01-01" 
                    data-time="12:00"
                    data-plan-status="CANCELLED"
                    data-search-terms="ALZ-MTH-2312-0005 December 01, 2023 12:00 PM 505 JVC, Dubai Residential CANCELLED (Sample)"
                    data-property-layout="Studio, 1 Bathroom"
                    data-materials-required="No"
                    data-materials-description="N/A"
                    data-additional-request="Client moved out after the first cleaning. Service cancelled."
                    data-image-1=""
                    data-image-2=""
                    data-image-3=""
                >
                    <div class="button-group-top">
                    <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showRecurringDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="javascript:void(0)" class="action-btn sessions-btn">
                            <i class='bx bx-list-ul'></i> Sessions
                        </a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-chat-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="report-link" onclick="showReportModal(this)"><i class='bx bx-error-alt'></i> Report Issue</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="appointment-details">
                        <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-MTH-2312-0005</span></p>
                        <p><i class='bx bx-calendar-check'></i> <strong>Start Date:</strong> December 01, 2023</p> <p class="end-date-detail"><i class='bx bx-calendar-check'></i> <strong>End Date:</strong> January 01, 2024</p>
                        <p><i class='bx bx-time'></i> <strong>Time:</strong> 12:00 PM</p> <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 3 hours</p>
                        <p class="frequency-detail"><i class='bx bx-sync'></i> <strong>Frequency:</strong> Monthly</p> <p class="sessions-detail"><i class='bx bx-list-ol'></i> <strong>No. of Sessions:</strong> <span class="sessions-count">1 of 2</span></p>
                        
                        <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 505 JVC, Dubai</p>
                        <hr class="divider full-width-detail">
                        <p><i class='bx bx-building-house'></i> <strong>Client Type:</strong> Residential</p>
                        <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> General Cleaning</p>

                        <p class="full-width-detail status-detail">
                            <strong>Plan Status:</strong>
                            <span class="overall-plan-tag overall-cancelled"><i class='bx bx-x-circle'></i> CANCELLED</span>
                        </p>
                        <p class="price-detail">Final Price: <span class="aed-color">AED 300</span></p>
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
        <button onclick="applyCustomRange(this.getAttribute('data-list-id'))" data-list-id="weekly-cleaning-list" class="primary-btn">Apply</button>
        <button onclick="closeModal('datePickerModal')" class="secondary-btn">Cancel</button>
    </div>
    
</div>
</div>

<div class="modal" id="detailsModal" onclick="if(event.target.id === 'detailsModal') closeModal('detailsModal')">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('detailsModal')">&times;</span> 

        <h3><i class='bx bx-list-ul'></i> Plan Sessions Overview</h3>
        
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

<script src="client_db.js"></script> 	 	 	
<script src="HIS_function.js"></script> 	

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
        
        // 1. **Close the initial confirmation modal**
        closeModal('cancelModal');
        
        // 2. **Display the custom success modal instead of the default alert()**
        document.getElementById('cancelled-ref-number').innerText = refNo;
        document.getElementById('cancelSuccessModal').style.display = 'flex';
        
        // In a real application, you would perform an AJAX call here,
        // and only show the success modal if the server confirms cancellation.
    };

    // Set display to 'flex' to make the modal visible
    document.getElementById('cancelModal').style.display = 'flex'; 
}

// --- START: Status Sorting Logic (Updated for 5 core statuses) ---

/**
 * Sorts appointment items within a container based on a predefined status order,
 * or filters them if a specific status is chosen.
 * Core Order (Plan Status): ACTIVE=1, PAUSED=2, COMPLETED=3, CANCELLED=4 
 * @param {string} containerId - The ID of the appointment list container (e.g., 'weekly-cleaning-list').
 * @param {string} filterValue - The value from the dropdown to filter/sort by.
 */
function sortAppointmentsByStatus(containerId, filterValue = 'default') {
    const container = document.getElementById(containerId);
    if (!container) return;

    // Order for Sorting: ACTIVE=1, PAUSED=2, COMPLETED=3, CANCELLED=4
    const statusSortOrder = {
        'ACTIVE': 1,
        'PAUSED': 2,
        'COMPLETED': 3,
        'CANCELLED': 4 
    };
    
    // Mapping for Filtering: Maps dropdown value to the core data-plan-status
    const filterMapping = {
        'ACTIVE': ['ACTIVE'], 
        'PAUSED': ['PAUSED'],
        'COMPLETED': ['COMPLETED'],
        'CANCELLED': ['CANCELLED'] 
    };

    const items = Array.from(container.querySelectorAll('.appointment-list-item'));
    const noAppointmentsMessage = container.querySelector('.no-appointments-message');
    const serviceName = noAppointmentsMessage ? noAppointmentsMessage.getAttribute('data-service-name') : 'appointments';

    // 1. Apply Filtering (Visibility)
    let visibleCount = 0;
    
    items.forEach(item => {
        const itemPlanStatus = item.getAttribute('data-plan-status'); 
        let isVisible = false;
        
        // Since all items with 0 sessions (including PENDING) are now removed from HTML,
        // we only need to check against the active status filters.
        if (filterValue === 'default') {
            // Show all items currently in the HTML (which are guaranteed to have >= 1 session)
            // and are not PENDING (implicitly handled by HTML removal)
            isVisible = true; 
        } else {
            // Filter by the selected status (which no longer includes PENDING)
            const targetStatuses = filterMapping[filterValue] || [];
            if (targetStatuses.includes(itemPlanStatus)) {
                isVisible = true;
            }
        }
        
        item.style.display = isVisible ? 'flex' : 'none'; 
        
        if (isVisible) {
            visibleCount++;
        }
    });
    
    // 2. Apply Default Sorting (always sort by priority when filter is 'default' or after filtering)
    if (filterValue === 'default') {
        
        const itemsToSort = items.filter(item => item.style.display !== 'none');
        
        itemsToSort.sort((a, b) => {
            const statusA = a.getAttribute('data-plan-status');
            const statusB = b.getAttribute('data-plan-status');
    
            // Use the redefined statusSortOrder which excludes PENDING
            const orderA = statusSortOrder[statusA] || 999;
            const orderB = statusSortOrder[statusB] || 999; 
    
            return orderA - orderB;
        });

        // Re-append the sorted items to the container
        if (noAppointmentsMessage) {
            container.prepend(noAppointmentsMessage);
        }
        itemsToSort.forEach(item => {
            container.appendChild(item);
        });
    }

    // 3. Handle No Appointments Message
    if (noAppointmentsMessage) {
        if (visibleCount === 0) {
            noAppointmentsMessage.style.display = 'block';
            if (filterValue !== 'default') {
                // Display the name of the filtered status
                let displayName = filterValue.charAt(0).toUpperCase() + filterValue.slice(1).toLowerCase();
                noAppointmentsMessage.innerHTML = `No ${displayName} ${serviceName} plans found.`;
            } else {
                // Modified message for 'default' to reflect the active filtering rule
                noAppointmentsMessage.innerHTML = `You have no ${serviceName} plans with completed sessions on record.`;
            }
        } else {
            noAppointmentsMessage.style.display = 'none';
        }
    }

}


/**
 * Initializes default sorting for all relevant containers when the script loads.
 */
function initializeStatusSortingOnLoad() {
    // Corrected container IDs for this recurring file
    const containerIds = [
        'weekly-cleaning-list',
        'biweekly-cleaning-list',
        'monthly-cleaning-list'
    ];

    containerIds.forEach(id => {
        // Initial call uses 'default' to apply sorting to all visible items (which now excludes 0-session items)
        sortAppointmentsByStatus(id, 'default');
    });
}

// Call the initialization function when the page loads
document.addEventListener('DOMContentLoaded', initializeStatusSortingOnLoad);

// --- END: Status Sorting Logic (Updated for 5 core statuses) ---


document.addEventListener('DOMContentLoaded', () => {
    // Logic to set href for Sessions buttons AND hide button if PENDING
    const sessionButtons = document.querySelectorAll('.sessions-btn');
    
    sessionButtons.forEach(button => {
        const listItem = button.closest('.appointment-list-item');
        if (listItem) {
            const planStatus = listItem.getAttribute('data-plan-status'); 
            
            // HIDE Sessions button if the plan status is PENDING
            // *NOTE: This check is now redundant since PENDING items are removed from HTML, 
            // but kept as a safeguard based on previous code structure.*
            if (planStatus === 'PENDING') {
                button.style.display = 'none';
            } else {
                // Set href for Sessions button if status is NOT PENDING
                const refNoElement = listItem.querySelector('.ref-no-value');
                if (refNoElement) {
                    const refNo = refNoElement.textContent.trim();
                    button.href = `HIS_sessions.php?ref=${encodeURIComponent(refNo)}`;
                    button.removeAttribute('onclick'); 
                    button.style.display = 'flex'; // Ensure visible if not PENDING (default button display is flex)
                }
            }
        }
    });
});
</script>
</body>
</html>