<?php
// PHP code for includes or logic goes here (if any)
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ALAZIMA - Employee Dashboard</title>
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="client_db.css">
<style>
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
.dashboard__content {
overflow-y: auto;
height: 100%;
}
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
/* --- Summary Cards --- */
.summary-cards-container {
display: flex;
flex-direction: row;
flex-wrap: wrap;
gap: 20px;
padding-bottom: 20px;
justify-content: center;
}
.summary-card {
border-radius: 15px;
box-shadow: 0 4px 15px rgba(0,0,0,0.1);
padding: 25px;
flex: 1;
min-width: 280px;
max-width: 350px;
background-color: #FFFFFF;
border-top: 8px solid;
position: relative;
overflow: hidden;
display: flex;
flex-direction: column;
justify-content: space-between;
min-height: 200px;
color: #333;
}
.summary-card.service-summary {
    /* Service Summary Top Border (Dark Violet/Purple) */
    border-top-color: #6A5ACD; 
}
.summary-card.pending-feedback {
    border-top-color: #FF9800;
}
.summary-card.quick-actions {
    border-top-color: #4CAF50;
}
.summary-card .card-icon {
position: absolute;
bottom: -20px;
right: -20px;
font-size: 8em;
color: rgba(0, 0, 0, 0.08);
z-index: 1;
}
.summary-card h3 {
margin-top: 0;
margin-bottom: 10px;
font-size: 1.5rem;
font-weight: bold;
color: #333;
}
/* Service Summary Title Line (Dark Violet/Purple) */
.summary-card.service-summary h3 {
    color: #333; 
    border-bottom: 2px solid #6A5ACD; 
    padding-bottom: 5px; 
    margin-bottom: 15px; 
}
.service-summary table {
width: 100%;
border-collapse: collapse;
margin-top: 10px;
}
.service-summary table td {
padding: 6px 0;
text-align: left;
color: #555;
font-size: 1.2em;
}
.service-summary table td.count {
    text-align: right;
    font-weight: bold;
    color: #6A5ACD; /* Dark Violet/Purple Counter Value */
}
.pending-feedback .feedback-link {
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
.pending-feedback .feedback-link:hover {
background-color: #e68900;
transform: translateY(-2px);
}
.pending-feedback .feedback-link i {
margin-right: 8px;
}
.quick-actions a {
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
.quick-actions a:hover {
background-color: #45a049;
transform: translateY(-2px);
}
.quick-actions a i {
margin-right: 10px;
font-size: 1.2rem;
color: white;
}
.quick-actions ul {
list-style: none;
padding: 0;
margin: 0;
}
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
    /* Upcoming Container Top Border (Blue #007bff) */
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
    /* Upcoming Container Icon Color (Blue #007bff) */
    color: #007bff; 
}
/* Style para sa bolded words sa title */
.container-title strong {
    font-weight: bold;
}
/* --- Appointment List Items (Updated Layout) --- */
.appointment-list-item {
background-color: #ffffff;
border-radius: 12px;
box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
padding: 18px;
margin-bottom: 15px;
transition: transform 0.3s, box-shadow 0.3s;
border: 1px solid #ddd;
position: relative;
}
.appointment-list-item:hover {
transform: translateY(-3px);
box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
}
.appointment-details {
display: grid;
grid-template-columns: repeat(2, 1fr);
gap: 5px 20px; 
margin-bottom: 15px;
}
.appointment-details p {
display: flex;
align-items: center;
gap: 10px;
margin: 0; 
font-size: 0.95em;
color: #555;
word-break: break-word;
}
/* New CSS Rule for Icon Color */
.appointment-details p i {
    /* Details Icon Color (Blue #007bff) */
    color: #007bff;
}
/* --- MODIFIED: button-group-top for clean layout with dropdown --- */
.button-group-top {
position: absolute;
top: 15px;
right: 15px;
display: flex;
gap: 8px;
align-items: center;
}
.action-btn {
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
}

/* Primary/Secondary Button Styles */
.view-details-btn {
    /* BINAGO: Ginawang Blue (#007bff) ang border at text color */
    background-color: transparent;
    border: 2px solid #007bff; 
    color: #007bff; 
}
.view-details-btn:hover {
    background-color: #007bff;
    color: white;
}
.call-btn {
    /* BINAGO: Ginawang Dark Blue (#0056b3) ang normal state */
    background-color: #0056b3; 
    border: 2px solid #0056b3;
    color: white;
}
.call-btn:hover {
    /* BINAGO: Ginawang mas matingkad na Blue (#0062cc) ang hover state */
    background-color: #0062cc; 
    border-color: #0062cc;
}

/* Ellipsis Menu Styles (RESTORED) */
.dropdown-menu-container {
    position: relative;
}
.more-options-btn {
    background: #f1f1f1;
    color: #333;
    border: 1px solid #ddd;
    padding: 8px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1.1em;
    line-height: 1; /* Para maayos ang alignment ng icon */
    transition: background 0.2s;
}
.more-options-btn:hover {
    background: #e0e0e0;
}

.dropdown-menu {
    position: absolute;
    top: 100%; /* Sa ilalim ng button */
    right: 0;
    background-color: #ffffff;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    z-index: 10;
    min-width: 150px;
    padding: 5px 0;
    display: none; /* Default hidden */
    list-style: none;
}
.dropdown-menu.show {
    display: block;
}
.dropdown-menu li {
    padding: 0;
}
.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    text-decoration: none;
    color: #333; /* Text color is now dark gray/black */
    font-size: 0.9em;
    transition: background-color 0.2s;
    width: 100%; /* Para clickable ang buong item */
    box-sizing: border-box;
    font-weight: 500; /* Hindi masyadong bold, mas professional */
}
.dropdown-menu a:hover {
    background-color: #f7f7f7;
}

