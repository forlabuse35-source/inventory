/**
 * Inventory Management System - Core JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {

    // ---- Sidebar Toggle (Mobile) ----
    const sidebar      = document.getElementById('sidebar');
    const menuToggle   = document.getElementById('menuToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const overlay      = document.getElementById('sidebarOverlay');

    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.add('open');
            overlay.classList.add('active');
        });
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    }

    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // ---- User Dropdown ----
    const headerUserBtn = document.getElementById('headerUserBtn');
    const userDropdown  = document.getElementById('userDropdown');

    if (headerUserBtn && userDropdown) {
        headerUserBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });

        document.addEventListener('click', () => {
            userDropdown.classList.remove('show');
        });
    }

    // ---- Auto-dismiss Alerts ----
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // ---- Modal Utilities ----
    window.openModal = function(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.add('show');
    };

    window.closeModal = function(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('show');
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
                form.querySelectorAll('.form-error').forEach(e => e.classList.remove('show'));
                form.querySelectorAll('.is-invalid').forEach(e => e.classList.remove('is-invalid'));
            }
        }
    };

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('show');
            }
        });
    });

    // ---- Confirm Dialog ----
    window.confirmAction = function(message, callback) {
        const overlay = document.getElementById('confirmOverlay');
        const msgEl   = document.getElementById('confirmMessage');
        const yesBtn  = document.getElementById('confirmYes');

        if (!overlay) return callback();

        msgEl.textContent = message;
        overlay.classList.add('show');

        const newYes = yesBtn.cloneNode(true);
        yesBtn.parentNode.replaceChild(newYes, yesBtn);

        newYes.addEventListener('click', () => {
            overlay.classList.remove('show');
            callback();
        });
    };

    window.closeConfirm = function() {
        const overlay = document.getElementById('confirmOverlay');
        if (overlay) overlay.classList.remove('show');
    };

    // ---- Form Validation Utility ----
    window.validateField = function(input, rules) {
        const value   = input.value.trim();
        const errorEl = input.parentElement.querySelector('.form-error');
        let message   = '';

        if (rules.required && value === '') {
            message = rules.requiredMsg || 'This field is required.';
        } else if (rules.minLength && value.length < rules.minLength) {
            message = `Minimum ${rules.minLength} characters required.`;
        } else if (rules.pattern && !rules.pattern.test(value)) {
            message = rules.patternMsg || 'Invalid format.';
        } else if (rules.min !== undefined && parseFloat(value) < rules.min) {
            message = `Minimum value is ${rules.min}.`;
        }

        if (message) {
            input.classList.add('is-invalid');
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.add('show');
            }
            return false;
        } else {
            input.classList.remove('is-invalid');
            if (errorEl) errorEl.classList.remove('show');
            return true;
        }
    };

    // Clear validation on input
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('input', () => {
            input.classList.remove('is-invalid');
            const errorEl = input.parentElement.querySelector('.form-error');
            if (errorEl) errorEl.classList.remove('show');
        });
    });

    // ---- Table Search ----
    window.initTableSearch = function(inputId, tableId) {
        const input = document.getElementById(inputId);
        const table = document.getElementById(tableId);
        if (!input || !table) return;

        input.addEventListener('input', () => {
            const filter = input.value.toLowerCase();
            const rows   = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    };
});
