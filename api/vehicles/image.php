<?php
/**
 * API Endpoint: Servir les images depuis la base de données
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/ImageManager.php';

// Récupérer l'ID de l'image
$image_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$image_id) {
    header('HTTP/1.0 400 Bad Request');
    exit('Image ID required');
}

try {
    $imageManager = new ImageManager();
    $image = $imageManager->getImage($image_id);
    
    if (!$image) {
        header('HTTP/1.0 404 Not Found');
        exit('Image not found');
    }
    
    // Définir les headers pour l'image
    header('Content-Type: ' . $image['image_type']);
    header('Content-Length: ' . $image['image_size']);
    header('Cache-Control: public, max-age=86400'); // Cache 1 jour
    header('Content-Disposition: inline; filename="' . $image['image_name'] . '"');
    
    // Envoyer l'image
    echo $image['image_data'];
    
} catch (Exception $e) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Error: ' . $e->getMessage());
}
