<?php
// navbar.php - Universal Navigation Bar for ShareMyRide

// We expect db_connect.php to be included before this file to have session_start() and $conn available.
// If not, we handle basic data from session or just defaults.

$nav_user_id = $_SESSION['user_id'] ?? null;
$nav_display_name = "User";
$nav_profile_pic = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';

if ($nav_user_id) {
    // Attempt to fetch fresh data if $conn is available
    if (isset($conn)) {
        $nav_stmt = $conn->prepare("SELECT name, profile_pic FROM users WHERE user_id = ?");
        $nav_stmt->bind_param("i", $nav_user_id);
        $nav_stmt->execute();
        $nav_res = $nav_stmt->get_result();
        $nav_user = $nav_res->fetch_assoc();
        
        if ($nav_user) {
            $nav_display_name = ucwords(strtolower($nav_user['name']));
            if (!empty($nav_user['profile_pic'])) {
                if (filter_var($nav_user['profile_pic'], FILTER_VALIDATE_URL)) {
                    $nav_profile_pic = $nav_user['profile_pic'];
                } elseif (file_exists($nav_user['profile_pic'])) {
                    $nav_profile_pic = $nav_user['profile_pic'];
                }
            }
        }
    } else {
        // Fallback to session data if DB connection isn't passed
        $nav_display_name = ucwords(strtolower($_SESSION['username'] ?? 'User'));
        if (isset($_SESSION['profile_pic']) && file_exists($_SESSION['profile_pic'])) {
            $nav_profile_pic = $_SESSION['profile_pic'];
        }
    }
}
?>

