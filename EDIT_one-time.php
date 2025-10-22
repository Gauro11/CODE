<?php
// =======================================================================
// PHP SCRIPT START: FETCHING AND HANDLING BOOKING DATA
// ******************* PALITAN ANG MOCK DATA NG IYONG ACTUAL DATABASE QUERY *******************
// =======================================================================

// 1. Check if a booking ID is provided in the URL
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    // Kung walang ID, ibalik sa history page
    header('Location: HIS_one-time.php');
    exit;
}

$booking_id = (int)$_GET['booking_id'];
$booking_data = []; // Array na maglalaman ng na-fetch na data

// 2. MOCK Database Query (PALITAN ITO NG IYONG ACTUAL DB CODE)
// require_once 'db_config.php'; // Halimbawa ng pag-include ng config

// *** SIMULASYON: Fetch Data mula sa Database ***
// Kunwari nahanap natin ang data para sa booking ID
if ($booking_id > 0) { // Assume booking ID exists for demonstration
    // Ito ang data na lalabas sa form. Tiyakin na ang keys ay tugma sa iyong form names/values.
    $booking_data = [
        'serviceType' => 'Deep Cleaning',
        'clientType' => 'Residential',
        'bookingDate' => '2025-10-15',
        'bookingTime' => '10:00',
        'duration' => '4', // 4 hours
        'address' => 'Unit 1001, Jumeirah Lakes Towers, Dubai',
        'propertyLayout' => "Residential Apartment\n2 Floors: 2 Bedrooms, 2 Bathrooms, 1 Kitchen, 1 Balcony",
        // Tiyakin na ang value ay tugma sa radio button value
        'cleaningMaterials' => 'No - 35 AED / hr', 
        'materialsNeeded' => '', // Empty dahil 'No' ang materials
        'additionalRequest' => 'Please avoid using harsh chemicals in the main bedroom.',
    ];
} else {
    // DITO KA MAGLAGAY NG REDIRECT O ERROR HANDLING KUNG WALANG NAFECH NA DATA
    // header('Location: HIS_one-time.php?error=notfound');
    // exit;
}

// 3. Helper function para i-display ang data nang ligtas
function e($key) {
    global $booking_data;
    echo htmlspecialchars($booking_data[$key] ?? '');
}

