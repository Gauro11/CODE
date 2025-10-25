<?php
session_start();
require 'connection.php';

// ✅ Ensure client is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='login.php';</script>";
    exit;
}

$user_email = trim($_SESSION['email']);

// ===================================================================
// HANDLE FORM SUBMISSION (UPDATE)
// ===================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);

    // Form data
    $serviceType = $_POST['serviceType'] ?? '';
    $clientType = $_POST['clientType'] ?? '';
    $bookingDate = $_POST['bookingDate'] ?? '';
    $bookingTime = $_POST['bookingTime'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $address = $_POST['address'] ?? '';
    $propertyLayout = $_POST['propertyLayout'] ?? '';
    $cleaningMaterials = $_POST['cleaningMaterials'] ?? '';
    $materialsNeeded = $_POST['materialsNeeded'] ?? '';
    $additionalRequest = $_POST['additionalRequest'] ?? '';

    // Handle file uploads
    $media1 = $_POST['existing_media1'] ?? '';
    $media2 = $_POST['existing_media2'] ?? '';
    $media3 = $_POST['existing_media3'] ?? '';

    if (isset($_FILES['mediaUpload']) && is_array($_FILES['mediaUpload']['name'])) {
        $upload_dir = 'uploads/';
        for ($i = 0; $i < 3; $i++) {
            if (!empty($_FILES['mediaUpload']['name'][$i]) && $_FILES['mediaUpload']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['mediaUpload']['tmp_name'][$i];
                $file_name = time() . '_' . basename($_FILES['mediaUpload']['name'][$i]);
                $target_path = $upload_dir . $file_name;
                if (move_uploaded_file($tmp_name, $target_path)) {
                    if ($i === 0) $media1 = $target_path;
                    if ($i === 1) $media2 = $target_path;
                    if ($i === 2) $media3 = $target_path;
                }
            }
        }
    }

    // ✅ Update booking
 // ✅ Update booking - Use 'comments' column instead of 'additional_request'
$sql = "UPDATE bookings SET 
    service_type = ?,
    client_type = ?,
    service_date = ?,
    service_time = ?,
    duration = ?,
    address = ?,
    property_type = ?,
    materials_provided = ?,
    materials_needed = ?,
    comments = ?,
    media1 = ?,
    media2 = ?,
    media3 = ?
    WHERE id = ? AND email = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

// ✅ 15 parameters total (13 data fields + id + email)
$stmt->bind_param(
    "sssssssssssssis",
    $serviceType,
    $clientType,
    $bookingDate,
    $bookingTime,
    $duration,
    $address,
    $propertyLayout,
    $cleaningMaterials,
    $materialsNeeded,
    $additionalRequest,  // This will go to 'comments' column
    $media1,
    $media2,
    $media3,
    $booking_id,
    $user_email
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    echo "<script>alert('Booking updated successfully!'); window.location.href='HIS_one-time.php';</script>";
    exit; // ✅ Stop script execution here
} else {
    echo "<script>alert('Error updating booking: " . $stmt->error . "');</script>";
}

$stmt->close();

}

// ===================================================================
// FETCH BOOKING DATA FOR EDITING
// ===================================================================
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    header('Location: HIS_one-time.php');
    exit;
}

$booking_id = intval($_GET['booking_id']);
$booking_data = [];

$sql = "SELECT * FROM bookings WHERE id = ? AND TRIM(email) = ? AND booking_type = 'One-Time'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $booking_id, $user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $booking_data = [
        'booking_id' => $row['id'],
        'serviceType' => $row['service_type'] ?? '',
        'clientType' => $row['client_type'] ?? '',
        'bookingDate' => $row['service_date'] ?? '',
        'bookingTime' => $row['service_time'] ?? '',
        'duration' => $row['duration'] ?? '',
        'address' => $row['address'] ?? '',
        'propertyLayout' => $row['property_type'] ?? '',
        'cleaningMaterials' => $row['materials_provided'] ?? '',
        'materialsNeeded' => $row['materials_needed'] ?? '',
        'additionalRequest' => $row['comments'] ?? '',  // ✅ Changed from 'additional_request'
        'media1' => $row['media1'] ?? '',
        'media2' => $row['media2'] ?? '',
        'media3' => $row['media3'] ?? '',
    ];

} else {
    echo "<script>alert('Booking not found.'); window.location.href='HIS_one-time.php';</script>";
    exit;
}

