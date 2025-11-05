<?php
/**
 * User Login API Endpoint
 * 
 * Validates user credentials and creates a session token
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
    $data = getInputData('POST', ['email', 'password']);
    
    // Initialize database
    $db = new Database();
    
    // Find user by email
    $user = $db->selectOne("SELECT * FROM users WHERE email = :email", [
        'email' => $data['email']
    ]);
    
    // Check if user exists and password is correct
    if (!$user || !password_verify($data['password'], $user['password'])) {
        sendResponse(401, 'Invalid email or password');
    }
    
    // Check if user is active
    if ($user['status'] !== 'active') {
        sendResponse(403, 'Your account is ' . $user['status'] . '. Please contact support.');
    }
    
    // Generate a new session token
    $token = generateToken(64);
    
    // Set token expiration (30 days by default)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Store token in database
    $db->insert('user_sessions', [
        'user_id' => $user['id'],
        'token' => $token,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'expires_at' => $expiresAt,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Update last login timestamp
    $db->update('users', 
        ['last_login' => date('Y-m-d H:i:s')], 
        'id = :id', 
        ['id' => $user['id']]
    );
    
    // Prepare user data to return (excluding sensitive fields)
    $userData = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'phone' => $user['phone'],
        'address' => $user['address'] ?? null,
        'email_notifications' => (bool)$user['email_notifications'],
        'sms_notifications' => (bool)$user['sms_notifications'],
        'created_at' => $user['created_at']
    ];
    
    // Return user data with token
    sendResponse(200, 'Login successful', [
        'token' => $token,
        'expires_at' => $expiresAt,
        'user' => $userData
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Login error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Login failed. Please try again later.');
}
