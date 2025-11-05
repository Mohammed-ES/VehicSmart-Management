<?php
/**
 * Header component for the admin dashboard
 * 
 * @package VehicSmart
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Get current page for highlighting active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Initialize common variables used in navigation
require_once __DIR__ . '/../../config/database.php';
$db = Database::getInstance();

// Get vehicle data for sidebar badges
try {
    // Check if vehicles table exists
    $check_table_query = "SHOW TABLES LIKE 'vehicles'";
    $table_exists = $db->select($check_table_query);
    
    if (!empty($table_exists)) {
        $vehicles_query = "SELECT 
                        COUNT(*) as total_vehicles,
                        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_vehicles,
                        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_vehicles
                      FROM vehicles";
        $vehicles_data = $db->selectOne($vehicles_query);
    } else {
        $vehicles_data = ['total_vehicles' => 0, 'available_vehicles' => 0, 'maintenance_vehicles' => 0];
    }
    
    // Check if alerts table exists
    $check_alerts_query = "SHOW TABLES LIKE 'alerts'";
    $alerts_table_exists = $db->select($check_alerts_query);
    
    if (!empty($alerts_table_exists)) {
        $alerts_query = "SELECT COUNT(*) as active_alerts FROM alerts WHERE is_read = 0";
        $alerts_data = $db->selectOne($alerts_query);
    } else {
        $alerts_data = ['active_alerts' => 0];
    }
} catch (Exception $e) {
    // Initialize with defaults if query fails
    $vehicles_data = ['total_vehicles' => 0, 'available_vehicles' => 0, 'maintenance_vehicles' => 0];
    $alerts_data = ['active_alerts' => 0];
    
    // Log the error for debugging
    error_log("Error in header.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin Dashboard' ?> - VehicSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin-style.css">
    <script src="js/admin-script.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-dark': '#1c1c1e',
                        'primary-light': '#f4f4f5',
                        'accent': '#ff7849',
                        'neutral': '#a1a1aa'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 font-sans">
    <!-- Mobile menu toggle (for responsive design) -->
    <div class="lg:hidden fixed top-0 right-0 p-4 z-50">
        <button id="mobile-menu-toggle" class="bg-accent text-white p-2 rounded-md">
            Menu
        </button>
    </div>

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
                <p class="text-sm font-medium opacity-70">Admin Panel</p>
                <p class="text-sm">Welcome, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Admin') ?></p>
            </div>
            
            <hr class="border-gray-700 mb-4">
            
            <!-- Navigation Links -->
            <ul class="space-y-1">
                <li>
                    <a href="dashboard.php" class="flex items-center p-2.5 rounded-lg hover:bg-gray-700 group <?= $current_page === 'dashboard.php' ? 'bg-accent' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="vehicles_manage.php" class="flex items-center p-2.5 rounded-lg hover:bg-gray-700 group <?= $current_page === 'vehicles_manage.php' ? 'bg-accent' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        Vehicles
                        <span class="inline-flex justify-center items-center ml-auto px-2 py-0.5 text-xs rounded bg-accent">
                            <?= $vehicles_data['total_vehicles'] ?? 0 ?>
                        </span>
                    </a>
                </li>
                <li>
                    <a href="clients_manage.php" class="flex items-center p-2.5 rounded-lg hover:bg-gray-700 group <?= $current_page === 'clients_manage.php' ? 'bg-accent' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Clients
                    </a>
                </li>
                <li>
                    <a href="maintenance.php" class="flex items-center p-2.5 rounded-lg hover:bg-gray-700 group <?= $current_page === 'maintenance.php' ? 'bg-accent' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Maintenance
                        <?php if (isset($vehicles_data['maintenance_vehicles']) && $vehicles_data['maintenance_vehicles'] > 0): ?>
                        <span class="inline-flex justify-center items-center ml-auto px-2 py-0.5 text-xs rounded bg-yellow-500 text-white">
                            <?= $vehicles_data['maintenance_vehicles'] ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="pt-3 pb-2">
                    <div class="px-3">
                        <div class="h-px bg-gray-700"></div>
                        <span class="text-xs text-gray-400 pl-2 mt-2 inline-block">COMMUNICATIONS</span>
                    </div>
                </li>
                
                <li>
                    <a href="alerts.php" class="flex items-center p-2.5 rounded-lg hover:bg-gray-700 group <?= $current_page === 'alerts.php' ? 'bg-accent' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        Alerts
                        <?php if (isset($alerts_data['active_alerts']) && $alerts_data['active_alerts'] > 0): ?>
                        <span class="inline-flex justify-center items-center ml-auto px-2 py-0.5 text-xs rounded bg-red-500 text-white notification-badge">
                            <?= $alerts_data['active_alerts'] ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <!-- Messages link removed - messaging system deleted -->
                <li>
                    <a href="reports.php" class="flex items-center p-2.5 rounded-lg hover:bg-gray-700 group <?= $current_page === 'reports.php' ? 'bg-accent' : '' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Reports
                    </a>
                </li>
                
                <li class="pt-3 pb-2">
                    <div class="px-3">
                        <div class="h-px bg-gray-700"></div>
                        <span class="text-xs text-gray-400 pl-2 mt-2 inline-block">SYSTEM</span>
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
                
            </ul>
            
            <!-- Logout & System Info Section -->
            <div class="mt-auto pt-8">
                <!-- Logout button -->
                <a href="../auth/logout.php" class="flex items-center p-2.5 rounded-lg hover:bg-red-700 group text-white bg-red-600 mx-3 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </a>
                
                <!-- System Info -->
                <div class="text-center text-xs text-gray-500 mt-4 pb-4">
                    <p id="live-clock" class="mt-1">--:--</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen bg-gray-100 flex flex-col">
        <!-- Top navigation bar -->
        <div class="bg-white shadow-sm p-4 flex items-center justify-between">
            <div class="flex items-center">
                <button id="mobile-menu-toggle" class="lg:hidden mr-4 text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h2 class="text-xl font-semibold text-gray-800"><?= $page_title ?? 'Dashboard' ?></h2>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Search -->
                <div class="hidden md:block">
                    <div class="relative">
                        <input type="text" class="bg-gray-100 border-0 text-sm rounded-full px-4 py-2 pl-10 w-64 focus:outline-none focus:ring-2 focus:ring-accent" placeholder="Search...">
                        <div class="absolute left-3 top-2.5 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Notifications -->
                <div class="relative">
                    <button id="notification-button" class="text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <?php 
                        // Use the alerts_data that was initialized at the top of the file
                        if (isset($alerts_data['active_alerts']) && $alerts_data['active_alerts'] > 0):
                        ?>
                        <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            <?= $alerts_data['active_alerts'] > 9 ? '9+' : $alerts_data['active_alerts'] ?>
                        </span>
                        <?php endif; ?>
                    </button>
                    
                    <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg py-1 z-50">
                        <div class="px-4 py-2 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="font-medium text-gray-800">Notifications</h3>
                            <?php if (isset($alerts_data['active_alerts']) && $alerts_data['active_alerts'] > 0): ?>
                            <span class="bg-red-100 text-red-800 text-xs font-medium px-2 py-0.5 rounded">
                                <?= $alerts_data['active_alerts'] ?> new
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($alerts_data['active_alerts']) && $alerts_data['active_alerts'] > 0): ?>
                            <a href="alerts.php" class="block px-4 py-3 hover:bg-gray-50 transition duration-150 ease-in-out border-b border-gray-100">
                                <div class="flex">
                                    <div class="flex-shrink-0 mr-3">
                                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">You have <?= $alerts_data['active_alerts'] ?> new alerts</p>
                                        <p class="text-xs text-gray-500 mt-1">Click to view all alerts</p>
                                    </div>
                                </div>
                            </a>
                        <?php else: ?>
                            <div class="px-4 py-6 text-center text-gray-500">
                                <p>No new notifications</p>
                            </div>
                        <?php endif; ?>
                        
                        <a href="alerts.php" class="block text-center text-sm font-medium text-accent hover:underline px-4 py-2 border-t border-gray-100">
                            View all notifications
                        </a>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="relative">
                    <button id="user-menu-button" class="flex items-center text-gray-800 hover:text-gray-600">
                        <span class="mr-2 hidden md:block"><?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Admin') ?></span>
                        <div class="h-8 w-8 rounded-full bg-accent text-white flex items-center justify-center">
                            <?= strtoupper(substr($_SESSION['user']['full_name'] ?? 'A', 0, 1)) ?>
                        </div>
                    </button>
                    <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</a>
                        <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                        <div class="border-t border-gray-100"></div>
                        <a href="../auth/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Page content container -->
        <div class="p-4 flex-grow">
