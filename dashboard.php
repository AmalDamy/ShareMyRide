<?php
require_once 'db_connect.php';

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Access Session Data
$userName = $_SESSION['username'] ?? 'User';
$userEmail = $_SESSION['email'] ?? 'user@example.com';
$userFiles = $_SESSION['profile_pic'] ?? null;

// Ensure we fetch the latest profile pic from DB (Good practice)
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
$u = $res->fetch_assoc();
$profilePic = !empty($u['profile_pic']) && file_exists($u['profile_pic']) 
              ? $u['profile_pic'] 
              : null; // Null means we use the default icon

// Stats Calculation
// 1. Rides Offered (Count of all rides created by user)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM rides WHERE driver_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$ridesOffered = $stmt->get_result()->fetch_assoc()['count'];

// 2. Rides Taken (Count of accepted/completed requests)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM ride_requests WHERE passenger_id = ? AND status IN ('accepted', 'completed')");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$ridesTaken = $stmt->get_result()->fetch_assoc()['count'];

// 3. User Rating (From Users table)
$myRating = isset($u['rating']) ? number_format($u['rating'], 1) : "0.0";

// 4. CO2 Saved (Approximation: 2.5kg per ride taken)
$co2Saved = $ridesTaken * 2.5;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - ShareMyRide</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background-color: #f3f4f6;">

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo">ShareMyRide</a>
            <div class="nav-links" id="navLinks">
                <a href="dashboard.php" style="color: var(--primary-teal); font-weight: 700;">Dashboard</a>
                <a href="find_ride.php">Find Ride</a>
                <a href="offer_ride.php">Offer Ride</a>
                <a href="long_trip.php">Long Trip</a>
                <button onclick="window.location.href='logout.php'" class="btn btn-outline" style="padding: 0.5rem 1rem; border-color: var(--error-red); color: var(--error-red);">Logout</button>
            </div>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <div class="container" style="padding: 3rem 0;">
        
        <!-- Welcome Header -->
        <div style="background: linear-gradient(135deg, var(--dark-teal), var(--primary-teal)); color: white; padding: 3rem; border-radius: var(--radius-lg); margin-bottom: 3rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="margin-bottom: 0.5rem;">Welcome back, <span id="userName"><?php echo htmlspecialchars($userName); ?></span>! 👋</h1>
                <p style="opacity: 0.9;">Manage your rides and bookings here.</p>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 1rem 2rem; border-radius: var(--radius-md); text-align: center;">
                <div style="font-size: 2rem; font-weight: 700;"><?php echo $myRating; ?></div>
                <div style="font-size: 0.8rem;">Rating</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            
            <!-- Main Content: My RIdes -->
            <div>
                <div class="search-card" style="margin-bottom: 2rem; display: block;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2 style="color: var(--text-dark);">My Published Rides</h2>
                        <a href="offer_ride.php" class="btn btn-primary"><i class="fas fa-plus"></i> Offer New Ride</a>
                    </div>
                    
                    <div id="myRidesList">
                        <!-- Loaded dynamically -->
                        <p style="color: var(--text-gray); text-align: center; padding: 2rem;">Loading rides...</p>
                    </div>
                </div>

                <!-- Sidebar: Requests & Bookings -->
                <div class="search-card" style="display: none; margin-bottom: 2rem; border-left: 4px solid var(--accent-yellow);" id="incomingSection">
                    <h2 style="color: var(--text-dark); margin-bottom: 1.5rem;">Ride Requests <span class="trip-badge" style="background: var(--error-red); color: white; font-size: 0.8rem;">Action Required</span></h2>
                    <div id="incomingRequestsList">
                        <!-- Loaded dynamically -->
                    </div>
                </div>

                <!-- Pending Reviews Section -->
                <div class="search-card" style="display: none; margin-bottom: 2rem; border-left: 4px solid var(--primary-teal);" id="pendingRatingsSection">
                    <h2 style="color: var(--text-dark); margin-bottom: 1.5rem;">Pending Ratings <span class="trip-badge" style="background: var(--accent-yellow); color: white; font-size: 0.8rem;">Rate Your Driver</span></h2>
                    <div id="pendingRatingsList">
                        <!-- Loaded dynamically -->
                    </div>
                </div>

                <div class="search-card" style="display: block;">
                    <h2 style="color: var(--text-dark); margin-bottom: 1.5rem;">My Bookings</h2>
                    <div id="myBookingsList">
                         <p style="color: var(--text-gray); text-align: center;">Loading bookings...</p>
                    </div>
                </div>
            </div>

            <!-- Sidebar: Profile -->
            <div>
                <div class="search-card" style="display: block;">
                    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'profile_updated'): ?>
                        <div class="success-message" style="margin-bottom: 1.5rem; padding: 0.8rem; background: #d1fae5; color: #065f46; border-radius: 6px; font-size: 0.9rem;">
                            <i class="fas fa-check-circle"></i> Profile updated successfully!
                        </div>
                    <?php endif; ?>

                    <div style="text-align: center; margin-bottom: 2rem;">
                        <div style="width: 100px; height: 100px; background: #e5e7eb; border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; overflow:hidden;">
                            <?php if ($profilePic): ?>
                                <img src="<?php echo htmlspecialchars($profilePic); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user" style="font-size: 2.5rem; color: var(--text-gray);"></i>
                            <?php endif; ?>
                        </div>
                        <h3 id="profileName"><?php echo htmlspecialchars($userName); ?></h3>
                        <p id="profileEmail" style="color: var(--text-gray); margin-bottom: 1rem;"><?php echo htmlspecialchars($userEmail); ?></p>
                        <a href="edit_profile.php" class="btn btn-outline" style="font-size: 0.85rem; padding: 0.4rem 1rem;">Edit Profile</a>
                    </div>
                    
                    <div style="border-top: 1px solid #e5e7eb; padding-top: 1.5rem;">
                        <h4 style="margin-bottom: 1rem;">Stats</h4>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: var(--text-gray);">Rides Offered</span>
                            <span style="font-weight: 700;"><?php echo $ridesOffered; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: var(--text-gray);">Rides Taken</span>
                            <span style="font-weight: 700;"><?php echo $ridesTaken; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-gray);">CO2 Saved</span>
                            <span style="font-weight: 700; color: var(--success-green);"><?php echo $co2Saved; ?> kg</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Usage of existing JS for API calls -->
    <script src="js/ride_manager.js"></script>
    <script>
        // Init Dashboard with Real API logic
        (async function() {
            // 1. Load My Published Rides
            loadMyRides();

            // 2. Load My Bookings (Outgoing Requests)
            loadMyBookings();

            // 3. Load Incoming Requests (If I am a driver)
            loadIncomingRequests();
        })();

        async function loadMyBookings() {
             const container = document.getElementById('myBookingsList');
             const ratingsContainer = document.getElementById('pendingRatingsList');
             const ratingsSection = document.getElementById('pendingRatingsSection');
             
             if(!container) return;

             try {
                // Fetch outgoing requests (my bookings)
                const response = await fetch('api_requests.php?type=outgoing');
                const data = await response.json();

                container.innerHTML = '';
                ratingsContainer.innerHTML = '';
                let hasPendingRatings = false;

                if (data.success && data.requests.length > 0) {
                    let bookingsHtml = '';
                    let ratingsHtml = '';
                    
                    data.requests.forEach(req => {
                        // Check if completed and NOT rated
                        if (req.status === 'completed' && req.has_rated == 0) {
                            hasPendingRatings = true;
                            ratingsHtml += `
                            <div class="trip-card" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; border: 1px solid #d1fae5; background: #ecfdf5; padding: 1.5rem;">
                                <div>
                                     <h3 style="font-size: 1.1rem; color: var(--dark-teal); margin-bottom: 0.5rem;">Ride to ${req.to_location} Completed</h3>
                                     <p style="color: var(--text-gray); font-size: 0.9rem;">Driver: <strong>${req.driver_name}</strong> • ${req.ride_date}</p>
                                </div>
                                <div>
                                    <a href="rate_ride.php?ride_id=${req.ride_id}" class="btn btn-primary" style="padding: 0.5rem 1.5rem;">Rate Driver <i class="fas fa-star"></i></a>
                                </div>
                            </div>`;
                            // Continue to prevent adding to main list? 
                            // Actually, let's keep it in main list too, or remove it?
                            // User request implies "Completed" rides should appear.
                            // Let's NOT replicate it in the main list to separate "Todo" from "History".
                            return; 
                        }

                        // Regular Booking List
                        let statusBadge = '';
                        let actionButtons = '';
                        
                        if(req.status === 'pending') {
                            statusBadge = '<span class="trip-badge" style="background:#fef3c7; color:#d97706;">Pending</span>';
                        } else if(req.status === 'accepted') {
                            statusBadge = '<span class="trip-badge" style="background:#d1fae5; color:#065f46;">Accepted</span>';
                            actionButtons = `
                                <div style="margin-top:5px; display:flex; flex-direction:column; align-items:flex-end; gap:5px;">
                                    <a href="ride_details.php?id=${req.ride_id}" style="font-size:0.8rem; color:var(--primary-teal);">Track Ride</a>
                                    <button onclick="confirmArrival(${req.request_id})" class="btn btn-outline" style="padding: 2px 8px; font-size: 0.75rem; border-color: var(--primary-teal); color: var(--primary-teal);">
                                        <i class="fas fa-map-marker-alt"></i> I have Arrived
                                    </button>
                                </div>
                            `;
                        } else if(req.status === 'rejected') {
                            statusBadge = '<span class="trip-badge" style="background:#fee2e2; color:#b91c1c;">Rejected</span>';
                        } else if(req.status === 'completed') {
                            statusBadge = '<span class="trip-badge" style="background:#f3f4f6; color:#374151;">Completed</span>';
                            if(req.has_rated == 1) {
                                actionButtons = '<div style="margin-top:5px; font-size:0.8rem; color: #10b981;"><i class="fas fa-check"></i> Rated</div>';
                            }
                        } else {
                            statusBadge = `<span class="trip-badge" style="background:#f3f4f6; color:#374151;">${req.status}</span>`;
                        }

                        bookingsHtml += `
                        <div class="trip-card" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 1rem;">
                            <div>
                                 <h3 style="font-size: 1.1rem; color: var(--dark-teal);">${req.from_location} → ${req.to_location}</h3>
                                 <p style="color: var(--text-gray); font-size: 0.9rem;">${req.ride_date} • Funding Driver: ${req.driver_name}</p>
                            </div>
                            <div style="text-align:right;">
                                ${statusBadge}
                                ${actionButtons}
                            </div>
                        </div>`;
                    });

                    // (The rest of the function remains the same, just closing the loop logic modification)
                    container.innerHTML = bookingsHtml || '<p style="color: var(--text-gray);">No active bookings history.</p>';
                    
                    if(hasPendingRatings) {
                        ratingsSection.style.display = 'block';
                        ratingsContainer.innerHTML = ratingsHtml;
                    } else {
                         ratingsSection.style.display = 'block';
                         ratingsContainer.innerHTML = '<p style="color: var(--text-gray); font-style: italic; font-size: 0.9rem;">No pending ratings. Completed rides will appear here.</p>';
                    }

                } else {
                    container.innerHTML = '<p style="color: var(--text-gray);">No bookings yet.</p>';
                    ratingsSection.style.display = 'block';
                    ratingsContainer.innerHTML = '<p style="color: var(--text-gray); font-style: italic; font-size: 0.9rem;">No pending ratings.</p>';
                }
            } catch (e) {
                console.error(e);
                container.innerHTML = '<p style="color: var(--error-red);">Error loading bookings.</p>';
            }
        }

        async function loadMyRides() {
            // ... (keep existing)
            // Filter by 'me'
            const rides = await RideManager.getAllRides({ driver_id: 'me' }); 
            const container = document.getElementById('myRidesList');
            container.innerHTML = '';
            
            if (rides.length === 0) {
                 container.innerHTML = '<p class="text-center" style="color: var(--text-gray);">You haven\'t offered any rides yet.</p>';
                 return;
            }
            
            rides.forEach(ride => {
                let statusColor = '#10b981'; 
                if(ride.seats_available == 0) statusColor = '#ef4444'; 
                
                // Complete Button Logic
                let actionBtn = '';
                if(ride.status === 'active') {
                    actionBtn = `<button onclick="completeRide(${ride.ride_id})" class="btn btn-outline" style="border-color: #059669; color: #059669; padding: 0.5rem; margin-right:5px;" title="Mark as Completed"><i class="fas fa-check"></i></button>`;
                } else if(ride.status === 'completed') {
                    actionBtn = `<span class="trip-badge" style="background:#d1fae5; color:#065f46; margin-right:5px;">Completed</span>`;
                }

                container.innerHTML += `
                    <div class="trip-card" style="margin-bottom: 1rem; border: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; padding: 1.5rem;">
                        <div>
                            <h3 style="font-size: 1.2rem; color: var(--dark-teal); margin-bottom: 0.5rem;">${ride.from_location} → ${ride.to_location}</h3>
                            <div style="color: var(--text-gray); font-size: 0.9rem;">
                                <i class="fas fa-calendar"></i> ${ride.ride_date} • <i class="fas fa-clock"></i> ${ride.ride_time}
                            </div>
                            <div style="margin-top: 0.5rem;">
                                <span class="trip-badge" style="background:${statusColor}20; color:${statusColor}">${ride.seats_available} seats left</span>
                                <span class="trip-badge" style="background: #f3f4f6; color: var(--text-dark);">₹${ride.price_per_seat}</span>
                            </div>
                        </div>
                        <div>
                            ${actionBtn}
                            <button onclick="deleteRide(${ride.ride_id})" class="btn btn-outline" style="border-color: var(--error-red); color: var(--error-red); padding: 0.5rem;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
        }

        async function completeRide(id) {
            if(!confirm("Mark this ride as completed? This will allow passengers to rate you.")) return;
            try {
                const res = await fetch('api_rides.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'complete', ride_id: id })
                });
                const data = await res.json();
                if(data.success) {
                    loadMyRides();
                } else {
                    alert(data.message);
                }
            } catch(e) { console.error(e); }
        }

        async function confirmArrival(reqId) {
            if(!confirm("Confirm you have arrived? This will mark the ride as completed for you and allow you to rate the driver.")) return;
            try {
                const res = await fetch('api_requests.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'complete_passenger', request_id: reqId })
                });
                const data = await res.json();
                if(data.success) {
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            } catch(e) { console.error(e); }
        }

        async function loadIncomingRequests() {
            const container = document.getElementById('incomingRequestsList'); 
            if(!container) return; 

            try {
                const response = await fetch('api_requests.php?type=incoming');
                const data = await response.json();
                
                // Clear previous content
                container.innerHTML = '';
                const section = document.getElementById('incomingSection');

                if (data.success && data.requests.length > 0) {
                    let html = '';
                    data.requests.forEach(req => {
                         html += `
                        <div class="trip-card" style="margin-bottom: 1rem; background: #fff; border: 1px solid #e0e7ff; padding: 1rem; border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <div>
                                    <span style="font-weight: 700; color:var(--text-dark);"><i class="fas fa-user"></i> ${req.passenger_name}</span>
                                    <span style="color: var(--text-gray); font-size: 0.9rem; margin-left: 10px;">requested ${req.seats_requested} seat(s)</span>
                                </div>
                                <div style="font-size:0.85rem; color:var(--text-gray);">${req.from_location} → ${req.to_location}</div>
                            </div>
                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <button onclick="handleRequest('reject', ${req.request_id})" class="btn btn-outline" style="padding: 0.4rem 1rem; font-size: 0.9rem; color: var(--error-red); border-color: var(--error-red);">Reject</button>
                                <button onclick="handleRequest('accept', ${req.request_id})" class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.9rem;">Accept</button>
                            </div>
                        </div>`;
                    });
                    container.innerHTML = html;
                    section.style.display = 'block';
                } else {
                    // Hide section if no incoming requests to avoid clutter
                    section.style.display = 'none'; 
                    // container.innerHTML = '<p style="color: var(--text-gray);">No new requests.</p>'; // Clean fallback if we wanted to keep it visible
                }
            } catch (e) { console.error(e); }
        }

        async function handleRequest(action, reqId) {
            if(!confirm(`Are you sure you want to ${action} this request?`)) return;

            try {
                const response = await fetch('api_requests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: action, request_id: reqId })
                });
                const res = await response.json();
                if(res.success) {
                    alert(res.message);
                    loadIncomingRequests(); // Refresh
                } else {
                    alert(res.message);
                }
            } catch(e) { console.error(e); }
        }
        
        function deleteRide(id) {
            if(confirm('Are you sure you want to cancel this ride?')) {
                alert('Ride cancellation API to be implemented.');
            }
        }

        function toggleMobileMenu() {
            document.getElementById('navLinks').classList.toggle('show');
        }
    </script>
</body>
</html>
