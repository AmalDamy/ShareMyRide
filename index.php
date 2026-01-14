<?php
require_once 'config.php';
require_once 'db_connect.php';

// Check for user login for UI customization
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShareMyRide - Travel Together</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            --primary: #10b981;
            --dark: #111827;
            --light: #f9fafb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            overflow-x: hidden;
            background-color: var(--dark);
            color: white;
        }

        /* Background Image */
        .hero-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background-color: #1f2937; /* Fallback gray */
            /* Gradient Fallback if image fails */
            background-image: 
                linear-gradient(rgba(17, 24, 39, 0.7), rgba(17, 24, 39, 0.7)),
                url('https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?q=80&w=2021&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            z-index: -2;
            /* Lighter filter to avoid "full black" look */
            filter: brightness(0.8); 
        }

        /* Overlay Gradient - lighter to show image */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            /* Gradient only at bottom to make text readable, keep top clear */
            background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.6));
            z-index: -1;
        }

        /* Navbar */
        nav {
            position: absolute;
            top: 0;
            width: 100%;
            padding: 2rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-decoration: none;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            background: white;
            color: var(--dark);
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 0 1rem;
            position: relative;
        }

        .hero h1 {
            font-size: 5rem;
            line-height: 1;
            margin-bottom: 1.5rem;
            font-weight: 800;
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        .hero span {
            color: var(--primary);
            background: linear-gradient(120deg, #34d399, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.5rem;
            max-width: 600px;
            margin-bottom: 3rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .cta-group {
            display: flex;
            gap: 1.5rem;
        }

        .btn {
            padding: 1rem 3rem;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 30px rgba(16, 185, 129, 0.5);
        }

        .btn-glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-glass:hover {
            background: white;
            color: var(--dark);
        }

        .scroll-indicator {
            position: absolute;
            bottom: 2rem;
            animation: bounce 2s infinite;
            opacity: 0.7;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-10px);}
            60% {transform: translateY(-5px);}
        }

        /* Content Sections */
        .content {
            background: white;
            position: relative;
            z-index: 2;
            color: var(--dark);
            border-radius: 40px 40px 0 0;
            margin-top: -40px;
            padding: 6rem 10%;
            box-shadow: 0 -20px 40px rgba(0,0,0,0.2);
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 4rem;
            font-weight: 800;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
        }

        .card {
            background: #f8fafc;
            padding: 3rem 2rem;
            border-radius: 20px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }

        .card i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .card p {
            color: #64748b;
            line-height: 1.6;
        }

        /* Interactive Steps */
        .step-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6rem;
        }

        .step-row:nth-child(even) {
            flex-direction: row-reverse;
        }

        .step-text {
            flex: 1;
            padding: 2rem;
        }

        .step-text h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .step-text p {
            font-size: 1.2rem;
            color: #4b5563;
            line-height: 1.7;
        }

        .step-img {
            flex: 1;
            height: 400px;
            background: #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .step-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .step-img:hover img {
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 3rem; }
            .grid-3 { grid-template-columns: 1fr; }
            .step-row, .step-row:nth-child(even) { flex-direction: column; gap: 2rem; }
            .step-img { width: 100%; height: 300px; }
        }
    </style>
