<?php
include 'connection.php';
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='landing_page2.html';</script>";
    exit;
}

// Function to get available cleaners (not in any group or only in current group being edited)
function getAvailableCleaners($conn, $excludeGroupId = null) {
    if ($excludeGroupId) {
        $stmt = $conn->prepare("
            SELECT DISTINCT e.id, e.first_name, e.last_name 
            FROM employees e
            WHERE e.position = 'Cleaner' 
            AND e.archived = 0
            AND (
                e.id NOT IN (
                    SELECT cleaner1_id FROM employee_groups WHERE cleaner1_id IS NOT NULL AND id != ?
                    UNION SELECT cleaner2_id FROM employee_groups WHERE cleaner2_id IS NOT NULL AND id != ?
                    UNION SELECT cleaner3_id FROM employee_groups WHERE cleaner3_id IS NOT NULL AND id != ?
                    UNION SELECT cleaner4_id FROM employee_groups WHERE cleaner4_id IS NOT NULL AND id != ?
                    UNION SELECT cleaner5_id FROM employee_groups WHERE cleaner5_id IS NOT NULL AND id != ?
                )
            )
            ORDER BY e.first_name, e.last_name
        ");
        $stmt->bind_param("iiiii", $excludeGroupId, $excludeGroupId, $excludeGroupId, $excludeGroupId, $excludeGroupId);
    } else {
        $stmt = $conn->prepare("
            SELECT DISTINCT e.id, e.first_name, e.last_name 
            FROM employees e
            WHERE e.position = 'Cleaner' 
            AND e.archived = 0
            AND e.id NOT IN (
                SELECT cleaner1_id FROM employee_groups WHERE cleaner1_id IS NOT NULL
                UNION SELECT cleaner2_id FROM employee_groups WHERE cleaner2_id IS NOT NULL
                UNION SELECT cleaner3_id FROM employee_groups WHERE cleaner3_id IS NOT NULL
                UNION SELECT cleaner4_id FROM employee_groups WHERE cleaner4_id IS NOT NULL
                UNION SELECT cleaner5_id FROM employee_groups WHERE cleaner5_id IS NOT NULL
            )
            ORDER BY e.first_name, e.last_name
        ");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $cleaners = [];
    while ($row = $result->fetch_assoc()) {
        $cleaners[] = $row;
    }
    $stmt->close();
    return $cleaners;
}

// Function to get available drivers
function getAvailableDrivers($conn, $excludeGroupId = null) {
    if ($excludeGroupId) {
        $stmt = $conn->prepare("
            SELECT DISTINCT e.id, e.first_name, e.last_name 
            FROM employees e
            WHERE e.position = 'Driver' 
            AND e.archived = 0
            AND (
                e.id NOT IN (
                    SELECT driver_id FROM employee_groups WHERE driver_id IS NOT NULL AND id != ?
                )
            )
            ORDER BY e.first_name, e.last_name
        ");
        $stmt->bind_param("i", $excludeGroupId);
    } else {
        $stmt = $conn->prepare("
            SELECT DISTINCT e.id, e.first_name, e.last_name 
            FROM employees e
            WHERE e.position = 'Driver' 
            AND e.archived = 0
            AND e.id NOT IN (
                SELECT driver_id FROM employee_groups WHERE driver_id IS NOT NULL
            )
            ORDER BY e.first_name, e.last_name
        ");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $drivers = [];
    while ($row = $result->fetch_assoc()) {
        $drivers[] = $row;
    }
    $stmt->close();
    return $drivers;
}

// Function to check if employee is already in another group
function isEmployeeInOtherGroup($conn, $employeeId, $excludeGroupId = null) {
    if ($excludeGroupId) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM employee_groups 
            WHERE id != ? AND (
                cleaner1_id = ? OR cleaner2_id = ? OR cleaner3_id = ? OR 
                cleaner4_id = ? OR cleaner5_id = ? OR driver_id = ?
            )
        ");
        $stmt->bind_param("iiiiiii", $excludeGroupId, $employeeId, $employeeId, $employeeId, $employeeId, $employeeId, $employeeId);
    } else {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM employee_groups 
            WHERE cleaner1_id = ? OR cleaner2_id = ? OR cleaner3_id = ? OR 
                  cleaner4_id = ? OR cleaner5_id = ? OR driver_id = ?
        ");
        $stmt->bind_param("iiiiii", $employeeId, $employeeId, $employeeId, $employeeId, $employeeId, $employeeId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'] > 0;
}

// Handle Create Group
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_group'])) {
    $group_name = trim($_POST['group_name']);
    $cleaner1 = !empty($_POST['cleaner1']) ? $_POST['cleaner1'] : NULL;
    $cleaner2 = !empty($_POST['cleaner2']) ? $_POST['cleaner2'] : NULL;
    $cleaner3 = !empty($_POST['cleaner3']) ? $_POST['cleaner3'] : NULL;
    $cleaner4 = !empty($_POST['cleaner4']) ? $_POST['cleaner4'] : NULL;
    $cleaner5 = !empty($_POST['cleaner5']) ? $_POST['cleaner5'] : NULL;
    $driver = !empty($_POST['driver']) ? $_POST['driver'] : NULL;
    
    $employees = array_filter([$cleaner1, $cleaner2, $cleaner3, $cleaner4, $cleaner5, $driver]);
    if (count($employees) !== count(array_unique($employees))) {
        echo "<script>alert('Error: Cannot assign the same employee twice!'); window.history.back();</script>";
        exit;
    }
    
    foreach ($employees as $empId) {
        if (isEmployeeInOtherGroup($conn, $empId)) {
            echo "<script>alert('Error: Employee already in another group!'); window.history.back();</script>";
            exit;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO employee_groups (group_name, cleaner1_id, cleaner2_id, cleaner3_id, cleaner4_id, cleaner5_id, driver_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiiiii", $group_name, $cleaner1, $cleaner2, $cleaner3, $cleaner4, $cleaner5, $driver);
    
    if ($stmt->execute()) {
        echo "<script>alert('Group created successfully!'); window.location.href='manage_groups.php';</script>";
    } else {
        echo "<script>alert('Error creating group');</script>";
    }
    $stmt->close();
}

// Handle Update Group
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_group'])) {
    $group_id = $_POST['group_id'];
    $group_name = trim($_POST['group_name']);
    $cleaner1 = !empty($_POST['cleaner1']) ? $_POST['cleaner1'] : NULL;
    $cleaner2 = !empty($_POST['cleaner2']) ? $_POST['cleaner2'] : NULL;
    $cleaner3 = !empty($_POST['cleaner3']) ? $_POST['cleaner3'] : NULL;
    $cleaner4 = !empty($_POST['cleaner4']) ? $_POST['cleaner4'] : NULL;
    $cleaner5 = !empty($_POST['cleaner5']) ? $_POST['cleaner5'] : NULL;
    $driver = !empty($_POST['driver']) ? $_POST['driver'] : NULL;
    
    $employees = array_filter([$cleaner1, $cleaner2, $cleaner3, $cleaner4, $cleaner5, $driver]);
    if (count($employees) !== count(array_unique($employees))) {
        echo "<script>alert('Error: Cannot assign the same employee twice!'); window.history.back();</script>";
        exit;
    }
    
    foreach ($employees as $empId) {
        if (isEmployeeInOtherGroup($conn, $empId, $group_id)) {
            echo "<script>alert('Error: Employee already in another group!'); window.history.back();</script>";
            exit;
        }
    }
    
    $stmt = $conn->prepare("UPDATE employee_groups SET group_name=?, cleaner1_id=?, cleaner2_id=?, cleaner3_id=?, cleaner4_id=?, cleaner5_id=?, driver_id=? WHERE id=?");
    $stmt->bind_param("siiiiiii", $group_name, $cleaner1, $cleaner2, $cleaner3, $cleaner4, $cleaner5, $driver, $group_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Group updated successfully!'); window.location.href='manage_groups.php';</script>";
    } else {
        echo "<script>alert('Error updating group');</script>";
    }
    $stmt->close();
}

// Handle Delete Group
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_group'])) {
    $group_id = $_POST['group_id'];
    $stmt = $conn->prepare("DELETE FROM employee_groups WHERE id=?");
    $stmt->bind_param("i", $group_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Group deleted successfully!'); window.location.href='manage_groups.php';</script>";
    } else {
        echo "<script>alert('Error deleting group');</script>";
    }
    $stmt->close();
}

// Fetch all groups
$groups_query = "SELECT eg.*, 
    CONCAT(e1.first_name, ' ', e1.last_name) as cleaner1_name,
    CONCAT(e2.first_name, ' ', e2.last_name) as cleaner2_name,
    CONCAT(e3.first_name, ' ', e3.last_name) as cleaner3_name,
    CONCAT(e4.first_name, ' ', e4.last_name) as cleaner4_name,
    CONCAT(e5.first_name, ' ', e5.last_name) as cleaner5_name,
    CONCAT(d.first_name, ' ', d.last_name) as driver_name
FROM employee_groups eg
LEFT JOIN employees e1 ON eg.cleaner1_id = e1.id
LEFT JOIN employees e2 ON eg.cleaner2_id = e2.id
LEFT JOIN employees e3 ON eg.cleaner3_id = e3.id
LEFT JOIN employees e4 ON eg.cleaner4_id = e4.id
LEFT JOIN employees e5 ON eg.cleaner5_id = e5.id
LEFT JOIN employees d ON eg.driver_id = d.id
ORDER BY eg.group_name";
$groups_result = $conn->query($groups_query);

// Get all cleaners and drivers
$all_cleaners = [];
$stmt = $conn->query("SELECT id, first_name, last_name FROM employees WHERE position = 'Cleaner' AND archived = 0 ORDER BY first_name, last_name");
while ($row = $stmt->fetch_assoc()) {
    $all_cleaners[$row['id']] = $row;
}

$all_drivers = [];
$stmt = $conn->query("SELECT id, first_name, last_name FROM employees WHERE position = 'Driver' AND archived = 0 ORDER BY first_name, last_name");
while ($row = $stmt->fetch_assoc()) {
    $all_drivers[$row['id']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Groups - Admin</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="icon" href="site_icon.png" type="image/png">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="admin_db.css">
    
    <style>
        /* Sidebar dropdown fix */
        .has-dropdown .dropdown__menu { display: none; }
        .has-dropdown.open .dropdown__menu { display: block; }
        .dashboard__sidebar {
            min-width: 250px;
            width: 250px;
            flex-shrink: 0;
        }

        .dashboard__wrapper {
            display: flex;
            min-height: 100vh;
        }

        .dashboard__content {
            flex: 1;
            overflow-x: auto;
            padding: 20px;
            background: #f5f7fa;
        }

        .content-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .page-header h2 {
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .btn-create {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .btn-create:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }

        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
        }

        .group-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .group-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .group-name {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }

        .group-actions {
            display: flex;
            gap: 8px;
        }

        .btn-edit, .btn-delete {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 18px;
        }

        .btn-edit {
            background: #28a745;
            color: white;
        }

        .btn-edit:hover {
            background: #218838;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .member-section {
            margin-bottom: 20px;
        }

        .member-section:last-child {
            margin-bottom: 0;
        }

        .member-section h4 {
            color: #007bff;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
        }

        .member-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .member-item {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #495057;
        }

        .member-item i {
            color: #6c757d;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 80px;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #6c757d;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #adb5bd;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .modal.show {
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 1;
        }

        .modal-content {
            background: white;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px 30px;
            background: #007bff;
            color: white;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .close-btn {
            font-size: 28px;
            cursor: pointer;
            color: white;
            line-height: 1;
        }

        .close-btn:hover {
            color: #f8f9fa;
        }

        #groupForm {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .btn-cancel, .btn-submit {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .btn-submit {
            background: #007bff;
            color: white;
        }

        .btn-submit:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<header class="header" id="header">
    <nav class="nav container">
        <a href="admin_dashboard.php?content=dashboard" class="nav__logo">
            <img src="LOGO.png" alt="ALAZIMA Logo" onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
        </a>
        <button class="nav__toggle" id="nav-toggle"><i class='bx bx-menu'></i></button>
    </nav>
</header>

<div class="dashboard__wrapper">

    <!-- Sidebar -->
    <aside class="dashboard__sidebar">
        <ul class="sidebar__menu">
            <li class="menu__item"><a href="admin_dashboard.php?content=dashboard" class="menu__link"><i class='bx bx-home-alt-2'></i> Dashboard</a></li>
            <li class="menu__item has-dropdown">
                <a href="#" class="menu__link"><i class='bx bx-user-circle'></i> User Management <i class='bx bx-chevron-down arrow-icon'></i></a>
                <ul class="dropdown__menu">
                    <li class="menu__item"><a href="clients.php" class="menu__link">Clients</a></li>
                    <li class="menu__item"><a href="UM_employees.php" class="menu__link">Employees</a></li>
                    <li class="menu__item"><a href="UM_admins.php" class="menu__link">Admins</a></li>
                    <li class="menu__item"><a href="archived_clients.php?content=manage-archive" class="menu__link">Archive</a></li>
                </ul>
            </li>
            <li class="menu__item has-dropdown">
                <a href="#" class="menu__link"><i class='bx bx-calendar-check'></i> Appointment Management <i class='bx bx-chevron-down arrow-icon'></i></a>
                <ul class="dropdown__menu">
                    <li class="menu__item"><a href="AP_one-time.php" class="menu__link">One-time Service</a></li>
                    <li class="menu__item"><a href="AP_recurring.php" class="menu__link">Recurring Service</a></li>
                </ul>
            </li>
            <li class="menu__item"><a href="ES.php" class="menu__link"><i class='bx bx-time'></i> Employee Scheduling</a></li>
            <li class="menu__item"><a href="manage_groups.php" class="menu__link active"><i class='bx bx-group'></i> Manage Groups</a></li>
            <li class="menu__item"><a href="admin_feedback_dashboard.php" class="menu__link"><i class='bx bx-star'></i> Feedback Overview</a></li>
            <li class="menu__item"><a href="Reports.php" class="menu__link"><i class='bx bx-file'></i> Reports</a></li>
            <li class="menu__item"><a href="concern.php?content=profile" class="menu__link"><i class='bx bx-info-circle'></i> Issues & Concerns</a></li>
            <li class="menu__item"><a href="admin_profile.php" class="menu__link"><i class='bx bx-user'></i> Profile</a></li>
            <li class="menu__item"><a href="javascript:void(0)" class="menu__link" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="dashboard__content">
        <div class="content-container">
            <div class="page-header">
                <h2><i class='bx bx-group'></i> Employee Groups Management</h2>
                <button class="btn-create" onclick="openCreateModal()">
                    <i class='bx bx-plus'></i> Create New Group
                </button>
            </div>
            
            <?php if ($groups_result->num_rows > 0): ?>
                <div class="groups-grid">
                    <?php while ($group = $groups_result->fetch_assoc()): ?>
                        <div class="group-card">
                            <div class="group-header">
                                <span class="group-name"><?= htmlspecialchars($group['group_name']) ?></span>
                                <div class="group-actions">
                                    <button class="btn-edit" onclick='openEditModal(<?= htmlspecialchars(json_encode($group), ENT_QUOTES) ?>)'>
                                        <i class='bx bx-edit'></i>
                                    </button>
                                    <button class="btn-delete" onclick="confirmDelete(<?= $group['id'] ?>)">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="group-members">
                                <div class="member-section">
                                    <h4><i class='bx bx-spray-can'></i> Cleaners:</h4>
                                    <div class="member-list">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <div class="member-item">
                                                <i class='bx bx-user'></i>
                                                <?= htmlspecialchars($group["cleaner{$i}_name"] ?? 'Not Assigned') ?>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <div class="member-section">
                                    <h4><i class='bx bx-car'></i> Driver:</h4>
                                    <div class="member-list">
                                        <div class="member-item">
                                            <i class='bx bx-user'></i>
                                            <?= htmlspecialchars($group['driver_name'] ?? 'Not Assigned') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class='bx bx-group'></i>
                    <h3>No Groups Created Yet</h3>
                    <p>Click "Create New Group" to start organizing your teams</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Create/Edit Modal -->
<div id="groupModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"><i class='bx bx-plus-circle'></i> Create New Group</h3>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        
        <form method="POST" id="groupForm">
            <input type="hidden" name="group_id" id="group_id">
            
            <div class="form-group">
                <label>Group Name *</label>
                <input type="text" name="group_name" id="group_name" required placeholder="e.g., Team Alpha, Group 1">
            </div>
            
            <h4 style="margin: 20px 0 15px 0; color: #007bff;">
                <i class='bx bx-spray-can'></i> Select Cleaners (Maximum 5)
            </h4>
            
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <div class="form-group">
                    <label>Cleaner <?= $i ?></label>
                    <select name="cleaner<?= $i ?>" id="cleaner<?= $i ?>" onchange="updateAvailableEmployees()">
                        <option value="">-- Select Cleaner --</option>
                    </select>
                </div>
            <?php endfor; ?>
            
            <h4 style="margin: 20px 0 15px 0; color: #007bff;">
                <i class='bx bx-car'></i> Select Driver
            </h4>
            
            <div class="form-group">
                <label>Driver</label>
                <select name="driver" id="driver" onchange="updateAvailableEmployees()">
                    <option value="">-- Select Driver --</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="create_group" id="submitBtn" class="btn-submit">Create Group</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form method="POST" id="deleteForm" style="display: none;">
    <input type="hidden" name="group_id" id="delete_group_id">
    <input type="hidden" name="delete_group" value="1">
</form>

<script>
// Store all employees data
const allCleaners = <?= json_encode(array_values($all_cleaners)) ?>;
const allDrivers = <?= json_encode(array_values($all_drivers)) ?>;
let currentGroupId = null;

// Sidebar dropdown functionality
(function(){
    const nav = document.querySelector('.sidebar__menu');
    if (!nav) return;
    const dropdownParents = nav.querySelectorAll('.has-dropdown');
    dropdownParents.forEach(parent => {
        const parentLink = parent.querySelector('.menu__link');
        if (!parentLink) return;
        parentLink.addEventListener('click', function(e){
            e.preventDefault();
            parent.classList.toggle('open');
        });
    });
})();

function openCreateModal() {
    currentGroupId = null;
    document.getElementById('groupModal').classList.add('show');
    document.getElementById('modalTitle').innerHTML = '<i class="bx bx-plus-circle"></i> Create New Group';
    document.getElementById('groupForm').reset();
    document.getElementById('group_id').value = '';
    document.getElementById('submitBtn').name = 'create_group';
    document.getElementById('submitBtn').textContent = 'Create Group';
    
    // Fetch available employees via AJAX for create mode
    fetchAvailableEmployees(null);
}

function openEditModal(group) {
    currentGroupId = group.id;
    document.getElementById('groupModal').classList.add('show');
    document.getElementById('modalTitle').innerHTML = '<i class="bx bx-edit"></i> Edit Group';
    
    document.getElementById('group_id').value = group.id;
    document.getElementById('group_name').value = group.group_name;
    
    // Fetch available employees for edit mode (excluding current group)
    fetchAvailableEmployees(group.id, () => {
        // Set selected values after options are loaded
        for (let i = 1; i <= 5; i++) {
            const select = document.getElementById('cleaner' + i);
            if (select && group['cleaner' + i + '_id']) {
                select.value = group['cleaner' + i + '_id'];
            }
        }
        
        const driverSelect = document.getElementById('driver');
        if (driverSelect && group.driver_id) {
            driverSelect.value = group.driver_id;
        }
    });
    
    document.getElementById('submitBtn').name = 'update_group';
    document.getElementById('submitBtn').textContent = 'Update Group';
}

function fetchAvailableEmployees(excludeGroupId, callback) {
    // Create form data
    const formData = new FormData();
    formData.append('get_available_employees', '1');
    if (excludeGroupId) {
        formData.append('exclude_group_id', excludeGroupId);
    }
    
    fetch('get_available_employees.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        populateEmployeeSelects(data.cleaners, data.drivers);
        if (callback) callback();
    })
    .catch(error => {
        console.error('Error fetching employees:', error);
        alert('Error loading available employees. Please refresh the page.');
    });
}

function populateEmployeeSelects(availableCleaners, availableDrivers) {
    // Populate cleaner selects
    for (let i = 1; i <= 5; i++) {
        const select = document.getElementById('cleaner' + i);
        const currentValue = select.value;
        select.innerHTML = '<option value="">-- Select Cleaner --</option>';
        
        availableCleaners.forEach(cleaner => {
            const option = document.createElement('option');
            option.value = cleaner.id;
            option.textContent = cleaner.first_name + ' ' + cleaner.last_name;
            select.appendChild(option);
        });
        
        // Restore previous value if it exists
        if (currentValue && select.querySelector(`option[value="${currentValue}"]`)) {
            select.value = currentValue;
        }
    }
    
    // Populate driver select
    const driverSelect = document.getElementById('driver');
    const currentDriverValue = driverSelect.value;
    driverSelect.innerHTML = '<option value="">-- Select Driver --</option>';
    
    availableDrivers.forEach(driver => {
        const option = document.createElement('option');
        option.value = driver.id;
        option.textContent = driver.first_name + ' ' + driver.last_name;
        driverSelect.appendChild(option);
    });
    
    // Restore previous value if it exists
    if (currentDriverValue && driverSelect.querySelector(`option[value="${currentDriverValue}"]`)) {
        driverSelect.value = currentDriverValue;
    }
}

function updateAvailableEmployees() {
    // Get currently selected employees in the form
    const selectedEmployees = [];
    
    for (let i = 1; i <= 5; i++) {
        const value = document.getElementById('cleaner' + i).value;
        if (value) selectedEmployees.push(value);
    }
    
    const driverValue = document.getElementById('driver').value;
    if (driverValue) selectedEmployees.push(driverValue);
    
    // Re-fetch available employees to update the dropdowns
    fetchAvailableEmployees(currentGroupId);
}

function closeModal() {
    document.getElementById('groupModal').classList.remove('show');
}

function confirmDelete(groupId) {
    if (confirm('Are you sure you want to delete this group? This action cannot be undone.')) {
        document.getElementById('delete_group_id').value = groupId;
        document.getElementById('deleteForm').submit();
    }
}

function showLogoutModal() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('groupModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

</body>
</html>

<?php $conn->close(); ?>