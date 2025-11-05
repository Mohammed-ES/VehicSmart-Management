<?php
/**
 * Database Configuration Example
 * 
 * INSTRUCTIONS:
 * 1. Copier ce fichier vers 'config.php'
 * 2. Modifier les valeurs selon votre environnement
 * 3. Ne JAMAIS commiter config.php dans Git
 * 
 * @package VehicSmart
 */

// Database Configuration
define('DB_HOST', 'localhost');      // Hôte de la base de données
define('DB_PORT', '3306');           // Port MySQL (3306 par défaut, 3307 pour certains XAMPP)
define('DB_NAME', 'vehicsmart');     // Nom de la base de données
define('DB_USER', 'root');           // Nom d'utilisateur
define('DB_PASS', '');               // Mot de passe

// Application Settings
define('APP_NAME', 'VehicSmart');
define('APP_URL', 'http://localhost/VehicSmart');
define('APP_ENV', 'development');    // development, production

// Security Settings
define('SESSION_LIFETIME', 3600);    // Durée de session en secondes (1 heure)
define('HASH_ALGO', 'sha256');       // Algorithme de hachage

// Email Configuration (optionnel)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-password');
define('MAIL_FROM', 'noreply@vehicsmart.com');
define('MAIL_FROM_NAME', 'VehicSmart');

// Upload Settings
define('MAX_UPLOAD_SIZE', 5242880);  // 5 MB en octets
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']);

// Timezone
date_default_timezone_set('America/New_York');

// Error Reporting (désactiver en production)
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
