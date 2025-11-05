<?php
/**
 * Vehicles Page
 * 
 * Displays all available vehicles for rental
 */

// Include required files
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
requireAuth();

// Get current user
$user = getCurrentUser();

// Set page title
$pageTitle = 'Vehicles';

// Initialize database
$db = new Database();

// Get vehicle type filter if set
$vehicleType = isset($_GET['type']) ? $_GET['type'] : null;

// Set up base query
$query = "SELECT * FROM vehicles WHERE is_available = 1";
$params = [];

// Add filter conditions
if ($vehicleType) {
    $query .= " AND vehicle_type = ?";
    $params[] = $vehicleType;
}

// Add sorting
$query .= " ORDER BY make ASC, model ASC";

// Get vehicles
try {
    $vehicles = $db->select($query, $params);
    
    // Get all vehicle types for filter
    $vehicleTypes = $db->select("SELECT DISTINCT vehicle_type FROM vehicles WHERE vehicle_type IS NOT NULL ORDER BY vehicle_type");
} catch (Exception $e) {
    error_log('Vehicles page error: ' . $e->getMessage());
    $vehicles = [];
    $vehicleTypes = [];
}

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Available Vehicles</h1>
            <p class="text-gray-600">Browse our fleet of vehicles available for rental</p>
        </div>
        
        <!-- Filter Controls -->
        <div class="mt-4 md:mt-0">
            <form action="" method="get" class="flex flex-wrap gap-2">
                <select name="type" class="px-4 py-2 border border-gray-300 rounded-md bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All Types</option>
                    <?php foreach ($vehicleTypes as $type): ?>
                        <option value="<?= htmlspecialchars($type['vehicle_type']) ?>" <?= $vehicleType === $type['vehicle_type'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($type['vehicle_type'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors">
                    Filter
                </button>
                <?php if ($vehicleType): ?>
                    <a href="vehicles.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <?php if (!empty($vehicles)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($vehicles as $vehicle): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="h-48 bg-gray-200 overflow-hidden">
                        <?php if (!empty($vehicle['image_url'])): ?>
                            <img src="<?= htmlspecialchars($vehicle['image_url']) ?>" alt="<?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?></h3>
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                                <?= htmlspecialchars(ucfirst($vehicle['vehicle_type'] ?? 'Car')) ?>
                            </span>
                        </div>
                        <p class="text-gray-600 mb-2"><?= htmlspecialchars($vehicle['year']) ?> • <?= htmlspecialchars($vehicle['color']) ?></p>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-xs text-gray-500">Daily Rate</p>
                                <p class="font-medium">$<?= number_format($vehicle['rental_rate_daily'], 2) ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Mileage</p>
                                <p class="font-medium"><?= number_format($vehicle['mileage']) ?> mi</p>
                            </div>
                        </div>
                        
                        <?php if (!empty($vehicle['features'])): ?>
                            <div class="mb-4">
                                <p class="text-xs text-gray-500 mb-1">Features</p>
                                <div class="flex flex-wrap gap-1">
                                    <?php 
                                        $features = explode(',', $vehicle['features']);
                                        foreach (array_slice($features, 0, 3) as $feature): 
                                    ?>
                                        <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">
                                            <?= htmlspecialchars(trim($feature)) ?>
                                        </span>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($features) > 3): ?>
                                        <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">
                                            +<?= count($features) - 3 ?> more
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-blue-600">$<?= number_format($vehicle['price'], 2) ?></span>
                            <a href="vehicle_details.php?id=<?= $vehicle['id'] ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="text-xl font-medium text-gray-800 mb-2">No Vehicles Found</h3>
            <p class="text-gray-600 mb-6">
                <?= $vehicleType ? "No $vehicleType vehicles are currently available." : "No vehicles are currently available." ?>
            </p>
            <?php if ($vehicleType): ?>
                <a href="vehicles.php" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-block">
                    View All Vehicles
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>
