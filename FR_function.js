/**
 * Global function to switch tabs and display appointment details modal.
 * This function will search for the specific appointment item and call showDetailsModal.
 * @param {HTMLElement} buttonElement - The 'View Appointment' button clicked.
 */
function viewAppointmentFromRating(buttonElement) {
    // 1. Get data from the parent rating-list-item
    const listItem = buttonElement.closest('.rating-list-item');
    const refNo = listItem.getAttribute('data-ref-no');
    const targetTabId = listItem.getAttribute('data-service-tab'); // e.g., 'checkout-cleaning'

    if (!refNo || !targetTabId) {
        console.error("Missing Reference Number or Target Tab ID.");
        return;
    }

    // 2. Switch to the target tab
    // openTab is assumed to be defined in HIS_function.js or client_db.js
    // We need to simulate the click event for the tab button
    const tabButton = document.querySelector(`.service-tabs-bar .tab-button[onclick*="'${targetTabId}'"]`);
    if (tabButton) {
        // Trigger the tab switch (active class and display change)
        const event = new Event('click');
        openTab(event, targetTabId); 
    }

    // 3. Search for the specific appointment item within the new active tab's list
    // The list ID follows the pattern: 'checkout-cleaning-list'
    const appointmentListContainer = document.getElementById(targetTabId + '-list');
    if (appointmentListContainer) {
        // Find the specific item using the data-ref-no attribute (added to all list items in the main tabs)
        const targetAppointmentItem = appointmentListContainer.querySelector(`.appointment-list-item[data-ref-no="${refNo}"]`);
        
        if (targetAppointmentItem) {
            // Scroll to the item for emphasis (optional but helpful)
            targetAppointmentItem.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Highlight the item temporarily (optional)
            targetAppointmentItem.style.border = '2px solid #E87722';
            setTimeout(() => {
                targetAppointmentItem.style.border = '1px solid #ddd';
            }, 3000); 
            
            // Show the details modal using the existing function (assumed to be defined)
            showDetailsModal(targetAppointmentItem);
        } else {
            console.warn(`Appointment item not found in ${targetTabId} for Ref No: ${refNo}`);
            // You might want to show a notification here if the item is truly missing (shouldn't happen with correct data)
        }
    }
}


/**
 * Shows the View Rating modal and populates it with mock data from the list item.
 * Works for both the main service tabs AND the Ratings Summary tab (since it now contains all data attributes).
 * @param {HTMLElement} listItem - The parent appointment-list-item OR rating-list-item element.
 */
