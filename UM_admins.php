<?php
$total_admin_users = 2; 

// BINAGO: Final Sample Data for Admin List (Administrator at Admin Assistant lang)
// INALIS: Si ID 2 na si Khalid Al-Mansoori
$admin_list_data = [
    // ID 1 ay may role na 'Administrator'
    ['id' => 1, 'first_name' => 'Danelle', 'last_name' => 'Beltran', 'email' => 'danellemarie6@gmail.com', 'contact' => '+971501234567', 'role' => 'Administrator'], 
    ['id' => 3, 'first_name' => 'Sarah', 'last_name' => 'Hassan', 'email' => 'sarah@alazima.com', 'contact' => '+971501122334', 'role' => 'Admin Assistant'],
];

// ***************************************************************
// TAMA NA ANG DATA: Sample Data for Archived Admins (Admin Assistant lang ang pwedeng ma-archive)
// ***************************************************************
// HINDI PALA! Dapat Administrator ang in-archive para masubok ang Restore/Delete ng Admin role.
// I-assume natin na si Aisha na A. Assistant ang na-archive
$archived_admins_data = [
    // Si Aisha (Admin Assistant) lang ang na-archive, kasi siya lang ang kailangang ma-archive
    ['id' => 5, 'first_name' => 'Aisha', 'last_name' => 'Khan', 'email' => 'aisha@alazima.com', 'contact' => '+971554443332', 'role' => 'Admin Assistant'],
];

