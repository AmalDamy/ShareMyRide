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
    <title>Long Trip - ShareMyRide</title>
    <link rel="stylesheet" href="style.css">
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
                <a href="long_trip.php">Long Trip</a>
                <a href="live_tracking.php" style="color: var(--primary-teal); font-weight: 700;"><i class="fas fa-location-arrow"></i> Live Tracking</a>
                <a href="fuel_calculator.php">Fuel Calculator</a>
                <a href="contact.php">Contact</a>
                <a href="logout.php" style="color: var(--error-red); font-weight: 600;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Page Header -->
    <div style="background: linear-gradient(135deg, var(--primary-teal), var(--dark-teal)); color: white; padding: 8rem 0 3rem; text-align: center;">
        <div class="container">
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Plan Your Long Trip</h1>
            <p style="font-size: 1.1rem; opacity: 0.9; margin-bottom: 2rem;">Weekend getaways, holiday trips, or hometown visits - share the journey, split the costs!</p>
            <a href="live_tracking.php" class="btn" style="background: white; color: var(--primary-teal); border: none; font-weight: 700;">
                <i class="fas fa-map-marked-alt"></i> Track Active Trip
            </a>
        </div>
    </div>

    <div class="container" style="margin-top: -2rem; padding-bottom: 4rem;">
        
        <!-- Create Trip Form -->
        <div class="search-card" style="margin-bottom: 3rem;">
            <h2 style="margin-bottom: 2rem; color: var(--dark-teal);">Create a New Trip</h2>
            <form id="tripForm" onsubmit="handleTripSubmit(event)">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Trip Title (Details) *</label>
                        <input type="text" id="tripDetails" class="form-input" placeholder="e.g., Weekend to Goa" required>
                        <span id="errorDetails" class="error-message">Please enter a trip title</span>
                    </div>
                    <div class="form-group">
                        <label>Vehicle Type *</label>
                        <select id="vehicleType" class="form-input" required>
                            <option value="">Select Vehicle</option>
                            <option value="car">Car</option>
                            <option value="suv">SUV</option>
                            <option value="van">Van</option>
                            <option value="bus">Mini Bus</option>
                        </select>
                        <span id="errorVehicle" class="error-message">Please select vehicle type</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>From *</label>
                        <input type="text" id="tripFrom" class="form-input" placeholder="Starting point" required>
                        <span id="errorFrom" class="error-message">Please enter start location</span>
                    </div>
                    <div class="form-group">
                        <label>To (Destination) *</label>
                        <input type="text" id="tripTo" class="form-input" placeholder="Destination" required>
                        <span id="errorTo" class="error-message">Please enter destination</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Start Date *</label>
                        <input type="date" id="startDate" class="form-input" required>
                        <span id="errorStartDate" class="error-message">Please select start date</span>
                    </div>
                    <div class="form-group">
                        <label>End Date *</label>
                        <input type="date" id="endDate" class="form-input" required>
                        <span id="errorEndDate" class="error-message">Please select end date</span>
                    </div>
                    <div class="form-group">
                        <label>Total Members (Seats) *</label>
                        <input type="number" id="totalMembers" class="form-input" min="2" max="15" placeholder="2-15" required>
                        <span id="errorMembers" class="error-message">Please enter members</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Estimated Total Cost (₹)</label>
                        <input type="number" id="totalCost" class="form-input" placeholder="Fuel + Tolls + Parking" min="1">
                        <span id="errorCost" class="error-message">Please enter estimated cost</span>
                    </div>
                    <div class="form-group">
                        <label>Cost Per Person</label>
                        <input type="text" id="costPerPerson" class="form-input" readonly style="background: #f3f4f6; font-weight: 600;">
                    </div>
                </div>

                <div id="formMessage"></div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                    <i class="fas fa-paper-plane"></i> Create Trip
                </button>
            </form>
        </div>

        <!-- Existing Trips -->
        <h2 style="font-size: 2rem; margin-bottom: 2rem; color: var(--text-dark);">Upcoming Long Trips</h2>
        
        <div id="tripsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem;">
            <!-- Loaded via JS -->
            <p>Loading trips...</p>
        </div>

    </div>

    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            document.getElementById('navLinks').classList.toggle('show');
        }

        // Calculate cost per person
        document.getElementById('totalCost').addEventListener('input', calculatePerPerson);
        document.getElementById('totalMembers').addEventListener('input', calculatePerPerson);

        function calculatePerPerson() {
            const total = parseFloat(document.getElementById('totalCost').value) || 0;
            const members = parseInt(document.getElementById('totalMembers').value) || 1;
            const perPerson = members > 0 ? (total / members).toFixed(2) : 0;
            document.getElementById('costPerPerson').value = perPerson > 0 ? `₹${perPerson}` : '';
        }

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
            document.getElementById('formMessage').innerHTML = '';
        }

        // Form submission
        async function handleTripSubmit(e) {
            e.preventDefault();
            
            clearErrors();
            let hasError = false;
            
            const formMessage = document.getElementById('formMessage');
            const submitBtn = e.target.querySelector('button[type="submit"]');
            
            // Data
            const details = document.getElementById('tripDetails').value.trim();
            const from = document.getElementById('tripFrom').value.trim();
            const to = document.getElementById('tripTo').value.trim();
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const seats = document.getElementById('totalMembers').value;
            const totalCost = document.getElementById('totalCost').value;
            const vehicle = document.getElementById('vehicleType').value;
            
            // Calculate price per seat roughly
            const price = seats > 0 ? (totalCost / seats) : 0;

            // Validation
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Regex: At least one letter
            const locationRegex = /[a-zA-Z]/;

            // Validate Details
            if (!details) {
                showError('tripDetails', 'errorDetails', 'Please enter trip title');
                hasError = true;
            }
            
            // Validate Vehicle
            if (!vehicle) {
                showError('vehicleType', 'errorVehicle', 'Please select a vehicle');
                hasError = true;
            }

            // Validate From
            if (!from) {
                showError('tripFrom', 'errorFrom', 'Please enter a start location');
                hasError = true;
            } else if (!locationRegex.test(from)) {
                showError('tripFrom', 'errorFrom', 'Location must contain letters');
                hasError = true;
            }

            // Validate To
            if (!to) {
                showError('tripTo', 'errorTo', 'Please enter a destination');
                hasError = true;
            } else if (!locationRegex.test(to)) {
                showError('tripTo', 'errorTo', 'Destination must contain letters');
                hasError = true;
            } else if (from.toLowerCase() === to.toLowerCase()) {
                showError('tripTo', 'errorTo', 'Destination cannot be same as start');
                hasError = true;
            }
            
            // Validate Dates
            let start = null; 
            if (!startDate) {
                showError('startDate', 'errorStartDate', 'Please select start date');
                hasError = true;
            } else {
                start = new Date(startDate);
                if (start < today) {
                    showError('startDate', 'errorStartDate', 'Start date cannot be in past');
                    hasError = true;
                }
            }

            if (!endDate) {
                showError('endDate', 'errorEndDate', 'Please select end date');
                hasError = true;
            } else if (start) {
                const end = new Date(endDate);
                if (end < start) {
                    showError('endDate', 'errorEndDate', 'End date cannot be before start');
                    hasError = true;
                }
            }
            
            // Validate Members
            if (!seats || isNaN(seats) || parseInt(seats) < 2) {
                showError('totalMembers', 'errorMembers', 'At least 2 members are required for a long trip');
                hasError = true;
            }
            
            // Validate Cost
            if (!totalCost || isNaN(totalCost) || parseFloat(totalCost) <= 0) {
                showError('totalCost', 'errorCost', 'Please enter a valid total cost greater than 0');
                hasError = true;
            }

            if (hasError) return;

            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Creating...';

            try {
                const response = await fetch('api_rides.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        type: 'long',
                        from: from,
                        to: to,
                        date: startDate,
                        end_date: endDate,
                        seats: seats,
                        vehicle: vehicle,
                        details: details,
                        total_cost: totalCost,
                        price: price,
                        time: '08:00' // Default start time
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    formMessage.innerHTML = '<div class="success-message"><i class="fas fa-check-circle"></i> ' + result.message + '</div>';
                    document.getElementById('tripForm').reset();
                    loadTrips(); // Reload list
                } else {
                    formMessage.innerHTML = '<div class="error-banner">' + result.message + '</div>';
                }
            } catch (error) {
                console.error(error);
                formMessage.innerHTML = '<div class="error-banner">Server Error</div>';
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Create Trip';
            }
        }

        // Load trips
        async function loadTrips() {
            const grid = document.getElementById('tripsGrid');
            try {
                const response = await fetch('api_rides.php?type=long');
                const data = await response.json();
                
                if (data.success && data.rides.length > 0) {
                    let html = '';
                    data.rides.forEach(trip => {
                        const cost = parseFloat(trip.price_per_seat).toFixed(0);
                        html += `
                        <div class="trip-card">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                <h3 style="font-size: 1.25rem; color: var(--dark-teal); margin: 0;">${trip.details}</h3>
                                <span class="trip-badge upcoming">Upcoming</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; color: var(--text-gray);">
                                <i class="fas fa-route"></i>
                                <span>${trip.from_location} → ${trip.to_location}</span>
                            </div>
                            <div style="display: flex; gap: 2rem; margin-bottom: 1rem; color: var(--text-gray); font-size: 0.9rem;">
                                <div><i class="fas fa-calendar"></i> ${trip.ride_date}</div>
                                <div><i class="fas fa-users"></i> ${trip.seats_available} seats</div>
                                <div><i class="fas fa-car"></i> ${trip.vehicle_type}</div>
                            </div>
                            <div style="border-top: 1px solid #e5e7eb; padding-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 0.85rem; color: var(--text-gray);">Cost per person</div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-teal);">₹${cost}</div>
                                </div>
                                <button onclick="window.location.href='find_ride.php?ride_id=${trip.ride_id}'" class="btn btn-primary">View Trip</button>
                            </div>
                        </div>
                        `;
                    });
                    grid.innerHTML = html;
                } else {
                    grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--text-gray);">No upcoming trips found. Create one!</p>';
                }
            } catch (e) {
                console.error(e);
            }
        }

        loadTrips();
    </script>
</body>
</html>
