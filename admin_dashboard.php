<?php
// KINAKAILANGAN: DYNAMIC DATA FOR TOTAL BOOKINGS BREAKDOWN (NEW)
// Kunin ang kasalukuyang buwan at taon
$current_month_year = date('F Y');

// Sample breakdown data by type and service
$one_time_services_breakdown = [
    'Checkout Cleaning' => 30,
    'In-House Cleaning' => 25,
    'Refresh Cleaning' => 40,
    'Deep Cleaning' => 12,
];

$recurring_services_breakdown = [
    'Weekly' => 15,
    'Bi-weekly' => 10,
    'Monthly' => 8,
];

// Kalkulahin ang kabuuang bilang ng bookings
$total_one_time = array_sum($one_time_services_breakdown);
$total_recurring = array_sum($recurring_services_breakdown);
$total_active_bookings = $total_one_time + $total_recurring;

// Para sa placeholder, kailangan nating malaman ang maximum number ng items
$max_list_items = max(count($one_time_services_breakdown), count($recurring_services_breakdown));
// KINAKAILANGAN: New function para mag-render ng breakdown list
function render_breakdown_list($data, $max_items) {
    $html = '<ul class="breakdown-list">';
    $current_count = 0;
    foreach ($data as $service => $count) {
        $html .= '<li><span class="service-name">' . htmlspecialchars($service) . '</span> <span class="count">' . number_format($count) . '</span></li>';
        $current_count++;
    }
    // Idagdag ang placeholder items kung mas kaunti ang listahan
    for ($i = $current_count; $i < $max_items; $i++) {
        $html .= '<li class="placeholder-item"><span class="service-name"></span> <span class="count"></span></li>';
    }
    $html .= '</ul>';
    return $html;
}

// ** TINANGGAL ANG $active_section PHP LOGIC DITO **
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ALAZIMA - Admin Dashboard</title>
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="admin_db.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
/* --- CSS Variables for new Modal styles --- */
:root {
    --container-color: #fefefe; /* White background for modal content */
    --title-color1: #333;       /* Dark text color for title */
    --z-fixed: 1000;           /* High z-index for fixed elements */
}

/* --- Core Layout & Global Helpers --- */
.view-all-container {
text-align: center;
margin-top: 20px;
}
.view-all-link {
display: inline-block;
padding: 10px 20px;
background-color: #f1f1f1;
color: #333;
text-decoration: none;
font-weight: bold;
border-radius: 8px;
transition: background-color 0.3s, color 0.3s;
border: 1px solid #ddd;
}
.view-all-link:hover {
background-color: #e0e0e0;
color: #000;
}

/* --- FIX: Full Sidebar Scroll and Visibility (Adjusted for Page Scrolling) --- */
.dashboard__sidebar {
    min-height: 100vh; 
    overflow-y: auto; 
    padding-bottom: 20px; 
}
.dashboard__sidebar .sidebar__menu {
    padding-bottom: 30px; 
}
/* End of Sidebar FIX */


.dashboard__content {
overflow-y: visible; 
height: auto; 
min-height: 100vh;
}

/*==================== MODAL STYLES ====================*/
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, .5);
    display: flex;
    justify-content: center;
    align-items: center;
    visibility: hidden;
    opacity: 0;
    transition: opacity .3s ease, visibility .3s ease;
    z-index: var(--z-fixed);
}

.modal.show {
    visibility: visible;
    opacity: 1;
}

.modal__content {
    background-color: var(--container-color);
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, .2);
    text-align: center;
    width: 90%;
    max-width: 400px;
    transform: translateY(-50px);
    transition: transform .3s ease;
}

.modal.show .modal__content {
    transform: translateY(0);
}

.modal__title {
    color: var(--title-color1);
    margin-bottom: 1.5rem;
}

.modal__actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
}
/* --- END MODAL STYLES --- */


.dashboard__summary {
overflow-y: auto;
max-height: 500px;
padding-right: 15px;
}
.dashboard__summary::-webkit-scrollbar { width: 10px; }
.dashboard__summary::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 5px; }
.dashboard__summary::-webkit-scrollbar-thumb { background: #888; border-radius: 5px; }
.dashboard__summary::-webkit-scrollbar-thumb:hover { background: #555; }
/* --- Back to Top button with smooth transition and better look --- */
#backToTopBtn {
display: none;
position: fixed;
bottom: 25px;
right: 25px;
z-index: 99;
border: none;
outline: none;
background-color: #3f51b5;
color: white;
cursor: pointer;
padding: 12px 18px;
border-radius: 50px;
font-size: 1rem;
font-weight: bold;
text-transform: uppercase;
letter-spacing: 0.5px;
box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
transition: background-color 0.3s, transform 0.2s, opacity 0.3s;
opacity: 0;
text-decoration: none;
align-items: center;
justify-content: center;
}
#backToTopBtn:hover {
background-color: #5363b9;
transform: translateY(-3px);
}
#backToTopBtn i {
margin-right: 8px;
font-size: 1.2em;
}

/* --- Summary Cards - NEW INLINE 4-CARD LAYOUT --- */
.summary-cards-container {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 20px;
    padding-bottom: 20px;
    /* Siguraduhin na ang cards ay naka-stretch sa lapad ng container */
    justify-content: space-between; 
}

