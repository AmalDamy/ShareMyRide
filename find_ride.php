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
    <title>Find a Ride - ShareMyRide</title>
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
                <a href="find_ride.php" style="color: var(--primary-teal);">Find Ride</a>
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

    <div class="container" style="margin-top: 2rem;">
        
        <!-- Search Bar (Compact) -->
        <div class="search-card" style="padding: 1rem 1.5rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm); border: 1px solid #eee;">
            <form action="find_ride.php" method="GET" style="display: flex; gap: 1rem; width: 100%; align-items: flex-end;">
                <div class="form-group">
                    <label>From</label>
                    <input type="text" name="from" class="form-input" placeholder="Leaving from..." value="<?php echo isset($_GET['from']) ? htmlspecialchars($_GET['from']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>To</label>
                    <input type="text" name="to" class="form-input" placeholder="Going to..." value="<?php echo isset($_GET['to']) ? htmlspecialchars($_GET['to']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" class="form-input" value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>">
                </div>
                <div class="search-btn-wrapper">
                    <button type="submit" class="btn btn-primary">Update Search</button>
                </div>
            </form>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="margin: 0;">Available Rides</h2>
            <a href="live_tracking.php" style="color: var(--primary-teal); font-weight: 600; text-decoration: none; font-size: 0.9rem;">
                <i class="fas fa-satellite-dish"></i> Track your current ride
            </a>
        </div>

        <div id="ridesGrid" class="rides-grid" style="grid-template-columns: 1fr;">
            <!-- Rides will be loaded here via JS -->
            <div style="text-align: center; color: var(--text-gray); padding: 2rem;">Loading available rides...</div>
        </div>

    </div>

    <!-- Footer same as index (simplified) -->
    <footer class="footer" style="margin-top: 5rem;">
        <div class="container">
            <p class="text-center" style="font-size: 0.9rem;">&copy; 2025 ShareMyRide.</p>
        </div>
    </footer>

    <!-- Request Ride Modal -->
    <div id="requestModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px);">
        <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 0; border: 1px solid #888; width: 90%; max-width: 500px; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
            
            <div style="background: var(--primary-teal); padding: 1.5rem; color: white; display: flex; justify-content: space-between; align-items: center; border-radius: 16px 16px 0 0;">
                <h2 style="margin: 0; font-size: 1.25rem;">Confirm Ride Request</h2>
                <span onclick="closeRequestModal()" style="color: white; font-size: 1.5rem; font-weight: bold; cursor: pointer;">&times;</span>
            </div>

            <div style="padding: 2rem;">
                <div id="modalLoading" style="text-align: center; color: var(--text-gray);">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Loading trip details...</p>
                </div>

                <div id="modalContent" style="display: none;">
                    
                    <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                        <div style="display: flex; flex-direction: column; align-items: center; margin-top: 5px;">
                            <div style="width: 10px; height: 10px; background: var(--primary-teal); border-radius: 50%;"></div>
                            <div style="width: 2px; flex: 1; background: #e5e7eb; margin: 4px 0;"></div>
                            <div style="width: 10px; height: 10px; background: var(--text-dark); border-radius: 50%;"></div>
                        </div>
                        <div style="flex: 1;">
                            <div style="margin-bottom: 1.5rem;">
                                <div id="mFrom" style="font-weight: 700; font-size: 1.1rem; color: var(--text-dark);">...</div>
                                <div id="mTime" style="color: var(--text-gray); font-size: 0.9rem;">...</div>
                            </div>
                            <div>
                                <div id="mTo" style="font-weight: 700; font-size: 1.1rem; color: var(--text-dark);">...</div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                             <div id="mPrice" style="font-size: 1.5rem; font-weight: 800; color: var(--dark-teal);">...</div>
                             <div style="font-size: 0.8rem; color: var(--text-gray);">per seat</div>
                        </div>
                    </div>

                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 40px; height: 40px; background: #e5e7eb; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user" style="color: #9ca3af;"></i>
                        </div>
                        <div>
                            <div id="mDriver" style="font-weight: 600; color: var(--text-dark);">...</div>
                            <div style="font-size: 0.85rem; color: var(--text-gray);">
                                <i class="fas fa-car"></i> <span id="mVehicle">...</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Seats Required</label>
                        <select id="mSeats" class="form-input">
                            <option value="1">1 Seat</option>
                            <option value="2">2 Seats</option>
                            <option value="3">3 Seats</option>
                        </select>
                    </div>

                    <div id="modalMsg"></div>

                    <div style="display: flex; gap: 1rem;">
                        <button onclick="closeRequestModal()" class="btn btn-outline" style="flex: 1;">Cancel</button>
                        <button onclick="submitRequest()" id="btnConfirm" class="btn btn-primary" style="flex: 2;">Confirm Request</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/ride_manager.js"></script>
    <script>
        function toggleMobileMenu() {
            document.getElementById('navLinks').classList.toggle('show');
        }

        // Initial Load
        window.addEventListener('load', () => {
             // Check URL params for search
            const urlParams = new URLSearchParams(window.location.search);
            const from = urlParams.get('from');
            const to = urlParams.get('to');
            const date = urlParams.get('date');
            const rideId = urlParams.get('ride_id'); // Check if redirected from long_trip.php
            
            if (rideId) {
                requestRide(rideId);
            } else {
                renderRides(from || '', to || '', date || '');
            }
        });

        async function renderRides(filterFrom, filterTo, filterDate) {
            const container = document.getElementById('ridesGrid');
            container.innerHTML = '<div style="text-align: center; color: var(--text-gray); padding: 2rem;">Loading available rides...</div>';

            const filters = {};
            if(filterFrom) filters.from = filterFrom;
            if(filterTo) filters.to = filterTo;
            if(filterDate) filters.date = filterDate;

            const rides = await RideManager.getAllRides(filters);

            if (rides.length === 0) {
                container.style.gridTemplateColumns = '1fr';
                let message = 'No rides found.';
                if (filterFrom || filterTo) {
                    message = `No rides found matching your search.`;
                }
                
                container.innerHTML = `
                    <div style="text-align: center; padding: 4rem; background: #fff; border-radius: var(--radius-lg); border: 1px solid #e5e7eb;">
                        <i class="fas fa-route" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                        <h3 style="color: var(--text-dark); margin-bottom: 0.5rem;">${message}</h3>
                        <p style="color: var(--text-gray); margin-bottom: 1.5rem;">There are no rides available for this criteria right now.</p>
                        <a href="offer_ride.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Be the first to Offer a Ride
                        </a>
                        <br><br>
                        <button onclick="window.location.href='find_ride.php'" class="btn btn-outline" style="font-size: 0.9rem;">
                            View All Available Rides
                        </button>
                    </div>
                `;
                return;
            }

            // Restore grid layout
            container.style.gridTemplateColumns = '1fr'; 
            
            let html = '';
            rides.forEach(ride => {
                const price = parseFloat(ride.price_per_seat).toFixed(0);
                const rating = ride.rating || 'New';
                const time = ride.ride_time.substring(0, 5); // HH:MM
                const isLong = ride.ride_type === 'long';
                const badge = isLong ? '<span class="trip-badge upcoming" style="font-size: 0.7rem; margin-left: 0.5rem;">Long Trip</span>' : '';

                html += `
                <div class="ride-card" style="display: flex; flex-direction: row; border: 1px solid #e5e7eb; margin-bottom: 1rem; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <div class="ride-header" style="display: flex; flex-direction: column; width: 140px; justify-content: center; align-items: flex-start; background: #f9fafb; border-right: 1px solid #eee; padding: 1.5rem;">
                        <div style="font-size: 1.4rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.25rem;">${time}</div>
                        <div style="color: var(--text-gray); font-size: 0.9rem;">${ride.ride_date}</div>
                    </div>
                    <div class="ride-body" style="flex: 1; display: flex; justify-content: space-between; align-items: center; padding: 1.5rem;">
                        <div>
                            <div class="ride-route" style="font-size: 1.3rem; margin-bottom: 0.75rem; color: var(--dark-teal); font-weight: 700;">
                                ${ride.from_location} <i class="fas fa-long-arrow-alt-right" style="color: var(--text-gray); margin: 0 0.5rem;"></i> ${ride.to_location}
                                ${badge}
                            </div>
                            <div style="display: flex; gap: 1.5rem; color: var(--text-gray); font-size: 0.95rem; align-items: center;">
                                <span style="display: flex; align-items: center;">
                                    <i class="fas fa-user-circle" style="font-size: 1.2rem; margin-right: 0.5rem; color: var(--primary-teal);"></i> 
                                    ${ride.driver_name} 
                                    <span style="font-size: 0.8rem; color: var(--accent-yellow); margin-left: 0.5rem;"><i class="fas fa-star"></i> ${rating}</span>
                                </span>
                                <span style="display: flex; align-items: center;">
                                    <i class="fas fa-car" style="margin-right: 0.5rem;"></i> ${ride.vehicle_type}
                                </span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div class="ride-price" style="font-size: 1.8rem; margin-bottom: 0.25rem; color: var(--text-dark); font-weight: 800;">₹${price}</div>
                            <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.75rem;">${ride.seats_available} seats available</div>
                            <button onclick="requestRide(${ride.ride_id})" class="btn btn-primary" style="padding: 0.6rem 2rem; border-radius: 50px;">
                                Request Ride
                            </button>
                        </div>
                    </div>
                </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Modal Variables
        let currentRideId = null;
        let currentRide = null;

        async function requestRide(rideId) {
            currentRideId = rideId;
            const modal = document.getElementById('requestModal');
            const loading = document.getElementById('modalLoading');
            const content = document.getElementById('modalContent');
            
            modal.style.display = 'block';
            loading.style.display = 'block';
            content.style.display = 'none';

            try {
                // Fetch Ride Details
                const response = await fetch(`api_rides.php?ride_id=${rideId}`);
                const data = await response.json();

                if (data.success && data.ride) {
                    currentRide = data.ride;
                    
                    // Populate Modal
                    document.getElementById('mFrom').textContent = currentRide.from_location;
                    document.getElementById('mTo').textContent = currentRide.to_location;
                    document.getElementById('mTime').textContent = `${currentRide.ride_date} at ${currentRide.ride_time}`;
                    document.getElementById('mPrice').textContent = '₹' + parseFloat(currentRide.price_per_seat).toFixed(0);
                    document.getElementById('mDriver').textContent = currentRide.driver_name;
                    document.getElementById('mVehicle').textContent = currentRide.vehicle_type;

                    loading.style.display = 'none';
                    content.style.display = 'block';
                } else {
                    alert('Error loading ride details.');
                    closeRequestModal();
                }
            } catch (e) {
                console.error(e);
                alert('Network Error');
                closeRequestModal();
            }
        }

        function closeRequestModal() {
            document.getElementById('requestModal').style.display = 'none';
            document.getElementById('modalMsg').innerHTML = ''; // Clear messages
            // If URL has ride_id, clear it so reload works normally
            const url = new URL(window.location);
            if (url.searchParams.get('ride_id')) {
                url.searchParams.delete('ride_id');
                window.history.pushState({}, '', url);
                // Also reload rides to show full list if we were filtering
                const urlParams = new URLSearchParams(window.location.search);
                renderRides(urlParams.get('from') || '', urlParams.get('to') || '', urlParams.get('date') || '');
            }
        }

        async function submitRequest() {
            if (!currentRideId) return;

            const seats = document.getElementById('mSeats').value;
            const btn = document.getElementById('btnConfirm');
            const msgBox = document.getElementById('modalMsg');

            btn.disabled = true;
            btn.innerHTML = 'Sending...';

            try {
                const response = await fetch('api_requests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ride_id: currentRideId,
                        seats_requested: seats
                    })
                });
                const result = await response.json();

                if (result.success) {
                    msgBox.innerHTML = '<div class="success-message" style="margin-bottom:1rem; padding: 1rem; background: #d1fae5; color: #065f46; border-radius: 8px;"><i class="fas fa-check"></i> Request Sent! Waiting for driver approval.</div>';
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1500);
                } else {
                    msgBox.innerHTML = `<div class="error-banner" style="margin-bottom:1rem; padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 8px;">${result.message}</div>`;
                    btn.disabled = false;
                    btn.innerHTML = 'Confirm Request';
                }
            } catch (error) {
                console.error(error);
                msgBox.innerHTML = `<div class="error-banner">Server Error</div>`;
                btn.disabled = false;
                btn.innerHTML = 'Confirm Request';
            }
        }

        // Close modal if clicked outside
        window.onclick = function(event) {
            const modal = document.getElementById('requestModal');
            if (event.target == modal) {
                closeRequestModal();
            }
        }
    </script>

</body>
</html>
