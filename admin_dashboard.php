<?php

require_once 'connection.php';
// ✅ Get current month and year


session_start();

// Get admin email from session, default to null
$admin_email = $_SESSION['email'] ?? null;
$adminFullName = 'Administrator'; // default if not found

if ($admin_email) {
    $query = "SELECT first_name, last_name FROM admins WHERE email = '$admin_email' LIMIT 1";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $adminFullName = $row['first_name'] . ' ' . $row['last_name'];
    }
}

$current_month_year = date('F Y');

// ===========================
// FETCH DYNAMIC DATA
// ===========================


// ✅ Total Clients (from clients table)
$sql_clients = "SELECT COUNT(*) AS total FROM clients";
$result_clients = $conn->query($sql_clients);
$totalClients = ($result_clients && $row = $result_clients->fetch_assoc()) ? (int)$row['total'] : 0;

// ✅ Total Bookings (from bookings table)
$sql_bookings = "SELECT COUNT(*) AS total FROM bookings";
$result_bookings = $conn->query($sql_bookings);
$totalBookings = ($result_bookings && $row = $result_bookings->fetch_assoc()) ? (int)$row['total'] : 0;

// ✅ Total Employees (only if table exists)
$sql_employees = "SHOW TABLES LIKE 'employees'";
$result_employees = $conn->query($sql_employees);
if ($result_employees && $result_employees->num_rows > 0) {
    $emp = $conn->query("SELECT COUNT(*) AS total FROM employees");
    $totalEmployees = ($emp && $row = $emp->fetch_assoc()) ? (int)$row['total'] : 0;
} else {
    $totalEmployees = 0;
}
// ✅ Total Concerns (from bookings with issues)
$sql_concerns = "SELECT COUNT(*) AS total FROM bookings 
                 WHERE (issue_type IS NOT NULL AND issue_type != '') 
                 OR (issue_description IS NOT NULL AND issue_description != '')";
$result_concerns = $conn->query($sql_concerns);
$totalConcerns = ($result_concerns && $row = $result_concerns->fetch_assoc()) ? (int)$row['total'] : 0;

// ✅ One-Time services breakdown
$one_time_services_breakdown = [];
$sql_one_time = "
    SELECT service_type, COUNT(*) AS total
    FROM bookings
    WHERE booking_type = 'One-Time'
    GROUP BY service_type
";
$result_one = $conn->query($sql_one_time);
if ($result_one && $result_one->num_rows > 0) {
    while ($row = $result_one->fetch_assoc()) {
        $one_time_services_breakdown[$row['service_type']] = (int)$row['total'];
    }
}

// ✅ Recurring services breakdown
$recurring_services_breakdown = [];
$sql_recurring = "
    SELECT service_type, COUNT(*) AS total
    FROM bookings
    WHERE booking_type = 'Recurring'
    GROUP BY service_type
";
$result_rec = $conn->query($sql_recurring);
if ($result_rec && $result_rec->num_rows > 0) {
    while ($row = $result_rec->fetch_assoc()) {
        $recurring_services_breakdown[$row['service_type']] = (int)$row['total'];
    }
}

// ✅ Compute totals
$total_one_time = array_sum($one_time_services_breakdown);
$total_recurring = array_sum($recurring_services_breakdown);
$total_active_bookings = $total_one_time + $total_recurring;

// ✅ Determine max items for layout balance
$max_list_items = max(count($one_time_services_breakdown), count($recurring_services_breakdown));

// ✅ Helper function: render breakdown list
function render_breakdown_list($data, $max_items) {
    $html = '<ul class="breakdown-list">';
    $current_count = 0;
    foreach ($data as $service => $count) {
        $html .= '<li><span class="service-name">' . htmlspecialchars($service) . '</span> <span class="count">' . number_format($count) . '</span></li>';
        $current_count++;
    }
    for ($i = $current_count; $i < $max_items; $i++) {
        $html .= '<li class="placeholder-item"><span class="service-name"></span> <span class="count"></span></li>';
    }
    $html .= '</ul>';
    return $html;
}
$sql_pending = "
    SELECT id, full_name, email, phone, service_type, booking_type, 
           service_date, service_time, address, created_at
    FROM bookings
    WHERE status = 'Pending'
    ORDER BY created_at ASC
    LIMIT 10
