
   // --- SERVICE CARD CLICK SELECTION ---
   const serviceCards = document.querySelectorAll('.service-card');

   serviceCards.forEach(card => {
       card.addEventListener('click', () => {
           // Remove 'selected' from all cards
           serviceCards.forEach(c => c.classList.remove('selected'));
           
           // Add 'selected' to clicked card
           card.classList.add('selected');
   
           // Optional: store selected service type
           // Tandaan: Ang 'serviceTypeInput' ay dapat dineclare sa loob ng DOMContentLoaded
           // o globally kung gusto mo itong gumana sa labas.
           const serviceTypeInput = document.getElementById('serviceTypeHidden');
           if (serviceTypeInput) {
               serviceTypeInput.value = card.getAttribute('data-service-type') || '';
           }
       });
   });
   
   
   // --- PRICE COMPUTATION LOGIC (Generic function para sa lahat ng forms) ---
   function updateEstimatedPrice(priceDisplayEl, durationSelectEl, materialsYesEl, materialsNoEl) {
       if (!priceDisplayEl || !durationSelectEl || !materialsYesEl || !materialsNoEl) {
           return;
       }
   
       const selectedDuration = durationSelectEl.value;
       let hourlyRate = 0;
   
       // Check if the price display is for recurring or one-time to determine the correct radio buttons
       // Since the radio button IDs are the same, we check the element passed.
       if (materialsYesEl.checked) {
           hourlyRate = 40;
       } else if (materialsNoEl.checked) {
           hourlyRate = 35;
       }
   
       let calculatedPrice = 0;
       if (selectedDuration) {
           const durationInHours = parseFloat(selectedDuration);
           calculatedPrice = hourlyRate * (isNaN(durationInHours) ? 0 : durationInHours);
       }
   
       // Use Math.round() as you provided in your code for the final display
       priceDisplayEl.textContent = `AED ${Math.round(calculatedPrice)}`;
   }
   
   document.addEventListener('DOMContentLoaded', () => {
       // --- ELEMENT DECLARATIONS (Para makita ng buong function) ---
       const navToggle = document.getElementById('nav-toggle');
       const sidebar = document.querySelector('.dashboard__sidebar');
       const body = document.body;
       const menuLinks = document.querySelectorAll('.sidebar__menu .menu__link');
       const contentSections = document.querySelectorAll('.dashboard__content .content__section');
   
       const logoutModal = document.getElementById('logoutModal');
       const confirmLogoutBtn = document.getElementById('confirmLogout');
       const cancelLogoutBtn = document.getElementById('cancelLogout');
   
       const profileSaveModal = document.getElementById('profileSaveModal');
       const confirmProfileSaveBtn = document.getElementById('confirmProfileSave');
   
       const requiredFieldsModal = document.getElementById('requiredFieldsModal');
       const confirmRequiredFieldsBtn = document.getElementById('confirmRequiredFields');
   
       const cancelModal = document.getElementById('cancelModal');
       const yesCancelBtn = document.getElementById('yesCancel');
       const noCancelBtn = document.getElementById('noCancel');
   
       const profileEditBtn = document.getElementById('editProfileBtn');
       const profileForm = document.getElementById('profileForm');
       const profileFormInputs = document.querySelectorAll('#profileForm input');
       const cancelEditBtn = document.getElementById('cancelEditBtn');
   
       // Create Save Button dynamically
       const saveBtn = document.createElement('button');
       saveBtn.setAttribute('type', 'submit');
       saveBtn.id = 'saveProfileBtn';
       saveBtn.className = 'btn btn--success';
       saveBtn.textContent = 'Save';
   
       const initialProfileData = {};
   
       function revertToDefaultProfileState() {
           if (saveBtn.parentNode) {
               saveBtn.parentNode.removeChild(saveBtn);
           }
   
           if (profileEditBtn) profileEditBtn.style.display = 'block';
           if (cancelEditBtn) cancelEditBtn.style.display = 'none';
   
           profileFormInputs.forEach(input => {
               input.disabled = true;
               input.value = initialProfileData[input.name] || '';
               input.classList.remove('is-invalid');
           });
       }
   
       // --- SET ACTIVE LINK BASED ON URL AND SHOW CORRESPONDING SECTION ---
       function setActiveLinkBasedOnUrl() {
           const currentPathname = window.location.pathname.split('/').pop();
           const currentUrlParams = new URLSearchParams(window.location.search);
           const contentParam = currentUrlParams.get('content');
   
           // 1. Reset all links and content
           menuLinks.forEach(link => {
               link.classList.remove('active');
               const parentItem = link.closest('.menu__item');
               if (parentItem) {
                   // Do not remove 'open' or 'active-parent' yet, as we need them for direct page loads (HIS_one-time.php)
                   // We reset them implicitly when setting the correct one.
                   const parentDropdown = parentItem.closest('.has-dropdown');
                   if (parentDropdown) {
                       parentDropdown.classList.remove('open');
                       parentDropdown.querySelector('.menu__link').classList.remove('active-parent');
                       const dropdownMenu = parentDropdown.querySelector('.dropdown__menu');
                       if (dropdownMenu) dropdownMenu.style.maxHeight = '0'; // Collapse dropdown
                   }
               }
           });
   
           contentSections.forEach(section => section.classList.remove('active'));
   
           let activeLinkFound = false;
   
           // 2. Handle client_dashboard.php?content=...
           if (contentParam && currentPathname === 'client_dashboard.php') {
               const targetLink = document.querySelector(`.menu__link[data-content="${contentParam}"]`);
               if (targetLink) {
                   targetLink.classList.add('active');
                   const targetSection = document.getElementById(contentParam);
                   if (targetSection) targetSection.classList.add('active');
                   activeLinkFound = true;
               }
           }
   
           // 3. Handle direct URL pages (HIS_one-time.php, BA_recurring.php, etc.)
           const exactMatchLink = document.querySelector(`.menu__link[href="${currentPathname}"]`);
           if (exactMatchLink) {
               exactMatchLink.classList.add('active');
               activeLinkFound = true;
   
               const parentDropdown = exactMatchLink.closest('.has-dropdown');
               if (parentDropdown) {
                   // This block ensures the parent of HIS_one-time.php or BA_one-time.php is OPEN and ACTIVE.
                   parentDropdown.classList.add('open');
                   const parentLink = parentDropdown.querySelector('.menu__link');
                   if (parentLink) parentLink.classList.add('active-parent');
                   
                   // Open the dropdown menu smoothly
                   const dropdownMenu = parentDropdown.querySelector('.dropdown__menu');
                   if (dropdownMenu) {
                       dropdownMenu.classList.add('show');
                       dropdownMenu.style.maxHeight = dropdownMenu.scrollHeight + "px";
                   }
               }
           }
           
           // ** IDINAGDAG ANG EXPLICIT FORCE SETTING DITO PARA SA HIS_RECURRING.PHP **
           if (currentPathname === 'HIS_recurring.php') {
               const historyParentLink = document.querySelector('.menu__link[data-content="history-parent"]');
               const recurringLink = document.querySelector('.menu__link[href="HIS_recurring.php"]');
               
               // Tiyakin na ang parent at ang link ay active at open
               if (historyParentLink) historyParentLink.classList.add('active-parent');
               if (recurringLink) {
                   recurringLink.classList.add('active');
                   const parentDropdown = recurringLink.closest('.has-dropdown');
                   if (parentDropdown) {
                       parentDropdown.classList.add('open');
                       const dropdownMenu = parentDropdown.querySelector('.dropdown__menu');
                       if (dropdownMenu) {
                           dropdownMenu.classList.add('show');
                           dropdownMenu.style.maxHeight = dropdownMenu.scrollHeight + "px";
                       }
                   }
               }
               activeLinkFound = true;
           }
           // *******************************************************************
   
           // 4. Fallback to Dashboard if no active link is found
           if (!activeLinkFound) {
               const dashboardLink = document.querySelector('.menu__link[data-content="dashboard"]');
               const dashboardSection = document.getElementById('dashboard');
               if (dashboardLink) dashboardLink.classList.add('active');
               if (dashboardSection) dashboardSection.classList.add('active');
           }
   
           // 5. Explicitly show content section based on page name (Tiyakin na lalabas ang content)
           // ** THIS IS THE CRUCIAL BLOCK FOR PAGE CONTENT VISIBILITY **
           if (currentPathname === 'BA_one-time.php') {
               const oneTimeContent = document.getElementById('one-time-service-content');
               if (oneTimeContent) oneTimeContent.classList.add('active');
           } else if (currentPathname === 'EDIT_one-time.php') { // <-- IDINAGDAG ANG EDIT PAGE LOGIC
               // Note: Ang ID 'edit-one-time-service-content' ay kailangang naroon sa iyong EDIT_one-time.php file
               const editOneTimeContent = document.getElementById('edit-one-time-service-content');
               if (editOneTimeContent) editOneTimeContent.classList.add('active');
           } else if (currentPathname === 'BA_recurring.php') {
               const recurringContent = document.getElementById('recurring-service-content');
               if (recurringContent) recurringContent.classList.add('active');
           } else if (currentPathname === 'HIS_one-time.php') {
               const hisOneTimeContent = document.getElementById('history-one-time-content');
               if (hisOneTimeContent) hisOneTimeContent.classList.add('active');
           } else if (currentPathname === 'HIS_recurring.php') {
               const hisRecurringContent = document.getElementById('history-recurring-content');
               if (hisRecurringContent) hisRecurringContent.classList.add('active');
           } 
           // ADDED LOGIC FOR FEEDBACK/RATINGS PAGES (FR_one-time.php and FR_recurring.php)
           else if (currentPathname === 'FR_one-time.php') {
               const frOneTimeContent = document.getElementById('feedback-one-time-content');
               if (frOneTimeContent) frOneTimeContent.classList.add('active');
           } else if (currentPathname === 'FR_recurring.php') {
               const frRecurringContent = document.getElementById('feedback-recurring-content');
               if (frRecurringContent) frRecurringContent.classList.add('active');
           }
           // ADD MORE ELSE IF BLOCKS HERE for FR_one-time.php, FR_recurring.php, etc.
   
   // ADDED LOGIC FOR PROFILE PAGE
   else if (currentPathname === 'client_profile.php') {
       // Note: Ang ID 'profile-content' ay kailangang naroon sa iyong client_profile.php file
       const profileContent = document.getElementById('profile-content');
       if (profileContent) profileContent.classList.add('active');
   }
   
   
       }
   
       // --- OTHER LOGIC (Lahat ay nasa loob ng DOMContentLoaded) ---
   
       // Initialize Profile data and state
       profileFormInputs.forEach(input => {
           initialProfileData[input.name] = input.value;
           input.disabled = true;
       });
   
       // Mobile Sidebar Toggle
       if (navToggle && sidebar) {
           navToggle.addEventListener('click', () => {
               sidebar.classList.toggle('show-sidebar');
               body.classList.toggle('show-sidebar');
           });
       }
   
       // Close Sidebar when clicking outside (FOR MOBILE)
       document.addEventListener('click', (event) => {
           if (sidebar.classList.contains('show-sidebar') && window.innerWidth <= 768) {
               const isClickInsideSidebar = sidebar.contains(event.target);
               const isClickOnToggle = navToggle && navToggle.contains(event.target);
   
               if (!isClickInsideSidebar && !isClickOnToggle) {
                   sidebar.classList.remove('show-sidebar');
                   body.classList.remove('show-sidebar');
               }
           }
       });
   
   
       // Sidebar Link Click Handler
       menuLinks.forEach(link => {
           link.addEventListener('click', (event) => {
               const targetId = link.getAttribute('data-content');
               const parentItem = link.closest('.menu__item');
   
               // --- DROPDOWN TOGGLE LOGIC: This handles the History, Booking, and Feedback clicks ---
               if (parentItem && parentItem.classList.contains('has-dropdown')) {
                   event.preventDefault();
   
                   // Toggle 'open' state (This is redundant with the HIS_one-time.php logic but handles internal clicking)
                   const dropdownMenu = parentItem.querySelector('.dropdown__menu');
                   if (parentItem.classList.contains('open')) {
                       parentItem.classList.remove('open');
                       dropdownMenu.style.maxHeight = '0';
                       dropdownMenu.classList.remove('show');
                       link.classList.remove('active-parent');
                   } else {
                       // Close other open dropdowns
                       document.querySelectorAll('.sidebar__menu .has-dropdown.open').forEach(otherParent => {
                           if (otherParent !== parentItem) {
                               otherParent.classList.remove('open');
                               otherParent.querySelector('.dropdown__menu').style.maxHeight = '0';
                               otherParent.querySelector('.dropdown__menu').classList.remove('show');
                               otherParent.querySelector('.menu__link').classList.remove('active-parent');
                           }
                       });
                       
                       // Open current dropdown
                       parentItem.classList.add('open');
                       dropdownMenu.classList.add('show');
                       dropdownMenu.style.maxHeight = dropdownMenu.scrollHeight + "px";
                       link.classList.add('active-parent');
                   }
                   return;
               }
   
               if (targetId === 'logout') {
                   event.preventDefault();
                   if (logoutModal) logoutModal.classList.add('show');
               }
   
               // Close sidebar after clicking a non-dropdown link on mobile
               if (window.innerWidth <= 768) {
                   sidebar.classList.remove('show-sidebar');
                   body.classList.remove('show-sidebar');
               }
           });
       });
   
       // --- MODAL HANDLING ---
       if (confirmLogoutBtn) confirmLogoutBtn.addEventListener('click', () => window.location.href = 'landing_page2.html');
       if (cancelLogoutBtn) cancelLogoutBtn.addEventListener('click', () => { if (logoutModal) logoutModal.classList.remove('show'); });
       if (confirmProfileSaveBtn) confirmProfileSaveBtn.addEventListener('click', () => { if (profileSaveModal) profileSaveModal.classList.remove('show'); revertToDefaultProfileState(); });
       if (confirmRequiredFieldsBtn) confirmRequiredFieldsBtn.addEventListener('click', () => { if (requiredFieldsModal) requiredFieldsModal.classList.remove('show'); });
   
       window.addEventListener('click', (event) => {
           if (event.target === logoutModal) { if (logoutModal) logoutModal.classList.remove('show'); }
           if (event.target === profileSaveModal) { if (profileSaveModal) profileSaveModal.classList.remove('show'); revertToDefaultProfileState(); }
           if (event.target === requiredFieldsModal) { if (requiredFieldsModal) requiredFieldsModal.classList.remove('show'); }
           if (event.target === cancelModal) { if (cancelModal) cancelModal.classList.remove('show'); }
       });
   
       // Profile Edit/Save Logic
       if (profileEditBtn) {
           profileEditBtn.addEventListener('click', () => {
               profileEditBtn.style.display = 'none';
               if (cancelEditBtn) cancelEditBtn.style.display = 'block';
               const formActions = document.querySelector('.form__actions');
               if (formActions) formActions.appendChild(saveBtn);
               profileFormInputs.forEach(input => input.disabled = false);
           });
       }
   
       if (saveBtn) {
           saveBtn.addEventListener('click', (event) => {
               event.preventDefault();
   
               let isValid = true;
               const formInputs = document.querySelectorAll('#profileForm input');
   
               formInputs.forEach(input => {
                   if (input.id === 'contactNumber') {
                       // Check if contact number has more than 4 characters (to account for +971)
                       if (input.value.length <= 4) {
                           isValid = false;
                           input.classList.add('is-invalid');
                       } else input.classList.remove('is-invalid');
                   } else if (input.value.trim() === '') {
                       isValid = false;
                       input.classList.add('is-invalid');
                   } else input.classList.remove('is-invalid');
               });
   
               if (!isValid) {
                   if (requiredFieldsModal) requiredFieldsModal.classList.add('show');
                   return;
               }
   
               // Assume validation passed and show save confirmation
               if (profileSaveModal) profileSaveModal.classList.add('show');
           });
       }
   
       if (cancelEditBtn) cancelEditBtn.addEventListener('click', (event) => { event.preventDefault(); if (cancelModal) cancelModal.classList.add('show'); });
       if (yesCancelBtn) yesCancelBtn.addEventListener('click', () => { if (cancelModal) cancelModal.classList.remove('show'); revertToDefaultProfileState(); });
       if (noCancelBtn) noCancelBtn.addEventListener('click', () => { if (cancelModal) cancelModal.classList.remove('show'); });
   
       // --- CONTACT NUMBER PREFIX ---
       const contactNumberInput = document.getElementById('contactNumber');
       const prefix = '+971';
       const maxLength = 9;
   
       if (contactNumberInput) {
           // Ensure the field starts with the prefix if it's currently empty or not set
           if (!contactNumberInput.value.startsWith(prefix)) {
               contactNumberInput.value = prefix;
           }
   
           contactNumberInput.addEventListener('input', () => {
               let value = contactNumberInput.value;
               // Always ensure the prefix is there
               if (!value.startsWith(prefix)) contactNumberInput.value = prefix;
               
               // Extract the numeric part after the prefix and enforce max length
               const numericPart = value.substring(prefix.length).replace(/\D/g, '');
               const truncatedValue = numericPart.substring(0, maxLength);
               contactNumberInput.value = prefix + truncatedValue;
           });
       }
   
       // --- LOGIC FOR BOOKING FORMS (one-time.php / recurring.php) ---
       const serviceTypeInput = document.getElementById('serviceTypeHidden');
       const clientTypeSelect = document.getElementById('clientType');
   
       const clientTypeOptions = {
           'Checkout Cleaning': ['Holiday Apartment'],
           'In-House Cleaning': ['Holiday Apartment'],
           'Refresh Cleaning': ['Holiday Apartment', 'Residential', 'Offices'],
           'Deep Cleaning': ['Holiday Apartment', 'Residential', 'Offices']
       };
   
       const bookingDateInput = document.getElementById('bookingDate');
       const bookingTimeInput = document.getElementById('bookingTime');
       const durationSelect = document.getElementById('duration');
       const priceDisplay = document.querySelector('.price-display'); // For one-time form
       const materialsYes = document.getElementById('materialsYes'); // For one-time form
       const materialsNo = document.getElementById('materialsNo'); // For one-time form
   
       // RECURRING SPECIFIC ELEMENTS (for recurring.php)
       const recurringDurationSelect = document.getElementById('duration');
       const recurringMaterialsYes = document.getElementById('materialsYesRecurring');
       const recurringMaterialsNo = document.getElementById('materialsNoRecurring');
       const recurringPriceDisplay = document.getElementById('recurringPrice');
   
   
       // --- INITIAL STATE ---
       if (clientTypeSelect) {
           clientTypeSelect.disabled = true;
           clientTypeSelect.innerHTML = `<option value="">Select a service type first...</option>`;
       }
       if (bookingDateInput) bookingDateInput.disabled = true;
       if (bookingTimeInput) bookingTimeInput.disabled = true;
       if (durationSelect) durationSelect.disabled = true;
   
       // --- SERVICE TYPE SELECTION (The part that was outside the wrapper) ---
       const serviceCardsGlobal = document.querySelectorAll('.service-card'); // Use a new declaration if the top one is global
       serviceCardsGlobal.forEach(card => {
           card.addEventListener('click', () => {
               serviceCardsGlobal.forEach(c => c.classList.remove('selected'));
               card.classList.add('selected');
   
               const selectedService = card.getAttribute('data-service-type');
               if (serviceTypeInput) serviceTypeInput.value = selectedService;
   
               // enable clientType
               if (clientTypeSelect) {
                   clientTypeSelect.disabled = false;
                   clientTypeSelect.innerHTML = `<option value="">Select Client Type...</option>`;
   
                   const allowedClients = clientTypeOptions[selectedService] || [];
                   allowedClients.forEach(type => {
                       const opt = document.createElement('option');
                       opt.value = type;
                       opt.textContent = type;
                       clientTypeSelect.appendChild(opt);
                   });
               }
   
               // reset others
               if (bookingDateInput) bookingDateInput.disabled = true;
               if (bookingTimeInput) bookingTimeInput.disabled = true;
               if (durationSelect) durationSelect.disabled = true;
               if (bookingDateInput) bookingDateInput.value = '';
               if (bookingTimeInput) bookingTimeInput.value = '';
               if (durationSelect) durationSelect.value = '';
           });
       });
   
       // --- CLIENT TYPE → enable Date ---
       if (clientTypeSelect) {
           clientTypeSelect.addEventListener('change', () => {
               if (clientTypeSelect.value) {
                   if (bookingDateInput) bookingDateInput.disabled = false;
               } else {
                   if (bookingDateInput) bookingDateInput.disabled = true;
                   if (bookingTimeInput) bookingTimeInput.disabled = true;
                   if (durationSelect) durationSelect.disabled = true;
               }
           });
       }
   
       // --- DATE → enable Time ---
       if (bookingDateInput) {
           bookingDateInput.addEventListener('change', () => {
               if (bookingDateInput.value) {
                   if (bookingTimeInput) bookingTimeInput.disabled = false;
               } else {
                   if (bookingTimeInput) bookingTimeInput.disabled = true;
                   if (durationSelect) durationSelect.disabled = true;
               }
           });
       }
   
       // --- TIME → enable Duration ---
       if (bookingTimeInput) {
           bookingTimeInput.addEventListener('change', () => {
               if (bookingTimeInput.value) {
                   if (durationSelect) durationSelect.disabled = false;
               } else {
                   if (durationSelect) durationSelect.disabled = true;
               }
           });
       }
   
       // --- ONE-TIME PRICE CALC ---
       if (materialsYes) {
           materialsYes.addEventListener('change', () => updateEstimatedPrice(priceDisplay, durationSelect, materialsYes, materialsNo));
       }
       if (materialsNo) {
           materialsNo.addEventListener('change', () => updateEstimatedPrice(priceDisplay, durationSelect, materialsYes, materialsNo));
       }
       if (durationSelect) {
           durationSelect.addEventListener('change', () => updateEstimatedPrice(priceDisplay, durationSelect, materialsYes, materialsNo));
       }
       // Initial call (dapat sa dulo para may value na ang mga elements)
       if (priceDisplay && durationSelect && materialsYes && materialsNo) {
           updateEstimatedPrice(priceDisplay, durationSelect, materialsYes, materialsNo);
       }
   
       // --- RECURRING PRICE CALC ---
       if (recurringPriceDisplay && recurringDurationSelect && recurringMaterialsYes && recurringMaterialsNo) {
           recurringMaterialsYes.addEventListener('change', () => 
               updateEstimatedPrice(recurringPriceDisplay, recurringDurationSelect, recurringMaterialsYes, recurringMaterialsNo)
           );
           recurringMaterialsNo.addEventListener('change', () => 
               updateEstimatedPrice(recurringPriceDisplay, recurringDurationSelect, recurringMaterialsYes, recurringMaterialsNo)
           );
           recurringDurationSelect.addEventListener('change', () => 
               updateEstimatedPrice(recurringPriceDisplay, recurringDurationSelect, recurringMaterialsYes, recurringMaterialsNo)
           );
   
           // Initial call
           updateEstimatedPrice(recurringPriceDisplay, recurringDurationSelect, recurringMaterialsYes, recurringMaterialsNo);
       }
   
   
       // --- ONE-TIME BOOKING FORM SUBMISSION ---
       const oneTimeBookingForm = document.getElementById('oneTimeBookingForm');
       if (oneTimeBookingForm) {
           oneTimeBookingForm.addEventListener('submit', (e) => {
               e.preventDefault();
   
               let hasError = false;
               const requiredInputs = oneTimeBookingForm.querySelectorAll('[required]');
   
               requiredInputs.forEach(input => {
                   if (!input.value.trim()) {
                       input.classList.add('is-invalid');
                       hasError = true;
                   } else {
                       input.classList.remove('is-invalid');
                   }
               });
   
               if (hasError) return;
               oneTimeBookingForm.submit();
           });
       }
   
       // --- RECURRING BOOKING FORM SUBMISSION ---
       const recurringBookingForm = document.getElementById('recurringBookingForm');
       if (recurringBookingForm) {
           recurringBookingForm.addEventListener('submit', (e) => {
               e.preventDefault();
   
               let hasError = false;
               const requiredInputs = recurringBookingForm.querySelectorAll('[required]');
   
               requiredInputs.forEach(input => {
                   if (!input.value.trim()) {
                       input.classList.add('is-invalid');
                       hasError = true;
                   } else {
                       input.classList.remove('is-invalid');
                   }
               });
   
               if (hasError) return;
               recurringBookingForm.submit();
           });
       }
   
       // --- FINAL CALLS ---
       revertToDefaultProfileState();
       setActiveLinkBasedOnUrl();
   });