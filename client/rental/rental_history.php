<?php
/**
 * Rental History
 * 
 * Shows the client's rental history
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

// Set page title
$pageTitle = 'Rental History'; 
$page_title = 'Rental History'; // For backwards compatibility with header.php

// Get status filter if provided
$status_filter = isset($_GET['status']) ? $_GET['status'] : null;

// Build the query based on filters
$query = "SELECT r.*, v.brand, v.model, v.year, v.license_plate,
          CONCAT(v.brand, ' ', v.model, ' (', v.year, ')') AS vehicle_name,
          (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image
          FROM rentals r
          LEFT JOIN vehicles v ON r.vehicle_id = v.id
          WHERE r.user_id = :user_id";

// Add status filter if provided
$params = ['user_id' => $user_id];
if ($status_filter) {
    $query .= " AND r.status = :status";
    $params['status'] = $status_filter;
}

// Order by most recent first
$query .= " ORDER BY r.start_date DESC";

// Execute the query
try {
    $rentals = $db->select($query, $params);
} catch (Exception $e) {
    $error = 'Unable to fetch rental history: ' . $e->getMessage();
    $rentals = [];
}

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="w-full p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Rental History</h1>
            <p class="text-gray-600">View and manage all your rentals</p>
        </div>
        
        <div>
            <a href="../client_dashboard.php" class="text-accent hover:text-accent/80 flex items-center text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Filter tabs -->
    <div class="mb-6 border-b">
        <nav class="flex space-x-4" aria-label="Tabs">
            <a href="./rental_history.php" class="<?= !$status_filter ? 'border-b-2 border-accent text-accent' : 'text-gray-500 hover:text-gray-700' ?> py-4 px-1 text-center text-sm font-medium">
                All Rentals
            </a>
            <a href="./rental_history.php?status=active" class="<?= $status_filter === 'active' ? 'border-b-2 border-accent text-accent' : 'text-gray-500 hover:text-gray-700' ?> py-4 px-1 text-center text-sm font-medium">
                Active
            </a>
            <a href="./rental_history.php?status=completed" class="<?= $status_filter === 'completed' ? 'border-b-2 border-accent text-accent' : 'text-gray-500 hover:text-gray-700' ?> py-4 px-1 text-center text-sm font-medium">
                Completed
            </a>
            <a href="./rental_history.php?status=cancelled" class="<?= $status_filter === 'cancelled' ? 'border-b-2 border-accent text-accent' : 'text-gray-500 hover:text-gray-700' ?> py-4 px-1 text-center text-sm font-medium">
                Cancelled
            </a>
        </nav>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <!-- Rentals table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <?php if (empty($rentals)): ?>
            <div class="p-6 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-800 mb-1">No rentals found</h3>
                <p class="text-gray-600">
                    <?php if ($status_filter): ?>
                        You don't have any <?= htmlspecialchars($status_filter) ?> rentals.
                    <?php else: ?>
                        You haven't made any rentals yet.
                    <?php endif; ?>
                </p>
                <div class="mt-4">
                    <a href="../select_vehicle.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-accent hover:bg-accent/80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent">
                        Browse Vehicles to Rent
                    </a>
                </div>
            </div>
        <?php else: ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Vehicle
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Rental Period
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cost
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($rentals as $rental): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-lg overflow-hidden">
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
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($rental['vehicle_name']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?= htmlspecialchars($rental['license_plate'] ?? 'No plate info') ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?= date('M j, Y', strtotime($rental['start_date'])) ?> - <?= date('M j, Y', strtotime($rental['end_date'])) ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php
                                    $start = new DateTime($rental['start_date']);
                                    $end = new DateTime($rental['end_date']);
                                    $days = $start->diff($end)->days;
                                    echo $days . ' ' . ($days === 1 ? 'day' : 'days');
                                    ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    $<?= number_format($rental['total_cost'] ?? 0, 2) ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    $<?= number_format($rental['daily_rate'] ?? (($rental['total_cost'] ?? 0) / max(1, $days)), 2) ?>/day
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusClass = '';
                                switch ($rental['status']) {
                                    case 'active':
                                        $statusClass = 'bg-green-100 text-green-800';
                                        break;
                                    case 'completed':
                                        $statusClass = 'bg-blue-100 text-blue-800';
                                        break;
                                    case 'cancelled':
                                        $statusClass = 'bg-red-100 text-red-800';
                                        break;
                                    case 'pending':
                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                        break;
                                    default:
                                        $statusClass = 'bg-gray-100 text-gray-800';
                                }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                    <?= ucfirst($rental['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <a href="./rental_details.php?id=<?= $rental['id'] ?>" class="text-accent hover:text-accent/80 mr-3">
                                    View Details
                                </a>
                                
                                <?php if ($rental['status'] === 'active'): ?>
                                    <a href="./return_vehicle.php?id=<?= $rental['id'] ?>" class="text-gray-600 hover:text-gray-900">
                                        Return
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>


<?php
// Chatbot system removed
?>
</body>
</html>

