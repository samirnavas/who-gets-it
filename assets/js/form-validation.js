/**
 * Responsive Form Validation JavaScript
 * Provides real-time validation, accessibility features, and enhanced UX
 */

class FormValidator {
    constructor(formElement, options = {}) {
        this.form = formElement;
        this.options = {
            validateOnInput: true,
            validateOnBlur: true,
            showSuccessStates: true,
            realTimeValidation: true,
            accessibilityMode: true,
            ...options
        };
        
        this.fields = new Map();
        this.validationRules = new Map();
        this.liveRegion = null;
        
        this.init();
    }
    
    init() {
        this.createLiveRegion();
        this.setupFormFields();
        this.bindEvents();
    }
    
    /**
     * Create ARIA live region for screen reader announcements
     */
    createLiveRegion() {
        if (!this.options.accessibilityMode) return;
        
        this.liveRegion = document.createElement('div');
        this.liveRegion.className = 'form-validation-live-region';
        this.liveRegion.setAttribute('aria-live', 'polite');
        this.liveRegion.setAttribute('aria-atomic', 'true');
        document.body.appendChild(this.liveRegion);
    }
    
    /**
     * Setup form fields and their validation containers
     */
    setupFormFields() {
        const inputs = this.form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            const fieldContainer = input.closest('.form-field');
            if (!fieldContainer) return;
            
            const fieldData = {
                input: input,
                container: fieldContainer,
                validationMessage: null,
                validationIcon: null,
                isValid: null,
                rules: []
            };
            
            // Setup validation message container
            this.setupValidationMessage(fieldData);
            
            // Setup validation icon
            this.setupValidationIcon(fieldData);
            
            // Add floating label support
            this.setupFloatingLabel(fieldData);
            
            // Setup character count if needed
            this.setupCharacterCount(fieldData);
            
            // Setup password strength if needed
            this.setupPasswordStrength(fieldData);
            
            this.fields.set(input.name || input.id, fieldData);
        });
    }
    
    /**
     * Setup validation message container
     */
    setupValidationMessage(fieldData) {
        let messageContainer = fieldData.container.querySelector('.form-validation-message');
        
        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.className = 'form-validation-message';
            messageContainer.style.display = 'none';
            fieldData.container.appendChild(messageContainer);
        }
        
        fieldData.validationMessage = messageContainer;
    }
    
    /**
     * Setup validation icon
     */
    setupValidationIcon(fieldData) {
        const inputWrapper = fieldData.input.parentElement;
        
        // Check if input wrapper needs icon support
        if (!inputWrapper.classList.contains('form-input-with-icon')) {
            inputWrapper.classList.add('form-input-with-icon');
        }
        
        let icon = inputWrapper.querySelector('.form-validation-icon');
        
        if (!icon) {
            icon = document.createElement('div');
            icon.className = 'form-validation-icon';
            icon.innerHTML = this.getValidationIconSVG('loading');
            icon.style.display = 'none';
            inputWrapper.appendChild(icon);
        }
        
        fieldData.validationIcon = icon;
    }
    
    /**
     * Setup floating label functionality
     */
    setupFloatingLabel(fieldData) {
        const floatingLabel = fieldData.container.querySelector('.form-label-floating');
        if (!floatingLabel) return;
        
        const updateLabelState = () => {
            const hasValue = fieldData.input.value.trim() !== '';
            fieldData.input.classList.toggle('has-value', hasValue);
        };
        
        // Initial state
        updateLabelState();
        
        // Update on input
        fieldData.input.addEventListener('input', updateLabelState);
        fieldData.input.addEventListener('blur', updateLabelState);
    }
    
    /**
     * Setup character count indicator
     */
    setupCharacterCount(fieldData) {
        const maxLength = fieldData.input.getAttribute('maxlength');
        if (!maxLength) return;
        
        const countContainer = document.createElement('div');
        countContainer.className = 'character-count-indicator';
        
        const countText = document.createElement('span');
        countText.className = 'character-count-current';
        
        const maxText = document.createElement('span');
        maxText.textContent = `/ ${maxLength}`;
        
        countContainer.appendChild(countText);
        countContainer.appendChild(maxText);
        
        fieldData.container.appendChild(countContainer);
        
        const updateCount = () => {
            const currentLength = fieldData.input.value.length;
            countText.textContent = currentLength;
            
            // Update styling based on character count
            countContainer.classList.remove('warning', 'error');
            
            if (currentLength > maxLength * 0.9) {
                countContainer.classList.add('warning');
            }
            
            if (currentLength >= maxLength) {
                countContainer.classList.add('error');
            }
        };
        
        fieldData.input.addEventListener('input', updateCount);
        updateCount();
    }
    
    /**
     * Setup password strength indicator
     */
    setupPasswordStrength(fieldData) {
        if (fieldData.input.type !== 'password') return;
        
        const strengthContainer = document.createElement('div');
        strengthContainer.className = 'password-strength-indicator';
        strengthContainer.innerHTML = `
            <div class="password-strength-label">Password Strength</div>
            <div class="password-strength-bar">
                <div class="password-strength-fill"></div>
            </div>
            <div class="password-strength-text">Enter a password</div>
        `;
        
        fieldData.container.appendChild(strengthContainer);
        
        const strengthFill = strengthContainer.querySelector('.password-strength-fill');
        const strengthText = strengthContainer.querySelector('.password-strength-text');
        
        const updateStrength = () => {
            const password = fieldData.input.value;
            const strength = this.calculatePasswordStrength(password);
            
            // Remove all strength classes
            strengthFill.classList.remove('weak', 'fair', 'good', 'strong');
            strengthText.classList.remove('weak', 'fair', 'good', 'strong');
            
            if (password.length === 0) {
                strengthText.textContent = 'Enter a password';
                return;
            }
            
            // Add appropriate strength class
            strengthFill.classList.add(strength.level);
            strengthText.classList.add(strength.level);
            strengthText.textContent = strength.text;
        };
        
        fieldData.input.addEventListener('input', updateStrength);
    }
    
    /**
     * Calculate password strength
     */
    calculatePasswordStrength(password) {
        if (password.length === 0) {
            return { level: '', text: 'Enter a password' };
        }
        
        let score = 0;
        
        // Length check
        if (password.length >= 8) score += 1;
        if (password.length >= 12) score += 1;
        
        // Character variety checks
        if (/[a-z]/.test(password)) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/[0-9]/.test(password)) score += 1;
        if (/[^A-Za-z0-9]/.test(password)) score += 1;
        
        // Common patterns (reduce score)
        if (/(.)\1{2,}/.test(password)) score -= 1; // Repeated characters
        if (/123|abc|qwe/i.test(password)) score -= 1; // Sequential patterns
        
        score = Math.max(0, Math.min(4, score));
        
        const levels = [
            { level: 'weak', text: 'Weak password' },
            { level: 'weak', text: 'Weak password' },
            { level: 'fair', text: 'Fair password' },
            { level: 'good', text: 'Good password' },
            { level: 'strong', text: 'Strong password' }
        ];
        
        return levels[score];
    }
    
    /**
     * Bind form events
     */
    bindEvents() {
        // Form submission
        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                this.focusFirstError();
            }
        });
        
        // Field-level validation
        this.fields.forEach((fieldData, fieldName) => {
            if (this.options.validateOnInput) {
                fieldData.input.addEventListener('input', () => {
                    if (this.options.realTimeValidation) {
                        this.debounce(() => this.validateField(fieldName), 300)();
                    }
                });
            }
            
            if (this.options.validateOnBlur) {
                fieldData.input.addEventListener('blur', () => {
                    this.validateField(fieldName);
                });
            }
            
            // Clear validation on focus (for better UX)
            fieldData.input.addEventListener('focus', () => {
                this.clearFieldValidation(fieldName);
            });
        });
    }
    
    /**
     * Add validation rule to a field
     */
    addRule(fieldName, rule) {
        const fieldData = this.fields.get(fieldName);
        if (!fieldData) return;
        
        fieldData.rules.push(rule);
    }
    
    /**
     * Validate a single field
     */
    validateField(fieldName) {
        const fieldData = this.fields.get(fieldName);
        if (!fieldData) return true;
        
        const value = fieldData.input.value.trim();
        let isValid = true;
        let errorMessage = '';
        
        // Run validation rules
        for (const rule of fieldData.rules) {
            const result = rule.validate(value, fieldData.input);
            if (!result.isValid) {
                isValid = false;
                errorMessage = result.message;
                break;
            }
        }
        
        // Built-in HTML5 validation
        if (isValid && !fieldData.input.checkValidity()) {
            isValid = false;
            errorMessage = fieldData.input.validationMessage;
        }
        
        this.setFieldValidationState(fieldName, isValid, errorMessage);
        
        return isValid;
    }
    
    /**
     * Set field validation state
     */
    setFieldValidationState(fieldName, isValid, message = '') {
        const fieldData = this.fields.get(fieldName);
        if (!fieldData) return;
        
        // Remove existing validation classes
        fieldData.container.classList.remove('has-error', 'has-success', 'has-warning');
        
        // Update validation state
        fieldData.isValid = isValid;
        
        if (isValid === null) {
            // Neutral state
            this.hideValidationMessage(fieldData);
            this.hideValidationIcon(fieldData);
        } else if (isValid) {
            // Success state
            if (this.options.showSuccessStates) {
                fieldData.container.classList.add('has-success');
                this.showValidationIcon(fieldData, 'success');
            }
            this.hideValidationMessage(fieldData);
        } else {
            // Error state
            fieldData.container.classList.add('has-error');
            this.showValidationMessage(fieldData, message, 'error');
            this.showValidationIcon(fieldData, 'error');
            
            // Add shake animation
            fieldData.container.classList.add('shake');
            setTimeout(() => {
                fieldData.container.classList.remove('shake');
            }, 500);
        }
        
        // Announce to screen readers
        if (this.options.accessibilityMode && !isValid && message) {
            this.announceToScreenReader(`${fieldData.input.labels[0]?.textContent || 'Field'}: ${message}`);
        }
    }
    
    /**
     * Show validation message
     */
    showValidationMessage(fieldData, message, type = 'error') {
        if (!fieldData.validationMessage) return;
        
        fieldData.validationMessage.textContent = message;
        fieldData.validationMessage.className = `form-validation-message form-validation-${type} slide-in`;
        fieldData.validationMessage.style.display = 'flex';
        
        // Set ARIA attributes
        fieldData.validationMessage.setAttribute('role', 'alert');
        fieldData.input.setAttribute('aria-describedby', fieldData.validationMessage.id || '');
        fieldData.input.setAttribute('aria-invalid', type === 'error' ? 'true' : 'false');
    }
    
    /**
     * Hide validation message
     */
    hideValidationMessage(fieldData) {
        if (!fieldData.validationMessage) return;
        
        fieldData.validationMessage.classList.add('slide-out');
        setTimeout(() => {
            fieldData.validationMessage.style.display = 'none';
            fieldData.validationMessage.classList.remove('slide-out');
        }, 300);
        
        // Remove ARIA attributes
        fieldData.input.removeAttribute('aria-describedby');
        fieldData.input.setAttribute('aria-invalid', 'false');
    }
    
    /**
     * Show validation icon
     */
    showValidationIcon(fieldData, type) {
        if (!fieldData.validationIcon) return;
        
        fieldData.validationIcon.className = `form-validation-icon ${type}`;
        fieldData.validationIcon.innerHTML = this.getValidationIconSVG(type);
        fieldData.validationIcon.style.display = 'block';
    }
    
    /**
     * Hide validation icon
     */
    hideValidationIcon(fieldData) {
        if (!fieldData.validationIcon) return;
        
        fieldData.validationIcon.style.display = 'none';
    }
    
    /**
     * Clear field validation
     */
    clearFieldValidation(fieldName) {
        const fieldData = this.fields.get(fieldName);
        if (!fieldData) return;
        
        fieldData.isValid = null;
        this.setFieldValidationState(fieldName, null);
    }
    
    /**
     * Validate entire form
     */
    validateForm() {
        let isFormValid = true;
        const errors = [];
        
        this.fields.forEach((fieldData, fieldName) => {
            const isFieldValid = this.validateField(fieldName);
            if (!isFieldValid) {
                isFormValid = false;
                const label = fieldData.input.labels[0]?.textContent || fieldName;
                const message = fieldData.validationMessage?.textContent || 'Invalid input';
                errors.push({ field: label, message });
            }
        });
        
        // Show form-level validation summary if there are errors
        if (!isFormValid) {
            this.showValidationSummary(errors);
        } else {
            this.hideValidationSummary();
        }
        
        return isFormValid;
    }
    
    /**
     * Show validation summary
     */
    showValidationSummary(errors) {
        let summary = this.form.querySelector('.form-validation-summary');
        
        if (!summary) {
            summary = document.createElement('div');
            summary.className = 'form-validation-summary';
            this.form.insertBefore(summary, this.form.firstChild);
        }
        
        const errorList = errors.map(error => 
            `<li class="form-validation-summary-item">${error.message}</li>`
        ).join('');
        
        summary.innerHTML = `
            <div class="form-validation-summary-title">Please correct the following errors:</div>
            <ul class="form-validation-summary-list">${errorList}</ul>
        `;
        
        summary.setAttribute('role', 'alert');
        summary.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    /**
     * Hide validation summary
     */
    hideValidationSummary() {
        const summary = this.form.querySelector('.form-validation-summary');
        if (summary) {
            summary.remove();
        }
    }
    
    /**
     * Focus first error field
     */
    focusFirstError() {
        for (const [fieldName, fieldData] of this.fields) {
            if (fieldData.isValid === false) {
                fieldData.input.focus();
                break;
            }
        }
    }
    
    /**
     * Announce message to screen readers
     */
    announceToScreenReader(message) {
        if (!this.liveRegion) return;
        
        this.liveRegion.textContent = message;
        
        // Clear after announcement
        setTimeout(() => {
            this.liveRegion.textContent = '';
        }, 1000);
    }
    
    /**
     * Get validation icon SVG
     */
    getValidationIconSVG(type) {
        const icons = {
            success: `<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>`,
            error: `<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>`,
            warning: `<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>`,
            loading: `<svg fill="currentColor" viewBox="0 0 20 20"><path d="M10 3a7 7 0 100 14 7 7 0 000-14zM2 10a8 8 0 1116 0 8 8 0 01-16 0z"></path></svg>`
        };
        
        return icons[type] || icons.loading;
    }
    
    /**
     * Debounce utility function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

/**
 * Common validation rules
 */
const ValidationRules = {
    required: (message = 'This field is required') => ({
        validate: (value) => ({
            isValid: value.length > 0,
            message
        })
    }),
    
    email: (message = 'Please enter a valid email address') => ({
        validate: (value) => {
            if (value.length === 0) return { isValid: true, message: '' };
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return {
                isValid: emailRegex.test(value),
                message
            };
        }
    }),
    
    minLength: (min, message) => ({
        validate: (value) => ({
            isValid: value.length >= min,
            message: message || `Must be at least ${min} characters long`
        })
    }),
    
    maxLength: (max, message) => ({
        validate: (value) => ({
            isValid: value.length <= max,
            message: message || `Must be no more than ${max} characters long`
        })
    }),
    
    pattern: (regex, message = 'Invalid format') => ({
        validate: (value) => {
            if (value.length === 0) return { isValid: true, message: '' };
            return {
                isValid: regex.test(value),
                message
            };
        }
    }),
    
    passwordStrength: (message = 'Password must be at least 8 characters with uppercase, lowercase, and numbers') => ({
        validate: (value) => {
            if (value.length === 0) return { isValid: true, message: '' };
            
            const hasLength = value.length >= 8;
            const hasUpper = /[A-Z]/.test(value);
            const hasLower = /[a-z]/.test(value);
            const hasNumber = /[0-9]/.test(value);
            
            return {
                isValid: hasLength && hasUpper && hasLower && hasNumber,
                message
            };
        }
    }),
    
    confirmPassword: (originalFieldName, message = 'Passwords do not match') => ({
        validate: (value, input) => {
            const form = input.closest('form');
            const originalField = form.querySelector(`[name="${originalFieldName}"]`);
            
            return {
                isValid: originalField ? value === originalField.value : true,
                message
            };
        }
    })
};

/**
 * Auto-initialize form validation on page load
 */
document.addEventListener('DOMContentLoaded', () => {
    // Auto-initialize forms with data-validate attribute
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        const validator = new FormValidator(form);
        
        // Add common validation rules based on input attributes
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            const fieldName = input.name || input.id;
            if (!fieldName) return;
            
            // Required validation
            if (input.hasAttribute('required')) {
                validator.addRule(fieldName, ValidationRules.required());
            }
            
            // Email validation
            if (input.type === 'email') {
                validator.addRule(fieldName, ValidationRules.email());
            }
            
            // Min/Max length validation
            const minLength = input.getAttribute('minlength');
            if (minLength) {
                validator.addRule(fieldName, ValidationRules.minLength(parseInt(minLength)));
            }
            
            const maxLength = input.getAttribute('maxlength');
            if (maxLength) {
                validator.addRule(fieldName, ValidationRules.maxLength(parseInt(maxLength)));
            }
            
            // Pattern validation
            const pattern = input.getAttribute('pattern');
            if (pattern) {
                validator.addRule(fieldName, ValidationRules.pattern(new RegExp(pattern)));
            }
            
            // Password strength validation
            if (input.type === 'password' && input.hasAttribute('data-strength')) {
                validator.addRule(fieldName, ValidationRules.passwordStrength());
            }
            
            // Confirm password validation
            const confirmFor = input.getAttribute('data-confirm');
            if (confirmFor) {
                validator.addRule(fieldName, ValidationRules.confirmPassword(confirmFor));
            }
        });
        
        // Store validator instance on form for external access
        form.formValidator = validator;
    });
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { FormValidator, ValidationRules };
}