<?php
session_start();
require 'connection.php'; // your DB connection

// ✅ Ensure admin is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='landing_page2.html';</script>";
    exit;
}

$admin_email = $_SESSION['email'];
$success = false;
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name'])) {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $birthday = $_POST['birthday'];
    $contactNumber = $_POST['contact_number'];
    $emailAddress = $_POST['email'];
    $address = $_POST['address'] ?? null; // optional
    $gender = $_POST['gender'] ?? 'Prefer Not to Say'; // default if not set
    $age = $_POST['age'] ?? null;

    $stmt = $conn->prepare("UPDATE admins SET first_name=?, last_name=?, birthday=?, contact_number=?, email=?, address=?, gender=?, age=? WHERE email=?");
    $stmt->bind_param("ssssssssi", $firstName, $lastName, $birthday, $contactNumber, $emailAddress, $address, $gender, $age, $admin_email);
    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        $_SESSION['email'] = $emailAddress;
        header("Location: admin_profile.php?success=1");
        exit;
    } else {
        $error = "Failed to update profile. Please try again.";
    }

    $stmt->close();
}

// Fetch admin data
$stmt = $conn->prepare("SELECT first_name, last_name, birthday, contact_number, email, address, gender, age FROM admins WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Admin not found.'); window.location.href='landing_page2.html';</script>";
    exit;
}

$admin = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Pre-fill variables
$firstName = $admin['first_name'];
$lastName = $admin['last_name'];
$birthday = $admin['birthday'];
$contactNumber = $admin['contact_number'];
$emailAddress = $admin['email'];
$address = $admin['address'];
$gender = $admin['gender'];
$age = $admin['age'];
?>
<!-- ✅ ADD THIS AFTER BODY TAG -->
<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
<script>
    alert("✅ Profile updated successfully!");
</script>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ALAZIMA - Admin Profile</title>
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="client_db.css">

<style>

    /* FORCE DROPDOWN TO WORK */
.dropdown__menu {
    max-height: 0 !important;
    overflow: hidden !important;
    transition: max-height 0.3s ease-out !important;
    padding: 0 !important;
    background-color: #f7f7f7 !important;
}

.has-dropdown.active-dropdown .dropdown__menu {
    max-height: 300px !important;
    padding: 5px 0 !important;
}

.has-dropdown.active-dropdown .arrow-icon {
    transform: rotate(180deg) !important;
}

.arrow-icon {
    transition: transform 0.3s ease !important;
}

.dropdown__menu .menu__link {
    padding-left: 50px !important;
    font-size: 0.9em !important;
}
/* --- SIDEBAR DROPDOWN STYLES (NEW) --- */
/* Hide dropdown menu by default */
.dropdown__menu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out, padding 0.3s ease-out;
    padding: 0; /* Ensures no padding when collapsed */
    background-color: #f7f7f7; /* Light background for visibility */
}

/* Show dropdown menu when active */
.has-dropdown.active-dropdown .dropdown__menu {
    max-height: 200px; /* Adjust based on max content height */
    padding: 5px 0; /* Add back padding when open */
}

/* Rotate arrow icon when dropdown is active */
.has-dropdown.active-dropdown .arrow-icon {
    transform: rotate(180deg);
}

/* Basic styling for the arrow icon */
.arrow-icon {
    transition: transform 0.3s ease;
    margin-left: auto; /* Push to the right */
}

/* Adjustments for dropdown items */
.dropdown__menu .menu__link {
    padding-left: 30px; /* Indent dropdown links */
    font-size: 0.95em;
}
/* --- END SIDEBAR DROPDOWN STYLES --- */

/* --- OVERRIDE FOR SIDEBAR HEIGHT (START) --- */
.dashboard__wrapper {
min-height: 100vh; /* O kung anuman ang tamang height setup ng inyong dashboard */
align-items: stretch;
}

.dashboard__sidebar {
height: auto !important; /* Siguruhin na aabot hanggang sa dulo ng content */
min-height: 100%; /* Mag-stretch kasabay ng content */
overflow-y: auto; /* Para sa sidebar scrolling kung kailangan */
}
/* --- OVERRIDE FOR SIDEBAR HEIGHT (END) --- */


/* Container for the profile card/form */
.profile-form-container {
background-color: #fff;
padding: 30px;
border-radius: 8px;
box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
max-width: 800px;
margin: 20px auto;
}

.profile-form-container .main-title {
margin-top: 0;
padding-bottom: 10px;
border-bottom: 1px solid #eee;
margin-bottom: 15px;
}

