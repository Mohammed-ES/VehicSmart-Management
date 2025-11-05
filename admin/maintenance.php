<?php
/**
 * Vehicle Maintenance Management
 * 
 * Page for tracking and managing vehicle maintenance records
 */

// Include required files
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is admin
requireAdmin();

// Get current user
$user = getCurrentUser();

// Set page title
$pageTitle = 'Vehicle Maintenance';
$page_title = 'Vehicle Maintenance'; // For backwards compatibility with header.php

// Include header
include_once 'includes/header.php';

// Initialize database
$db = Database::getInstance();

// Initialize variables
$maintenance_records = [];
$vehicles = [];
$message = '';
$messageType = '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$total_records = 0;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : null;

// Check if maintenance_records table exists
$check_maintenance_table = "SHOW TABLES LIKE 'maintenance_records'";
$maintenance_table_exists = $db->select($check_maintenance_table);

// Create the maintenance_records table if it doesn't exist
if (empty($maintenance_table_exists)) {
    try {
        $create_table_sql = "CREATE TABLE IF NOT EXISTS maintenance_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vehicle_id INT NOT NULL,
            service_type VARCHAR(100) NOT NULL,
            description TEXT,
            cost DECIMAL(10,2),
            service_date DATE NOT NULL,
            next_service_date DATE,
            mileage INT,
            status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
            notes TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
        )";
        $db->query($create_table_sql);
        $message = "Maintenance records table created successfully.";
        $messageType = "success";
    } catch (Exception $e) {
        $message = "Error creating maintenance records table: " . $e->getMessage();
        $messageType = "error";
    }
}

