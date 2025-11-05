// TypeScript for VehicSmart Authentication Pages
// Form validation and animations
var AuthFormHandler = /** @class */ (function () {
    function AuthFormHandler() {
        this.form = document.querySelector('form');
        if (this.form) {
            this.init();
        }
    }
    AuthFormHandler.prototype.init = function () {
        this.setupFormValidation();
        this.setupInputEffects();
        this.fadeInElements();
    };
    // Fade in elements with animation
    AuthFormHandler.prototype.fadeInElements = function () {
        var elements = document.querySelectorAll('.fade-in');
        elements.forEach(function (element, index) {
            element.style.animationDelay = "".concat(index * 0.1, "s");
        });
    };
    // Add input focus/blur effects
    AuthFormHandler.prototype.setupInputEffects = function () {
        var inputs = document.querySelectorAll('input');
        inputs.forEach(function (input) {
            input.addEventListener('focus', function () {
                var _a;
                (_a = input.parentElement) === null || _a === void 0 ? void 0 : _a.classList.add('ring-2', 'ring-accent', 'ring-opacity-50');
            });
            input.addEventListener('blur', function () {
                var _a;
                (_a = input.parentElement) === null || _a === void 0 ? void 0 : _a.classList.remove('ring-2', 'ring-accent', 'ring-opacity-50');
            });
        });
    };
    // Setup form validation
    AuthFormHandler.prototype.setupFormValidation = function () {
        var _this = this;
        if (!this.form)
            return;
        this.form.addEventListener('submit', function (e) {
            if (!_this.validateForm()) {
                e.preventDefault();
            }
        });
        // Real-time validation
        var inputs = this.form.querySelectorAll('input');
        inputs.forEach(function (input) {
            input.addEventListener('blur', function () {
                _this.validateField(input);
            });
        });
    };
    // Validate form on submit
    AuthFormHandler.prototype.validateForm = function () {
        if (!this.form)
            return false;
        var formType = this.form.getAttribute('data-form-type');
        var formData = this.getFormData();
        var validationResult = { isValid: true, errors: {} };
        switch (formType) {
            case 'login':
                validationResult = this.validateLoginForm(formData);
                break;
            case 'register':
                validationResult = this.validateRegisterForm(formData);
                break;
            case 'forgot-password':
                validationResult = this.validateForgotPasswordForm(formData);
                break;
            case 'verify-otp':
                validationResult = this.validateOTPForm(formData);
                break;
            case 'reset-password':
                validationResult = this.validateResetPasswordForm(formData);
                break;
        }
        if (!validationResult.isValid) {
            this.displayErrors(validationResult.errors);
            return false;
        }
        return true;
    };
    // Get form data
    AuthFormHandler.prototype.getFormData = function () {
        var data = {
            email: this.getInputValue('email'),
            password: this.getInputValue('password')
        };
        // Optional fields based on form type
        var confirmPassword = this.getInputValue('confirm_password');
        if (confirmPassword)
            data.confirmPassword = confirmPassword;
        var fullName = this.getInputValue('full_name');
        if (fullName)
            data.fullName = fullName;
        var phone = this.getInputValue('phone');
        if (phone)
            data.phone = phone;
        var otp = this.getInputValue('otp');
        if (otp)
            data.otp = otp;
        return data;
    };
    // Helper to get input value
    AuthFormHandler.prototype.getInputValue = function (name) {
        var _a;
        var input = (_a = this.form) === null || _a === void 0 ? void 0 : _a.querySelector("[name=\"".concat(name, "\"]"));
        return input ? input.value.trim() : '';
    };
    // Validate login form
    AuthFormHandler.prototype.validateLoginForm = function (data) {
        var errors = {};
        if (!data.email) {
            errors.email = 'Email is required';
        }
        else if (!this.isValidEmail(data.email)) {
            errors.email = 'Please enter a valid email address';
        }
        if (!data.password) {
            errors.password = 'Password is required';
        }
        return {
            isValid: Object.keys(errors).length === 0,
            errors: errors
        };
    };
    // Validate register form
    AuthFormHandler.prototype.validateRegisterForm = function (data) {
        var errors = {};
        if (!data.fullName) {
            errors.fullName = 'Full name is required';
        }
        else if (data.fullName.length < 3) {
            errors.fullName = 'Full name must be at least 3 characters';
        }
        if (!data.email) {
            errors.email = 'Email is required';
        }
        else if (!this.isValidEmail(data.email)) {
            errors.email = 'Please enter a valid email address';
        }
        if (data.phone && data.phone.length < 10) {
            errors.phone = 'Please enter a valid phone number';
        }
        if (!data.password) {
            errors.password = 'Password is required';
        }
        else if (data.password.length < 8) {
            errors.password = 'Password must be at least 8 characters';
        }
        if (!data.confirmPassword) {
            errors.confirmPassword = 'Please confirm your password';
        }
        else if (data.password !== data.confirmPassword) {
            errors.confirmPassword = 'Passwords do not match';
        }
        return {
            isValid: Object.keys(errors).length === 0,
            errors: errors
        };
    };
    // Validate forgot password form
    AuthFormHandler.prototype.validateForgotPasswordForm = function (data) {
        var errors = {};
        if (!data.email) {
            errors.email = 'Email is required';
        }
        else if (!this.isValidEmail(data.email)) {
            errors.email = 'Please enter a valid email address';
        }
        return {
            isValid: Object.keys(errors).length === 0,
            errors: errors
        };
    };
    // Validate OTP form
    AuthFormHandler.prototype.validateOTPForm = function (data) {
        var errors = {};
        if (!data.otp) {
            errors.otp = 'OTP code is required';
        }
        else if (data.otp.length !== 6 || !/^\d+$/.test(data.otp)) {
            errors.otp = 'Please enter a valid 6-digit OTP code';
        }
        return {
            isValid: Object.keys(errors).length === 0,
            errors: errors
        };
    };
    // Validate reset password form
    AuthFormHandler.prototype.validateResetPasswordForm = function (data) {
        var errors = {};
        if (!data.password) {
            errors.password = 'Password is required';
        }
        else if (data.password.length < 8) {
            errors.password = 'Password must be at least 8 characters';
        }
        if (!data.confirmPassword) {
            errors.confirmPassword = 'Please confirm your password';
        }
        else if (data.password !== data.confirmPassword) {
            errors.confirmPassword = 'Passwords do not match';
        }
        return {
            isValid: Object.keys(errors).length === 0,
            errors: errors
        };
    };
    // Validate a single field
    AuthFormHandler.prototype.validateField = function (field) {
        var _a;
        var name = field.name;
        var value = field.value.trim();
        var errorMessage = '';
        switch (name) {
            case 'email':
                if (!value) {
                    errorMessage = 'Email is required';
                }
                else if (!this.isValidEmail(value)) {
                    errorMessage = 'Please enter a valid email address';
                }
                break;
            case 'password':
                if (!value) {
                    errorMessage = 'Password is required';
                }
                else if (value.length < 8) {
                    errorMessage = 'Password must be at least 8 characters';
                }
                break;
            case 'confirm_password':
                var passwordField = (_a = this.form) === null || _a === void 0 ? void 0 : _a.querySelector('[name="password"]');
                if (!value) {
                    errorMessage = 'Please confirm your password';
                }
                else if (passwordField && value !== passwordField.value) {
                    errorMessage = 'Passwords do not match';
                }
                break;
            case 'full_name':
                if (!value) {
                    errorMessage = 'Full name is required';
                }
                else if (value.length < 3) {
                    errorMessage = 'Full name must be at least 3 characters';
                }
                break;
            case 'phone':
                if (value && value.length < 10) {
                    errorMessage = 'Please enter a valid phone number';
                }
                break;
            case 'otp':
                if (!value) {
                    errorMessage = 'OTP code is required';
                }
                else if (value.length !== 6 || !/^\d+$/.test(value)) {
                    errorMessage = 'Please enter a valid 6-digit OTP code';
                }
                break;
        }
        this.updateFieldError(field, errorMessage);
    };
    // Update field error message
    AuthFormHandler.prototype.updateFieldError = function (field, errorMessage) {
        var errorElement = document.getElementById("".concat(field.name, "-error"));
        if (!errorElement)
            return;
        if (errorMessage) {
            errorElement.textContent = errorMessage;
            errorElement.classList.remove('hidden');
            field.classList.add('border-red-300');
            field.classList.remove('border-gray-300');
        }
        else {
            errorElement.classList.add('hidden');
            field.classList.remove('border-red-300');
            field.classList.add('border-gray-300');
        }
    };
    // Display all form errors
    AuthFormHandler.prototype.displayErrors = function (errors) {
        var _this = this;
        Object.keys(errors).forEach(function (field) {
            var _a;
            var errorMessage = errors[field];
            if (!errorMessage)
                return;
            // Convert field name to input name (e.g., fullName -> full_name)
            var inputName = field.replace(/([A-Z])/g, '_$1').toLowerCase();
            if (inputName.startsWith('_')) {
                inputName = inputName.substring(1);
            }
            var input = (_a = _this.form) === null || _a === void 0 ? void 0 : _a.querySelector("[name=\"".concat(inputName, "\"]"));
            if (input) {
                _this.updateFieldError(input, errorMessage);
            }
        });
    };
    // Validate email format
    AuthFormHandler.prototype.isValidEmail = function (email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };
    return AuthFormHandler;
}());
// Initialize the form handler when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    new AuthFormHandler();
});
