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

        // 1. Create Bell if not exists
        if (!document.getElementById('navNotificationContainer')) {
            const container = document.createElement('div');
            container.id = 'navNotificationContainer';
            container.style.cssText = 'position: relative; display: flex; align-items: center; margin-right: 15px;';

            const bellItem = document.createElement('div');
            bellItem.id = 'navNotification';
            bellItem.style.cssText = 'cursor: pointer; padding: 10px; position: relative; color: var(--text-dark); display: flex; align-items: center; justify-content: center; transition: transform 0.2s;';
            bellItem.innerHTML = `
                <i class="fas fa-bell" style="font-size: 1.3rem;"></i>
                <span id="notifBadge" style="position: absolute; top: 2px; right: 2px; background: var(--error-red); color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s; pointer-events: none; border: 2px solid #fff;">0</span>
            `;

            bellItem.onclick = () => this.toggleModal();
            container.appendChild(bellItem);

            // Insert into Nav
            const btn = navLinks.querySelector('button');
            if (btn) navLinks.insertBefore(container, btn);
            else navLinks.appendChild(container);
        }

        // 2. Create Modal DOM if not exists
        this.createModal();

        // 3. Start Polling
        this.pollNotifications();
        setInterval(() => this.pollNotifications(), 5000);
    },

    createModal: function () {
        if (document.getElementById('notificationModal')) return;

        const modalHtml = `
            <div id="notificationModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center; backdrop-filter: blur(4px);">
                <div style="background:white; width:90%; max-width:500px; max-height:80vh; border-radius:16px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); animation: zoomIn 0.2s ease-out;">
                    <div style="padding:1.5rem; border-bottom:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center; background:#fff;">
                        <div>
                            <h3 style="margin:0; color:#111827; font-size:1.25rem; font-weight:700;">Notifications</h3>
                            <p style="margin:0; color:#6b7280; font-size:0.85rem;">Your latest updates</p>
                        </div>
                        <button onclick="RideManager.toggleModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#9ca3af; transition:color 0.2s;"><i class="fas fa-times"></i></button>
                    </div>
                    <div id="modalNotifList" style="overflow-y:auto; flex:1; padding:0; background:#f9fafb;">
                        <!-- Content -->
                        <div style="padding:2rem; text-align:center; color:#9ca3af;">Loading...</div>
                    </div>
                    <div style="padding:1rem; border-top:1px solid #f3f4f6; text-align:center; background:#fff;">
                        <button onclick="RideManager.markAllRead()" style="color:var(--primary-teal); border:none; background:none; font-weight:600; cursor:pointer; font-size:0.9rem;">Mark all as read</button>
                    </div>
                </div>
            </div>
            <style>
                @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
                #navNotification:hover { transform: scale(1.1); }
            </style>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // --- INJECT CUSTOM CONFIRMATION MODAL ---
        const confirmHtml = `
            <div id="customConfirmModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:10010; justify-content:center; align-items:center; backdrop-filter: blur(4px);">
                <div style="background:white; width:90%; max-width:400px; border-radius:16px; padding:24px; text-align:center; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); animation: zoomIn 0.2s ease-out; display:flex; flex-direction:column; gap:16px;">
                    <div style="width:60px; height:60px; background:#f0fdf4; color:#16a34a; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.75rem; margin:0 auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div>
                        <h3 id="confirmTitle" style="margin:0; font-size:1.25rem; font-weight:700; color:#1f2937;">Confirm Action</h3>
                        <p id="confirmMessage" style="margin:8px 0 0; color:#6b7280; font-size:0.95rem; line-height:1.5;">Are you sure you want to proceed?</p>
                    </div>
                    <div style="display:flex; gap:12px; justify-content:center; margin-top:12px;">
                        <button id="btnConfirmCancel" style="flex:1; padding:12px; border:1px solid #d1d5db; background:white; color:#374151; border-radius:8px; cursor:pointer; font-weight:600; transition:all 0.2s;">Cancel</button>
                        <button id="btnConfirmOk" style="flex:1; padding:12px; border:none; background:#10b981; color:white; border-radius:8px; cursor:pointer; font-weight:600; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3); transition:all 0.2s;">Yes, Confirm</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', confirmHtml);

        // --- INJECT CUSTOM ALERT MODAL ---
        const alertHtml = `
            <div id="customAlertModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:10020; justify-content:center; align-items:center; backdrop-filter: blur(4px);">
                <div style="background:white; width:90%; max-width:400px; border-radius:16px; padding:24px; text-align:center; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); animation: zoomIn 0.2s ease-out; display:flex; flex-direction:column; gap:16px;">
                    <div id="alertIconBox" style="width:60px; height:60px; background:#f0fdf4; color:#16a34a; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.75rem; margin:0 auto; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                        <i id="alertIcon" class="fas fa-check"></i>
                    </div>
                    <div>
                        <h3 id="alertTitle" style="margin:0; font-size:1.25rem; font-weight:700; color:#1f2937;">Success</h3>
                        <p id="alertMessage" style="margin:8px 0 0; color:#6b7280; font-size:0.95rem; line-height:1.5;">Operation successful.</p>
                    </div>
                    <div style="display:flex; justify-content:center; margin-top:12px;">
                        <button id="btnAlertOk" style="width:100%; padding:12px; border:none; background:#10b981; color:white; border-radius:8px; cursor:pointer; font-weight:600; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3); transition:all 0.2s;">OK</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', alertHtml);

        // Close on outside click
        document.getElementById('notificationModal').onclick = (e) => {
            if (e.target.id === 'notificationModal') this.toggleModal();
        };
    },

    toggleModal: function () {
        const modal = document.getElementById('notificationModal');
        if (!modal) return;
        const isHidden = modal.style.display === 'none';
        modal.style.display = isHidden ? 'flex' : 'none';
        document.body.style.overflow = isHidden ? 'hidden' : ''; // Prevent body scroll

        if (isHidden) this.renderModalList(); // Refresh content on open
    },

    // --- CUSTOM CONFIRM HELPER ---
    showConfirm: function (title, message) {
        return new Promise((resolve) => {
            const modal = document.getElementById('customConfirmModal');
            if (!modal) {
                if (confirm(message)) resolve(true);
                else resolve(false);
                return;
            }

            document.getElementById('confirmTitle').innerText = title;
            document.getElementById('confirmMessage').innerText = message;
            modal.style.display = 'flex';

            const close = () => { modal.style.display = 'none'; };

            const onOk = () => { close(); resolve(true); cleanup(); };
            const onCancel = () => { close(); resolve(false); cleanup(); };

            const cleanup = () => {
                btnOk.removeEventListener('click', onOk);
                btnCancel.removeEventListener('click', onCancel);
            }

            const btnOk = document.getElementById('btnConfirmOk');
            const btnCancel = document.getElementById('btnConfirmCancel');

            btnOk.addEventListener('click', onOk);
            btnCancel.addEventListener('click', onCancel);
        });
    },

    // --- CUSTOM ALERT HELPER ---
    showAlert: function (title, message, type = 'success') {
        return new Promise((resolve) => {
            const modal = document.getElementById('customAlertModal');
            if (!modal) {
                alert(message);
                resolve();
                return;
            }

            // Custom Styling based on Type
            const iconBox = document.getElementById('alertIconBox');
            const icon = document.getElementById('alertIcon');
            const btn = document.getElementById('btnAlertOk');

            if (type === 'error') {
                iconBox.style.background = '#fef2f2';
                iconBox.style.color = '#dc2626';
                icon.className = 'fas fa-times';
                btn.style.background = '#dc2626';
                btn.style.boxShadow = '0 4px 6px -1px rgba(220, 38, 38, 0.3)';
            } else {
                iconBox.style.background = '#f0fdf4';
                iconBox.style.color = '#16a34a';
                icon.className = 'fas fa-check';
                btn.style.background = '#10b981';
                btn.style.boxShadow = '0 4px 6px -1px rgba(16, 185, 129, 0.3)';
            }

            document.getElementById('alertTitle').innerText = title;
            document.getElementById('alertMessage').innerText = message;
            modal.style.display = 'flex';

            const onOk = () => {
                modal.style.display = 'none';
                btn.removeEventListener('click', onOk);
                resolve();
            };

            btn.addEventListener('click', onOk);
        });
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

                // If modal is open, refresh list
                const modal = document.getElementById('notificationModal');
                if (modal && modal.style.display !== 'none') {
                    this.renderModalList();
                }
            }
        } catch (e) { console.error(e); }
    },

    renderModalList: function () {
        const container = document.getElementById('modalNotifList');
        if (!container) return;

        if (this.notifications.length === 0) {
            container.innerHTML = `
                <div style="padding: 3rem 1rem; text-align: center; color: #6b7280; display:flex; flex-direction:column; align-items:center;">
                    <i class="fas fa-bell-slash" style="font-size:2rem; margin-bottom:1rem; opacity:0.5;"></i>
                    <p>No notifications yet</p>
                </div>`;
            return;
        }

        let html = '';
        this.notifications.forEach(n => {
            const isUnread = n.is_read == 0;
            const bg = isUnread ? '#fff' : '#f9fafb';
            const borderLeft = isUnread ? '4px solid var(--primary-teal)' : '4px solid transparent';
            const opacity = isUnread ? '1' : '0.7';

            // Icon based on type
            let icon = '<i class="fas fa-info-circle text-blue-500"></i>';
            let iconBg = '#dbeafe';
            let iconColor = '#2563eb';

            if (n.type === 'success') {
                icon = '<i class="fas fa-check"></i>';
                iconBg = '#d1fae5';
                iconColor = '#059669';
            } else if (n.type === 'warning' || n.type === 'error') {
                icon = '<i class="fas fa-exclamation"></i>';
                iconBg = '#fee2e2';
                iconColor = '#dc2626';
            }

            // Format Date nicely
            const d = new Date(n.created_at);
            const dateStr = d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + ', ' +
                d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });

            html += `
                <div onclick="RideManager.handleNotifClick(${n.id})" style="padding: 1rem 1.5rem; border-bottom: 1px solid #f3f4f6; background: ${bg}; cursor: pointer; display: flex; gap: 1rem; align-items: flex-start; border-left: ${borderLeft}; transition: background 0.2s;">
                    <div style="flex-shrink:0; width:40px; height:40px; border-radius:50%; background:${iconBg}; color:${iconColor}; display:flex; align-items:center; justify-content:center; font-size:1rem;">
                        ${icon}
                    </div>
                    <div style="flex:1; opacity: ${opacity};">
                        <div style="font-weight: 600; font-size: 0.95rem; color: #1f2937; margin-bottom:0.25rem; display:flex; justify-content:space-between;">
                            <span>${n.title || 'Notification'}</span>
                            ${isUnread ? '<span style="font-size:0.6rem; background:var(--primary-teal); color:white; padding:2px 6px; border-radius:10px;">NEW</span>' : ''}
                        </div>
                        <div style="font-size: 0.9rem; color: #4b5563; line-height:1.4;">${n.message}</div>
                        <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.5rem; font-style:italic;">${dateStr}</div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    },

    handleNotifClick: async function (id) {
        // Find notification
        const notif = this.notifications.find(n => n.id === id);
        if (!notif) return;

        // Navigate if link exists (do this first or after? After strictly speaking, but for UX maybe immediate?)
        // Let's do it after finding it.
        if (notif.link) {
            // If hash is different within same page, we might need to manually scroll
            if (notif.link.includes('#') && notif.link.split('#')[0] === window.location.pathname.split('/').pop()) {
                const targetId = notif.link.split('#')[1];
                const el = document.getElementById(targetId);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth' });
                    // Also close modal
                    this.toggleModal();
                }
            } else {
                window.location.href = notif.link;
            }
        }

        // Mark as read
        if (notif.is_read == 0) {
            notif.is_read = 1;
            this.renderModalList();
            try {
                await fetch('api_notifications.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'mark_read', id: id })
                });
                this.pollNotifications();
            } catch (e) { }
        }
    },

    markAllRead: async function () {
        const confirmed = await this.showConfirm("Mark all as read?", "This will clear the unread status of all your notifications.");
        if (!confirmed) return;

        try {
            await fetch('api_notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'mark_all_read' })
            });
            this.pollNotifications();
        } catch (e) { }
    },

    // --- INIT ---
    init: function () {
        // Auto-init notifications on load
        window.addEventListener('load', () => {
            this.initNotifications();
        });
    }
};

// Make available globally for HTML onclick handlers
window.RideManager = RideManager;

// Auto-init
RideManager.init();
