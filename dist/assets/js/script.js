/**
 * CampusKart - Frontend Interactions and Dynamic Controls
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Toast Notifications Auto-dismiss
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    });

    // 2. Search Modal Controls
    const searchTrigger = document.getElementById('searchTrigger');
    const searchModal = document.getElementById('searchModal');
    const searchClose = document.getElementById('searchClose');
    const searchInput = document.getElementById('searchInput');

    if (searchTrigger && searchModal) {
        searchTrigger.addEventListener('click', (e) => {
            e.preventDefault();
            searchModal.classList.add('active');
            if (searchInput) searchInput.focus();
        });

        if (searchClose) {
            searchClose.addEventListener('click', () => {
                searchModal.classList.remove('active');
            });
        }

        searchModal.addEventListener('click', (e) => {
            if (e.target === searchModal) {
                searchModal.classList.remove('active');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && searchModal.classList.contains('active')) {
                searchModal.classList.remove('active');
            }
        });
    }

    // 3. Mobile Navigation Drawer
    const mobileToggle = document.getElementById('mobileToggle');
    const mobileNav = document.getElementById('mobileNav');

    if (mobileToggle && mobileNav) {
        mobileToggle.addEventListener('click', () => {
            mobileNav.classList.toggle('active');
        });
    }

    // 4. Quantity Adjuster (+ and - controls)
    document.querySelectorAll('.qty-control').forEach(control => {
        const input = control.querySelector('.qty-input');
        const decBtn = control.querySelector('.qty-dec');
        const incBtn = control.querySelector('.qty-inc');

        if (!input) return;

        const min = parseInt(input.getAttribute('min') || '1', 10);
        const max = parseInt(input.getAttribute('max') || '99', 10);

        if (decBtn) {
            decBtn.addEventListener('click', (e) => {
                e.preventDefault();
                let current = parseInt(input.value || '1', 10);
                if (current > min) {
                    input.value = current - 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }

        if (incBtn) {
            incBtn.addEventListener('click', (e) => {
                e.preventDefault();
                let current = parseInt(input.value || '1', 10);
                if (current < max) {
                    input.value = current + 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }

        input.addEventListener('change', () => {
            let val = parseInt(input.value, 10);
            if (isNaN(val) || val < min) val = min;
            if (val > max) val = max;
            input.value = val;
        });
    });

    // 5. Confirmation for Destructive Actions
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            const message = el.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});
