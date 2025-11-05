<?php
/**
 * My Rentals Page
 * 
 * Displays user's rental history
 */

// Include required files
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
requireAuth();

// Get current user
$user = getCurrentUser();

// Set page title
$pageTitle = 'My Rentals';

// Initialize database
$db = new Database();

// Get status filter if set
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Set up base query
$query = "SELECT r.*, v.make, v.model, v.year, v.license_plate, v.image_url
          FROM rentals r
          JOIN vehicles v ON r.vehicle_id = v.id
          WHERE r.user_id = ?";
$params = [$user['id']];

// Add filter conditions
if ($status) {
    $query .= " AND r.status = ?";
    $params[] = $status;
}

// Add sorting
$query .= " ORDER BY r.start_date DESC";

// Get rentals
try {
    $rentals = $db->select($query, $params);
} catch (Exception $e) {
    error_log('Rentals page error: ' . $e->getMessage());
    $rentals = [];
}

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">My Rentals</h1>
            <p class="text-gray-600">View all your rental history</p>
        </div>
        
        <!-- Filter Controls -->
        <div class="mt-4 md:mt-0">
            <div class="flex space-x-2">
                <a href="rentals.php" class="px-4 py-2 <?= !$status ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800' ?> rounded-md hover:bg-opacity-90 transition-colors">
                    All
                </a>
                <a href="rentals.php?status=active" class="px-4 py-2 <?= $status === 'active' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800' ?> rounded-md hover:bg-opacity-90 transition-colors">
                    Active
                </a>
                <a href="rentals.php?status=completed" class="px-4 py-2 <?= $status === 'completed' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800' ?> rounded-md hover:bg-opacity-90 transition-colors">
                    Completed
                </a>
                <a href="rentals.php?status=cancelled" class="px-4 py-2 <?= $status === 'cancelled' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800' ?> rounded-md hover:bg-opacity-90 transition-colors">
                    Cancelled
                </a>
            </div>
        </div>
    </div>
    
    <?php if (!empty($rentals)): ?>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Vehicle
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Rental Period
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Cost
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($rentals as $rental): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php if ($rental['image_url']): ?>
                                                <img class="h-10 w-10 rounded-full object-cover" src="<?= htmlspecialchars($rental['image_url']) ?>" alt="">
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19l-7-7 7-7m8 14l-7-7 7-7" />
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($rental['make'] . ' ' . $rental['model']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($rental['year']) ?> • <?= htmlspecialchars($rental['license_plate']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= date('M d, Y', strtotime($rental['start_date'])) ?> - <?= date('M d, Y', strtotime($rental['end_date'])) ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php
                                            $days = (strtotime($rental['end_date']) - strtotime($rental['start_date'])) / (60 * 60 * 24);
                                            echo $days . ' ' . ($days == 1 ? 'day' : 'days');
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $statusClass = '';
                                        $statusBg = '';
                                        
                                        switch ($rental['status']) {
                                            case 'active':
                                                $statusClass = 'text-green-800';
                                                $statusBg = 'bg-green-100';
                                                break;
                                            case 'completed':
                                                $statusClass = 'text-blue-800';
                                                $statusBg = 'bg-blue-100';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'text-red-800';
                                                $statusBg = 'bg-red-100';
                                                break;
                                            case 'pending':
                                                $statusClass = 'text-yellow-800';
                                                $statusBg = 'bg-yellow-100';
                                                break;
                                            default:
                                                $statusClass = 'text-gray-800';
                                                $statusBg = 'bg-gray-100';
                                        }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusBg ?> <?= $statusClass ?>">
                                        <?= ucfirst($rental['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    $<?= number_format($rental['total_cost'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="rental_details.php?id=<?= $rental['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                    <?php if ($rental['status'] === 'active'): ?>
                                        <a href="#" class="text-red-600 hover:text-red-900" 
                                           onclick="confirmCancel(<?= $rental['id'] ?>, '<?= htmlspecialchars($rental['make'] . ' ' . $rental['model']) ?>')">
                                            Cancel
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="text-xl font-medium text-gray-800 mb-2">No Rentals Found</h3>
            <p class="text-gray-600 mb-6">
                <?php if ($status): ?>
                    You don't have any <?= $status ?> rentals.
                <?php else: ?>
                    You haven't rented any vehicles yet.
                <?php endif; ?>
            </p>
            <a href="../page/vehicles.php" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-block">
                Browse Vehicles
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Cancel Rental Modal -->
<div id="cancelModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    <div class="bg-white rounded-lg max-w-md w-full mx-4 z-10">
        <div class="p-6">
            <h3 class="text-xl font-bold mb-4">Confirm Cancellation</h3>
            <p class="mb-6">Are you sure you want to cancel your rental of <span id="vehicleName"></span>? Cancellation fees may apply.</p>
            <div class="flex justify-end space-x-3">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
                    No, Keep Rental
                </button>
                <form id="cancelForm" action="../api/rentals/cancel.php" method="post">
                    <input type="hidden" name="rental_id" id="rentalId">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Yes, Cancel Rental
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmCancel(rentalId, vehicleName) {
        document.getElementById('rentalId').value = rentalId;
        document.getElementById('vehicleName').textContent = vehicleName;
        document.getElementById('cancelModal').classList.remove('hidden');
    }
    
    function closeModal() {
        document.getElementById('cancelModal').classList.add('hidden');
    }
    
    // Close modal when clicking outside
    document.getElementById('cancelModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeModal();
        }
    });
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>
