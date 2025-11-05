<?php
/**
 * Send Message API Endpoint
 * 
 * Allows users to send messages to other users or admins
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
    $data = getInputData('POST', ['recipient_id', 'subject', 'message']);
    $recipientId = intval($data['recipient_id']);
    $subject = trim($data['subject']);
    $message = trim($data['message']);
    $relatedId = isset($data['related_id']) ? intval($data['related_id']) : null;
    $relatedType = isset($data['related_type']) ? $data['related_type'] : null;
    
    // Validate input
    if (empty($subject)) {
        sendResponse(400, 'Subject cannot be empty');
    }
    
    if (empty($message)) {
        sendResponse(400, 'Message cannot be empty');
    }
    
    if ($subject && strlen($subject) > 100) {
        sendResponse(400, 'Subject cannot exceed 100 characters');
    }
    
    if ($message && strlen($message) > 5000) {
        sendResponse(400, 'Message cannot exceed 5000 characters');
    }
    
    // Initialize database
    $db = new Database();
    
    // Check if recipient exists
    $recipient = $db->selectOne(
        "SELECT id, email, first_name, last_name, role, status FROM users WHERE id = :id",
        ['id' => $recipientId]
    );
    
    if (!$recipient) {
        sendResponse(404, 'Recipient not found');
    }
    
    // Check if recipient is active
    if ($recipient['status'] !== 'active') {
        sendResponse(400, 'Cannot send message to inactive user');
    }
    
    // Check if sender is blocked
    $isBlocked = $db->selectOne(
        "SELECT COUNT(*) as count FROM blocked_users 
         WHERE (user_id = :recipient_id AND blocked_user_id = :sender_id)",
        [
            'recipient_id' => $recipientId,
            'sender_id' => $user['id']
        ]
    );
    
    if ($isBlocked['count'] > 0) {
        sendResponse(403, 'You cannot send messages to this user');
    }
    
    // Validate related_type if provided
    if ($relatedType && !in_array($relatedType, ['rental', 'vehicle', 'purchase', 'maintenance', 'support'])) {
        sendResponse(400, 'Invalid related_type value');
    }
    
    // If related_id and related_type are provided, verify the relationship
    if ($relatedId && $relatedType) {
        $relationValid = false;
        
        switch ($relatedType) {
            case 'rental':
                $rental = $db->selectOne(
                    "SELECT id FROM rentals WHERE id = :id AND (user_id = :user_id OR :is_admin = 1)",
                    [
                        'id' => $relatedId,
                        'user_id' => $user['id'],
                        'is_admin' => $user['role'] === 'admin' ? 1 : 0
                    ]
                );
                $relationValid = (bool) $rental;
                break;
                
            case 'vehicle':
                $vehicle = $db->selectOne(
                    "SELECT id FROM vehicles WHERE id = :id",
                    ['id' => $relatedId]
                );
                $relationValid = (bool) $vehicle;
                break;
                
            case 'purchase':
                $purchase = $db->selectOne(
                    "SELECT id FROM purchases WHERE id = :id AND (user_id = :user_id OR :is_admin = 1)",
                    [
                        'id' => $relatedId,
                        'user_id' => $user['id'],
                        'is_admin' => $user['role'] === 'admin' ? 1 : 0
                    ]
                );
                $relationValid = (bool) $purchase;
                break;
                
            case 'maintenance':
                $maintenance = $db->selectOne(
                    "SELECT id FROM maintenance_records WHERE id = :id",
                    ['id' => $relatedId]
                );
                $relationValid = (bool) $maintenance;
                break;
                
            case 'support':
                $relationValid = true; // Support tickets don't need validation
                break;
        }
        
        if (!$relationValid) {
            sendResponse(400, "Invalid related_id for the specified related_type: $relatedType");
        }
    }
    
    // Create message record
    $messageData = [
        'sender_id' => $user['id'],
        'recipient_id' => $recipientId,
        'subject' => $subject,
        'message' => $message,
        'related_id' => $relatedId,
        'related_type' => $relatedType,
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $messageId = $db->insert('messages', $messageData);
    
    // Create notification for recipient
    $notificationData = [
        'user_id' => $recipientId,
        'type' => 'new_message',
        'message' => "New message from {$user['first_name']} {$user['last_name']}: $subject",
        'related_id' => $messageId,
        'related_type' => 'message',
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $db->insert('alerts', $notificationData);
    
    // Send response
    sendResponse(201, 'Message sent successfully', [
        'message_id' => $messageId,
        'sent_at' => $messageData['created_at'],
        'recipient' => [
            'id' => $recipient['id'],
            'name' => $recipient['first_name'] . ' ' . $recipient['last_name']
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Send message error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to send message: ' . $e->getMessage());
}
