// document.addEventListener('DOMContentLoaded', () => {
//     // --- ELEMENT DECLARATIONS ---
//     const navToggle = document.getElementById('nav-toggle');
//     const sidebar = document.querySelector('.dashboard__sidebar');
//     const body = document.body;
//     const menuLinks = document.querySelectorAll('.sidebar__menu .menu__link');
//     const contentSections = document.querySelectorAll('.dashboard__content .content__section');

//     const logoutModal = document.getElementById('logoutModal');
//     const confirmLogoutBtn = document.getElementById('confirmLogout');
//     const cancelLogoutBtn = document.getElementById('cancelLogout');

//     const profileSaveModal = document.getElementById('profileSaveModal');
//     const confirmProfileSaveBtn = document.getElementById('confirmProfileSave');

//     const requiredFieldsModal = document.getElementById('requiredFieldsModal');
//     const confirmRequiredFieldsBtn = document.getElementById('confirmRequiredFields');

//     const cancelModal = document.getElementById('cancelModal');
//     const yesCancelBtn = document.getElementById('yesCancel');
//     const noCancelBtn = document.getElementById('noCancel');

//     const profileEditBtn = document.getElementById('editProfileBtn');
//     const profileForm = document.getElementById('profileForm');
//     const profileFormInputs = document.querySelectorAll('#profileForm input');
//     const cancelEditBtn = document.getElementById('cancelEditBtn');

//     // --- SAVE BUTTON (Dynamic) ---
//     const saveBtn = document.createElement('button');
//     saveBtn.type = 'submit';
//     saveBtn.id = 'saveProfileBtn';
//     saveBtn.className = 'btn btn--success';
//     saveBtn.textContent = 'Save';

//     const initialProfileData = {};

//     function revertToDefaultProfileState() {
//         if (saveBtn.parentNode) saveBtn.parentNode.removeChild(saveBtn);
//         if (profileEditBtn) profileEditBtn.style.display = 'block';
//         if (cancelEditBtn) cancelEditBtn.style.display = 'none';

//         profileFormInputs.forEach(input => {
//             input.disabled = true;
//             input.value = initialProfileData[input.name] || '';
//             input.classList.remove('is-invalid');
//         });
//     }
    
//     // --- NEW: CENTRALIZED ACTIVATION FUNCTION ---
//     function activateContentSection(contentId) {
//         // 1. Deactivate all sections
//         contentSections.forEach(section => section.classList.remove('active'));
        
//         // 2. Activate the correct section
//         const targetSection = document.getElementById(contentId);
//         if (targetSection) {
//             targetSection.classList.add('active');
//         }
//     }

//     // --- NEW: INITIAL PAGE LOAD LOGIC (Pinalitan ang PHP) ---
//     function initializeActiveState() {
//         // 1. Reset all links and dropdowns
//         menuLinks.forEach(link => {
//             link.classList.remove('active', 'active-parent');
//             const parentDropdown = link.closest('.has-dropdown');
//             if (parentDropdown) {
//                 parentDropdown.classList.remove('open');
//                 const dropdownMenu = parentDropdown.querySelector('.dropdown__menu');
//                 if (dropdownMenu) dropdownMenu.style.maxHeight = '0';
//             }
//         });

//         const currentPathname = window.location.pathname.split('/').pop();
//         const urlParams = new URLSearchParams(window.location.search);
//         let contentIdToActivate = null;
        
//         if (currentPathname === 'admin_dashboard.php') {
//             // A. Kumuha ng 'content' parameter (e.g., ?content=reports)
//             contentIdToActivate = urlParams.get('content');
            
//             // B. Kung walang parameter, default sa 'dashboard'
//             if (!contentIdToActivate || contentIdToActivate.trim() === '') {
//                 contentIdToActivate = 'dashboard';
//             }
            
//             // C. Hanapin at i-activate ang link
//             const targetLink = document.querySelector(`.menu__link[data-content="${contentIdToActivate}"]`);
//             if (targetLink) {
//                 targetLink.classList.add('active');
//             }
            
//             // D. I-activate ang content section
//             activateContentSection(contentIdToActivate);

//         } else {
//             // 2. Logic para sa ibang PHP pages (e.g., UM_clients.php)
//             const exactMatchLink = document.querySelector(`.menu__link[href="${currentPathname}"]`);
            
