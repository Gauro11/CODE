<?php
// PHP code for includes or logic goes here (if any)


// KINAKAILANGA N: DYNAMIC DATA FOR CLIENT MANAGEMENT PAGE
// Sample Data for Client Management (For Placeholder/Initial Setup)
$total_registered_clients = 125;
$active_clients_count = 110;
$new_clients_this_month = 15;
$clients_with_open_concern = 8;

// NEW: Kinuha ang kasalukuyang buwan (Hal. "October")
$current_month = date('F'); 

// BINAGO: Idinagdag ang 'birthdate' field
$client_list_data = [
    ['id' => 101, 'first_name' => 'Maria', 'last_name' => 'Santos', 'birthdate' => '1990-05-15', 'email' => 'maria.s@example.com', 'contact' => '+971501112233', 'status' => 'Active', 'bookings' => 5],
    ['id' => 102, 'first_name' => 'Ahmed', 'last_name' => 'Almarzooqi', 'birthdate' => '1985-11-20', 'email' => 'ahmed.a@example.com', 'contact' => '+971554445566', 'status' => 'Inactive', 'bookings' => 0],
    ['id' => 103, 'first_name' => 'Chen', 'last_name' => 'Li', 'birthdate' => '1998-07-01', 'email' => 'chen.li@example.com', 'contact' => '+971527778899', 'status' => 'Active', 'bookings' => 12],
    ['id' => 104, 'first_name' => 'Fatima', 'last_name' => 'Hassan', 'birthdate' => '2000-01-25', 'email' => 'fatima.h@example.com', 'contact' => '+971509990011', 'status' => 'Active', 'bookings' => 2],
    // Idagdag pa ang iba kung kailangan, for display purposes lang ito
];

// NEW: Sample Data for Archived Clients (DINAGDAGAN NG ID para sa Restore Function)
$archived_clients_data = [
    ['id' => 201, 'first_name' => 'Rashid', 'last_name' => 'Abdullah', 'birthdate' => '1992-07-10', 'contact' => '+971920819507', 'email' => 'rashid.abdullah@example.com'],
    ['id' => 202, 'first_name' => 'Mariam', 'last_name' => 'Ebrahimi', 'birthdate' => '1988-07-17', 'contact' => '+971166420567', 'email' => 'mariam.ebrahimi@example.com'],
    ['id' => 203, 'first_name' => 'Tariq', 'last_name' => 'Khan', 'birthdate' => '2001-07-31', 'contact' => '+971110620035', 'email' => 'tariq.khan@example.com'],
];


// KINAKAILANGAN: Function para mag-render ng status badge
function render_status_badge($status) {
    $class = '';
    switch ($status) {
        case 'Active':
            $class = 'status-active';
            break;
        case 'Inactive':
            $class = 'status-inactive';
            break;
        case 'Pending':
            $class = 'status-pending';
            break;
        default:
            $class = 'status-default';
    }
    return '<span class="status-badge ' . $class . '">' . htmlspecialchars($status) . '</span>';
}

// Inalis na ang mga Booking breakdown functions/variables

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
<link rel="stylesheet" href="UM_design.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
/* ======================================= */
/* --- INTERNAL CSS FOR CLIENTS TABS --- */
/* ======================================= */

.client-tabs {
    display: flex;
    border-bottom: 2px solid #e0e0e0; 
    margin-bottom: 0;
    margin-top: 20px;
    padding: 0 10px;
}

.tab-button {
    padding: 12px 20px;
    cursor: pointer;
    border: none;
    background-color: transparent;
    border-bottom: 3px solid transparent; 
    font-weight: 700;
    color: #6c757d; 
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1em;
    margin-bottom: -2px;
    
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

/* NEW HOVER DESIGN - Mas subtle, mas madilim na text, walang background color */
.tab-button:hover {
    color: #333; 
    opacity: 0.85; 
    background-color: transparent; 
}

.tab-button.active {
    color: #007bff;
    border-bottom: 3px solid #007bff;
    background-color: #fff;
    box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.05);
}

.tab-pane {
    display: none;
    padding-top: 0px; 
    width: 100%;
}

.tab-pane.active {
    display: block;
}

.dashboard__container.client-management-data-container {
    border-top-left-radius: 0;
    border-top-right-radius: 0;
    margin-top: 0;
    padding-top: 20px; 
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06); 
    border-top: none; 
}