$stmt->close();
$conn->close();

function e($key) {
    global $booking_data;
    return htmlspecialchars($booking_data[$key] ?? '');
}
function isSelected($key, $value) {
    global $booking_data;
    return isset($booking_data[$key]) && $booking_data[$key] === $value ? 'selected' : '';
}
function isChecked($key, $value) {
    global $booking_data;
    return isset($booking_data[$key]) && $booking_data[$key] === $value ? 'checked' : '';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALAZIMA - Edit Booking</title>
    <link rel="icon" href="site_icon.png" type="image/png">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="client_db.css">
    <style>
        .btn {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.1s ease;
            border: none;
            text-decoration: none;
        }

        .btn--secondary {
            background-color: #e0e0e0;
            color: #333;
            transition: background-color 0.3s ease, color 0.3s ease;
            text-decoration: none;
        }
        .btn--secondary:hover {
            background-color: #b0b0b0;
            color: #333;
            text-decoration: none;
        }

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

        .hidden-materials-input {
            display: none;
            flex-direction: column;
            margin-top: 1rem;
        }

        .is-invalid {
            border-color: red !important;
        }
        .is-invalid-group {
            border: 1px solid red;
            border-radius: 8px;
            padding: 10px;
        }

        .service-card.selected {
            border-color: #007bff;
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
                        <li class="menu__item"><a href="BA_one-time.php" class="menu__link">One-Time Service</a></li>
                        <li class="menu__item"><a href="BA_recurring.php" class="menu__link">Recurring Service</a></li>
                    </ul>
                </li>
                
                <li class="menu__item has-dropdown">
                    <a href="#" class="menu__link" data-content="history-parent"><i class='bx bx-history'></i> History <i class='bx bx-chevron-down arrow-icon'></i></a>
                    <ul class="dropdown__menu">
                        <li class="menu__item"><a href="HIS_one-time.php" class="menu__link active">One-Time Service</a></li>
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
                <h2 class="section__title">Edit One-Time Booking</h2>
                <div class="booking__form">
                    
                    <form id="oneTimeBookingForm" action="" method="POST" enctype="multipart/form-data">
                       <input type="hidden" name="booking_id" value="<?php echo e('booking_id'); ?>">
                        <input type="hidden" name="existing_media1" value="<?php e('media1'); ?>">
                        <input type="hidden" name="existing_media2" value="<?php e('media2'); ?>">
                        <input type="hidden" name="existing_media3" value="<?php e('media3'); ?>">
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="serviceType">Service Type</label>
                                <div class="service-options">
                                    <button type="button" class="service-card <?php echo $booking_data['serviceType'] === 'Checkout Cleaning' ? 'selected' : ''; ?>" data-service-type="Checkout Cleaning">
                                        <i class='bx bx-check-shield'></i>
                                        <h4>Checkout Cleaning</h4>
                                        <p>Preparing a unit for a new occupant. The focus is on making it ready for a new guest.</p>
                                    </button>
                                    <button type="button" class="service-card <?php echo $booking_data['serviceType'] === 'In-House Cleaning' ? 'selected' : ''; ?>" data-service-type="In-House Cleaning">
                                        <i class='bx bx-home-heart'></i>
                                        <h4>In-House Cleaning</h4>
                                        <p>Providing an additional clean for a client who is currently staying in the unit.</p>
                                    </button>
                                    <button type="button" class="service-card <?php echo $booking_data['serviceType'] === 'Refresh Cleaning' ? 'selected' : ''; ?>" data-service-type="Refresh Cleaning">
                                        <i class='bx bx-wind'></i>
                                        <h4>Refresh Cleaning</h4>
                                        <p>A light touch-up for a unit that has been vacant for a period of time.</p>
                                    </button>
                                    <button type="button" class="service-card <?php echo $booking_data['serviceType'] === 'Deep Cleaning' ? 'selected' : ''; ?>" data-service-type="Deep Cleaning">
                                        <i class='bx bx-water'></i>
                                        <h4>Deep Cleaning</h4>
                                        <p>An intensive and thorough clean for units that are in "disaster" or very dirty conditions.</p>
                                    </button>
                                </div>
                                <input type="hidden" id="serviceTypeHidden" name="serviceType" value="<?php e('serviceType'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="clientType">Client Type</label>
                                <select id="clientType" name="clientType" required>
                                    <option value="">Select Client Type...</option>
                                    <option value="Holiday Apartment" <?php echo isSelected('clientType', 'Holiday Apartment'); ?>>Holiday Apartment</option>
                                    <option value="Residential" <?php echo isSelected('clientType', 'Residential'); ?>>Residential</option>
                                    <option value="Offices" <?php echo isSelected('clientType', 'Offices'); ?>>Offices</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="bookingDate">Date</label>
                                <input type="date" id="bookingDate" name="bookingDate" value="<?php e('bookingDate'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="bookingTime">Time</label>
                                <input type="time" id="bookingTime" name="bookingTime" value="<?php e('bookingTime'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="duration">Duration (Hours)</label>
                                <select id="duration" name="duration" required>
                                    <option value="">Select duration...</option>
                                    <option value="2" <?php echo isSelected('duration', '2'); ?>>2 hrs</option>
                                    <option value="3" <?php echo isSelected('duration', '3'); ?>>3 hrs</option>
                                    <option value="4" <?php echo isSelected('duration', '4'); ?>>4 hrs</option>
                                    <option value="5" <?php echo isSelected('duration', '5'); ?>>5 hrs</option>
                                    <option value="6" <?php echo isSelected('duration', '6'); ?>>6 hrs</option>
                                    <option value="7" <?php echo isSelected('duration', '7'); ?>>7 hrs</option>
                                    <option value="8" <?php echo isSelected('duration', '8'); ?>>8 hrs</option>
                                </select>
                                <p id="estimatedTimeDisplay" class="form-text" style="display: none; color: #555; margin-top: 0.5rem;"></p>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?php e('address'); ?>" placeholder="Enter full address" required>
                        </div>
                        
                        <div class="form-row form-section-gap">
                            <div class="form-group full-width">
                                <label>Property Layout</label>
                                <small class="form-text text-muted">Please specify the unit size/type, number of floors, and room breakdown per floor, and upload up to 3 images/videos to help us understand the actual layout.</small>
                                
                                <div class="side-by-side-container">
                                    <textarea id="propertyLayout" name="propertyLayout" rows="8" placeholder="Ex. Studio Type – 1 Floor: 1 Room, 1 Bathroom" required><?php e('propertyLayout'); ?></textarea>
                                    
                                    <div class="media-upload-container">
                                        <div class="upload-field">
                                            <label for="mediaUpload1">Image/Video 1</label>
                                            <input type="file" id="mediaUpload1" name="mediaUpload[]" accept="image/*,video/*">
                                            <?php if (!empty($booking_data['media1'])): ?>
                                                <small>Current: <?php echo basename($booking_data['media1']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="upload-field">
                                            <label for="mediaUpload2">Image/Video 2</label>
                                            <input type="file" id="mediaUpload2" name="mediaUpload[]" accept="image/*,video/*">
                                            <?php if (!empty($booking_data['media2'])): ?>
                                                <small>Current: <?php echo basename($booking_data['media2']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="upload-field">
                                            <label for="mediaUpload3">Image/Video 3</label>
                                            <input type="file" id="mediaUpload3" name="mediaUpload[]" accept="image/*,video/*">
                                            <?php if (!empty($booking_data['media3'])): ?>
                                                <small>Current: <?php echo basename($booking_data['media3']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label>Does the client require cleaning materials?</label>
                                <div class="radio-group" id="cleaningMaterialsGroup">
                                    <input type="radio" id="materialsYes" name="cleaningMaterials" value="Yes - 40 AED / hr" <?php echo isChecked('cleaningMaterials', 'Yes - 40 AED / hr'); ?> required>
                                    <label for="materialsYes">Yes - 40 AED / hr</label>
                                    <input type="radio" id="materialsNo" name="cleaningMaterials" value="No - 35 AED / hr" <?php echo isChecked('cleaningMaterials', 'No - 35 AED / hr'); ?> required>
                                    <label for="materialsNo">No - 35 AED / hr</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-row hidden-materials-input" style="<?php echo strpos($booking_data['cleaningMaterials'] ?? '', 'Yes') !== false ? 'display: flex;' : ''; ?>">
                            <div class="form-group full-width">
                                <label for="materialsNeeded">If yes, what materials are needed?</label>
                                <input type="text" id="materialsNeeded" name="materialsNeeded" value="<?php e('materialsNeeded'); ?>" placeholder="e.g., mop, disinfectant, vacuum cleaner">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="additionalRequest">Additional Request (Optional)</label>
                                <textarea id="additionalRequest" name="additionalRequest" rows="4" placeholder="e.g., Use organic products, focus on kitchen"><?php e('additionalRequest'); ?></textarea>
                            </div>
                        </div>

                        <div class="booking-summary">
                            <p class="summary-text">Estimated Price:</p>
                            <span class="price-display" id="finalPriceDisplay">AED 0</span>
                        </div>
                        
                        <div class="form__actions">
                            <a href="HIS_one-time.php" class="btn btn--secondary">Cancel</a>
                            <button type="submit" class="btn btn--success" id="finalSubmitBtn">Update Booking</button>
                        </div>
                    </form>
                    
                </div>
            </section>
        </main>
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
            const materialsNeededContainer = document.querySelector('.hidden-materials-input');
            const materialsYes = document.getElementById('materialsYes');
            const materialsNo = document.getElementById('materialsNo');
            const cleaningMaterialsGroup = document.getElementById('cleaningMaterialsGroup');

            if (materialsYes && materialsNo && materialsNeededContainer) {
                materialsYes.addEventListener('change', () => {
                    materialsNeededContainer.style.display = 'flex';
                    document.getElementById('materialsNeeded').setAttribute('required', '');
                });
                materialsNo.addEventListener('change', () => {
                    materialsNeededContainer.style.display = 'none';
                    const materialsNeededInput = document.getElementById('materialsNeeded');
                    if (materialsNeededInput) {
                        materialsNeededInput.value = '';
                        materialsNeededInput.removeAttribute('required');
                        materialsNeededInput.classList.remove('is-invalid');
                    }
                });
            }

            const form = document.getElementById("oneTimeBookingForm");
            const finalSubmitBtn = document.getElementById("finalSubmitBtn");

            function validateForm() {
                const requiredFields = form.querySelectorAll('[required]');
                let allFieldsFilled = true;

                form.querySelectorAll('.is-invalid, .is-invalid-group').forEach(el => {
                    el.classList.remove('is-invalid', 'is-invalid-group');
                });

                requiredFields.forEach(field => {
                    const isVisible = field.offsetWidth > 0 && field.offsetHeight > 0;
                    const isRadioOrCheckbox = field.type === 'radio' || field.type === 'checkbox';
                    const isValuePresent = field.value.trim() !== '';
                    const isChecked = field.checked;
                    const fieldParent = field.closest('.form-group');

                    if (isVisible && ((isRadioOrCheckbox && !isChecked) || (!isRadioOrCheckbox && !isValuePresent))) {
                        field.classList.add('is-invalid');
                        if (fieldParent) {
                            fieldParent.classList.add('is-invalid-group');
                        }
                        allFieldsFilled = false;
                    }
                });

                const serviceTypeHidden = document.getElementById('serviceTypeHidden');
                const serviceOptionsGroup = document.querySelector('.service-options');
                
                if (serviceTypeHidden && serviceTypeHidden.value === '') {
                    allFieldsFilled = false;
                    serviceOptionsGroup.classList.add('is-invalid-group');
                }

                return allFieldsFilled;
            }

            finalSubmitBtn.addEventListener("click", (e) => {
                if (!validateForm()) {
                    e.preventDefault();
                    const requiredFieldsModal = document.getElementById("requiredFieldsModal");
                    if (requiredFieldsModal) {
                        requiredFieldsModal.classList.add("show");
                    }
                }
            });

            const confirmRequiredFieldsBtn = document.getElementById("confirmRequiredFields");
            if (confirmRequiredFieldsBtn) {
                confirmRequiredFieldsBtn.addEventListener("click", () => {
                    document.getElementById("requiredFieldsModal").classList.remove("show");
                });
            }

            const bookingDateInput = document.getElementById('bookingDate');
            if (bookingDateInput) {
                const today = new Date().toISOString().split('T')[0];
                bookingDateInput.setAttribute('min', today);
            }

            const bookingTimeInput = document.getElementById('bookingTime');
            const durationSelect = document.getElementById('duration');
            const estimatedTimeDisplay = document.getElementById('estimatedTimeDisplay');
            const finalPriceDisplay = document.getElementById('finalPriceDisplay');

            function formatTime12Hour(timeString) {
                const [hours, minutes] = timeString.split(':').map(Number);
                const period = hours >= 12 ? 'PM' : 'AM';
                const adjustedHours = hours % 12 || 12;
                const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
                return `${adjustedHours}:${formattedMinutes} ${period}`;
            }

            function updateEstimatedCompletion() {
                const selectedTimeStr = bookingTimeInput.value;
                const selectedDuration = parseInt(durationSelect.value);
                
                let ratePerHour = 35;
                if (materialsYes.checked) {
                    ratePerHour = 40;
                }

                if (!selectedTimeStr || isNaN(selectedDuration)) {
                    estimatedTimeDisplay.style.display = 'none';
                    finalPriceDisplay.textContent = 'AED 0';
                    return;
                }

                const [hours, minutes] = selectedTimeStr.split(':').map(Number);
                const startDate = new Date();
                startDate.setHours(hours, minutes, 0, 0);

                const endDate = new Date(startDate.getTime() + selectedDuration * 60 * 60 * 1000);
                const formattedCompletionTime = formatTime12Hour(endDate.getHours().toString().padStart(2, '0') + ':' + endDate.getMinutes().toString().padStart(2, '0'));

                let completionText = `Estimated completion: ${formattedCompletionTime} (${selectedDuration} hrs)`;
                estimatedTimeDisplay.textContent = completionText;
                estimatedTimeDisplay.style.display = 'block';

                const estimatedPrice = selectedDuration * ratePerHour;
                finalPriceDisplay.textContent = `AED ${Math.round(estimatedPrice)}`;
            }

            if (bookingTimeInput) {
                bookingTimeInput.addEventListener('change', updateEstimatedCompletion);
            }
            if (durationSelect) {
                durationSelect.addEventListener('change', updateEstimatedCompletion);
            }
            materialsYes.addEventListener('change', updateEstimatedCompletion);
            materialsNo.addEventListener('change', updateEstimatedCompletion);

            const serviceCards = document.querySelectorAll('.service-card');
            const serviceTypeHiddenInput = document.getElementById('serviceTypeHidden');
            const clientTypeSelect = document.getElementById('clientType');

            const clientTypeOptions = {
                'Checkout Cleaning': ['Holiday Apartment'],
                'In-House Cleaning': ['Holiday Apartment'],
                'Refresh Cleaning': ['Holiday Apartment', 'Residential', 'Offices'],
                'Deep Cleaning': ['Holiday Apartment', 'Residential', 'Offices'],
            };

            function populateClientType(selectedServiceType) {
                const currentValue = clientTypeSelect.value;
                clientTypeSelect.innerHTML = '<option value="">Select Client Type...</option>';
                
                const options = clientTypeOptions[selectedServiceType] || [];
                options.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type;
                    option.textContent = type;
                    if (type === currentValue) {
                        option.selected = true;
                    }
                    clientTypeSelect.appendChild(option);
                });
            }

            serviceCards.forEach(card => {
                card.addEventListener('click', () => {
                    serviceCards.forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    const selectedServiceType = card.dataset.serviceType;
                    serviceTypeHiddenInput.value = selectedServiceType;
                    
                                       if (clientTypeSelect) {
                        populateClientType(selectedServiceType);
                    }
                    updateEstimatedCompletion();
                });
            });

            // Initialize client type options on page load
            const initialServiceType = serviceTypeHiddenInput.value;
            if (initialServiceType) {
                populateClientType(initialServiceType);
            }

            // Initialize estimated price/time display
            updateEstimatedCompletion();
        });
    </script>

</body>
</html>
