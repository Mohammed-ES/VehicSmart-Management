<?php
/**
 * Reset Password API Endpoint
 * 
 * Updates the user's password after OTP verification
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
    // Get input data with required fields
    $data = getInputData('POST', ['email', 'reset_token', 'new_password']);
    
    // Validate password strength
    if (strlen($data['new_password']) < 8) {
        sendResponse(400, 'Password must be at least 8 characters long');
    }
    
    // Initialize database
    $db = new Database();
    
    // Find user by email
    $user = $db->selectOne("SELECT id FROM users WHERE email = :email", [
        'email' => $data['email']
    ]);
    
    // If user not found, return error
    if (!$user) {
        sendResponse(404, 'User not found');
    }
    
    // Check if reset token exists and is valid
    $resetRequest = $db->selectOne(
        "SELECT * FROM password_resets 
         WHERE user_id = :user_id 
         AND token = :token 
         AND expires_at > NOW()",
        [
            'user_id' => $user['id'],
            'token' => $data['reset_token']
        ]
    );
    
    // If token invalid or expired
    if (!$resetRequest) {
        sendResponse(400, 'Invalid or expired reset token');
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    // Hash the new password
    $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
    
    // Update the user's password
    $db->update('users',
        [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        'id = :id',
        ['id' => $user['id']]
    );
    
    // Delete all password reset requests for this user
    $db->delete('password_resets', 'user_id = :user_id', ['user_id' => $user['id']]);
    
    // Invalidate all existing sessions for security
    $db->update('user_sessions',
        [
            'expires_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'updated_at' => date('Y-m-d H:i:s')
        ],
        'user_id = :user_id',
        ['user_id' => $user['id']]
    );
    
    // Commit transaction
    $db->commit();
    
    // Return success response
    sendResponse(200, 'Password reset successful. Please log in with your new password.');
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($db) && $db->getConnection()->inTransaction()) {
        $db->rollback();
    }
    
    // Log the error
    error_log("Reset password error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Password reset failed. Please try again later.');
}
