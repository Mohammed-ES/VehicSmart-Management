<?php
/**
 * Admin Settings
 * 
 * System settings and configuration page for administrators
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
$pageTitle = 'System Settings';
$page_title = 'System Settings'; // For backwards compatibility with header.php

// Include header
include_once 'includes/header.php';

// Initialize database
$db = Database::getInstance();

// Process settings update if form submitted
$message = '';
$messageType = '';

// Get current settings
try {
    // Check if settings table exists
    $check_settings_table = "SHOW TABLES LIKE 'system_settings'";
    $settings_table_exists = $db->select($check_settings_table);

    if (empty($settings_table_exists)) {
        // Create settings table if it doesn't exist
        $create_settings_table = "CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_name VARCHAR(50) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $db->query($create_settings_table);
        
        // Insert default settings
        $default_settings = [
            ['company_name', 'VehicSmart', 'Company name displayed throughout the application'],
            ['company_email', 'info@vehicsmart.com', 'Main contact email'],
            ['enable_notifications', '1', 'Enable email notifications'],
            ['maintenance_mode', '0', 'Put site in maintenance mode'],
            ['currency', 'USD', 'Default currency for prices'],
            ['date_format', 'Y-m-d', 'Default date format'],
            ['items_per_page', '10', 'Number of items to display per page in listings']
        ];
        
        $insert_setting = "INSERT INTO system_settings (setting_name, setting_value, setting_description) VALUES (?, ?, ?)";
        foreach ($default_settings as $setting) {
            $db->insert($insert_setting, $setting);
        }
    }
    
    // Get current settings
    $settings_query = "SELECT * FROM system_settings";
    $settings = $db->select($settings_query) ?: [];
    $settings_map = [];
    
    foreach ($settings as $setting) {
        $settings_map[$setting['setting_name']] = $setting['setting_value'];
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $setting_name = substr($key, 8); // Remove 'setting_' prefix
                $setting_value = is_array($value) ? implode(',', $value) : $value;
                
                // Update setting
                $update_setting = "UPDATE system_settings SET setting_value = ? WHERE setting_name = ?";
                $db->update($update_setting, [$setting_value, $setting_name]);
                
                // Update local map for display
                $settings_map[$setting_name] = $setting_value;
            }
        }
        
        $message = 'Settings updated successfully';
        $messageType = 'success';
    }
    
} catch (Exception $e) {
    $message = 'Error managing settings: ' . $e->getMessage();
    $messageType = 'error';
}
?>

<div class="p-6 w-full">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">System Settings</h1>
        <p class="text-gray-600">Configure system-wide settings and preferences</p>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="mb-6 p-4 rounded <?= $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Company Name -->
                <div>
                    <label for="setting_company_name" class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                    <input 
                        type="text" 
                        id="setting_company_name" 
                        name="setting_company_name" 
                        value="<?= htmlspecialchars($settings_map['company_name'] ?? 'VehicSmart') ?>" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                </div>
                
                <!-- Company Email -->
                <div>
                    <label for="setting_company_email" class="block text-sm font-medium text-gray-700 mb-1">Company Email</label>
                    <input 
                        type="email" 
                        id="setting_company_email" 
                        name="setting_company_email" 
                        value="<?= htmlspecialchars($settings_map['company_email'] ?? 'info@vehicsmart.com') ?>" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                </div>
                
                <!-- Currency -->
                <div>
                    <label for="setting_currency" class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                    <select 
                        id="setting_currency" 
                        name="setting_currency" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                        <?php 
                        $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'MAD'];
                        $selected_currency = $settings_map['currency'] ?? 'USD';
                        
                        foreach ($currencies as $currency) {
                            $selected = $currency === $selected_currency ? 'selected' : '';
                            echo "<option value=\"{$currency}\" {$selected}>{$currency}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <!-- Date Format -->
                <div>
                    <label for="setting_date_format" class="block text-sm font-medium text-gray-700 mb-1">Date Format</label>
                    <select 
                        id="setting_date_format" 
                        name="setting_date_format" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                        <?php 
                        $formats = [
                            'Y-m-d' => date('Y-m-d') . ' (YYYY-MM-DD)',
                            'd/m/Y' => date('d/m/Y') . ' (DD/MM/YYYY)',
                            'm/d/Y' => date('m/d/Y') . ' (MM/DD/YYYY)',
                            'd.m.Y' => date('d.m.Y') . ' (DD.MM.YYYY)'
                        ];
                        $selected_format = $settings_map['date_format'] ?? 'Y-m-d';
                        
                        foreach ($formats as $format => $display) {
                            $selected = $format === $selected_format ? 'selected' : '';
                            echo "<option value=\"{$format}\" {$selected}>{$display}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <!-- Items Per Page -->
                <div>
                    <label for="setting_items_per_page" class="block text-sm font-medium text-gray-700 mb-1">Items Per Page</label>
                    <select 
                        id="setting_items_per_page" 
                        name="setting_items_per_page" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                        <?php 
                        $options = [5, 10, 20, 50, 100];
                        $selected_option = $settings_map['items_per_page'] ?? 10;
                        
                        foreach ($options as $option) {
                            $selected = $option == $selected_option ? 'selected' : '';
                            echo "<option value=\"{$option}\" {$selected}>{$option}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-6 border-t border-b border-gray-200 py-4">
                <h2 class="text-lg font-medium mb-4">System Options</h2>
                <div class="space-y-3">
                    <!-- Enable Notifications -->
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="setting_enable_notifications" 
                            name="setting_enable_notifications" 
                            value="1" 
                            <?= ($settings_map['enable_notifications'] ?? '1') === '1' ? 'checked' : '' ?>
                            class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded"
                        >
                        <label for="setting_enable_notifications" class="ml-2 block text-sm text-gray-700">
                            Enable Email Notifications
                        </label>
                    </div>
                    
                    <!-- Maintenance Mode -->
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="setting_maintenance_mode" 
                            name="setting_maintenance_mode" 
                            value="1" 
                            <?= ($settings_map['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>
                            class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded"
                        >
                        <label for="setting_maintenance_mode" class="ml-2 block text-sm text-gray-700">
                            Enable Maintenance Mode
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button 
                    type="submit" 
                    name="update_settings" 
                    class="px-4 py-2 bg-accent text-white rounded-md hover:bg-accent/80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent"
                >
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>
