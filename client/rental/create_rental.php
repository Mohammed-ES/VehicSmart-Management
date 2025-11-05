<?php
/**
 * Create Rental
 * 
 * Process for creating a new vehicle rental
 */

// Include required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
requireLogin();
requireRole('client');

// Get current user
$user = getCurrentUser();
$user_id = $user['id'];

// Initialize database
$db = Database::getInstance();

// Get vehicle ID from URL parameter
$vehicle_id = isset($_GET['vehicle_id']) ? (int)$_GET['id'] : 0;

// Set page title
$pageTitle = 'Create Rental';
$page_title = 'Create Rental'; // For backwards compatibility with header.php

// Initialize variables
$vehicle = null;
$error = null;
$success = null;

// Fetch vehicle details
try {
    if ($vehicle_id > 0) {
        $query = "SELECT v.*, 
                  CONCAT(v.brand, ' ', v.model, ' (', v.year, ')') AS vehicle_name,
                  (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image
                  FROM vehicles v
                  WHERE v.id = :vehicle_id AND v.status = 'available'";
                  
        $vehicle = $db->selectOne($query, ['vehicle_id' => $vehicle_id]);
        
        if (!$vehicle) {
            $error = 'Vehicle not found or not available for rental.';
        }
    } else {
        $error = 'Invalid vehicle ID.';
    }
} catch (Exception $e) {
    $error = 'Error retrieving vehicle details: ' . $e->getMessage();
}

// Process the rental form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rental_submit']) && $vehicle) {
    // Get form data
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $driver_license = $_POST['driver_license'] ?? '';
    $insurance_option = isset($_POST['insurance']) ? (int)$_POST['insurance'] : 0;
    $pickup_location = $_POST['pickup_location'] ?? '';
    
    // Validate form data
    $errors = [];
    
    if (empty($start_date)) {
        $errors[] = 'Start date is required.';
    }
    
    if (empty($end_date)) {
        $errors[] = 'End date is required.';
    }
    
    if (!empty($start_date) && !empty($end_date)) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $now = new DateTime();
        
        if ($start < $now) {
            $errors[] = 'Start date cannot be in the past.';
        }
        
        if ($end <= $start) {
            $errors[] = 'End date must be after start date.';
        }
    }
    
    if (empty($driver_license)) {
        $errors[] = 'Driver\'s license number is required.';
    }
    
    if (empty($pickup_location)) {
        $errors[] = 'Pickup location is required.';
    }
    
    // If no errors, process the rental
    if (empty($errors)) {
        try {
            // Calculate rental duration
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $duration = $start->diff($end)->days;
            
            if ($duration < 1) {
                $duration = 1; // Minimum 1 day
            }
            
            // Calculate base cost
            $base_cost = $duration * $vehicle['daily_rate'];
            
            // Calculate insurance fee if selected
            $insurance_fee = 0;
            if ($insurance_option) {
                // Calculate insurance as 15% of the base rental cost
                $insurance_fee = $base_cost * 0.15;
            }
            
            // Calculate total cost
            $total_cost = $base_cost + $insurance_fee;
            
            // Create rental record
            $rental_data = [
                'user_id' => $user_id,
                'vehicle_id' => $vehicle_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'driver_license' => $driver_license,
                'pickup_location' => $pickup_location,
                'status' => 'pending',
                'base_cost' => $base_cost,
                'insurance_fee' => $insurance_fee,
                'total_cost' => $total_cost,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $rental_id = $db->insert('rentals', $rental_data);
            
            if ($rental_id) {
                // Update vehicle status to 'reserved'
                $db->update('vehicles', ['status' => 'reserved'], ['id' => $vehicle_id]);
                
                // Set success message and redirect to rental details
                setFlashMessage('success', 'Rental created successfully! Please proceed to payment.');
                header('Location: rental_details.php?id=' . $rental_id);
                exit;
            } else {
                $error = 'Failed to create rental. Please try again.';
            }
        } catch (Exception $e) {
            $error = 'Error creating rental: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="w-full p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Create New Rental</h1>
            <p class="text-gray-600">Fill in the details to rent a vehicle</p>
        </div>
        
        <div>
            <a href="../../index.php" class="text-accent hover:text-accent/80 flex items-center text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Vehicle List
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($vehicle): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Vehicle Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="h-40 bg-gray-200 relative">
                        <?php if (!empty($vehicle['image'])): ?>
                            <img src="<?= htmlspecialchars('../../uploads/vehicles/' . $vehicle['image']) ?>" alt="<?= htmlspecialchars($vehicle['vehicle_name']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-5h2a2 2 0 011.732 1H14a2 2 0 011.9 1.411 2.5 2.5 0 014.1 2.589H19a1 1 0 001-1v-1a2 2 0 00-2-2h-6.1a2 2 0 01-1.401-.586L8.887 8H4a1 1 0 00-1 1v.14l.143-.14A2 2 0 013 8V5a1 1 0 00-1-1h1z" />
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?></h3>
                        <p class="text-gray-600 mb-1">Year: <?= htmlspecialchars($vehicle['year']) ?></p>
                        <p class="text-gray-600 mb-3">License Plate: <?= htmlspecialchars($vehicle['license_plate']) ?></p>
                        
                        <div class="border-t border-gray-200 pt-3 mt-3">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Vehicle Specifications</h4>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="font-medium">Type:</span> <?= htmlspecialchars($vehicle['type'] ?? 'N/A') ?>
                                </div>
                                <div>
                                    <span class="font-medium">Seats:</span> <?= htmlspecialchars($vehicle['seats'] ?? 'N/A') ?>
                                </div>
                                <div>
                                    <span class="font-medium">Transmission:</span> <?= htmlspecialchars($vehicle['transmission'] ?? 'N/A') ?>
                                </div>
                                <div>
                                    <span class="font-medium">Fuel:</span> <?= htmlspecialchars($vehicle['fuel_type'] ?? 'N/A') ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-3 mt-3">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-gray-900">$<?= number_format($vehicle['daily_rate'], 2) ?></span>
                                <span class="text-sm text-gray-600">per day</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Rental Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Rental Information</h2>
                    
                    <form action="" method="post" id="rentalForm">
                        <!-- Rental Dates -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date*</label>
                                <input type="date" id="start_date" name="start_date" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent sm:text-sm">
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date*</label>
                                <input type="date" id="end_date" name="end_date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent sm:text-sm">
                            </div>
                        </div>
                        
                        <!-- Driver's License -->
                        <div class="mb-6">
                            <label for="driver_license" class="block text-sm font-medium text-gray-700 mb-1">Driver's License Number*</label>
                            <input type="text" id="driver_license" name="driver_license" value="<?= htmlspecialchars($_POST['driver_license'] ?? '') ?>" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent sm:text-sm">
                        </div>
                        
                        <!-- Pickup Location -->
                        <div class="mb-6">
                            <label for="pickup_location" class="block text-sm font-medium text-gray-700 mb-1">Pickup Location*</label>
                            <input type="text" id="pickup_location" name="pickup_location" value="<?= htmlspecialchars($_POST['pickup_location'] ?? '') ?>" placeholder="e.g. Main Office - 123 Example Street" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent sm:text-sm">
                        </div>
                        
                        <!-- Insurance Option -->
                        <div class="mb-6">
                            <div class="relative flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="insurance" name="insurance" type="checkbox" value="1" <?= isset($_POST['insurance']) && $_POST['insurance'] ? 'checked' : '' ?> class="focus:ring-accent h-4 w-4 text-accent border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="insurance" class="font-medium text-gray-700">Add Insurance Coverage (15% of rental cost)</label>
                                    <p class="text-gray-500">Provides additional protection against damage and liability during your rental period.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cost Estimate -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-md">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Estimated Cost</h3>
                            
                            <div class="flex justify-between mb-1 text-sm">
                                <span>Daily Rate:</span>
                                <span>$<?= number_format($vehicle['daily_rate'], 2) ?></span>
                            </div>
                            
                            <div id="durationCost" class="flex justify-between mb-1 text-sm hidden">
                                <span>Duration Cost (<span id="duration">0</span> days):</span>
                                <span id="totalDurationCost">$0.00</span>
                            </div>
                            
                            <div id="insuranceCost" class="flex justify-between mb-1 text-sm hidden">
                                <span>Insurance (15%):</span>
                                <span id="totalInsuranceCost">$0.00</span>
                            </div>
                            
                            <div class="flex justify-between font-medium text-gray-800 pt-2 mt-2 border-t border-gray-200">
                                <span>Estimated Total:</span>
                                <span id="estimatedTotal">$0.00</span>
                            </div>
                            
                            <p class="text-xs text-gray-500 mt-2">Final cost may vary based on actual rental duration and additional fees.</p>
                        </div>
                        
                        <!-- Terms Agreement -->
                        <div class="mb-6">
                            <div class="relative flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="terms" name="terms" type="checkbox" required class="focus:ring-accent h-4 w-4 text-accent border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="terms" class="font-medium text-gray-700">I agree to the Terms and Conditions</label>
                                    <p class="text-gray-500">I have read and agree to the <a href="#" class="text-accent hover:underline">rental terms and conditions</a>.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" name="rental_submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-accent hover:bg-accent/80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent">
                                Create Rental
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    // Calculate estimated cost
    const dailyRate = <?= $vehicle ? $vehicle['daily_rate'] : 0 ?>;
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const insuranceCheckbox = document.getElementById('insurance');
    const durationElement = document.getElementById('duration');
    const durationCostElement = document.getElementById('durationCost');
    const totalDurationCostElement = document.getElementById('totalDurationCost');
    const insuranceCostElement = document.getElementById('insuranceCost');
    const totalInsuranceCostElement = document.getElementById('totalInsuranceCost');
    const estimatedTotalElement = document.getElementById('estimatedTotal');
    
    function calculateCost() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (startDateInput.value && endDateInput.value && !isNaN(startDate) && !isNaN(endDate) && endDate >= startDate) {
            // Calculate duration in days
            const timeDiff = endDate.getTime() - startDate.getTime();
            const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
            
            // Calculate costs
            const durationCost = dailyRate * dayDiff;
            const insuranceCost = insuranceCheckbox.checked ? durationCost * 0.15 : 0;
            const totalCost = durationCost + insuranceCost;
            
            // Update display elements
            durationElement.textContent = dayDiff;
            totalDurationCostElement.textContent = '$' + durationCost.toFixed(2);
            totalInsuranceCostElement.textContent = '$' + insuranceCost.toFixed(2);
            estimatedTotalElement.textContent = '$' + totalCost.toFixed(2);
            
            // Show cost breakdown
            durationCostElement.classList.remove('hidden');
            if (insuranceCheckbox.checked) {
                insuranceCostElement.classList.remove('hidden');
            } else {
                insuranceCostElement.classList.add('hidden');
            }
        } else {
            // Hide/reset cost breakdown
            durationCostElement.classList.add('hidden');
            insuranceCostElement.classList.add('hidden');
            estimatedTotalElement.textContent = '$0.00';
        }
    }
    
    // Add event listeners
    startDateInput.addEventListener('input', calculateCost);
    endDateInput.addEventListener('input', calculateCost);
    insuranceCheckbox.addEventListener('change', calculateCost);
    
    // Set minimum date for end date based on start date
    startDateInput.addEventListener('input', function() {
        if (startDateInput.value) {
            const nextDay = new Date(startDateInput.value);
            nextDay.setDate(nextDay.getDate() + 1);
            const nextDayStr = nextDay.toISOString().split('T')[0];
            endDateInput.min = nextDayStr;
            
            // If end date is now invalid, clear it
            if (endDateInput.value && new Date(endDateInput.value) <= new Date(startDateInput.value)) {
                endDateInput.value = '';
            }
        }
    });
</script>
