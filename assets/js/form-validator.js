/**
 * Form Validator - Client-side form validation library
 * Provides real-time validation with customizable rules
 */

class FormValidator {
    constructor(form, rules = {}) {
        this.form = form;
        this.rules = rules;
        this.errors = {};
        this.fieldCallbacks = {};
        
        this.init();
    }
    
    init() {
        // Auto-detect validation rules from data-validate attributes
        const fields = this.form.querySelectorAll('[data-validate]');
        fields.forEach(field => {
            const rules = this.parseDataValidate(field.getAttribute('data-validate'));
            this.rules[field.name] = rules;
            
            // Attach validation on blur
            field.addEventListener('blur', () => {
                this.validateField(field.name);
            });
            
            // Real-time validation on input
            field.addEventListener('input', () => {
                if (this.errors[field.name]) {
                    this.validateField(field.name);
                }
            });
        });
        
        // Form submission validation
        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                this.showFirstError();
            }
        });
    }
    
    parseDataValidate(dataValidate) {
        if (!dataValidate) return {};
        
        const rules = {};
        const ruleStrings = dataValidate.split('|');
        
        ruleStrings.forEach(rule => {
            if (rule.includes(':')) {
                const [key, value] = rule.split(':');
                rules[key] = isNaN(value) ? value : parseInt(value);
            } else {
                rules[rule] = true;
            }
        });
        
        return rules;
    }
    
    validate(fieldName, value) {
        const rule = this.rules[fieldName];
        if (!rule) return null;
        
        // Required validation
        if (rule.required && (!value || value.trim() === '')) {
            return 'Bu alan zorunludur';
        }
        
        // Skip other validations if field is empty and not required
        if (!value || value.trim() === '') {
            return null;
        }
        
        // Email validation
        if (rule.email && !this.isValidEmail(value)) {
            return 'Geçerli bir e-posta adresi giriniz';
        }
        
        // Phone validation
        if (rule.phone && !this.isValidPhone(value)) {
            return 'Geçerli bir telefon numarası giriniz (10-11 hane)';
        }
        
        // Min length validation
        if (rule.min && value.length < rule.min) {
            return `Minimum ${rule.min} karakter olmalıdır`;
        }
        
        // Max length validation
        if (rule.max && value.length > rule.max) {
            return `Maksimum ${rule.max} karakter olmalıdır`;
        }
        
        // Pattern validation (for custom regex)
        if (rule.pattern && !new RegExp(rule.pattern).test(value)) {
            return rule.message || 'Geçersiz format';
        }
        
        // URL validation
        if (rule.url && !this.isValidUrl(value)) {
            return 'Geçerli bir URL giriniz';
        }
        
        // Numeric validation
        if (rule.numeric && isNaN(value)) {
            return 'Sadece sayı girebilirsiniz';
        }
        
        // Decimal validation
        if (rule.decimal && !this.isValidDecimal(value)) {
            return 'Geçerli bir ondalık sayı giriniz';
        }
        
        return null;
    }
    
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    isValidPhone(phone) {
        // Remove spaces, dashes, and parentheses
        const cleaned = phone.replace(/[\s\-\(\)]/g, '');
        return /^[0-9]{10,11}$/.test(cleaned);
    }
    
    isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
    
    isValidDecimal(value) {
        return /^\d+(\.\d+)?$/.test(value);
    }
    
    validateField(fieldName) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (!field) return false;
        
        const value = field.value;
        const error = this.validate(fieldName, value);
        
        if (error) {
            this.showError(fieldName, error);
            return false;
        } else {
            this.clearError(fieldName);
            return true;
        }
    }
    
    validateForm() {
        let isValid = true;
        this.errors = {};
        
        // Validate all fields with rules
        Object.keys(this.rules).forEach(fieldName => {
            if (!this.validateField(fieldName)) {
                isValid = false;
                this.errors[fieldName] = this.validate(fieldName, 
                    this.form.querySelector(`[name="${fieldName}"]`)?.value
                );
            }
        });
        
        return isValid;
    }
    
    showError(fieldName, message) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (!field) return;
        
        // Add error class to field
        field.classList.add('border-red-500', 'focus:ring-red-500');
        field.classList.remove('border-green-500', 'focus:ring-green-500');
        field.setAttribute('aria-invalid', 'true');
        
        // Show error message
        const errorElement = field.parentElement.querySelector('.field-error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
            errorElement.setAttribute('role', 'alert');
        }
        
        // Store error
        this.errors[fieldName] = message;
        
        // Trigger custom callback
        if (this.fieldCallbacks[fieldName]) {
            this.fieldCallbacks[fieldName]('error', message);
        }

        // Announce error to ARIA live region (global)
        try {
            const errorRegion = document.getElementById('aria-error-region');
            if (errorRegion) {
                const label = this.getFieldLabel(field) || fieldName;
                errorRegion.textContent = `${label}: ${message}`;
            }
        } catch (e) {
            // no-op
        }
    }
    
    clearError(fieldName) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (!field) return;
        
        // Remove error class, add success class
        field.classList.remove('border-red-500', 'focus:ring-red-500');
        
        // Only show success border if field has been touched and has a value
        if (field.value && field.classList.contains('border-gray-300')) {
            field.classList.add('border-green-500', 'focus:ring-green-500');
        }
        
        field.setAttribute('aria-invalid', 'false');
        
        // Hide error message
        const errorElement = field.parentElement.querySelector('.field-error');
        if (errorElement) {
            errorElement.classList.add('hidden');
            errorElement.removeAttribute('role');
        }
        
        // Remove error from store
        delete this.errors[fieldName];
        
        // Trigger custom callback
        if (this.fieldCallbacks[fieldName]) {
            this.fieldCallbacks[fieldName]('success', null);
        }

        // Announce success to ARIA status region (polite)
        try {
            const statusRegion = document.getElementById('aria-status-region');
            if (statusRegion) {
                const label = this.getFieldLabel(field) || fieldName;
                statusRegion.textContent = `${label} geçerli.`;
            }
        } catch (e) {
            // no-op
        }
    }
    
    showFirstError() {
        const firstErrorField = Object.keys(this.errors)[0];
        if (firstErrorField) {
            const field = this.form.querySelector(`[name="${firstErrorField}"]`);
            if (field) {
                field.focus();
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }
    
    // Callback registration
    onFieldValidation(fieldName, callback) {
        this.fieldCallbacks[fieldName] = callback;
    }
    
    // Get all errors
    getErrors() {
        return this.errors;
    }
    
    // Check if form has errors
    hasErrors() {
        return Object.keys(this.errors).length > 0;
    }

    // Helpers
    getFieldLabel(field) {
        try {
            // Prefer aria-label
            if (field.getAttribute('aria-label')) {
                return field.getAttribute('aria-label');
            }
            // Associate via label[for]
            if (field.id) {
                const label = this.form.querySelector(`label[for="${field.id}"]`);
                if (label) {
                    return label.innerText.replace('*', '').trim();
                }
            }
            // Fallback to nearest preceding label
            const parentLabel = field.closest('div')?.querySelector('label');
            if (parentLabel) {
                return parentLabel.innerText.replace('*', '').trim();
            }
        } catch (e) {
            // ignore
        }
        return null;
    }
}

// Global usage
window.FormValidator = FormValidator;

// Auto-initialize forms with data-validate attribute
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[data-validate="true"]');
    forms.forEach(form => {
        new FormValidator(form);
    });
});

