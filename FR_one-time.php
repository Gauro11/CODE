<?php
// PHP code for FR_one-time.php
// This file is a mirror of HIS_one-time.php but filtered for COMPLETED jobs
// and adjusted for the Feedback/Ratings page.
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
/* Custom style for the service type filter dropdown in Issues and Concern tab */
.service-type-filter-dropdown {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 0.8em;
    cursor: pointer;
    background-color: #fff;
    width: 170px; /* Fixed width */
    height: 40px; /* Fixed height */
    flex-shrink: 0;
}

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
                
                <div class="appointment-list-item" 
                    data-date="2024-09-28" 
                    data-time="11:00"
                    data-duration="5 hours"
                    data-client-type="Apartment"
                    data-service-type="Checkout Cleaning"
                    data-address="505 Business Bay, Dubai"
                    data-search-terms="ALZ-CC-2409-0015 September 28, 2024 11:00 AM 505 Business Bay, Dubai Apartment COMPLETED RATED"
                    data-property-layout="2 Bedrooms, 2 Bathrooms"
                    data-materials-required="No"
                    data-materials-description="N/A"
                    data-additional-request="Please use the client's vacuum cleaner. Ensure the kitchen cabinets are spotless."
                    data-image-1="https://alazima.com/files/ALZ-CC-2409-0015_img1.jpg"
                    data-image-2="https://alazima.com/files/ALZ-CC-2409-0015_img2.mp4"
                    data-image-3=""
                    data-has-feedback="true"
                    data-rating-stars="4" 
                    data-rating-feedback="The team was efficient and professional. Just a minor spot missed, but overall great job."
                    data-status="COMPLETED"
                    data-ref-no="ALZ-CC-2409-0015"
                    data-has-issue="true" > <div class="button-group-top">
                    <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <button type="button" class="action-btn feedback-btn" onclick="showViewRatingModal(this.closest('.appointment-list-item'))">
                            <i class='bx bx-star'></i> View Rating
                        </button>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="FR_one-time_form.php?ref=ALZ-CC-2409-0015&action=edit" class="edit-rating-link"><i class='bx bx-edit'></i> Edit Rating</a></li>
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="report-link view-issue-link" onclick="viewReportedIssue(this.closest('.appointment-list-item'))"><i class='bx bx-error-alt'></i> View Reported Issue</a></li>
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
                            <span class="status-tag rated-style" style="background-color: #fce899; color: #9c6c00; border: 1px solid #f9d857;"><i class='bx bx-star'></i> RATED</span>
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
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'in-house-cleaning-list')">
                </div>
                
            </div>

            <div class="appointment-list-container" id="in-house-cleaning-list">
                <div class="no-appointments-message" data-service-name="In-House Cleaning"></div>
                
                <div class="appointment-list-item" 
                    data-date="2024-09-20" 
                    data-time="14:00"
                    data-duration="3 hours"
                    data-client-type="Apartment"
                    data-service-type="In-House Cleaning"
                    data-address="606 JLT, Dubai"
                    data-search-terms="ALZ-IH-2409-0012 September 20, 2024 2:00 PM 606 JLT, Dubai Apartment COMPLETED"
                    data-property-layout="Studio, 1 Bathroom"
                    data-materials-required="No"
                    data-materials-description="N/A"
                    data-additional-request="Standard bi-weekly clean."
                    data-image-1="https://alazima.com/files/ALZ-IH-2409-0012_img1.jpg"
                    data-image-2=""
                    data-image-3=""
                    data-has-feedback="false"
                    data-status="COMPLETED"
                    data-ref-no="ALZ-IH-2409-0012"
                    data-has-issue="true" > <div class="button-group-top">
                    <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="FR_one-time_form.php?ref=ALZ-IH-2409-0012&action=leave" class="action-btn feedback-btn"><i class='bx bx-star'></i> Rate Now</a>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="https://wa.me/971529009188" target="_blank" class="whatsapp-link"><i class='bx bxl-whatsapp'></i> Chat on WhatsApp</a></li>
                                <li><a href="javascript:void(0)" class="report-link view-issue-link" onclick="viewReportedIssue(this.closest('.appointment-list-item'))"><i class='bx bx-error-alt'></i> View Reported Issue</a></li>
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
                            <span class="status-tag pending"><i class='bx bx-hourglass'></i> NOT YET RATED</span>
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
                    data-duration="4 hours"
                    data-client-type="Villa"
                    data-service-type="In-House Cleaning"
                    data-address="101 Downtown, Dubai"
                    data-search-terms="ALZ-IH-2409-0001 September 01, 2024 9:00 AM 101 Downtown, Dubai Villa COMPLETED RATED"
                    data-property-layout="4 Bedrooms, 5 Bathrooms (Villa)"
                    data-materials-required="Yes"
                    data-materials-description="Heavy-duty floor cleaner, 4 trash bags, long-reach duster."
                    data-additional-request="None."
                    data-image-1="https://alazima.com/files/ALZ-IH-2409-0001_img1.jpg"
                    data-image-2="https://alazima.com/files/ALZ-IH-2409-0001_img2.jpg"
                    data-image-3="https://alazima.com/files/ALZ-IH-2409-0001_img3.jpg"
                    data-has-feedback="true" 
                    data-rating-stars="5" 
                    data-rating-feedback="Perfect job, exceeded expectations! Will definitely book again."
                    data-status="COMPLETED"
                    data-ref-no="ALZ-IH-2409-0001" > <div class="button-group-top">
                    <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <button type="button" class="action-btn feedback-btn" onclick="showViewRatingModal(this.closest('.appointment-list-item'))">
                            <i class='bx bx-star'></i> View Rating
                        </button>
                        <div class="dropdown-menu-container">
                            <button class="more-options-btn" onclick="toggleDropdown(this)"><i class='bx bx-dots-vertical-rounded'></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="FR_one-time_form.php?ref=ALZ-IH-2409-0001&action=edit" class="edit-rating-link"><i class='bx bx-edit'></i> Edit Rating</a></li>
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
                            <span class="status-tag rated-style" style="background-color: #fce899; color: #9c6c00; border: 1px solid #f9d857;"><i class='bx bx-star'></i> RATED</span>
                        </p>
                        <div class="staff-details-container full-width-detail">
                            <h4><i class='bx bx-id-card'></i> Assigned Team</h4>
                            <p><i class='bx bx-car'></i> <strong>Driver:</strong> Mohamed A.</p>
                            <p><i class='bx bx-group'></i> <strong>Cleaners:</strong> Jenny Ramos, Sarah Kim, Mia Laoag, Chloe Jasmine Cruz, Ella Buenaflor, Rhea Generics, Joy Mae Tindugan, Anne Paraseo Santos</p>
                        </div>
                        <p class="price-detail">Final Price: <span class="aed-color">AED 400</span></p>
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
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'refresh-cleaning-list')">
                </div>
                
            </div>

            <div class="appointment-list-container" id="refresh-cleaning-list">
                <div class="no-appointments-message" data-service-name="Refresh Cleaning"></div>
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
                
                <div class="search-container">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search Here..." onkeyup="filterHistory(this, null, 'deep-cleaning-list')">
                </div>
                
            </div>

            <div class="appointment-list-container" id="deep-cleaning-list">
                 <div class="no-appointments-message" data-service-name="Deep Cleaning"></div>
                
                <div class="appointment-list-item" 
                    data-date="2024-09-25" 
                    data-time="08:00"
                    data-duration="6 hours"
                    data-client-type="Villa"
                    data-service-type="Deep Cleaning"
                    data-address="101 Marina View, Dubai"
                    data-search-terms="ALZ-DC-2409-0010 September 25, 2024 8:00 AM 101 Marina View, Dubai Villa COMPLETED"
                    data-property-layout="5 Bedrooms, 6 Bathrooms (Villa)"
                    data-materials-required="Yes"
                    data-materials-description="Steam cleaner, heavy-duty floor cleaner, post-construction debris bags."
                    data-additional-request="Full post-construction cleaning required. Please bring heavy-duty equipment."
                    data-image-1="https://alazima.com/files/ALZ-DC-2409-0010_img1.jpg"
                    data-image-2="https://alazima.com/files/ALZ-DC-2409-0010_vid2.mp4"
                    data-image-3="https://alazima.com/files/ALZ-DC-2409-0010_img3.jpg"
                    data-has-feedback="false"
                    data-status="COMPLETED"
                    data-ref-no="ALZ-DC-2409-0010" > <div class="button-group-top">
                    <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest('.appointment-list-item'))"><i class='bx bx-show'></i> View Details</a>
                        <a href="FR_one-time_form.php?ref=ALZ-DC-2409-0010&action=leave" class="action-btn feedback-btn"><i class='bx bx-star'></i> Rate Now</a>
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
                            <span class="status-tag pending"><i class='bx bx-hourglass'></i> NOT YET RATED</span>
                        </p>
                        <div class="staff-details-container full-width-detail">
                            <h4><i class='bx bx-id-card'></i> Assigned Team</h4>
                            <p><i class='bx bx-car'></i> <strong>Driver:</strong> Mohamed A.</p>
                            <p><i class='bx bx-group'></i> <strong>Cleaners:</strong> Jenny Ramos, Sarah Kim, Mia Laoag, Chloe Jasmine Cruz, Ella Buenaflor, Rhea Generics, Joy Mae Tindugan, Anne Paraseo Santos</p>
                        </div>
                        <p class="price-detail">Final Price: <span class="aed-color">AED 600</span></p>
                        </div>
                </div>
            </div>
        </div>
        
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
                            <span class="rating-value">4.5 / 5.0</span>
                        </div>

                        <p class="average-rating-label">
                            Average Rating Across All Services
                        </p>
                        
                        <div class="stats-grid-container">
                            
                            <div class="stat-tile rated-services-tile">
                                <div class="tile-icon-group">
                                    <i class='bx bx-check-circle'></i>
                                </div>
                                <span class="tile-value rated-count">5</span> <span class="tile-label">Rated Services</span>
                            </div>

                            <div class="stat-tile highest-rating-tile">
                                <div class="tile-icon-group">
                                    <i class='bx bxs-medal'></i>
                                </div>
                                <span class="tile-value highest-rating">5<small> Stars</small></span>
                                <span class="tile-label">Highest Rating</span>
                            </div>

                            <div class="stat-tile awaiting-rating-tile">
                                <div class="tile-icon-group">
                                    <i class='bx bx-hourglass'></i>
                                </div>
                                <span class="tile-value awaiting-rating">2</span>
                                <span class="tile-label">Awaiting Rating</span>
                            </div>
                            
                        </div>
                        </div>
                    
                    <div class="breakdown-container">
                        <h4>Ratings Breakdown by Service Type</h4>
                        <ul class="ratings-breakdown-list">
                            <li>
                                <strong>Checkout Cleaning:</strong> 
                                <span>3.5 Stars (2 ratings)</span>
                            </li>
                            <li>
                                <strong>In-House Cleaning:</strong> 
                                <span>4.5 Stars (2 ratings)</span>
                            </li>
                            <li>
                                <strong>Refresh Cleaning:</strong> 
                                <span class="no-ratings">No Ratings Yet</span>
                            </li>
                            <li>
                                <strong>Deep Cleaning:</strong> 
                                <span>5.0 Stars (1 rating)</span>
                            </li>
                        </ul>
                    </div>

                </div>
                
            <div class="detailed-ratings-list-container">
                
                <h4>All Submitted Ratings (5 Total)</h4>
                
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
                <div class="rating-list-item" 
                    data-ref-no="ALZ-CC-2409-0015" 
                    data-service-tab="checkout-cleaning"
                    data-rating-stars="4" 
                    data-rating-feedback="The team was efficient and professional. Just a minor spot missed, but overall great job."
                    data-date="2024-09-28" 
                    data-time="11:00"
                    data-duration="5 hours"
                    data-client-type="Apartment"
                    data-service-type="Checkout Cleaning"
                    data-address="505 Business Bay, Dubai"
                    > <div class="item-header">
                        <div class="item-title-group">
                            <strong>Checkout Cleaning (Ref: <span>ALZ-CC-2409-0015</span>)</strong>
                            <div class="item-rating-stars">
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bx-star'></i>
                            </div>
                        </div>
                        <div class="item-actions-group">
                             <button class="view-rating-btn" onclick="showViewRatingModal(this.closest('.rating-list-item'))">
                                 <i class='bx bx-star'></i> View Rating
                             </button>
                             <button class="view-appointment-btn" onclick="viewAppointmentFromRating(this)">
                                 <i class='bx bx-show'></i> View Appointment
                             </button>
                        </div>
                    </div>
                    <p class="item-details">
                        <span>Rated: September 29, 2024</span>
                        <span>Service Date: September 28, 2024</span>
                        <span>Service Type: Checkout Cleaning</span>
                    </p>
                    <div class="item-feedback">
                        "The team was efficient and professional. Just a minor spot missed, but overall great job."
                    </div>
                </div>

                <div class="rating-list-item" 
                    data-ref-no="ALZ-IH-2409-0001" 
                    data-service-tab="in-house-cleaning"
                    data-rating-stars="5" 
                    data-rating-feedback="Perfect job, exceeded expectations! Will definitely book again."
                    data-date="2024-09-01" 
                    data-time="09:00"
                    data-duration="4 hours"
                    data-client-type="Villa"
                    data-service-type="In-House Cleaning"
                    data-address="101 Downtown, Dubai"
                    > <div class="item-header">
                        <div class="item-title-group">
                            <strong>In-House Cleaning (Ref: <span>ALZ-IH-2409-0001</span>)</strong>
                            <div class="item-rating-stars">
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                            </div>
                        </div>
                         <div class="item-actions-group">
                             <button class="view-rating-btn" onclick="showViewRatingModal(this.closest('.rating-list-item'))">
                                 <i class='bx bx-star'></i> View Rating
                             </button>
                             <button class="view-appointment-btn" onclick="viewAppointmentFromRating(this)">
                                 <i class='bx bx-show'></i> View Appointment
                             </button>
                        </div>
                    </div>
                    <p class="item-details">
                        <span>Rated: September 02, 2024</span>
                        <span>Service Date: September 01, 2024</span>
                        <span>Service Type: In-House Cleaning</span>
                    </p>
                    <div class="item-feedback">
                        "Perfect job, exceeded expectations! Will definitely book again."
                    </div>
                </div>
                
                <div class="rating-list-item" 
                    data-ref-no="ALZ-IH-2408-0120" 
                    data-service-tab="in-house-cleaning"
                    data-rating-stars="4" 
                    data-rating-feedback="Good service overall, the crew arrived a bit late but finished on time. Everything was clean."
                    data-date="2024-08-28" 
                    data-time="10:00"
                    data-duration="3 hours"
                    data-client-type="Apartment"
                    data-service-type="In-House Cleaning"
                    data-address="202 Al Khail Heights, Dubai"
                    > <div class="item-header">
                        <div class="item-title-group">
                            <strong>In-House Cleaning (Ref: <span>ALZ-IH-2408-0120</span>)</strong>
                            <div class="item-rating-stars">
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bx-star'></i>
                            </div>
                        </div>
                         <div class="item-actions-group">
                             <button class="view-rating-btn" onclick="showViewRatingModal(this.closest('.rating-list-item'))">
                                 <i class='bx bx-star'></i> View Rating
                             </button>
                             <button class="view-appointment-btn" onclick="viewAppointmentFromRating(this)">
                                 <i class='bx bx-show'></i> View Appointment
                             </button>
                        </div>
                    </div>
                    <p class="item-details">
                        <span>Rated: August 29, 2024</span>
                        <span>Service Date: August 28, 2024</span>
                        <span>Service Type: In-House Cleaning</span>
                    </p>
                    <div class="item-feedback">
                        "Good service overall, the crew arrived a bit late but finished on time. Everything was clean."
                    </div>
                </div>
                
                <div class="rating-list-item" 
                    data-ref-no="ALZ-DC-2407-0050" 
                    data-service-tab="deep-cleaning"
                    data-rating-stars="5" 
                    data-rating-feedback="Fantastic deep clean after the renovation. Worth the price."
                    data-date="2024-07-10" 
                    data-time="08:00"
                    data-duration="6 hours"
                    data-client-type="Villa"
                    data-service-type="Deep Cleaning"
                    data-address="404 Jumeirah Park, Dubai"
                    > <div class="item-header">
                        <div class="item-title-group">
                            <strong>Deep Cleaning (Ref: <span>ALZ-DC-2407-0050</span>)</strong>
                            <div class="item-rating-stars">
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                            </div>
                        </div>
                        <div class="item-actions-group">
                             <button class="view-rating-btn" onclick="showViewRatingModal(this.closest('.rating-list-item'))">
                                 <i class='bx bx-star'></i> View Rating
                             </button>
                             <button class="view-appointment-btn" onclick="viewAppointmentFromRating(this)">
                                 <i class='bx bx-show'></i> View Appointment
                             </button>
                        </div>
                    </div>
                    <p class="item-details">
                        <span>Rated: July 15, 2024</span>
                        <span>Service Date: July 10, 2024</span>
                        <span>Service Type: Deep Cleaning</span>
                    </p>
                    <div class="item-feedback">
                        "Fantastic deep clean after the renovation. Worth the price."
                    </div>
                </div>
                
                <div class="rating-list-item" 
                    data-ref-no="ALZ-CC-2406-0005" 
                    data-service-tab="checkout-cleaning"
                    data-rating-stars="3" 
                    data-rating-feedback="Average service. Some areas were rushed."
                    data-date="2024-06-04" 
                    data-time="13:00"
                    data-duration="4 hours"
                    data-client-type="Apartment"
                    data-service-type="Checkout Cleaning"
                    data-address="1201 Dubai Marina, Dubai"
                    > <div class="item-header">
                        <div class="item-title-group">
                            <strong>Checkout Cleaning (Ref: <span>ALZ-CC-2406-0005</span>)</strong>
                            <div class="item-rating-stars">
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bx-star'></i>
                                <i class='bx bx-star'></i>
                            </div>
                        </div>
                        <div class="item-actions-group">
                             <button class="view-rating-btn" onclick="showViewRatingModal(this.closest('.rating-list-item'))">
                                 <i class='bx bx-star'></i> View Rating
                             </button>
                             <button class="view-appointment-btn" onclick="viewAppointmentFromRating(this)">
                                 <i class='bx bx-show'></i> View Appointment
                             </button>
                        </div>
                    </div>
                    <p class="item-details">
                        <span>Rated: June 05, 2024</span>
                        <span>Service Date: June 04, 2024</span>
                        <span>Service Type: Checkout Cleaning</span>
                    </p>
                    <div class="item-feedback">
                        "Average service. Some areas were rushed."
                    </div>
                </div>

            </div>
            </div>
        </div>
        
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
                    <div class="issue-list-item"
                        data-ref-no="ALZ-CC-2409-0015"
                        data-issue-type="Unsatisfied with Quality of Cleaning"
                        data-issue-status="Under Investigation"
                        data-incident-date="September 28, 2024"
                        data-report-date="September 29, 2024"
                        data-scheduled-time="11:00"
                        data-service-type="Checkout Cleaning" 
                        data-issue-description="The team missed cleaning the balcony glass door and the refrigerator interior was still dirty despite being listed in the scope. We request a re-clean or a partial refund."
                        data-attachment-1="https://alazima.com/files/issue/ALZ-CC-2409-0015_missed_area1.jpg"
                        data-attachment-2="https://alazima.com/files/issue/ALZ-CC-2409-0015_missed_area2.jpg"
                        data-attachment-3="https://alazima.com/files/issue/ALZ-CC-2409-0015_missed_video.mp4">
                        
                        <div class="item-header">
                            <div class="item-title-group">
                                <strong>Checkout Cleaning (Ref: <span>ALZ-CC-2409-0015</span>)</strong>
                                <span class="issue-type-label">Unsatisfied with Quality of Cleaning</span>
                            </div>
                            <div class="item-actions-group">
                                <button class="view-issue-btn" onclick="viewReportedIssue(this.closest('.issue-list-item'))">
                                    <i class='bx bx-show'></i> View Details
                                </button>
                                
                                <button class="view-appointment-btn" onclick="viewAppointmentFromIssue(this)">
                                    <i class='bx bx-calendar-check'></i> View Appointment
                                </button>
                                <button class="contact-btn" onclick="window.open('https://wa.me/971529009188', '_blank')">
                                    <i class='bx bxl-whatsapp'></i> Chat
                                </button>
                            </div>
                        </div>
                        <p class="item-details">
                            <span><i class='bx bx-calendar'></i> Service Date: September 28, 2024</span>
                            <span><i class='bx bx-error-circle'></i> Reported: September 29, 2024</span>
                            <span class="status-tag status-pending">In Progress</span>
                        </p>
                        <div class="item-feedback">
                            "The team missed cleaning the balcony glass door and the refrigerator interior was still dirty..."
                        </div>
                    </div>
                    <div class="issue-list-item"
                        data-ref-no="ALZ-IH-2409-0012"
                        data-issue-type="Property Damage"
                        data-issue-status="Closed - Compensation Provided"
                        data-incident-date="September 20, 2024"
                        data-report-date="September 20, 2024"
                        data-scheduled-time="14:00"
                        data-service-type="In-House Cleaning"
                        data-issue-description="One of the cleaning staff accidentally scratched the wooden floor in the living room while moving the ladder. The scratch is about 10cm long. Compensation was provided."
                        data-attachment-1="https://alazima.com/files/issue/ALZ-IH-2409-0012_scratch_photo.jpg"
                        data-attachment-2=""
                        data-attachment-3="">
                        
                        <div class="item-header">
                            <div class="item-title-group">
                                <strong>In-House Cleaning (Ref: <span>ALZ-IH-2409-0012</span>)</strong>
                                <span class="issue-type-label">Property Damage</span>
                            </div>
                            <div class="item-actions-group">
                                <button class="view-issue-btn" onclick="viewReportedIssue(this.closest('.issue-list-item'))">
                                    <i class='bx bx-show'></i> View Details
                                </button>
                                
                                <button class="view-appointment-btn" onclick="viewAppointmentFromIssue(this)">
                                    <i class='bx bx-calendar-check'></i> View Appointment
                                </button>
                                <button class="contact-btn" onclick="window.open('https://wa.me/971529009188', '_blank')">
                                    <i class='bx bxl-whatsapp'></i> Chat
                                </button>
                            </div>
                        </div>
                        <p class="item-details">
                            <span><i class='bx bx-calendar'></i> Service Date: September 20, 2024</span>
                            <span><i class='bx bx-error-circle'></i> Reported: September 20, 2024</span>
                            <span class="status-tag status-completed">Resolved</span>
                        </p>
                        <div class="item-feedback">
                            "One of the cleaning staff accidentally scratched the wooden floor in the living room..."
                        </div>
                    </div>
                    <div class="no-issues-message" style="display: none; text-align: center; padding: 30px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; color: #777;">
                        <i class='bx bx-folder-open' style="font-size: 2em; display: block; margin-bottom: 10px;"></i>
                        No reported issues found.
                    </div>
                    
                    </div>
            </div>
        </div>
        
        </main>
