<?php
/**
 * Edit Client
 * Form for editing existing client accounts
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

// Check if user is admin
requireAdmin();

// Get current user
$user = getCurrentUser();

// Set page title
$pageTitle = 'Edit Client';
$page_title = 'Edit Client';

// Initialize database
$db = Database::getInstance();

// Get client ID from URL
$client_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables
$errors = [];
$success = false;
$client = null;

// Fetch client data
if ($client_id > 0) {
    try {
        $client = $db->selectOne("
            SELECT id, first_name, last_name, email, phone, status
            FROM users 
            WHERE id = ? AND role = 'client'
        ", [$client_id]);
        
        if (!$client) {
            header('Location: clients_manage.php?error=not_found');
            exit;
        }
    } catch (Exception $e) {
        $errors[] = 'Error fetching client data: ' . $e->getMessage();
    }
} else {
    header('Location: clients_manage.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $client) {
    $first_name = cleanString($_POST['first_name'] ?? '');
    $last_name = cleanString($_POST['last_name'] ?? '');
    $email = cleanString($_POST['email'] ?? '');
    $phone = cleanString($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!validateEmail($email)) $errors[] = 'Invalid email format';
    if (!in_array($status, ['active', 'inactive'])) $errors[] = 'Invalid status';
    
    // Password validation (only if provided)
    if (!empty($password)) {
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
        if ($password !== $confirm_password) $errors[] = 'Passwords do not match';
    }
    
    // Check if email already exists for another user
    if (empty($errors)) {
        try {
            $existing = $db->selectOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $client_id]);
            if ($existing) {
                $errors[] = 'Email already exists';
            }
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    
    // Update client if no errors
    if (empty($errors)) {
        try {
            if (!empty($password)) {
                // Update with password
                $hashed_password = hashPassword($password);
                $db->update("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, status = ?, password = ?, updated_at = NOW()
                    WHERE id = ?
                ", [$first_name, $last_name, $email, $phone, $status, $hashed_password, $client_id]);
            } else {
                // Update without password
                $db->update("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ", [$first_name, $last_name, $email, $phone, $status, $client_id]);
            }
            
            header('Location: clients_manage.php?success=updated');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error updating client: ' . $e->getMessage();
        }
    }
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Client
            </h1>
            <p class="text-gray-600 mt-1">Update client account information</p>
        </div>
        <a href="clients_manage.php" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Clients
        </a>
    </div>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-sm animated-card">
            <div class="flex items-start">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="font-bold mb-2">Please correct the following errors:</h3>
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-lg shadow-md overflow-hidden animated-card">
        <!-- Form Header -->
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">Client Information</h2>
        </div>

        <!-- Form Body -->
        <div class="p-6">
            <!-- Personal Information Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Personal Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            First Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="first_name" value="<?= e($_POST['first_name'] ?? $client['first_name']) ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" 
                               placeholder="Enter first name" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Last Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="last_name" value="<?= e($_POST['last_name'] ?? $client['last_name']) ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" 
                               placeholder="Enter last name" required>
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Contact Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" value="<?= e($_POST['email'] ?? $client['email']) ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" 
                               placeholder="client@example.com" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Phone Number
                        </label>
                        <input type="tel" name="phone" value="<?= e($_POST['phone'] ?? $client['phone']) ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" 
                               placeholder="+1 (555) 123-4567">
                    </div>
                </div>
            </div>

            <!-- Account Status Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Account Status
                </h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select name="status" class="w-full md:w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                        <option value="active" <?= (($_POST['status'] ?? $client['status']) === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (($_POST['status'] ?? $client['status']) === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-2">Inactive clients cannot log in to the system</p>
                </div>
            </div>

            <!-- Change Password Section (Optional) -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Change Password (Optional)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            New Password
                        </label>
                        <input type="password" name="password" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" 
                               placeholder="Leave blank to keep current">
                        <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters long</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm New Password
                        </label>
                        <input type="password" name="confirm_password" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition" 
                               placeholder="Confirm new password">
                    </div>
                </div>
            </div>

            <!-- Information Box -->
            <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-orange-600 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-sm text-orange-700">
                        <p class="font-semibold mb-1">Update Note</p>
                        <p>Leave the password fields blank if you don't want to change the client's password. Only fill them in if you need to reset the password.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Footer -->
        <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-t border-gray-200">
            <a href="view_client.php?id=<?= $client['id'] ?>" class="text-orange-600 hover:text-orange-700 font-medium flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                View Client Details
            </a>
            <div class="flex gap-3">
                <a href="clients_manage.php" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-medium">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition font-medium flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Update Client
                </button>
            </div>
        </div>
    </form>
</div>

<?php include_once 'includes/footer.php'; ?>
