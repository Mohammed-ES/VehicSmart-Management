<?php
/**
 * Select Vehicle Page - For clients to search and filter vehicles
 * 
 * @package VehicSmart
 */

// Set page title
$page_title = 'Find a Vehicle';

// Include database connection
require_once '../config/database.php';
require_once '../config/ImageManager.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Get database instance
$db = Database::getInstance();
$imageManager = new ImageManager($db);

// Process filters
$where_clauses = ["status = 'available'"]; // Only show available vehicles
$params = [];

// Filter by type
if (isset($_GET['type']) && !empty($_GET['type'])) {
    $where_clauses[] = "type = :type";
    $params['type'] = $_GET['type'];
}

// Filter by fuel type
if (isset($_GET['fuel_type']) && !empty($_GET['fuel_type'])) {
    $where_clauses[] = "fuel_type = :fuel_type";
    $params['fuel_type'] = $_GET['fuel_type'];
}

// Filter by price range
if (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) {
    $where_clauses[] = "daily_rate >= :min_price";
    $params['min_price'] = $_GET['min_price'];
}

if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
    $where_clauses[] = "daily_rate <= :max_price";
    $params['max_price'] = $_GET['max_price'];
}

// Search by brand, make, model
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $where_clauses[] = "(brand LIKE :search OR make LIKE :search OR model LIKE :search)";
    $params['search'] = $search;
}

// Build the WHERE clause
$where_sql = implode(' AND ', $where_clauses);

// Pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Count total vehicles with filters
$count_sql = "SELECT COUNT(*) as total FROM vehicles WHERE $where_sql";
$total_result = $db->selectOne($count_sql, $params);
$total_vehicles = $total_result ? $total_result['total'] : 0;
$total_pages = ceil($total_vehicles / $limit);

// Vérifier si la table vehicle_images existe
$check_images_table = "SHOW TABLES LIKE 'vehicle_images'";
$images_table_exists = $db->select($check_images_table);

// Get vehicles with pagination - N'utilisez pas de paramètres pour LIMIT et OFFSET
// car PDO ajoute des guillemets qui causent des erreurs de syntaxe SQL
$limit_int = (int)$limit;
$offset_int = (int)$offset;

