<?php
/**
 * Get User by ID API Endpoint
 * 
 * Retrieves detailed information about a specific user
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
    $currentUser = requireAuth();
    
    // Get user ID from query parameters
    if (!isset($_GET['id'])) {
        sendResponse(400, 'User ID is required');
    }
    
    $userId = intval($_GET['id']);
    
    // Security check: users can only view their own profiles unless they are admins
    if ($userId != $currentUser['id'] && $currentUser['role'] !== 'admin') {
        sendResponse(403, 'You do not have permission to view this user');
    }
    
    // Initialize database
    $db = new Database();
    
    // Fetch user information
    $query = "SELECT id, email, first_name, last_name, role, status, phone, 
              profile_image, created_at, updated_at, last_login_at, email_verified, 
              address, city, state, zip_code, country, bio, company_name, company_position
              FROM users 
              WHERE id = :id";
    
    $user = $db->selectOne($query, ['id' => $userId]);
    
    if (!$user) {
        sendResponse(404, 'User not found');
    }
    
    // Get user statistics if admin is viewing
    $statistics = null;
    if ($currentUser['role'] === 'admin') {
        // Fetch rental count and total spent
        $rentalStats = $db->selectOne(
            "SELECT COUNT(*) as rental_count, SUM(total_cost) as total_spent 
             FROM rentals 
             WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
        
        // Fetch last rental
        $lastRental = $db->selectOne(
            "SELECT r.id, r.start_date, r.end_date, r.total_cost, v.make, v.model
             FROM rentals r
             JOIN vehicles v ON r.vehicle_id = v.id
             WHERE r.user_id = :user_id
             ORDER BY r.created_at DESC
             LIMIT 1",
            ['user_id' => $userId]
        );
        
        $statistics = [
            'rental_count' => $rentalStats['rental_count'] ?? 0,
            'total_spent' => $rentalStats['total_spent'] ?? 0,
            'last_rental' => $lastRental ? [
                'id' => $lastRental['id'],
                'start_date' => $lastRental['start_date'],
                'end_date' => $lastRental['end_date'],
                'total_cost' => $lastRental['total_cost'],
                'vehicle' => $lastRental['make'] . ' ' . $lastRental['model']
            ] : null
        ];
    }
    
    // Format response
    $result = [
        'id' => $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'full_name' => $user['first_name'] . ' ' . $user['last_name'],
        'role' => $user['role'],
        'status' => $user['status'],
        'phone' => $user['phone'],
        'profile_image' => $user['profile_image'],
        'email_verified' => (bool) $user['email_verified'],
        'created_at' => $user['created_at'],
        'updated_at' => $user['updated_at'],
        'last_login_at' => $user['last_login_at'],
        'contact' => [
            'address' => $user['address'],
            'city' => $user['city'],
            'state' => $user['state'],
            'zip_code' => $user['zip_code'],
            'country' => $user['country']
        ],
        'professional' => [
            'bio' => $user['bio'],
            'company_name' => $user['company_name'],
            'company_position' => $user['company_position']
        ]
    ];
    
    // Add statistics if available
    if ($statistics) {
        $result['statistics'] = $statistics;
    }
    
    // Send response
    sendResponse(200, 'User details retrieved successfully', $result);
    
} catch (Exception $e) {
    // Log the error
    error_log("Get user details error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to retrieve user details: ' . $e->getMessage());
}
