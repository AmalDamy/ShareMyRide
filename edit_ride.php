<?php
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$rideId = $_GET['id'] ?? null;
if (!$rideId) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ride - ShareMyRide</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo">ShareMyRide</a>
            <div class="nav-links" id="navLinks">
                <a href="dashboard.php" style="color: var(--primary-teal); font-weight: 700;">Dashboard</a>
                <a href="find_ride.php">Find Ride</a>
                <a href="offer_ride.php">Offer Ride</a>
                <a href="logout.php" style="color: var(--error-red); font-weight: 600;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <div class="container" style="padding-top: 7rem; max-width: 800px;">
        
        <div class="text-center mb-4">
            <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-dark);">Edit Ride</h1>
            <p style="color: var(--text-gray);">Update details for your upcoming trip.</p>
        </div>

        <div class="search-card" style="display: block; padding: 2.5rem; border-top: 4px solid var(--accent-yellow);">
            <div id="loading" style="text-align:center; padding: 2rem;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>Loading details...</p>
            </div>

            <form id="rideForm" onsubmit="handleRideSubmit(event)" style="display:none;">
                <input type="hidden" id="rideId" value="<?php echo htmlspecialchars($rideId); ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>From *</label>
                        <input type="text" id="offerFrom" class="form-input" placeholder="City or Campus" required>
                        <span id="errorFrom" class="error-message">Please enter a valid start location</span>
                    </div>
                    <div class="form-group">
                        <label>To *</label>
                        <input type="text" id="offerTo" class="form-input" placeholder="Destination" required>
                        <span id="errorTo" class="error-message">Please enter a valid destination</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Date *</label>
                        <input type="date" id="offerDate" class="form-input" required>
                        <span id="errorDate" class="error-message">Please select a future date</span>
                    </div>
                    <div class="form-group">
                        <label>Time *</label>
                        <input type="time" id="offerTime" class="form-input" required>
                        <span id="errorTime" class="error-message">Please select a time</span>
                    </div>
                    <div class="form-group">
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

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="form-group">
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
                    <div class="form-group">
                        <label>Price per Seat (₹) *</label>
                        <input type="number" id="offerPrice" class="form-input" placeholder="e.g. 150" min="1" required>
                        <span id="errorPrice" class="error-message">Please enter a valid price</span>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label>Trip Details / Instructions</label>
                    <textarea id="offerDetails" class="form-input" rows="3" placeholder="Specific pickup point, luggage info, etc."></textarea>
                </div>

                <!-- Global message container for success/network errors -->
                <div id="rideFormMessage"></div>

                <div style="display:flex; justify-content:space-between; gap: 1rem;">
                    <button type="button" onclick="window.history.back()" class="btn btn-outline" style="flex:1;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex:2;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>

            </form>
        </div>

    </div>

    <script src="js/ride_manager.js"></script>
    <script>
        function toggleMobileMenu() {
            document.getElementById('navLinks').classList.toggle('show');
        }

        const rideId = document.getElementById('rideId').value;
        const form = document.getElementById('rideForm');
        const loader = document.getElementById('loading');

        // Load Ride Details
        window.addEventListener('load', async () => {
            try {
                const res = await fetch(`api_rides.php?ride_id=${rideId}`);
                const data = await res.json();
                
                if (data.success) {
                    const ride = data.ride;
                    document.getElementById('offerFrom').value = ride.from_location;
                    document.getElementById('offerTo').value = ride.to_location;
                    document.getElementById('offerDate').value = ride.ride_date;
                    document.getElementById('offerTime').value = ride.ride_time.substring(0,5); // Trim seconds
                    document.getElementById('vehicleType').value = ride.vehicle_type;
                    document.getElementById('offerSeats').value = ride.seats_available;
                    document.getElementById('offerPrice').value = ride.price_per_seat;
                    document.getElementById('offerDetails').value = ride.details;
                    
                    loader.style.display = 'none';
                    form.style.display = 'block';
                } else {
                    loader.innerHTML = '<p class="text-error">Ride not found.</p>';
                }
            } catch (e) {
                console.error(e);
                loader.innerHTML = '<p class="text-error">Error loading ride details.</p>';
            }
        });

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

        // Helper to clear all errors
        function clearErrors() {
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(i => i.classList.remove('error'));
            const errors = document.querySelectorAll('.error-message');
            errors.forEach(e => e.style.display = 'none');
            document.getElementById('rideFormMessage').innerHTML = '';
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
            const details = document.getElementById('offerDetails').value;

            // Validate All Required
            if (!from || !to || !date || !time || !vehicle || !seats || !price) {
                 document.getElementById('rideFormMessage').innerHTML = `<div class="error-banner">Please fill all required fields</div>`;
                 return;
            }

            // Validate specifically for numbers
            if (isNaN(seats) || parseInt(seats) < 1) {
                showError('offerSeats', 'errorSeats', 'At least 1 seat required');
                hasError = true;
            }
            if (isNaN(price) || parseFloat(price) <= 0) {
                showError('offerPrice', 'errorPrice', 'Price must be greater than 0');
                hasError = true;
            }

            if (hasError) return;

            // Show loading
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;

            try {
                const res = await fetch('api_rides.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'update',
                        ride_id: rideId,
                        from, to, date, time, vehicle, seats, price, details
                    })
                });
                
                const data = await res.json();
                
                if (data.success) {
                    document.getElementById('rideFormMessage').innerHTML = '<div class="success-message fade-in"><i class="fas fa-check-circle"></i> Updated successfully!</div>';
                    setTimeout(() => window.location.href = 'dashboard.php', 1000);
                } else {
                     document.getElementById('rideFormMessage').innerHTML = `<div class="error-banner">${data.message}</div>`;
                     btn.innerHTML = originalText;
                     btn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    </script>

</body>
</html>
