<?php
// PHP code for FR_one-time_form.php
// Handles the display and submission of the client's rating and feedback.

// 1. Get the reference number and action (leave or edit) from the URL
$ref_no = isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : 'ALZ-CC-2409-0015'; // Default Ref No
$action = isset($_GET['action']) ? htmlspecialchars($_GET['action']) : 'leave';

// --- REAL DATA SIMULATION ---
$job_details = [
    'ref_no' => $ref_no,
    'date' => '2025-08-24',             
    'time' => '08:18 PM',               
    'duration' => '5 Hours',            
    'address' => 'Lucky Korean Mall, Baguio City',
    'service_type' => 'General Cleaning',
    'client_type' => 'Offices',
    // Kept the employee data for Step 2 rating generation
    'cleaners' => ['Anna Sanchez', 'Ben Kuya', 'Cali Magno', 'Dan Cruz', 'Eva Ramos', 'Finn Reyes'],
    'driver' => 'David Perez',
];
// --- END OF SIMULATION ---

// 2. Determine the page title and button text based on action
$page_title = ($action === 'edit') ? "Edit Service Rating" : "Rate Service";
$submit_button_text = ($action === 'edit') ? "Update" : "Submit";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALAZIMA - <?php echo $page_title; ?></title>
    <link rel="icon" href="site_icon.png" type="image/png">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="client_db.css"> 
    <link rel="stylesheet" href="HIS_design.css">
    
    <style>
        /* Ensures the background matches the dashboard body background */
        body {
            background-color: #f4f7ff; 
            font-family: Arial, sans-serif; 
        }
        
        /* The main content wrapper. */
        .dashboard__content {
            padding: 20px; 
            min-height: calc(100vh - 80px); 
        }
        
        /* Form Container (Matches the card/modal look) */
        .form-container {
            width: 100%;
            max-width: 500px; 
            margin: 70px auto 30px; 
            padding: 25px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .form-header {
            text-align: center;
            color: #004A80;
            font-size: 1.8em;
            margin-bottom: 20px;
        }

        /* Job Details Display (COMPRESSED) */
        .job-details {
            font-size: 1em;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
            line-height: 1.2; 
            color: #333; 
        }
        .job-details strong { font-weight: 700; color: #333; }
        .job-details__line { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .job-details__line p { flex-grow: 1; flex-basis: 50%; margin-bottom: 0; }
        .job-details p:not(.job-details__line p) { margin-bottom: 4px; } 
        .ref-no-line { margin-bottom: 8px; } 
        /* Reference Number Value Style */
        .ref-no-line .value { 
            color: #B32133; 
            font-weight: 700; 
        }
        
        /* General Rating Section (Step 1) */
        .rating-group h4 {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px; 
        }
        .star-rating-container {
            padding: 0 0 20px; 
            border-bottom: 1px solid #ddd;
            margin: 10px 0 25px; 
        }
        .star-rating {
            display: flex;
            flex-direction: row-reverse; 
            justify-content: center;
            margin: 0 auto; 
            width: fit-content; 
        }
        .star-rating input { display: none; }
        .star-rating label {
            font-size: 45px;
            color: #ccc; 
            cursor: pointer;
            transition: color 0.2s;
            padding: 0 5px; 
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #FFC107; }
        
        /* Rating Labels container for general rating */
        .rating-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.85em;
            color: #555;
            margin: 5px auto 0; 
            max-width: 280px; 
            padding: 0 5px; 
            box-sizing: border-box;
        }
        .rating-labels span { white-space: nowrap; }
        .rating-labels span:first-child { margin-left: -5px; }
        .rating-labels span:last-child { margin-right: -5px; }

        /* Feedback Textarea */
        .input-group h4 { font-weight: 600; color: #333; margin-bottom: 10px; }
        .input-group textarea {
            width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ccc;
            border-radius: 5px; resize: vertical; min-height: 100px;
            box-sizing: border-box; font-family: inherit;
        }
        .input-group textarea:focus { border-color: #E87722; box-shadow: 0 0 5px rgba(232, 119, 34, .5); outline: none; }

        /* Individual Rating Section (Step 2 - MAX COMPRESSION) */
        #step2-content {
            padding-top: 0px; 
        }
        
        .cleaners-rating-list {
            margin-top: 0px; 
            margin-bottom: 15px; 
            padding-bottom: 10px; 
            border-bottom: 1px solid #ddd; 
        }
        .cleaners-rating-list h4 {
            margin-bottom: 5px; 
        }
        .driver-rating-list {
            margin-top: 5px; 
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .driver-rating-list h4 {
            margin-bottom: 5px; 
        }

        /* Single Rating Guide for the group (cleaners/driver) - Refined Alignment */
        .group-rating-guide {
            display: flex;
            justify-content: space-between; 
            font-size: 0.75em;
            color: #777;
            width: 160px; 
            margin-left: auto; 
            margin-top: -5px; 
            margin-bottom: 10px; 
        }
        .group-rating-guide span { 
            white-space: nowrap; 
        }
        
        /* Rate All Cleaners Option (Restored to V2 Style) */
        .rate-all-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f9f9f9;
            padding: 5px 10px;
            border-radius: 5px;
            margin-bottom: 10px; 
        }

        .rate-all-option span {
            font-weight: 600;
            color: #004A80;
            font-size: 0.9em;
            flex-grow: 1;
        }

        /* Reusing employee-stars for the "Rate All" option */
        .rate-all-option .employee-stars label {
            font-size: 24px; /* Slightly smaller stars for the overall option */
            padding: 0 2px;
        }

        .individual-rating-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0px; 
            padding: 2px 0; 
            line-height: 1.2;
        }
        
        .employee-name {
            font-weight: 500; 
            color: #333; 
            flex-grow: 1;
            padding-right: 15px; 
        }
        
        /* Employee Stars */
        .employee-stars {
            display: flex;
            flex-direction: row-reverse;
            width: fit-content;
        }
        .employee-stars input {
            display: none;
        }
        .employee-stars label {
            font-size: 28px; 
            color: #ccc; 
            cursor: pointer;
            transition: color 0.2s;
            padding: 0 2px;
        }
        .employee-stars input:checked ~ label,
        .employee-stars label:hover,
        .employee-stars label:hover ~ label {
            color: #FFC107; 
        }

        /* Button Group (Step 1) */
        .button-group {
            display: flex;
            justify-content: flex-end; 
            margin-top: 30px;
        }
        /* Step 2 Button Group - Aligned to the lower right */
        .button-group-step2 {
             justify-content: flex-end; 
        }
        
        /* Back Button */
        .btn-back {
            padding: .5rem 2rem; 
            background-color: #e0e0e0; 
            color: #333;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.0em; 
            font-weight: 600;
            text-align: center;
            text-decoration: none; 
            margin-right: 10px; 
            transition: background-color 0.3s ease, color 0.3s ease;
            display: inline-block; 
        }
        .btn-back:hover { background-color: #b0b0b0; color: #333; }

        /* Submit/Next Button (Primary Button) */
        .btn-submit, .btn-next {
            padding: .5rem 2rem; 
            background-color: #E87722; 
            color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .2);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.0em; 
            font-weight: 600;
            text-align: center;
            margin-left: 0; 
            transition: background-color 0.3s;
        }
        .btn-submit:hover, .btn-next:hover { background-color: #D66C1E; }
        
        /* The Error Message Style */
        .error-message {
            color: red;
            font-size: 0.9em;
            margin-top: -5px;
            margin-bottom: 10px;
            display: none;
            background-color: #ffe0e0;
            padding: 3px 8px;
            border-radius: 4px;
        }

        /* MODAL STYLES */
        .report-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.5); display: none; justify-content: center;
            align-items: center; z-index: 1000; 
        }
        .report-modal-content {
            background-color: #fff; padding: 0; border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); max-width: 400px;
            width: 90%; animation: fadeIn 0.3s ease-out; text-align: center;
        }
        .primary-btn {
            background-color: #E87722; color: #fff; padding: 10px 30px;
            border: none; border-radius: 5px; font-size: 1.1em; font-weight: 600;
            cursor: pointer; transition: background-color 0.3s; margin-top: 15px; 
        }
        .primary-btn:hover { background-color: #D66C1E; }

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

    <main class="dashboard__content">
        
        <div class="form-container">
            <h2 class="form-header"><?php echo $page_title; ?></h2>
            
            <div class="job-details">
                <p class="ref-no-line">
                    <strong>Reference No:</strong> 
                    <span class="value"><?php echo $job_details['ref_no']; ?></span>
                </p>

                <p><strong>Date:</strong> <?php echo $job_details['date']; ?></p>
                
                <div class="job-details__line">
                    <p><strong>Time:</strong> <?php echo $job_details['time']; ?></p>
                    <p><strong>Duration:</strong> <?php echo $job_details['duration']; ?></p>
                </div>

                <div class="job-details__line">
                    <p><strong>Client Type:</strong> <?php echo $job_details['client_type']; ?></p>
                    <p><strong>Service Type:</strong> <?php echo $job_details['service_type']; ?></p>
                </div>
                
                <p><strong>Address:</strong> <?php echo $job_details['address']; ?></p>
                
            </div>
            
            <form action="FR_one-time.php" method="POST" id="ratingForm" onsubmit="return validateFinalSubmission(event)">
                <input type="hidden" name="ref_no" value="<?php echo $job_details['ref_no']; ?>">
                <input type="hidden" name="action" value="<?php echo $action; ?>">

                <div id="step1-content">
                    <div class="rating-group">
                        <h4>How would you rate our service?</h4>
                        <div id="ratingError" class="error-message">Please complete this required field</div>
                        
                        <div class="star-rating-container">
                            <div class="star-rating">
                                <input type="radio" id="star5" name="rating_general" value="5">
                                <label for="star5" title="5 stars"><i class='bx bx-star'></i></label>
                                <input type="radio" id="star4" name="rating_general" value="4">
                                <label for="star4" title="4 stars"><i class='bx bx-star'></i></label>
                                <input type="radio" id="star3" name="rating_general" value="3">
                                <label for="star3" title="3 stars"><i class='bx bx-star'></i></label>
                                <input type="radio" id="star2" name="rating_general" value="2">
                                <label for="star2" title="2 stars"><i class='bx bx-star'></i></label>
                                <input type="radio" id="star1" name="rating_general" value="1">
                                <label for="star1" title="1 star"><i class='bx bx-star'></i></label>
                            </div>
                            <div class="rating-labels">
                                <span>Lowest (1)</span>
                                <span>Highest (5)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <h4>Tell us what you think:</h4>
                        <div id="feedbackError" class="error-message">Please complete this required field</div>
                        <textarea id="feedback" name="feedback_general" placeholder="Type your feedback here..."></textarea>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn-back" onclick="window.location.href='FR_one-time.php'">Back</button>
                        <button type="button" class="btn-next" onclick="validateStep1()">Next</button>
                    </div>
                </div>
                <div id="step2-content" style="display: none;">
                    
                    <div class="rating-group cleaners-rating-list">
                        <h4>Rate the Cleaning Team:</h4>
                        <div id="cleanerRatingError" class="error-message">Please rate all cleaners.</div>
                        
                        <div class="rate-all-option">
                            <span>Rate All Cleaners:</span>
                            <div class="employee-stars">
                                <input type="radio" id="bulk_star5" name="rate_all_cleaners_value" value="5" onclick="rateAllCleaners(5)">
                                <label for="bulk_star5" title="5 stars"><i class='bx bx-star'></i></label>
                                <input type="radio" id="bulk_star4" name="rate_all_cleaners_value" value="4" onclick="rateAllCleaners(4)">
                                <label for="bulk_star4" title="4 stars"><i class='bx bx-star'></i></label>
                                <input type="radio" id="bulk_star3" name="rate_all_cleaners_value" value="3" onclick="rateAllCleaners(3)">
                                <label for="bulk_star3" title="3 stars"><i class='bx bx-star'></i></label>
                                <input type="radio" id="bulk_star2" name="rate_all_cleaners_value" value="2" onclick="rateAllCleaners(2)">
                                <label for="bulk_star2" title="2 stars"><i class='bx bx-star'></i></label>
                                <input type="radio" id="bulk_star1" name="rate_all_cleaners_value" value="1" onclick="rateAllCleaners(1)">
                                <label for="bulk_star1" title="1 star"><i class='bx bx-star'></i></label>
                            </div>
                        </div>

                        <div class="group-rating-guide">
                            <span>Lowest (1)</span>
                            <span>Highest (5)</span>
                        </div>

                        <?php foreach ($job_details['cleaners'] as $index => $cleaner): ?>
                            <?php $cleaner_key = 'rating_cleaner_' . ($index + 1); // Consistent naming scheme ?>
                            <div class="individual-rating-item">
                                <span class="employee-name"><?php echo htmlspecialchars($cleaner); ?></span>
                                <div class="employee-stars">
                                    <input type="radio" id="<?php echo $cleaner_key; ?>_star5" name="<?php echo $cleaner_key; ?>" value="5">
                                    <label for="<?php echo $cleaner_key; ?>_star5" title="5 stars"><i class='bx bx-star'></i></label>
                                    <input type="radio" id="<?php echo $cleaner_key; ?>_star4" name="<?php echo $cleaner_key; ?>" value="4">
                                    <label for="<?php echo $cleaner_key; ?>_star4" title="4 stars"><i class='bx bx-star'></i></label>
                                    <input type="radio" id="<?php echo $cleaner_key; ?>_star3" name="<?php echo $cleaner_key; ?>" value="3">
                                    <label for="<?php echo $cleaner_key; ?>_star3" title="3 stars"><i class='bx bx-star'></i></label>
                                    <input type="radio" id="<?php echo $cleaner_key; ?>_star2" name="<?php echo $cleaner_key; ?>" value="2">
                                    <label for="<?php echo $cleaner_key; ?>_star2" title="2 stars"><i class='bx bx-star'></i></label>
                                    <input type="radio" id="<?php echo $cleaner_key; ?>_star1" name="<?php echo $cleaner_key; ?>" value="1">
                                    <label for="<?php echo $cleaner_key; ?>_star1" title="1 star"><i class='bx bx-star'></i></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="rating-group driver-rating-list">
                        <h4>Rate the Driver:</h4>
                        <div id="driverRatingError" class="error-message">Please rate the driver.</div>
                        
                        <div class="group-rating-guide">
                            <span>Lowest (1)</span>
                            <span>Highest (5)</span>
                        </div>

                        <div class="individual-rating-item">
                            <span class="employee-name"><?php echo htmlspecialchars($job_details['driver']); ?></span>
                            <div class="employee-stars">
                                <input type="radio" id="rating_driver_star5" name="rating_driver" value="5">
                                <label for="rating_driver_star5" title="5 stars"><i class='bx bx-star'></i></label>
                                <input type="radio" id="rating_driver_star4" name="rating_driver" value="4">
                                <label for="rating_driver_star4" title="4 stars"><i class='bx bx-star'></i></label>
                                <input type="radio" id="rating_driver_star3" name="rating_driver" value="3">
                                <label for="rating_driver_star3" title="3 stars"><i class='bx bx-star'></i></label>
                                <input type="radio" id="rating_driver_star2" name="rating_driver" value="2">
                                <label for="rating_driver_star2" title="2 stars"><i class='bx bx-star'></i></label>
                                <input type="radio" id="rating_driver_star1" name="rating_driver" value="1">
                                <label for="rating_driver_star1" title="1 star"><i class='bx bx-star'></i></label>
                            </div>
                        </div>
                        
                    </div>
                    
                    <div class="button-group button-group-step2">
                        <button type="button" class="btn-back" onclick="showStep1()">Back</button>
                        <button type="submit" class="btn-submit"><?php echo $submit_button_text; ?></button>
                    </div>
                </div>
                </form>
        </div>
        
    </main>

    <div class="report-modal" id="reportSuccessModal" onclick="if(event.target.id === 'reportSuccessModal') redirectToPage()">
        <div class="report-modal-content">
            <div style="padding: 20px;">
                <i class='bx bx-check-circle' style="font-size: 4em; color: #00A86B; margin-bottom: 10px;"></i>
                <h3 id="modalTitle" style="border-bottom: none; margin-bottom: 10px;"></h3>
                
                <p id="success-message" style="color: #555; font-size: 1em;">
                    <span id="modalBodyText"></span> for Ref: <span id="submitted-ref-number" style="color: #B32133; font-weight: 700;"></span> has been successfully recorded.
                </p>
                
                <button onclick="redirectToPage()" class="primary-btn report-confirm-btn">
                    Got It
                </button>
            </div>
        </div>
    </div>
    <script>
        // Store PHP variables in JS constants
        const REF_NO = "<?php echo $job_details['ref_no']; ?>";
        const REDIRECT_URL = "FR_one-time.php"; 
        const ACTION = "<?php echo $action; ?>"; // 'leave' or 'edit'

        // List of all employee rating names
        const employeeRatingNames = [
            <?php foreach ($job_details['cleaners'] as $index => $cleaner): ?>
                <?php echo "'rating_cleaner_" . ($index + 1) . "',"; ?>
            <?php endforeach; ?>
            'rating_driver'
        ];

        // --- Cleaners Bulk Rating Functions ---
        
        function rateAllCleaners(value) {
            // Rate all cleaners based on the value clicked in the bulk rating stars
            const cleanerNames = employeeRatingNames.slice(0, -1); 

            cleanerNames.forEach(name => {
                const radioId = `${name}_star${value}`;
                const radio = document.getElementById(radioId);
                
                if (radio) {
                    radio.checked = true;
                }
            });
            
            // Re-hide the cleaner rating error message after applying the bulk rating
            document.getElementById('cleanerRatingError').style.display = 'none';
        }

        // --- STEP NAVIGATION ---

        function showStep1() {
            document.getElementById('step1-content').style.display = 'block';
            document.getElementById('step2-content').style.display = 'none';
        }

        function showStep2() {
            document.getElementById('step1-content').style.display = 'none';
            document.getElementById('step2-content').style.display = 'block';
            // Scroll to the top of the form when navigating to step 2
             document.querySelector('.form-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // --- STEP 1 VALIDATION ---
        function validateStep1() {
            let isValid = true;

            // 1. Validate General Star Rating
            const ratingRadios = document.getElementsByName('rating_general');
            let ratingChecked = false;
            for (let i = 0; i < ratingRadios.length; i++) {
                if (ratingRadios[i].checked) {
                    ratingChecked = true;
                    break;
                }
            }
            const ratingError = document.getElementById('ratingError');
            if (!ratingChecked) {
                ratingError.style.display = 'inline-block';
                isValid = false;
            } else {
                ratingError.style.display = 'none';
            }

            // 2. Validate General Feedback Textarea
            const feedbackTextarea = document.getElementById('feedback');
            const feedbackError = document.getElementById('feedbackError');
            if (feedbackTextarea.value.trim() === "") {
                feedbackError.style.display = 'inline-block';
                isValid = false; 
            } else {
                feedbackError.style.display = 'none';
            }
            
            if (isValid) {
                showStep2(); // Move to the next step
            }
            
            return isValid;
        }

        // --- STEP 2 VALIDATION AND FINAL SUBMISSION ---
        function validateFinalSubmission(event) {
            event.preventDefault(); // Prevent default submission initially
            
            let isValid = true;
            let firstUnratedElement = null;

            // 1. Validate All Employee Ratings
            const cleanerError = document.getElementById('cleanerRatingError');
            const driverError = document.getElementById('driverRatingError');

            let allCleanersRated = true;
            let driverRated = false;
            
            // A. Check Cleaners
            const cleanerNames = employeeRatingNames.slice(0, -1);
            cleanerNames.forEach(name => {
                let ratingChecked = false;
                const radios = document.getElementsByName(name);
                for (let i = 0; i < radios.length; i++) {
                    if (radios[i].checked) {
                        ratingChecked = true;
                        break;
                    }
                }
                if (!ratingChecked) {
                    allCleanersRated = false;
                    if (!firstUnratedElement) {
                        // Find the container element to scroll to
                        firstUnratedElement = radios[0].closest('.cleaners-rating-list');
                    }
                }
            });

            if (!allCleanersRated) {
                cleanerError.style.display = 'inline-block';
                isValid = false;
            } else {
                cleanerError.style.display = 'none';
            }
            
            // B. Check Driver
            const driverRadios = document.getElementsByName('rating_driver');
            for (let i = 0; i < driverRadios.length; i++) {
                if (driverRadios[i].checked) {
                    driverRated = true;
                    break;
                }
            }

            if (!driverRated) {
                driverError.style.display = 'inline-block';
                isValid = false;
                if (!firstUnratedElement) {
                    firstUnratedElement = driverRadios[0].closest('.driver-rating-list');
                }
            } else {
                driverError.style.display = 'none';
            }

            // 2. Handle final submission logic
            if (isValid) {
                // If all good, we simulate successful submission and show modal

                // Determine Modal Text based on ACTION
                let titleText;
                let bodyText;

                if (ACTION === 'edit') {
                    titleText = 'Rating Updated!';
                    bodyText = 'Your updated rating';
                } else {
                    titleText = 'Rating Submitted!';
                    bodyText = 'Thank you! Your rating';
                }

                // 1. Set the dynamic text inside the modal
                document.getElementById('modalTitle').textContent = titleText;
                document.getElementById('modalBodyText').textContent = bodyText;
                document.getElementById('submitted-ref-number').textContent = REF_NO;

                // 2. SHOW MODAL
                const modal = document.getElementById('reportSuccessModal');
                modal.style.display = 'flex';
                
                return false; 
            }
            
            // If validation fails, scroll to the first unrated element/error
            if (!isValid && firstUnratedElement) {
                 firstUnratedElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            return false; 
        }

        // Function to hide modal and redirect the user
        function redirectToPage() {
             document.getElementById('reportSuccessModal').style.display = 'none';
             // Simulating the redirect after successful submission
             window.location.href = REDIRECT_URL + "?status=success&ref=" + REF_NO;
        }
    </script>
    
    </body>
</html>