/**
 * Authentication Forms Enhancement JavaScript
 * Provides enhanced UX for login and registration forms
 * Includes accessibility features, animations, and user feedback
 */

class AuthFormEnhancer {
    constructor() {
        this.init();
    }

    init() {
        this.setupPasswordStrengthIndicator();
        this.setupFormAnimations();
        this.setupAccessibilityFeatures();
        this.setupProgressiveEnhancement();
        this.setupFormPersistence();
    }

    /**
     * Enhanced password strength indicator
     */
    setupPasswordStrengthIndicator() {
        const passwordFields = document.querySelectorAll('input[type="password"][data-strength]');
        
        passwordFields.forEach(field => {
            this.createPasswordStrengthIndicator(field);
        });
    }

    createPasswordStrengthIndicator(passwordField) {
        const container = passwordField.closest('.form-field');
        if (!container) return;

        // Create strength indicator
        const strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'password-strength-indicator mt-3';
        strengthIndicator.innerHTML = `
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-medium text-gray-600">Password Strength</span>
                <span class="password-strength-text text-xs font-medium text-gray-500">Enter password</span>
            </div>
            <div class="password-strength-bar h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="password-strength-fill h-full transition-all duration-300 rounded-full"></div>
            </div>
            <div class="password-requirements mt-2 space-y-1 text-xs text-gray-600">
                <div class="requirement" data-requirement="length">
                    <span class="requirement-icon">○</span>
                    <span>At least 6 characters</span>
                </div>
                <div class="requirement" data-requirement="uppercase">
                    <span class="requirement-icon">○</span>
                    <span>One uppercase letter</span>
                </div>
                <div class="requirement" data-requirement="lowercase">
                    <span class="requirement-icon">○</span>
                    <span>One lowercase letter</span>
                </div>
                <div class="requirement" data-requirement="number">
                    <span class="requirement-icon">○</span>
                    <span>One number</span>
                </div>
            </div>
        `;

        container.appendChild(strengthIndicator);

        const strengthFill = strengthIndicator.querySelector('.password-strength-fill');
        const strengthText = strengthIndicator.querySelector('.password-strength-text');
        const requirements = strengthIndicator.querySelectorAll('.requirement');

        passwordField.addEventListener('input', () => {
            this.updatePasswordStrength(passwordField.value, strengthFill, strengthText, requirements);
        });
    }

    updatePasswordStrength(password, strengthFill, strengthText, requirements) {
        const checks = {
            length: password.length >= 6,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password)
        };

        let score = 0;
        let metRequirements = 0;

        // Update requirement indicators
        requirements.forEach(req => {
            const requirement = req.dataset.requirement;
            const icon = req.querySelector('.requirement-icon');
            const met = checks[requirement];

            if (met) {
                req.classList.add('text-green-600');
                req.classList.remove('text-gray-600');
                icon.textContent = '✓';
                icon.classList.add('text-green-600');
                metRequirements++;
            } else {
                req.classList.remove('text-green-600');
                req.classList.add('text-gray-600');
                icon.textContent = '○';
                icon.classList.remove('text-green-600');
            }
        });

        // Calculate score
        score = metRequirements;

        // Additional scoring for length
        if (password.length >= 8) score += 0.5;
        if (password.length >= 12) score += 0.5;

        // Penalty for common patterns
        if (/(.)\1{2,}/.test(password)) score -= 0.5; // Repeated characters
        if (/123|abc|qwe|password/i.test(password)) score -= 1; // Common patterns

        score = Math.max(0, Math.min(5, score));

        // Update visual indicator
        const percentage = (score / 5) * 100;
        strengthFill.style.width = `${percentage}%`;

        // Update colors and text
        strengthFill.classList.remove('bg-red-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500');
        strengthText.classList.remove('text-red-600', 'text-yellow-600', 'text-blue-600', 'text-green-600');

