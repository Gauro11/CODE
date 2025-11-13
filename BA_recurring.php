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
    const endDateInput = document.getElementById("endDate");
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
    const endDateErrorMessage = document.getElementById("endDateErrorMessage");

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

    function addInvalidClass(element) {
        const parentGroup = element.closest('.form-group');
        if (parentGroup) {
            parentGroup.classList.add('is-invalid-group');
        }
    }

    function removeInvalidClass(element) {
        const parentGroup = element.closest('.form-group');
        if (parentGroup) {
            parentGroup.classList.remove('is-invalid-group');
        }
    }

    // ========== RESTRICTED TIME SLOTS (FROM ONE-TIME) ==========
    function isTimeRestricted(timeString) {
        if (!timeString) return { restricted: false };
        
        const [hours, minutes] = timeString.split(':').map(Number);
        const timeInMinutes = hours * 60 + minutes;
        
        // 1:00 PM - 2:00 PM (13:00 - 14:00) - Prayer/Lunch Break
        const prayerLunchStart = 13 * 60; // 780 minutes
        const prayerLunchEnd = 14 * 60;   // 840 minutes
        
        // 5:00 PM - 5:30 PM (17:00 - 17:30) - Short Break
        const shortBreakStart = 17 * 60;      // 1020 minutes
        const shortBreakEnd = 17 * 60 + 30;   // 1050 minutes
        
        if (timeInMinutes >= prayerLunchStart && timeInMinutes < prayerLunchEnd) {
            return { restricted: true, reason: '1:00 PM - 2:00 PM is reserved for Prayer/Lunch Break' };
        }
        
        if (timeInMinutes >= shortBreakStart && timeInMinutes < shortBreakEnd) {
            return { restricted: true, reason: '5:00 PM - 5:30 PM is reserved for Short Break' };
        }
        
        return { restricted: false };
    }
    
