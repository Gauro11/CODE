<?php
include 'connection.php'; // ✅ DB connection
session_start();

// ✅ Ensure user is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

// Handle Add Employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $age = $_POST['age'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $position = trim($_POST['position']);
    
    // Hash default password
    $password = password_hash('Employee123!', PASSWORD_DEFAULT);
    $archived = 0; // default active
    
    $stmt = $conn->prepare("INSERT INTO employees (first_name, last_name, email, password, phone_number, age, birthdate, gender, position, archived) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssisssi", $first_name, $last_name, $email, $password, $phone_number, $age, $birthdate, $gender, $position, $archived);
    
    if ($stmt->execute()) {
        echo "<script>alert('Employee added successfully!'); window.location.href='UM_employees.php';</script>";
    } else {
        echo "<script>alert('Error adding employee: " . $conn->error . "');</script>";
    }
    $stmt->close();
}

// Handle Update Employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_employee'])) {
    $employee_id = $_POST['employee_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $age = $_POST['age'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $position = trim($_POST['position']);
    
    $stmt = $conn->prepare("UPDATE employees SET first_name=?, last_name=?, email=?, phone_number=?, age=?, birthdate=?, gender=?, position=? WHERE id=?");
    $stmt->bind_param("ssssisssi", $first_name, $last_name, $email, $phone_number, $age, $birthdate, $gender, $position, $employee_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Employee updated successfully!'); window.location.href='UM_employees.php';</script>";
    } else {
        echo "<script>alert('Error updating employee');</script>";
    }
    $stmt->close();
}

// Search and sort
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

// Base query: exclude archived employees from list
$query = "SELECT * FROM employees WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?) AND archived = 0";

// Sorting
$allowedSorts = ['first_name', 'last_name', 'position'];
if ($sort && in_array($sort, $allowedSorts)) {
    $query .= " ORDER BY $sort ASC";
}

$stmt = $conn->prepare($query);
$searchTerm = "%$search%";
$stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Management - Employees</title>
<link rel="stylesheet" href="admin_dashboard.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="admin_db.css">
<style>

