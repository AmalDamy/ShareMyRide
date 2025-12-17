<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Ride Tracking - ShareMyRide</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <style>
        body {
            background-color: #f3f4f6;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Map fills remaining height */
        #map {
            flex: 1;
            width: 100%;
            z-index: 1;
        }

        .tracking-overlay {
            position: absolute;
            top: 100px;
            left: 20px;
            z-index: 1000;
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            width: 320px;
            animation: slideIn 0.5s ease-out;
        }

        .driver-info {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .driver-avatar {
            width: 50px;
            height: 50px;
            background: #e0f2f1;
            color: var(--primary-teal);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-waiting { background: #fee2e2; color: #991b1b; }
        .status-active { background: #d1fae5; color: #065f46; animation: pulse 2s infinite; }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .control-panel {
            margin-top: 1rem;
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo">ShareMyRide</a>
            <div class="nav-links" id="navLinks">
                <a href="index.php">Home</a>
                <a href="find_ride.php">Find Ride</a>
                <a href="live_tracking.php" style="color: var(--primary-teal); font-weight: 700;">Live Tracking</a>
                <a href="dashboard.php">My Profile</a>
                <a href="logout.php" style="color: var(--error-red); font-weight: 600; margin-left: 10px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
            <button class="mobile-menu-toggle" onclick="document.getElementById('navLinks').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Map Container -->
    <div id="map"></div>

    <!-- Tracking Info Card -->
    <div class="tracking-overlay">
        <h3 style="margin-bottom: 0.5rem;">Live Ride Status</h3>
        <div id="rideStatusBadge" class="status-badge status-waiting">Waiting to Start</div>
        
        <div class="driver-info" style="margin-top: 1.5rem;">
            <div class="driver-avatar"><i class="fas fa-user"></i></div>
            <div>
                <div style="font-weight: 700;">Arjun Kumar</div>
                <div style="font-size: 0.9rem; color: var(--text-gray);">Honda City • KL-11-AX-1234</div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <span style="color: var(--text-gray);">Estimated Arrival</span>
            <span style="font-weight: 700;" id="etaText">-- min</span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span style="color: var(--text-gray);">Distance Remaining</span>
            <span style="font-weight: 700;" id="distanceText">-- km</span>
        </div>

        <div class="control-panel">
            <button id="btnStart" onclick="startSimulation()" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-location-arrow"></i> Start Live Tracking
            </button>
            <button id="btnStop" onclick="stopSimulation()" class="btn btn-outline" style="width: 100%; display: none; border-color: var(--error-red); color: var(--error-red);">
                <i class="fas fa-stop-circle"></i> Stop Tracking
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/ride_manager.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        // Check Auth
        if (!RideManager.getCurrentUser()) {
            window.location.href = 'login.php';
        }

        // Initialize Map (Centered on Kerala roughly)
        const map = L.map('map').setView([10.0, 76.3], 10); // Around Kochi

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Coordinates for Simulation (Kochi to Thrissur roughly)
        const startPoint = [9.9312, 76.2673]; // Kochi
        const endPoint = [10.5276, 76.2144]; // Thrissur
        
        // Icons
        const carIcon = L.divIcon({
            html: '<div style="font-size: 2rem; color: var(--primary-teal); text-shadow: 2px 2px 4px rgba(0,0,0,0.3);"><i class="fas fa-car-side"></i></div>',
            className: 'custom-div-icon',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        const destIcon = L.divIcon({
            html: '<div style="font-size: 2rem; color: var(--error-red);"><i class="fas fa-map-marker-alt"></i></div>',
            className: 'custom-div-icon',
            iconSize: [30, 30],
            iconAnchor: [15, 30]
        });

        // Add permanent markers
        const destinationMarker = L.marker(endPoint, {icon: destIcon}).addTo(map).bindPopup("Destination");
        let carMarker = L.marker(startPoint, {icon: carIcon}).addTo(map).bindPopup("Driver: Arjun");

        // Draw Route Line
        const routeLine = L.polyline([startPoint, endPoint], {
            color: '#009688',
            weight: 5,
            opacity: 0.7,
            dashArray: '10, 10'
        }).addTo(map);

        map.fitBounds(routeLine.getBounds(), {padding: [50, 50]});

        // Simulation Variables
        let simulationInterval;
        let progress = 0;
        const speed = 0.005; // increment per tick

        function startSimulation() {
            // UI Updates
            document.getElementById('btnStart').style.display = 'none';
            document.getElementById('btnStop').style.display = 'block';
            
            const badge = document.getElementById('rideStatusBadge');
            badge.textContent = 'Ride in Progress';
            badge.className = 'status-badge status-active';

            // Reset
            progress = 0;

            // Loop
            simulationInterval = setInterval(() => {
                progress += speed;

                if (progress >= 1) {
                    stopSimulation(true);
                    return;
                }

                // Interpolate Position
                const currentLat = startPoint[0] + (endPoint[0] - startPoint[0]) * progress;
                const currentLng = startPoint[1] + (endPoint[1] - startPoint[1]) * progress;
                
                carMarker.setLatLng([currentLat, currentLng]);
                
                // Update Info
                const remaining = Math.round(75 * (1 - progress)); // Mock 75km distance
                document.getElementById('distanceText').innerText = remaining + ' km';
                document.getElementById('etaText').innerText = Math.round(remaining * 1.5) + ' min'; // Mock time

                // Keep map centered on car occasionally
                if (progress % 0.1 < speed) {
                    map.panTo([currentLat, currentLng]);
                }

            }, 50); // Tick every 50ms
        }

        function stopSimulation(finished = false) {
            clearInterval(simulationInterval);
            
            // UI Updates
            document.getElementById('btnStart').style.display = 'block';
            document.getElementById('btnStop').style.display = 'none';
            
            const badge = document.getElementById('rideStatusBadge');
            
            if (finished) {
                badge.textContent = 'Ride Completed';
                badge.className = 'status-badge status-active'; // Keep green but stop pulsing maybe?
                badge.style.animation = 'none';
                alert('You have arrived at your destination!');
            } else {
                badge.textContent = 'Tracking Paused';
                badge.className = 'status-badge status-waiting';
            }
        }
    </script>
</body>
</html>