//             if (exactMatchLink) {
//                 exactMatchLink.classList.add('active');

//                 const parentDropdown = exactMatchLink.closest('.has-dropdown');
//                 if (parentDropdown) {
//                     parentDropdown.classList.add('open');
//                     const parentLink = parentDropdown.querySelector('.menu__link');
//                     if (parentLink) parentLink.classList.add('active-parent');
                    
//                     const dropdownMenu = parentDropdown.querySelector('.dropdown__menu');
//                     if (dropdownMenu) {
//                         dropdownMenu.style.maxHeight = dropdownMenu.scrollHeight + "px";
//                     }
//                 }
//             }
            
//             // 3. I-activate ang Content Section sa ibang PHP page (based on your existing IDs)
//             let contentId = '';
//             if (currentPathname === 'UM_clients.php') contentId = 'UM-clients-content';
//             else if (currentPathname === 'UM_employees.php') contentId = 'UM-employees-content';
//             else if (currentPathname === 'UM_admins.php') contentId = 'UM-admins-content';
//             else if (currentPathname === 'AP_one-time.php') contentId = 'AP-one-time-content';
//             else if (currentPathname === 'AP_recurring.php') contentId = 'AP-recurring-content';
//             else if (currentPathname === 'ES.php') contentId = 'employee-scheduling-content';
//             else if (currentPathname === 'FR.php') contentId = 'feedbackratings-content';
//             else if (currentPathname === 'Reports.php') contentId = 'reports-content';
//             else if (currentPathname === 'admin_profile.php') contentId = 'profile-content';

//             if (contentId) activateContentSection(contentId);
//         }
//     }


//     // --- Mobile Sidebar Toggle at Click Outside Logic (Hindi Binago) ---
//     if (navToggle && sidebar) {
//         navToggle.addEventListener('click', () => {
//             sidebar.classList.toggle('show-sidebar');
//             body.classList.toggle('show-sidebar');
//         });
//     }

//     document.addEventListener('click', (event) => {
//         if (sidebar.classList.contains('show-sidebar') && window.innerWidth <= 768) {
//             const isClickInsideSidebar = sidebar.contains(event.target);
//             const isClickOnToggle = navToggle && navToggle.contains(event.target);

//             if (!isClickInsideSidebar && !isClickOnToggle) {
//                 sidebar.classList.remove('show-sidebar');
//                 body.classList.remove('show-sidebar');
//             }
//         }
//     });


//     // --- Sidebar Link Click Handler (FIXED to include URL persistence) ---
//     menuLinks.forEach(link => {
//         link.addEventListener('click', (event) => {
//             const targetId = link.getAttribute('data-content');
//             const parentItem = link.closest('.menu__item');

//             // 1. Dropdown Toggle Logic
//             if (parentItem && parentItem.classList.contains('has-dropdown')) {
//                 event.preventDefault();
//                 const dropdownMenu = parentItem.querySelector('.dropdown__menu');
//                 if (parentItem.classList.contains('open')) {
//                     parentItem.classList.remove('open');
//                     dropdownMenu.style.maxHeight = '0';
//                     dropdownMenu.classList.remove('show');
//                     link.classList.remove('active-parent');
//                 } else {
//                     document.querySelectorAll('.sidebar__menu .has-dropdown.open').forEach(otherParent => {
//                         if (otherParent !== parentItem) {
//                             otherParent.classList.remove('open');
//                             otherParent.querySelector('.dropdown__menu').style.maxHeight = '0';
//                             otherParent.querySelector('.dropdown__menu').classList.remove('show');
//                             otherParent.querySelector('.menu__link').classList.remove('active-parent');
//                         }
//                     });
                    
//                     parentItem.classList.add('open');
//                     dropdownMenu.classList.add('show');
//                     dropdownMenu.style.maxHeight = dropdownMenu.scrollHeight + "px";
//                     link.classList.add('active-parent');
//                 }
//                 return;
//             }

//             // 2. Internal Dashboard Link Logic (Tab switching within admin_dashboard.php)
//             if (targetId && targetId !== 'logout' && link.closest('.dropdown__menu') === null) {
//                 // Tiyakin na ito ay isang link na nagre-redirect sa admin_dashboard.php
//                 if (link.href.split('/').pop().split('?')[0] === 'admin_dashboard.php') {
//                     event.preventDefault(); // Pigilan ang page reload

