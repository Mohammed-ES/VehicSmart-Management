<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin();
/**
 * Reports Management Page
 * 
 * @package VehicSmart
 */

// Set page title
$page_title = 'Reports & Analytics';

// Include database connection
require_once '../config/database.php';

// Get database instance
$db = Database::getInstance();

// Set default date range (last 30 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Process date range filter
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $end_date = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
}

// Initialize data arrays
$vehicle_stats = [
    'total' => 0,
    'active' => 0,
    'sold' => 0,
    'maintenance' => 0,
];

$category_data = [];
$monthly_data = [];
$recent_activities = [];

// Check if required tables exist
$check_vehicles_table = "SHOW TABLES LIKE 'vehicles'";
$vehicles_table_exists = $db->select($check_vehicles_table);

if (!empty($vehicles_table_exists)) {
    // Vehicle status counts
    try {
        // Total vehicles
        $total_sql = "SELECT COUNT(*) as count FROM vehicles";
        $result = $db->selectOne($total_sql);
        $vehicle_stats['total'] = $result ? $result['count'] : 0;
        
        // Active vehicles
        $active_sql = "SELECT COUNT(*) as count FROM vehicles WHERE status = 'available'";
        $result = $db->selectOne($active_sql);
        $vehicle_stats['active'] = $result ? $result['count'] : 0;
        
        // Sold vehicles
        $sold_sql = "SELECT COUNT(*) as count FROM vehicles WHERE status = 'sold'";
        $result = $db->selectOne($sold_sql);
        $vehicle_stats['sold'] = $result ? $result['count'] : 0;
        
        // Maintenance vehicles
        $maintenance_sql = "SELECT COUNT(*) as count FROM vehicles WHERE status = 'maintenance'";
        $result = $db->selectOne($maintenance_sql);
        $vehicle_stats['maintenance'] = $result ? $result['count'] : 0;
        
        // Category distribution
        $category_sql = "SELECT vc.name as category, COUNT(v.id) as count 
                        FROM vehicles v
                        JOIN vehicle_categories vc ON v.category_id = vc.id
                        GROUP BY v.category_id
                        ORDER BY count DESC";
        $category_data = $db->select($category_sql) ?: [];
        
        // On n'utilise plus la tendance mensuelle
        
        // Recent activities (last 10)
        $activities_sql = "SELECT v.id, v.brand, v.model, v.year, v.purchase_price as price, v.status, v.created_at, vc.name as category
                          FROM vehicles v
                          JOIN vehicle_categories vc ON v.category_id = vc.id
                          ORDER BY v.created_at DESC
                          LIMIT 10";
        $recent_activities = $db->select($activities_sql) ?: [];
    } catch (Exception $e) {
        // Log error
        error_log('Reports page error: ' . $e->getMessage());
    }
}

// Include header
include 'includes/header.php';
?>