/* ================================================= */
/* === START: UPDATED SUMMARY CARD STYLES (GRADIENT - MORE CONTRAST) === */
.summary-card {
    /* Base styles */
    border-radius: 12px; /* Smoother corners */
    box-shadow: 0 4px 15px rgba(0,0,0,0.2); /* Stronger shadow */
    padding: 20px 25px; 
    flex: 1 1 200px; 
    max-width: 250px; 
    min-width: 200px; 
    
    /* NEW: Gradient Background Setup - Changed angle to 150deg for better visibility */
    background: linear-gradient(150deg, var(--start-color), var(--end-color));
    color: white; /* White text for contrast */
    border: none; /* Remove all borders */
    
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: center; /* Center content vertically */
    min-height: 120px; 
    transition: transform 0.3s ease, box-shadow 0.3s ease; 
}
.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}

/* 1. Total Client (Pink/Magenta Gradient - MORE CONTRAST) */
.summary-card.total-clients { 
    --start-color: #ff4081; /* Pink/Magenta */
    --end-color: #8c0054;   /* DARKER Pink/Purple */
}
/* 2. Total Bookings (Light Blue/Cyan Gradient - MORE CONTRAST) */
.summary-card.total-bookings { 
    --start-color: #40c4ff; /* Light Blue/Cyan */
    --end-color: #0056b3;   /* DARKER Blue */
}
/* 3. No. of Concerns (Red Gradient - MORE CONTRAST) */
.summary-card.concerns { 
    --start-color: #ff5252; /* Bright Red */
    --end-color: #8b0000;   /* DARK RED */
}
/* 4. No. of Employees (Green Gradient - MORE CONTRAST) */
.summary-card.active-employees { 
    --start-color: #4CAF50; /* Green */
    --end-color: #1b5e20;   /* DARK GREEN */
}

/* Update Card Content Structure for better alignment */
.summary-card .card-content {
    position: relative;
    z-index: 2; /* Make sure content is above the icon */
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 80px; 
}

/* Mga bagong styles para sa loob ng stat card */
.summary-card .stat-title {
    margin-bottom: 5px;
    font-size: 1.2em; /* Bigger title/label */
    font-weight: 500;
    color: rgba(255, 255, 255, 0.9); /* Lighter text for label */
    text-align: right; /* Ilipat sa kanan ang text */
}
.summary-card .stat-value {
    margin: 0;
    font-size: 3.5rem; /* Bigger count number */
    font-weight: bold;
    color: white;
    line-height: 1;
    text-align: left; /* Ilipat sa kaliwa ang number */
}

/* Palitan ang icon size at position para sa simpleng stat card */
.summary-card .card-icon {
    position: absolute;
    top: 50%;
    right: 15px;
    transform: translateY(-50%);
    font-size: 5em; /* Mas malaking icon */
    color: rgba(255, 255, 255, 0.3); /* Pale white for background effect */
    z-index: 1;
}

/* Temporary fix for icon types - Use boxicons that match the image intent */
.summary-card.total-clients .card-icon { 
    font-size: 5em;
    right: 10px;
}
.summary-card.total-bookings .card-icon { 
    font-size: 5em;
    right: 10px;
}
.summary-card.concerns .card-icon { 
    font-size: 5em;
    right: 10px;
}
.summary-card.active-employees .card-icon { 
    font-size: 5em;
    right: 10px;
}
/* === END: UPDATED SUMMARY CARD STYLES === */
/* ================================================= */


/* --- Additional Cards Row for the other two cards (Pending/Quick Actions) --- */
.additional-cards-row {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 20px;
    padding-bottom: 20px;
    justify-content: space-between;
    margin-top: 20px;
}

.summary-card.pending-requests-detail,
.summary-card.quick-actions-detail {
    flex: 1 1 45%; 
    max-width: 48%; /* Hatiin sa dalawa ang lapad */
    min-width: 300px;
    min-height: 200px; 
    
    /* Revert to border-top style for the detail cards */
    border-top: 8px solid;
    border-left: none; /* Remove border-left */
    
    /* Override gradient card background */
    background: #ffffff;
    color: #333;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.summary-card.pending-requests-detail {
    border-top-color: #FF9800; 
}
.summary-card.quick-actions-detail {
    border-top-color: #4CAF50;
}
/* Re-introduce styles for the content inside these specific cards */
.summary-card.pending-requests-detail h3,
.summary-card.quick-actions-detail h3 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}
.summary-card.pending-requests-detail .feedback-link {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #FF9800;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    padding: 12px 20px;
    transition: background-color 0.3s, transform 0.2s;
    margin-top: 10px;
}
.summary-card.pending-requests-detail .feedback-link:hover {
    background-color: #e68900;
    transform: translateY(-2px);
}
.summary-card.quick-actions-detail a {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    background-color: #4CAF50;
    padding: 10px 15px;
    border-radius: 5px;
    text-decoration: none;
    color: white;
    font-size: 0.95em;
    transition: background-color 0.3s, transform 0.2s;
    margin-bottom: 10px;
}
.summary-card.quick-actions-detail a:hover {
    background-color: #45a049;
    transform: translateY(-2px);
}
.summary-card.quick-actions-detail a i {
    margin-right: 10px;
    font-size: 1.2rem;
    color: white;
}
.summary-card.quick-actions-detail ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

