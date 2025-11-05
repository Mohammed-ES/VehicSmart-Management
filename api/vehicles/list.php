<?php
/**
 * Vehicle List API Endpoint
 * 
 * Returns list of vehicles with optional filtering
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
    // Initialize database
    $db = new Database();
    
    // Get query parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause based on filters
    $whereClause = "WHERE 1=1";
    $params = [];
    
    // Filter by type
    if (isset($_GET['type']) && !empty($_GET['type'])) {
        $whereClause .= " AND v.type = :type";
        $params['type'] = $_GET['type'];
    }
    
    // Filter by status
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $whereClause .= " AND v.status = :status";
        $params['status'] = $_GET['status'];
    }
    
    // Filter by make
    if (isset($_GET['make']) && !empty($_GET['make'])) {
        $whereClause .= " AND v.make = :make";
        $params['make'] = $_GET['make'];
    }
    
    // Filter by model
    if (isset($_GET['model']) && !empty($_GET['model'])) {
        $whereClause .= " AND v.model = :model";
        $params['model'] = $_GET['model'];
    }
    
    // Filter by year range
    if (isset($_GET['year_min']) && is_numeric($_GET['year_min'])) {
        $whereClause .= " AND v.year >= :year_min";
        $params['year_min'] = intval($_GET['year_min']);
    }
    
    if (isset($_GET['year_max']) && is_numeric($_GET['year_max'])) {
        $whereClause .= " AND v.year <= :year_max";
        $params['year_max'] = intval($_GET['year_max']);
    }
    
    // Filter by price range
    if (isset($_GET['price_min']) && is_numeric($_GET['price_min'])) {
        $whereClause .= " AND v.price >= :price_min";
        $params['price_min'] = floatval($_GET['price_min']);
    }
    
    if (isset($_GET['price_max']) && is_numeric($_GET['price_max'])) {
        $whereClause .= " AND v.price <= :price_max";
        $params['price_max'] = floatval($_GET['price_max']);
    }
    
    // Add search term if provided
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $searchTerm = '%' . $_GET['search'] . '%';
        $whereClause .= " AND (v.name LIKE :search OR v.make LIKE :search OR v.model LIKE :search OR v.description LIKE :search)";
        $params['search'] = $searchTerm;
    }
    
    // Add sorting
    $sortField = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $validSortFields = ['name', 'make', 'model', 'year', 'price', 'created_at'];
    
    if (!in_array($sortField, $validSortFields)) {
        $sortField = 'created_at';
    }
    
    $sortDirection = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM vehicles v $whereClause";
    $countResult = $db->selectOne($countQuery, $params);
    $total = $countResult['total'];
    
    // Get vehicles with primary images
    $query = "
        SELECT 
            v.*, 
            (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as primary_image,
            (SELECT COUNT(*) FROM vehicle_images WHERE vehicle_id = v.id) as image_count
        FROM 
            vehicles v
        $whereClause
        ORDER BY 
            v.$sortField $sortDirection
        LIMIT 
            :offset, :limit
    ";
    
    $params['offset'] = $offset;
    $params['limit'] = $limit;
    
    $vehicles = $db->select($query, $params);
    
    // Process vehicles to format data correctly
    foreach ($vehicles as &$vehicle) {
        // Format decimal values
        $vehicle['price'] = floatval($vehicle['price']);
        $vehicle['rental_rate_daily'] = floatval($vehicle['rental_rate_daily']);
        
        // Format boolean values
        $vehicle['is_available'] = (bool)$vehicle['is_available'];
        
        // Include full URL for primary image
        if (!empty($vehicle['primary_image'])) {
            $vehicle['primary_image_url'] = 'https://vehicsmart.com/uploads/vehicles/' . $vehicle['primary_image'];
        } else {
            $vehicle['primary_image_url'] = null;
        }
        
        // Convert image_count to integer
        $vehicle['image_count'] = intval($vehicle['image_count']);
    }
    
    // Calculate pagination info
    $totalPages = ceil($total / $limit);
    $hasNextPage = $page < $totalPages;
    $hasPreviousPage = $page > 1;
    
    // Return vehicles list with pagination
    sendResponse(200, 'Vehicles retrieved successfully', [
        'vehicles' => $vehicles,
        'pagination' => [
            'total' => $total,
            'count' => count($vehicles),
            'per_page' => $limit,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_next_page' => $hasNextPage,
            'has_previous_page' => $hasPreviousPage
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Vehicle list error: " . $e->getMessage());
    
    // Return error response
    sendResponse(500, 'Failed to retrieve vehicles. Please try again later.');
}
