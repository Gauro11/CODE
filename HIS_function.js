/**
 * FILENAME: HIS_function.js
 * DESCRIPTION: In-page script for handling specific history dropdowns,
 * filtering, modal displays (View Details, Report Issue, Logout),
 * and tab navigation for the history pages.
 */

// ==============================================================================
// === DROPDOWN AND GENERIC MODAL HANDLING ===
// ==============================================================================

// --- History Contents Dropdown Logic (Existing) ---
/**
 * Toggles the visibility of a history item's dropdown menu and closes others.
 * @param {HTMLElement} button - The clicked 'more options' button.
 */
function toggleDropdown(button) {
    const dropdown = button.closest('.dropdown-menu-container').querySelector('.dropdown-menu');

    // Close other open dropdowns
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (menu !== dropdown) {
            menu.classList.remove('show');
        }
    });

    // Toggle the clicked dropdown
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
window.onclick = function(event) {
    if (!event.target.matches('.more-options-btn') && !event.target.matches('.more-options-btn i')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
}

// --- Generic Close Modal Function (Centralized) ---
/**
 * Closes any modal based on its ID and handles body scroll fix.
 * Note: This function is duplicated/redefined in the original code,
 * the first definition is used for the scroll fix, and the last one
 * is simpler and used for other modals. I will keep the scroll-fix version
 * here as it's more robust, but include the functionality of the simple one.
 *
 * @param {string} modalId - The ID of the modal to close.
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        // First definition logic (for scroll fix)
        if(modalId === 'detailsModal' || modalId === 'datePickerModal') {
            // Remove the aggressive class
            modal.classList.remove('is-visible-now');
            
            // FIX: Ito ang nagpapahintulot na bumukas ulit ang modal.
            modal.style.removeProperty('display');
            
            // TANGGALIN ITO: Tanggalin ang body class sa pagsara (Scroll fix)
            document.body.classList.remove('modal-open-body-no-scroll');
        } else {
             // Second definition logic (for other simple modals)
             modal.style.display = 'none';
        }
    }
   
    // Clear the date inputs if the date picker modal is closed
    if (modalId === 'datePickerModal') {
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
    }
}

// ==============================================================================
// === VIEW DETAILS MODAL LOGIC (One-Time Service Version) ===
// ==============================================================================
/**
 * Helper function to get the appropriate icon for the status tag for ONE-TIME services.
 */
function getOneTimeStatusIcon(status) {
    if (typeof status !== 'string') return 'bx-info-circle';

    const normalizedStatus = status.toUpperCase().trim();

    switch (normalizedStatus) {
        // --- SHARED / RECURRING ICONS, using ONE-TIME's preferred versions ---
        case 'ACTIVE':
            return 'bx-play-circle'; 
        case 'COMPLETED':
            return 'bx-check-circle'; // <--- FIXED: Tamang icon para sa One-Time
        case 'PAUSED':
            return 'bx-pause-circle';
        case 'PENDING':
            return 'bx-hourglass'; // <--- FIXED: Tamang icon para sa One-Time
        case 'TERMINATED':
            return 'bx-x-circle'; 

        // --- ONE-TIME SPECIFIC STATUSES (Para sa showDetailsModal) ---
        case 'CONFIRMED':
            return 'bx-calendar-check'; 
        case 'ONGOING':
            return 'bx-loader-circle';
        case 'CANCELLED':
            return 'bx-x-circle'; 
        case 'NO SHOW':
            return 'bx-user-minus';
            
        default:
            return 'bx-info-circle'; 
    }
}

/**
 * Custom function to extract text and clean up labels from the item details.
 * Specifically for the one-time service list item structure.
 */
const getText = (detailsContainer, selector, label) => {
    // Tiyaking ma-capture ang text kahit iba ang icon class sa service type
    let element = detailsContainer.querySelector(selector);
    
    // Handle Client Type icon variations
    if (!element && (label === 'Client Type:' || label === 'Client Type')) {
        element = detailsContainer.querySelector('.bx-building-house, .bx-home-heart');
    } else if (!element && (label === 'Service Type:' || label === 'Service Type')) {
         element = detailsContainer.querySelector('.bx-wrench, .bx-water, .bx-wind');
    }

    if (!element) return 'N/A';
    
    const parentText = element.closest('p')?.textContent || '';
    const cleanedText = parentText.replace(new RegExp(`\\b${label}:\\s*|Client Type:\\s*|Service Type:\\s*`, 'i'), '').trim(); 
    return cleanedText || 'N/A';
}


/**
 * Helper for Media Links - Strictly outputs a list item with the link text or N/A.
 */
const getMediaLink = (url, label) => {
    if (!url || url.trim() === '') { 
        const icon = label.includes('Video') ? 'bx-video-recording' : 'bx-image';
        // UPDATED: Gamitin ang "Not Applicable"
        return `<li><i class='bx ${icon}' style="font-size: 1em; margin-right: 5px;"></i> ${label}: <span style="font-style: italic; color: #aaa;">Not Applicable</span></li>`;
    }
    
    const isVideo = url.toLowerCase().includes('.mp4') || url.toLowerCase().includes('.mov') || url.toLowerCase().includes('video');
    const icon = isVideo ? 'bx-video-recording' : 'bx-image';
    const linkText = `<a href="${url}" target="_blank" style="color: #007bff; text-decoration: underline;">View File</a>`;
    
    // Output: "• Image/Video 1: View File"
    return `<li><i class='bx ${icon}' style="font-size: 0.95em; margin-right: 5px;"></i> ${label}: ${linkText}</li>`;
};

/**
 * Extracts details from the one-time appointment list item and displays them in the 'detailsModal'.
 * Uses the complex Flexbox/CSS layout for modal content.
 * @param {HTMLElement} itemElement - The parent .appointment-list-item element.
 */
function showDetailsModal(itemElement) {
    const detailsModal = document.getElementById('detailsModal');
    const modalContent = document.getElementById('modal-details-content');
    
    // Check if the parameter is the list item or the button itself (to support both one-time and recurring logic)
    if (itemElement && itemElement.closest('.appointment-list-item')) {
        itemElement = itemElement.closest('.appointment-list-item');
    } else if (!itemElement.classList.contains('appointment-list-item')) {
        // Fallback/Safety Check for the recurring logic's showDetailsModal
         
        // In this merged file, the one-time version is the first definition.
    }
    
    if (!detailsModal || !itemElement || !modalContent) return;

    // Helper for consistent divider
    const dividerHtml = `<hr style="border: 0; border-top: 1px solid #ccc; margin: 15px 0;">`;

    // Style for content box (Blue border - for YES, Property Layout, Request)
    const contentBoxStyle = `background-color: #f7f7f7; padding: 10px; border-radius: 4px; border-left: 3px solid #007bff; color: #333; margin-top: 5px; font-size: 0.95em;`;
    // Style for N/A content box (Grey border - for NO, Not provided)
    const naBoxStyle = `background-color: #f7f7f7; padding: 10px; border-radius: 4px; border-left: 3px solid #ccc; font-style: italic; color: #aaa; margin-top: 5px; font-size: 0.95em;`;

    try {
        const detailsContainer = itemElement.querySelector('.appointment-details');
        let html = '';
        
        // --- 1. Extract Details from Summary & Data Attributes ---
        const refNo = detailsContainer.querySelector('.ref-no-value')?.textContent || 'N/A';
        
        // Get status from data attribute
        const statusRaw = itemElement.getAttribute('data-status') || 'UNKNOWN';
        const status = statusRaw.toUpperCase(); 
        
        // BAGONG LOGIC: I-CREATE lang ang Status Tag HTML at I-STORE sa variable
        const statusClass = statusRaw.toLowerCase().replace(/ /g, '-'); 
        const iconClass = getOneTimeStatusIcon(status); // <--- Kukunin ang icon sa tamang function
            
        // GAGAWIN ITONG VARIABLE na gagamitin sa ROW 1
        const statusTagHtml = `
            <span class="status-tag ${statusClass}">
                <i class='bx ${iconClass}'></i> 
                ${status}
            </span>`;
        
        const date = getText(detailsContainer, '.bx-calendar-check', 'Date');
        const time = getText(detailsContainer, '.bx-time', 'Time');
        const address = getText(detailsContainer, '.bx-map-alt', 'Address');
        const serviceType = getText(detailsContainer, '.service-type-detail', 'Service Type');
        
        // FIX: Kukunin ang raw AED value (hal: "AED 500") mula sa span na may aed-color
        const priceElement = detailsContainer.querySelector('.price-detail');
        const priceValue = priceElement ? priceElement.querySelector('.aed-color')?.textContent || '0' : '0';
        
        const durationDetailElement = detailsContainer.querySelector('.duration-detail');
        const durationRaw = durationDetailElement ? durationDetailElement.textContent.replace('Duration:', '').trim() : 'N/A';
        const duration = durationRaw.replace('hours', '').trim(); 
        
        const clientType = getText(detailsContainer, null, 'Client Type');
        
        const layout = itemElement.getAttribute('data-property-layout') || 'N/A';
        const materialsRequired = itemElement.getAttribute('data-materials-required') || 'N/A';
        const materialDescription = itemElement.getAttribute('data-materials-description') || 'N/A';
        const request = itemElement.getAttribute('data-additional-request') || 'None';
        const img1 = itemElement.getAttribute('data-image-1') || '';
        const img2 = itemElement.getAttribute('data-image-2') || '';
        const img3 = itemElement.getAttribute('data-image-3') || '';


        // Generate Media Links HTML (Using UL/LI)
        let mediaLinks = `
            <ul style="list-style-type: none; margin: 5px 0 0 0; padding: 0; font-size: 0.95em;">
                ${getMediaLink(img1, 'Image/Video 1')}
                ${getMediaLink(img2, 'Image/Video 2')}
                ${getMediaLink(img3, 'Image/Video 3')}
            </ul>
        `;
        
        // --- 2. Build HTML according to the Requested Layout (FINAL FIXED & ALIGNED) ---
        
        // ************ ROW 1: Reference No. at Status Tag (Flexbox Layout) ************
        html += `<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">`; // <--- FLEX CONTAINER
            
            // Reference No. sa KALIWA
            html += `<p style="font-size: 1.1em; padding-top: 0; margin-bottom: 0;"><strong>Reference No:</strong> <span style="color: #B32133; font-weight: bold;">${refNo}</span></p>`;
            
            // Status Tag sa KANAN (Gamit ang pre-calculated statusTagHtml)
            html += statusTagHtml; // <<< DITO ILALABAS ANG STATUS TAG
        
        html += `</div>`;


        // ************ ROW 2: Date (Standalone - New Line) ************
        html += `<p style="font-size: 0.95em; margin-bottom: 5px;"><strong>Date:</strong> <span>${date}</span></p>`;

        // ************ ROW 3: Time at Duration (Aligned sa 40px margin) ************
        html += `<div style="display: flex; margin-bottom: 10px; flex-wrap: wrap; font-size: 0.95em;">`;
            // Time: 30% width (Aligned sa Client Type)
            html += `<div style="flex: 0 0 30%;"><strong>Time:</strong> <span>${time}</span></div>`; 
            // Duration: 40px margin-left
            html += `<div style="margin-left: 40px;"><strong>Duration (Hours):</strong> <span>${duration}</span></div>`; 
        html += `</div>`;


        // ************ ROW 4: Address (Standalone - New Line) ************
        html += `<p style="font-size: 0.95em; margin-bottom: 0;"><strong>Address:</strong> <span>${address}</span></p>`;

        // --- Separator 1 ---
        html += dividerHtml; 

        // ROW 5: Client Type / Service Type (Alignment Reference)
        html += `<div style="display: flex; margin-bottom: 10px; flex-wrap: wrap; font-size: 0.95em;">`;
            html += `<div style="flex: 0 0 30%;"><strong>Client Type:</strong> <span>${clientType}</span></div>`; 
            // Service Type: 40px margin-left
            html += `<div style="margin-left: 40px;"><strong>Service Type:</strong> <span>${serviceType}</span></div>`;
        html += `</div>`;


        // ************ ROW 6: Property Layout (Boxed Content) ************
        html += `<p style="font-size: 0.95em; margin-bottom: 5px;"><strong>Property Layout:</strong></p>`;
        // Ginamit ang contentBoxStyle (blue border)
        html += `<div style="${contentBoxStyle}; margin-bottom: 10px;">${layout}</div>`;
        
        // ROW 7: Attachments (Media)
        html += `<p style="font-size: 0.95em; margin-bottom: 5px;"><strong>Attachments:</strong></p>`;
        html += mediaLinks;
        
        // --- Separator 2 ---
        html += dividerHtml;

        // ************ ROW 8: Materials Required (Boxed Content with Conditional Style) ************
        html += `<p style="font-size: 0.95em; margin-bottom: 5px;"><strong>Does the client require cleaning materials? (Yes or No):</strong></p>`;

        let materialsReqStyle = materialsRequired.toLowerCase() === 'yes' ? contentBoxStyle : naBoxStyle;

        // Ginamit ang materialsReqStyle para sa conditional styling (blue border kung Yes, grey kung No/N/A)
        html += `<div style="${materialsReqStyle}; margin-bottom: 10px;">${materialsRequired}</div>`;

        // ROW 9: If yes, what materials are needed? (Boxed content)
      // ROW 9: If yes, what materials are needed? (Boxed content)
html += `<p style="font-size: 0.95em; margin-bottom: 5px;">
            <strong>If yes, what materials are needed?:</strong>
         </p>`;

let materialsContent = '';

// Show materials_needed only if client said "Yes"
if (materialsRequired.toLowerCase().includes('yes')) {
    // Display the actual DB value or fallback to "N/A" if empty
    materialsContent = `<div style="${contentBoxStyle}">${materialDescription && materialDescription.trim() !== '' ? materialDescription : 'N/A'}</div>`;
} else {
    // If "No", grey box
    materialsContent = `<div style="${naBoxStyle}">N/A (Client provides materials)</div>`;
}

html += materialsContent;

        
        // ROW 10: Additional Request (Boxed content)
        html += `<p style="font-size: 0.95em; margin-top: 15px; margin-bottom: 5px;"><strong>Additional Request:</strong></p>`;
        // Use the standard content box style for the request itself
        html += `<div style="${contentBoxStyle}">${request}</div>`;

        // --- Separator 3 ---
        html += dividerHtml;

        // ************ ROW 11: Estimated/Final Price Logic (MODAL VERSION) ************
        // let priceLabel = 'Estimated Price:';
        // Gagamitin na ang brand color para sa lahat ng presyo (maliban kung Final)
        let priceDisplay = `<span style="color: #fefefeff; font-weight: bold;">${priceValue}</span>`; 
        
        // Check status (case-insensitive)
        if (status.toLowerCase() === 'completed') {
            priceLabel = '';
            // Final Price uses the same AED style
        } 
        
        // Applied color #333 (Dark Grey) here
        html += `<div style="text-align: right; margin-top: 10px; color: #333;">`; 
            // Gamitin ang dynamic na priceLabel at priceDisplay
            html += `<strong style="font-size: 1.2em;">${priceLabel} ${priceDisplay}</strong>`;
        html += `</div>`;


        // --- 3. Finalize Modal Display (UPDATED FOR SCROLL FIX) ---
        modalContent.innerHTML = html;
        
        // Gawin itong instant at dagdagan ng class ang body
        detailsModal.classList.add('is-visible-now');
        document.body.classList.add('modal-open-body-no-scroll');
        
    } catch (e) {
        console.error("RUNTIME ERROR INSIDE showDetailsModal:", e);
    }
}


// ------------------------------------------------------------------------------
// --- SEPARATION OF LOGIC ---
// ------------------------------------------------------------------------------
















// ==============================================================================
// === HELPER FUNCTION: GET STATUS ICON FOR RECURRING SERVICES ===
// ==============================================================================

/**
 * Returns the appropriate Boxicons class for a given appointment/plan status.
 * (This function is for RECURRING PLANS and their specific statuses.)
 * @param {string} status - The status string (e.g., 'ACTIVE', 'PAUSED').
 * @returns {string} The Boxicons class string (e.g., 'bx-play-circle').
 */
function getRecurringStatusIcon(status) { 
    if (typeof status !== 'string') return 'bx-info-circle'; // Default icon

    const normalizedStatus = status.toUpperCase().trim();

    switch (normalizedStatus) {
        case 'ACTIVE':
            return 'bx-play-circle'; // Recurring Icon
        case 'COMPLETED':
            return 'bx-check-double'; // Recurring Icon
        case 'PAUSED':
            return 'bx-pause-circle'; // Recurring Icon
        case 'PENDING':
            return 'bx-time-five'; // Recurring Icon
        case 'TERMINATED':
            return 'bx-x-circle'; // Recurring Icon
        // I-maintain natin ang iba pang status na ginagamit mo sa One-Time
        case 'CANCELLED':
            return 'bx-x-circle'; 
        case 'SCHEDULED':
            return 'bx-calendar-check';
        default:
            return 'bx-info-circle'; // Default fallback icon
    }
}


// ==============================================================================
// === RECURRING DETAILS MODAL FUNCTION (UPDATED: COMPRESSED ROW SPACING) ===
// ==============================================================================

/**
 * Extracts details from the recurring appointment list item and displays them in the 'detailsModal'.
 * @param {HTMLElement} itemElement - The parent .appointment-list-item element.
 */
function showRecurringDetailsModal(itemElement) {
    const detailsModal = document.getElementById('detailsModal');
    const modalContent = document.getElementById('modal-details-content');

    // Tiyakin na ang element na pinasa ay ang parent list item
    if (itemElement && itemElement.closest('.appointment-list-item')) {
        itemElement = itemElement.closest('.appointment-list-item');
    } else {
        return; 
    }

    if (!detailsModal || !itemElement || !modalContent) return;

    // Helper for consistent divider
    const dividerHtml = `<hr style="border: 0; border-top: 1px solid #ccc; margin: 15px 0;">`;

    // Style for content box (Blue border - for YES, Property Layout, Request)
    const contentBoxStyle = `background-color: #f7f7f7; padding: 10px; border-radius: 4px; border-left: 3px solid #007bff; color: #333; margin-top: 5px; font-size: 0.95em;`;
    // Style for N/A content box (Grey border - for NO, Not provided)
    const naBoxStyle = `background-color: #f7f7f7; padding: 10px; border-radius: 4px; border-left: 3px solid #ccc; font-style: italic; color: #aaa; margin-top: 5px; font-size: 0.95em;`;


    try {
        const detailsContainer = itemElement.querySelector('.appointment-details');
        let html = '';

        // --- 1. Extract Details from Summary & Data Attributes ---
        const refNo = detailsContainer.querySelector('.ref-no-value')?.textContent || 'N/A';
        
        // RECURRING SPECIFIC: Overall Plan Status
        const overallPlanStatusRaw = itemElement.getAttribute('data-plan-status') || 'UNKNOWN';
        const overallPlanStatus = overallPlanStatusRaw.toUpperCase();
        
        // **CORRECTED LOGIC:** Gumawa ng class name na TUGMA sa CSS mo (e.g., overall-active)
        const planStatusClass = `overall-${overallPlanStatusRaw.toLowerCase().replace(/ /g, '-')}`; 
        const statusIconClass = getRecurringStatusIcon(overallPlanStatus); // Kukunin ang icon sa tamang function
        
        // GAGAWIN ITONG VARIABLE na gagamitin sa ROW 1
        const planStatusTagHtml = `
            <span class="overall-plan-tag ${planStatusClass}"> 
                <i class='bx ${statusIconClass}'></i> 
                ${overallPlanStatus}
            </span>`;
        
        const address = getText(detailsContainer, '.bx-map-alt', 'Address');
        const serviceType = getText(detailsContainer, '.service-type-detail', 'Service Type');
        
        // FIX: Kukunin ang raw AED value (hal: "AED 500") mula sa span na may aed-color
        const priceElement = detailsContainer.querySelector('.price-detail');
        const priceValue = priceElement ? priceElement.querySelector('.aed-color')?.textContent || '0' : '0';
        
        const clientType = getText(detailsContainer, null, 'Client Type'); // Kinuha ang Client Type
        
        // RECURRING SPECIFIC: Frequency, Start/End Date, Time, Duration, Sessions Count
        const frequency = itemElement.getAttribute('data-frequency') || 'N/A';
        const startDate = itemElement.getAttribute('data-start-date') || 'N/A';
        const endDate = itemElement.getAttribute('data-end-date') || 'N/A';
        
        // NEWLY REQUIRED FIELDS (Assuming data attributes exist on the list item)
        const time = itemElement.getAttribute('data-time') || 'N/A'; 
        const duration = itemElement.getAttribute('data-duration') || 'N/A';
        const noOfSessions = itemElement.getAttribute('data-sessions-count') || 'N/A';
        
        // Other one-time details
        const layout = itemElement.getAttribute('data-property-layout') || 'N/A';
        const materialsRequired = itemElement.getAttribute('data-materials-required') || 'N/A';
        const materialDescription = itemElement.getAttribute('data-materials-description') || 'N/A';
        const request = itemElement.getAttribute('data-additional-request') || 'None';
        const img1 = itemElement.getAttribute('data-image-1') || '';
        const img2 = itemElement.getAttribute('data-image-2') || '';
        const img3 = itemElement.getAttribute('data-image-3') || '';


        // Generate Media Links HTML (Using UL/LI)
        let mediaLinks = `
            <ul style="list-style-type: none; margin: 5px 0 0 0; padding: 0; font-size: 0.95em;">
                ${getMediaLink(img1, 'Image/Video 1')}
                ${getMediaLink(img2, 'Image/Video 2')}
                ${getMediaLink(img3, 'Image/Video 3')}
            </ul>
        `;
        
        // --- 2. Build HTML (STRICTLY FOLLOWING NEW ROW ORDER) ---
        
        // ************ ROW 1: Reference No. at Overall Plan Status Tag (Flexbox Layout) ************
        html += `<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">`; 
        
            // Reference No. sa KALIWA
            html += `<p style="font-size: 1.1em; padding-top: 0; margin-bottom: 0;"><strong>Reference No:</strong> <span style="color: #B32133; font-weight: bold;">${refNo}</span></p>`;
            
            // Overall Plan Status Tag sa KANAN
            html += planStatusTagHtml; 
        
        html += `</div>`;


        // ************ ROW 2: Start Date at End Date (Aligned) - COMPRESSED MARGIN ************
        // OLD: margin-bottom: 10px;  ->  NEW: margin-bottom: 5px;
        html += `<div style="display: flex; margin-bottom: 5px; flex-wrap: wrap; font-size: 0.95em; gap: 20px;">`; 
            html += `<div style="flex: 1;"><strong>Start Date:</strong> <span>${startDate}</span></div>`; 
            html += `<div style="flex: 1;"><strong>End Date:</strong> <span>${endDate}</span></div>`;     
            html += `<div style="flex: 1;"></div>`; // Empty column to maintain alignment
        html += `</div>`;
        
        // ************ ROW 3: Time at Duration (Aligned) - COMPRESSED MARGIN ************
        // OLD: margin-bottom: 10px;  ->  NEW: margin-bottom: 5px;
        html += `<div style="display: flex; margin-bottom: 5px; flex-wrap: wrap; font-size: 0.95em; gap: 20px;">`; 
            html += `<div style="flex: 1;"><strong>Time:</strong> <span>${time}</span></div>`; 
            html += `<div style="flex: 1;"><strong>Duration:</strong> <span>${duration}</span></div>`;
            html += `<div style="flex: 1;"></div>`; // Empty column to maintain alignment
        html += `</div>`;
        
        // ************ ROW 4: Frequency at No. of Sessions (Aligned) - COMPRESSED MARGIN ************
        // OLD: margin-bottom: 10px;  ->  NEW: margin-bottom: 5px;
        html += `<div style="display: flex; margin-bottom: 5px; flex-wrap: wrap; font-size: 0.95em; gap: 20px;">`; 
            html += `<div style="flex: 1;"><strong>Frequency:</strong> <span>${frequency}</span></div>`; 
            html += `<div style="flex: 1;"><strong>No. of Sessions:</strong> <span>${noOfSessions}</span></div>`;
            html += `<div style="flex: 1;"></div>`; // Empty column to maintain alignment
        html += `</div>`;

        // ************ ROW 5: Address (Standalone) ************
        // Pinanatili ang 10px dito para may sapat na space bago ang divider
        html += `<p style="font-size: 0.95em; margin-bottom: 10px;"><strong>Address:</strong> <span>${address}</span></p>`;


        // --- Separator 1 ---
        html += dividerHtml; 
        
        
        // ************ AFTER DIVIDER: Client Type at Service Type (Aligned) ************
        html += `<div style="display: flex; margin-bottom: 10px; flex-wrap: wrap; font-size: 0.95em; gap: 20px;">`;
            html += `<div style="flex: 1;"><strong>Client Type:</strong> <span>${clientType}</span></div>`; 
            html += `<div style="flex: 1;"><strong>Service Type:</strong> <span>${serviceType}</span></div>`;
            html += `<div style="flex: 1;"></div>`; // Empty column to maintain alignment
        html += `</div>`;


        // ************ ROW 6: Property Layout (Boxed Content) ************
        html += `<p style="font-size: 0.95em; margin-bottom: 5px;"><strong>Property Layout:</strong></p>`;
        html += `<div style="${contentBoxStyle}; margin-bottom: 10px;">${layout}</div>`;
        
        // ************ ROW 7: Attachments (Media) ************
        html += `<p style="font-size: 0.95em; margin-bottom: 5px;"><strong>Attachments:</strong></p>`;
        html += mediaLinks;
        
        // --- Separator 2 ---
        html += dividerHtml;

       // ************ ROW 8: Materials Required (Boxed Content with Conditional Style) ************
html += `<p style="font-size: 0.95em; margin-bottom: 5px;"><strong>Does the client require cleaning materials? (Yes or No):</strong></p>`;

let materialsReqStyle = materialsRequired.toLowerCase().includes('yes') ? contentBoxStyle : naBoxStyle;

html += `<div style="${materialsReqStyle}; margin-bottom: 10px;">${materialsRequired}</div>`;

// ROW 9: If yes, what materials are needed? (Boxed content)
html += `<p style="font-size: 0.95em; margin-bottom: 5px;"><strong>If yes, what materials are needed?:</strong></p>`;
let materialsContent = ''; 

if (materialsRequired.toLowerCase().includes('yes') && materialDescription && materialDescription.trim() !== '') {
     materialsContent = `<div style="${contentBoxStyle}">${materialDescription}</div>`;
} else if (materialsRequired.toLowerCase().includes('no')) {
     materialsContent = `<div style="${naBoxStyle}">N/A (Client provides materials)</div>`; 
} else {
     materialsContent = `<div style="${naBoxStyle}">Not Provided</div>`;
}
html += materialsContent;
        
        // ************ ROW 10: Additional Request (Boxed content) ************
        html += `<p style="font-size: 0.95em; margin-top: 15px; margin-bottom: 5px;"><strong>Additional Request:</strong></p>`;
        html += `<div style="${contentBoxStyle}">${request}</div>`;

        // --- Separator 3 ---
        html += dividerHtml;

        // ************ ROW 11: Total Plan Price Logic (MODAL VERSION) ************
        // let priceLabel = 'Total Plan Price:';
        // let priceDisplay = `<span style="color: #B32133; font-weight: bold;">${priceValue}</span>`; 
        
        // html += `<div style="text-align: right; margin-top: 10px; color: #333;">`; 
        //     html += `<strong style="font-size: 1.2em;">${priceLabel} ${priceDisplay}</strong>`;
        // html += `</div>`;


        // --- 3. Finalize Modal Display ---
        modalContent.innerHTML = html;
        
        detailsModal.classList.add('is-visible-now');
        document.body.classList.add('modal-open-body-no-scroll');
        
    } catch (e) {
        console.error("RUNTIME ERROR INSIDE showRecurringDetailsModal:", e);
    }
}

// ==============================================================================
// === HISTORY TAB NAVIGATION LOGIC ===
// ==============================================================================

/**
 * Function to switch between horizontal service tabs (e.g., 'Checkout Cleaning', 'Recurring Cleaning').
 * @param {Event} evt - The click event.
 * @param {string} tabContentId - The ID of the tab content to show (e.g., 'checkout-cleaning').
 */
function openTab(evt, tabContentId) {
    let i, tabcontent, tablinks;

    // 1. Get all elements with class="tab-content" and hide them
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // 2. Get all elements with class="tab-button" and remove the "active" class
    tablinks = document.getElementsByClassName("tab-button");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }

    // 3. Show the current tab, and add an "active" class to the button that opened the tab
    const currentTabContent = document.getElementById(tabContentId);
    currentTabContent.style.display = "block";
    evt.currentTarget.classList.add("active");

    // 4. Trigger filter/initial check for the newly opened tab
    const currentList = currentTabContent.querySelector('.appointment-list-container');
    if (currentList) {
         const dropdown = currentTabContent.querySelector('.date-filter-dropdown');
         const searchInput = currentTabContent.querySelector('input[type=text]');
         // Re-trigger the filter for the current list (using its current dropdown/search values)
         filterHistory(searchInput, dropdown.value, currentList.id);
    }
}

