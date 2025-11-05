<?php
/**
 * Admin Profile
 * 
 * Profile management page for administrators
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
$pageTitle = 'Admin Profile';
$page_title = 'Admin Profile'; // For backwards compatibility with header.php

// Include header
include_once 'includes/header.php';

// Initialize database
$db = Database::getInstance();

// Process profile update if form submitted
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $current_password = filter_input(INPUT_POST, 'current_password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $new_password = filter_input(INPUT_POST, 'new_password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    try {
        // Update basic profile information
        $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?";
        $db->update($update_query, [$first_name, $last_name, $email, $user['id']]);
        
        // Update password if provided
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $message = 'New passwords do not match';
                $messageType = 'error';
            } else {
                // Verify current password
                $check_password_query = "SELECT password FROM users WHERE id = ?";
                $user_data = $db->selectOne($check_password_query, [$user['id']]);
                
                if (password_verify($current_password, $user_data['password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_password_query = "UPDATE users SET password = ? WHERE id = ?";
                    $db->update($update_password_query, [$hashed_password, $user['id']]);
                    
                    $message = 'Profile and password updated successfully';
                    $messageType = 'success';
                } else {
                    $message = 'Current password is incorrect';
                    $messageType = 'error';
                }
            }
        } else {
            $message = 'Profile updated successfully';
            $messageType = 'success';
        }
        
        // Refresh user data
        $user = getCurrentUser(true);
    } catch (Exception $e) {
        $message = 'Error updating profile: ' . $e->getMessage();
        $messageType = 'error';
    }
}
?>

<div class="p-6 w-full">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">My Profile</h1>
        <p class="text-gray-600">Manage your account information</p>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="mb-6 p-4 rounded <?= $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                    <input 
                        type="text" 
                        id="first_name" 
                        name="first_name" 
                        value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                </div>
                
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                    <input 
                        type="text" 
                        id="last_name" 
                        name="last_name" 
                        value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                </div>
                
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <input 
                        type="text" 
                        id="role" 
                        value="<?= htmlspecialchars($user['role'] ?? 'Administrator') ?>" 
                        disabled
                        class="w-full px-3 py-2 border border-gray-300 bg-gray-50 rounded-md"
                    >
                </div>
            </div>
            
            <h2 class="text-xl font-medium text-gray-800 mb-4">Change Password</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                </div>
                
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                </div>
            </div>
            
            <div class="flex justify-end">
                <button 
                    type="submit" 
                    name="update_profile" 
                    class="px-4 py-2 bg-accent text-white rounded-md hover:bg-accent/80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent"
                >
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>