.search-sort {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}
.search-sort input, .search-sort select {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
}
.search-sort button, .add-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    background: #007bff;
    color: white;
    cursor: pointer;
}
.add-btn { margin-bottom: 10px; }
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 10px 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}
th { background: #f4f4f4; }
.actions button {
    padding: 6px 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    color: #fff;
}
.edit { background: #007bff; }
.archive { background: #dc3545; }
.restore { background: #28a745; }
.edit:hover { background: #0056b3; }
.archive:hover { background: #a71d2a; }
.restore:hover { background: #1e7e34; }

/* Sidebar dropdown fix */
.has-dropdown .dropdown__menu { display: none; }
.has-dropdown.open .dropdown__menu { display: block; }

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
    max-width: 700px;
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

#employeeForm {
    padding: 30px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
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

.form-group input, .form-group select {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 15px;
    transition: all 0.3s;
    box-sizing: border-box;
}

.form-group input:focus, .form-group select:focus {
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

.modal__title {
    padding: 20px;
    margin: 0;
    text-align: center;
}

.modal__actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    padding: 20px;
}

.btn {
    padding: 12px 30px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 500;
    transition: all 0.3s;
}

.btn--secondary {
    background: #6c757d;
    color: white;
}

.btn--secondary:hover {
    background: #5a6268;
}

.btn--primary {
    background: #007bff;
    color: white;
}

.btn--primary:hover {
    background: #0056b3;
}
#addEmployeeForm {
    padding: 30px;
}

</style>
</head>
<body>
<!-- HEADER -->
<header class="header" id="header">
<nav class="nav container">
    <a href="admin_dashboard.php?content=dashboard" class="nav__logo">
        <img src="LOGO.png" alt="ALAZIMA Logo" 
             onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
    </a>
    <button class="nav__toggle" id="nav-toggle"><i class='bx bx-menu'></i></button>
</nav>
</header>

<!-- DASHBOARD WRAPPER -->
<div class="dashboard__wrapper">
    <!-- SIDEBAR -->
    <aside class="dashboard__sidebar">
        <ul class="sidebar__menu">
            <li class="menu__item"><a href="admin_dashboard.php?content=dashboard" class="menu__link"><i class='bx bx-home-alt-2'></i> Dashboard</a></li>
            
            <li class="menu__item has-dropdown">
                <a href="#" class="menu__link active-parent"><i class='bx bx-user-circle'></i> User Management <i class='bx bx-chevron-down arrow-icon'></i></a>
                <ul class="dropdown__menu">
                    <li class="menu__item"><a href="clients.php?content=manage-clients" class="menu__link">Clients</a></li>
                    <li class="menu__item"><a href="UM_employees.php?content=manage-employees" class="menu__link active">Employees</a></li>
                    <li class="menu__item"><a href="UM_admins.php?content=manage-admins" class="menu__link">Admins</a></li>
                     <li class="menu__item"><a href="archived_clients.php?content=manage-archive" class="menu__link" data-content="manage-archive">Archive</a></li>
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
            <li class="menu__item"><a href="manage_groups.php" class="menu__link "><i class='bx bx-group'></i> Manage Groups</a></li>
             <li class="menu__item"><a href="admin_feedback_dashboard.php" class="menu__link "><i class='bx bx-star'></i> Feedback Overview</a></li>
            <li class="menu__item"><a href="Reports.php" class="menu__link"><i class='bx bx-file'></i> Reports</a></li>
               <li class="menu__item"><a href="concern.php?content=profile" class="menu__link" data-content="profile"><i class='bx bx-info-circle'></i> Issues&Concerns</a></li>
            <li class="menu__item"><a href="admin_profile.php" class="menu__link"><i class='bx bx-user'></i> Profile</a></li>
            <li class="menu__item"><a href="javascript:void(0)" class="menu__link" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard__content">
        <section class="content__section active">
            <div class="content-container">
                <h2>User Management - Employees</h2>

                <!-- Add Employee button always visible -->
                <button class="add-btn" onclick="openAddModal()">Add Employee</button>

                <form method="GET" class="search-sort">
                    <input type="text" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>">
                    <select name="sort">
                        <option value="">Sort by</option>
                        <option value="first_name" <?= $sort=='first_name'?'selected':'' ?>>First Name</option>
                        <option value="last_name" <?= $sort=='last_name'?'selected':'' ?>>Last Name</option>
                        <option value="position" <?= $sort=='position'?'selected':'' ?>>Position</option>
                    </select>
                    <button type="submit">Apply</button>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>FName</th>
                            <th>LName</th>
                            <th>Email</th>
                            <th>Contact Number</th>
                            <th>Age</th>
                            <th>Birthday</th>
                            <th>Gender</th>
                            <th>Position</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                                    <td><?= htmlspecialchars($row['last_name']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['phone_number']) ?></td>
                                    <td><?= htmlspecialchars($row['age']) ?></td>
                                    <td><?= htmlspecialchars($row['birthdate']) ?></td>
                                    <td><?= htmlspecialchars($row['gender']) ?></td>
                                    <td><?= htmlspecialchars($row['position']) ?></td>
                                    <td class="actions">
                                        <button class="edit" onclick='openEditModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)'>Edit</button>
                                        <?php if(isset($row['archived']) && $row['archived'] == 1): ?>
                                            <button class="restore" onclick="restoreEmployee(<?= $row['id'] ?>)">Restore</button>
                                        <?php else: ?>
                                            <button class="archive" onclick="archiveEmployee(<?= $row['id'] ?>)">Archive</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align:center;">No employees found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<!-- Add Employee Modal -->
<div id="addEmployeeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class='bx bx-user-plus'></i> Add New Employee</h3>
            <span class="close-btn" onclick="closeAddModal()">&times;</span>
        </div>
        
        <form method="POST" id="addEmployeeForm">
            <div class="form-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" id="add_first_name" required>
                </div>
                
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" id="add_last_name" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="add_email" required>
                </div>
                
                <div class="form-group">
                    <label>Contact Number *</label>
                   <input type="text" name="phone_number" id="add_phone_number" value="+971" required maxlength="16">

                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Age *</label>
                    <input type="number" name="age" id="add_age" required min="18" max="100">
                </div>
                
                <div class="form-group">
                    <label>Birthday *</label>
                    <input type="date" name="birthdate" id="add_birthdate" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Gender *</label>
                    <select name="gender" id="add_gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Prefer Not to Say">Prefer Not to Say</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Position *</label>
                    <select name="position" id="add_position" required>
                        <option value="">Select Position</option>
                        <option value="Driver">Driver</option>
                        <option value="Cleaner">Cleaner</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeAddModal()">Cancel</button>
                <button type="submit" name="add_employee" class="btn-submit">Add Employee</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Employee Modal -->
<div id="employeeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class='bx bx-edit'></i> Edit Employee</h3>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        
        <form method="POST" id="employeeForm">
            <input type="hidden" name="employee_id" id="employee_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" id="first_name" required>
                </div>
                
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" id="last_name" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label>Contact Number *</label>
                    <input type="text" name="phone_number" id="add_phone_number" value="+971" required maxlength="16">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Age *</label>
                    <input type="number" name="age" id="age" required min="18" max="100">
                </div>
                
                <div class="form-group">
                    <label>Birthday *</label>
                    <input type="date" name="birthdate" id="birthdate" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Gender *</label>
                    <select name="gender" id="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Prefer Not to Say">Prefer Not to Say</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Position *</label>
                    <select name="position" id="position" required>
                        <option value="">Select Position</option>
                        <option value="Driver">Driver</option>
                        <option value="Cleaner">Cleaner</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="update_employee" class="btn-submit">Update Employee</button>
            </div>
        </form>
    </div>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="modal">
<div class="modal-content">
<h3 class="modal__title">Are you sure you want to log out?</h3>
<div class="modal__actions">
<button id="cancelLogout" class="btn btn--secondary">Cancel</button>
<button id="confirmLogout" class="btn btn--primary">Log Out</button>
</div>
</div>
</div>
<script>
const phoneInput = document.getElementById("add_phone_number");

// Always ensure +971 shows, even when the user tries to delete it
phoneInput.addEventListener("input", function () {
    let val = this.value;

    // Remove everything except numbers and +
    val = val.replace(/[^0-9+]/g, "");

    // Force prefix +971
    if (!val.startsWith("+971")) {
        val = "+971" + val.replace("+971", "");
    }

    this.value = val;
});

// Prevent deleting +971 with backspace
phoneInput.addEventListener("keydown", function (e) {
    if (this.selectionStart <= 4 && (e.key === "Backspace" || e.key === "Delete")) {
        e.preventDefault();
    }
});
</script>



<script>
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

// Edit Employee Modal Functions
function openAddModal() {
    document.getElementById('addEmployeeModal').classList.add('show');
    document.getElementById('addEmployeeForm').reset();
}

function closeAddModal() {
    document.getElementById('addEmployeeModal').classList.remove('show');
}

function openEditModal(employee) {
    document.getElementById('employeeModal').classList.add('show');
    document.getElementById('employee_id').value = employee.id;
    document.getElementById('first_name').value = employee.first_name;
    document.getElementById('last_name').value = employee.last_name;
    document.getElementById('email').value = employee.email;
    document.getElementById('phone_number').value = employee.phone_number;
    document.getElementById('age').value = employee.age;
    document.getElementById('birthdate').value = employee.birthdate;
    document.getElementById('gender').value = employee.gender;
    document.getElementById('position').value = employee.position;
}

function closeModal() {
    document.getElementById('employeeModal').classList.remove('show');
}

// Archive
function archiveEmployee(id) {
    if(confirm('Are you sure you want to archive this employee?')) {
        window.location.href='archive_employee.php?id='+id;
    }
}

function restoreEmployee(id) {
    if(confirm('Restore this employee?')) {
        window.location.href='restore_employee.php?id='+id;
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addEmployeeModal');
    const editModal = document.getElementById('employeeModal');
    const logoutModal = document.getElementById('logoutModal');
    
    if (event.target === addModal) {
        closeAddModal();
    }
    if (event.target === editModal) {
        closeModal();
    }
    if (event.target === logoutModal) {
        logoutModal.classList.remove('show');
    }
}

// Sidebar dropdown toggle
document.querySelectorAll('.has-dropdown > .menu__link').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const parent = link.parentElement;

    // close others
    document.querySelectorAll('.has-dropdown').forEach(item => {
      if (item !== parent) item.classList.remove('open');
    });

    // toggle current
    parent.classList.toggle('open');
  });
});
</script>
</body>
</html>