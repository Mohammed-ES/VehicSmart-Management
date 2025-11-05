/**
 * VehicSmart - Authentication Form Handler
 * Gère la soumission AJAX des formulaires d'authentification
 */

document.addEventListener('DOMContentLoaded', function() {
    // Gérer tous les formulaires d'authentification
    const authForms = document.querySelectorAll('form[data-form-type]');
    
    authForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmit(form);
        });
    });
});

/**
 * Gérer la soumission du formulaire
 */
function handleFormSubmit(form) {
    const formType = form.getAttribute('data-form-type');
    const submitBtn = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);
    
    // Désactiver le bouton pendant le traitement
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
    }
    
    // Effacer les messages d'erreur précédents
    clearErrors(form);
    
    // Envoyer la requête AJAX
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Afficher le message de succès
            showMessage(form, data.message, 'success');
            
            // Rediriger après un court délai
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 500);
            }
        } else {
            // Afficher le message d'erreur
            showMessage(form, data.message, 'error');
            
            // Réactiver le bouton
            if (submitBtn) {
                resetButton(submitBtn, formType);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage(form, 'Une erreur est survenue. Veuillez réessayer.', 'error');
        
        // Réactiver le bouton
        if (submitBtn) {
            resetButton(submitBtn, formType);
        }
    });
}

/**
 * Réinitialiser le bouton
 */
function resetButton(button, formType) {
    button.disabled = false;
    
    const buttonTexts = {
        'login': 'Sign In',
        'register': 'Create Account',
        'forgot-password': 'Send Reset Code',
        'verify-otp': 'Verify Code',
        'reset-password': 'Reset Password'
    };
    
    button.innerHTML = buttonTexts[formType] || 'Submit';
}

/**
 * Afficher un message
 */
function showMessage(form, message, type) {
    // Chercher ou créer le conteneur de message
    let messageDiv = form.querySelector('.form-message');
    
    if (!messageDiv) {
        messageDiv = document.createElement('div');
        messageDiv.className = 'form-message';
        form.insertBefore(messageDiv, form.firstChild);
    }
    
    // Classes CSS selon le type
    const bgColor = type === 'success' ? 'bg-green-50' : 'bg-red-50';
    const textColor = type === 'success' ? 'text-green-700' : 'text-red-700';
    const borderColor = type === 'success' ? 'border-green-200' : 'border-red-200';
    
    messageDiv.className = `form-message p-4 mb-6 rounded-xl border ${bgColor} ${textColor} ${borderColor} animate-fade-in`;
    messageDiv.innerHTML = `
        <div class="flex items-center">
            <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                ${type === 'success' 
                    ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />'
                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
                }
            </svg>
            <span>${message}</span>
        </div>
    `;
    
    // Auto-effacer après 5 secondes si erreur
    if (type === 'error') {
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            setTimeout(() => messageDiv.remove(), 300);
        }, 5000);
    }
}

/**
 * Effacer les messages d'erreur
 */
function clearErrors(form) {
    const messageDiv = form.querySelector('.form-message');
    if (messageDiv) {
        messageDiv.remove();
    }
    
    // Effacer les erreurs individuelles des champs
    const errorMessages = form.querySelectorAll('.text-red-600');
    errorMessages.forEach(msg => {
        msg.classList.add('hidden');
    });
}

// Ajouter les styles d'animation
const style = document.createElement('style');
style.textContent = `
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-fade-in {
        animation: fade-in 0.3s ease-out;
    }
    
    .animate-spin {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }
`;
document.head.appendChild(style);