/* Custom style for the icon in the main title */
.profile-form-container .main-title i {
color: #007bff; /* Kulay asul */
margin-right: 8px; /* Nagdagdag ng konting space */
font-size: 1.3em; /* Pinalaki nang kaunti */
}

.profile-form-container .page-description {
margin-top: -10px;
margin-bottom: 25px;
color: #666;
font-size: 0.95em;
}

/* Grid layout for two-column fields */
.form-grid {
display: grid;
grid-template-columns: 1fr;
gap: 20px 30px;
}

@media (min-width: 600px) {
.form-grid {
grid-template-columns: 1fr 1fr;
}
.form-group-full {
grid-column: 1 / -1;
}
}

.form-group {
margin-bottom: 5px;
}

.form-group label {
display: block;
font-weight: 600;
color: #333;
margin-bottom: 5px;
font-size: 1em;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="date"],
.form-group input[type="tel"] {
width: 100%;
padding: 12px 15px;
border: 1px solid #ccc;
border-radius: 5px;
box-sizing: border-box;
font-size: 1.0em; /* Base size */
color: #555;
background-color: #f9f9f9;
transition: border-color 0.3s, box-shadow 0.3s, background-color 0.3s;
}

/* --- START BIRTHDAY FONT SIZE INCREASE (NEW) --- */
.form-group input[type="date"] {
    font-size: 1.15em; /* Linalakihan ang font size para sa Birthday */
    /* Maaari ring magdagdag ng padding o line-height kung kinakailangan */
}
/* --- END BIRTHDAY FONT SIZE INCREASE --- */


.form-group input:disabled {
background-color: #f1f1f1;
color: #777;
cursor: default;
border-color: #e0e0e0;
}

/* MODIFIED: Input Focus Styles */
.form-group input:focus:not(:disabled) {
border-color: #007bff;
box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
outline: none;
background-color: #fff;
}

/* NEW: Style for the inline error message (KEEP IN CASE YOU NEED IT LATER, BUT WE ARE HIDING IT NOW)*/
.error-message {
color: #dc3545; /* Pula */
font-size: 0.85em;
margin-top: 5px;
display: none; /* Default hidden */
}

/* Buttons */
.form-actions {
margin-top: 25px;
display: flex;
gap: 10px;
justify-content: flex-start;
}

.btn {
padding: 10px 18px;
border: none;
border-radius: 6px;
font-size: 0.95em;
cursor: pointer;
transition: background-color 0.3s ease;
}

.btn--edit {
background-color: #004a80;
color: white;
}
.btn--edit:hover {
background-color: #005a99;
}

/* Save Button (orange) */
.btn--save {
background-color: #E87722;
color: #fff;
}
.btn--save:hover {
background-color: #D66C1E;
}

/* Cancel Button (light gray, soft) */
.btn--cancel {
background-color: #e6e6e6;
color: #444;
}
.btn--cancel:hover {
background-color: #d5d5d5;
}

/* Success Modal */
#successModal {
display: none;
position: fixed;
top: 0;
left: 0;
width: 100%;
height: 100%;
background: rgba(0,0,0,0.4);
justify-content: center;
align-items: center;
z-index: 9999;
}

.modal-content {
background: white;
padding: 25px 35px;
border-radius: 10px;
text-align: center;
max-width: 350px;
box-shadow: 0 8px 16px rgba(0,0,0,0.2);
}

.modal-content i {
font-size: 50px;
color: #28a745;
margin-bottom: 10px;
}

.modal-content h3 {
margin-bottom: 8px;
color: #333;
}

.modal-content p {
color: #666;
margin-bottom: 15px;
}

.modal-content button {
background-color: #004a80;
color: white;
padding: 8px 20px;
border: none;
border-radius: 6px;
cursor: pointer;
}
.modal-content button:hover {
background-color: #005a99;
}

/* --- LOGOUT MODAL STYLES --- */
/* Base Modal Container for Logout */
#logoutModal {
display: none; /* Hidden by default */
position: fixed;
top: 0;
left: 0;
width: 100%;
height: 100%;
background: rgba(0, 0, 0, 0.4);
justify-content: center;
align-items: center;
z-index: 9999;
}

/* Content Box for Logout Modal */
#logoutModal .modal__content {
background-color: white; /* Used white instead of undefined var(--container-color) */
padding: 2.5rem;
border-radius: 8px;
box-shadow: 0 4px 15px rgba(0, 0, 0, .2);
text-align: center;
width: 90%;
max-width: 380px;
/* Pabababain ng 20px mula sa center */
transform: translateY(20px);
}

/* Title/Question for Logout Modal */
#logoutModal .modal__title {
color: #333; /* Used standard color instead of undefined var(--title-color1) */
margin-bottom: 2.5rem;
display: block;
font-size: 1.17em;
font-weight: bold;
margin-top: 0;
}

