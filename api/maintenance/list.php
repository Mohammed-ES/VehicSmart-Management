<?php
/**
 * List Maintenance Records API Endpoint
 * 
 * Retrieves maintenance records with filtering options
 */

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(405, 'Method Not Allowed. Please use GET');
}

try {
    // Verify authentication
    $user = requireAuth();
    
    // Initialize database
    $db = new Database();
    
    // Set parameters with defaults
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    // Filter parameters
    $vehicleId = isset($_GET['vehicle_id']) ? intval($_GET['vehicle_id']) : null;
    $maintenanceType = isset($_GET['type']) ? $_GET['type'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : null;
    $toDate = isset($_GET['to_date']) ? $_GET['to_date'] : null;
    $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'maintenance_date';
    $sortOrder = isset($_GET['sort_order']) && strtolower($_GET['sort_order']) === 'asc' ? 'ASC' : 'DESC';
    
    // Validate sortBy to prevent SQL injection
    $allowedSortFields = ['id', 'vehicle_id', 'maintenance_date', 'maintenance_type', 'status', 'cost', 'created_at'];
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'maintenance_date';
    }
    
    // Base query
    $query = "SELECT m.*, 
              v.make, v.model, v.year, v.license_plate, v.vin
              FROM maintenance_records m
              JOIN vehicles v ON m.vehicle_id = v.id
              WHERE 1=1";
    
    $params = [];
    
    // Apply filters
    if ($vehicleId) {
        $query .= " AND m.vehicle_id = :vehicle_id";
        $params['vehicle_id'] = $vehicleId;
    }
    
    if ($maintenanceType) {
        $query .= " AND m.maintenance_type = :maintenance_type";
        $params['maintenance_type'] = $maintenanceType;
    }
    
    if ($status) {
        $query .= " AND m.status = :status";
        $params['status'] = $status;
    }
    
    if ($fromDate) {
        $query .= " AND m.maintenance_date >= :from_date";
        $params['from_date'] = $fromDate;
    }
    
    if ($toDate) {
        $query .= " AND m.maintenance_date <= :to_date";
        $params['to_date'] = $toDate;
    }
    
    // Limit access based on role
    if ($user['role'] !== 'admin') {
        // For non-admin users, only show records for rentals they have access to
        $query .= " AND EXISTS (
            SELECT 1 FROM rentals r 
            WHERE r.vehicle_id = m.vehicle_id AND r.user_id = :user_id
        )";
        $params['user_id'] = $user['id'];
    }
    
    // Get total count
    $countQuery = str_replace(
        "SELECT m.*, v.make, v.model, v.year, v.license_plate, v.vin", 
        "SELECT COUNT(*) as total", 
        $query
    );
    
    $totalResult = $db->selectOne($countQuery, $params);
    $total = $totalResult['total'];
    
    // Add sorting
    $query .= " ORDER BY m.$sortBy $sortOrder";
    
    // Add pagination
    $query .= " LIMIT :limit OFFSET :offset";
    $params['limit'] = $limit;
    $params['offset'] = $offset;
    
    // Execute query
    $records = $db->select($query, $params);
    
    // Process results
    $results = [];
    foreach ($records as $record) {
        $results[] = [
            'id' => $record['id'],
            'vehicle' => [
                'id' => $record['vehicle_id'],
                'make' => $record['make'],
                'model' => $record['model'],
                'year' => $record['year'],
                'license_plate' => $record['license_plate'],
                'vin' => $record['vin']
            ],
            'maintenance_date' => $record['maintenance_date'],
            'maintenance_type' => $record['maintenance_type'],
            'description' => $record['description'],
            'cost' => $record['cost'],
            'status' => $record['status'],
            'odometer_reading' => $record['odometer_reading'],
            'performed_by' => $record['performed_by'],
            'notes' => $record['notes'],
            'documents' => $record['documents'] ? json_decode($record['documents'], true) : [],
            'created_at' => $record['created_at'],
            'updated_at' => $record['updated_at']
        ];
    }
    
    // Get maintenance types for filters
    $maintenanceTypes = $db->select(
        "SELECT DISTINCT maintenance_type FROM maintenance_records ORDER BY maintenance_type"
    );
    
    $types = array_map(function($type) {
        return $type['maintenance_type'];
    }, $maintenanceTypes);
    
    // Calculate pagination info
    $totalPages = ceil($total / $limit);
    $hasNextPage = $page < $totalPages;
    $hasPrevPage = $page > 1;
    
    // Send response
    sendResponse(200, 'Maintenance records retrieved successfully', [
        'data' => $results,
        'types' => $types,
        'pagination' => [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_next_page' => $hasNextPage,
            'has_prev_page' => $hasPrevPage
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("List maintenance records error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to retrieve maintenance records: ' . $e->getMessage());
}
