<?php
/**
 * Cancel Rental API Endpoint
 * 
 * Allows users to cancel their rentals with optional refund processing
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
    // Verify authentication
    $user = requireAuth();
    
    // Get input data
    $data = getInputData('POST', ['rental_id']);
    $rentalId = $data['rental_id'];
    
    // Initialize database
    $db = new Database();
    
    // Fetch the rental
    $rental = $db->selectOne(
        "SELECT r.*, t.id as transaction_id, t.amount, t.payment_method, t.payment_intent_id
         FROM rentals r
         LEFT JOIN transactions t ON r.transaction_id = t.id
         WHERE r.id = :id",
        ['id' => $rentalId]
    );
    
    if (!$rental) {
        sendResponse(404, 'Rental not found');
    }
    
    // Check if this user owns the rental or is an admin
    if ($rental['user_id'] != $user['id'] && $user['role'] !== 'admin') {
        sendResponse(403, 'You do not have permission to cancel this rental');
    }
    
    // Check if the rental can be canceled
    if ($rental['status'] !== 'active' && $rental['status'] !== 'pending') {
        sendResponse(400, 'This rental cannot be canceled because its status is ' . $rental['status']);
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Calculate refund amount (if applicable)
    $refundAmount = 0;
    $cancellationReason = isset($data['reason']) ? $data['reason'] : 'User requested cancellation';
    $cancelFees = isset($data['apply_cancellation_fees']) ? (bool)$data['apply_cancellation_fees'] : true;
    
    // Get current date and start date
    $currentDate = strtotime(date('Y-m-d'));
    $startDate = strtotime($rental['start_date']);
    $daysDifference = ($startDate - $currentDate) / (60 * 60 * 24);
    
    if ($currentDate < $startDate) {
        // Rental hasn't started yet
        if ($daysDifference >= 7) {
            // 7+ days in advance: full refund (optional: minus small processing fee)
            $refundAmount = $cancelFees ? $rental['amount'] * 0.95 : $rental['amount'];
        } elseif ($daysDifference >= 3) {
            // 3-6 days in advance: 70% refund
            $refundAmount = $cancelFees ? $rental['amount'] * 0.7 : $rental['amount'];
        } elseif ($daysDifference >= 1) {
            // 1-2 days in advance: 50% refund
            $refundAmount = $cancelFees ? $rental['amount'] * 0.5 : $rental['amount'];
        } else {
            // Less than 24 hours: 30% refund
            $refundAmount = $cancelFees ? $rental['amount'] * 0.3 : $rental['amount'];
        }
    } else {
        // Rental has started - only refund remaining days if partial refunds are allowed
        if (isset($data['allow_partial_refund']) && $data['allow_partial_refund']) {
            $endDate = strtotime($rental['end_date']);
            $totalDays = $rental['duration_days'];
            $remainingDays = ($endDate - $currentDate) / (60 * 60 * 24);
            
            if ($remainingDays > 0) {
                $refundPercentage = $remainingDays / $totalDays;
                $refundAmount = $cancelFees ? $rental['amount'] * $refundPercentage * 0.7 : $rental['amount'] * $refundPercentage;
            }
        }
    }
    
    // Round to 2 decimal places
    $refundAmount = round($refundAmount, 2);
    
    // Process refund if applicable (this is a placeholder - actual refund processing depends on the payment provider)
    $refundProcessed = false;
    $refundTransactionId = null;
    
    if ($refundAmount > 0) {
        // Placeholder for actual payment processing
        // For Stripe, you would use their SDK to process a refund
        $refundProcessed = true;
        $refundTransactionId = 'refund_' . uniqid();
        
        // Create refund record in transactions table
        $refundData = [
            'user_id' => $rental['user_id'],
            'amount' => -$refundAmount, // Negative amount for refunds
            'payment_method' => $rental['payment_method'],
            'payment_intent_id' => $refundTransactionId,
            'status' => 'completed',
            'related_transaction_id' => $rental['transaction_id'],
            'description' => 'Refund for rental #' . $rentalId . ': ' . $cancellationReason,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $refundId = $db->insert('transactions', $refundData);
    }
    
    // Update rental status
    $db->update('rentals', 
        [
            'status' => 'canceled',
            'updated_at' => date('Y-m-d H:i:s'),
            'cancellation_reason' => $cancellationReason,
            'canceled_by_user_id' => $user['id'],
            'refund_amount' => $refundAmount,
            'refund_transaction_id' => $refundTransactionId
        ], 
        'id = :id', 
        ['id' => $rentalId]
    );
    
    // Create an alert for the cancellation
    $vehicleInfo = $db->selectOne(
        "SELECT make, model, license_plate FROM vehicles WHERE id = :id",
        ['id' => $rental['vehicle_id']]
    );
    
    $db->insert('alerts', [
        'type' => 'rental_canceled',
        'message' => "Rental canceled: {$vehicleInfo['make']} {$vehicleInfo['model']} ({$vehicleInfo['license_plate']})" . 
                    ($refundAmount > 0 ? " with refund of $" . number_format($refundAmount, 2) : " without refund"),
        'vehicle_id' => $rental['vehicle_id'],
        'user_id' => $rental['user_id'],
        'related_id' => $rentalId,
        'related_type' => 'rental',
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Commit transaction
    $db->commit();
    
    // Return success response
    sendResponse(200, 'Rental canceled successfully', [
        'rental_id' => $rentalId,
        'status' => 'canceled',
        'refund_amount' => $refundAmount,
        'refund_processed' => $refundProcessed,
        'refund_transaction_id' => $refundTransactionId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($db) && $db->getConnection()->inTransaction()) {
        $db->rollback();
    }
    
    // Log the error
    error_log("Cancel rental error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to cancel rental: ' . $e->getMessage());
}
