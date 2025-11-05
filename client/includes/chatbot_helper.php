<?php
/**
 * Helper script to include chatbot on all client pages
 *
 * Include this file once in each client folder to ensure the chatbot is present on all pages
 */

// Define constant for security check
if (!defined('VEHICSMART_SECURED')) {
    define('VEHICSMART_SECURED', true);
}

// Add chatbot to all client pages - update in footer.php
function add_chatbot() {
    global $baseUrl;
    
    // Default baseUrl if not set
    if (!isset($baseUrl)) {
        $baseUrl = '../';
    }
    
    // Only include for authenticated clients
    if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'client') {
        include_once __DIR__ . '/includes/chatbot-include.php';
    }
}

// Register function to be called at the end of each page
register_shutdown_function('add_chatbot');
?>
