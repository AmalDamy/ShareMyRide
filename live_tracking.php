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
            overflow: hidden;
        }

        /* Layout: Sidebar + Map */
        .tracking-container {
            display: flex;
            flex: 1;
            height: calc(100vh - 70px); /* Subtract navbar height */
            position: relative;
        }

        .sidebar {
            width: 350px;
            background: white;
            border-right: 1px solid #e5e7eb;
            overflow-y: auto;
            z-index: 10;
            padding: 1.5rem;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }

        #map {
            flex: 1;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .ride-item {
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            background: #f9fafb;
        }

        .ride-item:hover {
            border-color: var(--primary-teal);
            transform: translateX(2px);
            background: #fff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .ride-item.active {
            border-color: var(--primary-teal);
            background: #f0fdf4;
            border-width: 2px;
        }

        .driver-avatar {
            width: 40px;
            height: 40px;
            background: #e0f2f1;
            color: var(--primary-teal);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 0.8rem;
        }
        
        /* Map Controls Overlay */
        .map-overlay-card {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 300px;
            display: none;
        }

        /* Geocoding fallback: simple mapping for demo cities */
        /* In production, integrate Leaflet Control Geocoder or similar */
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo">ShareMyRide</a>
            <div class="nav-links" id="navLinks">
                <a href="dashboard.php">Dashboard</a>
                <a href="find_ride.php">Find Ride</a>
                <a href="offer_ride.php">Offer Ride</a>
                <a href="live_tracking.php" style="color: var(--primary-teal); font-weight: 700;">Live Tracking</a>
                <a href="logout.php" style="color: var(--error-red);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
            <button class="mobile-menu-toggle" onclick="document.getElementById('navLinks').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <div class="tracking-container">
        
        <!-- Sidebar List -->
        <div class="sidebar">
            <h2 style="margin-bottom: 1.5rem; font-size: 1.5rem;">Active Rides</h2>
            <div id="ridesList">
                <p style="color: #6b7280; text-align: center; margin-top: 2rem;">Loading active rides...</p>
            </div>
        </div>

        <!-- Map -->
        <div id="map"></div>

        <!-- Info Overlay -->
        <div id="mapOverlay" class="map-overlay-card">
            <h4 id="ovDriver" style="margin-bottom: 0.5rem; color: var(--dark-teal);">Driver Name</h4>
            <div style="font-size: 0.9rem; color: #4b5563; margin-bottom: 0.5rem;">
                <i class="fas fa-map-marker-alt" style="color: var(--primary-teal);"></i> <span id="ovFrom">Origin</span> <i class="fas fa-arrow-right"></i> <span id="ovTo">Dest</span>
            </div>
            <div class="status-badge" style="background:#d1fae5; color:#065f46; padding:0.2rem 0.6rem; border-radius:4px; font-size:0.8rem; display:inline-block;">Live Now</div>
        </div>

    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        // 1. Initialize Map
        // Center on Amal Jyothi College of Engineering (Kanjirapally)
        const campusCoords = [9.5293, 76.8221];
        
        const map = L.map('map', {
            zoomControl: false 
        }).setView(campusCoords, 11); 
        
        // Add Google Maps Tile Layer
        L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
            attribution: '&copy; Google Maps'
        }).addTo(map);

        // Add Zoom Control to bottom-right
        L.control.zoom({
            position: 'bottomright'
        }).addTo(map);

        // Campus Marker
        const campusIcon = L.divIcon({
            html: '<div style="font-size: 2.5rem; color: #ea4335; filter: drop-shadow(2px 2px 2px rgba(0,0,0,0.3));"><i class="fas fa-university"></i></div>',
            className: 'custom-div-icon',
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });
        L.marker(campusCoords, {icon: campusIcon}).addTo(map).bindPopup("<b>Amal Jyothi College of Engineering</b><br>Campus").openPopup();

        // Icons
        const carIcon = L.divIcon({
            html: '<div style="font-size: 2rem; color: var(--primary-teal); filter: drop-shadow(2px 2px 2px rgba(0,0,0,0.3));"><i class="fas fa-car-side"></i></div>',
            className: 'custom-div-icon',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        // Pre-populate cache with known locations for the demo to ensure 100% accuracy
        const geoCache = {
            'Amal Jyothi': [9.5293, 76.8221],
            'Amal Jyothi College': [9.5293, 76.8221],
            'Campus': [9.5293, 76.8221],
            'AJCE': [9.5293, 76.8221], 
            'Kanjirapally': [9.5546, 76.7865],
            'Kottayam': [9.5916, 76.5222],
            'Kochi': [9.9312, 76.2673],
            'Cochin': [9.9312, 76.2673],
            'Ernakulam': [9.9816, 76.2999],
            'Trivandrum': [8.5241, 76.9366],
            'Thiruvananthapuram': [8.5241, 76.9366],
            'Kollam': [8.8932, 76.6141],
            'Thrissur': [10.5276, 76.2144],
            'Pala': [9.7088, 76.6806],
            'Palai': [9.7088, 76.6806],
            'Mundakayam': [9.5833, 76.8833],
            'Ponkunnam': [9.5663, 76.7622],
            'Changanassery': [9.4424, 76.5402],
            'Alappuzha': [9.4981, 76.3388],
            'Alleppey': [9.4981, 76.3388]
        };

        const CAMPUS_COORDS = [9.5293, 76.8221];

        function normalizePlace(name) {
            if (!name) return '';
            const text = name.toLowerCase().replace(/\./g, '').trim(); // Remove dots, keep spaces
            
            // Specific variations for Amal Jyothi
            if (text.includes('amal jyothi') || text.includes('amaljyothi') || text === 'ajce' || text.includes('ajce campus')) {
                return 'Amal Jyothi';
            }
            // "Campus" might be ambiguous, but if user implies *the* campus:
            if (text === 'campus' || text === 'college') {
                return 'Amal Jyothi'; 
            }
            
            return name; // Return original if no special match
        }

        async function getCoordinates(placeName) {
            // 1. Normalize name to handle typos/variations
            const normalizedName = normalizePlace(placeName);

            // 2. Check if normalized name is the campus
            if (normalizedName === 'Amal Jyothi') {
                return CAMPUS_COORDS;
            }

            // 3. Check cache (using both original and normalized keys if possible, but mainly original for exact matches)
            // We iterate keys to do a case-insensitive match for cache hits
            const cacheKey = Object.keys(geoCache).find(key => key.toLowerCase() === placeName.toLowerCase());
            if (cacheKey) return geoCache[cacheKey];

            try {
                // 4. API Call
                const query = `${placeName}, Kerala, India`; 
                const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;
                
                const response = await fetch(url);
                const data = await response.json();

                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    const coords = [lat, lon];
                    
                    // Save to local cache to save future requests
                    geoCache[placeName] = coords;
                    return coords;
                }
            } catch (e) {
                console.warn("Geocoding failed for", placeName, e);
            }
            
            // 5. Hard Fallback (Use map center or alert, don't use Kanjirapally silently)
            // Returning NULL allows us to handle the error in UI
            console.error("Could not locate:", placeName);
            return null; 
        }

        let currentMarkers = [];
        let currentRoute = null;

        // 2. Load Active Rides
        async function loadActiveRides() {
            const listContainer = document.getElementById('ridesList');
            try {
                // Fetch all rides
                const response = await fetch('api_rides.php?type='); 
                const data = await response.json();

                if(data.success && data.rides.length > 0) {
                    listContainer.innerHTML = '';
                    data.rides.forEach(ride => {
                        listContainer.innerHTML += `
                            <div class="ride-item" onclick="focusRide(${ride.ride_id}, '${ride.from_location}', '${ride.to_location}', '${ride.driver_name}')" id="ride-${ride.ride_id}">
                                <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                    <div class="driver-avatar"><i class="fas fa-user"></i></div>
                                    <div>
                                        <div style="font-weight: 700; color: #1f2937;">${ride.driver_name}</div>
                                        <div style="font-size: 0.8rem; color: #6b7280;">${ride.vehicle_type}</div>
                                    </div>
                                </div>
                                <div style="font-size: 0.95rem; font-weight: 600; color: var(--dark-teal); margin-bottom: 0.3rem;">
                                    ${ride.from_location} → ${ride.to_location}
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #6b7280;">
                                    <span><i class="fas fa-calendar"></i> ${ride.ride_date}</span>
                                    <span><i class="fas fa-clock"></i> ${ride.ride_time.substring(0,5)}</span>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    listContainer.innerHTML = '<p style="text-align:center; padding: 2rem;">No active rides found.</p>';
                }
            } catch(e) {
                console.error(e);
                listContainer.innerHTML = '<p style="color:red; text-align:center;">Error loading rides.</p>';
            }
        }

        // 3. Focus Map on Ride with Dynamic Geocoding
        window.focusRide = async function(id, from, to, driver) {
            // Highlight list item
            document.querySelectorAll('.ride-item').forEach(el => el.classList.remove('active'));
            const activeItem = document.getElementById(`ride-${id}`);
            if(activeItem) {
                activeItem.classList.add('active');
                activeItem.innerHTML += '<div style="font-size:0.8rem; color:var(--primary-teal); margin-top:5px;"><i class="fas fa-spinner fa-spin"></i> Locating path...</div>';
            }

            // Clear map
            if(currentRoute) map.removeLayer(currentRoute);
            currentMarkers.forEach(m => map.removeLayer(m));
            currentMarkers = [];

            // Get Real Coordinates
            const start = await getCoordinates(from);
            const end = await getCoordinates(to);
            
            // Remove loading spinner
            if(activeItem) {
                const spinner = activeItem.querySelector('.fa-spinner').parentNode;
                if(spinner) spinner.remove();
            }

            // Draw Markers
            const m1 = L.marker(start).addTo(map).bindPopup(`<b>Start:</b> ${from}`);
            const m2 = L.marker(end).addTo(map).bindPopup(`<b>End:</b> ${to}`);
            
            // Draw Car (Simulate at start)
            const car = L.marker(start, {icon: carIcon, zIndexOffset: 1000}).addTo(map);

            currentMarkers = [m1, m2, car];

            // Draw Route Line
            currentRoute = L.polyline([start, end], {
                color: '#10b981',
                weight: 5,
                opacity: 0.8,
                dashArray: '10, 10'
            }).addTo(map);

            // Fit Bounds
            map.fitBounds(currentRoute.getBounds(), {padding: [50, 50]});

            // Update Overlay
            document.getElementById('mapOverlay').style.display = 'block';
            document.getElementById('ovDriver').innerText = driver;
            document.getElementById('ovFrom').innerText = from;
            document.getElementById('ovTo').innerText = to;

            // ANIMATE CAR
            animateCar(car, start, end);
        }

        function animateCar(marker, start, end) {
            let progress = 0;
            const speed = 0.005; // speed factor
            
            // Clear any existing animation on this marker context if strictly needed, 
            // but for simplicity we rely on map clearing in focusRide.
            
            function tick() {
                // Check if marker is still on map (in case user switched rides)
                if(!map.hasLayer(marker)) return; 

                progress += speed;
                if(progress > 1) progress = 0; // Loop animation

                const lat = start[0] + (end[0] - start[0]) * progress;
                const lng = start[1] + (end[1] - start[1]) * progress;
                marker.setLatLng([lat, lng]);
                
                requestAnimationFrame(tick);
            }
            tick();
        }

        // Init
        loadActiveRides();

    </script>
</body>
</html>
