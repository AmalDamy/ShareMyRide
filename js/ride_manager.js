/**
 * ShareMyRide - Data Manager (API Version)
 * Communicates with PHP Backend
 */

const RideManager = {

    // --- AUTHENTICATION ---

    // Login
    login: async function (email, password, role) {
        // Now handled directly by login.php form submission to auth.php
        // This function is kept for legacy compatibility if needed, but updated
        try {
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('email', email);
            formData.append('password', password);
            formData.append('role', role);

            const response = await fetch('auth.php', { method: 'POST', body: formData });
            return await response.json();
        } catch (error) {
            console.error('Login Error:', error);
            return { success: false, message: 'Network Error' };
        }
    },

    // Logout
    logout: async function () {
        try {
            const formData = new FormData();
            formData.append('action', 'logout');
            await fetch('auth.php', { method: 'POST', body: formData });
            window.location.href = 'login.php';
        } catch (error) {
            console.error('Logout error:', error);
        }
    },

    // Check Auth (Now we rely on PHP Session, this checks if session is valid via API if needed)
    // For simple pages, we can check a global JS variable set by PHP or just make a call
    // For now, let's assume if this is called, we want to verify session
    checkAuth: async function () {
        // In a real SPA we would verify token. 
        // Here, we look for a user object injected by PHP into window.user or make a call.
        // Let's make a lightweight call to get current user info.
        try {
            const response = await fetch('api_user.php'); // We might need this endpoint
            const data = await response.json();
            if (!data.success) {
                window.location.href = 'login.php';
                return null;
            }
            return data.user;
        } catch (e) {
            // silent fail or redirect
            return null;
        }
    },

    // --- RIDES ---

    // Get Rides (Async)
    getAllRides: async function (filters = {}) {
        try {
            const params = new URLSearchParams(filters);
            const response = await fetch(`api_rides.php?${params.toString()}`);
            const data = await response.json();
            return data.success ? data.rides : [];
        } catch (error) {
            console.error('Get Rides Error:', error);
            return [];
        }
    },

    // Add Ride (Async)
    addRide: async function (rideData) {
        try {
            const response = await fetch('api_rides.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(rideData)
            });
            return await response.json();
        } catch (error) {
            console.error('Add Ride Error:', error);
            return { success: false, message: 'Network Error' };
        }
    },

    // --- NOTIFICATIONS ---

    initNotifications: function () {
        const navLinks = document.getElementById('navLinks');
        if (!navLinks) return;

        // Check if already added
        if (document.getElementById('navNotification')) return;

        // Create Bell Item
        const bellItem = document.createElement('a');
        bellItem.id = 'navNotification';
        bellItem.href = 'dashboard.php#incomingSection'; // Anchor to notifications
        bellItem.style = 'position: relative; color: var(--text-dark); margin-right: 15px; cursor: pointer; display: flex; align-items: center;';
        bellItem.innerHTML = `
            <i class="fas fa-bell" style="font-size: 1.2rem;"></i>
            <span id="notifBadge" style="position: absolute; top: -5px; right: -8px; background: var(--error-red); color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s;">0</span>
        `;

        // Insert before the button (Logout/Login) or at end
        const btn = navLinks.querySelector('button');
        if (btn) {
            navLinks.insertBefore(bellItem, btn);
        } else {
            navLinks.appendChild(bellItem);
        }

        // Start Polling
        this.pollNotifications();
        setInterval(() => this.pollNotifications(), 5000); // Poll every 5s
    },

    pollNotifications: async function () {
        try {
            // Count INCOMING PENDING REQUESTS
            const response = await fetch('api_requests.php?type=incoming');
            const data = await response.json();

            if (data.success) {
                const count = data.requests.length;
                const badge = document.getElementById('notifBadge');
                if (badge) {
                    badge.innerText = count;
                    badge.style.opacity = count > 0 ? '1' : '0';
                }
            }
        } catch (e) {
            // silent
        }
    },

    // --- INIT ---
    init: function () {
        // Auto-init notifications on load
        window.addEventListener('load', () => {
            this.initNotifications();
        });
    }
};

// Auto-init
RideManager.init();
