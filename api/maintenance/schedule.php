<?php
/**
 * Schedule Maintenance API Endpoint
 * 
 * Allows scheduling new maintenance for vehicles
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
    // Verify authentication with admin role
    $user = requireRole('admin');
    
    // Get input data
    $data = getInputData('POST', [
        'vehicle_id',
        'maintenance_date',
        'maintenance_type',
        'description'
    ]);
    
    // Initialize database
    $db = new Database();
    
    // Check if vehicle exists
    $vehicle = $db->selectOne(
        "SELECT * FROM vehicles WHERE id = :id",
        ['id' => $data['vehicle_id']]
    );
    
    if (!$vehicle) {
        sendResponse(404, 'Vehicle not found');
    }
    
    // Validate maintenance_date
    $maintenanceDate = strtotime($data['maintenance_date']);
    if (!$maintenanceDate) {
        sendResponse(400, 'Invalid maintenance date format');
    }
    
    // Format date for database
    $maintenanceDateStr = date('Y-m-d', $maintenanceDate);
    
    // Validate cost if provided
    $cost = null;
    if (isset($data['cost'])) {
        $cost = filter_var($data['cost'], FILTER_VALIDATE_FLOAT);
        if ($cost === false || $cost < 0) {
            sendResponse(400, 'Invalid cost value');
        }
    }
    
    // Validate odometer reading if provided
    $odometerReading = null;
    if (isset($data['odometer_reading'])) {
        $odometerReading = filter_var($data['odometer_reading'], FILTER_VALIDATE_INT);
        if ($odometerReading === false || $odometerReading < 0) {
            sendResponse(400, 'Invalid odometer reading');
        }
    }
    
    // Process documents if provided
    $documents = [];
    if (isset($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
        $uploadDir = '../../uploads/maintenance_docs/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Process each uploaded file
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        foreach ($_FILES['documents']['name'] as $key => $name) {
            if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                // Check file type
                $tmpName = $_FILES['documents']['tmp_name'][$key];
                $fileType = mime_content_type($tmpName);
                
                if (!in_array($fileType, $allowedTypes)) {
                    continue; // Skip invalid file types
                }
                
                // Generate unique filename
                $fileName = time() . '_' . $vehicle['id'] . '_' . basename($name);
                $targetFile = $uploadDir . $fileName;
                
                // Move uploaded file
                if (move_uploaded_file($tmpName, $targetFile)) {
                    $documents[] = [
                        'name' => $name,
                        'path' => '/uploads/maintenance_docs/' . $fileName,
                        'type' => $fileType,
                        'uploaded_at' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }
    }
    
    // Create maintenance record
    $maintenanceData = [
        'vehicle_id' => $data['vehicle_id'],
        'maintenance_date' => $maintenanceDateStr,
        'maintenance_type' => $data['maintenance_type'],
        'description' => $data['description'],
        'cost' => $cost,
        'status' => isset($data['status']) ? $data['status'] : 'scheduled',
        'odometer_reading' => $odometerReading,
        'performed_by' => isset($data['performed_by']) ? $data['performed_by'] : null,
        'notes' => isset($data['notes']) ? $data['notes'] : null,
        'documents' => !empty($documents) ? json_encode($documents) : null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $maintenanceId = $db->insert('maintenance_records', $maintenanceData);
    
    // Create alert for admins about new maintenance
    $alertMessage = "Maintenance scheduled: {$data['maintenance_type']} for {$vehicle['make']} {$vehicle['model']} ({$vehicle['license_plate']}) on {$maintenanceDateStr}";
    
    // Find admin users
    $admins = $db->select(
        "SELECT id FROM users WHERE role = 'admin' AND status = 'active'"
    );
    
    foreach ($admins as $admin) {
        $db->insert('alerts', [
            'user_id' => $admin['id'],
            'type' => 'maintenance_scheduled',
            'message' => $alertMessage,
            'vehicle_id' => $data['vehicle_id'],
            'related_id' => $maintenanceId,
            'related_type' => 'maintenance',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Check for active rentals for this vehicle during maintenance
    $activeRentals = $db->select(
        "SELECT id, user_id, start_date, end_date 
         FROM rentals 
         WHERE vehicle_id = :vehicle_id 
         AND status = 'active'
         AND (
             (start_date <= :maintenance_date AND end_date >= :maintenance_date)
         )",
        [
            'vehicle_id' => $data['vehicle_id'],
            'maintenance_date' => $maintenanceDateStr
        ]
    );
    
    // Notify renters if there are active rentals during maintenance
    foreach ($activeRentals as $rental) {
        $rentalAlertMessage = "Maintenance scheduled: {$data['maintenance_type']} for your rented {$vehicle['make']} {$vehicle['model']} on {$maintenanceDateStr}. This may affect your rental.";
        
        $db->insert('alerts', [
            'user_id' => $rental['user_id'],
            'type' => 'maintenance_affects_rental',
            'message' => $rentalAlertMessage,
            'vehicle_id' => $data['vehicle_id'],
            'related_id' => $maintenanceId,
            'related_type' => 'maintenance',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Send response
    sendResponse(201, 'Maintenance scheduled successfully', [
        'maintenance_id' => $maintenanceId,
        'vehicle' => [
            'id' => $vehicle['id'],
            'make' => $vehicle['make'],
            'model' => $vehicle['model'],
            'license_plate' => $vehicle['license_plate']
        ],
        'maintenance_date' => $maintenanceDateStr,
        'maintenance_type' => $data['maintenance_type'],
        'status' => $maintenanceData['status'],
        'documents' => $documents
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Schedule maintenance error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to schedule maintenance: ' . $e->getMessage());
}