        if (password.length === 0) {
            strengthText.textContent = 'Enter password';
            strengthText.classList.add('text-gray-500');
        } else if (score < 2) {
            strengthFill.classList.add('bg-red-500');
            strengthText.classList.add('text-red-600');
            strengthText.textContent = 'Weak';
        } else if (score < 3) {
            strengthFill.classList.add('bg-yellow-500');
            strengthText.classList.add('text-yellow-600');
            strengthText.textContent = 'Fair';
        } else if (score < 4) {
            strengthFill.classList.add('bg-blue-500');
            strengthText.classList.add('text-blue-600');
            strengthText.textContent = 'Good';
        } else {
            strengthFill.classList.add('bg-green-500');
            strengthText.classList.add('text-green-600');
            strengthText.textContent = 'Strong';
        }
    }

    /**
     * Setup form animations and micro-interactions
     */
    setupFormAnimations() {
        // Floating label animations
        const floatingInputs = document.querySelectorAll('.form-field-floating input');
        
        floatingInputs.forEach(input => {
            // Initial state check
            this.updateFloatingLabel(input);
            
            input.addEventListener('input', () => this.updateFloatingLabel(input));
            input.addEventListener('focus', () => this.handleInputFocus(input));
            input.addEventListener('blur', () => this.handleInputBlur(input));
        });

        // Button hover effects
        const buttons = document.querySelectorAll('.form-button-primary');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', this.handleButtonHover);
            button.addEventListener('mouseleave', this.handleButtonLeave);
        });

        // Form card entrance animation
        const formCard = document.querySelector('.form-card');
        if (formCard) {
            formCard.style.opacity = '0';
            formCard.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                formCard.style.transition = 'all 0.6s ease-out';
                formCard.style.opacity = '1';
                formCard.style.transform = 'translateY(0)';
            }, 100);
        }
    }

    updateFloatingLabel(input) {
        const hasValue = input.value.trim() !== '';
        input.classList.toggle('has-value', hasValue);
    }

    handleInputFocus(input) {
        const field = input.closest('.form-field');
        if (field) {
            field.classList.add('focused');
        }
    }

    handleInputBlur(input) {
        const field = input.closest('.form-field');
        if (field) {
            field.classList.remove('focused');
        }
    }

    handleButtonHover(e) {
        const button = e.target;
        const ripple = document.createElement('div');
        ripple.className = 'absolute inset-0 bg-white opacity-10 rounded-lg transform scale-0 transition-transform duration-300';
        button.appendChild(ripple);
        
        setTimeout(() => {
            ripple.style.transform = 'scale(1)';
        }, 10);
    }

    handleButtonLeave(e) {
        const button = e.target;
        const ripples = button.querySelectorAll('.absolute.inset-0.bg-white');
        ripples.forEach(ripple => {
            ripple.style.transform = 'scale(0)';
            setTimeout(() => {
                if (ripple.parentNode) {
                    ripple.parentNode.removeChild(ripple);
                }
            }, 300);
        });
    }

    /**
     * Enhanced accessibility features
     */
    setupAccessibilityFeatures() {
        // Keyboard navigation enhancements
        document.addEventListener('keydown', this.handleKeyboardNavigation.bind(this));
        
        // Screen reader announcements
        this.setupScreenReaderAnnouncements();
        
        // Focus management
        this.setupFocusManagement();
        
        // ARIA live regions
        this.setupAriaLiveRegions();
    }

    handleKeyboardNavigation(e) {
        // Enhanced tab navigation
        if (e.key === 'Tab') {
            this.highlightFocusedElement();
        }
        
        // Escape key handling
        if (e.key === 'Escape') {
            this.handleEscapeKey();
        }
        
        // Enter key on buttons
        if (e.key === 'Enter' && e.target.tagName === 'BUTTON') {
            e.target.click();
        }
    }

    highlightFocusedElement() {
        // Remove previous highlights
        document.querySelectorAll('.keyboard-focused').forEach(el => {
            el.classList.remove('keyboard-focused');
        });
        
        // Add highlight to currently focused element
        setTimeout(() => {
            if (document.activeElement) {
                document.activeElement.classList.add('keyboard-focused');
            }
        }, 10);
    }

    handleEscapeKey() {
        // Clear form validation messages
        const validationMessages = document.querySelectorAll('.form-validation-message');
        validationMessages.forEach(msg => {
            if (msg.style.display !== 'none') {
                msg.style.opacity = '0';
                setTimeout(() => {
                    msg.style.display = 'none';
                    msg.style.opacity = '1';
                }, 200);
            }
        });
        
        // Hide loading overlay
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay && !loadingOverlay.classList.contains('hidden')) {
            loadingOverlay.classList.add('hidden');
        }
    }

    setupScreenReaderAnnouncements() {
        // Create announcement region
        const announcer = document.createElement('div');
        announcer.id = 'screen-reader-announcer';
        announcer.setAttribute('aria-live', 'polite');
        announcer.setAttribute('aria-atomic', 'true');
        announcer.className = 'sr-only';
        document.body.appendChild(announcer);
        
        this.announcer = announcer;
    }

    announce(message) {
        if (this.announcer) {
            this.announcer.textContent = message;
            setTimeout(() => {
                this.announcer.textContent = '';
            }, 1000);
        }
    }

    setupFocusManagement() {
        // Focus first input on page load
        const firstInput = document.querySelector('.form-field input:not([type="hidden"])');
        if (firstInput) {
            setTimeout(() => {
                firstInput.focus();
            }, 500);
        }
        
        // Focus management for validation errors
        const form = document.querySelector('form[data-validate]');
        if (form) {
            form.addEventListener('submit', (e) => {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    this.focusFirstError();
                }
            });
        }
    }

    focusFirstError() {
        const firstError = document.querySelector('.form-field.has-error input, .form-field.has-error textarea, .form-field.has-error select');
        if (firstError) {
            firstError.focus();
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Announce error to screen reader
            const errorMessage = firstError.closest('.form-field').querySelector('.form-validation-message');
            if (errorMessage) {
                this.announce(`Error: ${errorMessage.textContent}`);
            }
        }
    }

    setupAriaLiveRegions() {
        // Setup live region for form status updates
        const liveRegion = document.createElement('div');
        liveRegion.id = 'form-status-live-region';
        liveRegion.setAttribute('aria-live', 'assertive');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.className = 'sr-only';
        document.body.appendChild(liveRegion);
        
        this.liveRegion = liveRegion;
    }

    /**
     * Progressive enhancement features
     */
    setupProgressiveEnhancement() {
        // Add enhanced features only if JavaScript is enabled
        document.documentElement.classList.add('js-enabled');
        
        // Setup advanced form features
        this.setupFormAutoSave();
        this.setupSmartValidation();
        this.setupFormAnalytics();
    }

    setupFormAutoSave() {
        const form = document.querySelector('form[data-validate]');
        if (!form) return;
        
        const inputs = form.querySelectorAll('input:not([type="password"]), textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('input', this.debounce(() => {
                this.saveFormData(input);
            }, 1000));
        });
        
        // Restore form data on page load
        this.restoreFormData();
    }

    saveFormData(input) {
        if (input.type === 'password') return; // Never save passwords
        
        const key = `form_${input.name || input.id}`;
        const value = input.value;
        
        try {
            localStorage.setItem(key, value);
        } catch (e) {
            // Handle localStorage errors gracefully
            console.warn('Could not save form data:', e);
        }
    }

    restoreFormData() {
        const form = document.querySelector('form[data-validate]');
        if (!form) return;
        
        const inputs = form.querySelectorAll('input:not([type="password"]), textarea, select');
        
        inputs.forEach(input => {
            const key = `form_${input.name || input.id}`;
            
            try {
                const savedValue = localStorage.getItem(key);
                if (savedValue && !input.value) {
                    input.value = savedValue;
                    this.updateFloatingLabel(input);
                }
            } catch (e) {
                // Handle localStorage errors gracefully
                console.warn('Could not restore form data:', e);
            }
        });
    }

    setupSmartValidation() {
        // Implement smart validation timing
        const inputs = document.querySelectorAll('.form-field input, .form-field textarea, .form-field select');
        
        inputs.forEach(input => {
            let hasInteracted = false;
            
            input.addEventListener('focus', () => {
                hasInteracted = true;
            });
            
            input.addEventListener('input', this.debounce(() => {
                if (hasInteracted && input.value.length > 0) {
                    this.validateField(input);
                }
            }, 500));
            
            input.addEventListener('blur', () => {
                if (hasInteracted) {
                    this.validateField(input);
                }
            });
        });
    }

    validateField(input) {
        const form = input.closest('form');
        if (form && form.formValidator) {
            const fieldName = input.name || input.id;
            form.formValidator.validateField(fieldName);
        }
    }

    setupFormAnalytics() {
        // Track form interaction patterns (privacy-friendly)
        const form = document.querySelector('form[data-validate]');
        if (!form) return;
        
        let formStartTime = null;
        let fieldInteractions = {};
        
        form.addEventListener('focusin', (e) => {
            if (!formStartTime) {
                formStartTime = Date.now();
            }
            
            const fieldName = e.target.name || e.target.id;
            if (fieldName && !fieldInteractions[fieldName]) {
                fieldInteractions[fieldName] = {
                    firstFocus: Date.now() - formStartTime,
                    focusCount: 0
                };
            }
            
            if (fieldInteractions[fieldName]) {
                fieldInteractions[fieldName].focusCount++;
            }
        });
        
        form.addEventListener('submit', () => {
            const completionTime = Date.now() - formStartTime;
            
            // Log analytics data (could be sent to analytics service)
            console.log('Form Analytics:', {
                completionTime,
                fieldInteractions,
                timestamp: new Date().toISOString()
            });
        });
    }

    /**
     * Form persistence for better UX
     */
    setupFormPersistence() {
        // Clear saved form data on successful submission
        const form = document.querySelector('form[data-validate]');
        if (!form) return;
        
        form.addEventListener('submit', (e) => {
            // Only clear if form is valid
            if (form.checkValidity()) {
                this.clearSavedFormData();
            }
        });
        
        // Clear saved data on successful registration
        const successMessage = document.querySelector('.form-validation-success');
        if (successMessage) {
            this.clearSavedFormData();
        }
    }

    clearSavedFormData() {
        const form = document.querySelector('form[data-validate]');
        if (!form) return;
        
        const inputs = form.querySelectorAll('input:not([type="password"]), textarea, select');
        
        inputs.forEach(input => {
            const key = `form_${input.name || input.id}`;
            try {
                localStorage.removeItem(key);
            } catch (e) {
                console.warn('Could not clear saved form data:', e);
            }
        });
    }

    /**
     * Utility functions
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

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new AuthFormEnhancer();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthFormEnhancer;
}