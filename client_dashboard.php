<?php
session_start();
require 'connection.php';

$client_email = $_SESSION['email'] ?? null;
$client_name = $_SESSION['full_name'] ?? 'Client';

// Fetch client first name from clients table
if ($client_email) {
    $query = "SELECT first_name FROM clients WHERE email = '$client_email' LIMIT 1";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $client_name = $row['first_name'];
    }
}

// Count One-Time bookings
$one_time_count_query = "SELECT COUNT(*) as count FROM bookings WHERE booking_type = 'One-Time' AND status IN ('Pending','Confirmed','Ongoing')";
if ($client_email) {
    $one_time_count_query .= " AND email = '$client_email'";
}
$count_result = $conn->query($one_time_count_query);
$one_time_count = ($count_result && $count_result->num_rows > 0) ? $count_result->fetch_assoc()['count'] : 0;

// Count Recurring bookings
$recurring_count_query = "SELECT COUNT(*) as count FROM bookings WHERE booking_type = 'Recurring' AND status IN ('Pending','Active','Paused')";
if ($client_email) {
    $recurring_count_query .= " AND email = '$client_email'";
}
$count_result = $conn->query($recurring_count_query);
$recurring_count = ($count_result && $count_result->num_rows > 0) ? $count_result->fetch_assoc()['count'] : 0;

// Total services
$total_services = $one_time_count + $recurring_count;

// Fetch COMBINED bookings (One-Time + Recurring) with LIMIT 3
$combined_query = "
    SELECT 
        id,
        booking_type,
        service_date,
        service_time,
        duration,
        status,
        service_type,
        client_type,
        address,
        property_type,
        materials_provided,
        materials_needed,
        comments,
        media1,
        media2,
        media3,
        start_date,
        end_date,
        frequency
    FROM bookings 
    WHERE (
        (booking_type = 'One-Time' AND status IN ('Pending','Confirmed','Ongoing'))
        OR 
        (booking_type = 'Recurring' AND status IN ('Pending','Active','Paused'))
    )
";

if ($client_email) {
    $combined_query .= " AND email = '$client_email' ";
}

$combined_query .= " ORDER BY 
    CASE 
        WHEN booking_type = 'One-Time' THEN service_date 
        ELSE start_date 
    END DESC
    LIMIT 3";

$combined_result = $conn->query($combined_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ALAZIMA - Client Dashboard </title>
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="client_db.css"> <link rel="stylesheet" href="HIS_design.css">

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
/* Dito idinagdag ang padding: 20px; */
overflow-y: auto;
height: 100%;
padding: 20px; /* NAG-REPAIR: Idinagdag ang padding para sa espasyo sa paligid */
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
    /* Upcoming Container Top Border (Blue #007bff) - Same as Recurring now */
    border-top: 8px solid #007bff; 
}
.dashboard__container.recurring-container {
    /* Recurring Container Top Border (Blue #007bff) - UPDATED TO BLUE */
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
/* Recurring Container Icon Color (Blue #007bff) - UPDATED TO BLUE */
.dashboard__container.recurring-container .container-title i {
    color: #007bff;
}
/* Style para sa bolded words sa title */
.container-title strong {
    font-weight: bold;
}
/* Temporary CSS for the new Cancel Modal to ensure it matches the image. */
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

/* --- New CSS for Recurring Plan Status Tags --- */
/* --- New CSS for Recurring Plan Status Tags --- */
.overall-plan-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 0.9em;
    margin-left: 5px;
    white-space: nowrap;
    border: 1px solid transparent;
    color: inherit; 
}

/* Blue for ACTIVE */
.overall-plan-tag.overall-active {
    background-color: #cce5ff;  /* Light Blue background */
    color: #004085;             /* Dark Blue text */
    border: 1px solid #b8daff;  /* Blue border */
}

/* Yellow for PAUSED */
.overall-plan-tag.overall-paused {
    background-color: #fff3cd;  /* Light Yellow background */
    color: #856404;             /* Dark Orange/Brown text */
    border: 1px solid #ffeeba;  /* Yellow border */
}

/* Gray for PENDING */
.overall-plan-tag.overall-pending {
    background-color: #e0e5ea;
    color: #495057;
    border: 1px solid #c4ccd5;
}

/* Red for SUSPENDED */
.overall-plan-tag.overall-suspended {
    background-color: #fcebeb;
    color: #b32133;
    border: 1px solid #b32133;
}

