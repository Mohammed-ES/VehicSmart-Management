<?php
/**
 * List User Rentals API Endpoint
 * 
 * Retrieves all rentals for the authenticated user or a specific user for admins
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
    
    // Set default parameters
    $userId = $user['id'];
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 10;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // Allow admins to view rentals for any user
    if ($user['role'] === 'admin' && isset($_GET['user_id'])) {
        $userId = intval($_GET['user_id']);
    }
    
    // Start building the query
    $query = "SELECT r.*, 
              v.make, v.model, v.year, v.license_plate, v.image_url,
              u.email, u.first_name, u.last_name
              FROM rentals r
              JOIN vehicles v ON r.vehicle_id = v.id
              JOIN users u ON r.user_id = u.id
              WHERE r.user_id = :user_id";
    
    $params = ['user_id' => $userId];
    
    // Add status filter if specified
    if ($status) {
        $query .= " AND r.status = :status";
        $params['status'] = $status;
    }
    
    // Add sorting (newest first)
    $query .= " ORDER BY r.created_at DESC";
    
    // Get total count
    $countQuery = str_replace("SELECT r.*, v.make, v.model, v.year, v.license_plate, v.image_url, u.email, u.first_name, u.last_name", "SELECT COUNT(*) as total", $query);
    $totalResult = $db->selectOne($countQuery, $params);
    $total = $totalResult['total'];
    
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
                'name' => $rental['first_name'] . ' ' . $rental['last_name']
            ],
            'transaction_id' => $rental['transaction_id'],
            'start_date' => $rental['start_date'],
            'end_date' => $rental['end_date'],
            'duration_days' => $rental['duration_days'],
            'total_cost' => $rental['total_cost'],
            'status' => $rental['status'],
            'created_at' => $rental['created_at']
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
    error_log("List rentals error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to retrieve rentals: ' . $e->getMessage());
}
