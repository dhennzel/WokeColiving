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

    // --- Profile Dropdown Logic ---
    const profileToggle = document.getElementById('profileToggle');
    const profileMenu = document.getElementById('profileMenu');
    
    if (profileToggle && profileMenu) {
        profileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            profileMenu.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!profileToggle.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.classList.remove('show');
            }
        });
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