/* UPDATED: Title Styles (Bold and smaller text) */
.dashboard__container .container-title {
    font-weight: bold; 
    font-size: 1.1em; 
    color: #333;
}
.dashboard__container .container-title i {
    font-size: 1.4em; 
}

/* --- Tiyakin na Title Case ang Table Headers (FIX) --- */
.client-table th, .archived-client-table th {
    text-transform: none; /* ITO ANG FIX para maging Title Case */
    font-size: 0.95em; 
}

/* --- Internal CSS para sa Search Results Row kapag Walang Nakita (Requested by User) --- */
.client-table tr#noResultsRow td,
.archived-client-table tr#noArchivedResultsRow td {
    color: #6c757d !important;
    font-weight: 400 !important;
    font-style: italic;
    padding: 20px !important;
}

/* --- INTERNAL CSS for Modal Button Colors --- */

/* Archive Button (Orange) */
.btn--primary.archive-btn {
    background-color: #FF9800 !important;
    border-color: #FF9800 !important;
}
.btn--primary.archive-btn:hover {
    background-color: #e68900 !important;
    border-color: #e68900 !important;
}

/* Restore Button (Green) */
.btn--primary.restore-btn {
    background-color: #4CAF50 !important;
    border-color: #4CAF50 !important;
}
.btn--primary.restore-btn:hover {
    background-color: #45a049 !important;
    border-color: #45a049 !important;
}


/* NEW: Style para sa Action buttons sa Table (Solid buttons na may icon) */
.client-actions, .action-buttons {
    display: flex;
    gap: 5px; 
    justify-content: center; /* I-center ang buttons sa cell */
}
/* Unified style para sa buttons sa action column */
.client-actions button, .action-buttons button {
    /* Base style para maging katulad ng .btn--primary, pero maliit */
    font-size: 0.9em; 
    padding: 6px 6px; /* Para maging parang square (adjust as needed) */
    width: 32px; /* Fixed width */
    height: 32px; /* Fixed height */
    border-radius: 5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center; /* I-center ang icon */
    color: white !important; 
    border: 1px solid transparent;
}
.client-actions button i, .action-buttons button i {
    font-size: 1.1em; 
}

/* Edit Button (Teal/Cyan) - NEW COLOR */
.client-actions button[title="Edit Client"] {
    background-color: #17A2B8; /* Teal/Cyan */
    border-color: #17A2B8;
}
.client-actions button[title="Edit Client"]:hover {
    background-color: #138496; /* Darker Teal/Cyan */
    border-color: #138496;
}

/* Archive Button (Orange) */
.client-actions button[title="Archive Client"] {
    background-color: #FF9800; /* Orange */
    border-color: #FF9800;
}
.client-actions button[title="Archive Client"]:hover {
    background-color: #e68900;
    border-color: #e68900;
}

