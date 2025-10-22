<?php
// PHP code for includes or logic goes here (if any)
// Check the URL parameter to determine which section should be active by default
// If 'content' is not set OR it's set to an empty string, default to 'manage-employees'
// ITO ANG NAGSE-SET NG DEFAULT SECTION
$active_section = isset($_GET['content']) && !empty($_GET['content']) ? $_GET['content'] : 'manage-employees';

// KINAKAILANGA N: DYNAMIC DATA FOR EMPLOYEE MANAGEMENT PAGE
// Sample Data for Employee Management (For Placeholder/Initial Setup)
$total_registered_employees = 45;
$active_employees_count = 40;
$new_employees_this_month = 5;
$employees_with_open_concern = 3;

// NEW: Kinuha ang kasalukuyang buwan (Hal. "October")
$current_month = date('F'); 

// BINAGO: Idinagdag ang 'job_title' at 'salary' field, pinalitan ang 'birthdate' ng 'hire_date'
$employee_list_data = [
    ['id' => 1001, 'first_name' => 'Maria', 'last_name' => 'Santos', 'hire_date' => '2020-05-15', 'job_title' => 'Senior Developer', 'email' => 'maria.s@example.com', 'contact' => '+971501112233', 'status' => 'Active', 'salary' => 80000],
    ['id' => 1002, 'first_name' => 'Ahmed', 'last_name' => 'Almarzooqi', 'hire_date' => '2022-01-20', 'job_title' => 'Marketing Specialist', 'email' => 'ahmed.a@example.com', 'contact' => '+971504445566', 'status' => 'Active', 'salary' => 55000],
    ['id' => 1003, 'first_name' => 'Lisa', 'last_name' => 'Wong', 'hire_date' => '2023-08-01', 'job_title' => 'HR Manager', 'email' => 'lisa.w@example.com', 'contact' => '+971567778899', 'status' => 'Active', 'salary' => 65000],
    ['id' => 1004, 'first_name' => 'Kenji', 'last_name' => 'Tanaka', 'hire_date' => '2021-11-10', 'job_title' => 'IT Support', 'email' => 'kenji.t@example.com', 'contact' => '+971550001122', 'status' => 'Inactive', 'salary' => 45000],
    ['id' => 1005, 'first_name' => 'Fatima', 'last_name' => 'Ali', 'hire_date' => '2024-02-29', 'job_title' => 'Junior Accountant', 'email' => 'fatima.a@example.com', 'contact' => '+971523334455', 'status' => 'Active', 'salary' => 40000]
];

// Sample Data for Archived Employees (For Placeholder/Initial Setup)
$archived_employee_list_data = [
    ['id' => 2001, 'first_name' => 'Jose', 'last_name' => 'Rizal', 'job_title' => 'Former Sales Rep', 'archive_date' => '2024-01-01', 'reason' => 'Resigned'],
    ['id' => 2002, 'first_name' => 'Aisha', 'last_name' => 'Khan', 'job_title' => 'Former Trainee', 'archive_date' => '2024-03-15', 'reason' => 'Contract Ended']
];

// Function to render the status badge/pill
function renderEmployeeStatusBadge($status) {
    switch (strtolower($status)) {
        case 'active':
            return '<span class="status-pill status-active">Active</span>';
        case 'inactive':
            return '<span class="status-pill status-inactive">Inactive</span>';
        case 'on-leave':
            return '<span class="status-pill status-on-leave">On-Leave</span>';
        default:
            return '<span class="status-pill status-unknown">Unknown</span>';
    }
}

// Function to render the management tabs
function renderManagementTabs($active_section) {
    $tabs = [
        'manage-employees' => 'Manage Employees',
        'add-employee' => 'Add New Employee',
        'employee-archive' => 'Employee Archive'
    ];
    echo '<ul class="nav-tabs">';
    foreach ($tabs as $key => $title) {
        $class = ($active_section === $key) ? 'active' : '';
        echo "<li class='nav-tab $class'><a href='?content=$key'>$title</a></li>";
    }
    echo '</ul>';
}

// Function to render the main content section
function renderMainContent($active_section, $employee_list_data, $archived_employee_list_data) {
    switch ($active_section) {
        case 'manage-employees':
            renderManageEmployeesSection($employee_list_data);
            break;
        case 'add-employee':
            renderAddEmployeeSection();
            break;
        case 'employee-archive':
            renderEmployeeArchiveSection($archived_employee_list_data);
            break;
        default:
            renderManageEmployeesSection($employee_list_data); // Default to manage employees
    }
}

