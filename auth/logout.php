<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';

// Déconnexion sécurisée
logout();

// Redirection vers la page de login
header('Location: /VehicSmart/auth/login.php');
exit;