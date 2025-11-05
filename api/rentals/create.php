<?php
/**
 * Create Rental API Endpoint
 * 
 * Creates a new vehicle rental or purchase
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
    // Verify token and ensure client role
    $user = requireRole('client');
    
    // Get input data with required fields
    $data = getInputData('POST', [
        'vehicle_id', 
        'transaction_type', // 'rental' or 'purchase'
        'payment_method' // 'stripe', 'paypal', 'bank_transfer', etc.
    ]);
    
    // If rental, require start and end dates
    if ($data['transaction_type'] === 'rental') {
        if (!isset($data['start_date']) || !isset($data['end_date'])) {
            sendResponse(400, 'Start and end dates are required for rentals');
        }
        
        // Validate dates
        $startDate = strtotime($data['start_date']);
        $endDate = strtotime($data['end_date']);
        
        if (!$startDate || !$endDate) {
            sendResponse(400, 'Invalid date format');
        }
        
        if ($endDate <= $startDate) {
            sendResponse(400, 'End date must be after start date');
        }
        
        // Convert to datetime strings
        $startDateStr = date('Y-m-d', $startDate);
        $endDateStr = date('Y-m-d', $endDate);
        
        // Calculate rental duration in days
        $duration = round(($endDate - $startDate) / (60 * 60 * 24));
        
        if ($duration < 1) {
            $duration = 1; // Minimum 1 day
        }
    } else if ($data['transaction_type'] !== 'purchase') {
        sendResponse(400, 'Invalid transaction type. Must be "rental" or "purchase"');
    }
    
    // Initialize database
    $db = new Database();
    
    // Check if vehicle exists
    $vehicle = $db->selectOne("SELECT * FROM vehicles WHERE id = :id", [
        'id' => $data['vehicle_id']
    ]);
    
    if (!$vehicle) {
        sendResponse(404, 'Vehicle not found');
    }
    
    // Check if vehicle is available
    if (!$vehicle['is_available']) {
        sendResponse(400, 'Vehicle is not available');
    }
    
    // If rental, check for date conflicts
    if ($data['transaction_type'] === 'rental') {
        $conflicts = $db->selectOne(
            "SELECT COUNT(*) as conflict_count 
             FROM rentals 
             WHERE vehicle_id = :vehicle_id 
             AND status IN ('active', 'pending') 
             AND (
                 (start_date <= :end_date AND end_date >= :start_date)
             )",
            [
                'vehicle_id' => $data['vehicle_id'],
                'start_date' => $startDateStr,
                'end_date' => $endDateStr
            ]
        );
        
        if ($conflicts['conflict_count'] > 0) {
            sendResponse(409, 'Vehicle is not available for the selected dates');
        }
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Calculate cost
    $amount = 0;
    
    if ($data['transaction_type'] === 'rental') {
        // Rental cost = daily rate * duration
        $amount = $vehicle['rental_rate_daily'] * $duration;
    } else {
        // Purchase cost = vehicle price
        $amount = $vehicle['price'];
    }
    
    // Apply discount if provided
    if (isset($data['discount_percentage']) && is_numeric($data['discount_percentage'])) {
        $discount = min(max(0, floatval($data['discount_percentage'])), 100);
        $discountAmount = $amount * ($discount / 100);
        $amount -= $discountAmount;
    }
    
    // Process payment (this is a placeholder - actual payment processing would depend on the provider)
    // For Stripe integration, you would use their SDK here
    
    // For demo purposes, we'll simulate a payment confirmation
    $paymentConfirmed = true;
    $paymentIntentId = 'pi_' . uniqid();
    
    if (!$paymentConfirmed) {
        throw new Exception('Payment processing failed');
    }
    
    // Create transaction record
    $transactionData = [
        'user_id' => $user['id'],
        'amount' => $amount,
        'payment_method' => $data['payment_method'],
        'payment_intent_id' => $paymentIntentId,
        'status' => 'completed',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $transactionId = $db->insert('transactions', $transactionData);
    
    // Create rental or purchase record
    if ($data['transaction_type'] === 'rental') {
        $rentalData = [
            'user_id' => $user['id'],
            'vehicle_id' => $data['vehicle_id'],
            'transaction_id' => $transactionId,
            'start_date' => $startDateStr,
            'end_date' => $endDateStr,
            'duration_days' => $duration,
            'total_cost' => $amount,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $recordId = $db->insert('rentals', $rentalData);
        $recordType = 'rental';
        
        // Create an alert for the new rental
        $db->insert('alerts', [
            'type' => 'new_rental',
            'message' => "New rental: {$vehicle['make']} {$vehicle['model']} ({$vehicle['license_plate']}) from $startDateStr to $endDateStr",
            'vehicle_id' => $data['vehicle_id'],
            'user_id' => $user['id'],
            'related_id' => $recordId,
            'related_type' => 'rental',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        $purchaseData = [
            'user_id' => $user['id'],
            'vehicle_id' => $data['vehicle_id'],
            'transaction_id' => $transactionId,
            'price' => $amount,
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $recordId = $db->insert('purchases', $purchaseData);
        $recordType = 'purchase';
        
        // Update vehicle status to sold
        $db->update('vehicles', 
            [
                'status' => 'sold',
                'is_available' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ], 
            'id = :id', 
            ['id' => $data['vehicle_id']]
        );
        
        // Create an alert for the new purchase
        $db->insert('alerts', [
            'type' => 'new_purchase',
            'message' => "Vehicle purchased: {$vehicle['make']} {$vehicle['model']} ({$vehicle['license_plate']})",
            'vehicle_id' => $data['vehicle_id'],
            'user_id' => $user['id'],
            'related_id' => $recordId,
            'related_type' => 'purchase',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Create payment record
    $paymentData = [
        'user_id' => $user['id'],
        'amount' => $amount,
        'related_id' => $recordId,
        'related_type' => $recordType,
        'payment_method' => $data['payment_method'],
        'transaction_id' => $transactionId,
        'status' => 'completed',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $db->insert('payments', $paymentData);
    
    // Commit transaction
    $db->commit();
    
    // Return success response
    sendResponse(201, 'Transaction completed successfully', [
        'transaction_id' => $transactionId,
        'record_id' => $recordId,
        'record_type' => $recordType,
        'amount' => $amount,
        'vehicle' => [
            'id' => $vehicle['id'],
            'name' => $vehicle['name'],
            'make' => $vehicle['make'],
            'model' => $vehicle['model'],
            'year' => $vehicle['year']
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($db) && $db->getConnection()->inTransaction()) {
        $db->rollback();
    }
    
    // Log the error
    error_log("Create rental/purchase error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Transaction failed: ' . $e->getMessage());
}
