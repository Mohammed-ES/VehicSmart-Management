<?php
/**
 * Profile Page
 * 
 * Allows users to view and edit their profile information
 */

// Include required files
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
requireAuth();

// Get current user
$user = getCurrentUser();

// Set page title
$pageTitle = 'My Profile';

// Initialize database
$db = new Database();

// Handle profile update
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    
    // Validate inputs
    if (!$full_name || !$email) {
        $error = 'Name and email are required fields.';
    } else {
        try {
            // Check if email is already taken by another user
            $existing_user = $db->selectOne(
                "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1",
                [$email, $user['id']]
            );
            
            if ($existing_user) {
                $error = 'This email is already in use by another account.';
            } else {
                // Update user profile
                $db->update(
                    "UPDATE users SET 
                        full_name = ?, 
                        email = ?, 
                        phone = ?,
                        updated_at = NOW()
                     WHERE id = ?",
                    [$full_name, $email, $phone, $user['id']]
                );
                
                // Update session
                $_SESSION['user']['full_name'] = $full_name;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['phone'] = $phone;
                
                $message = 'Profile updated successfully!';
                
                // Refresh user data
                $user = getCurrentUser();
            }
        } catch (Exception $e) {
            error_log('Profile update error: ' . $e->getMessage());
            $error = 'Failed to update profile. Please try again.';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (!$current_password || !$new_password || !$confirm_password) {
        $error = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        try {
            // Get current hashed password
            $user_data = $db->selectOne(
                "SELECT password FROM users WHERE id = ?",
                [$user['id']]
            );
            
            if ($user_data && password_verify($current_password, $user_data['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $db->update(
                    "UPDATE users SET 
                        password = ?,
                        updated_at = NOW()
                     WHERE id = ?",
                    [$hashed_password, $user['id']]
                );
                
                $message = 'Password changed successfully!';
            } else {
                $error = 'Current password is incorrect.';
            }
        } catch (Exception $e) {
            error_log('Password change error: ' . $e->getMessage());
            $error = 'Failed to change password. Please try again.';
        }
    }
}

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">My Profile</h1>
    
    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?= htmlspecialchars($message) ?></span>
            <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentNode.remove()">
                <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <title>Close</title>
                    <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                </svg>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
            <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentNode.remove()">
                <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <title>Close</title>
                    <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                </svg>
            </button>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Profile Information -->
        <div class="md:col-span-2">
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-xl font-semibold text-gray-800">Profile Information</h2>
                </div>
                <div class="p-6">
                    <form action="" method="post">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden mt-6">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-xl font-semibold text-gray-800">Change Password</h2>
                </div>
                <div class="p-6">
                    <form action="" method="post">
                        <input type="hidden" name="action" value="change_password">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input type="password" id="new_password" name="new_password" required minlength="8" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-sm text-gray-500 mt-1">Password must be at least 8 characters long</p>
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Account Summary -->
        <div>
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-xl font-semibold text-gray-800">Account Summary</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-center mb-6">
                        <div class="h-24 w-24 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-blue-800 font-bold text-2xl">
                                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="text-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($user['full_name']) ?></h3>
                        <p class="text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    
                    <div class="border-t pt-4">
                        <div class="flex justify-between py-2">
                            <span class="text-gray-500">Account Type:</span>
                            <span class="font-medium"><?= ucfirst($user['role']) ?></span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-500">Member Since:</span>
                            <span class="font-medium"><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Account Actions -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden mt-6">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-xl font-semibold text-gray-800">Account Actions</h2>
                </div>
                <div class="p-6">
                    <ul class="space-y-2">
                        <li>
                            <a href="rentals.php" class="flex items-center text-blue-600 hover:text-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <span>View My Rentals</span>
                            </a>
                        </li>
                        <li>
                            <a href="messages.php" class="flex items-center text-blue-600 hover:text-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                                <span>Messages</span>
                            </a>
                        </li>
                        <li>
                            <a href="../auth/logout.php" class="flex items-center text-red-600 hover:text-red-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>