// =======================================================================
// PHP SCRIPT END: FETCHING AND HANDLING BOOKING DATA
// =======================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head> <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALAZIMA - Client Dashboard</title>
    <link rel="icon" href="site_icon.png" type="image/png">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="client_db.css">
    <style>
        /* Pinaliit ang padding at font-size para lumiit ang buttons at text */
        .btn {
            display: inline-block;
            padding: 0.5rem 1.5rem; /* Niliitan na ang height */
            font-size: 0.9rem; /* Pinaliit ang font size */
            font-weight: 600;
            text-align: center;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.1s ease;
            border: none;
            text-decoration: none;
        }

        /* Cancel hover animation */
        .btn--secondary {
            background-color: #e0e0e0; /* Light grey */
            color: #333; /* Dark text for contrast */
            transition: background-color 0.3s ease, color 0.3s ease;
            text-decoration: none; /* Inalis ang underline */
        }
        .btn--secondary:hover {
            background-color: #b0b0b0; /* Darker grey on hover */
            color: #333;
            text-decoration: none; /* Tiyakin na walang underline */
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

        /* New CSS for the materials input */
        .hidden-materials-input {
            display: none; /* This will be toggled by JS */
            flex-direction: column;
            margin-top: 1rem;
        }

        /* Added for visual validation */
        .is-invalid {
            border-color: red !important;
        }
        .is-invalid-group {
            border: 1px solid red;
            border-radius: 8px;
            padding: 10px;
        }

        /* Style for selected service card */
        .service-card.selected {
            border-color: #007bff; /* Example highlight color */
            background-color: #e7f3ff;
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
                        <li class="menu__item"><a href="BA_one-time.php" class="menu__link active">One-Time Service</a></li>
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
                
                <li class="menu__item"><a href="client_dashboard.php?content=profile" class="menu__link" data-content="profile"><i class='bx bx-user'></i> My Profile</a></li>
                <li class="menu__item"><a href="#" class="menu__link" data-content="logout"><i class='bx bx-log-out'></i> Logout</a></li>
            </ul>
        </aside>

        <main class="dashboard__content">
            <section id="one-time-service-content" class="content__section active">
                <h2 class="section__title">Book One-Time Service</h2>
                <div class="booking__form">
                    
                    <form id="oneTimeBookingForm" action="#" method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="serviceType">Service Type</label>
                                <div class="service-options">
                                    <button type="button" class="service-card" data-service-type="Checkout Cleaning">
                                        <i class='bx bx-check-shield'></i>
                                        <h4>Checkout Cleaning</h4>
                                        <p>Preparing a unit for a new occupant. The focus is on making it ready for a new guest.</p>
                                    </button>
                                    <button type="button" class="service-card" data-service-type="In-House Cleaning">
                                        <i class='bx bx-home-heart'></i>
                                        <h4>In-House Cleaning</h4>
                                        <p>Providing an additional clean for a client who is currently staying in the unit.</p>
                                    </button>
                                    <button type="button" class="service-card" data-service-type="Refresh Cleaning">
                                        <i class='bx bx-wind'></i>
                                        <h4>Refresh Cleaning</h4>
                                        <p>A light touch-up for a unit that has been vacant for a period of time.</p>
                                    </button>
                                    <button type="button" class="service-card" data-service-type="Deep Cleaning">
                                        <i class='bx bx-water'></i>
                                        <h4>Deep Cleaning</h4>
                                        <p>An intensive and thorough clean for units that are in "disaster" or very dirty conditions.</p>
                                    </button>
                                </div>
                                <input type="hidden" id="serviceTypeHidden" name="serviceType" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="clientType">Client Type</label>
                                <select id="clientType" name="clientType" required disabled>
                                    <option value="">Select a service type first...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="bookingDate">Date</label>
                                <input type="date" id="bookingDate" name="bookingDate" required>
                            </div>
                            <div class="form-group">
                                <label for="bookingTime">Time</label>
                                <input type="time" id="bookingTime" name="bookingTime" required>
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
                                <p id="estimatedTimeDisplay" class="form-text" style="display: none; color: #555; margin-top: 0.5rem;"></p>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" placeholder="Enter full address" required>
                        </div>
                        
                        <div class="form-row form-section-gap">
                            <div class="form-group full-width">
                                <label>Property Layout</label>
                                <small class="form-text text-muted">Please specify the unit size/type, number of floors, and room breakdown per floor, and upload up to 3 images/videos to help us understand the actual layout.</small>
                                
                                <div class="side-by-side-container">
                                    <textarea id="propertyLayout" name="propertyLayout" rows="8" placeholder="Ex. Studio Type – 1 Floor: 1 Room, 1 Bathroom" required></textarea>
                                    
                                    <div class="media-upload-container">
                                        <div class="upload-field">
                                            <label for="mediaUpload1">Image/Video 1</label>
                                            <input type="file" id="mediaUpload1" name="mediaUpload[]" accept="image/*,video/*">
                                        </div>
                                        <div class="upload-field">
                                            <label for="mediaUpload2">Image/Video 2</label>
                                            <input type="file" id="mediaUpload2" name="mediaUpload[]" accept="image/*,video/*">
                                        </div>
                                        <div class="upload-field">
                                            <label for="mediaUpload3">Image/Video 3</label>
                                            <input type="file" id="mediaUpload3" name="mediaUpload[]" accept="image/*,video/*">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label>Does the client require cleaning materials?</label>
                                <div class="radio-group" id="cleaningMaterialsGroup">
                                    <input type="radio" id="materialsYes" name="cleaningMaterials" value="Yes - 40 AED / hr" required>
                                    <label for="materialsYes">Yes - 40 AED / hr</label>
                                    <input type="radio" id="materialsNo" name="cleaningMaterials" value="No - 35 AED / hr" required>
                                    <label for="materialsNo">No - 35 AED / hr</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-row hidden-materials-input">
                            <div class="form-group full-width">
                                <label for="materialsNeeded">If yes, what materials are needed?</label>
                                <input type="text" id="materialsNeeded" name="materialsNeeded" placeholder="e.g., mop, disinfectant, vacuum cleaner">
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
                            <span class="price-display" id="finalPriceDisplay">AED 0</span>
                        </div>
                        
                        <div class="form__actions">
                            <a href="HIS_one-time.php" class="btn btn--secondary">Cancel</a>
                            <button type="submit" class="btn btn--success" id="finalSubmitBtn">Update</button>
                        </div>
                    </form>
                    
                    </div>
            </section>
        </main>
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
    <div class="modal" id="profileSaveModal">
        <div class="modal__content">
            <h3 class="modal__title">Profile Saved</h3>
            <p>Your profile has been updated successfully!</p>
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
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // BAGONG JS PARA SA CLEANING MATERIALS
            const materialsNeededContainer = document.querySelector('.hidden-materials-input');
            const materialsYes = document.getElementById('materialsYes');
            const materialsNo = document.getElementById('materialsNo');
            const cleaningMaterialsGroup = document.getElementById('cleaningMaterialsGroup'); // Added to group the radio buttons for validation

            if (materialsYes && materialsNo && materialsNeededContainer) {
                materialsYes.addEventListener('change', () => {
                    materialsNeededContainer.style.display = 'flex';
                    // Ensure 'materialsNeeded' is marked as required if 'Yes' is selected
                    document.getElementById('materialsNeeded').setAttribute('required', ''); 
                });
                materialsNo.addEventListener('change', () => {
                    materialsNeededContainer.style.display = 'none';
                    // Clear the value if "No" is selected and remove 'required' attribute
                    const materialsNeededInput = document.getElementById('materialsNeeded');
                    if (materialsNeededInput) {
                        materialsNeededInput.value = '';
                        materialsNeededInput.removeAttribute('required'); // Remove required attribute
                        materialsNeededInput.classList.remove('is-invalid'); // Remove validation if cleared
                    }
                });
            }

            const form = document.getElementById("oneTimeBookingForm");
            const finalSubmitBtn = document.getElementById("finalSubmitBtn");

            // --- Form Validation Logic ---
            function validateForm() {
                const requiredFields = form.querySelectorAll('[required]');
                let allFieldsFilled = true;

                // Reset previous invalid states
                form.querySelectorAll('.is-invalid, .is-invalid-group').forEach(el => {
                    el.classList.remove('is-invalid', 'is-invalid-group');
                });

                requiredFields.forEach(field => {
                    const isVisible = field.offsetWidth > 0 && field.offsetHeight > 0; // Check if element is visible
                    const isRadioOrCheckbox = field.type === 'radio' || field.type === 'checkbox';
                    const isValuePresent = field.value.trim() !== '';
                    const isChecked = field.checked;
                    const fieldParent = field.closest('.form-group');

                    // Only validate visible and required fields
                    if (isVisible && ((isRadioOrCheckbox && !isChecked) || (!isRadioOrCheckbox && !isValuePresent))) {
                        field.classList.add('is-invalid');
                        if (fieldParent) {
                            fieldParent.classList.add('is-invalid-group');
                        }
                        allFieldsFilled = false;
                    }
                });

                // Custom validation for service type selection
                const serviceTypeHidden = document.getElementById('serviceTypeHidden');
                const serviceOptionsGroup = document.querySelector('.service-options');
                const serviceLabel = document.querySelector('label[for="serviceType"]');
                
                if (serviceTypeHidden && serviceTypeHidden.value === '') {
                    allFieldsFilled = false;
                    serviceOptionsGroup.classList.add('is-invalid-group');
                    // Added: Apply is-invalid-group to the parent of the label
                    if (serviceLabel && serviceLabel.closest('.form-group')) {
                        serviceLabel.closest('.form-group').classList.add('is-invalid-group');
                    }
                } else {
                    serviceOptionsGroup.classList.remove('is-invalid-group');
                    // Added: Remove is-invalid-group from the parent of the label
                    if (serviceLabel && serviceLabel.closest('.form-group')) {
                        serviceLabel.closest('.form-group').classList.remove('is-invalid-group');
                    }
                }
                
                // Custom validation for cleaning materials radio group
                const cleaningMaterialsRadios = document.querySelectorAll('input[name="cleaningMaterials"]');
                const cleaningMaterialsParent = cleaningMaterialsGroup.closest('.form-group'); // Get the parent .form-group
                let isCleaningMaterialSelected = false;
                cleaningMaterialsRadios.forEach(radio => {
                    if (radio.checked) {
                        isCleaningMaterialSelected = true;
                    }
                });

                if (!isCleaningMaterialSelected) {
                    allFieldsFilled = false;
                    if (cleaningMaterialsParent) {
                        cleaningMaterialsParent.classList.add('is-invalid-group');
                    }
                } else {
                    if (cleaningMaterialsParent) {
                        cleaningMaterialsParent.classList.remove('is-invalid-group');
                    }
                }

                // Custom validation for 'materialsNeeded' if 'materialsYes' is checked and it's required
                if (materialsYes.checked) {
                    const materialsNeededInput = document.getElementById('materialsNeeded');
                    if (materialsNeededInput && materialsNeededInput.value.trim() === '') {
                        allFieldsFilled = false;
                        materialsNeededInput.classList.add('is-invalid');
                        materialsNeededInput.closest('.form-group').classList.add('is-invalid-group');
                    } else if (materialsNeededInput) {
                        materialsNeededInput.classList.remove('is-invalid');
                        materialsNeededInput.closest('.form-group').classList.remove('is-invalid-group');
                    }
                }

                return allFieldsFilled;
            }
            // --- End of Form Validation Logic ---

            // ** START OF NEW REAL-TIME VALIDATION LOGIC **
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                const isRadioOrCheckbox = field.type === 'radio' || field.type === 'checkbox';
                const eventType = isRadioOrCheckbox ? 'change' : 'input';

                field.addEventListener(eventType, () => {
                    // Check if the field is no longer empty or unchecked
                    let isValid = false;
                    if (isRadioOrCheckbox) {
                        const fieldName = field.name;
                        const radioGroup = form.querySelectorAll(`[name="${fieldName}"]`);
                        radioGroup.forEach(radio => {
                            if (radio.checked) {
                                isValid = true;
                            }
                        });
                    } else {
                        isValid = field.value.trim() !== '';
                    }

                    // Remove the invalid class if the field is now valid
                    if (isValid) {
                        field.classList.remove('is-invalid');
                        const fieldParent = field.closest('.form-group');
                        if (fieldParent) {
                            fieldParent.classList.remove('is-invalid-group');
                        }
                    }

                    // Special handling for the cleaning materials group
                    if (isRadioOrCheckbox && field.name === 'cleaningMaterials') {
                        const cleaningMaterialsParent = cleaningMaterialsGroup.closest('.form-group');
                        const cleaningMaterialsRadios = document.querySelectorAll('input[name="cleaningMaterials"]');
                        let isSelected = false;
                        cleaningMaterialsRadios.forEach(radio => {
                            if (radio.checked) {
                                isSelected = true;
                            }
                        });
                        if (isSelected) {
                            if (cleaningMaterialsParent) {
                                cleaningMaterialsParent.classList.remove('is-invalid-group');
                            }
                        }
                    }

                    // Special handling for the service type buttons
                    if (field.id === 'serviceTypeHidden' && field.value !== '') {
                        const serviceOptionsGroup = document.querySelector('.service-options');
                        serviceOptionsGroup.classList.remove('is-invalid-group');
                    }
                });
            });
            // ** END OF NEW REAL-TIME VALIDATION LOGIC **

            // --- FINAL SUBMIT LOGIC (I-handle ang validation bago mag-submit) ---
            finalSubmitBtn.addEventListener("click", (e) => {
                if (!validateForm()) {
                    e.preventDefault(); // Pigilan ang form submission kung may error
                    const requiredFieldsModal = document.getElementById("requiredFieldsModal");
                    if (requiredFieldsModal) {
                        requiredFieldsModal.classList.add("show");
                    }
                } 
                // Kung magpapatuloy at walang errors, papayagan ang default form submission (Update)
            });

            // Close required fields modal
            const confirmRequiredFieldsBtn = document.getElementById("confirmRequiredFields");
            if (confirmRequiredFieldsBtn) {
                confirmRequiredFieldsBtn.addEventListener("click", () => {
                    document.getElementById("requiredFieldsModal").classList.remove("show");
                });
            }

            // Optional: close modal pag click sa labas
            window.addEventListener("click", (e) => {
                if (e.target === document.getElementById("requiredFieldsModal")) {
                    document.getElementById("requiredFieldsModal").classList.remove("show");
                }
            });

            // --- DATE VALIDATION (NEW) ---
            const bookingDateInput = document.getElementById('bookingDate');
            if (bookingDateInput) {
                const today = new Date().toISOString().split('T')[0];
                bookingDateInput.setAttribute('min', today);
            }
            // --- END DATE VALIDATION ---

            // --- TIME & PRICE LOGIC ---
            const bookingTimeInput = document.getElementById('bookingTime');
            const durationSelect = document.getElementById('duration');
            const estimatedTimeDisplay = document.getElementById('estimatedTimeDisplay');
            const finalPriceDisplay = document.getElementById('finalPriceDisplay'); // Kuha ang element para sa presyo

            let timeHelper = document.createElement('small');
            timeHelper.style.display = 'none';
            timeHelper.style.color = 'red';
            timeHelper.style.marginTop = '0.25rem';
            timeHelper.textContent = 'Please choose between 9 AM and 6 PM';
            if (bookingTimeInput) {
                bookingTimeInput.parentNode.appendChild(timeHelper);
            }

            let durationHelper = document.createElement('small');
            durationHelper.style.display = 'none'; // Initially hidden
            durationHelper.style.color = '#555';
            durationHelper.style.marginTop = '0.25rem';
            if (durationSelect) {
                durationSelect.parentNode.appendChild(durationHelper);
            }

            // Function to format time to 12-hour format
            function formatTime12Hour(timeString) {
                const [hours, minutes] = timeString.split(':').map(Number);
                const period = hours >= 12 ? 'PM' : 'AM';
                const adjustedHours = hours % 12 || 12; // Convert 0 to 12 for midnight/noon
                const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
                return `${adjustedHours}:${formattedMinutes} ${period}`;
            }

            if (bookingTimeInput) {
                bookingTimeInput.addEventListener('change', () => {
                    const selectedTime = bookingTimeInput.value;
                    // Check if time is outside 09:00 to 18:00 (6 PM)
                    if (selectedTime && (selectedTime < '09:00' || selectedTime > '18:00')) {
                        timeHelper.style.display = 'block';
                        // If time is invalid, disable duration and clear estimate
                        if (durationSelect) durationSelect.disabled = true;
                        estimatedTimeDisplay.style.display = 'none';
                    } else {
                        timeHelper.style.display = 'none';
                        // If time is valid, enable duration and update estimate
                        if (durationSelect) durationSelect.disabled = false;
                        updateEstimatedCompletion(); // Update estimate when time is valid
                    }
                });
            }

            if (durationSelect) {
                durationSelect.addEventListener('change', () => {
                    updateEstimatedCompletion(); // Update completion time when duration changes
                });
            }

            function updateEstimatedCompletion() {
                const selectedTimeStr = bookingTimeInput.value;
                const selectedDuration = parseInt(durationSelect.value);
                
                // Base rates from your radio buttons (assuming this logic for demo)
                // Default: 'No materials' rate (35 AED/hr)
                let ratePerHour = 35; 
                if (materialsYes.checked) {
                    ratePerHour = 40; // 'Yes materials' rate (40 AED/hr)
                }

                // Check for valid Duration selection
                if (!selectedTimeStr || isNaN(selectedDuration) || bookingTimeInput.classList.contains('is-invalid') || durationSelect.disabled) {
                    estimatedTimeDisplay.style.display = 'none';
                    // Kapag walang duration, ipapakita ang AED 0
                    finalPriceDisplay.textContent = 'AED 0'; 
                    return;
                }

                // --- Time Calculation (Existing Logic) ---
                const [hours, minutes] = selectedTimeStr.split(':').map(Number);
                const startDate = new Date();
                startDate.setHours(hours, minutes, 0, 0); 

                const endDate = new Date(startDate.getTime() + selectedDuration * 60 * 60 * 1000);

                const formattedCompletionTime = formatTime12Hour(endDate.getHours().toString().padStart(2, '0') + ':' + endDate.getMinutes().toString().padStart(2, '0'));

                let completionText = `Estimated completion: ${formattedCompletionTime}`;
                completionText += ` (${selectedDuration} hrs)`;

                estimatedTimeDisplay.textContent = completionText;
                estimatedTimeDisplay.style.display = 'block';
                // --- End Time Calculation ---

                // --- Price Calculation ---
                const estimatedPrice = selectedDuration * ratePerHour;
                
                // Ginamit ang Math.round() para masigurong whole number at walang decimal
                finalPriceDisplay.textContent = `AED ${Math.round(estimatedPrice)}`; 
            }

            // I-call ang updateEstimatedCompletion tuwing magbabago ang cleaning materials
            materialsYes.addEventListener('change', updateEstimatedCompletion);
            materialsNo.addEventListener('change', updateEstimatedCompletion);

            // =========================================================
            // *** CLIENT TYPE LOGIC ***
            // =========================================================
            const serviceCards = document.querySelectorAll('.service-card');
            const serviceTypeHiddenInput = document.getElementById('serviceTypeHidden');
            const clientTypeSelect = document.getElementById('clientType');

            // MAPPING ng Client Type batay sa ibinigay na logic
            const clientTypeOptions = {
                'Checkout Cleaning': ['Holiday Apartment'],
                'In-House Cleaning': ['Holiday Apartment'],
                'Refresh Cleaning': ['Holiday Apartment', 'Residential', 'Offices'],
                'Deep Cleaning': ['Holiday Apartment', 'Residential', 'Offices'],
            };
            
            // Function para i-populate ang clientTypeSelect
            function populateClientType(selectedServiceType) {
                // I-clear ang lahat ng kasalukuyang options at ibalik sa default prompt
                clientTypeSelect.innerHTML = '<option value="">Select Client Type...</option>';
                
                // I-enable ang select field
                clientTypeSelect.disabled = false;

                // I-populate ang bagong options
                const options = clientTypeOptions[selectedServiceType] || [];
                options.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type;
                    option.textContent = type;
                    clientTypeSelect.appendChild(option);
                });
            }

            serviceCards.forEach(card => {
                card.addEventListener('click', () => {
                    serviceCards.forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    const selectedServiceType = card.dataset.serviceType;
                    serviceTypeHiddenInput.value = selectedServiceType;
                    
                    // Tiyakin na gumagana ang Client Type logic
                    if (clientTypeSelect) {
                         populateClientType(selectedServiceType);
                    }
                    
                    // Also enable duration select and reset if needed
                    if (durationSelect) {
                        durationSelect.disabled = false;
                        durationSelect.value = ""; // Reset to default prompt
                        updateEstimatedCompletion(); // Clear estimate if duration is reset
                    }
                    // Clear any previous validation on service options
                    const serviceOptionsGroup = document.querySelector('.service-options');
                    const serviceLabel = document.querySelector('label[for="serviceType"]');
                    serviceOptionsGroup.classList.remove('is-invalid-group');
                    if (serviceLabel && serviceLabel.closest('.form-group')) {
                        serviceLabel.closest('.form-group').classList.remove('is-invalid-group');
                    }
                });
            });

            // Initial call: Para ma-set ang default/initial value ng Estimated Price (AED 0)
            updateEstimatedCompletion(); 
        });
    </script>

</body>
</html>