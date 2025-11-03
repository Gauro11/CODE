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
document.getElementById('viewFeedback').textContent = feedback;

    
    
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
/**
 * Submit issue report
 */
function submitReport() {
    document.getElementById('issueTypeError').style.display = 'none';
    document.getElementById('issueDetailsError').style.display = 'none';
    
    const issueType = document.getElementById('issueType').value;
    const issueDetails = document.getElementById('issueDetails').value;

    if (!issueType) {
        document.getElementById('issueTypeError').style.display = 'block';
        return false;
    }
    if (!issueDetails.trim()) {
        document.getElementById('issueDetailsError').style.display = 'block';
        return false;
    }

    const bookingId = document.getElementById('report-booking-id').value;
    const refNumber = document.getElementById('report-ref-number').textContent;

    const formData = new FormData();
    formData.append('report_issue', '1');
    formData.append('booking_id', bookingId);
    formData.append('issue_type', issueType);
    formData.append('issue_description', issueDetails);

    formData.append('issue_date', document.getElementById('issueDate').value);
    formData.append('issue_time', document.getElementById('issueTime').value);

    formData.append('submission_date', document.getElementById('submissionDate').value);
    formData.append('submission_time', document.getElementById('submissionTime').value);

    formData.append('attachment1', document.getElementById('attachment1').files[0] || '');
    formData.append('attachment2', document.getElementById('attachment2').files[0] || '');
    formData.append('attachment3', document.getElementById('attachment3').files[0] || '');

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        closeModal('reportIssueModal');
        document.getElementById('submitted-ref-number').textContent = refNumber;
        document.getElementById('reportSuccessModal').style.display = 'flex';

        setTimeout(() => location.reload(), 2000);
    })
    .catch(err => {
        alert("Error submitting report.");
        console.log(err);
    });

    return false;
}


/**
 * Clear error message when user starts typing/selecting
 */
function clearError(element) {
    const errorId = element.id + 'Error';
    const errorElement = document.getElementById(errorId);
    if (errorElement) {
        errorElement.style.display = 'none';
    }
}

/**
 * Update file name display when file is selected
 */
function updateFileName(input) {
    const fileNameDisplay = input.parentElement.querySelector('.custom-file-text');
    if (input.files && input.files[0]) {
        fileNameDisplay.textContent = input.files[0].name;
    } else {
        fileNameDisplay.textContent = 'No file chosen';
    }
}

/**
 * Shows the Report Issue modal and populates data from the list item.
 * @param {HTMLElement} element - The 'Report Issue' link element.
 */
// function showReportModal(element) {
//     // ✅ Read data sent from the button
//     const bookingId = element.dataset.bookingId;
//     const refNumber = element.dataset.refNo;
//     const date = element.dataset.date;
//     const time = element.dataset.time;

//     // ✅ Send to modal
//     document.getElementById('report-ref-number').textContent = refNumber;
//     document.getElementById('report-booking-id').value = bookingId;
//     document.getElementById('issueDate').value = date;
//     document.getElementById('issueTime').value = time;

//     // ✅ Auto-fill submission (current) date & time
//     const now = new Date();
//     document.getElementById('submissionDate').value = now.toISOString().split('T')[0];
//     document.getElementById('submissionTime').value = now.toTimeString().slice(0, 5);

//     // ✅ Reset select/textarea
//     document.getElementById('issueType').value = '';
//     document.getElementById('issueDetails').value = '';
//     document.querySelectorAll('.custom-file-text').forEach(e => e.textContent = 'No file chosen');

//     // ✅ Open modal
//     document.getElementById('reportIssueModal').style.display = 'flex';
// }

// ✅ Update file label
function updateFileName(input) {
    const label = input.nextElementSibling.nextElementSibling;
    label.textContent = input.files.length > 0 ? input.files[0].name : 'No file chosen';
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