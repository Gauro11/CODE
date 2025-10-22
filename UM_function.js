    // --- Profile Section Edit/Save/Cancel Logic & Age Calculation ---
    
    const profileForm = document.getElementById('profileForm');
    const editableFields = profileForm.querySelectorAll('input:not([readonly]):not([disabled]):not([type="submit"]):not([type="button"]), select:not([disabled])');
    const birthdayField = document.getElementById('birthday');
    const ageField = document.getElementById('age');
    
    const editProfileBtn = document.getElementById('editProfileBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    
    const profileSaveModal = document.getElementById('profileSaveModal');
    const requiredFieldsModal = document.getElementById('requiredFieldsModal');
    const cancelModal = document.getElementById('cancelModal');

    // KINAKAILANGAN: Variable para i-store ang original values
    let originalFormValues = {};
    
    // Function para kumuha ng current values ng form
    function captureOriginalValues() {
        editableFields.forEach(field => {
            originalFormValues[field.id] = field.value;
        });
    }

    // Function para i-disable ang fields at i-set sa Read-only Mode
    function setReadOnlyMode(revert = false) {
        editableFields.forEach(field => {
            field.setAttribute('disabled', 'disabled');
            if (revert) {
                // Ibalik sa original value kapag nag-Cancel
                field.value = originalFormValues[field.id];
            }
        });
        
        editProfileBtn.innerHTML = "<i class='bx bx-edit'></i> Edit Profile";
        cancelEditBtn.style.display = 'none';
        
        // I-re-calculate ang edad kapag nag-revert para sa consistency (kung na-reset ang birthday)
        if (revert && birthdayField) calculateAge();
    }
    
    // Function para i-enable ang fields at i-set sa Edit Mode
    function setEditMode() {
        // I-store muna ang original values bago mag-enable
        captureOriginalValues();
        
        editableFields.forEach(field => {
            field.removeAttribute('disabled');
        });
        
        editProfileBtn.innerHTML = "<i class='bx bx-save'></i> Save Changes";
        cancelEditBtn.style.display = 'block';
    }

    // Function para mag-validate ng required fields
    function validateForm() {
        let isValid = true;
        profileForm.querySelectorAll('[required]:not([disabled])').forEach(field => {
            if (field.value.trim() === '') {
                isValid = false;
                // Kung gusto mong mag-highlight ng invalid field
                field.style.borderColor = 'red'; 
            } else {
                field.style.borderColor = ''; // Ibalik sa default
            }
        });
        return isValid;
    }
    
    // 5. Age Calculation Function
    function calculateAge() {
        if (!birthdayField || !ageField || !birthdayField.value) {
            ageField.value = '';
            return;
        }

        const birthday = new Date(birthdayField.value);
        const today = new Date();
        
        let age = today.getFullYear() - birthday.getFullYear();
        const monthDifference = today.getMonth() - birthday.getMonth();

        // I-adjust ang edad kung hindi pa naabot ang kaarawan sa taong ito
        if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthday.getDate())) {
            age--;
        }
        
        ageField.value = age >= 0 ? age : 0;
    }

    // 1. Toggle Edit Mode (Initial check for logic)
    editProfileBtn.addEventListener('click', function(e) {
        if (editProfileBtn.textContent.includes('Edit Profile')) {
            // Pasok sa Edit Mode
            setEditMode();
            
        } else {
            // 2. Save Changes (Check for validation)
            e.preventDefault();
            
            if (validateForm()) {
                // Valid, mag-Save
                setReadOnlyMode(false);
                profileSaveModal.classList.add('show');
                
            } else {
                // Hindi Valid, ipakita ang Required Fields Modal
                requiredFieldsModal.classList.add('show');
            }
        }
    });

    // 3. Cancel Changes
    cancelEditBtn.addEventListener('click', function() {
        // Ipakita ang Discard Changes Modal
        cancelModal.classList.add('show');
    });

    // 4. Confirm Discard (Yes button sa loob ng #cancelModal)
    document.getElementById('yesCancel').addEventListener('click', function() {
        setReadOnlyMode(true); // I-reset ang form at i-disable ang fields
        cancelModal.classList.remove('show');
    });
    
    // Cancel Discard (No button sa loob ng #cancelModal)
    document.getElementById('noCancel').addEventListener('click', function() {
        cancelModal.classList.remove('show');
    });

    // 5. Birthday Field Listener
    if (birthdayField) {
        birthdayField.addEventListener('change', calculateAge);
    }
    
    // Initial check ng edad sa page load, para kung may default value
    if (birthdayField) calculateAge();
    
    // --- Core Navigation Fix Logic (INIBA ANG LOGIC DITO) ---
    const navLinks = document.querySelectorAll('.sidebar__menu .menu__link');
    const sections = document.querySelectorAll('.content__section');
    
    // KINAKAILANGAN: Kunin ang Logout elements
    const logoutLink = document.querySelector('.sidebar__menu .menu__link[data-content="logout"]');
    const logoutModal = document.getElementById('logoutModal');
    const cancelLogoutBtn = document.getElementById('cancelLogout');
    const confirmLogoutBtn = document.getElementById('confirmLogout');


    function activateSection(contentId) {
        sections.forEach(section => {
            section.classList.remove('active');
        });
        const targetSection = document.getElementById(contentId);
        if (targetSection) {
            targetSection.classList.add('active');
        }

        // Deactivate all links
        navLinks.forEach(nav => nav.classList.remove('active'));
        
        // Activate the target link
        const targetLink = document.querySelector(`.sidebar__menu .menu__link[data-content="${contentId}"]`);
        if (targetLink) {
            targetLink.classList.add('active');
            
            // Check if the link is inside a dropdown (to activate the parent link as well)
            const parentDropdown = targetLink.closest('.dropdown__menu');
            if (parentDropdown) {
                 const parentLink = parentDropdown.previousElementSibling;
                 if (parentLink && parentLink.classList.contains('menu__link')) {
                    parentLink.classList.add('active'); 
                 }
            }
        }
    }
    
    // FIX: LOGIC PARA IPAKITA ANG LOGOUT MODAL
    if (logoutLink && logoutModal) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault(); // PIGILAN ANG PAG-REDIRECT
            // CHANGE: Use classList.add('show')
            logoutModal.classList.add('show'); 
        });
    }

    // LOGIC PARA I-CLOSE ANG MODAL (Cancel)
    if (cancelLogoutBtn && logoutModal) {
        cancelLogoutBtn.addEventListener('click', function() {
            // CHANGE: Use classList.remove('show')
            logoutModal.classList.remove('show');
        });
    }

    // LOGIC PARA SA CONFIRM LOGOUT (i-reredirect sa href ng link)
    if (confirmLogoutBtn && logoutLink) {
        confirmLogoutBtn.addEventListener('click', function() {
            window.location.href = logoutLink.href; // I-redirect sa ?content=logout
        });
    }

    // UPDATED Navigation Logic: INALIS ANG history.pushState() DITO
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const contentId = this.getAttribute('data-content');
            
            // Huwag i-preventDefault o i-handle ang 'user-management-parent/appointment-management-parent' dito.
            if (contentId === 'user-management-parent' || contentId === 'appointment-management-parent') {
                return; 
            }
            
            // Huwag i-handle ang 'logout' dito dahil i-hi-handle na ng bagong logic sa itaas.
            if (contentId === 'logout') {
                return;
            }
            
            // Kung may contentId (i.e., 'dashboard', 'manage-clients', 'reports'), i-a-activate ang section.
            // PERO HINDI NA TAYO MAG-U-UPDATE NG URL GAMIT ANG history.pushState.
            if (contentId) {
                e.preventDefault();
                activateSection(contentId); 
                // TANGGAL: history.pushState(null, '', `?content=${contentId}`); 
            }
        });
    });

    const urlParams = new URLSearchParams(window.location.search);
    // Dito, hahanapin pa rin ang content parameter, pero ang default ay 'manage-clients'
    const initialContent = urlParams.get('content') || 'manage-clients'; 
    
    activateSection(initialContent);
    
    // TANGGALIN ANG LAHAT NG CHART LOGIC
    