<style>
    /* Universal Navbar Styles */
    .user-nav-pill {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f1f5f9;
        padding: 5px 15px 5px 5px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        color: #1e293b;
        transition: all 0.3s;
        border: 1px solid #e2e8f0;
        text-decoration: none;
    }
    .user-nav-pill:hover {
        background: #e2e8f0;
        border-color: var(--primary-teal, #0d9488);
        transform: translateY(-1px);
    }
    .user-nav-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .glass-nav {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    .nav-link-item {
        font-weight: 600;
        color: #475569;
        text-decoration: none;
        transition: color 0.2s;
    }
    .nav-link-item:hover {
        color: var(--primary-teal, #0d9488);
    }
</style>

<nav class="navbar glass-nav">
    <div class="container nav-content">
        <a href="index.php" class="logo">Share<span style="color: var(--primary-teal, #0d9488);">MyRide</span></a>
        
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="nav-links" id="navLinks">
            <a href="dashboard.php" class="nav-link-item">Dashboard</a>
            <a href="find_ride.php" class="nav-link-item">Find Ride</a>
            <a href="offer_ride.php" class="nav-link-item">Offer Ride</a>
            
            <?php if ($nav_user_id): ?>
                
                <!-- Notification Bell -->
                <div class="nav-item" style="position: relative; cursor: pointer;" id="notifDropdownToggle" onclick="toggleNotifDropdown()">
                    <i class="fas fa-bell" style="font-size: 1.2rem; color: #64748b;"></i>
                    <span id="navNotifCount" style="display: none; position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; font-size: 0.65rem; font-weight: bold; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">0</span>
                    
                    <!-- Notification Dropdown -->
                    <div id="notifDropdown" style="display: none; position: absolute; top: 150%; right: -50px; width: 320px; background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; overflow: hidden; z-index: 1001;">
                        <div style="padding: 1rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                            <h4 style="margin: 0; font-size: 0.95rem; color: #1e293b;">Notifications</h4>
                            <button onclick="markAllNotificationsRead(event)" style="background:none; border:none; color: var(--primary-teal, #0d9488); font-size: 0.8rem; cursor: pointer; font-weight: 600;">Mark all read</button>
                        </div>
                        <div id="notifList" style="max-height: 350px; overflow-y: auto; padding: 0.5rem 0;">
                            <div style="padding: 1rem; text-align: center; color: #94a3b8; font-size: 0.9rem;">Loading...</div>
                        </div>
                    </div>
                </div>

                <!-- User Pill Button -->
                <div style="display: flex; align-items: center; gap: 10px;">
                    <a href="edit_profile.php" class="user-nav-pill">
                        <img src="<?php echo htmlspecialchars($nav_profile_pic); ?>" class="user-nav-avatar">
                        <span class="user-nav-name"><?php echo htmlspecialchars($nav_display_name); ?></span>
                    </a>
                    <a href="logout.php" style="color: #ef4444; font-size: 1.2rem;" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary" style="padding: 0.5rem 1.5rem; border-radius: 50px;">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Spacer to prevent content from going under the fixed navbar -->
<div style="height: 75px;"></div>

<script>
    // Universal functions (work even if logged out)
    function toggleMobileMenu() {
        const navLinks = document.getElementById('navLinks');
        if (navLinks) {
            navLinks.classList.toggle('active');
        }
    }

    // Global click listener for closing menu when clicking outside
    document.addEventListener('click', function(event) {
        const navLinks = document.getElementById('navLinks');
        const menuBtn = document.querySelector('.mobile-menu-btn');
        
        if (navLinks && navLinks.classList.contains('active')) {
            // If click is NOT on menu and NOT on toggle button
            if (!navLinks.contains(event.target) && !menuBtn.contains(event.target)) {
                navLinks.classList.remove('active');
            }
        }

        // Close notification dropdown if exists
        const dropdown = document.getElementById('notifDropdown');
        const toggle = document.getElementById('notifDropdownToggle');
        if (dropdown && toggle && !toggle.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.style.display = 'none';
        }
    });
</script>

<?php if ($nav_user_id): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadNavNotifications();
        
        // Fetch new notifications every 30 seconds
        setInterval(loadNavNotifications, 30000);
    });

    function toggleNotifDropdown() {
        const dropdown = document.getElementById('notifDropdown');
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
    }

    async function loadNavNotifications() {
        try {
            const res = await fetch('api_notifications.php');
            const data = await res.json();
            
            if (data.success) {
                const badge = document.getElementById('navNotifCount');
                const list = document.getElementById('notifList');
                
                let unreadCount = 0;
                list.innerHTML = '';
                
                if (data.notifications && data.notifications.length > 0) {
                    data.notifications.forEach(notif => {
                        if (notif.is_read == 0) unreadCount++;
                        
                        const isUnreadMsg = notif.is_read == 0 ? '; background:#f0fdf4' : '';
                        const dateStr = new Date(notif.created_at).toLocaleDateString('en-IN', { month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
                        
                        // Icon based on type
                        let icon = 'fa-bell';
                        let color = '#0284c7';
                        if (notif.type === 'admin_reply') { icon = 'fa-reply'; color = '#10b981'; }
                        else if (notif.type === 'ride_request') { icon = 'fa-car'; color = '#f59e0b'; }
                        
                        list.innerHTML += `
                            <div style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.2s${isUnreadMsg}" onclick="markNotificationRead(${notif.id}, event)">
                                <div style="display: flex; gap: 12px; align-items: flex-start;">
                                    <div style="background: ${color}20; color: ${color}; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.85rem;">
                                        <i class="fas ${icon}"></i>
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-size: 0.85rem; color: #334155; line-height: 1.4; margin-bottom: 4px; ${notif.is_read == 0 ? 'font-weight: 600;' : ''}">${notif.message}</div>
                                        <div style="font-size: 0.7rem; color: #94a3b8;"><i class="far fa-clock"></i> ${dateStr}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    list.innerHTML = `
                        <div style="padding: 2rem 1rem; text-align: center; color: #94a3b8;">
                            <i class="far fa-bell" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; opacity: 0.5;"></i>
                            <span style="font-size: 0.9rem;">You're all caught up!</span>
                        </div>
                    `;
                }
                
                if (unreadCount > 0) {
                    badge.innerText = unreadCount > 9 ? '9+' : unreadCount;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        } catch (e) {
            console.error('Failed to load notifications:', e);
        }
    }

    async function markNotificationRead(id, event) {
        if (event) event.stopPropagation(); // Keep dropdown open
        try {
            await fetch('api_notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'mark_read', id: id })
            });
            loadNavNotifications();
        } catch (e) {}
    }

    async function markAllNotificationsRead(event) {
        if (event) event.stopPropagation();
        try {
            await fetch('api_notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'mark_all_read' })
            });
            loadNavNotifications();
        } catch (e) {}
    }
</script>
<?php endif; ?>
