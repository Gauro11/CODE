<?php
session_start();
require 'connection.php'; // your DB connection

// ✅ Ensure user is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

$client_email = $_SESSION['email'];
$success = false;
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['firstName'])) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $birthday = $_POST['birthday'];
    $contactNumber = $_POST['contactNumber'];
    $emailAddress = $_POST['emailAddress'];

    $stmt = $conn->prepare("UPDATE clients SET first_name=?, last_name=?, birthday=?, contact_number=?, email=? WHERE email=?");
    $stmt->bind_param("ssssss", $firstName, $lastName, $birthday, $contactNumber, $emailAddress, $client_email);
    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        // Update session email if changed
        $_SESSION['email'] = $emailAddress;
        // ✅ REDIRECT to prevent form resubmission on refresh (PRG pattern)
        header("Location: client_profile.php?success=1");
        exit;
    } else {
        $error = "Failed to update profile. Please try again.";
    }

    $stmt->close();
}

// Fetch user data from database
$stmt = $conn->prepare("SELECT first_name, last_name, birthday, contact_number, email FROM clients WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $client_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('User not found.'); window.location.href='login.php';</script>";
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

$firstName = $user['first_name'];
$lastName = $user['last_name'];
$birthday = $user['birthday'];
$contactNumber = $user['contact_number'];
$emailAddress = $user['email'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ALAZIMA - My Profile</title>
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="client_db.css">

<style>
/* --- SIDEBAR DROPDOWN STYLES (NEW) --- */
/* Hide dropdown menu by default */
.dropdown__menu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out, padding 0.3s ease-out;
    padding: 0;
    background-color: #f7f7f7;
}

/* Show dropdown menu when active */
.has-dropdown.active-dropdown .dropdown__menu {
    max-height: 200px;
    padding: 5px 0;
}

/* Rotate arrow icon when dropdown is active */
.has-dropdown.active-dropdown .arrow-icon {
    transform: rotate(180deg);
}

/* Basic styling for the arrow icon */
.arrow-icon {
    transition: transform 0.3s ease;
    margin-left: auto;
}

/* Adjustments for dropdown items */
.dropdown__menu .menu__link {
    padding-left: 30px;
    font-size: 0.95em;
}
/* --- END SIDEBAR DROPDOWN STYLES --- */

/* --- OVERRIDE FOR SIDEBAR HEIGHT (START) --- */
.dashboard__wrapper {
    min-height: 100vh;
    align-items: stretch;
}

.dashboard__sidebar {
    height: auto !important;
    min-height: 100%;
    overflow-y: auto;
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
    color: #007bff;
    margin-right: 8px;
    font-size: 1.3em;
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
    font-size: 1.0em;
    color: #555;
    background-color: #f9f9f9;
    transition: border-color 0.3s, box-shadow 0.3s, background-color 0.3s;
}

/* --- START BIRTHDAY FONT SIZE INCREASE (NEW) --- */
.form-group input[type="date"] {
    font-size: 1.15em;
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

/* NEW: Style for the inline error message */
.error-message {
    color: #dc3545;
    font-size: 0.85em;
    margin-top: 5px;
    display: none;
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
    display: none;
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
    background-color: white;
    padding: 2.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, .2);
    text-align: center;
    width: 90%;
    max-width: 380px;
    transform: translateY(20px);
}

/* Title/Question for Logout Modal */
#logoutModal .modal__title {
    color: #333;
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
    color: #444;
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

<li class="menu__item has-dropdown">
<a href="#" class="menu__link" data-content="history-parent"><i class='bx bx-history'></i> History <i class='bx bx-chevron-down arrow-icon'></i></a>
<ul class="dropdown__menu">
<li class="menu__item"><a href="HIS_one-time.php" class="menu__link">One-Time Service</a></li>
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

<li class="menu__item"><a href="client_profile.php" class="menu__link active" data-content="profile"><i class='bx bx-user'></i> My Profile</a></li>
<li class="menu__item"><a href="javascript:void(0)" class="menu__link" data-content="logout" onclick="showLogoutModal()"><i class='bx bx-log-out'></i> Logout</a></li>
</ul>
</aside>

<main class="dashboard__content">
<div class="profile-form-container">
<h2 class="main-title"><i class='bx bx-user'></i> My Profile</h2>
<p class="page-description">Review and update your personal and contact information.</p>

<form id="profileForm" method="POST">
<div class="form-grid">
<div class="form-group">
<label for="firstName">First name:</label>
<input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($firstName); ?>" required disabled>
</div>
<div class="form-group">
<label for="lastName">Last name:</label>
<input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($lastName); ?>" required disabled>
</div>
<div class="form-group">
<label for="birthday">Birthday:</label>
<input type="date" id="birthday" name="birthday" value="<?php echo $birthday; ?>" required disabled>
</div>
<div class="form-group">
<label for="contactNumber">Contact Number:</label>
<input type="tel" id="contactNumber" name="contactNumber"
value="<?php echo htmlspecialchars($contactNumber); ?>"
required disabled
pattern="^\+971[0-9]{9}$"
oninvalid="this.setCustomValidity('Contact Number must start with +971 and be followed by exactly 9 digits (e.g., +971501234567)')"
oninput="this.setCustomValidity('')">
</div>
<div class="form-group form-group-full">
<label for="emailAddress">Email Address:</label>
<input type="email" id="emailAddress" name="emailAddress" value="<?php echo htmlspecialchars($emailAddress); ?>" required disabled>
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
</div>

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
// Show success modal if profile was updated
<?php if ($success): ?>
window.addEventListener('DOMContentLoaded', () => {
    showModal('successModal');
});
<?php endif; ?>

// --- START SIDEBAR DROPDOWN TOGGLE LOGIC (NEW) ---
document.addEventListener('DOMContentLoaded', () => {
    const dropdownToggles = document.querySelectorAll('.has-dropdown > .menu__link');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            
            const parentItem = toggle.closest('.has-dropdown');
            parentItem.classList.toggle('active-dropdown');

            document.querySelectorAll('.has-dropdown.active-dropdown').forEach(item => {
                if (item !== parentItem) {
                    item.classList.remove('active-dropdown');
                }
            });
        });
    });
});
// --- END SIDEBAR DROPDOWN TOGGLE LOGIC ---


