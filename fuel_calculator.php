<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Cost Calculator - ShareMyRide</title>
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
                <a href="fuel_calculator.php" style="color: var(--primary-teal);">Fuel Calculator</a>
                <a href="contact.php">Contact</a>
                <a href="logout.php" style="color: var(--error-red); font-weight: 600;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Header -->
    <div style="background: linear-gradient(135deg, var(--primary-teal), var(--dark-teal)); color: white; padding: 4rem 0; text-align: center;">
        <div class="container">
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem;"><i class="fas fa-calculator"></i> Fuel Cost Calculator</h1>
            <p style="font-size: 1.1rem; opacity: 0.9;">Calculate trip costs and savings when sharing rides</p>
        </div>
    </div>

    <div class="container" style="padding: 4rem 0; max-width: 900px;">
        
        <!-- Calculator Form -->
        <div class="calculator-card">
            <h2 style="color: var(--dark-teal); margin-bottom: 2rem;">Enter Trip Details</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label><i class="fas fa-route"></i> Distance (km) *</label>
                    <input type="number" id="distance" class="form-input" placeholder="e.g., 150" oninput="calculate()">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-tachometer-alt"></i> Vehicle Mileage (km/l) *</label>
                    <input type="number" id="mileage" class="form-input" placeholder="e.g., 15" oninput="calculate()">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label><i class="fas fa-gas-pump"></i> Fuel Price (₹/liter) *</label>
                    <input type="number" id="fuelPrice" class="form-input" placeholder="e.g., 105" value="105" oninput="calculate()">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-users"></i> Number of Passengers</label>
                    <input type="number" id="passengers" class="form-input" placeholder="e.g., 3" min="1" value="1" oninput="calculate()">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label><i class="fas fa-road"></i> Additional Costs (Tolls, Parking)</label>
                    <input type="number" id="additional" class="form-input" placeholder="e.g., 200" value="0" oninput="calculate()">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-sync-alt"></i> Round Trip?</label>
                    <select id="roundTrip" class="form-input" onchange="calculate()">
                        <option value="1">One Way</option>
                        <option value="2">Round Trip</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div id="results" style="display: none;">
            <div class="calc-result">
                <h3 style="font-size: 1.25rem; opacity: 0.9; margin-bottom: 1rem;">Total Trip Cost</h3>
                <div class="calc-result-value">₹<span id="totalCost">0</span></div>
                <p style="opacity: 0.8; margin-top: 0.5rem;">Fuel + Additional Costs</p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); text-align: center;">
                    <div style="color: var(--text-gray); margin-bottom: 0.5rem;">Cost Per Person</div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--primary-teal);">₹<span id="perPerson">0</span></div>
                </div>

                <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); text-align: center;">
                    <div style="color: var(--text-gray); margin-bottom: 0.5rem;">Your Savings</div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--success-green);">₹<span id="savings">0</span></div>
                </div>
            </div>

            <!-- Breakdown -->
            <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-top: 2rem;">
                <h3 style="margin-bottom: 1.5rem; color: var(--dark-teal);">Cost Breakdown</h3>
                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb;">
                    <span>Fuel Required:</span>
                    <span class="font-bold"><span id="fuelReq">0</span> liters</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb;">
                    <span>Fuel Cost:</span>
                    <span class="font-bold">₹<span id="fuelCost">0</span></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb;">
                    <span>Additional Costs:</span>
                    <span class="font-bold">₹<span id="addCost">0</span></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; font-size: 1.1rem;">
                    <span class="font-bold">Total:</span>
                    <span class="font-bold" style="color: var(--primary-teal);">₹<span id="totalBreak">0</span></span>
                </div>
            </div>

            <!-- Sharing Tip -->
            <div style="background: var(--light-teal); padding: 1.5rem; border-radius: var(--radius-md); margin-top: 2rem; border-left: 4px solid var(--primary-teal);">
                <h4 style="color: var(--dark-teal); margin-bottom: 0.5rem;"><i class="fas fa-lightbulb"></i> Sharing Tip</h4>
                <p style="color: var(--text-gray); margin: 0;">By sharing this ride with <strong id="passCount">1</strong> passenger(s), you're saving <strong style="color: var(--success-green);">₹<span id="savingsTip">0</span></strong> compared to traveling alone! Plus, you're reducing carbon emissions and making new friends.</p>
            </div>
        </div>

    </div>

    <script>
        function toggleMobileMenu() {
            document.getElementById('navLinks').classList.toggle('show');
        }

        function calculate() {
            const distance = parseFloat(document.getElementById('distance').value) || 0;
            const mileage = parseFloat(document.getElementById('mileage').value) || 0;
            const fuelPrice = parseFloat(document.getElementById('fuelPrice').value) || 0;
            const passengers = parseInt(document.getElementById('passengers').value) || 1;
            const additional = parseFloat(document.getElementById('additional').value) || 0;
            const roundTrip = parseFloat(document.getElementById('roundTrip').value) || 1;

            if (distance > 0 && mileage > 0 && fuelPrice > 0) {
                const totalDistance = distance * roundTrip;
                const fuelRequired = (totalDistance / mileage).toFixed(2);
                const fuelCostCalc = (fuelRequired * fuelPrice).toFixed(2);
                const totalCostCalc = (parseFloat(fuelCostCalc) + additional).toFixed(2);
                const perPersonCost = (totalCostCalc / passengers).toFixed(2);
                const savingsCalc = (totalCostCalc - perPersonCost).toFixed(2);

                // Update display
                document.getElementById('fuelReq').textContent = fuelRequired;
                document.getElementById('fuelCost').textContent = fuelCostCalc;
                document.getElementById('addCost').textContent = additional.toFixed(2);
                document.getElementById('totalBreak').textContent = totalCostCalc;
                document.getElementById('totalCost').textContent = totalCostCalc;
                document.getElementById('perPerson').textContent = perPersonCost;
                document.getElementById('savings').textContent = savingsCalc;
                document.getElementById('passCount').textContent = passengers;
                document.getElementById('savingsTip').textContent = savingsCalc;

                document.getElementById('results').style.display = 'block';
            } else {
                document.getElementById('results').style.display = 'none';
            }
        }
    </script>

</body>
</html>