// ==============================================================================
// === HISTORY FILTERING LOGIC (Date Range & Search) ===
// ==============================================================================

// Helper function to calculate the Start Date bound for the selected quick filter period
function getStartDateBound(filterType) {
    const now = new Date();
    // Set time to start of day for accurate comparison
    now.setHours(0, 0, 0, 0); 
    const bound = new Date(now);

    if (filterType === 'last7days') {
        bound.setDate(now.getDate() - 7); 
    } else if (filterType === 'last30days') {
        bound.setDate(now.getDate() - 30); 
    } else if (filterType === 'this_year') {
        bound.setMonth(0, 1); // January 1st of current year
    } else if (filterType === 'all') {
        return null; // No date filter
    } 

    return bound;
}

// Handle the Custom Range click and quick preset changes from the dropdown
function handleFilterChange(selectElement, listId) {
    if (selectElement.value === 'customrange') {
        // Set the target list ID sa modal button at i-open ang modal
        const applyBtn = document.querySelector('#datePickerModal button[data-list-id]');
        applyBtn.setAttribute('data-list-id', listId);
        document.getElementById('datePickerModal').classList.add('is-visible-now');
        document.body.classList.add('modal-open-body-no-scroll');
        
        // Ibalik sa 'all' muna para hindi magulo ang filter logic habang nasa modal.
        selectElement.value = 'all'; 
    } else {
        // I-apply ang filter for the quick presets
        // Since we removed .filter-controls, we get the search input differently:
        const searchInput = selectElement.closest('.filter-controls-tab').querySelector('input[type=text]');
        // Pass the filter type (e.g., 'last7days') directly
        filterHistory(searchInput, selectElement.value, listId); 
    }
}

