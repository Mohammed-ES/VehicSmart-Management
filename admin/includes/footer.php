        </div> <!-- End of page content -->
    </div> <!-- End of main content -->

    <!-- Footer -->

    <!-- Admin Scripts - admin-script.js is loaded in header with defer -->
    
    <script>
        // User dropdown functionality
        const userMenuButton = document.getElementById('user-menu-button');
        const userDropdown = document.getElementById('user-dropdown');
        
        if (userMenuButton && userDropdown) {
            userMenuButton.addEventListener('click', () => {
                userDropdown.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (event) => {
                if (!userMenuButton.contains(event.target) && !userDropdown.contains(event.target)) {
                    userDropdown.classList.add('hidden');
                }
            });
        }
        
        // Live clock functionality
        function updateClock() {
            const now = new Date();
            const liveClock = document.getElementById('live-clock');
            if (liveClock) {
                const hours = now.getHours().toString().padStart(2, '0');
                const minutes = now.getMinutes().toString().padStart(2, '0');
                liveClock.textContent = `${hours}:${minutes}`;
            }
        }
        
        // Update the clock immediately and then every second
        updateClock();
        setInterval(updateClock, 60000);
        
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.getElementById('sidebar');
        
        if (mobileMenuToggle && sidebar) {
            mobileMenuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
            });
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', (event) => {
                if (window.innerWidth < 1024 && 
                    !sidebar.contains(event.target) && 
                    !mobileMenuToggle.contains(event.target) && 
                    !sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.add('-translate-x-full');
                }
            });
        }
        
        // Notification dropdown functionality
        const notificationButton = document.getElementById('notification-button');
        const notificationDropdown = document.getElementById('notification-dropdown');
        
        if (notificationButton && notificationDropdown) {
            notificationButton.addEventListener('click', () => {
                notificationDropdown.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (event) => {
                if (!notificationButton.contains(event.target) && !notificationDropdown.contains(event.target)) {
                    notificationDropdown.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>
