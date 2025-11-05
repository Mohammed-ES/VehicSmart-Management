<?php
/**
 * My Vehicles Page - For clients to view their rented and purchased vehicles
 * 
 * @package VehicSmart
 */

// Set page title
$page_title = 'My Vehicles';

// Include database connection and configuration
require_once '../config/database.php';

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
$user_id = $_SESSION['user_id'];

// Initialize variables
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'all';
$vehicle_detail = null;

// If viewing a specific rental
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $rental_id = $_GET['id'];
    
    // Vérifier si la table vehicle_images existe
    $check_images_table = "SHOW TABLES LIKE 'vehicle_images'";
    $images_table_exists = $db->select($check_images_table);

    // Get rental details with vehicle info
    if (!empty($images_table_exists)) {
        // Si la table existe, inclure la sous-requête pour l'image
        $rental_sql = "SELECT r.*, v.brand, v.model, v.year, v.license_plate, v.engine_type as fuel_type, v.color,
                      (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image
                      FROM rentals r 
                      JOIN vehicles v ON r.vehicle_id = v.id 
                      WHERE r.id = :id AND r.user_id = :user_id";
    } else {
        // Si la table n'existe pas, ne pas inclure la sous-requête pour l'image
        $rental_sql = "SELECT r.*, v.brand, v.model, v.year, v.license_plate, v.engine_type as fuel_type, v.color,
                      NULL as image
                      FROM rentals r 
                      JOIN vehicles v ON r.vehicle_id = v.id 
                      WHERE r.id = :id AND r.user_id = :user_id";
    }
    
    $vehicle_detail = $db->selectOne($rental_sql, [
        'id' => $rental_id,
        'user_id' => $user_id
    ]);
}

// Vérifier si la table vehicle_images existe
$check_images_table = "SHOW TABLES LIKE 'vehicle_images'";
$images_table_exists = $db->select($check_images_table);

