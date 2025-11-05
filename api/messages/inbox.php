<?php
/**
 * Inbox API Endpoint
 * 
 * Retrieves all messages for the authenticated user with filtering and pagination
 */

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(405, 'Method Not Allowed. Please use GET');
}

try {
    // Verify authentication
    $user = requireAuth();
    
    // Initialize database
    $db = new Database();
    
    // Set parameters with defaults
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    // Filter parameters
    $folder = isset($_GET['folder']) ? $_GET['folder'] : 'inbox';  // inbox, sent, archived
    $isRead = isset($_GET['is_read']) ? (bool) $_GET['is_read'] : null;
    $relatedType = isset($_GET['related_type']) ? $_GET['related_type'] : null;
    $relatedId = isset($_GET['related_id']) ? intval($_GET['related_id']) : null;
    $contactId = isset($_GET['contact_id']) ? intval($_GET['contact_id']) : null;
    
    // Validate folder
    if (!in_array($folder, ['inbox', 'sent', 'archived', 'all'])) {
        sendResponse(400, 'Invalid folder. Must be one of: inbox, sent, archived, all');
    }
    
    // Build base query
    $query = "SELECT m.*,
              sender.id as sender_id, sender.first_name as sender_first_name, 
              sender.last_name as sender_last_name, sender.email as sender_email,
              sender.profile_image as sender_profile_image, sender.role as sender_role,
              recipient.id as recipient_id, recipient.first_name as recipient_first_name, 
              recipient.last_name as recipient_last_name, recipient.email as recipient_email,
              recipient.profile_image as recipient_profile_image, recipient.role as recipient_role
              FROM messages m
              LEFT JOIN users sender ON m.sender_id = sender.id
              LEFT JOIN users recipient ON m.recipient_id = recipient.id
              WHERE ";
    
    $params = [];
    
    // Apply folder filter
    switch ($folder) {
        case 'inbox':
            $query .= "m.recipient_id = :user_id AND m.is_archived = 0";
            $params['user_id'] = $user['id'];
            break;
        case 'sent':
            $query .= "m.sender_id = :user_id AND m.sender_archived = 0";
            $params['user_id'] = $user['id'];
            break;
        case 'archived':
            $query .= "((m.recipient_id = :user_id AND m.is_archived = 1) OR 
                        (m.sender_id = :user_id AND m.sender_archived = 1))";
            $params['user_id'] = $user['id'];
            break;
        case 'all':
            $query .= "(m.recipient_id = :user_id OR m.sender_id = :user_id)";
            $params['user_id'] = $user['id'];
            break;
    }
    
    // Apply read/unread filter
    if ($isRead !== null) {
        if ($folder === 'sent') {
            // Sent messages are always read by the sender
            if (!$isRead) {
                // If requesting unread sent messages, there won't be any
                // But we need to complete the query anyway
                $query .= " AND 0=1";
            }
        } else {
            $query .= " AND m.is_read = :is_read";
            $params['is_read'] = $isRead ? 1 : 0;
        }
    }
    
    // Apply related_type filter
    if ($relatedType) {
        $query .= " AND m.related_type = :related_type";
        $params['related_type'] = $relatedType;
    }
    
    // Apply related_id filter
    if ($relatedId) {
        $query .= " AND m.related_id = :related_id";
        $params['related_id'] = $relatedId;
    }
    
    // Apply contact filter (to get conversation with specific user)
    if ($contactId) {
        $query .= " AND ((m.sender_id = :contact_id AND m.recipient_id = :user_id2) OR
                          (m.sender_id = :user_id3 AND m.recipient_id = :contact_id2))";
        $params['contact_id'] = $contactId;
        $params['user_id2'] = $user['id'];
        $params['user_id3'] = $user['id'];
        $params['contact_id2'] = $contactId;
    }
    
    // Apply search filter
    if (isset($_GET['search']) && $_GET['search']) {
        $search = '%' . $_GET['search'] . '%';
        $query .= " AND (m.subject LIKE :search OR m.message LIKE :search)";
        $params['search'] = $search;
    }
    
    // Count total messages
    $countQuery = str_replace(
        "SELECT m.*, sender.id as sender_id", 
        "SELECT COUNT(*) as total", 
        $query
    );
    $countQuery = preg_replace('/LEFT JOIN users recipient.*?WHERE/s', 'LEFT JOIN users recipient ON m.recipient_id = recipient.id WHERE', $countQuery);
    
    $totalResult = $db->selectOne($countQuery, $params);
    $total = $totalResult['total'];
    
    // Add sorting (newest first by default)
    $query .= " ORDER BY m.created_at DESC";
    
    // Add pagination
    $query .= " LIMIT :limit OFFSET :offset";
    $params['limit'] = $limit;
    $params['offset'] = $offset;
    
    // Execute query
    $messages = $db->select($query, $params);
    
    // Process results
    $results = [];
    foreach ($messages as $message) {
        $results[] = [
            'id' => $message['id'],
            'sender' => [
                'id' => $message['sender_id'],
                'name' => $message['sender_first_name'] . ' ' . $message['sender_last_name'],
                'email' => $message['sender_email'],
                'profile_image' => $message['sender_profile_image'],
                'role' => $message['sender_role']
            ],
            'recipient' => [
                'id' => $message['recipient_id'],
                'name' => $message['recipient_first_name'] . ' ' . $message['recipient_last_name'],
                'email' => $message['recipient_email'],
                'profile_image' => $message['recipient_profile_image'],
                'role' => $message['recipient_role']
            ],
            'subject' => $message['subject'],
            'message' => $message['message'],
            'is_read' => (bool) $message['is_read'],
            'is_archived' => $message['recipient_id'] == $user['id'] ? (bool) $message['is_archived'] : (bool) $message['sender_archived'],
            'created_at' => $message['created_at'],
            'related_id' => $message['related_id'],
            'related_type' => $message['related_type'],
            'parent_id' => $message['parent_id']
        ];
    }
    
    // Get unread count for inbox badge
    $unreadCount = $db->selectOne(
        "SELECT COUNT(*) as count FROM messages WHERE recipient_id = :user_id AND is_read = 0 AND is_archived = 0",
        ['user_id' => $user['id']]
    );
    
    // Calculate pagination info
    $totalPages = ceil($total / $limit);
    $hasNextPage = $page < $totalPages;
    $hasPrevPage = $page > 1;
    
    // Send response
    sendResponse(200, 'Messages retrieved successfully', [
        'data' => $results,
        'folder' => $folder,
        'unread_count' => $unreadCount['count'],
        'pagination' => [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_next_page' => $hasNextPage,
            'has_prev_page' => $hasPrevPage
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Inbox error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to retrieve messages: ' . $e->getMessage());
}
