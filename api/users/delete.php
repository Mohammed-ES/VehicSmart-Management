<?php
/**
 * Delete User API Endpoint
 * 
 * Allows admins to delete users or users to delete their own accounts
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
    
    // Security check: users can only delete their own accounts unless they are admins
    if ($userId != $currentUser['id'] && $currentUser['role'] !== 'admin') {
        sendResponse(403, 'You do not have permission to delete this user');
    }
    
    // Prevent admins from deleting themselves
    if ($userId == $currentUser['id'] && $currentUser['role'] === 'admin') {
        sendResponse(400, 'Admin users cannot delete their own accounts through the API');
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
    
    // Check for active rentals
    $activeRentals = $db->selectOne(
        "SELECT COUNT(*) as count FROM rentals 
         WHERE user_id = :user_id AND status IN ('active', 'pending')",
        ['user_id' => $userId]
    );
    
    if ($activeRentals['count'] > 0 && !isset($data['force']) && $currentUser['role'] !== 'admin') {
        sendResponse(400, 'Cannot delete user with active rentals', [
            'active_rentals_count' => $activeRentals['count']
        ]);
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Check if this is a soft delete or hard delete
    $hardDelete = isset($data['hard_delete']) && $data['hard_delete'] && $currentUser['role'] === 'admin';
    
    if ($hardDelete) {
        // Hard delete - only for admins with explicit permission
        // This is dangerous and should be used very carefully
        
        // Update rentals to remove user association
        $db->update('rentals',
            [
                'user_id' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'user_id = :user_id',
            ['user_id' => $userId]
        );
        
        // Update transactions to remove user association
        $db->update('transactions',
            [
                'user_id' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'user_id = :user_id',
            ['user_id' => $userId]
        );
        
        // Delete user alerts
        $db->delete('alerts', 'user_id = :user_id', ['user_id' => $userId]);
        
        // Delete user messages
        $db->delete('messages', 'sender_id = :user_id OR recipient_id = :user_id', ['user_id' => $userId]);
        
        // Delete the user
        $db->delete('users', 'id = :id', ['id' => $userId]);
        
        $message = 'User has been permanently deleted';
    } else {
        // Soft delete - standard approach
        // Get verification code from request or generate one
        $verificationCode = isset($data['verification_code']) ? $data['verification_code'] : null;
        
        // For self-deletion, require verification code
        if ($userId == $currentUser['id'] && !isset($data['verification_code'])) {
            // Generate and store verification code
            $verificationCode = generateRandomString(8);
            
            // Store verification code with expiration
            $db->update('users',
                [
                    'verification_code' => $verificationCode,
                    'verification_code_expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                ['id' => $userId]
            );
            
            // Send verification code (in a real app, this would email the code)
            // For demo, we'll just return it in the response
            $db->commit();
            sendResponse(202, 'Verification code generated for account deletion', [
                'requires_verification' => true,
                'verification_code' => $verificationCode,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ]);
            exit;
        } elseif ($userId == $currentUser['id']) {
            // Verify the code
            $userVerify = $db->selectOne(
                "SELECT verification_code, verification_code_expires_at 
                 FROM users 
                 WHERE id = :id AND verification_code = :code",
                [
                    'id' => $userId,
                    'code' => $verificationCode
                ]
            );
            
            if (!$userVerify || strtotime($userVerify['verification_code_expires_at']) < time()) {
                sendResponse(400, 'Invalid or expired verification code');
            }
        }
        
        // Anonymize user data
        $anonymizedEmail = 'deleted_' . $userId . '_' . time() . '@example.com';
        
        $db->update('users',
            [
                'email' => $anonymizedEmail,
                'password_hash' => null,
                'first_name' => 'Deleted',
                'last_name' => 'User',
                'phone' => null,
                'profile_image' => null,
                'status' => 'deleted',
                'address' => null,
                'city' => null,
                'state' => null,
                'zip_code' => null,
                'country' => null,
                'bio' => null,
                'company_name' => null,
                'company_position' => null,
                'deleted_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = :id',
            ['id' => $userId]
        );
        
        $message = 'User has been deactivated and data anonymized';
    }
    
    // Log the deletion
    $db->insert('activity_logs', [
        'user_id' => $currentUser['id'],
        'action' => $hardDelete ? 'hard_delete_user' : 'soft_delete_user',
        'description' => ($hardDelete ? 'Hard delete' : 'Soft delete') . ' of user #' . $userId . ' by user #' . $currentUser['id'],
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Commit transaction
    $db->commit();
    
    // Send success response
    sendResponse(200, $message, [
        'user_id' => $userId,
        'hard_delete' => $hardDelete
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($db) && $db->getConnection()->inTransaction()) {
        $db->rollback();
    }
    
    // Log the error
    error_log("Delete user error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to delete user: ' . $e->getMessage());
}

/**
 * Generate a random string of specified length
 * 
 * @param int $length Length of the random string
 * @return string Random string
 */
function generateRandomString($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