/* --- NEW: Booking Breakdown and Recent Activity Row --- */
.booking-activity-row {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 20px;
    margin-bottom: 20px;
}
/* Para mag-full width ang booking breakdown kapag nawala ang activity section */
.booking-breakdown-container {
    flex: 1 1 100%; /* Mag-occupy ng buong lapad */
    min-width: 300px;
    padding-top: 20px;
}

/* --- NEW CSS for Side-by-Side Breakdown CONTENT (Re-applied) --- */
.breakdown-content-wrapper {
    display: flex;
    gap: 20px;
    flex-wrap: wrap; /* Para mag-stack sa mobile */
    margin-bottom: 20px; 
}
.booking-breakdown-container {
    border-top: 8px solid #6A5ACD; /* Keeping the container border-top for consistency */
}
/* *** NEW STYLES FOR INNER BREAKDOWN SECTIONS *** */
.breakdown-section {
    flex: 1 1 45%; 
    min-width: 250px; 

    margin-bottom: 0;
    padding: 15px; 
    border: 1px solid #e0e0e0; 
    border-radius: 8px; 
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); 
    background-color: #f7f7f7; 
    border-bottom: none; 
    padding-bottom: 15px; 
    transition: transform 0.2s;
}
.breakdown-section:hover {
    transform: translateY(-2px);
    box-shadow: 4px 10px rgba(0, 0, 0, 0.1);
}

.breakdown-section h4 {
    color: #333; 
    font-size: 1.3em; 
    margin-top: 0;
    margin-bottom: 12px;
    border-left: 5px solid #6A5ACD; 
    padding-left: 10px;
    font-weight: 700;
}
.breakdown-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.breakdown-list li {
    display: flex;
    justify-content: space-between;
    padding: 8px 5px; 
    font-size: 1em;
    color: #555;
    border-bottom: 1px solid #eeeeee; 
}
.breakdown-list li:last-child {
    border-bottom: none;
}
.breakdown-list li .service-name {
    font-weight: 500;
    color: #555;
}
.breakdown-list li .count {
    font-weight: bold;
    color: #007bff; 
}
/* Ensure the placeholder item doesn't show a border or content */
.breakdown-list li.placeholder-item {
    visibility: hidden; 
    height: 38px; 
    border-bottom: none;
    padding: 0;
}
/* --- END NEW CSS for Side-by-Side Breakdown CONTENT --- */

.total-summary {
    border-top: 2px solid #ddd;
    padding-top: 15px;
    margin-top: 10px;
    font-size: 1.1em;
    color: #333;
    text-align: right;
    font-weight: bold;
}
.total-summary span {
    color: #B32133;
    font-size: 1.2em;
}

/* --- END: Booking Breakdown and Recent Activity Row --- */

/* --- Dashboard Containers --- */
.dashboard__container {
background-color: #ffffff;
border-radius: 15px;
box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
padding: 30px;
margin-top: 30px;
margin-bottom: 20px;
}
.dashboard__container.upcoming-container {
    border-top: 8px solid #007bff; 
}
/* New style para sa container-title */
.container-title {
    font-size: 1.5em;
    color: #333;
    margin-bottom: 20px; 
    text-align: left;
    display: flex; 
    align-items: center; 
    gap: 12px; 
}
.container-title i {
    font-size: 1.5em; 
    color: #007bff; 
}
.container-title strong {
    font-weight: bold;
}

/* --- Performance Overview Chart Styles --- */
.dashboard__container.performance-overview-container {
    border-top: 8px solid #4CAF50; /* Green border for performance */
}

/* KINAKAILANGAN: NEW FLEX CONTAINER FOR CHARTS */
.charts-row {
    display: flex;
    flex-wrap: wrap; /* Mag-stack kapag maliit ang screen */
    gap: 25px;
    margin-top: 10px; /* ADJUSTED: Reduced space above the charts row */
    
}

/* KINAKAILANGAN: NEW HALF-WIDTH CLASS FOR CHART CONTAINERS (INIBA SA 40%) */
.dashboard__container.chart-half-width {
    flex: 1 1 40%; /* Ginamit ang 40% para lumiit pa */
    max-width: 50%; /* Nilimitahan ang maximum width sa 40% */
    min-width: 280px; /* Inadjust ang min-width para hindi masyadong maipit */
    padding: 20px; 
    margin-top: 0; 
}
/* KINAKAILANGAN: CHART-CONTAINER FOR INNER CHART */
.chart-container {
    width: 100%;
    /* 450px height sa desktop, magre-responsive sa mobile */
    height: 350px; /* Ibinaba ang height para bumagay sa smaller width */
    margin: 10px 0;
}


