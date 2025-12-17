<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ShareMyRide</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            border-left: 4px solid var(--primary-teal);
            box-shadow: var(--shadow-sm);
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        .admin-table th, .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .admin-table th {
            background: #f9fafb;
            font-weight: 600;
        }
    </style>
</head>
<body style="background-color: #f3f4f6;">

    <!-- Admin Nav -->
    <nav style="background: #1f2937; color: white; padding: 1rem 0;">
        <div class="container nav-content">
            <div style="font-weight: 700; font-size: 1.25rem;">ShareMyRide <span style="font-weight: 400; opacity: 0.7;">| Admin Panel</span></div>
            <button onclick="RideManager.logout()" class="btn" style="background: #374151; color: white; border: none;">Logout</button>
        </div>
    </nav>

    <div class="container" style="padding: 3rem 0;">
        
        <h1 style="margin-bottom: 2rem;">System Overview</h1>

        <!-- Stats -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 3rem;">
            <div class="stat-card">
                <div style="font-size: 0.9rem; color: var(--text-gray);">Total Users</div>
                <div style="font-size: 2rem; font-weight: 700;">1,240</div>
            </div>
            <div class="stat-card" style="border-color: var(--success-green);">
                <div style="font-size: 0.9rem; color: var(--text-gray);">Active Rides</div>
                <div style="font-size: 2rem; font-weight: 700;" id="activeRidesCount">0</div>
            </div>
            <div class="stat-card" style="border-color: var(--warning-orange);">
                <div style="font-size: 0.9rem; color: var(--text-gray);">Pending Verifications</div>
                <div style="font-size: 2rem; font-weight: 700;">15</div>
            </div>
            <div class="stat-card" style="border-color: #6366f1;">
                <div style="font-size: 0.9rem; color: var(--text-gray);">Total Revenue</div>
                <div style="font-size: 2rem; font-weight: 700;">₹45K</div>
            </div>
        </div>

        <!-- Rides Management -->
        <div class="search-card" style="display: block; padding: 0; overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; background: white;">
                <h2 style="margin: 0;">Manage Rides</h2>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ride ID</th>
                            <th>Driver</th>
                            <th>Route</th>
                            <th>Date/Time</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ridesTableBody">
                        <!-- Loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="js/ride_manager.js"></script>
    <script>
        // Check Admin Auth
        RideManager.checkAuth('admin');

        function renderAdminView() {
            const rides = RideManager.getAllRides();
            document.getElementById('activeRidesCount').innerText = rides.length;

            const tbody = document.getElementById('ridesTableBody');
            let html = '';

            rides.forEach(ride => {
                html += `
                    <tr>
                        <td style="font-family: monospace; color: var(--text-gray);">${ride.id}</td>
                        <td>${ride.driver_name || 'User'}</td>
                        <td><strong>${ride.from}</strong> <i class="fas fa-arrow-right" style="font-size: 0.8rem; margin: 0 0.5rem;"></i> <strong>${ride.to}</strong></td>
                        <td>${ride.date} <br> <span style="font-size: 0.85rem; color: var(--text-gray);">${ride.time}</span></td>
                        <td>₹${ride.price}</td>
                        <td><span class="trip-badge" style="background: #d1fae5; color: #065f46;">Active</span></td>
                        <td>
                            <button onclick="deleteRide('${ride.id}')" class="btn" style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.75rem; font-size: 0.8rem;">
                                Delete
                            </button>
                        </td>
                    </tr>
                `;
            });

            if (rides.length === 0) {
                html = '<tr><td colspan="7" class="text-center" style="padding: 2rem;">No active rides found.</td></tr>';
            }

            tbody.innerHTML = html;
        }

        function deleteRide(id) {
            if(confirm('ADMIN WARNING: Are you sure you want to delete this ride? This action cannot be undone.')) {
                RideManager.deleteRide(id);
                renderAdminView();
            }
        }

        // Init
        renderAdminView();
    </script>
</body>
</html>
