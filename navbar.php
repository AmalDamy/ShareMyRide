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

<nav class="navbar glass-nav" style="background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.05); height: 75px; display: flex; align-items: center; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000;">
    <div class="container nav-content" style="display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 1.5rem;">
        <a href="index.php" class="logo" style="color: #1e293b; font-weight: 800; font-size: 1.5rem; text-decoration: none; text-transform: none; letter-spacing: -0.5px;">Share<span style="color: var(--primary-teal, #0d9488);">MyRide</span></a>
        
        <div class="nav-links" style="display: flex; align-items: center; gap: 20px;">
            <a href="dashboard.php" class="nav-link-item">Dashboard</a>
            <a href="find_ride.php" class="nav-link-item">Find Ride</a>
            <a href="offer_ride.php" class="nav-link-item">Offer Ride</a>
            
            <?php if ($nav_user_id): ?>
                <!-- User Pill Button -->
                <a href="edit_profile.php" class="user-nav-pill">
                    <img src="<?php echo htmlspecialchars($nav_profile_pic); ?>" class="user-nav-avatar">
                    <span class="user-nav-name"><?php echo htmlspecialchars($nav_display_name); ?></span>
                </a>
                <a href="logout.php" style="color: #ef4444; font-size: 1.2rem; margin-left: 5px;" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary" style="padding: 0.5rem 1.5rem; border-radius: 50px;">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Spacer to prevent content from going under the fixed navbar -->
<div style="height: 75px;"></div>
