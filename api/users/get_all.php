<?php
/**
 * Get All Users API Endpoint
 * 
 * Retrieves all users with filtering and pagination for admin users
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
    $role = isset($_GET['role']) ? $_GET['role'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
    $sortOrder = isset($_GET['sort_order']) && strtolower($_GET['sort_order']) === 'asc' ? 'ASC' : 'DESC';
    
    // Validate sortBy to prevent SQL injection
    $allowedSortFields = ['id', 'email', 'first_name', 'last_name', 'role', 'status', 'created_at'];
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'created_at';
    }
    
    // Base query
    $query = "SELECT id, email, first_name, last_name, role, status, phone, profile_image,
              created_at, updated_at, last_login_at, email_verified 
              FROM users 
              WHERE 1=1";
    
    $params = [];
    
    // Apply filters
    if ($role) {
        $query .= " AND role = :role";
        $params['role'] = $role;
    }
    
    if ($status) {
        $query .= " AND status = :status";
        $params['status'] = $status;
    }
    
    // Search by text (name or email)
    if (isset($_GET['search']) && $_GET['search']) {
        $searchTerm = '%' . $_GET['search'] . '%';
        $query .= " AND (email LIKE :search OR first_name LIKE :search OR last_name LIKE :search OR phone LIKE :search)";
        $params['search'] = $searchTerm;
    }
    
    // Get total count
    $countQuery = str_replace(
        "SELECT id, email, first_name, last_name, role, status, phone, profile_image, created_at, updated_at, last_login_at, email_verified", 
        "SELECT COUNT(*) as total", 
        $query
    );
    
    $totalResult = $db->selectOne($countQuery, $params);
    $total = $totalResult['total'];
    
    // Add sorting
    $query .= " ORDER BY $sortBy $sortOrder";
    
    // Add pagination
    $query .= " LIMIT :limit OFFSET :offset";
    $params['limit'] = $limit;
    $params['offset'] = $offset;
    
    // Execute query
    $users = $db->select($query, $params);
    
    // Process results - exclude sensitive information
    $results = [];
    foreach ($users as $userData) {
        // Fetch rental count for each user
        $rentalCount = $db->selectOne(
            "SELECT COUNT(*) as count FROM rentals WHERE user_id = :user_id",
            ['user_id' => $userData['id']]
        );
        
        $results[] = [
            'id' => $userData['id'],
            'email' => $userData['email'],
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'full_name' => $userData['first_name'] . ' ' . $userData['last_name'],
            'role' => $userData['role'],
            'status' => $userData['status'],
            'phone' => $userData['phone'],
            'profile_image' => $userData['profile_image'],
            'email_verified' => (bool) $userData['email_verified'],
            'created_at' => $userData['created_at'],
            'updated_at' => $userData['updated_at'],
            'last_login_at' => $userData['last_login_at'],
            'rental_count' => $rentalCount['count']
        ];
    }
    
    // Calculate pagination info
    $totalPages = ceil($total / $limit);
    $hasNextPage = $page < $totalPages;
    $hasPrevPage = $page > 1;
    
    // Send response
    sendResponse(200, 'Users retrieved successfully', [
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
    error_log("Get all users error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to retrieve users: ' . $e->getMessage());
}
