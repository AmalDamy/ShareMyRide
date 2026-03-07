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
    <!-- Razorpay -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>if(!window.Razorpay) document.write('<script src="rzp_sdk.js"><\/script>');</script>
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
                <a href="live_tracking.php" style="font-weight: 700;"><i class="fas fa-location-arrow"></i> Live Tracking</a>
                <a href="fuel_calculator.php">Fuel Calculator</a>
                <a href="contact.php">Contact</a>
                <a href="logout.php" style="color: var(--error-red); font-weight: 600;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <div class="container" style="padding-top: 6rem;">
        
        <!-- Search Bar (Compact) -->
        <div class="search-card" style="padding: 1rem 1.5rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm); border: 1px solid #eee;">
            <form action="find_ride.php" method="GET" style="display: flex; gap: 1rem; width: 100%; align-items: flex-end;" onsubmit="return validateSearch()">
                <div class="form-group">
                    <label>From</label>
                    <input type="text" name="from" id="searchFrom" class="form-input" placeholder="Leaving from..." value="<?php echo isset($_GET['from']) ? htmlspecialchars($_GET['from']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>To</label>
                    <input type="text" name="to" id="searchTo" class="form-input" placeholder="Going to..." value="<?php echo isset($_GET['to']) ? htmlspecialchars($_GET['to']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" id="searchDate" class="form-input" value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>">
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



    <!-- Request Ride Modal -->
    <div id="requestModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px); animation: fadeIn 0.3s;">
        <div class="modal-content" style="background-color: #fefefe; margin: 2rem auto; padding: 0; border: none; width: 95%; max-width: 450px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden;">
            
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
                    
                    <div style="display: flex; gap: 1rem; margin-bottom: 1.25rem; background: #f8fafc; padding: 1rem; border-radius: 12px;">
                        <div style="flex: 1; border-right: 1px dashed #e2e8f0; padding-right: 1rem;">
                            <div id="mFrom" style="font-weight: 700; font-size: 1rem; color: var(--text-dark);">...</div>
                            <div style="margin: 4px 0; color: #94a3b8;"><i class="fas fa-arrow-down" style="font-size: 0.8rem;"></i></div>
                            <div id="mTo" style="font-weight: 700; font-size: 1rem; color: var(--text-dark);">...</div>
                            <div id="mTime" style="color: var(--text-gray); font-size: 0.8rem; margin-top: 6px;">...</div>
                        </div>
                        <div style="text-align: right; min-width: 80px; display: flex; flex-direction: column; justify-content: center;">
                             <div id="mPrice" style="font-size: 1.25rem; font-weight: 800; color: var(--dark-teal);">...</div>
                             <div style="font-size: 0.75rem; color: var(--text-gray);">per seat</div>
                        </div>
                    </div>

                    <!-- Custom Route Section -->
                    <div style="background: #eff6ff; padding: 0.75rem; border-radius: 12px; margin-bottom: 1rem; border: 1px solid #dbeafe;">
                        <h4 style="margin: 0 0 8px 0; font-size: 0.85rem; color: #1e40af; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="fas fa-magic"></i> Customize Pickup/Drop
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <div class="form-group" style="margin:0;">
                                <input type="text" id="mCustomPickup" class="form-input" style="font-size: 0.85rem; padding: 8px; height: 36px;" placeholder="My Pickup" oninput="recalcPrice()">
                            </div>
                            <div class="form-group" style="margin:0;">
                                <input type="text" id="mCustomDrop" class="form-input" style="font-size: 0.85rem; padding: 8px; height: 36px;" placeholder="My Dropoff" oninput="recalcPrice()">
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                            <div id="priceAdjustmentMsg" style="font-size: 0.75rem; color: #059669; font-weight: 600; display: none;">
                                <i class="fas fa-tag"></i> Discount applied!
                            </div>
                            <div id="finalPriceDisplay" style="font-size: 1rem; font-weight: 800; color: #1e40af; margin-left: auto;">
                                Total: ₹<span id="txtFinalPrice">0</span>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: stretch;">
                        <div style="flex: 2; background: #f9fafb; padding: 0.75rem; border-radius: 12px; display: flex; align-items: center; gap: 0.75rem; border: 1px solid #f1f5f9;">
                            <div style="width: 32px; height: 32px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas fa-user-tie" style="color: #64748b; font-size: 0.9rem;"></i>
                            </div>
                            <div style="overflow: hidden;">
                                <div id="mDriver" style="font-weight: 600; color: var(--text-dark); font-size: 0.85rem; white-space: nowrap; text-overflow: ellipsis;">...</div>
                                <div id="mVehicle" style="font-size: 0.75rem; color: var(--text-gray); white-space: nowrap; text-overflow: ellipsis;">...</div>
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <select id="mSeats" class="form-input" style="height: 100%; font-size: 0.85rem; padding: 8px; border-radius: 12px; background: #f8fafc;">
                                <option value="1">1 Seat</option>
                                <option value="2">2 Seats</option>
                                <option value="3">3 Seats</option>
                            </select>
                        </div>
                    </div>

                    <!-- Hidden inputs for backward compatibility/internal tracking if needed, though we extract from OCR now -->
                    <input type="hidden" id="mIdType" value="Aadhar Card">
                    <input type="hidden" id="mIdNumber" value="">

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <div class="upload-zone" id="uploadZone" onclick="document.getElementById('mProof').click()" style="border: 1px dashed #cbd5e1; border-radius: 12px; padding: 1rem; text-align: center; cursor: pointer; transition: all 0.3s; background: #f8fafc;">
                            <i class="fas fa-id-card" style="font-size: 1.2rem; color: var(--primary-teal); margin-bottom: 4px;"></i>
                            <p id="uploadText" style="margin: 0; color: #64748b; font-weight: 600; font-size: 0.85rem;">Upload Aadhar (Govt. Certified) *</p>
                            <div id="fileSelectedName" style="margin-top: 4px; font-weight: 600; color: var(--primary-teal); display: none; font-size: 0.75rem;"></div>
                        </div>
                        <input type="file" id="mProof" class="form-input" accept="image/*,.pdf" style="display: none;" onchange="handleFileSelect(this)">
                    </div>

                    <?php if (empty($_SESSION['phone'])): ?>
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label style="font-size: 0.8rem; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">
                            <i class="fas fa-phone-alt"></i> Verify Phone Number
                        </label>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span style="background: #f1f5f9; padding: 10px; border-radius: 8px; font-weight: 700; border: 1px solid #ddd; color: #64748b; font-size: 0.85rem;">+91</span>
                            <input type="tel" id="mPhone" class="form-input" placeholder="9876543210" style="flex: 1; padding: 10px; font-size: 0.85rem; height: auto;" maxlength="10" pattern="[0-9]{10}">
                        </div>
                        <p style="font-size: 0.7rem; color: #94a3b8; margin-top: 4px;">For SMS updates and secure payment saving.</p>
                    </div>
                    <?php else: ?>
                    <input type="hidden" id="mPhone" value="<?php echo htmlspecialchars($_SESSION['phone']); ?>">
                    <?php endif; ?>

                    <div id="modalMsg"></div>

                    <!-- Integrated Payment Options -->
                    <style>
                        .payment-method-container {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: 1rem;
                            margin-top: 0.5rem;
                        }
                        .pay-method-card {
                            border: 2px solid #e2e8f0;
                            border-radius: 12px;
                            padding: 1rem;
                            cursor: pointer;
                            transition: all 0.3s ease;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            gap: 0.5rem;
                            background: white;
                            text-align: center;
                        }
                        .pay-method-card i {
                            font-size: 1.5rem;
                            color: #64748b;
                            transition: all 0.3s ease;
                        }
                        .pay-method-card span {
                            font-size: 0.85rem;
                            font-weight: 600;
                            color: #475569;
                        }
                        .pay-method-card:hover {
                            border-color: #0d9488;
                            background: #f0fdfa;
                        }
                        .pay-method-card.active {
                            border-color: #0d9488;
                            background: #f0fdfa;
                            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.15);
                        }
                        .pay-method-card.active i {
                            color: #0d9488;
                            transform: scale(1.1);
                        }
                        .pay-method-card.active span {
                            color: #134e4a;
                        }
                    </style>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <div class="payment-method-container">
                        <div class="pay-method-card active" onclick="selectPayMethod('razorpay_card', this)" style="padding: 0.75rem; border-radius: 14px;">
                            <i class="fas fa-credit-card" style="font-size: 1.2rem;"></i>
                            <span style="font-size: 0.8rem;">Pay Now</span>
                            <input type="radio" name="paymentMethod" value="razorpay_card" checked style="display:none;">
                        </div>
                        <div class="pay-method-card" onclick="selectPayMethod('cash', this)" style="padding: 0.75rem; border-radius: 14px;">
                            <i class="fas fa-clock" style="font-size: 1.2rem;"></i>
                            <span style="font-size: 0.8rem;">Pay Later</span>
                            <input type="radio" name="paymentMethod" value="cash" style="display:none;">
                        </div>
                    </div>
                </div>

                    <script>
                        function selectPayMethod(val, el) {
                            document.querySelectorAll('.pay-method-card').forEach(c => c.classList.remove('active'));
                            el.classList.add('active');
                            el.querySelector('input').checked = true;
                        }
                    </script>

                    <!-- Payment Done Step -->
                    <style>
                        @keyframes success-pop {
                            0% { transform: scale(0.5); opacity: 0; }
                            70% { transform: scale(1.1); }
                            100% { transform: scale(1); opacity: 1; }
                        }
                        @keyframes shine {
                            0% { left: -100%; }
                            20% { left: 100%; }
                            100% { left: 100%; }
                        }
                        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
                        @keyframes scaleUp { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
                        @keyframes ping { 75%, 100% { transform: scale(1.5); opacity: 0; } }
                        .success-anim { animation: success-pop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
                        .btn-shiny {
                            position: relative;
                            overflow: hidden;
                            background: linear-gradient(135deg, #0d9488, #0f766e);
                            border: none;
                            transition: all 0.3s ease;
                        }
                        .btn-shiny::after {
                            content: '';
                            position: absolute;
                            top: -50%;
                            left: -100%;
                            width: 50%;
                            height: 200%;
                            background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%);
                            transform: rotate(30deg);
                            animation: shine 3s infinite;
                        }
                        .btn-shiny:hover {
                            transform: translateY(-3px);
                            box-shadow: 0 10px 20px rgba(13, 148, 136, 0.3);
                        }
                    </style>
                    <div id="paymentDoneStep" style="display:none; animation: scaleUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);">
                        <div style="text-align:center; padding: 1rem 0;">
                            <div style="position: relative; width: 100px; height: 100px; margin: 0 auto 1.25rem;">
                                <div id="payDoneIconDiv" class="success-anim" style="width:100%; height:100%; background:linear-gradient(135deg,#10b981,#059669); border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow: 0 20px 40px rgba(16, 185, 129, 0.25); position: relative; z-index: 2;">
                                    <i id="payDoneIcon" class="fas fa-check" style="font-size:3rem; color:white;"></i>
                                </div>
                                <div id="successPing" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 4px solid #10b981; border-radius: 50%; animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite; opacity: 0.4;"></div>
                            </div>
                            
                            <div id="bookingBadge" style="display: inline-block; padding: 4px 12px; background: #ecfdf5; color: #065f46; border-radius: 50px; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                <i id="badgeIcon" class="fas fa-magic"></i> <span id="badgeText">Confirmation Secured</span>
                            </div>

                            <h2 id="payDoneTitle" style="color:#134e4a; margin-bottom:0.75rem; font-weight: 900; font-size: 1.75rem; letter-spacing: -0.5px;">Success! 🎉</h2>
                            
                            <div style="background: #f8fafc; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid #f1f5f9;">
                                <p id="payDoneMsg" style="color:#475569; font-size:0.9rem; margin-bottom:0; line-height: 1.5;"></p>
                                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px dotted #e2e8f0; color: #94a3b8; font-size: 0.7rem;">
                                    <i class="fas fa-shield-alt"></i> Safe & Secure Booking
                                </div>
                            </div>

                            <div id="paymentResultButtons">
                                <button id="btnResultPrimary" onclick="window.location.href='dashboard.php'" class="btn btn-primary btn-shiny" style="width:100%; padding: 1rem; font-size: 1rem; border-radius: 14px; font-weight: 700; box-shadow: 0 8px 20px rgba(13, 148, 136, 0.3);">
                                    <i class="fas fa-location-arrow" style="margin-right: 8px;"></i> View in Dashboard
                                </button>
                                <button id="btnResultRetry" onclick="retryPayment()" class="btn btn-outline" style="width:100%; margin-top: 10px; display:none; border-radius: 14px; padding: 0.75rem;">
                                    <i class="fas fa-redo-alt"></i> Try Payment Again
                                </button>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem;" id="confirmButtons">
                        <button onclick="closeRequestModal()" class="btn btn-outline" style="flex: 1;">Cancel</button>
                        <button onclick="submitRequest()" id="btnConfirm" class="btn btn-primary" style="flex: 2;">Confirm Request</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/tesseract.js@v4.0.2/dist/tesseract.min.js"></script>
    <script src="js/ride_manager.js"></script>
    <script>
        function showPaymentResult(type, title, message) {
            document.getElementById('confirmButtons').style.display = 'none';
            document.getElementById('modalMsg').style.display = 'none';
            
            document.querySelectorAll('#modalContent > div:not(#paymentDoneStep)').forEach(el => el.style.display = 'none');
            document.querySelectorAll('#modalContent > .form-group').forEach(el => el.style.display = 'none');
            
            const iconDiv = document.getElementById('payDoneIconDiv');
            const icon = document.getElementById('payDoneIcon');
            const titleEl = document.getElementById('payDoneTitle');
            const msgEl = document.getElementById('payDoneMsg');
            const badge = document.getElementById('bookingBadge');
            const badgeText = document.getElementById('badgeText');
            const badgeIcon = document.getElementById('badgeIcon');
            const retryBtn = document.getElementById('btnResultRetry');
            
            const modalContent = document.querySelector('.modal-content');
            modalContent.style.maxWidth = '400px'; 
            
            iconDiv.classList.remove('success-anim');
            void iconDiv.offsetWidth; 
            iconDiv.classList.add('success-anim');

            retryBtn.style.display = 'none';

            if (type === 'success') {
                iconDiv.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                icon.className = 'fas fa-check';
                titleEl.style.color = '#134e4a';
                badge.style.background = '#ecfdf5';
                badge.style.color = '#065f46';
                badgeText.textContent = 'Confirmation Secured';
                badgeIcon.className = 'fas fa-magic';
                try { confettiEffect(); } catch(e) {}
            } else if (type === 'warning') {
                iconDiv.style.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
                icon.className = 'fas fa-exclamation-triangle';
                titleEl.style.color = '#92400e';
                badge.style.background = '#fffbeb';
                badge.style.color = '#92400e';
                badgeText.textContent = 'Action Required';
                badgeIcon.className = 'fas fa-info-circle';
            } else {
                iconDiv.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
                icon.className = 'fas fa-times';
                titleEl.style.color = '#7f1d1d';
                badge.style.background = '#fef2f2';
                badge.style.color = '#991b1b';
                badgeText.textContent = 'Payment Stopped';
                badgeIcon.className = 'fas fa-shield-alt';
                retryBtn.style.display = 'block';
            }
            
            titleEl.textContent = title;
            msgEl.textContent = message;
            
            document.getElementById('paymentDoneStep').style.display = 'block';
        }

        function retryPayment() {
            // Re-hide results and show loader
            document.getElementById('paymentDoneStep').style.display = 'none';
            document.getElementById('confirmButtons').style.display = 'flex';
            document.getElementById('modalMsg').style.display = 'block';
            
            // Restore form layout
            document.querySelectorAll('#modalContent > div:not(#paymentDoneStep)').forEach(el => el.style.display = '');
            document.querySelectorAll('#modalContent > .form-group').forEach(el => el.style.display = '');
            const modalContent = document.querySelector('.modal-content');
            modalContent.style.maxWidth = '450px'; 
            
            // Restart payment flow if we already have a request ID
            if (pendingRequestId) {
                const pMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
                if (pMethod.startsWith('razorpay')) {
                    const methodType = pMethod === 'razorpay_upi' ? 'upi' : 'card';
                    triggerRazorpay(methodType);
                }
            }
        }

        function confettiEffect() {
            const container = document.getElementById('requestModal');
            for(let i=0; i<30; i++) {
                const conf = document.createElement('div');
                conf.style.position = 'absolute';
                conf.style.width = '8px';
                conf.style.height = '8px';
                conf.style.backgroundColor = ['#10b981','#f59e0b','#4f46e5','#ef4444'][Math.floor(Math.random()*4)];
                conf.style.left = '50%';
                conf.style.top = '40%';
                conf.style.zIndex = '1000';
                conf.style.borderRadius = '2px';
                
                const angle = Math.random() * Math.PI * 2;
                const velocity = 5 + Math.random() * 10;
                const vx = Math.cos(angle) * velocity;
                const vy = Math.sin(angle) * velocity - 5;
                
                container.appendChild(conf);
                
                let x = 50, y = 40, opacity = 1;
                const interval = setInterval(() => {
                    x += vx;
                    y += vy + (1/opacity); // gravity
                    opacity -= 0.02;
                    conf.style.left = `calc(50% + ${x}px)`;
                    conf.style.top = `calc(40% + ${y}px)`;
                    conf.style.opacity = opacity;
                    if(opacity <= 0) {
                        clearInterval(interval);
                        conf.remove();
                    }
                }, 20);
            }
        }

        // Tesseract Worker (Global)
        // Note: Initializing worker may take a moment.
        
        const currentUserName = "<?php echo isset($_SESSION['username']) ? addslashes($_SESSION['username']) : ''; ?>";

        function toggleMobileMenu() {
            document.getElementById('navLinks').classList.toggle('show');
        }

        function handleFileSelect(input) {
            const fileName = input.files[0] ? input.files[0].name : '';
            const display = document.getElementById('fileSelectedName');
            const zone = document.getElementById('uploadZone');
            
            if (fileName) {
                display.innerText = "Selected: " + fileName;
                display.style.display = 'block';
                zone.style.borderColor = 'var(--primary-teal)';
                zone.style.background = '#f0fdfa';
            } else {
                display.style.display = 'none';
                zone.style.borderColor = '#cbd5e1';
                zone.style.background = '#f8fafc';
            }
        }
        
        async function verifyDocumentContent(file, idType, typedIdNumber = '') {
             // Helper: Load Image
             const loadImage = (src) => new Promise((resolve, reject) => {
                 const img = new Image();
                 img.onload = () => resolve(img);
                 img.onerror = reject;
                 img.src = src;
             });

             // Helper: Preprocess Image (Enhanced for OCR)
             const preprocessImage = (img) => {
                 const MAX_DIM = 2000;
                 let width = img.width;
                 let height = img.height;
                 
                 if (width > height) {
                     if (width < 1200) { height *= 1200/width; width = 1200; }
                 } else {
                     if (height < 1200) { width *= 1200/height; height = 1200; }
                 }

                 const canvas = document.createElement('canvas');
                 canvas.width = width;
                 canvas.height = height;
                 const ctx = canvas.getContext('2d');
                 
                 // Try to improve contrast for Tesseract
                 ctx.fillStyle = 'white';
                 ctx.fillRect(0, 0, width, height);
                 ctx.filter = 'grayscale(100%) contrast(150%) brightness(110%)';
                 ctx.drawImage(img, 0, 0, width, height);
                 return canvas;
             };

             // Helper: Get Rotated Data URL
             const getRotatedImage = (sourceCanvas, angle) => {
                 const canvas = document.createElement('canvas');
                 const ctx = canvas.getContext('2d');
                 
                 if (angle === 90 || angle === 270) {
                     canvas.width = sourceCanvas.height;
                     canvas.height = sourceCanvas.width;
                 } else {
                     canvas.width = sourceCanvas.width;
                     canvas.height = sourceCanvas.height;
                 }
                 
                 ctx.translate(canvas.width / 2, canvas.height / 2);
                 ctx.rotate(angle * Math.PI / 180);
                 ctx.drawImage(sourceCanvas, -sourceCanvas.width / 2, -sourceCanvas.height / 2);
                 return canvas.toDataURL('image/jpeg');
             };

             // Helper: Scan Text
             const scan = async (imageSource) => {
                 const worker = await Tesseract.createWorker({ logger: m => {} });
                 await worker.loadLanguage('eng');
                 await worker.initialize('eng');
                 const { data: { text } } = await worker.recognize(imageSource);
                 await worker.terminate();
                 console.log("OCR Raw Output:", text); 
                 return text.toLowerCase();
             };

             // Helper: Validate Text
             const validate = (text) => {
                 // Expanded Keyword List (Including common OCR errors)
                 const keywords = [
                     'uidai', 'aadhaar', 'unique identification', 'government of india', 
                     'mera aadhaar', 'pehchan', 'male', 'female', 'india', 'address',
                     'dob', 'yob', 'year of birth', 'enrolment', 'authority', 'vid',
                     'adhar', 'adhar card', 'government', 'govt', 'issued by', 'india.gov.in',
                     's/o', 'd/o', 'w/o', 'father', 'birth'
                 ];
                 
                 // Identify matched words
                 const matchedWords = keywords.filter(k => text.includes(k));
                 
                 // 1. Check for 12-digit number (Most critical part of Aadhar)
                 // Clean common digit misreads first
                 let cleanedText = text.replace(/o|O/g, '0').replace(/l|i|I|\|/g, '1').replace(/s|S/g, '5');
                 
                 // Pattern for 12 digits (with spaces or continuous)
                 const aadharRegex = /\b\d{4}\s\d{4}\s\d{4}\b|\b\d{12}\b/;
                 const aadharMatch = cleanedText.match(aadharRegex);
                 let extractedNumber = '';
                 
                 if (aadharMatch) {
                     extractedNumber = aadharMatch[0].replace(/\s/g, '');
                 } else {
                     // Try to find any 12 digits in sequence even if they have weird characters between them
                     const allDigits = cleanedText.replace(/\D/g, '');
                     if (allDigits.length >= 12) {
                         const match = allDigits.match(/[2-9]\d{11}/);
                         if (match) extractedNumber = match[0];
                     }
                 }

                 // SUCCESS CONDITION:
                 // Either we find a 12-digit number AND at least one keyword
                 // OR we find at least TWO keywords (trusting document type even if number is blurry)
                 const hasNumber = extractedNumber.length === 12;
                 const hasStrongKeywords = matchedWords.length >= 1;
                 
                 if (!hasNumber && matchedWords.length < 2) {
                     return { 
                         valid: false, 
                         reason: "Document does not look like a Government-certified Aadhar Card. Please ensure the card belongs to you and the image is clear." 
                     };
                 }

                 // 2. ID NUMBER MISMATCH (If number found but is invalid)
                 if (!hasNumber) {
                     return { 
                         valid: false, 
                         reason: "Verification Failed: Could not clearly read the 12-digit Aadhar number. Please ensure the image is sharp and well-lit." 
                     };
                 }

                 // 3. Name Verification: Skipped — the driver will verify the card in person.

                 return { valid: true, id_number: extractedNumber };
             }

             // Main Logic
             try {
                 const imageUrl = URL.createObjectURL(file);
                 const img = await loadImage(imageUrl);
                 
                 // 0. Preprocess
                 const processedCanvas = preprocessImage(img);
                 const processedDataUrl = processedCanvas.toDataURL('image/jpeg');

                 // Try different rotations
                 let results = [];
                 
                 // 1. Original
                 console.log("Scanning original...");
                 let text = await scan(processedDataUrl);
                 let res = validate(text);
                 if (res.valid) return res;
                 results.push(res);

                 // 2. Rotate 90
                 console.log("Scanning 90deg...");
                 let rot90 = getRotatedImage(processedCanvas, 90);
                 text = await scan(rot90);
                 res = validate(text);
                 if (res.valid) return res;
                 results.push(res);

                 // 3. Rotate 270
                 console.log("Scanning 270deg...");
                 let rot270 = getRotatedImage(processedCanvas, 270);
                 text = await scan(rot270);
                 res = validate(text);
                 if (res.valid) return res;
                 results.push(res);

                 // If all rotations failed, return general fail
                 
                 return { valid: false, reason: "Verification Failed: Document not recognized as Aadhar. Please upload a clear photo of the FRONT side of your original Aadhar card." };
                 
             } catch(e) {
                 console.error("OCR Error:", e);
                 return { valid: false, reason: "Error processing image: " + e.message };
             }
         }

        // Auto-Format ID Number Input (Add spaces every 4 digits)
        const idInput = document.getElementById('mIdNumber');
        if (idInput) {
            idInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                if (value.length > 12) value = value.substring(0, 12); // Limit to 12 digits
                
                // Add spaces: 1234 5678 1234
                let formatted = value.replace(/(\d{4})(?=\d)/g, '$1 ');
                e.target.value = formatted;
            });
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

            // Real-time Search Listeners with Debounce
            const inputs = ['searchFrom', 'searchTo', 'searchDate'];
            let debounceTimer;

            inputs.forEach(id => {
                document.getElementById(id).addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                         // Validate first
                        const isValid = validateSearch(false);
                        const fromVal = document.getElementById('searchFrom').value.trim();
                        const toVal = document.getElementById('searchTo').value.trim();
                        
                        // If invalid (e.g. numbers only), we should probably clear results or show specific error state
                        // But validateSearch returns false if regex fails.
                        
                        if(isValid) {
                            const dateVal = document.getElementById('searchDate').value;
                            renderRides(fromVal, toVal, dateVal);
                        } else {
                            // If invalid input (like numbers), show empty/error state
                             // specific check for number-only spam
                             const locationRegex = /[a-zA-Z]/;
                             if ( (fromVal && !locationRegex.test(fromVal)) || (toVal && !locationRegex.test(toVal)) ) {
                                 const container = document.getElementById('ridesGrid');
                                 container.innerHTML = `
                                    <div style="text-align: center; padding: 4rem;">
                                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #f59e0b; margin-bottom: 1rem;"></i>
                                        <p style="color: var(--text-gray);">Please enter a valid location (must contain letters).</p>
                                    </div>`;
                             }
                        }
                    }, 300); // 300ms delay
                });
            });
        });

        function validateSearch(showAlerts = true) {
            const fromInput = document.getElementById('searchFrom');
            const toInput = document.getElementById('searchTo');
            const dateInput = document.getElementById('searchDate');

            const from = fromInput.value.trim();
            const to = toInput.value.trim();
            const date = dateInput.value;
            
            // Allow empty for "show all" logic, but if typed, check regex
            const locationRegex = /[a-zA-Z]/;
            let valid = true;

            // Reset classes
            fromInput.classList.remove('input-error');
            toInput.classList.remove('input-error');
            dateInput.classList.remove('input-error');

            // Only validate formatting if something is typed
            if (from && !locationRegex.test(from)) {
                if(showAlerts) alert("Please enter a valid starting location (must contain letters).");
                fromInput.classList.add('input-error');
                valid = false;
            }
            if (to && !locationRegex.test(to)) {
                 if(showAlerts) alert("Please enter a valid destination (must contain letters).");
                 toInput.classList.add('input-error');
                 valid = false;
            }

            if (from && to && from.toLowerCase() === to.toLowerCase()) {
                if(showAlerts) alert("Pickup and Destination cannot be the same!");
                fromInput.classList.add('input-error');
                toInput.classList.add('input-error');
                valid = false;
            }
            
            if (date) {
                const selected = new Date(date);
                const today = new Date();
                today.setHours(0,0,0,0);
                if (selected < today) {
                    if(showAlerts) alert("Please select a valid date (today or future).");
                    dateInput.classList.add('input-error');
                    valid = false;
                }
            }
            return valid;
        }

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
                                    <a href="view_profile.php?id=${ride.driver_id}" style="text-decoration: none; color: var(--text-dark); font-weight: 700;">${ride.driver_name}</a>
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

        let calculatedPrice = 0;

        function recalcPrice() {
            if (!currentRide) return;
            
            const seats = parseInt(document.getElementById('mSeats').value) || 1;
            const basePrice = parseFloat(currentRide.price_per_seat);
            
            const origFrom = currentRide.from_location.trim().toLowerCase();
            const origTo = currentRide.to_location.trim().toLowerCase();
            
            const myFrom = document.getElementById('mCustomPickup').value.trim().toLowerCase();
            const myTo = document.getElementById('mCustomDrop').value.trim().toLowerCase();
            
            let factor = 1.0;
            
            // Logic: Assume strings match roughly if one contains the other or identical
            // Or simpler: Exact match check for logic simplicity.
            
            const isDiffFrom = (myFrom && myFrom !== origFrom);
            const isDiffTo = (myTo && myTo !== origTo);
            
            if (isDiffFrom && isDiffTo) {
                factor = 0.8; // Both different (Middle segment)
            } else if (isDiffFrom || isDiffTo) {
                factor = 0.9; // One different (Start/End segment)
            }
            
            const unitPrice = basePrice * factor;
            calculatedPrice = unitPrice * seats;
            
            document.getElementById('txtFinalPrice').innerText = calculatedPrice.toFixed(0);
            
            const msg = document.getElementById('priceAdjustmentMsg');
            if (factor < 1.0) {
                msg.style.display = 'block';
                msg.innerText = `Partial route discount applied (${Math.round((1-factor)*100)}% off)`;
            } else {
                msg.style.display = 'none';
            }
        }
        
        // Listen to Seats change too
        document.getElementById('mSeats').addEventListener('change', recalcPrice);

        async function requestRide(rideId) {
            currentRideId = rideId;
            const modal = document.getElementById('requestModal');
            const loading = document.getElementById('modalLoading');
            const content = document.getElementById('modalContent');
            
            modal.style.display = 'block';
            loading.style.display = 'block';
            content.style.display = 'none';

            try {
                const response = await fetch(`api_rides.php?ride_id=${rideId}`);
                const data = await response.json();

                if (data.success && data.ride) {
                    currentRide = data.ride;
                    
                    document.getElementById('mFrom').textContent = currentRide.from_location;
                    document.getElementById('mTo').textContent = currentRide.to_location;
                    document.getElementById('mTime').textContent = `${currentRide.ride_date} at ${currentRide.ride_time}`;
                    document.getElementById('mPrice').textContent = '₹' + parseFloat(currentRide.price_per_seat).toFixed(0);
                    document.getElementById('mDriver').innerHTML = `<a href="view_profile.php?id=${currentRide.driver_id}" style="text-decoration: none; color: var(--text-dark);">${currentRide.driver_name}</a>`;
                    document.getElementById('mVehicle').textContent = currentRide.vehicle_type;

                    // Set defaults for custom fields
                    document.getElementById('mCustomPickup').value = currentRide.from_location;
                    document.getElementById('mCustomDrop').value = currentRide.to_location;
                    
                    // Init Price
                    recalcPrice();

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
            // Reset payment steps
            document.getElementById('paymentDoneStep').style.display = 'none';
            document.getElementById('confirmButtons').style.display = 'flex';
            document.getElementById('modalMsg').style.display = 'block';
            document.querySelectorAll('#modalContent > div, #modalContent > .form-group').forEach(el => el.style.display = '');
            const modalContent = document.querySelector('.modal-content');
            if(modalContent) modalContent.style.maxWidth = '450px'; 

            pendingRequestId = null; pendingAmount = 0;
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

            // Inputs
            const seatsInput = document.getElementById('mSeats');
            const proofInput = document.getElementById('mProof');
            const phoneInput = document.getElementById('mPhone');
            const idTypeInput = document.getElementById('mIdType');
            const idNumberInput = document.getElementById('mIdNumber');
            
            const pickupInput = document.getElementById('mCustomPickup');
            const dropInput = document.getElementById('mCustomDrop');
            
            const btn = document.getElementById('btnConfirm');
            const msgBox = document.getElementById('modalMsg');

            // Reset Styles & Messages
            const inputs = [seatsInput, proofInput, idTypeInput, idNumberInput, pickupInput, dropInput];
            inputs.forEach(el => el.style.borderColor = '#ddd');
            msgBox.innerHTML = '';
            
            let isValid = true;
            let firstError = null;
            const setError = (el, msg) => {
                el.style.borderColor = 'var(--error-red)';
                if (!firstError) firstError = msg;
                isValid = false;
            };

            // 1. Custom Location Validation
            if (!pickupInput.value.trim()) setError(pickupInput, "Pickup location cannot be empty.");
            if (!dropInput.value.trim()) setError(dropInput, "Dropoff location cannot be empty.");

            // Phone Validation
            if (phoneInput && (phoneInput.value.length !== 10 || !/^\d+$/.test(phoneInput.value))) {
                setError(phoneInput, "Please enter a valid 10-digit phone number.");
            }

            // 2. ID type label (driver will verify in person)
            const idType = "Aadhar Card";

            // 3. File Validation
            if (proofInput.files.length === 0) {
                 setError(proofInput, "Please upload your ID card before confirming.");
            } else {
                const file = proofInput.files[0];
                const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                if (!validTypes.includes(file.type)) {
                    setError(proofInput, "Only JPG, PNG, or PDF files are allowed.");
                } else if (file.size > 5 * 1024 * 1024) {
                    setError(proofInput, "File is too large. Max 5MB allowed.");
                }
            }

            if (!isValid) {
                msgBox.innerHTML = `<div class="error-banner" style="margin-bottom:1rem; padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 8px;"><i class="fas fa-exclamation-circle"></i> ${firstError}</div>`;
                return;
            }

            // Skip OCR — driver verifies the card in person. Just upload the file directly.
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            try {
                const formData = new FormData();
                formData.append('action', 'create');
                formData.append('ride_id', currentRideId);
                formData.append('seats_requested', seatsInput.value);
                formData.append('id_type', idType);
                formData.append('id_number', ''); // Driver extracts ID manually
                formData.append('pickup_loc', pickupInput.value.trim());
                formData.append('drop_loc', dropInput.value.trim());
                formData.append('final_price', calculatedPrice.toFixed(2));
                formData.append('phone', phoneInput ? phoneInput.value : '');

                if (proofInput.files.length > 0) {
                    formData.append('proof_image', proofInput.files[0]);
                }

                const response = await fetch('api_requests.php', {
                    method: 'POST',
                    body: formData
                });

                // Read raw text first so we can debug bad responses
                const rawText = await response.text();
                let result;
                try {
                    result = JSON.parse(rawText);
                } catch (parseErr) {
                    console.error('Non-JSON response from server:', rawText);
                    throw new Error('Server returned an unexpected response. Check server logs.');
                }

                if (result.success || result.already_requested) {
                    pendingRequestId = result.request_id;
                    pendingAmount    = parseFloat(result.amount) || calculatedPrice;

                    const pMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
                    if (pMethod.startsWith('razorpay')) {
                        triggerRazorpay();
                    } else {
                        if (result.already_requested) {
                            showPaymentResult('warning', 'Already Requested', 'You have already requested this ride. You can manage it from your dashboard.');
                        } else {
                            showPaymentResult('success', 'Request Sent! 🎉', 'Your request has been sent. You can pay from your dashboard later.');
                        }
                    }
                } else {
                    msgBox.innerHTML = `<div class="error-banner" style="margin-bottom:1rem; padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 8px;">${result.message}</div>`;
                    btn.disabled = false;
                    btn.innerHTML = 'Confirm Request';
                }
            } catch (error) {
                console.error('Submit error:', error);
                msgBox.innerHTML = `<div class="error-banner" style="margin-bottom:1rem; padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 8px;">Error: ${error.message}</div>`;
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

        // ─── Razorpay: called from Pay Now button ────────────────────────────
        let pendingRequestId = null;
        let pendingAmount    = 0;

        async function triggerRazorpay() {
            if (!pendingRequestId) return;

            const btn = document.getElementById('btnConfirm');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Opening Payment...';

            try {
                // Create Razorpay order
                const res = await fetch('api_payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'create_order', request_id: pendingRequestId })
                });
                const order = await res.json();

                if (!order.success) {
                    showPaymentResult('error', 'Payment Failed', `Payment initialization failed (${order.message}). Your ride request was still sent. You can try paying later from the dashboard.`);
                    return;
                }

                // Clean, minimal Razorpay options — no customer_id, no remember_customer,
                // no config.display blocks. These cause "Payment Failed" in test/web mode.
                const options = {
                    key:         order.key_id,
                    amount:      order.amount,
                    currency:    order.currency,
                    name:        'ShareMyRide',
                    description: order.description || 'Ride Payment',
                    order_id:    order.order_id,
                    customer_id: order.razorpay_customer_id || undefined,
                    remember_customer: true,
                    prefill: {
                        name:    order.name,
                        email:   '<?php echo addslashes($_SESSION["email"] ?? ""); ?>',
                        contact: order.phone || '<?php echo addslashes(preg_replace("/[^0-9]/", "", $_SESSION["phone"] ?? "9999999999")); ?>'
                    },
                    theme: { color: '#0f766e' },
                    handler: async function(response) {
                        // Verify on server
                        try {
                            const vRes = await fetch('api_payment.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    action:              'verify_payment',
                                    razorpay_order_id:   response.razorpay_order_id,
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_signature:  response.razorpay_signature,
                                    request_id:          pendingRequestId
                                })
                            });
                            const vData = await vRes.json();

                            if (!vData.success) {
                                showPaymentResult('warning', 'Payment Unverified', 'Payment received, but server verification is pending. Contact support if status doesn\'t update.');
                            } else {
                                showPaymentResult('success', 'You\'re All Set! 🚀', 'Your booking is confirmed and ride details are saved. Your driver will see you soon!');
                            }
                        } catch(vErr) {
                            console.error('Verify error:', vErr);
                            showPaymentResult('warning', 'Verify Error', 'Payment made but verification failed. Contact support.');
                        }
                    },
                    modal: {
                        ondismiss: function() {
                            // User closed without paying — request exists, can pay later
                            showPaymentResult('warning', 'Payment Cancelled', 'Payment cancelled. Your request was still sent, you can pay from the dashboard later.');
                        }
                    }
                };

                const rzp = new Razorpay(options);

                // Catch payment failures — show our custom UI instead of browser alert
                rzp.on('payment.failed', async function(failureResponse) {
                    const err = failureResponse.error || {};
                    const msg = err.description || err.reason || 'Payment could not be completed.';
                    console.error('Razorpay payment.failed:', failureResponse);
                    showPaymentResult('error', 'Payment Failed', msg + ' Your ride request was still sent. You can pay later from the dashboard.');
                });

                rzp.open();

            } catch(e) {
                console.error('Razorpay error:', e);
                const cause = (typeof window.Razorpay === 'undefined') ? 'Razorpay script was blocked or failed to load. Please disable adblockers.' : (e.message || e);
                showPaymentResult('error', 'Payment Error', `Network error starting payment: ${cause}. Your ride request was still sent. You can try paying later from the dashboard.`);
            }
        }
        // Disable past dates
        const todayStr = new Date().toLocaleDateString('en-CA');
        if(document.getElementById('searchDate')) document.getElementById('searchDate').min = todayStr;
    </script>

</body>
</html>
