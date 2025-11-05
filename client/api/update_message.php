<?php
/**
 * API Endpoint to update a message
 * 
 * @package VehicSmart
 */

// Set headers for JSON response
header('Content-Type: application/json');

// Include database connection
require_once '../../config/database.php';
require_once '../../config/helpers.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn() || $_SESSION['user']['role'] !== 'client') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Get current user
$user = getCurrentUser();
$user_id = $user['id'];

// Initialize response
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

// Process update request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get message ID and content
    $message_id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $message_content = htmlspecialchars(filter_input(INPUT_POST, 'message') ?: '');
    
    // Validate inputs
    if (empty($message_id) || empty($message_content)) {
        $response['message'] = 'Message ID and content are required.';
    } else {
        try {
            // Initialize database
            $db = Database::getInstance();
            
            // Check if this user owns the message
            $message = $db->selectOne(
                "SELECT * FROM messages WHERE id = :id AND sender_id = :user_id",
                [
                    'id' => $message_id,
                    'user_id' => $user_id
                ]
            );
            
            if (!$message) {
                $response['message'] = 'You can only edit your own messages.';
            } else {
                // Update the message
                $result = $db->query(
                    "UPDATE messages SET message = :message, updated_at = NOW() WHERE id = :id AND sender_id = :user_id",
                    [
                        'message' => $message_content,
                        'id' => $message_id,
                        'user_id' => $user_id
                    ]
                );
                
                if ($result) {
                    $response = [
                        'success' => true,
                        'message' => 'Message updated successfully'
                    ];
                } else {
                    $response['message'] = 'Failed to update message.';
                }
            }
        } catch (Exception $e) {
            $response['message'] = 'Error updating message: ' . $e->getMessage();
        }
    }
}

// Return JSON response
echo json_encode($response);
