<?php
/**
 * Header component for the client dashboard
 * 
 * @package VehicSmart
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Get current page for highlighting active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Client Dashboard' ?> - VehicSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-dark': '#1c1c1e',
                        'primary-light': '#f4f4f5',
                        'accent': '#ff7849',
                        'neutral': '#a1a1aa'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script src="/VehicSmart/client/js/client-modals.js"></script>
</head>
<body class="bg-gray-100 font-sans">
    <?php 
    // Include client sidebar
    include_once 'client_sidebar.php'; 
    ?>

    <!-- Main Content Container -->
    <div class="lg:pl-64 min-h-screen">
        <!-- Page content goes here -->
        <div class="p-4"><?php // Page content will go here ?>