/* Specific Dropdown ICON Colors ONLY (RESTORED) */
.dropdown-menu a i {
    font-size: 1.1em;
}
.dropdown-menu .edit-link i {
    color: #4CAF50; /* Green icon for Edit */
}
.dropdown-menu .cancel-link i {
    color: #B32133; /* Red icon for Cancel (Destructive) */
}
/* END MODIFIED BUTTON STYLES */

.appointment-list-item .divider {
border: 0;
height: 1px;
background-color: #ccc;
margin: 15px 0;
width: 100%;
}
/* Bagong Styles para sa One-Time Appointments na may bagong layout */
.appointment-details .full-width-detail {
grid-column: 1 / -1;
}
.appointment-details .duration-detail {
grid-column: 2;
justify-self: start;
}
.appointment-details .service-type-detail {
grid-column: 2;
}
.appointment-details .price-detail {
grid-column: 2;
justify-self: end;
font-weight: bold;
color: #333; 
font-size: 1.2em;
}
.appointment-details .price-detail span.aed-color {
  color: #333; 
}
.appointment-details .status-detail {
grid-column: 1 / -1;
}
.appointment-details .ref-no-detail {
    color: #333; /* Keep the "Reference No:" black */
}
.appointment-details .ref-no-value {
    color: #B32133; /* Dark magenta with a red hue for the value */
    font-weight: bold; /* Make the value bold */
}
/* Bagong Styles para sa Recurring Appointments */
.appointment-details p.recurring-details {
grid-column: 2;
margin: 0;
justify-self: start;
align-items: center;
gap: 10px;
}
.appointment-details p.recurring-details:nth-of-type(2) {
grid-column: 1;
}
.appointment-details p.recurring-details:nth-of-type(3) {
grid-column: 2;
justify-self: start;
}
.appointment-details p {
word-break: break-word;
}
.appointment-details .full-width-detail {
grid-column: 1 / -1;
}
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
<div class="dashboard__wrapper">
<aside class="dashboard__sidebar">
<ul class="sidebar__menu">
<li class="menu__item">
<a href="?content=dashboard" class="menu__link" data-content="dashboard">
<i class='bx bx-home-alt-2'></i> Dashboard
</a>
</li>
<li class="menu__item has-dropdown">
    <a href="#" class="menu__link" data-content="appointments-parent">
        <i class='bx bx-calendar-check'></i> My Appointments <i class='bx bx-chevron-down arrow-icon'></i>
    </a>
    <ul class="dropdown__menu">
        <li class="menu__item">
            <a href="EMP_appointments_today.php" class="menu__link" data-content="today-appointments">Today's Appointments</a>
        </li>
        <li class="menu__item">
            <a href="EMP_appointments_history.php" class="menu__link" data-content="appointment-history">History</a>
        </li>
    </ul>
