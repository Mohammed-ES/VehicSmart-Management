<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Check if email exists in session
if (!isset($_SESSION['reset_email'])) {
    setFlashMessage('error', 'Invalid request. Please try the password reset process again.');
    redirect('forgot_password.php');
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Invalid request method');
    redirect('verify_code.php');
}

// Get the email from session and OTP from form
$email = $_SESSION['reset_email'];
$otp = sanitizeInput($_POST['otp'] ?? '');

// Basic validation
if (empty($otp) || strlen($otp) != 6 || !ctype_digit($otp)) {
    setFlashMessage('error', 'Please enter a valid 6-digit code');
    redirect('verify_code.php');
}

try {
    // Get the database connection
    $db = Database::getInstance();
    
    // Check if the OTP is valid and not expired
    $resetRecord = $db->selectOne(
        "SELECT * FROM password_resets WHERE email = ? AND otp = ? AND expires_at > ?",
        [$email, $otp, date('Y-m-d H:i:s')]
    );
    
    if (!$resetRecord) {
        setFlashMessage('error', 'Invalid or expired code. Please try again.');
        redirect('verify_code.php');
    }
    
    // Mark the verification as successful in the session
    $_SESSION['reset_verified'] = true;
    
    // Redirect to the password reset page
    redirect('reset_password.php');

} catch (Exception $e) {
    // Log the error (in a real application)
    error_log('OTP verification error: ' . $e->getMessage());
    
    // Display generic error message to the user
    setFlashMessage('error', 'An error occurred while verifying your code. Please try again.');
    redirect('verify_code.php');
}
