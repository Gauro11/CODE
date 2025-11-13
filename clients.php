<?php
include 'connection.php';
session_start();

// ✅ Ensure user is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

// Handle Update Client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_client'])) {
    $client_id = $_POST['client_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $birthday = $_POST['birthday'];
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    
    $stmt = $conn->prepare("UPDATE clients SET first_name=?, last_name=?, birthday=?, contact_number=?, email=? WHERE id=?");
    $stmt->bind_param("sssssi", $first_name, $last_name, $birthday, $contact_number, $email, $client_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Client updated successfully!'); window.location.href='clients.php';</script>";
    } else {
        echo "<script>alert('Error updating client');</script>";
    }
    $stmt->close();
}

// Search and sort
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

// Base query (exclude archived by default)
$query = "SELECT * FROM clients WHERE archived = 0 AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";

// Sorting
$allowedSorts = ['first_name', 'last_name'];
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
<title>User Management - Clients</title>
<link rel="stylesheet" href="admin_dashboard.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="admin_db.css">

<style>

.search-sort { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
.search-sort input, .search-sort select { padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; }
.search-sort button { padding: 8px 16px; border: none; border-radius: 6px; background: #007bff; color: white; cursor: pointer; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px 12px; border-bottom: 1px solid #ddd; text-align: left; }
th { background: #f4f4f4; }
.actions button { padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; color: #fff; }
.edit { background: #007bff; }
.archive { background: #dc3545; }
.edit:hover { background: #0056b3; }
.archive:hover { background: #a71d2a; }
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

#clientForm {
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

.form-group input {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 15px;
    transition: all 0.3s;
    box-sizing: border-box;
}

.form-group input:focus {
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
/* --- Sidebar Dropdown Fix --- */
.has-dropdown {
  position: relative;
}

.has-dropdown > .menu__link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
}

.has-dropdown .dropdown__menu {
  display: none !important; /* hide by default */
  list-style: none;
  padding-left: 20px;
  margin: 5px 0 0;
  transition: all 0.3s ease;
}

.has-dropdown.open .dropdown__menu {
  display: block !important; /* show when open */
}

.has-dropdown .arrow-icon {
  transition: transform 0.3s;
}

.has-dropdown.open .arrow-icon {
  transform: rotate(180deg);
}

</style>
</head>
<body>

<header class="header" id="header">
<nav class="nav container">
    <a href="admin_dashboard.php?content=dashboard" class="nav__logo">
        <img src="LOGO.png" alt="ALAZIMA Logo" 
             onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
    </a>
    <button class="nav__toggle" id="nav-toggle"><i class='bx bx-menu'></i></button>
</nav>
</header>

<div class="dashboard__wrapper">
    <aside class="dashboard__sidebar">
        <ul class="sidebar__menu">
            <li class="menu__item"><a href="admin_dashboard.php?content=dashboard" class="menu__link"><i class='bx bx-home-alt-2'></i> Dashboard</a></li>
            
            <li class="menu__item has-dropdown">
                <a href="#" class="menu__link active-parent"><i class='bx bx-user-circle'></i> User Management <i class='bx bx-chevron-down arrow-icon'></i></a>
                <ul class="dropdown__menu">
                    <li class="menu__item"><a href="clients.php?content=manage-clients" class="menu__link active">Clients</a></li>
                    <li class="menu__item"><a href="UM_employees.php?content=manage-employees" class="menu__link">Employees</a></li>
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
            <!-- <li class="menu__item"><a href="FR.php" class="menu__link"><i class='bx bx-star'></i> Feedback & Ratings</a></li> -->
            <li class="menu__item"><a href="Reports.php" class="menu__link"><i class='bx bx-file'></i> Reports</a></li>
               <li class="menu__item"><a href="concern.php?content=profile" class="menu__link" data-content="profile"><i class='bx bx-info-circle'></i> Issues&Concerns</a></li>
            <li class="menu__item"><a href="admin_profile.php" class="menu__link"><i class='bx bx-user'></i> Profile</a></li>
            <li class="menu__item"><a href="javascript:void(0)" class="menu__link" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard__content">
        <section class="content__section active">
            <div class="content-container">
                <h2>User Management - Clients</h2>

                <form method="GET" class="search-sort">
                    <input type="text" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>">
                    <select name="sort">
                        <option value="">Sort by</option>
                        <option value="first_name" <?= $sort=='first_name'?'selected':'' ?>>First Name</option>
                        <option value="last_name" <?= $sort=='last_name'?'selected':'' ?>>Last Name</option>
                    </select>
                    <button type="submit">Apply</button>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Birthday</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                                    <td><?= htmlspecialchars($row['last_name']) ?></td>
                                    <td><?= htmlspecialchars($row['birthday']) ?></td>
                                    <td><?= htmlspecialchars($row['contact_number']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td class="actions">
                                        <button class="edit" onclick='openEditModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)'>Edit</button>
                                        <button class="archive" onclick="archiveUser(<?= $row['id'] ?>)">Archive</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;">No clients found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<!-- Edit Client Modal -->
<div id="clientModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class='bx bx-edit'></i> Edit Client</h3>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        
        <form method="POST" id="clientForm">
            <input type="hidden" name="client_id" id="client_id">
            
            <div class="form-group">
                <label>First Name *</label>
                <input type="text" name="first_name" id="first_name" required>
            </div>
            
            <div class="form-group">
                <label>Last Name *</label>
                <input type="text" name="last_name" id="last_name" required>
            </div>
            
            <div class="form-group">
                <label>Birthday *</label>
                <input type="date" name="birthday" id="birthday" required>
            </div>
            
            <div class="form-group">
                <label>Contact Number *</label>
                <input type="text" name="contact_number" id="contact_number" required>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" id="email" required>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="update_client" class="btn-submit">Update Client</button>
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

// Edit Client Modal Functions
function openEditModal(client) {
    document.getElementById('clientModal').classList.add('show');
    document.getElementById('client_id').value = client.id;
    document.getElementById('first_name').value = client.first_name;
    document.getElementById('last_name').value = client.last_name;
    document.getElementById('birthday').value = client.birthday;
    document.getElementById('contact_number').value = client.contact_number;
    document.getElementById('email').value = client.email;
}

function closeModal() {
    document.getElementById('clientModal').classList.remove('show');
}

// Archive
function archiveUser(id) {
    if(confirm('Are you sure you want to archive this client?')) {
        window.location.href='archive_client.php?id='+id;
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('clientModal');
    const logoutModal = document.getElementById('logoutModal');
    if (event.target === modal) {
        closeModal();
    }
    if (event.target === logoutModal) {
        logoutModal.classList.remove('show');
    }
}

// Sidebar dropdown
// ✅ Sidebar dropdown toggle
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

<?php $conn->close(); ?>