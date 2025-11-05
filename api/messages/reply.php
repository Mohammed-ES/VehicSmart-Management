<?php
/**
 * Reply to Message API Endpoint
 * 
 * Allows users to reply to a message
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
    $data = getInputData('POST', ['parent_message_id', 'message']);
    $parentMessageId = intval($data['parent_message_id']);
    $message = trim($data['message']);
    
    // Validate input
    if (empty($message)) {
        sendResponse(400, 'Message cannot be empty');
    }
    
    if (strlen($message) > 5000) {
        sendResponse(400, 'Message cannot exceed 5000 characters');
    }
    
    // Initialize database
    $db = new Database();
    
    // Retrieve parent message
    $parentMessage = $db->selectOne(
        "SELECT * FROM messages WHERE id = :id",
        ['id' => $parentMessageId]
    );
    
    if (!$parentMessage) {
        sendResponse(404, 'Parent message not found');
    }
    
    // Verify that the user is a participant in the conversation
    if ($parentMessage['sender_id'] != $user['id'] && $parentMessage['recipient_id'] != $user['id']) {
        sendResponse(403, 'You do not have permission to reply to this message');
    }
    
    // Determine recipient (the other party in the conversation)
    $recipientId = $parentMessage['sender_id'] == $user['id'] 
        ? $parentMessage['recipient_id'] 
        : $parentMessage['sender_id'];
    
    // Check if recipient exists and is active
    $recipient = $db->selectOne(
        "SELECT id, email, first_name, last_name, status FROM users WHERE id = :id",
        ['id' => $recipientId]
    );
    
    if (!$recipient) {
        sendResponse(404, 'Recipient no longer exists');
    }
    
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
    
    // Generate reply subject
    $subject = 'Re: ' . preg_replace('/^Re: /', '', $parentMessage['subject']);
    
    // Create message record
    $messageData = [
        'sender_id' => $user['id'],
        'recipient_id' => $recipientId,
        'subject' => $subject,
        'message' => $message,
        'parent_id' => $parentMessageId,
        'related_id' => $parentMessage['related_id'],
        'related_type' => $parentMessage['related_type'],
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $messageId = $db->insert('messages', $messageData);
    
    // Mark parent message as read if it was sent to the current user
    if ($parentMessage['recipient_id'] == $user['id'] && !$parentMessage['is_read']) {
        $db->update('messages',
            ['is_read' => 1],
            'id = :id',
            ['id' => $parentMessageId]
        );
    }
    
    // Create notification for recipient
    $notificationData = [
        'user_id' => $recipientId,
        'type' => 'new_message',
        'message' => "New reply from {$user['first_name']} {$user['last_name']}: $subject",
        'related_id' => $messageId,
        'related_type' => 'message',
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $db->insert('alerts', $notificationData);
    
    // Send response
    sendResponse(201, 'Reply sent successfully', [
        'message_id' => $messageId,
        'parent_message_id' => $parentMessageId,
        'sent_at' => $messageData['created_at'],
        'recipient' => [
            'id' => $recipient['id'],
            'name' => $recipient['first_name'] . ' ' . $recipient['last_name']
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Reply message error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to send reply: ' . $e->getMessage());
}
