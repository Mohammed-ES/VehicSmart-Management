<?php
/**
 * List All Rentals API Endpoint
 * 
 * For administrators to view all rentals in the system with filtering options
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
    // Verify admin authentication
    $user = requireRole('admin');
    
    // Initialize database
    $db = new Database();
    
    // Set parameters with defaults
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    // Filter parameters
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    $vehicleId = isset($_GET['vehicle_id']) ? intval($_GET['vehicle_id']) : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $startDateFrom = isset($_GET['start_date_from']) ? $_GET['start_date_from'] : null;
    $startDateTo = isset($_GET['start_date_to']) ? $_GET['start_date_to'] : null;
    $endDateFrom = isset($_GET['end_date_from']) ? $_GET['end_date_from'] : null;
    $endDateTo = isset($_GET['end_date_to']) ? $_GET['end_date_to'] : null;
    $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
    $sortOrder = isset($_GET['sort_order']) && strtolower($_GET['sort_order']) === 'asc' ? 'ASC' : 'DESC';
    
    // Validate sortBy to prevent SQL injection
    $allowedSortFields = ['created_at', 'start_date', 'end_date', 'total_cost', 'status'];
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'created_at';
    }
    
    // Base query
    $query = "SELECT r.*, 
              v.make, v.model, v.year, v.license_plate, v.image_url,
              u.email, u.first_name, u.last_name, u.phone
              FROM rentals r
              JOIN vehicles v ON r.vehicle_id = v.id
              JOIN users u ON r.user_id = u.id
              WHERE 1=1";
    
    $params = [];
    
    // Apply filters
    if ($userId) {
        $query .= " AND r.user_id = :user_id";
        $params['user_id'] = $userId;
    }
    
    if ($vehicleId) {
        $query .= " AND r.vehicle_id = :vehicle_id";
        $params['vehicle_id'] = $vehicleId;
    }
    
    if ($status) {
        $query .= " AND r.status = :status";
        $params['status'] = $status;
    }
    
    if ($startDateFrom) {
        $query .= " AND r.start_date >= :start_date_from";
        $params['start_date_from'] = $startDateFrom;
    }
    
    if ($startDateTo) {
        $query .= " AND r.start_date <= :start_date_to";
        $params['start_date_to'] = $startDateTo;
    }
    
    if ($endDateFrom) {
        $query .= " AND r.end_date >= :end_date_from";
        $params['end_date_from'] = $endDateFrom;
    }
    
    if ($endDateTo) {
        $query .= " AND r.end_date <= :end_date_to";
        $params['end_date_to'] = $endDateTo;
    }
    
    // Search by text (vehicle make, model, license plate, or user name/email)
    if (isset($_GET['search']) && $_GET['search']) {
        $searchTerm = '%' . $_GET['search'] . '%';
        $query .= " AND (v.make LIKE :search OR v.model LIKE :search OR v.license_plate LIKE :search OR 
                        u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)";
        $params['search'] = $searchTerm;
    }
    
    // Get total count
    $countQuery = str_replace(
        "SELECT r.*, v.make, v.model, v.year, v.license_plate, v.image_url, u.email, u.first_name, u.last_name, u.phone", 
        "SELECT COUNT(*) as total", 
        $query
    );
    
    $totalResult = $db->selectOne($countQuery, $params);
    $total = $totalResult['total'];
    
    // Add sorting
    $query .= " ORDER BY r.$sortBy $sortOrder";
    
    // Add pagination
    $query .= " LIMIT :limit OFFSET :offset";
    $params['limit'] = $limit;
    $params['offset'] = $offset;
    
    // Execute query
    $rentals = $db->select($query, $params);
    
    // Process results
    $results = [];
    foreach ($rentals as $rental) {
        $results[] = [
            'id' => $rental['id'],
            'vehicle' => [
                'id' => $rental['vehicle_id'],
                'make' => $rental['make'],
                'model' => $rental['model'],
                'year' => $rental['year'],
                'license_plate' => $rental['license_plate'],
                'image_url' => $rental['image_url']
            ],
            'user' => [
                'id' => $rental['user_id'],
                'email' => $rental['email'],
                'name' => $rental['first_name'] . ' ' . $rental['last_name'],
                'phone' => $rental['phone']
            ],
            'transaction_id' => $rental['transaction_id'],
            'start_date' => $rental['start_date'],
            'end_date' => $rental['end_date'],
            'duration_days' => $rental['duration_days'],
            'total_cost' => $rental['total_cost'],
            'status' => $rental['status'],
            'created_at' => $rental['created_at'],
            'updated_at' => $rental['updated_at'],
            'cancellation_reason' => $rental['cancellation_reason'],
            'refund_amount' => $rental['refund_amount']
        ];
    }
    
    // Calculate pagination info
    $totalPages = ceil($total / $limit);
    $hasNextPage = $page < $totalPages;
    $hasPrevPage = $page > 1;
    
    // Send response
    sendResponse(200, 'Rentals retrieved successfully', [
        'data' => $results,
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
    error_log("List all rentals error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to retrieve rentals: ' . $e->getMessage());
}
