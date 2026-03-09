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
    <title>Request Ride - ShareMyRide</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    
    <?php include 'navbar.php'; ?>


    <div class="container" style="margin-top: 3rem; max-width: 600px;">
        
        <div id="loading" style="text-align: center; padding: 2rem;">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p style="color: var(--text-gray); margin-top: 1rem;">Loading trip details...</p>
        </div>

        <div id="rideCard" style="display: none; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);">
            <!-- Map Placeholder -->
            <div style="height: 150px; background: #e5e7eb; display: flex; align-items: center; justify-content: center; color: var(--text-gray);">
                <i class="fas fa-map-marked-alt" style="margin-right: 0.5rem;"></i> Route Preview
            </div>
            
            <div style="padding: 2rem;">
                
                <h1 style="font-size: 1.5rem; margin-bottom: 0.5rem; color: var(--text-dark);">Confirm Request</h1>
                <p style="color: var(--text-gray); margin-bottom: 2rem;">Review the details before requesting a seat.</p>

                <div style="display: flex; gap: 1rem; margin-bottom: 2rem; align-items: flex-start;">
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="width: 12px; height: 12px; border-radius: 50%; background: var(--primary-teal);"></div>
                        <div style="width: 2px; height: 40px; background: #e5e7eb; margin: 4px 0;"></div>
                        <div style="width: 12px; height: 12px; border-radius: 50%; background: var(--text-dark);"></div>
                    </div>
                    <div style="flex: 1;">
                        <div style="margin-bottom: 2rem;">
                            <label style="font-size: 0.85rem; color: var(--text-gray); text-transform: uppercase; font-weight: 600;">Pickup</label>
                            <div id="lblFrom" style="font-size: 1.1rem; font-weight: 600; color: var(--text-dark);">...</div>
                            <div id="lblTime" style="font-size: 0.9rem; color: var(--text-gray);">...</div>
                        </div>
                        <div>
                            <label style="font-size: 0.85rem; color: var(--text-gray); text-transform: uppercase; font-weight: 600;">Dropoff</label>
                            <div id="lblTo" style="font-size: 1.1rem; font-weight: 600; color: var(--text-dark);">...</div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div id="lblPrice" style="font-size: 1.5rem; font-weight: 800; color: var(--dark-teal);">...</div>
                        <div style="font-size: 0.85rem; color: var(--text-gray);">per seat</div>
                    </div>
                </div>

                <div style="border-top: 1px solid #e5e7eb; padding-top: 1.5rem; margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                            <i class="fas fa-user" style="color: #9ca3af;"></i>
                        </div>
                        <div>
                            <div id="lblDriver" style="font-weight: 600; color: var(--text-dark);">Driver Name</div>
                            <div style="font-size: 0.9rem; color: var(--text-gray);"><i class="fas fa-star" style="color: #f59e0b;"></i> 4.8 Rating</div>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label>Seats Requiring</label>
                    <select id="reqSeats" class="form-input">
                        <option value="1">1 Seat</option>
                        <option value="2">2 Seats</option>
                        <option value="3">3 Seats</option>
                    </select>
                </div>
                
                <div id="reqMessage"></div>

                <div style="display: flex; gap: 1rem;">
                    <button onclick="window.history.back()" class="btn btn-outline" style="flex: 1;">Cancel</button>
                    <button onclick="submitRequest()" class="btn btn-primary" style="flex: 2;">
                        <i class="fas fa-paper-plane"></i> Send Request
                    </button>
                </div>

            </div>
        </div>
    </div>

    <script src="js/ride_manager.js"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const rideId = urlParams.get('ride_id');
        let currentRide = null;

        // Load Ride Details
        (async function() {
            if (!rideId) {
                alert('Invalid Ride ID');
                window.location.href = 'find_ride.php';
                return;
            }

            try {
                // Fetch specific ride
                const response = await fetch(`api_rides.php?ride_id=${rideId}`);
                const data = await response.json();
                
                if (data.success && data.ride) {
                    currentRide = data.ride;
                    renderRide(currentRide);
                } else {
                    document.getElementById('loading').innerHTML = '<p>Ride not found.</p>';
                }
            } catch (e) {
                console.error(e);
            }
        })();

        function renderRide(ride) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('rideCard').style.display = 'block';

            document.getElementById('lblFrom').textContent = ride.from_location;
            document.getElementById('lblTo').textContent = ride.to_location;
            document.getElementById('lblTime').textContent = `${ride.ride_date} at ${ride.ride_time}`;
            document.getElementById('lblPrice').textContent = '₹' + parseFloat(ride.price_per_seat).toFixed(0);
            document.getElementById('lblDriver').textContent = ride.driver_name;
        }

        async function submitRequest() {
            const seats = document.getElementById('reqSeats').value;
            const msgBox = document.getElementById('reqMessage');
            
            // Show processing
            const btn = document.querySelector('.btn-primary');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Sending...';
            btn.disabled = true;

            try {
                const response = await fetch('api_requests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ride_id: rideId,
                        seats_requested: seats
                    })
                });
                const result = await response.json();

                if (result.success) {
                    msgBox.innerHTML = '<div class="success-message" style="margin-bottom:1rem; color: green;"><i class="fas fa-check"></i> Request Sent! Waiting for driver approval.</div>';
                    setTimeout(() => {
                        window.location.href = 'dashboard.php'; // Or track page
                    }, 2000);
                } else {
                    if (result.message === 'Unauthorized') {
                         window.location.href = 'login.php';
                         return;
                    }
                    msgBox.innerHTML = `<div class="error-banner" style="margin-bottom:1rem; color: red;">${result.message}</div>`;
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error(error);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
