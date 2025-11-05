<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin();
/**
 * Vehicle Form - Add/Edit Vehicles
 * 
 * @package VehicSmart
 */

// Set page title based on mode
$is_edit_mode = isset($_GET['id']);
$page_title = $is_edit_mode ? 'Edit Vehicle' : 'Add Vehicle';

// Include database connection
require_once '../config/database.php';
require_once '../config/ImageManager.php';

// Get database instance
$db = Database::getInstance();
$imageManager = new ImageManager($db);

// Initialize variables
$vehicle = [
    'id' => '',
    'brand' => '',
    'category_id' => 1, // Car par défaut
    'model' => '',
    'year' => date('Y'),
    'license_plate' => '',
    'vin' => '',
    'engine_type' => 'petrol',
    'color' => '',
    'seating_capacity' => '',
    'daily_rate' => '',
    'purchase_price' => '',
    'status' => 'available',
    'description' => '',
    'mileage' => 0
];

$images = [];
$errors = [];
$success = false;

// If in edit mode, load vehicle data
if ($is_edit_mode) {
    $vehicle_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    // Fetch vehicle data
    $vehicle_query = "SELECT * FROM vehicles WHERE id = :id";
    $result = $db->selectOne($vehicle_query, ['id' => $vehicle_id]);
    
    if ($result) {
        $vehicle = array_merge($vehicle, $result);
    } else {
        // Vehicle not found
        $errors[] = 'Vehicle not found.';
    }
    
    // Fetch vehicle images
    $images_query = "SELECT * FROM vehicle_images WHERE vehicle_id = :vehicle_id";
    $images = $db->select($images_query, ['vehicle_id' => $vehicle_id]);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    if (empty($_POST['name'])) {
        $errors[] = 'Vehicle brand is required.';
    }
    
    if (empty($_POST['make'])) {
        $errors[] = 'Model extra info is required.';
    }
    
    if (empty($_POST['model'])) {
        $errors[] = 'Model is required.';
    }
    
    if (empty($_POST['license_plate'])) {
        $errors[] = 'License plate is required.';
    }
    
    if (empty($_POST['vin'])) {
        $errors[] = 'VIN is required.';
    }
    
    // Validate numeric fields
    if (!is_numeric($_POST['daily_rate']) || $_POST['daily_rate'] <= 0) {
        $errors[] = 'Daily rate must be a positive number.';
    }
    
    if (!is_numeric($_POST['purchase_price']) || $_POST['purchase_price'] <= 0) {
        $errors[] = 'Purchase price must be a positive number.';
    }
    
    // If no errors, save the vehicle
    if (empty($errors)) {
        // Vérifier si la catégorie existe
        // Récupérer la catégorie sélectionnée du formulaire
        $category_id = isset($_POST['type']) && is_numeric($_POST['type']) ? (int)$_POST['type'] : 1;
        
        // Vérifier dans la base de données si cette catégorie existe
        $category_exists = $db->selectOne("SELECT id FROM vehicle_categories WHERE id = :id", ['id' => $category_id]);
        
        // Si la catégorie n'existe pas, utiliser une catégorie valide (ID 1)
        if (empty($category_exists)) {
            // Obtenir une catégorie valide 
            $valid_category = $db->selectOne("SELECT id FROM vehicle_categories ORDER BY id ASC LIMIT 1");
            
            if ($valid_category) {
                $category_id = $valid_category['id'];
                $errors[] = "La catégorie sélectionnée (#" . $_POST['type'] . ") n'existe pas. Utilisation de la catégorie #" . $category_id . " à la place.";
            } else {
                // Aucune catégorie n'existe, il faut en créer
                $errors[] = "Aucune catégorie de véhicule n'existe dans la base de données. Veuillez aller à <a href='database_maintenance.php?check_table=vehicle_categories' class='text-blue-600 underline'>Database Maintenance</a> pour créer les catégories par défaut.";
                // Empêcher la soumission
                throw new Exception("Aucune catégorie de véhicule n'existe. Impossible de continuer.");
            }
        }
        
        $vehicle_data = [
            'brand' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'category_id' => $category_id,
            'model' => filter_input(INPUT_POST, 'model', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'year' => filter_input(INPUT_POST, 'year', FILTER_SANITIZE_NUMBER_INT),
            'license_plate' => filter_input(INPUT_POST, 'license_plate', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'vin' => filter_input(INPUT_POST, 'vin', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'engine_type' => filter_input(INPUT_POST, 'fuel_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'color' => filter_input(INPUT_POST, 'color', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'seating_capacity' => filter_input(INPUT_POST, 'seats', FILTER_SANITIZE_NUMBER_INT) ?: null,
            'daily_rate' => filter_input(INPUT_POST, 'daily_rate', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'purchase_price' => filter_input(INPUT_POST, 'purchase_price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'status' => filter_input(INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'mileage' => filter_input(INPUT_POST, 'mileage', FILTER_SANITIZE_NUMBER_INT) ?: 0
        ];
        
        try {
            $db->getConnection()->beginTransaction();
            
            if ($is_edit_mode) {
                // Update existing vehicle
                $update_sql = "UPDATE vehicles SET 
                               brand = :brand,
                               category_id = :category_id,
                               model = :model,
                               year = :year,
                               license_plate = :license_plate,
                               vin = :vin,
                               engine_type = :engine_type,
                               color = :color,
                               seating_capacity = :seating_capacity,
                               daily_rate = :daily_rate,
                               purchase_price = :purchase_price,
                               status = :status,
                               description = :description,
                               mileage = :mileage,
                               updated_at = NOW()
                               WHERE id = :id";
                
                $vehicle_data['id'] = $vehicle_id;
                $db->query($update_sql, $vehicle_data);
                
                $vehicle_id = $vehicle['id'];
            } else {
                // Insert new vehicle
                $insert_sql = "INSERT INTO vehicles 
                              (brand, category_id, model, year, license_plate, vin, engine_type, 
                               color, seating_capacity, daily_rate, purchase_price, status, description, mileage)
                              VALUES 
                              (:brand, :category_id, :model, :year, :license_plate, :vin, :engine_type,
                               :color, :seating_capacity, :daily_rate, :purchase_price, :status, :description, :mileage)";
                
                $db->query($insert_sql, $vehicle_data);
                $vehicle_id = $db->getConnection()->lastInsertId();
            }
            
            // Handle image uploads avec le nouveau système BLOB
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                // Process each uploaded image
                $file_count = count($_FILES['images']['name']);
                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        // Préparer les données du fichier pour ImageManager
                        $file_data = [
                            'name' => $_FILES['images']['name'][$i],
                            'type' => $_FILES['images']['type'][$i],
                            'tmp_name' => $_FILES['images']['tmp_name'][$i],
                            'error' => $_FILES['images']['error'][$i],
                            'size' => $_FILES['images']['size'][$i]
                        ];
                        
                        try {
                            // Première image est principale pour les nouveaux véhicules
                            $is_primary = $i === 0 && !$is_edit_mode;
                            $image_id = $imageManager->saveVehicleImage($vehicle_id, $file_data, $is_primary);
                            
                            if (!$image_id) {
                                $errors[] = "Échec de l'upload de l'image: " . $_FILES['images']['name'][$i];
                            }
                        } catch (Exception $e) {
                            $errors[] = "Erreur lors de l'upload: " . $e->getMessage();
                        }
                    }
                }
            }
            
            // Process primary image selection
            if (isset($_POST['primary_image']) && !empty($_POST['primary_image'])) {
                try {
                    $imageManager->setPrimaryImage($_POST['primary_image'], $vehicle_id);
                } catch (Exception $e) {
                    $errors[] = "Erreur lors de la définition de l'image principale: " . $e->getMessage();
                }
            }
            
            // Process image deletions
            if (isset($_POST['delete_images']) && !empty($_POST['delete_images'])) {
                foreach ($_POST['delete_images'] as $image_id) {
                    try {
                        $imageManager->deleteImage($image_id);
                    } catch (Exception $e) {
                        $errors[] = "Erreur lors de la suppression de l'image: " . $e->getMessage();
                    }
                }
            }
            
            $db->getConnection()->commit();
            $success = true;
            
            // Redirect after successful save
            header("Location: vehicles_manage.php?success=1");
            exit;
            
        } catch (Exception $e) {
            $db->getConnection()->rollBack();
            $error_msg = $e->getMessage();
            $errors[] = 'Error saving vehicle: ' . $error_msg;
            
            // Si l'erreur concerne les catégories de véhicule
            if (strpos($error_msg, 'CONSTRAINT `vehicles_ibfk_1`') !== false) {
                $errors[] = 'La catégorie de véhicule sélectionnée n\'existe pas. Veuillez aller à <a href="database_maintenance.php?check_table=vehicle_categories" class="text-blue-600 underline">Database Maintenance</a> pour créer les catégories par défaut.';
            }
        }
    }
    
    // If there were errors, fill in the form with submitted values
    if (!empty($errors)) {
        $vehicle = array_merge($vehicle, $_POST);
    }
}

// Include header
include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h2 class="text-xl font-semibold"><?= $page_title ?></h2>
            <a href="check_database.php" class="text-blue-600 hover:underline text-sm">Check Database Structure</a>
        </div>
        <a href="vehicles_manage.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded">
            Back to Vehicles
        </a>
    </div>
    
    <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Error!</strong>
            <ul class="mt-1 list-disc list-inside">
                <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endforeach; ?>
            </ul>
        </div>
    <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endif; ?>
    
    <form action="<?= $is_edit_mode ? "vehicle_form.php?id={$vehicle['id']}" : "vehicle_form.php" ?>" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6">
        <?php echo csrfField(); ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="font-medium text-lg mb-4">Vehicle Information</h3>
                
                <!-- Name -->
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Brand *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="<?= htmlspecialchars($vehicle['brand'] ?? '') ?>" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                </div>
                
                <!-- Type (Category) -->
                <div class="mb-4">
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Vehicle Category *</label>
                    <select 
                        id="type" 
                        name="type" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                        <option value="1" <?= ($vehicle['category_id'] ?? '') == 1 ? 'selected' : '' ?>>Car</option>
                        <option value="2" <?= ($vehicle['category_id'] ?? '') == 2 ? 'selected' : '' ?>>Truck</option>
                        <option value="3" <?= ($vehicle['category_id'] ?? '') == 3 ? 'selected' : '' ?>>Bus</option>
                        <option value="4" <?= ($vehicle['category_id'] ?? '') == 4 ? 'selected' : '' ?>>Tractor</option>
                        <option value="5" <?= ($vehicle['category_id'] ?? '') == 5 ? 'selected' : '' ?>>Van</option>
                        <option value="6" <?= ($vehicle['category_id'] ?? '') == 6 ? 'selected' : '' ?>>Motorcycle</option>
                        <option value="7" <?= ($vehicle['category_id'] ?? '') == 7 ? 'selected' : '' ?>>SUV</option>
                    </select>
                </div>
                
                <!-- Make, Model, Year -->
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="make" class="block text-sm font-medium text-gray-700 mb-1">Model (Extra) *</label>
                        <input 
                            type="text" 
                            id="make" 
                            name="make" 
                            value="<?= htmlspecialchars($vehicle['make'] ?? '') ?>" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                        >
                    </div>
                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-700 mb-1">Model *</label>
                        <input 
                            type="text" 
                            id="model" 
                            name="model" 
                            value="<?= htmlspecialchars($vehicle['model'] ?? '') ?>" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                        >
                    </div>
                    <div>
                        <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Year *</label>
                        <input 
                            type="number" 
                            id="year" 
                            name="year" 
                            value="<?= htmlspecialchars($vehicle['year']) ?>" 
                            min="1900" 
                            max="<?= date('Y') + 1 ?>" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                        >
                    </div>
                </div>
                
                <!-- License Plate and VIN -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="license_plate" class="block text-sm font-medium text-gray-700 mb-1">License Plate *</label>
                        <input 
                            type="text" 
                            id="license_plate" 
                            name="license_plate" 
                            value="<?= htmlspecialchars($vehicle['license_plate']) ?>" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                        >
                    </div>
                    <div>
                        <label for="vin" class="block text-sm font-medium text-gray-700 mb-1">VIN *</label>
                        <input 
                            type="text" 
                            id="vin" 
                            name="vin" 
                            value="<?= htmlspecialchars($vehicle['vin']) ?>" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                        >
                    </div>
                </div>
                
                <!-- Fuel Type, Color, Seats -->
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="fuel_type" class="block text-sm font-medium text-gray-700 mb-1">Engine Type *</label>
                        <select 
                            id="fuel_type" 
                            name="fuel_type" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                        >
                            <option value="petrol" <?= ($vehicle['engine_type'] ?? '') === 'petrol' ? 'selected' : '' ?>>Petrol</option>
                            <option value="diesel" <?= ($vehicle['engine_type'] ?? '') === 'diesel' ? 'selected' : '' ?>>Diesel</option>
                            <option value="electric" <?= ($vehicle['engine_type'] ?? '') === 'electric' ? 'selected' : '' ?>>Electric</option>
                            <option value="hybrid" <?= ($vehicle['engine_type'] ?? '') === 'hybrid' ? 'selected' : '' ?>>Hybrid</option>
                        </select>
                    </div>
                    <div>
                        <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Color *</label>
                        <input 
                            type="text" 
                            id="color" 
                            name="color" 
                            value="<?= htmlspecialchars($vehicle['color']) ?>" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                        >
                    </div>
                    <div>
                        <label for="seats" class="block text-sm font-medium text-gray-700 mb-1">Seating Capacity</label>
                        <input 
                            type="number" 
                            id="seats" 
                            name="seats" 
                            value="<?= htmlspecialchars($vehicle['seating_capacity'] ?? '') ?>" 
                            min="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                        >
                    </div>
                </div>
                
                <!-- Mileage -->
                <div class="mb-4">
                    <label for="mileage" class="block text-sm font-medium text-gray-700 mb-1">Mileage (km)</label>
                    <input 
                        type="number" 
                        id="mileage" 
                        name="mileage" 
                        value="<?= htmlspecialchars($vehicle['mileage']) ?>" 
                        min="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                </div>
            </div>
            
            <div>
                <h3 class="font-medium text-lg mb-4">Pricing & Availability</h3>
                
                <!-- Daily Rate and Purchase Price -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="daily_rate" class="block text-sm font-medium text-gray-700 mb-1">Daily Rate ($) *</label>
                        <input 
                            type="number" 
                            id="daily_rate" 
                            name="daily_rate" 
                            value="<?= htmlspecialchars($vehicle['daily_rate']) ?>" 
                            step="0.01" 
                            min="0" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                        >
                    </div>
                    <div>
                        <label for="purchase_price" class="block text-sm font-medium text-gray-700 mb-1">Purchase Price ($) *</label>
                        <input 
                            type="number" 
                            id="purchase_price" 
                            name="purchase_price" 
                            value="<?= htmlspecialchars($vehicle['purchase_price']) ?>" 
                            step="0.01" 
                            min="0" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                        >
                    </div>
                </div>
                
                <!-- Status -->
                <div class="mb-4">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select 
                        id="status" 
                        name="status" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                        <option value="available" <?= $vehicle['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="rented" <?= $vehicle['status'] === 'rented' ? 'selected' : '' ?>>Rented</option>
                        <option value="maintenance" <?= $vehicle['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        <option value="sold" <?= $vehicle['status'] === 'sold' ? 'selected' : '' ?>>Sold</option>
                    </select>
                </div>
                
                <!-- Description -->
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        rows="5" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    ><?= htmlspecialchars($vehicle['description']) ?></textarea>
                </div>
                
                <!-- Images -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Images</label>
                    
                    <!-- Existing Images -->
                    <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); if (!empty($images)): ?>
                        <div class="grid grid-cols-3 gap-2 mb-3">
                            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); foreach ($images as $image): ?>
                                <div class="relative border rounded-md p-1">
                                    <img src="../uploads/vehicles/<?= htmlspecialchars($image['image_path']) ?>" alt="Vehicle Image" class="w-full h-24 object-cover rounded">
                                    <div class="mt-1 flex justify-between items-center">
                                        <div>
                                            <input 
                                                type="radio" 
                                                id="primary_<?= $image['id'] ?>" 
                                                name="primary_image" 
                                                value="<?= $image['id'] ?>" 
                                                <?= $image['is_primary'] ? 'checked' : '' ?>
                                            >
                                            <label for="primary_<?= $image['id'] ?>" class="text-xs">Primary</label>
                                        </div>
                                        <div>
                                            <input 
                                                type="checkbox" 
                                                id="delete_<?= $image['id'] ?>" 
                                                name="delete_images[]" 
                                                value="<?= $image['id'] ?>"
                                            >
                                            <label for="delete_<?= $image['id'] ?>" class="text-xs text-red-600">Delete</label>
                                        </div>
                                    </div>
                                </div>
                            <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endforeach; ?>
                        </div>
                    <?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin(); endif; ?>
                    
                    <!-- Upload New Images -->
                    <input 
                        type="file" 
                        id="images" 
                        name="images[]" 
                        multiple 
                        accept="image/*"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                    >
                    <p class="text-sm text-gray-500 mt-1">You can select multiple images. The first image will be set as primary for new vehicles.</p>
                </div>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <a href="vehicles_manage.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded mr-2">
                Cancel
            </a>
            <button type="submit" class="bg-accent hover:bg-accent/80 text-white py-2 px-4 rounded">
                <?= $is_edit_mode ? 'Update Vehicle' : 'Add Vehicle' ?>
            </button>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin();
// Include footer
include 'includes/footer.php';
?>
