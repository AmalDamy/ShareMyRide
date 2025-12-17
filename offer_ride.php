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

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo">ShareMyRide</a>
            <div class="nav-links" id="navLinks">
                <a href="index.php">Home</a>
                <a href="find_ride.php">Find Ride</a>
                <a href="offer_ride.php" style="color: var(--primary-teal);">Offer Ride</a>
                <a href="long_trip.php">Long Trip</a>
                <a href="fuel_calculator.php">Fuel Calculator</a>
                <a href="contact.php">Contact</a>
                <a href="logout.php" style="color: var(--error-red); font-weight: 600;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <div class="container" style="margin-top: 3rem; max-width: 800px;">
        
        <div class="text-center mb-4">
            <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-dark);">Publish a Ride</h1>
            <p style="color: var(--text-gray);">Cover your driving costs by sharing your empty seats.</p>
        </div>

        <div class="search-card" style="display: block; padding: 2.5rem; border-top: 4px solid var(--primary-teal);">
            <form id="rideForm" onsubmit="handleRideSubmit(event)">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>From *</label>
                        <input type="text" id="offerFrom" class="form-input" placeholder="City or Campus" required>
                        <span class="error-message">Please enter starting point</span>
                    </div>
                    <div class="form-group">
                        <label>To *</label>
                        <input type="text" id="offerTo" class="form-input" placeholder="Destination" required>
                        <span class="error-message">Please enter destination</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Date *</label>
                        <input type="date" id="offerDate" class="form-input" required>
                        <span class="error-message">Please select date</span>
                    </div>
                    <div class="form-group">
                        <label>Time *</label>
                        <input type="time" id="offerTime" class="form-input" required>
                        <span class="error-message">Please select time</span>
                    </div>
                    <div class="form-group">
                        <label>Vehicle Type *</label>
                        <select id="vehicleType" class="form-input" required>
                            <option value="">Select</option>
                            <option value="car">Car</option>
                            <option value="bike">Bike</option>
                            <option value="suv">SUV</option>
                        </select>
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
                    </div>
                    <div class="form-group">
                        <label>Price per Seat (₹) *</label>
                        <input type="number" id="offerPrice" class="form-input" placeholder="e.g. 150" min="1" required>
                        <span class="error-message">Please enter price</span>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label>Trip Details / Instructions</label>
                    <textarea id="offerDetails" class="form-input" rows="3" placeholder="Specific pickup point, luggage info, etc."></textarea>
                </div>

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

        // Set minimum date to today
        document.getElementById('offerDate').min = new Date().toISOString().split('T')[0];

        async function handleRideSubmit(e) {
            e.preventDefault();
            
            const formMessage = document.getElementById('rideFormMessage');
            
            // Check auth first (simple check if we are logged in - API checks session)
            // Ideally we should await RideManager.checkAuth() but for now relying on backend response
            
            const from = document.getElementById('offerFrom').value.trim();
            const to = document.getElementById('offerTo').value.trim();
            const date = document.getElementById('offerDate').value;
            const time = document.getElementById('offerTime').value;
            const vehicle = document.getElementById('vehicleType').value;
            const seats = document.getElementById('offerSeats').value;
            const price = document.getElementById('offerPrice').value;

            // Validation
            let hasError = false;
            
            if (!from || !to || !date || !time || !vehicle || !seats || !price) {
                formMessage.innerHTML = '<div class="error-banner"><i class="fas fa-exclamation-circle"></i> Please fill all required fields!</div>';
                hasError = true;
            }
            
            const selectedDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                formMessage.innerHTML = '<div class="error-banner"><i class="fas fa-exclamation-circle"></i> Please select a future date!</div>';
                hasError = true;
            }
            
            if (hasError) return;
            
            // Show loading state
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
            btn.disabled = true;

            // Save Ride via API
            const rideData = {
                from, to, date, time, vehicle, seats, price,
                details: document.getElementById('offerDetails').value
            };
            
            const result = await RideManager.addRide(rideData);

            if (result.success) {
                formMessage.innerHTML = '<div class="success-message fade-in"><i class="fas fa-check-circle"></i> Ride published successfully!</div>';
                
                setTimeout(() => {
                    document.getElementById('rideForm').reset();
                    formMessage.innerHTML = '';
                    window.location.href = 'find_ride.php';
                }, 1500);
            } else {
                formMessage.innerHTML = `<div class="error-banner"><i class="fas fa-exclamation-circle"></i> ${result.message}</div>`;
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    </script>

</body>
</html>
