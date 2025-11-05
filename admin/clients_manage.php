<?php
/**
 * Client Management
 * 
 * Page for managing client accounts
 */

// Include required files
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is admin
requireAdmin();

// Get current user
$user = getCurrentUser();

// Set page title
$pageTitle = 'Manage Clients';
$page_title = 'Manage Clients'; // For backwards compatibility with header.php

// Include header
include_once 'includes/header.php';

// Initialize database
$db = Database::getInstance();

// Initialize variables
$clients = [];
$message = '';
$messageType = '';
$total_clients = 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Check if users table exists
$check_users_table = "SHOW TABLES LIKE 'users'";
$users_table_exists = $db->select($check_users_table);

// Process actions (activate, deactivate, delete)
if (isset($_POST['action']) && isset($_POST['user_id'])) {
    $action = $_POST['action'];
    $user_id = (int)$_POST['user_id'];
    
    try {
        switch ($action) {
            case 'activate':
                $db->update("UPDATE users SET status = 'active' WHERE id = ?", [$user_id]);
                $message = "User #$user_id has been activated.";
                $messageType = 'success';
                break;
                
            case 'deactivate':
                $db->update("UPDATE users SET status = 'inactive' WHERE id = ?", [$user_id]);
                $message = "User #$user_id has been deactivated.";
                $messageType = 'success';
                break;
                
            case 'delete':
                // Check for related records before deletion
                $rentals_check = $db->selectOne("SELECT COUNT(*) as count FROM rentals WHERE user_id = ?", [$user_id]);
                
                if ($rentals_check && $rentals_check['count'] > 0) {
                    $message = "Cannot delete user #$user_id because they have rental records. Deactivate instead.";
                    $messageType = 'error';
                } else {
                    $db->delete("DELETE FROM users WHERE id = ?", [$user_id]);
                    $message = "User #$user_id has been deleted.";
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get clients data
if (!empty($users_table_exists)) {
    try {
        // Count total clients
        $count_query = "SELECT COUNT(*) as count FROM users WHERE role = 'client'";
        $count_result = $db->selectOne($count_query);
        $total_clients = $count_result ? $count_result['count'] : 0;
        
        // Get clients with pagination
        $clients_query = "SELECT id, first_name, last_name, email, phone, status, created_at FROM users 
                          WHERE role = 'client' 
                          ORDER BY created_at DESC 
                          LIMIT $offset, $limit";
        $clients = $db->select($clients_query) ?: [];
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Calculate pagination values
$total_pages = ceil($total_clients / $limit);
?>

<div class="p-6 w-full">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800"><?= $page_title ?></h1>
            <p class="text-gray-600">View and manage client accounts</p>
        </div>
        <div>
            <a href="add_client.php" class="bg-accent hover:bg-accent/80 text-white py-2 px-4 rounded flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add New Client
            </a>
        </div>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="mb-6 p-4 rounded <?= $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($users_table_exists)): ?>
        <div class="bg-white p-8 rounded-lg shadow text-center text-gray-500">
            <p class="mb-4">The users table doesn't exist in the database.</p>
            <p>Go to <a href="database_maintenance.php" class="text-accent hover:underline">Database Maintenance</a> to create the necessary tables.</p>
        </div>
    <?php else: ?>
        <?php if (empty($clients)): ?>
            <div class="bg-white p-8 rounded-lg shadow text-center text-gray-500">
                <p>No client accounts found.</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Joined</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= $client['id'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($client['email']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($client['phone'] ?? 'N/A') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?= $client['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= ucfirst(htmlspecialchars($client['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y', strtotime($client['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="view_client.php?id=<?= $client['id'] ?>" class="text-accent hover:text-accent/80" title="View Details">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <a href="edit_client.php?id=<?= $client['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            
                                            <form method="POST" action="" class="inline delete-form">
                                                <input type="hidden" name="user_id" value="<?= $client['id'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="button" class="text-red-600 hover:text-red-900 delete-btn" title="Delete" data-client-name="<?= e($client['first_name'] . ' ' . $client['last_name']) ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="p-4 border-t border-gray-200 flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_clients) ?> of <?= $total_clients ?> clients
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                                    &laquo; Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                                    Next &raquo;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Modern confirmation dialogs for client actions
// Wait for admin-script.js to be fully loaded
window.addEventListener('load', function() {
    // Delete confirmation
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const clientName = this.dataset.clientName;
            const form = this.closest('form');
            
            const confirmed = await showConfirmModal({
                title: 'Delete Client',
                message: `Are you sure you want to delete <strong>${clientName}</strong>? This action cannot be undone and will remove all client data.`,
                confirmText: 'Delete',
                cancelText: 'Cancel',
                type: 'danger'
            });
            
            if (confirmed) {
                form.submit();
            }
        });
    });
});
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
