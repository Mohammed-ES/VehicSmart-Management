<?php
/**
 * VehicSmart Chatbot Include File
 * 
 * Include this file at the end of any page where you want the chatbot to appear
 * 
 * @package VehicSmart
 * @version 1.0
 */

// Ensure this file is accessed properly
if (!defined('VEHICSMART_SECURED')) {
    define('VEHICSMART_SECURED', true);
}

// Only load for authenticated users in client role
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'client') {
    // Check if we need to get the current user's vehicle data
    $loadVehicleData = isset($loadChatbotVehicleData) && $loadChatbotVehicleData;
    
    // Current page for context
    $currentPage = basename($_SERVER['PHP_SELF']);
    
    // Get user language preference
    $userLanguage = $_SESSION['language'] ?? 'en';
?>
<!-- VehicSmart AI Chatbot -->
<link rel="stylesheet" href="<?= $baseUrl ?? '' ?>client/chatbot/css/chatbot.css">
<link rel="stylesheet" href="<?= $baseUrl ?? '' ?>client/chatbot/css/language.css">
<link rel="stylesheet" href="<?= $baseUrl ?? '' ?>client/chatbot/css/pulse-animation.css">
<link rel="stylesheet" href="<?= $baseUrl ?? '' ?>client/chatbot/css/modern-theme.css">

<script>
    // Pass PHP variables to JavaScript
    window.vsChatbotConfig = {
        currentPage: '<?= htmlspecialchars($currentPage) ?>',
        username: '<?= htmlspecialchars($_SESSION['full_name'] ?? 'Client') ?>',
        userId: <?= (int)$_SESSION['user_id'] ?>,
        baseUrl: '<?= $baseUrl ?? '' ?>',
        language: '<?= $userLanguage ?>',
<?php if ($loadVehicleData && isset($vehicleData)): ?>
        vehicleData: <?= json_encode($vehicleData) ?>,
<?php endif; ?>
        apiEndpoint: '<?= $baseUrl ?? '' ?>client/chatbot/api/direct_response.php'
    };
    
    // Afficher la configuration dans la console pour déboguer
    console.log('Configuration du chatbot:', window.vsChatbotConfig);
</script>
<script src="<?= $baseUrl ?? '' ?>client/chatbot/js/chatbot_ultra_simple.js" defer></script>

<!-- Chatbot HTML Structure -->
<!-- Floating Chatbot Button with Pulse Animation -->
<div class="vs-chatbot-button" aria-label="Open AI assistant">
    <div class="vs-chatbot-button-pulse pulse-animation"></div>
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v3zM16 8H8V6h8v2z" fill="#ff7849" stroke="white" stroke-width="0.5"/>
    </svg>
</div>

<div id="vs-chatbot-container" class="vs-chatbot-container vs-chatbot-collapsed">
    <div class="vs-chatbot-header">
        <div class="vs-chatbot-title">
            <svg class="vs-chatbot-logo" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v3zM16 8H8V6h8v2z" fill="currentColor"/>
            </svg>
            Assistant IA
        </div>
        <div class="vs-chatbot-controls">
            <button class="vs-chatbot-language-toggle" title="Change language / Changer de langue">
                <span class="vs-chatbot-current-lang"><?= strtoupper($userLanguage) ?></span>
            </button>
            <button class="vs-chatbot-minimize" aria-label="Minimize chatbot">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
            <button class="vs-chatbot-close" aria-label="Close chatbot">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
    </div>
    <div class="vs-chatbot-body">
        <div class="vs-chatbot-welcome">
            <div class="vs-chatbot-welcome-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v3zM16 8H8V6h8v2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h3 class="vs-chatbot-welcome-title">Comment puis-je vous aider aujourd'hui?</h3>
        </div>
        <div id="vs-chatbot-messages" class="vs-chatbot-messages">
            <!-- Messages will be dynamically inserted here -->
        </div>
        <div class="vs-chatbot-typing vs-hidden">
            <div class="vs-chatbot-dot"></div>
            <div class="vs-chatbot-dot"></div>
            <div class="vs-chatbot-dot"></div>
        </div>
        <div class="vs-chatbot-input-container">
            <textarea id="vs-chatbot-input" 
                      class="vs-chatbot-input" 
                      placeholder="Écrivez votre message ici..."
                      rows="1"></textarea>
            <button id="vs-chatbot-send" class="vs-chatbot-send" aria-label="Envoyer">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22 2L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="vs-chatbot-suggestions">
            <!-- Dynamic suggestions will be added here -->
        </div>
    </div>
    <div class="vs-chatbot-button">
        <div class="vs-chatbot-button-pulse"></div>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v3zM16 8H8V6h8v2z" fill="currentColor" stroke="white" stroke-width="0.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
</div>

<?php } ?>
