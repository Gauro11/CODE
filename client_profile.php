<?php
// PHP code for client_profile.php
$firstName="Danelle Marie";
$lastName="Beltran";
$birthday="2003-06-11";
$contactNumber="+971809954545"; // Dapat 13 characters ang total (+971 at 9 digits)
$emailAddress="danellemarie6@gmail.com";
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
// --- START SIDEBAR DROPDOWN TOGGLE LOGIC (NEW) ---
document.addEventListener('DOMContentLoaded', () => {
    const dropdownToggles = document.querySelectorAll('.has-dropdown > .menu__link');

    dropdownToggles.forEach(toggle => {
        // I-prevent ang default action para hindi mag-redirect sa '#'
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Hanapin ang parent <li> element
            const parentItem = toggle.closest('.has-dropdown');
            
            // I-toggle ang active-dropdown class
            parentItem.classList.toggle('active-dropdown');

            // Optionally, i-close ang iba pang bukas na dropdowns
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

// Iimbak ang orihinal na values sa isang JS object
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

// Event listener for real-time input cleaning (removes any non-digit character that slips through)
contactNumberInput.addEventListener('input', () => {
const prefix = '+971';
let value = contactNumberInput.value;

// Clean non-digit characters except + at the start and enforce the prefix
let cleanValue = prefix + value.substring(prefix.length).replace(/[^\d]/g, '');

// Enforce 13 character limit
if (cleanValue.length > 13) {
cleanValue = cleanValue.substring(0, 13);
}

// If the displayed value needs cleaning, update it
if (contactNumberInput.value !== cleanValue) {
contactNumberInput.value = cleanValue;
}

// Keep cursor away from the prefix area
if (contactNumberInput.selectionStart < prefix.length) {
setCursorPosition(contactNumberInput, prefix.length);
}
});


editBtn.addEventListener('click', () => {
// I-store ang original contact number bago mag-edit
originalContactNumber = contactNumberInput.value; 
inputs.forEach(input => input.disabled = false);

// Wala nang automatic focus dito.

editBtn.style.display = 'none';
saveBtn.style.display = 'inline-block';
cancelBtn.style.display = 'inline-block';
});

cancelBtn.addEventListener('click', () => {
inputs.forEach(input => {
        // I-restore ang original value mula sa JS object
        input.value = originalValues[input.id];
        input.disabled = true;
});

// Siguraduhin na ang contact number ay restored din
contactNumberInput.value = originalContactNumber;

saveBtn.style.display = 'none';
cancelBtn.style.display = 'none';
editBtn.style.display = 'inline-block';
});

document.getElementById('profileForm').addEventListener('submit', e => {
// Ang checkValidity() at pattern attribute ang mag-ha-handle ng error pop-up.
if (!contactNumberInput.checkValidity()) {
// Ensure the cursor is set after the prefix if validation fails
setCursorPosition(contactNumberInput, 4);
return; // Let the browser handle the error display
}

e.preventDefault(); // I-prevent ang default submit para sa Success Modal (at server submit)

// Dito mo na isasagawa ang AJAX/fetch request para i-save ang data sa database.

// Simulate Success:
showModal('successModal');
inputs.forEach(input => input.disabled = true);
saveBtn.style.display = 'none';
cancelBtn.style.display = 'none';
editBtn.style.display = 'inline-block';
});

// Modal Functions (No Transition)
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

// Added the necessary function to specifically show the logout modal
function showLogoutModal() {
showModal('logoutModal');
}

// Event listeners for the logout modal buttons (using the new IDs)
document.getElementById('cancelLogout').addEventListener('click', () => {
closeModal('logoutModal');
});

// For demonstration, confirmLogout just closes the modal (You would add your actual logout logic here)
document.getElementById('confirmLogout').addEventListener('click', () => {
// window.location.href = "logout.php";  // Example logout redirect
closeModal('logoutModal'); // For now, just close the modal
});

</script>

</body>
</html>