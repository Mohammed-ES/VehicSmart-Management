<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// If user is already logged in, redirect
if (isLoggedIn()) {
    $user = getCurrentUser();
    $redirectTo = $user['role'] === 'admin' ? '../admin/dashboard.php' : '../client_dashboard.php';
    redirect($redirectTo);
}

// Check if reset token exists in session
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_verified'])) {
    setFlashMessage('error', 'Invalid request. Please try the password reset process again.');
    redirect('forgot_password.php');
}

// Set page title
$pageTitle = 'Reset Password';

// Include header
include_once 'includes/header.php';
?>
        <div class="px-4 py-2 <?= $flash['type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
            <?= $flash['message'] ?>
        </div>
    <?php endif; ?>

<div class="px-8 py-8">
    <div class="text-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2 fade-in">Reset Your Password</h1>
        <p class="text-gray-600 mb-6 fade-in">Choose a new secure password for your account</p>
    </div>
    
    <div class="p-5 bg-gray-50 rounded-lg mb-6 fade-in border border-gray-200">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-gray-700">
                    <strong class="font-medium">Strong password tips:</strong>
                </p>
                <ul class="mt-1 list-disc list-inside text-xs text-gray-600 space-y-1">
                    <li>At least 8 characters long</li>
                    <li>Include uppercase and lowercase letters</li>
                    <li>Include at least one number and special character</li>
                    <li>Avoid using personal information</li>
                </ul>
            </div>
        </div>
    </div>
    
    <form method="POST" action="process_reset_password.php" data-form-type="reset-password" class="space-y-6">
        <!-- Password Field -->
        <div class="fade-in">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
            <div class="relative rounded-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input type="password" name="password" id="password" 
                    class="block w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-200"
                    placeholder="••••••••" required minlength="8">
            </div>
            <p id="password-error" class="mt-1 text-sm text-red-600 hidden"></p>
        </div>

        <!-- Confirm Password Field -->
        <div class="fade-in">
            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
            <div class="relative rounded-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input type="password" name="confirm_password" id="confirm_password" 
                    class="block w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-200"
                    placeholder="••••••••" required minlength="8">
            </div>
            <p id="confirm_password-error" class="mt-1 text-sm text-red-600 hidden"></p>
        </div>

        <!-- Submit Button -->
        <div class="fade-in pt-2">
            <button type="submit" 
                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-base font-medium text-white bg-accent hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent transition-all duration-300 transform hover:scale-[1.02]">
                Reset Password
            </button>
        </div>
    </form>
    
    <!-- Back to Login Link -->
    <div class="text-center mt-8 fade-in">
        <a href="login.php" class="text-sm text-gray-500 hover:text-accent transition-colors flex items-center justify-center">
            <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to login
        </a>
    </div>
</div>
</main>

<script src="../assets/js/auth.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Focus the first input when the page loads
        document.getElementById('password')?.focus();
        
        // Form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirm_password');
                let isValid = true;
                
                // Validate password
                if (password.value.length < 8) {
                    const passwordError = document.getElementById('password-error');
                    passwordError.textContent = 'Password must be at least 8 characters';
                    passwordError.classList.remove('hidden');
                    password.classList.add('border-red-500');
                    isValid = false;
                }
                
                // Check if passwords match
                if (password.value !== confirmPassword.value) {
                    const confirmError = document.getElementById('confirm_password-error');
                    confirmError.textContent = 'Passwords do not match';
                    confirmError.classList.remove('hidden');
                    confirmPassword.classList.add('border-red-500');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
        }
    });
</script>

</script>
</body>
</html>
