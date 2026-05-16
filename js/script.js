/**
 * AeroBook – Main JavaScript
 */

// Navbar scroll effect
window.addEventListener('scroll', function () {
    const navbar = document.getElementById('mainNavbar');
    if (navbar) {
        navbar.classList.toggle('scrolled', window.scrollY > 50);
    }
});

// Form validation helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    let valid = true;
    form.querySelectorAll('[required]').forEach(function (input) {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            valid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    return valid;
}

// Email validation
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// Phone validation (10 digits)
function isValidPhone(phone) {
    return /^[6-9]\d{9}$/.test(phone);
}

// Registration form validation
document.addEventListener('DOMContentLoaded', function () {
    const regForm = document.getElementById('registerForm');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            let valid = true;
            const name = document.getElementById('name');
            const email = document.getElementById('email');
            const phone = document.getElementById('phone');
            const password = document.getElementById('password');
            const confirmPass = document.getElementById('confirm_password');

            if (name && name.value.trim().length < 3) {
                name.classList.add('is-invalid');
                valid = false;
            }
            if (email && !isValidEmail(email.value)) {
                email.classList.add('is-invalid');
                valid = false;
            }
            if (phone && !isValidPhone(phone.value)) {
                phone.classList.add('is-invalid');
                valid = false;
            }
            if (password && password.value.length < 6) {
                password.classList.add('is-invalid');
                valid = false;
            }
            if (confirmPass && password && confirmPass.value !== password.value) {
                confirmPass.classList.add('is-invalid');
                valid = false;
            }
            if (!valid) e.preventDefault();
        });
    }

    // Booking form validation
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function (e) {
            let valid = true;
            const passengerName = document.getElementById('passenger_name');
            const age = document.getElementById('age');

            if (passengerName && passengerName.value.trim().length < 3) {
                passengerName.classList.add('is-invalid');
                valid = false;
            }
            if (age && (parseInt(age.value) < 1 || parseInt(age.value) > 120)) {
                age.classList.add('is-invalid');
                valid = false;
            }
            if (!valid) e.preventDefault();
        });
    }

    // Flight Search Source and Destination Logic
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

    // Remove invalid class on input
    document.querySelectorAll('.form-control, .form-select').forEach(function (el) {
        el.addEventListener('input', function () {
            this.classList.remove('is-invalid');
        });
    });

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // Fade-in animation on scroll
    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.feature-card, .stat-card, .flight-card').forEach(function (el) {
        observer.observe(el);
    });
});
