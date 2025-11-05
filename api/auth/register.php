<?php
/**
 * User Registration API Endpoint
 * 
 * Creates a new client account with validation
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
    $data = getInputData('POST', ['name', 'email', 'password', 'phone']);
    
    // Additional validation
    if (!isValidEmail($data['email'])) {
        sendResponse(400, 'Invalid email address');
    }
    
    if (strlen($data['password']) < 8) {
        sendResponse(400, 'Password must be at least 8 characters long');
    }
    
    // Initialize database
    $db = new Database();
    
    // Check if email already exists
    $existingUser = $db->selectOne("SELECT id FROM users WHERE email = :email", [
        'email' => $data['email']
    ]);
    
    if ($existingUser) {
        sendResponse(409, 'Email already registered');
    }
    
    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Prepare user data for insertion
    $userData = [
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => $hashedPassword,
        'phone' => $data['phone'],
        'role' => 'client', // Default role for registrations
        'status' => 'active',
        'email_notifications' => 1, // Enable by default
        'sms_notifications' => 0, // Disable by default
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Add address if provided
    if (isset($data['address'])) {
        $userData['address'] = $data['address'];
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Insert user
    $userId = $db->insert('users', $userData);
    
    // Generate verification token
    $token = generateToken(32);
    
    // Store token in database for email verification
    $db->insert('user_verifications', [
        'user_id' => $userId,
        'token' => $token,
        'type' => 'email',
        'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')),
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Commit transaction
    $db->commit();
    
    // Generate verification URL (for email)
    $verificationUrl = 'https://vehicsmart.com/verify-email?token=' . $token;
    
    // In a real application, send email with verification link
    // Email sending logic would go here
    
    // Return success response
    sendResponse(201, 'Registration successful. Please check your email for verification instructions.', [
        'user_id' => $userId,
        'verification_url' => $verificationUrl // Remove in production
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($db) && $db->getConnection()->inTransaction()) {
        $db->rollback();
    }
    
    // Log the error
    error_log("Registration error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Registration failed. Please try again later.');
}