// --- CLIENT TABLE SEARCH AND FILTER LOGIC (UPDATED to include Status and Bookings Filter) ---
const clientSearchInput = document.getElementById('clientSearch');
const clientStatusFilter = document.getElementById('clientStatusFilter');
const clientBookingFilter = document.getElementById('clientBookingFilter');

const clientTableBody = document.querySelector('.client-table tbody');
// Exclude the new #noResultsRow from the initial clientRows list
const clientRows = clientTableBody ? clientTableBody.querySelectorAll('tr:not(#noResultsRow)') : [];
const noResultsRow = document.getElementById('noResultsRow');
const searchTermDisplay = document.getElementById('searchTermDisplay');

// Central function para mag-handle ng lahat ng filtering at searching
function filterClientTable() {
    const searchTerm = clientSearchInput.value.toLowerCase().trim();
    const statusFilter = clientStatusFilter.value.toLowerCase(); // 'active', 'inactive', or ''
    const bookingFilter = clientBookingFilter.value; // '>0', '0', or ''
    let resultsFound = 0;

    clientRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        
        // Data na kailangan sa filtering
        const rowId = cells[0] ? cells[0].textContent.toLowerCase() : '';
        const rowFirstName = cells[1] ? cells[1].textContent.toLowerCase() : '';
        const rowLastName = cells[2] ? cells[2].textContent.toLowerCase() : '';
        const rowEmail = cells[4] ? cells[4].textContent.toLowerCase() : '';
        const rowContact = cells[5] ? cells[5].textContent.toLowerCase() : '';
        const rowBookings = cells[6] ? parseInt(cells[6].textContent.replace(/,/g, '')) : 0; // Kukunin ang number at tatanggalin ang comma
        const rowStatus = cells[7] ? cells[7].textContent.toLowerCase() : '';


        // 1. Search Criteria Check
        // Kukunin ang text content ng cells na kailangan i-search (Index 0 hanggang 5)
        const isSearchMatch = (
            rowId.includes(searchTerm) ||
            rowFirstName.includes(searchTerm) ||
            rowLastName.includes(searchTerm) ||
            rowEmail.includes(searchTerm) ||
            rowContact.includes(searchTerm)
        );
        
        
        // 2. Status Filter Check
        let isStatusMatch = true;
        if (statusFilter !== '' && rowStatus !== statusFilter) {
            isStatusMatch = false;
        }

        // 3. Bookings Filter Check
        let isBookingMatch = true;
        if (bookingFilter === '>0') {
            if (rowBookings <= 0) {
                isBookingMatch = false;
            }
        } else if (bookingFilter === '0') {
            if (rowBookings > 0) {
                isBookingMatch = false;
            }
        }
        
        // Final Decision: Dapat mag-match sa lahat ng criteria
        if (isSearchMatch && isStatusMatch && isBookingMatch) {
            row.style.display = ''; // Ipakita ang row
            resultsFound++;
        } else {
            row.style.display = 'none'; // Itago ang row
        }
    });
    
    // Logic para sa No Results Message
    const totalActiveFilters = searchTerm !== "" || statusFilter !== "" || bookingFilter !== "";
    
    if (resultsFound === 0 && totalActiveFilters) {
        // Ipinapakita ang search term kung may search input, kung wala naman, ang 'the applied filters'
        searchTermDisplay.textContent = searchTerm === "" ? "the applied filters" : searchTerm;
        noResultsRow.style.display = '';
    } else {
        noResultsRow.style.display = 'none';
    }
}

