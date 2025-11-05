<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Invalid request method');
    redirect('forgot_password.php');
}

// Get and sanitize form data
$email = sanitizeInput($_POST['email'] ?? '');

// Basic validation
if (empty($email) || !isValidEmail($email)) {
    setFlashMessage('error', 'Please enter a valid email address');
    redirect('forgot_password.php');
}

try {
    // Get the database connection
    $db = Database::getInstance();
    
    // Check if the user exists
    $user = $db->selectOne(
        "SELECT id, email FROM users WHERE email = ? AND status = 'active'",
        [$email]
    );
    
    if (!$user) {
        // Still show success message to prevent email enumeration attacks
        setFlashMessage('success', 'If your email is registered, you will receive a password reset code shortly.');
        redirect('login.php');
        exit;
    }
    
    // Generate a 6-digit OTP
    $otp = generateOTP(6);
    
    // Set expiration time (15 minutes)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Delete any existing OTP for this email
    $db->delete(
        "DELETE FROM password_resets WHERE email = ?",
        [$email]
    );
    
    // Store the OTP in the database
    $db->insert(
        "INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)",
        [$email, $otp, $expiresAt]
    );
    
    // Store email in session for verification page
    $_SESSION['reset_email'] = $email;
    
    // Prepare email content
    $subject = "Password Reset Code - VehicSmart";
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #ff7849;'>Password Reset Code</h2>
            <p>Hello,</p>
            <p>You have requested a password reset for your VehicSmart account. Please use the following code to reset your password:</p>
            <div style='background-color: #f7f7f7; padding: 15px; text-align: center; font-size: 24px; letter-spacing: 5px; margin: 20px 0;'>
                <strong>$otp</strong>
            </div>
            <p>This code will expire in 15 minutes.</p>
            <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
            <p>Regards,<br>VehicSmart Team</p>
        </div>
    </body>
    </html>
    ";
    
    // Send email (in production, use a proper email sending library)
    sendEmail($email, $subject, $message);
    
    // Redirect to verify code page
    redirect('verify_code.php');

} catch (Exception $e) {
    // Log the error (in a real application)
    error_log('Password reset error: ' . $e->getMessage());
    
    // Display generic error message to the user
    setFlashMessage('error', 'An error occurred while processing your request. Please try again.');
    redirect('forgot_password.php');
}
