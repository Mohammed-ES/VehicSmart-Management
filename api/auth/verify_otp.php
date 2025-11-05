<?php
/**
 * Verify OTP API Endpoint
 * 
 * Verifies the OTP sent for password reset
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
    $data = getInputData('POST', ['email', 'otp']);
    
    // Validate OTP format
    if (!preg_match('/^\d{6}$/', $data['otp'])) {
        sendResponse(400, 'Invalid OTP format. Must be 6 digits.');
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
    
    // Check if OTP exists and is valid
    $resetRequest = $db->selectOne(
        "SELECT * FROM password_resets 
         WHERE user_id = :user_id 
         AND token = :token 
         AND expires_at > NOW()",
        [
            'user_id' => $user['id'],
            'token' => $data['otp']
        ]
    );
    
    // If OTP invalid or expired
    if (!$resetRequest) {
        sendResponse(400, 'Invalid or expired OTP');
    }
    
    // Generate a reset token for the next step
    $resetToken = generateToken(32);
    
    // Update the reset request with the new token
    $db->update('password_resets',
        [
            'token' => $resetToken,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+15 minutes')) // Extend expiration
        ],
        'user_id = :user_id',
        ['user_id' => $user['id']]
    );
    
    // Return success with reset token
    sendResponse(200, 'OTP verified successfully', [
        'reset_token' => $resetToken
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Verify OTP error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Verification failed. Please try again later.');
}
