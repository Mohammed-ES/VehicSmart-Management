<?php
/**
 * Logout API Endpoint
 * 
 * Invalidates the current session token
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
    // Verify token and get user data
    $user = verifyToken();
    
    if (!$user) {
        sendResponse(401, 'Unauthorized. Please log in.');
    }
    
    // Initialize database
    $db = new Database();
    
    // Get token from authorization header
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        sendResponse(400, 'Invalid request. No token provided.');
    }
    
    // Invalidate the current token
    $db->update('user_sessions',
        [
            'expires_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        'user_id = :user_id AND token = :token',
        [
            'user_id' => $user['id'],
            'token' => $token
        ]
    );
    
    // Return success response
    sendResponse(200, 'Logout successful');
    
} catch (Exception $e) {
    // Log the error
    error_log("Logout error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Logout failed. Please try again later.');
}