if (clientSearchInput) {
    // Tawagin ang filter function kapag nag-type sa search box
    clientSearchInput.addEventListener('keyup', filterClientTable);
}

if (clientStatusFilter) {
    // Tawagin ang filter function kapag nag-change ang status filter
    clientStatusFilter.addEventListener('change', filterClientTable);
}

if (clientBookingFilter) {
    // Tawagin ang filter function kapag nag-change ang booking filter
    clientBookingFilter.addEventListener('change', filterClientTable);
}

// Para mag-run ang filter sa initial load kung may existing filters o search (optional, pero good practice)
// filterClientTable();

// --- END CLIENT TABLE SEARCH AND FILTER LOGIC ---


// --- BAGONG LOGIC: EDIT CLIENT MODAL (UPDATED: Title, Removed Password, Removed Auto-focus) ---

const clientSaveSuccessModal = document.getElementById('clientSaveSuccessModal');
const confirmClientSaveBtn = document.getElementById('confirmClientSave');


// 1. Function para buksan ang modal at i-populate ang data
function openEditClientModal(button) {
    // Kuhanin ang data mula sa button (gamit ang data- attributes)
    const id = button.getAttribute('data-client-id');
    const firstName = button.getAttribute('data-first-name');
    const lastName = button.getAttribute('data-last-name');
    const email = button.getAttribute('data-email');
    const contact = button.getAttribute('data-contact');
    const birthdate = button.getAttribute('data-birthdate'); // Format: YYYY-MM-DD

    // I-populate ang modal fields
    document.getElementById('editClientId').value = id;
    document.getElementById('editFirstName').value = firstName;
    document.getElementById('editLastName').value = lastName;
    document.getElementById('editEmail').value = email;
    
    // LOGIC: I-populate ang Contact Number
    const contactInput = document.getElementById('editContact');
    const prefix = contactInput.getAttribute('data-prefix');

    // Tiyakin na ang value ay nagsisimula sa prefix, kung hindi ay ilagay ang prefix.
    let contactValue = contact || prefix;
    if (!contactValue.startsWith(prefix)) {
        contactValue = prefix;
    }
    
    // Tiyakin na ang value ay hindi lalampas sa 13 characters (+971 at 9 digits)
    contactInput.value = contactValue.substring(0, 13);
    
    document.getElementById('editBirthdate').value = birthdate;
    
    // Ipakita ang modal
    document.getElementById('editClientModal').classList.add('show');

    // BINAGO: INALIS ANG AUTOMATIC FOCUSING (Timeout Block)
}

