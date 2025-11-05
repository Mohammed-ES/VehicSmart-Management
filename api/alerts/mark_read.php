<?php
/**
 * Mark Alerts as Read API Endpoint
 * 
 * Allows users to mark alerts as read
 */

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, 'Method Not Allowed. Please use POST');
}

try {
    // Verify authentication
    $user = requireAuth();
    
    // Get input data
    $data = getInputData('POST');
    
    // Initialize database
    $db = new Database();
    
    $updatedCount = 0;
    
    // Handle marking specific alerts as read
    if (isset($data['alert_ids']) && is_array($data['alert_ids'])) {
        // Validate IDs
        $alertIds = array_map('intval', $data['alert_ids']);
        if (empty($alertIds)) {
            sendResponse(400, 'No valid alert IDs provided');
        }
        
        // Format IDs for SQL IN clause
        $idParams = [];
        $placeholders = [];
        
        foreach ($alertIds as $i => $id) {
            $key = "id$i";
            $idParams[$key] = $id;
            $placeholders[] = ":$key";
        }
        
        // Update alerts
        $query = "UPDATE alerts SET is_read = 1 
                 WHERE user_id = :user_id AND id IN (" . implode(',', $placeholders) . ")";
        
        $params = array_merge(['user_id' => $user['id']], $idParams);
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute($params);
        
        $updatedCount = $stmt->rowCount();
    } 
    // Handle marking all alerts as read
    else if (isset($data['mark_all']) && $data['mark_all']) {
        // Optional filter by type
        $query = "UPDATE alerts SET is_read = 1 WHERE user_id = :user_id";
        $params = ['user_id' => $user['id']];
        
        if (isset($data['type']) && !empty($data['type'])) {
            $query .= " AND type = :type";
            $params['type'] = $data['type'];
        }
        
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute($params);
        
        $updatedCount = $stmt->rowCount();
    }
    // Handle marking all alerts for a related item as read
    else if (isset($data['related_type']) && isset($data['related_id'])) {
        $query = "UPDATE alerts SET is_read = 1 
                 WHERE user_id = :user_id AND related_type = :related_type AND related_id = :related_id";
        
        $params = [
            'user_id' => $user['id'],
            'related_type' => $data['related_type'],
            'related_id' => intval($data['related_id'])
        ];
        
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute($params);
        
        $updatedCount = $stmt->rowCount();
    }
    else {
        sendResponse(400, 'Invalid request. Provide alert_ids array, mark_all=true, or related_type with related_id');
    }
    
    // Get current unread count for badge display
    $unreadCount = $db->selectOne(
        "SELECT COUNT(*) as count FROM alerts WHERE user_id = :user_id AND is_read = 0",
        ['user_id' => $user['id']]
    );
    
    // Send response
    sendResponse(200, 'Alerts marked as read successfully', [
        'updated_count' => $updatedCount,
        'unread_count' => $unreadCount['count']
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Mark alerts as read error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to mark alerts as read: ' . $e->getMessage());
}