</li>
<li class="menu__item has-dropdown">
    <a href="#" class="menu__link" data-content="timeoff-parent">
        <i class='bx bx-time-five'></i> Time Off <i class='bx bx-chevron-down arrow-icon'></i>
    </a>
    <ul class="dropdown__menu">
        <li class="menu__item"><a href="EMP_timeoff_request.php" class="menu__link" data-content="timeoff-request">Request Time Off</a></li>
        <li class="menu__item"><a href="?content=timeoff-status" class="menu__link" data-content="timeoff-status">Status/History</a></li>
    </ul>
</li>
<li class="menu__item">
    <a href="?content=payroll" class="menu__link" data-content="payroll">
        <i class='bx bx-wallet-alt'></i> Payroll
    </a>
</li>
<li class="menu__item">
<a href="?content=profile" class="menu__link" data-content="profile">
<i class='bx bx-user'></i> My Profile
</a>
</li>
<li class="menu__item"><a href="?content=logout" class="menu__link" data-content="logout"><i class='bx bx-log-out'></i> Logout</a></li>
</ul>
</aside>
<main class="dashboard__content">
<section id="dashboard" class="content__section active">
<h2 class="section__title">Welcome back, Employee Name!!</h2>
<p class="welcome__message">Here's a quick overview of your upcoming tasks and work information. You can check your schedule, request time off, or update your profile from the menu.</p>
<div class="summary-cards-container">
<div class="summary-card service-summary">
<div class="card-content">
<h3>Today's Tasks</h3>
<table>
<tr>
<td>Total Appointments Today:</td>
<td class="count">0</td>
</tr>
<tr>
<td>Upcoming in 7 Days:</td>
<td class="count">0</td>
</tr>
<tr>
<td>Pending Start:</td>
<td class="count">0</td>
</tr>
</table>
</div>
<i class='bx bx-clipboard card-icon'></i>
</div>
<div class="summary-card pending-feedback">
<div class="card-content">
<h3>Pending Time Off</h3>
<p>You have 1 pending Time Off request.</p>
<a href="#" class="feedback-link"><i class='bx bx-time-five'></i> View Request Status</a>
</div>
<i class='bx bx-bell card-icon'></i>
</div>
<div class="summary-card quick-actions">
<div class="card-content">
<h3>Quick Links</h3>
<ul>
<li><a href="EMP_timeoff_request.php"><i class='bx bx-calendar-exclamation'></i> Request Time Off</a></li>
<li><a href="EMP_appointments_history.php"><i class='bx bx-list-check'></i> View Appointment History</a></li>
<li><a href="?content=payroll"><i class='bx bx-dollar-circle'></i> View Payroll</a></li>
</ul>
</div>
<i class='bx bx-cog card-icon'></i>
</div>
</div>
<div class="dashboard__container upcoming-container one-time-container">
    <div class="container-title">
        <i class='bx bx-calendar'></i> My Upcoming <strong>One-Time</strong> Tasks
    </div>
    <div class="appointment-list-container">
        <div class="appointment-list-item">
            <div class="button-group-top">
                <a href="#" class="action-btn view-details-btn"><i class='bx bx-show'></i> View</a>
                <a href="tel:+1234567890" class="action-btn call-btn"><i class='bx bx-phone'></i> Call Client</a>
                <div class="dropdown-menu-container">
                    <button class="more-options-btn"><i class='bx bx-dots-vertical-rounded'></i></button>
                    <ul class="dropdown-menu">
                        <li><a href="#" class="edit-link"><i class='bx bx-play-circle'></i> Start Job</a></li>
                        <li><a href="#" class="cancel-link"><i class='bx bx-check-circle'></i> Complete Job</a></li>
                    </ul>
                </div>
            </div>
            <div class="appointment-details">
                <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-DC-2511-0001</span></p>
                <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> November 1, 2025</p>
                <p><i class='bx bx-time'></i> <strong>Time:</strong> 2:00 PM</p>
                <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 4 hours</p>
                <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 456 Main Blvd., Abu Dhabi</p>
                <hr class="divider full-width-detail">
                <p><i class='bx bx-buildings'></i> <strong>Client Type:</strong> Office</p>
                <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> Deep Cleaning</p>
                <p class="full-width-detail status-detail"><i class='bx bx-info-circle'></i> <strong>Status:</strong> Confirmed</p>
                <p class="price-detail">Client Pays: <span class="aed-color">AED 300</span></p>
            </div>
        </div>
        <div class="appointment-list-item">
            <div class="button-group-top">
                <a href="#" class="action-btn view-details-btn"><i class='bx bx-show'></i> View</a>
                <a href="tel:+1234567890" class="action-btn call-btn"><i class='bx bx-phone'></i> Call Client</a>
                <div class="dropdown-menu-container">
                    <button class="more-options-btn"><i class='bx bx-dots-vertical-rounded'></i></button>
                    <ul class="dropdown-menu">
                        <li><a href="#" class="edit-link"><i class='bx bx-play-circle'></i> Start Job</a></li>
                        <li><a href="#" class="cancel-link"><i class='bx bx-check-circle'></i> Complete Job</a></li>
                    </ul>
                </div>
            </div>
            <div class="appointment-details">
                <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-IC-2511-0002</span></p>
                <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> November 5, 2025</p>
                <p><i class='bx bx-time'></i> <strong>Time:</strong> 9:00 AM</p>
                <p class="duration-detail"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 5 hours</p>
                <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 789 Lake View, Dubai</p>
                <hr class="divider full-width-detail">
                <p><i class='bx bx-home-heart'></i> <strong>Client Type:</strong> Villa</p>
                <p class="service-type-detail"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> Move-in Cleaning</p>
                <p class="full-width-detail status-detail"><i class='bx bx-info-circle'></i> <strong>Status:</strong> Pending Confirmation</p>
                <p class="price-detail">Client Pays: <span class="aed-color">AED 450</span></p>
            </div>
        </div>
        <div class="view-all-container">
            <a href="?content=today-appointments" class="view-all-link">See More...</a>
        </div>
    </div>
