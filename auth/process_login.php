<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';

// Désactiver temporairement les vérifications pour debug
/*
// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    die('Token de sécurité invalide');
}

// Rate limiting: 5 tentatives max par 5 minutes
if (!rateLimit('login', 5, 300)) {
    die('Trop de tentatives. Veuillez réessayer dans 5 minutes.');
}
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Méthode non autorisée');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    die('Veuillez remplir tous les champs');
}

// Validation simple de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('Email invalide');
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password, role, status FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !verifyPassword($password, $user['password'])) {
        die('Email ou mot de passe incorrect');
    }
    
    if ($user['status'] !== 'active') {
        die('Compte désactivé. Contactez l\'administrateur.');
    }
    
    // Ne pas régénérer l'ID pour éviter les problèmes (temporaire)
    // session_regenerate_id(true);
    
    // Stocker les informations de l'utilisateur en session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    
    // Générer un nouveau token CSRF
    unset($_SESSION['csrf_token']);
    generateCSRFToken();
    
    // Redirection avec URL complète
    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
    $redirect = ($user['role'] === 'admin') ? $baseUrl . '/VehicSmart/admin/dashboard.php' : $baseUrl . '/VehicSmart/client/client_dashboard.php';
    
    // Forcer la redirection immédiate
    header("Location: $redirect");
    exit;
    
    /* Version JSON désactivée
    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie',
        'redirect' => $redirect
    ]);
    */
    
} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    die('Erreur de connexion. Veuillez réessayer.');
}