function updateAvailableDurations(startTime) {
    if (!startTime || !durationSelect) return;
    
    const [hours, minutes] = startTime.split(':').map(Number);
    const startMinutes = hours * 60 + minutes;
    const endOfDayMinutes = 20 * 60; // 8:00 PM (20:00)
    
    // Calculate maximum available work hours until 8 PM
    let maxAvailableMinutes = endOfDayMinutes - startMinutes;
    
    // Check if the time period includes the 1:00-2:00 PM break
    const breakStart = 13 * 60;
    const breakEnd = 14 * 60;
    
    // If booking starts before 1 PM and could extend past 1 PM, account for break
    if (startMinutes < breakStart && (startMinutes + maxAvailableMinutes) > breakStart) {
        maxAvailableMinutes -= 60; // Subtract 1 hour for break
    }
    
    const maxWorkHours = Math.floor(maxAvailableMinutes / 60);
    
    // Save current selection
    const currentValue = durationSelect.value;
    
    // Clear and repopulate duration options
    durationSelect.innerHTML = '<option value="">Select duration...</option>';
    
    for (let i = 2; i <= 8; i++) {
        if (i <= maxWorkHours) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = `${i} hrs`;
            durationSelect.appendChild(option);
        }
    }
    
    // Restore selection if still valid
    if (currentValue && parseInt(currentValue) <= maxWorkHours) {
        durationSelect.value = currentValue;
    } else {
        durationSelect.value = '';
    }
    
    // Show helper text if limited
    if (maxWorkHours < 8) {
        let durationLimitHelper = durationSelect.parentNode.querySelector('.duration-limit-helper');
        if (!durationLimitHelper) {
            durationLimitHelper = document.createElement('small');
            durationLimitHelper.className = 'duration-limit-helper';
            durationLimitHelper.style.cssText = 'color: #856404; margin-top: 0.25rem; display: block;';
            // Insert before completion helper if it exists
            const completionHelper = durationSelect.parentNode.querySelector('.duration-completion-helper');
            if (completionHelper) {
                durationSelect.parentNode.insertBefore(durationLimitHelper, completionHelper);
            } else {
                durationSelect.parentNode.appendChild(durationLimitHelper);
            }
        }
        // durationLimitHelper.textContent = `Maximum ${maxWorkHours} hours available (bookings must end by 8:00 PM)`;
        durationLimitHelper.style.display = 'block';
    } else {
        const existingHelper = durationSelect.parentNode.querySelector('.duration-limit-helper');
        if (existingHelper) {
            existingHelper.style.display = 'none';
        }
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
        validateDateRange();
        updateSessionCountHelper();
        
        startDateInput.value = "";
        endDateInput.value = "";
        fpEnd.set('enable', [() => true]);
        fpEnd.set('minDate', 'today');
        fpEnd.set('defaultDate', null);
        
        if(preferredDaySelect.value) {
             enableFlatpickrForPreferredDay(preferredDaySelect.value);
        }
    });

    preferredDaySelect.addEventListener("change", function () {
        const chosenDay = this.value;
        const hasPreferredDay = chosenDay !== "";
        if (hasPreferredDay) {
            removeInvalidClass(preferredDaySelect);
            
            startDateInput.disabled = false;
            startDateInput.style.pointerEvents = 'auto';
            startDateInput.style.backgroundColor = 'transparent';
            startDateInput.placeholder = "Select your desired date";
            
            resetDependentFields(startDateInput, endDateInput, bookingTimeInput, durationSelect);
            endDateInput.placeholder = "Please select a start date first.";
            
            enableFlatpickrForPreferredDay(chosenDay);
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
            
            endDateInput.disabled = false;
            endDateInput.style.pointerEvents = 'auto';
            endDateInput.style.backgroundColor = 'transparent';
            endDateInput.placeholder = "Select your end date";
            
            const freq = frequencySelect.value;
            const rawStartDate = new Date(startDateInput.value);
            const startDate = new Date(rawStartDate.getFullYear(), rawStartDate.getMonth(), rawStartDate.getDate());
            
            const preferredDayNumber = startDate.getDay(); 
            
            let minEndDate;
            let defaultEndDate = null;
            let enableFunction;

            function isPreferredDay(date) {
                return date.getDay() === preferredDayNumber;
            }
            
            minEndDate = startDateInput.value;
            
            if (freq === 'Weekly') {
                let minEndWeekly = new Date(startDate.getTime());
                minEndWeekly.setDate(minEndWeekly.getDate() + 7);
                minEndDate = minEndWeekly.toISOString().split('T')[0];
                defaultEndDate = minEndDate;

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
                defaultEndDate = minEndDate;

                enableFunction = [
                    function (date) {
                        const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
                        const timeDiff = dateOnly.getTime() - startDate.getTime();
                        const dayDiff = Math.round(timeDiff / (1000 * 3600 * 24)); 
                        
                        return dayDiff >= intervalDays && dayDiff % intervalDays === 0;
                    }
                ];

            } else {
                 let minEndMonthly = new Date(startDate.getTime());
                 minEndMonthly.setMonth(minEndMonthly.getMonth() + 1);
                 minEndDate = minEndMonthly.toISOString().split('T')[0];
                 defaultEndDate = minEndDate;

                 const startDayOfMonth = startDate.getDate();
                
                 enableFunction = [
                    function (date) {
                        const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
                        
                        if (dateOnly.getDate() !== startDayOfMonth) {
                            return false; 
                        }
                        
                        const monthDiff = (dateOnly.getFullYear() - startDate.getFullYear()) * 12 + (dateOnly.getMonth() - startDate.getMonth());
                        
                        return monthDiff >= 1;
                    }
                 ];
            }

            fpEnd.set('minDate', minEndDate);
            fpEnd.set('enable', enableFunction);
            
            if (defaultEndDate) {
                fpEnd.set('defaultDate', defaultEndDate);
                fpEnd.clear(); 
                endDateInput.value = "";
            } else {
                fpEnd.clear(); 
                endDateInput.value = ""; 
            }
            
            resetDependentFields(startDateInput, bookingTimeInput, durationSelect);
            validateDateRange(); 

        } else {
            resetDependentFields(startDateInput, endDateInput, bookingTimeInput, durationSelect);
            endDateInput.placeholder = "Please select a start date first.";
            
            fpEnd.set('enable', [() => true]); 
            fpEnd.set('minDate', 'today');
            fpEnd.set('defaultDate', null);
        }
    });

    endDateInput.addEventListener("change", () => {
        const hasEndDate = endDateInput.value !== "";
        
        removeInvalidClass(endDateInput); 

        if (hasEndDate) {
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
    
    function validateDateRange() {
        const freq = frequencySelect.value;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        endDateErrorMessage.textContent = "";
        endDateErrorMessage.classList.remove("show");
        removeInvalidClass(endDateInput); 

        if (!startDate || !endDate) {
            return true;
        }
        
        if (freq === 'Weekly' || freq === 'Bi-Weekly' || freq === 'Monthly') {
            if (startDate === endDate) {
                const serviceType = freq === 'Weekly' ? 'Weekly' : freq === 'Bi-Weekly' ? 'Bi-Weekly' : 'Monthly';
                addInvalidClass(endDateInput);
                endDateErrorMessage.textContent = `For ${serviceType} recurring service, the End Date must be after the Start Date.`;
                endDateErrorMessage.classList.add("show");
                return false;
            }
        }

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

    function updateSessionCountHelper() {
        const startDateStr = startDateInput.value;
        const endDateStr = endDateInput.value;
        const frequency = frequencySelect.value;
        const helperElementId = 'sessionCountHelper';
        const hiddenSessionInput = document.getElementById('estimatedSessionsHidden');

        let helperElement = endDateInput.parentNode.querySelector('#' + helperElementId);
        if (!helperElement) {
            helperElement = document.createElement('small');
            helperElement.className = 'session-count-helper';
            helperElement.id = helperElementId;
            helperElement.style.cssText = 'color: rgb(85, 85, 85); margin-top: 0.25rem; display: block;'; 
            endDateInput.parentNode.appendChild(helperElement);
        }

        if (!startDateStr || !endDateStr || !frequency || !validateDateRange()) {
            helperElement.style.display = 'none';
            if (hiddenSessionInput) hiddenSessionInput.value = '';
            return;
        }

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
            if (hiddenSessionInput) hiddenSessionInput.value = '';
            return;
        }

        if (sessionCount === 0 && startDate.getTime() <= endDate.getTime()) {
            sessionCount = 1;
        }

        helperElement.textContent = `Estimated Sessions: ${sessionCount}`;
        helperElement.style.display = 'block';
        
        if (hiddenSessionInput) {
            hiddenSessionInput.value = sessionCount;
        }
    }

    
bookingTimeInput.addEventListener("change", () => {
    const hasBookingTime = bookingTimeInput.value !== "";
    if (hasBookingTime) {
        // Validate immediately on change
        if (validateAndShowTimeGuide()) {
            removeInvalidClass(bookingTimeInput);
            durationSelect.disabled = false;
            // ✅ ADD THIS LINE: Update available durations based on start time
            updateAvailableDurations(bookingTimeInput.value);
        } else {
            durationSelect.disabled = true;
        }
    } else {
        resetDependentFields(bookingTimeInput, durationSelect);
    }
    updateDurationHelper();
});
    
    // ========== UPDATED TIME VALIDATION (WITH RESTRICTIONS) ==========
    function validateAndShowTimeGuide() {
        const timeValue = bookingTimeInput.value;
        const timeErrorMessage = document.getElementById("bookingTimeErrorMessage");

        timeErrorMessage.textContent = "";
        timeErrorMessage.classList.remove("show");
        removeInvalidClass(bookingTimeInput);

        if (timeValue === '') {
            return false; 
        }

        const [hours, minutes] = timeValue.split(':').map(Number);
        const totalMinutes = hours * 60 + minutes;
        
        // Check 9 AM to 6 PM range
        const isValidTime = totalMinutes >= 540 && totalMinutes <= 1080;

        if (!isValidTime) {
            timeErrorMessage.textContent = "Please choose between 9 AM and 6 PM";
            timeErrorMessage.classList.add("show");
            addInvalidClass(bookingTimeInput);
            return false;
        }
        
        // ✅ Check if time is in restricted period
        const restrictionCheck = isTimeRestricted(timeValue);
        if (restrictionCheck.restricted) {
            timeErrorMessage.textContent = "⛔ " + restrictionCheck.reason;
            timeErrorMessage.classList.add("show");
            addInvalidClass(bookingTimeInput);
            return false;
        }
        
        return true;
    }

    bookingTimeInput.addEventListener("input", validateAndShowTimeGuide);

    // --- FLATPICKR ---
    const fpStart = flatpickr(startDateInput, {
        dateFormat: "Y-m-d",
        minDate: "today",
        disable: []
    });
    
    const fpEnd = flatpickr(endDateInput, {
        dateFormat: "Y-m-d",
        minDate: "today",
        enable: [() => true]
    });

    function getPreferredDayNumber(day) {
        const days = { Sunday: 0, Monday: 1, Tuesday: 2, Wednesday: 3, Thursday: 4, Friday: 5, Saturday: 6 };
        return days[day] ?? null;
    }

    function enableFlatpickrForPreferredDay(chosenDay) {
        const preferredDayNum = getPreferredDayNumber(chosenDay);
        
        fpStart.set('disable', [
            function (date) {
                return date.getDay() !== preferredDayNum;
            }
        ]);
        
        let nextDate = new Date();
        while (nextDate.getDay() !== preferredDayNum || nextDate.getTime() < new Date().getTime() - (24 * 60 * 60 * 1000)) {
            nextDate.setDate(nextDate.getDate() + 1);
        }
        
        const today = new Date();
        today.setHours(0,0,0,0);
        if (nextDate.getTime() < today.getTime()) {
            nextDate.setDate(nextDate.getDate() + 7);
        }
        
        fpStart.setDate(nextDate.toISOString().split('T')[0], false); 
        
        endDateInput.disabled = true; 
        endDateInput.value = "";
        endDateInput.placeholder = "Please select a start date first.";
        fpEnd.clear(); 
        fpEnd.set('defaultDate', null);
        fpEnd.set('minDate', 'today');
        fpEnd.set('enable', [() => true]);
    }
    
    function validateTimeRange() {
        const timeValue = bookingTimeInput.value;
        if (!timeValue) return false;
        
        const [hours, minutes] = timeValue.split(':').map(Number);
        const totalMinutes = hours * 60 + minutes;
        
        // Check 9 AM to 6 PM
        if (totalMinutes < 540 || totalMinutes > 1080) {
            return false;
        }
        
        // Check restricted times
        const restrictionCheck = isTimeRestricted(timeValue);
        if (restrictionCheck.restricted) {
            return false;
        }
        
        return true;
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
        updateDurationHelper();
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
                removeInvalidClass(materialsNeededInput);
            }
        });
    });

    // --- UAE BREAK CALCULATION FUNCTIONS ---
    function includesBreakTime(startTime, workHours) {
        if (!startTime || !workHours) return false;
        
        const [hours, minutes] = startTime.split(':').map(Number);
        const startMinutes = hours * 60 + minutes;
        
        const breakStart = 13 * 60;
        const breakEnd = 14 * 60;
        
        const endMinutes = startMinutes + (workHours * 60);
        
        return startMinutes < breakEnd && endMinutes > breakStart;
    }

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

    function checkConsecutiveHours(workHours) {
        return workHours > 5;
    }

    function updateDurationHelper() {
        const duration = parseInt(durationSelect.value);
        const timeValue = bookingTimeInput.value;

        if (isNaN(duration) || !timeValue) {
            durationSelect.parentNode.querySelector('.duration-completion-helper')?.remove();
            return;
        }

        const durationCalc = calculateActualDuration(timeValue, duration);

        const [hours, minutes] = timeValue.split(":").map(Number);
        const startTime = new Date();
        startTime.setHours(hours, minutes, 0);

        const endTime = new Date(startTime.getTime() + durationCalc.totalHours * 60 * 60 * 1000);
        let endHours = endTime.getHours();
        const endMinutes = endTime.getMinutes();
        const ampm = endHours >= 12 ? 'PM' : 'AM';
        endHours = endHours % 12 || 12;

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
        
        completionText += ` (${duration} hrs work`;
        
        if (durationCalc.hasBreak) {
            completionText += ` + 1 hr break [1:00 PM - 2:00 PM Prayer/Lunch]`;
        }
        
        completionText += `)`;

        if (checkConsecutiveHours(duration) && !durationCalc.hasBreak) {
            completionText += ` ⚠️ Exceeds 5 hours without break`;
            helperElement.style.color = '#d9534f';
        } else {
            helperElement.style.color = '#555';
        }

        helperElement.textContent = completionText;
        helperElement.style.display = 'block';
    }

    

    // --- FINAL VALIDATION FUNCTION ---
    function validateRecurringForm() {
        let isValid = true;
        
        const allFormGroups = document.querySelectorAll('.form-group');
        allFormGroups.forEach(group => {
            group.classList.remove('is-invalid-group');
            group.querySelectorAll('.error-message').forEach(el => {
                if(el.id !== "endDateErrorMessage" && el.id !== "bookingTimeErrorMessage") el.classList.remove("show");
            });
        });

        if (!validateDateRange()) {
            isValid = false;
        }
        
        const serviceOptionsGroup = document.querySelector('.service-options').closest('.form-group');
        if (serviceTypeHidden.value === "") {
            serviceOptionsGroup.classList.add('is-invalid-group');
            isValid = false;
        }

        const formElements = [clientTypeSelect, addressInput, frequencySelect,
            preferredDaySelect, startDateInput, endDateInput, durationSelect,
            propertyLayoutTextarea];

        formElements.forEach(el => {
            if (el.id === 'endDate' && el.value.trim() === '') {
                addInvalidClass(el);
                isValid = false;
            } 
            else if (el.hasAttribute('required') && el.value.trim() === '') {
                addInvalidClass(el);
                isValid = false;
            }
        });

        // ✅ UPDATED TIME VALIDATION - Use validateAndShowTimeGuide
        const timeValue = bookingTimeInput.value.trim();
        if (timeValue === '') {
            addInvalidClass(bookingTimeInput);
            isValid = false;
        } else {
            if (!validateAndShowTimeGuide()) {
                isValid = false;
            }
        }

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