// 2. Logic para isara ang modal gamit ang close/cancel button
document.querySelectorAll('.close-modal-btn').forEach(button => {
    button.addEventListener('click', function() {
        const modalId = this.getAttribute('data-modal') || 'editClientModal';
        document.getElementById(modalId).classList.remove('show');
    });
});

// 3. Simple Form Submission/Save Logic (Palitan ng AJAX call sa totoong system)
document.getElementById('editClientForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Dito dapat mag-validate na ang contact number ay +971 at 9 digits (13 chars total)
    const contactInput = document.getElementById('editContact');
    const contactValue = contactInput.value.trim();
    const prefix = contactInput.getAttribute('data-prefix');

    if (contactValue.length !== 13 || !contactValue.startsWith(prefix)) {
        alert("Please ensure the contact number is +971 followed by exactly 9 digits.");
        contactInput.focus();
        return; // Pigilan ang submission
    }
    
    // 1. Isara ang Edit Modal
    document.getElementById('editClientModal').classList.remove('show');

    // 2. Ipakita ang Success Modal
    clientSaveSuccessModal.classList.add('show');
});

// 4. LOGIC: Isara ang modal kapag kinlik ang labas (background)
document.getElementById('editClientModal').addEventListener('click', function(e) {
    // Tiyakin na ang na-click ay ang mismong modal background at hindi ang content
    if (e.target.id === 'editClientModal') {
        e.target.classList.remove('show');
    }
});

// 5. LOGIC: Isara ang Success Modal kapag kinlik ang OK
if (confirmClientSaveBtn) {
    confirmClientSaveBtn.addEventListener('click', function() {
        clientSaveSuccessModal.classList.remove('show');
    });
}

// --- BAGONG LOGIC PARA SA CONTACT NUMBER INPUT RESTRICTIONS ---

const editContactInput = document.getElementById('editContact');