if (!empty($images_table_exists)) {
    // Si la table existe, inclure la sous-requête pour les images
    $vehicles_sql = "SELECT v.*,
                    (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image
                    FROM vehicles v
                    WHERE $where_sql
                    ORDER BY v.daily_rate ASC
                    LIMIT $limit_int OFFSET $offset_int";
} else {
    // Si la table n'existe pas, ne pas inclure la sous-requête pour les images
    $vehicles_sql = "SELECT v.*, NULL as image
                    FROM vehicles v
                    WHERE $where_sql
                    ORDER BY v.daily_rate ASC
                    LIMIT $limit_int OFFSET $offset_int";
}
$vehicles = $db->select($vehicles_sql, $params);

// Get min/max prices for the filter slider
$price_range = $db->selectOne("SELECT MIN(daily_rate) as min_price, MAX(daily_rate) as max_price FROM vehicles WHERE status = 'available'");
$min_price = floor($price_range['min_price'] ?? 0);
$max_price = ceil($price_range['max_price'] ?? 500);

// Include header
include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">Find Your Perfect Vehicle</h2>
        <a href="rent_or_buy.php" class="bg-accent hover:bg-accent/80 text-white py-2 px-4 rounded">
            Rent or Buy
        </a>
    </div>
    
    <!-- Filters -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="font-medium mb-4">Filter Vehicles</h3>
        
        <form action="" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                        placeholder="Search by name, make or model" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                </div>
                
                <!-- Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type</label>
                    <select 
                        id="type" 
                        name="type" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                        <option value="">All Types</option>
                        <option value="car" <?= isset($_GET['type']) && $_GET['type'] === 'car' ? 'selected' : '' ?>>Car</option>
                        <option value="truck" <?= isset($_GET['type']) && $_GET['type'] === 'truck' ? 'selected' : '' ?>>Truck</option>
                        <option value="bus" <?= isset($_GET['type']) && $_GET['type'] === 'bus' ? 'selected' : '' ?>>Bus</option>
                        <option value="tractor" <?= isset($_GET['type']) && $_GET['type'] === 'tractor' ? 'selected' : '' ?>>Tractor</option>
                        <option value="other" <?= isset($_GET['type']) && $_GET['type'] === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                
                <!-- Fuel Type -->
                <div>
                    <label for="fuel_type" class="block text-sm font-medium text-gray-700 mb-1">Fuel Type</label>
                    <select 
                        id="fuel_type" 
                        name="fuel_type" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                        <option value="">All Fuel Types</option>
                        <option value="gasoline" <?= isset($_GET['fuel_type']) && $_GET['fuel_type'] === 'gasoline' ? 'selected' : '' ?>>Gasoline</option>
                        <option value="diesel" <?= isset($_GET['fuel_type']) && $_GET['fuel_type'] === 'diesel' ? 'selected' : '' ?>>Diesel</option>
                        <option value="electric" <?= isset($_GET['fuel_type']) && $_GET['fuel_type'] === 'electric' ? 'selected' : '' ?>>Electric</option>
                        <option value="hybrid" <?= isset($_GET['fuel_type']) && $_GET['fuel_type'] === 'hybrid' ? 'selected' : '' ?>>Hybrid</option>
                        <option value="other" <?= isset($_GET['fuel_type']) && $_GET['fuel_type'] === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                
                <div class="md:col-span-1 flex items-end">
                    <button type="submit" class="bg-accent hover:bg-accent/80 text-white py-2 px-4 rounded mr-2">
                        Apply Filters
                    </button>
                    <a href="select_vehicle.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded">
                        Reset
                    </a>
                </div>
            </div>
            
            <!-- Price Range -->
            <div>
                <label for="price_range" class="block text-sm font-medium text-gray-700 mb-1">
                    Daily Rate Range: 
                    <span id="min_price_display">
                        $<?= isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : $min_price ?>
                    </span> - 
                    <span id="max_price_display">
                        $<?= isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : $max_price ?>
                    </span>
                </label>
                <div class="flex space-x-4">
                    <input 
                        type="range" 
                        id="min_price" 
                        name="min_price" 
                        min="<?= $min_price ?>" 
                        max="<?= $max_price ?>" 
                        step="5" 
                        value="<?= isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : $min_price ?>"
                        class="w-1/2"
                        oninput="updatePriceDisplay()"
                    >
                    <input 
                        type="range" 
                        id="max_price" 
                        name="max_price" 
                        min="<?= $min_price ?>" 
                        max="<?= $max_price ?>" 
                        step="5" 
                        value="<?= isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : $max_price ?>"
                        class="w-1/2"
                        oninput="updatePriceDisplay()"
                    >
                </div>
            </div>
        </form>
    </div>
    
    <!-- Results count -->
    <div class="mb-4 text-gray-600">
        Found <?= $total_vehicles ?> vehicle<?= $total_vehicles !== 1 ? 's' : '' ?> matching your criteria
    </div>
    
    <!-- Vehicles Grid -->
    <?php if (empty($vehicles)): ?>
        <div class="bg-white p-8 rounded-lg shadow text-center">
            <h3 class="text-lg font-medium mb-2">No vehicles found</h3>
            <p class="text-gray-600 mb-4">Try adjusting your filters to see more results.</p>
            <a href="select_vehicle.php" class="text-accent hover:text-accent/80">Clear all filters</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($vehicles as $vehicle): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <!-- Vehicle Image -->
                    <div class="h-48 relative overflow-hidden bg-gradient-to-br from-blue-50 to-gray-100">
                        <?php 
                        // Récupérer l'image du véhicule depuis la base de données
                        $vehicle_image = $imageManager->getVehicleDisplayImage($vehicle['id']);
                        
                        if ($vehicle_image): 
                            // Image depuis la base de données (BLOB)
                            if (isset($vehicle_image['id'])) {
                                $image_url = $imageManager->getImageUrl($vehicle_image['id']);
                            } else {
                                // Image placeholder (data URL)
                                $image_url = $vehicle_image;
                            }
                        ?>
                            <img 
                                src="<?= $image_url ?>" 
                                alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>" 
                                class="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <!-- Fallback: Icône de voiture si aucune image n'est disponible -->
                            <div class="w-full h-full bg-gradient-to-br from-blue-100 to-gray-200 flex flex-col items-center justify-center">
                                <svg class="w-20 h-20 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                                </svg>
                                <p class="text-gray-500 text-sm mt-2">No image</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="absolute top-2 right-2 px-2 py-1 bg-accent text-white rounded-md">
                            $<?= number_format($vehicle['daily_rate'] ?? 0, 2) ?>/day
                        </div>
                    </div>
                    
                    <!-- Vehicle Info -->
                    <div class="p-4">
                        <h3 class="font-medium text-lg"><?= html_entity_decode(($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?></h3>
                        <p class="text-gray-600"><?= html_entity_decode(($vehicle['year'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?></p>
                        
                        <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="font-medium">Type:</span> <?= ucfirst(htmlspecialchars($vehicle['type'] ?? 'N/A')) ?>
                            </div>
                            <div>
                                <span class="font-medium">Fuel:</span> <?= ucfirst(htmlspecialchars($vehicle['fuel_type'] ?? 'N/A')) ?>
                            </div>
                            <?php if (!empty($vehicle['seats'])): ?>
                            <div>
                                <span class="font-medium">Seats:</span> <?= htmlspecialchars($vehicle['seats']) ?>
                            </div>
                            <?php endif; ?>
                            <div>
                                <span class="font-medium">Color:</span> <?= html_entity_decode($vehicle['color'] ?? 'N/A') ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($vehicle['description'] ?? '')): ?>
                            <div class="mt-3 text-sm text-gray-600">
                                <?= substr(html_entity_decode($vehicle['description'] ?? ''), 0, 100) . (strlen($vehicle['description'] ?? '') > 100 ? '...' : '') ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4 flex justify-between items-center">
                            <div>
                                <span class="font-medium">Purchase:</span>
                                <span class="text-gray-900">$<?= number_format($vehicle['purchase_price'] ?? 0, 2) ?></span>
                            </div>
                            <a 
                                href="rent_or_buy.php?vehicle_id=<?= $vehicle['id'] ?>" 
                                class="bg-accent hover:bg-accent/80 text-white px-4 py-1 rounded"
                            >
                                Select
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-8 flex justify-center">
                <div class="flex space-x-1">
                    <?php
                    // Generate the query string for pagination links
                    $query_parts = [];
                    foreach ($_GET as $key => $value) {
                        if ($key !== 'page' && !empty($value)) {
                            $query_parts[] = urlencode($key) . '=' . urlencode($value);
                        }
                    }
                    $query_string = implode('&', $query_parts);
                    if (!empty($query_string)) $query_string = '&' . $query_string;
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 . $query_string ?>" class="px-3 py-1 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 border border-gray-300 rounded-md">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a 
                            href="?page=<?= $i . $query_string ?>" 
                            class="px-3 py-1 <?= $i === $page ? 'bg-accent text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> text-sm font-medium border border-gray-300 rounded-md"
                        >
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 . $query_string ?>" class="px-3 py-1 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 border border-gray-300 rounded-md">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function updatePriceDisplay() {
    const minPrice = document.getElementById('min_price').value;
    const maxPrice = document.getElementById('max_price').value;
    
    document.getElementById('min_price_display').textContent = '$' + minPrice;
    document.getElementById('max_price_display').textContent = '$' + maxPrice;
    
    // Ensure min doesn't exceed max
    if (parseInt(minPrice) > parseInt(maxPrice)) {
        document.getElementById('min_price').value = maxPrice;
        document.getElementById('min_price_display').textContent = '$' + maxPrice;
    }
}
</script>


<?php
// Chatbot system removed
?>
</body>
</html>