// Process form submission for adding/editing maintenance record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_record']) || isset($_POST['edit_record'])) {
        $recordId = isset($_POST['record_id']) ? (int)$_POST['record_id'] : null;
        $vehicleId = (int)$_POST['vehicle_id'];
        $serviceType = filter_input(INPUT_POST, 'service_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $cost = filter_input(INPUT_POST, 'cost', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $serviceDate = filter_input(INPUT_POST, 'service_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $nextServiceDate = filter_input(INPUT_POST, 'next_service_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: null;
        $mileage = filter_input(INPUT_POST, 'mileage', FILTER_SANITIZE_NUMBER_INT) ?: null;
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        try {
            if ($recordId) {
                // Update existing record
                $update_sql = "UPDATE maintenance_records 
                              SET vehicle_id = ?, service_type = ?, description = ?, cost = ?, 
                                  service_date = ?, next_service_date = ?, mileage = ?, status = ?, notes = ? 
                              WHERE id = ?";
                $db->update($update_sql, [
                    $vehicleId, $serviceType, $description, $cost, 
                    $serviceDate, $nextServiceDate, $mileage, $status, $notes, $recordId
                ]);
                $message = "Maintenance record updated successfully.";
            } else {
                // Add new record
                $insert_sql = "INSERT INTO maintenance_records 
                              (vehicle_id, service_type, description, cost, service_date, next_service_date, mileage, status, notes, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $db->insert($insert_sql, [
                    $vehicleId, $serviceType, $description, $cost, 
                    $serviceDate, $nextServiceDate, $mileage, $status, $notes, $user['id']
                ]);
                $message = "Maintenance record added successfully.";
            }
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
    
    // Process delete action
    if (isset($_POST['delete_record'])) {
        $recordId = (int)$_POST['record_id'];
        
        try {
            $db->delete("DELETE FROM maintenance_records WHERE id = ?", [$recordId]);
            $message = "Maintenance record deleted successfully.";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Get all vehicles for dropdown
try {
    $vehicles_query = "SELECT id, brand, model, year FROM vehicles ORDER BY brand, model";
    $vehicles = $db->select($vehicles_query) ?: [];
} catch (Exception $e) {
    // Silently handle error
}

// Get maintenance records with filtering and pagination
if (!empty($maintenance_table_exists)) {
    try {
        // Build filter conditions
        $conditions = [];
        $params = [];
        
        if ($vehicleId) {
            $conditions[] = "m.vehicle_id = ?";
            $params[] = $vehicleId;
        }
        
        if ($filter !== 'all') {
            $conditions[] = "m.status = ?";
            $params[] = $filter;
        }
        
        $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        // Count total records with filter
        $count_query = "SELECT COUNT(*) as count FROM maintenance_records m $where_clause";
        $count_result = $db->selectOne($count_query, $params);
        $total_records = $count_result ? $count_result['count'] : 0;
        
        // Get paginated records
        $query_params = array_merge($params, [$offset, $limit]);
        $records_query = "SELECT m.*, v.brand, v.model, v.year
                          FROM maintenance_records m 
                          JOIN vehicles v ON m.vehicle_id = v.id 
                          $where_clause 
                          ORDER BY m.service_date DESC 
                          LIMIT $offset, $limit";
        $maintenance_records = $db->select($records_query, $params) ?: [];
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Calculate pagination values
$total_pages = ceil($total_records / $limit);
?>

<div class="p-6 w-full">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800"><?= $page_title ?></h1>
            <p class="text-gray-600">Track and manage vehicle maintenance records</p>
        </div>
        <button id="addMaintenanceBtn" class="bg-accent hover:bg-accent/80 text-white py-2 px-4 rounded flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Maintenance Record
        </button>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="mb-6 p-4 rounded <?= $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>
    
    <!-- Filter controls -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="vehicle_id" class="block text-sm font-medium text-gray-700 mb-1">Vehicle</label>
                <select 
                    id="vehicle_id" 
                    name="vehicle_id" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                >
                    <option value="">All Vehicles</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= $vehicle['id'] ?>" <?= $vehicleId == $vehicle['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="filter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select 
                    id="filter" 
                    name="filter" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                >
                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="scheduled" <?= $filter === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="in_progress" <?= $filter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed" <?= $filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-accent hover:bg-accent/80 text-white py-2 px-4 rounded mr-2">Apply Filters</button>
                <a href="maintenance.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded">Reset</a>
            </div>
        </form>
    </div>
    
    <!-- Maintenance records table -->
    <?php if (!$maintenance_table_exists): ?>
        <div class="bg-white p-8 rounded-lg shadow text-center text-gray-500">
            <p class="mb-4">The maintenance_records table doesn't exist in the database.</p>
            <p>Use the button above to add your first maintenance record and the table will be created automatically.</p>
        </div>
    <?php elseif (empty($maintenance_records)): ?>
        <div class="bg-white p-8 rounded-lg shadow text-center text-gray-500">
            <p>No maintenance records found. Add your first record using the button above.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Service</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($maintenance_records as $record): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($record['brand'] . ' ' . $record['model'] . ' (' . $record['year'] . ')') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($record['service_type']) ?>
                                    <?php if (!empty($record['description'])): ?>
                                        <div class="text-xs text-gray-400 truncate max-w-[150px]" title="<?= htmlspecialchars($record['description']) ?>">
                                            <?= htmlspecialchars($record['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($record['service_date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    $<?= number_format($record['cost'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php 
                                    switch($record['status']) {
                                        case 'scheduled':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'in_progress':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'completed':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'cancelled':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                        <?= ucfirst(str_replace('_', ' ', $record['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $record['next_service_date'] ? date('M j, Y', strtotime($record['next_service_date'])) : 'N/A' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button 
                                            class="text-blue-600 hover:text-blue-900 edit-record" 
                                            data-record='<?= htmlspecialchars(json_encode($record)) ?>'
                                            title="Edit"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        
                                        <form method="POST" action="" class="inline delete-maintenance-form">
                                            <input type="hidden" name="record_id" value="<?= $record['id'] ?>">
                                            <input type="hidden" name="delete_record" value="1">
                                            <button type="button" class="text-red-600 hover:text-red-900 delete-maintenance-btn" title="Delete" data-vehicle="<?= e($record['brand'] . ' ' . $record['model']) ?>" data-type="<?= e($record['maintenance_type']) ?>">
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
                        Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_records) ?> of <?= $total_records ?> maintenance records
                    </div>
                    <div class="flex space-x-1">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&filter=<?= $filter ?>&vehicle_id=<?= $vehicleId ?? '' ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                                &laquo; Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>&filter=<?= $filter ?>&vehicle_id=<?= $vehicleId ?? '' ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                                Next &raquo;
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Maintenance Record Modal -->
<div id="maintenanceModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b">
                <h3 id="modalTitle" class="text-xl font-semibold text-gray-800">Add Maintenance Record</h3>
            </div>
            <div class="p-6">
                <form id="maintenanceForm" method="POST" action="">
                    <input type="hidden" id="record_id" name="record_id" value="">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="vehicle_id_modal" class="block text-sm font-medium text-gray-700 mb-1">Vehicle *</label>
                            <select 
                                id="vehicle_id_modal" 
                                name="vehicle_id" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                            >
                                <option value="">Select Vehicle</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?= $vehicle['id'] ?>">
                                        <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="service_type" class="block text-sm font-medium text-gray-700 mb-1">Service Type *</label>
                            <select 
                                id="service_type" 
                                name="service_type" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                            >
                                <option value="">Select Service Type</option>
                                <option value="Oil Change">Oil Change</option>
                                <option value="Tire Rotation">Tire Rotation</option>
                                <option value="Brake Service">Brake Service</option>
                                <option value="Air Filter">Air Filter</option>
                                <option value="Battery Replacement">Battery Replacement</option>
                                <option value="Engine Tune-up">Engine Tune-up</option>
                                <option value="Fluid Change">Fluid Change</option>
                                <option value="Spark Plugs">Spark Plugs</option>
                                <option value="Wheel Alignment">Wheel Alignment</option>
                                <option value="Regular Maintenance">Regular Maintenance</option>
                                <option value="Repair">Repair</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                                placeholder="Detailed description of the service or repair"
                            ></textarea>
                        </div>
                        
                        <div>
                            <label for="service_date" class="block text-sm font-medium text-gray-700 mb-1">Service Date *</label>
                            <input 
                                type="date" 
                                id="service_date" 
                                name="service_date" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                            >
                        </div>
                        
                        <div>
                            <label for="next_service_date" class="block text-sm font-medium text-gray-700 mb-1">Next Service Date</label>
                            <input 
                                type="date" 
                                id="next_service_date" 
                                name="next_service_date" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                            >
                        </div>
                        
                        <div>
                            <label for="cost" class="block text-sm font-medium text-gray-700 mb-1">Cost ($) *</label>
                            <input 
                                type="number" 
                                id="cost" 
                                name="cost" 
                                required
                                step="0.01" 
                                min="0" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                                placeholder="0.00"
                            >
                        </div>
                        
                        <div>
                            <label for="mileage" class="block text-sm font-medium text-gray-700 mb-1">Mileage</label>
                            <input 
                                type="number" 
                                id="mileage" 
                                name="mileage" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                                placeholder="Vehicle mileage at service time"
                            >
                        </div>
                        
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select 
                                id="status" 
                                name="status" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                            >
                                <option value="scheduled">Scheduled</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea 
                                id="notes" 
                                name="notes" 
                                rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                                placeholder="Additional notes about this maintenance"
                            ></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <button 
                            type="button" 
                            id="closeModal"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            id="submitBtn"
                            name="add_record"
                            class="px-4 py-2 bg-accent text-white rounded-md hover:bg-accent/80"
                        >
                            Save Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('maintenanceModal');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('maintenanceForm');
    const addBtn = document.getElementById('addMaintenanceBtn');
    const closeBtn = document.getElementById('closeModal');
    const submitBtn = document.getElementById('submitBtn');
    const recordIdInput = document.getElementById('record_id');
    
    // Open modal for adding new record
    addBtn.addEventListener('click', function() {
        modalTitle.textContent = 'Add Maintenance Record';
        form.reset();
        recordIdInput.value = '';
        submitBtn.name = 'add_record';
        submitBtn.textContent = 'Save Record';
        modal.classList.remove('hidden');
    });
    
    // Edit record functionality
    document.querySelectorAll('.edit-record').forEach(button => {
        button.addEventListener('click', function() {
            const record = JSON.parse(this.dataset.record);
            
            modalTitle.textContent = 'Edit Maintenance Record';
            recordIdInput.value = record.id;
            document.getElementById('vehicle_id_modal').value = record.vehicle_id;
            document.getElementById('service_type').value = record.service_type;
            document.getElementById('description').value = record.description || '';
            document.getElementById('service_date').value = record.service_date;
            document.getElementById('next_service_date').value = record.next_service_date || '';
            document.getElementById('cost').value = record.cost;
            document.getElementById('mileage').value = record.mileage || '';
            document.getElementById('status').value = record.status;
            document.getElementById('notes').value = record.notes || '';
            
            submitBtn.name = 'edit_record';
            submitBtn.textContent = 'Update Record';
            modal.classList.remove('hidden');
        });
    });
    
    // Close modal
    closeBtn.addEventListener('click', function() {
        modal.classList.add('hidden');
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
    
    // Delete maintenance record confirmation
    document.querySelectorAll('.delete-maintenance-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const vehicle = this.dataset.vehicle;
            const type = this.dataset.type;
            const form = this.closest('form');
            
            const confirmed = await showConfirmModal({
                title: 'Delete Maintenance Record',
                message: `Are you sure you want to delete the <strong>${type}</strong> record for <strong>${vehicle}</strong>? This action cannot be undone.`,
                confirmText: 'Delete Record',
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
