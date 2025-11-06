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
    $address = $_POST['address'] ?? '';
    $frequency = $_POST['frequency'] ?? '';
    $preferredDay = $_POST['preferredDay'] ?? '';
    $startDate = $_POST['startDate'] ?? '';
    $endDate = $_POST['endDate'] ?? '';
    $bookingTime = $_POST['bookingTime'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $propertyLayout = $_POST['propertyLayout'] ?? '';
    $cleaningMaterials = $_POST['cleaningMaterials'] ?? '';
    $materialsNeeded = $_POST['materialsNeeded'] ?? '';
    $additionalRequest = $_POST['additionalRequest'] ?? '';

    // Handle materials provided format
    $materialsProvided = ($cleaningMaterials === 'yes') ? 'Yes - 40 AED / hr' : 'No - 35 AED / hr';

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
    $sql = "UPDATE bookings SET 
        service_type = ?,
        client_type = ?,
        address = ?,
        frequency = ?,
        preferred_day = ?,
        service_date = ?,
        end_date = ?,
        service_time = ?,
        duration = ?,
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

    $stmt->bind_param(
    "ssssssssssssssssis",  // ← Changed to 18 characters (16 's' + 1 'i' + 1 's')
    $serviceType,
    $clientType,
    $address,
    $frequency,
    $preferredDay,
    $startDate,
    $endDate,
    $bookingTime,
    $duration,
    $propertyLayout,
    $materialsProvided,
    $materialsNeeded,
    $additionalRequest,
    $media1,
    $media2,
    $media3,
    $booking_id,      // integer
    $user_email       // string
);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        echo "<script>alert('Booking updated successfully!'); window.location.href='HIS_recurring.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error updating booking: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}

// ===================================================================
// FETCH BOOKING DATA FOR EDITING
// ===================================================================
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    header('Location: HIS_recurring.php');
    exit;
}

$booking_id = intval($_GET['booking_id']);
$booking_data = [];

