/**
 * Serene Shores Coastal Villa - Front-End Interactions & Calculations
 * Academic Project: IT1208 – Web Technologies (Continuous Assessment 2 – 2026)
 * Tech Stack: Vanilla JavaScript (ES6+)
 */

document.addEventListener('DOMContentLoaded', () => {
    // ------------------------------------------------------------------------
    // 1. Mobile Navigation Menu Toggle
    // ------------------------------------------------------------------------
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('open');
            const expanded = navMenu.classList.contains('open');
            navToggle.setAttribute('aria-expanded', expanded);
            navToggle.innerHTML = expanded ? '&#10005;' : '&#9776;'; // Switch between X and Hamburger
        });
    }

    // ------------------------------------------------------------------------
    // 2. Date Input Setup (Set Min Date to Today)
    // ------------------------------------------------------------------------
    const today = new Date().toISOString().split('T')[0];
    const checkInInputs = document.querySelectorAll('input[type="date"][name="check_in"]');
    const checkOutInputs = document.querySelectorAll('input[type="date"][name="check_out"]');

    checkInInputs.forEach(input => {
        if (!input.getAttribute('min')) {
            input.setAttribute('min', today);
        }
    });

    checkOutInputs.forEach(input => {
        if (!input.getAttribute('min')) {
            input.setAttribute('min', today);
        }
    });

    // ------------------------------------------------------------------------
    // 3. Dynamic Room Booking Calculator (book.php)
    // ------------------------------------------------------------------------
    const bookingForm = document.getElementById('bookingForm');
    const bookCheckIn = document.getElementById('bookCheckIn');
    const bookCheckOut = document.getElementById('bookCheckOut');
    const numNightsSpan = document.getElementById('numNights');
    const totalCostSpan = document.getElementById('totalCostDisplay');
    const totalAmountInput = document.getElementById('total_amount_input');
    const pricePerNightInput = document.getElementById('price_per_night_input');

    if (bookingForm && bookCheckIn && bookCheckOut && pricePerNightInput) {
        const pricePerNight = parseFloat(pricePerNightInput.value) || 0;

        function updateBookingCalculation() {
            const checkInVal = bookCheckIn.value;
            const checkOutVal = bookCheckOut.value;

            if (!checkInVal || !checkOutVal) {
                if (numNightsSpan) numNightsSpan.textContent = '0';
                if (totalCostSpan) totalCostSpan.textContent = 'LKR 0.00';
                if (totalAmountInput) totalAmountInput.value = '0';
                return;
            }

            const inDate = new Date(checkInVal);
            const outDate = new Date(checkOutVal);

            // Dynamically adjust check-out min date
            const nextDay = new Date(inDate);
            nextDay.setDate(nextDay.getDate() + 1);
            bookCheckOut.min = nextDay.toISOString().split('T')[0];

            const diffTime = outDate.getTime() - inDate.getTime();
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays > 0) {
                const total = diffDays * pricePerNight;
                if (numNightsSpan) numNightsSpan.textContent = diffDays.toString();
                if (totalCostSpan) totalCostSpan.textContent = 'LKR ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (totalAmountInput) totalAmountInput.value = total.toFixed(2);
            } else {
                if (numNightsSpan) numNightsSpan.textContent = '0 (Invalid Range)';
                if (totalCostSpan) totalCostSpan.textContent = 'LKR 0.00';
                if (totalAmountInput) totalAmountInput.value = '0';
            }
        }

        bookCheckIn.addEventListener('change', () => {
            if (bookCheckIn.value && bookCheckOut.value && bookCheckOut.value <= bookCheckIn.value) {
                const nextDay = new Date(bookCheckIn.value);
                nextDay.setDate(nextDay.getDate() + 1);
                bookCheckOut.value = nextDay.toISOString().split('T')[0];
            }
            updateBookingCalculation();
        });

        bookCheckOut.addEventListener('change', updateBookingCalculation);

        // Run initial calculation if pre-filled from search query
        updateBookingCalculation();

        // Form Submit Validation
        bookingForm.addEventListener('submit', (e) => {
            const inDate = new Date(bookCheckIn.value);
            const outDate = new Date(bookCheckOut.value);

            if (outDate <= inDate) {
                e.preventDefault();
                alert('Please select a valid check-out date that occurs after your check-in date.');
                bookCheckOut.focus();
                return false;
            }

            const guestsInput = document.getElementById('guests_count');
            const maxGuests = parseInt(guestsInput?.getAttribute('max') || '10', 10);
            const chosenGuests = parseInt(guestsInput?.value || '1', 10);

            if (chosenGuests > maxGuests) {
                e.preventDefault();
                alert(`The selected room accommodates a maximum of ${maxGuests} guests.`);
                guestsInput.focus();
                return false;
            }
        });
    }

    // ------------------------------------------------------------------------
    // 4. Client-Side Password Confirmation Match (register.php)
    // ------------------------------------------------------------------------
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            const password = document.getElementById('password')?.value;
            const confirmPassword = document.getElementById('confirm_password')?.value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please re-enter.');
                document.getElementById('confirm_password')?.focus();
            }
        });
    }

    // ------------------------------------------------------------------------
    // 5. Booking Cancellation Confirmation Prompt
    // ------------------------------------------------------------------------
    const cancelForms = document.querySelectorAll('.cancel-booking-form');
    cancelForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const confirmed = confirm('Are you sure you want to cancel this booking reservation? This action cannot be undone.');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    });

    // ------------------------------------------------------------------------
    // 6. Flash Alert Auto-Dismiss
    // ------------------------------------------------------------------------
    setTimeout(() => {
        const dismissibleAlerts = document.querySelectorAll('.alert-dismissible');
        dismissibleAlerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 6000);
});
