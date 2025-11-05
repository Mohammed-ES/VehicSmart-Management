<?php
/**
 * Vehicle Gallery - Affiche toutes les images des véhicules
 * 
 * @package VehicSmart
 */

// Include security and session management
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
requireAdmin();

require_once '../config/database.php';
require_once '../config/ImageManager.php';

$db = Database::getInstance();
$imageManager = new ImageManager($db);

// Récupérer tous les véhicules avec leurs images
$vehicles = $db->select("SELECT * FROM vehicles ORDER BY brand ASC, model ASC");

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galerie des Véhicules - VehicSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .image-card {
            transition: all 0.3s ease;
        }
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .badge-primary {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-images text-blue-600"></i>
                        Galerie des Véhicules
                    </h1>
                    <p class="text-gray-600">Visualisez toutes les images de vos véhicules</p>
                </div>
                <div class="space-x-2">
                    <a href="import_vehicle_illustrations.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-cloud-download-alt mr-2"></i>Importer Images
                    </a>
                    <a href="vehicles_manage.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <?php
            $total_vehicles = count($vehicles);
            $vehicles_with_images = 0;
            $total_images = 0;
            
            foreach ($vehicles as $vehicle) {
                $images = $imageManager->getVehicleImages($vehicle['id']);
                if (!empty($images)) {
                    $vehicles_with_images++;
                    $total_images += count($images);
                }
            }
            
            $vehicles_without_images = $total_vehicles - $vehicles_with_images;
            $percentage_with_images = $total_vehicles > 0 ? round(($vehicles_with_images / $total_vehicles) * 100, 1) : 0;
            ?>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                        <i class="fas fa-car text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Véhicules</p>
                        <p class="text-2xl font-bold"><?php echo $total_vehicles; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Avec Images</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $vehicles_with_images; ?></p>
                        <p class="text-xs text-gray-500"><?php echo $percentage_with_images; ?>%</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                        <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Sans Images</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo $vehicles_without_images; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 rounded-full p-3">
                        <i class="fas fa-images text-purple-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Images</p>
                        <p class="text-2xl font-bold text-purple-600"><?php echo $total_images; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Galerie des véhicules -->
        <div class="space-y-8">
            <?php foreach ($vehicles as $vehicle): ?>
            <?php
            $images = $imageManager->getVehicleImages($vehicle['id']);
            $primary_image = $imageManager->getPrimaryImage($vehicle['id']);
            $image_count = count($images);
            ?>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- En-tête du véhicule -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center text-white">
                            <i class="fas fa-car text-2xl mr-3"></i>
                            <div>
                                <h2 class="text-xl font-bold">
                                    <?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>
                                </h2>
                                <p class="text-blue-100 text-sm">
                                    <?php echo htmlspecialchars($vehicle['year']); ?> • 
                                    <?php echo htmlspecialchars($vehicle['color']); ?> • 
                                    $<?php echo number_format($vehicle['daily_rate'], 2); ?>/jour
                                </p>
                            </div>
                        </div>
                        <div class="text-right text-white">
                            <p class="text-sm opacity-75">Images:</p>
                            <p class="text-2xl font-bold"><?php echo $image_count; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="p-6">
                    <?php if (empty($images)): ?>
                        <!-- Aucune image -->
                        <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                            <i class="fas fa-image-slash text-gray-400 text-5xl mb-4"></i>
                            <p class="text-gray-600 font-medium">Aucune image pour ce véhicule</p>
                            <a href="import_vehicle_illustrations.php" class="text-blue-600 hover:text-blue-700 text-sm mt-2 inline-block">
                                <i class="fas fa-plus-circle mr-1"></i>Importer des images
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Grille d'images -->
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            <?php foreach ($images as $image): ?>
                            <div class="image-card relative group">
                                <!-- Badge image principale -->
                                <?php if ($image['is_primary']): ?>
                                <div class="absolute top-2 left-2 z-10 badge-primary">
                                    <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full font-semibold shadow-lg">
                                        <i class="fas fa-star mr-1"></i>Principale
                                    </span>
                                </div>
                                <?php endif; ?>

                                <!-- Image -->
                                <div class="aspect-w-4 aspect-h-3 bg-gray-200 rounded-lg overflow-hidden">
                                    <img 
                                        src="<?php echo $imageManager->getImageUrl($image['id']); ?>" 
                                        alt="<?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>"
                                        class="w-full h-48 object-cover rounded-lg cursor-pointer"
                                        onclick="openImageModal(this.src, '<?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>')"
                                    >
                                </div>

                                <!-- Infos de l'image -->
                                <div class="mt-2 text-center">
                                    <p class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($image['image_name']); ?>
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        <?php echo round($image['image_size'] / 1024, 1); ?> KB
                                    </p>
                                </div>

                                <!-- Actions (visibles au survol) -->
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-300 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100">
                                    <div class="space-x-2">
                                        <button 
                                            onclick="openImageModal('<?php echo $imageManager->getImageUrl($image['id']); ?>', '<?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>')"
                                            class="bg-white text-gray-800 px-3 py-2 rounded-full hover:bg-gray-100 transition"
                                            title="Voir en grand"
                                        >
                                            <i class="fas fa-search-plus"></i>
                                        </button>
                                        <a 
                                            href="vehicle_images.php?vehicle_id=<?php echo $vehicle['id']; ?>" 
                                            class="bg-blue-600 text-white px-3 py-2 rounded-full hover:bg-blue-700 transition inline-block"
                                            title="Gérer"
                                        >
                                            <i class="fas fa-cog"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Actions -->
                        <div class="mt-6 flex items-center justify-between border-t pt-4">
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-info-circle text-blue-600 mr-1"></i>
                                Image principale affichée sur la page de sélection
                            </div>
                            <a 
                                href="vehicle_images.php?vehicle_id=<?php echo $vehicle['id']; ?>" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm"
                            >
                                <i class="fas fa-edit mr-2"></i>Gérer les images
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal pour afficher l'image en grand -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 flex items-center justify-center p-4" onclick="closeImageModal()">
        <div class="relative max-w-5xl w-full">
            <button 
                onclick="closeImageModal()" 
                class="absolute top-4 right-4 text-white bg-black bg-opacity-50 rounded-full w-10 h-10 flex items-center justify-center hover:bg-opacity-75 transition z-10"
            >
                <i class="fas fa-times text-xl"></i>
            </button>
            <img id="modalImage" src="" alt="" class="w-full h-auto rounded-lg shadow-2xl">
            <p id="modalTitle" class="text-white text-center mt-4 text-xl font-semibold"></p>
        </div>
    </div>

    <script>
        function openImageModal(src, title) {
            document.getElementById('modalImage').src = src;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('imageModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Fermer avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</body>
</html>
