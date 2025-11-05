<?php
/**
 * Import Real Vehicle Illustrations
 * 
 * Ce script télécharge des illustrations de véhicules depuis une API publique
 * et les sauvegarde dans la base de données (système BLOB)
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
$results = [];
$stats = [
    'success' => 0,
    'failed' => 0,
    'skipped' => 0,
    'total' => 0
];

/**
 * Télécharge une image depuis une URL et retourne les données
 */
function downloadImage($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'VehicSmart/1.0');
    
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $data !== false) {
        return $data;
    }
    
    return false;
}

/**
 * Génère une URL d'image de véhicule depuis Unsplash
 */
function getUnsplashCarImage($brand, $model, $color) {
    // Unsplash offre des images gratuites de haute qualité
    $query = urlencode("$brand $model car automobile");
    $width = 800;
    $height = 600;
    
    // URL de l'API Unsplash (utilise la source publique)
    // Note: Pour un usage en production, obtenir une clé API gratuite sur unsplash.com/developers
    $url = "https://source.unsplash.com/{$width}x{$height}/?{$query}";
    
    return $url;
}

/**
 * Génère une URL d'image depuis Placeholder.com avec le design du véhicule
 */
function getPlaceholderCarImage($brand, $model, $type = 'sedan') {
    $width = 800;
    $height = 600;
    
    // Couleurs selon le type de véhicule
    $colors = [
        'sedan' => '3B82F6',      // Bleu
        'suv' => '10B981',        // Vert
        'truck' => '6B7280',      // Gris
        'van' => 'F59E0B',        // Orange
        'coupe' => 'EF4444',      // Rouge
        'convertible' => '8B5CF6', // Violet
        'hatchback' => '06B6D4',  // Cyan
        'wagon' => '84CC16'       // Lime
    ];
    
    $bgColor = isset($colors[$type]) ? $colors[$type] : '3B82F6';
    $text = urlencode("$brand $model");
    
    return "https://via.placeholder.com/{$width}x{$height}/{$bgColor}/ffffff?text={$text}";
}

/**
 * Crée une image SVG personnalisée pour le véhicule
 */
