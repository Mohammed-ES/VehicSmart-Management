<?php
/**
 * View Client
 * Display detailed information about a client
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is admin
requireAdmin();

// Get current user
$user = getCurrentUser();

// Set page title
$pageTitle = 'View Client Details';
$page_title = 'View Client Details';

// Initialize database
$db = Database::getInstance();

// Get client ID from URL
$client_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch client data
$client = null;
$rentals = [];
$total_rentals = 0;
$active_rentals = 0;

if ($client_id > 0) {
    try {
        // Get client information
        $client = $db->selectOne("
            SELECT id, first_name, last_name, email, phone, status, created_at, updated_at
            FROM users 
            WHERE id = ? AND role = 'client'
        ", [$client_id]);
        
        if ($client) {
            // Get rental statistics
            $rental_stats = $db->selectOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
                FROM rentals 
                WHERE user_id = ?
            ", [$client_id]);
            
            $total_rentals = $rental_stats['total'] ?? 0;
            $active_rentals = $rental_stats['active'] ?? 0;
            
            // Get recent rentals
            $rentals = $db->select("
                SELECT r.*, v.brand, v.model, v.year
                FROM rentals r
                LEFT JOIN vehicles v ON r.vehicle_id = v.id
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC
                LIMIT 10
            ", [$client_id]) ?: [];
        }
    } catch (Exception $e) {
        $error = 'Error fetching client data: ' . $e->getMessage();
    }
}

// If client not found, redirect
if (!$client) {
    header('Location: clients_manage.php?error=not_found');
    exit;
}

// Include header
include_once 'includes/header.php';
?>

<div class="p-6 w-full">
    <!-- Page Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                <svg class="w-8 h-8 mr-3 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Client Details
            </h1>
            <p class="text-gray-600 mt-1">Complete information about the client</p>
        </div>
        <div class="flex gap-3">
            <a href="edit_client.php?id=<?= $client['id'] ?>" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Client
            </a>
            <a href="clients_manage.php" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Clients
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Client Information -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Client Profile Card -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden animated-card">
                <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-8 text-center">
                    <div class="w-24 h-24 bg-white rounded-full mx-auto mb-4 flex items-center justify-center">
                        <svg class="w-16 h-16 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-white"><?= e($client['first_name'] . ' ' . $client['last_name']) ?></h2>
                    <p class="text-orange-100 mt-1">Client ID: #<?= $client['id'] ?></p>
                </div>
                
                <div class="p-6">
                    <!-- Status Badge -->
                    <div class="mb-4 text-center">
                        <?php if ($client['status'] === 'active'): ?>
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Active
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                Inactive
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Contact Information -->
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Email</p>
                                <p class="text-gray-800 font-medium"><?= e($client['email']) ?></p>
                            </div>
                        </div>

                        <?php if (!empty($client['phone'])): ?>
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Phone</p>
                                <p class="text-gray-800 font-medium"><?= e($client['phone']) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Member Since</p>
                                <p class="text-gray-800 font-medium"><?= date('F d, Y', strtotime($client['created_at'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="bg-white rounded-lg shadow-md p-6 animated-card">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Rental Statistics
                </h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                        <span class="text-gray-700 font-medium">Total Rentals</span>
                        <span class="text-2xl font-bold text-orange-600"><?= $total_rentals ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                        <span class="text-gray-700 font-medium">Active Rentals</span>
                        <span class="text-2xl font-bold text-green-600"><?= $active_rentals ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Rental History -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden animated-card">
                <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
                    <h2 class="text-xl font-semibold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Recent Rental History
                    </h2>
                </div>

                <div class="p-6">
                    <?php if (empty($rentals)): ?>
                        <div class="text-center py-12">
                            <svg class="w-20 h-20 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-600 mb-2">No Rental History</h3>
                            <p class="text-gray-500">This client hasn't rented any vehicles yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Vehicle</th>
                                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Dates</th>
                                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Total</th>
                                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rentals as $rental): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                                        <td class="py-4 px-4">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-800">
                                                        <?= e($rental['brand'] . ' ' . $rental['model']) ?>
                                                    </p>
                                                    <p class="text-sm text-gray-500"><?= e($rental['year']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <p class="text-sm text-gray-800">
                                                <?= date('M d, Y', strtotime($rental['start_date'])) ?>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                to <?= date('M d, Y', strtotime($rental['end_date'])) ?>
                                            </p>
                                        </td>
                                        <td class="py-4 px-4">
                                            <p class="font-semibold text-gray-800">
                                                $<?= number_format($rental['total_amount'], 2) ?>
                                            </p>
                                        </td>
                                        <td class="py-4 px-4">
                                            <?php
                                            $status_classes = [
                                                'active' => 'bg-green-100 text-green-800',
                                                'completed' => 'bg-gray-100 text-gray-800',
                                                'cancelled' => 'bg-red-100 text-red-800'
                                            ];
                                            $status_class = $status_classes[$rental['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $status_class ?>">
                                                <?= ucfirst($rental['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
