<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Check if reset token exists in session
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_verified'])) {
    setFlashMessage('error', 'Invalid request. Please try the password reset process again.');
    redirect('forgot_password.php');
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Invalid request method');
    redirect('reset_password.php');
}

// Get form data
$email = $_SESSION['reset_email'];
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Basic validation
if (empty($password) || strlen($password) < 8) {
    setFlashMessage('error', 'Password must be at least 8 characters long');
    redirect('reset_password.php');
}

if ($password !== $confirmPassword) {
    setFlashMessage('error', 'Passwords do not match');
    redirect('reset_password.php');
}

try {
    // Get the database connection
    $db = Database::getInstance();
    
    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Update the user's password
    $result = $db->update(
        "UPDATE users SET password = ? WHERE email = ?",
        [$hashedPassword, $email]
    );
    
    // Check if the update was successful
    if ($result === 0) {
        setFlashMessage('error', 'Failed to update password. Please try again.');
        redirect('reset_password.php');
    }
    
    // Delete all OTP records for this email
    $db->delete(
        "DELETE FROM password_resets WHERE email = ?",
        [$email]
    );
    
    // Clear reset session variables
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_verified']);
    
    // Set success message
    setFlashMessage('success', 'Your password has been successfully reset. You can now log in with your new password.');
    
    // Redirect to login page
    redirect('login.php');

} catch (Exception $e) {
    // Log the error (in a real application)
    error_log('Password reset error: ' . $e->getMessage());
    
    // Display generic error message to the user
    setFlashMessage('error', 'An error occurred while resetting your password. Please try again.');
    redirect('reset_password.php');
}
