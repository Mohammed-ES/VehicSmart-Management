<?php
/**
 * Client Sidebar Component
 * 
 * @package VehicSmart
 */

// Get current page for highlighting active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Initialize common variables used in navigation
require_once __DIR__ . '/../../config/database.php';
$db = Database::getInstance();

// Get unread messages count for sidebar badge
try {
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Messages count 
    $messages_query = "SELECT COUNT(*) as unread_messages FROM messages WHERE receiver_id = ? AND is_read = 0";
    $messages_data = $db->selectOne($messages_query, [$user_id]);
    $unread_count = $messages_data ? $messages_data['unread_messages'] : 0;
    
    // Active rentals count
    $rentals_query = "SELECT COUNT(*) as active_rentals FROM rentals WHERE user_id = ? AND status = 'active'";
    $rentals_data = $db->selectOne($rentals_query, [$user_id]);
    $active_rentals = $rentals_data ? $rentals_data['active_rentals'] : 0;
    
} catch (Exception $e) {
    // Initialize with defaults if query fails
    $unread_count = 0;
    $active_rentals = 0;
    
    // Log the error for debugging
    error_log("Error in client_sidebar.php: " . $e->getMessage());
}

// Get base URL
$base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url .= "://".$_SERVER['HTTP_HOST'];
$base_path = dirname($_SERVER['PHP_SELF']);
$base_path = rtrim($base_path, '/client');
$base_url .= $base_path;
?>

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full lg:translate-x-0 bg-primary-dark text-white">
    <div class="h-full px-3 py-4 overflow-y-auto">
        <!-- Logo and App Name -->
        <div class="flex items-center pl-2.5 mb-5">
            <span class="self-center text-xl font-semibold whitespace-nowrap">
                VehicSmart
            </span>
        </div>
        
        <div class="px-4 py-2 mb-4">
            <p class="text-sm font-medium opacity-70">Client Portal</p>
            <p class="text-sm">Welcome, <?= htmlspecialchars($_SESSION['user']['first_name'] ?? 'Client') ?></p>
        </div>
        
        <hr class="border-gray-700 mb-4">
        
        <!-- Navigation Links -->
        <ul class="space-y-1">
            <li>
                <a href="client_dashboard.php" class="flex items-center p-2.5 rounded-lg hover:bg-gray-700 group <?= $current_page === 'client_dashboard.php' ? 'bg-accent' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="select_vehicle.php" class="flex items-center p-2.5 rounded-lg hover:bg-gray-700 group <?= $current_page === 'select_vehicle.php' ? 'bg-accent' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    Find Vehicles
                </a>
            </li>
            <li>
                <a href="my_vehicles.php" class="flex items-center p-2.5 rounded-lg hover:bg-gray-700 group <?= $current_page === 'my_vehicles.php' ? 'bg-accent' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    My Vehicles
                    <?php if ($active_rentals > 0): ?>
                    <span class="inline-flex justify-center items-center ml-auto px-2 py-0.5 text-xs rounded bg-accent">
                        <?= $active_rentals ?>
                    </span>
                    <?php endif; ?>
                </a>
            </li>
            
            <!-- COMMUNICATIONS and AI Assistant sections removed -->
            
            <li class="pt-3 pb-2">
                <div class="px-3">
                    <div class="h-px bg-gray-700"></div>
                    <span class="text-xs text-gray-400 pl-2 mt-2 inline-block">ACCOUNT</span>
                </div>
            </li>
            
            <li>
                <a href="settings.php" class="flex items-center p-2.5 rounded-lg hover:bg-gray-700 group <?= $current_page === 'settings.php' ? 'bg-accent' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>
            </li>
            <li>
                <a href="../auth/logout.php" class="flex items-center p-2.5 rounded-lg hover:bg-gray-700 group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</aside>

<!-- Mobile menu toggle -->
<div class="lg:hidden fixed top-0 right-0 p-4 z-50">
    <button id="mobile-menu-toggle" class="bg-accent text-white p-2 rounded-md">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
        </svg>
    </button>
</div>

<script>
// Mobile menu toggle
document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('-translate-x-full');
});
</script>
