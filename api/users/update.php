<?php
/**
 * Update User API Endpoint
 * 
 * Allows users to update their profile information or admins to update any user
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
    $currentUser = requireAuth();
    
    // Get input data
    $data = getInputData('POST', ['user_id']);
    $userId = intval($data['user_id']);
    
    // Security check: users can only update their own profiles unless they are admins
    if ($userId != $currentUser['id'] && $currentUser['role'] !== 'admin') {
        sendResponse(403, 'You do not have permission to update this user');
    }
    
    // Initialize database
    $db = new Database();
    
    // Check if user exists
    $user = $db->selectOne(
        "SELECT id, email, role FROM users WHERE id = :id",
        ['id' => $userId]
    );
    
    if (!$user) {
        sendResponse(404, 'User not found');
    }
    
    // Start building the update fields and values
    $updateFields = [];
    $updateValues = [];
    $fieldsUpdated = [];
    
    // Define allowed fields for regular users and admins
    $allowedUserFields = [
        'first_name', 'last_name', 'phone', 'profile_image',
        'address', 'city', 'state', 'zip_code', 'country',
        'bio', 'company_name', 'company_position'
    ];
    
    $allowedAdminFields = [
        'email', 'first_name', 'last_name', 'phone', 'profile_image',
        'address', 'city', 'state', 'zip_code', 'country',
        'bio', 'company_name', 'company_position', 'role', 'status',
        'email_verified'
    ];
    
    $allowedFields = $currentUser['role'] === 'admin' ? $allowedAdminFields : $allowedUserFields;
    
    // Process each allowed field
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            // Special validation for specific fields
            switch ($field) {
                case 'email':
                    // Validate email format
                    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        sendResponse(400, 'Invalid email format');
                    }
                    
                    // Check if email is already taken by another user
                    $existingUser = $db->selectOne(
                        "SELECT id FROM users WHERE email = :email AND id != :id",
                        ['email' => $data['email'], 'id' => $userId]
                    );
                    
                    if ($existingUser) {
                        sendResponse(409, 'Email is already in use by another account');
                    }
                    
                    // If email is changed, reset verification status
                    if ($data['email'] !== $user['email']) {
                        $updateFields[] = 'email_verified = 0';
                        $fieldsUpdated[] = 'email_verified';
                    }
                    break;
                    
                case 'role':
                    // Validate role value
                    $validRoles = ['admin', 'client', 'staff'];
                    if (!in_array($data['role'], $validRoles)) {
                        sendResponse(400, 'Invalid role. Must be one of: ' . implode(', ', $validRoles));
                    }
                    
                    // Prevent changing own role for admins
                    if ($userId == $currentUser['id'] && $data['role'] !== $user['role']) {
                        sendResponse(400, 'Admins cannot change their own role');
                    }
                    break;
                    
                case 'status':
                    // Validate status value
                    $validStatuses = ['active', 'inactive', 'suspended', 'pending'];
                    if (!in_array($data['status'], $validStatuses)) {
                        sendResponse(400, 'Invalid status. Must be one of: ' . implode(', ', $validStatuses));
                    }
                    break;
                    
                case 'email_verified':
                    // Only accept boolean values
                    $data[$field] = $data[$field] ? 1 : 0;
                    break;
                    
                case 'phone':
                    // Basic phone validation
                    if (!empty($data['phone']) && !preg_match('/^[+]?[0-9() -]{8,20}$/', $data['phone'])) {
                        sendResponse(400, 'Invalid phone number format');
                    }
                    break;
                    
                case 'zip_code':
                    // Basic ZIP/postal code validation
                    if (!empty($data['zip_code']) && !preg_match('/^[a-zA-Z0-9 -]{3,10}$/', $data['zip_code'])) {
                        sendResponse(400, 'Invalid ZIP/postal code format');
                    }
                    break;
            }
            
            // Add field to update
            $updateFields[] = "$field = :$field";
            $updateValues[$field] = $data[$field];
            $fieldsUpdated[] = $field;
        }
    }
    
    // Handle password update if provided
    if (isset($data['password']) && !empty($data['password'])) {
        // Validate password strength
        if (strlen($data['password']) < 8) {
            sendResponse(400, 'Password must be at least 8 characters long');
        }
        
        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $updateFields[] = "password_hash = :password_hash";
        $updateValues['password_hash'] = $passwordHash;
        $fieldsUpdated[] = 'password';
    }
    
    // If no fields were updated, return early
    if (empty($updateFields)) {
        sendResponse(400, 'No valid fields provided for update');
    }
    
    // Always update the updated_at timestamp
    $updateFields[] = "updated_at = :updated_at";
    $updateValues['updated_at'] = date('Y-m-d H:i:s');
    
    // Add user ID to update values
    $updateValues['id'] = $userId;
    
    // Build and execute update query
    $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $db->getConnection()->prepare($query)->execute($updateValues);
    
    // Handle profile image upload if file is provided
    $profileImageUrl = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        // Process file upload
        $uploadDir = '../../uploads/profile_images/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $fileName = $userId . '_' . time() . '_' . basename($_FILES['profile_image']['name']);
        $targetFile = $uploadDir . $fileName;
        
        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['profile_image']['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            sendResponse(400, 'Invalid file type. Only JPG, PNG and GIF are allowed');
        }
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
            // Update profile image URL in database
            $profileImageUrl = '/uploads/profile_images/' . $fileName;
            $db->update('users', 
                ['profile_image' => $profileImageUrl], 
                'id = :id', 
                ['id' => $userId]
            );
            
            $fieldsUpdated[] = 'profile_image';
        } else {
            error_log("Failed to move uploaded profile image for user $userId");
        }
    }
    
    // Log the update
    $db->insert('activity_logs', [
        'user_id' => $currentUser['id'],
        'action' => 'update_user',
        'description' => 'Update of user #' . $userId . ' by user #' . $currentUser['id'] . '. Fields: ' . implode(', ', $fieldsUpdated),
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Send success response
    sendResponse(200, 'User information updated successfully', [
        'user_id' => $userId,
        'updated_fields' => $fieldsUpdated,
        'profile_image_url' => $profileImageUrl
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Update user error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to update user: ' . $e->getMessage());
}