<!-- Page Content -->
<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold"><?= $page_title ?></h2>
    </div>
    
    <!-- Date Range Filter -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input 
                    type="date" 
                    id="start_date" 
                    name="start_date" 
                    value="<?= $start_date ?>" 
                    max="<?= date('Y-m-d') ?>" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                >
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input 
                    type="date" 
                    id="end_date" 
                    name="end_date" 
                    value="<?= $end_date ?>" 
                    max="<?= date('Y-m-d') ?>" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                >
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-accent hover:bg-accent/80 text-white py-2 px-4 rounded mr-2">Apply Filter</button>
                <a href="reports.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded">Reset</a>
            </div>
        </form>
    </div>


    
    <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); if (empty($vehicles_table_exists)): ?>
        <div class="bg-white p-8 rounded-lg shadow text-center text-gray-500">
            <p class="mb-4">The vehicles table doesn't exist in the database.</p>
            <p>Go to <a href="database_maintenance.php" class="text-accent hover:underline">Database Maintenance</a> to create the necessary tables.</p>
        </div>
    <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); else: ?>
        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Vehicles -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-600 mb-2">Total Vehicles</h3>
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-full mr-4">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="text-3xl font-bold text-gray-800"><?= $vehicle_stats['total'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Active Vehicles -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-600 mb-2">Available Vehicles</h3>
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-full mr-4">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="text-3xl font-bold text-gray-800"><?= $vehicle_stats['active'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Sold Vehicles -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-600 mb-2">Sold Vehicles</h3>
                <div class="flex items-center">
                    <div class="bg-purple-100 p-3 rounded-full mr-4">
                        <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="text-3xl font-bold text-gray-800"><?= $vehicle_stats['sold'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Maintenance Vehicles -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-600 mb-2">In Maintenance</h3>
                <div class="flex items-center">
                    <div class="bg-yellow-100 p-3 rounded-full mr-4">
                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="text-3xl font-bold text-gray-800"><?= $vehicle_stats['maintenance'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 gap-6 mb-6">
            <!-- Category Distribution -->
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-600">Vehicle Category Distribution</h3>
                    <div class="text-sm text-gray-500">
                        <span class="font-semibold"><?= count($category_data) ?></span> categories total
                    </div>
                </div>
                
                <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); if (empty($category_data)): ?>
                    <div class="p-4 text-center text-gray-500">
                        <p>No category data available.</p>
                    </div>
                <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="relative md:col-span-2" style="height: 400px;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                        <div class="flex flex-col justify-center">
                            <div class="overflow-y-auto max-h-[350px]">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-left font-medium text-gray-500 py-2">Category</th>
                                            <th class="text-right font-medium text-gray-500 py-2">Count</th>
                                            <th class="text-right font-medium text-gray-500 py-2">Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); 
                                        $total_vehicles = array_sum(array_column($category_data, 'count'));
                                        foreach ($category_data as $item): 
                                            $percentage = $total_vehicles > 0 ? round(($item['count'] / $total_vehicles) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td class="py-1"><?= htmlspecialchars($item['category']) ?></td>
                                            <td class="text-right py-1"><?= $item['count'] ?></td>
                                            <td class="text-right py-1"><?= $percentage ?>%</td>
                                        </tr>
                                        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endif; ?>
            </div>


        </div>

        <!-- Recent Activities -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-600 mb-4">Recent Vehicle Activities</h3>
            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); if (empty($recent_activities)): ?>
                <div class="p-4 text-center text-gray-500">
                    <p>No recent activities found.</p>
                </div>
            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Added</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); foreach ($recent_activities as $activity): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= $activity['id'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($activity['brand'] . ' ' . $activity['model'] . ' (' . $activity['year'] . ')') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($activity['category']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); if ($activity['price'] !== null): ?>
                                            $<?= number_format($activity['price'], 2) ?>
                                        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); else: ?>
                                            N/A
                                        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); if ($activity['status'] === 'available'): ?>
                                            bg-green-100 text-green-800
                                        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); elseif ($activity['status'] === 'sold'): ?>
                                            bg-purple-100 text-purple-800
                                        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); else: ?>
                                            bg-yellow-100 text-yellow-800
                                        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endif; ?>">
                                            <?= ucfirst(htmlspecialchars($activity['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y', strtotime($activity['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endif; ?>
        </div>
    <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endif; ?>
</div>

<!-- JavaScript for Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const categoryCtx = document.getElementById('categoryChart');
        if (categoryCtx) {
            // Calculate total for percentages
            const totalVehicles = <?= array_sum(array_column($category_data, 'count')) ?>;
            
            const categoryData = {
                labels: [
                    <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); foreach ($category_data as $item): ?>
                    '<?= addslashes($item['category']) ?>',
                    <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endforeach; ?>
                ],
                datasets: [{
                    label: 'Vehicles by Category',
                    data: [
                        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); foreach ($category_data as $item): ?>
                        <?= $item['count'] ?>,
                        <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endforeach; ?>
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(199, 199, 199, 0.8)',
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(199, 199, 199, 1)',
                    ],
                    borderWidth: 2,
                    hoverOffset: 15
                }]
            };

            new Chart(categoryCtx, {
                type: 'doughnut',
                data: categoryData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw;
                                    const percentage = totalVehicles > 0 ? Math.round((value / totalVehicles) * 100) : 0;
                                    return `${context.label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });
        }
        // Fin du if
    });
</script>

<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin();
// Include footer
include 'includes/footer.php';
?>
