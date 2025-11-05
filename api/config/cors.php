<?php
/**
 * CORS Middleware
 * 
 * Handles Cross-Origin Resource Sharing for the VehicSmart API
 */

// Set allowed origins
$allowedOrigins = [
    'http://localhost',
    'http://localhost:8080',
    'http://localhost:3000',
    'https://vehicsmart.com',
    'https://www.vehicsmart.com',
    'https://admin.vehicsmart.com',
    // Add additional origins as needed
];

// Get the origin from the request
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Check if the origin is allowed
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: {$origin}");
} else {
    // For development, allow any origin (remove in production)
    header("Access-Control-Allow-Origin: *");
}

// Allow credentials
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Allow these HTTP methods
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    
    // Allow these headers
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    
    // Cache preflight response for 1 hour
    header("Access-Control-Max-Age: 3600");
    
    // Send 204 No Content response
    http_response_code(204);
    exit;
}

// Set content type for API responses
header("Content-Type: application/json; charset=UTF-8");