// Profile Edit/Save/Cancel Logic
const editBtn = document.getElementById('editBtn');
const saveBtn = document.getElementById('saveBtn');
const cancelBtn = document.getElementById('cancelBtn');
const inputs = document.querySelectorAll('#profileForm input');
const contactNumberInput = document.getElementById('contactNumber'); 

// Store original values
const originalValues = {
    firstName: "<?php echo htmlspecialchars($firstName); ?>",
    lastName: "<?php echo htmlspecialchars($lastName); ?>",
    birthday: "<?php echo htmlspecialchars($birthday); ?>",
    contactNumber: "<?php echo htmlspecialchars($contactNumber); ?>",
    emailAddress: "<?php echo htmlspecialchars($emailAddress); ?>"
};
let originalContactNumber = originalValues.contactNumber; 

// Function to set cursor position
function setCursorPosition(input, pos) {
    if (input.setSelectionRange) {
        input.focus();
        input.setSelectionRange(pos, pos);
    }
}

// EVENT LISTENER FOR CONTACT NUMBER: Enforce prefix and restrict non-digit input
contactNumberInput.addEventListener('keydown', (e) => {
    if (contactNumberInput.disabled) return;

    const prefix = '+971';
    const start = contactNumberInput.selectionStart;
    const end = contactNumberInput.selectionEnd;
    const value = contactNumberInput.value;

    // 1. Prevent deleting/editing the prefix (+971)
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

    // 2. Restrict input to numbers (0-9) only for new typing
    if (!e.metaKey && !e.ctrlKey && e.key.length === 1 && (e.key < '0' || e.key > '9')) {
        e.preventDefault();
        return;
    }

    // 3. Limit length (13 chars: +971 and 9 digits)
    if (value.length >= 13 && start >= prefix.length && e.key.length === 1 && (e.key >= '0' && e.key <= '9') && start === end) {
        e.preventDefault();
    }
});

// Event listener for real-time input cleaning
contactNumberInput.addEventListener('input', () => {
    const prefix = '+971';
    let value = contactNumberInput.value;

    let cleanValue = prefix + value.substring(prefix.length).replace(/[^\d]/g, '');

    if (cleanValue.length > 13) {
        cleanValue = cleanValue.substring(0, 13);
    }

    if (contactNumberInput.value !== cleanValue) {
        contactNumberInput.value = cleanValue;
    }

    if (contactNumberInput.selectionStart < prefix.length) {
        setCursorPosition(contactNumberInput, prefix.length);
    }
});


editBtn.addEventListener('click', () => {
    originalContactNumber = contactNumberInput.value; 
    inputs.forEach(input => input.disabled = false);

    editBtn.style.display = 'none';
    saveBtn.style.display = 'inline-block';
    cancelBtn.style.display = 'inline-block';
});

cancelBtn.addEventListener('click', () => {
    inputs.forEach(input => {
        input.value = originalValues[input.id];
        input.disabled = true;
    });

    contactNumberInput.value = originalContactNumber;

    saveBtn.style.display = 'none';
    cancelBtn.style.display = 'none';
    editBtn.style.display = 'inline-block';
});

// ✅ FIXED: Form submission now actually submits to server
document.getElementById('profileForm').addEventListener('submit', e => {
    if (!contactNumberInput.checkValidity()) {
        setCursorPosition(contactNumberInput, 4);
        e.preventDefault();
        return;
    }

    // Enable all inputs before submitting so their values are sent
    inputs.forEach(input => input.disabled = false);
    
    // Let the form submit naturally (page will reload and show success modal via PHP)
});


// Modal Functions
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

// Event listeners for the logout modal buttons
document.getElementById('cancelLogout').addEventListener('click', () => {
    closeModal('logoutModal');
});

document.getElementById('confirmLogout').addEventListener('click', () => {
    window.location.href = "logout.php";
});

</script>

</body>
</html>