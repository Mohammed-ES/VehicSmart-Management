<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// If user is already logged in, redirect
if (isLoggedIn()) {
    $user = getCurrentUser();
    $redirectTo = $user['role'] === 'admin' ? '../admin/dashboard.php' : '../client_dashboard.php';
    redirect($redirectTo);
}

// Set page title
$pageTitle = 'Create a VehicSmart Account';

// Include header
include_once 'includes/header.php';
?>

<div class="px-8 py-8">
    <div class="text-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2 fade-in">Create an Account</h1>
        <p class="text-gray-600 fade-in">Join VehicSmart today</p>
    </div>
    
    <form method="POST" action="process_register.php" data-form-type="register" class="space-y-5">
        <!-- Full Name Field -->
        <div class="fade-in">
            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
            <div class="relative rounded-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <input type="text" name="full_name" id="full_name" 
                    class="block w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-200"
                    placeholder="John Doe" required>
            </div>
            <p id="full_name-error" class="mt-1 text-sm text-red-600 hidden"></p>
        </div>

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

        <!-- Phone Field -->
        <div class="fade-in">
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone (Optional)</label>
            <div class="relative rounded-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </div>
                <input type="tel" name="phone" id="phone" 
                    class="block w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-200"
                    placeholder="(123) 456-7890">
            </div>
            <p id="phone-error" class="mt-1 text-sm text-red-600 hidden"></p>
        </div>

        <!-- Password Field -->
        <div class="fade-in">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <div class="relative rounded-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input type="password" name="password" id="password" 
                    class="block w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-200"
                    placeholder="••••••••" required>
            </div>
            <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
            <p id="password-error" class="mt-1 text-sm text-red-600 hidden"></p>
        </div>

        <!-- Confirm Password Field -->
        <div class="fade-in">
            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
            <div class="relative rounded-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input type="password" name="confirm_password" id="confirm_password" 
                    class="block w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-200"
                    placeholder="••••••••" required>
            </div>
            <p id="confirm_password-error" class="mt-1 text-sm text-red-600 hidden"></p>
        </div>

        <!-- Terms Agreement -->
        <div class="flex items-center fade-in">
            <input id="terms" name="terms" type="checkbox" class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded" required>
            <label for="terms" class="ml-2 block text-sm text-gray-700">
                I agree to the <a href="#" class="text-accent hover:underline">Terms of Service</a> and <a href="#" class="text-accent hover:underline">Privacy Policy</a>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="fade-in pt-2">
            <button type="submit" 
                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-base font-medium text-white bg-accent hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent transition-all duration-300 transform hover:scale-[1.02]">
                Create Account
            </button>
        </div>
    </form>
    
    <!-- Login Link -->
    <div class="text-center mt-6 fade-in">
        <p class="text-sm text-gray-600">
            Already have an account? 
            <a href="login.php" class="font-medium text-accent hover:text-accent/80 transition-colors">
                Sign in
            </a>
        </p>
    </div>
</div>
</main>

<script src="../assets/js/auth.js"></script>
</body>
</html>
