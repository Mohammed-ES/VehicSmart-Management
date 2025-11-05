    <!-- Footer -->
    <footer class="bg-primary-dark text-white py-6 mt-auto">
        <div class="container mx-auto px-4 lg:pl-64">
            <div class="flex flex-col md:flex-row justify-between">
                <div class="mb-6 md:mb-0">
                    <h3 class="text-xl font-bold mb-4">VehicSmart</h3>
                    <p class="text-gray-400">Professional Vehicle Management System</p>
                </div>
                <div class="mb-6 md:mb-0">
                    <h4 class="text-lg font-semibold mb-3">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Home</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Vehicles</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Rentals</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="mb-6 md:mb-0">
                    <h4 class="text-lg font-semibold mb-3">Contact Us</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-2"></i>
                            <a href="mailto:contact@vehicsmart.com">contact@vehicsmart.com</a>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone-alt mr-2"></i>
                            <a href="tel:+123456789">+1 (234) 567-89</a>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            <span>123 Fleet Street, Vehicle City</span>
                        </li>
                    </ul>
                </div>
            </div>
            <hr class="border-gray-700 my-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400">© <?php echo date('Y'); ?> VehicSmart. All rights reserved.</p>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="<?php 
        $base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $base_url .= "://".$_SERVER['HTTP_HOST'];
        $base_url .= dirname($_SERVER['PHP_SELF']);
        $base_url = rtrim($base_url, '/includes');
        echo $base_url; 
    ?>/assets/js/script.js"></script>
    
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        window.addEventListener('click', function(event) {
            const dropdowns = document.querySelectorAll('.group');
            dropdowns.forEach(function(dropdown) {
                if (!dropdown.contains(event.target)) {
                    dropdown.querySelector('.absolute').classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
