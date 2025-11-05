<?php
/**
 * Client Settings Page - For clients to update their profile and preferences
 * 
 * @package VehicSmart
 */

// Set page title
$page_title = 'Settings';

// Include database connection and configuration
require_once '../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Get database instance
$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Initialize variables
$user = null;
$error = null;
$success = null;
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

// Get user data
try {
    $user_sql = "SELECT 
                    id, 
                    first_name,
                    last_name,
                    CONCAT(first_name, ' ', last_name) AS name,
                    email, 
                    phone, 
                    '' AS address, /* Added this as a placeholder since address column wasn't found in the table structure */
                    password, 
                    0 AS email_notifications, /* Added placeholders for these columns if they don't exist */
                    0 AS sms_notifications, 
                    created_at AS last_login, /* Using created_at as fallback for last_login */
                    created_at, 
                    updated_at,
                    NULL AS profile_image
                FROM users 
                WHERE id = :id";
    $user = $db->selectOne($user_sql, ['id' => $user_id]);
    
    if (!$user) {
        // User not found in database, log them out
        header("Location: ../auth/logout.php");
        exit;
    }
} catch (Exception $e) {
    $error = "Database error: Unable to retrieve user information";
    $user = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'email_notifications' => 0,
        'sms_notifications' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => date('Y-m-d H:i:s')
    ];
}

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Profile update form
    if (isset($_POST['update_profile'])) {
        $name = trim(filter_input(INPUT_POST, 'name') ?: '');
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $phone = trim(filter_input(INPUT_POST, 'phone') ?: '');
        $address = trim(filter_input(INPUT_POST, 'address') ?: '');
        
        // Validation
        if (empty($name)) {
            $error = 'Full name is required.';
        } elseif (empty($email)) {
            $error = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($name) > 100) {
            $error = 'Full name cannot exceed 100 characters.';
        } else {
            // Check if email is already in use by another user
            try {
                $existing_user = $db->selectOne(
                    "SELECT id FROM users WHERE email = :email AND id != :id",
                    ['email' => $email, 'id' => $user_id]
                );
                
                if ($existing_user) {
                    $error = 'Email address is already in use by another account.';
                } else {
                    try {
                        // Handle profile image upload if provided
                        $profile_image = null;
                        $profile_image_sql = '';
                        $profile_image_params = [];
                        
                        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                            $max_size = 2 * 1024 * 1024; // 2MB
                            
                            $file_info = $_FILES['profile_image'];
                            $file_type = mime_content_type($file_info['tmp_name']);
                            $file_size = $file_info['size'];
                            
                            // Validate file type and size
                            if (!in_array($file_type, $allowed_types)) {
                                throw new Exception('Invalid file type. Please upload JPEG, PNG, or GIF images only.');
                            }
                            
                            if ($file_size > $max_size) {
                                throw new Exception('File size exceeds the 2MB limit.');
                            }
                            
                            // Generate unique filename
                            $file_extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
                            $new_filename = $user_id . '_' . time() . '.' . $file_extension;
                            
                            // Ensure uploads directory exists
                            $upload_dir = '../uploads/profiles/';
                            if (!file_exists($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            // Move uploaded file
                            $upload_path = $upload_dir . $new_filename;
                            if (move_uploaded_file($file_info['tmp_name'], $upload_path)) {
                                $profile_image = $new_filename;
                                $profile_image_sql = ', profile_image = :profile_image';
                                $profile_image_params = ['profile_image' => $profile_image];
                            } else {
                                throw new Exception('Failed to upload profile image.');
                            }
                        }
                        
                        // Split name into first and last name
                        $name_parts = explode(' ', $name, 2);
                        $first_name = $name_parts[0];
                        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
                        
                        // Check if address column exists
                        $check_address_column = $db->query("SHOW COLUMNS FROM users LIKE 'address'");
                        $address_column_exists = $check_address_column->rowCount() > 0;
                        
                        // Prepare base query and parameters
                        $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone";
                        $params = [
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'email' => $email,
                            'phone' => $phone,
                            'id' => $user_id
                        ];
                        
                        // Add address if column exists
                        if ($address_column_exists && !empty($address)) {
                            $query .= ", address = :address";
                            $params['address'] = $address;
                        }
                        
                        // Add profile image if provided
                        $query .= $profile_image_sql;
                        $params = array_merge($params, $profile_image_params);
                        
                        // Add updated_at and WHERE clause
                        $query .= ", updated_at = NOW() WHERE id = :id";
                        
                        // Execute query
                        $db->query($query, $params);
                        
                        // Update session data
                        $_SESSION['user']['full_name'] = $name;
                        $_SESSION['user']['email'] = $email;
                        
                        $success = 'Profile updated successfully.';
                        $active_tab = 'profile';
                        
                        // Refresh user data
                        $user = $db->selectOne($user_sql, ['id' => $user_id]);
                    } catch (Exception $e) {
                        $error = 'Error updating profile: ' . $e->getMessage();
                    }
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
    
    // Password change form
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New password and confirmation do not match.';
        } elseif (strlen($new_password) < 8) {
            $error = 'New password must be at least 8 characters long.';
        } else {
            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                try {
                    // Hash new password
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update password in database
                    $db->query(
                        "UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id",
                        ['password' => $password_hash, 'id' => $user_id]
                    );
                    
                    $success = 'Password changed successfully.';
                    $active_tab = 'security';
                } catch (Exception $e) {
                    $error = 'Error changing password: ' . $e->getMessage();
                }
            }
        }
    }
    
    // Notification preferences form
    if (isset($_POST['update_preferences'])) {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        
        try {
            // Check if notification columns exist
            $check_columns = $db->query("SHOW COLUMNS FROM users LIKE 'email_notifications'");
            $columns_exist = $check_columns->rowCount() > 0;
            
            if ($columns_exist) {
                // Update notification preferences
                $db->query(
                    "UPDATE users SET 
                     email_notifications = :email_notifications, 
                     sms_notifications = :sms_notifications, 
                     updated_at = NOW() 
                     WHERE id = :id",
                    [
                        'email_notifications' => $email_notifications,
                        'sms_notifications' => $sms_notifications,
                        'id' => $user_id
                    ]
                );
            } else {
                // Notifications settings not supported in database
                $success = 'Your preferences have been noted. Notification features will be available soon.';
            }
            
            $success = 'Notification preferences updated successfully.';
            $active_tab = 'notifications';
            
            // Refresh user data
            $user = $db->selectOne($user_sql, ['id' => $user_id]);
        } catch (Exception $e) {
            $error = 'Error updating preferences: ' . $e->getMessage();
        }
    }
}

// Include header
include 'includes/header.php';
?>

<style>
    /* Dark mode styles */
    .dark {
        color-scheme: dark;
    }
    
    .dark body {
        background-color: #121212;
        color: #e5e5e5;
    }
    
    .dark .bg-white {
        background-color: #1e1e1e;
    }
    
    .dark .text-gray-700,
    .dark .text-gray-800,
    .dark .text-gray-900 {
        color: #e5e5e5;
    }
    
    .dark .text-gray-500,
    .dark .text-gray-600 {
        color: #9ca3af;
    }
    
    .dark .bg-gray-50 {
        background-color: #2d2d2d;
    }
    
    .dark .border-gray-300 {
        border-color: #4b5563;
    }
    
    .dark .border-b {
        border-color: #4b5563;
    }
    
    .dark .shadow-md,
    .dark .shadow-lg {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    /* Accent colors */
    .accent-blue .border-accent {
        border-color: #3b82f6;
    }
    
    .accent-blue .text-accent {
        color: #3b82f6;
    }
    
    .accent-blue .bg-accent {
        background-color: #3b82f6;
    }
    
    .accent-blue .hover\:bg-accent\/80:hover {
        background-color: rgba(59, 130, 246, 0.8);
    }
    
    .accent-green .border-accent {
        border-color: #10b981;
    }
    
    .accent-green .text-accent {
        color: #10b981;
    }
    
    .accent-green .bg-accent {
        background-color: #10b981;
    }
    
    .accent-green .hover\:bg-accent\/80:hover {
        background-color: rgba(16, 185, 129, 0.8);
    }
    
    .accent-purple .border-accent {
        border-color: #8b5cf6;
    }
    
    .accent-purple .text-accent {
        color: #8b5cf6;
    }
    
    .accent-purple .bg-accent {
        background-color: #8b5cf6;
    }
    
    .accent-purple .hover\:bg-accent\/80:hover {
        background-color: rgba(139, 92, 246, 0.8);
    }
    
    .accent-red .border-accent {
        border-color: #ef4444;
    }
    
    .accent-red .text-accent {
        color: #ef4444;
    }
    
    .accent-red .bg-accent {
        background-color: #ef4444;
    }
    
    .accent-red .hover\:bg-accent\/80:hover {
        background-color: rgba(239, 68, 68, 0.8);
    }
    
    .accent-amber .border-accent {
        border-color: #f59e0b;
    }
    
    .accent-amber .text-accent {
        color: #f59e0b;
    }
    
    .accent-amber .bg-accent {
        background-color: #f59e0b;
    }
    
    .accent-amber .hover\:bg-accent\/80:hover {
        background-color: rgba(245, 158, 11, 0.8);
    }
    
    /* Animations */
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(1rem); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes fade-out {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(1rem); }
    }
    
    .animate-fade-in {
        animation: fade-in 0.3s ease-out forwards;
    }
    
    .animate-fade-out {
        animation: fade-out 0.3s ease-out forwards;
    }
</style>

<div class="mb-6">
    <div class="mb-4">
        <h2 class="text-xl font-semibold">Account Settings</h2>
    </div>
    
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 p-4 rounded mb-4 flex items-start">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 mt-0.5 text-red-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 p-4 rounded mb-4 flex items-start">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 mt-0.5 text-green-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div><?= htmlspecialchars($success) ?></div>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow relative">
        <!-- Settings Tabs -->
        <div class="border-b overflow-x-auto">
            <nav class="flex flex-nowrap min-w-full" aria-label="Tabs">
                <a 
                    href="?tab=profile" 
                    class="py-4 px-8 border-b-2 font-medium text-sm <?= $active_tab === 'profile' ? 'border-accent text-accent' : 'border-transparent text-gray-600 hover:text-gray-700 hover:border-gray-300' ?>"
                >
                    <i class="fas fa-user mr-2"></i>
                    Profile
                </a>
                <a 
                    href="?tab=security" 
                    class="py-4 px-8 border-b-2 font-medium text-sm <?= $active_tab === 'security' ? 'border-accent text-accent' : 'border-transparent text-gray-600 hover:text-gray-700 hover:border-gray-300' ?>"
                >
                    <i class="fas fa-lock mr-2"></i>
                    Security
                </a>
                <a 
                    href="?tab=notifications" 
                    class="py-4 px-8 border-b-2 font-medium text-sm <?= $active_tab === 'notifications' ? 'border-accent text-accent' : 'border-transparent text-gray-600 hover:text-gray-700 hover:border-gray-300' ?>"
                >
                    <i class="fas fa-bell mr-2"></i>
                    Notifications
                </a>
                <a 
                    href="?tab=payment" 
                    class="py-4 px-8 border-b-2 font-medium text-sm <?= $active_tab === 'payment' ? 'border-accent text-accent' : 'border-transparent text-gray-600 hover:text-gray-700 hover:border-gray-300' ?>"
                >
                    <i class="fas fa-credit-card mr-2"></i>
                    Payment Methods
                </a>
                <a 
                    href="?tab=summary" 
                    class="py-4 px-8 border-b-2 font-medium text-sm <?= $active_tab === 'summary' ? 'border-accent text-accent' : 'border-transparent text-gray-600 hover:text-gray-700 hover:border-gray-300' ?>"
                >
                    <i class="fas fa-chart-bar mr-2"></i>
                    Account Summary
                </a>
            </nav>
        </div>
        
        <!-- Tab Content -->
        <div class="p-6">
            <!-- Profile Tab -->
            <?php if ($active_tab === 'profile'): ?>
                <form action="settings.php?tab=profile" method="POST" enctype="multipart/form-data" id="profile-form" novalidate>
                    <div class="bg-white rounded-lg border shadow-sm p-6 mb-6">
                        <h3 class="text-lg font-medium mb-6 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                            Personal Information
                        </h3>
                        
                        <div class="mb-6">
                            <div class="flex flex-col md:flex-row items-center md:items-start mb-6">
                                <div class="mb-4 md:mb-0 md:mr-6">
                                    <div class="relative group">
                                        <div class="h-24 w-24 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden border-4 border-white shadow">
                                            <?php if (!empty($user['profile_image'])): ?>
                                                <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile picture" class="h-full w-full object-cover">
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                        <div class="absolute inset-0 bg-black bg-opacity-40 rounded-full opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity cursor-pointer">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label for="profile_image" class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
                                    <input 
                                        type="file" 
                                        id="profile_image" 
                                        name="profile_image" 
                                        class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-accent/10 file:text-accent hover:file:bg-accent/20"
                                        accept="image/jpeg,image/png,image/gif"
                                    >
                                    <p class="text-xs text-gray-500 mt-1">Maximum file size: 2MB. Supported formats: JPEG, PNG, GIF</p>
                                </div>
                            </div>
                            
                            <!-- Name and Contact Information Section -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 mb-6">
                                <h4 class="font-medium text-gray-700 mb-4 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Basic Information
                                </h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                        <input 
                                            type="text" 
                                            id="first_name" 
                                            name="first_name" 
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50" 
                                            value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" 
                                            required
                                        >
                                    </div>
                                    
                                    <div>
                                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                        <input 
                                            type="text" 
                                            id="last_name" 
                                            name="last_name" 
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50" 
                                            value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" 
                                            required
                                        >
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <input 
                                                type="email" 
                                                id="email" 
                                                name="email" 
                                                class="w-full border-gray-300 rounded-md shadow-sm pl-10 focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50" 
                                                value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                                                required
                                            >
                                        </div>
                                        <p class="text-red-500 text-xs mt-1 hidden" id="email-error"></p>
                                    </div>
                                    
                                    <div>
                                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                </svg>
                                            </div>
                                            <input 
                                                type="tel" 
                                                id="phone" 
                                                name="phone" 
                                                class="w-full border-gray-300 rounded-md shadow-sm pl-10 focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50" 
                                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                                placeholder="(123) 456-7890"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Address Section -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                                <h4 class="font-medium text-gray-700 mb-4 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Address Information
                                </h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label for="address_line1" class="block text-sm font-medium text-gray-700 mb-1">Address Line 1</label>
                                        <input 
                                            type="text" 
                                            id="address_line1" 
                                            name="address_line1" 
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50" 
                                            value="<?= htmlspecialchars($user['address_line1'] ?? '') ?>"
                                            placeholder="Street address, P.O. box, company name, c/o"
                                        >
                                    </div>
                                    
                                    <div class="md:col-span-2">
                                        <label for="address_line2" class="block text-sm font-medium text-gray-700 mb-1">Address Line 2</label>
                                        <input 
                                            type="text" 
                                            id="address_line2" 
                                            name="address_line2" 
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50" 
                                            value="<?= htmlspecialchars($user['address_line2'] ?? '') ?>"
                                            placeholder="Apartment, suite, unit, building, floor, etc."
                                        >
                                    </div>
                                    
                                    <div>
                                        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                        <input 
                                            type="text" 
                                            id="city" 
                                            name="city" 
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50" 
                                            value="<?= htmlspecialchars($user['city'] ?? '') ?>"
                                        >
                                    </div>
                                    
                                    <div>
                                        <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State / Province</label>
                                        <input 
                                            type="text" 
                                            id="state" 
                                            name="state" 
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50" 
                                            value="<?= htmlspecialchars($user['state'] ?? '') ?>"
                                        >
                                    </div>
                                    
                                    <div>
                                        <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Postal / Zip Code</label>
                                        <input 
                                            type="text" 
                                            id="postal_code" 
                                            name="postal_code" 
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50" 
                                            value="<?= htmlspecialchars($user['postal_code'] ?? '') ?>"
                                        >
                                    </div>
                                    
                                    <div>
                                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                        <select
                                            id="country"
                                            name="country"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50"
                                        >
                                            <option value="">Select a country</option>
                                            <option value="US" <?= isset($user['country']) && $user['country'] == 'US' ? 'selected' : '' ?>>United States</option>
                                            <option value="CA" <?= isset($user['country']) && $user['country'] == 'CA' ? 'selected' : '' ?>>Canada</option>
                                            <option value="AU" <?= isset($user['country']) && $user['country'] == 'AU' ? 'selected' : '' ?>>Australia</option>
                                            <option value="IN" <?= isset($user['country']) && $user['country'] == 'IN' ? 'selected' : '' ?>>India</option>
                                            <option value="MX" <?= isset($user['country']) && $user['country'] == 'MX' ? 'selected' : '' ?>>Mexico</option>
                                            <option value="MR" <?= isset($user['country']) && $user['country'] == 'MR' ? 'selected' : '' ?>>Morocco</option>
                                            <option value="IT" <?= isset($user['country']) && $user['country'] == 'IT' ? 'selected' : '' ?>>Italy</option>
                                            <option value="ES" <?= isset($user['country']) && $user['country'] == 'ES' ? 'selected' : '' ?>>Spain</option>
                                            <option value="NL" <?= isset($user['country']) && $user['country'] == 'NL' ? 'selected' : '' ?>>Netherlands</option>
                                            <option value="DE" <?= isset($user['country']) && $user['country'] == 'DE' ? 'selected' : '' ?>>Germany</option>
                                            <option value="FR" <?= isset($user['country']) && $user['country'] == 'FR' ? 'selected' : '' ?>>France</option>
                                            <option value="JP" <?= isset($user['country']) && $user['country'] == 'JP' ? 'selected' : '' ?>>Japan</option>
                                            <option value="CN" <?= isset($user['country']) && $user['country'] == 'CN' ? 'selected' : '' ?>>China</option>
                                            <option value="BR" <?= isset($user['country']) && $user['country'] == 'BR' ? 'selected' : '' ?>>Brazil</option>

                                            <!-- Add more countries as needed -->
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <button 
                            type="submit" 
                            name="update_profile" 
                            class="bg-accent hover:bg-accent/80 text-white py-2 px-6 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-accent focus:ring-opacity-50 flex items-center"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Save Profile Changes
                        </button>
                        
                        <button 
                            type="reset" 
                            class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 py-2 px-4 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50"
                        >
                            Reset
                        </button>
                    </div>
                </form>
                
                <script>
                    // Show selected image preview
                    document.getElementById('profile_image').addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                // Find the profile image container and update it
                                const imgContainer = document.querySelector('.h-24.w-24.rounded-full');
                                
                                // Clear existing content
                                while (imgContainer.firstChild) {
                                    imgContainer.removeChild(imgContainer.firstChild);
                                }
                                
                                // Create and add new image
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.alt = "Profile picture";
                                img.classList.add('h-full', 'w-full', 'object-cover');
                                imgContainer.appendChild(img);
                            }
                            reader.readAsDataURL(file);
                        }
                    });
                    
                    // Basic form validation
                    document.getElementById('profile-form').addEventListener('submit', function(e) {
                        let valid = true;
                        const emailField = document.getElementById('email');
                        const emailError = document.getElementById('email-error');
                        
                        // Email validation
                        if (emailField.value && !emailField.value.match(/^[^@]+@[^@]+\.[a-zA-Z]{2,}$/)) {
                            emailError.textContent = "Please enter a valid email address";
                            emailError.classList.remove('hidden');
                            valid = false;
                        } else {
                            emailError.classList.add('hidden');
                        }
                        
                        if (!valid) {
                            e.preventDefault();
                        }
                    });
                </script>
                
            <!-- Security Tab -->
            <?php elseif ($active_tab === 'security'): ?>
                <form action="settings.php?tab=security" method="POST">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium mb-4">Change Password</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                <input 
                                    type="password" 
                                    id="current_password" 
                                    name="current_password" 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50" 
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password" 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50" 
                                    required
                                    minlength="8"
                                    oninput="checkPasswordStrength(this.value)"
                                >
                                <div class="mt-2">
                                    <div class="flex items-center">
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div id="password-strength-meter" class="h-2.5 rounded-full bg-red-500" style="width: 0%"></div>
                                        </div>
                                        <span id="password-strength-text" class="ml-2 text-xs text-gray-600">Weak</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">Password must be at least 8 characters long</p>
                                    <ul class="text-xs text-gray-500 mt-2 list-disc pl-5">
                                        <li id="length-check" class="text-red-500">At least 8 characters</li>
                                        <li id="uppercase-check" class="text-red-500">At least one uppercase letter</li>
                                        <li id="lowercase-check" class="text-red-500">At least one lowercase letter</li>
                                        <li id="number-check" class="text-red-500">At least one number</li>
                                        <li id="special-check" class="text-red-500">At least one special character</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50" 
                                    required
                                >
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <h3 class="text-lg font-medium mb-4">Account Activity</h3>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium">Last Login</p>
                                    <p class="text-gray-600">
                                        <?= date('F j, Y g:i A', strtotime($user['last_login'] ?? $user['created_at'])) ?>
                                    </p>
                                </div>
                                
                                <div>
                                    <p class="font-medium">Account Created</p>
                                    <p class="text-gray-600">
                                        <?= date('F j, Y', strtotime($user['created_at'])) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4">
                        <button 
                            type="submit" 
                            name="change_password" 
                            class="bg-accent hover:bg-accent/80 text-white py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-accent focus:ring-opacity-50"
                        >
                            Change Password
                        </button>
                    </div>
                </form>
                
            <!-- Notifications Tab -->
            <?php elseif ($active_tab === 'notifications'): ?>
                <form action="settings.php?tab=notifications" method="POST">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium mb-4">Notification Preferences</h3>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        Customize how and when you receive notifications. You can change these settings at any time.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="bg-white rounded-lg border p-5">
                                <h4 class="font-medium text-gray-900 mb-3">Email Notifications</h4>
                                
                                <div class="space-y-3">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input 
                                                type="checkbox" 
                                                id="email_notifications" 
                                                name="email_notifications" 
                                                class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded" 
                                                <?= isset($user['email_notifications']) && $user['email_notifications'] ? 'checked' : '' ?>
                                            >
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="email_notifications" class="font-medium text-gray-700">All Email Notifications</label>
                                            <p class="text-gray-500">Receive all email notifications (master toggle)</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start pl-7">
                                        <div class="flex items-center h-5">
                                            <input 
                                                type="checkbox" 
                                                id="email_rentals" 
                                                name="email_rentals" 
                                                class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded" 
                                                <?= isset($user['email_rentals']) && $user['email_rentals'] ? 'checked' : '' ?>
                                            >
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="email_rentals" class="font-medium text-gray-700">Rental Updates</label>
                                            <p class="text-gray-500">Notifications about your rental status changes</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start pl-7">
                                        <div class="flex items-center h-5">
                                            <input 
                                                type="checkbox" 
                                                id="email_messages" 
                                                name="email_messages" 
                                                class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded" 
                                                <?= isset($user['email_messages']) && $user['email_messages'] ? 'checked' : '' ?>
                                            >
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="email_messages" class="font-medium text-gray-700">New Messages</label>
                                            <p class="text-gray-500">Be notified when you receive new messages</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start pl-7">
                                        <div class="flex items-center h-5">
                                            <input 
                                                type="checkbox" 
                                                id="email_promos" 
                                                name="email_promos" 
                                                class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded" 
                                                <?= isset($user['email_promos']) && $user['email_promos'] ? 'checked' : '' ?>
                                            >
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="email_promos" class="font-medium text-gray-700">Promotions & Discounts</label>
                                            <p class="text-gray-500">Receive exclusive offers and promotions</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white rounded-lg border p-5">
                                <h4 class="font-medium text-gray-900 mb-3">SMS Notifications</h4>
                                
                                <div class="space-y-3">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input 
                                                type="checkbox" 
                                                id="sms_notifications" 
                                                name="sms_notifications" 
                                                class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded" 
                                                <?= isset($user['sms_notifications']) && $user['sms_notifications'] ? 'checked' : '' ?>
                                            >
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="sms_notifications" class="font-medium text-gray-700">All SMS Notifications</label>
                                            <p class="text-gray-500">Receive all SMS notifications (master toggle)</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start pl-7">
                                        <div class="flex items-center h-5">
                                            <input 
                                                type="checkbox" 
                                                id="sms_rentals" 
                                                name="sms_rentals" 
                                                class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded" 
                                                <?= isset($user['sms_rentals']) && $user['sms_rentals'] ? 'checked' : '' ?>
                                            >
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="sms_rentals" class="font-medium text-gray-700">Rental Status Updates</label>
                                            <p class="text-gray-500">Receive text alerts for rental confirmations and returns</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start pl-7">
                                        <div class="flex items-center h-5">
                                            <input 
                                                type="checkbox" 
                                                id="sms_security" 
                                                name="sms_security" 
                                                class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded" 
                                                <?= isset($user['sms_security']) && $user['sms_security'] ? 'checked' : '' ?>
                                            >
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="sms_security" class="font-medium text-gray-700">Security Alerts</label>
                                            <p class="text-gray-500">Receive security notifications and login alerts</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white rounded-lg border p-5">
                                <h4 class="font-medium text-gray-900 mb-3">Notification Frequency</h4>
                                
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <input type="radio" id="freq_realtime" name="notification_frequency" value="realtime" 
                                            class="h-4 w-4 text-accent focus:ring-accent border-gray-300"
                                            <?= (!isset($user['notification_frequency']) || $user['notification_frequency'] == 'realtime') ? 'checked' : '' ?>>
                                        <label for="freq_realtime" class="ml-2 block text-sm text-gray-700">
                                            Real-time
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="freq_daily" name="notification_frequency" value="daily"
                                            class="h-4 w-4 text-accent focus:ring-accent border-gray-300"
                                            <?= (isset($user['notification_frequency']) && $user['notification_frequency'] == 'daily') ? 'checked' : '' ?>>
                                        <label for="freq_daily" class="ml-2 block text-sm text-gray-700">
                                            Daily digest
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="freq_weekly" name="notification_frequency" value="weekly"
                                            class="h-4 w-4 text-accent focus:ring-accent border-gray-300"
                                            <?= (isset($user['notification_frequency']) && $user['notification_frequency'] == 'weekly') ? 'checked' : '' ?>>
                                        <label for="freq_weekly" class="ml-2 block text-sm text-gray-700">
                                            Weekly summary
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 flex justify-between">
                        <button 
                            type="submit" 
                            name="update_preferences" 
                            class="bg-accent hover:bg-accent/80 text-white py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-accent focus:ring-opacity-50"
                        >
                            Save Notification Settings
                        </button>
                        
                        <button 
                            type="reset" 
                            class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50"
                        >
                            Reset
                        </button>
                    </div>
                </form>
                
            <!-- Payment Methods Tab -->
            <?php elseif ($active_tab === 'payment'): ?>
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">Your Payment Methods</h3>
                        <button 
                            type="button" 
                            id="add-payment-method" 
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-accent hover:bg-accent/80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Payment Method
                        </button>
                    </div>
                    
                    <!-- Saved Payment Methods -->
                    <div class="space-y-4 mb-8">
                        <!-- Credit Card -->
                        <div class="bg-white border rounded-lg shadow-sm p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-blue-50 p-2 rounded-full mr-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
                                            <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center">
                                            <p class="font-medium text-gray-900">Visa ending in 1234</p>
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Default
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500">Expires 12/25</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <button class="text-gray-400 hover:text-gray-500 focus:outline-none" id="edit-card-btn" onclick="editPaymentMethod('card', '1234')">
                                        <span class="sr-only">Edit</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </button>
                                    
                                    <button class="text-gray-400 hover:text-gray-500 focus:outline-none" id="delete-card-btn" onclick="deletePaymentMethod('card', '1234')">
                                        <span class="sr-only">Delete</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- PayPal -->
                        <div class="bg-white border rounded-lg shadow-sm p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-blue-50 p-2 rounded-full mr-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.384a.64.64 0 0 1 .632-.537h6.013c2.935 0 5.312.562 6.829 1.951 1.536 1.407 2.079 3.388 1.65 6.450-.476 3.307-2.06 5.694-4.497 7.056-2.209 1.24-5.315 1.678-9.447 1.678h-2.31l-.388 1.262a.639.639 0 0 1-.633.536H1.38"/>
                                            <path d="M6.695 8.547h2.3l-.621 3.129h1.85c1.933 0 2.707-.613 2.898-2.444.195-1.863-.649-2.07-2.562-2.07H8.363l.435-2.19h2.671c2.97 0 4.254 1.647 3.878 3.756-.422 2.355-2.041 3.896-4.944 3.896H6.693"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">PayPal</p>
                                        <p class="text-sm text-gray-500">client@vehicsmart.com</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <button class="text-gray-400 hover:text-gray-500 focus:outline-none" id="make-default-paypal-btn" onclick="makeDefaultPaymentMethod('paypal', 'client@vehicsmart.com')">
                                        <span class="sr-only">Make Default</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    
                                    <button class="text-gray-400 hover:text-gray-500 focus:outline-none" id="delete-paypal-btn" onclick="deletePaymentMethod('paypal', 'client@vehicsmart.com')">
                                        <span class="sr-only">Delete</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add Payment Method Form (initially hidden) -->
                    <div id="payment-form" class="hidden bg-white border rounded-lg p-6 mb-6">
                        <h4 class="font-medium text-gray-900 mb-4">Add New Payment Method</h4>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Type</label>
                                <div class="flex space-x-4">
                                    <div class="flex items-center">
                                        <input type="radio" id="type_card" name="payment_type" value="card" class="h-4 w-4 text-accent focus:ring-accent border-gray-300" checked>
                                        <label for="type_card" class="ml-2 block text-sm text-gray-700">Credit / Debit Card</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="type_paypal" name="payment_type" value="paypal" class="h-4 w-4 text-accent focus:ring-accent border-gray-300">
                                        <label for="type_paypal" class="ml-2 block text-sm text-gray-700">PayPal</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label for="card_number" class="block text-sm font-medium text-gray-700 mb-1">Card Number</label>
                                    <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50">
                                </div>
                                
                                <div>
                                    <label for="expiry" class="block text-sm font-medium text-gray-700 mb-1">Expiration Date</label>
                                    <input type="text" id="expiry" name="expiry" placeholder="MM/YY" class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50">
                                </div>
                                
                                <div>
                                    <label for="cvv" class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                                    <input type="text" id="cvv" name="cvv" placeholder="123" class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50">
                                </div>
                                
                                <div class="col-span-2">
                                    <label for="name_on_card" class="block text-sm font-medium text-gray-700 mb-1">Name on Card</label>
                                    <input type="text" id="name_on_card" name="name_on_card" placeholder="John Doe" class="w-full border-gray-300 rounded-md shadow-sm focus:border-accent focus:ring focus:ring-accent focus:ring-opacity-50">
                                </div>
                                
                                <div class="col-span-2">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="save_card" name="save_card" class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded">
                                        <label for="save_card" class="ml-2 block text-sm text-gray-700">
                                            Save as default payment method
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex space-x-3">
                            <button type="button" class="bg-accent hover:bg-accent/80 text-white py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-accent focus:ring-opacity-50">
                                Add Payment Method
                            </button>
                            
                            <button type="button" id="cancel-payment" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50">
                                Cancel
                            </button>
                        </div>
                    </div>
                
                <!-- Payment History -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium mb-4">Payment History</h3>
                    
                    <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            Jul 10, 2025
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            Vehicle Rental - Premium SUV
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            Visa •••• 1234
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                            $245.00
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Completed
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <a href="#" class="text-accent hover:text-accent/80">View</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            Jun 28, 2025
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            Vehicle Rental - Economy Sedan
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            PayPal
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                            $120.00
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Completed
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <a href="#" class="text-accent hover:text-accent/80">View</a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <script>
                    document.getElementById('add-payment-method').addEventListener('click', function() {
                        document.getElementById('payment-form').classList.remove('hidden');
                        this.classList.add('hidden');
                    });
                    
                    document.getElementById('cancel-payment').addEventListener('click', function() {
                        document.getElementById('payment-form').classList.add('hidden');
                        document.getElementById('add-payment-method').classList.remove('hidden');
                    });
                    
                    // Function to edit payment method
                    function editPaymentMethod(type, id) {
                        // Show the payment form
                        document.getElementById('payment-form').classList.remove('hidden');
                        document.getElementById('add-payment-method').classList.add('hidden');
                        
                        // If it's a card, pre-fill the form
                        if (type === 'card') {
                            document.getElementById('type_card').checked = true;
                            document.getElementById('card_number').value = '•••• •••• •••• ' + id;
                            document.getElementById('expiry').value = '12/25';
                            document.getElementById('name_on_card').value = 'John Doe';
                        } else if (type === 'paypal') {
                            document.getElementById('type_paypal').checked = true;
                        }
                        
                        // Scroll to the form
                        document.getElementById('payment-form').scrollIntoView({behavior: 'smooth'});
                        
                        // Show a message
                        alert('Editing ' + type + ' ending in ' + id);
                    }
                    
                    // Function to delete payment method
                    function deletePaymentMethod(type, id) {
                        if (confirm('Are you sure you want to delete this payment method?')) {
                            alert('Payment method deleted successfully');
                            
                            // In a real application, we would make an AJAX call to delete the payment method
                            // and then remove the element from the DOM
                            
                            // For demo purposes, we'll just hide the element
                            const parentElement = event.target.closest('.bg-white.border.rounded-lg.shadow-sm.p-4');
                            if (parentElement) {
                                parentElement.style.display = 'none';
                            }
                        }
                    }
                    
                    // Function to make a payment method default
                    function makeDefaultPaymentMethod(type, id) {
                        alert('Payment method set as default');
                        
                        // In a real application, we would make an AJAX call to set the payment method as default
                        // and then update the UI accordingly
                        
                        // For demo purposes, we'll just add a "Default" badge to the element
                        const parentElement = event.target.closest('.bg-white.border.rounded-lg.shadow-sm.p-4');
                        if (parentElement) {
                            // Find if there's already a default badge in this element
                            const existingBadge = parentElement.querySelector('.bg-green-100.text-green-800');
                            if (!existingBadge) {
                                // Create a new badge
                                const badge = document.createElement('span');
                                badge.className = 'ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
                                badge.textContent = 'Default';
                                
                                // Find the element to append it to
                                const nameElement = parentElement.querySelector('.font-medium.text-gray-900');
                                if (nameElement) {
                                    nameElement.appendChild(badge);
                                }
                            }
                        }
                        
                        // Remove "Default" badge from other elements
                        const allElements = document.querySelectorAll('.bg-white.border.rounded-lg.shadow-sm.p-4');
                        allElements.forEach(element => {
                            if (element !== parentElement) {
                                const badge = element.querySelector('.bg-green-100.text-green-800');
                                if (badge) {
                                    badge.remove();
                                }
                            }
                        });
                    }
                    
                    // Function to edit billing address
                    function editBillingAddress() {
                        // In a real application, we would show a form to edit the billing address
                        // For now, we'll just show a dialog
                        alert('Edit billing address functionality will be available soon.');
                    }
                </script>
                
            <!-- Account Summary Tab -->
            <?php elseif ($active_tab === 'summary'): ?>
                <div class="mb-6">
                    <h3 class="text-lg font-medium mb-4">Account Overview</h3>
                    
                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <div class="flex items-center mb-4">
                            <div class="bg-accent/10 p-3 rounded-full mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h2 class="text-lg font-semibold"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></h2>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4">
                            <dl>
                                <div class="flex justify-between py-2">
                                    <dt class="text-sm font-medium text-gray-500">Member since</dt>
                                    <dd class="text-sm text-gray-900">
                                        <?= date('F j, Y', strtotime($user['created_at'] ?? 'now')) ?>
                                    </dd>
                                </div>
                                
                                <div class="flex justify-between py-2 border-t border-gray-100">
                                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                                    <dd class="text-sm text-gray-900"><?= htmlspecialchars($user['email'] ?? '') ?></dd>
                                </div>
                                
                                <div class="flex justify-between py-2 border-t border-gray-100">
                                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                    <dd class="text-sm text-gray-900"><?= htmlspecialchars($user['phone'] ?? 'Not provided') ?></dd>
                                </div>
                                
                                <div class="flex justify-between py-2 border-t border-gray-100">
                                    <dt class="text-sm font-medium text-gray-500">Last Login</dt>
                                    <dd class="text-sm text-gray-900"><?= date('F j, Y g:i A', strtotime($user['last_login'] ?? $user['created_at'])) ?></dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    
                    <h3 class="text-lg font-medium mb-4">Rental Activity</h3>
                    
                    <?php
                    // Get user's rental history summary
                    try {
                        $rental_stats = $db->selectOne(
                            "SELECT 
                            COUNT(*) as total_rentals,
                            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_rentals,
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_rentals
                            FROM rentals 
                            WHERE user_id = :user_id",
                            ['user_id' => $user_id]
                        );
                    } catch (Exception $e) {
                        $rental_stats = [
                            'total_rentals' => 0,
                            'active_rentals' => 0,
                            'completed_rentals' => 0
                        ];
                    }
                    ?>
                    
                    <!-- Account Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white border rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Rentals</p>
                                <p class="text-3xl font-bold text-gray-900 mt-1"><?= $rental_stats['total_rentals'] ?? 0 ?></p>
                                <p class="text-xs text-gray-500 mt-2">Lifetime rentals</p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-100">
                            <a href="rental_history.php" class="text-sm text-accent hover:text-accent/80 font-medium flex items-center">
                                View history
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        </div>
                    </div>
                    
                    <div class="bg-white border rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Active Rentals</p>
                                <p class="text-3xl font-bold text-gray-900 mt-1"><?= $rental_stats['active_rentals'] ?? 0 ?></p>
                                <p class="text-xs text-gray-500 mt-2">Currently ongoing</p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-100">
                            <a href="active_rentals.php" class="text-sm text-accent hover:text-accent/80 font-medium flex items-center">
                                Manage active rentals
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        </div>
                    </div>
                    
                    <div class="bg-white border rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Completed Rentals</p>
                                <p class="text-3xl font-bold text-gray-900 mt-1"><?= $rental_stats['completed_rentals'] ?? 0 ?></p>
                                <p class="text-xs text-gray-500 mt-2">Successfully completed</p>
                            </div>
                            <div class="bg-purple-100 p-3 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-100">
                            <a href="rental_history.php?status=completed" class="text-sm text-accent hover:text-accent/80 font-medium flex items-center">
                                View completed rentals
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                    <a href="my_vehicles.php" class="flex items-center justify-center bg-accent hover:bg-accent/80 text-white py-3 px-4 rounded-lg shadow-sm transition-all duration-200 hover:shadow focus:outline-none focus:ring-2 focus:ring-accent focus:ring-opacity-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-5h2a2 2 0 011.732 1H14a2 2 0 011.9 1.411 2.5 2.5 0 014.1 2.589H19a1 1 0 001-1v-1a2 2 0 00-2-2h-6.1a2 2 0 01-1.401-.586L8.887 8H4a1 1 0 00-1 1v.14l.143-.14A2 2 0 013 8V5a1 1 0 00-1-1h1z" />
                        </svg>
                        Manage My Vehicles
                    </a>
                    
                    <a href="rental_history.php" class="flex items-center justify-center bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 py-3 px-4 rounded-lg shadow-sm transition-all duration-200 hover:shadow focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM14 11a1 1 0 011 1v1h1a1 1 0 110 2h-1v1a1 1 0 11-2 0v-1h-1a1 1 0 110-2h1v-1a1 1 0 011-1z" />
                        </svg>
                        View Rental History
                    </a>
                </div>
                </div>
            <?php endif; ?>
            
            <!-- Account Summary Sidebar has been removed -->
        </div>
    </div>
