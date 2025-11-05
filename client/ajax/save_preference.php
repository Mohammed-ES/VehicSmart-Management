<?php
/**
 * Ajax endpoint to save user preferences
 * 
 * @package VehicSmart
 * @version 3.0
 */

// Include database connection
require_once '../../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get parameters
$key = filter_input(INPUT_POST, 'key', FILTER_SANITIZE_STRING);
$value = filter_input(INPUT_POST, 'value', FILTER_SANITIZE_STRING);

// Validate parameters
if (empty($key) || empty($value)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

// Validate key (whitelist allowed preferences)
$allowedKeys = ['theme', 'fontSize', 'notificationsEnabled'];
if (!in_array($key, $allowedKeys)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid preference key']);
    exit;
}

// Get current preferences
$db = Database::getInstance();
$result = $db->query(
    "SELECT preferences FROM user_preferences WHERE user_id = :user_id",
    ['user_id' => $_SESSION['user_id']]
);

$preferences = [];
if ($result && count($result) > 0) {
    $preferences = json_decode($result[0]['preferences'], true) ?: [];
}

// Update preference
$preferences[$key] = $value;

// Save preferences
$db->query(
    "INSERT INTO user_preferences (user_id, preferences) VALUES (:user_id, :preferences)
     ON DUPLICATE KEY UPDATE preferences = :preferences, updated_at = NOW()",
    [
        'user_id' => $_SESSION['user_id'],
        'preferences' => json_encode($preferences)
    ]
);

// Send success response
echo json_encode(['success' => true, 'message' => 'Preference saved']);
