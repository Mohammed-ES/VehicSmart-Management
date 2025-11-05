<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/helpers.php';

// Récupérer les messages flash si disponibles
$flashMessages = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Authentication' ?> - VehicSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-dark': '#1c1c1e',
                        'primary-light': '#f4f4f5',
                        'accent': '#ff7849',
                        'neutral': '#a1a1aa'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <style>
        .scroll-reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        .scroll-reveal.revealed {
            opacity: 1;
            transform: translateY(0);
        }
        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        body {
            background-image: url('../assets/images/auth-bg.jpg');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-primary-dark min-h-screen flex flex-col">
    <!-- Navigation -->
    <nav class="bg-primary-dark fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex-shrink-0">
                    <a href="../index.php" class="text-white text-2xl font-bold hover:text-accent transition-colors">VehicSmart</a>
                </div>

            </div>
        </div>
    </nav>

    <main class="flex-grow flex items-center justify-center py-24 px-4">
        <div class="max-w-md w-full bg-white rounded-xl shadow-xl overflow-hidden auth-card fade-in">
            <?php if (!empty($flashMessages)): ?>
                <?php foreach ($flashMessages as $flash): ?>
                    <div class="p-4 <?= $flash['type'] === 'error' ? 'bg-red-50 text-red-700' : ($flash['type'] === 'success' ? 'bg-green-50 text-green-700' : 'bg-blue-50 text-blue-700') ?> rounded-t-xl">
                        <p class="font-medium"><?= e($flash['message']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