";
$result_pending = $conn->query($sql_pending);
$pending_bookings = [];
if ($result_pending && $result_pending->num_rows > 0) {
    while ($row = $result_pending->fetch_assoc()) {
        $pending_bookings[] = $row;
    }
}
$total_pending = count($pending_bookings);

// ===========================
// BOOKINGS CHART DATA
// ===========================

// Current month range
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');

// 1️⃣ Bookings Trend - daily bookings this month
$trendQuery = "SELECT service_date as day, COUNT(*) as total 
               FROM bookings 
               WHERE service_date BETWEEN ? AND ? 
               GROUP BY service_date 
               ORDER BY service_date ASC";
$stmtTrend = $conn->prepare($trendQuery);
$stmtTrend->bind_param("ss", $startOfMonth, $endOfMonth);
$stmtTrend->execute();
$resultTrend = $stmtTrend->get_result();

$trendLabels = [];
$trendData = [];
while($row = $resultTrend->fetch_assoc()) {
    $trendLabels[] = $row['day'];
    $trendData[] = $row['total'];
}

// 2️⃣ Service Popularity - bookings per service_type this month
$serviceQuery = "SELECT service_type, COUNT(*) as total 
                 FROM bookings 
                 WHERE service_date BETWEEN ? AND ? 
                 GROUP BY service_type";
$stmtService = $conn->prepare($serviceQuery);
$stmtService->bind_param("ss", $startOfMonth, $endOfMonth);
$stmtService->execute();
$resultService = $stmtService->get_result();

$serviceLabels = [];
$serviceData = [];
while($row = $resultService->fetch_assoc()) {
    $serviceLabels[] = $row['service_type'];
    $serviceData[] = $row['total'];
}

$conn->close();
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
 .dashboard__sidebar {
    min-width: 240px;
    width: 240px;
    flex-shrink: 0;
}

.dashboard__wrapper {
    display: flex;
    min-height: 100vh;
}

.dashboard__content {
    flex: 1;
    overflow-x: auto;
}
/* --- NEW: Pending Bookings Section Styles --- */
.pending-bookings-container {
    margin-top: 20px;
    padding: 25px;
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-top: 8px solid #FF9800; /* Orange for pending */
}

.pending-bookings-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.pending-bookings-header h3 {
    font-size: 1.5em;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.pending-bookings-header h3 i {
    color: #FF9800;
    font-size: 1.3em;
}

.pending-count-badge {
    background-color: #FF9800;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 1.1em;
}

.pending-bookings-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.pending-booking-item {
    background-color: #f9f9f9;
    border-left: 5px solid #FF9800;
    padding: 15px 20px;
    margin-bottom: 15px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: transform 0.2s, box-shadow 0.2s;
}

.pending-booking-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.booking-info {
    flex: 1;
}

.booking-info-row {
    display: flex;
    gap: 20px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.booking-info-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.95em;
    color: #555;
}

.booking-info-item i {
    color: #FF9800;
    font-size: 1.1em;
}

.booking-info-item strong {
    color: #333;
    font-weight: 600;
}

.booking-type-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: bold;
    margin-left: 10px;
}

.booking-type-badge.one-time {
    background-color: #e3f2fd;
    color: #1976d2;
}

.booking-type-badge.recurring {
    background-color: #f3e5f5;
    color: #7b1fa2;
}

.booking-actions {
    display: flex;
    gap: 10px;
}