// Main Filter Logic (MODIFIED to use dynamic dateFilterType and separate search)
function filterHistory(inputElement, dateFilterType, listId) {
    
    const list = document.getElementById(listId);
    if (!list) return;
    
    // Kunin ang dropdown mula sa closest .tab-content
    const tabContent = list.closest('.tab-content');
    const dropdown = tabContent.querySelector('.date-filter-dropdown');

    // 1. Determine the active date filter and text filter
    let textFilter = '';
    let currentFilterType = dateFilterType;

    if (inputElement.tagName === 'INPUT') {
        // Kung galing sa search input ang tawag
        currentFilterType = dropdown.value; 
        textFilter = inputElement.value.trim().toLowerCase();
    } else {
        // Kung galing sa dropdown ang tawag, kunin ang value ng search input
        const searchInput = tabContent.querySelector('input[type=text]');
        textFilter = searchInput ? searchInput.value.trim().toLowerCase() : '';
    }
    
    const items = Array.from(list.getElementsByClassName('appointment-list-item'));
    const noAppointmentsMessage = list.querySelector('.no-appointments-message'); 
    
    let visibleCount = 0;
    
    let startDateBound = null;
    let endDateBound = null; 
    let filterName = dropdown.options[dropdown.selectedIndex] ? dropdown.options[dropdown.selectedIndex].text : 'All Time';

    // 2. Determine Date Bounds (Quick Preset or Custom Range)
    if (currentFilterType && currentFilterType.startsWith('custom:')) {
        const parts = currentFilterType.substring(7).split(':');
        startDateBound = new Date(parts[0]);
        endDateBound = new Date(parts[1]);
        endDateBound.setHours(23, 59, 59, 999); 
        filterName = `${parts[0]} to ${parts[1]}`;
    } else {
        startDateBound = getStartDateBound(currentFilterType);
        
        if (currentFilterType === 'all') {
            filterName = 'All Time';
        } else if (currentFilterType === 'last7days') {
            filterName = 'Last 7 Days';
        } else if (currentFilterType === 'last30days') {
            filterName = 'Last 30 Days';
        } else if (currentFilterType === 'this_year') {
            filterName = 'This Year';
        }
    }
    
    if (startDateBound) startDateBound.setHours(0, 0, 0, 0);

    // 3. Iterate and Filter Items
    items.forEach(item => {
        const searchTerms = item.getAttribute('data-search-terms') ? item.getAttribute('data-search-terms').toLowerCase() : '';
        const itemDateString = item.getAttribute('data-date'); 
        
        // 1. Text Search Check
        const passesTextFilter = searchTerms.includes(textFilter);
        
        // 2. Date Range Check
        let passesDateFilter = true;
        if (itemDateString) {
            const itemDate = new Date(itemDateString + 'T00:00:00'); 
            
            if (startDateBound) {
                passesDateFilter = itemDate >= startDateBound;
            }
            
            if (endDateBound) {
                passesDateFilter = passesDateFilter && (itemDate <= endDateBound);
            }
        }

        // Show item only if it passes BOTH filters
        if (passesTextFilter && passesDateFilter) {
            item.style.display = "block"; // Show
            visibleCount++;
        } else {
            item.style.display = "none"; // Hide
        }
    });
    
    // 4. Handle "No results found" message visibility
    if (noAppointmentsMessage) {
        if (visibleCount === 0) {
            if (items.length === 0 && textFilter === '' && (currentFilterType === 'all' || dropdown.selectedIndex === -1 || !currentFilterType)) {
                // Default "No history" message (Initial Empty State)
                const serviceName = noAppointmentsMessage.getAttribute('data-service-name') || 'Cleaning Service';
                noAppointmentsMessage.innerHTML = `
                    No history available for this service yet.
                    <div class="book-btn-container">
                         <a href="BA_one-time.php" class="book-btn">
                             <i class='bx bx-calendar-plus'></i> Book Now
                         </a>
                    </div>
                `;
            } else if (items.length > 0) {
                 // "No results" found due to filter/search
                 let message = `No appointments found.`;
                 const dateRangeText = (currentFilterType && currentFilterType !== 'all') ? ` in the selected time range (${filterName})` : ''
                 
                 if (textFilter !== '') {
                    message = `No results found matching "${textFilter}"${dateRangeText}.`;
                 } else if (currentFilterType && currentFilterType !== 'all') {
                    message = `No appointments found for ${filterName}.`;
                 }
                 noAppointmentsMessage.textContent = message;
            } 
            
            noAppointmentsMessage.style.display = "block";
        } else {
            noAppointmentsMessage.style.display = "none";
        }
    }
}