/* --- Appointment List Items (Updated Layout) */
/* ... (Rest of the CSS remains unchanged) ... */
</style>
</head>
<body>
<header class="header" id="header">
<nav class="nav container">
<a href="?content=dashboard" class="nav__logo">
<img src="LOGO.png" alt="ALAZIMA Cleaning Services LLC Logo" onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
</a>
<button class="nav__toggle" id="nav-toggle" aria-label="Toggle navigation menu">
<i class='bx bx-menu'></i>
</button>
</nav>
</header>

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
                <li class="menu__item"><a href="admin_dashboard.php?content=dashboard" class="menu__link active-parent active" data-content="dashboard"><i class='bx bx-home-alt-2'></i> Dashboard</a></li>
                
                <li class="menu__item has-dropdown">
                    <a href="#" class="menu__link" data-content="user-management-parent"><i class='bx bx-user-circle'></i> User Management <i class='bx bx-chevron-down arrow-icon'></i></a>
                    <ul class="dropdown__menu">
                        <li class="menu__item"><a href="UM_clients.php?content=manage-clients" class="menu__link" data-content="manage-clients">Clients</a></li>
                        <li class="menu__item"><a href="UM_employees.php?content=manage-employees" class="menu__link" data-content="manage-employees">Employees</a></li>
                        <li class="menu__item"><a href="UM_admins.php?content=manage-admins" class="menu__link" data-content="manage-admins">Admins</a></li>
                    </ul>       
                </li>
                
                <li class="menu__item has-dropdown">
                    <a href="#" class="menu__link" data-content="appointment-management-parent"><i class='bx bx-calendar-check'></i> Appointment Management <i class='bx bx-chevron-down arrow-icon'></i></a>
                    <ul class="dropdown__menu">
                        <li class="menu__item"><a href="AP_one-time.php?content=appointments-one-time" class="menu__link" data-content="appointments-one-time">One-time Service</a></li>
                        <li class="menu__item"><a href="AP_recurring.php?content=appointments-recurring" class="menu__link" data-content="appointments-recurring">Recurring Service</a></li>
                    </ul>
                </li>
                
                <li class="menu__item"><a href="ES.php?content=employee-scheduling" class="menu__link" data-content="employee-scheduling"><i class='bx bx-time'></i> Employee Scheduling</a></li>

                <li class="menu__item"><a href="FR.php?content=feedback-ratings" class="menu__link" data-content="feedback-ratings"><i class='bx bx-star'></i> Feedback & Ratings</a></li>
                
                <li class="menu__item"><a href="Reports.php?content=reports" class="menu__link" data-content="reports"><i class='bx bx-file-text'></i> Reports</a></li>
                
                <li class="menu__item"><a href="admin_profile.php?content=profile" class="menu__link" data-content="profile"><i class='bx bx-user'></i> Profile</a></li>
                
                <li class="menu__item"><a href="javascript:void(0)" class="menu__link" data-content="logout" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
            </ul>
        </aside>
 
        
    <main class="dashboard__content">
<section id="dashboard" class="content__section active">
<h2 class="section__title">Welcome back, Admin Name!!</h2>
<p class="welcome__message">Here's a quick overview of system activity and pending tasks. Use the sidebar for management and reports.</p>
<div class="summary-cards-container">
    
    <div class="summary-card stat-card total-clients">
        <div class="card-content">
            <h2 class="stat-value">125</h2> 
            <p class="stat-title">Total Client</p>
        </div>
        <i class='bx bx-group card-icon'></i>
    </div>
    
    <div class="summary-card stat-card total-bookings">
        <div class="card-content">
            <h2 class="stat-value">140</h2> 
            <p class="stat-title">Total Bookings</p>
        </div>
        <i class='bx bx-book-open card-icon'></i>
    </div>
    
    <div class="summary-card stat-card concerns">
        <div class="card-content">
            <h2 class="stat-value">17</h2> 
            <p class="stat-title">No. of Concerns</p> 
        </div>
        <i class='bx bx-error-alt card-icon'></i>
    </div>
    
    <div class="summary-card stat-card active-employees">
        <div class="card-content">
            <h2 class="stat-value">45</h2> 
            <p class="stat-title">No. of Employees</p>
        </div>
        <i class='bx bx-user-pin card-icon'></i>
    </div>
</div>

<div class="booking-activity-row">
    
    <div class="dashboard__container booking-breakdown-container">
        <div class="container-title">
            <i class='bx bx-book-content'></i> Total Bookings Breakdown **(<?php echo $current_month_year; ?>)**
        </div>
        
        <div class="breakdown-content-wrapper"> 
            
            <div class="breakdown-section one-time-section">
                <h4>One-time Services</h4>
                <?php echo render_breakdown_list($one_time_services_breakdown, $max_list_items); ?>
            </div>
            
            <div class="breakdown-section recurring-section">
                <h4>Recurring Services</h4>
                <?php echo render_breakdown_list($recurring_services_breakdown, $max_list_items); ?>
            </div>

        </div>
        
        <div class="total-summary">
            <strong>Total Active Bookings:</strong> <span><?php echo number_format($total_active_bookings); ?></span>
        </div>
        </div>
    

</div>

