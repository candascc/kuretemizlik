/**
 * Universal Button Loading State Handler
 * UX Quick Win: Consistent loading feedback
 */

class ButtonLoading {
    constructor(button) {
        this.button = button;
        this.originalContent = button.innerHTML;
        this.originalDisabled = button.disabled;
        this.originalClasses = button.className;
    }
    
    /**
     * Start loading state
     */
    start(message = 'İşleniyor...') {
        this.button.disabled = true;
        this.button.classList.add('btn-loading', 'pointer-events-none', 'opacity-70');
        
        this.button.innerHTML = `
            <i class="fas fa-spinner fa-spin mr-2"></i>
            <span>${message}</span>
        `;
    }
    
    /**
     * Stop loading and restore original state
     */
    stop() {
        this.button.disabled = this.originalDisabled;
        this.button.classList.remove('btn-loading', 'pointer-events-none', 'opacity-70', 'btn-success', 'btn-error');
        this.button.innerHTML = this.originalContent;
    }
    
    /**
     * Show success state temporarily
     */
    success(message = 'Başarılı!', duration = 2000) {
        this.button.disabled = false;
        this.button.classList.remove('btn-loading', 'opacity-70');
        this.button.classList.add('btn-success');
        
        this.button.innerHTML = `
            <i class="fas fa-check mr-2"></i>
            <span>${message}</span>
        `;
        
        setTimeout(() => {
            this.stop();
        }, duration);
    }
    
    /**
     * Show error state temporarily
     */
    error(message = 'Hata!', duration = 3000) {
        this.button.disabled = false;
        this.button.classList.remove('btn-loading', 'opacity-70');
        this.button.classList.add('btn-error');
        
        this.button.innerHTML = `
            <i class="fas fa-times mr-2"></i>
            <span>${message}</span>
        `;
        
        setTimeout(() => {
            this.stop();
        }, duration);
    }
}

/**
 * Auto-enhance forms with loading states
 */
function enhanceFormWithLoading(form) {
    const submitButton = form.querySelector('button[type="submit"]');
    if (!submitButton) return;
    
    form.addEventListener('submit', (e) => {
        // Only if not prevented by validation
        if (!form.checkValidity()) return;
        
        const loading = new ButtonLoading(submitButton);
        loading.start();
        
        // Store loading instance for manual control
        form._loadingButton = loading;
    });
}

// Auto-enhance all forms
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-loading="true"]').forEach(form => {
        enhanceFormWithLoading(form);
    });
});

// Expose globally
window.ButtonLoading = ButtonLoading;
window.enhanceFormWithLoading = enhanceFormWithLoading;

/**
 * CSS Styles (add to custom.css or inline)
 */
const styles = `
<style>
.btn-success {
    background-color: #10b981 !important;
    border-color: #059669 !important;
    color: white !important;
}

.btn-error {
    background-color: #ef4444 !important;
    border-color: #dc2626 !important;
    color: white !important;
}

@keyframes successPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.btn-success {
    animation: successPulse 0.3s ease-in-out;
}
</style>
`;

