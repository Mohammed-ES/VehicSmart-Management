<?php
/**
 * Return Vehicle
 * 
 * Process the return of a rented vehicle
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

// Get rental ID from URL parameter
$rental_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Set page title
$pageTitle = 'Return Vehicle';
$page_title = 'Return Vehicle'; // For backwards compatibility with header.php

// Initialize variables
$rental = null;
$error = null;
$success = false;

// Fetch rental details
try {
    if ($rental_id > 0) {
        $query = "SELECT r.*, 
                  v.brand, v.model, v.year, v.license_plate, v.daily_rate,
                  CONCAT(v.brand, ' ', v.model, ' (', v.year, ')') AS vehicle_name,
                  (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image
                  FROM rentals r
                  LEFT JOIN vehicles v ON r.vehicle_id = v.id
                  WHERE r.id = :rental_id AND r.user_id = :user_id AND r.status = 'active'";
                  
        $rental = $db->selectOne($query, [
            'rental_id' => $rental_id,
            'user_id' => $user_id
        ]);
        
        if (!$rental) {
            $error = 'Rental not found or you do not have permission to return this vehicle.';
        }
    } else {
        $error = 'Invalid rental ID.';
    }
    
    // Calculate rental information
    if ($rental) {
        $start_date = new DateTime($rental['start_date']);
        $end_date = new DateTime($rental['end_date']);
        $today = new DateTime();
        $rental_duration = $start_date->diff($end_date)->days;
        $is_late = $today > $end_date;
        
        if ($is_late) {
            $late_days = $end_date->diff($today)->days;
            $late_fee = $late_days * ($rental['daily_rate'] * 1.5); // 50% extra for late days
        } else {
            $late_days = 0;
            $late_fee = 0;
        }
        
        // Calculate total cost
        $base_cost = $rental_duration * $rental['daily_rate'];
        $insurance_fee = $rental['insurance_fee'] ?? 0;
        $additional_fees = $rental['additional_fees'] ?? 0;
        $discount = $rental['discount'] ?? 0;
        $total_cost = $base_cost + $insurance_fee + $additional_fees + $late_fee - $discount;
    }
} catch (Exception $e) {
    $error = 'Error retrieving rental details: ' . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_vehicle']) && $rental) {
    try {
        // Update the rental record
        $db->update('rentals', [
            'status' => 'completed',
            'return_date' => date('Y-m-d H:i:s'),
            'actual_return_location' => $_POST['return_location'] ?? $rental['return_location'],
            'late_fee' => $late_fee,
            'total_cost' => $total_cost,
            'payment_status' => 'paid', // Assuming payment is processed automatically
            'condition_notes' => $_POST['condition_notes'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => $rental_id
        ]);
        
        // Update the vehicle status to available
        $db->update('vehicles', [
            'status' => 'available',
            'updated_at' => date('Y-m-d H:i:s')
        ], [
            'id' => $rental['vehicle_id']
        ]);
        
        $success = true;
        setFlashMessage('success', 'Vehicle returned successfully. Thank you for renting with us!');
        
        // Redirect to invoice
        header('Location: invoice.php?rental_id=' . $rental_id);
        exit;
        
    } catch (Exception $e) {
        $error = 'Error processing vehicle return: ' . $e->getMessage();
    }
}

// Include header
include_once 'includes/header.php';
?>

<div class="w-full p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Return Vehicle</h1>
            <p class="text-gray-600">Complete your rental and return your vehicle</p>
        </div>
        
        <div>
            <a href="rental_details.php?id=<?= $rental_id ?>" class="text-accent hover:text-accent/80 flex items-center text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Rental Details
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php elseif ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            Vehicle returned successfully. You will be redirected to your invoice.
        </div>
    <?php elseif ($rental): ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Vehicle Information -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Vehicle Information</h3>
                        
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 h-16 w-16 bg-gray-200 rounded-lg overflow-hidden flex items-center justify-center">
                                <?php if (!empty($rental['image'])): ?>
                                    <img src="<?= htmlspecialchars('../uploads/vehicles/' . $rental['image']) ?>" alt="<?= htmlspecialchars($rental['vehicle_name']) ?>" class="h-full w-full object-cover">
                                <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                        <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-5h2a2 2 0 011.732 1H14a2 2 0 011.9 1.411 2.5 2.5 0 014.1 2.589H19a1 1 0 001-1v-1a2 2 0 00-2-2h-6.1a2 2 0 01-1.401-.586L8.887 8H4a1 1 0 00-1 1v.14l.143-.14A2 2 0 013 8V5a1 1 0 00-1-1h1z" />
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($rental['vehicle_name']) ?></h4>
                                <p class="text-sm text-gray-500">
                                    <?= htmlspecialchars($rental['license_plate'] ?? 'No plate info') ?>
                                </p>
                            </div>
                        </div>
                        
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 mt-4">
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Rental Start Date</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= date('F j, Y', strtotime($rental['start_date'])) ?></dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Rental End Date</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <?= date('F j, Y', strtotime($rental['end_date'])) ?>
                                    <?php if ($is_late): ?>
                                        <span class="text-red-600 text-xs ml-1">(<?= $late_days ?> days overdue)</span>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Return Date</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= date('F j, Y') ?> (Today)</dd>
                            </div>
                        </dl>
                    </div>
                </div>
                
                <!-- Cost Summary -->
                <div class="mt-6 bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Cost Summary</h3>
                        
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Base Rental Cost</dt>
                                <dd class="text-sm text-gray-900">$<?= number_format($base_cost, 2) ?></dd>
                            </div>
                            
                            <?php if ($insurance_fee > 0): ?>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Insurance Fee</dt>
                                <dd class="text-sm text-gray-900">$<?= number_format($insurance_fee, 2) ?></dd>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($additional_fees > 0): ?>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Additional Fees</dt>
                                <dd class="text-sm text-gray-900">$<?= number_format($additional_fees, 2) ?></dd>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($late_fee > 0): ?>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Late Return Fee</dt>
                                <dd class="text-sm text-red-600">$<?= number_format($late_fee, 2) ?></dd>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($discount > 0): ?>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Discount</dt>
                                <dd class="text-sm text-green-600">-$<?= number_format($discount, 2) ?></dd>
                            </div>
                            <?php endif; ?>
                            
                            <div class="pt-3 border-t flex justify-between font-medium">
                                <dt class="text-base text-gray-900">Total Cost</dt>
                                <dd class="text-base text-gray-900">$<?= number_format($total_cost, 2) ?></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
            
            <!-- Return Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Return Your Vehicle</h3>
                        
                        <?php if ($is_late): ?>
                        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">
                                        <strong>Late Return Notice:</strong> Your vehicle is <?= $late_days ?> <?= $late_days === 1 ? 'day' : 'days' ?> overdue. 
                                        A late fee of $<?= number_format($late_fee, 2) ?> has been added to your invoice.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="return_vehicle.php?id=<?= $rental_id ?>">
                            <div class="space-y-6">
                                <div>
                                    <label for="return_location" class="block text-sm font-medium text-gray-700">Return Location</label>
                                    <select id="return_location" name="return_location" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-accent focus:border-accent sm:text-sm">
                                        <option value="Main Office" <?= ($rental['return_location'] == 'Main Office') ? 'selected' : '' ?>>Main Office</option>
                                        <option value="Downtown Branch" <?= ($rental['return_location'] == 'Downtown Branch') ? 'selected' : '' ?>>Downtown Branch</option>
                                        <option value="Airport Location" <?= ($rental['return_location'] == 'Airport Location') ? 'selected' : '' ?>>Airport Location</option>
                                        <option value="Other">Other Location</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="condition_notes" class="block text-sm font-medium text-gray-700">Vehicle Condition Notes</label>
                                    <div class="mt-1">
                                        <textarea id="condition_notes" name="condition_notes" rows="4" class="shadow-sm focus:ring-accent focus:border-accent block w-full sm:text-sm border border-gray-300 rounded-md" placeholder="Please note any damages, fuel level, mileage, etc."></textarea>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Please report any damages or issues with the vehicle to avoid additional charges later.
                                    </p>
                                </div>
                                
                                <div class="bg-gray-50 p-4 rounded-md">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="terms" name="terms" type="checkbox" required class="focus:ring-accent h-4 w-4 text-accent border-gray-300 rounded">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="terms" class="font-medium text-gray-700">I confirm that all information provided is accurate</label>
                                            <p class="text-gray-500">I understand that I am responsible for any damages not reported at the time of return.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end">
                                    <a href="rental_details.php?id=<?= $rental_id ?>" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent mr-3">
                                        Cancel
                                    </a>
                                    <button type="submit" name="return_vehicle" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-accent hover:bg-accent/80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent">
                                        Complete Return & Pay
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Important:</strong> Once you complete the return process, your payment will be processed automatically.
                                An invoice will be generated and you will be redirected to the payment page.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

