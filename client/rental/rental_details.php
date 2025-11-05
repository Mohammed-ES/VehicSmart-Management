<?php
/**
 * Rental Details
 * 
 * Shows the details of a specific rental
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
$pageTitle = 'Rental Details';
$page_title = 'Rental Details'; // For backwards compatibility with header.php

// Fetch rental details with vehicle information
$rental = null;
$error = null;

try {
    if ($rental_id > 0) {
        $query = "SELECT r.*, 
                  v.brand, v.model, v.year, v.license_plate, v.daily_rate,
                  CONCAT(v.brand, ' ', v.model, ' (', v.year, ')') AS vehicle_name,
                  (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image
                  FROM rentals r
                  LEFT JOIN vehicles v ON r.vehicle_id = v.id
                  WHERE r.id = :rental_id AND r.user_id = :user_id";
                  
        $rental = $db->selectOne($query, [
            'rental_id' => $rental_id,
            'user_id' => $user_id
        ]);
        
        if (!$rental) {
            $error = 'Rental not found or you do not have permission to view it.';
        }
    } else {
        $error = 'Invalid rental ID.';
    }
} catch (Exception $e) {
    $error = 'Error retrieving rental details: ' . $e->getMessage();
}

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="w-full p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Rental Details</h1>
            <p class="text-gray-600">View detailed information about your rental</p>
        </div>
        
        <div>
            <a href="./rental_history.php" class="text-accent hover:text-accent/80 flex items-center text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Rental History
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php elseif ($rental): ?>
        <!-- Rental Status Banner -->
        <?php 
        $statusClass = '';
        $statusIcon = '';
        switch ($rental['status']) {
            case 'active':
                $statusClass = 'bg-green-50 border-green-500 text-green-800';
                $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                               </svg>';
                break;
            case 'completed':
                $statusClass = 'bg-blue-50 border-blue-500 text-blue-800';
                $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                              </svg>';
                break;
            case 'cancelled':
                $statusClass = 'bg-red-50 border-red-500 text-red-800';
                $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                              </svg>';
                break;
            case 'pending':
                $statusClass = 'bg-yellow-50 border-yellow-500 text-yellow-800';
                $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                              </svg>';
                break;
            default:
                $statusClass = 'bg-gray-50 border-gray-500 text-gray-800';
                $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                              </svg>';
        }
        ?>
        <div class="rounded-lg border-l-4 <?= $statusClass ?> p-4 mb-6 flex items-center">
            <div class="flex-shrink-0 mr-3">
                <?= $statusIcon ?>
            </div>
            <div>
                <h3 class="font-medium">Rental Status: <?= ucfirst($rental['status']) ?></h3>
                <p class="text-sm">
                    <?php if ($rental['status'] === 'active'): ?>
                        This rental is currently active. Please return the vehicle by <?= date('F j, Y', strtotime($rental['end_date'])) ?>.
                    <?php elseif ($rental['status'] === 'completed'): ?>
                        This rental was completed on <?= date('F j, Y', strtotime($rental['return_date'] ?? $rental['end_date'])) ?>.
                    <?php elseif ($rental['status'] === 'cancelled'): ?>
                        This rental was cancelled.
                    <?php elseif ($rental['status'] === 'pending'): ?>
                        This rental is pending confirmation.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Rental and Vehicle Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Vehicle Details -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Vehicle Details</h3>
                        
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 h-16 w-16 bg-gray-200 rounded-lg overflow-hidden flex items-center justify-center">
                                <?php if (!empty($rental['image'])): ?>
                                    <img src="<?= htmlspecialchars('../../uploads/vehicles/' . $rental['image']) ?>" alt="<?= htmlspecialchars($rental['vehicle_name']) ?>" class="h-full w-full object-cover">
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
                                <dt class="text-sm font-medium text-gray-500">Vehicle Brand</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($rental['brand'] ?? 'N/A') ?></dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Model</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($rental['model'] ?? 'N/A') ?></dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Year</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($rental['year'] ?? 'N/A') ?></dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Daily Rate</dt>
                                <dd class="mt-1 text-sm text-gray-900">$<?= number_format($rental['daily_rate'] ?? 0, 2) ?></dd>
                            </div>
                        </dl>

                        <?php if ($rental['status'] === 'active'): ?>
                            <div class="mt-6">
                                <a href="./return_vehicle.php?id=<?= $rental['id'] ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-accent hover:bg-accent/80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent">
                                    Return Vehicle
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Rental Details -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Rental Information</h3>
                        
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Rental ID</dt>
                                <dd class="mt-1 text-sm text-gray-900">#<?= htmlspecialchars($rental['id']) ?></dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1 text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                        <?= ucfirst($rental['status']) ?>
                                    </span>
                                </dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Rental Start Date</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= date('F j, Y', strtotime($rental['start_date'])) ?></dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Rental End Date</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= date('F j, Y', strtotime($rental['end_date'])) ?></dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Rental Duration</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <?php
                                    $start = new DateTime($rental['start_date']);
                                    $end = new DateTime($rental['end_date']);
                                    $days = $start->diff($end)->days;
                                    echo $days . ' ' . ($days === 1 ? 'day' : 'days');
                                    ?>
                                </dd>
                            </div>
                            <div class="sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500">Return Date</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <?= !empty($rental['return_date']) ? date('F j, Y', strtotime($rental['return_date'])) : 'Not yet returned' ?>
                                </dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Additional Notes</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <?= !empty($rental['notes']) ? nl2br(htmlspecialchars($rental['notes'])) : 'No additional notes' ?>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
                
                <!-- Payment Details -->
                <div class="bg-white rounded-lg shadow overflow-hidden mt-6">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Payment Information</h3>
                        
                        <table class="min-w-full divide-y divide-gray-200 mt-2">
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        Base Rental Rate
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        $<?= number_format($rental['daily_rate'] ?? 0, 2) ?> × <?= $days ?> days
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        $<?= number_format(($rental['daily_rate'] ?? 0) * $days, 2) ?>
                                    </td>
                                </tr>
                                
                                <?php if (!empty($rental['insurance_fee']) && $rental['insurance_fee'] > 0): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        Insurance Fee
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        $<?= number_format($rental['insurance_fee'], 2) ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if (!empty($rental['additional_fees']) && $rental['additional_fees'] > 0): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        Additional Fees
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        $<?= number_format($rental['additional_fees'], 2) ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if (!empty($rental['discount']) && $rental['discount'] > 0): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        Discount
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-right">
                                        -$<?= number_format($rental['discount'], 2) ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                
                                <tr class="bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        Total
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-lg font-bold text-gray-900 text-right">
                                        $<?= number_format($rental['total_cost'] ?? 0, 2) ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex flex-wrap justify-between items-center mt-4">
            <div>
                <a href="./rental_history.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Rental History
                </a>
            </div>
            
            <div class="flex space-x-3 mt-4 sm:mt-0">
                <?php if ($rental['status'] === 'active'): ?>
                <a href="./return_vehicle.php?id=<?= $rental['id'] ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-accent hover:bg-accent/80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Return Vehicle
                </a>
                <?php endif; ?>
                
                <a href="./invoice.php?rental_id=<?= $rental['id'] ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    View Invoice
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>


<?php
// Chatbot system removed
?>
</body>
</html>


