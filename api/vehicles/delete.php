<?php
/**
 * Delete Vehicle API Endpoint
 * 
 * Allows admin to delete a vehicle
 */

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle only DELETE or POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, 'Method Not Allowed. Please use DELETE or POST');
}

try {
    // Verify token and ensure admin role
    $user = requireRole('admin');
    
    // Get vehicle ID
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // For DELETE requests, get ID from URL parameter
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            sendResponse(400, 'Missing or invalid vehicle ID');
        }
        $vehicleId = intval($_GET['id']);
    } else {
        // For POST requests, get ID from POST data
        $data = getInputData('POST', ['id']);
        $vehicleId = intval($data['id']);
    }
    
    // Initialize database
    $db = new Database();
    
    // Check if vehicle exists
    $vehicle = $db->selectOne("SELECT * FROM vehicles WHERE id = :id", [
        'id' => $vehicleId
    ]);
    
    if (!$vehicle) {
        sendResponse(404, 'Vehicle not found');
    }
    
    // Check if vehicle is currently rented or has active bookings
    $activeRental = $db->selectOne(
        "SELECT id FROM rentals 
         WHERE vehicle_id = :vehicle_id 
         AND status IN ('active', 'pending') 
         LIMIT 1",
        ['vehicle_id' => $vehicleId]
    );
    
    if ($activeRental) {
        sendResponse(400, 'Cannot delete vehicle: it has active or pending rentals');
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Get all images for this vehicle to delete files
    $images = $db->select("SELECT image_path FROM vehicle_images WHERE vehicle_id = :vehicle_id", [
        'vehicle_id' => $vehicleId
    ]);
    
    // Delete image files
    foreach ($images as $image) {
        $imagePath = UPLOADS_DIR . '/vehicles/' . $image['image_path'];
        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }
    }
    
    // Delete related records
    $db->delete('vehicle_images', 'vehicle_id = :vehicle_id', ['vehicle_id' => $vehicleId]);
    $db->delete('alerts', 'vehicle_id = :vehicle_id', ['vehicle_id' => $vehicleId]);
    $db->delete('maintenance', 'vehicle_id = :vehicle_id', ['vehicle_id' => $vehicleId]);
    
    // Get completed rental/purchase info for archival
    $rentals = $db->select(
        "SELECT * FROM rentals 
         WHERE vehicle_id = :vehicle_id 
         AND status IN ('completed', 'cancelled')",
        ['vehicle_id' => $vehicleId]
    );
    
    $purchases = $db->select(
        "SELECT * FROM purchases 
         WHERE vehicle_id = :vehicle_id",
        ['vehicle_id' => $vehicleId]
    );
    
    // Create archive record with JSON data before deleting
    if (!empty($rentals) || !empty($purchases) || !empty($vehicle)) {
        $archiveData = [
            'vehicle_data' => json_encode($vehicle),
            'rentals_data' => !empty($rentals) ? json_encode($rentals) : null,
            'purchases_data' => !empty($purchases) ? json_encode($purchases) : null,
            'deleted_by' => $user['id'],
            'deleted_at' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('deleted_vehicles', $archiveData);
    }
    
    // Delete remaining related records (after archiving them)
    $db->delete('rentals', 'vehicle_id = :vehicle_id', ['vehicle_id' => $vehicleId]);
    $db->delete('purchases', 'vehicle_id = :vehicle_id', ['vehicle_id' => $vehicleId]);
    
    // Delete the vehicle
    $db->delete('vehicles', 'id = :id', ['id' => $vehicleId]);
    
    // Create an alert for the deleted vehicle
    $db->insert('alerts', [
        'type' => 'vehicle_deleted',
        'message' => "Vehicle deleted: {$vehicle['make']} {$vehicle['model']} ({$vehicle['license_plate']})",
        'user_id' => $user['id'],
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Commit transaction
    $db->commit();
    
    // Return success response
    sendResponse(200, 'Vehicle deleted successfully', [
        'vehicle_id' => $vehicleId,
        'vehicle_info' => [
            'make' => $vehicle['make'],
            'model' => $vehicle['model'],
            'year' => $vehicle['year'],
            'license_plate' => $vehicle['license_plate']
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($db) && $db->getConnection()->inTransaction()) {
        $db->rollback();
    }
    
    // Log the error
    error_log("Delete vehicle error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to delete vehicle: ' . $e->getMessage());
}