/* Restore Button (Green) - Para sa Archived List */
/* Gagamitin ang universal icon-only style, pero may sariling color */
.action-buttons button[title="Restore Client"] {
    background-color: #4CAF50 !important;
    border-color: #4CAF50 !important;
}
.action-buttons button[title="Restore Client"]:hover {
    background-color: #45a049 !important;
    border-color: #45a049 !important;
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
      <a href="admin_dashboard.php" class="menu__link">
        <i class='bx bx-home-alt-2'></i> Dashboard
      </a>
    </li>

    <li class="menu__item has-dropdown">
      <a href="#" class="menu__link">
        <i class='bx bx-user-circle'></i> User Management
        <i class='bx bx-chevron-down arrow-icon'></i>
      </a>
      <ul class="dropdown__menu">
        <li class="menu__item"><a href="UM_clients.php" class="menu__link active">Clients</a></li>
        <li class="menu__item"><a href="UM_employees.php" class="menu__link">Employees</a></li>
        <li class="menu__item"><a href="UM_admins.php" class="menu__link">Admins</a></li>
      </ul>
    </li>

    <li class="menu__item has-dropdown">
      <a href="#" class="menu__link">
        <i class='bx bx-calendar-check'></i> Appointment Management
        <i class='bx bx-chevron-down arrow-icon'></i>
      </a>
      <ul class="dropdown__menu">
        <li class="menu__item"><a href="AP_one-time.php" class="menu__link">One-time Service</a></li>
        <li class="menu__item"><a href="AP_recurring.php" class="menu__link">Recurring Service</a></li>
      </ul>
    </li>

    <li class="menu__item">
      <a href="ES.php" class="menu__link"><i class='bx bx-time'></i> Employee Scheduling</a>
    </li>

    <li class="menu__item">
      <a href="FR.php" class="menu__link"><i class='bx bx-star'></i> Feedback & Ratings</a>
    </li>

    <li class="menu__item">
      <a href="Reports.php" class="menu__link"><i class='bx bx-chart'></i> Reports</a>
    </li>

    <li class="menu__item">
      <a href="admin_profile.php" class="menu__link"><i class='bx bx-user'></i> My Profile</a>
    </li>

    <li class="menu__item">
      <a href="#" id="logoutLink" class="menu__link"><i class='bx bx-log-out'></i> Logout</a>
    </li>

  </ul>
</aside>
</div>
<main class="dashboard__content">
<section id="UM-clients-content" class="content__section">
    <h2 class="section__title">User Management - Clients</h2>
    <p class="welcome__message">View and manage client accounts.</p>
    
    <div class="client-management-header">
        <div class="summary-cards-container">
            
            <div class="summary-card client-management-summary-card total-registered">
                <div class="card-content">
                    <h2 class="stat-value"><?php echo number_format($total_registered_clients); ?></h2> 
                    <p class="stat-title">Total Registered Clients</p>
                </div>
                <i class='bx bx-user-plus card-icon'></i>
            </div>
            
            <div class="summary-card client-management-summary-card active-clients">
                <div class="card-content">
                    <h2 class="stat-value"><?php echo number_format($active_clients_count); ?></h2> 
                    <p class="stat-title">Active Clients</p>
                </div>
                <i class='bx bx-user-check card-icon'></i>
            </div>
            
            <div class="summary-card client-management-summary-card new-clients">
                <div class="card-content">
                    <h2 class="stat-value"><?php echo number_format($new_clients_this_month); ?></h2> 
                    <p class="stat-title">New Clients (<?php echo $current_month; ?>)</p> 
                </div>
                <i class='bx bx-user-voice card-icon'></i>
            </div>
            
            <div class="summary-card client-management-summary-card open-concerns">
                <div class="card-content">
                    <h2 class="stat-value"><?php echo number_format($clients_with_open_concern); ?></h2> 
                    <p class="stat-title">Clients with Open Concern</p>
                </div>
                <i class='bx bx-chat card-icon'></i>
            </div>
        </div>
    </div>
    
    <?php 
    // Get the current tab from GET parameter, default to 'active-clients'
    $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'active-clients'; 
    ?>
    <nav class="client-tabs">
        <button class="tab-button <?php echo ($current_tab == 'active-clients' ? 'active' : ''); ?>" data-tab="active-clients" onclick="showClientTab('active-clients')">
            <i class='bx bx-list-ul' style="font-size: 1.1em;"></i> Active Client List
        </button>
        <button class="tab-button <?php echo ($current_tab == 'archived-clients' ? 'active' : ''); ?>" data-tab="archived-clients" onclick="showClientTab('archived-clients')">
            <i class='bx bx-archive-in' style="font-size: 1.1em;"></i> Archived Accounts
        </button>
    </nav>

    <div class="tab-content-wrapper">

        <div id="active-clients" class="tab-pane <?php echo ($current_tab == 'active-clients' ? 'active' : ''); ?>">
            
            <div class="dashboard__container client-management-data-container upcoming-container">
                <div class="container-title" style="justify-content: flex-start;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class='bx bx-table'></i> Active Client Data
                    </div>
                </div>
                
                <div class="client-filter-controls">
                    <div class="filter-input-wrapper" style="flex: 1 1 300px;"> 
                        <i class='bx bx-search'></i> 
                        <input type="text" id="clientSearch" placeholder="Search by name, ID, or email..." class="filter-input">
                    </div>
                    
                    <div style="display: flex; gap: 20px; flex: 1 1 300px; max-width: 600px;">
                        
                        <div class="filter-select-wrapper" style="flex: 1;">
                            <i class='bx bx-check-shield'></i> 
                            <select id="clientStatusFilter" class="filter-select">
                                <option value="">Filter by Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="filter-select-wrapper" style="flex: 1;">
                            <i class='bx bx-calendar-check'></i> 
                            <select id="clientBookingFilter" class="filter-select">
                                <option value="">Filter by Bookings</option>
                                <option value=">0">Has Bookings</option>
                                <option value="0">No Bookings</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="client-table-container">
                    <table class="client-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>First name</th>
                                <th>Last name</th>
                                <th>Birthdate</th>
                                <th>Email</th>
                                <th>Contact #</th>
                                <th class="bookings-column">No. of bookings</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($client_list_data as $client): ?>
                            <tr>
                                <td><?php echo $client['id']; ?></td>
                                <td><?php echo htmlspecialchars($client['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($client['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($client['birthdate']); ?></td>
                                <td><?php echo htmlspecialchars($client['email']); ?></td>
                                <td><?php echo htmlspecialchars($client['contact']); ?></td>
                                <td class="bookings-column"><?php echo number_format($client['bookings']); ?></td>
                                <td><?php echo render_status_badge($client['status']); ?></td>
                                <td>
                                    <div class="client-actions">
                                        <button title="Edit Client" 
                                            onclick="openEditClientModal(this)" 
                                            data-client-id="<?php echo $client['id']; ?>"
                                            data-first-name="<?php echo htmlspecialchars($client['first_name']); ?>"
                                            data-last-name="<?php echo htmlspecialchars($client['last_name']); ?>"
                                            data-email="<?php echo htmlspecialchars($client['email']); ?>"
                                            data-contact="<?php echo htmlspecialchars($client['contact']); ?>"
                                            data-birthdate="<?php echo htmlspecialchars($client['birthdate']); ?>">
                                            <i class='bx bx-edit-alt'></i>
                                        </button>
                                        <button title="Archive Client" onclick="archiveClient(<?php echo $client['id']; ?>)">
                                            <i class='bx bx-archive-in'></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr id="noResultsRow" style="display: none;">
                                <td colspan="9" style="text-align: center;">
                                    No results found matching "<strong><span id="searchTermDisplay"></span></strong>"
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div> <div id="archived-clients" class="tab-pane <?php echo ($current_tab == 'archived-clients' ? 'active' : ''); ?>">
            <div class="dashboard__container client-management-data-container archived-container">
                <div class="container-title" style="justify-content: flex-start;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class='bx bx-archive'></i> Archived Client Accounts
                    </div>
                </div>
                
                <div class="client-filter-controls" style="justify-content: flex-start;">
                    <div class="filter-input-wrapper" style="flex: 0 1 400px; max-width: 100%;">
                        <i class='bx bx-search'></i> 
                        <input type="text" id="archivedClientSearch" placeholder="Search archived clients by name, ID, or email..." class="filter-input">
                    </div>
                </div>
                
                <div class="client-table-container">
                    <table class="archived-client-table">
                        <thead>
                            <tr>
                                <th>ID</th> <th>First name</th>
                                <th>Last name</th>
                                <th>Birthdate</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th class="action-cell">Action</th> 
                            </tr>
                        </thead>
                        <tbody id="archivedTableBody">
                            <?php foreach ($archived_clients_data as $archived_client): ?>
                            <tr data-client-id="<?php echo $archived_client['id']; ?>">
                                <td><?php echo htmlspecialchars($archived_client['id']); ?></td> <td><?php echo htmlspecialchars($archived_client['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($archived_client['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($archived_client['birthdate']); ?></td>
                                <td><?php echo htmlspecialchars($archived_client['contact']); ?></td>
                                <td><?php echo htmlspecialchars($archived_client['email']); ?></td>
                                <td class="action-cell">
                                    <div class="action-buttons">
                                        <button title="Restore Client" onclick="restoreClient(<?php echo $archived_client['id']; ?>)">
                                            <i class='bx bx-revision'></i>
                                        </button>
                                        
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr id="noArchivedResultsRow" style="display: none; background-color: #f8f9fa;">
                                <td colspan="7" style="text-align: center;">
                                    No archived results found matching "<strong><span id="archivedSearchTermDisplay"></span></strong>"
                                </td>
                            </tr>
                            <?php if (empty($archived_clients_data)): ?>
                            <tr class="default-empty-row">
                                <td colspan="7" style="text-align: center; color: #6c757d; font-style: italic;">
                                    No clients currently in the archive.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div> </div> </section>
<section id="manage-employees" class="content__section <?php echo ($active_section == 'manage-employees' ? 'active' : ''); ?>">
    <h2 class="section__title">User Management - Employees</h2>
    <p>Manage all employee accounts here.</p>
</section>
<section id="manage-admins" class="content__section <?php echo ($active_section == 'manage-admins' ? 'active' : ''); ?>">
    <h2 class="section__title">User Management - Admins</h2>
    <p>Manage all administrator accounts here.</p>
</section>
<section id="appointments-one-time" class="content__section <?php echo ($active_section == 'appointments-one-time' ? 'active' : ''); ?>">
    <h2 class="section__title">Appointment Management - One-time Service</h2>
    <p>Manage one-time service appointments.</p>
</p>
</section>
<section id="appointments-recurring" class="content__section <?php echo ($active_section == 'appointments-recurring' ? 'active' : ''); ?>">
    <h2 class="section__title">Appointment Management - Recurring Service</h2>
    <p>Manage recurring service contracts and appointments.</p>
</section>
<section id="employee-scheduling" class="content__section <?php echo ($active_section == 'employee-scheduling' ? 'active' : ''); ?>">
    <h2 class="section__title">Employee Scheduling</h2>
    <p>View and manage staff shifts and service assignments.</p>
</section>
<section id="feedback-ratings" class="content__section <?php echo ($active_section == 'feedback-ratings' ? 'active' : ''); ?>">
    <h2 class="section__title">Feedback & Ratings</h2>
    <p>Review customer ratings and feedback for services and employees.</p>
</section>
<section id="reports" class="content__section <?php echo ($active_section == 'reports' ? 'active' : ''); ?>">
    <h2 class="section__title">Reports</h2>
    <p>Access sales, payroll, and performance analytics reports.</p>
</section>

<section id="profile" class="content__section <?php echo ($active_section == 'profile' ? 'active' : ''); ?>">
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
<p>Your profile has been updated successfully.</p>
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

<div id="editClientModal" class="modal">
    <div class="modal__content" style="max-width: 900px; width: 95%; text-align: left;">
        <h3 class="modal__title" style="display: flex; justify-content: space-between; align-items: center;">
            Edit Client Information
            <button class="close-modal-btn" data-modal="editClientModal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #555;">&times;</button>
        </h3>
        
        <form id="editClientForm">
            <input type="hidden" id="editClientId" name="clientId">
            
            <div class="form-row two-column" style="display: flex; gap: 20px; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label for="editFirstName" class="required-field">First Name</label>
                    <input type="text" id="editFirstName" name="firstName" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="editLastName" class="required-field">Last Name</label>
                    <input type="text" id="editLastName" name="lastName" required>
                </div>
            </div>
            
            <div class="form-row two-column" style="display: flex; gap: 20px; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label for="editEmail" class="required-field">Email</label>
                    <input type="email" id="editEmail" name="email" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="editBirthdate" class="required-field">Birthdate</label>
                    <input type="date" id="editBirthdate" name="birthdate" required>
                </div>
            </div>
            
            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 25px; max-width: 50%;">
                <div class="form-group" style="flex: 1;">
                    <label for="editContact" class="required-field">Contact Number</label>
                    <input type="text" id="editContact" name="contact" required 
                           maxlength="13" 
                           data-prefix="+971"
                           pattern="^\+971[0-9]{9}$" 
                           title="Contact number must be +971 followed by 9 digits (e.g., +971501234567).">
                </div>
            </div>

            <div class="form-row" style="margin-top: 20px; text-align: right;">
                <button type="submit" class="btn btn--primary" style="background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; transition: background-color 0.3s;">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<div id="clientSaveSuccessModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Changes Saved</h3>
        <p>Client details have been updated successfully.</p>
        <div class="modal__actions">
            <button class="btn btn--primary" id="confirmClientSave">OK</button>
        </div>
    </div>
</div>

<div id="archiveConfirmModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Confirm Client Archival</h3>
        <p>Are you sure you want to archive Client ID: <strong id="archiveClientIdDisplay"></strong>? They will be moved to the Archives and marked as inactive.</p>
        <div class="modal__actions">
            <button id="cancelArchive" class="btn btn--secondary">Cancel</button>
            <button id="confirmArchive" class="btn btn--primary archive-btn">Archive</button>
        </div>
    </div>
</div>

<div id="archiveSuccessModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Client Archived!</h3>
        <p>Client ID <strong id="archiveSuccessIdDisplay"></strong> has been successfully moved to the archives.</p>
        <div class="modal__actions">
            <button class="btn btn--primary" id="confirmArchiveSuccess">OK</button>
        </div>
    </div>
</div>

<div id="restoreConfirmModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Confirm Client Restoration</h3>
        <p>Are you sure you want to restore Client ID: <strong id="restoreClientIdDisplay"></strong>? They will be returned to the active Client List.</p>
        <div class="modal__actions">
            <button id="cancelRestore" class="btn btn--secondary">Cancel</button>
            <button id="confirmRestore" class="btn btn--primary restore-btn">Restore</button> </div>
    </div>
</div>

<div id="restoreSuccessModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Client Restored!</h3>
        <p>Client ID <strong id="restoreSuccessIdDisplay"></strong> has been successfully restored to the active list.</p>
        <div class="modal__actions">
            <button class="btn btn--primary restore-btn" id="confirmRestoreSuccess">OK</button>
        </div>
    </div>
</div>

<script>
    // Assuming this function is called when a tab button is clicked
    function showClientTab(tabId) {
        // 1. I-hide ang lahat ng tab content
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });
        
        // 2. I-show ang tamang tab content
        const targetPane = document.getElementById(tabId);
        if (targetPane) {
            targetPane.classList.add('active');
        }
        
        // 3. I-update ang active state ng buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
            if (button.getAttribute('data-tab') === tabId) {
                button.classList.add('active');
            }
        });
        
        // Optional: I-update ang URL hash (hindi GET parameter) para ma-retain sa refresh kung gusto
        // window.history.pushState(null, null, '?content=manage-clients&tab=' + tabId); 
    }
    
    // Default call on load to ensure the correct tab is visible
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        let initialTab = urlParams.get('tab') || 'active-clients';
        
        // If 'manage-clients' section is the active_section, ensure the initial tab state is set
        if (document.getElementById('manage-clients').classList.contains('active')) {
             // Find the button that matches the initialTab (or default) and click it
            const initialButton = document.querySelector(`.tab-button[data-tab="${initialTab}"]`);
            if (initialButton) {
                initialButton.click();
            } else {
                // Default to 'active-clients' if somehow the URL tab is invalid
                showClientTab('active-clients');
            }
        }
    });
    
    // =========================================================================
    // JS FOR ARCHIVED CLIENT SEARCH
    // =========================================================================
    document.addEventListener('DOMContentLoaded', () => {
        const archivedSearchInput = document.getElementById('archivedClientSearch');
        const archivedTableBody = document.getElementById('archivedTableBody');
        const noArchivedResultsRow = document.getElementById('noArchivedResultsRow');
        const archivedSearchTermDisplay = document.getElementById('archivedSearchTermDisplay');
        const defaultEmptyRow = archivedTableBody ? archivedTableBody.querySelector('.default-empty-row') : null;

        if (archivedSearchInput && archivedTableBody) {
            archivedSearchInput.addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase().trim();
                let resultsFound = false;
                
                // I-hide ang default empty row (if it exists)
                if (defaultEmptyRow) {
                    defaultEmptyRow.style.display = 'none';
                }

                archivedTableBody.querySelectorAll('tr').forEach(row => {
                    // Huwag isama ang no results row sa iteration
                    if (row.id === 'noArchivedResultsRow') {
                        return;
                    }

                    // Kuhanin ang text content ng lahat ng cells maliban sa Action column
                    const cells = row.querySelectorAll('td');
                    let rowText = '';
                    cells.forEach((cell, index) => {
                        // Huwag isama ang huling column (Action)
                        if (index < cells.length - 1) {
                            rowText += cell.textContent.toLowerCase() + ' ';
                        }
                    });

                    if (rowText.includes(searchValue)) {
                        row.style.display = ''; // Ipakita ang row
                        resultsFound = true;
                    } else {
                        row.style.display = 'none'; // I-hide ang row
                    }
                });

                // Ipakita/I-hide ang "No results found" row
                if (resultsFound || searchValue === '') {
                    noArchivedResultsRow.style.display = 'none';
                    // Ipakita ulit ang default empty row kung walang search value at walang laman ang listahan
                    if (searchValue === '' && !archivedTableBody.querySelector('tr:not(#noArchivedResultsRow):not(.default-empty-row)')) {
                         if (defaultEmptyRow) {
                            defaultEmptyRow.style.display = '';
                        }
                    }
                } else {
                    archivedSearchTermDisplay.textContent = searchValue;
                    noArchivedResultsRow.style.display = '';
                }
            });
        }
    });
    // =========================================================================
    
    // NOTE: Ang JS functions para sa active client search (clientSearch, clientStatusFilter, etc.) 
    // at ang mga modal functions (openEditClientModal, archiveClient, restoreClient) ay dapat 
    // nakalagay sa inyong 'UM_function.js' file. Ang logic para sa archived search ay 
    // idinagdag ko na lang dito para makita agad na gumagana.
</script>


<script src="client_db.js"></script>
<script src="UM_function.js"></script>
</script>
</body>
</html>