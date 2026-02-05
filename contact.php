<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - ShareMyRide</title>
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
                <a href="fuel_calculator.php">Fuel Calculator</a>
                <a href="contact.php" style="color: var(--primary-teal);">Contact</a>
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
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Get in Touch</h1>
            <p style="font-size: 1.1rem; opacity: 0.9;">We're here to help! Reach out for any questions or support.</p>
        </div>
    </div>

    <div class="container" style="padding: 4rem 0;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">
            
            <!-- Contact Form -->
            <div>
                <h2 style="font-size: 1.75rem; margin-bottom: 2rem;">Send us a Message</h2>
                <form id="contactForm" onsubmit="handleContactSubmit(event)">
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Your Name *</label>
                        <input type="text" id="contactName" class="form-input" placeholder="John Doe" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Email Address *</label>
                        <input type="email" id="contactEmail" class="form-input" placeholder="john@example.com" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Subject *</label>
                        <select id="contactSubject" class="form-input" required>
                            <option value="">Select a subject</option>
                            <option value="general">General Inquiry</option>
                            <option value="support">Technical Support</option>
                            <option value="safety">Safety Concern</option>
                            <option value="partnership">Partnership</option>
                            <option value="feedback">Feedback</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Message *</label>
                        <textarea id="contactMessage" class="form-input" rows="5" placeholder="Tell us how we can help..." required></textarea>
                    </div>

                    <div id="contactFormMessage"></div>

                    <button type="submit" class="btn btn-primary w-full" style="padding: 1rem;">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>

            <!-- Contact Info -->
            <div>
                <h2 style="font-size: 1.75rem; margin-bottom: 2rem;">Contact Information</h2>
                
                <div class="contact-info-card">
                    <div class="contact-info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 0.25rem;">Office Address</h4>
                        <p style="color: var(--text-gray); margin: 0;">ShareMyRide Headquarters<br>Tech Park, Infopark Road<br>Kochi, Kerala 682030, India</p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 0.25rem;">Phone</h4>
                        <p style="color: var(--text-gray); margin: 0;">+91 484 1234567<br>+91 484 7654321</p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 0.25rem;">Email</h4>
                        <p style="color: var(--text-gray); margin: 0;">support@sharemyride.com<br>info@sharemyride.com</p>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 0.25rem;">Working Hours</h4>
                        <p style="color: var(--text-gray); margin: 0;">Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 4:00 PM<br>Sunday: Closed</p>
                    </div>
                </div>

                <!-- Social Media -->
                <!-- Social Media -->
                <div style="margin-top: 2rem;">
                    <h3 style="margin-bottom: 1rem; color: var(--dark-teal);">Follow Us</h3>
                    <div style="display: flex; gap: 1rem;">
                        <!-- Facebook -->
                        <a href="#" style="width: 45px; height: 45px; background: #3b5998; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 6px rgba(59, 89, 152, 0.3);" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 12px rgba(59, 89, 152, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(59, 89, 152, 0.3)'">
                            <i class="fab fa-facebook-f" style="font-size: 1.2rem;"></i>
                        </a>
                        
                        <!-- Instagram -->
                        <a href="#" style="width: 45px; height: 45px; background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 6px rgba(220, 39, 67, 0.3);" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 12px rgba(220, 39, 67, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(220, 39, 67, 0.3)'">
                            <i class="fab fa-instagram" style="font-size: 1.2rem;"></i>
                        </a>
                        
                        <!-- Twitter/X -->
                        <a href="#" style="width: 45px; height: 45px; background: #1DA1F2; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 6px rgba(29, 161, 242, 0.3);" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 12px rgba(29, 161, 242, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(29, 161, 242, 0.3)'">
                            <i class="fab fa-twitter" style="font-size: 1.2rem;"></i>
                        </a>
                        
                        <!-- LinkedIn -->
                        <a href="#" style="width: 45px; height: 45px; background: #0077b5; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 6px rgba(0, 119, 181, 0.3);" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 12px rgba(0, 119, 181, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(0, 119, 181, 0.3)'">
                            <i class="fab fa-linkedin-in" style="font-size: 1.2rem;"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            document.getElementById('navLinks').classList.toggle('show');
        }

        function handleContactSubmit(e) {
            e.preventDefault();
            
            const formMessage = document.getElementById('contactFormMessage');
            const name = document.getElementById('contactName').value;
            const email = document.getElementById('contactEmail').value;
            const subject = document.getElementById('contactSubject').value;
            const message = document.getElementById('contactMessage').value;

            if (!name || !email || !subject || !message) {
                formMessage.innerHTML = '<div class="error-banner">Please fill all required fields!</div>';
                return;
            }

            // Success message
            formMessage.innerHTML = '<div class="success-message fade-in"><i class="fas fa-check-circle"></i> Thank you! Your message has been sent successfully. We\'ll get back to you within 24 hours.</div>';

            // Reset form
            setTimeout(() => {
                document.getElementById('contactForm').reset();
                formMessage.innerHTML = '';
            }, 3000);
        }
    </script>

</body>
</html>