</div>

<div class="dashboard__container upcoming-container recurring-container">
    <div class="container-title">
        <i class='bx bx-repeat'></i> My Upcoming <strong>Recurring</strong> Tasks
    </div>
    <div class="appointment-list-container">
        <div class="appointment-list-item">
            <div class="button-group-top">
                <a href="#" class="action-btn view-details-btn"><i class='bx bx-show'></i> View</a>
                <a href="tel:+1234567890" class="action-btn call-btn"><i class='bx bx-phone'></i> Call Client</a>
                <div class="dropdown-menu-container">
                    <button class="more-options-btn"><i class='bx bx-dots-vertical-rounded'></i></button>
                    <ul class="dropdown-menu">
                        <li><a href="#" class="edit-link"><i class='bx bx-play-circle'></i> Start Job</a></li>
                        <li><a href="#" class="cancel-link"><i class='bx bx-check-circle'></i> Complete Job</a></li>
                    </ul>
                </div>
            </div>
            <div class="appointment-details">
                <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-GC-2510-0007</span></p>
                <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> October 26, 2025</p>
                <p><i class='bx bx-calendar-week'></i> <strong>Day:</strong> Sunday</p>
                <p class="recurring-details"><i class='bx bx-repeat'></i> <strong>Frequency:</strong> Weekly</p>
                <p><i class='bx bx-time'></i> <strong>Time:</strong> 10:00 AM</p>
                <p class="recurring-details"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 3 hours</p>
                <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 123 Alazima St., Dubai</p>
                <hr class="divider full-width-detail">
                <p><i class='bx bx-user'></i> <strong>Client Type:</strong> Apartment</p>
                <p class="recurring-details"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> General Cleaning</p>
                <p class="full-width-detail"><i class='bx bx-info-circle'></i> <strong>Status:</strong> Scheduled</p>
                <p class="price-detail">Client Pays: <span class="aed-color">AED 250</span></p>
            </div>
        </div>
        <div class="appointment-list-item">
            <div class="button-group-top">
                <a href="#" class="action-btn view-details-btn"><i class='bx bx-show'></i> View</a>
                <a href="tel:+1234567890" class="action-btn call-btn"><i class='bx bx-phone'></i> Call Client</a>
                <div class="dropdown-menu-container">
                    <button class="more-options-btn"><i class='bx bx-dots-vertical-rounded'></i></button>
                    <ul class="dropdown-menu">
                        <li><a href="#" class="edit-link"><i class='bx bx-play-circle'></i> Start Job</a></li>
                        <li><a href="#" class="cancel-link"><i class='bx bx-check-circle'></i> Complete Job</a></li>
                    </ul>
                </div>
            </div>
            <div class="appointment-details">
                <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">ALZ-GC-2511-0003</span></p>
                <p class="full-width-detail"><i class='bx bx-calendar-check'></i> <strong>Date:</strong> November 2, 2025</p>
                <p><i class='bx bx-calendar-week'></i> <strong>Day:</strong> Monday</p>
                <p class="recurring-details"><i class='bx bx-repeat'></i> <strong>Frequency:</strong> Bi-Weekly</p>
                <p><i class='bx bx-time'></i> <strong>Time:</strong> 9:30 AM</p>
                <p class="recurring-details"><i class='bx bx-stopwatch'></i> <strong>Duration:</strong> 5 hours</p>
                <p class="full-width-detail"><i class='bx bx-map-alt'></i> <strong>Address:</strong> 202 Palm Jumeirah, Dubai</p>
                <hr class="divider full-width-detail">
                <p><i class='bx bx-user'></i> <strong>Client Type:</strong> Apartment</p>
                <p class="recurring-details"><i class='bx bx-wrench'></i> <strong>Service Type:</strong> Deep Cleaning</p>
                <p class="full-width-detail"><i class='bx bx-info-circle'></i> <strong>Status:</strong> Confirmed</p>
                <p class="price-detail">Client Pays: <span class="aed-color">AED 450</span></p>
            </div>
        </div>
        <div class="view-all-container">
            <a href="?content=appointment-history" class="view-all-link">See More...</a>
        </div>
    </div>
