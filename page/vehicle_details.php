<?php
/**
 * Vehicle Details Page
 * 
 * Displays detailed information about a specific vehicle
 */

// Include required files
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
requireAuth();

// Get current user
$user = getCurrentUser();

// Get vehicle ID from URL
$vehicleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$vehicleId) {
    redirect('vehicles.php');
}

// Set page title
$pageTitle = 'Vehicle Details';

// Initialize database
$db = new Database();

// Get vehicle details
try {
    $vehicle = $db->selectOne(
        "SELECT * FROM vehicles WHERE id = ?",
        [$vehicleId]
    );
    
    if (!$vehicle) {
        redirect('vehicles.php');
    }
    
    // Get vehicle features as array
    $features = !empty($vehicle['features']) ? explode(',', $vehicle['features']) : [];
    
    // Format features
    $features = array_map('trim', $features);
    
    // Get similar vehicles
    $similarVehicles = $db->select(
        "SELECT * FROM vehicles 
         WHERE vehicle_type = ? AND id != ? AND is_available = 1
         ORDER BY RAND() 
         LIMIT 3",
        [$vehicle['vehicle_type'], $vehicleId]
    );
    
    // Get vehicle reviews
    $reviews = $db->select(
        "SELECT r.*, u.full_name, u.avatar
         FROM vehicle_reviews r
         JOIN users u ON r.user_id = u.id
         WHERE r.vehicle_id = ?
         ORDER BY r.created_at DESC",
        [$vehicleId]
    );
    
    // Calculate average rating
    $avgRating = 0;
    $totalRatings = count($reviews);
    
    if ($totalRatings > 0) {
        $ratingSum = array_sum(array_column($reviews, 'rating'));
        $avgRating = $ratingSum / $totalRatings;
    }
    
    // Set page title with vehicle name
    $pageTitle = $vehicle['make'] . ' ' . $vehicle['model'] . ' ' . $vehicle['year'];
    
} catch (Exception $e) {
    error_log('Vehicle details error: ' . $e->getMessage());
    redirect('vehicles.php');
}

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book') {
    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
    $end_date = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);
    
    // Redirect to booking page with parameters
    redirect("booking.php?vehicle_id=$vehicleId&start_date=$start_date&end_date=$end_date");
}

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb Navigation -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="../client_dashboard.php" class="text-gray-700 hover:text-gray-900 inline-flex items-center">
                    <svg class="w-5 h-5 mr-2.5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                    </svg>
                    Home
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <a href="vehicles.php" class="ml-1 text-gray-700 hover:text-gray-900 md:ml-2">Vehicles</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="ml-1 text-gray-500 md:ml-2"><?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?></span>
                </div>
            </li>
        </ol>
    </nav>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Vehicle Details -->
        <div class="md:col-span-2">
            <!-- Vehicle Image Gallery -->
            <div class="mb-6">
                <div class="bg-gray-100 rounded-lg overflow-hidden">
                    <?php if (!empty($vehicle['image_url'])): ?>
                        <img src="<?= htmlspecialchars($vehicle['image_url']) ?>" alt="<?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?>" class="w-full h-96 object-cover">
                    <?php else: ?>
                        <div class="w-full h-96 flex items-center justify-center bg-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Vehicle Info -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model'] . ' ' . $vehicle['year']) ?></h1>
                            <div class="flex items-center mb-2">
                                <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded mr-2">
                                    <?= htmlspecialchars(ucfirst($vehicle['vehicle_type'] ?? 'Car')) ?>
                                </span>
                                <?php if ($vehicle['is_available']): ?>
                                    <span class="bg-green-100 text-green-800 text-sm font-medium px-2.5 py-0.5 rounded">
                                        Available
                                    </span>
                                <?php else: ?>
                                    <span class="bg-red-100 text-red-800 text-sm font-medium px-2.5 py-0.5 rounded">
                                        Not Available
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($totalRatings > 0): ?>
                                <div class="flex items-center">
                                    <div class="flex items-center">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= round($avgRating)): ?>
                                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                            <?php else: ?>
                                                <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="ml-2 text-gray-600"><?= number_format($avgRating, 1) ?> (<?= $totalRatings ?> reviews)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="text-2xl font-bold text-blue-600">
                            $<?= number_format($vehicle['price'], 2) ?>
                        </div>
                    </div>
                    
                    <p class="text-gray-700 mb-6"><?= nl2br(htmlspecialchars($vehicle['description'] ?? '')) ?></p>
                    
                    <!-- Vehicle Specifications -->
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Specifications</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500">Make</p>
                            <p class="font-medium"><?= htmlspecialchars($vehicle['make']) ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500">Model</p>
                            <p class="font-medium"><?= htmlspecialchars($vehicle['model']) ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500">Year</p>
                            <p class="font-medium"><?= htmlspecialchars($vehicle['year']) ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500">Color</p>
                            <p class="font-medium"><?= htmlspecialchars($vehicle['color'] ?? 'N/A') ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500">Mileage</p>
                            <p class="font-medium"><?= number_format($vehicle['mileage'] ?? 0) ?> mi</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500">Fuel Type</p>
                            <p class="font-medium"><?= htmlspecialchars(ucfirst($vehicle['fuel_type'] ?? 'Gasoline')) ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500">Transmission</p>
                            <p class="font-medium"><?= htmlspecialchars(ucfirst($vehicle['transmission'] ?? 'Automatic')) ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500">Seats</p>
                            <p class="font-medium"><?= htmlspecialchars($vehicle['seats'] ?? '5') ?></p>
                        </div>
                    </div>
                    
                    <!-- Features -->
                    <?php if (!empty($features)): ?>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Features</h2>
                        <div class="flex flex-wrap gap-2 mb-6">
                            <?php foreach ($features as $feature): ?>
                                <span class="bg-gray-100 text-gray-800 text-sm px-3 py-1 rounded-full">
                                    <?= htmlspecialchars($feature) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Rental Rates -->
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Rental Rates</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg text-center">
                            <p class="text-sm text-gray-700 mb-1">Daily Rate</p>
                            <p class="text-2xl font-bold text-blue-600">$<?= number_format($vehicle['rental_rate_daily'] ?? $vehicle['price'] / 30, 2) ?></p>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-lg text-center">
                            <p class="text-sm text-gray-700 mb-1">Weekly Rate</p>
                            <p class="text-2xl font-bold text-blue-600">$<?= number_format(($vehicle['rental_rate_daily'] ?? $vehicle['price'] / 30) * 6, 2) ?></p>
                            <p class="text-xs text-green-600">Save 14%</p>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-lg text-center">
                            <p class="text-sm text-gray-700 mb-1">Monthly Rate</p>
                            <p class="text-2xl font-bold text-blue-600">$<?= number_format(($vehicle['rental_rate_daily'] ?? $vehicle['price'] / 30) * 25, 2) ?></p>
                            <p class="text-xs text-green-600">Save 17%</p>
                        </div>
                    </div>
                    
                    <!-- Reviews -->
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Customer Reviews</h2>
                    <?php if (!empty($reviews)): ?>
                        <div class="space-y-4">
                            <?php foreach (array_slice($reviews, 0, 3) as $review): ?>
                                <div class="border-b border-gray-200 pb-4">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 mr-3">
                                            <?php if (!empty($review['avatar'])): ?>
                                                <img class="h-10 w-10 rounded-full" src="<?= htmlspecialchars($review['avatar']) ?>" alt="<?= htmlspecialchars($review['full_name']) ?>">
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <span class="text-blue-800 font-medium">
                                                        <?= strtoupper(substr($review['full_name'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="flex items-center mb-1">
                                                <h4 class="text-sm font-medium text-gray-800 mr-2"><?= htmlspecialchars($review['full_name']) ?></h4>
                                                <span class="text-sm text-gray-500"><?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                                            </div>
                                            <div class="flex items-center mb-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $review['rating']): ?>
                                                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                        </svg>
                                                    <?php else: ?>
                                                        <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                        </svg>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            <p class="text-gray-700"><?= htmlspecialchars($review['comment']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($reviews) > 3): ?>
                                <div class="text-center mt-4">
                                    <button id="show-more-reviews" class="text-blue-600 hover:text-blue-800 font-medium">
                                        Show All <?= count($reviews) ?> Reviews
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <p class="text-gray-600">No reviews yet for this vehicle.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Similar Vehicles -->
            <?php if (!empty($similarVehicles)): ?>
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Similar Vehicles</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($similarVehicles as $similar): ?>
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="h-40 bg-gray-200 overflow-hidden">
                                <?php if (!empty($similar['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($similar['image_url']) ?>" alt="<?= htmlspecialchars($similar['make'] . ' ' . $similar['model']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-bold text-gray-800 mb-1"><?= htmlspecialchars($similar['make'] . ' ' . $similar['model']) ?></h3>
                                <p class="text-gray-600 text-sm mb-2"><?= htmlspecialchars($similar['year']) ?></p>
                                <div class="flex justify-between items-center">
                                    <span class="text-blue-600 font-bold">$<?= number_format($similar['price'], 2) ?></span>
                                    <a href="vehicle_details.php?id=<?= $similar['id'] ?>" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Booking Form -->
        <div>
            <div class="bg-white shadow-md rounded-lg overflow-hidden sticky top-8">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-xl font-semibold text-gray-800">Book This Vehicle</h2>
                </div>
                <div class="p-6">
                    <form action="" method="post">
                        <input type="hidden" name="action" value="book">
                        <div class="mb-4">
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" id="start_date" name="start_date" required min="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="mb-4">
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" id="end_date" name="end_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div class="mb-6">
                            <p class="text-sm text-gray-700 mb-2">Estimated Rental Cost:</p>
                            <div class="flex justify-between mb-1">
                                <span>Daily Rate:</span>
                                <span>$<?= number_format($vehicle['rental_rate_daily'] ?? $vehicle['price'] / 30, 2) ?></span>
                            </div>
                            <div id="rental-days" class="flex justify-between mb-1">
                                <span>Days:</span>
                                <span>0</span>
                            </div>
                            <div class="border-t border-gray-200 pt-2 mt-2">
                                <div class="flex justify-between font-semibold">
                                    <span>Total:</span>
                                    <span id="total-cost">$0.00</span>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            Book Now
                        </button>
                    </form>
                    
                    <div class="mt-6">
                        <h3 class="text-sm font-medium text-gray-800 mb-2">Rental Includes:</h3>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Comprehensive insurance</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>24/7 roadside assistance</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Free cancellation up to 24 hours before pickup</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Calculate rental cost
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const rentalDaysElement = document.getElementById('rental-days');
    const totalCostElement = document.getElementById('total-cost');
    const dailyRate = <?= floatval($vehicle['rental_rate_daily'] ?? $vehicle['price'] / 30) ?>;
    
    function calculateRentalCost() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (startDate && endDate && startDate < endDate) {
            const diffTime = Math.abs(endDate - startDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            const totalCost = dailyRate * diffDays;
            
            rentalDaysElement.innerHTML = `<span>Days:</span><span>${diffDays}</span>`;
            totalCostElement.textContent = `$${totalCost.toFixed(2)}`;
        } else {
            rentalDaysElement.innerHTML = `<span>Days:</span><span>0</span>`;
            totalCostElement.textContent = '$0.00';
        }
    }
    
    startDateInput.addEventListener('change', calculateRentalCost);
    endDateInput.addEventListener('change', calculateRentalCost);
    
    // Show more reviews
    const showMoreReviewsBtn = document.getElementById('show-more-reviews');
    if (showMoreReviewsBtn) {
        showMoreReviewsBtn.addEventListener('click', function() {
            // This would typically load more reviews via AJAX
            this.textContent = 'Loading...';
        });
    }
    
    // Set min date for end date based on start date
    startDateInput.addEventListener('change', function() {
        const nextDay = new Date(this.value);
        nextDay.setDate(nextDay.getDate() + 1);
        
        const year = nextDay.getFullYear();
        const month = String(nextDay.getMonth() + 1).padStart(2, '0');
        const day = String(nextDay.getDate()).padStart(2, '0');
        
        endDateInput.min = `${year}-${month}-${day}`;
        
        if (endDateInput.value && new Date(endDateInput.value) <= new Date(this.value)) {
            endDateInput.value = `${year}-${month}-${day}`;
        }
        
        calculateRentalCost();
    });
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>