// Function to apply custom range (FINAL FIXED VERSION with Custom Error Handling)
function applyCustomRange(listId) {
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    // Ang element ID na idinagdag mo sa HTML
    const dateRangeError = document.getElementById('dateRangeError'); 
    
    // 1. Reset Error Display
    if (dateRangeError) dateRangeError.style.display = 'none';
    
    const start = startDateInput.value;
    const end = endDateInput.value;
    
    let isValid = true;

    if (!start || !end) {
        // Error Case 1: Missing dates
        if (dateRangeError) {
            dateRangeError.textContent = 'Please select both Start and End Dates.';
            dateRangeError.style.display = 'inline-block'; // Ito ang nagpapakita ng error
        } 
        isValid = false;
    } else if (new Date(start) > new Date(end)) {
        // Error Case 2: End Date < Start Date
        if (dateRangeError) {
            dateRangeError.textContent = 'End Date cannot be before Start Date.';
            dateRangeError.style.display = 'inline-block'; // Ito ang nagpapakita ng error
        } 
        isValid = false;
    }
    
    if (!isValid) {
        return; // Ititigil ang function at hindi magpa-proceed sa filter
    }

    // --- SUCCESS PATH (Only runs if isValid is true) ---
    
    const list = document.getElementById(listId);
    if (!list) return; 

    const tabContent = list.closest('.tab-content');
    if (!tabContent) return; 
    
    const dropdown = tabContent.querySelector('.date-filter-dropdown');
    const searchInput = tabContent.querySelector('input[type=text]');
    
    // 1. Update the Dropdown
    const customValue = `custom:${start}:${end}`;
    
    let customOption = dropdown.querySelector(`option[value^="custom:"]`);
    if (customOption) {
         customOption.value = customValue;
         customOption.textContent = `${start} to ${end}`;
    } else {
        // Create new option if one doesn't exist
        customOption = document.createElement('option');
        customOption.value = customValue;
        customOption.textContent = `${start} to ${end}`;
        
        const customRangeOption = dropdown.querySelector('option[value="customrange"]');
        if (customRangeOption) {
            dropdown.insertBefore(customOption, customRangeOption);
        } else {
            dropdown.appendChild(customOption);
        }
    }
    
    // 2. Select the new option and close the modal
    dropdown.value = customValue;
    closeModal('datePickerModal'); 
    
    // 3. Trigger the filter
    filterHistory(searchInput, null, list.id); 
}

