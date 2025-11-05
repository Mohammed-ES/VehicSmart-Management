<?php
/**
 * Alerts Management Page
 * 
 * @package VehicSmart
 */

// Set page title
$page_title = 'Manage Alerts';

// Include database connection
require_once '../config/database.php';

// Get database instance
$db = Database::getInstance();

// Process form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle alert action
    if (isset($_POST['mark_read'])) {
        $alert_id = filter_input(INPUT_POST, 'alert_id', FILTER_SANITIZE_NUMBER_INT);
        
        try {
            // Update alert to mark as read
            $db->query("UPDATE alerts SET is_read = 1 WHERE id = :id", ['id' => $alert_id]);
            $message = 'Alert marked as read.';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error updating alert: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get alerts with pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Check if alerts table exists
$check_alerts_table = "SHOW TABLES LIKE 'alerts'";
$alerts_table_exists = $db->select($check_alerts_table);

$alerts = [];
$total_alerts = 0;
$total_pages = 1;

if (!empty($alerts_table_exists)) {
    // Count total alerts
    $count_sql = "SELECT COUNT(*) as total FROM alerts";
    $total_result = $db->selectOne($count_sql);
    $total_alerts = $total_result ? $total_result['total'] : 0;
    $total_pages = ceil($total_alerts / $limit);
    
    // Get alerts with pagination - Use direct values for LIMIT instead of parameters
    $alerts_sql = "SELECT a.*, 
                  v.brand, v.model, v.year, 
                  u.first_name, u.last_name, u.email
                  FROM alerts a
                  LEFT JOIN vehicles v ON a.target_vehicle_id = v.id
                  LEFT JOIN users u ON a.target_user_id = u.id
                  ORDER BY a.created_at DESC
                  LIMIT $offset, $limit";
    $alerts = $db->select($alerts_sql);
}

// Include header
include 'includes/header.php';
?>

<!-- Page Content -->
<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold"><?= $page_title ?></h2>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="mb-4 p-4 rounded-md <?= $message_type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- Alerts List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <?php if (empty($alerts_table_exists)): ?>
            <div class="p-8 text-center text-gray-500">
                <p class="mb-4">The alerts table doesn't exist in the database.</p>
                <p>Go to <a href="database_maintenance.php" class="text-accent hover:underline">Database Maintenance</a> to create the necessary tables.</p>
            </div>
        <?php elseif (empty($alerts)): ?>
            <div class="p-8 text-center text-gray-500">
                <p>No alerts found.</p>
            </div>
        <?php else: ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alert</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($alerts as $alert): ?>
                        <tr class="<?= $alert['is_read'] ? '' : 'bg-blue-50' ?>">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium <?= $alert['is_read'] ? 'text-gray-700' : 'text-blue-800 font-semibold' ?>">
                                    <?= htmlspecialchars($alert['title']) ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?= date('M j, Y g:i A', strtotime($alert['created_at'])) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-700">
                                    <?= nl2br(htmlspecialchars($alert['message'])) ?>
                                </div>
                                <?php if ($alert['target_vehicle_id']): ?>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Vehicle: <?= htmlspecialchars(($alert['brand'] ?? '') . ' ' . ($alert['model'] ?? '') . ' ' . ($alert['year'] ?? '')) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($alert['target_user_id']): ?>
                                    <div class="text-xs text-gray-500 mt-1">
                                        User: <?= htmlspecialchars(($alert['first_name'] ?? '') . ' ' . ($alert['last_name'] ?? '') . ' (' . ($alert['email'] ?? '') . ')') ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($alert['is_read']): ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Read
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Unread
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if (!$alert['is_read']): ?>
                                    <form action="" method="POST" class="inline">
                                        <input type="hidden" name="alert_id" value="<?= $alert['id'] ?>">
                                        <button type="submit" name="mark_read" class="text-blue-600 hover:text-blue-900">
                                            Mark as read
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-gray-400">Already read</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700">
                            Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_alerts) ?> of <?= $total_alerts ?> alerts
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>" class="px-3 py-1 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 border border-gray-300 rounded-md">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a 
                                    href="?page=<?= $i ?>" 
                                    class="px-3 py-1 <?= $i === $page ? 'bg-accent text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> text-sm font-medium border border-gray-300 rounded-md"
                                >
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>" class="px-3 py-1 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 border border-gray-300 rounded-md">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