if(editContactInput) {
    const prefix = editContactInput.getAttribute('data-prefix'); 
    const prefixLength = prefix.length; // 4

    editContactInput.addEventListener('keydown', function(e) {
        const start = this.selectionStart;
        const end = this.selectionEnd;
        
        // Check for backspace or delete
        if (e.key === 'Backspace' || e.key === 'Delete') {
            // Kapag nasa loob ng prefix ang cursor at nag-backspace/delete, pigilan
            if (start < prefixLength || end < prefixLength) {
                e.preventDefault();
                // Kung nasa gitna ng prefix, ilipat ang cursor sa dulo ng prefix (para 'di ma-delete ang +971)
                if (start <= prefixLength) {
                    this.setSelectionRange(prefixLength, prefixLength);
                }
                return;
            }
        }

        // Pigilan ang pag-paste kung ang cursor ay nasa loob ng prefix
        if ((e.ctrlKey || e.metaKey) && e.key === 'v' && start < prefixLength) {
            e.preventDefault();
            return;
        }
    });

    editContactInput.addEventListener('input', function(e) {
        let value = this.value;
        const currentCursorPosition = this.selectionStart;
        
        // 1. I-ensure na laging may prefix
        if (!value.startsWith(prefix)) {
            // Kung na-delete ang prefix, ibalik ito at ilipat ang cursor sa tamang position
            if (currentCursorPosition < prefixLength) {
                value = prefix + value.substring(currentCursorPosition); 
                // I-adjust ang cursor position
                this.value = value;
                this.setSelectionRange(prefixLength, prefixLength);
                return;
            }
        }
        
        // 2. Tanggalin ang lahat ng non-digit character pagkatapos ng prefix
        let numberPart = value.substring(prefixLength);
        // Tanggalin ang lahat ng symbol, letter, at space sa number part
        const sanitizedNumberPart = numberPart.replace(/[^0-9]/g, ''); 
        
        // 3. I-enforce ang maximum 9 digits (13 total characters)
        const finalNumberPart = sanitizedNumberPart.substring(0, 9);
        
        this.value = prefix + finalNumberPart;
        
        // 4. I-correct ang cursor position para hindi ma-stuck sa prefix
        let newCursorPosition = currentCursorPosition;
        
        if (newCursorPosition < prefixLength) {
            // Kapag nag-type sa loob ng prefix area, ilipat sa dulo ng prefix
            newCursorPosition = prefixLength;
        } else if (newCursorPosition > this.value.length) {
            // Kapag nag-type/paste na lumampas sa max length, ilipat sa dulo
            newCursorPosition = this.value.length;
        }

        // Setahan ng max length (13) sa attribute
        this.setAttribute('maxlength', 13);

        // Final cursor position setting
        this.setSelectionRange(newCursorPosition, newCursorPosition);

    });
}

// --- END BAGONG LOGIC PARA SA CONTACT NUMBER INPUT RESTRICTIONS ---

// --- END BAGONG LOGIC: EDIT CLIENT MODAL ---

// --- ARCHIVE AND RESTORE CLIENT MODAL LOGIC (UPDATED: Added Restore Logic) ---

const openArchivesModalBtn = document.getElementById('openArchivesModal');
const archivedClientsModal = document.getElementById('archivedClientsModal');

// Archive Elements
const archiveConfirmModal = document.getElementById('archiveConfirmModal');
const archiveSuccessModal = document.getElementById('archiveSuccessModal');
const confirmArchiveBtn = document.getElementById('confirmArchive');
const cancelArchiveBtn = document.getElementById('cancelArchive');
const confirmArchiveSuccessBtn = document.getElementById('confirmArchiveSuccess');

// Restore Elements (NEW)
const restoreConfirmModal = document.getElementById('restoreConfirmModal');
const restoreSuccessModal = document.getElementById('restoreSuccessModal');
const confirmRestoreBtn = document.getElementById('confirmRestore');
const cancelRestoreBtn = document.getElementById('cancelRestore');
const confirmRestoreSuccessBtn = document.getElementById('confirmRestoreSuccess');

let clientToArchiveId = null; // Variable para i-store ang ID ng client na a-a-archive
let clientToRestoreId = null; // Variable para i-store ang ID ng client na i-re-restore