// --- Specific Content Rendering Functions ---

function renderManageEmployeesSection($employee_list_data) {
    global $total_registered_employees, $active_employees_count, $new_employees_this_month, $employees_with_open_concern, $current_month;
    ?>
    <section id="manage-employees-section">
        <h2 class="section-title">Registered Employees</h2>

        <div class="overview-cards">
            <div class="card">
                <h3>Total Employees</h3>
                <p class="big-number"><?php echo number_format($total_registered_employees); ?></p>
            </div>
            <div class="card">
                <h3>Active Employees</h3>
                <p class="big-number"><?php echo number_format($active_employees_count); ?></p>
            </div>
            <div class="card card-highlight">
                <h3>New Employees (<?php echo $current_month; ?>)</h3>
                <p class="big-number"><?php echo number_format($new_employees_this_month); ?></p>
            </div>
            <div class="card card-warning">
                <h3>Employees with Concerns</h3>
                <p class="big-number"><?php echo number_format($employees_with_open_concern); ?></p>
            </div>
        </div>
        
        <div class="table-container">
            <h3>Current Employee List</h3>
            <div class="table-controls">
                <input type="text" id="employeeSearch" placeholder="Search by name or ID...">
                <button class="btn btn-primary" onclick="window.location.href='?content=add-employee'">+ Add Employee</button>
            </div>
            <table id="employeeTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Job Title</th>
                        <th>Hire Date</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Salary</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($employee_list_data as $employee) {
                        // Assuming 'salary' is an integer and formatted as currency (e.g., PHP)
                        $salary_display = 'AED ' . number_format($employee['salary']);
                        
                        echo "<tr>";
                        echo "<td>{$employee['id']}</td>";
                        echo "<td>{$employee['first_name']} {$employee['last_name']}</td>";
                        echo "<td>{$employee['job_title']}</td>";
                        echo "<td>{$employee['hire_date']}</td>";
                        echo "<td>{$employee['email']}</td>";
                        echo "<td>{$employee['contact']}</td>";
                        echo "<td>" . renderEmployeeStatusBadge($employee['status']) . "</td>";
                        echo "<td>{$salary_display}</td>";
                        echo "<td class='actions-cell'>";
                        echo "<button class='btn btn-small btn-secondary edit-employee' data-employee-id='{$employee['id']}'>Edit</button>";
                        echo "<button class='btn btn-small btn-danger archive-employee' data-employee-id='{$employee['id']}'>Archive</button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </section>

    

    <div id="archiveConfirmModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h4>Confirm Employee Archival</h4>
            <p>Are you sure you want to archive Employee ID: <strong id="employeeIdDisplay"></strong>?</p>
            <p>This action will move the employee's record to the archive.</p>
            <div class="modal-actions">
                <button id="confirmArchiveBtn" class="btn btn-danger">Yes, Archive</button>
                <button id="cancelArchiveBtn" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
    
    <div id="archiveSuccessModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h4>Employee Archived Successfully!</h4>
            <p>Employee ID: <strong id="archiveSuccessIdDisplay"></strong> has been successfully archived.</p>
            <div class="modal-actions">
                <button id="confirmArchiveSuccessBtn" class="btn btn-primary">OK</button>
            </div>
        </div>
    </div>
    <?php
}

function renderAddEmployeeSection() {
    ?>
    <section id="add-employee-section">
        <h2 class="section-title">Add New Employee</h2>
        <div class="form-container">
            <form id="addEmployeeForm">
                <div class="form-group">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="firstName" required>
                </div>
                <div class="form-group">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="lastName" required>
                </div>
                <div class="form-group">
                    <label for="jobTitle">Job Title</label>
                    <input type="text" id="jobTitle" name="jobTitle" required>
                </div>
                <div class="form-group">
                    <label for="hireDate">Hire Date</label>
                    <input type="date" id="hireDate" name="hireDate" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <input type="tel" id="contact" name="contact">
                </div>
                <div class="form-group">
                    <label for="salary">Salary (AED)</label>
                    <input type="number" id="salary" name="salary" min="0" step="1000">
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="On-Leave">On-Leave</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Employee</button>
                    <button type="reset" class="btn btn-secondary">Clear Form</button>
                </div>
            </form>
        </div>
    </section>
    <?php
}

function renderEmployeeArchiveSection($archived_employee_list_data) {
    ?>
    <section id="employee-archive-section">
        <h2 class="section-title">Employee Archive</h2>
        <p>Records of employees who are no longer active in the system.</p>
        
        <div class="table-container">
            <h3>Archived Employees</h3>
            <div class="table-controls">
                <input type="text" id="archiveSearch" placeholder="Search archived employees...">
            </div>
            <table id="archiveTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Job Title</th>
                        <th>Archive Date</th>
                        <th>Reason</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($archived_employee_list_data as $employee) {
                        echo "<tr>";
                        echo "<td>{$employee['id']}</td>";
                        echo "<td>{$employee['first_name']} {$employee['last_name']}</td>";
                        echo "<td>{$employee['job_title']}</td>";
                        echo "<td>{$employee['archive_date']}</td>";
                        echo "<td>{$employee['reason']}</td>";
                        echo "<td class='actions-cell'>";
                        echo "<button class='btn btn-small btn-success restore-employee' data-employee-id='{$employee['id']}'>Restore</button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </section>

    <div id="restoreConfirmModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h4>Confirm Employee Restoration</h4>
            <p>Are you sure you want to restore Employee ID: <strong id="employeeToRestoreIdDisplay"></strong>?</p>
            <p>This action will move the employee's record back to the active list.</p>
            <div class="modal-actions">
                <button id="confirmRestoreBtn" class="btn btn-success">Yes, Restore</button>
                <button id="cancelRestoreBtn" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>
    
    <div id="restoreSuccessModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h4>Employee Restored Successfully!</h4>
            <p>Employee ID: <strong id="restoreSuccessIdDisplay"></strong> has been successfully restored.</p>
            <div class="modal-actions">
                <button id="confirmRestoreSuccessBtn" class="btn btn-primary">OK</button>
            </div>
        </div>
    </div>
    <?php
}


// --- HTML Structure Starts Here ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management (UM_employees)</title>
    <style>
        /* General Styles */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f4f7f9; color: #333; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        h1 { color: #007bff; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        h2.section-title { color: #007bff; margin-top: 30px; border-left: 5px solid #007bff; padding-left: 10px; }

        /* Navigation Tabs */
        .nav-tabs { list-style: none; padding: 0; margin: 0 0 20px 0; display: flex; border-bottom: 2px solid #ddd; }
        .nav-tab { margin-right: 15px; }
        .nav-tab a { display: block; padding: 10px 15px; text-decoration: none; color: #555; border-bottom: 3px solid transparent; transition: all 0.3s; }
        .nav-tab.active a, .nav-tab a:hover { color: #007bff; border-bottom: 3px solid #007bff; }

        /* Overview Cards */
        .overview-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .card { background-color: #f8f8f8; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); border-left: 5px solid #ccc; }
        .card-highlight { border-left-color: #28a745; background-color: #e6f6e9; }
        .card-warning { border-left-color: #ffc107; background-color: #fff7e6; }
        .card h3 { margin-top: 0; font-size: 16px; color: #555; }
        .card .big-number { font-size: 32px; font-weight: bold; margin: 5px 0 0 0; color: #007bff; }
        .card-highlight .big-number { color: #28a745; }
        .card-warning .big-number { color: #ffc107; }

        /* Tables */
        .table-container { background-color: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); margin-top: 20px; }
        .table-controls { display: flex; justify-content: space-between; margin-bottom: 15px; align-items: center; }
        #employeeSearch, #archiveSearch { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 300px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #f0f3f5; color: #555; font-weight: 600; text-transform: uppercase; font-size: 14px; }
        tr:hover { background-color: #f9f9f9; }
        .actions-cell button { margin-right: 5px; }

        /* Status Pills */
        .status-pill { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: capitalize; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .status-on-leave { background-color: #fff3cd; color: #856404; }
        .status-unknown { background-color: #e2e3e5; color: #383d41; }
        
        /* Buttons */
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; transition: background-color 0.2s; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-secondary:hover { background-color: #5a6268; }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-danger:hover { background-color: #bd2130; }
        .btn-success { background-color: #28a745; color: white; }
        .btn-success:hover { background-color: #1e7e34; }
        .btn-small { padding: 5px 10px; font-size: 12px; }

        /* Form Styles (Add Employee Section) */
        .form-container { max-width: 600px; margin: 20px auto; padding: 30px; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Important for padding */
        }
        .form-actions { margin-top: 20px; display: flex; gap: 10px; }

        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            padding-top: 50px;
        }
        .modal.show {
            display: block;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; /* 5% from the top and centered */
            padding: 30px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 400px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }
        .modal-content h4 { margin-top: 0; color: #007bff; }
        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 15px;
        }
        .close-btn:hover,
        .close-btn:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
        .modal-actions { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; }

    </style>
</head>
<body>

    <div class="container">
        <h1>Employee Management System</h1>

        <?php 
        // Render the navigation tabs
        renderManagementTabs($active_section);

        // Render the main content based on the active section
        renderMainContent($active_section, $employee_list_data, $archived_employee_list_data);
        ?>
    </div>

    <script>
        // --- Employee Management Section (Archive) ---
        
        let employeeToArchiveId = null; 

        // 1A. Get Elements
        const archiveButtons = document.querySelectorAll('.archive-employee');
        const archiveConfirmModal = document.getElementById('archiveConfirmModal');
        const employeeIdDisplay = document.getElementById('employeeIdDisplay');
        const confirmArchiveBtn = document.getElementById('confirmArchiveBtn');
        const cancelArchiveBtn = document.getElementById('cancelArchiveBtn');
        const archiveSuccessModal = document.getElementById('archiveSuccessModal');
        const confirmArchiveSuccessBtn = document.getElementById('confirmArchiveSuccessBtn');
        
        // Event listeners for Archive Buttons (Manage Employees Table)
        archiveButtons.forEach(button => {
            button.addEventListener('click', function() {
                employeeToArchiveId = this.getAttribute('data-employee-id');
                employeeIdDisplay.textContent = employeeToArchiveId;
                archiveConfirmModal.classList.add('show');
            });
        });

        // 1B. Confirm Archive Button Click
        if (confirmArchiveBtn) {
            confirmArchiveBtn.addEventListener('click', function() {
                // TANGGALIN/I-HIDE ANG CONFIRMATION MODAL
                archiveConfirmModal.classList.remove('show');
                
                // DITO MO ILALAGAY ANG IYONG REAL AJAX CALL para i-archive ang employeeToArchiveId
                console.log('Sending AJAX request to archive employee ID: ' + employeeToArchiveId);
                
                // Sa totoong system, after successful AJAX call, gawin ito:
                
                // Ipakita ang Success Modal
                document.getElementById('archiveSuccessIdDisplay').textContent = employeeToArchiveId;
                archiveSuccessModal.classList.add('show');
                
                // Optional: I-hide/i-remove ang row mula sa manage table 
                // Example: removeEmployeeRowFromManageTable(employeeToArchiveId);
            });
        }
        
        // 1C. Cancel Archive Button Click
        if (cancelArchiveBtn) {
            cancelArchiveBtn.addEventListener('click', function() {
                archiveConfirmModal.classList.remove('show');
                employeeToArchiveId = null; 
            });
        }

        // 1D. Close Archive Success Modal
        if (confirmArchiveSuccessBtn) {
            confirmArchiveSuccessBtn.addEventListener('click', function() {
                archiveSuccessModal.classList.remove('show');
                employeeToArchiveId = null; 
                
                // Optional: I-reload ang page o i-update ang table
                // window.location.reload(); 
            });
        }


        // --- Employee Archive Section (Restore) ---
        
        let employeeToRestoreId = null; 

        // 2A. Get Elements
        const restoreButtons = document.querySelectorAll('.restore-employee');
        const restoreConfirmModal = document.getElementById('restoreConfirmModal');
        const employeeToRestoreIdDisplay = document.getElementById('employeeToRestoreIdDisplay');
        const confirmRestoreBtn = document.getElementById('confirmRestoreBtn');
        const cancelRestoreBtn = document.getElementById('cancelRestoreBtn');
        const restoreSuccessModal = document.getElementById('restoreSuccessModal');
        const confirmRestoreSuccessBtn = document.getElementById('confirmRestoreSuccessBtn');
        
        // Event listeners for Restore Buttons (Employee Archive Table)
        restoreButtons.forEach(button => {
            button.addEventListener('click', function() {
                employeeToRestoreId = this.getAttribute('data-employee-id');
                employeeToRestoreIdDisplay.textContent = employeeToRestoreId;
                restoreConfirmModal.classList.add('show');
            });
        });

        // 2B. Confirm Restore Button Click
        if (confirmRestoreBtn) {
            confirmRestoreBtn.addEventListener('click', function() {
                // TANGGALIN/I-HIDE ANG CONFIRMATION MODAL
                restoreConfirmModal.classList.remove('show');
                
                // DITO MO ILALAGAY ANG IYONG REAL AJAX CALL para i-restore ang employeeToRestoreId
                console.log('Sending AJAX request to restore employee ID: ' + employeeToRestoreId);
                
                // Sa totoong system, after successful AJAX call, gawin ito:
                
                // Ipakita ang Success Modal
                document.getElementById('restoreSuccessIdDisplay').textContent = employeeToRestoreId;
                restoreSuccessModal.classList.add('show');
                
                // Optional: I-hide/i-remove ang row mula sa archive table 
                // Example: removeEmployeeRowFromArchiveTable(employeeToRestoreId);
            });
        }
        
        // 2C. Cancel Restore Button Click
        if (cancelRestoreBtn) {
            cancelRestoreBtn.addEventListener('click', function() {
                restoreConfirmModal.classList.remove('show');
                employeeToRestoreId = null; 
            });
        }
        
        // 2D. Close Restore Success Modal
        if (confirmRestoreSuccessBtn) {
            confirmRestoreSuccessBtn.addEventListener('click', function() {
                restoreSuccessModal.classList.remove('show');
                employeeToRestoreId = null; 
                
                // Optional: I-reload ang page o i-update ang table
                // window.location.reload(); 
            });
        }

        // Para rin maisara ang lahat ng confirmation/success modals kapag kinlik ang labas (background)
        [archiveConfirmModal, archiveSuccessModal, restoreConfirmModal, restoreSuccessModal].forEach(modal => {
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.classList.remove('show');
                        // Clear the pending IDs if the main confirmation modal is closed this way
                        if (modal.id === 'archiveConfirmModal') {
                             employeeToArchiveId = null;
                        }
                        if (modal.id === 'restoreConfirmModal') {
                             employeeToRestoreId = null;
                        }
                    }
                });
                
                // Close button (X) functionality
                const closeBtn = modal.querySelector('.close-btn');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        modal.classList.remove('show');
                        // Clear the pending IDs if the main confirmation modal is closed this way
                        if (modal.id === 'archiveConfirmModal') {
                             employeeToArchiveId = null;
                        }
                        if (modal.id === 'restoreConfirmModal') {
                             employeeToRestoreId = null;
                        }
                    });
                }
            }
        });


        // --- Other JS Functions ---

        // Employee Search/Filtering (Basic client-side implementation)
        const employeeSearch = document.getElementById('employeeSearch');
        const employeeTable = document.getElementById('employeeTable');

        if (employeeSearch && employeeTable) {
            employeeSearch.addEventListener('keyup', function() {
                const filter = employeeSearch.value.toUpperCase();
                const tr = employeeTable.getElementsByTagName('tr');
                
                for (let i = 1; i < tr.length; i++) { // Start from 1 to skip the header row
                    let display = 'none';
                    // Check columns 0 (ID) and 1 (Name)
                    for (let j = 0; j <= 1; j++) {
                        const td = tr[i].getElementsByTagName('td')[j];
                        if (td) {
                            const txtValue = td.textContent || td.innerText;
                            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                                display = '';
                                break;
                            }
                        }
                    }
                    tr[i].style.display = display;
                }
            });
        }
        
        // Add Employee Form Submission (Placeholder)
        const addEmployeeForm = document.getElementById('addEmployeeForm');
        if (addEmployeeForm) {
            addEmployeeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Form Submitted (Placeholder)');
                
                const formData = new FormData(addEmployeeForm);
                const employeeData = Object.fromEntries(formData.entries());
                
                console.log('New Employee Data:', employeeData);
                
                alert('Employee "' + employeeData.firstName + ' ' + employeeData.lastName + '" added successfully (Placeholder)!');
                addEmployeeForm.reset();
                // DITO MO ILALAGAY ANG IYONG REAL AJAX CALL
            });
        }

    </script>
</body>
</html>