//                     // I-update ang URL at i-activate ang section
//                     history.pushState(null, '', `admin_dashboard.php?content=${targetId}`);
                    
//                     // I-activate ang link at content
//                     menuLinks.forEach(l => l.classList.remove('active'));
//                     link.classList.add('active');
//                     activateContentSection(targetId);
//                 }
//             }

//             // 3. Logout Modal Logic
//             if (targetId === 'logout') {
//                 event.preventDefault();
//                 if (logoutModal) logoutModal.classList.add('show');
//             }

//             // 4. Close sidebar on mobile
//             if (window.innerWidth <= 768) {
//                 sidebar.classList.remove('show-sidebar');
//                 body.classList.remove('show-sidebar');
//             }
//         });
//     });

//     // --- MODAL HANDLING (Hindi Binago) ---
//     if (confirmLogoutBtn) confirmLogoutBtn.addEventListener('click', () => window.location.href = 'landing_page2.html');
//     if (cancelLogoutBtn) cancelLogoutBtn.addEventListener('click', () => { if (logoutModal) logoutModal.classList.remove('show'); });
//     if (confirmProfileSaveBtn) confirmProfileSaveBtn.addEventListener('click', () => { if (profileSaveModal) profileSaveModal.classList.remove('show'); revertToDefaultProfileState(); });
//     if (confirmRequiredFieldsBtn) confirmRequiredFieldsBtn.addEventListener('click', () => { if (requiredFieldsModal) requiredFieldsModal.classList.remove('show'); });

//     window.addEventListener('click', (event) => {
//         if (event.target === logoutModal) { if (logoutModal) logoutModal.classList.remove('show'); }
//         if (event.target === profileSaveModal) { if (profileSaveModal) profileSaveModal.classList.remove('show'); revertToDefaultProfileState(); }
//         if (event.target === requiredFieldsModal) { if (requiredFieldsModal) requiredFieldsModal.classList.remove('show'); }
//         if (event.target === cancelModal) { if (cancelModal) cancelModal.classList.remove('show'); }
//     });

//     // Profile Edit/Save Logic (Hindi Binago)
//     if (profileEditBtn) {
//         profileEditBtn.addEventListener('click', () => {
//             profileEditBtn.style.display = 'none';
//             if (cancelEditBtn) cancelEditBtn.style.display = 'block';
//             const formActions = document.querySelector('.form__actions');
//             if (formActions) formActions.appendChild(saveBtn);
//             profileFormInputs.forEach(input => input.disabled = false);
//         });
//     }

//     if (saveBtn) {
//         saveBtn.addEventListener('click', (event) => {
//             event.preventDefault();

//             let isValid = true;
//             const formInputs = document.querySelectorAll('#profileForm input');

//             formInputs.forEach(input => {
//                 if (input.id === 'contactNumber') {
//                     // Check if contact number has more than 4 characters (to account for +971)
//                     if (input.value.length <= 4) {
//                         isValid = false;
//                         input.classList.add('is-invalid');
//                     } else input.classList.remove('is-invalid');
//                 } else if (input.value.trim() === '') {
//                     isValid = false;
//                     input.classList.add('is-invalid');
//                 } else input.classList.remove('is-invalid');
//             });

//             if (!isValid) {
//                 if (requiredFieldsModal) requiredFieldsModal.classList.add('show');
//                 return;
//             }

//             // Assume validation passed and show save confirmation
//             if (profileSaveModal) profileSaveModal.classList.add('show');
//         });
//     }

//     if (cancelEditBtn) cancelEditBtn.addEventListener('click', (event) => { event.preventDefault(); if (cancelModal) cancelModal.classList.add('show'); });
//     if (yesCancelBtn) yesCancelBtn.addEventListener('click', () => { if (cancelModal) cancelModal.classList.remove('show'); revertToDefaultProfileState(); });
//     if (noCancelBtn) noCancelBtn.addEventListener('click', () => { if (cancelModal) cancelModal.classList.remove('show'); });


//     // --- FINAL CALLS ---
//     initializeActiveState(); // Tiyakin na ang tamang section ang magpapakita sa simula
//     revertToDefaultProfileState(); 
// });