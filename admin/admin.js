/**
 * admin.js
 * Interactive logic for Woke Coliving Admin Dashboard
 */

document.addEventListener('DOMContentLoaded', () => {
    
    // --- Sidebar Toggle Logic ---
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    }

    // Open sidebar when clicking any nav item while collapsed
    if (sidebar) {
        const navItems = sidebar.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.addEventListener('click', () => {
                if (sidebar.classList.contains('collapsed')) {
                    sidebar.classList.remove('collapsed');
                }
            });
        });
    }

    // --- Top Navbar Hide/Show Logic ---
    const topNavbar = document.querySelector('.top-navbar');
    const restoreTrigger = document.getElementById('navbar-restore-trigger');

    if (topNavbar && restoreTrigger) {
        // Double click to hide
        topNavbar.addEventListener('dblclick', (e) => {
            // Don't trigger if clicking inside an interactive element
            if (e.target.closest('button') || e.target.closest('input') || e.target.closest('a') || e.target.closest('.profile-dropdown')) {
                return;
            }
            
            // Clear text selection in case user highlighted text accidentally
            if (window.getSelection) {
                window.getSelection().removeAllRanges();
            }

            topNavbar.classList.add('hidden-navbar');
            restoreTrigger.classList.add('show-trigger');
            localStorage.setItem('topNavbarHidden', 'true');
        });

        // Click pull-tab to restore
        restoreTrigger.addEventListener('click', () => {
            topNavbar.classList.remove('hidden-navbar');
            restoreTrigger.classList.remove('show-trigger');
            localStorage.setItem('topNavbarHidden', 'false');
        });

        // Initialize state from local storage
        if (localStorage.getItem('topNavbarHidden') === 'true') {
            topNavbar.classList.add('hidden-navbar');
            restoreTrigger.classList.add('show-trigger');
        }
    }

    // --- Admin Night Mode Toggle ---
    const adminNightModeToggle = document.getElementById('adminNightModeToggle');
    const adminUser = window.currentAdminUser || 'admin';
    const nightModeKey = 'adminNightMode_' + adminUser;

    // Initialize state globally immediately on load so it works on every page
    if (localStorage.getItem(nightModeKey) === 'enabled') {
        document.body.classList.add('night-mode');
    }

    if (adminNightModeToggle) {
        adminNightModeToggle.addEventListener('click', (e) => {
            e.preventDefault();
            document.body.classList.add('theme-transition');
            document.body.classList.toggle('night-mode');
            const isNight = document.body.classList.contains('night-mode');
            localStorage.setItem(nightModeKey, isNight ? 'enabled' : 'disabled');
            
            // Toggle icon and text
            if (isNight) {
                adminNightModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
            } else {
                adminNightModeToggle.innerHTML = '<i class="fas fa-moon"></i> Night Mode';
            }
        });

        if (localStorage.getItem(nightModeKey) === 'enabled') {
            adminNightModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
        }
    }

    // --- Modal Handling Logic ---
    const actionModal = document.getElementById('actionModal');
    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelModalBtn = document.getElementById('cancelModalBtn');

    const openModal = () => {
        actionModal.classList.add('active');
    };

    const closeModal = () => {
        actionModal.classList.remove('active');
    };

    if (openModalBtn) openModalBtn.addEventListener('click', openModal);
    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if (cancelModalBtn) cancelModalBtn.addEventListener('click', closeModal);

    // Close modal on outside click
    window.addEventListener('click', (e) => {
        if (e.target === actionModal) {
            closeModal();
        }
    });

    // --- Simple Data Table Sorting Logic ---
    const table = document.getElementById('reservationsTable');
    if (table) {
        const headers = table.querySelectorAll('th[data-sort]');
        
        headers.forEach((header, index) => {
            header.addEventListener('click', () => {
                const type = header.getAttribute('data-sort');
                sortTable(table, index, type);
                
                // Toggle sort icons
                headers.forEach(h => {
                    if(h !== header) {
                        h.querySelector('i').className = 'fas fa-sort';
                        h.removeAttribute('data-order');
                    }
                });
                
                const icon = header.querySelector('i');
                const order = header.getAttribute('data-order') === 'asc' ? 'desc' : 'asc';
                header.setAttribute('data-order', order);
                
                if(order === 'asc') {
                    icon.className = 'fas fa-sort-up';
                } else {
                    icon.className = 'fas fa-sort-down';
                }
            });
        });
    }

    /**
     * Sorts table rows based on column index and data type
     * @param {HTMLTableElement} table 
     * @param {number} column 
     * @param {string} type 
     */
    function sortTable(table, column, type) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const header = table.querySelectorAll('th')[column];
        const isAscending = header.getAttribute('data-order') !== 'asc';

        rows.sort((a, b) => {
            let valA = a.querySelectorAll('td')[column].innerText.trim();
            let valB = b.querySelectorAll('td')[column].innerText.trim();

            if (type === 'date') {
                valA = new Date(valA).getTime();
                valB = new Date(valB).getTime();
            } else {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }

            if (valA < valB) return isAscending ? -1 : 1;
            if (valA > valB) return isAscending ? 1 : -1;
            return 0;
        });

        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
    }
});