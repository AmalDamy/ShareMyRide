<?php require_once 'db_connect.php'; ?>
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
            position: relative;
            min-height: 0; /* Important for flex children with overflow */
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

    <?php include 'navbar.php'; ?>
    <?php include 'sub_navbar.php'; ?>


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
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1rem;">
                <div class="status-badge" style="background:#d1fae5; color:#065f46; padding:0.2rem 0.6rem; border-radius:4px; font-size:0.8rem; display:inline-block;"><i class="fas fa-broadcast-tower fa-beat" style="margin-right:4px;"></i> Live Now</div>
                <div id="ovEta" style="font-size: 0.8rem; font-weight: 600; color: #64748b;">ETA: --</div>
            </div>
            
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #e2e8f0;">
                <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; margin-bottom: 0.3rem;">Current Location / Action</div>
                <div id="ovStatus" style="font-size: 1rem; font-weight: 600; color: #1e293b; line-height: 1.3;"><i class="fas fa-spinner fa-spin"></i> Locating vehicle...</div>
                
                <!-- Progress bar -->
                <div style="margin-top: 0.8rem; width: 100%; height: 6px; background: #e2e8f0; border-radius: 10px; overflow: hidden;">
                    <div id="ovProgress" style="width: 0%; height: 100%; background: var(--primary-teal); transition: width 0.3s ease;"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <!-- Leaflet Routing Machine -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>

    <script>
        // 1. Initialize Map
        const campusCoords = [9.5293, 76.8221];
        
        const map = L.map('map', {
            zoomControl: false 
        }).setView(campusCoords, 11); 
        
        L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
            attribution: '&copy; Google Maps'
        }).addTo(map);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

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
            'AJCE': [9.5293, 76.8221],
            'Kanjirapally': [9.5546, 76.7865],
            'Kanjirappally': [9.5546, 76.7865],
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
            'Mundakkayam': [9.5833, 76.8833],
            'Ponkunnam': [9.5663, 76.7622],
            'Changanassery': [9.4424, 76.5402],
            'Alappuzha': [9.4981, 76.3388],
            'Alleppey': [9.4981, 76.3388],
            '26 Mile': [9.5480, 76.8050], // Adjusted to be closer to Kanjirapally NH183
            '26th Mile': [9.5480, 76.8050],
            '26mile': [9.5480, 76.8050],
            'Erumeli': [9.4827, 76.8369],
            'Ranni': [9.3800, 76.7800],
            'Pambadv': [9.5600, 76.6500],
            'Pampady': [9.5600, 76.6500],
            'Koovappally': [9.5400, 76.8000],
            'Koovapally': [9.5400, 76.8000],
            '26 mile': [9.5480, 76.8050],
            'mundakkayam': [9.5833, 76.8833]
        };

        const CAMPUS_COORDS = [9.5293, 76.8221];

        function normalizePlace(name) {
            if (!name) return '';
            let text = name.toLowerCase().replace(/[.,]/g, '').trim(); // Remove dots/commas
            if (text.includes('amal jyothi') || text.includes('ajce') || text === 'campus') return 'Amal Jyothi';
            if (text.includes('26') && text.includes('mile')) return '26 Mile';
            return name;
        }

        async function getCoordinates(placeName) {
            const normalizedName = normalizePlace(placeName);
            
            // Check cache case-insensitive
            const cacheKey = Object.keys(geoCache).find(key => key.toLowerCase() === normalizedName.toLowerCase() || key.toLowerCase() === placeName.toLowerCase());
            if (cacheKey) return geoCache[cacheKey];

            try {
                // Try very specific search for Kottayam District first
                let query = `${placeName}, Kottayam District, Kerala`; 
                let response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`);
                let data = await response.json();

                if (data && data.length > 0) {
                    const coords = [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                    geoCache[placeName] = coords;
                    return coords;
                }

                // Retry with broader search (All India)
                query = `${placeName}, India`;
                response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`);
                data = await response.json();

                if (data && data.length > 0) {
                    const coords = [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                    geoCache[placeName] = coords;
                    return coords;
                }
                
                // Final generic retry (Global)
                query = placeName;
                response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`);
                data = await response.json();

                if (data && data.length > 0) {
                    const coords = [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                    geoCache[placeName] = coords;
                    return coords;
                }

            } catch (e) {
                console.warn("Geocoding failed for", placeName, e);
            }
            
            // If all searches fail, return null (Control flow handles alert)
            return null; 
        }

        let routingControl = null;
        let carMarker = null;

        async function loadActiveRides() {
            const listContainer = document.getElementById('ridesList');
            try {
                const response = await fetch('api_rides.php'); 
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
                            </div>
                        `;
                    });
                } else {
                    listContainer.innerHTML = '<p style="text-align:center; padding: 2rem;">No active rides found.</p>';
                }
            } catch(e) { console.error(e); }
        }

        window.focusRide = async function(id, from, to, driver) {
            // UI Update
            document.querySelectorAll('.ride-item').forEach(el => el.classList.remove('active'));
            const activeItem = document.getElementById(`ride-${id}`);
            if(activeItem) {
                activeItem.classList.add('active');
                activeItem.innerHTML += '<div class="loading-ind" style="font-size:0.8rem; color:var(--primary-teal);"><i class="fas fa-spinner fa-spin"></i> Calculating route...</div>';
            }

            // Cleanup
            if (routingControl) {
                map.removeControl(routingControl);
                routingControl = null;
            }
            if (carMarker) {
                map.removeLayer(carMarker);
                carMarker = null;
            }

            // Coordinates
            const start = await getCoordinates(from);
            const end = await getCoordinates(to);

            // Remove spinner
            if(activeItem) {
                const spinner = activeItem.querySelector('.loading-ind');
                if(spinner) spinner.remove();
            }

            if (!start || !end) {
                alert(`Could not locate one of the locations: ${!start ? from : to}. Please check the location names.`);
                return;
            }

            // Draw Route using Leaflet Routing Machine
            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(start[0], start[1]),
                    L.latLng(end[0], end[1])
                ],
                routeWhileDragging: false,
                addWaypoints: false,
                draggableWaypoints: false,
                fitSelectedRoutes: true,
                show: false, // Hide instructions
                lineOptions: {
                    styles: [{color: '#10b981', opacity: 0.8, weight: 6}]
                },
                createMarker: function(i, wp, nWps) {
                    // Custom Markers for Start/End
                    if (i === 0) {
                        return L.marker(wp.latLng).bindPopup(`<b>Start:</b> ${from}`).openPopup();
                    } else if (i === nWps - 1) {
                        return L.marker(wp.latLng).bindPopup(`<b>End:</b> ${to}`).openPopup();
                    }
                    return null;
                }
            }).addTo(map);

            // Handle Routing Error (Fallback to straight line)
            routingControl.on('routingerror', function(e) {
                console.warn('Routing failed', e);
                // Fallback: Straight line
                const routeLine = L.polyline([start, end], {color: '#10b981', weight: 6, opacity: 0.8, dashArray: '10,10'}).addTo(map);
                carMarker = L.marker(start, {icon: carIcon, zIndexOffset: 1000}).addTo(map);
                
                // Simple straight animation
                let progress = 0;
                function simpleTick() {
                    if (!carMarker) return;
                    progress += 0.002;
                    if(progress > 1) progress = 0;
                    const lat = start[0] + (end[0] - start[0]) * progress;
                    const lng = start[1] + (end[1] - start[1]) * progress;
                    carMarker.setLatLng([lat, lng]);
                    requestAnimationFrame(simpleTick);
                }
                simpleTick();
            });

            // Listen for route found to animate car
            routingControl.on('routesfound', function(e) {
                const routes = e.routes;
                
                // Update Overlay
                document.getElementById('mapOverlay').style.display = 'block';
                document.getElementById('ovDriver').innerText = driver;
                document.getElementById('ovFrom').innerText = from;
                document.getElementById('ovTo').innerText = to;

                // Animate Car along the detailed path coordinates
                const pathCoords = routes[0].coordinates; 
                const instructions = routes[0].instructions;
                const routeTimeMs = routes[0].summary && routes[0].summary.totalTime ? routes[0].summary.totalTime * 1000 : 150000;
                animateCarOnPath(pathCoords, instructions, routeTimeMs);
            });
        }

        function animateCarOnPath(coords, instructions, totalDuration) {
            if (!coords || coords.length === 0) return;
            
            if(carMarker) map.removeLayer(carMarker);
            carMarker = L.marker(coords[0], {icon: carIcon, zIndexOffset: 1000}).addTo(map);

            let index = 0;
            let lastTime = null;
            let currentInstructionIdx = 0;
            
            // Time it should take to move between 1 point to the next (using realistic route time)
            const timePerPoint = totalDuration / coords.length;

            const statusEl = document.getElementById('ovStatus');
            const progressEl = document.getElementById('ovProgress');
            const etaEl = document.getElementById('ovEta');

            function tick(timestamp) {
                if (!carMarker) return;
                if (!lastTime) lastTime = timestamp;

                const deltaTime = timestamp - lastTime;

                // Move forward if enough time has passed based on our calculated speed
                if (deltaTime >= timePerPoint) {
                    const pointsToMove = Math.floor(deltaTime / timePerPoint);
                    index += pointsToMove;
                    
                    if (index >= coords.length) {
                        index = 0; // Restart animation
                        currentInstructionIdx = 0;
                    }
                    
                    carMarker.setLatLng(coords[index]);

                    // Update Progress bar & ETA
                    const progressPercent = (index / coords.length) * 100;
                    if(progressEl) progressEl.style.width = progressPercent + '%';
                    
                    if(etaEl) {
                        const timeLeftMs = Math.max(0, totalDuration - (index * timePerPoint));
                        const minsLeft = Math.ceil(timeLeftMs / 60000);
                        if (minsLeft >= 60) {
                            const hrs = Math.floor(minsLeft / 60);
                            const mns = minsLeft % 60;
                            etaEl.innerText = `ETA: ${hrs} hr ${mns} min`;
                        } else {
                            etaEl.innerText = `ETA: ${minsLeft} min${minsLeft === 1 ? '' : 's'}`;
                        }
                    }

                    // Update Live Status based on current location/instruction mapping
                    if (statusEl && instructions && instructions.length > 0) {
                        // Advance instruction index if vehicle passed the waypoint
                        while (currentInstructionIdx < instructions.length - 1 && index >= instructions[currentInstructionIdx + 1].index) {
                            currentInstructionIdx++;
                        }
                        
                        let text = instructions[currentInstructionIdx].text;
                        if (text) {
                            // Make it sound continuous
                            if (text === 'Destination reached') text = 'Arriving at destination';
                            statusEl.innerHTML = `<i class="fas fa-location-arrow" style="color:var(--primary-teal); margin-right:4px;"></i> ${text}`;
                        }
                    }
                    
                    // Reset lastTime but keep remainder so we don't drift and lose speed accuracy
                    lastTime = timestamp - (deltaTime % timePerPoint);
                }
                
                requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
        }

        loadActiveRides();
    </script>
</body>
</html>
