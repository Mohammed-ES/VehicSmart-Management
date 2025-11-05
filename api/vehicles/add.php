<?php
/**
 * Add Vehicle API Endpoint
 * 
 * Allows admin to add a new vehicle
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
    // Verify token and ensure admin role
    $user = requireRole('admin');
    
    // Get input data with required fields
    $data = getInputData('POST', [
        'name', 
        'make', 
        'model', 
        'year', 
        'type', 
        'price',
        'rental_rate_daily',
        'fuel_type',
        'transmission',
        'license_plate'
    ]);
    
    // Additional validation
    if (!is_numeric($data['year']) || $data['year'] < 1900 || $data['year'] > date('Y') + 1) {
        sendResponse(400, 'Invalid year value');
    }
    
    if (!is_numeric($data['price']) || $data['price'] < 0) {
        sendResponse(400, 'Invalid price value');
    }
    
    if (!is_numeric($data['rental_rate_daily']) || $data['rental_rate_daily'] < 0) {
        sendResponse(400, 'Invalid rental rate value');
    }
    
    // Initialize database
    $db = new Database();
    
    // Check if license plate already exists
    $existingVehicle = $db->selectOne("SELECT id FROM vehicles WHERE license_plate = :license_plate", [
        'license_plate' => $data['license_plate']
    ]);
    
    if ($existingVehicle) {
        sendResponse(409, 'A vehicle with this license plate already exists');
    }
    
    // Prepare vehicle data
    $vehicleData = [
        'name' => $data['name'],
        'make' => $data['make'],
        'model' => $data['model'],
        'year' => intval($data['year']),
        'type' => $data['type'],
        'price' => floatval($data['price']),
        'rental_rate_daily' => floatval($data['rental_rate_daily']),
        'fuel_type' => $data['fuel_type'],
        'transmission' => $data['transmission'],
        'license_plate' => $data['license_plate'],
        'status' => 'available',
        'is_available' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Add optional fields if present
    $optionalFields = [
        'color', 'mileage', 'seats', 'doors', 'engine', 
        'vin', 'description', 'features', 'notes'
    ];
    
    foreach ($optionalFields as $field) {
        if (isset($data[$field])) {
            $vehicleData[$field] = $data[$field];
        }
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Insert vehicle
    $vehicleId = $db->insert('vehicles', $vehicleData);
    
    // Process images if any
    $imageCount = 0;
    $primaryImage = null;
    
    // Check if images array is provided in JSON
    if (isset($data['images']) && is_array($data['images'])) {
        foreach ($data['images'] as $index => $imageData) {
            if (isset($imageData['image_path'])) {
                $isPrimary = isset($imageData['is_primary']) ? (bool)$imageData['is_primary'] : ($index === 0);
                
                $imageInsertData = [
                    'vehicle_id' => $vehicleId,
                    'image_path' => $imageData['image_path'],
                    'is_primary' => $isPrimary ? 1 : 0,
                    'sort_order' => $index + 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $db->insert('vehicle_images', $imageInsertData);
                $imageCount++;
                
                if ($isPrimary) {
                    $primaryImage = $imageData['image_path'];
                }
            }
        }
    }
    
    // Handle single primary image upload (common for direct uploads)
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
            $imageInsertData = [
                'vehicle_id' => $vehicleId,
                'image_path' => $filename,
                'is_primary' => 1,
                'sort_order' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $db->insert('vehicle_images', $imageInsertData);
            $imageCount++;
            $primaryImage = $filename;
        } else {
            throw new Exception('Failed to upload image');
        }
    }
    
    // Process additional images upload
    if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
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
                    $imageInsertData = [
                        'vehicle_id' => $vehicleId,
                        'image_path' => $filename,
                        'is_primary' => 0, // Not primary
                        'sort_order' => $imageCount + 1,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $db->insert('vehicle_images', $imageInsertData);
                    $imageCount++;
                }
            }
        }
    }
    
    // Create an alert for the new vehicle
    $db->insert('alerts', [
        'type' => 'vehicle_added',
        'message' => "New vehicle added: {$data['make']} {$data['model']} ({$data['license_plate']})",
        'vehicle_id' => $vehicleId,
        'user_id' => $user['id'],
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Commit transaction
    $db->commit();
    
    // Return success response
    sendResponse(201, 'Vehicle added successfully', [
        'vehicle_id' => $vehicleId,
        'images_count' => $imageCount,
        'primary_image' => $primaryImage
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($db) && $db->getConnection()->inTransaction()) {
        $db->rollback();
    }
    
    // Log the error
    error_log("Add vehicle error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to add vehicle: ' . $e->getMessage());
}
