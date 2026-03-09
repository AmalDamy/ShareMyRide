<?php
// sub_navbar.php - Secondary navigation for specific sections

$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    .sub-nav {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.75rem 0;
        margin-bottom: 2rem;
        position: sticky;
        top: 75px; /* Right below the fixed main navbar */
        z-index: 999;
    }
    .sub-nav-content {
        display: flex;
        justify-content: center;
        gap: 2rem;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1.5rem;
    }
    .sub-nav-link {
        text-decoration: none;
        color: #64748b;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.2s;
    }
    .sub-nav-link:hover {
        color: var(--primary-teal, #0d9488);
        background: #f1f5f9;
    }
    .sub-nav-link.active {
        color: var(--primary-teal, #0d9488);
        background: #f0fdf4;
        box-shadow: inset 0 0 0 1px rgba(13, 148, 136, 0.1);
    }
    @media (max-width: 768px) {
        .sub-nav-content {
            gap: 0.5rem;
            overflow-x: auto;
            justify-content: flex-start;
            padding-bottom: 5px;
        }
        .sub-nav-link {
            white-space: nowrap;
            padding: 0.4rem 0.75rem;
        }
    }
</style>

<div class="sub-nav">
    <div class="sub-nav-content">
        <a href="dashboard.php" class="sub-nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>

        <a href="live_tracking.php" class="sub-nav-link <?php echo ($current_page == 'live_tracking.php') ? 'active' : ''; ?>">
            <i class="fas fa-location-arrow"></i> Live Tracking
        </a>
        <a href="long_trip.php" class="sub-nav-link <?php echo ($current_page == 'long_trip.php') ? 'active' : ''; ?>">
            <i class="fas fa-route"></i> Long Trip
        </a>
        <a href="fuel_calculator.php" class="sub-nav-link <?php echo ($current_page == 'fuel_calculator.php') ? 'active' : ''; ?>">
            <i class="fas fa-gas-pump"></i> Fuel Calculator
        </a>
        <a href="contact.php" class="sub-nav-link <?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i> Contact
        </a>
    </div>
</div>
