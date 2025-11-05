<?php
/**
 * Redirection file for client dashboard
 * This file redirects to the proper client dashboard location
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simply redirect to the real client dashboard
header("Location: client/client_dashboard.php");
exit;
