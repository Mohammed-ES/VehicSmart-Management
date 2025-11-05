<?php
/**
 * Forgot Password API Endpoint
 * 
 * Generates and stores a 6-digit OTP for password reset
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
    $data = getInputData('POST', ['email']);
    
    // Validate email
    if (!isValidEmail($data['email'])) {
        sendResponse(400, 'Invalid email address');
    }
    
    // Initialize database
    $db = new Database();
    
    // Find user by email
    $user = $db->selectOne("SELECT id, name, email, status FROM users WHERE email = :email", [
        'email' => $data['email']
    ]);
    
    // If user not found, still return success for security
    // This prevents email enumeration attacks
    if (!$user) {
        sendResponse(200, 'If your email is registered, you will receive password reset instructions shortly.');
    }
    
    // Check if user is active
    if ($user['status'] !== 'active') {
        sendResponse(200, 'If your email is registered, you will receive password reset instructions shortly.');
    }
    
    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));
    
    // Set OTP expiration (15 minutes)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Delete any existing OTPs for this user
    $db->delete('password_resets', 'user_id = :user_id', ['user_id' => $user['id']]);
    
    // Store new OTP in database
    $db->insert('password_resets', [
        'user_id' => $user['id'],
        'token' => $otp,
        'expires_at' => $expiresAt,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // In a real application, send email with OTP
    // Email sending logic would go here
    
    // Return success response
    sendResponse(200, 'If your email is registered, you will receive password reset instructions shortly.', [
        'otp' => $otp, // Remove in production
        'expires_at' => $expiresAt
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Forgot password error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Request failed. Please try again later.');
}
