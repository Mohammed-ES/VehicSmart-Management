<?php
/**
 * Global Chatbot Include Script
 * 
 * This script should be included at the end of all pages in the client section
 * to ensure consistent chatbot functionality across all pages.
 * 
 * @package VehicSmart
 * @version 1.0
 */

// If chatbot is not already included and user is logged in as client
if (!defined('CHATBOT_INCLUDED') && 
    isset($_SESSION['user_id']) && 
    isset($_SESSION['role']) && 
    $_SESSION['role'] === 'client') {
    
    // Set constant to prevent multiple inclusions
    define('CHATBOT_INCLUDED', true);
    
    // Define constant for security check if not already defined
    if (!defined('VEHICSMART_SECURED')) {
        define('VEHICSMART_SECURED', true);
    }
    
    // Set base URL for assets if not already set
    if (!isset($baseUrl)) {
        $baseUrl = '../';
    }
    
    // Include the chatbot component
    include_once __DIR__ . '/chatbot-include.php';
}