function showViewRatingModal(listItem) {
    // 1. Get data from the list item
    // MODIFIED: Use data-ref-no which is present on both service list items and rating list items.
    let refNo = listItem.dataset.refNo || 'N/A';
    
    // Get mock rating data from data attributes
    // Use 'data-rating-stars' and 'data-rating-feedback' which should be present in all items that are RATED.
    const stars = listItem.dataset.ratingStars || '0';
    const feedback = listItem.dataset.ratingFeedback || 'No feedback provided.';
    
    // GET ALL APPOINTMENT DETAILS FROM DATA ATTRIBUTES
    // These attributes are expected to be present on the list item, regardless of which tab it is from.
    const date = listItem.dataset.date || 'N/A';
    // Convert time from 24h (e.g., "14:00") to 12h format (e.g., "2:00 PM")
    let time24 = listItem.dataset.time || '00:00';
    let timeParts = time24.split(':');
    let hours = parseInt(timeParts[0]);
    let minutes = timeParts[1];
    let ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // The hour '0' should be '12'
    const formattedTime = hours + ':' + minutes + ' ' + ampm;
    
    const duration = listItem.dataset.duration || 'N/A';
    const clientType = listItem.dataset.clientType || 'N/A';
    const serviceType = listItem.dataset.serviceType || 'N/A';
    const address = listItem.dataset.address || 'N/A';

    // 2. MODIFIED: CONSTRUCT AND POPULATE APPOINTMENT DETAILS 
    // Inayos ang margin at width para magkaroon ng 25px na agwat sa pagitan ng magkatabing detalye.
    const appointmentDetailsHtml = `
        <div style="font-size: 1em; line-height: 1.3;">
            <p style="margin: 0 0 5px 0; padding: 0; width: 100%;"><strong>Date:</strong> ${date}</p>
            
            <p style="margin: 5px 25px 5px 0; padding: 0; display: inline-block; width: 40%;"><strong>Time:</strong> ${formattedTime}</p>
            <p style="margin: 5px 0; padding: 0; display: inline-block; width: 50%; vertical-align: top;"><strong>Duration:</strong> ${duration}</p>
            
            <p style="margin: 5px 25px 5px 0; padding: 0; display: inline-block; width: 40%;"><strong>Client Type:</strong> ${clientType}</p>
            <p style="margin: 5px 0; padding: 0; display: inline-block; width: 50%; vertical-align: top;"><strong>Service Type:</strong> ${serviceType}</p>
            
            <p style="margin: 5px 0 0 0; padding: 0; width: 100%;"><strong>Address:</strong> ${address}</p>
        </div>
    `;


    // 3. Populate the modal content (Rating Details)
    // Tinitiyak na ang Reference No. na nasa static HTML ay napupunan
    document.getElementById('viewRefNo').textContent = refNo;
    
    // Popunan ang Appointment Details Grid
    document.getElementById('viewAppointmentDetails').innerHTML = appointmentDetailsHtml;
    
    // Popunan ang Feedback
    document.getElementById('viewFeedback').textContent = feedback;
    
    // 4. Display the stars (using boxicons filled/empty stars) - MODIFIED LOGIC
    let starsHtml = '';
    const rating = parseInt(stars);
    for(let i = 0; i < 5; i++) {
        // Binago ang icon sa 'bxs-star' at 'bx-star' para sa filled/empty stars
        if (i < rating) {
            // Binago ang style dito para maging inline at mas malaki
            starsHtml += "<i class='bx bxs-star' style='font-size: 1.8em; vertical-align: middle;'></i>"; // Filled Star
        } else {
            // Binago ang style dito para maging inline at mas malaki
            starsHtml += "<i class='bx bx-star' style='font-size: 1.8em; vertical-align: middle;'></i>"; // Empty Star
        }
    }
    // Binago ang target mula sa 'viewStars' patungo sa 'viewStarsContainer'
    document.getElementById('viewStarsContainer').innerHTML = starsHtml;

    // 5. Update the Edit link inside the modal (Inilipat dito ang More Options link)
    const editLink = `FR_one-time_form.php?action=edit&ref=${refNo}`;
    document.getElementById('editRatingLinkInModal').setAttribute('href', editLink);
    
    // 6. Show the modal
    let modal = document.getElementById('viewRatingModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}


/**
 * Updates the custom file input text with the selected file name.
 * @param {HTMLInputElement} input - The file input element.
 */
function updateFileName(input) {
    const textElement = document.getElementById('file-name-' + input.id.slice(-1));
    if (input.files.length > 0) {
        // Display only the file name (N...en)
        textElement.textContent = input.files[0].name;
    } else {
        textElement.textContent = 'No file chosen';
    }
}

/**
 * Captures the current system date and time and populates the hidden fields
 * for admin audit trail upon submission.
 */
function captureSubmissionTime() {
    const now = new Date();
    
    // Format Date: YYYY-MM-DD
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const formattedDate = `${year}-${month}-${day}`;
    
    // Format Time: HH:MM:SS
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const formattedTime = `${hours}:${minutes}:${seconds}`;

    // Set the values to the hidden input fields
    document.getElementById('submissionDate').value = formattedDate;
    document.getElementById('submissionTime').value = formattedTime;
}

/**
 * Clears the associated error message for a field in real-time.
 * @param {HTMLElement} element - The input, select, or textarea element.
 */
function clearError(element) {
    let errorElementId;
    let shouldClear = false;

    // Determine the ID of the corresponding error message div and check its content
    if (element.id === 'issueType') {
        // Check if a valid option (non-empty value) has been selected
        if (element.value !== "") {
            errorElementId = 'issueTypeError';
            shouldClear = true;
        }
    } else if (element.id === 'issueDetails') {
        // Check if the textarea has non-whitespace content
        if (element.value.trim() !== "") {
            errorElementId = 'issueDetailsError';
            shouldClear = true;
        }
    }

    if (shouldClear) {
        const errorElement = document.getElementById(errorElementId);
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }
}


/**
 * KEY CHANGE: Handles the final submission process, closing the issue modal 
 * and opening the success modal, OR displaying individual field errors.
 * @returns {boolean} Always returns false to prevent standard form submission (since we don't have a backend yet).
 */
function submitReport() {
    // Elements
    const issueType = document.getElementById('issueType');
    const issueDetails = document.getElementById('issueDetails');
    
    // New Error Message Elements
    const issueTypeError = document.getElementById('issueTypeError');
    const issueDetailsError = document.getElementById('issueDetailsError');

    let isValid = true;
    
    // Reset ONLY the error message visibility (just in case they were hidden by clearError())
    issueTypeError.style.display = 'none'; 
    issueDetailsError.style.display = 'none'; 

    // 1. Validate Type of Issue 
    if (issueType.value === "") {
        issueTypeError.style.display = 'inline-block'; // Show error message
        isValid = false;
    }

    // 2. Validate Issue Description 
    if (issueDetails.value.trim() === "") {
        issueDetailsError.style.display = 'inline-block'; // Show error message
        isValid = false;
    }
    
    // 3. Handle Invalid Submission (MODIFIED TO NO LONGER SHOW THE GENERAL MODAL)
    if (!isValid) {
        // Optional: Focus on the first invalid field
        if (issueType.value === "") {
            issueType.focus();
        } else if (issueDetails.value.trim() === "") {
            issueDetails.focus();
        }
        
        // IMPORTANT: Return false to prevent form submission and stop the success modal logic
        return false;
    }

    // --- START: Original Success Logic (Only runs if isValid is true) ---

    // 1. Capture the exact submission time for audit trail
    captureSubmissionTime();

    // 2. Get the Reference Number to display in the success modal
    const refNumber = document.getElementById('report-ref-number').textContent;

    // 3. Close the main Report Issue Modal
    closeModal('reportIssueModal');

    // 4. Update the success modal with the Ref Number
    document.getElementById('submitted-ref-number').textContent = refNumber;

    // 5. Open the success modal
    let successModal = document.getElementById('reportSuccessModal');
    if (successModal) {
        modal.style.display = 'block'; 
    }

    // We return false to prevent the form from actually submitting and refreshing the page
    return false;
}

/**
 * Shows the Report Issue modal and populates data from the list item.
 * @param {HTMLElement} element - The 'Report Issue' link element.
 */
function showReportModal(element) {
    // 1. Find the parent appointment-list-item
    let listItem = element.closest('.appointment-list-item');
    if (!listItem) return; 

    // 2. Get the required data attributes (Reference No, Date, Time)
    let refNoElement = listItem.querySelector('.ref-no-value');
    let refNo = refNoElement ? refNoElement.textContent.trim() : 'N/A';
    
    // Get the values from the appointment details 
    // These are in the required ISO format (YYYY-MM-DD for date, HH:MM for time) from data attributes.
    let dateText = listItem.dataset.date || ''; // Get ISO date (YYYY-MM-DD) from data-date
    let timeText = listItem.dataset.time || ''; // Get ISO time (HH:MM) from data-time 
    
    // 3. Populate the modal content
    document.getElementById('report-ref-number').textContent = refNo;
    
    // Populate Date and Time with ISO format from data attributes
    document.getElementById('issueDate').value = dateText;
    document.getElementById('issueTime').value = timeText;
    
    // Reset form fields
    document.getElementById('issueType').selectedIndex = 0;
    document.getElementById('issueDetails').value = '';
    
    // NEW: Hide individual error messages when opening the modal
    document.getElementById('issueTypeError').style.display = 'none';
    document.getElementById('issueDetailsError').style.display = 'none';
    
    // Reset file inputs and their display text
    document.getElementById('attachment1').value = '';
    document.getElementById('file-name-1').textContent = 'No file chosen';
    document.getElementById('attachment2').value = '';
    document.getElementById('file-name-2').textContent = 'No file chosen';
    document.getElementById('attachment3').value = '';
    document.getElementById('file-name-3').textContent = 'No file chosen';
    
    // 4. Show the modal
    let modal = document.getElementById('reportIssueModal');
    if (modal) {
        modal.style.display = 'block';
        
        // Close any open dropdown menu
        let openDropdown = document.querySelector('.dropdown-menu-container .dropdown-menu.show');
        if (openDropdown) {
            openDropdown.classList.remove('show');
        }
    }
}

/**
 * Closes any modal based on its ID.
 * @param {string} modalId - The ID of the modal to close.
 */
function closeModal(modalId) {
    let modal = document.getElementById(modalId);
    if (modal) {
        // Use 'none' for modals that originally use 'flex'
        if (modalId === 'detailsModal' || modalId === 'reportIssueModal' || modalId === 'reportSuccessModal' || modalId === 'datePickerModal' || modalId === 'viewRatingModal') {
            modal.style.display = 'none';
        } 
        // Use 'none' for modals that originally use 'block' or are simple
        else if (modalId === 'logoutModal' || modalId === 'requiredFieldsModal') {
            modal.style.display = 'none';
        }
    }
}