/* Action Buttons Container for Logout Modal */
#logoutModal .modal__actions {
display: flex;
justify-content: center;
gap: 15px;
margin-top: 1.5rem;
}

/* Common button styling for Logout Modal */
#logoutModal .modal__actions button {
font-size: 0.85em;
padding: 10px 20px;
border: none;
border-radius: 5px;
cursor: pointer;
font-weight: bold;
transition: background-color .3s ease, color .3s ease;
}

/* Cancel Button */
#logoutModal #cancelLogout {
background-color: #ddd;
color: #444; /* Standard text color */
}
#logoutModal #cancelLogout:hover {
background-color: #cccccc;
}

/* Log Out Button */
#logoutModal #confirmLogout {
background-color: #4040BF;
color: #fff;
}
#logoutModal #confirmLogout:hover {
background-color: #303099;
}
/* --- END LOGOUT MODAL STYLES --- */
</style>
</head>


<body>
<header class="header" id="header">
<nav class="nav container">
<a href="admin_dashboard.php?content=dashboard" class="nav__logo">
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
            <li class="menu__item"><a href="manage_groups.php" class="menu__link "><i class='bx bx-group'></i> Manage Groups</a></li>
            <li class="menu__item"><a href="admin_feedback_dashboard.php" class="menu__link "><i class='bx bx-star'></i> Feedback Overview</a></li>
            <li class="menu__item"><a href="Reports.php" class="menu__link "><i class='bx bx-file'></i> Reports</a></li>
            <li class="menu__item"><a href="concern.php?content=profile" class="menu__link " data-content="profile"><i class='bx bx-info-circle'></i> Issues & Concerns</a></li>
            <li class="menu__item"><a href="admin_profile.php" class="menu__link active"><i class='bx bx-user'></i> Profile</a></li>
            <li class="menu__item"><a href="javascript:void(0)" class="menu__link" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </aside>

<main class="dashboard__content">
<div class="profile-form-container">
    <h2 class="main-title"><i class='bx bx-user'></i> Admin Profile</h2>
    <p class="page-description">Review and update your personal and contact information.</p>

    
    <form id="profileForm" method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" 
                       value="<?= htmlspecialchars($firstName) ?>" required disabled>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" 
                       value="<?= htmlspecialchars($lastName) ?>" required disabled>
            </div>

            <div class="form-group">
                <label for="birthday">Birthday:</label>
                <input type="date" id="birthday" name="birthday" 
                       value="<?= $birthday ?>" required disabled>
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number:</label>
                <input type="tel" id="contact_number" name="contact_number"
                       value="<?= htmlspecialchars($contactNumber) ?>" required disabled
                       pattern="^\+971[0-9]{9}$"
                       oninvalid="this.setCustomValidity('Contact Number must start with +971 and be followed by exactly 9 digits (e.g., +971501234567)')"
                       oninput="this.setCustomValidity('')">
            </div>

            <div class="form-group form-group-full">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($emailAddress) ?>" required disabled>
            </div>

            <div class="form-group">
                <label for="address">Address:</label>
                <input type="text" id="address" name="address" 
                       value="<?= htmlspecialchars($address) ?>" disabled>
            </div>

            <div class="form-group">
                <label for="gender">Gender:</label>
                <select id="gender" name="gender" disabled>
                    <option value="Male" <?= $gender==='Male'?'selected':'' ?>>Male</option>
                    <option value="Female" <?= $gender==='Female'?'selected':'' ?>>Female</option>
                    <option value="Prefer Not to Say" <?= $gender==='Prefer Not to Say'?'selected':'' ?>>Prefer Not to Say</option>
                </select>
            </div>

            <div class="form-group">
                <label for="age">Age:</label>
                <input type="number" id="age" name="age" 
                       value="<?= htmlspecialchars($age) ?>" min="0" disabled>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" id="editBtn" class="btn btn--edit">Edit</button>
            <button type="submit" id="saveBtn" class="btn btn--save" style="display:none;">Save</button>
            <button type="button" id="cancelBtn" class="btn btn--cancel" style="display:none;">Cancel</button>
        </div>
    </form>
</div>
</main>

<div id="successModal">
    <div class="modal-content">
        <i class='bx bx-check-circle'></i>
        <h3>Profile Saved!</h3>
        <p>Your changes have been successfully saved.</p>
        <button onclick="closeModal('successModal')">OK</button>
    </div>
</div>


