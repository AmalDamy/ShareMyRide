<?php
require_once 'db_connect.php';

// Access Control
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
    <title>ShareMyRide - College Carpool & Ride Sharing Platform</title>
    <meta name="description" content="ShareMyRide - The best carpool and ride-sharing platform for college students and commuters. Share rides, save money, and make new friends.">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background-color: #f3f4f6;">
    <script src="js/ride_manager.js"></script>
    <script>
        // Enforce Login: Redirect to login if no user session found
        if (!RideManager.getCurrentUser()) {
            window.location.href = 'login.php';
        }
    </script>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo">ShareMyRide</a>
            <div class="nav-links" id="navLinks">
                <a href="index.php" style="color: var(--primary-teal);">Home</a>
                <a href="find_ride.php">Find Ride</a>
                <a href="offer_ride.php">Offer Ride</a>
                <a href="long_trip.php">Long Trip</a>
                <a href="live_tracking.php" style="color: var(--primary-teal); font-weight: 700;"><i class="fas fa-location-arrow"></i> Live Tracking</a>
                <a href="fuel_calculator.php">Fuel Calculator</a>
                <a href="contact.php">Contact</a>
                <a href="logout.php" style="color: var(--error-red); font-weight: 600;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">Your Campus <span class="text-teal">Carpool</span> Community</h1>
                    <p class="hero-subtitle">Save money, reduce carbon footprint, and make new friends. ShareMyRide connects students and commuters for shared journeys.</p>
                    <div class="hero-buttons">
                        <a href="find_ride.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Find a Ride
                        </a>
                        <a href="offer_ride.php" class="btn btn-outline">
                            <i class="fas fa-car"></i> Offer a Ride
                        </a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=600&h=400&fit=crop" alt="Happy students carpooling" style="border-radius: var(--radius-lg); box-shadow: var(--shadow-xl);">
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Search Card -->
    <div class="search-container">
        <div class="container">
            <div class="search-card">
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> From</label>
                    <input type="text" id="searchFrom" class="form-input" placeholder="Leaving from...">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> To</label>
                    <input type="text" id="searchTo" class="form-input" placeholder="Going to...">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Date</label>
                    <input type="date" id="searchDate" class="form-input">
                </div>
                <div class="search-btn-wrapper">
                    <button class="btn btn-primary" onclick="performSearch()">
                        <i class="fas fa-search"></i> Search Rides
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="text-center" style="font-size: 2.5rem; margin-bottom: 1rem;">Why Choose ShareMyRide?</h2>
            <p class="text-center" style="color: var(--text-gray); margin-bottom: 3rem; font-size: 1.1rem;">The smart way to travel together</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <h3 class="feature-title">Save Money</h3>
                    <p class="feature-text">Split fuel costs and save up to 70% on your daily commute or long trips.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3 class="feature-title">Eco-Friendly</h3>
                    <p class="feature-text">Reduce carbon emissions and help create a sustainable future for our planet.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Meet New People</h3>
                    <p class="feature-text">Connect with fellow students and commuters. Make friends on the go!</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Safe & Verified</h3>
                    <p class="feature-text">Verified profiles and ratings ensure a safe and trustworthy community.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="feature-title">Flexible Timing</h3>
                    <p class="feature-text">Choose rides that match your schedule. Daily commutes or one-time trips.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h3 class="feature-title">Cost Calculator</h3>
                    <p class="feature-text">Built-in fuel calculator to help you split costs fairly among passengers.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
        <div class="container">
            <h2 class="text-center" style="font-size: 2.5rem; margin-bottom: 1rem;">How It Works</h2>
            <p class="text-center" style="color: var(--text-gray); margin-bottom: 3rem;">Getting started is easy!</p>

            <div class="steps-container">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Sign Up</h3>
                    <p style="color: var(--text-gray);">Create your free account with your college email or Google account.</p>
                </div>

                <div class="step-item">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Post or Find</h3>
                    <p style="color: var(--text-gray);">Offer your empty seats or search for rides going your way.</p>
                </div>

                <div class="step-item">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Connect</h3>
                    <p style="color: var(--text-gray);">Chat with riders, confirm details, and set the meeting point.</p>
                </div>

                <div class="step-item">
                    <div class="step-number">4</div>
                    <h3 class="step-title">Travel Together</h3>
                    <p style="color: var(--text-gray);">Share the journey, split the cost, and enjoy the company!</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Student Banner -->
    <div class="container">
        <section class="student-banner">
            <div class="banner-content">
                <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">Made for Students, By Students</h2>
                <p style="font-size: 1.2rem; margin-bottom: 2rem; opacity: 0.95;">Join thousands of students already saving money and making their commute more fun!</p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="find_ride.php" class="btn" style="background: var(--white); color: var(--primary-teal); border: none;">Get Started Now</a>
                    <a href="long_trip.php" class="btn" style="background: transparent; color: var(--white); border: 2px solid var(--white);">Plan Long Trip</a>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h4>ShareMyRide</h4>
                    <p style="margin-top: 1rem;">Making campus travel affordable, sustainable, and social.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="find_ride.php">Find a Ride</a></li>
                        <li><a href="offer_ride.php">Offer a Ride</a></li>
                        <li><a href="long_trip.php">Long Trips</a></li>
                        <li><a href="fuel_calculator.php">Fuel Calculator</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul class="footer-links">
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Safety Guidelines</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Connect</h4>
                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <a href="#" style="color: #9ca3af; font-size: 1.5rem;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: #9ca3af; font-size: 1.5rem;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color: #9ca3af; font-size: 1.5rem;"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="color: #9ca3af; font-size: 1.5rem;"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 ShareMyRide. All rights reserved. Made with <i class="fas fa-heart" style="color: var(--error-red);"></i> for students.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            document.getElementById('navLinks').classList.toggle('show');
        }

        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('searchDate').min = today;
        document.getElementById('searchDate').value = today;

        // Search functionality
        function performSearch() {
            const from = document.getElementById('searchFrom').value;
            const to = document.getElementById('searchTo').value;
            const date = document.getElementById('searchDate').value;
            
            // Redirect to find_ride.php with search parameters
            window.location.href = `find_ride.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&date=${encodeURIComponent(date)}`;
        }

        // Allow Enter key to trigger search
        document.getElementById('searchFrom').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') performSearch();
        });
        document.getElementById('searchTo').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') performSearch();
        });
        document.getElementById('searchDate').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') performSearch();
        });
    </script>

</body>
</html>