</div>

<script>
// Password strength check function
function checkPasswordStrength(password) {
    // Initialize variables
    let strength = 0;
    const meter = document.getElementById('password-strength-meter');
    const strengthText = document.getElementById('password-strength-text');
    
    // Check length
    const lengthCheck = document.getElementById('length-check');
    if (password.length >= 8) {
        strength += 1;
        lengthCheck.classList.remove('text-red-500');
        lengthCheck.classList.add('text-green-500');
    } else {
        lengthCheck.classList.remove('text-green-500');
        lengthCheck.classList.add('text-red-500');
    }
    
    // Check uppercase
    const upperCheck = document.getElementById('uppercase-check');
    if (/[A-Z]/.test(password)) {
        strength += 1;
        upperCheck.classList.remove('text-red-500');
        upperCheck.classList.add('text-green-500');
    } else {
        upperCheck.classList.remove('text-green-500');
        upperCheck.classList.add('text-red-500');
    }
    
    // Check lowercase
    const lowerCheck = document.getElementById('lowercase-check');
    if (/[a-z]/.test(password)) {
        strength += 1;
        lowerCheck.classList.remove('text-red-500');
        lowerCheck.classList.add('text-green-500');
    } else {
        lowerCheck.classList.remove('text-green-500');
        lowerCheck.classList.add('text-red-500');
    }
    
    // Check numbers
    const numberCheck = document.getElementById('number-check');
    if (/[0-9]/.test(password)) {
        strength += 1;
        numberCheck.classList.remove('text-red-500');
        numberCheck.classList.add('text-green-500');
    } else {
        numberCheck.classList.remove('text-green-500');
        numberCheck.classList.add('text-red-500');
    }
    
    // Check special characters
    const specialCheck = document.getElementById('special-check');
    if (/[^A-Za-z0-9]/.test(password)) {
        strength += 1;
        specialCheck.classList.remove('text-red-500');
        specialCheck.classList.add('text-green-500');
    } else {
        specialCheck.classList.remove('text-green-500');
        specialCheck.classList.add('text-red-500');
    }
    
    // Update strength meter color and width
    meter.style.width = (strength * 20) + '%';
    
    // Change color based on strength
    if (strength === 0) {
        meter.className = 'h-2.5 rounded-full bg-gray-300';
        strengthText.textContent = 'None';
    } else if (strength <= 2) {
        meter.className = 'h-2.5 rounded-full bg-red-500';
        strengthText.textContent = 'Weak';
    } else if (strength <= 4) {
        meter.className = 'h-2.5 rounded-full bg-yellow-500';
        strengthText.textContent = 'Medium';
    } else {
        meter.className = 'h-2.5 rounded-full bg-green-500';
        strengthText.textContent = 'Strong';
    }
}

