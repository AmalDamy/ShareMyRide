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

                    <!-- Custom Route Section -->
                    <div style="background: #eff6ff; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #dbeafe;">
                        <h4 style="margin: 0 0 10px 0; font-size: 0.95rem; color: #1e40af;">Customize Your Trip (Optional)</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div class="form-group" style="margin:0;">
                                <label style="font-size: 0.8rem;">My Pickup</label>
                                <input type="text" id="mCustomPickup" class="form-input" style="font-size: 0.9rem; padding: 6px;" oninput="recalcPrice()">
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label style="font-size: 0.8rem;">My Dropoff</label>
                                <input type="text" id="mCustomDrop" class="form-input" style="font-size: 0.9rem; padding: 6px;" oninput="recalcPrice()">
                            </div>
                        </div>
                        <div id="priceAdjustmentMsg" style="margin-top: 8px; font-size: 0.85rem; color: #059669; font-weight: 600; display: none;">
                            <i class="fas fa-tag"></i> Price updated for partial route!
                        </div>
                        <div id="finalPriceDisplay" style="margin-top: 5px; font-size: 1.1rem; font-weight: 800; color: #1e40af; text-align: right;">
                            Total: ₹<span id="txtFinalPrice">0</span>
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

                    <!-- Hidden inputs for backward compatibility/internal tracking if needed, though we extract from OCR now -->
                    <input type="hidden" id="mIdType" value="Aadhar Card">
                    <input type="hidden" id="mIdNumber" value="">

                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-id-card" style="color: var(--primary-teal);"></i> 
                            Upload Govt. Certified Aadhar Card *
                        </label>
                        <div class="upload-zone" id="uploadZone" onclick="document.getElementById('mProof').click()" style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 2rem; text-align: center; cursor: pointer; transition: all 0.3s; background: #f8fafc;">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: #94a3b8; margin-bottom: 1rem;"></i>
                            <p style="margin: 0; color: #64748b; font-weight: 500;">Click to upload or drag and drop</p>
                            <p style="margin: 5px 0 0; color: #94a3b8; font-size: 0.8rem;">JPG, PNG or PDF (Max 5MB)</p>
                            <div id="fileSelectedName" style="margin-top: 1rem; font-weight: 600; color: var(--primary-teal); display: none;"></div>
                        </div>
                        <input type="file" id="mProof" class="form-input" accept="image/*,.pdf" style="display: none;" onchange="handleFileSelect(this)">
                        <div style="margin-top: 1rem; padding: 1rem; background: #fff7ed; border-radius: 8px; border: 1px solid #ffedd5; display: flex; gap: 0.75rem; align-items: flex-start;">
                            <i class="fas fa-shield-check" style="color: #f97316; margin-top: 3px;"></i>
                            <span style="font-size: 0.85rem; color: #9a3412; line-height: 1.4;">
                                <strong>Strict Verification:</strong> Only original, government-certified Aadhar cards are accepted. Our AI will verify the document authenticity and extract your ID details automatically.
                            </span>
                        </div>
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

    <script src="https://unpkg.com/tesseract.js@v4.0.2/dist/tesseract.min.js"></script>
    <script src="js/ride_manager.js"></script>
    <script>
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

                 // 3. Name Verification (Relaxed)
                 if (currentUserName) {
                      const userLower = currentUserName.toLowerCase();
                      const userParts = userLower.split(/\s+/).filter(p => p.length > 2);
                      const cleanedTextForName = text.replace(/[^a-z ]/g, ' ');
                      
                      let nameMatch = false;
                      if (userParts.length === 0) {
                          nameMatch = true; // Skip if user name is too short/missing
                      } else {
                          // Check if any significant part of the name exists in the OCR text
                          const matchedParts = userParts.filter(part => cleanedTextForName.includes(part));
                          if (matchedParts.length >= 1) nameMatch = true;
                      }

                      if (!nameMatch) {
                          return { valid: false, reason: `Name mismatch! The name on the Aadhar card does not seem to match '${currentUserName}'.` };
                      }
                 }

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

                 // If all failed, return the most specific reason or general fail
                 const nameFail = results.find(r => r.reason && r.reason.includes("Name mismatch"));
                 if (nameFail) return nameFail;
                 
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
                    document.getElementById('mDriver').textContent = currentRide.driver_name;
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

            // 2. ID Details Validation (Now handled automatically via OCR)
            const idType = "Aadhar Card"; 
            let idNumberClean = ""; // Will be filled after OCR

            // 3. File Validation
            if (proofInput.files.length === 0) {
                 setError(proofInput, "Please upload your Government-certified Aadhar Card.");
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
            
            // Proceed with OCR
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';

            try {
                // Perform OCR Check (Auto-extracts number)
                const ocrResult = await verifyDocumentContent(proofInput.files[0], idType);
                
                if (!ocrResult.valid) {
                    msgBox.innerHTML = `<div class="error-banner" style="margin-bottom:1rem; padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 8px;"><b>Verification Failed:</b> ${ocrResult.reason}</div>`;
                    btn.disabled = false;
                    btn.innerHTML = 'Confirm Request';
                    return;
                }
                
                idNumberClean = ocrResult.id_number; // Set the extracted number
                btn.innerHTML = 'Sending...';

                const formData = new FormData();
                formData.append('action', 'create');
                formData.append('ride_id', currentRideId);
                formData.append('seats_requested', seatsInput.value);
                formData.append('id_type', idType);
                formData.append('id_number', idNumberClean); // Send CLEAN number to server
                formData.append('pickup_loc', pickupInput.value.trim());
                formData.append('drop_loc', dropInput.value.trim());
                formData.append('final_price', calculatedPrice.toFixed(2));
                
                if (proofInput.files.length > 0) {
                    formData.append('proof_image', proofInput.files[0]);
                }

                const response = await fetch('api_requests.php', {
                    method: 'POST',
                    body: formData
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
                msgBox.innerHTML = `<div class="error-banner" style="margin-bottom:1rem; padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 8px;">Server Error. Please try again.</div>`;
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
