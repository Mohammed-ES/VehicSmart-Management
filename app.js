// TypeScript for VehicSmart Landing Page
// Form validation and scroll animations
var VehicSmartApp = /** @class */ (function () {
    function VehicSmartApp() {
        this.form = document.getElementById('contactForm');
        this.scrollElements = document.querySelectorAll('.scroll-reveal');
        this.init();
    }
    VehicSmartApp.prototype.init = function () {
        this.setupScrollAnimation();
        this.setupFormValidation();
        this.setupSmoothScrolling();
    };
    // Scroll Animation Setup
    VehicSmartApp.prototype.setupScrollAnimation = function () {
        var observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                }
            });
        }, observerOptions);
        this.scrollElements.forEach(function (element) {
            observer.observe(element);
        });
    };
    // Form Validation Setup
    VehicSmartApp.prototype.setupFormValidation = function () {
        var _this = this;
        if (!this.form)
            return;
        this.form.addEventListener('submit', function (e) {
            e.preventDefault();
            _this.handleFormSubmit();
        });
        // Real-time validation
        var inputs = this.form.querySelectorAll('input, textarea');
        inputs.forEach(function (input) {
            input.addEventListener('blur', function () {
                _this.validateField(input);
            });
        });
    };
    // Smooth Scrolling Setup
    VehicSmartApp.prototype.setupSmoothScrolling = function () {
        var navLinks = document.querySelectorAll('nav a[href^="#"]');
        navLinks.forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var target = link.getAttribute('href');
                if (target) {
                    var element = document.querySelector(target);
                    if (element) {
                        var offsetTop = element.getBoundingClientRect().top + window.pageYOffset - 64; // Account for fixed nav
                        window.scrollTo({
                            top: offsetTop,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
    };
    // Form Submission Handler
    VehicSmartApp.prototype.handleFormSubmit = function () {
        var formData = this.getFormData();
        var validation = this.validateForm(formData);
        if (validation.isValid) {
            this.clearErrors();
            this.showSuccess();
            this.resetForm();
            // Here you would typically send data to server
            console.log('Form submitted successfully:', formData);
        }
        else {
            this.displayErrors(validation.errors);
        }
    };
    // Get Form Data
    VehicSmartApp.prototype.getFormData = function () {
        var nameInput = document.getElementById('name');
        var emailInput = document.getElementById('email');
        var messageInput = document.getElementById('message');
        return {
            name: (nameInput === null || nameInput === void 0 ? void 0 : nameInput.value.trim()) || '',
            email: (emailInput === null || emailInput === void 0 ? void 0 : emailInput.value.trim()) || '',
            message: (messageInput === null || messageInput === void 0 ? void 0 : messageInput.value.trim()) || ''
        };
    };
    // Validate Entire Form
    VehicSmartApp.prototype.validateForm = function (data) {
        var errors = {};
        // Name validation
        if (!data.name || data.name.length < 2) {
            errors.name = 'Please enter a valid full name (at least 2 characters)';
        }
        // Email validation
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!data.email || !emailRegex.test(data.email)) {
            errors.email = 'Please enter a valid email address';
        }
        // Message validation
        if (!data.message || data.message.length < 10) {
            errors.message = 'Please enter a message (at least 10 characters)';
        }
        return {
            isValid: Object.keys(errors).length === 0,
            errors: errors
        };
    };
    // Validate Single Field
    VehicSmartApp.prototype.validateField = function (field) {
        var fieldName = field.name;
        var value = field.value.trim();
        var errorElement = document.getElementById("".concat(fieldName, "Error"));
        if (!errorElement)
            return;
        var isValid = true;
        var errorMessage = '';
        switch (fieldName) {
            case 'name':
                if (!value || value.length < 2) {
                    isValid = false;
                    errorMessage = 'Please enter a valid full name (at least 2 characters)';
                }
                break;
            case 'email':
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!value || !emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address';
                }
                break;
            case 'message':
                if (!value || value.length < 10) {
                    isValid = false;
                    errorMessage = 'Please enter a message (at least 10 characters)';
                }
                break;
        }
        if (isValid) {
            errorElement.classList.add('hidden');
            field.classList.remove('border-red-500');
            field.classList.add('border-green-500');
        }
        else {
            errorElement.textContent = errorMessage;
            errorElement.classList.remove('hidden');
            field.classList.add('border-red-500');
            field.classList.remove('border-green-500');
        }
    };
    // Display Form Errors
    VehicSmartApp.prototype.displayErrors = function (errors) {
        Object.keys(errors).forEach(function (field) {
            var errorElement = document.getElementById("".concat(field, "Error"));
            var inputElement = document.getElementById(field);
            if (errorElement && inputElement && errors[field]) {
                errorElement.textContent = errors[field] || '';
                errorElement.classList.remove('hidden');
                inputElement.classList.add('border-red-500');
                inputElement.classList.remove('border-green-500');
            }
        });
    };
    // Clear All Errors
    VehicSmartApp.prototype.clearErrors = function () {
        var _a, _b;
        var errorElements = (_a = this.form) === null || _a === void 0 ? void 0 : _a.querySelectorAll('[id$="Error"]');
        var inputElements = (_b = this.form) === null || _b === void 0 ? void 0 : _b.querySelectorAll('input, textarea');
        errorElements === null || errorElements === void 0 ? void 0 : errorElements.forEach(function (element) {
            element.classList.add('hidden');
        });
        inputElements === null || inputElements === void 0 ? void 0 : inputElements.forEach(function (element) {
            element.classList.remove('border-red-500', 'border-green-500');
        });
    };
    // Show Success Message
    VehicSmartApp.prototype.showSuccess = function () {
        var successElement = document.getElementById('formSuccess');
        if (successElement) {
            successElement.classList.remove('hidden');
            setTimeout(function () {
                successElement.classList.add('hidden');
            }, 5000);
        }
    };
    // Reset Form
    VehicSmartApp.prototype.resetForm = function () {
        if (this.form) {
            this.form.reset();
        }
    };
    return VehicSmartApp;
}());
// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    new VehicSmartApp();
});
// Add scroll-based navbar background change
window.addEventListener('scroll', function () {
    var navbar = document.querySelector('nav');
    if (navbar) {
        if (window.scrollY > 50) {
            navbar.classList.add('backdrop-blur-md', 'bg-opacity-95');
        }
        else {
            navbar.classList.remove('backdrop-blur-md', 'bg-opacity-95');
        }
    }
});
