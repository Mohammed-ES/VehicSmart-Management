<?php
/**
 * Client Dashboard
 * 
 * Main dashboard page for clients
 */

// Masquer les avertissements et notices pour éviter d'affecter l'affichage de l'interface utilisateur
error_reporting(E_ERROR | E_PARSE);

// Include required files
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

// Check if user is logged in
requireLogin();

// Verify user is a client
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: /VehicSmart/auth/login.php');
    exit;
}

// Get current user
$user = getCurrentUser();
$user_id = $user['id'];

// Initialize database
$db = Database::getInstance();

// Set page title
$pageTitle = 'Client Dashboard'; 
$page_title = 'Client Dashboard'; // For backwards compatibility with header.php

// Get summary data
try {
    // Check if vehicles table exists
    $check_vehicles_table = "SHOW TABLES LIKE 'vehicles'";
    $vehicles_table_exists = $db->select($check_vehicles_table);

    // Total vehicles available for rent
    if (!empty($vehicles_table_exists)) {
        $vehicles_query = "SELECT 
                        COUNT(*) as total_available_vehicles
                      FROM vehicles
                      WHERE status = 'available'";
        $vehicles_data = $db->selectOne($vehicles_query);
    } else {
        $vehicles_data = ['total_available_vehicles' => 0];
    }
    
    // Check if rentals table exists
    $check_rentals_table = "SHOW TABLES LIKE 'rentals'";
    $rentals_table_exists = $db->select($check_rentals_table);
    
    // User's rentals
    if (!empty($rentals_table_exists)) {
        $rentals_query = "SELECT 
                       COUNT(*) as total_rentals,
                       SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_rentals,
                       SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_rentals
                     FROM rentals
                     WHERE user_id = :user_id";
        $rentals_data = $db->selectOne($rentals_query, ['user_id' => $user_id]);
    } else {
        $rentals_data = ['total_rentals' => 0, 'active_rentals' => 0, 'completed_rentals' => 0];
    }
    
    // User's active rentals
    $activeRentals = $db->select(
        "SELECT r.*, v.brand, v.model, v.year, v.license_plate, 
         (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image
         FROM rentals r
         JOIN vehicles v ON r.vehicle_id = v.id
         WHERE r.user_id = :user_id AND r.status = 'active'
         ORDER BY r.start_date ASC",
        ['user_id' => $user_id]
    );
    
    // User's rental history
    $rentalHistory = $db->select(
        "SELECT r.*, v.brand, v.model, v.year, v.license_plate,
         (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image
         FROM rentals r
         JOIN vehicles v ON r.vehicle_id = v.id
         WHERE r.user_id = :user_id AND r.status != 'active'
         ORDER BY r.created_at DESC
         LIMIT 5",
        ['user_id' => $user_id]
    );
    
    // Recent vehicles
    $availableVehicles = $db->select(
        "SELECT v.*, 
         (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image,
         vc.name as category_name
         FROM vehicles v
         LEFT JOIN vehicle_categories vc ON v.category_id = vc.id
         WHERE v.status = 'available'
         ORDER BY v.created_at DESC
         LIMIT 6"
    );
} catch (Exception $e) {
    error_log('Client dashboard error: ' . $e->getMessage());
}

// Add custom CSS for dashboard - Styled to match admin dashboard
echo '<style>
    /* Force body to use full viewport */
    html, body {
        height: 100%;
        width: 100%;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }
    
    /* Main layout styles */
    .btn-primary {
        display: inline-flex;
        align-items: center;
        background-color: #ff7849;
        color: #ffffff;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    .btn-primary:hover {
        background-color: #f05b2c;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    /* Card styles */
    .dashboard-card {
        transition: all 0.3s ease-in-out;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    /* Status badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        margin-right: 0.5rem;
    }
    .status-available {
        background-color: #d1fae5;
        color: #065f46;
    }
    .status-rented {
        background-color: #dbeafe;
        color: #1e40af;
    }
    .status-pending {
        background-color: #fff7ed;
        color: #c2410c;
    }
    .status-maintenance {
        background-color: #fee2e2;
        color: #b91c1c;
    }
    
    /* Card types */
    .dashboard-card.vehicles .rounded-full {
        background-color: #e0f2fe;
    }
    .dashboard-card.vehicles svg {
        color: #0369a1;
    }
    
    .dashboard-card.rentals .rounded-full {
        background-color: #dcfce7;
    }
    .dashboard-card.rentals svg {
        color: #059669;
    }
    
    .dashboard-card.account .rounded-full {
        background-color: #f3e8ff;
    }
    .dashboard-card.account svg {
        color: #9333ea;
    }
    
    /* Animations */
    .animated-card {
        opacity: 0;
        transform: translateY(20px);
        animation: slideIn 0.6s forwards;
    }
    @keyframes slideIn {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .delay-1 {
        animation-delay: 0.1s;
    }
    .delay-2 {
        animation-delay: 0.2s;
    }
    .delay-3 {
        animation-delay: 0.3s;
    }
    
    /* Notification badge pulse effect */
    .notification-badge {
        position: relative;
    }
    .notification-badge:after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        animation: pulse 2s infinite;
        box-shadow: 0 0 0 0 rgba(255, 82, 82, 0.7);
    }
    @keyframes pulse {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(255, 82, 82, 0.7);
        }
        70% {
            transform: scale(1);
            box-shadow: 0 0 0 10px rgba(255, 82, 82, 0);
        }
        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(255, 82, 82, 0);
        }
    }
    
    /* Section headers */
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #f3f4f6;
    }
    
    /* View All Link */
    .view-all {
        color: #ff7849;
        font-size: 0.875rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        transition: all 0.2s;
    }
    .view-all:hover {
        color: #f05b2c;
    }
    .view-all svg {
        width: 1rem;
        height: 1rem;
        margin-left: 0.25rem;
    }
    
    /* Cards list */
    .card-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    
    .card {
        border-radius: 0.5rem;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transition: all 0.2s;
    }
    .card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        transform: translateY(-2px);
    }
</style>';

// Include header
include_once 'includes/header.php';
?>

<div class="w-full overflow-y-auto min-h-screen">
    <div class="p-6">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Welcome, <?= explode(' ', $user['full_name'] ?? 'User')[0] ?>!</h1>
            <p class="text-gray-600">Here's what's happening with your rentals today - <?= date('F j, Y') ?></p>
        </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <!-- Active Rentals Card -->
        <div class="bg-white rounded-lg shadow p-5 dashboard-card rentals animated-card delay-1">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wider font-medium">Active Rentals</p>
                    <p class="text-3xl font-bold mt-1"><?= $rentals_data['active_rentals'] ?? 0 ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="status-badge status-available"><?= $rentals_data['active_rentals'] ?? 0 ?> Active</span>
                <span class="status-badge status-rented"><?= $rentals_data['completed_rentals'] ?? 0 ?> Completed</span>
            </div>
        </div>
        <!-- ...Active Rentals Card supprimée... -->
        
        <!-- Available Vehicles Card -->
        <div class="bg-white rounded-lg shadow p-5 dashboard-card vehicles animated-card delay-2">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wider font-medium">Available Vehicles</p>
                    <p class="text-3xl font-bold mt-1"><?= $vehicles_data['total_available_vehicles'] ?? 0 ?></p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <a href="select_vehicle.php" class="btn btn-primary inline-flex items-center">
                    Browse vehicles
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
        
        <!-- Total Rentals Card -->
        <div class="bg-white rounded-lg shadow p-5 dashboard-card animated-card delay-3">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wider font-medium">Total Rentals</p>
                    <p class="text-3xl font-bold mt-1"><?= $rentals_data['total_rentals'] ?? 0 ?></p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <a href="rental/rental_history.php" class="btn btn-primary inline-flex items-center">
                    View history
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
        
<!-- ...existing code... -->

<!-- Available Vehicles Section -->
<div class="bg-white rounded-lg shadow-md overflow-hidden animated-card delay-3 mb-6">
    <div class="p-4 border-b bg-gray-50">
        <h2 class="text-lg font-medium flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Available Vehicles
        </h2>
    </div>
    <div class="p-6">
        <?php if (!empty($availableVehicles)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($availableVehicles as $vehicle): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300">
                        <div class="h-40 bg-gray-200 overflow-hidden relative">
                            <?php if (!empty($vehicle['image'])): ?>
                                <img src="../uploads/vehicles/<?= htmlspecialchars($vehicle['image']) ?>" alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <span class="absolute top-2 right-2 status-badge status-available">Available</span>
                        </div>
                        <div class="p-4">
                            <h3 class="text-lg font-bold text-gray-800"><?= html_entity_decode(($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?></h3>
                            <p class="text-sm text-gray-600 mb-2"><?= html_entity_decode($vehicle['category_name'] ?? 'Vehicle') ?> · <?= html_entity_decode($vehicle['year'] ?? '') ?></p>
                            
                            <div class="flex justify-between items-center mt-3">
                                <div>
                                    <span class="text-lg font-bold text-accent">$<?= number_format($vehicle['daily_rate'] ?? 0, 2) ?></span>
                                    <span class="text-xs text-gray-500">/day</span>
                                </div>
                                <a href="vehicle_details.php?id=<?= $vehicle['id'] ?>" class="btn btn-primary text-sm">Rent Now</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-6 text-center">
                <a href="select_vehicle.php" class="btn btn-primary inline-flex items-center">
                    View All Available Vehicles
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h3 class="text-xl font-medium text-gray-800 mb-2">No Vehicles Available</h3>
                <p class="text-gray-600 mb-6">No vehicles available at the moment. Please check back later.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

            </div>
        </div>
    </div>

    <!-- JavaScript for animations and interactivity -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animated entrance for cards
        const cards = document.querySelectorAll('.animated-card');
        cards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
        });
        
        setTimeout(() => {
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        }, 200);
        
        // Notifications button
        const notificationsBtn = document.getElementById('notifications-btn');
        if (notificationsBtn) {
            notificationsBtn.addEventListener('click', function() {
                alert('Notification center will be implemented in a future update.');
            });
        }
    });
    </script>

    </div> <!-- End of p-6 div -->
</div> <!-- End of w-full overflow-y-auto div -->


<?php
// Chatbot system removed
?>
</body>
</html>
