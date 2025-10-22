    // NOTE: Ang function na ito ay inilagay dito para maging self-contained ang file, 
    // dahil ang instruction ay huwag galawin ang ibang files (e.g., FR_function.js).
    // Ito ang magpapakita ng View Reported Issue Modal.

    function viewReportedIssue(listItem) {
        const refNo = listItem.getAttribute('data-ref-no');
        
        // Simulating fetching reported issue data based on refNo
        let issueData;

        // --- MODIFIED DUMMY DATA FOR DEMONSTRATION (Updated to check for data- prefix) ---
        const incidentDate = listItem.getAttribute('data-date') || listItem.getAttribute('data-incident-date');
        const scheduledTime = listItem.getAttribute('data-time') || listItem.getAttribute('data-scheduled-time');

        // Check if the item is from the Issues List (it will have a data-issue-status)
        if (listItem.hasAttribute('data-issue-status')) {
            issueData = {
                ref: listItem.getAttribute('data-ref-no'),
                reportDate: listItem.getAttribute('data-report-date'),
                reportTime: '10:30 AM', // Assume default time for simplicity
                incidentDate: incidentDate, 
                scheduledTime: scheduledTime, 
                issueType: listItem.getAttribute('data-issue-type'),
                description: listItem.getAttribute('data-issue-description'),
                attachments: [
                    listItem.getAttribute('data-attachment-1') || null,
                    listItem.getAttribute('data-attachment-2') || null,
                    listItem.getAttribute('data-attachment-3') || null
                ],
                status: listItem.getAttribute('data-issue-status')
            };
        }
        // Fallback for items in the Appointment List (like ALZ-CC-2409-0015 and ALZ-IH-2409-0012)
        else if (refNo === 'ALZ-CC-2409-0015') {
            issueData = {
                ref: refNo,
                reportDate: 'September 29, 2024',
                reportTime: '10:30 AM',
                incidentDate: incidentDate, 
                scheduledTime: scheduledTime, 
                issueType: 'Unsatisfied with Quality of Cleaning',
                description: 'The team missed cleaning the balcony glass door and the refrigerator interior was still dirty despite being listed in the scope. Attached are photos showing the missed areas. We request a re-clean or a partial refund.',
                attachments: [
                    'https://alazima.com/files/issue/ALZ-CC-2409-0015_missed_area1.jpg',
                    'https://alazima.com/files/issue/ALZ-CC-2409-0015_missed_area2.jpg',
                    'https://alazima.com/files/issue/ALZ-CC-2409-0015_missed_video.mp4' 
                ],
                // Magiging 'In Progress' ito
                status: 'Under Investigation' 
            };
        } else if (refNo === 'ALZ-IH-2409-0012') {
             issueData = {
                ref: refNo,
                reportDate: 'September 20, 2024',
                reportTime: '04:15 PM',
                incidentDate: incidentDate, 
                scheduledTime: scheduledTime, 
                issueType: 'Property Damage',
                description: 'One of the cleaning staff accidentally scratched the wooden floor in the living room while moving the ladder. The scratch is about 10cm long. We are awaiting your proposal for repair/compensation.',
                attachments: [
                    'https://alazima.com/files/issue/ALZ-IH-2409-0012_scratch_photo.jpg',
                    null,
                    null
                ],
                // Magiging 'Resolved' ito
                status: 'Closed - Compensation Provided' 
            };
        } else {
            // Fallback if somehow triggered on an item without data-has-issue="true"
            console.error("No reported issue data found for " + refNo);
            return; 
        }
        // --- END MODIFIED DUMMY DATA ---

        // Populate Modal Fields (UPDATED SECTION)
        document.getElementById('view-issue-ref-number').textContent = issueData.ref;
        document.getElementById('view-issue-report-date').textContent = issueData.reportDate;
        document.getElementById('view-issue-report-time').textContent = issueData.reportTime;
        document.getElementById('view-issue-incident-date').textContent = issueData.incidentDate;
        
        // Pag-convert ng 24-hour time sa 12-hour AM/PM format
        let formattedTime = issueData.scheduledTime;
        if (issueData.scheduledTime && issueData.scheduledTime.includes(':')) {
             const [hour, minute] = issueData.scheduledTime.split(':');
             formattedTime = new Date(0, 0, 0, hour, minute).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }
        document.getElementById('view-issue-scheduled-time').textContent = formattedTime; 
        
        document.getElementById('view-issue-type').textContent = issueData.issueType;
        document.getElementById('view-issue-description').textContent = issueData.description;
        
        // --- STATUS LOGIC START ---
        const statusTag = document.getElementById('view-issue-status');
        statusTag.className = 'status-tag'; // Reset class
        
        const statusLower = issueData.status.toLowerCase();

        // Che-check kung Resolved ba
        if (statusLower.includes('resolved') || statusLower.includes('closed') || statusLower.includes('completed')) {
            statusTag.textContent = 'Resolved'; 
            statusTag.classList.add('status-completed'); // Green
        } else {
            // Lahat ng iba ay In Progress (Pending, Review, Investigation, etc.)
            statusTag.textContent = 'In Progress'; 
            statusTag.classList.add('status-pending'); // Yellowish/Amber
        }
        // --- STATUS LOGIC END ---
        
        // Populate Attachments
        const attachmentsContainer = document.getElementById('view-issue-attachments');
        attachmentsContainer.innerHTML = ''; // Clear previous attachments

        // Iterating 3 times for all 3 slots
        const totalAttachments = 3; 
        for(let index = 0; index < totalAttachments; index++) {
            // Gumamit ng url mula sa issueData.attachments, o null kung walang data
            const url = issueData.attachments && issueData.attachments.length > index ? issueData.attachments[index] : null;
            
            const attachmentWrapper = document.createElement('div');
            
            const attachmentLink = document.createElement('a');
            attachmentLink.className = 'attachment-button-link'; // New class for styling
            
            const fileTypeIcon = `<i class='bx bx-paperclip' style='margin-right: 5px;'></i>`;

            if (url && url.length > 5) {
                attachmentLink.href = url;
                attachmentLink.target = '_blank';
                attachmentLink.className += ' active-attachment-link';
                attachmentLink.innerHTML = `${fileTypeIcon}Attachment ${index + 1}`;
                attachmentLink.title = "Click to View/Download";
            } else {
                attachmentLink.className += ' disabled-attachment-link';
                attachmentLink.innerHTML = `<i class='bx bx-minus-circle' style='margin-right: 5px; color: #999;'></i><span style='font-weight: 400;'>No File ${index + 1}</span>`;
                attachmentLink.style.pointerEvents = 'none';
            }
            
            attachmentWrapper.appendChild(attachmentLink);
            attachmentsContainer.appendChild(attachmentWrapper);
        }

        // Show Modal
        document.getElementById('viewReportedIssueModal').style.display = 'block';
    }
    
    // START: NEW FUNCTION viewAppointmentFromIssue (Walang ibang pinalitan sa code maliban sa itaas at ito)
    function viewAppointmentFromIssue(buttonElement) {
        const issueItem = buttonElement.closest('.issue-list-item');
        const refNo = issueItem.getAttribute('data-ref-no');
        
        let serviceTypeShort;
        if (refNo.startsWith('ALZ-CC')) {
            serviceTypeShort = 'checkout-cleaning';
        } else if (refNo.startsWith('ALZ-IH')) {
            serviceTypeShort = 'in-house-cleaning';
        } else if (refNo.startsWith('ALZ-RC')) {
            serviceTypeShort = 'refresh-cleaning';
        } else if (refNo.startsWith('ALZ-DC')) {
            serviceTypeShort = 'deep-cleaning';
        } else {
            console.error("Unknown reference number prefix: " + refNo);
            return;
        }

        // 1. Switch tab
        openTab(null, serviceTypeShort);
        
        // 2. Scroll and highlight the specific appointment item
        setTimeout(() => {
            const tabContent = document.getElementById(serviceTypeShort);
            if (tabContent) {
                const targetItem = tabContent.querySelector(`.appointment-list-item[data-ref-no="${refNo}"]`);
                if (targetItem) {
                    targetItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Simple highlighting effect (assuming CSS class 'highlight-temp' exists)
                    targetItem.classList.add('highlight-temp');
                    setTimeout(() => {
                        targetItem.classList.remove('highlight-temp');
                    }, 3000); 
                    
                } else {
                    alert(`Appointment with Reference No. ${refNo} not found in the ${serviceTypeShort} tab.`);
                }
            } else {
                alert(`Cannot find the tab content for ${serviceTypeShort}.`);
            }
        }, 100); // Short delay to allow tab switch to complete
    }
    // END: NEW FUNCTION viewAppointmentFromIssue (Walang ibang pinalitan sa code maliban sa itaas at ito)
    
    // START: ADDED NEW JAVASCRIPT FUNCTION FOR ISSUES FILTERING
    function filterIssues(searchTerm, serviceType) {
        // Find the issue list container
        const listContainer = document.querySelector('#issues-concern .issue-list-container');
        const issueItems = listContainer.querySelectorAll('.issue-list-item');
        const noIssuesMessage = listContainer.querySelector('.no-issues-message');

        // Get current filter values if one is null (from the other event)
        // If searchTerm is null, get it from the input. If serviceType is null, get it from the dropdown.
        let currentSearchTerm = searchTerm !== null ? searchTerm.toLowerCase() : listContainer.parentElement.querySelector('.search-container input').value.toLowerCase();
        let currentServiceType = serviceType !== null ? serviceType : listContainer.parentElement.querySelector('.service-type-filter-dropdown').value;

        let visibleCount = 0;

        issueItems.forEach(item => {
            const refNo = item.getAttribute('data-ref-no').toLowerCase();
            const issueType = item.getAttribute('data-issue-type').toLowerCase();
            // Get the new data-service-type attribute
            const itemServiceType = item.getAttribute('data-service-type'); 

            const matchesSearch = currentSearchTerm === '' || refNo.includes(currentSearchTerm) || issueType.includes(currentSearchTerm);
            const matchesServiceType = currentServiceType === 'all' || itemServiceType === currentServiceType;

            if (matchesSearch && matchesServiceType) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Show/hide the "No issues" message
        if (visibleCount === 0) {
            noIssuesMessage.style.display = 'block';
        } else {
            noIssuesMessage.style.display = 'none';
        }
    }
    // END: ADDED NEW JAVASCRIPT FUNCTION FOR ISSUES FILTERING
    
    // START: ADDED NEW JAVASCRIPT FUNCTION FOR RATINGS SUMMARY FILTERING
    function filterRatingsSummary(searchTerm, serviceType) {
        const listContainer = document.querySelector('#ratings-summary .detailed-ratings-list-container');
        const ratingItems = listContainer.querySelectorAll('.rating-list-item');
        
        // Get current filter values. We search for the elements inside the detailed-ratings-list-container now
        let currentSearchTerm = searchTerm !== null ? searchTerm.toLowerCase() : listContainer.querySelector('.search-container input').value.toLowerCase();
        let currentServiceType = serviceType !== null ? serviceType : listContainer.querySelector('.service-type-filter-dropdown').value;

        let visibleCount = 0;

        ratingItems.forEach(item => {
            const refNo = item.getAttribute('data-ref-no').toLowerCase();
            const itemServiceType = item.getAttribute('data-service-type'); // Keep for exact match later
            const serviceTypeName = itemServiceType.toLowerCase();
            const feedbackText = item.querySelector('.item-feedback').textContent.toLowerCase();

            // Search Logic
            const matchesSearch = currentSearchTerm === '' || 
                                refNo.includes(currentSearchTerm) || 
                                serviceTypeName.includes(currentSearchTerm) || 
                                feedbackText.includes(currentSearchTerm);
                                
            // Service Type Filter Logic
            const matchesServiceType = currentServiceType === 'all' || itemServiceType === currentServiceType;

            if (matchesSearch && matchesServiceType) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Handle "No Ratings Found" message
        let noRatingsMessage = document.getElementById('no-ratings-found-message');
        if (!noRatingsMessage) {
            noRatingsMessage = document.createElement('div');
            noRatingsMessage.id = 'no-ratings-found-message';
            noRatingsMessage.innerHTML = "<div style='text-align: center; padding: 30px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; color: #777; margin-top: 20px;'> <i class='bx bx-x-circle' style='font-size: 2em; display: block; margin-bottom: 10px;'></i> No ratings match your current filter selection.</div>";
            listContainer.appendChild(noRatingsMessage);
        }

        if (visibleCount === 0) {
            noRatingsMessage.style.display = 'block';
        } else {
            noRatingsMessage.style.display = 'none';
        }
    }
    // END: ADDED NEW JAVASCRIPT FUNCTION FOR RATINGS SUMMARY FILTERING