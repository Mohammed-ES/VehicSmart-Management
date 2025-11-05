<?php
/**
 * Rent or Buy Page - For clients to rent or purchase vehicles
 * 
 * @package VehicSmart
 */

// Set page title
$page_title = 'Rent or Buy Vehicle';
// Include database connection and configuration
require_once '../config/database.php';
require_once '../config/config.php';

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
$vehicle = null;
$errors = [];
$success = false;
$transaction_type = isset($_POST['transaction_type']) ? $_POST['transaction_type'] : (isset($_GET['type']) ? $_GET['type'] : 'rent');
$total_cost = 0;
$days = 0;

// Check if a specific vehicle was selected
if (isset($_GET['vehicle_id']) && is_numeric($_GET['vehicle_id'])) {
    $vehicle_id = $_GET['vehicle_id'];
    
    // Vérifier si la table vehicle_images existe
    $check_images_table = "SHOW TABLES LIKE 'vehicle_images'";
    $images_table_exists = $db->select($check_images_table);

    // Get vehicle details
    if (!empty($images_table_exists)) {
        // Si la table existe, inclure la sous-requête pour l'image
        $vehicle_sql = "SELECT v.*, 
                    (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image
                    FROM vehicles v 
                    WHERE v.id = :id AND v.status = 'available'";
    } else {
        // Si la table n'existe pas, ne pas inclure la sous-requête pour l'image
        $vehicle_sql = "SELECT v.*, NULL as image
                    FROM vehicles v 
                    WHERE v.id = :id AND v.status = 'available'";
    }
    $vehicle = $db->selectOne($vehicle_sql, ['id' => $vehicle_id]);
    
    if (!$vehicle) {
        $errors[] = 'Vehicle not found or not available.';
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Validate input
    $vehicle_id = filter_input(INPUT_POST, 'vehicle_id', FILTER_SANITIZE_NUMBER_INT);
    $transaction_type = filter_input(INPUT_POST, 'transaction_type') ? htmlspecialchars(filter_input(INPUT_POST, 'transaction_type')) : null;
    
    if (empty($vehicle_id)) {
        $errors[] = 'Please select a vehicle.';
    }
    
    // Get vehicle details again to make sure it's still available
    $vehicle_sql = "SELECT * FROM vehicles WHERE id = :id AND status = 'available'";
    $vehicle = $db->selectOne($vehicle_sql, ['id' => $vehicle_id]);
    
    if (!$vehicle) {
        $errors[] = 'Vehicle not found or no longer available.';
    } else {
        // Process rental
        if ($transaction_type === 'rent') {
            $start_date = filter_input(INPUT_POST, 'start_date') ? htmlspecialchars(filter_input(INPUT_POST, 'start_date')) : null;
            $end_date = filter_input(INPUT_POST, 'end_date') ? htmlspecialchars(filter_input(INPUT_POST, 'end_date')) : null;
            
            // Validate dates
            if (empty($start_date) || empty($end_date)) {
                $errors[] = 'Please select start and end dates.';
            } elseif (strtotime($start_date) < strtotime(date('Y-m-d'))) {
                $errors[] = 'Start date cannot be in the past.';
            } elseif (strtotime($end_date) <= strtotime($start_date)) {
                $errors[] = 'End date must be after start date.';
            } else {
                // Calculate number of days
                $start = new DateTime($start_date);
                $end = new DateTime($end_date);
                $interval = $start->diff($end);
                $days = $interval->days;
                
                if ($days < 1) {
                    $errors[] = 'Rental period must be at least 1 day.';
                } else {
                    // Calculate cost
                    $total_cost = $days * $vehicle['daily_rate'];
                    
                    // Process payment
                    try {
                        // In a real application, this is where you would call Stripe API
                        // For this example, we'll simulate a successful payment
                        $payment_id = 'test_payment_' . time();
                        
                        // Begin transaction
                        $db->getConnection()->beginTransaction();
                        
                        // Create rental record
                        $rental_data = [
                            'user_id' => $user_id,
                            'vehicle_id' => $vehicle_id,
                            'start_date' => $start_date,
                            'end_date' => $end_date,
                            'total_days' => $days,
                            'daily_rate' => $vehicle['daily_rate'],
                            'total_amount' => $total_cost,
                            'status' => 'pending',
                            'payment_id' => $payment_id,
                            'notes' => 'Created on ' . date('Y-m-d H:i:s')
                        ];
                        
                        $db->query(
                            "INSERT INTO rentals (user_id, vehicle_id, start_date, end_date, total_days, daily_rate, total_amount, status, payment_id, notes)
                             VALUES (:user_id, :vehicle_id, :start_date, :end_date, :total_days, :daily_rate, :total_amount, :status, :payment_id, :notes)",
                            $rental_data
                        );
                        
                        $rental_id = $db->getConnection()->lastInsertId();
                        
                        // Record payment - commented out as payments table doesn't exist yet
                        // To enable payment tracking, create the payments table first
                        /*
                        $payment_data = [
                            'user_id' => $user_id,
                            'amount' => $total_cost,
                            'payment_type' => 'rental',
                            'payment_method' => 'stripe',
                            'stripe_payment_id' => $payment_id,
                            'status' => 'completed',
                            'rental_id' => $rental_id
                        ];
                        
                        $db->query(
                            "INSERT INTO payments (user_id, amount, payment_type, payment_method, stripe_payment_id, status, rental_id)
                             VALUES (:user_id, :amount, :payment_type, :payment_method, :stripe_payment_id, :status, :rental_id)",
                            $payment_data
                        );
                        */
                        
                        // Update vehicle status
                        $db->query(
                            "UPDATE vehicles SET status = 'rented' WHERE id = :id",
                            ['id' => $vehicle_id]
                        );
                        
                        // Create alert for admin
                        $db->query(
                            "INSERT INTO alerts (type, title, message, target_vehicle_id, target_user_id)
                             VALUES ('general', :title, :message, :vehicle_id, :user_id)",
                            [
                                'title' => "New Vehicle Rental",
                                'message' => "New rental: " . ($vehicle['brand'] . ' ' . $vehicle['model'] ?? 'Vehicle') . " rented from $start_date to $end_date",
                                'vehicle_id' => $vehicle_id,
                                'user_id' => $user_id
                            ]
                        );
                        
                        // Commit transaction
                        $db->getConnection()->commit();
                        
                        $success = true;
                    } catch (Exception $e) {
                        // Rollback on error
                        $db->getConnection()->rollBack();
                        $errors[] = 'Error processing rental: ' . $e->getMessage();
                    }
                }
            }
        } 
        // Process purchase
        elseif ($transaction_type === 'purchase') {
            // Calculate cost
            $total_cost = $vehicle['purchase_price'];
            
            // Process payment
            try {
                // In a real application, this is where you would call Stripe API
                // For this example, we'll simulate a successful payment
                $payment_id = 'test_payment_' . time();
                
                // Begin transaction
                $db->getConnection()->beginTransaction();
                
                // Create purchase record
                $purchase_data = [
                    'user_id' => $user_id,
                    'vehicle_id' => $vehicle_id,
                    'purchase_date' => date('Y-m-d'),
                    'amount' => $total_cost,
                    'payment_id' => $payment_id,
                    'status' => 'completed',
                    'notes' => 'Created on ' . date('Y-m-d H:i:s')
                ];
                
                $db->query(
                    "INSERT INTO purchases (user_id, vehicle_id, purchase_date, amount, payment_id, status, notes)
                     VALUES (:user_id, :vehicle_id, :purchase_date, :amount, :payment_id, :status, :notes)",
                    $purchase_data
                );
                
                $purchase_id = $db->getConnection()->lastInsertId();
                
                // Record payment - commented out as payments table doesn't exist yet
                // To enable payment tracking, create the payments table first
                /*
                $payment_data = [
                    'user_id' => $user_id,
                    'amount' => $total_cost,
                    'payment_type' => 'purchase',
                    'payment_method' => 'stripe',
                    'stripe_payment_id' => $payment_id,
                    'status' => 'completed',
                    'purchase_id' => $purchase_id
                ];
                
                $db->query(
                    "INSERT INTO payments (user_id, amount, payment_type, payment_method, stripe_payment_id, status, purchase_id)
                     VALUES (:user_id, :amount, :payment_type, :payment_method, :stripe_payment_id, :status, :purchase_id)",
                    $payment_data
                );
                */
                
                // Update vehicle status
                $db->query(
                    "UPDATE vehicles SET status = 'sold' WHERE id = :id",
                    ['id' => $vehicle_id]
                );
                
                // Create alert for admin
                $db->query(
                    "INSERT INTO alerts (type, title, message, target_vehicle_id, target_user_id)
                     VALUES ('payment_due', :title, :message, :vehicle_id, :user_id)",
                    [
                        'title' => "New Vehicle Purchase",
                        'message' => "New purchase: " . ($vehicle['brand'] . ' ' . $vehicle['model'] ?? 'Vehicle') . " purchased on " . date('Y-m-d'),
                        'vehicle_id' => $vehicle_id,
                        'user_id' => $user_id
                    ]
                );
                
                // Commit transaction
                $db->getConnection()->commit();
                
                $success = true;
            } catch (Exception $e) {
                // Rollback on error
                $db->getConnection()->rollBack();
                $errors[] = 'Error processing purchase: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Invalid transaction type.';
        }
    }
}

// Get available vehicles for the dropdown
$available_vehicles_sql = "SELECT id, brand, model, year, daily_rate, purchase_price FROM vehicles WHERE status = 'available' ORDER BY brand, model";
$available_vehicles = $db->select($available_vehicles_sql);

// Include header
include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold"><?= $transaction_type === 'rent' ? 'Rent a Vehicle' : 'Purchase a Vehicle' ?></h2>
        <div>
            <a 
                href="?type=rent<?= isset($_GET['vehicle_id']) ? '&vehicle_id=' . $_GET['vehicle_id'] : '' ?>" 
                class="<?= $transaction_type === 'rent' ? 'bg-accent text-white' : 'bg-gray-200 text-gray-800' ?> py-2 px-4 rounded-l-md"
            >
                Rent
            </a>
            <a 
                href="?type=purchase<?= isset($_GET['vehicle_id']) ? '&vehicle_id=' . $_GET['vehicle_id'] : '' ?>" 
                class="<?= $transaction_type === 'purchase' ? 'bg-accent text-white' : 'bg-gray-200 text-gray-800' ?> py-2 px-4 rounded-r-md"
            >
                Buy
            </a>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline">
                Your <?= $transaction_type === 'rent' ? 'rental' : 'purchase' ?> has been processed successfully.
            </span>
            <div class="mt-3">
                <a href="my_vehicles.php" class="text-accent hover:text-accent/80 font-medium">View My Vehicles</a>
                <span class="mx-2">|</span>
                <a href="select_vehicle.php" class="text-accent hover:text-accent/80 font-medium">Find More Vehicles</a>
            </div>
        </div>
    <?php elseif (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
            <strong class="font-bold">Error!</strong>
            <ul class="mt-1 list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!$success): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Form -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-medium text-lg mb-4">
                        <?= $transaction_type === 'rent' ? 'Rental Details' : 'Purchase Details' ?>
                    </h3>
                    
                    <form action="" method="POST" id="transaction-form">
                        <input type="hidden" name="transaction_type" value="<?= $transaction_type ?>">
                        
                        <!-- Vehicle Selection -->
                        <div class="mb-4">
                            <label for="vehicle_id" class="block text-sm font-medium text-gray-700 mb-1">Select Vehicle</label>
                            <select 
                                id="vehicle_id" 
                                name="vehicle_id" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                                <?= $vehicle ? 'disabled' : '' ?>
                            >
                                <option value="">-- Select a Vehicle --</option>
                                <?php foreach ($available_vehicles as $available_vehicle): ?>
                                    <option 
                                        value="<?= $available_vehicle['id'] ?>" 
                                        data-daily="<?= $available_vehicle['daily_rate'] ?>"
                                        data-purchase="<?= $available_vehicle['purchase_price'] ?>"
                                        <?= ($vehicle && $vehicle['id'] == $available_vehicle['id']) ? 'selected' : '' ?>
                                    >
                                        <?= html_entity_decode($available_vehicle['brand'] . ' ' . $available_vehicle['model'] . ' ' . $available_vehicle['year']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($vehicle): ?>
                                <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?>">
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($transaction_type === 'rent'): ?>
                            <!-- Rental Dates -->
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                    <input 
                                        type="date" 
                                        id="start_date" 
                                        name="start_date" 
                                        min="<?= date('Y-m-d') ?>" 
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                                        onchange="calculateCost()"
                                    >
                                </div>
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                    <input 
                                        type="date" 
                                        id="end_date" 
                                        name="end_date" 
                                        min="<?= date('Y-m-d', strtotime('+1 day')) ?>" 
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                                        onchange="calculateCost()"
                                    >
                                </div>
                            </div>
                            
                            <!-- Rental Duration -->
                            <div class="mb-4 hidden" id="duration-container">
                                <div class="bg-gray-100 p-3 rounded-md">
                                    <div class="flex justify-between items-center">
                                        <div class="text-sm">
                                            <span class="font-medium">Rental Duration:</span>
                                            <span id="duration-days">0</span> days
                                        </div>
                                        <div class="text-sm">
                                            <span class="font-medium">Daily Rate:</span>
                                            $<span id="daily-rate">0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Payment Information -->
                        <div class="mt-6">
                            <h4 class="font-medium mb-3">Payment Information</h4>
                            
                            <div class="mb-4">
                                <label for="card_number" class="block text-sm font-medium text-gray-700 mb-1">Card Number</label>
                                <input 
                                    type="text" 
                                    id="card_number" 
                                    placeholder="4242 4242 4242 4242" 
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                                >
                                <p class="text-sm text-gray-500 mt-1">For testing, use any of the Stripe test card numbers.</p>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label for="expiry_month" class="block text-sm font-medium text-gray-700 mb-1">Expiry Month</label>
                                    <select 
                                        id="expiry_month" 
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                                    >
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="expiry_year" class="block text-sm font-medium text-gray-700 mb-1">Expiry Year</label>
                                    <select 
                                        id="expiry_year" 
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                                    >
                                        <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                            <option value="<?= $i ?>"><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="cvc" class="block text-sm font-medium text-gray-700 mb-1">CVC</label>
                                    <input 
                                        type="text" 
                                        id="cvc" 
                                        placeholder="123" 
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="mt-6 flex items-center justify-between">
                            <a href="select_vehicle.php" class="text-accent hover:text-accent/80">Back to Vehicle Selection</a>
                            <button 
                                type="submit" 
                                name="submit" 
                                class="bg-accent hover:bg-accent/80 text-white py-2 px-6 rounded font-medium"
                            >
                                <?= $transaction_type === 'rent' ? 'Complete Rental' : 'Complete Purchase' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Summary -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                    <h3 class="font-medium text-lg mb-4">Summary</h3>
                    
                    <?php if ($vehicle): ?>
                        <!-- Selected Vehicle -->
                        <div class="mb-4">
                            <div class="rounded-lg overflow-hidden border mb-3">
                                <?php if (!empty($vehicle['image'])): ?>
                                    <img 
                                        src="../uploads/vehicles/<?= htmlspecialchars($vehicle['image']) ?>" 
                                        alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>" 
                                        class="w-full h-32 object-cover"
                                    >
                                <?php else: ?>
                                    <div class="bg-gray-200 w-full h-32 flex items-center justify-center">
                                        🚗
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h4 class="font-medium"><?= html_entity_decode($vehicle['brand'] ?? '') ?></h4>
                            <p class="text-gray-600"><?= html_entity_decode(($vehicle['model'] ?? '') . ' ' . ($vehicle['year'] ?? '')) ?></p>
                        </div>
                    <?php else: ?>
                        <div class="mb-4 text-gray-500">
                            Please select a vehicle to see summary.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Pricing -->
                    <div class="border-t pt-4 mt-4">
                        <h4 class="font-medium mb-2">Pricing</h4>
                        
                        <?php if ($transaction_type === 'rent'): ?>
                            <div class="flex justify-between mb-2">
                                <span>Daily Rate:</span>
                                <span id="summary-rate"><?= $vehicle ? '$' . number_format($vehicle['daily_rate'] ?? 0, 2) : '---' ?></span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span>Number of Days:</span>
                                <span id="summary-days">---</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between mb-2 font-medium">
                            <span>Total:</span>
                            <span id="summary-total"><?= $vehicle ? ($transaction_type === 'rent' ? '---' : '$' . number_format($vehicle['purchase_price'] ?? 0, 2)) : '---' ?></span>
                        </div>
                    </div>
                    
                    <!-- Additional Information -->
                    <div class="border-t pt-4 mt-4 text-sm text-gray-600">
                        <p>
                            <?php if ($transaction_type === 'rent'): ?>
                                Your payment will be processed securely via Stripe. You will not be charged until the rental is confirmed.
                            <?php else: ?>
                                By proceeding with this purchase, you agree to our terms and conditions. Full payment is required to complete the transaction.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Calculate rental cost based on selected dates
function calculateCost() {
    const startDateEl = document.getElementById('start_date');
    const endDateEl = document.getElementById('end_date');
    const vehicleEl = document.getElementById('vehicle_id');
    
    if (startDateEl.value && endDateEl.value && vehicleEl.value) {
        const startDate = new Date(startDateEl.value);
        const endDate = new Date(endDateEl.value);
        
        // Calculate number of days
        const diffTime = Math.abs(endDate - startDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        // Get daily rate
        const selectedOption = vehicleEl.options[vehicleEl.selectedIndex];
        const dailyRate = parseFloat(selectedOption.dataset.daily);
        
        // Update UI
        document.getElementById('duration-container').classList.remove('hidden');
        document.getElementById('duration-days').textContent = diffDays;
        document.getElementById('daily-rate').textContent = dailyRate.toFixed(2);
        document.getElementById('summary-days').textContent = diffDays;
        document.getElementById('summary-rate').textContent = '$' + dailyRate.toFixed(2);
        
        // Calculate total
        const total = diffDays * dailyRate;
        document.getElementById('summary-total').textContent = '$' + total.toFixed(2);
    }
}

// Update summary when vehicle is selected
document.getElementById('vehicle_id')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    
    if (this.value) {
        const transactionType = document.querySelector('input[name="transaction_type"]').value;
        
        if (transactionType === 'rent') {
            const dailyRate = parseFloat(selectedOption.dataset.daily);
            document.getElementById('summary-rate').textContent = '$' + dailyRate.toFixed(2);
            calculateCost();
        } else {
            const purchasePrice = parseFloat(selectedOption.dataset.purchase);
            document.getElementById('summary-total').textContent = '$' + purchasePrice.toFixed(2);
        }
    }
});

// Initialize date calculations if dates already set
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('start_date')?.value && document.getElementById('end_date')?.value) {
        calculateCost();
    }
});
</script>


<?php
// Chatbot system removed
?>
</body>
</html>