// ==============================================================================
// === REPORT ISSUE MODAL LOGIC (Validation & Submission) ===
// ==============================================================================

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
 * Handles the final submission process for the Report Issue form, performing validation,
 * closing the issue modal, and opening the success modal upon success.
 * @returns {boolean} Always returns false to prevent standard form submission.
 */
function submitReport() {
    // Elements
    const issueType = document.getElementById('issueType');
    const issueDetails = document.getElementById('issueDetails');
    
    // New Error Message Elements
    const issueTypeError = document.getElementById('issueTypeError');
    const issueDetailsError = document.getElementById('issueDetailsError');

    let isValid = true;
    
    // Reset ONLY the error message visibility
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
    
    // 3. Handle Invalid Submission
    if (!isValid) {
        // Optional: Focus on the first invalid field
        if (issueType.value === "") {
            issueType.focus();
        } else if (issueDetails.value.trim() === "") {
            issueDetails.focus();
        }
        
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
        successModal.style.display = 'block'; 
    }

    // We return false to prevent the form from actually submitting and refreshing the page
    return false;
}

/**
 * Shows the Report Issue modal and populates data from the list item or details modal.
 * Note: This function is duplicated/overridden in the original code. I will keep the final version.
 * @param {HTMLElement} element - The 'Report Issue' link element (from dropdown) or button (from details modal).
 */