// Get the current tab for Admins, default to 'active-admins'
$current_tab_admins = isset($_GET['admintab']) ? $_GET['admintab'] : 'active-admins';
// NOTE: Ito ay kailangan nasa PHP block, pero dahil hindi kasama ang buong file, inilagay ko ito sa taas.

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
.client-table th, .archived-client-table th, .admin-table th, .archived-admin-table th { /* DINAGDAG: .archived-admin-table th */
    text-transform: none; /* ITO ANG FIX para maging Title Case */
    font-size: 0.95em; 
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
.client-actions, .action-buttons, .admin-actions {
    display: flex;
    gap: 5px; 
    justify-content: center; /* I-center ang buttons sa cell */
}
/* Unified style para sa buttons sa action column */
.client-actions button, .action-buttons button, .admin-actions button {
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
.client-actions button i, .action-buttons button i, .admin-actions button i {
    font-size: 1.1em; 
}

/* Edit Button (Teal/Cyan) - NEW COLOR */
.client-actions button[title="Edit Client"], .admin-actions button[title="Edit Admin"] {
    background-color: #17A2B8; /* Teal/Cyan */
    border-color: #17A2B8;
}
.client-actions button[title="Edit Client"]:hover, .admin-actions button[title="Edit Admin"]:hover {
    background-color: #138496; /* Darker Teal/Cyan */
    border-color: #138496;
}

/* Archive Button (Orange) */
.client-actions button[title="Archive Client"], .admin-actions button[title="Archive Admin"] { /* DINAGDAG: .admin-actions button[title="Archive Admin"] */
    background-color: #FF9800; /* Orange */
    border-color: #FF9800;
}
.client-actions button[title="Archive Client"]:hover, .admin-actions button[title="Archive Admin"]:hover { /* DINAGDAG: .admin-actions button[title="Archive Admin"]:hover */
    background-color: #e68900;
    border-color: #e68900;
}

/* Restore Button (Green) - Para sa Archived List */
/* Gagamitin ang universal icon-only style, pero may sariling color */
.action-buttons button[title="Restore Client"], .action-buttons button[title="Restore Admin"] { /* DINAGDAG: .action-buttons button[title="Restore Admin"] */
    background-color: #4CAF50 !important;
    border-color: #4CAF50 !important;
}
.action-buttons button[title="Restore Client"]:hover, .action-buttons button[title="Restore Admin"]:hover { /* DINAGDAG: .action-buttons button[title="Restore Admin"]:hover */
    background-color: #45a049 !important;
    border-color: #45a049 !important;
}

/* NEW: Delete Admin Button (Red) */
/* BINAGO: Inalis ang [title="Delete Admin"] dahil gagamitin na lang natin ang [title="Remove Admin"] */
.admin-actions button[title="Remove Admin"] { 
    background-color: #dc3545; /* Red */
    border-color: #dc3545;
}
.admin-actions button[title="Remove Admin"]:hover {
    background-color: #c82333; /* Darker Red */
    border-color: #c82333;
}

/* NEW: Style para i-disable ang button (Para sa Admin Assistant Role) */
.admin-actions button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none; /* Tiyakin na hindi talaga ma-click */
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
        <li class="menu__item"><a href="admin_dashboard.php?content=dashboard" class="menu__link" data-content="dashboard"><i class='bx bx-home-alt-2'></i> Dashboard</a></li>

        <li class="menu__item has-dropdown">
            <a href="#" class="menu__link" data-content="book-appointment-parent"><i class='bx bx-calendar'></i> User Management <i class='bx bx-chevron-down arrow-icon'></i></a>
        <ul class="dropdown__menu">
            <li class="menu__item"><a href="UM_clients.php" class="menu__link">Clients</a></li>
            <li class="menu__item"><a href="UM_employees.php" class="menu__link">Employees</a></li>
            <li class="menu__item"><a href="UM_admins.php" class="menu__link active">Admins</a></li>
        </ul>
    </li>

<li class="menu__item has-dropdown">
    <a href="#" class="menu__link <?php echo (in_array($active_section, ['appointments-one-time', 'appointments-recurring']) ? 'active' : ''); ?>" data-content="appointment-management-parent">
        <i class='bx bx-calendar-edit'></i> Appointment Management <i class='bx bx-chevron-down arrow-icon'></i>
    </a>
    <ul class="dropdown__menu">
        <li class="menu__item"><a href="?content=appointments-one-time" class="menu__link <?php echo ($active_section == 'appointments-one-time' ? 'active' : ''); ?>" data-content="appointments-one-time">One-time Service</a></li>
        <li class="menu__item"><a href="?content=appointments-recurring" class="menu__link <?php echo ($active_section == 'appointments-recurring' ? 'active' : ''); ?>" data-content="appointments-recurring">Recurring Service</a></li>
    </ul>
</li>

<li class="menu__item">
    <a href="?content=employee-scheduling" class="menu__link <?php echo ($active_section == 'employee-scheduling' ? 'active' : ''); ?>" data-content="employee-scheduling">
        <i class='bx bx-time-five'></i> Employee Scheduling
    </a>
</li>

<li class="menu__item">
    <a href="?content=feedback-ratings" class="menu__link <?php echo ($active_section == 'feedback-ratings' ? 'active' : ''); ?>" data-content="feedback-ratings">
        <i class='bx bx-star'></i> Feedback & Ratings
    </a>
</li>

<li class="menu__item">
    <a href="?content=reports" class="menu__link <?php echo ($active_section == 'reports' ? 'active' : ''); ?>" data-content="reports">
        <i class='bx bx-line-chart'></i> Reports
    </a>
</li>

<li class="menu__item">
    <a href="?content=profile" class="menu__link <?php echo ($active_section == 'profile' ? 'active' : ''); ?>" data-content="profile">
        <i class='bx bx-user'></i> Profile
    </a>
</li>

<li class="menu__item"><a href="?content=logout" class="menu__link" data-content="logout"><i class='bx bx-log-out'></i> Logout</a></li>
</ul>
</aside>
<main class="dashboard__content">
<section id="dashboard" class="content__section <?php echo ($active_section == 'dashboard' ? 'active' : ''); ?>">
</section>


<section id="manage-employees" class="content__section <?php echo ($active_section == 'manage-employees' ? 'active' : ''); ?>">
    <h2 class="section__title">User Management - Employees</h2>
    <p>Manage all employee accounts here.</p>
</section>

<section id="manage-admins" class="content__section <?php echo ($active_section == 'manage-admins' ? 'active' : ''); ?>">
    <h2 class="section__title">User Management - Admins</h2>
    <p class="welcome__message">View and manage administrator accounts.</p>
    
    <div class="client-management-header"> 
        <div class="summary-cards-container">
            
            <div class="summary-card client-management-summary-card total-registered" style="background-color: #6c757d;">
                <div class="card-content">
                    <h2 class="stat-value"><?php echo number_format($total_admin_users); ?></h2> 
                    <p class="stat-title">Total Admin Users</p>
                </div>
                <i class='bx bx-shield-alt-2 card-icon'></i>
            </div>
            
        </div>
        
        <div style="width: 100%; display: flex; justify-content: flex-end; padding-right: 15px; margin-bottom: 20px;">
             <button class="btn btn--primary" id="addNewAdminBtn" onclick="openAddAdminModal()">
                <i class='bx bx-plus'></i> Add New Admin
            </button>
        </div>
    </div>
    
    <?php 
    // Get the current tab for Admins, default to 'active-admins'
    $current_tab_admins = isset($_GET['admintab']) ? $_GET['admintab'] : 'active-admins'; 
    ?>
    <nav class="client-tabs">
        <button class="tab-button <?php echo ($current_tab_admins == 'active-admins' ? 'active' : ''); ?>" data-tab="active-admins" onclick="showAdminTab('active-admins')">
            <i class='bx bx-list-ul' style="font-size: 1.1em;"></i> Active Admin List
        </button>
        <button class="tab-button <?php echo ($current_tab_admins == 'archived-admins' ? 'active' : ''); ?>" data-tab="archived-admins" onclick="showAdminTab('archived-admins')">
            <i class='bx bx-archive-in' style="font-size: 1.1em;"></i> Archived Admins
        </button>
    </nav>

    <div class="tab-content-wrapper">
        <div id="active-admins" class="tab-pane <?php echo ($current_tab_admins == 'active-admins' ? 'active' : ''); ?>">
            
            <div class="dashboard__container client-management-data-container admin-container">
                <div class="container-title" style="justify-content: flex-start;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class='bx bx-table'></i> Administrator List
                    </div>
                </div>
                
                <div class="client-filter-controls" style="justify-content: flex-start;">
                    <div class="filter-input-wrapper" style="flex: 0 1 400px; max-width: 100%;">
                        <i class='bx bx-search'></i> 
                        <input type="text" id="adminSearch" placeholder="Search by name, ID, or email..." class="filter-input">
                    </div>
                </div>

                <div class="client-table-container">
                    <table class="admin-table client-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">ID</th>
                                <th style="width: 15%;">First name</th>
                                <th style="width: 15%;">Last name</th>
                                <th style="width: 25%;">Email</th>
                                <th style="width: 15%;">Contact #</th>
                                <th style="width: 15%;">Role</th>
                                <th style="width: 10%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="adminTableBody">
                            <?php foreach ($admin_list_data as $admin): ?>
                            <tr data-admin-id="<?php echo $admin['id']; ?>">
                                <td><?php echo $admin['id']; ?></td>
                                <td><?php echo htmlspecialchars($admin['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($admin['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><?php echo htmlspecialchars($admin['contact']); ?></td>
                                <td><?php echo htmlspecialchars($admin['role']); ?></td>
                                <td>
                                    <div class="admin-actions client-actions">
                                        
                                        <button title="Edit Admin" 
                                            onclick="openEditAdminModal(this)" 
                                            data-admin-id="<?php echo $admin['id']; ?>">
                                            <i class='bx bx-edit-alt'></i>
                                        </button>
                                        
                                        <?php if ($admin['role'] == 'Administrator'): ?>
                                            
                                            <button title="Archive Admin" onclick="archiveAdmin(<?php echo $admin['id']; ?>)">
                                                <i class='bx bx-archive-in'></i>
                                            </button>
                                            
                                            <button title="Remove Admin" onclick="deleteAdmin(<?php echo $admin['id']; ?>)">
                                                <i class='bx bx-trash'></i>
                                            </button>

                                        <?php else: ?>
                                            
                                            <button title="Archive Admin" onclick="archiveAdmin(<?php echo $admin['id']; ?>)">
                                                <i class='bx bx-archive-in'></i>
                                            </button>
                                            
                                            <button title="Remove Admin" class="disabled" disabled>
                                                <i class='bx bx-trash'></i>
                                            </button>

                                        <?php endif; ?>
                                        
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr id="noAdminResultsRow" style="display: none;">
                                <td colspan="7" style="text-align: center;">
                                    No administrator found matching "<strong><span id="adminSearchTermDisplay"></span></strong>"
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div> 
        
        <div id="archived-admins" class="tab-pane <?php echo ($current_tab_admins == 'archived-admins' ? 'active' : ''); ?>">
            
            <div class="dashboard__container client-management-data-container archived-admin-container">
                <div class="container-title" style="justify-content: flex-start;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class='bx bx-archive-in'></i> Archived Administrator List
                    </div>
                </div>
                
                <div class="client-filter-controls" style="justify-content: flex-start;">
                    <div class="filter-input-wrapper" style="flex: 0 1 400px; max-width: 100%;">
                        <i class='bx bx-search'></i> 
                        <input type="text" id="archivedAdminSearch" placeholder="Search archived admins by name, ID, or email..." class="filter-input">
                    </div>
                </div>

                <div class="client-table-container">
                    <table class="archived-admin-table client-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">ID</th>
                                <th style="width: 15%;">First name</th>
                                <th style="width: 15%;">Last name</th>
                                <th style="width: 25%;">Email</th>
                                <th style="width: 15%;">Contact #</th>
                                <th style="width: 15%;">Role</th>
                                <th style="width: 10%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="archivedAdminTableBody">
                            <?php if (empty($archived_admins_data)): ?>
                                <tr class="default-empty-row">
                                    <td colspan="7" style="text-align: center; color: #6c757d; font-style: italic;">
                                        No archived administrators found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($archived_admins_data as $admin): ?>
                                <tr data-admin-id="<?php echo $admin['id']; ?>">
                                    <td><?php echo $admin['id']; ?></td>
                                    <td><?php echo htmlspecialchars($admin['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['contact']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['role']); ?></td>
                                    <td>
                                        <div class="action-buttons client-actions">
                                            
                                            <button title="Restore Admin" onclick="restoreAdmin(<?php echo $admin['id']; ?>)">
                                                <i class='bx bx-archive-out'></i>
                                            </button>
                                            
                                            <button title="Remove Admin" onclick="deleteAdmin(<?php echo $admin['id']; ?>)" style="background-color: #dc3545; border-color: #dc3545;">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr id="noArchivedAdminResultsRow" style="display: none;">
                                <td colspan="7" style="text-align: center;">
                                    No archived administrator found matching "<strong><span id="archivedAdminSearchTermDisplay"></span></strong>"
                                </td>
                            </tr>
                        </tbody>
                    </table>
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

<div id="addAdminModal" class="modal">
    <div class="modal__content" style="max-width: 900px; width: 95%; text-align: left;">
        <h3 class="modal__title" style="display: flex; justify-content: space-between; align-items: center;">
            Add New Administrator/Staff
            <button class="close-modal-btn" data-modal="addAdminModal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #555;">&times;</button>
        </h3>
        
        <form id="addAdminForm">
            <fieldset style="border: 1px solid #ccc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <legend style="font-weight: bold; padding: 0 10px; color: #007bff;">Personal Details</legend>
                
                <div class="form-row two-column" style="display: flex; gap: 20px; margin-bottom: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="addFirstName" class="required-field">First Name</label>
                        <input type="text" id="addFirstName" name="firstName" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="addLastName" class="required-field">Last Name</label>
                        <input type="text" id="addLastName" name="lastName" required>
                    </div>
                </div>
                
                <div class="form-row two-column" style="display: flex; gap: 20px; margin-bottom: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="addEmail" class="required-field">Email</label>
                        <input type="email" id="addEmail" name="email" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="addContact" class="required-field">Contact Number</label>
                        <input type="text" id="addContact" name="contact" required 
                               maxlength="13" 
                               data-prefix="+971"
                               pattern="^\+971[0-9]{9}$" 
                               title="Contact number must be +971 followed by 9 digits (e.g., +971501234567).">
                    </div>
                </div>
            </fieldset>

            <fieldset style="border: 1px solid #ccc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <legend style="font-weight: bold; padding: 0 10px; color: #007bff;">Account & Access</legend>
                
                <div class="form-row two-column" style="display: flex; gap: 20px; margin-bottom: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="addRole" class="required-field">Role</label>
                        <select id="addRole" name="role" required>
                            <option value="" selected disabled>- Select Admin Role -</option>
                            <option value="Administrator">Administrator</option>
                            <option value="Admin Assistant">Admin Assistant</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="addPassword" class="required-field">Initial Password</label>
                        <input type="password" id="addPassword" name="password" required>
                    </div>
                </div>
            </fieldset>
            
            <div class="form-row" style="margin-top: 20px; text-align: right;">
                <button type="button" class="btn btn--secondary" onclick="closeModal('addAdminModal')">
                    Cancel
                </button>
                <button type="submit" class="btn btn--primary" style="background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; transition: background-color 0.3s;">
                    <i class='bx bx-user-plus'></i> Create Admin Account
                </button>
            </div>
        </form>
    </div>
</div>

<div id="addAdminSuccessModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Admin Added!</h3>
        <p>A new administrator account has been successfully created.</p>
        <div class="modal__actions">
            <button class="btn btn--primary" id="confirmAddAdminSuccess">OK</button>
        </div>
    </div>
</div>
<div id="archiveAdminConfirmModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Confirm Admin Archival</h3>
        <p>Are you sure you want to archive Admin ID: <strong id="archiveAdminIdDisplay"></strong>? They will be moved to the Admin Archives and lose access to the system.</p>
        <div class="modal__actions">
            <button id="cancelAdminArchive" class="btn btn--secondary">Cancel</button>
            <button id="confirmAdminArchive" class="btn btn--primary archive-btn">Archive Admin</button>
        </div>
    </div>
</div>

<div id="archiveAdminSuccessModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Admin Archived!</h3>
        <p>Admin ID <strong id="archiveAdminSuccessIdDisplay"></strong> has been successfully moved to the archives.</p>
        <div class="modal__actions">
            <button class="btn btn--primary" id="confirmAdminArchiveSuccess">OK</button>
        </div>
    </div>
</div>

<div id="restoreAdminConfirmModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Confirm Admin Restoration</h3>
        <p>Are you sure you want to restore Admin ID: <strong id="restoreAdminIdDisplay"></strong>? They will be returned to the active Admin List.</p>
        <div class="modal__actions">
            <button id="cancelAdminRestore" class="btn btn--secondary">Cancel</button>
            <button id="confirmAdminRestore" class="btn btn--primary restore-btn">Restore Admin</button> </div>
    </div>
</div>

<div id="restoreAdminSuccessModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Admin Restored!</h3>
        <p>Admin ID <strong id="restoreAdminSuccessIdDisplay"></strong> has been successfully restored to the active list.</p>
        <div class="modal__actions">
            <button class="btn btn--primary restore-btn" id="confirmAdminRestoreSuccess">OK</button>
        </div>
    </div>
</div>

<div id="deleteAdminConfirmModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Confirm Admin Removal</h3>
        <p>Are you sure you want to permanently **remove** Admin ID: <strong id="deleteAdminIdDisplay"></strong>? This action cannot be undone.</p>
        <div class="modal__actions">
            <button id="cancelAdminDelete" class="btn btn--secondary">Cancel</button>
            <button id="confirmAdminDelete" class="btn btn--primary" style="background-color: #dc3545; border-color: #dc3545;">Remove Permanently</button>
        </div>
    </div>
</div>

<div id="editAdminModal" class="modal">
    <div class="modal__content" style="max-width: 900px; width: 95%; text-align: left;">
        <h3 class="modal__title" style="display: flex; justify-content: space-between; align-items: center;">
            Edit Administrator Information
            <button class="close-modal-btn" data-modal="editAdminModal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #555;">&times;</button>
        </h3>
        
        <form id="editAdminForm">
            <input type="hidden" id="editAdminId" name="adminId">
            <input type="hidden" id="originalAdminRole" name="originalRole"> <fieldset style="border: 1px solid #ccc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <legend style="font-weight: bold; padding: 0 10px; color: #007bff;">Personal Details</legend>
                
                <div class="form-row two-column" style="display: flex; gap: 20px; margin-bottom: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="editAdminFirstName" class="required-field">First Name</label>
                        <input type="text" id="editAdminFirstName" name="firstName" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="editAdminLastName" class="required-field">Last Name</label>
                        <input type="text" id="editAdminLastName" name="lastName" required>
                    </div>
                </div>
                
                <div class="form-row two-column" style="display: flex; gap: 20px; margin-bottom: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="editAdminEmail" class="required-field">Email</label>
                        <input type="email" id="editAdminEmail" name="email" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="editAdminContact" class="required-field">Contact Number</label>
                        <input type="text" id="editAdminContact" name="contact" required 
                               maxlength="13" 
                               data-prefix="+971"
                               pattern="^\+971[0-9]{9}$" 
                               title="Contact number must be +971 followed by 9 digits (e.g., +971501234567).">
                    </div>
                </div>
            </fieldset>

            <fieldset style="border: 1px solid #ccc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <legend style="font-weight: bold; padding: 0 10px; color: #007bff;">Account & Access</legend>
                
                <div class="form-row two-column" style="display: flex; gap: 20px; margin-bottom: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="editAdminRole" class="required-field">Role</label>
                        <select id="editAdminRole" name="role" required>
                            <option value="" selected disabled>- Select Admin Role -</option>
                            <option value="Administrator">Administrator</option>
                            <option value="Admin Assistant">Admin Assistant</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                         <label for="editAdminPassword" class="required-field">Change Password (Leave blank to keep old password)</label>
                        <input type="password" id="editAdminPassword" name="password" placeholder="New Password">
                    </div>
                </div>
            </fieldset>
            
            <div class="form-row" style="margin-top: 20px; text-align: right;">
                <button type="button" class="btn btn--secondary" onclick="closeModal('editAdminModal')">
                    Cancel
                </button>
                <button type="submit" class="btn btn--primary">
                    <i class='bx bx-save'></i> Update Admin Account
                </button>
            </div>
        </form>
    </div>
</div>

<div id="editAdminSuccessModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Admin Updated!</h3>
        <p>Administrator details have been successfully updated.</p>
        <div class="modal__actions">
            <button class="btn btn--primary" id="confirmEditAdminSuccess">OK</button>
        </div>
    </div>
</div>


<script>

    // *************************************************************
    // BAGONG FUNCTION: showAdminTab (Para sa Admin Management)
    // *************************************************************
    function showAdminTab(tabId) {
        // 1. I-hide ang lahat ng admin tab content
        document.querySelectorAll('#manage-admins .tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });
        
        // 2. I-show ang tamang admin tab content
        const targetPane = document.getElementById(tabId);
        if (targetPane) {
            targetPane.classList.add('active');
        }
        
        // 3. I-update ang active state ng admin buttons
        document.querySelectorAll('#manage-admins .tab-button').forEach(button => {
            button.classList.remove('active');
            if (button.getAttribute('data-tab') === tabId) {
                button.classList.add('active');
            }
        });
        
        // Optional: I-update ang URL hash
        // window.history.pushState(null, null, '?content=manage-admins&admintab=' + tabId); 
    }
    // *************************************************************
    
 
    
    
    // =========================================================================
    // JS FOR ACTIVE ADMIN SEARCH (In-update para hindi na ma-filter ng tabs)
    // =========================================================================
    document.addEventListener('DOMContentLoaded', () => {
        const adminSearchInput = document.getElementById('adminSearch');
        const adminTableBody = document.getElementById('adminTableBody');
        const noAdminResultsRow = document.getElementById('noAdminResultsRow');
        const adminSearchTermDisplay = document.getElementById('adminSearchTermDisplay');

        if (adminSearchInput && adminTableBody) {
            adminSearchInput.addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase().trim();
                let resultsFound = false;

                adminTableBody.querySelectorAll('tr').forEach(row => {
                    // Huwag isama ang no results row sa iteration
                    if (row.id === 'noAdminResultsRow') {
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
                    noAdminResultsRow.style.display = 'none';
                } else {
                    adminSearchTermDisplay.textContent = searchValue;
                    noAdminResultsRow.style.display = '';
                }
            });
        }
    });
    // =========================================================================
    
    // *************************************************************
    // BAGONG JS: JS FOR ARCHIVED ADMIN SEARCH (Katulad ng Archived Client Search)
    // *************************************************************
    document.addEventListener('DOMContentLoaded', () => {
        const archivedAdminSearchInput = document.getElementById('archivedAdminSearch');
        const archivedAdminTableBody = document.getElementById('archivedAdminTableBody');
        const noArchivedAdminResultsRow = document.getElementById('noArchivedAdminResultsRow');
        const archivedAdminSearchTermDisplay = document.getElementById('archivedAdminSearchTermDisplay');
        // Kukunin ang default empty row, kung meron
        const defaultEmptyRow = archivedAdminTableBody ? archivedAdminTableBody.querySelector('.default-empty-row') : null; 

        if (archivedAdminSearchInput && archivedAdminTableBody) {
            archivedAdminSearchInput.addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase().trim();
                let resultsFound = false;
                let rowsCount = 0;

                // I-hide muna ang default empty row bago mag-search
                if (defaultEmptyRow) {
                    defaultEmptyRow.style.display = 'none';
                }

                archivedAdminTableBody.querySelectorAll('tr').forEach(row => {
                    if (row.id === 'noArchivedAdminResultsRow' || row.classList.contains('default-empty-row')) {
                        return;
                    }
                    rowsCount++; // Bilangin lang ang data rows

                    const cells = row.querySelectorAll('td');
                    let rowText = '';
                    cells.forEach((cell, index) => {
                        // Huwag isama ang huling column (Action)
                        if (index < cells.length - 1) {
                            rowText += cell.textContent.toLowerCase() + ' ';
                        }
                    });

                    if (rowText.includes(searchValue)) {
                        row.style.display = '';
                        resultsFound = true;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Logic para sa No Results at Default Empty Row
                if (resultsFound) {
                    noArchivedAdminResultsRow.style.display = 'none';
                } else if (searchValue !== '') {
                    // Walang nakita sa search
                    archivedAdminSearchTermDisplay.textContent = searchValue;
                    noArchivedAdminResultsRow.style.display = '';
                } else {
                    // Walang search term at walang results found, ipakita ang default empty row kung walang data
                    noArchivedAdminResultsRow.style.display = 'none';
                    if (rowsCount === 0 && defaultEmptyRow) {
                        defaultEmptyRow.style.display = '';
                    }
                }

                // Tiyakin na lumalabas ang data rows kapag inalis ang search term
                if (searchValue === '') {
                     archivedAdminTableBody.querySelectorAll('tr').forEach(row => {
                        if (!row.id.includes('ResultsRow')) {
                            row.style.display = '';
                        }
                    });
                    // I-hide ang default-empty-row kung may laman (rowsCount > 0), at ipakita kung wala (rowsCount === 0)
                    if (defaultEmptyRow) {
                         defaultEmptyRow.style.display = (rowsCount === 0 ? '' : 'none');
                    }
                }
            });
        }
    });
    // *************************************************************
    
    // *************************************************************
    // BAGONG JS: Placeholder for deleteAdmin function (Para sa Remove Button)
    // *************************************************************
    let adminIdToDelete = null;

    function deleteAdmin(adminId) {
        adminIdToDelete = adminId;
        const modal = document.getElementById('deleteAdminConfirmModal');
        document.getElementById('deleteAdminIdDisplay').textContent = adminId;
        modal.style.display = 'block';
    }

    document.getElementById('confirmAdminDelete').addEventListener('click', () => {
        // Dito ilalagay ang actual logic/AJAX call para i-delete ang adminIdToDelete
        alert('Admin ID ' + adminIdToDelete + ' has been permanently removed (Placeholder Action).');
        
        // Halimbawa: I-reload ang page, o i-remove ang row mula sa DOM
        const rowToRemoveActive = document.querySelector(`#adminTableBody tr[data-admin-id="${adminIdToDelete}"]`);
        if (rowToRemoveActive) {
            rowToRemoveActive.remove();
        }
        const rowToRemoveArchived = document.querySelector(`#archivedAdminTableBody tr[data-admin-id="${adminIdToDelete}"]`);
        if (rowToRemoveArchived) {
            rowToRemoveArchived.remove();
        }

        document.getElementById('deleteAdminConfirmModal').style.display = 'none';
        adminIdToDelete = null; 
    });

    document.getElementById('cancelAdminDelete').addEventListener('click', () => {
        document.getElementById('deleteAdminConfirmModal').style.display = 'none';
        adminIdToDelete = null;
    });

    // Function para mag-close ng modal (Reusable) - Nilagay ko na rin dito para magamit ng Add/Edit/Close buttons
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    }

    document.querySelectorAll('.close-modal-btn').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            closeModal(modalId);
        });
    });

    // Event listeners para sa pag-close ng modals kapag clinick ang labas (Reusable)
    window.addEventListener('click', (event) => {
        document.querySelectorAll('.modal').forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });


    // *************************************************************
    // JS FOR ADMIN MANAGEMENT MODALS (Add/Edit/Archive/Restore)
    // *************************************************************
    let adminIdToArchive = null;
    let adminIdToRestore = null;

    // 1. ADD ADMIN
    function openAddAdminModal() {
        const modal = document.getElementById('addAdminModal');
        document.getElementById('addAdminForm').reset(); // I-reset ang form
        modal.style.display = 'block';
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Event listener para sa pag-submit ng Add Admin Form (Placeholder)
        const addAdminForm = document.getElementById('addAdminForm');
        const addAdminSuccessModal = document.getElementById('addAdminSuccessModal');
        const confirmAddAdminSuccess = document.getElementById('confirmAddAdminSuccess');

        if (addAdminForm) {
            addAdminForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Dito ang AJAX/Fetch request para i-save ang data
                console.log('Admin Data to Save: Form Submitted');
                
                // After successful save (simulated delay)
                setTimeout(() => {
                    closeModal('addAdminModal'); 
                    addAdminSuccessModal.style.display = 'block';
                }, 500);
            });
        }
        
        // Close Add Admin Success Modal
        if (confirmAddAdminSuccess) {
            confirmAddAdminSuccess.addEventListener('click', () => {
                closeModal('addAdminSuccessModal');
                // Optional: I-reload ang page/listahan para makita ang bagong admin
                // window.location.reload(); 
            });
        }
    });

    // 2. EDIT ADMIN
    function openEditAdminModal(button) {
        const row = button.closest('tr');
        const adminId = row.dataset.adminId;
        
        // Get data from table cells (Assuming the order)
        const cells = row.querySelectorAll('td');
        const firstName = cells[1].textContent;
        const lastName = cells[2].textContent;
        const email = cells[3].textContent;
        const contact = cells[4].textContent;
        const role = cells[5].textContent;

        // Populate the modal fields
        document.getElementById('editAdminId').value = adminId;
        document.getElementById('editAdminFirstName').value = firstName;
        document.getElementById('editAdminLastName').value = lastName;
        document.getElementById('editAdminEmail').value = email;
        document.getElementById('editAdminContact').value = contact;
        document.getElementById('originalAdminRole').value = role; // Store original role
        
        // Select the role in the dropdown
        document.getElementById('editAdminRole').value = role;
        
        // Clear password field
        document.getElementById('editAdminPassword').value = ''; 

        document.getElementById('editAdminModal').style.display = 'block';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const editAdminForm = document.getElementById('editAdminForm');
        const editAdminSuccessModal = document.getElementById('editAdminSuccessModal');
        const confirmEditAdminSuccess = document.getElementById('confirmEditAdminSuccess');
        
        if (editAdminForm) {
            editAdminForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const adminId = document.getElementById('editAdminId').value;
                const newFirstName = document.getElementById('editAdminFirstName').value;
                
                // Dito ang AJAX/Fetch request para i-save ang data
                console.log(`Admin ID ${adminId} updated. New name: ${newFirstName}`);
                
                // After successful save (simulated delay)
                setTimeout(() => {
                    closeModal('editAdminModal'); 
                    editAdminSuccessModal.style.display = 'block';
                }, 500);
            });
        }
        
        // Close Edit Admin Success Modal
        if (confirmEditAdminSuccess) {
            confirmEditAdminSuccess.addEventListener('click', () => {
                closeModal('editAdminSuccessModal');
                // Optional: I-reload ang page/listahan
                // window.location.reload(); 
            });
        }
    });

    // 3. ARCHIVE ADMIN
    function archiveAdmin(adminId) {
        adminIdToArchive = adminId;
        const modal = document.getElementById('archiveAdminConfirmModal');
        document.getElementById('archiveAdminIdDisplay').textContent = adminId;
        modal.style.display = 'block';
    }

    document.getElementById('confirmAdminArchive').addEventListener('click', () => {
        // Dito ilalagay ang actual logic/AJAX call para i-archive
        const adminId = adminIdToArchive;
        alert('Admin ID ' + adminId + ' archived (Placeholder Action).');

        // Halimbawa: I-update ang display
        document.getElementById('archiveAdminSuccessIdDisplay').textContent = adminId;
        closeModal('archiveAdminConfirmModal');
        document.getElementById('archiveAdminSuccessModal').style.display = 'block';

        // Temporary removal from active list (Need actual server logic in real app)
        const rowToRemove = document.querySelector(`#adminTableBody tr[data-admin-id="${adminId}"]`);
        if (rowToRemove) {
            rowToRemove.remove();
        }

        adminIdToArchive = null;
    });

    document.getElementById('cancelAdminArchive').addEventListener('click', () => {
        closeModal('archiveAdminConfirmModal');
        adminIdToArchive = null;
    });
    
    document.getElementById('confirmAdminArchiveSuccess').addEventListener('click', () => {
        closeModal('archiveAdminSuccessModal');
        // window.location.reload(); // Para makita ang update sa archive list
    });

    // 4. RESTORE ADMIN (Galing sa Archive)
    function restoreAdmin(adminId) {
        adminIdToRestore = adminId;
        const modal = document.getElementById('restoreAdminConfirmModal');
        document.getElementById('restoreAdminIdDisplay').textContent = adminId;
        modal.style.display = 'block';
    }

    document.getElementById('confirmAdminRestore').addEventListener('click', () => {
        // Dito ilalagay ang actual logic/AJAX call para i-restore
        const adminId = adminIdToRestore;
        alert('Admin ID ' + adminId + ' restored (Placeholder Action).');

        // Halimbawa: I-update ang display
        document.getElementById('restoreAdminSuccessIdDisplay').textContent = adminId;
        closeModal('restoreAdminConfirmModal');
        document.getElementById('restoreAdminSuccessModal').style.display = 'block';
        
        // Temporary removal from archived list (Need actual server logic in real app)
        const rowToRemove = document.querySelector(`#archivedAdminTableBody tr[data-admin-id="${adminIdToRestore}"]`);
        if (rowToRemove) {
            rowToRemove.remove();
        }
        
        adminIdToRestore = null;
    });

    document.getElementById('cancelAdminRestore').addEventListener('click', () => {
        closeModal('restoreAdminConfirmModal');
        adminIdToRestore = null;
    });
    
    document.getElementById('confirmAdminRestoreSuccess').addEventListener('click', () => {
        closeModal('restoreAdminSuccessModal');
        // window.location.reload(); // Para makita ang update sa active list
    });
    
    // *************************************************************
    
</script>


<script src="client_db.js"></script>
<script src="UM_function.js"></script>
</body>
</html>