</div> 
<a href="#header" id="backToTopBtn" title="Back to Top"><i class='bx bx-up-arrow-alt'></i> Back to Top</a>

<div id="logoutModal" class="modal">
<div class="modal__content">
<h3 class="modal__title">Are you sure you want to log out? </h3>
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

<div class="report-modal" id="viewRatingModal" onclick="if(event.target.id === 'viewRatingModal') closeModal('viewRatingModal')">
    <div class="report-modal-content" style="max-width: 650px;">
        <span class="close-btn" onclick="closeModal('viewRatingModal')">&times;</span> 

        <div style="padding: 25px; text-align: left;">
            <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 20px;"><i class='bx bx-star'></i> Service Rating Details</h3>
            
            <p style="margin-bottom: 5px;"><strong>Reference No:</strong> <span id="viewRefNo" style="color: #B32133; font-weight: 700;"></span></p>
            
            <div id="viewAppointmentDetails" style="margin-top: 10px; margin-bottom: 15px;">
                </div>

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
                    <div id="viewFeedback" style="border: 1px solid #ddd; padding: 15px; border-radius: 6px; background-color: #f9f9f9; color: #333; line-height: 1.5;">
                        </div>
                </div>
            </div>
            
            <div style="text-align: right; margin-top: 30px;">
                <a id="editRatingLinkInModal" href="#" class="primary-btn" style="background-color: #004A80;">Edit Rating</a>
            </div>
        </div>
    </div>
</div>

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
            <div id="view-issue-attachments" class="attachments-list">
                </div>
        </div>
        
        <div class="issue-status-section">
            <label>Current Status</label>
            <span id="view-issue-status" class="status-tag">In Progress</span>
        </div>
        
        <div class="issue-modal-footer">
            
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
<script src="FR_function.js"></script> 
<script src="FR_function2.js"></script> 
<script src="client_db.js"></script> 	 	 	
<script src="HIS_function.js"></script> 	


<script>

</script>
</body>
</html>

