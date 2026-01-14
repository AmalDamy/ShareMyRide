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

    notifications: [],

    initNotifications: function () {
        const navLinks = document.getElementById('navLinks');
        if (!navLinks) return;
        if (document.getElementById('navNotificationContainer')) return;

        // Container
        const container = document.createElement('div');
        container.id = 'navNotificationContainer';
        container.style.cssText = 'position: relative; display: flex; align-items: center; margin-right: 15px;';

        // Bell Icon
        const bellItem = document.createElement('div');
        bellItem.id = 'navNotification';
        bellItem.style.cssText = 'cursor: pointer; padding: 10px; position: relative; color: var(--text-dark); display: flex; align-items: center; justify-content: center;';
        bellItem.innerHTML = `
            <i class="fas fa-bell" style="font-size: 1.2rem;"></i>
            <span id="notifBadge" style="position: absolute; top: 0px; right: 0px; background: var(--error-red); color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s; pointer-events: none;">0</span>
        `;

        // Dropdown
        const dropdown = document.createElement('div');
        dropdown.id = 'notifDropdown';
        dropdown.style.cssText = 'position: absolute; top: 120%; right: -10px; width: 320px; background: white; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); display: none; z-index: 9999; max-height: 400px; overflow-y: auto;';

        // Arrow for dropdown
        const arrow = document.createElement('div');
        arrow.style.cssText = 'position: absolute; top: -6px; right: 15px; width: 12px; height: 12px; background: white; transform: rotate(45deg); border-left: 1px solid #e5e7eb; border-top: 1px solid #e5e7eb;';
        dropdown.appendChild(arrow);

        container.appendChild(bellItem);
        container.appendChild(dropdown);

        // Insert into Nav
        const btn = navLinks.querySelector('button');
        if (btn) navLinks.insertBefore(container, btn);
        else navLinks.appendChild(container);

        // Toggle
        bellItem.onclick = (e) => {
            e.stopPropagation();
            console.log("Bell clicked");
            const isVisible = dropdown.style.display === 'block';
            dropdown.style.display = isVisible ? 'none' : 'block';
            if (!isVisible && this.markAllReadDisplay) this.markAllReadDisplay();
        };

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) dropdown.style.display = 'none';
        });

        // Start Polling
        this.pollNotifications();
        setInterval(() => this.pollNotifications(), 5000);
    },

    pollNotifications: async function () {
        try {
            const response = await fetch('api_notifications.php');
            const data = await response.json();

            if (data.success) {
                this.notifications = data.notifications;
                const unreadCount = this.notifications.filter(n => n.is_read == 0).length;

                const badge = document.getElementById('notifBadge');
                if (badge) {
                    badge.innerText = unreadCount;
                    badge.style.opacity = unreadCount > 0 ? '1' : '0';
                }

                this.renderDropdown();
            }
        } catch (e) {
            console.error(e);
        }
    },

    renderDropdown: function () {
        const dropdown = document.getElementById('notifDropdown');
        if (!dropdown) return;

        if (this.notifications.length === 0) {
            dropdown.innerHTML = '<div style="padding: 1rem; text-align: center; color: #6b7280; font-size: 0.9rem;">No notifications</div>';
            return;
        }

        let html = '<div style="padding: 0.5rem 0;">';
        this.notifications.forEach(n => {
            const bg = n.is_read == 0 ? '#f0fdf4' : '#fff';
            const icon = n.type === 'success' ? '<i class="fas fa-check-circle" style="color:#10b981;"></i>' : '<i class="fas fa-info-circle" style="color:#3b82f6;"></i>';

            html += `
                <div onclick="RideManager.handleNotifClick(${n.id})" style="padding: 0.8rem 1rem; border-bottom: 1px solid #f3f4f6; background: ${bg}; cursor: pointer; display: flex; gap: 10px; align-items: flex-start;">
                    <div style="margin-top: 2px;">${icon}</div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.9rem; color: #1f2937;">${n.title || 'Notification'}</div>
                        <div style="font-size: 0.85rem; color: #4b5563;">${n.message}</div>
                        <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 2px;">${n.created_at}</div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        dropdown.innerHTML = html;
    },

    handleNotifClick: async function (id) {
        // Mark as read
        try {
            await fetch('api_notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'mark_read', id: id })
            });
            this.pollNotifications(); // Refresh
        } catch (e) { }
    },

    markAllReadDisplay: function () {
        // Just a visual helper or could call API 'mark_all_read'
    }

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