// Confirm password match check
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (newPassword && confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            if (newPassword.value === confirmPassword.value) {
                confirmPassword.setCustomValidity('');
            } else {
                confirmPassword.setCustomValidity('Passwords do not match');
            }
        });
        
        newPassword.addEventListener('input', function() {
            if (newPassword.value === confirmPassword.value) {
                confirmPassword.setCustomValidity('');
            } else {
                confirmPassword.setCustomValidity('Passwords do not match');
            }
        });
    }
    
    // Initialize password strength if we're on the security tab
    if (window.location.href.includes('tab=security') && newPassword) {
        checkPasswordStrength(newPassword.value);
    }
    
    // Dark mode toggle
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    if (darkModeToggle) {
        // Check if dark mode is enabled from localStorage
        const isDarkMode = localStorage.getItem('dark-mode') === 'true';
        darkModeToggle.checked = isDarkMode;
        
        // Apply dark mode if enabled
        if (isDarkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        // Toggle dark mode on change
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('dark-mode', 'true');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('dark-mode', 'false');
            }
        });
    }
});

// Placeholder for other functions if needed

// Initialize client-side validation
document.addEventListener('DOMContentLoaded', function() {
    
    // Client-side form validation for profile
    const profileForm = document.getElementById('profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate name
            const nameInput = document.getElementById('name');
            if (!nameInput.value.trim()) {
                isValid = false;
                nameInput.classList.add('border-red-500');
                const errorElement = document.createElement('p');
                errorElement.className = 'text-red-500 text-xs mt-1';
                errorElement.textContent = 'Full name is required';
                nameInput.parentNode.appendChild(errorElement);
            }
            
            // Validate email
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('email-error');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailInput.value.trim()) {
                isValid = false;
                emailInput.classList.add('border-red-500');
                emailError.textContent = 'Email address is required';
                emailError.classList.remove('hidden');
            } else if (!emailRegex.test(emailInput.value.trim())) {
                isValid = false;
                emailInput.classList.add('border-red-500');
                emailError.textContent = 'Please enter a valid email address';
                emailError.classList.remove('hidden');
            }
            
            // Validate phone number format if provided
            const phoneInput = document.getElementById('phone');
            if (phoneInput.value.trim()) {
                const phoneRegex = /^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/;
                if (!phoneRegex.test(phoneInput.value.trim())) {
                    isValid = false;
                    phoneInput.classList.add('border-red-500');
                    const errorElement = document.createElement('p');
                    errorElement.className = 'text-red-500 text-xs mt-1';
                    errorElement.textContent = 'Please enter a valid phone number';
                    phoneInput.parentNode.appendChild(errorElement);
                }
            }
            
            // Prevent form submission if validation fails
            if (!isValid) {
                e.preventDefault();
                
                // Show validation summary at top of form
                const summary = document.createElement('div');
                summary.className = 'bg-red-100 border border-red-400 text-red-700 p-4 rounded mb-4';
                summary.innerHTML = '<p>Please correct the errors in the form before submitting.</p>';
                profileForm.prepend(summary);
                
                // Scroll to top of form
                window.scrollTo({
                    top: profileForm.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
        
        // Clear error messages when user starts typing
        const inputs = profileForm.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('border-red-500');
                const errorMessage = this.parentNode.querySelector('.text-red-500');
                if (errorMessage && errorMessage.id !== 'email-error') {
                    errorMessage.remove();
                } else if (errorMessage && errorMessage.id === 'email-error') {
                    errorMessage.classList.add('hidden');
                }
                
                // Remove validation summary if present
                const summary = profileForm.querySelector('.bg-red-100.border.border-red-400');
                if (summary) {
                    summary.remove();
                }
            });
        });
    }
});
</script>


<?php
// Chatbot system removed
?>
</body>
</html>