// ==============================================================================
// === LOGOUT MODAL HANDLING ===
// ==============================================================================

/**
 * Shows the Logout confirmation modal.
 */
function showLogoutModal() {
    let modal = document.getElementById('logoutModal');
    if (modal) {
        // Use flex to easily center the content
        modal.style.display = 'flex'; 
    }
}

/**
 * Hides the Logout confirmation modal.
 */
function hideLogoutModal() {
    let modal = document.getElementById('logoutModal');
    if (modal) {
        modal.style.display = 'none';
    }
}


// ==============================================================================
// === SCROLL TO TOP FUNCTIONALITY ===
// ==============================================================================

let mybutton = document.getElementById("backToTopBtn");

/**
 * Checks scroll position and shows/hides the Back to Top button.
 */
function scrollFunction() {
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
        mybutton.style.display = "flex";
        setTimeout(() => { mybutton.style.opacity = "1"; }, 10);
    } else {
        mybutton.style.opacity = "0";
        setTimeout(() => { mybutton.style.display = "none"; }, 300);
    }
}

window.onscroll = function() {
    scrollFunction();
};


// ==============================================================================
// === DOM CONTENT LOADED INITIALIZATION ===
// ==============================================================================

document.addEventListener('DOMContentLoaded', () => {
    // --- Sidebar Active State Initialization ---
    const activeLink = document.querySelector('.sidebar a.menu__link.active');

    if (activeLink) {
        // Find the closest parent that is a dropdown group
        const parentDropdown = activeLink.closest('.has-dropdown');
        if (parentDropdown) {
            parentDropdown.classList.add('open'); 
            // Activate the history-parent link as well
            const parentLink = parentDropdown.querySelector('.menu__link[data-content="history-parent"]');
            if (parentLink) {
                parentLink.classList.add('active-parent'); 
            }
        }
    }
    
    // --- Initial Setup for Tabs and Filters ---
    
    // Kunin ang active tab content (na may style="display: block;" - Checkout Cleaning)
    const initialActiveTabContent = document.querySelector('.tab-content[style="display: block;"]');

    if (initialActiveTabContent) {
        // Kunin ang appointment list sa loob ng active tab
        const initialList = initialActiveTabContent.querySelector('.appointment-list-container');
        
        if (initialList) {
             // 1. Initial Empty List Check (Show "No history" message)
            const items = Array.from(initialList.querySelectorAll('.appointment-list-item'));
            const noMessage = initialList.querySelector('.no-appointments-message');
            
            if (items.length === 0 && noMessage) {
                const serviceName = noMessage.getAttribute('data-service-name') || 'Cleaning Service';
                noMessage.innerHTML = `
                    No history available for this service yet.
                    <div class="book-btn-container">
                         <a href="BA_one-time.php" class="book-btn">
                             <i class='bx bx-calendar-plus'></i> Book Now
                         </a>
                    </div>
                `;
                noMessage.style.display = 'block'; 
            } else if (noMessage) {
                noMessage.style.display = 'none';
            }
            
            // 2. Initial Filter: Apply 'All Time' (default selected option)
            const dropdown = initialActiveTabContent.querySelector('.date-filter-dropdown');
            const searchInput = initialActiveTabContent.querySelector('input[type=text]');

            if (dropdown && searchInput) {
                // Trigger filter for the initially active tab content
                filterHistory(searchInput, dropdown.value, initialList.id); 
            }
        }
    }
    
    // --- Back To Top Button Event Listener ---
    mybutton.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // --- Logout Modal Button Bindings ---
    const logoutModalElement = document.getElementById('logoutModal');
    // Optional: Close modal when clicking outside of the content
    if (logoutModalElement) {
        logoutModalElement.addEventListener('click', (event) => {
            if (event.target === logoutModalElement) {
                hideLogoutModal();
            }
        });
    }

    // --- jQuery Selector Utility (Required by showReportModal for recurring logic) ---
    // Note: This relies on jQuery being loaded on the page.
    if (window.jQuery) {
        $.expr[':'].textEquals = $.expr.createPseudo(function(arg) {
            return function( elem ) {
                return $(elem).text().trim() === arg;
            };
        });
    }

    console.log("DOM fully loaded. All functions defined.");
});

// ==============================================================================
// === DUPLICATED/OVERRIDDEN LOGIC (Kept for completeness but noted) ===
// ==============================================================================

// NOTE: The following functions are duplicates or versions intended for the Recurring History page.
// The initial functions (showDetailsModal, showReportModal, closeModal, getStatusIcon, etc.)
// are usually the ones intended for the One-Time History page.
// In a clean script, these duplicates would be removed, but I am keeping them
// commented out/separated here to indicate they are redundant in the final file structure.

/*
// --- RECURRING HISTORY VERSION of showDetailsModal ---
// This version is specialized for Recurring Plans (with Start/End Date, Plan Status)
function showDetailsModal(element) {
    // ... [Original Recurring Logic Here] ...
}

// --- RECURRING HISTORY VERSION of showReportModal ---
// This version uses a specific lookup to find the original list item by Ref No
function showReportModal(element) {
    // ... [Original Recurring Logic Here] ...
}
*/



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
        successModal.style.display = 'block'; 
    }

    // We return false to prevent the form from actually submitting and refreshing the page
    return false;
}

/**
 * Shows the Report Issue modal and populates data from the list item.
 * @param {HTMLElement} element - The 'Report Issue' link element.
 */
