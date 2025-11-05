<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// If user is already logged in, redirect
if (isLoggedIn()) {
    $user = getCurrentUser();
    $redirectTo = $user['role'] === 'admin' ? '../admin/dashboard.php' : '../client_dashboard.php';
    redirect($redirectTo);
}

// Set page title
$pageTitle = 'Forgot Password';

// Include header
include_once 'includes/header.php';
?>

<div class="px-8 py-8">
    <div class="text-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2 fade-in">Forgot Password?</h1>
        <p class="text-gray-600 mb-6 fade-in">No worries, we'll help you reset it</p>
    </div>
    
    <div class="p-6 bg-gray-50 rounded-lg mb-6 fade-in">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-gray-700">
                    Enter your email address and we'll send you a 6-digit code to reset your password.
                </p>
            </div>
        </div>
    </div>
    
    <form method="POST" action="process_forgot_password.php" data-form-type="forgot-password" class="space-y-6">
        <!-- Email Field -->
        <div class="fade-in">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
            <div class="relative rounded-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </div>
                <input type="email" name="email" id="email" 
                    class="block w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-200"
                    placeholder="your@email.com" required>
            </div>
            <p id="email-error" class="mt-1 text-sm text-red-600 hidden"></p>
        </div>

        <!-- Submit Button -->
        <div class="fade-in pt-2">
            <button type="submit" 
                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-base font-medium text-white bg-accent hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent transition-all duration-300 transform hover:scale-[1.02]">
                Send Reset Code
            </button>
        </div>
    </form>
    
    <!-- Back to Login Link -->
    <div class="text-center mt-8 fade-in">
        <p class="text-sm text-gray-600">
            Remember your password? 
            <a href="login.php" class="font-medium text-accent hover:text-accent/80 transition-colors">
                Back to login
            </a>
        </p>
    </div>
</div>
</main>

<script src="../assets/js/auth.js"></script>
</body>
</html>
