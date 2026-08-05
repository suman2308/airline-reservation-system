/**
 * AeroBook – Main JavaScript
 * v1.1.1 — Dark-only theme (pinned in <head>), loading states, accessible validation
 */

// ─── Theme ───
// Dark mode is the only theme. The <html> data-theme="dark" attribute is pinned
// by an inline script in the header before CSS paints, so nothing is needed here.

window.addEventListener('scroll', function () {
    const navbar = document.getElementById('mainNavbar');
    if (navbar) {
        navbar.classList.toggle('scrolled', window.scrollY > 50);
    }
});

// ─── Form validation helpers ───
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return /^[6-9]\d{9}$/.test(phone);
}

function showFieldError(field, message) {
    if (!field) return;
    field.classList.add('is-invalid');
    const feedback = field.parentElement.querySelector('.invalid-feedback') || field.parentElement.querySelector('.error-msg');
    if (feedback) {
        feedback.textContent = message;
        feedback.setAttribute('aria-live', 'polite');
    }
    field.setAttribute('aria-invalid', 'true');
    field.setAttribute('aria-describedby', feedback ? feedback.id || '' : '');
}

function clearFieldError(field) {
    if (!field) return;
    field.classList.remove('is-invalid');
    field.removeAttribute('aria-invalid');
}

// ─── Loading spinner overlay ───
function showLoading() {
    var overlay = document.getElementById('loadingOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'spinner-overlay';
        overlay.innerHTML = '<div class="spinner"></div>';
        overlay.setAttribute('role', 'status');
        overlay.setAttribute('aria-label', 'Loading');
        document.body.appendChild(overlay);
    }
    overlay.classList.add('active');
}

function hideLoading() {
    var overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function () {
    // ─── Registration form validation ───
    const regForm = document.getElementById('registerForm');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            let valid = true;
            const name = document.getElementById('name');
            const email = document.getElementById('email');
            const phone = document.getElementById('phone');
            const password = document.getElementById('password');
            const confirmPass = document.getElementById('confirm_password');

            [name, email, phone, password, confirmPass].forEach(clearFieldError);

            if (name && name.value.trim().length < 3) { showFieldError(name, 'Name must be at least 3 characters.'); valid = false; }
            if (email && !isValidEmail(email.value)) { showFieldError(email, 'Please enter a valid email address.'); valid = false; }
            if (phone && !isValidPhone(phone.value)) { showFieldError(phone, 'Please enter a valid 10-digit Indian phone number.'); valid = false; }
            if (password && password.value.length < 6) { showFieldError(password, 'Password must be at least 6 characters.'); valid = false; }
            if (confirmPass && password && confirmPass.value !== password.value) { showFieldError(confirmPass, 'Passwords do not match.'); valid = false; }

            if (valid) showLoading();
            if (!valid) e.preventDefault();
        });
    }

    // ─── Login form validation ───
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            let valid = true;
            const email = document.getElementById('loginEmail');
            const password = document.getElementById('loginPassword');

            [email, password].forEach(clearFieldError);

            if (email && !isValidEmail(email.value)) { showFieldError(email, 'Please enter a valid email address.'); valid = false; }
            if (password && password.value.length < 6) { showFieldError(password, 'Password must be at least 6 characters.'); valid = false; }

            if (valid) showLoading();
            if (!valid) e.preventDefault();
        });
    }

    // ─── General form loading spinner (for forms with .needs-loading) ───
    document.querySelectorAll('form.needs-loading').forEach(function (form) {
        form.addEventListener('submit', function () {
            // Quick validation check: if any required input is empty, don't show spinner
            var hasEmptyRequired = false;
            form.querySelectorAll('[required]').forEach(function (el) {
                if (!el.value || el.value.trim() === '') hasEmptyRequired = true;
            });
            if (!hasEmptyRequired) showLoading();
        });
    });

    // ─── Flight Search Source ↔ Destination logic ───
    const sourceSelects = document.querySelectorAll('select[name="source"]');
    const destSelects = document.querySelectorAll('select[name="destination"]');
    sourceSelects.forEach((sourceSelect, index) => {
        const destSelect = destSelects[index];
        if (destSelect) {
            sourceSelect.addEventListener('change', function() {
                const selectedSource = this.value;
                Array.from(destSelect.options).forEach(opt => {
                    if (opt.value && opt.value === selectedSource) {
                        opt.disabled = true;
                        if (destSelect.value === selectedSource) {
                            destSelect.value = '';
                        }
                    } else {
                        opt.disabled = false;
                    }
                });
            });
        }
    });

    // ─── Remove invalid class on input ───
    document.querySelectorAll('.form-control, .form-select').forEach(function (el) {
        el.addEventListener('input', function () {
            this.classList.remove('is-invalid');
            this.removeAttribute('aria-invalid');
        });
    });

    // ─── Auto-dismiss alerts after 5 seconds ───
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            try {
                var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            } catch (e) {}
        }, 5000);
    });

    // ─── Fade-in animation on scroll ───
    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.feature-card, .stat-card, .flight-card, .fare-compare-card').forEach(function (el) {
        observer.observe(el);
    });

    // ─── Enable lazy loading for images ───
    document.querySelectorAll('img:not([loading])').forEach(function (img) {
        if (img.dataset.src) {
            img.loading = 'lazy';
        }
    });

    // ─── Keyboard: Escape dismisses active alerts ───
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.alert-dismissible.show').forEach(function (alert) {
                try {
                    var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                } catch (err) {}
            });
        }
    });
});
