<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin();
/**
 * Vehicles Management Page
 * 
 * @package VehicSmart
 */

// Set page title
$page_title = 'Manage Vehicles';

// Include database connection
require_once '../config/database.php';

// Get database instance
$db = Database::getInstance();

// Process form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle vehicle deletion
    if (isset($_POST['delete_vehicle'])) {
        $vehicle_id = filter_input(INPUT_POST, 'vehicle_id', FILTER_SANITIZE_NUMBER_INT);
        
        // Vérifier si la table vehicle_images existe
        $check_images_table = "SHOW TABLES LIKE 'vehicle_images'";
        $images_table_exists = $db->select($check_images_table);
        
        // Delete associated images from the filesystem only if the table exists
        if (!empty($images_table_exists)) {
            $images_query = "SELECT image_path FROM vehicle_images WHERE vehicle_id = :vehicle_id";
            $images = $db->select($images_query, ['vehicle_id' => $vehicle_id]);
            
            foreach ($images as $image) {
                $image_path = '../uploads/vehicles/' . $image['image_path'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
        }
        
        // Now delete the vehicle from the database
        try {
            // Begin transaction
            $db->getConnection()->beginTransaction();
            
            // Delete associated images only if the table exists
            if (!empty($images_table_exists)) {
                $db->query("DELETE FROM vehicle_images WHERE vehicle_id = :vehicle_id", ['vehicle_id' => $vehicle_id]);
            }
            
            // Delete rentals associated with this vehicle
            $db->query("DELETE FROM rentals WHERE vehicle_id = :vehicle_id", ['vehicle_id' => $vehicle_id]);
            
            // Delete purchases associated with this vehicle
            $db->query("DELETE FROM purchases WHERE vehicle_id = :vehicle_id", ['vehicle_id' => $vehicle_id]);
            
            // Delete maintenance records
            $db->query("DELETE FROM maintenance WHERE vehicle_id = :vehicle_id", ['vehicle_id' => $vehicle_id]);
            
            // Delete alerts
            $db->query("DELETE FROM alerts WHERE vehicle_id = :vehicle_id", ['vehicle_id' => $vehicle_id]);
            
            // Finally delete the vehicle
            $db->query("DELETE FROM vehicles WHERE id = :id", ['id' => $vehicle_id]);
            
            // Commit transaction
            $db->getConnection()->commit();
            
            $message = 'Vehicle deleted successfully.';
            $message_type = 'success';
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->getConnection()->rollBack();
            $message = 'Error deleting vehicle: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get vehicles with filtering and pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query based on filters
$where_clauses = [];
$query_params = [];

// Filter by type (category_id)
if (isset($_GET['type']) && !empty($_GET['type'])) {
    // Si le type est une valeur numérique, filtrer par category_id
    if (is_numeric($_GET['type'])) {
        $where_clauses[] = "v.category_id = :category_id";
        $query_params['category_id'] = $_GET['type'];
    } else {
        // Sinon, essayer de faire correspondre avec le nom de la catégorie
        $where_clauses[] = "vc.name LIKE :category_name";
        $query_params['category_name'] = '%' . $_GET['type'] . '%';
    }
}

// Filter by status
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_clauses[] = "v.status = :status";
    $query_params['status'] = $_GET['status'];
}

// Filter by search term (brand, model, license plate)
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $where_clauses[] = "(v.brand LIKE :search OR v.model LIKE :search OR v.license_plate LIKE :search)";
    $query_params['search'] = $search;
}

// Build the WHERE clause
$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Count total vehicles with filters
$count_sql = "SELECT COUNT(*) as total 
              FROM vehicles v 
              LEFT JOIN vehicle_categories vc ON v.category_id = vc.id 
              $where_sql";
$total_result = $db->selectOne($count_sql, $query_params);
$total_vehicles = $total_result ? $total_result['total'] : 0;
$total_pages = ceil($total_vehicles / $limit);

// Vérifier si la table vehicle_images existe
$check_images_table = "SHOW TABLES LIKE 'vehicle_images'";
$images_table_exists = $db->select($check_images_table);

// Get vehicles with pagination
// Modifier la façon dont les paramètres de limite et d'offset sont fournis
if (!empty($images_table_exists)) {
    // Si la table existe, inclure la sous-requête pour les images
    $vehicles_sql = "SELECT v.*,
                    (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image,
                    vc.name as category_name
                    FROM vehicles v
                    LEFT JOIN vehicle_categories vc ON v.category_id = vc.id
                    $where_sql
                    ORDER BY v.created_at DESC
                    LIMIT $limit OFFSET $offset";
} else {
    // Si la table n'existe pas, ne pas inclure la sous-requête pour les images
    $vehicles_sql = "SELECT v.*, 
                    NULL as image,
                    vc.name as category_name
                    FROM vehicles v
                    LEFT JOIN vehicle_categories vc ON v.category_id = vc.id
                    $where_sql
                    ORDER BY v.created_at DESC
                    LIMIT $limit OFFSET $offset";
}

// Nous n'avons plus besoin d'ajouter limit et offset comme paramètres nommés
// car ils sont maintenant directement intégrés à la requête
$vehicles = $db->select($vehicles_sql, $query_params);

// Include header
include 'includes/header.php';
?>

<!-- Page Content -->
<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">Vehicle Inventory</h2>
        <a href="vehicle_form.php" class="bg-accent hover:bg-accent/80 text-white py-2 px-4 rounded">
            Add New Vehicle
        </a>
    </div>
    
    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input 
                    type="text" 
                    id="search" 
                    name="search" 
                    value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                    placeholder="Name, make, model..." 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                >
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type</label>
                <select 
                    id="type" 
                    name="type" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                >
                    <option value="">All Types</option>
                    <option value="1" <?= isset($_GET['type']) && $_GET['type'] === '1' ? 'selected' : '' ?>>Car</option>
                    <option value="2" <?= isset($_GET['type']) && $_GET['type'] === '2' ? 'selected' : '' ?>>Truck</option>
                    <option value="3" <?= isset($_GET['type']) && $_GET['type'] === '3' ? 'selected' : '' ?>>Bus</option>
                    <option value="4" <?= isset($_GET['type']) && $_GET['type'] === '4' ? 'selected' : '' ?>>Tractor</option>
                    <option value="5" <?= isset($_GET['type']) && $_GET['type'] === '5' ? 'selected' : '' ?>>Van</option>
                    <option value="6" <?= isset($_GET['type']) && $_GET['type'] === '6' ? 'selected' : '' ?>>Motorcycle</option>
                    <option value="7" <?= isset($_GET['type']) && $_GET['type'] === '7' ? 'selected' : '' ?>>SUV</option>
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select 
                    id="status" 
                    name="status" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                >
                    <option value="">All Statuses</option>
                    <option value="available" <?= isset($_GET['status']) && $_GET['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="rented" <?= isset($_GET['status']) && $_GET['status'] === 'rented' ? 'selected' : '' ?>>Rented</option>
                    <option value="maintenance" <?= isset($_GET['status']) && $_GET['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    <option value="sold" <?= isset($_GET['status']) && $_GET['status'] === 'sold' ? 'selected' : '' ?>>Sold</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-accent hover:bg-accent/80 text-white py-2 px-4 rounded mr-2">Filter</button>
                <a href="vehicles_manage.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded">Reset</a>
            </div>
        </form>
    </div>

    <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); if (!empty($message)): ?>
        <div class="mb-4 p-4 rounded-md <?= $message_type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
            <?= $message ?>
        </div>
    <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endif; ?>

    <!-- Vehicles List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); if (empty($vehicles)): ?>
            <div class="p-8 text-center text-gray-500">
                No vehicles found. Please try different filters or <a href="vehicle_form.php" class="text-accent hover:underline">add a new vehicle</a>.
            </div>
        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); else: ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pricing</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); foreach ($vehicles as $vehicle): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-12 w-12">
                                        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); if (!empty($vehicle['image'])): ?>
                                            <img src="../uploads/vehicles/<?= htmlspecialchars($vehicle['image']) ?>" alt="<?= htmlspecialchars($vehicle['brand'] ?? 'Vehicle') ?>" class="h-12 w-12 object-cover rounded">
                                        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); else: ?>
                                            <div class="h-12 w-12 rounded bg-gray-200 flex items-center justify-center text-gray-500">
                                                🚗
                                            </div>
                                        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endif; ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($vehicle['brand'] ?? 'Unknown') ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars(($vehicle['model'] ?? '') . ' ' . ($vehicle['year'] ?? '')) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">Type: <?= ucfirst(htmlspecialchars($vehicle['category_name'] ?? 'Unknown')) ?></div>
                                <div class="text-sm text-gray-500">License: <?= htmlspecialchars($vehicle['license_plate'] ?? 'Unknown') ?></div>
                                <div class="text-sm text-gray-500">Fuel: <?= ucfirst(htmlspecialchars($vehicle['engine_type'] ?? 'Unknown')) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin();
                                $status_color = 'gray';
                                switch ($vehicle['status']) {
                                    case 'available':
                                        $status_color = 'green';
                                        break;
                                    case 'rented':
                                        $status_color = 'blue';
                                        break;
                                    case 'maintenance':
                                        $status_color = 'orange';
                                        break;
                                    case 'sold':
                                        $status_color = 'purple';
                                        break;
                                }
                                ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?= $status_color ?>-100 text-<?= $status_color ?>-800">
                                    <?= ucfirst(htmlspecialchars($vehicle['status'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">Daily: $<?= number_format($vehicle['daily_rate'], 2) ?></div>
                                <div class="text-sm text-gray-500">Purchase: $<?= number_format($vehicle['purchase_price'], 2) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="vehicle_form.php?id=<?= $vehicle['id'] ?>" class="text-accent hover:text-accent/80 mr-3">Edit</a>
                                <button 
                                    onclick="confirmDelete(<?= $vehicle['id'] ?>, '<?= htmlspecialchars(addslashes($vehicle['brand'] ?? 'Vehicle')) ?>')" 
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Delete
                                </button>
                                
                                <!-- Hidden form for delete operation -->
                                <form id="delete-form-<?= $vehicle['id'] ?>" action="" method="POST" class="hidden">
                                    <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?>">
                                    <input type="hidden" name="delete_vehicle" value="1">
                                </form>
                            </td>
                        </tr>
                    <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); if ($total_pages > 1): ?>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700">
                            Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_vehicles) ?> of <?= $total_vehicles ?> vehicles
                        </div>
                        <div class="flex space-x-1">
                            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin();
                            // Generate the query string for pagination links
                            $query_string = [];
                            if (isset($_GET['search'])) $query_string[] = 'search=' . urlencode($_GET['search']);
                            if (isset($_GET['type'])) $query_string[] = 'type=' . urlencode($_GET['type']);
                            if (isset($_GET['status'])) $query_string[] = 'status=' . urlencode($_GET['status']);
                            $query_string = implode('&', $query_string);
                            if (!empty($query_string)) $query_string = '&' . $query_string;
                            ?>
                            
                            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 . $query_string ?>" class="px-3 py-1 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 border border-gray-300 rounded-md">
                                    Previous
                                </a>
                            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endif; ?>
                            
                            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a 
                                    href="?page=<?= $i . $query_string ?>" 
                                    class="px-3 py-1 <?= $i === $page ? 'bg-accent text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> text-sm font-medium border border-gray-300 rounded-md"
                                >
                                    <?= $i ?>
                                </a>
                            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endfor; ?>
                            
                            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 . $query_string ?>" class="px-3 py-1 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 border border-gray-300 rounded-md">
                                    Next
                                </a>
                            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endif; ?>
                        </div>
                    </div>
                </div>
            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endif; ?>
        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endif; ?>
    </div>
</div>

<script>
async function confirmDelete(vehicleId, vehicleName) {
    const confirmed = await showConfirmModal({
        title: 'Delete Vehicle',
        message: `Are you sure you want to delete <strong>"${vehicleName}"</strong>? This action cannot be undone and will remove all related data including rental history and maintenance records.`,
        confirmText: 'Delete Vehicle',
        cancelText: 'Cancel',
        type: 'danger'
    });
    
    if (confirmed) {
        document.getElementById(`delete-form-${vehicleId}`).submit();
    }
}
</script>

<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin();
// Include footer
include 'includes/footer.php';
?>
