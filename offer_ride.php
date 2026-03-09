<?php
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offer a Ride - ShareMyRide</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <?php include 'navbar.php'; ?>


    <div class="container" style="padding-top: 7rem; max-width: 800px;">
        
        <div class="text-center mb-4">
            <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-dark);">Publish a Ride</h1>
            <p style="color: var(--text-gray);">Cover your driving costs by sharing your empty seats.</p>
        </div>

        <div class="search-card" style="display: block; padding: clamp(1rem, 5vw, 2.5rem); border-top: 4px solid var(--primary-teal);">
            <form id="rideForm" onsubmit="handleRideSubmit(event)">
                <div style="display: flex; gap: 1.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label>From *</label>
                        <input type="text" id="offerFrom" class="form-input" placeholder="City or Campus" oninput="validateLocationLive('offerFrom', 'errorFrom', 'Starting location')" required>
                        <span id="errorFrom" class="error-message">Please enter a valid start location</span>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label>To *</label>
                        <input type="text" id="offerTo" class="form-input" placeholder="Destination" oninput="validateLocationLive('offerTo', 'errorTo', 'Destination')" required>
                        <span id="errorTo" class="error-message">Please enter a valid destination</span>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Intermediate Stops (Optional)</label>
                    <input type="text" id="offerStops" class="form-input" placeholder="e.g. Kanjirapally, Ponkunnam (Comma separated)" oninput="validateLocationLive('offerStops', 'errorStops', 'Intermediate stops')">
                    <span id="errorStops" class="error-message"></span>
                    <span style="font-size: 0.8rem; color: var(--text-gray);">Adding stops helps passengers find your ride easier.</span>
                </div>

                <div style="display: flex; gap: 1.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label>Date *</label>
                        <input type="date" id="offerDate" class="form-input" required>
                        <span id="errorDate" class="error-message">Please select a future date</span>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label>Time *</label>
                        <input type="time" id="offerTime" class="form-input" required>
                        <span id="errorTime" class="error-message">Please select a time</span>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label>Vehicle Type *</label>
                        <select id="vehicleType" class="form-input" required>
                            <option value="">Select</option>
                            <option value="car">Car</option>
                            <option value="bike">Bike</option>
                            <option value="suv">SUV</option>
                        </select>
                        <span id="errorVehicle" class="error-message">Please select vehicle type</span>
                    </div>
                </div>

                <div style="display: flex; gap: 1.5rem; margin-bottom: 2rem; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label>Empty Seats *</label>
                        <select id="offerSeats" class="form-input" required>
                            <option value="">Select</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                        <span id="errorSeats" class="error-message">Please select seat count</span>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label>Price per Seat (₹) *</label>
                        <input type="text" id="offerPrice" class="form-input" placeholder="e.g. 150" oninput="this.value = this.value.replace(/[^0-9.]/g, ''); if(this.value && parseFloat(this.value) > 0) clearError('offerPrice','errorPrice');" required>
                        <span id="errorPrice" class="error-message">Please enter a valid price</span>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label>Trip Details / Instructions</label>
                    <textarea id="offerDetails" class="form-input" rows="3" placeholder="Specific pickup point, luggage info, etc."></textarea>
                </div>

                <!-- Global message container for success/network errors -->
                <div id="rideFormMessage"></div>

                <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.1rem; padding: 1rem;">
                    <i class="fas fa-check-circle"></i> Publish Ride
                </button>

            </form>
        </div>

    </div>

    <script src="js/ride_manager.js"></script>
    <script>
        function toggleMobileMenu() {
            document.getElementById('navLinks').classList.toggle('show');
        }

        // Set minimum date to today (using local time)
        const today = new Date().toLocaleDateString('en-CA');
        document.getElementById('offerDate').min = today;

        // Helper to show inline error
        function showError(fieldId, errorId, message) {
            const input = document.getElementById(fieldId);
            const errorSpan = document.getElementById(errorId);
            if(input) input.classList.add('error');
            if(errorSpan) {
                errorSpan.innerText = message;
                errorSpan.style.display = 'block';
            }
        }

        // Vehicle -> Seat Logic
        const vehicleSelect = document.getElementById('vehicleType');
        const seatsSelect = document.getElementById('offerSeats');

        if (vehicleSelect && seatsSelect) {
            vehicleSelect.addEventListener('change', function() {
                const type = this.value;
                let maxSeats = 4; // Default Car
                
                if (type === 'bike') maxSeats = 1;
                else if (type === 'suv') maxSeats = 6;
                else if (type === 'car') maxSeats = 4;
                
                // Clear existing options
                seatsSelect.innerHTML = '<option value="">Select</option>';
                
                // Populate based on max
                for (let i = 1; i <= maxSeats; i++) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.text = i;
                    seatsSelect.appendChild(option);
                }
            });
        }
        
        // Helper to clear all errors
        function clearErrors() {
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(i => i.classList.remove('error'));
            const errors = document.querySelectorAll('.error-message');
            errors.forEach(e => e.style.display = 'none');
            document.getElementById('rideFormMessage').innerHTML = '';
        }
        function clearError(fieldId, errorId) {
            const input = document.getElementById(fieldId);
            const errorSpan = document.getElementById(errorId);
            if(input) input.classList.remove('error');
            if(errorSpan) {
                errorSpan.style.display = 'none';
            }
        }

        // Live Validation: Allows letters, numbers, spaces, and valid special chars like , . - '
        function validateLocationLive(fieldId, errorId, fieldName) {
            const val = document.getElementById(fieldId).value.trim();
            const hasLetters = /[a-zA-Z]/.test(val);
            const validPattern = /^[a-zA-Z0-9\s,\.\-']+$/;
            
            if (val === '') {
                clearError(fieldId, errorId);
                return true;
            } else if (!hasLetters) {
                showError(fieldId, errorId, `${fieldName} must contain at least one letter`);
                return false;
            } else if (!validPattern.test(val)) {
                showError(fieldId, errorId, `${fieldName} contains invalid characters (@,#,$,etc)`);
                return false;
            } else {
                clearError(fieldId, errorId);
                return true;
            }
        }

        async function handleRideSubmit(e) {
            e.preventDefault();
            
            clearErrors();
            let hasError = false;

            const from = document.getElementById('offerFrom').value.trim();
            const to = document.getElementById('offerTo').value.trim();
            const date = document.getElementById('offerDate').value;
            const time = document.getElementById('offerTime').value;
            const vehicle = document.getElementById('vehicleType').value;
            const seats = document.getElementById('offerSeats').value;
            const price = document.getElementById('offerPrice').value;

            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Validate From
            const fromValid = validateLocationLive('offerFrom', 'errorFrom', 'Starting location');
            if (!fromValid) hasError = true;
            if (fromValid && !from) {
                showError('offerFrom', 'errorFrom', 'Please enter starting location');
                hasError = true;
            }

            // Validate To
            const toValid = validateLocationLive('offerTo', 'errorTo', 'Destination');
            if (!toValid) hasError = true;
            if (toValid && !to) {
                showError('offerTo', 'errorTo', 'Please enter destination');
                hasError = true;
            } else if (from.toLowerCase() === to.toLowerCase() && from !== '') {
                showError('offerTo', 'errorTo', 'Destination cannot be same as start');
                hasError = true;
            }

            // Validate Date
            if (!date) {
                showError('offerDate', 'errorDate', 'Please select date');
                hasError = true;
            } else {
                const selectedDate = new Date(date);
                if (selectedDate < today) {
                    showError('offerDate', 'errorDate', 'Date cannot be in the past');
                    hasError = true;
                }
            }
            
            // Validate Time
            if (!time) {
                showError('offerTime', 'errorTime', 'Please select time');
                hasError = true;
            }
            
            // Validate Vehicle
            if (!vehicle) {
                showError('vehicleType', 'errorVehicle', 'Please select a vehicle');
                hasError = true;
            }

            // Validate Seats
            if (!seats || isNaN(seats) || parseInt(seats) < 1) {
                showError('offerSeats', 'errorSeats', 'At least 1 seat required');
                hasError = true;
            }

            // Validate Price
            if (!price || price === '' || isNaN(parseFloat(price)) || parseFloat(price) <= 0) {
                showError('offerPrice', 'errorPrice', 'Price must be greater than 0');
                hasError = true;
            }
            
            if (hasError) return;
            
            // Show loading state
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
            btn.disabled = true;

            try {
                // Save Ride via API
                const rideData = {
                    from, to, date, time, vehicle, seats, price,
                    details: document.getElementById('offerDetails').value,
                    stops: document.getElementById('offerStops').value.trim()
                };
                
                // Debug log
                console.log("Submitting ride:", rideData);

                const result = await RideManager.addRide(rideData);
                console.log("Result:", result);

                if (result.success) {
                    document.getElementById('rideFormMessage').innerHTML = '<div class="success-message fade-in"><i class="fas fa-check-circle"></i> Ride published successfully!</div>';
                    
                    setTimeout(() => {
                        document.getElementById('rideForm').reset();
                        document.getElementById('rideFormMessage').innerHTML = '';
                        window.location.href = 'dashboard.php'; // Redirect to dashboard to see the ride
                    }, 1500);
                } else {
                    document.getElementById('rideFormMessage').innerHTML = `<div class="error-banner"><i class="fas fa-exclamation-circle"></i> ${result.message}</div>`;
                    const btn = e.target.querySelector('button[type="submit"]');
                    btn.innerHTML = btn.dataset.originalText || 'Publish Ride'; // Restore text
                    btn.disabled = false;
                }
            } catch (err) {
                console.error("Submission Error:", err);
                alert("An unexpected error occurred: " + err.message);
                const btn = e.target.querySelector('button[type="submit"]');
                btn.innerHTML = btn.dataset.originalText || 'Publish Ride';
                btn.disabled = false;
            }
        }
    </script>

</body>
</html>
