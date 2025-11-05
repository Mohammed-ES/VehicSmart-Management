<?php
/**
 * Update Vehicle API Endpoint
 * 
 * Allows admin to update vehicle information
 */

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/cors.php';

// Handle only PUT or POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, 'Method Not Allowed. Please use PUT or POST');
}

try {
    // Verify token and ensure admin role
    $user = requireRole('admin');
    
    // Get input data
    $data = getInputData($_SERVER['REQUEST_METHOD'], ['id']);
    
    // Initialize database
    $db = new Database();
    
    // Check if vehicle exists
    $vehicle = $db->selectOne("SELECT * FROM vehicles WHERE id = :id", [
        'id' => $data['id']
    ]);
    
    if (!$vehicle) {
        sendResponse(404, 'Vehicle not found');
    }
    
    // Fields that can be updated
    $updatableFields = [
        'name', 'make', 'model', 'year', 'type', 'price', 
        'rental_rate_daily', 'fuel_type', 'transmission', 
        'license_plate', 'color', 'mileage', 'seats', 'doors', 
        'engine', 'vin', 'description', 'features', 'notes',
        'status', 'is_available'
    ];
    
    // Build update data array
    $updateData = [];
    
    foreach ($updatableFields as $field) {
        if (isset($data[$field])) {
            // Validation for specific fields
            if ($field === 'year' && (!is_numeric($data['year']) || $data['year'] < 1900 || $data['year'] > date('Y') + 1)) {
                sendResponse(400, 'Invalid year value');
            }
            
            if (($field === 'price' || $field === 'rental_rate_daily') && (!is_numeric($data[$field]) || $data[$field] < 0)) {
                sendResponse(400, 'Invalid ' . $field . ' value');
            }
            
            if ($field === 'is_available') {
                $updateData[$field] = $data[$field] ? 1 : 0;
            } else {
                $updateData[$field] = $data[$field];
            }
        }
    }
    
    // Check if license plate is being changed and if it already exists
    if (isset($updateData['license_plate']) && $updateData['license_plate'] !== $vehicle['license_plate']) {
        $existingVehicle = $db->selectOne("SELECT id FROM vehicles WHERE license_plate = :license_plate AND id != :id", [
            'license_plate' => $updateData['license_plate'],
            'id' => $data['id']
        ]);
        
        if ($existingVehicle) {
            sendResponse(409, 'A vehicle with this license plate already exists');
        }
    }
    
    // Add updated_at timestamp
    $updateData['updated_at'] = date('Y-m-d H:i:s');
    
    // Start transaction
    $db->beginTransaction();
    
    // Update vehicle data
    if (!empty($updateData)) {
        $db->update('vehicles', $updateData, 'id = :id', ['id' => $data['id']]);
    }
    
    // Handle primary image update
    if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
        // Validate file
        $validationResult = validateFileUpload(
            $_FILES['primary_image'], 
            ['image/jpeg', 'image/png', 'image/webp'], 
            5242880 // 5MB
        );
        
        if ($validationResult !== true) {
            throw new Exception($validationResult);
        }
        
        // Generate unique filename
        $extension = pathinfo($_FILES['primary_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('vehicle_') . '.' . $extension;
        $uploadPath = UPLOADS_DIR . '/vehicles/' . $filename;
        
        // Ensure uploads directory exists
        if (!is_dir(UPLOADS_DIR . '/vehicles/')) {
            mkdir(UPLOADS_DIR . '/vehicles/', 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['primary_image']['tmp_name'], $uploadPath)) {
            // Update all existing primary images to non-primary
            $db->update(
                'vehicle_images', 
                ['is_primary' => 0], 
                'vehicle_id = :vehicle_id', 
                ['vehicle_id' => $data['id']]
            );
            
            // Insert new primary image
            $db->insert('vehicle_images', [
                'vehicle_id' => $data['id'],
                'image_path' => $filename,
                'is_primary' => 1,
                'sort_order' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            throw new Exception('Failed to upload image');
        }
    }
    
    // Handle image deletions if specified
    if (isset($data['delete_images']) && is_array($data['delete_images'])) {
        foreach ($data['delete_images'] as $imageId) {
            // Get image info first to potentially delete file
            $image = $db->selectOne("SELECT * FROM vehicle_images WHERE id = :id AND vehicle_id = :vehicle_id", [
                'id' => $imageId,
                'vehicle_id' => $data['id']
            ]);
            
            if ($image) {
                // Delete image record
                $db->delete('vehicle_images', 'id = :id', ['id' => $imageId]);
                
                // Delete actual file if exists
                $imagePath = UPLOADS_DIR . '/vehicles/' . $image['image_path'];
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
        }
        
        // If we deleted the primary image, make another image primary
        $primaryCheck = $db->selectOne("SELECT COUNT(*) as count FROM vehicle_images WHERE vehicle_id = :id AND is_primary = 1", [
            'id' => $data['id']
        ]);
        
        if ($primaryCheck['count'] == 0) {
            // Find the first available image and make it primary
            $firstImage = $db->selectOne("SELECT id FROM vehicle_images WHERE vehicle_id = :id ORDER BY sort_order ASC LIMIT 1", [
                'id' => $data['id']
            ]);
            
            if ($firstImage) {
                $db->update(
                    'vehicle_images', 
                    ['is_primary' => 1], 
                    'id = :id', 
                    ['id' => $firstImage['id']]
                );
            }
        }
    }
    
    // Process additional images upload
    if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
        // Get current max sort order
        $maxSortResult = $db->selectOne("SELECT MAX(sort_order) as max_sort FROM vehicle_images WHERE vehicle_id = :id", [
            'id' => $data['id']
        ]);
        
        $sortOrder = ($maxSortResult && $maxSortResult['max_sort']) ? $maxSortResult['max_sort'] : 0;
        
        for ($i = 0; $i < count($_FILES['additional_images']['name']); $i++) {
            if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['additional_images']['name'][$i],
                    'type' => $_FILES['additional_images']['type'][$i],
                    'tmp_name' => $_FILES['additional_images']['tmp_name'][$i],
                    'error' => $_FILES['additional_images']['error'][$i],
                    'size' => $_FILES['additional_images']['size'][$i]
                ];
                
                // Validate file
                $validationResult = validateFileUpload(
                    $file, 
                    ['image/jpeg', 'image/png', 'image/webp'], 
                    5242880 // 5MB
                );
                
                if ($validationResult !== true) {
                    continue; // Skip invalid files
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('vehicle_') . '.' . $extension;
                $uploadPath = UPLOADS_DIR . '/vehicles/' . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $sortOrder++;
                    
                    $db->insert('vehicle_images', [
                        'vehicle_id' => $data['id'],
                        'image_path' => $filename,
                        'is_primary' => 0, // Not primary
                        'sort_order' => $sortOrder,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
    }
    
    // Create an alert for the vehicle update
    $db->insert('alerts', [
        'type' => 'vehicle_updated',
        'message' => "Vehicle updated: {$vehicle['make']} {$vehicle['model']} ({$vehicle['license_plate']})",
        'vehicle_id' => $data['id'],
        'user_id' => $user['id'],
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Commit transaction
    $db->commit();
    
    // Get updated vehicle data
    $updatedVehicle = $db->selectOne(
        "SELECT v.*, 
        (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as primary_image,
        (SELECT COUNT(*) FROM vehicle_images WHERE vehicle_id = v.id) as image_count
        FROM vehicles v
        WHERE v.id = :id",
        ['id' => $data['id']]
    );
    
    // Format vehicle data
    $updatedVehicle['price'] = floatval($updatedVehicle['price']);
    $updatedVehicle['rental_rate_daily'] = floatval($updatedVehicle['rental_rate_daily']);
    $updatedVehicle['is_available'] = (bool)$updatedVehicle['is_available'];
    $updatedVehicle['image_count'] = intval($updatedVehicle['image_count']);
    
    if (!empty($updatedVehicle['primary_image'])) {
        $updatedVehicle['primary_image_url'] = 'https://vehicsmart.com/uploads/vehicles/' . $updatedVehicle['primary_image'];
    } else {
        $updatedVehicle['primary_image_url'] = null;
    }
    
    // Return success response
    sendResponse(200, 'Vehicle updated successfully', [
        'vehicle' => $updatedVehicle
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($db) && $db->getConnection()->inTransaction()) {
        $db->rollback();
    }
    
    // Log the error
    error_log("Update vehicle error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to update vehicle: ' . $e->getMessage());
}
