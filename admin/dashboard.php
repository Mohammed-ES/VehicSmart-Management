<?php
/**
 * Admin Dashboard
 * 
 * Main dashboard page for administrators
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
$pageTitle = 'Admin Dashboard';
$page_title = 'Admin Dashboard'; // For backwards compatibility with header.php

// Include header
include_once 'includes/header.php';

// Initialize database
$db = Database::getInstance();

// Get summary data
try {
    // Check if vehicles table exists
    $check_vehicles_table = "SHOW TABLES LIKE 'vehicles'";
    $vehicles_table_exists = $db->select($check_vehicles_table);

    // Total vehicles
    if (!empty($vehicles_table_exists)) {
        $vehicles_query = "SELECT 
                        COUNT(*) as total_vehicles,
                        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_vehicles,
                        SUM(CASE WHEN status = 'rented' THEN 1 ELSE 0 END) as rented_vehicles,
                        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_vehicles,
                        SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold_vehicles
                      FROM vehicles";
        $vehicles_data = $db->selectOne($vehicles_query);
    } else {
        $vehicles_data = [
            'total_vehicles' => 0, 
            'available_vehicles' => 0, 
            'rented_vehicles' => 0, 
            'maintenance_vehicles' => 0, 
            'sold_vehicles' => 0
        ];
    }
    
    // Check if users table exists
    $check_users_table = "SHOW TABLES LIKE 'users'";
    $users_table_exists = $db->select($check_users_table);
    
    // Total clients
    if (!empty($users_table_exists)) {
        $clients_query = "SELECT COUNT(*) as total_clients FROM users WHERE role = 'client'";
        $clients_data = $db->selectOne($clients_query);
    } else {
        $clients_data = ['total_clients' => 0];
    }
    
    // Check if rentals table exists
    $check_rentals_table = "SHOW TABLES LIKE 'rentals'";
    $rentals_table_exists = $db->select($check_rentals_table);
    
    // Total rentals
    if (!empty($rentals_table_exists)) {
        $rentals_query = "SELECT 
                       COUNT(*) as total_rentals,
                       SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_rentals,
                       SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_rentals
                     FROM rentals";
        $rentals_data = $db->selectOne($rentals_query);
    } else {
        $rentals_data = ['total_rentals' => 0, 'active_rentals' => 0, 'completed_rentals' => 0];
    }
    
    // Check if alerts table exists
    $check_alerts_table = "SHOW TABLES LIKE 'alerts'";
    $alerts_table_exists = $db->select($check_alerts_table);
    
    // Active alerts
    if (!empty($alerts_table_exists)) {
        $alerts_query = "SELECT COUNT(*) as active_alerts FROM alerts WHERE is_read = 0";
        $alerts_data = $db->selectOne($alerts_query);
    } else {
        $alerts_data = ['active_alerts' => 0];
    }
    
    // Check if vehicle_images table exists
    $check_image_table = "SHOW TABLES LIKE 'vehicle_images'";
    $image_table_exists = $db->select($check_image_table);
    
    // Check if vehicle_categories table exists
    $check_categories_table = "SHOW TABLES LIKE 'vehicle_categories'";
    $categories_table_exists = $db->select($check_categories_table);
    
    // Recent vehicles
    if (!empty($image_table_exists)) {
        if (!empty($categories_table_exists)) {
            $recent_vehicles_query = "SELECT v.*, 
                                 (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image,
                                 vc.name as category_name
                                 FROM vehicles v
                                 LEFT JOIN vehicle_categories vc ON v.category_id = vc.id
                                 ORDER BY v.created_at DESC 
                                 LIMIT 5";
        } else {
            $recent_vehicles_query = "SELECT v.*, 
                                 (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image,
                                 NULL as category_name
                                 FROM vehicles v
                                 ORDER BY v.created_at DESC 
                                 LIMIT 5";
        }
    } else {
        if (!empty($categories_table_exists)) {
            $recent_vehicles_query = "SELECT v.*, 
                                 NULL as image,
                                 vc.name as category_name
                                 FROM vehicles v
                                 LEFT JOIN vehicle_categories vc ON v.category_id = vc.id
                                 ORDER BY v.created_at DESC 
                                 LIMIT 5";
        } else {
            $recent_vehicles_query = "SELECT v.*, 
                                 NULL as image,
                                 NULL as category_name
                                 FROM vehicles v
                                 ORDER BY v.created_at DESC 
                                 LIMIT 5";
        }
    }
    $recent_vehicles = $db->select($recent_vehicles_query);
    
    // Check if rentals table exists
    $check_rentals_table = "SHOW TABLES LIKE 'rentals'";
    $rentals_table_exists = $db->select($check_rentals_table);

    // Recent rentals
    if (!empty($rentals_table_exists)) {
        $recent_rentals_query = "SELECT r.*, CONCAT(v.brand, ' ', v.model) as vehicle_name, u.first_name, u.last_name, u.email
                            FROM rentals r
                            JOIN vehicles v ON r.vehicle_id = v.id
                            JOIN users u ON r.user_id = u.id
                            ORDER BY r.created_at DESC
                            LIMIT 5";
        $recent_rentals = $db->select($recent_rentals_query);
    } else {
        $recent_rentals = [];
    }
} catch (Exception $e) {
    error_log('Admin dashboard error: ' . $e->getMessage());
}
?>

<div class="p-6 w-full">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Welcome, <?= $user['name'] ?? 'Admin' ?></h1>
        <p class="text-gray-600">Here's what's happening with your fleet today - <?= date('F j, Y') ?></p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Vehicles Card -->
        <div class="bg-white rounded-lg shadow p-5 dashboard-card vehicles animated-card delay-1">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wider font-medium">Total Vehicles</p>
                    <p class="text-3xl font-bold mt-1"><?= $vehicles_data['total_vehicles'] ?? 0 ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="status-badge status-available"><?= $vehicles_data['available_vehicles'] ?? 0 ?> Available</span>
                <span class="status-badge status-rented"><?= $vehicles_data['rented_vehicles'] ?? 0 ?> Rented</span>
                <span class="status-badge status-maintenance"><?= $vehicles_data['maintenance_vehicles'] ?? 0 ?> Maintenance</span>
            </div>
        </div>
        
        <!-- Total Clients Card -->
        <div class="bg-white rounded-lg shadow p-5 dashboard-card clients animated-card delay-2">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wider font-medium">Total Clients</p>
                    <p class="text-3xl font-bold mt-1"><?= $clients_data['total_clients'] ?? 0 ?></p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <a href="clients_manage.php" class="btn btn-primary inline-flex items-center">
                    View all clients
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
        
        <!-- Rentals Card -->
        <div class="bg-white rounded-lg shadow p-5 dashboard-card rentals animated-card delay-3">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wider font-medium">Total Rentals</p>
                    <p class="text-3xl font-bold mt-1"><?= $rentals_data['total_rentals'] ?? 0 ?></p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="status-badge status-available"><?= $rentals_data['active_rentals'] ?? 0 ?> Active</span>
                <span class="status-badge status-rented"><?= $rentals_data['completed_rentals'] ?? 0 ?> Completed</span>
            </div>
        </div>
        
        <!-- Alerts Card -->
        <div class="bg-white rounded-lg shadow p-5 dashboard-card alerts animated-card delay-4">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-xs uppercase tracking-wider font-medium">Active Alerts</p>
                    <p class="text-3xl font-bold mt-1"><?= $alerts_data['active_alerts'] ?? 0 ?></p>
                </div>
                <div class="bg-red-100 p-3 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <a href="alerts.php" class="btn btn-primary inline-flex items-center">
                    View all alerts
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6 w-full">
    <!-- Recent Vehicles -->
    <div class="bg-white rounded-lg shadow overflow-hidden animated-card delay-1">
        <div class="p-4 border-b bg-gray-50">
            <h2 class="text-lg font-medium flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Recent Vehicles
            </h2>
        </div>
        <div class="p-4">
            <table class="min-w-full admin-table">
                <thead>
                    <tr>
                        <th class="text-left py-2">Vehicle</th>
                        <th class="text-left py-2">Type</th>
                        <th class="text-left py-2">Status</th>
                        <th class="text-right py-2">Daily Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_vehicles)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-500">No vehicles found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_vehicles as $vehicle): ?>
                            <tr class="border-t">
                                <td class="py-3">
                                    <div class="flex items-center">
                                        <?php if (!empty($vehicle['image'])): ?>
                                            <img src="../uploads/vehicles/<?= htmlspecialchars($vehicle['image']) ?>" alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) ?>" class="w-10 h-10 object-cover rounded mr-2">
                                        <?php else: ?>
                                            <div class="w-10 h-10 bg-gray-200 rounded mr-2 flex items-center justify-center">🚗</div>
                                        <?php endif; ?>
                                        <?= htmlspecialchars(($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?>
                                    </div>
                                </td>
                                <td class="py-3"><?= ucfirst(htmlspecialchars($vehicle['category_name'] ?? $vehicle['category_id'] ?? 'Unknown')) ?></td>
                                <td class="py-3">
                                    <?php
                                    $status_color = 'gray';
                                    switch ($vehicle['status']) {
                                        case 'available':
                                            $status_color = 'green';
                                            break;
                                        case 'rented':
                                            $status_color = 'blue';
                                            break;
                                        case 'maintenance':
                                            $status_color = 'orange';
                                            break;
                                        case 'sold':
                                            $status_color = 'purple';
                                            break;
                                    }
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-<?= $status_color ?>-100 text-<?= $status_color ?>-800">
                                        <?= ucfirst(htmlspecialchars($vehicle['status'])) ?>
                                    </span>
                                </td>
                                <td class="py-3 text-right"><?= '$' . number_format($vehicle['daily_rate'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="mt-4 text-center">
                <a href="vehicles_manage.php" class="text-accent hover:underline">View All Vehicles</a>
            </div>
        </div>
    </div>
    
    <!-- Recent Rentals -->
    <div class="bg-white rounded-lg shadow overflow-hidden animated-card delay-2">
        <div class="p-4 border-b bg-gray-50">
            <h2 class="text-lg font-medium flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Recent Rentals
            </h2>
        </div>
        <div class="p-4">
            <table class="min-w-full admin-table">
                <thead>
                    <tr>
                        <th class="text-left py-2">Client</th>
                        <th class="text-left py-2">Vehicle</th>
                        <th class="text-left py-2">Status</th>
                        <th class="text-right py-2">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_rentals)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-500">No rentals found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_rentals as $rental): ?>
                            <tr class="border-t">
                                <td class="py-3">
                                    <div>
                                        <?= htmlspecialchars($rental['first_name'] . ' ' . $rental['last_name']) ?>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($rental['email']) ?></div>
                                    </div>
                                </td>
                                <td class="py-3"><?= htmlspecialchars($rental['vehicle_name']) ?></td>
                                <td class="py-3">
                                    <?php
                                    $status_color = 'gray';
                                    switch ($rental['status']) {
                                        case 'pending':
                                            $status_color = 'yellow';
                                            break;
                                        case 'confirmed':
                                            $status_color = 'purple';
                                            break;
                                        case 'active':
                                            $status_color = 'green';
                                            break;
                                        case 'completed':
                                            $status_color = 'blue';
                                            break;
                                        case 'cancelled':
                                            $status_color = 'red';
                                            break;
                                    }
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-<?= $status_color ?>-100 text-<?= $status_color ?>-800">
                                        <?= ucfirst(htmlspecialchars($rental['status'])) ?>
                                    </span>
                                </td>
                                <td class="py-3 text-right"><?= '$' . number_format($rental['total_amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="mt-4 text-center">
                <a href="reports.php" class="text-accent hover:underline">View All Rentals</a>
            </div>
        </div>
    </div>
</div>

<!-- Graphs section with Chart.js -->
<div class="bg-white p-8 rounded-lg shadow mb-6 animated-card delay-3 w-full">
    <h2 class="text-xl font-semibold mb-6 flex items-center text-gray-800">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 mr-3 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
        </svg>
        Rental Performance Analysis
    </h2>
    <div class="grid grid-cols-1 gap-8 w-full">
        <div class="bg-gray-50 p-5 rounded-lg border border-gray-100 shadow-sm mx-auto w-full">
            <h3 class="text-lg font-semibold mb-6 text-center text-gray-700">Rental Trends (Last 7 Months)</h3>
            <div class="chart-container px-2" style="position: relative; height:450px; width:100%;">
                <canvas id="rentalsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Add Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js global defaults
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#64748b';
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(17, 24, 39, 0.8)';
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 4;
    
    // Rentals Chart - Enhanced with better styling
    const rentalsCtx = document.getElementById('rentalsChart').getContext('2d');
    
    // Gradient background for the chart area
    const gradientFill = rentalsCtx.createLinearGradient(0, 0, 0, 350);
    gradientFill.addColorStop(0, 'rgba(255, 120, 73, 0.25)');
    gradientFill.addColorStop(0.5, 'rgba(255, 120, 73, 0.15)');
    gradientFill.addColorStop(1, 'rgba(255, 120, 73, 0.01)');
    
    new Chart(rentalsCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
            datasets: [{
                label: 'Monthly Rentals',
                data: [12, 19, 15, 25, 22, 30, 28],
                backgroundColor: gradientFill,
                borderColor: 'rgb(255, 120, 73)',
                pointBackgroundColor: 'rgb(255, 120, 73)',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 10,
                pointHoverBackgroundColor: '#ffffff',
                pointHoverBorderColor: 'rgb(255, 120, 73)',
                pointHoverBorderWidth: 4,
                borderWidth: 4,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'center',
                    labels: {
                        boxWidth: 16,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 25,
                        font: {
                            size: 14,
                            weight: '600'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    padding: 12,
                    displayColors: true,
                    borderColor: 'rgba(255, 255, 255, 0.2)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y} rentals`;
                        },
                        labelTextColor: function(context) {
                            return '#ffffff';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: true,
                        color: 'rgba(0, 0, 0, 0.03)',
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        color: 'rgba(55, 65, 81, 0.8)',
                        padding: 8
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.03)',
                        drawBorder: false,
                        z: 0
                    },
                    ticks: {
                        precision: 0,
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        color: 'rgba(55, 65, 81, 0.8)',
                        padding: 10,
                        callback: function(value) {
                            return value;
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            },
            animation: {
                duration: 1800,
                easing: 'easeOutQuart'
            },
            layout: {
                padding: {
                    left: 5,
                    right: 20,
                    top: 10,
                    bottom: 10
                }
            },
            elements: {
                line: {
                    tension: 0.4,
                    borderWidth: 3,
                    borderCapStyle: 'round'
                },
                point: {
                    radius: 5,
                    hitRadius: 10,
                    hoverRadius: 8,
                    hoverBorderWidth: 3
                }
            }
        }
    });

    // No additional charts
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