function createVehicleSVGImage($brand, $model, $color, $type = 'sedan') {
    // Mapping des couleurs
    $colorMap = [
        'bleu' => '#3B82F6', 'bleu métallique' => '#1E40AF',
        'noir' => '#1F2937', 'blanc' => '#F3F4F6',
        'rouge' => '#EF4444', 'rouge vif' => '#DC2626',
        'gris' => '#6B7280', 'gris métallique' => '#4B5563',
        'argent' => '#9CA3AF', 'vert' => '#10B981',
        'jaune' => '#F59E0B', 'orange' => '#F97316',
        'marron' => '#92400E', 'beige' => '#D6D3D1'
    ];
    
    $carColor = $colorMap[strtolower($color)] ?? '#3B82F6';
    
    // Type de véhicule détermine la forme
    $carShapes = [
        'sedan' => [
            'viewBox' => '0 0 800 600',
            'body' => 'M150,350 L250,250 L550,250 L650,350 L650,450 L150,450 Z',
            'roof' => 'M250,250 L300,200 L500,200 L550,250 Z',
            'window_front' => 'M260,260 L290,220 L350,220 L380,260 Z',
            'window_rear' => 'M420,260 L470,220 L510,220 L540,260 Z'
        ],
        'suv' => [
            'viewBox' => '0 0 800 600',
            'body' => 'M100,300 L200,200 L600,200 L700,300 L700,480 L100,480 Z',
            'roof' => 'M200,200 L250,150 L550,150 L600,200 Z',
            'window_front' => 'M220,220 L240,170 L350,170 L370,220 Z',
            'window_rear' => 'M430,220 L450,170 L560,170 L580,220 Z'
        ],
        'truck' => [
            'viewBox' => '0 0 800 600',
            'body' => 'M100,320 L100,480 L700,480 L700,320 L600,320 L600,250 L300,250 L200,320 Z',
            'roof' => 'M200,250 L250,200 L550,200 L600,250 Z',
            'window_front' => 'M220,270 L240,220 L340,220 L360,270 Z',
            'window_rear' => 'M420,270 L440,220 L540,220 L560,270 Z'
        ]
    ];
    
    $shape = $carShapes[$type] ?? $carShapes['sedan'];
    
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="{$shape['viewBox']}" width="800" height="600">
    <defs>
        <linearGradient id="carGradient" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" style="stop-color:{$carColor};stop-opacity:1" />
            <stop offset="100%" style="stop-color:{$carColor};stop-opacity:0.7" />
        </linearGradient>
        <linearGradient id="glassGradient" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" style="stop-color:#E0F2FE;stop-opacity:0.6" />
            <stop offset="100%" style="stop-color:#0369A1;stop-opacity:0.3" />
        </linearGradient>
    </defs>
    
    <!-- Background -->
    <rect width="800" height="600" fill="#F8FAFC"/>
    <rect y="450" width="800" height="150" fill="#E2E8F0"/>
    
    <!-- Shadow -->
    <ellipse cx="400" cy="500" rx="350" ry="30" fill="#CBD5E1" opacity="0.5"/>
    
    <!-- Car Body -->
    <path d="{$shape['body']}" fill="url(#carGradient)" stroke="#1E293B" stroke-width="3"/>
    
    <!-- Car Roof -->
    <path d="{$shape['roof']}" fill="url(#carGradient)" stroke="#1E293B" stroke-width="3"/>
    
    <!-- Windows -->
    <path d="{$shape['window_front']}" fill="url(#glassGradient)" stroke="#1E293B" stroke-width="2"/>
    <path d="{$shape['window_rear']}" fill="url(#glassGradient)" stroke="#1E293B" stroke-width="2"/>
    
    <!-- Wheels -->
    <circle cx="220" cy="450" r="50" fill="#1F2937" stroke="#0F172A" stroke-width="3"/>
    <circle cx="220" cy="450" r="30" fill="#6B7280"/>
    <circle cx="580" cy="450" r="50" fill="#1F2937" stroke="#0F172A" stroke-width="3"/>
    <circle cx="580" cy="450" r="30" fill="#6B7280"/>
    
    <!-- Headlights -->
    <rect x="120" y="370" width="30" height="20" rx="5" fill="#FEF08A" stroke="#CA8A04" stroke-width="2"/>
    
    <!-- Taillights -->
    <rect x="650" y="370" width="30" height="20" rx="5" fill="#FCA5A5" stroke="#DC2626" stroke-width="2"/>
    
    <!-- Brand and Model Text -->
    <text x="400" y="550" font-family="Arial, sans-serif" font-size="32" font-weight="bold" text-anchor="middle" fill="#1E293B">
        $brand $model
    </text>
    
    <!-- Type Badge -->
    <rect x="320" y="100" width="160" height="40" rx="20" fill="white" stroke="#E2E8F0" stroke-width="2"/>
    <text x="400" y="127" font-family="Arial, sans-serif" font-size="20" font-weight="600" text-anchor="middle" fill="#64748B">
        $type
    </text>
</svg>
SVG;
    
    return $svg;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    // Récupérer tous les véhicules
    $vehicles = $db->select("SELECT * FROM vehicles ORDER BY id ASC");
    $stats['total'] = count($vehicles);
    
    foreach ($vehicles as $vehicle) {
        $vehicle_id = $vehicle['id'];
        $brand = $vehicle['brand'];
        $model = $vehicle['model'];
        $color = $vehicle['color'];
        $type = strtolower($vehicle['type'] ?? 'sedan');
        
        // Vérifier si le véhicule a déjà des images
        $existing_images = $imageManager->getVehicleImages($vehicle_id);
        if (!empty($existing_images)) {
            $results[] = [
                'vehicle' => "$brand $model",
                'status' => 'skipped',
                'message' => 'Véhicule a déjà des images'
            ];
            $stats['skipped']++;
            continue;
        }
        
        try {
            // Méthode 1: Créer une illustration SVG personnalisée
            if (isset($_POST['use_svg']) && $_POST['use_svg'] === '1') {
                $svg_content = createVehicleSVGImage($brand, $model, $color, $type);
                
                // Créer un fichier temporaire pour le SVG
                $temp_file = tempnam(sys_get_temp_dir(), 'vehicle_svg_');
                file_put_contents($temp_file, $svg_content);
                
                // Simuler un fichier uploadé
                $file_data = [
                    'name' => "{$brand}_{$model}.svg",
                    'type' => 'image/svg+xml',
                    'tmp_name' => $temp_file,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($svg_content)
                ];
                
                $image_id = $imageManager->saveVehicleImage($vehicle_id, $file_data, true);
                
                // Nettoyer le fichier temporaire
                @unlink($temp_file);
                
                $results[] = [
                    'vehicle' => "$brand $model",
                    'status' => 'success',
                    'message' => 'Illustration SVG créée et sauvegardée'
                ];
                $stats['success']++;
            }
            // Méthode 2: Télécharger depuis Placeholder.com
            elseif (isset($_POST['use_placeholder']) && $_POST['use_placeholder'] === '1') {
                $image_url = getPlaceholderCarImage($brand, $model, $type);
                $image_data = downloadImage($image_url);
                
                if ($image_data !== false) {
                    // Créer un fichier temporaire
                    $temp_file = tempnam(sys_get_temp_dir(), 'vehicle_img_');
                    file_put_contents($temp_file, $image_data);
                    
                    $file_data = [
                        'name' => "{$brand}_{$model}.jpg",
                        'type' => 'image/jpeg',
                        'tmp_name' => $temp_file,
                        'error' => UPLOAD_ERR_OK,
                        'size' => strlen($image_data)
                    ];
                    
                    $image_id = $imageManager->saveVehicleImage($vehicle_id, $file_data, true);
                    @unlink($temp_file);
                    
                    $results[] = [
                        'vehicle' => "$brand $model",
                        'status' => 'success',
                        'message' => 'Image téléchargée et sauvegardée'
                    ];
                    $stats['success']++;
                } else {
                    throw new Exception("Échec du téléchargement");
                }
            }
            // Méthode 3: Télécharger depuis Unsplash
            elseif (isset($_POST['use_unsplash']) && $_POST['use_unsplash'] === '1') {
                $image_url = getUnsplashCarImage($brand, $model, $color);
                $image_data = downloadImage($image_url);
                
                if ($image_data !== false) {
                    $temp_file = tempnam(sys_get_temp_dir(), 'vehicle_img_');
                    file_put_contents($temp_file, $image_data);
                    
                    $file_data = [
                        'name' => "{$brand}_{$model}.jpg",
                        'type' => 'image/jpeg',
                        'tmp_name' => $temp_file,
                        'error' => UPLOAD_ERR_OK,
                        'size' => strlen($image_data)
                    ];
                    
                    $image_id = $imageManager->saveVehicleImage($vehicle_id, $file_data, true);
                    @unlink($temp_file);
                    
                    $results[] = [
                        'vehicle' => "$brand $model",
                        'status' => 'success',
                        'message' => 'Image Unsplash téléchargée'
                    ];
                    $stats['success']++;
                } else {
                    throw new Exception("Échec du téléchargement Unsplash");
                }
            }
        } catch (Exception $e) {
            $results[] = [
                'vehicle' => "$brand $model",
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
            $stats['failed']++;
        }
    }
}

// Compter les véhicules sans images
$vehicles_without_images = $db->select("
    SELECT v.id, v.brand, v.model, v.type 
    FROM vehicles v
    LEFT JOIN vehicle_images_blob vib ON v.id = vib.vehicle_id
    WHERE vib.id IS NULL
    ORDER BY v.id ASC
");
$count_without_images = count($vehicles_without_images);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import d'Illustrations de Véhicules - VehicSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-images text-blue-600"></i>
                        Import d'Illustrations de Véhicules
                    </h1>
                    <p class="text-gray-600">Téléchargez et sauvegardez des illustrations pour vos véhicules</p>
                </div>
                <a href="vehicles_manage.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-arrow-left mr-2"></i>Retour
                </a>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-full p-3">
                        <i class="fas fa-car text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total Véhicules</p>
                        <p class="text-2xl font-bold"><?php echo $stats['total'] ?: count($db->select("SELECT id FROM vehicles")); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-100 rounded-full p-3">
                        <i class="fas fa-image-slash text-red-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Sans Images</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo $count_without_images; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-full p-3">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Succès</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $stats['success']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Échecs</p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['failed']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire d'import -->
        <?php if ($count_without_images > 0): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                <i class="fas fa-download text-blue-600"></i>
                Importer des Illustrations
            </h2>

            <form method="POST" class="space-y-6">
                <div class="bg-blue-50 border-l-4 border-blue-600 p-4 mb-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                        <div>
                            <p class="font-semibold text-blue-900">Choisissez une méthode d'import:</p>
                            <p class="text-blue-800 text-sm mt-1">Les illustrations seront sauvegardées directement dans la base de données (système BLOB).</p>
                        </div>
                    </div>
                </div>

                <!-- Méthodes d'import -->
                <div class="space-y-4">
                    <!-- SVG personnalisé (recommandé) -->
                    <label class="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <input type="radio" name="method" value="svg" class="mt-1" checked onchange="updateMethod('svg')">
                        <div class="ml-3 flex-1">
                            <div class="flex items-center justify-between">
                                <div class="font-semibold text-gray-900">
                                    <i class="fas fa-vector-square text-purple-600 mr-2"></i>
                                    Illustrations SVG Personnalisées
                                </div>
                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Recommandé</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">
                                Crée des illustrations vectorielles uniques basées sur la marque, modèle, couleur et type du véhicule. 
                                Léger (<5KB), adaptable, et cohérent avec votre design.
                            </p>
                        </div>
                    </label>

                    <!-- Placeholder.com -->
                    <label class="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <input type="radio" name="method" value="placeholder" class="mt-1" onchange="updateMethod('placeholder')">
                        <div class="ml-3 flex-1">
                            <div class="font-semibold text-gray-900">
                                <i class="fas fa-image text-blue-600 mr-2"></i>
                                Images Placeholder.com
                            </div>
                            <p class="text-sm text-gray-600 mt-1">
                                Génère des images placeholder colorées avec le nom du véhicule. Rapide et fiable.
                            </p>
                        </div>
                    </label>

                    <!-- Unsplash -->
                    <label class="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <input type="radio" name="method" value="unsplash" class="mt-1" onchange="updateMethod('unsplash')">
                        <div class="ml-3 flex-1">
                            <div class="font-semibold text-gray-900">
                                <i class="fas fa-camera text-green-600 mr-2"></i>
                                Photos Unsplash
                            </div>
                            <p class="text-sm text-gray-600 mt-1">
                                Télécharge des photos réelles de véhicules depuis Unsplash. Haute qualité mais peut être lent.
                            </p>
                            <p class="text-xs text-yellow-700 mt-1">
                                <i class="fas fa-exclamation-circle"></i> 
                                Note: Les images peuvent ne pas correspondre exactement au véhicule spécifique.
                            </p>
                        </div>
                    </label>
                </div>

                <!-- Hidden inputs pour les méthodes -->
                <input type="hidden" name="use_svg" id="use_svg" value="1">
                <input type="hidden" name="use_placeholder" id="use_placeholder" value="0">
                <input type="hidden" name="use_unsplash" id="use_unsplash" value="0">

                <!-- Véhicules à traiter -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">
                        Véhicules sans images (<?php echo $count_without_images; ?>):
                    </h3>
                    <div class="max-h-60 overflow-y-auto space-y-2">
                        <?php foreach (array_slice($vehicles_without_images, 0, 10) as $vehicle): ?>
                        <div class="flex items-center justify-between bg-white p-3 rounded">
                            <div>
                                <span class="font-medium"><?php echo htmlspecialchars($vehicle['brand']); ?></span>
                                <span class="text-gray-600"><?php echo htmlspecialchars($vehicle['model']); ?></span>
                            </div>
                            <span class="text-xs bg-gray-200 px-2 py-1 rounded"><?php echo htmlspecialchars($vehicle['type']); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if ($count_without_images > 10): ?>
                        <p class="text-sm text-gray-600 text-center">
                            ... et <?php echo $count_without_images - 10; ?> autres véhicules
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Confirmation -->
                <div class="flex items-center">
                    <input type="checkbox" name="confirm" id="confirm" required class="h-4 w-4 text-blue-600">
                    <label for="confirm" class="ml-2 text-sm text-gray-700">
                        Je confirme vouloir importer des illustrations pour tous les véhicules sans images
                    </label>
                </div>

                <!-- Bouton submit -->
                <button type="submit" name="import" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition flex items-center justify-center">
                    <i class="fas fa-cloud-download-alt mr-2"></i>
                    Importer les Illustrations
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="bg-green-50 border-l-4 border-green-600 p-6 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-600 text-3xl mr-4"></i>
                <div>
                    <h3 class="text-lg font-semibold text-green-900">Tous les véhicules ont des images!</h3>
                    <p class="text-green-800">Aucun import nécessaire pour le moment.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Résultats -->
        <?php if (!empty($results)): ?>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                <i class="fas fa-list-check text-blue-600"></i>
                Résultats de l'Import
            </h2>

            <div class="space-y-2">
                <?php foreach ($results as $result): ?>
                <div class="flex items-center justify-between p-3 rounded <?php 
                    echo $result['status'] === 'success' ? 'bg-green-50 border-l-4 border-green-600' : 
                         ($result['status'] === 'failed' ? 'bg-red-50 border-l-4 border-red-600' : 
                          'bg-yellow-50 border-l-4 border-yellow-600'); 
                ?>">
                    <div class="flex items-center">
                        <i class="fas <?php 
                            echo $result['status'] === 'success' ? 'fa-check-circle text-green-600' : 
                                 ($result['status'] === 'failed' ? 'fa-times-circle text-red-600' : 
                                  'fa-info-circle text-yellow-600'); 
                        ?> mr-3"></i>
                        <div>
                            <span class="font-medium"><?php echo htmlspecialchars($result['vehicle']); ?></span>
                            <span class="text-sm text-gray-600 ml-2">- <?php echo htmlspecialchars($result['message']); ?></span>
                        </div>
                    </div>
                    <span class="text-xs uppercase font-semibold <?php 
                        echo $result['status'] === 'success' ? 'text-green-700' : 
                             ($result['status'] === 'failed' ? 'text-red-700' : 'text-yellow-700'); 
                    ?>">
                        <?php echo $result['status']; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Résumé -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-3xl font-bold text-green-600"><?php echo $stats['success']; ?></p>
                        <p class="text-sm text-gray-600">Réussites</p>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-red-600"><?php echo $stats['failed']; ?></p>
                        <p class="text-sm text-gray-600">Échecs</p>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-yellow-600"><?php echo $stats['skipped']; ?></p>
                        <p class="text-sm text-gray-600">Ignorés</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function updateMethod(method) {
            document.getElementById('use_svg').value = method === 'svg' ? '1' : '0';
            document.getElementById('use_placeholder').value = method === 'placeholder' ? '1' : '0';
            document.getElementById('use_unsplash').value = method === 'unsplash' ? '1' : '0';
        }
    </script>
</body>
</html>