/* --- Status Tag Styling for One-Time Appointments (NEW/UPDATED) --- */
.status-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 0.85em;
    white-space: nowrap;
    margin-left: 5px; 
    border: 1px solid;
}

.status-tag.pending {
    background-color: #e0e5ea;
    color: #495057;
    border: 1px solid #c4ccd5;
}
/* CONFIRMED (Blue) */
.status-tag.confirmed {
    background-color: #cce5ff;
    color: #004085;
    border: 1px solid #b8daff;
}

.status-tag.ongoing {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}
/* COMPLETED (Green) - Added for completeness */
.status-tag.completed {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
/* CANCELLED/NO SHOW (Red) - Added for completeness */
.status-tag.cancelled, .status-tag.no-show {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
/* --- END Status Tag Styling --- */

/* --- WhatsApp Button CSS --- */
/* Default Green WhatsApp icon (for links outside the green button) */
.whatsapp-chat-link i.bxl-whatsapp,
.whatsapp-link i.bxl-whatsapp,
.action-btn:not(.whatsapp-chat-btn) i.bxl-whatsapp {
    color: #25D366 !important; /* WhatsApp Brand Green */
}
/* Override the hover state of dropdown menu links: manatiling Green ang icon */
.dropdown-menu a.whatsapp-chat-link:hover i.bxl-whatsapp,
.dropdown-menu a.whatsapp-link:hover i.bxl-whatsapp {
    color: #25D366 !important; 
}
/* General style for the WhatsApp action button (GREEN background/border, WHITE text) */
.action-btn.whatsapp-chat-btn {
    background-color: #25D366; 
    border: 2px solid #25D366;
    color: #fff; /* White text para babagay sa green background */
}
/* Hover style for the WhatsApp action button */
.action-btn.whatsapp-chat-btn:hover {
    color: #fff; /* White text on hover */
    background-color: #1FAF59; /* Slightly darker green on hover */
    border-color: #1FAF59;
}
/* SPECIFIC RULE: Tiyakin na ang icon ay White sa loob ng GREEN button */
.action-btn.whatsapp-chat-btn i.bxl-whatsapp {
    color: #fff !important; 
}

/* --- Sessions Button CSS --- */
.action-btn.sessions-btn {
    font-size: 0.9em;
    background-color: #008080; /* Teal */
    border: 2px solid #008080;
    color: white;
}
.action-btn.sessions-btn:hover {
    background-color: #009999; 
    border-color: #009999; 
    color: white; 
}
/* Tiyakin na ang icon ay white sa loob ng sessions button */
.action-btn.sessions-btn i {
    color: white;
}

/* --- New CSS for Edit Buttons (One-Time and Recurring Pending) - Blue Style --- */
.action-btn.edit-btn,
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
    background-color: #0056b3; /* Primary Blue */
    border: 2px solid #0056b3;
    color: white;
}
.action-btn.edit-btn:hover,
.action-btn.edit-plan-btn:hover {
    background-color: #004085; /* Darker Blue on Hover */
    border-color: #004085;
    color: white;
}
.action-btn.edit-btn i,
.action-btn.edit-plan-btn i {
    color: white !important; /* Ensure the icon is white */
}
/* --- END UPDATED CSS --- */


/* --- CSS Update to Compress Vertical Spacing --- */
/* Tiyakin na ang default margin/padding ay na-reset o binawasan sa lahat ng <p> */
.appointment-details p {
    margin-top: 0;
    margin-bottom: 3px; /* Binawasan ito mula 8px para ma-compress ang spacing */
    padding: 0;
}
/* Tiyakin na ang Reference Number ay may sapat na space sa ilalim */
.dashboard__container .appointment-details .ref-no-detail {
    margin-bottom: 15px; 
}

/* Ito ay para sa divider na may sapat na space sa itaas at ibaba */
.appointment-details .divider {
    margin: 10px 0; 
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

/* REMOVED: Specific rule for cleaners' list to compress names under the label (for multi-line) */
/* .staff-details-container .cleaners-list {
    margin-left: 73px; 
    margin-top: -5px; 
    margin-bottom: 5px; 
    display: block; 
    color: #555;
} */
/* --- END Staff Details CSS --- */
/* Remove the span and show only the value */




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
<section id="dashboard" class="content__section active">
<h2 class="section__title">Welcome back, <?php echo htmlspecialchars($client_name); ?>!!</h2>
<p class="welcome__message">Here's a quick overview of your services and upcoming appointments. You can book new services, view your history, or update your profile from the menu.</p>

<div class="summary-cards-container">

    <!-- Service Summary -->
    <div class="summary-card service-summary">
        <div class="card-content">
            <h3>Service Summary</h3>
            <table>
                <tr>
                    <td>Total Services Booked:</td>
                    <td class="count"><?php echo $total_services; ?></td>
                </tr>
                <tr>
                    <td>Recurring Services:</td>
                    <td class="count"><?php echo $recurring_count; ?></td>
                </tr>
                <tr>
                    <td>One-Time Services:</td>
                    <td class="count"><?php echo $one_time_count; ?></td>
                </tr>
            </table>
        </div>
        <i class='bx bx-notepad card-icon'></i>
    </div>

    <!-- Pending Feedback -->
   <div class="summary-card pending-feedback">
    <div class="card-content">
        <h3>Pending Feedback</h3>
       
        
        <!-- Buttons for One-Time and Recurring Feedback -->
        <div class="feedback-buttons">
            <a href="FR_one-time.php" class="feedback-link btn-one-time">
                <i class='bx bx-edit-alt'></i> One-Time Service
            </a>
            <a href="FR_recurring.php" class="feedback-link btn-recurring">
                <i class='bx bx-edit-alt'></i> Recurring Service
            </a>
        </div>
    </div>
    <i class='bx bx-message-dots card-icon'></i>
</div>


    <!-- Quick Actions -->
    <div class="summary-card quick-actions">
        <div class="card-content">
            <h3>Quick Actions</h3>
            <ul>
                <li><a href="BA_one-time.php"><i class='bx bx-calendar-plus'></i> Book One-Time Service</a></li>
                <li><a href="BA_recurring.php"><i class='bx bx-history'></i> Book Recurring Service</a></li>
                <li><a href="client_profile.php"><i class='bx bx-edit'></i> Update Profile</a></li>
            </ul>
        </div>
        <i class='bx bx-cog card-icon'></i>
    </div>

</div>


                    

<div class="dashboard__container upcoming-container">
<h2 class="container-title">
    <i class='bx bx-calendar'></i> Upcoming <strong>One-Time/Recurring</strong> Appointments
</h2>

<div class="appointment-list-container" id="one-time-appointments-list">
<?php
if ($combined_result && $combined_result->num_rows > 0) {
    while ($row = $combined_result->fetch_assoc()) {
        $booking_type = $row['booking_type'];
        $status = $row['status'] ?? "Pending";
        $isPending = (strtolower($status) === 'pending');
        
        if ($booking_type === 'One-Time') {
            // ONE-TIME BOOKING DISPLAY
            $ref_number = "ALZ-OT-" . date("ymd", strtotime($row['service_date'])) . "-" . str_pad($row['id'], 4, "0", STR_PAD_LEFT);
            
            $service_date = date("F d, Y", strtotime($row['service_date']));
            $service_time = date("g:i A", strtotime($row['service_time']));
            $duration = $row['duration'] ?? "N/A";
            $service_type = $row['service_type'] ?? "Service";
            $client_type = $row['client_type'] ?? "N/A";
            $address = $row['address'] ?? "";
            $property_type = $row['property_type'] ?? "N/A";
            $materials_provided = $row['materials_provided'] ?? "No";
            $materials_needed = $row['materials_needed'] ?? "";
            $comments = $row['comments'] ?? "";
            $media1 = $row['media1'] ?? "";
            $media2 = $row['media2'] ?? "";
            $media3 = $row['media3'] ?? "";

            echo '<div class="appointment-list-item" 
                data-date="'.htmlspecialchars($row['service_date']).'" 
                data-time="'.htmlspecialchars($row['service_time']).'"
                data-status="'.htmlspecialchars($status).'"
                data-search-terms="'.htmlspecialchars("$ref_number $service_date $service_time $address $client_type $status").'"
                data-property-layout="'.htmlspecialchars($property_type).'"
                data-materials-required="'.htmlspecialchars($materials_provided).'"
                data-additional-request="'.htmlspecialchars($comments).'"
                data-materials-description="'.htmlspecialchars($materials_needed).'"
                data-image-1="'.htmlspecialchars($media1).'"
                data-image-2="'.htmlspecialchars($media2).'"
                data-image-3="'.htmlspecialchars($media3).'"
            >
                <div class="button-group-top">
                    <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showDetailsModal(this.closest(\'.appointment-list-item\'))"><i class="bx bx-show"></i> View Details</a>';
                    
            // Only show Edit button if status is Pending
            if ($isPending) {
                echo '<a href="EDIT_one-time.php?booking_id='.$row['id'].'" class="action-btn edit-btn"><i class="bx bx-edit"></i> Edit</a>';
            }
            
            echo '<div class="dropdown-menu-container">
                        <button class="more-options-btn" onclick="toggleDropdown(this)"><i class="bx bx-dots-vertical-rounded"></i></button>
                        <ul class="dropdown-menu">';
            
            // Only show Cancel option if status is Pending
            if ($isPending) {
                echo '<li>
                        <a href="javascript:void(0)" class="cancel-link" 
                           onclick="showCancelModal('.$row['id'].', \''.$ref_number.'\')">
                           <i class="bx bx-x-circle" style="color: #B32133;"></i> Cancel
                        </a>
                    </li>';
            }
            
            echo '<li>
                        <a href="https://wa.me/971529009188" target="_blank" class="whatsapp-chat-link">
                            <i class="bx bxl-whatsapp"></i> Chat on WhatsApp
                        </a>
                    </li>
                        </ul>
                    </div>
                </div>
                
                <div class="appointment-details">
                    <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">'.$ref_number.'</span></p>
                    <p class="full-width-detail"><i class="bx bx-calendar-check"></i> <strong>Date:</strong> '.$service_date.'</p>
                    <p><i class="bx bx-time"></i> <strong>Time:</strong> '.$service_time.'</p>
                    <p class="duration-detail"><i class="bx bx-stopwatch"></i> <strong>Duration:</strong> '.$duration.'</p>
                    <p class="full-width-detail"><i class="bx bx-map-alt"></i> <strong>Address:</strong> '.$address.'</p>
                    <hr class="divider full-width-detail">
                    <p><i class="bx bx-building-house"></i> <strong>Client Type:</strong> '.$client_type.'</p>
                    <p class="service-type-detail"><i class="bx bx-wrench"></i> <strong>Service Type:</strong> '.$service_type.'</p>
                    <p class="full-width-detail status-detail">
                        <strong>Status:</strong>
                        <span class="status-tag '.strtolower($status).'"><i class="bx bx-hourglass"></i> '.$status.'</span>
                    </p>
                </div>
            </div>';
            
        } else {
            // RECURRING BOOKING DISPLAY
            $ref_number = "ALZ-RC-" . date("ymd") . "-" . str_pad($row['id'], 4, "0", STR_PAD_LEFT);
            
            $start_date = date("F d, Y", strtotime($row['start_date']));
            $end_date = date("F d, Y", strtotime($row['end_date']));
            $frequency = $row['frequency'] ?? "N/A";
            $service_type = $row['service_type'] ?? "Service";
            $client_type = $row['client_type'] ?? "N/A";
            $address = $row['address'] ?? "";
            $property_type = $row['property_type'] ?? "N/A";
            $materials_provided = $row['materials_provided'] ?? "No";
            $materials_needed = $row['materials_needed'] ?? "";
            $comments = $row['comments'] ?? "";
            $service_time = $row['service_time'] ?? "N/A";
            $duration = $row['duration'] ?? "N/A";
            $media1 = $row['media1'] ?? "";
            $media2 = $row['media2'] ?? "";
            $media3 = $row['media3'] ?? "";

            // Calculate sessions
            $start_dt = new DateTime($row['start_date']);
            $end_dt = new DateTime($row['end_date']);
            $freq_lower = strtolower($frequency);

            if ($freq_lower === 'weekly')       $interval = new DateInterval('P1W');
            elseif ($freq_lower === 'bi-weekly') $interval = new DateInterval('P2W');
            elseif ($freq_lower === 'monthly')  $interval = new DateInterval('P1M');
            else                                $interval = new DateInterval('P1W');

            $period = new DatePeriod($start_dt, $interval, $end_dt->modify('+1 day'));
            $no_of_sessions = iterator_count($period);

            echo '
            <div class="appointment-list-item"
                data-start-date="'.htmlspecialchars($row['start_date']).'"
                data-end-date="'.htmlspecialchars($row['end_date']).'"
                data-time="'.htmlspecialchars($service_time).'"
                data-duration="'.htmlspecialchars($duration).'"
                data-frequency="'.htmlspecialchars($frequency).'"
                data-sessions-count="'.htmlspecialchars($no_of_sessions).'"
                data-plan-status="'.htmlspecialchars($status).'"
                data-property-layout="'.htmlspecialchars($property_type).'"
                data-materials-required="'.htmlspecialchars($materials_provided).'"
                data-materials-description="'.htmlspecialchars($materials_needed).'"
                data-additional-request="'.htmlspecialchars($comments).'"
                data-image-1="'.htmlspecialchars($media1).'"
                data-image-2="'.htmlspecialchars($media2).'"
                data-image-3="'.htmlspecialchars($media3).'"
            >
                <div class="button-group-top">
                    <a href="javascript:void(0)" class="action-btn view-details-btn" onclick="showRecurringDetailsModal(this.closest(\'.appointment-list-item\'))"><i class="bx bx-show"></i> View Details</a>';
            
            // Only show Edit button if status is Pending
            if ($isPending) {
                echo '<a href="EDIT_recurring.php?booking_id='.$row['id'].'" class="action-btn edit-btn"><i class="bx bx-edit"></i> Edit</a>';
            }
            
            echo '<div class="dropdown-menu-container">
                        <button class="more-options-btn" onclick="toggleDropdown(this)"><i class="bx bx-dots-vertical-rounded"></i></button>
                        <ul class="dropdown-menu">';
            
            // Only show Cancel option if status is Pending
            if ($isPending) {
                echo '<li>
                        <a href="javascript:void(0)" class="cancel-link"
                           onclick="showCancelModal('.$row['id'].', \''.$ref_number.'\')">
                           <i class="bx bx-x-circle" style="color: #B32133;"></i> Cancel
                        </a>
                    </li>';
            }
            
            echo '<li>
                        <a href="https://wa.me/971529009188" target="_blank" class="whatsapp-chat-link">
                            <i class="bx bxl-whatsapp"></i> Chat on WhatsApp
                        </a>
                    </li>
                        </ul>
                    </div>
                </div>

                <div class="appointment-details">
                    <p class="full-width-detail ref-no-detail"><strong>Reference No:</strong> <span class="ref-no-value">'.$ref_number.'</span></p>
                    <p class="full-width-detail"><i class="bx bx-calendar-check"></i> <strong>Start:</strong> '.$start_date.'</p>
                    <p class="full-width-detail"><i class="bx bx-calendar-check"></i> <strong>End:</strong> '.$end_date.'</p>
                    <p><i class="bx bx-repeat"></i> <strong>Frequency:</strong> '.$frequency.'</p>
                    <p><i class="bx bx-time"></i> <strong>Time:</strong> '.$service_time.'</p>
                    <p class="duration-detail"><i class="bx bx-stopwatch"></i> <strong>Duration:</strong> '.$duration.'</p>
                    <p class="full-width-detail"><i class="bx bx-map-alt"></i> <strong>Address:</strong> '.$address.'</p>
                    <p style="font-size:0.95em;"><strong>No. of Sessions:</strong> '.$no_of_sessions.'</p>
                    <hr class="divider full-width-detail">
                    <p><i class="bx bx-building-house"></i> <strong>Client Type:</strong> '.$client_type.'</p>
                    <p class="service-type-detail"><i class="bx bx-wrench"></i> <strong>Service Type:</strong> '.$service_type.'</p>
                    <p class="full-width-detail status-detail">
    <strong>Status:</strong>
    <span class="overall-plan-tag overall-'.strtolower($status).'"><i class="bx bx-hourglass"></i> '.$status.'</span>
</p>
                </div>
            </div>';
        }
    }
} else {
    echo '<p class="no-appointments-message">No upcoming appointments found.</p>';
}
?>





</section>
</main>


</div> 
<a href="#header" id="backToTopBtn" title="Back to Top"><i class='bx bx-up-arrow-alt'></i> Back to Top</a>

<div id="logoutModal" class="modal">
<div class="modal__content">
<h3 class="modal__title">Are you sure you want to log out?</h3>
<div class="modal__actions">
<button id="cancelLogout" class="btn btn--secondary" onclick="closeModal('logoutModal')">Cancel</button>
<button id="confirmLogout" class="btn btn--primary">Log Out</button>
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
function showCancelModal(bookingId, refNo) {
    const cancelRefEl = document.getElementById('cancel-ref-number');
    const confirmBtn = document.getElementById('confirmCancel');
    const successModal = document.getElementById('cancelSuccessModal');
    const cancelledRefEl = document.getElementById('cancelled-ref-number');

    // Set the reference number in the modal
    cancelRefEl.innerText = refNo;
    confirmBtn.onclick = null;

    // Confirm cancellation
    confirmBtn.onclick = async function() {
        closeModal('cancelModal');

        try {
            const response = await fetch('cancel_appointment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(bookingId)
            });

            const text = await response.text();
            console.log('Raw server response:', text);

            let data;
            try { 
                data = JSON.parse(text); 
            } catch (err) {
                console.error('Invalid JSON from server:', text);
                alert('Server returned invalid response. Check console.');
                return;
            }

            if (data.success) {
                // Show success modal
                cancelledRefEl.innerText = refNo;
                successModal.style.display = 'flex';

                // Update status in the appointment list
                const item = document.querySelector(`.appointment-list-item[data-search-terms*="${refNo}"]`);
                if (item) {
                    item.setAttribute('data-status', 'Cancelled');
                    const statusSpan = item.querySelector('.status-tag, .overall-plan-tag');
                    if (statusSpan) {
                        statusSpan.className = 'status-tag cancelled';
                        statusSpan.innerHTML = `<i class="bx bx-x-circle"></i> Cancelled`;
                    }
                }
            } else {
                alert('Failed: ' + (data.message || 'Unknown error'));
            }
        } catch (err) {
            console.error('Fetch error:', err);
            alert('Error connecting to server. Check console.');
        }
    };

    // Show the cancel modal
    document.getElementById('cancelModal').style.display = 'flex';
}




// Close modal function
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

// Close modal helper
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}


function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

// Close modal function
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}


// Close modal function
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
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
    
// --- START: Status Sorting Logic (Updated to handle Recurring) ---

/**
 * Sorts appointment items within a container based on a predefined status order,
 * or filters them if a specific status is chosen.
 * Order: PENDING, ONGOING, CONFIRMED, COMPLETED, CANCELLED, NO SHOW
 * @param {string} containerId - The ID of the appointment list container.
 * @param {string} filterStatus - The status to filter by (or 'default' to sort all).
 */
function sortAppointmentsByStatus(containerId, filterStatus = 'default') {
    const container = document.getElementById(containerId);
    if (!container) return;

    // The desired order of statuses for default sorting
    const statusOrder = {
        'PENDING': 1,
        'ONGOING': 2, // <-- UPDATED: Now higher priority than Confirmed
        'ACTIVE': 2,  // Recurring 'ACTIVE' is similar to One-time 'ONGOING'
        'CONFIRMED': 3, // <-- UPDATED: Now lower priority than Ongoing
        'COMPLETED': 4,
        'CANCELLED': 5,
        'NO SHOW': 6,
        'SUSPENDED': 7 // Added for recurring plan status
    };

    // Get all appointment items
    const items = Array.from(container.querySelectorAll('.appointment-list-item'));
    const noAppointmentsMessage = container.querySelector('.no-appointments-message');

    // 1. Apply Filtering (Visibility)
    let visibleCount = 0;
    items.forEach(item => {
        // Use data-status for one-time or data-plan-status for recurring
        const itemStatus = item.getAttribute('data-status') || item.getAttribute('data-plan-status'); 
        
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
            const statusA = a.getAttribute('data-status') || a.getAttribute('data-plan-status');
            const statusB = b.getAttribute('data-status') || b.getAttribute('data-plan-status');
    
            const orderA = statusOrder[statusA] || 999; // Use a high number for unknown statuses
            const orderB = statusOrder[statusB] || 999; 
    
            // Sort by Status Order first
            if (orderA !== orderB) {
                return orderA - orderB;
            }
            
            // If statuses are the same, sort by Date (ascending)
            const dateA = new Date(a.getAttribute('data-date'));
            const dateB = new Date(b.getAttribute('data-date'));
            return dateA.getTime() - dateB.getTime();

        });

        // Re-append the sorted items to the container
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
 */
function initializeStatusSortingOnLoad() {
    // Nagbago na ang IDs: 'one-time-appointments-list' at 'recurring-plans-list' na lang ang gagamitin
    const containerIds = [
        'one-time-appointments-list',
        'recurring-plans-list'
    ];

    containerIds.forEach(id => {
        // Initial call uses 'default' to apply sorting to all visible items
        sortAppointmentsByStatus(id, 'default');
    });
}

        

// Call the initialization function when the page loads
document.addEventListener('DOMContentLoaded', initializeStatusSortingOnLoad);

// --- END: Status Sorting Logic (Updated to handle Recurring) ---
</script> 
<script src="client_db.js"></script> 	 	 	
<script src="HIS_function.js"></script> 	
</body>
</html>