</head>
<body>

    <!-- Background Image (More Reliable & Lively) -->
    <div class="hero-bg"></div>
    <div class="overlay"></div>

    <!-- Navigation -->
    <nav data-aos="fade-down" data-aos-duration="1000">
        <a href="index.php" class="logo">Share<span style="color:var(--primary)">MyRide</span></a>
        <!-- Use flex-grow to push auth buttons to right if needed, but space-between handles it -->
        <div style="display:flex; gap:1rem;">
            <?php if ($isLoggedIn): ?>
                <a href="dashboard.php" class="nav-btn">Dashboard</a>
                <a href="find_ride.php" class="nav-btn" style="background: transparent; border: none;">Find Ride</a>
                <a href="offer_ride.php" class="nav-btn" style="background: transparent; border: none;">Offer Ride</a>
                <a href="long_trip.php" class="nav-btn" style="background: transparent; border: none;">Long Trip</a>
                <a href="logout.php" class="nav-btn" style="background: rgba(220,38,38,0.2); border-color: rgba(220,38,38,0.4);">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            <?php else: ?>
                <!-- Added Sign Up link in nav for convenience -->
                <a href="login.php" class="nav-btn" style="background:transparent; border:none;">Log In</a>
                <a href="login.php?mode=signup" class="nav-btn">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <h1 data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
            Travel Smart.<br>
            <span>Travel Together.</span>
        </h1>
        <p data-aos="fade-up" data-aos-duration="1000" data-aos-delay="400">
            Connect with students, save money on fuel, and create memories on the road.
        </p>
        <div class="cta-group" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="600">
            <?php if ($isLoggedIn): ?>
                <a href="find_ride.php" class="btn btn-primary">Find a Ride</a>
                <a href="offer_ride.php" class="btn btn-glass">Offer a Ride</a>
            <?php else: ?>
                <a href="login.php?mode=signup" class="btn btn-primary">Start Riding</a>
            <?php endif; ?>
        </div>

        <div class="scroll-indicator">
            <i class="fas fa-chevron-down" style="font-size: 2rem;"></i>
        </div>
    </section>

    <!-- Scroll Details Section -->
    <div class="content">
        
        <!-- Features Grid -->
        <h2 class="section-title" data-aos="fade-up">Why ShareMyRide?</h2>
        <div class="grid-3" style="margin-bottom: 8rem;">
            <div class="card" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-wallet"></i>
                <h3>Save Money</h3>
                <p>Cut your travel costs by splitting fuel expenses with fellow students heading your way.</p>
            </div>
            <div class="card" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-leaf"></i>
                <h3>Eco Friendly</h3>
                <p>Help the environment by reducing the number of cars on the road. Every ride counts.</p>
            </div>
            <div class="card" data-aos="fade-up" data-aos-delay="300">
                <i class="fas fa-shield-alt"></i>
                <h3>Verified & Safe</h3>
                <p>Travel with confidence knowing all community members are verified students.</p>
            </div>
        </div>

        <!-- How It Works Rows -->
        <h2 class="section-title" data-aos="fade-up">How It Works</h2>

        <div class="step-row">
            <div class="step-text" data-aos="fade-right">
                <div style="font-size: 5rem; color: #e2e8f0; font-weight: 900; line-height: 1; margin-bottom: 1rem;">01</div>
                <h2>Offer a Ride</h2>
                <p>Driving somewhere? Publish your ride details including date, time, and available seats. Set your price per seat and wait for requests.</p>
            </div>
            <div class="step-img" data-aos="fade-left">
                <img src="https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=800&q=80" alt="Driving">
            </div>
        </div>

        <div class="step-row">
            <div class="step-text" data-aos="fade-left">
                <div style="font-size: 5rem; color: #e2e8f0; font-weight: 900; line-height: 1; margin-bottom: 1rem;">02</div>
                <h2>Find & Request</h2>
                <p>Need a ride? Search for trips matching your schedule. View driver profiles and ratings before sending a request to join.</p>
            </div>
            <div class="step-img" data-aos="fade-right">
                <img src="https://images.unsplash.com/photo-1517672651691-24622a91b550?w=800&q=80" alt="Passenger">
            </div>
        </div>

        <div class="step-row">
            <div class="step-text" data-aos="fade-right">
                <div style="font-size: 5rem; color: #e2e8f0; font-weight: 900; line-height: 1; margin-bottom: 1rem;">03</div>
                <h2>Travel Together</h2>
                <p>Meet at the designated spot and enjoy the journey! Rate your experience afterwards to help build trust in our community.</p>
            </div>
            <div class="step-img" data-aos="fade-left">
                <img src="https://images.unsplash.com/photo-1529333166437-7750a6dd5a70?w=800&q=80" alt="Friends">
            </div>
        </div>

        <!-- Final CTA -->
        <div style="text-align: center; margin-top: 4rem; padding: 4rem; background: var(--dark); border-radius: 30px; color: white;" data-aos="zoom-in">
            <h2 style="font-size: 2.5rem; margin-bottom: 1.5rem;">Ready to hit the road?</h2>
            <p style="margin-bottom: 2rem; opacity: 0.8;">Join thousands of students saving money today.</p>
            <?php if ($isLoggedIn): ?>
                <a href="find_ride.php" class="btn btn-primary" style="padding: 1rem 4rem; font-size: 1.2rem;">Detailed Search</a>
            <?php else: ?>
                <a href="login.php?mode=signup" class="btn btn-primary" style="padding: 1rem 4rem; font-size: 1.2rem;">Create Free Account</a>
            <?php endif; ?>
        </div>

        <footer style="text-align: center; margin-top: 4rem; color: #94a3b8; font-size: 0.9rem;">
            &copy; 2025 ShareMyRide. Designed for Aesthetics.
        </footer>

    </div>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            offset: 100
        });
    </script>
</body>
</html>
