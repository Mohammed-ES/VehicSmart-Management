<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

// Get base URL for assets
$base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url .= "://".$_SERVER['HTTP_HOST'];
$base_url .= dirname($_SERVER['PHP_SELF']);
$base_url = rtrim($base_url, '/includes');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'VehicSmart' ?> - Vehicle Management System</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
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
</head>
<body class="bg-gray-100 font-sans">
    <?php
    // Include client sidebar
    include_once __DIR__ . '/client_sidebar.php';
    ?>
            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-white hover:text-gray-200 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>
            </div>
        </div>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-blue-700 pb-4">
            <a href="<?php echo $base_url; ?>/client_dashboard.php" class="block px-4 py-2 text-white hover:bg-blue-800">Dashboard</a>
            <a href="<?php echo $base_url; ?>/page/vehicles.php" class="block px-4 py-2 text-white hover:bg-blue-800">Vehicles</a>
            <a href="<?php echo $base_url; ?>/page/rentals.php" class="block px-4 py-2 text-white hover:bg-blue-800">My Rentals</a>
            <a href="<?php echo $base_url; ?>/page/messages.php" class="block px-4 py-2 text-white hover:bg-blue-800">Messages</a>
            <a href="<?php echo $base_url; ?>/page/profile.php" class="block px-4 py-2 text-white hover:bg-blue-800">Profile</a>
            <a href="<?php echo $base_url; ?>/page/settings.php" class="block px-4 py-2 text-white hover:bg-blue-800">Settings</a>
            <a href="<?php echo $base_url; ?>/auth/logout.php" class="block px-4 py-2 text-white hover:bg-blue-800">Logout</a>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <div class="container mx-auto px-4 py-8">