if (openArchivesModalBtn) {
    openArchivesModalBtn.addEventListener('click', function() {
        archivedClientsModal.classList.add('show');
    });
}

// Logic para isara ang archived modal kapag kinlik ang X o background
if (archivedClientsModal) {
    archivedClientsModal.addEventListener('click', function(e) {
        // Tiyakin na ang na-click ay ang mismong modal background o ang close button
        if (e.target.id === 'archivedClientsModal' || e.target.closest('.close-modal-btn')) {
            archivedClientsModal.classList.remove('show');
        }
    });
}

// *** 1. Archive Client Functionality ***
function archiveClient(clientId) {
    clientToArchiveId = clientId;
    
    // Ipakita ang Client ID sa confirmation modal
    document.getElementById('archiveClientIdDisplay').textContent = clientId;
    
    // Ipakita ang Confirmation Modal
    archiveConfirmModal.classList.add('show');
}

// 1A. Confirm Archive Button Click
confirmArchiveBtn.addEventListener('click', function() {
    archiveConfirmModal.classList.remove('show');
    
    // *** PLACEHOLDER FOR ARCHIVE AJAX CALL DITO ***
    console.log('Archiving client ID: ' + clientToArchiveId);
    
    // Sa totoong system, after successful AJAX call, gawin ito:
    
    // Ipakita ang Success Modal
    document.getElementById('archiveSuccessIdDisplay').textContent = clientToArchiveId;
    archiveSuccessModal.classList.add('show');
    
    // Optional: I-hide/i-remove ang row mula sa active table 
    // Example: removeClientRowFromTable(clientToArchiveId);
});

// 1B. Cancel Archive Button Click
cancelArchiveBtn.addEventListener('click', function() {
    archiveConfirmModal.classList.remove('show');
    clientToArchiveId = null; 
});

// 1C. Close Archive Success Modal
confirmArchiveSuccessBtn.addEventListener('click', function() {
    archiveSuccessModal.classList.remove('show');
    clientToArchiveId = null; 
    
    // Optional: I-reload ang page o i-update ang table
    // window.location.reload(); 
});


// *** 2. Restore Client Functionality (NEW) ***

window.restoreClient = function(clientId) {
    clientToRestoreId = clientId;
    
    // Ipakita ang Client ID sa confirmation modal
    document.getElementById('restoreClientIdDisplay').textContent = clientId;
    
    // Ipakita ang Restoration Confirmation Modal
    restoreConfirmModal.classList.add('show');
}

// 2A. Confirm Restore Button Click
if (confirmRestoreBtn) {
    confirmRestoreBtn.addEventListener('click', function() {
        restoreConfirmModal.classList.remove('show');
        
        // *** PLACEHOLDER FOR RESTORE AJAX CALL DITO ***
        console.log('Restoring client ID: ' + clientToRestoreId);
        
        // Sa totoong system, after successful AJAX call, gawin ito:
        
        // Ipakita ang Success Modal
        document.getElementById('restoreSuccessIdDisplay').textContent = clientToRestoreId;
        restoreSuccessModal.classList.add('show');
        
        // Optional: I-hide/i-remove ang row mula sa archive table 
        // Example: removeClientRowFromArchiveTable(clientToRestoreId);
    });
}

// 2B. Cancel Restore Button Click
if (cancelRestoreBtn) {
    cancelRestoreBtn.addEventListener('click', function() {
        restoreConfirmModal.classList.remove('show');
        clientToRestoreId = null; 
    });
}

// 2C. Close Restore Success Modal
if (confirmRestoreSuccessBtn) {
    confirmRestoreSuccessBtn.addEventListener('click', function() {
        restoreSuccessModal.classList.remove('show');
        clientToRestoreId = null; 
        
        // Optional: I-reload ang page o i-update ang table
        // window.location.reload(); 
    });
}

// Para rin maisara ang lahat ng confirmation/success modals kapag kinlik ang labas (background)
[archiveConfirmModal, archiveSuccessModal, restoreConfirmModal, restoreSuccessModal].forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    });
});
// --- END ARCHIVE AND RESTORE CLIENT MODAL LOGIC ---