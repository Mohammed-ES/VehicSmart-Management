<?php
/**
 * Settings Page
 * 
 * Allows users to configure their account settings
 */

// Include required files
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
requireAuth();

// Get current user
$user = getCurrentUser();

// Set page title
$pageTitle = 'Settings';

// Initialize database
$db = new Database();

// Handle notification settings update
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_notifications') {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
    
    try {
        // Check if settings exist
        $existing_settings = $db->selectOne(
            "SELECT * FROM user_settings WHERE user_id = ?",
            [$user['id']]
        );
        
        if ($existing_settings) {
            // Update existing settings
            $db->update(
                "UPDATE user_settings SET 
                    email_notifications = ?, 
                    sms_notifications = ?,
                    updated_at = NOW()
                 WHERE user_id = ?",
                [$email_notifications, $sms_notifications, $user['id']]
            );
        } else {
            // Insert new settings
            $db->insert(
                "INSERT INTO user_settings 
                    (user_id, email_notifications, sms_notifications, created_at, updated_at)
                 VALUES (?, ?, ?, NOW(), NOW())",
                [$user['id'], $email_notifications, $sms_notifications]
            );
        }
        
        $message = 'Notification settings updated successfully!';
    } catch (Exception $e) {
        error_log('Settings update error: ' . $e->getMessage());
        $error = 'Failed to update notification settings. Please try again.';
    }
}

// Get current settings
try {
    $settings = $db->selectOne(
        "SELECT * FROM user_settings WHERE user_id = ?",
        [$user['id']]
    );
    
    if (!$settings) {
        $settings = [
            'email_notifications' => 1,
            'sms_notifications' => 0
        ];
    }
} catch (Exception $e) {
    error_log('Settings fetch error: ' . $e->getMessage());
    $settings = [
        'email_notifications' => 1,
        'sms_notifications' => 0
    ];
}

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Account Settings</h1>
    
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
        <!-- Settings Navigation -->
        <div class="md:col-span-1">
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <nav class="p-4">
                    <h2 class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-2">Settings</h2>
                    <ul>
                        <li>
                            <a href="#notifications" class="block px-4 py-2 rounded-md bg-blue-50 text-blue-700 font-medium">
                                Notification Preferences
                            </a>
                        </li>
                        <li>
                            <a href="profile.php" class="block px-4 py-2 rounded-md text-gray-700 hover:bg-gray-50 mt-1">
                                Profile Information
                            </a>
                        </li>
                        <li>
                            <a href="#" class="block px-4 py-2 rounded-md text-gray-700 hover:bg-gray-50 mt-1">
                                Payment Methods
                            </a>
                        </li>
                        <li>
                            <a href="#" class="block px-4 py-2 rounded-md text-gray-700 hover:bg-gray-50 mt-1">
                                Privacy Settings
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        
        <!-- Settings Content -->
        <div class="md:col-span-2">
            <!-- Notification Settings -->
            <div id="notifications" class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-xl font-semibold text-gray-800">Notification Preferences</h2>
                </div>
                <div class="p-6">
                    <form action="" method="post">
                        <input type="hidden" name="action" value="update_notifications">
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-medium text-gray-800">Email Notifications</h3>
                                    <p class="text-sm text-gray-500">Receive email updates about your rentals and account activity</p>
                                </div>
                                <div class="flex items-center">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="email_notifications" class="sr-only" <?= $settings['email_notifications'] ? 'checked' : '' ?>>
                                        <div class="relative">
                                            <div class="block bg-gray-300 w-10 h-6 rounded-full transition"></div>
                                            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition <?= $settings['email_notifications'] ? 'transform translate-x-4 bg-blue-600' : '' ?>"></div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="border-t pt-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-base font-medium text-gray-800">SMS Notifications</h3>
                                        <p class="text-sm text-gray-500">Receive text message alerts about your rentals</p>
                                    </div>
                                    <div class="flex items-center">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="sms_notifications" class="sr-only" <?= $settings['sms_notifications'] ? 'checked' : '' ?>>
                                            <div class="relative">
                                                <div class="block bg-gray-300 w-10 h-6 rounded-full transition"></div>
                                                <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition <?= $settings['sms_notifications'] ? 'transform translate-x-4 bg-blue-600' : '' ?>"></div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <?php if (!$user['phone']): ?>
                                    <p class="text-sm text-yellow-600 mt-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <a href="profile.php" class="underline">Add a phone number</a> to your profile to enable SMS notifications.
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="border-t pt-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-base font-medium text-gray-800">Rental Reminders</h3>
                                        <p class="text-sm text-gray-500">Receive reminders about upcoming and ending rentals</p>
                                    </div>
                                    <div class="flex items-center">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="rental_reminders" class="sr-only" checked>
                                            <div class="relative">
                                                <div class="block bg-gray-300 w-10 h-6 rounded-full transition"></div>
                                                <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform translate-x-4 bg-blue-600"></div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border-t pt-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-base font-medium text-gray-800">Marketing Emails</h3>
                                        <p class="text-sm text-gray-500">Receive promotions, deals, and newsletters</p>
                                    </div>
                                    <div class="flex items-center">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="marketing_emails" class="sr-only">
                                            <div class="relative">
                                                <div class="block bg-gray-300 w-10 h-6 rounded-full transition"></div>
                                                <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition"></div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Save Preferences
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Coming Soon Section -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden mt-6">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-xl font-semibold text-gray-800">Coming Soon</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center mb-6">
                        <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-800">Payment Methods</h3>
                            <p class="text-gray-500">Save and manage payment methods for faster checkout</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center mb-6">
                        <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-800">Privacy Settings</h3>
                            <p class="text-gray-500">Control your data and privacy preferences</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-800">Two-Factor Authentication</h3>
                            <p class="text-gray-500">Add an extra layer of security to your account</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle switch animation
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const dot = this.parentNode.querySelector('.dot');
            
            if (this.checked) {
                dot.classList.add('transform', 'translate-x-4', 'bg-blue-600');
            } else {
                dot.classList.remove('transform', 'translate-x-4', 'bg-blue-600');
            }
        });
    });
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>
