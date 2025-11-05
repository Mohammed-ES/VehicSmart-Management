<?php
/**
 * Get Rental Details API Endpoint
 * 
 * Retrieves detailed information about a specific rental
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
    
    // Get rental ID from query parameters
    if (!isset($_GET['id'])) {
        sendResponse(400, 'Rental ID is required');
    }
    
    $rentalId = intval($_GET['id']);
    
    // Initialize database
    $db = new Database();
    
    // Fetch the rental with related information
    $query = "SELECT r.*, 
              v.id AS vehicle_id, v.make, v.model, v.year, v.license_plate, v.image_url, v.description, v.vehicle_type,
              u.id AS user_id, u.email, u.first_name, u.last_name, u.phone,
              t.id AS transaction_id, t.amount, t.payment_method, t.status AS payment_status
              FROM rentals r
              JOIN vehicles v ON r.vehicle_id = v.id
              JOIN users u ON r.user_id = u.id
              LEFT JOIN transactions t ON r.transaction_id = t.id
              WHERE r.id = :id";
    
    $rental = $db->selectOne($query, ['id' => $rentalId]);
    
    if (!$rental) {
        sendResponse(404, 'Rental not found');
    }
    
    // Security check: ensure user is authorized to view this rental
    if ($rental['user_id'] != $user['id'] && $user['role'] !== 'admin') {
        sendResponse(403, 'You do not have permission to view this rental');
    }
    
    // Fetch payment records
    $payments = $db->select(
        "SELECT * FROM transactions 
         WHERE id = :transaction_id OR related_transaction_id = :related_id
         ORDER BY created_at DESC",
        [
            'transaction_id' => $rental['transaction_id'],
            'related_id' => $rental['transaction_id']
        ]
    );
    
    // Format payment records
    $paymentRecords = [];
    foreach ($payments as $payment) {
        $paymentRecords[] = [
            'id' => $payment['id'],
            'amount' => $payment['amount'],
            'payment_method' => $payment['payment_method'],
            'status' => $payment['status'],
            'created_at' => $payment['created_at'],
            'description' => $payment['description'] ?? ($payment['amount'] < 0 ? 'Refund' : 'Payment')
        ];
    }
    
    // Format response
    $result = [
        'id' => $rental['id'],
        'vehicle' => [
            'id' => $rental['vehicle_id'],
            'make' => $rental['make'],
            'model' => $rental['model'],
            'year' => $rental['year'],
            'license_plate' => $rental['license_plate'],
            'image_url' => $rental['image_url'],
            'description' => $rental['description'],
            'vehicle_type' => $rental['vehicle_type']
        ],
        'user' => [
            'id' => $rental['user_id'],
            'email' => $rental['email'],
            'name' => $rental['first_name'] . ' ' . $rental['last_name'],
            'phone' => $rental['phone']
        ],
        'rental_details' => [
            'start_date' => $rental['start_date'],
            'end_date' => $rental['end_date'],
            'duration_days' => $rental['duration_days'],
            'total_cost' => $rental['total_cost'],
            'status' => $rental['status'],
            'created_at' => $rental['created_at'],
            'updated_at' => $rental['updated_at']
        ],
        'payment' => [
            'transaction_id' => $rental['transaction_id'],
            'amount' => $rental['amount'],
            'payment_method' => $rental['payment_method'],
            'status' => $rental['payment_status'],
            'records' => $paymentRecords
        ]
    ];
    
    // Add cancellation info if applicable
    if ($rental['status'] === 'canceled') {
        $result['cancellation'] = [
            'reason' => $rental['cancellation_reason'],
            'canceled_at' => $rental['updated_at'],
            'refund_amount' => $rental['refund_amount'],
            'refund_transaction_id' => $rental['refund_transaction_id']
        ];
        
        // Get cancellation user info if available
        if (!empty($rental['canceled_by_user_id'])) {
            $cancelUser = $db->selectOne(
                "SELECT id, email, first_name, last_name FROM users WHERE id = :id",
                ['id' => $rental['canceled_by_user_id']]
            );
            
            if ($cancelUser) {
                $result['cancellation']['canceled_by'] = [
                    'id' => $cancelUser['id'],
                    'email' => $cancelUser['email'],
                    'name' => $cancelUser['first_name'] . ' ' . $cancelUser['last_name']
                ];
            }
        }
    }
    
    // Send response
    sendResponse(200, 'Rental details retrieved successfully', $result);
    
} catch (Exception $e) {
    // Log the error
    error_log("Get rental details error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to retrieve rental details: ' . $e->getMessage());
}