.booking-actions .btn {
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9em;
    font-weight: 600;
    transition: background-color 0.3s, transform 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.booking-actions .btn-view {
    background-color: #2196F3;
    color: white;
}

.booking-actions .btn-view:hover {
    background-color: #1976D2;
    transform: translateY(-2px);
}

.booking-actions .btn-confirm {
    background-color: #4CAF50;
    color: white;
}

.booking-actions .btn-confirm:hover {
    background-color: #45a049;
    transform: translateY(-2px);
}

.no-pending-message {
    text-align: center;
    padding: 40px 20px;
    color: #999;
    font-size: 1.1em;
}

.no-pending-message i {
    font-size: 3em;
    color: #ddd;
    margin-bottom: 15px;
}

/* --- END: Pending Bookings Section Styles --- */
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
    max-width: 43%; /* Nilimitahan ang maximum width sa 40% */
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

.booking-info-row {
    display: flex;
    flex-wrap: wrap; /* allows wrapping on smaller screens */
    gap: 15px;       /* space between columns */
    margin-bottom: 4px;
    align-items: center; /* vertically align items */
}

.booking-info-item {
    display: flex;
    align-items: center;
    gap: 4px;
    min-width: 180px; /* ensures uniform width for alignment */
    font-size: 14px;
}

.booking-info-row:last-child .booking-info-item {
    min-width: 180px; /* apply same width to booked column */
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
                        <li class="menu__item"><a href="archived_clients.php?content=manage-archive" class="menu__link" data-content="manage-archive">Archive</a></li>
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
                <li class="menu__item"><a href="manage_groups.php" class="menu__link "><i class='bx bx-group'></i> Manage Groups</a></li>
 <li class="menu__item"><a href="admin_feedback_dashboard.php" class="menu__link "><i class='bx bx-star'></i> Feedback Overview</a></li>
                <!-- <li class="menu__item"><a href="FR.php?content=feedback-ratings" class="menu__link" data-content="feedback-ratings"><i class='bx bx-star'></i> Feedback & Ratings</a></li> -->
                
                <li class="menu__item"><a href="Reports.php?content=reports" class="menu__link" data-content="reports"><i class='bx bx-file'></i> Reports</a></li>

                <li class="menu__item"><a href="concern.php?content=profile" class="menu__link" data-content="profile"><i class='bx bx-info-circle'></i> Issues&Concerns</a></li>
                
                <li class="menu__item"><a href="admin_profile.php?content=profile" class="menu__link" data-content="profile"><i class='bx bx-user'></i> Profile</a></li>

                
                
                <li class="menu__item"><a href="javascript:void(0)" class="menu__link" data-content="logout" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
            </ul>
        </aside>
 
        
    <main class="dashboard__content">
<section id="dashboard" class="content__section active">
<h2 class="section__title">
    Welcome back, <?= htmlspecialchars($adminFullName) ?>!
</h2>
<p class="welcome__message">Here's a quick overview of system activity and pending tasks. Use the sidebar for management and reports.</p>
<div class="summary-cards-container">
    
     

  <div class="summary-cards-container">
            <!-- Total Clients -->
            <div class="summary-card stat-card total-clients">
                <div class="card-content">
                    <h2 class="stat-value"><?php echo $totalClients; ?></h2>
                    <p class="stat-title">Total Clients</p>
                </div>
                <i class='bx bx-group card-icon'></i>
            </div>

            <!-- Total Bookings -->
            <div class="summary-card stat-card total-bookings">
                <div class="card-content">
                    <h2 class="stat-value"><?php echo $totalBookings; ?></h2>
                    <p class="stat-title">Total Bookings</p>
                </div>
                <i class='bx bx-book-open card-icon'></i>
            </div>

            <!-- Concerns (Static Example) -->
            <!-- Concerns (Dynamic from Database) -->
<div class="summary-card stat-card concerns">
    <div class="card-content">
        <h2 class="stat-value"><?php echo $totalConcerns; ?></h2>
        <p class="stat-title">No. of Concerns</p>
    </div>
    <i class='bx bx-error-alt card-icon'></i>
</div>

            <!-- Total Employees -->
            <div class="summary-card stat-card active-employees">
                <div class="card-content">
                    <h2 class="stat-value"><?php echo $totalEmployees; ?></h2>
                    <p class="stat-title">No. of Employees</p>
                </div>
                <i class='bx bx-user-pin card-icon'></i>
            </div>
        </div>
    

</div>
<!-- ========================= -->
<!-- PENDING BOOKINGS SECTION -->
<!-- ========================= -->
<div class="pending-bookings-container">
    <div class="pending-bookings-header">
        <h3>
            <i class='bx bx-time-five'></i>
            Pending Bookings 
        </h3>
        <span class="pending-count-badge"><?php echo $total_pending; ?> Pending</span>
    </div>

    <?php if (count($pending_bookings) > 0): ?>
        <ul class="pending-bookings-list">
            <?php foreach ($pending_bookings as $booking): ?>
                <li class="pending-booking-item">
                    <div class="booking-info">
                        <div class="booking-info-row">
                            <div class="booking-info-item">
                                <i class='bx bx-user'></i>
                                <strong><?php echo htmlspecialchars($booking['full_name']); ?></strong>
                            </div>
                            <div class="booking-info-item">
                                <i class='bx bx-phone'></i>
                                <?php echo htmlspecialchars($booking['phone']); ?>
                            </div>
                            <div class="booking-info-item">
                                <i class='bx bx-envelope'></i>
                                <?php echo htmlspecialchars($booking['email']); ?>
                            </div>
                        </div>
                        <div class="booking-info-row">
                            <div class="booking-info-item">
                                <i class='bx bx-calendar'></i>
                                <strong>Date:</strong> <?php echo date('M d, Y', strtotime($booking['service_date'])); ?>
                            </div>
                            <div class="booking-info-item">
                                <i class='bx bx-time'></i>
                                <strong>Time:</strong> <?php echo date('h:i A', strtotime($booking['service_time'])); ?>
                            </div>
                            <div class="booking-info-item">
                                <i class='bx bx-customize'></i>
                                <strong>Service:</strong> <?php echo htmlspecialchars($booking['service_type']); ?>
                                <span class="booking-type-badge <?php echo strtolower($booking['booking_type']) === 'one-time' ? 'one-time' : 'recurring'; ?>">
                                    <?php echo htmlspecialchars($booking['booking_type']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="booking-info-row">
                            <div class="booking-info-item">
                                <i class='bx bx-map'></i>
                                <strong>Address:</strong> <?php echo htmlspecialchars(substr($booking['address'], 0, 50)) . (strlen($booking['address']) > 50 ? '...' : ''); ?>
                            </div>
                            <div class="booking-info-item">
                                <i class='bx bx-calendar-plus'></i>
                                <strong>Booked:</strong> <?php echo date('M d, Y h:i A', strtotime($booking['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <div class="booking-actions">
                       
                        <button onclick="confirmBooking(<?php echo $booking['id']; ?>)" class="btn btn-confirm">
                            <i class='bx bx-check'></i> Confirm
                        </button>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <?php if ($total_pending > 10): ?>
            <div class="view-all-container">
                <a href="AP_one-time.php?filter=pending" class="view-all-link">
                    View All Pending Bookings <i class='bx bx-right-arrow-alt'></i>
                </a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-pending-message">
            <i class='bx bx-check-circle'></i>
            <p>No pending bookings at the moment. All caught up!</p>
        </div>
    <?php endif; ?>
</div>

<!-- ========================= -->
<!-- DASHBOARD CHARTS ROW -->
<!-- ========================= -->
<div class="charts-row" style="display:flex; gap:20px; flex-wrap:wrap; margin:20px 0;">

    <!-- Bookings Trend Chart -->
    <div class="dashboard__container performance-overview-container chart-half-width" style="flex:1 1 45%; background:#fff; border-radius:12px; padding:20px; box-shadow:0 3px 10px rgba(0,0,0,0.1);">
        <div class="container-title">
            <i class='bx bx-trending-up'></i> Performance Overview - Bookings Trend (<?php echo $current_month_year; ?>)
        </div>
        <div class="chart-container">
            <canvas id="bookingsTrendChart"></canvas>
        </div>
        <div class="view-all-container">
    
</div>



    </div>

    <!-- Service Popularity Chart -->
    <div class="dashboard__container performance-overview-container chart-half-width" style="flex:1 1 45%; background:#fff; border-radius:12px; padding:20px; box-shadow:0 3px 10px rgba(0,0,0,0.1);">
        <div class="container-title">
            <i class='bx bx-pie-chart-alt-2'></i> Service Popularity Breakdown (<?php echo $current_month_year; ?>)
        </div>
        <div class="chart-container" style="max-height:350px;">
            <canvas id="servicePopularityChart"></canvas>
        </div>
       

<div class="view-all-container">
   
</div>

    </div>

</div>


<!-- ========================= -->
<!-- CHART.JS SCRIPT -->
<!-- ========================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Bookings Trend Chart
    const bookingsTrendCtx = document.getElementById('bookingsTrendChart').getContext('2d');
    const bookingsTrendChart = new Chart(bookingsTrendCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($trendLabels); ?>,
            datasets: [{
                label: 'Bookings per Day',
                data: <?php echo json_encode($trendData); ?>,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                x: { title: { display: true, text: 'Date' } },
                y: { title: { display: true, text: 'Number of Bookings' }, beginAtZero: true }
            }
        }
    });

    // Service Popularity Chart
    const servicePopularityCtx = document.getElementById('servicePopularityChart').getContext('2d');
    const servicePopularityChart = new Chart(servicePopularityCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($serviceLabels); ?>,
            datasets: [{
                label: 'Bookings per Service',
                data: <?php echo json_encode($serviceData); ?>,
                backgroundColor: [
                    '#007bff','#28a745','#ffc107','#dc3545','#17a2b8','#6f42c1','#fd7e14','#20c997'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { mode: 'nearest', intersect: true }
            }
        }
    });
</script>

</section>

<!-- MAIN DASHBOARD -->
<section id="manage-clients" class="content__section" onclick="redirectTo('clients.php')">
    <h2 class="section__title">User Management - Clients</h2>
    <p>Manage all client accounts here.</p>
</section>

<section id="manage-employees" class="content__section" onclick="redirectTo('employees.html')">
    <h2 class="section__title">User Management - Employees</h2>
    <p>Manage all employee accounts here.</p>
</section>

<section id="manage-admins" class="content__section" onclick="redirectTo('admins.html')">
    <h2 class="section__title">User Management - Admins</h2>
    <p>Manage all administrator accounts here.</p>
</section>

<section id="appointments-one-time" class="content__section" onclick="redirectTo('AP_one-time.php')">
    <h2 class="section__title">Appointment Management - One-time Service</h2>
    <p>Manage one-time service appointments.</p>
</section>

<section id="appointments-recurring" class="content__section" onclick="redirectTo('appointments_recurring.html')">
    <h2 class="section__title">Appointment Management - Recurring Service</h2>
    <p>Manage recurring service contracts and appointments.</p>
</section>







<script>
function redirectTo(page) {
    window.location.href = page;
}
</script>

<style>

</style>




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
<script>// Fixed Navigation Script - Replace the entire script section at the bottom

const navLinks = document.querySelectorAll('.sidebar__menu .menu__link');
const logoutLink = document.querySelector('.sidebar__menu .menu__link[data-content="logout"]');
const logoutModal = document.getElementById('logoutModal');
const cancelLogoutBtn = document.getElementById('cancelLogout');
const confirmLogoutBtn = document.getElementById('confirmLogout');

// Handle logout modal
function showLogoutModal() {
    if (logoutModal) logoutModal.classList.add('show');
}

if (cancelLogoutBtn && logoutModal) {
    cancelLogoutBtn.addEventListener('click', function() {
        logoutModal.classList.remove('show');
    });
}

if (confirmLogoutBtn) {
    confirmLogoutBtn.addEventListener('click', function() {
        window.location.href = "landing_page2.html";
    });
}

// Dropdown and Navigation Handler
(function(){
  const nav = document.querySelector('.sidebar__menu');
  if (!nav) return;

  const dropdownParents = nav.querySelectorAll('.has-dropdown');
  const menuLinks = nav.querySelectorAll('.menu__link');

  function closeAllDropdowns(except = null) {
    dropdownParents.forEach(item => {
      if (item !== except) {
        item.classList.remove('open');
        const link = item.querySelector('.menu__link');
        if (link) link.classList.remove('active', 'active-parent');
      }
    });
  }

  // Attach click handler for parent dropdown links ONLY
  dropdownParents.forEach(parent => {
    const parentLink = parent.querySelector(':scope > .menu__link'); // Direct child only
    if (!parentLink) return;

    parentLink.addEventListener('click', function(e) {
      e.preventDefault(); // Always prevent for dropdown parents
      const isOpen = parent.classList.contains('open');
      if (isOpen) {
        parent.classList.remove('open');
        parentLink.classList.remove('active', 'active-parent');
      } else {
        closeAllDropdowns(parent);
        parent.classList.add('open');
        parentLink.classList.add('active', 'active-parent');
      }
    });
  });

  // Handle clicks for CHILD menu links (inside dropdowns)
  const childLinks = nav.querySelectorAll('.dropdown__menu .menu__link');
  childLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      const contentId = link.getAttribute('data-content');
      
      // Map content IDs to their respective pages
      const pageMap = {
        'manage-clients': 'clients.php',
        'manage-employees': 'UM_employees.php',
        'manage-admins': 'UM_admins.php',
        'appointments-one-time': 'AP_one-time.php',
        'appointments-recurring': 'AP_recurring.php',
        'employee-scheduling': 'employeessched.php',
        'feedback-ratings': 'FR.php',
        'reports': 'Reports.php',
        'profile': 'admin_profile.php'
      };

      // If this link should redirect to another page
      if (pageMap[contentId]) {
        e.preventDefault();
        window.location.href = pageMap[contentId] + '?content=' + contentId;
        return;
      }

      // Handle logout
      if (contentId === 'logout') {
        e.preventDefault();
        showLogoutModal();
        return;
      }

      // For in-page sections (like dashboard)
      const target = document.getElementById(contentId);
      if (target) {
        e.preventDefault();
        document.querySelectorAll('.content__section').forEach(s => s.classList.remove('active'));
        target.classList.add('active');

        menuLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        const parentItem = link.closest('.has-dropdown');
        if (parentItem) {
          const parentLink = parentItem.querySelector(':scope > .menu__link');
          if (parentLink) parentLink.classList.add('active-parent');
        }
      }
    });
  });

  // Handle direct menu links (not in dropdowns)
  const directLinks = Array.from(menuLinks).filter(link => {
    return !link.closest('.dropdown__menu') && !link.closest('.has-dropdown');
  });

  directLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      const contentId = link.getAttribute('data-content');
      
      if (contentId === 'logout') {
        e.preventDefault();
        showLogoutModal();
        return;
      }

      if (contentId === 'dashboard') {
        e.preventDefault();
        window.location.href = 'admin_dashboard.php?content=dashboard';
        return;
      }

      // Handle other direct links as needed
      const target = document.getElementById(contentId);
      if (target) {
        e.preventDefault();
        closeAllDropdowns();
        document.querySelectorAll('.content__section').forEach(s => s.classList.remove('active'));
        target.classList.add('active');

        menuLinks.forEach(l => l.classList.remove('active', 'active-parent'));
        link.classList.add('active');
      }
    });
  });

  // Restore state from URL on load
  window.addEventListener('load', function() {
    const params = new URLSearchParams(window.location.search);
    const content = params.get('content');
    if (content) {
      const link = nav.querySelector(`.menu__link[data-content="${content}"]`);
      if (link) {
        const parentItem = link.closest('.has-dropdown');
        if (parentItem) {
          parentItem.classList.add('open');
          const parentLink = parentItem.querySelector(':scope > .menu__link');
          if (parentLink) parentLink.classList.add('active-parent');
        }
        link.classList.add('active');
      }
    }
  });
})();
// ========================= 
// BOOKING CONFIRMATION SCRIPT 
// ========================= 
function confirmBooking(bookingId) {
    if (confirm('Are you sure you want to confirm this booking?')) {
        // Send AJAX request to confirm the booking
        fetch('confirm_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'booking_id=' + bookingId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Booking confirmed successfully!');
                location.reload(); // Reload to update the list
            } else {
                alert('Error confirming booking: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error confirming booking. Please try again.');
        });
    }
}
</script>

</body>
</html>