<?php
require_once 'db_connect.php';

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Access Session Data
$userName = ucwords(strtolower($_SESSION['username'] ?? 'User'));
$userEmail = $_SESSION['email'] ?? 'user@example.com';
$userFiles = $_SESSION['profile_pic'] ?? null;

// Ensure we fetch the latest profile pic and rating from DB
$stmt = $conn->prepare("SELECT profile_pic, rating FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
$u = $res->fetch_assoc();

$profilePic = null;
if (!empty($u['profile_pic'])) {
    if (filter_var($u['profile_pic'], FILTER_VALIDATE_URL)) {
        $profilePic = $u['profile_pic'];
    } elseif (file_exists($u['profile_pic'])) {
        $profilePic = $u['profile_pic'];
    }
}

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
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
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

    <div class="container" style="padding: 6rem 0 3rem;">
        
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
                        
                        <div style="display: flex; gap: 10px;">
                             <!-- Toggle Buttons -->
                             <button id="btnViewActive" onclick="switchView('active')" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Active</button>
                             <button id="btnViewHistory" onclick="switchView('history')" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.9rem;">History</button>
                             <a href="offer_ride.php" class="btn btn-outline" style="border-color: var(--primary-teal); color: var(--primary-teal); padding: 0.5rem 1rem;"><i class="fas fa-plus"></i></a>
                        </div>
                    </div>
                    
                    <!-- Active Rides List -->
                    <div id="myRidesList">
                        <p style="color: var(--text-gray); text-align: center; padding: 2rem;">Loading rides...</p>
                    </div>

                    <!-- History (Completed/Deleted) List -->
                     <div id="myHistoryList" style="display: none;">
                        <p style="color: var(--text-gray); text-align: center; padding: 2rem;">Loading history...</p>
                    </div>
                </div>

                <!-- Sidebar: Requests & Bookings -->
                <div class="search-card" style="margin-bottom: 2rem; border-left: 4px solid var(--accent-yellow);" id="incomingSection">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2 style="color: var(--text-dark); margin:0; font-size:1.3rem;">Passenger Requests</h2>
                        <div style="display: flex; gap: 8px;">
                             <button id="btnReqPending" onclick="switchRequestView('pending')" class="btn btn-primary" style="padding: 0.35rem 0.8rem; font-size: 0.85rem;">Pending</button>
                             <button id="btnReqHistory" onclick="switchRequestView('history')" class="btn btn-outline" style="padding: 0.35rem 0.8rem; font-size: 0.85rem;">History</button>
                        </div>
                    </div>
                    
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

    <!-- Image Preview Modal (Structured Card) -->
    <div id="imageModal" class="modal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: hidden; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(3px); align-items: center; justify-content: center;">
        
        <!-- Modal Card -->
        <div style="background: white; width: 650px; max-width: 95%; border-radius: 16px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); position: relative; display: flex; flex-direction: column;">
            
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600; color: #111827;">ID Proof Viewer</h3>
                <button onclick="document.getElementById('imageModal').style.display='none'" style="background: none; border: none; font-size: 1.5rem; color: #6b7280; cursor: pointer; padding: 0;">&times;</button>
            </div>
            
            <!-- Landscape Viewer Container -->
            <div style="width: 100%; height: 360px; background: #1f2937; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative; margin-bottom: 16px;">
                <img id="modalImage" style="max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.3s ease;">
            </div>
            
            <!-- Footer controls -->
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: #6b7280;">Use rotation if image is sideways</span>
                <div>
                     <button onclick="rotateImage()" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.9rem; margin-right: 8px; border-color: #cbd5e1; color: #334155;">
                        <i class="fas fa-redo"></i> Rotate
                     </button>
                     <button onclick="document.getElementById('imageModal').style.display='none'" class="btn btn-primary" style="padding: 0.5rem 1.5rem; font-size: 0.9rem;">Done</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage of existing JS for API calls -->
    <script src="js/ride_manager.js?v=<?php echo time(); ?>"></script>
    <script>
        let currentRotation = 0;

        function openImageModal(src) {
            const modal = document.getElementById('imageModal');
            const img = document.getElementById('modalImage');
            
            // Reset state
            currentRotation = 0;
            img.style.transform = `rotate(0deg)`;
            
            img.src = src;
            modal.style.display = "flex"; // Flex ensures centering
        }
        
        function rotateImage() {
            const img = document.getElementById('modalImage');
            currentRotation += 90;
            img.style.transform = `rotate(${currentRotation}deg)`;
        }
        
        // Safe Event Listener for Modal
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        });

        // Init Dashboard with Real API logic
        (async function() {
            try {
                if (typeof RideManager === 'undefined') {
                    console.error("RideManager is missing. Check js/ride_manager.js");
                    // System critical error, can't strictly use RideManager.showAlert if it's missing, but let's try or standard alert
                    alert("System Error: RideManager script not loaded. Please refresh."); 
                    return;
                }

                // 1. Load My Published Rides
                await loadMyRides();

                // 2. Load My Bookings (Outgoing Requests)
                await loadMyBookings();

                // 3. Load Incoming Requests (If I am a driver)
                await loadIncomingRequests('pending');

            } catch (err) {
                console.error("Dashboard Init Error:", err);
            }
        })();

        async function loadMyBookings() {
             const container = document.getElementById('myBookingsList');
             const ratingsContainer = document.getElementById('pendingRatingsList');
             const ratingsSection = document.getElementById('pendingRatingsSection');
             
             if(!container) return;

             try {
                // Clear loading state immediately
                container.innerHTML = '<div style="text-align:center; padding:1rem; color:#9ca3af;"><i class="fas fa-spinner fa-spin"></i> Checking bookings...</div>';

                // Fetch outgoing requests (my bookings)
                const response = await fetch('api_requests.php?type=outgoing');
                const data = await response.json();

                container.innerHTML = ''; // Clear spinner
                ratingsContainer.innerHTML = '';
                let hasPendingRatings = false;

                if (data.success && data.requests.length > 0) {
                    let bookingsHtml = '';
                    let ratingsHtml = '';
                    
                    data.requests.forEach(req => {
                        let statusBadge = '';
                        let actionButtons = '';
                        
                        // Normalizing status for logic
                        const isRideFinished = (req.ride_status === 'completed');
                        const isMyReqFinished = (req.status === 'completed');
                        const isRated = (parseInt(req.has_rated) > 0);
                        
                        // DEBUG INFO (Remove in production)
                        const debugInfo = `<div style="font-size:0.7rem; color:#9ca3af; margin-top:5px;">Ref: #${req.request_id} | St: ${req.status} | RSt: ${req.ride_status || 'act'}</div>`;

                        // 1. Status Badge
                        if(req.status === 'pending') {
                            statusBadge = '<span class="trip-badge" style="background:#fef3c7; color:#d97706;">Pending</span>';
                        } else if(req.status === 'rejected') {
                            statusBadge = '<span class="trip-badge" style="background:#fee2e2; color:#b91c1c;">Rejected</span>';
                        } else if(isRideFinished || isMyReqFinished) {
                             statusBadge = '<span class="trip-badge" style="background:#d1fae5; color:#065f46;">Completed</span>';
                        } else if(req.status === 'accepted') {
                             statusBadge = '<span class="trip-badge" style="background:#d1fae5; color:#065f46;">Accepted</span>';
                        } else {
                             statusBadge = `<span class="trip-badge" style="background:#f3f4f6; color:#374151;">${req.status}</span>`;
                        }

                        // 2. Action Buttons
                        if (isRated) {
                            actionButtons = '<div style="margin-top:5px; font-size:0.8rem; color: #10b981;"><i class="fas fa-check"></i> Already Rated</div>';
                        } else {
                             // Corrected Logic: Priority to Rating if finished
                             if (isRideFinished || isMyReqFinished) {
                                actionButtons = `<div style="margin-top:5px;">
                                    <a href="rate_ride.php?request_id=${req.request_id}" class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.9rem;">Rate Driver <i class="fas fa-star"></i></a>
                                </div>`;
                             } else if (req.status === 'accepted') {
                                actionButtons = `
                                    <div style="margin-top:5px; display:flex; flex-direction:column; align-items:flex-end; gap:5px;">
                                        <a href="ride_details.php?id=${req.ride_id}" style="font-size:0.8rem; color:var(--primary-teal);">Track Ride</a>
                                        <button onclick="confirmArrival(${req.request_id})" class="btn btn-outline" style="padding: 0.3rem 0.8rem; font-size: 0.8rem; border-color: var(--primary-teal); color: var(--primary-teal);">
                                            <i class="fas fa-check-circle"></i> End Trip & Rate
                                        </button>
                                    </div>
                                `;
                             }
                        }

                        bookingsHtml += `
                        <div class="trip-card" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 1rem;">
                            <div>
                                 <h3 style="font-size: 1.1rem; color: var(--dark-teal);">${req.from_location} → ${req.to_location}</h3>
                                 <p style="color: var(--text-gray); font-size: 0.9rem;">${req.ride_date} • Funding Driver: ${req.driver_name}</p>
                                 ${debugInfo} 
                            </div>
                            <div style="text-align:right;">
                                ${statusBadge}
                                ${actionButtons}
                            </div>
                        </div>`;
                    });

                    container.innerHTML = bookingsHtml || '<p style="color: var(--text-gray);">No active bookings history.</p>';
                    ratingsSection.style.display = 'none'; // Replaced by inline buttons

                } else {
                    container.innerHTML = '<p style="color: var(--text-gray);">No bookings yet.</p>';
                }
            } catch (e) {
                console.error(e);
                container.innerHTML = '<p style="color: var(--error-red);">Error loading bookings.</p>';
            }
        }

        // View Toggle Logic
        let currentView = 'active';

        function switchView(view) {
             currentView = view;
             
             const btnActive = document.getElementById('btnViewActive');
             const btnHistory = document.getElementById('btnViewHistory');
             const listActive = document.getElementById('myRidesList');
             const listHistory = document.getElementById('myHistoryList');

             if (view === 'active') {
                 // Update Buttons
                 btnActive.className = 'btn btn-primary';
                 btnHistory.className = 'btn btn-outline';
                 
                 // Show Active List
                 listActive.style.display = 'block';
                 listHistory.style.display = 'none';
                 
                 loadMyRides(); // Refresh Active
             } else {
                 // Update Buttons
                 btnActive.className = 'btn btn-outline';
                 btnHistory.className = 'btn btn-primary';
                 
                 // Show History List
                 listActive.style.display = 'none';
                 listHistory.style.display = 'block';
                 
                 loadMyHistory(); // Load History
             }
        }

        async function loadMyHistory() {
             const container = document.getElementById('myHistoryList');
             if (!container) return;
             
             container.innerHTML = '<p class="text-center" style="color: var(--text-gray); padding: 2rem;">Loading history...</p>';

             try {
                // Fetch completed and cancelled
                const rides = await RideManager.getAllRides({ driver_id: 'me', status: 'completed,cancelled' });
                
                container.innerHTML = ''; // Clear loading
                
                if (!rides || rides.length === 0) {
                     container.innerHTML = '<p class="text-center" style="color: var(--text-gray); padding: 2rem;">No history found.</p>';
                     return;
                }
                
                // Reuse render logic or similar
                renderRideList(rides, container, true);
             
             } catch (e) {
                 console.error("Error loading history:", e);
                 container.innerHTML = '<p style="color: var(--error-red); text-align: center;">Error loading history.</p>';
             }
        }

        async function loadMyRides() {
            const container = document.getElementById('myRidesList');
            if (!container) return;

            try {
                // Filter by 'me' and ACTIVE status
                const rides = await RideManager.getAllRides({ driver_id: 'me', status: 'active' }); 
                container.innerHTML = '';
                
                if (!rides || rides.length === 0) {
                     container.innerHTML = '<p class="text-center" style="color: var(--text-gray); padding: 2rem;">You haven\'t published any active rides.</p>';
                     return;
                }
                
                renderRideList(rides, container, false);

            } catch (e) {
                console.error("Error loading rides:", e);
                container.innerHTML = '<p style="color: var(--error-red); text-align: center;">Error loading rides. Please refresh.</p>';
            }
        }

        // Shared Render Logic
        function renderRideList(rides, container, isHistory) {
             rides.forEach(ride => {
                    let statusColor = '#10b981'; 
                    if(ride.seats_available == 0) statusColor = '#ef4444'; 
                    
                    let activeActions = '';
                    let statusLabel = '';

                    // 1. Badge Logic
                    if (ride.status === 'active') {
                        statusLabel = `<span class="trip-badge" style="background:${statusColor}20; color:${statusColor}">${ride.seats_available} seats left</span>`;
                    } else if (ride.status === 'completed') {
                        statusLabel = `<span class="trip-badge" style="background:#d1fae5; color:#065f46;">Completed</span>`;
                    } else if (ride.status === 'cancelled') {
                        statusLabel = `<span class="trip-badge" style="background:#fee2e2; color:#b91c1c;">Cancelled</span>`;
                    }

                    // 2. Button Logic
                    if (!isHistory) {
                         // Active Actions
                        activeActions = `
                            <button onclick="completeRide(${ride.ride_id})" class="btn btn-outline" style="border-color: #059669; color: #059669; padding: 0.5rem; margin-right:5px;" title="Mark as Completed"><i class="fas fa-check"></i></button>
                            <a href="edit_ride.php?id=${ride.ride_id}" class="btn btn-outline" style="border-color: var(--primary-teal); color: var(--primary-teal); padding: 0.5rem; margin-right: 5px;" title="Edit Ride">
                                     <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="deleteRide(${ride.ride_id})" class="btn btn-outline" style="border-color: var(--error-red); color: var(--error-red); padding: 0.5rem;" title="Cancel Ride">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                    } else {
                        // History Actions (Maybe delete permanently? Or just view?)
                        // For now just View
                         activeActions = `
                            <button disabled class="btn btn-outline" style="border-color: #ccc; color: #ccc; padding: 0.5rem; cursor: not-allowed;" title="Archived">
                                <i class="fas fa-archive"></i>
                            </button>
                        `;
                    }

                    // Title Logic (Long Trip vs Regular)
                    let displayTitle = `${ride.from_location} → ${ride.to_location}`;
                    let subTitle = '';
                    let typeBadge = '';

                    if (ride.ride_type === 'long') {
                        displayTitle = ride.details || 'Long Trip';
                        subTitle = `<div style="font-size: 0.95rem; color: var(--primary-teal); margin-bottom: 0.25rem;"><i class="fas fa-route"></i> ${ride.from_location} → ${ride.to_location}</div>`;
                        typeBadge = `<span class="trip-badge" style="background: var(--dark-teal); color: white; margin-right: 5px;">Long Trip</span>`;
                    }

                    container.innerHTML += `
                        <div class="trip-card" style="margin-bottom: 1rem; border: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; opacity: ${isHistory ? 0.8 : 1}; background: ${isHistory ? '#f9fafb' : 'white'};">
                            <div>
                                <h3 style="font-size: 1.2rem; color: var(--dark-teal); margin-bottom: 0.5rem;">${displayTitle}</h3>
                                ${subTitle}
                                <div style="color: var(--text-gray); font-size: 0.9rem;">
                                    <i class="fas fa-calendar"></i> ${ride.ride_date} • <i class="fas fa-clock"></i> ${ride.ride_time}
                                </div>
                                <div style="margin-top: 0.5rem;">
                                    ${typeBadge}
                                    ${statusLabel}
                                    <span class="trip-badge" style="background: #f3f4f6; color: var(--text-dark);">₹${ride.price_per_seat}</span>
                                </div>
                            </div>
                            <div>
                                ${activeActions}
                            </div>
                        </div>
                    `;
                });
        }

        async function completeRide(rideId) {
            const confirmed = await RideManager.showConfirm("Complete Ride?", "Are you sure you want to mark this ride as completed?");
            if(!confirmed) return;
            try {
                const res = await fetch('api_rides.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'complete', ride_id: rideId })
                });
                const data = await res.json();
                if(data.success) {
                    await RideManager.showAlert('Ride Completed', data.message, 'success');
                    loadMyRides(); // Refresh the list
                } else {
                    await RideManager.showAlert('Error', data.message, 'error');
                }
            } catch(e) { console.error(e); }
        }
        
        // ... ...

        async function confirmArrival(reqId) {
            const confirmed = await RideManager.showConfirm("Confirm Arrival?", "Have you arrived safely? This will complete the trip and allow you to rate the driver.");
            if(!confirmed) return;
            try {
                const res = await fetch('api_requests.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'complete_passenger', request_id: reqId })
                });
                const data = await res.json();
                if(data.success) {
                    // Redirect directly to rating
                    window.location.href = `rate_ride.php?request_id=${reqId}`;
                } else {
                    await RideManager.showAlert('Error', data.message, 'error');
                }
            } catch(e) { console.error(e); }
        }

        let currentRequestView = 'pending';

        function switchRequestView(view) {
            currentRequestView = view;
            
            const btnPending = document.getElementById('btnReqPending');
            const btnHistory = document.getElementById('btnReqHistory');
            
            if(view === 'pending') {
                 btnPending.className = 'btn btn-primary';
                 btnHistory.className = 'btn btn-outline';
            } else {
                 btnPending.className = 'btn btn-outline';
                 btnHistory.className = 'btn btn-primary';
            }
            
            loadIncomingRequests(view);
        }

        async function loadIncomingRequests(filter = 'pending') {
            const container = document.getElementById('incomingRequestsList'); 
            const section = document.getElementById('incomingSection');
            if(!container) return; 
            
            container.innerHTML = '<div style="text-align:center; padding:1rem; color:#9ca3af;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            // If checking pending initially, we might hide the section if empty. 
            // BUT if we have buttons now, maybe we should always show the section if the user is a driver?
            // Since we don't know if they are a driver easily without checking rides, let's just show it if data comes back.
            // Actually, best to just keep it hidden until we find at least one request (pending OR history).
            // But checking history everytime on load might be overkill.
            // Let's stick to: Show section if we have results.
            
            try {
                const response = await fetch(`api_requests.php?type=incoming&filter=${filter}`);
                const data = await response.json();
                
                // Clear previous content
                container.innerHTML = '';

                if (data.success && data.requests.length > 0) {
                    let html = '';
                    data.requests.forEach(req => {
                        let proofDisplay = '';
                        if (req.proof_image) {
                            proofDisplay = `
                            <button onclick="openImageModal('${req.proof_image}')" class="btn btn-outline" style="padding: 0.4rem 1rem; font-size: 0.8rem; height: auto; display: inline-flex; align-items: center; border-color: #94a3b8; color: #475569; margin-right: 0.5rem;">
                                <i class="fas fa-file-invoice" style="margin-right:0.3rem;"></i> View Proof
                            </button>`;
                        }

                        const rating = req.rating ? parseFloat(req.rating).toFixed(1) : 'New';
                        const ratingColor = (rating !== 'New' && rating >= 4.0) ? '#eab308' : '#6b7280';
                        
                        let profileImgHtml = `<div style="width: 40px; height: 40px; background: #e0e7ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #4f46e5; margin-right: 0.75rem;">
                                                ${req.passenger_name.charAt(0).toUpperCase()}
                                              </div>`;
                                              
                        if (req.profile_pic) {
                             profileImgHtml = `<img src="${req.profile_pic}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 0.75rem;">`;
                        }

                        // Actions vs Status
                        let actionArea = '';
                        if (filter === 'pending') {
                            actionArea = `
                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center; margin-top: 0.5rem;">
                                ${proofDisplay}
                                <button onclick="handleRequest('reject', ${req.request_id})" class="btn btn-outline" style="padding: 0.4rem 1rem; font-size: 0.9rem; color: var(--error-red); border-color: var(--error-red);">Reject</button>
                                <button onclick="handleRequest('accept', ${req.request_id})" class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.9rem;">Accept</button>
                            </div>`;
                        } else {
                            // History View
                            let statusBadge = '';
                            if(req.status === 'accepted' || req.status === 'completed') statusBadge = '<span class="trip-badge" style="background:#d1fae5; color:#065f46;">Accepted</span>';
                            else if(req.status === 'rejected') statusBadge = '<span class="trip-badge" style="background:#fee2e2; color:#b91c1c;">Rejected</span>';
                            else statusBadge = `<span class="trip-badge">${req.status}</span>`;

                            actionArea = `
                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center; margin-top: 0.5rem;">
                                ${proofDisplay}
                                ${statusBadge}
                            </div>`;
                        }
                       
                         html += `
                        <div class="trip-card" style="margin-bottom: 1rem; background: #fff; border: 1px solid #e0e7ff; padding: 1rem; border-radius: 8px;">
                            <div style="display: flex; align-items: center; margin-bottom: 0.75rem;">
                                ${profileImgHtml}
                                <div>
                                    <div style="font-weight: 700; color:var(--text-dark); display: flex; align-items: center; gap: 0.5rem;">
                                        ${req.passenger_name}
                                        <span class="trip-badge" style="background: #f3f4f6; color: ${ratingColor}; font-size: 0.75rem; padding: 2px 6px;">
                                            <i class="fas fa-star" style="font-size: 0.7rem;"></i> ${rating}
                                        </span>
                                    </div>
                                    <div style="color: var(--text-gray); font-size: 0.85rem;">
                                        Wants <strong>${req.seats_requested}</strong> seat(s) for this trip
                                    </div>
                                </div>
                            </div>
                            
                            <div style="background: #f8fafc; padding: 0.5rem; border-radius: 6px; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.75rem;">
                                <i class="fas fa-route" style="color: var(--primary-teal);"></i> Trip: <strong>${req.from_location}</strong> to <strong>${req.to_location}</strong>
                            </div>
                            
                            ${actionArea}
                        </div>`;
                    });
                    container.innerHTML = html;
                    section.style.display = 'block';
                } else {
                    if (filter === 'pending') {
                        // Only hide if pending is empty AND we haven't manually switched?
                        // Actually, if pending is empty, we might still want to see history.
                        // But for now, let's show a message if manually switched, or hide if initial load.
                        container.innerHTML = '<p style="color: var(--text-gray); text-align: center; padding: 1rem;">No pending requests.</p>';
                        // We keep display block so user can click History
                         section.style.display = 'block'; 
                    } else {
                         container.innerHTML = '<p style="color: var(--text-gray); text-align: center; padding: 1rem;">No request history.</p>';
                    }
                }
            } catch (e) { console.error(e); }
        }

        async function handleRequest(action, reqId) {
            const confirmed = await RideManager.showConfirm(`${action.charAt(0).toUpperCase() + action.slice(1)} Request?`, `Are you sure you want to ${action} this request?`);
            if(!confirmed) return;

            try {
                const response = await fetch('api_requests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: action, request_id: reqId })
                });
                const res = await response.json();
                if(res.success) {
                    await RideManager.showAlert('Success', res.message, 'success');
                    loadIncomingRequests('pending'); // Refresh (default filter)
                } else {
                    await RideManager.showAlert('Error', res.message, 'error');
                }
            } catch(e) { console.error(e); }
        }
        
        async function deleteRide(id) {
            const confirmed = await RideManager.showConfirm("Delete Ride?", "Are you sure you want to delete this ride? This cannot be undone.");
            if(!confirmed) return;
            
            try {
                const res = await fetch('api_rides.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'delete', ride_id: id })
                });
                const data = await res.json();
                if(data.success) {
                    // Remove from UI immediately or reload
                    loadMyRides();
                } else {
                    await RideManager.showAlert('Error', data.message, 'error');
                }
            } catch(e) { console.error(e); }
        }

        function toggleMobileMenu() {
            document.getElementById('navLinks').classList.toggle('show');
        }
    </script>
</body>
</html>