$sql = "SELECT * FROM bookings WHERE id = ? AND TRIM(email) = ? AND booking_type = 'Recurring'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $booking_id, $user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Check if booking status is Pending
    if ($row['status'] !== 'Pending') {
        echo "<script>alert('You can only edit bookings with Pending status.'); window.location.href='HIS_recurring.php';</script>";
        exit;
    }
    
    $booking_data = [
        'booking_id' => $row['id'],
        'serviceType' => $row['service_type'] ?? '',
        'clientType' => $row['client_type'] ?? '',
        'address' => $row['address'] ?? '',
        'frequency' => $row['frequency'] ?? '',
        'preferredDay' => $row['preferred_day'] ?? '',
        'startDate' => $row['service_date'] ?? '',
        'endDate' => $row['end_date'] ?? '',
        'bookingTime' => $row['service_time'] ?? '',
        'duration' => $row['duration'] ?? '',
        'propertyLayout' => $row['property_type'] ?? '',
        'materialsProvided' => $row['materials_provided'] ?? '',
        'materialsNeeded' => $row['materials_needed'] ?? '',
        'additionalRequest' => $row['comments'] ?? '',
        'media1' => $row['media1'] ?? '',
        'media2' => $row['media2'] ?? '',
        'media3' => $row['media3'] ?? '',
    ];

} else {
    echo "<script>alert('Booking not found.'); window.location.href='HIS_recurring.php';</script>";
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
    // Check for materials - handle both 'yes'/'no' and the full text
    if ($key === 'cleaningMaterials') {
        $materialsProvided = $booking_data['materialsProvided'] ?? '';
        if ($value === 'yes' && strpos($materialsProvided, '40') !== false) return 'checked';
        if ($value === 'no' && strpos($materialsProvided, '35') !== false) return 'checked';
        return '';
    }
    return isset($booking_data[$key]) && $booking_data[$key] === $value ? 'checked' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALAZIMA - Edit Recurring Booking</title>
    <link rel="icon" href="site_icon.png" type="image/png">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="client_db.css">
    <style>
        .btn--secondary {
            background-color: #e0e0e0;
            color: #333;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .btn--secondary:hover {
            background-color: #b0b0b0;
            color: #333;
        }

        .dashboard__content {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.content__section {
    display: block !important;
}

.booking__form {
    display: block !important;
    background: white;
    padding: 20px;
    border-radius: 8px;
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

        .service-options {
            text-align: left;
        }
        .service-card {
            display: inline-block;
        }

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

        .readonly-field {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        .edit-notice {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .edit-notice i {
            font-size: 24px;
            color: #856404;
        }

        .edit-notice p {
            margin: 0;
            color: #856404;
            font-weight: 500;
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

            <li class="menu__item has-dropdown">
                <a href="#" class="menu__link" data-content="book-appointment-parent"><i class='bx bx-calendar'></i> Book Appointment <i class='bx bx-chevron-down arrow-icon'></i></a>
                <ul class="dropdown__menu">
                    <li class="menu__item"><a href="BA_one-time.php" class="menu__link">One-Time Service</a></li>
                    <li class="menu__item"><a href="BA_recurring.php" class="menu__link">Recurring Service</a></li>
                </ul>
            </li>

            <li class="menu__item has-dropdown open">
                <a href="#" class="menu__link active-parent" data-content="history-parent"><i class='bx bx-history'></i> History <i class='bx bx-chevron-down arrow-icon'></i></a>
                <ul class="dropdown__menu">
                    <li class="menu__item"><a href="HIS_one-time.php" class="menu__link">One-Time Service</a></li>
                    <li class="menu__item"><a href="HIS_recurring.php" class="menu__link active">Recurring Service</a></li>
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
            <h2 class="section__title"><i class='bx bx-edit'></i> Edit Recurring Booking</h2>
            
            <div class="edit-notice">
                <i class='bx bx-info-circle'></i>
                <p>You are editing a pending booking. Make your changes and click "Update Booking" to save.</p>
            </div>

            <div class="booking__form">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="booking_id" value="<?php echo e('booking_id'); ?>">
                    <input type="hidden" name="existing_media1" value="<?php echo e('media1'); ?>">
                    <input type="hidden" name="existing_media2" value="<?php echo e('media2'); ?>">
                    <input type="hidden" name="existing_media3" value="<?php echo e('media3'); ?>">
                    
                    <div id="bookingFormSectionRecurring">
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="serviceType">Service Type</label>
                                <div class="service-options">
                                    <button type="button" class="service-card active" id="generalCleaningBtn" data-service-type="General Cleaning">
                                        <i class="fa-solid fa-broom"></i>
                                        <h4>General Cleaning</h4>
                                        <p>Your standard, routine cleaning to maintain cleanliness.</p>
                                    </button>
                                </div>
                                <input type="hidden" id="serviceTypeHiddenRecurring" name="serviceType" value="<?php echo e('serviceType'); ?>" required>
                                <div id="serviceTypeErrorMessage" class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="clientTypeRecurring">Client Type</label>
                                <select id="clientTypeRecurring" name="clientType" required>
                                    <option value="">Select client type...</option>
                                    <option value="Residential" <?php echo isSelected('clientType', 'Residential'); ?>>Residential</option>
                                    <option value="Offices" <?php echo isSelected('clientType', 'Offices'); ?>>Offices</option>
                                </select>
                                <div id="clientTypeErrorMessage" class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="addressRecurring">Address</label>
                                <input type="text" id="addressRecurring" name="address" placeholder="Enter full address" value="<?php echo e('address'); ?>" required>
                                <div id="addressErrorMessage" class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="frequency">Frequency</label>
                                <select id="frequency" name="frequency" required>
                                    <option value="">Select frequency...</option>
                                    <option value="Weekly" <?php echo isSelected('frequency', 'Weekly'); ?>>Weekly</option>
                                    <option value="Bi-Weekly" <?php echo isSelected('frequency', 'Bi-Weekly'); ?>>Bi-Weekly</option>
                                    <option value="Monthly" <?php echo isSelected('frequency', 'Monthly'); ?>>Monthly</option>
                                </select>
                                <div id="frequencyErrorMessage" class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="preferredDay">Preferred Day of the Week</label>
                                <select id="preferredDay" name="preferredDay" required>
                                    <option value="">Select day...</option>
                                    <option value="Monday" <?php echo isSelected('preferredDay', 'Monday'); ?>>Monday</option>
                                    <option value="Tuesday" <?php echo isSelected('preferredDay', 'Tuesday'); ?>>Tuesday</option>
                                    <option value="Wednesday" <?php echo isSelected('preferredDay', 'Wednesday'); ?>>Wednesday</option>
                                    <option value="Thursday" <?php echo isSelected('preferredDay', 'Thursday'); ?>>Thursday</option>
                                    <option value="Friday" <?php echo isSelected('preferredDay', 'Friday'); ?>>Friday</option>
                                    <option value="Saturday" <?php echo isSelected('preferredDay', 'Saturday'); ?>>Saturday</option>
                                    <option value="Sunday" <?php echo isSelected('preferredDay', 'Sunday'); ?>>Sunday</option>
                                </select>
                                <div id="preferredDayErrorMessage" class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="startDate">Start Date</label>
                                <input type="text" id="startDate" name="startDate" value="<?php echo e('startDate'); ?>" required>

                                <div id="startDateErrorMessage" class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="endDate">End Date</label>
                                <input type="text" id="endDate" name="endDate" value="<?php echo e('endDate'); ?>" required>
                                <div id="endDateErrorMessage" class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="bookingTimeRecurring">Preferred Time</label>
                                <input type="time" id="bookingTimeRecurring" name="bookingTime" value="<?php echo e('bookingTime'); ?>" required>
                                <div id="bookingTimeErrorMessage" class="error-message"></div>
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

                        <div class="form-row form-section-gap">
                            <div class="form-group full-width">
                                <label>Property Layout</label>
                                <small class="form-text text-muted">Please specify the unit size/type, number of floors, and room breakdown per floor.</small>
                                <div class="side-by-side-container">
                                    <textarea id="propertyLayout" name="propertyLayout" rows="8" placeholder="Ex. Studio Type – 1 Floor: 1 Room, 1 Bathroom" required><?php echo e('propertyLayout'); ?></textarea>
                                    <div id="propertyLayoutErrorMessage" class="error-message"></div>

                                    <div class="media-upload-container">
                                        <div class="upload-field">
                                            <label for="mediaUpload1">Image/Video 1 (Optional)</label>
                                            <input type="file" id="mediaUpload1" name="mediaUpload[]" accept="image/*,video/*">
                                            <?php if (!empty($booking_data['media1'])): ?>
                                                <small>Current: <?php echo basename($booking_data['media1']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="upload-field">
                                            <label for="mediaUpload2">Image/Video 2 (Optional)</label>
                                            <input type="file" id="mediaUpload2" name="mediaUpload[]" accept="image/*,video/*">
                                            <?php if (!empty($booking_data['media2'])): ?>
                                                <small>Current: <?php echo basename($booking_data['media2']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="upload-field">
                                            <label for="mediaUpload3">Image/Video 3 (Optional)</label>
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
                                <div class="radio-group">
                                    <input type="radio" id="materialsYesRecurring" name="cleaningMaterials" value="yes" <?php echo isChecked('cleaningMaterials', 'yes'); ?> required>
                                    <label for="materialsYesRecurring">Yes - 40 AED / hr</label>

                                    <input type="radio" id="materialsNoRecurring" name="cleaningMaterials" value="no" <?php echo isChecked('cleaningMaterials', 'no'); ?> required>
                                    <label for="materialsNoRecurring">No - 35 AED / hr</label>
                                </div>
                                <div id="cleaningMaterialsErrorMessage" class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-row" id="materialsNeededContainer" style="display: <?php echo (strpos($booking_data['materialsProvided'] ?? '', '40') !== false) ? 'flex' : 'none'; ?>;">
                            <div class="form-group full-width">
                                <label for="materialsNeeded">If yes, what materials are needed?</label>
                                <input type="text" id="materialsNeeded" name="materialsNeeded" placeholder="e.g., mop, disinfectant, vacuum cleaner" value="<?php echo e('materialsNeeded'); ?>">
                                <div id="materialsNeededErrorMessage" class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="additionalRequest">Additional Request (Optional)</label>
                                <textarea id="additionalRequest" name="additionalRequest" rows="4" placeholder="e.g., Use organic products, focus on kitchen"><?php echo e('additionalRequest'); ?></textarea>
                            </div>
                        </div>

                        <div class="booking-summary">
                            <p class="summary-text">Estimated Price:</p>
                            <span id="recurringPrice" class="price-display">AED 0</span>
                        </div>
                    </div>

                    <div class="form__actions" id="formActionsPrimary">
                        <button type="button" class="btn btn--secondary" onclick="window.location.href='HIS_recurring.php';">Cancel</button>
                        <button type="submit" class="btn btn--success" id="updateBookingBtn">Update Booking</button>
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
<script src="client_db.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize all form elements
    const serviceTypeHidden = document.getElementById("serviceTypeHiddenRecurring");
    const generalCleaningBtn = document.getElementById("generalCleaningBtn");
    const clientTypeSelect = document.getElementById("clientTypeRecurring");
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
    
    // ✅ OVERRIDE: Force duration to always be enabled
    function forceEnableDuration() {
        if (durationSelect) {
            durationSelect.disabled = false;
            durationSelect.removeAttribute('disabled');
            durationSelect.style.pointerEvents = 'auto';
            durationSelect.style.opacity = '1';
            durationSelect.style.backgroundColor = 'white';
            durationSelect.style.cursor = 'pointer';
        }
    }
    
    // Force enable immediately
    forceEnableDuration();
    
    // ✅ Override General Cleaning button click
    if (generalCleaningBtn) {
        generalCleaningBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            forceEnableDuration();
        });
    }
    
    // ✅ Use MutationObserver to watch for attribute changes on duration
    if (durationSelect) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'disabled') {
                    console.log('Duration was disabled, forcing enable...');
                    forceEnableDuration();
                }
            });
        });
        
        observer.observe(durationSelect, {
            attributes: true,
            attributeFilter: ['disabled']
        });
    }
    
    // Initialize Flatpickr
    const fpStart = flatpickr(startDateInput, {
        dateFormat: "Y-m-d",
        minDate: "today"
    });
    
    const fpEnd = flatpickr(endDateInput, {
        dateFormat: "Y-m-d",
        minDate: "today"
    });

    // Helper function to get day number
    function getPreferredDayNumber(day) {
        const days = { Sunday: 0, Monday: 1, Tuesday: 2, Wednesday: 3, Thursday: 4, Friday: 5, Saturday: 6 };
        return days[day] ?? null;
    }

    // Set initial state based on loaded data
    if (preferredDaySelect.value) {
        const preferredDayNum = getPreferredDayNumber(preferredDaySelect.value);
        fpStart.set('disable', [
            function (date) {
                return date.getDay() !== preferredDayNum;
            }
        ]);
    }

    // Helper functions for price calculation
    function getSessionsPerMonth(frequency) {
        switch(frequency) {
            case 'Weekly': return 4;
            case 'Bi-Weekly': return 2;
            case 'Monthly': return 1;
            default: return 0;
        }
    }

    function convert24To12Hour(hours, minutes) {
        const period = hours >= 12 ? 'PM' : 'AM';
        const displayHours = hours % 12 || 12;
        const displayMinutes = String(minutes).padStart(2, '0');
        return `${displayHours}:${displayMinutes} ${period}`;
    }

    function updateEstimatedPrice() {
        const duration = parseFloat(durationSelect.value) || 0;
        const frequency = frequencySelect.value;
        const materialsRequired = materialsYes.checked;
        
        const hourlyRate = materialsRequired ? 40 : 35;
        const sessionsPerMonth = getSessionsPerMonth(frequency);
        
        const pricePerSession = duration * hourlyRate;
        const totalMonthlyPrice = pricePerSession * sessionsPerMonth;
        
        const recurringPriceDisplay = document.getElementById("recurringPrice");
        if (recurringPriceDisplay) {
            recurringPriceDisplay.textContent = `AED ${totalMonthlyPrice.toFixed(2)}`;
        }
    }

    function updateEstimatedTime() {
        const duration = parseFloat(durationSelect.value) || 0;
        const estimatedTimeDisplay = document.getElementById("estimatedTimeDisplay");
        
        if (duration > 0 && estimatedTimeDisplay) {
            const startTime = bookingTimeInput.value;
            if (startTime) {
                const [hours, minutes] = startTime.split(':').map(Number);
                const endHours = hours + Math.floor(duration);
                const endMinutes = minutes + ((duration % 1) * 60);
                
                const finalEndHours = endHours + Math.floor(endMinutes / 60);
                const finalEndMinutes = Math.floor(endMinutes % 60);
                
                const endTime12hr = convert24To12Hour(finalEndHours, finalEndMinutes);
                estimatedTimeDisplay.textContent = `Estimated end time: ${endTime12hr}`;
                estimatedTimeDisplay.style.display = 'block';
            }
        } else if (estimatedTimeDisplay) {
            estimatedTimeDisplay.style.display = 'none';
        }
    }

    // Materials radio logic
    materialsRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (materialsYes.checked) {
                materialsNeededContainer.style.display = 'flex';
                materialsNeededInput.required = true;
            } else {
                materialsNeededContainer.style.display = 'none';
                materialsNeededInput.required = false;
            }
            updateEstimatedPrice();
        });
    });

    if (materialsYes.checked) {
        materialsNeededContainer.style.display = 'flex';
    }

    // Duration change event
    durationSelect.addEventListener("change", () => {
        updateEstimatedPrice();
        updateEstimatedTime();
    });
    
    durationSelect.addEventListener("click", forceEnableDuration);

    // Frequency change event
    frequencySelect.addEventListener('change', updateEstimatedPrice);

    // Time validation
    bookingTimeInput.addEventListener("change", () => {
        const timeValue = bookingTimeInput.value;
        const [hours, minutes] = timeValue.split(':').map(Number);
        const totalMinutes = hours * 60 + minutes;
        const timeErrorMessage = document.getElementById("bookingTimeErrorMessage");
        
        if (timeErrorMessage) {
            timeErrorMessage.textContent = "";
            timeErrorMessage.classList.remove("show");
            
            if (totalMinutes < 540 || totalMinutes > 1080) {
                timeErrorMessage.textContent = "Please choose between 9 AM and 6 PM";
                timeErrorMessage.classList.add("show");
            }
        }
        
        updateEstimatedTime();
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        let isValid = true;
        
        const timeValue = bookingTimeInput.value;
        if (timeValue) {
            const [hours, minutes] = timeValue.split(':').map(Number);
            const totalMinutes = hours * 60 + minutes;
            
            if (totalMinutes < 540 || totalMinutes > 1080) {
                e.preventDefault();
                alert('Please select a time between 9 AM and 6 PM');
                isValid = false;
            }
        }
        
        if (materialsYes.checked && materialsNeededInput.value.trim() === '') {
            e.preventDefault();
            alert('Please specify what materials are needed');
            isValid = false;
        }
        
        return isValid;
    });

    // Initial calculations
    updateEstimatedPrice();
    updateEstimatedTime();
    
    // Final check with longer delay
    setTimeout(forceEnableDuration, 200);
    setTimeout(forceEnableDuration, 500);
});
</script>
</body>
</html>