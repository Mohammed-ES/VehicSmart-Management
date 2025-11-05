<?php
/**
 * Automatic System Repair Tool
 * Corrige automatiquement tous les problèmes détectés
 */

require_once __DIR__ . '/../config/database.php';

$page_title = 'System Auto-Repair';
include_once 'includes/header.php';

$db = Database::getInstance();
$fixes_applied = [];
$errors = [];

echo '<div class="p-6">';
echo '<h1 class="text-3xl font-bold mb-6 text-gray-900">🔧 System Auto-Repair Tool</h1>';
echo '<div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <p class="text-blue-700">This tool will automatically fix common database and configuration issues.</p>
      </div>';

// Fix 1: Ensure all required tables exist
echo '<div class="bg-white rounded-lg shadow-md p-6 mb-6">';
echo '<h2 class="text-xl font-semibold mb-4">📊 Checking Database Tables...</h2>';

$required_tables = [
    'users' => "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `first_name` varchar(50) NOT NULL,
        `last_name` varchar(50) NOT NULL,
        `email` varchar(100) NOT NULL UNIQUE,
        `password` varchar(255) NOT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `role` enum('admin','client') DEFAULT 'client',
        `status` enum('active','inactive','suspended') DEFAULT 'active',
        `email_verified` tinyint(1) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'vehicles' => "CREATE TABLE IF NOT EXISTS `vehicles` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `category_id` int(11) DEFAULT NULL,
        `brand` varchar(50) NOT NULL,
        `model` varchar(50) NOT NULL,
        `year` int(11) NOT NULL,
        `color` varchar(30) DEFAULT NULL,
        `license_plate` varchar(20) DEFAULT NULL,
        `vin` varchar(50) DEFAULT NULL,
        `engine_type` enum('petrol','diesel','electric','hybrid') DEFAULT 'petrol',
        `fuel_capacity` decimal(5,2) DEFAULT NULL,
        `seating_capacity` int(11) DEFAULT NULL,
        `mileage` decimal(10,2) DEFAULT 0.00,
        `daily_rate` decimal(10,2) NOT NULL,
        `purchase_price` decimal(12,2) DEFAULT NULL,
        `status` enum('available','rented','maintenance','sold') DEFAULT 'available',
        `description` text DEFAULT NULL,
        `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
        `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'vehicle_categories' => "CREATE TABLE IF NOT EXISTS `vehicle_categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(50) NOT NULL,
        `description` text DEFAULT NULL,
        `icon` varchar(100) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'vehicle_images' => "CREATE TABLE IF NOT EXISTS `vehicle_images` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `vehicle_id` int(11) NOT NULL,
        `image_path` varchar(255) NOT NULL,
        `is_primary` tinyint(1) DEFAULT 0,
        `sort_order` int(11) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `vehicle_id` (`vehicle_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'messages' => "CREATE TABLE IF NOT EXISTS `messages` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sender_id` int(11) NOT NULL,
        `receiver_id` int(11) NOT NULL,
        `subject` varchar(200) DEFAULT NULL,
        `message` text NOT NULL,
        `is_read` tinyint(1) DEFAULT 0,
        `message_type` enum('general','rental','purchase','maintenance','support') DEFAULT 'general',
        `reference_id` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'rentals' => "CREATE TABLE IF NOT EXISTS `rentals` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `vehicle_id` int(11) NOT NULL,
        `start_date` date NOT NULL,
        `end_date` date NOT NULL,
        `total_days` int(11) NOT NULL,
        `daily_rate` decimal(10,2) NOT NULL,
        `total_amount` decimal(12,2) NOT NULL,
        `deposit_amount` decimal(10,2) DEFAULT 0.00,
        `status` enum('pending','confirmed','active','completed','cancelled') DEFAULT 'pending',
        `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
        `payment_method` varchar(50) DEFAULT NULL,
        `payment_id` varchar(100) DEFAULT NULL,
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'maintenance_records' => "CREATE TABLE IF NOT EXISTS `maintenance_records` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `vehicle_id` int(11) NOT NULL,
        `service_type` varchar(100) NOT NULL,
        `description` text DEFAULT NULL,
        `cost` decimal(10,2) DEFAULT NULL,
        `service_date` date NOT NULL,
        `next_service_date` date DEFAULT NULL,
        `mileage` int(11) DEFAULT NULL,
        `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
        `notes` text DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    'alerts' => "CREATE TABLE IF NOT EXISTS `alerts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `type` enum('maintenance','contract_expiry','payment_due','inspection','general') NOT NULL,
        `title` varchar(200) NOT NULL,
        `message` text NOT NULL,
        `target_user_id` int(11) DEFAULT NULL,
        `target_vehicle_id` int(11) DEFAULT NULL,
        `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
        `is_read` tinyint(1) DEFAULT 0,
        `action_required` tinyint(1) DEFAULT 0,
        `due_date` date DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($required_tables as $table_name => $create_sql) {
    try {
        $check = $db->select("SHOW TABLES LIKE '$table_name'");
        if (empty($check)) {
            $db->getConnection()->exec($create_sql);
            $fixes_applied[] = "✓ Created table: <code>$table_name</code>";
            echo "<div class='text-green-600 mb-2'>✓ Created table: <strong>$table_name</strong></div>";
        } else {
            echo "<div class='text-gray-600 mb-2'>✓ Table exists: <strong>$table_name</strong></div>";
        }
    } catch (Exception $e) {
        $errors[] = "Failed to create table $table_name: " . $e->getMessage();
        echo "<div class='text-red-600 mb-2'>✗ Error with table <strong>$table_name</strong>: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

echo '</div>';

// Fix 2: Ensure vehicle categories exist
echo '<div class="bg-white rounded-lg shadow-md p-6 mb-6">';
echo '<h2 class="text-xl font-semibold mb-4">🚗 Checking Vehicle Categories...</h2>';

try {
    $categories_count = $db->selectOne("SELECT COUNT(*) as count FROM vehicle_categories");
    
    if ($categories_count && $categories_count['count'] == 0) {
        $categories = [
            ['Car', 'Standard passenger cars for personal transportation', 'fa-car'],
            ['Truck', 'Commercial trucks for cargo and freight transport', 'fa-truck'],
            ['Bus', 'Passenger buses for group transportation', 'fa-bus'],
            ['Tractor', 'Agricultural and industrial tractors', 'fa-tractor'],
            ['Van', 'Commercial vans for cargo and passenger transport', 'fa-van-shuttle'],
            ['Motorcycle', 'Two-wheeled motor vehicles', 'fa-motorcycle'],
            ['SUV', 'Sport Utility Vehicles for versatile transportation', 'fa-car-side']
        ];
        
        $stmt = $db->getConnection()->prepare("INSERT INTO vehicle_categories (name, description, icon) VALUES (?, ?, ?)");
        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }
        
        $fixes_applied[] = "✓ Added default vehicle categories (7 categories)";
        echo "<div class='text-green-600 mb-2'>✓ Added 7 default vehicle categories</div>";
    } else {
        echo "<div class='text-gray-600 mb-2'>✓ Vehicle categories exist (" . ($categories_count['count'] ?? 0) . " categories)</div>";
    }
} catch (Exception $e) {
    $errors[] = "Failed to add vehicle categories: " . $e->getMessage();
    echo "<div class='text-red-600 mb-2'>✗ Error adding categories: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo '</div>';

// Fix 3: Ensure admin user exists
echo '<div class="bg-white rounded-lg shadow-md p-6 mb-6">';
echo '<h2 class="text-xl font-semibold mb-4">👤 Checking Admin User...</h2>';

try {
    $admin_check = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    
    if ($admin_check && $admin_check['count'] == 0) {
        $password = password_hash('password', PASSWORD_DEFAULT);
        $db->query(
            "INSERT INTO users (first_name, last_name, email, password, role, status, email_verified) VALUES (?, ?, ?, ?, 'admin', 'active', 1)",
            ['Admin', 'User', 'admin@vehicsmart.com', $password]
        );
        
        $fixes_applied[] = "✓ Created admin user (email: admin@vehicsmart.com, password: password)";
        echo "<div class='text-green-600 mb-2'>✓ Created admin user</div>";
        echo "<div class='bg-yellow-50 border border-yellow-200 p-3 rounded mt-2'>";
        echo "<p class='text-sm'><strong>Login:</strong> admin@vehicsmart.com</p>";
        echo "<p class='text-sm'><strong>Password:</strong> password</p>";
        echo "</div>";
    } else {
        echo "<div class='text-gray-600 mb-2'>✓ Admin user exists (" . ($admin_check['count'] ?? 0) . " admin(s))</div>";
    }
} catch (Exception $e) {
    $errors[] = "Failed to create admin user: " . $e->getMessage();
    echo "<div class='text-red-600 mb-2'>✗ Error creating admin: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo '</div>';

// Summary
echo '<div class="bg-white rounded-lg shadow-md p-6 mb-6">';
echo '<h2 class="text-xl font-semibold mb-4">📋 Summary</h2>';

if (!empty($fixes_applied)) {
    echo '<div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">';
    echo '<h3 class="text-green-800 font-semibold mb-2">✓ Fixes Applied:</h3>';
    echo '<ul class="list-disc list-inside text-green-700">';
    foreach ($fixes_applied as $fix) {
        echo "<li>$fix</li>";
    }
    echo '</ul>';
    echo '</div>';
}

if (!empty($errors)) {
    echo '<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">';
    echo '<h3 class="text-red-800 font-semibold mb-2">✗ Errors:</h3>';
    echo '<ul class="list-disc list-inside text-red-700">';
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo '</ul>';
    echo '</div>';
}

if (empty($fixes_applied) && empty($errors)) {
    echo '<div class="bg-blue-50 border-l-4 border-blue-500 p-4">';
    echo '<p class="text-blue-700">✓ No issues found - system is healthy!</p>';
    echo '</div>';
}

echo '<div class="mt-6 space-x-3">';
echo '<a href="system_diagnostic.php" class="inline-block bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">View Diagnostic Report</a>';
echo '<a href="dashboard.php" class="inline-block bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-700">Back to Dashboard</a>';
echo '</div>';

echo '</div>';

echo '</div>';

include_once 'includes/footer.php';
?>
