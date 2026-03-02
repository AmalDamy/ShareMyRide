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
    <title>Track Ride - ShareMyRide</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    
    <style>
        body {
            background-color: #f3f4f6;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

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
            width: 340px;
            animation: slideIn 0.5s ease-out;
            max-height: 80vh;
            overflow-y: auto;
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
            overflow: hidden;
        }
        
        .driver-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo">ShareMyRide</a>
            <div class="nav-links">
                <a href="dashboard.php">Back to Dashboard</a>
                <a href="logout.php" style="color: var(--error-red); font-weight: 600; margin-left: 10px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <!-- Map -->
    <div id="map"></div>

    <!-- Tracking Card -->
    <div class="tracking-overlay" id="overlay">
        <!-- Loading State -->
        <div id="loading" style="text-align: center;">
            <i class="fas fa-spinner fa-spin"></i> Loading...
        </div>

        <!-- Content -->
        <div id="content" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                <div>
                    <h3 style="margin-bottom: 0.2rem; font-size: 1.2rem;">Live Ride Status</h3>
                    <div id="rideStatus" class="status-badge status-active">On the way</div>
                </div>
                 <div style="text-align: right;">
                    <div style="font-size: 0.8rem; color: var(--text-gray);">ETA</div>
                    <div style="font-size: 1.2rem; font-weight: 800; color: var(--dark-teal);" id="eta">15 min</div>
                </div>
            </div>
            
            <div class="driver-info">
                <div class="driver-avatar">
                   <div id="driverPic"><i class="fas fa-user"></i></div>
                </div>
                <div>
                    <div style="font-weight: 700;" id="driverName">Driver Name</div>
                    <div style="font-size: 0.9rem; color: var(--text-gray);">
                        <i class="fas fa-star" style="color: #f59e0b;"></i> <span id="driverRating">4.8</span>
                    </div>
                </div>
                <div style="margin-left: auto; display: flex; gap: 0.5rem;">
                    <button class="btn btn-primary" style="padding: 0.5rem; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-phone"></i></button>
                    <button class="btn btn-outline" style="padding: 0.5rem; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-comment"></i></button>
                </div>
            </div>

            <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <h4 style="font-size: 0.9rem; color: var(--text-gray); margin-bottom: 0.5rem; text-transform: uppercase;">Vehicle Details</h4>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600;" id="carModel">Car Model</div>
                        <div style="font-size: 0.9rem; color: var(--text-gray);" id="carPlate">KL-XX-YYYY</div>
                    </div>
                    <i class="fas fa-car" style="font-size: 1.5rem; color: #d1d5db;"></i>
                </div>
            </div>

            <h4 style="font-size: 0.9rem; color: var(--text-gray); margin-bottom: 0.5rem; text-transform: uppercase;">Trip Route</h4>
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <div style="display: flex; flex-direction: column; align-items: center; padding-top: 5px;">
                    <div style="width: 10px; height: 10px; background: var(--primary-teal); border-radius: 50%;"></div>
                    <div style="width: 2px; flex: 1; background: #e5e7eb; min-height: 20px;"></div>
                    <div style="width: 10px; height: 10px; background: var(--text-dark); border-radius: 50%;"></div>
                </div>
                <div style="flex: 1;">
                    <div style="margin-bottom: 1rem;">
                        <div style="font-weight: 600;" id="locFrom">From</div>
                        <div style="font-size: 0.85rem; color: var(--text-gray);">Pickup Point</div>
                    </div>
                    <div>
                        <div style="font-weight: 600;" id="locTo">To</div>
                        <div style="font-size: 0.85rem; color: var(--text-gray);">Dropoff Point</div>
                    </div>
                </div>
            </div>
            
            <button onclick="alert('Panic button pressed - Alert sent to emergency contacts!')" style="width: 100%; border: 1px solid #fee2e2; background: #fef2f2; color: #b91c1c; padding: 0.8rem; border-radius: 8px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-shield-alt"></i> Safety Assure
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const rideId = urlParams.get('id');

        // Initial Map Setup
        const map = L.map('map').setView([10.0, 76.3], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        
        const carIcon = L.divIcon({
            html: '<div style="font-size: 2rem; color: var(--primary-teal); filter: drop-shadow(2px 2px 2px rgba(0,0,0,0.3));"><i class="fas fa-car-side"></i></div>',
            className: 'custom-div-icon', iconSize: [30, 30], iconAnchor: [15, 15]
        });

        // City Coordinates Mock (Cache)
        const cities = {
            'kochi': [9.9312, 76.2673],
            'munnar': [10.0889, 77.0595],
            'trivandrum': [8.5241, 76.9366],
            'thiruvananthapuram': [8.5241, 76.9366],
            'thrissur': [10.5276, 76.2144],
            'kozhikode': [11.2588, 75.7804],
            'alappuzha': [9.4981, 76.3388],
            'kottayam': [9.5916, 76.5222],
            'kollam': [8.8932, 76.6141],
            'palakkad': [10.7867, 76.6548],
            'wayanad': [11.6854, 76.1320],
            'kannur': [11.8745, 75.3704],
            'kasaragod': [12.5102, 74.9852],
            'idukki': [9.8494, 76.9809],
            'pathanamthitta': [9.2648, 76.7870],
            'malappuram': [11.0510, 76.0711],
            'eranakulam': [9.9816, 76.2999], 
            'ernakulam': [9.9816, 76.2999],
            
            // Local Area Fixes
            'ponkunnam': [9.5667, 76.7667],
            'ponnkunnam': [9.5667, 76.7667],
            'koovapally': [9.5400, 76.8000], 
            'koovappally': [9.5400, 76.8000],
            'kanjirapally': [9.5546, 76.7865],
            'kanjirappally': [9.5546, 76.7865],
            'mundakkayam': [9.5826, 76.8841],
            'mundakayam': [9.5826, 76.8841],
            'pala': [9.7112, 76.6806],
            '26 mile': [9.5480, 76.8050],
            '26th mile': [9.5480, 76.8050],
            'amal jyothi': [9.5293, 76.8221],
            'ajce': [9.5293, 76.8221]
        };

        const FALLBACK_COORDS = [9.5293, 76.8221]; // Amal Jyothi Campus

        async function getCoords(city) {
             if(!city) return FALLBACK_COORDS; 
             
             // Normalize: lowercase, trim, and remove trailing commas/dots
             let cleanName = city.toLowerCase().replace(/[.,]$/, '').trim();
             
             // 1. Exact Match in Dictionary
             if(cities[cleanName]) return cities[cleanName];
             
             // 2. Contains Match (Handle "Mundakkayam, Kerala")
             const foundKey = Object.keys(cities).find(k => cleanName.includes(k) || k.includes(cleanName));
             if(foundKey) return cities[foundKey];

             // 3. Amal Jyothi Alias logic
             if(cleanName.includes('amal jyothi') || cleanName.includes('ajce')) return cities['amal jyothi'];

             // 4. Try Nominatim API (OpenStreetMap)
             try {
                 const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(city + ", Kerala")}`);
                 const data = await response.json();
                 if(data && data.length > 0) {
                     return [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                 }
             } catch(e) {
                 console.warn("Geocoding failed for", city, e);
             }

             // 5. Final Fallback
             return [FALLBACK_COORDS[0] + (Math.random() * 0.01), FALLBACK_COORDS[1] + (Math.random() * 0.01)]; 
        }

        // Fetch Data
        (async function() {
            if(!rideId) { 
                alert('No Ride ID'); window.location.href = 'dashboard.php'; return; 
            }

            try {
                const response = await fetch(`api_rides.php?ride_id=${rideId}`);
                const data = await response.json();
                
                if(data.success && data.ride) {
                    initTracking(data.ride);
                } else {
                    document.getElementById('loading').innerText = 'Ride not found';
                }
            } catch(e) { console.error(e); }
        })();

        async function initTracking(ride) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('content').style.display = 'block';

            // Fill Data
            document.getElementById('driverName').textContent = ride.driver_name;
            document.getElementById('driverRating').textContent = ride.rating || '4.5';
            document.getElementById('carModel').textContent = ride.vehicle_type;
            document.getElementById('carPlate').textContent = ride.vehicle_number || 'KL-01-AB-1234';
            document.getElementById('locFrom').textContent = ride.from_location;
            document.getElementById('locTo').textContent = ride.to_location;

            if(ride.profile_pic && ride.profile_pic !== 'default_user.png') {
                document.getElementById('driverPic').innerHTML = `<img src="${ride.profile_pic}" alt="Driver">`;
            }

            // Map Logic
            const startCoords = await getCoords(ride.from_location);
            const endCoords = await getCoords(ride.to_location);

            // Add Markers
            L.marker(startCoords).addTo(map).bindPopup(`<b>Pickup:</b> ${ride.from_location}`);
            L.marker(endCoords).addTo(map).bindPopup(`<b>Dropoff:</b> ${ride.to_location}`);
            
            const carMarker = L.marker(startCoords, {icon: carIcon, zIndexOffset: 1000}).addTo(map);

            // 1. Draw Actual Road Route
            const routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(startCoords[0], startCoords[1]),
                    L.latLng(endCoords[0], endCoords[1])
                ],
                routeWhileDragging: false,
                addWaypoints: false,
                draggableWaypoints: false,
                fitSelectedRoutes: true,
                show: false,
                lineOptions: {
                    styles: [{color: '#0d9488', opacity: 0.6, weight: 6}]
                },
                createMarker: function() { return null; } // Don't add markers via routing machine
            }).addTo(map);

            routingControl.on('routesfound', function(e) {
                const routes = e.routes;
                const pathCoords = routes[0].coordinates;
                
                // Simulate Movement along the coordinates
                let index = 0;
                const interval = setInterval(() => {
                    index += 1;
                    if (index >= pathCoords.length) {
                        index = pathCoords.length - 1;
                        document.getElementById('rideStatus').innerText = "Arrived";
                        document.getElementById('rideStatus').className = "status-badge status-active"; 
                        document.getElementById('rideStatus').style.animation = "none";
                        document.getElementById('eta').innerText = "Arrived";
                        clearInterval(interval);
                    }
                    
                    const pos = pathCoords[index];
                    carMarker.setLatLng(pos);
                    
                    // Update ETA
                    const progress = index / pathCoords.length;
                    const mins = Math.max(0, Math.round(30 * (1 - progress)));
                    document.getElementById('eta').innerText = mins + ' min';
                    
                    // Occasionally Re-center
                    if(index % 20 === 0) map.panTo(pos);
                }, 100);
            });

            // Handle Error (Fallback to straight line if routing service is down)
            routingControl.on('routingerror', function() {
                console.warn("Routing failed, falling back to straight line.");
                const route = L.polyline([startCoords, endCoords], { color: '#0d9488', weight: 5, dashArray: '10,10' }).addTo(map);
                map.fitBounds(route.getBounds(), {padding: [50, 50]});
            });
        }
    </script>
</body>
</html>
