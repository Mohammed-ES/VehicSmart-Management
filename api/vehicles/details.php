<?php
/**
 * Vehicle Details API Endpoint
 * 
 * Returns detailed information about a specific vehicle
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
    // Get vehicle ID from URL parameter
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        sendResponse(400, 'Missing or invalid vehicle ID');
    }
    
    $vehicleId = intval($_GET['id']);
    
    // Initialize database
    $db = new Database();
    
    // Get vehicle details
    $vehicle = $db->selectOne(
        "SELECT v.* FROM vehicles v WHERE v.id = :id",
        ['id' => $vehicleId]
    );
    
    if (!$vehicle) {
        sendResponse(404, 'Vehicle not found');
    }
    
    // Get vehicle images
    $images = $db->select(
        "SELECT id, image_path, is_primary, sort_order 
         FROM vehicle_images 
         WHERE vehicle_id = :vehicle_id 
         ORDER BY is_primary DESC, sort_order ASC",
        ['vehicle_id' => $vehicleId]
    );
    
    // Process images to add full URLs
    foreach ($images as &$image) {
        $image['is_primary'] = (bool)$image['is_primary'];
        $image['image_url'] = 'https://vehicsmart.com/uploads/vehicles/' . $image['image_path'];
    }
    
    // Format vehicle data
    $vehicle['price'] = floatval($vehicle['price']);
    $vehicle['rental_rate_daily'] = floatval($vehicle['rental_rate_daily']);
    $vehicle['is_available'] = (bool)$vehicle['is_available'];
    $vehicle['year'] = intval($vehicle['year']);
    
    if (isset($vehicle['mileage'])) {
        $vehicle['mileage'] = intval($vehicle['mileage']);
    }
    
    if (isset($vehicle['seats'])) {
        $vehicle['seats'] = intval($vehicle['seats']);
    }
    
    if (isset($vehicle['doors'])) {
        $vehicle['doors'] = intval($vehicle['doors']);
    }
    
    // Add images to vehicle data
    $vehicle['images'] = $images;
    
    // Find primary image
    $primaryImage = null;
    foreach ($images as $image) {
        if ($image['is_primary']) {
            $primaryImage = $image['image_path'];
            break;
        }
    }
    
    $vehicle['primary_image'] = $primaryImage;
    
    if (!empty($primaryImage)) {
        $vehicle['primary_image_url'] = 'https://vehicsmart.com/uploads/vehicles/' . $primaryImage;
    } else {
        $vehicle['primary_image_url'] = null;
    }
    
    // Get maintenance history if requested
    if (isset($_GET['include_maintenance']) && $_GET['include_maintenance'] == '1') {
        // Check if user is authenticated and has permission
        $user = verifyToken();
        
        if ($user && ($user['role'] === 'admin' || $user['role'] === 'mechanic')) {
            $maintenance = $db->select(
                "SELECT * FROM maintenance 
                 WHERE vehicle_id = :vehicle_id 
                 ORDER BY maintenance_date DESC",
                ['vehicle_id' => $vehicleId]
            );
            
            $vehicle['maintenance_history'] = $maintenance;
        }
    }
    
    // Get rental availability if requested
    if (isset($_GET['check_availability']) && $_GET['check_availability'] == '1') {
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+7 days'));
        
        // Check for overlapping rentals
        $availability = $db->selectOne(
            "SELECT COUNT(*) as conflict_count 
             FROM rentals 
             WHERE vehicle_id = :vehicle_id 
             AND status IN ('active', 'pending') 
             AND (
                 (start_date <= :end_date AND end_date >= :start_date)
             )",
            [
                'vehicle_id' => $vehicleId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        );
        
        $vehicle['is_available_for_rental'] = $vehicle['is_available'] && $availability['conflict_count'] == 0;
        $vehicle['rental_conflicts'] = intval($availability['conflict_count']);
    }
    
    // Return vehicle details
    sendResponse(200, 'Vehicle details retrieved successfully', [
        'vehicle' => $vehicle
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Vehicle details error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to retrieve vehicle details. Please try again later.');
}
