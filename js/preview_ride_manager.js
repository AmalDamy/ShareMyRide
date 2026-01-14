/**
 * ShareMyRide - Preview Data Manager (Mock Version)
 * Returns static data for demonstration purposes without Database
 */

const RideManager = {

    // --- AUTHENTICATION ---

    // Mock Check Auth
    checkAuth: async function () {
        return { user_id: 1, name: 'Demo User', email: 'demo@example.com' };
    },

    getCurrentUser: function () {
        return { user_id: 1, name: 'Demo User', email: 'demo@example.com' };
    },

    // --- RIDES ---

    // Get Rides (Async) - Returns Mock Data
    getAllRides: async function (filters = {}) {
        return [
            {
                ride_id: 1,
                driver_name: 'John Doe',
                from_location: 'Campus Main Gate',
                to_location: 'City Center Mall',
                ride_date: '2025-12-20',
                ride_time: '18:00',
                seats_available: 3,
                price_per_seat: 40,
                vehicle_type: 'Sedan'
            },
            {
                ride_id: 2,
                driver_name: 'Sarah Smith',
                from_location: 'Library',
                to_location: 'North Avenue Station',
                ride_date: '2025-12-21',
                ride_time: '09:00',
                seats_available: 1,
                price_per_seat: 25,
                vehicle_type: 'Hatchback'
            }
        ];
    },

    // --- NOTIFICATIONS ---

    initNotifications: function () {
        // Mock notifications
        const navLinks = document.getElementById('navLinks');
        if (!navLinks || document.getElementById('navNotification')) return;

        const bellItem = document.createElement('a');
        bellItem.id = 'navNotification';
        bellItem.href = '#';
        bellItem.style = 'position: relative; color: var(--text-dark); margin-right: 15px; cursor: pointer; display: flex; align-items: center;';
        bellItem.innerHTML = `
             <i class="fas fa-bell" style="font-size: 1.2rem;"></i>
             <span id="notifBadge" style="position: absolute; top: -5px; right: -8px; background: var(--error-red); color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center;">2</span>
         `;

        const btn = navLinks.querySelector('button');
        if (btn) navLinks.insertBefore(bellItem, btn);
        else navLinks.appendChild(bellItem);
    },

    pollNotifications: async function () { return; },

    // --- INIT ---
    init: function () {
        window.addEventListener('load', () => {
            this.initNotifications();
        });
    }
};

// Auto-init
RideManager.init();

// Mock fetch for dashboard specific calls if they occur outside RideManager
window.originalFetch = window.fetch;
window.fetch = async function (url, options) {
    if (url.includes('api_requests.php?type=outgoing')) {
        return {
            json: async () => ({
                success: true,
                requests: [
                    { ride_id: 101, from_location: 'Hostel A', to_location: 'Airport', ride_date: '2025-12-25', driver_name: 'Mike Ross', status: 'accepted' },
                    { ride_id: 102, from_location: 'Campus', to_location: 'Tech Park', ride_date: '2025-12-26', driver_name: 'Rachel Zane', status: 'pending' }
                ]
            })
        };
    }
    if (url.includes('api_requests.php?type=incoming')) {
        return {
            json: async () => ({
                success: true,
                requests: [
                    { request_id: 201, passenger_name: 'Harvey Specter', seats_requested: 1, from_location: 'Downtown', to_location: 'Campus', status: 'pending' },
                    { request_id: 202, passenger_name: 'Donna Paulsen', seats_requested: 2, from_location: 'City Mall', to_location: 'Hostel B', status: 'pending' }
                ]
            })
        };
    }
    // Fallback to original fetch (which will fail for DB API, but ok for other resources)
    return window.originalFetch(url, options);
};