<div class="charts-row">

    <div class="dashboard__container performance-overview-container chart-half-width">
        <div class="container-title">
            <i class='bx bx-trending-up'></i> Performance Overview - Bookings Trend
        </div>
        <div class="chart-container">
            <canvas id="bookingsTrendChart"></canvas>
        </div>
        <div class="view-all-container">
            <a href="?content=reports" class="view-all-link">View Full Reports <i class='bx bx-right-arrow-alt'></i></a>
        </div>
    </div>
    
    <div class="dashboard__container performance-overview-container chart-half-width">
        <div class="container-title">
            <i class='bx bx-pie-chart-alt-2'></i> Service Popularity Breakdown (<?php echo date('F Y'); ?>)
        </div>
        <div class="chart-container" style="max-height: 350px;">
            <canvas id="servicePopularityChart"></canvas>
        </div>
        <div class="view-all-container">
            <a href="?content=reports" class="view-all-link">View Full Service Statistics <i class='bx bx-right-arrow-alt'></i></a>
        </div>
    </div>
    
</div>
</section>

<section id="manage-clients" class="content__section">
    <h2 class="section__title">User Management - Clients</h2>
    <p>Manage all client accounts here.</p>
</section>
<section id="manage-employees" class="content__section">
    <h2 class="section__title">User Management - Employees</h2>
    <p>Manage all employee accounts here.</p>
</section>
<section id="manage-admins" class="content__section">
    <h2 class="section__title">User Management - Admins</h2>
    <p>Manage all administrator accounts here.</p>
</section>
<section id="appointments-one-time" class="content__section">
    <h2 class="section__title">Appointment Management - One-time Service</h2>
    <p>Manage one-time service appointments.</p>
</p>
</section>
<section id="appointments-recurring" class="content__section">
    <h2 class="section__title">Appointment Management - Recurring Service</h2>
    <p>Manage recurring service contracts and appointments.</p>
</section>
<section id="employee-scheduling" class="content__section">
    <h2 class="section__title">Employee Scheduling</h2>
    <p>View and manage staff shifts and service assignments.</p>
</section>
<section id="feedback-ratings" class="content__section">
    <h2 class="section__title">Feedback & Ratings</h2>
    <p>Review customer ratings and feedback for services and employees.</p>
</section>
<section id="reports" class="content__section">
    <h2 class="section__title">Reports</h2>
    <p>Access sales, payroll, and performance analytics reports.</p>
</section>

<section id="profile" class="content__section">
<h2 class="section__title">Personal Information</h2> 
<div class="profile__edit-form">
<form id="profileForm">
    
    <div class="form-row two-column">
        <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="firstName" required value="Danelle" disabled>
        </div>
        <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="lastName" required value="Beltran" disabled>
        </div>
    </div>
    
    <div class="form-row two-column">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="danellemarie6@gmail.com" disabled>
        </div>
        <div class="form-group">
            <label for="contactNumber">Contact Number</label>
            <input type="text" id="contactNumber" name="contactNumber" required 
                   value="+971501234567" 
                   maxlength="13" 
                   pattern="^\+971[0-9]{9}$" 
                   title="Please enter a valid 9-digit number after +971." 
                   disabled>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group full-width">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" required value="123 Admin Street, Dubai, UAE" disabled>
        </div>
    </div>

    <div class="form-row three-column">
        <div class="form-group">
            <label for="birthday">Birthday</label>
            <input type="date" id="birthday" name="birthday" required value="1995-10-01" placeholder="mm/dd/yyyy" disabled>
        </div>
        <div class="form-group">
            <label for="age">Age</label>
            <input type="number" id="age" name="age" value="29" readonly> 
        </div>
        <div class="form-group">
            <label for="gender">Gender</label>
            <select id="gender" name="gender" disabled>
                <option value="">-Select Here-</option>
                <option value="Male">Male</option>
                <option value="Female" selected>Female</option>
                <option value="Other">Other</option>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group full-width">
            <label for="role">Role</label>
            <span id="role" class="role-display">Administrator</span> 
        </div>
    </div>
    
    <div class="form__actions">
        <button type="button" class="btn btn--primary" id="editProfileBtn"><i class='bx bx-edit'></i> Edit Profile</button>
        
        <button type="button" class="btn btn--secondary" id="cancelEditBtn"><i class='bx bx-x-circle'></i> Cancel</button>
    </div>
