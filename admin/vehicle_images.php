<?php
/**
 * Gestion des Images de Véhicules
 * Upload et gestion des images en base de données
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ImageManager.php';

// Check if user is admin
requireAdmin();

$pageTitle = 'Gestion des Images';

// Get vehicle ID
$vehicle_id = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;

if (!$vehicle_id) {
    header('Location: vehicles_manage.php');
    exit;
}

// Initialize
$db = Database::getInstance();
$imageManager = new ImageManager();
$message = '';
$messageType = '';

// Get vehicle info
$vehicle = $db->select("SELECT * FROM vehicles WHERE id = ?", [$vehicle_id]);
if (!$vehicle) {
    header('Location: vehicles_manage.php');
    exit;
}
$vehicle = $vehicle[0];

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['vehicle_image'])) {
    try {
        $is_primary = isset($_POST['is_primary']) ? true : false;
        $image_id = $imageManager->saveVehicleImage($vehicle_id, $_FILES['vehicle_image'], $is_primary);
        
        $message = "Image uploadée avec succès !";
        $messageType = "success";
    } catch (Exception $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// Handle set primary
if (isset($_POST['set_primary'])) {
    $image_id = (int)$_POST['image_id'];
    $imageManager->setPrimaryImage($image_id, $vehicle_id);
    $message = "Image définie comme principale";
    $messageType = "success";
}

// Handle delete
if (isset($_POST['delete_image'])) {
    $image_id = (int)$_POST['image_id'];
    $imageManager->deleteImage($image_id);
    $message = "Image supprimée";
    $messageType = "success";
}

// Get all images
$images = $imageManager->getVehicleImages($vehicle_id);

include_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Gestion des Images</h1>
            <p class="text-gray-600 mt-1">
                <?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')') ?>
            </p>
        </div>
        <a href="vehicles_manage.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
            ← Retour
        </a>
    </div>

    <?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 border-l-4 border-green-500 text-green-800' : 'bg-red-50 border-l-4 border-red-500 text-red-800' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- Upload Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">📤 Uploader une Nouvelle Image</h2>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Image du véhicule</label>
                <input type="file" name="vehicle_image" accept="image/*" required
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                <p class="text-xs text-gray-500 mt-1">Formats acceptés: JPG, PNG, GIF, WEBP (max 5MB)</p>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" name="is_primary" id="is_primary" class="h-4 w-4 text-orange-600 rounded">
                <label for="is_primary" class="ml-2 text-sm text-gray-700">
                    Définir comme image principale
                </label>
            </div>
            
            <button type="submit" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700 transition">
                Uploader l'Image
            </button>
        </form>
    </div>

    <!-- Images List -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">
            🖼️ Images du Véhicule (<?= count($images) ?>)
        </h2>
        
        <?php if (empty($images)): ?>
            <div class="text-center py-12 bg-gray-50 rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-gray-600">Aucune image pour ce véhicule</p>
                <p class="text-gray-500 text-sm mt-1">Uploadez la première image ci-dessus</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($images as $image): ?>
                <div class="border rounded-lg p-4 <?= $image['is_primary'] ? 'border-orange-500 bg-orange-50' : 'border-gray-200' ?>">
                    <!-- Image Preview -->
                    <div class="relative mb-3">
                        <img src="<?= $imageManager->getImageUrl($image['id']) ?>" 
                             alt="<?= htmlspecialchars($image['image_name']) ?>"
                             class="w-full h-48 object-cover rounded">
                        
                        <?php if ($image['is_primary']): ?>
                        <span class="absolute top-2 right-2 bg-orange-600 text-white text-xs px-2 py-1 rounded">
                            ⭐ Principale
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Image Info -->
                    <div class="mb-3">
                        <p class="text-sm font-medium text-gray-800 truncate" title="<?= htmlspecialchars($image['image_name']) ?>">
                            <?= htmlspecialchars($image['image_name']) ?>
                        </p>
                        <p class="text-xs text-gray-500">
                            <?= number_format($image['image_size'] / 1024, 2) ?> KB • 
                            <?= date('d/m/Y H:i', strtotime($image['uploaded_at'])) ?>
                        </p>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex gap-2">
                        <?php if (!$image['is_primary']): ?>
                        <form method="POST" class="flex-1">
                            <input type="hidden" name="image_id" value="<?= $image['id'] ?>">
                            <button type="submit" name="set_primary" 
                                    class="w-full bg-blue-600 text-white text-sm px-3 py-1.5 rounded hover:bg-blue-700 transition">
                                Principale
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <form method="POST" onsubmit="return confirm('Supprimer cette image ?');">
                            <input type="hidden" name="image_id" value="<?= $image['id'] ?>">
                            <button type="submit" name="delete_image"
                                    class="bg-red-600 text-white text-sm px-3 py-1.5 rounded hover:bg-red-700 transition">
                                Supprimer
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Info Box -->
    <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
        <h3 class="font-semibold text-blue-800 mb-2">ℹ️ Informations</h3>
        <ul class="text-sm text-blue-700 space-y-1">
            <li>• Les images sont stockées en base de données (BLOB)</li>
            <li>• Une seule image peut être définie comme principale</li>
            <li>• L'image principale s'affiche par défaut sur les listes</li>
            <li>• Taille maximum: 5MB par image</li>
            <li>• Formats acceptés: JPG, PNG, GIF, WEBP</li>
        </ul>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