// Get all rented vehicles
if (!empty($images_table_exists)) {
    // Si la table existe, inclure la sous-requête pour l'image
    $rentals_sql = "SELECT r.*, v.brand, v.model, v.year, v.engine_type as type, v.license_plate, 
                    (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image
                    FROM rentals r 
                    JOIN vehicles v ON r.vehicle_id = v.id 
                    WHERE r.user_id = :user_id
                    ORDER BY 
                        CASE 
                            WHEN r.status = 'active' THEN 1 
                            WHEN r.status = 'pending' THEN 2
                            ELSE 3
                        END,
                        r.start_date DESC";
} else {
    // Si la table n'existe pas, ne pas inclure la sous-requête pour l'image
    $rentals_sql = "SELECT r.*, v.brand, v.model, v.year, v.engine_type as type, v.license_plate, 
                    NULL as image
                    FROM rentals r 
                    JOIN vehicles v ON r.vehicle_id = v.id 
                    WHERE r.user_id = :user_id
                    ORDER BY 
                        CASE 
                            WHEN r.status = 'active' THEN 1 
                            WHEN r.status = 'pending' THEN 2
                            ELSE 3
                        END,
                        r.start_date DESC";
}

$rentals = $db->select($rentals_sql, ['user_id' => $user_id]);

// Get all purchased vehicles
if (!empty($images_table_exists)) {
    // Si la table existe, inclure la sous-requête pour l'image
    $purchases_sql = "SELECT p.*, v.brand, v.model, v.year, v.engine_type as type, v.license_plate, 
                    (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image
                    FROM purchases p
                    JOIN vehicles v ON p.vehicle_id = v.id
                    WHERE p.user_id = :user_id
                    ORDER BY p.created_at DESC";
} else {
    // Si la table n'existe pas, ne pas inclure la sous-requête pour l'image
    $purchases_sql = "SELECT p.*, v.brand, v.model, v.year, v.engine_type as type, v.license_plate, 
                    NULL as image
                    FROM purchases p
                    JOIN vehicles v ON p.vehicle_id = v.id
                    WHERE p.user_id = :user_id
                    ORDER BY p.created_at DESC";
}

$purchases = $db->select($purchases_sql, ['user_id' => $user_id]);

// Process rental cancellation
$message = '';
$message_type = '';

if (isset($_POST['cancel_rental']) && isset($_POST['rental_id'])) {
    $rental_id = filter_input(INPUT_POST, 'rental_id', FILTER_SANITIZE_NUMBER_INT);
    
    // Get rental details
    $rental = $db->selectOne("SELECT * FROM rentals WHERE id = :id AND user_id = :user_id", [
        'id' => $rental_id,
        'user_id' => $user_id
    ]);
    
    if ($rental && ($rental['status'] === 'pending' || $rental['status'] === 'active')) {
        try {
            // Begin transaction
            $db->getConnection()->beginTransaction();
            
            // Update rental status
            $db->query("UPDATE rentals SET status = 'cancelled', updated_at = NOW() WHERE id = :id", ['id' => $rental_id]);
            
            // Update vehicle status
            $db->query("UPDATE vehicles SET status = 'available' WHERE id = :id", ['id' => $rental['vehicle_id']]);
            
            // Create alert for admin
            $db->query(
                "INSERT INTO alerts (type, message, vehicle_id, user_id)
                 VALUES ('rental_expiry', :message, :vehicle_id, :user_id)",
                [
                    'message' => "Rental cancelled by client for vehicle ID: {$rental['vehicle_id']}",
                    'vehicle_id' => $rental['vehicle_id'],
                    'user_id' => $user_id
                ]
            );
            
            // Commit transaction
            $db->getConnection()->commit();
            
            $message = 'Rental cancelled successfully.';
            $message_type = 'success';
            
            // Redirect to remove the POST data
            header("Location: my_vehicles.php?cancelled=1");
            exit;
        } catch (Exception $e) {
            // Rollback on error
            $db->getConnection()->rollBack();
            $message = 'Error cancelling rental: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = 'Rental not found or cannot be cancelled.';
        $message_type = 'error';
    }
}

// Show cancellation message if redirected after cancellation
if (isset($_GET['cancelled']) && $_GET['cancelled'] == 1) {
    $message = 'Rental cancelled successfully.';
    $message_type = 'success';
}

// Include header
include 'includes/header.php';
?>

<div class="mb-6">
    <?php if ($vehicle_detail): ?>
        <!-- Vehicle Detail View -->
        <div class="mb-4 flex justify-between items-center">
            <h2 class="text-xl font-semibold">Vehicle Details</h2>
            <a href="my_vehicles.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded">
                Back to My Vehicles
            </a>
        </div>
        
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-0">
                <!-- Vehicle Image -->
                <div class="md:col-span-1 bg-gray-100">
                    <?php if (!empty($vehicle_detail['image'])): ?>
                        <img 
                            src="../uploads/vehicles/<?= htmlspecialchars($vehicle_detail['image'] ?? '') ?>"                                            alt="<?= htmlspecialchars(($vehicle_detail['brand'] ?? '') . ' ' . ($vehicle_detail['model'] ?? '')) ?>" 
                            class="w-full h-full object-cover"
                            style="max-height: 400px;"
                        >
                    <?php else: ?>
                        <div class="w-full h-64 flex items-center justify-center bg-gray-200">
                            <span class="text-6xl">🚗</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Vehicle Information -->
                <div class="md:col-span-2 p-6">
                    <h2 class="text-2xl font-semibold mb-2"><?= html_entity_decode($vehicle_detail['brand'] ?? '') ?></h2>
                    <p class="text-gray-600 text-lg mb-4"><?= html_entity_decode(($vehicle_detail['model'] ?? '') . ' ' . ($vehicle_detail['year'] ?? '')) ?></p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-medium mb-2">Vehicle Information</h3>
                            <ul class="space-y-1 text-gray-700">
                                <li><span class="font-medium">Type:</span> <?= ucfirst(html_entity_decode($vehicle_detail['type'] ?? 'N/A')) ?></li>
                                <li><span class="font-medium">License Plate:</span> <?= html_entity_decode($vehicle_detail['license_plate'] ?? 'N/A') ?></li>
                                <li><span class="font-medium">Fuel Type:</span> <?= ucfirst(html_entity_decode($vehicle_detail['fuel_type'] ?? 'N/A')) ?></li>
                                <li><span class="font-medium">Color:</span> <?= html_entity_decode($vehicle_detail['color'] ?? 'N/A') ?></li>
                            </ul>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-medium mb-2">Rental Information</h3>
                            <ul class="space-y-1 text-gray-700">
                                <li><span class="font-medium">Start Date:</span> <?= date('F j, Y', strtotime($vehicle_detail['start_date'])) ?></li>
                                <li><span class="font-medium">End Date:</span> <?= date('F j, Y', strtotime($vehicle_detail['end_date'])) ?></li>
                                <li><span class="font-medium">Total Cost:</span> $<?= number_format($vehicle_detail['total_amount'], 2) ?></li>
                                <li>
                                    <span class="font-medium">Status:</span> 
                                    <span class="
                                        <?php
                                        switch ($vehicle_detail['status']) {
                                            case 'active': echo 'text-green-600'; break;
                                            case 'pending': echo 'text-yellow-600'; break;
                                            case 'completed': echo 'text-blue-600'; break;
                                            case 'cancelled': echo 'text-red-600'; break;
                                        }
                                        ?>
                                    ">
                                        <?= ucfirst(htmlspecialchars($vehicle_detail['status'] ?? '')) ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <?php if ($vehicle_detail['status'] === 'pending' || $vehicle_detail['status'] === 'active'): ?>
                        <div class="mt-4 border-t pt-4">
                            <h3 class="text-lg font-medium mb-2">Actions</h3>
                            
                            <form action="my_vehicles.php" method="POST" class="cancel-rental-form">
                                <input type="hidden" name="rental_id" value="<?= $vehicle_detail['id'] ?>">
                                <button 
                                    type="button" 
                                    class="cancel-rental-btn bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded"
                                    data-vehicle="<?= e($vehicle_detail['brand'] . ' ' . $vehicle_detail['model']) ?>"
                                >
                                    Cancel Rental
                                </button>
                                <span class="ml-2 text-sm text-gray-600">
                                    <?php if ($vehicle_detail['status'] === 'pending'): ?>
                                        You can cancel this rental without penalties as it's still pending.
                                    <?php else: ?>
                                        Cancelling an active rental may incur fees as per our rental policy.
                                    <?php endif; ?>
                                </span>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($vehicle_detail['notes'] ?? '')): ?>
                        <div class="mt-4 border-t pt-4">
                            <h3 class="text-lg font-medium mb-2">Notes</h3>
                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($vehicle_detail['notes'] ?? '')) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- My Vehicles List View -->
        <div class="mb-4">
            <h2 class="text-xl font-semibold">My Vehicles</h2>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 rounded-md <?= $message_type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <!-- View Toggle -->
        <div class="mb-6">
            <div class="inline-flex rounded-md shadow-sm" role="group">
                <a 
                    href="?view=all" 
                    class="px-4 py-2 text-sm font-medium rounded-l-lg <?= $view_mode === 'all' ? 'bg-accent text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-200"
                >
                    All Vehicles
                </a>
                <a 
                    href="?view=rentals" 
                    class="px-4 py-2 text-sm font-medium <?= $view_mode === 'rentals' ? 'bg-accent text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border-t border-b border-gray-200"
                >
                    Rentals
                </a>
                <a 
                    href="?view=purchases" 
                    class="px-4 py-2 text-sm font-medium rounded-r-lg <?= $view_mode === 'purchases' ? 'bg-accent text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-200"
                >
                    Purchases
                </a>
            </div>
        </div>
        
        <?php if (empty($rentals) && empty($purchases)): ?>
            <div class="bg-white p-8 rounded-lg shadow text-center">
                <h3 class="text-lg font-medium mb-2">No vehicles found</h3>
                <p class="text-gray-600 mb-4">You don't have any rented or purchased vehicles yet.</p>
                <a href="select_vehicle.php" class="inline-block bg-accent hover:bg-accent/80 text-white py-2 px-4 rounded">
                    Find Vehicles
                </a>
            </div>
        <?php else: ?>
            <?php if ($view_mode === 'all' || $view_mode === 'rentals'): ?>
                <?php if (!empty($rentals)): ?>
                    <!-- Rentals Section -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium mb-4">My Rentals</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($rentals as $rental): ?>
                                <div class="bg-white rounded-lg shadow overflow-hidden">
                                    <!-- Vehicle Image -->
                                    <div class="h-48 relative">
                                        <?php if (!empty($rental['image'])): ?>
                                            <img 
                                                src="../uploads/vehicles/<?= htmlspecialchars($rental['image'] ?? '') ?>" 
                                                alt="<?= htmlspecialchars(($rental['brand'] ?? '') . ' ' . ($rental['model'] ?? '')) ?>" 
                                                class="w-full h-full object-cover"
                                            >
                                        <?php else: ?>
                                            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                                🚗
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $status_color = 'bg-gray-500';
                                        switch ($rental['status']) {
                                            case 'active': $status_color = 'bg-green-500'; break;
                                            case 'pending': $status_color = 'bg-yellow-500'; break;
                                            case 'completed': $status_color = 'bg-blue-500'; break;
                                            case 'cancelled': $status_color = 'bg-red-500'; break;
                                        }
                                        ?>
                                        <div class="absolute top-2 right-2 px-2 py-1 <?= $status_color ?> text-white rounded-md">
                                            <?= ucfirst(htmlspecialchars($rental['status'] ?? '')) ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Vehicle Info -->
                                    <div class="p-4">
                                        <h3 class="font-medium text-lg"><?= html_entity_decode($rental['brand'] ?? '') ?></h3>
                                        <p class="text-gray-600"><?= html_entity_decode(($rental['model'] ?? '') . ' ' . ($rental['year'] ?? '')) ?></p>
                                        
                                        <div class="mt-3 text-sm">
                                            <div><span class="font-medium">Type:</span> <?= ucfirst(html_entity_decode($rental['type'] ?? 'N/A')) ?></div>
                                            <div><span class="font-medium">License:</span> <?= html_entity_decode($rental['license_plate'] ?? 'N/A') ?></div>
                                            <div><span class="font-medium">Period:</span> <?= date('M d', strtotime($rental['start_date'] ?? 'now')) ?> - <?= date('M d, Y', strtotime($rental['end_date'] ?? 'now')) ?></div>
                                            <div><span class="font-medium">Total:</span> $<?= number_format($rental['total_amount'] ?? 0, 2) ?></div>
                                        </div>
                                        
                                        <div class="mt-4 flex justify-between items-center">
                                            <a href="?id=<?= $rental['id'] ?>" class="text-accent hover:text-accent/80">
                                                View Details
                                            </a>
                                            
                                            <?php if ($rental['status'] === 'pending' || $rental['status'] === 'active'): ?>
                                                <form action="my_vehicles.php" method="POST" class="inline cancel-rental-form-list">
                                                    <input type="hidden" name="rental_id" value="<?= $rental['id'] ?>">
                                                    <button 
                                                        type="button" 
                                                        class="cancel-rental-btn-list text-red-600 hover:text-red-800 text-sm"
                                                        data-vehicle="<?= e($rental['brand'] . ' ' . $rental['model']) ?>"
                                                    >
                                                        Cancel
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($view_mode === 'all' || $view_mode === 'purchases'): ?>
                <?php if (!empty($purchases)): ?>
                    <!-- Purchases Section -->
                    <div>
                        <h3 class="text-lg font-medium mb-4">My Purchases</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($purchases as $purchase): ?>
                                <div class="bg-white rounded-lg shadow overflow-hidden">
                                    <!-- Vehicle Image -->
                                    <div class="h-48 relative">
                                        <?php if (!empty($purchase['image'])): ?>
                                            <img 
                                                src="../uploads/vehicles/<?= htmlspecialchars($purchase['image'] ?? '') ?>" 
                                                alt="<?= htmlspecialchars(($purchase['brand'] ?? '') . ' ' . ($purchase['model'] ?? '')) ?>" 
                                                class="w-full h-full object-cover"
                                            >
                                        <?php else: ?>
                                            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                                🚗
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="absolute top-2 right-2 px-2 py-1 bg-purple-500 text-white rounded-md">
                                            Purchased
                                        </div>
                                    </div>
                                    
                                    <!-- Vehicle Info -->
                                    <div class="p-4">
                                        <h3 class="font-medium text-lg"><?= html_entity_decode($purchase['brand'] ?? '') ?></h3>
                                        <p class="text-gray-600"><?= html_entity_decode(($purchase['model'] ?? '') . ' ' . ($purchase['year'] ?? '')) ?></p>
                                        
                                        <div class="mt-3 text-sm">
                                            <div><span class="font-medium">Type:</span> <?= ucfirst(html_entity_decode($purchase['type'] ?? 'N/A')) ?></div>
                                            <div><span class="font-medium">License:</span> <?= html_entity_decode($purchase['license_plate'] ?? 'N/A') ?></div>
                                            <div><span class="font-medium">Purchase Date:</span> <?= date('F j, Y', strtotime($purchase['created_at'] ?? 'now')) ?></div>
                                            <div><span class="font-medium">Amount:</span> $<?= number_format($purchase['purchase_price'] ?? 0, 2) ?></div>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <span class="text-sm px-2 py-1 bg-green-100 text-green-800 rounded">
                                                <?= ucfirst(html_entity_decode($purchase['status'] ?? 'N/A')) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>


<script>
// Modern confirmation for rental cancellations
document.addEventListener('DOMContentLoaded', function() {
    // Cancel rental from detail view
    document.querySelectorAll('.cancel-rental-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const vehicle = this.dataset.vehicle;
            const form = this.closest('form');
            
            const confirmed = await showConfirmModal({
                title: 'Cancel Rental',
                message: `Are you sure you want to cancel your rental of <strong>${vehicle}</strong>? This action cannot be undone and may result in cancellation fees.`,
                confirmText: 'Cancel Rental',
                cancelText: 'Keep Rental',
                type: 'danger'
            });
            
            if (confirmed) {
                // Add name attribute for form submission
                const submitBtn = document.createElement('input');
                submitBtn.type = 'hidden';
                submitBtn.name = 'cancel_rental';
                submitBtn.value = '1';
                form.appendChild(submitBtn);
                form.submit();
            }
        });
    });
    
    // Cancel rental from list view
    document.querySelectorAll('.cancel-rental-btn-list').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const vehicle = this.dataset.vehicle;
            const form = this.closest('form');
            
            const confirmed = await showConfirmModal({
                title: 'Cancel Rental',
                message: `Are you sure you want to cancel your rental of <strong>${vehicle}</strong>? This action cannot be undone.`,
                confirmText: 'Cancel Rental',
                cancelText: 'Keep Rental',
                type: 'danger'
            });
            
            if (confirmed) {
                const submitBtn = document.createElement('input');
                submitBtn.type = 'hidden';
                submitBtn.name = 'cancel_rental';
                submitBtn.value = '1';
                form.appendChild(submitBtn);
                form.submit();
            }
        });
    });
});
</script>

<?php
// Chatbot system removed
?>
</body>
</html>