</div>
</section>
<section id="profile" class="content__section">
<h2 class="section__title">My Profile</h2>
<div class="profile__edit-form">
<form id="profileForm">
<div class="form-row">
<div class="form-group">
<label for="firstName">First name:</label>
<input type="text" id="firstName" name="firstName" required>
</div>
<div class="form-group">
<label for="lastName">Last name:</label>
<input type="text" id="lastName" name="lastName" required>
</div>
</div>
<div class="form-row">
<div class="form-group">
<label for="birthday">Birthday:</label>
<input type="date" id="birthday" name="birthday" required>
</div>
<div class="form-group">
<label for="contactNumber">Contact Number:</label>
<input type="tel" id="contactNumber" name="contactNumber" required placeholder="+971" pattern="^\+971[0-9]{9}$" title="Please enter a valid UAE number starting with +971 followed by 9 digits.">
</div>
</div>
<div class="form-group full-width">
<label for="email">Email Address: </label>
<input type="email" id="email" name="email" required>
</div>
<div class="form__actions">
<button type="button" class="btn btn--primary" id="editProfileBtn">Edit</button>
<button type="button" class="btn btn--secondary" id="cancelEditBtn">Cancel</button>
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
<script src="client_db.js"></script>
<script>
// Toggle function for the ellipsis menu (RESTORED JAVASCRIPT)
document.querySelectorAll('.more-options-btn').forEach(button => {
    button.addEventListener('click', function() {
        // Find the next sibling which is the dropdown menu
        const menu = this.closest('.dropdown-menu-container').querySelector('.dropdown-menu');
        // Toggle the 'show' class
        menu.classList.toggle('show');
    });
});

// Close the dropdown if the user clicks outside of it (RESTORED JAVASCRIPT)
window.addEventListener('click', function(e) {
    document.querySelectorAll('.dropdown-menu-container').forEach(container => {
        const button = container.querySelector('.more-options-btn');
        const menu = container.querySelector('.dropdown-menu');
        
        // If the click is not on the button AND the menu is open
        if (!button.contains(e.target) && menu.classList.contains('show')) {
            // Check if the click is not inside the menu
            if (!menu.contains(e.target)) {
                menu.classList.remove('show');
            }
        }
    });
});

// Get the button element
let mybutton = document.getElementById("backToTopBtn");
// When the user scrolls down, show or hide the button with a smooth transition
window.onscroll = function() {
scrollFunction();
};
function scrollFunction() {
if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
mybutton.style.display = "flex";
setTimeout(() => { mybutton.style.opacity = "1"; }, 10);
} else {
mybutton.style.opacity = "0";
setTimeout(() => { mybutton.style.display = "none"; }, 300);
}
}
// Smooth scroll to top when the button is clicked
mybutton.addEventListener('click', function(e) {
e.preventDefault(); // Prevent the default jump behavior
window.scrollTo({
top: 0,
behavior: 'smooth'
});
});
</script>
</body>
</html>