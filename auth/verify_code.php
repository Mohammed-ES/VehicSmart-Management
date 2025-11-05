<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// If user is already logged in, redirect
if (isLoggedIn()) {
    $user = getCurrentUser();
    $redirectTo = $user['role'] === 'admin' ? '../admin/dashboard.php' : '../client_dashboard.php';
    redirect($redirectTo);
}

// Check if email exists in session
if (!isset($_SESSION['reset_email'])) {
    setFlashMessage('error', 'Invalid request. Please try the password reset process again.');
    redirect('forgot_password.php');
}

$email = $_SESSION['reset_email'];

// Set page title
$pageTitle = 'Verify OTP Code';

// Include header
include_once 'includes/header.php';
?>

<div class="px-8 py-8">
    <div class="text-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2 fade-in">Verify Your Code</h1>
        <p class="text-gray-600 mb-6 fade-in">
            We've sent a 6-digit code to<br>
            <strong class="text-accent"><?= htmlspecialchars($email) ?></strong>
        </p>
    </div>
    
    <div class="p-6 bg-gray-50 rounded-lg mb-6 fade-in border border-gray-200">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-gray-700">
                    Please check your email inbox and spam folder. The code will expire in 15 minutes.
                </p>
            </div>
        </div>
    </div>
    
    <form method="POST" action="process_verify_code.php" data-form-type="verify-otp" class="space-y-6">
        <!-- OTP Field -->
        <div class="fade-in">
            <label for="otp" class="block text-sm font-medium text-gray-700 mb-1">6-Digit Code</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <input type="text" name="otp" id="otp" 
                    class="block w-full pl-10 pr-4 py-3 text-center font-medium text-lg tracking-[0.5em] rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-200"
                    placeholder="······" maxlength="6" pattern="[0-9]{6}" required
                    inputmode="numeric">
            </div>
            <p id="otp-error" class="mt-1 text-sm text-red-600 hidden"></p>
        </div>

        <!-- Submit Button -->
        <div class="fade-in pt-2">
            <button type="submit" 
                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-base font-medium text-white bg-accent hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent transition-all duration-300 transform hover:scale-[1.02]">
                Verify Code
            </button>
        </div>
    </form>
    
    <!-- Resend Code -->
    <div class="text-center mt-8 fade-in">
        <p class="text-sm text-gray-600">
            Didn't receive a code?
            <a href="process_resend_code.php" class="font-medium text-accent hover:text-accent/80 transition-colors">
                Resend code
            </a>
        </p>
    </div>
    
    <!-- Back to Login Link -->
    <div class="text-center mt-3 fade-in">
        <a href="login.php" class="text-sm text-gray-500 hover:text-accent transition-colors flex items-center justify-center">
            <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to login
        </a>
    </div>
</div>

<script src="../assets/js/auth.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto focus OTP input when page loads
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            setTimeout(() => otpInput.focus(), 300);
            
            // Auto submit when 6 digits entered
            otpInput.addEventListener('input', function() {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
                
                if (this.value.length === 6) {
                    setTimeout(() => {
                        this.form.submit();
                    }, 200);
                }
            });
        }
    });
</script>
</body>
</html>