</form>
</div>
</section>

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
<div class="modal" id="profileSaveModal">
<div class="modal__content">
<h3 class="modal__title">Profile Saved</h3>
<p>Your profile has been updated successfully!</p>
<div class="modal__actions">
<button class="btn btn--primary" id="confirmProfileSave">OK</button>
</div>
</div>
</div>
<div id="requiredFieldsModal" class="modal">
<div class="modal__content">
<h3 class="modal__title">Please fill out all required fields.</h3>
<div class="modal__actions">
<button class="btn btn--primary" id="confirmRequiredFields">OK</button>
</div>
</div>
</div>
<div id="cancelModal" class="modal">
<div class="modal__content">
<h3 class="modal__title">Discard Changes?</h3>
<p>Your unsaved changes will be lost. Continue?</p>
<div class="modal__actions">
<button id="noCancel" class="btn btn--secondary">No</button>
<button id="yesCancel" class="btn btn--primary">Yes</button>
</div>
</div>
</div>
<script>
    // --- Profile Section Edit/Save/Cancel Logic & Age Calculation ---
    
    const profileForm = document.getElementById('profileForm');
    const editableFields = profileForm.querySelectorAll('input:not([readonly]):not([disabled]):not([type="submit"]):not([type="button"]), select:not([disabled])');
    const birthdayField = document.getElementById('birthday');
    const ageField = document.getElementById('age');
    
    const editProfileBtn = document.getElementById('editProfileBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    
    const profileSaveModal = document.getElementById('profileSaveModal');
    const requiredFieldsModal = document.getElementById('requiredFieldsModal');
    const cancelModal = document.getElementById('cancelModal');

    // KINAKAILANGAN: Variable para i-store ang original values
    let originalFormValues = {};
    
    // Function para kumuha ng current values ng form
    function captureOriginalValues() {
        editableFields.forEach(field => {
            originalFormValues[field.id] = field.value;
        });
    }

    // Function para i-disable ang fields at i-set sa Read-only Mode
    function setReadOnlyMode(revert = false) {
        editableFields.forEach(field => {
            field.setAttribute('disabled', 'disabled');
            if (revert) {
                // Ibalik sa original value kapag nag-Cancel
                field.value = originalFormValues[field.id];
            }
        });
        
        editProfileBtn.innerHTML = "<i class='bx bx-edit'></i> Edit Profile";
        cancelEditBtn.style.display = 'none';
        
        // I-re-calculate ang edad kapag nag-revert para sa consistency (kung na-reset ang birthday)
        if (revert && birthdayField) calculateAge();
    }
    
    // Function para i-enable ang fields at i-set sa Edit Mode
    function setEditMode() {
        // I-store muna ang original values bago mag-enable
        captureOriginalValues();
        
        editableFields.forEach(field => {
            field.removeAttribute('disabled');
        });
        
        editProfileBtn.innerHTML = "<i class='bx bx-save'></i> Save Changes";
        cancelEditBtn.style.display = 'block';
    }

    // Function para mag-validate ng required fields
    function validateForm() {
        let isValid = true;
        profileForm.querySelectorAll('[required]:not([disabled])').forEach(field => {
            if (field.value.trim() === '') {
                isValid = false;
                // Kung gusto mong mag-highlight ng invalid field
                field.style.borderColor = 'red'; 
            } else {
                field.style.borderColor = ''; // Ibalik sa default
            }
        });
        return isValid;
    }
    
    // 5. Age Calculation Function
    function calculateAge() {
        if (!birthdayField || !ageField || !birthdayField.value) {
            ageField.value = '';
            return;
        }

        const birthday = new Date(birthdayField.value);
        const today = new Date();
        
        let age = today.getFullYear() - birthday.getFullYear();
        const monthDifference = today.getMonth() - birthday.getMonth();

        // I-adjust ang edad kung hindi pa naabot ang kaarawan sa taong ito
        if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthday.getDate())) {
            age--;
        }
        
        ageField.value = age >= 0 ? age : 0;
    }

    // 1. Toggle Edit Mode (Initial check for logic)
    editProfileBtn.addEventListener('click', function(e) {
        if (editProfileBtn.textContent.includes('Edit Profile')) {
            // Pasok sa Edit Mode
            setEditMode();
            
        } else {
            // 2. Save Changes (Check for validation)
            e.preventDefault();
            
            if (validateForm()) {
                // Valid, mag-Save
                setReadOnlyMode(false);
                profileSaveModal.classList.add('show');
                
            } else {
                // Hindi Valid, ipakita ang Required Fields Modal
                requiredFieldsModal.classList.add('show');
            }
        }
    });

    // 3. Cancel Changes
    cancelEditBtn.addEventListener('click', function() {
        // Ipakita ang Discard Changes Modal
        cancelModal.classList.add('show');
    });

    // 4. Confirm Discard (Yes button sa loob ng #cancelModal)
    document.getElementById('yesCancel').addEventListener('click', function() {
        setReadOnlyMode(true); // I-reset ang form at i-disable ang fields
        cancelModal.classList.remove('show');
    });
    
    // Cancel Discard (No button sa loob ng #cancelModal)
    document.getElementById('noCancel').addEventListener('click', function() {
        cancelModal.classList.remove('show');
    });

    // 5. Birthday Field Listener
    if (birthdayField) {
        birthdayField.addEventListener('change', calculateAge);
    }
    
    // Initial check ng edad sa page load, para kung may default value
    if (birthdayField) calculateAge();
    
    // --- Core Navigation Fix Logic ---
    const navLinks = document.querySelectorAll('.sidebar__menu .menu__link');
    const sections = document.querySelectorAll('.content__section');
    
    // KINAKAILANGAN: Kunin ang Logout elements
    const logoutLink = document.querySelector('.sidebar__menu .menu__link[data-content="logout"]');
    const logoutModal = document.getElementById('logoutModal');
    const cancelLogoutBtn = document.getElementById('cancelLogout');
    const confirmLogoutBtn = document.getElementById('confirmLogout');
    const dropdownParents = document.querySelectorAll('.sidebar__menu .has-dropdown');


    // Variable to store the currently active content link (e.g., Dashboard, Clients, Reports)
    let activeContentLink = document.querySelector('.menu__link.active');

    // ** SIMULA NG BINAGONG NAVIGATION LOGIC PARA SA PERMANENTENG HIGHLIGHT SA PARENT **

    // Function para i-activate ang isang section
    function activateSection(contentId) {
        sections.forEach(section => {
            section.classList.remove('active');
        });

        const targetSection = document.getElementById(contentId);
        if (targetSection) {
            targetSection.classList.add('active');
        }
    }

    // Function para i-handle ang 'active' class sa navigation links (Para sa Content Links)
    function updateActiveContentLink(targetLink) {
        // 1. Alisin ang 'active' at 'active-parent' sa lahat ng links
        navLinks.forEach(link => {
            link.classList.remove('active');
            link.classList.remove('active-parent');
            // Isara din ang lahat ng dropdowns
            link.closest('.has-dropdown')?.classList.remove('open');
        });
        
        // 2. I-set ang 'active' at 'active-parent' sa bagong napiling link (Content Link)
        targetLink.classList.add('active');
        targetLink.classList.add('active-parent');
        activeContentLink = targetLink; // Update the global tracker

        // 3. Kung ang napiling link ay sub-menu, i-set ang 'active-parent' sa parent nito at i-open
        const parentDropdown = targetLink.closest('.has-dropdown');
        if (parentDropdown) {
             const parentLink = parentDropdown.querySelector('.menu__link');
             if (parentLink) {
                parentLink.classList.add('active-parent');
                parentDropdown.classList.add('open');
             }
        }
    }

    // Function para i-toggle ang highlight at open state ng Parent (Dropdown Link)
    function toggleDropdownHighlight(parentLink, parentItem) {
        const isCurrentlyOpen = parentItem.classList.contains('open');

        // 1. I-toggle ang 'open' state at highlight
        parentItem.classList.toggle('open', !isCurrentlyOpen); // I-toggle ang open state
        parentLink.classList.toggle('active', !isCurrentlyOpen); // I-toggle ang highlight
        parentLink.classList.toggle('active-parent', !isCurrentlyOpen); // I-toggle ang highlight

        // 2. Isara ang lahat ng iba pang dropdowns
        dropdownParents.forEach(otherParent => {
            if (otherParent !== parentItem && otherParent.classList.contains('open')) {
                otherParent.classList.remove('open');
                otherParent.querySelector('.menu__link').classList.remove('active');
                otherParent.querySelector('.menu__link').classList.remove('active-parent');
            }
        });
        
        // 3. Tiyakin na ang activeContentLink (kahit nasa sub-menu o top-level) ay naka-highlight
        if (activeContentLink) {
            // Tiyakin na ang activeContentLink ay may active class (para hindi mawala ang Dashboard highlight)
            activeContentLink.classList.add('active');
            activeContentLink.classList.add('active-parent');
            
            // At kung ang activeContentLink ay nasa loob ng ibang dropdown, i-highlight din ang parent na 'yon
            const activeContentParent = activeContentLink.closest('.has-dropdown');
            if (activeContentParent && activeContentParent !== parentItem) {
                 activeContentParent.querySelector('.menu__link').classList.add('active-parent');
            }
        }
    }

    // Navigation Link Click Listener
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const contentId = link.getAttribute('data-content');
            
            // A. Kung ang link ay parent dropdown (href="#")
            if (link.closest('.has-dropdown') && link.getAttribute('href') === '#') {
                e.preventDefault(); 
                
                const parentItem = link.closest('.has-dropdown');
                toggleDropdownHighlight(link, parentItem);
                
            } 
            // B. Kung ang link ay sub-menu link, normal top-level link, o 'logout'
            else if (contentId) {
                
                if (contentId !== 'logout') {
                    e.preventDefault(); 
                    
                    // Kunin ang contentId (Hal. 'dashboard', 'manage-clients', 'profile')
                    const targetContentId = contentId.includes('-') ? contentId.split('-').pop() : contentId;

                    // I-activate ang section
                    activateSection(targetContentId);
                    
                    // I-update ang active link (ito na ang mag-aalis ng lahat ng highlight at magse-set ng bago)
                    updateActiveContentLink(link);
                }
                // Hayaan ang 'logout' na mag-trigger ng modal.
            }
        });
    });

    // Initial content activation logic (Based sa URL parameter o default sa 'dashboard')
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        let activeContent = urlParams.get('content') || 'dashboard';
        
        // I-activate ang tamang section
        activateSection(activeContent);

        // Hanapin at i-activate ang tamang link
        const initialActiveLink = document.querySelector(`.sidebar__menu .menu__link[data-content*="${activeContent}"]`);
        if (initialActiveLink) {
             updateActiveContentLink(initialActiveLink); // Set the active content link
        }
        
        // Tiyakin na bukas ang dropdown kung active ang sub-menu
        const activeLinkParent = initialActiveLink ? initialActiveLink.closest('.has-dropdown') : null;
        if (activeLinkParent) {
            activeLinkParent.classList.add('open');
            // Tiyakin na ang parent link ay may active-parent class din
            activeLinkParent.querySelector('.menu__link').classList.add('active-parent');
        }
        
        // I-set ang initial active content link
        activeContentLink = initialActiveLink;
    };
    
    // ** WAKAS NG BINAGONG NAVIGATION LOGIC **
    
    // FIX: LOGIC PARA IPAKITA ANG LOGOUT MODAL
    if (logoutLink && logoutModal) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault(); // PIGILAN ANG PAG-REDIRECT
            // CHANGE: Use classList.add('show')
            logoutModal.classList.add('show'); 
        });
    }

    // LOGIC PARA I-CLOSE ANG MODAL (Cancel)
    if (cancelLogoutBtn && logoutModal) {
        cancelLogoutBtn.addEventListener('click', function() {
            // CHANGE: Use classList.remove('show')
            logoutModal.classList.remove('show');
        });
    }

    // LOGIC PARA SA CONFIRM LOGOUT (i-reredirect sa href ng link)
    if (confirmLogoutBtn && logoutLink) {
        confirmLogoutBtn.addEventListener('click', function() {
            // CHANGE: Ang 'logout' link natin ay may 'href' na 'javascript:void(0)', kaya palitan na lang natin ang window location
            // window.location.href = logoutLink.href; // O kaya gawin na lang nating redirect sa home o login page
            window.location.href = "landing_page2.html"; // I-redirect sa login page/home page
        });
    }
    
    // --- 1. Bookings Trend Chart Logic (Chart.js) ---
    const bookingsTrendChartData = {
        // Labels remain 3 months
        labels: ['Apr 2024', 'May 2024', 'Jun 2024'], 
        datasets: [
            {
                label: 'Completed', 
                data: [70, 50, 45], 
                backgroundColor: 'rgba(76, 175, 80, 0.8)', // Green
                borderColor: 'rgba(76, 175, 80, 1)',
                borderWidth: 1,
            },
            // TANGGAL: Inalis ang 'Confirmed' dataset para lumiit ang chart data
            /*
            {
                label: 'Confirmed', 
                data: [10, 8, 12],
                backgroundColor: 'rgba(66, 165, 245, 0.8)', // Light Blue
                borderColor: 'rgba(66, 165, 245, 1)',
                borderWidth: 1,
            },
            */
            {
                label: 'Cancelled', 
                data: [6, 8, 7],
                backgroundColor: 'rgba(255, 152, 0, 0.8)', // Orange
                borderColor: 'rgba(255, 152, 0, 1)',
                borderWidth: 1,
            },
            {
                label: 'No Show', 
                data: [2, 4, 3],
                backgroundColor: 'rgba(179, 33, 51, 0.8)', // Red
                borderColor: 'rgba(179, 33, 51, 1)',
                borderWidth: 1,
            }
        ]
    };

    const bookingsTrendOptions = {
        responsive: true,
        maintainAspectRatio: false, 
        // START MODIFICATION FOR WIDER BARS (Ibinabalik ang dati nating ginawa)
        categoryPercentage: 0.8, 
        barPercentage: 0.7, 
        // END MODIFICATION FOR WIDER BARS
        scales: {
            x: {
                // Stacked set to false for grouped bar chart
                stacked: false, 
                title: {
                    display: true,
                    text: 'Month/Period'
                }
            },
            y: {
                // Stacked set to false for grouped bar chart
                stacked: false, 
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Bookings'
                }
            }
        },
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                mode: 'index',
                intersect: false
            }
        }
    };

    const bookingsTrendCtx = document.getElementById('bookingsTrendChart');

    if (bookingsTrendCtx) {
        new Chart(bookingsTrendCtx, {
            type: 'bar', 
            data: bookingsTrendChartData,
            options: bookingsTrendOptions,
        });
    }

    // --- 2. Service Popularity Pie Chart Logic (Chart.js) ---
    const servicePopularityData = {
        labels: [
            'Refresh Cleaning', 
            'Checkout Cleaning', 
            'In-House Cleaning', 
            'Deep Cleaning',
        ],
        datasets: [{
            data: [40, 30, 25, 12], // Total: 107
            backgroundColor: [
                '#FF6384', // Refresh (Pink)
                '#36A2EB', // Checkout (Blue)
                '#FFCE56', // In-House (Yellow)
                '#9966FF', // Deep (Purple)
            ],
            hoverOffset: 4
        }]
    };
    
    const servicePopularityOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed !== null) {
                            // Calculate percentage
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.parsed;
                            const percentage = ((value / total) * 100).toFixed(1);
                            // Dito na lang ilalagay ang count at percentage
                            label = label + ' (' + value + ') - ' + percentage + '%';
                        }
                        return label;
                    }
                }
            }
        }
    };
    
    const servicePopularityCtx = document.getElementById('servicePopularityChart');

    if (servicePopularityCtx) {
        new Chart(servicePopularityCtx, {
            type: 'pie', // Pie chart ang ginamit natin
            data: servicePopularityData,
            options: servicePopularityOptions,
        });
    }



    // --- DROPDOWN TOGGLE LOGIC (RE-IMPLEMENTED) ---
    // (Wala nang kailangan gawin dito dahil nasa navLinks listener na ang logic)
    // --- END DROPDOWN TOGGLE LOGIC ---
/* --- END: Profile Section Edit/Save/Cancel Logic & Navigation Fix (FINAL FIX) --- */
</script>
</body>
</html>