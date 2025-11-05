<?php
/**
 * Exécution de la Migration: Système d'Images BLOB
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is admin
requireAdmin();

$pageTitle = 'Migration Images BLOB';

$results = [];
$migration_complete = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    $db = Database::getInstance();
    
    try {
        // Créer la table vehicle_images_blob
        $sql1 = "CREATE TABLE IF NOT EXISTS `vehicle_images_blob` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `vehicle_id` int(11) NOT NULL,
          `image_data` LONGBLOB NOT NULL,
          `image_name` varchar(255) NOT NULL,
          `image_type` varchar(50) NOT NULL,
          `image_size` int(11) NOT NULL,
          `is_primary` tinyint(1) DEFAULT 0,
          `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `idx_vehicle_images_vehicle_id` (`vehicle_id`),
          KEY `idx_vehicle_images_primary` (`vehicle_id`, `is_primary`),
          CONSTRAINT `vehicle_images_blob_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->query($sql1);
        $results[] = ['type' => 'success', 'message' => 'Table vehicle_images_blob créée'];
        
        // Ajouter les colonnes placeholder
        try {
            $sql2 = "ALTER TABLE `vehicles` ADD COLUMN `placeholder_image` LONGBLOB DEFAULT NULL";
            $db->query($sql2);
            $results[] = ['type' => 'success', 'message' => 'Colonne placeholder_image ajoutée'];
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                $results[] = ['type' => 'info', 'message' => 'Colonne placeholder_image existe déjà'];
            } else {
                throw $e;
            }
        }
        
        try {
            $sql3 = "ALTER TABLE `vehicles` ADD COLUMN `placeholder_image_type` varchar(50) DEFAULT NULL";
            $db->query($sql3);
            $results[] = ['type' => 'success', 'message' => 'Colonne placeholder_image_type ajoutée'];
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                $results[] = ['type' => 'info', 'message' => 'Colonne placeholder_image_type existe déjà'];
            } else {
                throw $e;
            }
        }
        
        $migration_complete = true;
        $results[] = ['type' => 'success', 'message' => '✅ Migration terminée avec succès !'];
        
    } catch (Exception $e) {
        $results[] = ['type' => 'error', 'message' => 'Erreur: ' . $e->getMessage()];
    }
}

include_once 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">🗄️ Migration: Système d'Images BLOB</h1>
        <p class="text-gray-600 mt-2">Migration du système de stockage d'images dans la base de données</p>
    </div>

    <?php if ($migration_complete): ?>
        <!-- Résultats -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Migration Terminée
            </h2>
            
            <?php foreach ($results as $result): ?>
                <div class="mb-3 p-4 rounded-lg <?php
                    if ($result['type'] === 'success') echo 'bg-green-50 border-l-4 border-green-500';
                    elseif ($result['type'] === 'error') echo 'bg-red-50 border-l-4 border-red-500';
                    else echo 'bg-blue-50 border-l-4 border-blue-500';
                ?>">
                    <p class="<?php
                        if ($result['type'] === 'success') echo 'text-green-800';
                        elseif ($result['type'] === 'error') echo 'text-red-800';
                        else echo 'text-blue-800';
                    ?>">
                        <?= htmlspecialchars($result['message']) ?>
                    </p>
                </div>
            <?php endforeach; ?>
            
            <div class="mt-6 p-4 bg-orange-50 rounded-lg border-l-4 border-orange-500">
                <h3 class="font-semibold text-orange-800 mb-2">📋 Prochaines Étapes</h3>
                <ul class="list-disc list-inside text-orange-700 space-y-1">
                    <li>Aller dans Gestion des Véhicules</li>
                    <li>Cliquer sur "Gérer Images" pour chaque véhicule</li>
                    <li>Uploader les images des véhicules</li>
                    <li>Définir une image principale par véhicule</li>
                </ul>
            </div>
            
            <div class="mt-6 flex gap-3">
                <a href="vehicles_manage.php" class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition">
                    Gérer les Véhicules
                </a>
                <a href="dashboard.php" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                    Dashboard
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Formulaire de confirmation -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800">📊 Détails de la Migration</h2>
            
            <div class="mb-6">
                <h3 class="font-semibold text-gray-800 mb-2">Cette migration va créer:</h3>
                <ul class="list-disc list-inside text-gray-700 space-y-2">
                    <li>
                        <strong>Table vehicle_images_blob</strong>
                        <p class="ml-6 text-sm text-gray-600">Stockage des images en BLOB avec métadonnées</p>
                    </li>
                    <li>
                        <strong>Colonne placeholder_image</strong>
                        <p class="ml-6 text-sm text-gray-600">Image placeholder/illustration par véhicule</p>
                    </li>
                    <li>
                        <strong>Colonne placeholder_image_type</strong>
                        <p class="ml-6 text-sm text-gray-600">Type MIME du placeholder</p>
                    </li>
                </ul>
            </div>
            
            <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4">
                <h3 class="font-semibold text-blue-800 mb-2">✨ Avantages du Système BLOB</h3>
                <ul class="list-disc list-inside text-blue-700 space-y-1 text-sm">
                    <li>Images stockées directement en base de données</li>
                    <li>Pas de dossier uploads/ à gérer</li>
                    <li>Sauvegarde simplifiée (tout dans la DB)</li>
                    <li>Sécurité améliorée (contrôle d'accès centralisé)</li>
                    <li>Pas de problèmes de chemins relatifs</li>
                    <li>Gestion d'image principale automatique</li>
                </ul>
            </div>
            
            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 p-4">
                <h3 class="font-semibold text-yellow-800 mb-2">⚠️ Important</h3>
                <p class="text-yellow-700 text-sm">
                    Cette migration est sûre et ne supprime aucune donnée existante. 
                    Les anciennes images dans le dossier uploads/ resteront intactes.
                </p>
            </div>
        </div>

        <form method="POST" class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-semibold mb-4 text-gray-800">Confirmer la Migration</h3>
            
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" required class="mr-2 h-5 w-5 text-orange-600">
                    <span class="text-gray-700">
                        Je comprends que cette migration va créer de nouvelles tables et colonnes
                    </span>
                </label>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" name="run_migration" class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition font-semibold">
                    🚀 Exécuter la Migration
                </button>
                <a href="dashboard.php" class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 transition">
                    Annuler
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
