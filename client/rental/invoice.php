<?php
/**
 * Invoice
 * 
 * Generates and displays an invoice for a rental
 */

// Include required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
requireLogin();
requireRole('client');

// Get current user
$user = getCurrentUser();
$user_id = $user['id'];

// Initialize database
$db = Database::getInstance();

// Get rental ID from URL parameter
$rental_id = isset($_GET['rental_id']) ? (int)$_GET['rental_id'] : 0;

// Set page title
$pageTitle = 'Invoice'; 
$page_title = 'Invoice'; // For backwards compatibility with header.php

// Initialize variables
$rental = null;
$error = null;

// Fetch rental details with vehicle and user information
try {
    if ($rental_id > 0) {
        $query = "SELECT r.*, 
                  v.brand, v.model, v.year, v.license_plate, v.daily_rate,
                  CONCAT(v.brand, ' ', v.model, ' (', v.year, ')') AS vehicle_name,
                  (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image,
                  u.first_name, u.last_name, u.email, u.phone
                  FROM rentals r
                  LEFT JOIN vehicles v ON r.vehicle_id = v.id
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE r.id = :rental_id AND r.user_id = :user_id";
                  
        $rental = $db->selectOne($query, [
            'rental_id' => $rental_id,
            'user_id' => $user_id
        ]);
        
        if (!$rental) {
            $error = 'Invoice not found or you do not have permission to view it.';
        }
    } else {
        $error = 'Invalid rental ID.';
    }
    
    // Calculate rental duration
    if ($rental) {
        $start_date = new DateTime($rental['start_date']);
        $end_date = new DateTime($rental['end_date']);
        $rental_duration = $start_date->diff($end_date)->days;
        
        // Calculate base cost
        $base_cost = $rental_duration * $rental['daily_rate'];
        
        // Get company information
        $company = [
            'name' => 'VehicSmart Rental',
            'address' => '123 Rental Street, Car City',
            'phone' => '+1 (555) 123-4567',
            'email' => 'billing@vehicsmart.com',
            'website' => 'www.vehicsmart.com',
            'tax_id' => 'TAX-123456789'
        ];
        
        // Generate invoice number
        $invoice_number = 'INV-' . str_pad($rental['id'], 6, '0', STR_PAD_LEFT);
        
        // Generate invoice date (use return_date if available, otherwise current date)
        $invoice_date = !empty($rental['return_date']) ? new DateTime($rental['return_date']) : new DateTime();
    }
} catch (Exception $e) {
    $error = 'Error retrieving invoice details: ' . $e->getMessage();
}

// Handle download if requested
if (isset($_GET['download']) && $rental) {
    // Only allow HTML download
    $html_content = '<!DOCTYPE html>';
    $html_content .= '\n<html>...'; // ...existing HTML generation code...
    // Create a new file in the exports directory to store the HTML
    $exports_dir = __DIR__ . '/exports';
    if (!file_exists($exports_dir)) {
        mkdir($exports_dir, 0755, true);
    }
    $debug_file = __DIR__ . '/exports/invoice_debug_' . $rental_id . '.html';
    file_put_contents($debug_file, $html_content);
    // Force download as HTML file
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="invoice_' . $invoice_number . '.html"');
    echo $html_content;
    exit;
}

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="w-full p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Invoice</h1>
            <p class="text-gray-600">Rental Invoice #<?= htmlspecialchars($invoice_number ?? 'N/A') ?></p>
        </div>
        
        <div class="flex items-center space-x-4">
            <a href="./rental_details.php?id=<?= $rental_id ?>" class="text-accent hover:text-accent/80 flex items-center text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Rental Details
            </a>
            
            <?php if ($rental): ?>
                <div class="flex space-x-2">
                    <a href="./invoice.php?rental_id=<?= $rental_id ?>&download=html" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-accent hover:bg-accent/80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Download 
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php elseif (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php elseif ($rental): ?>
        <!-- Print-friendly invoice -->
        <div class="bg-white rounded-lg shadow-md p-8 mb-6" id="invoice-printable">
            <!-- Invoice Header -->
            <div class="flex flex-col md:flex-row justify-between mb-8 pb-4 border-b">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($company['name']) ?></h2>
                    <p class="text-gray-600"><?= htmlspecialchars($company['address']) ?></p>
                    <p class="text-gray-600"><?= htmlspecialchars($company['phone']) ?></p>
                    <p class="text-gray-600"><?= htmlspecialchars($company['email']) ?></p>
                </div>
                <div class="mt-4 md:mt-0 text-right">
                    <h3 class="text-xl font-bold text-gray-800">INVOICE</h3>
                    <p class="text-gray-600">
                        <span class="font-semibold">Invoice Number:</span> <?= htmlspecialchars($invoice_number) ?>
                    </p>
                    <p class="text-gray-600">
                        <span class="font-semibold">Invoice Date:</span> <?= $invoice_date->format('F j, Y') ?>
                    </p>
                    <p class="text-gray-600">
                        <span class="font-semibold">Due Date:</span> <?= $invoice_date->format('F j, Y') ?>
                    </p>
                </div>
            </div>
            
            <!-- Customer Info -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Billed To:</h3>
                <p class="text-gray-800 font-medium"><?= htmlspecialchars($rental['first_name'] . ' ' . $rental['last_name']) ?></p>
                <p class="text-gray-600"><?= htmlspecialchars($rental['email']) ?></p>
                <?php if (!empty($rental['phone'])): ?>
                <p class="text-gray-600"><?= htmlspecialchars($rental['phone']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Rental Summary -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Rental Summary</h3>
                <table class="min-w-full">
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rental Period</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                    <tr>
                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-lg overflow-hidden">
                                    <?php if (!empty($rental['image'])): ?>
                                        <img src="<?= htmlspecialchars('../../uploads/vehicles/' . $rental['image']) ?>" alt="<?= htmlspecialchars($rental['vehicle_name']) ?>" class="h-full w-full object-cover">
                                    <?php else: ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-5h2a2 2 0 011.732 1H14a2 2 0 011.9 1.411 2.5 2.5 0 014.1 2.589H19a1 1 0 001-1v-1a2 2 0 00-2-2h-6.1a2 2 0 01-1.401-.586L8.887 8H4a1 1 0 00-1 1v.14l.143-.14A2 2 0 013 8V5a1 1 0 00-1-1h1z" />
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <div class="font-medium text-gray-900">
                                        <?= htmlspecialchars($rental['vehicle_name']) ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?= htmlspecialchars($rental['license_plate'] ?? 'No plate info') ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                            <?= date('M j, Y', strtotime($rental['start_date'])) ?><br>
                            to<br>
                            <?= date('M j, Y', strtotime($rental['end_date'])) ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                            <?= $rental_duration ?> <?= ($rental_duration === 1) ? 'day' : 'days' ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                            <?php
                            $statusClass = '';
                            switch ($rental['status']) {
                                case 'active':
                                    $statusClass = 'bg-green-100 text-green-800';
                                    break;
                                case 'completed':
                                    $statusClass = 'bg-blue-100 text-blue-800';
                                    break;
                                case 'cancelled':
                                    $statusClass = 'bg-red-100 text-red-800';
                                    break;
                                case 'pending':
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                    break;
                            }
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                <?= ucfirst($rental['status']) ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Payment Breakdown -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Payment Details</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                Vehicle Rental: <?= htmlspecialchars($rental['vehicle_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                $<?= number_format($rental['daily_rate'], 2) ?>/day
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= $rental_duration ?> <?= ($rental_duration === 1) ? 'day' : 'days' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                $<?= number_format($base_cost, 2) ?>
                            </td>
                        </tr>
                        
                        <?php if (!empty($rental['insurance_fee']) && $rental['insurance_fee'] > 0): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                Insurance Coverage
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                -
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                -
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                $<?= number_format($rental['insurance_fee'], 2) ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($rental['additional_fees']) && $rental['additional_fees'] > 0): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                Additional Services
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                -
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                -
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                $<?= number_format($rental['additional_fees'], 2) ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($rental['late_fee']) && $rental['late_fee'] > 0): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                Late Return Fee
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                -
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                -
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                $<?= number_format($rental['late_fee'], 2) ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($rental['discount']) && $rental['discount'] > 0): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                Discount Applied
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                -
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                -
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-right">
                                -$<?= number_format($rental['discount'], 2) ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50">
                            <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                Subtotal
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">
                                $<?= number_format($rental['total_cost'] ?? $base_cost, 2) ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                Tax (included)
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                $<?= number_format(($rental['total_cost'] ?? $base_cost) * 0.1, 2) ?>
                            </td>
                        </tr>
                        <tr class="bg-gray-100">
                            <td colspan="3" class="px-6 py-4 whitespace-nowrap text-base font-bold text-gray-900">
                                Total Amount
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-base font-bold text-gray-900 text-right">
                                $<?= number_format($rental['total_cost'] ?? $base_cost, 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Payment Status -->
            <div class="mb-8 p-4 <?= ($rental['payment_status'] === 'paid') ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200' ?> rounded-md">
                <h3 class="text-lg font-semibold <?= ($rental['payment_status'] === 'paid') ? 'text-green-800' : 'text-yellow-800' ?> mb-2">
                    Payment Status: <?= ucfirst($rental['payment_status'] ?? 'pending') ?>
                </h3>
                <p class="text-sm <?= ($rental['payment_status'] === 'paid') ? 'text-green-700' : 'text-yellow-700' ?>">
                    <?php if ($rental['payment_status'] === 'paid'): ?>
                        This invoice has been paid in full. Thank you for your business!
                    <?php else: ?>
                        This invoice is pending payment. Please complete the payment to avoid any service interruptions.
                    <?php endif; ?>
                </p>
            </div>
            
            <!-- Terms and Notes -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Terms & Conditions</h3>
                <p class="text-sm text-gray-600">
                    1. Payment is due immediately upon vehicle return.<br>
                    2. Late returns will incur additional fees at 150% of the daily rate.<br>
                    3. Vehicle must be returned in the same condition as received.<br>
                    4. Any damage not covered by insurance will be billed separately.<br>
                    5. Refunds will be processed according to our refund policy.
                </p>
            </div>
            
            <!-- Thank You Note -->
            <div class="text-center pt-8 border-t">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Thank You for Choosing <?= htmlspecialchars($company['name']) ?></h3>
                <p class="text-sm text-gray-600">If you have any questions about this invoice, please contact us at <?= htmlspecialchars($company['email']) ?>.</p>
            </div>
        </div>
        
        <!-- Information message -->
        <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        You can download the invoice in HTML format. Click the "Download" button above to save it to your device.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex justify-center mt-6">
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Invoice
            </button>
            
        </div>
    <?php endif; ?>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #invoice-printable, #invoice-printable * {
            visibility: visible;
        }
        #invoice-printable {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 20px;
            box-shadow: none;
        }
        .no-print {
            display: none;
        }
    }
</style>


<?php
// Chatbot system removed
?>
</body>
</html>

