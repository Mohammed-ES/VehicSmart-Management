<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';

header('Content-Type: application/json');

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
    exit;
}

// Rate limiting
if (!rateLimit('register', 3, 600)) {
    echo json_encode(['success' => false, 'message' => 'Trop de tentatives. Veuillez réessayer plus tard.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$first_name = cleanString($_POST['first_name'] ?? '');
$last_name = cleanString($_POST['last_name'] ?? '');
$email = cleanString($_POST['email'] ?? '');
$phone = cleanString($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validations
if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit;
}

if (!validateEmail($email)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}

if (!empty($phone) && !validatePhone($phone)) {
    echo json_encode(['success' => false, 'message' => 'Numéro de téléphone invalide']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères']);
    exit;
}

if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas']);
    exit;
}

try {
    $pdo = getPDO();
    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé']);
        exit;
    }
    
    // Créer l'utilisateur
    $hashed_password = hashPassword($password);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (first_name, last_name, email, phone, password, role, status, email_verified, created_at)
        VALUES (?, ?, ?, ?, ?, 'client', 'active', 0, NOW())
    ");
    
    $stmt->execute([$first_name, $last_name, $email, $phone, $hashed_password]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Inscription réussie! Vous pouvez maintenant vous connecter.',
        'redirect' => '/VehicSmart/auth/login.php'
    ]);
    
} catch (PDOException $e) {
    error_log("Register error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'inscription. Veuillez réessayer.']);
}