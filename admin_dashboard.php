<?php
require_once 'db_connect.php';

// Strict Admin Request Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ShareMyRide</title>
    <!-- Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #0F172A; /* Dark Navy for Sidebar */
            --secondary: #3B82F6; /* Bright Blue for Accents */
            --bg-body: #F1F5F9;
            --text-main: #334155;
            --text-light: #94A3B8;
            --white: #FFFFFF;
            --border: #E2E8F0;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { background-color: var(--bg-body); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 260px;
            background-color: var(--primary);
            color: var(--white);
            display: flex;
            flex-direction: column;
            transition: width 0.3s;
            flex-shrink: 0;
        }
        
        .brand {
            height: 64px;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: var(--white);
            text-decoration: none;
        }
        .brand span { color: var(--secondary); }

        .menu { flex: 1; padding: 1rem 0; overflow-y: auto; }
        
        .menu-label {
            padding: 0.75rem 1.5rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
            letter-spacing: 0.05em;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            border-left: 3px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            background-color: rgba(255,255,255,0.05);
            color: var(--white);
            border-left-color: var(--secondary);
        }

        .nav-item i { width: 20px; margin-right: 12px; font-size: 1.1rem; }

        /* --- MAIN CONTENT --- */
        .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

        /* Top Header */
        .topbar {
            height: 64px;
            background: var(--white);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
        }

        .top-left h2 { font-size: 1.1rem; font-weight: 600; }
        
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .user-info { text-align: right; }
        .user-name { font-weight: 600; font-size: 0.9rem; }
        .user-role { font-size: 0.75rem; color: var(--text-light); }
        .avatar {
            width: 40px; height: 40px;
            background: var(--secondary);
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
        }

        /* Content Scroll Area */
        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
        }

        /* --- DASHBOARD WIDGETS --- */
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .grid-1 { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }

        .card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .stat-card {
            padding: 1.5rem;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }
        
        .stat-info h3 { font-size: 2rem; font-weight: 700; margin-bottom: 0.25rem; color: var(--primary); }
        .stat-info p { color: var(--text-light); font-size: 0.875rem; font-weight: 500; }
        
        .icon-box {
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
        }
        .ib-blue { background: #DBEAFE; color: var(--secondary); }
        .ib-green { background: #D1FAE5; color: var(--success); }
        .ib-yellow { background: #FEF3C7; color: var(--warning); }
        .ib-indigo { background: #E0E7FF; color: #6366F1; }

        /* --- TABLES --- */
        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-header h3 { font-size: 1.1rem; font-weight: 600; }

        table { width: 100%; border-collapse: collapse; }
        th { 
            background: #F8FAFC; 
            padding: 1rem 1.5rem; 
            text-align: left; 
            font-size: 0.75rem; 
            font-weight: 600; 
            text-transform: uppercase; 
            color: var(--text-light);
            border-bottom: 1px solid var(--border);
        }
        td { 
            padding: 1rem 1.5rem; 
            border-bottom: 1px solid var(--border); 
            font-size: 0.9rem;
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #F8FAFC; }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .bg-green { background: #D1FAE5; color: #065F46; }
        .bg-yellow { background: #FEF3C7; color: #92400E; }
        .bg-gray { background: #F1F5F9; color: #475569; }

        /* --- BUTTONS --- */
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-sm { padding: 0.35rem 0.75rem; font-size: 0.8rem; }
        .btn-outline { border: 1px solid var(--border); background: white; color: var(--text-main); }
        .btn-outline:hover { background: #F8FAFC; border-color: #CBD5E1; }
        .btn-danger-soft { background: #FEE2E2; color: #991B1B; } 
        .btn-danger-soft:hover { background: #FECACA; }
        .btn-primary { background: var(--secondary); color: white; }
        .btn-primary:hover { background: #2563EB; }

        /* Section Visibility */
        .view-section { display: none; }
        .view-section.active { display: block; animation: fadeUp 0.4s ease; }
        @keyframes fadeUp { from{opacity:0; transform:translateY(10px);} to{opacity:1; transform:translateY(0);} }
        
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <a href="#" class="brand">
            <i class="fas fa-layer-group" style="margin-right:12px; color:var(--secondary);"></i> 
            Share<span>MyRide</span>
        </a>

        <div class="menu">
            <div class="menu-label">Main Menu</div>
            <div class="nav-item active" onclick="switchView('dashboard', this)">
                <i class="fas fa-home"></i> Dashboard
            </div>
            
            <div class="menu-label">Management</div>
            <div class="nav-item" onclick="switchView('rides', this)">
                <i class="fas fa-car"></i> Rides
            </div>
            <div class="nav-item" onclick="switchView('users', this)">
                <i class="fas fa-users"></i> Users
            </div>
            <div class="nav-item" onclick="switchView('messages', this)">
                <i class="fas fa-envelope"></i> Messages <span id="msg-badge" style="margin-left:auto; background:var(--danger); color:white; font-size:0.7rem; padding:2px 7px; border-radius:10px; display:none;"></span>
            </div>
            <div class="nav-item" onclick="switchView('reports', this)">
                <i class="fas fa-flag"></i> Reports
            </div>

            <div class="menu-label">System</div>
            <div class="nav-item" onclick="window.location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </div>
        </div>
    </aside>

    <!-- CONTENT -->
    <main class="main">
        <!-- HEADER -->
        <header class="topbar">
            <div class="top-left">
                <h2 id="pageTitle">Overview</h2>
            </div>
            <div class="user-menu">
                <button class="btn btn-outline" onclick="refreshAll()" title="Refresh Data"><i class="fas fa-sync-alt"></i></button>
                <div class="user-info">
                    <div class="user-name">Super Admin</div>
                    <div class="user-role">Administrator</div>
                </div>
                <div class="avatar">SA</div>
            </div>
        </header>

        <div class="content-area">
            
            <!-- VIEW: DASHBOARD -->
            <div id="dashboard" class="view-section active">
                <div class="grid-4">
                    <div class="card stat-card">
                        <div class="stat-info">
                            <h3 id="d-users">--</h3>
                            <p>Total Users</p>
                        </div>
                        <div class="icon-box ib-blue"><i class="fas fa-users"></i></div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-info">
                            <h3 id="d-rides">--</h3>
                            <p>Active Rides</p>
                        </div>
                        <div class="icon-box ib-green"><i class="fas fa-route"></i></div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-info">
                            <h3 id="d-pending">--</h3>
                            <p>Pending Review</p>
                        </div>
                        <div class="icon-box ib-yellow"><i class="fas fa-clock"></i></div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-info">
                            <h3 id="d-revenue">₹0</h3>
                            <p>Est. Value</p>
                        </div>
                        <div class="icon-box ib-indigo"><i class="fas fa-rupee-sign"></i></div>
                    </div>
                </div>

                <div class="card" style="margin-bottom: 2rem;">
                    <div class="table-header">
                        <h3>Recent Rides</h3>
                        <button class="btn btn-sm btn-outline" onclick="switchView('rides', document.querySelectorAll('.nav-item')[1])">View All</button>
                    </div>
                    <table class="data-table" id="dashboardTable">
                        <thead>
                            <tr><th>Route</th><th>Driver</th><th>Date</th><th>Status</th></tr>
                        </thead>
                        <tbody><tr><td colspan="4" style="text-align:center; padding:1.5rem;">Loading...</td></tr></tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW: RIDES -->
            <div id="rides" class="view-section">
                <div class="card">
                    <div class="table-header">
                        <h3>All Active Rides</h3>
                        <div>
                             <input type="text" placeholder="Search..." style="padding:0.4rem; border:1px solid #ccc; border-radius:4px; margin-right:5px;">
                        </div>
                    </div>
                    <div style="overflow-x:auto;">
                        <table id="ridesTableFull">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Driver Info</th>
                                    <th>Journey</th>
                                    <th>Schedule</th>
                                    <th>Fare</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- VIEW: USERS -->
            <div id="users" class="view-section">
                <div class="card">
                    <div class="table-header">
                        <h3>User Database</h3>
                    </div>
                    <div style="overflow-x:auto;">
                        <table id="usersTableFull">
                            <thead>
                                <tr>
                                    <th>Profile</th>
                                    <th>Contact</th>
                                    <th>Role</th>
                                    <th>Account Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- VIEW: MESSAGES -->
            <div id="messages" class="view-section">
                <div class="card">
                    <div class="table-header">
                        <h3>User Messages & Enquiries</h3>
                        <div style="display:flex; gap:8px;">
                            <select id="msgFilter" onchange="loadMessages()" style="padding:0.4rem; border:1px solid #ccc; border-radius:4px; font-size:0.85rem;">
                                <option value="all">All Messages</option>
                                <option value="new">New</option>
                                <option value="read">Read</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>
                    </div>
                    <div style="overflow-x:auto;">
                        <table id="messagesTable">
                            <thead>
                                <tr>
                                    <th>From</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- VIEW: REPORTS -->
            <div id="reports" class="view-section">
                <div class="card" style="padding:3rem; text-align:center;">
                    <div style="font-size:3rem; color:#cbd5e1; margin-bottom:1rem;"><i class="fas fa-chart-pie"></i></div>
                    <h3>Reports & Analytics</h3>
                    <p style="color:#64748b;">Detailed reporting features are coming soon.</p>
                </div>
            </div>

        </div>
    </main>

    <script>
        // --- Navigation ---
        function switchView(viewId, el) {
            // Update Title
            const titles = {
                'dashboard': 'Overview',
                'rides': 'Manage Rides',
                'users': 'User Management',
                'messages': 'Messages & Enquiries',
                'reports': 'Reports'
            };
            document.getElementById('pageTitle').innerText = titles[viewId];

            // Toggle Menu Active Class
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            if(el) el.classList.add('active');

            // Toggle Section
            document.querySelectorAll('.view-section').forEach(sec => sec.classList.remove('active'));
            document.getElementById(viewId).classList.add('active');

            // Load Data
            if(viewId === 'dashboard') loadStats();
            if(viewId === 'rides') loadRides();
            if(viewId === 'users') loadUsers();
            if(viewId === 'messages') loadMessages();
        }

        function refreshAll() {
            loadStats();
            loadRides();
            loadUsers();
        }

        // --- Data Fetching ---
        async function loadStats() {
            try {
                const res = await fetch('api_admin.php?action=stats');
                const data = await res.json();
                if(data.success) {
                    const s = data.stats;
                    document.getElementById('d-users').innerText = s.total_users;
                    document.getElementById('d-rides').innerText = s.active_rides;
                    document.getElementById('d-pending').innerText = s.pending_verifications;
                    document.getElementById('d-revenue').innerText = s.total_revenue;
                    
                    // Also load preview table
                    loadDashboardTable();
                }
            } catch(e) { console.error(e); }
        }

        async function loadDashboardTable() {
             const tbody = document.querySelector('#dashboardTable tbody');
             try {
                const res = await fetch('api_rides.php?type=');
                const data = await res.json();
                if(data.success) {
                    tbody.innerHTML = '';
                    const limit = data.rides.slice(0, 5); // Show last 5
                    limit.forEach(r => {
                        tbody.innerHTML += `
                            <tr>
                                <td><strong>${r.from_location}</strong> → ${r.to_location}</td>
                                <td>${r.driver_name}</td>
                                <td>${r.ride_date}</td>
                                <td><span class="badge bg-green">Active</span></td>
                            </tr>
                        `;
                    });
                     if(limit.length === 0) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No recent activity.</td></tr>';
                }
             } catch(e) {}
        }

        async function loadRides() {
            const tbody = document.querySelector('#ridesTableFull tbody');
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:1rem;">Loading...</td></tr>';
            try {
                const res = await fetch('api_rides.php?type=');
                const data = await res.json();
                if(data.success) {
                    tbody.innerHTML = '';
                    data.rides.forEach(r => {
                        tbody.innerHTML += `
                        <tr>
                            <td>#${r.ride_id}</td>
                            <td>
                                <div style="font-weight:600;">${r.driver_name}</div>
                                <div style="font-size:0.8rem; color:#94A3B8;">ID: ${r.driver_id}</div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:var(--primary);">${r.from_location}</div>
                                <div style="font-size:0.8rem;">to ${r.to_location}</div>
                            </td>
                            <td>${r.ride_date}<br><small>${r.ride_time}</small></td>
                            <td>₹${parseInt(r.price_per_seat)}</td>
                            <td><span class="badge bg-green">Active</span></td>
                            <td>
                                <button onclick="deleteItem('ride', ${r.ride_id})" class="btn-sm btn-danger-soft">Delete</button>
                            </td>
                        </tr>
                        `;
                    });
                    if(data.rides.length === 0) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:2rem;">No active rides found.</td></tr>';
                }
            } catch(e) {}
        }

        async function loadUsers() {
            const tbody = document.querySelector('#usersTableFull tbody');
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:1rem;">Loading...</td></tr>';
            try {
                const res = await fetch('api_admin.php?action=users');
                const data = await res.json();
                if(data.success) {
                    tbody.innerHTML = '';
                    data.users.forEach(u => {
                        const status = u.is_verified == 1 
                            ? '<span class="badge bg-green">Verified</span>' 
                            : '<span class="badge bg-yellow">Pending</span>';
                            
                        const verifyBtn = u.is_verified == 0 
                            ? `<button onclick="verifyUser(${u.user_id})" class="btn-sm btn-primary" style="margin-right:5px;">Verify</button>` 
                            : '';

                        tbody.innerHTML += `
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center;">
                                    <div class="avatar" style="width:32px; height:32px; font-size:0.8rem; margin-right:10px;">${u.name.charAt(0)}</div>
                                    <span style="font-weight:600;">${u.name}</span>
                                </div>
                            </td>
                            <td>${u.email}</td>
                            <td style="text-transform:capitalize;">${u.role}</td>
                            <td>${status}</td>
                            <td>${u.created_at}</td>
                            <td>
                                ${verifyBtn}
                                <button onclick="deleteItem('user', ${u.user_id})" class="btn-sm btn-danger-soft">Remove</button>
                            </td>
                        </tr>
                        `;
                    });
                }
            } catch(e) {}
        }

        // --- Actions ---
        async function deleteItem(type, id) {
             if(!confirm('Are you sure? This cannot be undone.')) return;
             try {
                const res = await fetch('api_admin.php?action=delete_'+type, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(type === 'ride' ? {ride_id: id} : {user_id: id})
                });
                const data = await res.json();
                if(data.success) {
                    if(type === 'ride') loadRides();
                    if(type === 'user') loadUsers();
                    loadStats(); 
                } else {
                    alert('Error: ' + data.message);
                }
             } catch(e) { console.error(e); }
        }

        async function verifyUser(id) {
             try {
                const res = await fetch('api_admin.php?action=verify_user', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({user_id: id})
                });
                const data = await res.json();
                if(data.success) loadUsers();
             } catch(e) {}
        }

        // Init
        loadStats();
        loadMessageBadge();

        // --- Messages ---
        async function loadMessageBadge() {
            try {
                const res = await fetch('api_admin.php?action=messages');
                const data = await res.json();
                if(data.success) {
                    const newCount = data.messages.filter(m => m.status === 'new').length;
                    const badge = document.getElementById('msg-badge');
                    if(newCount > 0) {
                        badge.innerText = newCount;
                        badge.style.display = 'inline';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            } catch(e) {}
        }

        async function loadMessages() {
            const tbody = document.querySelector('#messagesTable tbody');
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:1rem;">Loading...</td></tr>';
            const filter = document.getElementById('msgFilter')?.value || 'all';
            try {
                const res = await fetch('api_admin.php?action=messages');
                const data = await res.json();
                if(data.success) {
                    let msgs = data.messages;
                    if(filter !== 'all') {
                        msgs = msgs.filter(m => m.status === filter);
                    }
                    tbody.innerHTML = '';
                    if(msgs.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:2rem; color:#94A3B8;"><i class="fas fa-inbox" style="font-size:2rem; display:block; margin-bottom:0.5rem;"></i>No messages found.</td></tr>';
                        return;
                    }
                    const subjectLabels = {
                        'general': 'General Inquiry',
                        'support': 'Technical Support',
                        'safety': 'Safety Concern',
                        'partnership': 'Partnership',
                        'feedback': 'Feedback'
                    };
                    msgs.forEach(m => {
                        const statusClass = m.status === 'new' ? 'bg-yellow' : (m.status === 'resolved' ? 'bg-green' : 'bg-gray');
                        const statusLabel = m.status.charAt(0).toUpperCase() + m.status.slice(1);
                        const shortMsg = m.message.length > 80 ? m.message.substring(0, 80) + '...' : m.message;
                        const dateStr = new Date(m.created_at).toLocaleDateString('en-IN', { day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });

                        let actionBtns = '';
                        if(m.status === 'new') {
                            actionBtns += `<button onclick="updateMsgStatus(${m.id}, 'read')" class="btn-sm btn-outline" style="margin-right:4px;" title="Mark as Read"><i class="fas fa-eye"></i></button>`;
                        }
                        if(m.status !== 'resolved') {
                            actionBtns += `<button onclick="updateMsgStatus(${m.id}, 'resolved')" class="btn-sm btn-primary" style="margin-right:4px;" title="Mark Resolved"><i class="fas fa-check"></i></button>`;
                        }
                        actionBtns += `<button onclick="deleteMessage(${m.id})" class="btn-sm btn-danger-soft" title="Delete"><i class="fas fa-trash"></i></button>`;

                        tbody.innerHTML += `
                        <tr style="${m.status === 'new' ? 'background:#FFFBEB;' : ''}">
                            <td>
                                <div style="font-weight:600;">${m.name}</div>
                                <div style="font-size:0.8rem; color:#94A3B8;">${m.email}</div>
                                ${m.user_id ? '<div style="font-size:0.7rem; color:#64748b;"><i class="fas fa-user-check"></i> Registered User</div>' : '<div style="font-size:0.7rem; color:#94A3B8;"><i class="fas fa-user"></i> Guest</div>'}
                            </td>
                            <td><span style="background:#E0E7FF; color:#4338CA; padding:3px 8px; border-radius:6px; font-size:0.8rem; font-weight:600;">${subjectLabels[m.subject] || m.subject}</span></td>
                            <td style="max-width:300px;"><div style="font-size:0.9rem; line-height:1.4;">${shortMsg}</div></td>
                            <td style="white-space:nowrap; font-size:0.85rem;">${dateStr}</td>
                            <td><span class="badge ${statusClass}">${statusLabel}</span></td>
                            <td style="white-space:nowrap;">${actionBtns}</td>
                        </tr>
                        `;
                    });
                    loadMessageBadge();
                }
            } catch(e) { console.error(e); }
        }

        async function updateMsgStatus(id, status) {
            try {
                const res = await fetch('api_admin.php?action=update_message_status', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id, status })
                });
                const data = await res.json();
                if(data.success) loadMessages();
            } catch(e) {}
        }

        async function deleteMessage(id) {
            if(!confirm('Delete this message permanently?')) return;
            try {
                const res = await fetch('api_admin.php?action=delete_message', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if(data.success) loadMessages();
            } catch(e) {}
        }
    </script>
</body>
</html>
