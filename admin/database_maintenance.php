<?php
/**
 * Database Check and Fix
 * 
 * This page allows administrators to check and fix database issues
 * 
 * @package VehicSmart
 */

// Set page title
$page_title = 'Database Maintenance';

// Include header
include 'includes/header.php';

$message = '';
$message_type = '';

// Check if specific table check was requested
if (isset($_GET['check_table'])) {
    if ($_GET['check_table'] === 'vehicle_images') {
        // Include the check vehicle_images table script
        ob_start();
        include_once '../database/check_vehicle_images_table.php';
        $check_result = ob_get_clean();
        
        $message = $check_result;
        $message_type = strpos($check_result, 'Error') !== false ? 'error' : 'success';
    } 
    elseif ($_GET['check_table'] === 'vehicle_categories') {
        // Include the create vehicle categories script
        ob_start();
        include_once '../database/create_vehicle_categories.php';
        $check_result = ob_get_clean();
        
        $message = $check_result;
        $message_type = strpos($check_result, 'Error') !== false ? 'error' : 'success';
    }
}
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Database Maintenance</h2>
        <a href="../database/run_migrations.php" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
            Run All Migrations
        </a>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="mb-6 p-4 rounded-md <?= $message_type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Database Tables</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Vehicle Images Table -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-medium">Vehicle Images Table</h4>
                        <p class="text-sm text-gray-600 mt-1">Stores images associated with vehicles</p>
                    </div>
                    <a href="?check_table=vehicle_images" class="bg-accent hover:bg-accent/80 text-white text-sm py-1 px-3 rounded">
                        Check & Fix
                    </a>
                </div>
            </div>
            
            <!-- Vehicle Categories Table -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-medium">Vehicle Categories</h4>
                        <p class="text-sm text-gray-600 mt-1">Defines categories for vehicle classification</p>
                    </div>
                    <a href="?check_table=vehicle_categories" class="bg-accent hover:bg-accent/80 text-white text-sm py-1 px-3 rounded">
                        Check & Fix
                    </a>
                </div>
            </div>
            
            <!-- Additional tables can be added here -->
        </div>
    </div>
    
    <div class="bg-white shadow-md rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">Database Migrations</h3>
        <p class="mb-4">For complete database management, visit the migrations page:</p>
        
        <a href="../database/run_migrations.php" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded inline-block">
            Manage Migrations
        </a>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