<div id="logoutModal">
<div class="modal__content">
<h3 class="modal__title">Are you sure you want to log out?</h3>
<div class="modal__actions">
<button id="cancelLogout">Cancel</button>
<button id="confirmLogout">Log Out</button>
</div>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- SIDEBAR DROPDOWN TOGGLE LOGIC ---
    const dropdownToggles = document.querySelectorAll('.has-dropdown > .menu__link');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            
            const parentItem = toggle.closest('.has-dropdown');
            parentItem.classList.toggle('active-dropdown');

            // Close other open dropdowns
            document.querySelectorAll('.has-dropdown.active-dropdown').forEach(item => {
                if (item !== parentItem) {
                    item.classList.remove('active-dropdown');
                }
            });
        });
    });

    // --- PROFILE EDIT/SAVE/CANCEL LOGIC ---
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const formFields = document.querySelectorAll('#profileForm input, #profileForm select');
    const contactNumberInput = document.getElementById('contact_number');
    
    let originalValues = {};
    
    // Save original values
    formFields.forEach(field => {
        originalValues[field.id] = field.value;
    });

    // Function to set cursor position
    function setCursorPosition(input, pos) {
        if (input.setSelectionRange) {
            input.focus();
            input.setSelectionRange(pos, pos);
        }
    }

    // Contact Number Input Validation
    if (contactNumberInput) {
        contactNumberInput.addEventListener('keydown', (e) => {
            if (contactNumberInput.disabled) return;

            const prefix = '+971';
            const start = contactNumberInput.selectionStart;
            const end = contactNumberInput.selectionEnd;
            const value = contactNumberInput.value;

            // Prevent deleting/editing the prefix (+971)
            if (
                (e.key === 'Backspace' && start <= prefix.length && start === end) ||
                (e.key === 'Delete' && start < prefix.length) ||
                (e.key === '-' || e.key === ' ')
            ) {
                e.preventDefault();
                if (e.key === 'Backspace') {
                    setCursorPosition(contactNumberInput, prefix.length);
                }
                return;
            }

            // Restrict input to numbers (0-9) only
            if (!e.metaKey && !e.ctrlKey && e.key.length === 1 && (e.key < '0' || e.key > '9')) {
                e.preventDefault();
                return;
            }

            // Limit length (13 chars: +971 and 9 digits)
            if (value.length >= 13 && start >= prefix.length && e.key.length === 1 && (e.key >= '0' && e.key <= '9') && start === end) {
                e.preventDefault();
            }
        });

        contactNumberInput.addEventListener('input', () => {
            const prefix = '+971';
            let value = contactNumberInput.value;

            // Clean non-digit characters except + at the start
            let cleanValue = prefix + value.substring(prefix.length).replace(/[^\d]/g, '');

            // Enforce 13 character limit
            if (cleanValue.length > 13) {
                cleanValue = cleanValue.substring(0, 13);
            }

            if (contactNumberInput.value !== cleanValue) {
                contactNumberInput.value = cleanValue;
            }

            // Keep cursor away from the prefix area
            if (contactNumberInput.selectionStart < prefix.length) {
                setCursorPosition(contactNumberInput, prefix.length);
            }
        });
    }

    // Edit Button
    editBtn.addEventListener('click', () => {
        formFields.forEach(field => field.disabled = false);
        editBtn.style.display = 'none';
        saveBtn.style.display = 'inline-block';
        cancelBtn.style.display = 'inline-block';
    });

    // Cancel Button
    cancelBtn.addEventListener('click', () => {
        formFields.forEach(field => {
            field.value = originalValues[field.id];
            field.disabled = true;
        });
        editBtn.style.display = 'inline-block';
        saveBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
    });

    // Form Submit
    document.getElementById('profileForm').addEventListener('submit', (e) => {
        // Validate contact number
        if (contactNumberInput && !contactNumberInput.checkValidity()) {
            setCursorPosition(contactNumberInput, 4);
            return;
        }

        // Form will submit normally to PHP for database update
        // The PHP redirect will show the success message
    });

    // --- LOGOUT MODAL LISTENERS ---
    const cancelLogoutBtn = document.getElementById('cancelLogout');
    const confirmLogoutBtn = document.getElementById('confirmLogout');

    if (cancelLogoutBtn) {
        cancelLogoutBtn.addEventListener('click', () => {
            closeModal('logoutModal');
        });
    }

    if (confirmLogoutBtn) {
        confirmLogoutBtn.addEventListener('click', () => {
            window.location.href = "landing_page2.html";
        });
    }
});

// --- MODAL FUNCTIONS (GLOBAL SCOPE) ---
function showModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.style.display = 'none';
    }
}

function showLogoutModal() {
    showModal('logoutModal');
}
</script>

</body>
</html>