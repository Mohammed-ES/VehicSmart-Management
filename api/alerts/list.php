<?php
/**
 * List Alerts API Endpoint
 * 
 * Retrieves all alerts/notifications for the authenticated user
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
    $isRead = isset($_GET['is_read']) ? (bool) $_GET['is_read'] : null;
    $type = isset($_GET['type']) ? $_GET['type'] : null;
    $relatedType = isset($_GET['related_type']) ? $_GET['related_type'] : null;
    $relatedId = isset($_GET['related_id']) ? intval($_GET['related_id']) : null;
    
    // Base query
    $query = "SELECT * FROM alerts WHERE user_id = :user_id";
    $params = ['user_id' => $user['id']];
    
    // Apply filters
    if ($isRead !== null) {
        $query .= " AND is_read = :is_read";
        $params['is_read'] = $isRead ? 1 : 0;
    }
    
    if ($type) {
        $query .= " AND type = :type";
        $params['type'] = $type;
    }
    
    if ($relatedType) {
        $query .= " AND related_type = :related_type";
        $params['related_type'] = $relatedType;
    }
    
    if ($relatedId) {
        $query .= " AND related_id = :related_id";
        $params['related_id'] = $relatedId;
    }
    
    // Get total count
    $countQuery = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
    $totalResult = $db->selectOne($countQuery, $params);
    $total = $totalResult['total'];
    
    // Add sorting (newest first)
    $query .= " ORDER BY created_at DESC";
    
    // Add pagination
    $query .= " LIMIT :limit OFFSET :offset";
    $params['limit'] = $limit;
    $params['offset'] = $offset;
    
    // Execute query
    $alerts = $db->select($query, $params);
    
    // Process results and add related info
    $results = [];
    foreach ($alerts as $alert) {
        $alertData = [
            'id' => $alert['id'],
            'type' => $alert['type'],
            'message' => $alert['message'],
            'is_read' => (bool) $alert['is_read'],
            'created_at' => $alert['created_at'],
            'related_id' => $alert['related_id'],
            'related_type' => $alert['related_type'],
        ];
        
        // Add related data based on type
        if ($alert['related_id'] && $alert['related_type']) {
            switch ($alert['related_type']) {
                case 'rental':
                    $rentalInfo = $db->selectOne(
                        "SELECT r.*, v.make, v.model 
                         FROM rentals r
                         JOIN vehicles v ON r.vehicle_id = v.id
                         WHERE r.id = :id",
                        ['id' => $alert['related_id']]
                    );
                    
                    if ($rentalInfo) {
                        $alertData['related_data'] = [
                            'start_date' => $rentalInfo['start_date'],
                            'end_date' => $rentalInfo['end_date'],
                            'status' => $rentalInfo['status'],
                            'vehicle' => $rentalInfo['make'] . ' ' . $rentalInfo['model']
                        ];
                    }
                    break;
                    
                case 'message':
                    $messageInfo = $db->selectOne(
                        "SELECT id, subject, sender_id, created_at,
                         (SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE id = sender_id) as sender_name
                         FROM messages 
                         WHERE id = :id",
                        ['id' => $alert['related_id']]
                    );
                    
                    if ($messageInfo) {
                        $alertData['related_data'] = [
                            'subject' => $messageInfo['subject'],
                            'sender_name' => $messageInfo['sender_name'],
                            'created_at' => $messageInfo['created_at']
                        ];
                    }
                    break;
                    
                case 'vehicle':
                    $vehicleInfo = $db->selectOne(
                        "SELECT id, make, model, year, license_plate 
                         FROM vehicles 
                         WHERE id = :id",
                        ['id' => $alert['related_id']]
                    );
                    
                    if ($vehicleInfo) {
                        $alertData['related_data'] = [
                            'make' => $vehicleInfo['make'],
                            'model' => $vehicleInfo['model'],
                            'year' => $vehicleInfo['year'],
                            'license_plate' => $vehicleInfo['license_plate']
                        ];
                    }
                    break;
                    
                case 'maintenance':
                    $maintenanceInfo = $db->selectOne(
                        "SELECT m.*, v.make, v.model 
                         FROM maintenance_records m
                         JOIN vehicles v ON m.vehicle_id = v.id
                         WHERE m.id = :id",
                        ['id' => $alert['related_id']]
                    );
                    
                    if ($maintenanceInfo) {
                        $alertData['related_data'] = [
                            'date' => $maintenanceInfo['maintenance_date'],
                            'type' => $maintenanceInfo['maintenance_type'],
                            'vehicle' => $maintenanceInfo['make'] . ' ' . $maintenanceInfo['model']
                        ];
                    }
                    break;
            }
        }
        
        $results[] = $alertData;
    }
    
    // Calculate pagination info
    $totalPages = ceil($total / $limit);
    $hasNextPage = $page < $totalPages;
    $hasPrevPage = $page > 1;
    
    // Get unread count for badge display
    $unreadCount = $db->selectOne(
        "SELECT COUNT(*) as count FROM alerts WHERE user_id = :user_id AND is_read = 0",
        ['user_id' => $user['id']]
    );
    
    // Send response
    sendResponse(200, 'Alerts retrieved successfully', [
        'data' => $results,
        'unread_count' => $unreadCount['count'],
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
    error_log("List alerts error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to retrieve alerts: ' . $e->getMessage());
}
