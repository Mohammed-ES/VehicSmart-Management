<?php
// Version ultra-simple pour tester
session_save_path(__DIR__ . '/../sessions');
session_set_cookie_params([
    'lifetime' => 7200,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Méthode non autorisée');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    die('Veuillez remplir tous les champs');
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password, role, status FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die('Utilisateur non trouvé');
    }
    
    if (!password_verify($password, $user['password'])) {
        die('Mot de passe incorrect');
    }
    
    if ($user['status'] !== 'active') {
        die('Compte désactivé');
    }
    
    // Créer la session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();
    
    // Redirection
    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
    if ($user['role'] === 'admin') {
        header("Location: {$baseUrl}/VehicSmart/admin/dashboard.php");
    } else {
        header("Location: {$baseUrl}/VehicSmart/client/client_dashboard.php");
    }
    exit;
    
} catch (PDOException $e) {
    die('Erreur base de données: ' . $e->getMessage());
}
