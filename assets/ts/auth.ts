// TypeScript for VehicSmart Authentication Pages
// Form validation and animations

interface AuthFormData {
    email: string;
    password: string;
    confirmPassword?: string;
    fullName?: string;
    phone?: string;
    otp?: string;
}

interface ValidationResult {
    isValid: boolean;
    errors: {
        email?: string;
        password?: string;
        confirmPassword?: string;
        fullName?: string;
        phone?: string;
        otp?: string;
    };
}

class AuthFormHandler {
    private form: HTMLFormElement | null;

    constructor() {
        this.form = document.querySelector('form') as HTMLFormElement;
        
        if (this.form) {
            this.init();
        }
    }

    private init(): void {
        this.setupFormValidation();
        this.setupInputEffects();
        this.fadeInElements();
    }

    // Fade in elements with animation
    private fadeInElements(): void {
        const elements = document.querySelectorAll('.fade-in');
        elements.forEach((element, index) => {
            (element as HTMLElement).style.animationDelay = `${index * 0.1}s`;
        });
    }

    // Add input focus/blur effects
    private setupInputEffects(): void {
        const inputs = document.querySelectorAll('input');
        
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement?.classList.add('ring-2', 'ring-accent', 'ring-opacity-50');
            });
            
            input.addEventListener('blur', () => {
                input.parentElement?.classList.remove('ring-2', 'ring-accent', 'ring-opacity-50');
            });
        });
    }

    // Setup form validation
    private setupFormValidation(): void {
        if (!this.form) return;

        this.form.addEventListener('submit', (e: Event) => {
            if (!this.validateForm()) {
                e.preventDefault();
            }
        });

        // Real-time validation
        const inputs = this.form.querySelectorAll('input');
        inputs.forEach((input) => {
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
        });
    }

    // Validate form on submit
    private validateForm(): boolean {
        if (!this.form) return false;
        
        const formType = this.form.getAttribute('data-form-type');
        const formData = this.getFormData();
        let validationResult: ValidationResult = { isValid: true, errors: {} };
        
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
    }

    // Get form data
    private getFormData(): AuthFormData {
        const data: AuthFormData = {
            email: this.getInputValue('email'),
            password: this.getInputValue('password')
        };

        // Optional fields based on form type
        const confirmPassword = this.getInputValue('confirm_password');
        if (confirmPassword) data.confirmPassword = confirmPassword;
        
        const fullName = this.getInputValue('full_name');
        if (fullName) data.fullName = fullName;
        
        const phone = this.getInputValue('phone');
        if (phone) data.phone = phone;
        
        const otp = this.getInputValue('otp');
        if (otp) data.otp = otp;

        return data;
    }

    // Helper to get input value
    private getInputValue(name: string): string {
        const input = this.form?.querySelector(`[name="${name}"]`) as HTMLInputElement;
        return input ? input.value.trim() : '';
    }

    // Validate login form
    private validateLoginForm(data: AuthFormData): ValidationResult {
        const errors: ValidationResult['errors'] = {};

        if (!data.email) {
            errors.email = 'Email is required';
        } else if (!this.isValidEmail(data.email)) {
            errors.email = 'Please enter a valid email address';
        }

        if (!data.password) {
            errors.password = 'Password is required';
        }

        return {
            isValid: Object.keys(errors).length === 0,
            errors
        };
    }

    // Validate register form
    private validateRegisterForm(data: AuthFormData): ValidationResult {
        const errors: ValidationResult['errors'] = {};

        if (!data.fullName) {
            errors.fullName = 'Full name is required';
        } else if (data.fullName.length < 3) {
            errors.fullName = 'Full name must be at least 3 characters';
        }

        if (!data.email) {
            errors.email = 'Email is required';
        } else if (!this.isValidEmail(data.email)) {
            errors.email = 'Please enter a valid email address';
        }

        if (data.phone && data.phone.length < 10) {
            errors.phone = 'Please enter a valid phone number';
        }

        if (!data.password) {
            errors.password = 'Password is required';
        } else if (data.password.length < 8) {
            errors.password = 'Password must be at least 8 characters';
        }

        if (!data.confirmPassword) {
            errors.confirmPassword = 'Please confirm your password';
        } else if (data.password !== data.confirmPassword) {
            errors.confirmPassword = 'Passwords do not match';
        }

        return {
            isValid: Object.keys(errors).length === 0,
            errors
        };
    }

    // Validate forgot password form
    private validateForgotPasswordForm(data: AuthFormData): ValidationResult {
        const errors: ValidationResult['errors'] = {};

        if (!data.email) {
            errors.email = 'Email is required';
        } else if (!this.isValidEmail(data.email)) {
            errors.email = 'Please enter a valid email address';
        }

        return {
            isValid: Object.keys(errors).length === 0,
            errors
        };
    }

    // Validate OTP form
    private validateOTPForm(data: AuthFormData): ValidationResult {
        const errors: ValidationResult['errors'] = {};

        if (!data.otp) {
            errors.otp = 'OTP code is required';
        } else if (data.otp.length !== 6 || !/^\d+$/.test(data.otp)) {
            errors.otp = 'Please enter a valid 6-digit OTP code';
        }

        return {
            isValid: Object.keys(errors).length === 0,
            errors
        };
    }

    // Validate reset password form
    private validateResetPasswordForm(data: AuthFormData): ValidationResult {
        const errors: ValidationResult['errors'] = {};

        if (!data.password) {
            errors.password = 'Password is required';
        } else if (data.password.length < 8) {
            errors.password = 'Password must be at least 8 characters';
        }

        if (!data.confirmPassword) {
            errors.confirmPassword = 'Please confirm your password';
        } else if (data.password !== data.confirmPassword) {
            errors.confirmPassword = 'Passwords do not match';
        }

        return {
            isValid: Object.keys(errors).length === 0,
            errors
        };
    }

    // Validate a single field
    private validateField(field: HTMLInputElement): void {
        const name = field.name;
        const value = field.value.trim();
        let errorMessage = '';
        
        switch (name) {
            case 'email':
                if (!value) {
                    errorMessage = 'Email is required';
                } else if (!this.isValidEmail(value)) {
                    errorMessage = 'Please enter a valid email address';
                }
                break;
                
            case 'password':
                if (!value) {
                    errorMessage = 'Password is required';
                } else if (value.length < 8) {
                    errorMessage = 'Password must be at least 8 characters';
                }
                break;
                
            case 'confirm_password':
                const passwordField = this.form?.querySelector('[name="password"]') as HTMLInputElement;
                if (!value) {
                    errorMessage = 'Please confirm your password';
                } else if (passwordField && value !== passwordField.value) {
                    errorMessage = 'Passwords do not match';
                }
                break;
                
            case 'full_name':
                if (!value) {
                    errorMessage = 'Full name is required';
                } else if (value.length < 3) {
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
                } else if (value.length !== 6 || !/^\d+$/.test(value)) {
                    errorMessage = 'Please enter a valid 6-digit OTP code';
                }
                break;
        }
        
        this.updateFieldError(field, errorMessage);
    }

    // Update field error message
    private updateFieldError(field: HTMLInputElement, errorMessage: string): void {
        const errorElement = document.getElementById(`${field.name}-error`);
        if (!errorElement) return;
        
        if (errorMessage) {
            errorElement.textContent = errorMessage;
            errorElement.classList.remove('hidden');
            field.classList.add('border-red-300');
            field.classList.remove('border-gray-300');
        } else {
            errorElement.classList.add('hidden');
            field.classList.remove('border-red-300');
            field.classList.add('border-gray-300');
        }
    }

    // Display all form errors
    private displayErrors(errors: ValidationResult['errors']): void {
        Object.keys(errors).forEach(field => {
            const errorMessage = errors[field as keyof ValidationResult['errors']];
            if (!errorMessage) return;
            
            // Convert field name to input name (e.g., fullName -> full_name)
            let inputName = field.replace(/([A-Z])/g, '_$1').toLowerCase();
            if (inputName.startsWith('_')) {
                inputName = inputName.substring(1);
            }
            
            const input = this.form?.querySelector(`[name="${inputName}"]`) as HTMLInputElement;
            if (input) {
                this.updateFieldError(input, errorMessage);
            }
        });
    }

    // Validate email format
    private isValidEmail(email: string): boolean {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
}

// Initialize the form handler when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AuthFormHandler();
});
