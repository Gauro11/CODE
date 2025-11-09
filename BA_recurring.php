<?php
// File: BA_recurring.php - CORRECTED CODE with FIXED Flatpickr Reset Logic

// This file contains PHP and HTML structure, and the main logic is handled by JavaScript.
// Note: Actual PHP backend code for saving data is-not included, only the frontend structure and JS logic.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALAZIMA - Client Dashboard</title>
    <link rel="icon" href="site_icon.png" type="image/png">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="client_db.css">
    <style>
        /* Updated CSS for the 'Back' and 'Cancel' button */
        .btn--secondary {
            background-color: #e0e0e0; /* Light grey */
            color: #333; /* Dark text for contrast */
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .btn--secondary:hover {
            background-color: #b0b0b0; /* Darker grey on hover */
            color: #333;
        }

        /* Modal general styling */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal.show {
            display: flex;
        }
        .modal__content {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            max-width: 400px;
            text-align: center;
        }

        /* Grid fix for form rows */
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .form-row .form-group {
            flex: 1;
            min-width: 200px;
            display: flex;
            flex-direction: column;
        }
        .form-row .form-group.full-width {
            flex: 100%;
        }

        /* Align service card to the left */
        .service-options {
            text-align: left; /* Aligns inline-block elements to the left */
        }
        .service-card {
            display: inline-block; /* Allows alignment */
        }

        /* --- VALIDATION STYLES --- */
        .is-invalid-group {
            border: 1px solid red;
            border-radius: 8px;
            padding: 10px;
        }
        .error-message {
            color: red;
            font-size: 0.8em;
            margin-top: 0.25rem;
            display: none;
        }
        .error-message.show {
            display: block;
        }
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

            <li class="menu__item has-dropdown open">
                <a href="#" class="menu__link active-parent" data-content="book-appointment-parent"><i class='bx bx-calendar'></i> Book Appointment <i class='bx bx-chevron-down arrow-icon'></i></a>
                <ul class="dropdown__menu">
                    <li class="menu__item"><a href="BA_one-time.php" class="menu__link">One-Time Service</a></li>
                    <li class="menu__item"><a href="BA_recurring.php" class="menu__link active">Recurring Service</a></li>
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

            <li class="menu__item"><a href="client_profile.php" class="menu__link" data-content="profile"><i class='bx bx-user'></i> My Profile</a></li>
            <li class="menu__item"><a href="#" class="menu__link" data-content="logout"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </aside>

    <main class="dashboard__content">
        <section id="recurring-service-content" class="content__section active">
            <h2 class="section__title">Book Recurring Service</h2>
            <div class="booking__form">

              <form action="my_savebookings.php" method="POST" enctype="multipart/form-data">

                    
                    <div id="bookingFormSectionRecurring">
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="serviceType">Service Type</label>
                                <div class="service-options">
                                    <button type="button" class="service-card" id="generalCleaningBtn" data-service-type="General Cleaning">
                                        <i class="fa-solid fa-broom"></i>
                                        <h4>General Cleaning</h4>
                                        <p>Your standard, routine cleaning to maintain cleanliness.</p>
                                    </button>
                                </div>
                                <input type="hidden" id="serviceTypeHiddenRecurring" name="serviceType" value="" required>
                                <div id="serviceTypeErrorMessage" class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="clientTypeRecurring">Client Type</label>
                                <select id="clientTypeRecurring" name="clientType" required>
                                    <option value="">Select a service type first...</option>
                                    <option value="Residential">Residential</option>
                                    <option value="Offices">Offices</option>
                                </select>
                                <div id="clientTypeErrorMessage" class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="addressRecurring">Address</label>
                                <input type="text" id="addressRecurring" name="address" placeholder="Enter full address" required>
                                <div id="addressErrorMessage" class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="frequency">Frequency</label>
                                <select id="frequency" name="frequency" required disabled>
                                    <option value="">Select client type first...</option>
                                    <option value="Weekly">Weekly</option>
                                    <option value="Bi-Weekly">Bi-Weekly</option>
                                    <option value="Monthly">Monthly</option>
                                </select>
                                <div id="frequencyErrorMessage" class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="preferredDay">Preferred Day of the Week</label>
                                <select id="preferredDay" name="preferredDay" required disabled>
                                    <option value="">Select frequency first...</option>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                    <option value="Sunday">Sunday</option>
                                </select>
                                <div id="preferredDayErrorMessage" class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-row">
    <div class="form-group">
        <label for="startDate">Start Date</label>
        <input type="text" id="startDate" name="startDate" required disabled placeholder="Please select a preferred day first.">
        <div id="startDateErrorMessage" class="error-message"></div>
    </div>
    <div class="form-group">
        <label for="endDate">End Date</label>
        <input type="text" id="endDate" name="endDate" required disabled placeholder="Please select a start date first.">
        <div id="endDateErrorMessage" class="error-message"></div>
    </div>
</div>

<!-- Hidden input for estimated sessions (moved outside form-row) -->
<input type="hidden" id="estimatedSessionsHidden" name="estimated_sessions" value="">

<div class="form-row">
                            <div class="form-group">
                                <label for="bookingTimeRecurring">Preferred Time</label>
                                <input type="time" id="bookingTimeRecurring" name="bookingTime" required disabled>
                                <div id="bookingTimeErrorMessage" class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="duration">Duration (Hours)</label>
                                <select id="duration" name="duration" required disabled>
                                    <option value="">Select a time first...</option>
                                    <option value="2">2 hrs</option>
                                    <option value="3">3 hrs</option>
                                    <option value="4">4 hrs</option>
                                    <option value="5">5 hrs</option>
                                    <option value="6">6 hrs</option>
                                    <option value="7">7 hrs</option>
                                    <option value="8">8 hrs</option>
                                </select>
                                <div id="durationErrorMessage" class="error-message"></div>
                            </div>
                        </div>
                        <div class="form-row form-section-gap">
                            <div class="form-group full-width">
                                <label>Property Layout</label>
                                <small class="form-text text-muted">Please specify the unit size/type, number of floors, and room breakdown per floor, and upload up to 3 images/videos to help us understand the actual layout.</small>

                                <div class="side-by-side-container">
                                    <textarea id="propertyLayout" name="propertyLayout" rows="8" placeholder="Ex. Studio Type – 1 Floor: 1 Room, 1 Bathroom" required></textarea>
                                    <div id="propertyLayoutErrorMessage" class="error-message"></div>

                                    <div class="media-upload-container">
                                        <div class="upload-field">
                                            <label for="mediaUpload1">Image/Video 1 (Optional)</label>
                                            <input type="file" id="mediaUpload1" name="mediaUpload[]" accept="image/*,video/*">
                                        </div>
                                        <div class="upload-field">
                                            <label for="mediaUpload2">Image/Video 2 (Optional)</label>
                                            <input type="file" id="mediaUpload2" name="mediaUpload[]" accept="image/*,video/*">
                                        </div>
                                        <div class="upload-field">
                                            <label for="mediaUpload3">Image/Video 3 (Optional)</label>
                                            <input type="file" id="mediaUpload3" name="mediaUpload[]" accept="image/*,video/*">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label>Does the client require cleaning materials?</label>
                                <div class="radio-group">
                                    <input type="radio" id="materialsYesRecurring" name="cleaningMaterials" value="yes" required>
                                    <label for="materialsYesRecurring">Yes - 40 AED / hr</label>

                                    <input type="radio" id="materialsNoRecurring" name="cleaningMaterials" value="no" required>
                                    <label for="materialsNoRecurring">No - 35 AED / hr</label>
                                </div>
                                <div id="cleaningMaterialsErrorMessage" class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-row" id="materialsNeededContainer" style="display: none;">
                            <div class="form-group full-width">
                                <label for="materialsNeeded">If yes, what materials are needed?</label>
                                <input type="text" id="materialsNeeded" name="materialsNeeded" placeholder="e.g., mop, disinfectant, vacuum cleaner">
                                <div id="materialsNeededErrorMessage" class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="additionalRequest" id="additionalRequestLabel">Additional Request (Optional)</label>
                                <textarea id="additionalRequest" name="additionalRequest" rows="4" placeholder="e.g., Use organic products, focus on kitchen"></textarea>
                            </div>
                        </div>

                        <div class="booking-summary">
                            <p class="summary-text">Estimated Price:</p>
                            <span id="recurringPrice" class="price-display">AED 0</span>
                        </div>
                    </div> 
                    
                    <div id="waiverSectionRecurring" style="display:none;">
                        <div class="waiver-box" style="border:1px solid #ccc; border-radius:6px; margin-bottom:1rem;">
                            <iframe src="waiver.html" style="width:100%; height:500px; border:none;"></iframe>
                        </div>
                        <label>
                            <input type="checkbox" id="agreeWaiverRecurring"> I have read and agree to the terms
                        </label>
                        <br><br>
                    </div>

                    <div class="form__actions" id="formActionsPrimary">
                        <button type="button" class="btn btn--secondary" id="backToFormRecurringBtn" style="display:none;">Back</button>
                        <button type="button" class="btn btn--success" id="nextToWaiverRecurringBtn">Next</button>
                        <button type="submit" class="btn btn--success" id="finalSubmitRecurringBtn" style="display:none;">Submit Booking</button>
                    </div>
                </form>

            </div>
        </section>
    </main>
</div>

<div id="waiverRequiredModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Waiver Agreement Required</h3>
        <p>Please agree to the waiver before submitting your booking.</p>
        <div class="modal__actions">
            <button class="btn btn--primary" id="closeWaiverModal">OK</button>
        </div>
    </div>
</div>

<div id="requiredFieldsModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Please fill out all required fields.</h3>
        <div class="modal__actions">
            <button class="btn btn--primary" id="closeRequiredFieldsModal">OK</button>
        </div>
    </div>
</div>

<div id="logoutModal" class="modal">
    <div class="modal__content">
        <h3 class="modal__title">Are you sure you want to log out?</h3>
        <div class="modal__actions">
            <button id="cancelLogout" class="btn btn--secondary">Cancel</button>
            <button id="confirmLogout" class="btn btn--primary">Log Out</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    
document.addEventListener('DOMContentLoaded', () => {
    // --- SIDEBAR TOGGLE ---
    const navToggle = document.getElementById('nav-toggle');
    const sidebar = document.querySelector('.dashboard__sidebar');
    const body = document.body;

    if (navToggle) {
        navToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show-sidebar');
            body.classList.toggle('sidebar-open');
        });
    }

    document.addEventListener('click', (event) => {
        if (sidebar.classList.contains('show-sidebar') &&
            !sidebar.contains(event.target) &&
            !navToggle.contains(event.target)) {
            sidebar.classList.remove('show-sidebar');
            body.classList.remove('sidebar-open');
        }
    });

    // --- FORM ELEMENTS ---
    const bookingFormSection = document.getElementById("bookingFormSectionRecurring");
    const generalCleaningBtn = document.getElementById("generalCleaningBtn");
    const clientTypeSelect = document.getElementById("clientTypeRecurring");
    const serviceTypeHidden = document.getElementById("serviceTypeHiddenRecurring");
    const frequencySelect = document.getElementById("frequency");
    const preferredDaySelect = document.getElementById("preferredDay");
    const startDateInput = document.getElementById("startDate");
    const endDateInput = document.getElementById("endDate"); // Added End Date
    const bookingTimeInput = document.getElementById("bookingTimeRecurring");
    const durationSelect = document.getElementById("duration");
    const materialsRadios = document.querySelectorAll('input[name="cleaningMaterials"]');
    const materialsYes = document.getElementById('materialsYesRecurring');
    const materialsNo = document.getElementById('materialsNoRecurring');
    const materialsNeededContainer = document.getElementById("materialsNeededContainer");
    const materialsNeededInput = document.getElementById("materialsNeeded");
    const propertyLayoutTextarea = document.getElementById('propertyLayout');
    const addressInput = document.getElementById('addressRecurring');
    const timeErrorMessage = document.getElementById("bookingTimeErrorMessage");
    const endDateErrorMessage = document.getElementById("endDateErrorMessage"); // Get the End Date error element

    const recurringBookingForm = document.getElementById("recurringBookingForm");
    const nextToWaiverBtn = document.getElementById("nextToWaiverRecurringBtn");
    const backToFormBtn = document.getElementById("backToFormRecurringBtn");
    const finalSubmitBtn = document.getElementById("finalSubmitRecurringBtn");
    const waiverSection = document.getElementById("waiverSectionRecurring");
    const agreeWaiverCheckbox = document.getElementById("agreeWaiverRecurring");
    const waiverRequiredModal = document.getElementById("waiverRequiredModal");
    const closeWaiverModal = document.getElementById("closeWaiverModal");
    const requiredFieldsModal = document.getElementById("requiredFieldsModal");
    const closeRequiredFieldsModal = document.getElementById("closeRequiredFieldsModal");

    // --- UTILS ---
    function resetDependentFields(startField, ...fieldsToReset) {
        fieldsToReset.forEach(field => {
            if (field.tagName === 'SELECT') {
                field.value = "";
                field.disabled = true;
                if (field.id === 'frequency') field.options[0].textContent = "Select client type first...";
                if (field.id === 'preferredDay') field.options[0].textContent = "Select frequency first...";
                if (field.id === 'duration') field.options[0].textContent = "Select a time first...";
            } else if (field.type === 'time' || field.type === 'text' || field.type === 'date') {
                field.value = "";
                field.disabled = true;
            }
        });
    }

    // Function to add invalid class
    function addInvalidClass(element) {
        const parentGroup = element.closest('.form-group');
        if (parentGroup) {
            parentGroup.classList.add('is-invalid-group');
        }
    }

    // Function to remove invalid class
    function removeInvalidClass(element) {
        const parentGroup = element.closest('.form-group');
        if (parentGroup) {
            parentGroup.classList.remove('is-invalid-group');
        }
    }
    
    // --- SERVICE TYPE BUTTON LOGIC ---
    generalCleaningBtn.addEventListener("click", () => {
        serviceTypeHidden.value = "General Cleaning";
        removeInvalidClass(generalCleaningBtn.closest('.form-group'));
        clientTypeSelect.disabled = false;
        clientTypeSelect.selectedIndex = 0;
        resetDependentFields(clientTypeSelect, frequencySelect, preferredDaySelect, startDateInput, endDateInput, bookingTimeInput, durationSelect);
        startDateInput.placeholder = "Please select a preferred day first.";
        endDateInput.placeholder = "Please select a start date first.";
        generalCleaningBtn.classList.add("active");
        document.querySelector('.service-options').querySelectorAll('.service-card').forEach(card => {
            if (card !== generalCleaningBtn) card.classList.remove('active');
        });
    });

    // --- ENABLE FIELDS BASED ON SELECTION ---
    clientTypeSelect.addEventListener("change", () => {
        const hasClientType = clientTypeSelect.value !== "";
        if (hasClientType) {
            removeInvalidClass(clientTypeSelect);
            frequencySelect.disabled = false;
            frequencySelect.options[0].textContent = "Select frequency";
        } else {
            resetDependentFields(clientTypeSelect, frequencySelect, preferredDaySelect, startDateInput, endDateInput, bookingTimeInput, durationSelect);
            frequencySelect.options[0].textContent = "Select client type first...";
        }
    });

    frequencySelect.addEventListener("change", () => {
        const hasFrequency = frequencySelect.value !== "";
        if (hasFrequency) {
            removeInvalidClass(frequencySelect);
            preferredDaySelect.disabled = false;
            preferredDaySelect.options[0].textContent = "Select day";
        } else {
            resetDependentFields(frequencySelect, preferredDaySelect, startDateInput, endDateInput, bookingTimeInput, durationSelect);
            preferredDaySelect.options[0].textContent = "Select frequency first...";
        }
        validateDateRange(); // Re-validate when frequency changes
        updateSessionCountHelper();
        
        // When frequency changes, clear dates and reset flatpickr limits
        startDateInput.value = "";
        endDateInput.value = "";
        fpEnd.set('enable', [() => true]); // Reset to enable all days
        fpEnd.set('minDate', 'today'); // <<< FIX: Reset minDate to 'today'
        fpEnd.set('defaultDate', null); // <<< FIX: Clear defaultDate so it opens to today's month
        
        // **IMPORTANT:** If Preferred Day is already selected, re-trigger the setting of Start Date
        if(preferredDaySelect.value) {
             enableFlatpickrForPreferredDay(preferredDaySelect.value); // This will set Start Date
        } else
         {
             // If preferred day is also empty, the flatpickr remains disabled until preferredDay is selected
        }
    });

    preferredDaySelect.addEventListener("change", function () {
        const chosenDay = this.value;
        const hasPreferredDay = chosenDay !== "";
        if (hasPreferredDay) {
            removeInvalidClass(preferredDaySelect);
            
            // Enable Start Date
            startDateInput.disabled = false;
            startDateInput.style.pointerEvents = 'auto';
            startDateInput.style.backgroundColor = 'transparent';
            startDateInput.placeholder = "Select your desired date";
            
            // Disable/Reset dependent fields 
            resetDependentFields(startDateInput, endDateInput, bookingTimeInput, durationSelect);
            endDateInput.placeholder = "Please select a start date first.";
            
            enableFlatpickrForPreferredDay(chosenDay); // This will set Start Date
        } else {
            resetDependentFields(preferredDaySelect, startDateInput, endDateInput, bookingTimeInput, durationSelect);
            startDateInput.placeholder = "Please select a preferred day first.";
            endDateInput.placeholder = "Please select a start date first.";
            fpStart.set('disable', []);
            fpStart.clear();
        }
    });

    startDateInput.addEventListener("change", () => {
        const hasStartDate = startDateInput.value !== "";
        if (hasStartDate) {
            removeInvalidClass(startDateInput);
            
            // Enable End Date fields
            endDateInput.disabled = false;
            endDateInput.style.pointerEvents = 'auto';
            endDateInput.style.backgroundColor = 'transparent';
            endDateInput.placeholder = "Select your end date";
            
            // --- LOGIC FOR MIN DATE & INTERVAL RESTRICTIONS ---
            const freq = frequencySelect.value;
            const rawStartDate = new Date(startDateInput.value);
            // Normalize start date to ensure accurate time calculations
            const startDate = new Date(rawStartDate.getFullYear(), rawStartDate.getMonth(), rawStartDate.getDate());
            
            // Kunin ang preferred day number (0-6)
            const preferredDayNumber = startDate.getDay(); 
            
            let minEndDate;
            let defaultEndDate = null; // Variable for the nearest applicable date
            let enableFunction;

            // Function to restrict End Date to the Preferred Day
            function isPreferredDay(date) {
                return date.getDay() === preferredDayNumber;
            }
            
            // Set minDate to Start Date (General rule: End Date cannot be before Start Date)
            minEndDate = startDateInput.value;
            
            if (freq === 'Weekly') {
                // For Weekly, the minimum end date must be 7 days AFTER the start date.
                let minEndWeekly = new Date(startDate.getTime());
                minEndWeekly.setDate(minEndWeekly.getDate() + 7);
                minEndDate = minEndWeekly.toISOString().split('T')[0];
                defaultEndDate = minEndDate; // Set default to 7 days after

                // Enable only dates that are on the preferred day AND are at least 7 days after the Start Date.
                enableFunction = [
                    function (date) {
                        const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
                        return isPreferredDay(date) && dateOnly.getTime() >= new Date(minEndDate).getTime();
                    }
                ];
                
            } else if (freq === 'Bi-Weekly') {
                const intervalDays = 14; 
                let minEndBiWeekly = new Date(startDate.getTime());
                minEndBiWeekly.setDate(minEndBiWeekly.getDate() + intervalDays);
                minEndDate = minEndBiWeekly.toISOString().split('T')[0];
                defaultEndDate = minEndDate; // Set default to 14 days after

                // STRICT Bi-Weekly INTERVAL LOGIC
                enableFunction = [
                    function (date) {
                        const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
                        const timeDiff = dateOnly.getTime() - startDate.getTime();
                        // Day difference calculation (must be accurate to avoid DST issues)
                        const dayDiff = Math.round(timeDiff / (1000 * 3600 * 24)); 
                        
                        // 1. Must be on or after the minimum date (14 days after Start Date)
                        // 2. The day difference must be an exact multiple of 14 (14, 28, 42, ...)
                        return dayDiff >= intervalDays && dayDiff % intervalDays === 0;
                    }
                ];

            } else { // Monthly
                 // Minimum End Date is 1 Month after Start Date
                 let minEndMonthly = new Date(startDate.getTime());
                 minEndMonthly.setMonth(minEndMonthly.getMonth() + 1);
                 minEndDate = minEndMonthly.toISOString().split('T')[0];
                 defaultEndDate = minEndDate; // Set default to 1 month after

                 // STRICT Monthly INTERVAL Logic: Must be the same day of the month, N months later
                 const startDayOfMonth = startDate.getDate();
                
                 enableFunction = [
                    function (date) {
                        const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
                        
                        // Rule 1: Must be on the same day of the month as the Start Date 
                        if (dateOnly.getDate() !== startDayOfMonth) {
                            return false; 
                        }
                        
                        // Rule 2: Must be an exact number of months after Start Date (at least 1)
                        const monthDiff = (dateOnly.getFullYear() - startDate.getFullYear()) * 12 + (dateOnly.getMonth() - startDate.getMonth());
                        
                        return monthDiff >= 1;
                    }
                 ];
            }

            // --- APPLY RESTRICTIONS AND AUTO-ADJUST CALENDAR VIEW (FIXED) ---
            fpEnd.set('minDate', minEndDate);
            fpEnd.set('enable', enableFunction);
            
            // **FIX: Use `defaultDate` to make the calendar view jump to the earliest valid date**
            if (defaultEndDate) {
                // Set the default date for the picker's view (triggers auto-navigation)
                fpEnd.set('defaultDate', defaultEndDate);
                
                // Clear the actual input value to force user selection, 
                // but the picker will open to the right month 
                fpEnd.clear(); 
                endDateInput.value = "";
                
            } else {
                fpEnd.clear(); 
                endDateInput.value = ""; 
            }
            
            // Reset dependent fields as End Date is now cleared
            resetDependentFields(startDateInput, bookingTimeInput, durationSelect);
            
            // Re-check validation and helper count (will be hidden since End Date is cleared)
            validateDateRange(); 

        } else {
            resetDependentFields(startDateInput, endDateInput, bookingTimeInput, durationSelect);
            endDateInput.placeholder = "Please select a start date first.";
            
            // IMPORTANT: Reset the enable property to allow all days when start date is cleared
            fpEnd.set('enable', [() => true]); 
            fpEnd.set('minDate', 'today');
            fpEnd.set('defaultDate', null); // FIX: Clear defaultDate
        }
    });

    endDateInput.addEventListener("change", () => {
        const hasEndDate = endDateInput.value !== "";
        
        // Always remove the group border (if it was applied by another validation)
        removeInvalidClass(endDateInput); 

        if (hasEndDate) {
            // Re-check date range to make sure it's valid before enabling next field
            if(validateDateRange()) {
                bookingTimeInput.disabled = false;
            } else {
                resetDependentFields(endDateInput, bookingTimeInput, durationSelect);
            }
        } else {
            resetDependentFields(endDateInput, bookingTimeInput, durationSelect);
        }
        updateSessionCountHelper();
    });
    
    // ** MODIFIED VALIDATION LOGIC (ONLY GENERAL CHECKS) **
    function validateDateRange() {
        const freq = frequencySelect.value;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        // Clear previous error first
        endDateErrorMessage.textContent = "";
        endDateErrorMessage.classList.remove("show");
        removeInvalidClass(endDateInput); 

        if (!startDate || !endDate) {
            return true;
        }
        
        // 1. Weekly/Bi-Weekly/Monthly Start Date == End Date Check (Essential for ensuring recurring)
        if (freq === 'Weekly' || freq === 'Bi-Weekly' || freq === 'Monthly') {
            if (startDate === endDate) {
                const serviceType = freq === 'Weekly' ? 'Weekly' : freq === 'Bi-Weekly' ? 'Bi-Weekly' : 'Monthly';
                addInvalidClass(endDateInput);
                endDateErrorMessage.textContent = `For ${serviceType} recurring service, the End Date must be after the Start Date.`;
                endDateErrorMessage.classList.add("show");
                return false;
            }
        }

        // 2. Existing date comparison validation (End Date cannot be before Start Date)
        const start = new Date(startDate);
        const end = new Date(endDate);
        if (end < start) {
            addInvalidClass(endDateInput);
            endDateErrorMessage.textContent = "End Date cannot be before Start Date.";
            endDateErrorMessage.classList.add("show");
            return false;
        }
        
        return true;
    }
    // ** END OF MODIFIED VALIDATION LOGIC **

    // --- SESSION COUNT HELPER (UNCHANGED) ---
   // --- SESSION COUNT HELPER (MODIFIED) ---
function updateSessionCountHelper() {
    const startDateStr = startDateInput.value;
    const endDateStr = endDateInput.value;
    const frequency = frequencySelect.value;
    const helperElementId = 'sessionCountHelper';
    const hiddenSessionInput = document.getElementById('estimatedSessionsHidden'); // Add this line

    // Find or create the helper element
    let helperElement = endDateInput.parentNode.querySelector('#' + helperElementId);
    if (!helperElement) {
        helperElement = document.createElement('small');
        helperElement.className = 'session-count-helper';
        helperElement.id = helperElementId;
        helperElement.style.cssText = 'color: rgb(85, 85, 85); margin-top: 0.25rem; display: block;'; 
        endDateInput.parentNode.appendChild(helperElement);
    }

    // Hide if required conditions are not met or if validation fails
    if (!startDateStr || !endDateStr || !frequency || !validateDateRange()) {
        helperElement.style.display = 'none';
        if (hiddenSessionInput) hiddenSessionInput.value = ''; // Clear hidden value
        return;
    }

    // Normalize dates to start of day for accurate comparison
    const startDate = new Date(startDateStr + 'T00:00:00');
    const endDate = new Date(endDateStr + 'T00:00:00');
    let sessionCount = 0;

    if (frequency === 'Weekly' || frequency === 'Bi-Weekly') {
        const intervalDays = frequency === 'Weekly' ? 7 : 14;
        let currentDate = new Date(startDate.getTime());
        
        while (currentDate.getTime() <= endDate.getTime()) {
            sessionCount++;
            currentDate.setDate(currentDate.getDate() + intervalDays);
        }
        
    } else if (frequency === 'Monthly') {
        const startDay = startDate.getDate();
        let currentDate = new Date(startDate.getTime());
        
        if(currentDate.getTime() <= endDate.getTime()) {
            sessionCount = 1; 
        } else {
            sessionCount = 0;
        }
        
        let nextDate = new Date(startDate.getTime());

        while (true) {
            nextDate.setMonth(nextDate.getMonth() + 1); 
            
            let tempNextDate = new Date(nextDate.getFullYear(), nextDate.getMonth(), startDay);

            if (tempNextDate.getTime() > endDate.getTime() || nextDate.getMonth() > endDate.getMonth() + 12) {
                break;
            }

            if (tempNextDate.getTime() >= startDate.getTime() && tempNextDate.getTime() <= endDate.getTime()) {
                if (tempNextDate.getDate() === startDay) {
                    sessionCount++;
                }
            } else if (nextDate.getTime() <= endDate.getTime() && nextDate.getDate() === startDay) {
                sessionCount++;
            }

            if (nextDate.getTime() > endDate.getTime()) {
                break;
            }
        }
        
    } else {
        helperElement.style.display = 'none';
        if (hiddenSessionInput) hiddenSessionInput.value = ''; // Clear hidden value
        return;
    }

    // Final safety check
    if (sessionCount === 0 && startDate.getTime() <= endDate.getTime()) {
        sessionCount = 1;
    }

    helperElement.textContent = `Estimated Sessions: ${sessionCount}`;
    helperElement.style.display = 'block';
    
    // UPDATE HIDDEN INPUT WITH SESSION COUNT (Add this)
    if (hiddenSessionInput) {
        hiddenSessionInput.value = sessionCount;
    }
}


    bookingTimeInput.addEventListener("change", () => {
        const hasBookingTime = bookingTimeInput.value !== "";
        if (hasBookingTime) {
            removeInvalidClass(bookingTimeInput);
            durationSelect.disabled = false;
        } else {
            resetDependentFields(bookingTimeInput, durationSelect);
        }
        updateDurationHelper();
    });
    
    // --- REAL-TIME TIME VALIDATION AND GUIDE
    function validateAndShowTimeGuide() {
        const timeValue = bookingTimeInput.value;
        const timeErrorMessage = document.getElementById("bookingTimeErrorMessage");

        timeErrorMessage.textContent = "";
        timeErrorMessage.classList.remove("show");

        if (timeValue === '') {
            return; 
        }

        const [hours, minutes] = timeValue.split(':').map(Number);
        const totalMinutes = hours * 60 + minutes;
        
        const isValidTime = totalMinutes >= 540 && totalMinutes <= 1080; // 9:00 AM to 6:00 PM

        if (!isValidTime) {
            timeErrorMessage.textContent = "Please choose between 9 AM and 6 PM";
            timeErrorMessage.classList.add("show");
        }
    }

    bookingTimeInput.addEventListener("input", validateAndShowTimeGuide);
    bookingTimeInput.addEventListener("change", validateAndShowTimeGuide);


    // --- FLATPICKR & TIME VALIDATION ---
    const fpStart = flatpickr(startDateInput, {
        dateFormat: "Y-m-d",
        minDate: "today",
        disable: []
    });
    
    const fpEnd = flatpickr(endDateInput, {
        dateFormat: "Y-m-d",
        minDate: "today",
        enable: [() => true] // Initialize with enable all days
    });

    function getPreferredDayNumber(day) {
        const days = { Sunday: 0, Monday: 1, Tuesday: 2, Wednesday: 3, Thursday: 4, Friday: 5, Saturday: 6 };
        return days[day] ?? null;
    }

    function enableFlatpickrForPreferredDay(chosenDay) {
        const preferredDayNum = getPreferredDayNumber(chosenDay);
        
        // Restriction for Start Date (must be on preferred day)
        fpStart.set('disable', [
            function (date) {
                return date.getDay() !== preferredDayNum;
            }
        ]);
        
        // Auto-select the next available preferred day
        let nextDate = new Date();
        // Set to next preferred day, but ensure it's not today if today is the preferred day and time is past
        while (nextDate.getDay() !== preferredDayNum || nextDate.getTime() < new Date().getTime() - (24 * 60 * 60 * 1000)) {
            nextDate.setDate(nextDate.getDate() + 1);
        }
        
        const today = new Date();
        today.setHours(0,0,0,0);
        if (nextDate.getTime() < today.getTime()) {
            nextDate.setDate(nextDate.getDate() + 7);
        }
        
        // **FIX 1: Set the Start Date value WITHOUT triggering the change event (false)**
        fpStart.setDate(nextDate.toISOString().split('T')[0], false); 
        
        // **FIX 2: Ensure End Date is fully reset, cleared, and DISABLED, waiting for user interaction.**
        endDateInput.disabled = true; 
        endDateInput.value = "";
        endDateInput.placeholder = "Please select a start date first.";
        fpEnd.clear(); 
        fpEnd.set('defaultDate', null); // <<< FIX: I-reset ang view ng End Date picker
        fpEnd.set('minDate', 'today'); // <<< ITO ANG FINAL FIX para bumalik sa current month
        fpEnd.set('enable', [() => true]); // I-reset ang enable function
    }
    
    function validateTimeRange() {
        const timeValue = bookingTimeInput.value;
        const [hours, minutes] = timeValue.split(':').map(Number);
        const totalMinutes = hours * 60 + minutes;
        
        return totalMinutes >= 540 && totalMinutes <= 1080; // 9:00 AM to 6:00 PM
    }

    // --- REAL-TIME VALIDATION REMOVAL ---
    addressInput.addEventListener('input', () => {
        if (addressInput.value.trim() !== '') {
            removeInvalidClass(addressInput);
        }
    });

    durationSelect.addEventListener('change', () => {
        if (durationSelect.value.trim() !== '') {
            removeInvalidClass(durationSelect);
        }
    });

    propertyLayoutTextarea.addEventListener('input', () => {
        if (propertyLayoutTextarea.value.trim() !== '') {
            removeInvalidClass(propertyLayoutTextarea);
        }
    });

    materialsRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            const materialsRadioGroup = materialsYes.closest('.form-group');
            if (document.querySelector('input[name="cleaningMaterials"]:checked')) {
                removeInvalidClass(materialsRadioGroup);
            }

            if (materialsYes.checked) {
                materialsNeededContainer.style.display = 'block';
                materialsNeededInput.required = true;
            } else {
                materialsNeededContainer.style.display = 'none';
                materialsNeededInput.required = false;
                removeInvalidClass(materialsNeededInput); // Remove red border when not needed
            }
        });
    });

    // --- DURATION COMPLETION TIME HELPER ---
  // --- UAE BREAK CALCULATION FUNCTIONS ---
    
    // Check if work period includes the 1:00 PM - 2:00 PM break
    function includesBreakTime(startTime, workHours) {
        if (!startTime || !workHours) return false;
        
        const [hours, minutes] = startTime.split(':').map(Number);
        const startMinutes = hours * 60 + minutes;
        
        // Prayer + Lunch break is 1:00 PM - 2:00 PM (13:00 - 14:00)
        const breakStart = 13 * 60; // 1:00 PM in minutes
        const breakEnd = 14 * 60;   // 2:00 PM in minutes
        
        // Calculate end time in minutes (without break)
        const endMinutes = startMinutes + (workHours * 60);
        
        // Check if the work period overlaps with break time
        return startMinutes < breakEnd && endMinutes > breakStart;
    }

    // Calculate actual duration with break
    function calculateActualDuration(startTime, workHours) {
        const hasBreak = includesBreakTime(startTime, workHours);
        const breakDuration = hasBreak ? 1 : 0;
        const totalHours = workHours + breakDuration;
        
        return {
            workHours: workHours,
            breakHours: breakDuration,
            totalHours: totalHours,
            hasBreak: hasBreak
        };
    }

    // Check if duration exceeds 5 consecutive hours
    function checkConsecutiveHours(workHours) {
        return workHours > 5;
    }

    // --- DURATION COMPLETION TIME HELPER ---
    function updateDurationHelper() {
        const duration = parseInt(durationSelect.value);
        const timeValue = bookingTimeInput.value;

        if (isNaN(duration) || !timeValue) {
            durationSelect.parentNode.querySelector('.duration-completion-helper')?.remove();
            return;
        }

        // Calculate UAE break requirements
        const durationCalc = calculateActualDuration(timeValue, duration);

        const [hours, minutes] = timeValue.split(":").map(Number);
        const startTime = new Date();
        startTime.setHours(hours, minutes, 0);

        // Add TOTAL hours (work + break)
        const endTime = new Date(startTime.getTime() + durationCalc.totalHours * 60 * 60 * 1000);
        let endHours = endTime.getHours();
        const endMinutes = endTime.getMinutes();
        const ampm = endHours >= 12 ? 'PM' : 'AM';
        endHours = endHours % 12 || 12;

        // Format start time
        const startAmpm = hours >= 12 ? 'PM' : 'AM';
        const startHours12 = hours % 12 || 12;

        let helperElement = durationSelect.parentNode.querySelector('.duration-completion-helper');
        if (!helperElement) {
            helperElement = document.createElement('small');
            helperElement.className = 'duration-completion-helper';
            helperElement.style.marginTop = '0.25rem';
            durationSelect.parentNode.appendChild(helperElement);
        }

        let completionText = `${startHours12}:${minutes.toString().padStart(2, '0')} ${startAmpm} - ${endHours}:${endMinutes.toString().padStart(2, '0')} ${ampm}`;
        
        // Show work hours
        completionText += ` (${duration} hrs work`;
        
        // Add break information if applicable
        if (durationCalc.hasBreak) {
            completionText += ` + 1 hr break [1:00 PM - 2:00 PM Prayer/Lunch]`;
        }
        
        completionText += `)`;

        // Warning if exceeds 5 consecutive hours without break
        if (checkConsecutiveHours(duration) && !durationCalc.hasBreak) {
            completionText += ` ⚠️ Exceeds 5 hours without break`;
            helperElement.style.color = '#d9534f';
        } else {
            helperElement.style.color = '#555';
        }

        helperElement.textContent = completionText;
        helperElement.style.display = 'block';
    }

    durationSelect.addEventListener("change", updateDurationHelper);
    bookingTimeInput.addEventListener('change', updateDurationHelper);

    // --- FINAL VALIDATION FUNCTION ---
    function validateRecurringForm() {
        let isValid = true;
        
        // Clear all previous errors
        const allFormGroups = document.querySelectorAll('.form-group');
        allFormGroups.forEach(group => {
            group.classList.remove('is-invalid-group');
            group.querySelectorAll('.error-message').forEach(el => {
                // Only clear error messages for fields that will be re-validated below
                if(el.id !== "endDateErrorMessage" && el.id !== "bookingTimeErrorMessage") el.classList.remove("show");
            });
        });

        // Date Range Validation (Run first to clear specific date errors)
        if (!validateDateRange()) {
            isValid = false;
        }
        
        // Manual validation for the service card group
        const serviceOptionsGroup = document.querySelector('.service-options').closest('.form-group');
        if (serviceTypeHidden.value === "") {
            serviceOptionsGroup.classList.add('is-invalid-group');
            isValid = false;
        }

        // REQUIRED FIELDS CHECK
        const formElements = [clientTypeSelect, addressInput, frequencySelect,
            preferredDaySelect, startDateInput, endDateInput, durationSelect,
            propertyLayoutTextarea];

        formElements.forEach(el => {
            // Special handling for endDateInput if it's empty
            if (el.id === 'endDate' && el.value.trim() === '') {
                // If End Date is empty, apply the required field border
                addInvalidClass(el);
                isValid = false;
            } 
            // Generic required field check for all others
            else if (el.hasAttribute('required') && el.value.trim() === '') {
                addInvalidClass(el);
                isValid = false;
            }
        });

        // TIME VALIDATION (Preferred Time)
        const timeValue = bookingTimeInput.value.trim();
        const timeErrorMessage = document.getElementById("bookingTimeErrorMessage");
        if (timeValue === '') {
            addInvalidClass(bookingTimeInput);
            isValid = false;
        } else if (!validateTimeRange()) {
            addInvalidClass(bookingTimeInput);
            timeErrorMessage.textContent = "Please choose between 9 AM and 6 PM";
            timeErrorMessage.classList.add("show");
            isValid = false;
        } else {
            removeInvalidClass(bookingTimeInput);
            timeErrorMessage.classList.remove("show");
        }

        // Cleaning Materials validation
        const selectedMaterialOption = document.querySelector('input[name="cleaningMaterials"]:checked');
        const materialsRadioGroup = materialsYes.closest('.form-group');
        if (!selectedMaterialOption) {
            materialsRadioGroup.classList.add('is-invalid-group');
            isValid = false;
        } else {
            if (selectedMaterialOption.value === 'yes' && materialsNeededInput.value.trim() === '') {
                addInvalidClass(materialsNeededInput);
                isValid = false;
            }
        }
        
        if (propertyLayoutTextarea.value.trim() === '') {
            addInvalidClass(propertyLayoutTextarea);
            isValid = false;
        }

        return isValid;
    }
    
    // --- WAIVER SECTION TRANSITION ---
    nextToWaiverBtn.addEventListener("click", (e) => {
        e.preventDefault();
        if (validateRecurringForm()) {
            bookingFormSection.style.display = 'none';
            waiverSection.style.display = 'block';
            nextToWaiverBtn.style.display = 'none';
            backToFormBtn.style.display = 'block';
            finalSubmitBtn.style.display = 'block';
        } else {
            requiredFieldsModal.classList.add('show');
        }
    });

    backToFormBtn.addEventListener("click", (e) => {
        e.preventDefault();
        bookingFormSection.style.display = 'block';
        waiverSection.style.display = 'none';
        nextToWaiverBtn.style.display = 'block';
        backToFormBtn.style.display = 'none';
        finalSubmitBtn.style.display = 'none';
    });

    finalSubmitBtn.addEventListener("click", (e) => {
        if (!agreeWaiverCheckbox.checked) {
            e.preventDefault();
            waiverRequiredModal.classList.add('show');
        }
    });

    closeWaiverModal.addEventListener("click", () => {
        waiverRequiredModal.classList.remove('show');
    });

    closeRequiredFieldsModal.addEventListener("click", () => {
        requiredFieldsModal.classList.remove('show');
    });

    // Initial state setup
    serviceTypeHidden.value = "";
    clientTypeSelect.disabled = true;
    frequencySelect.disabled = true;
    preferredDaySelect.disabled = true;
    startDateInput.disabled = true;
    endDateInput.disabled = true;
    bookingTimeInput.disabled = true;
    durationSelect.disabled = true;
    startDateInput.placeholder = "Please select a preferred day first.";
    endDateInput.placeholder = "Please select a start date first.";

    generalCleaningBtn.classList.remove("active");
});
</script>

<script src="client_db.js"></script>

<script src="client_db.js"></script>
</body>
</html>