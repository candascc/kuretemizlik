/**
 * Payment Overpayment Prevention - LOGIC-002
 * 
 * Client-side validation to prevent overpayment
 * Real-time feedback for payment amounts
 */

const PAYMENT_VALIDATION_DEBUG =
    typeof window !== 'undefined' && window.PAYMENT_VALIDATION_DEBUG;

class PaymentValidation {
    constructor() {
        this.init();
    }
    
    init() {
        // Enhance all payment amount inputs
        this.enhancePaymentInputs();
    }
    
    enhancePaymentInputs() {
        const paymentInputs = document.querySelectorAll('input[name*="payment"], input[name*="amount"]');
        
        paymentInputs.forEach(input => {
            const form = input.closest('form');
            if (PAYMENT_VALIDATION_DEBUG) {
                const formId = form ? (form.getAttribute('id') || form.getAttribute('action') || 'anonymous-form') : 'no-form';
                console.debug('[payment-validation] inspecting input', {
                    name: input.name,
                    form: formId,
                    paymentSkip: form?.dataset?.paymentSkip
                });
            }

            const skipHost = input.closest('[data-payment-skip="true"]');
            if (skipHost) {
                if (PAYMENT_VALIDATION_DEBUG) {
                    console.debug('[payment-validation] skip (ancestor data-payment-skip)', input.name);
                }
                return;
            }

            if (!form) {
                if (PAYMENT_VALIDATION_DEBUG) {
                    console.debug('[payment-validation] skip (no form)', input.name);
                }
                return;
            }

            if (form.dataset && form.dataset.paymentSkip === 'true') {
                if (PAYMENT_VALIDATION_DEBUG) {
                    console.debug('[payment-validation] skip (data-payment-skip=true)', input.name);
                }
                return;
            }
            
            // Skip if already enhanced
            if (input.dataset.paymentValidated) return;
            input.dataset.paymentValidated = 'true';
            
            // Find related total amount
            const totalInput = form?.querySelector('input[name*="total"], [data-total-amount]');
            if (PAYMENT_VALIDATION_DEBUG) {
                console.debug('[payment-validation] attach', {
                    input: input.name,
                    totalField: totalInput ? (totalInput.getAttribute('name') || totalInput.dataset?.totalAmount || 'unknown') : 'not-found'
                });
            }
            
            if (!totalInput) return;
            
            // Add real-time validation
            input.addEventListener('input', () => {
                this.validatePaymentAmount(input, totalInput);
            });
            
            // Add blur validation
            input.addEventListener('blur', () => {
                this.validatePaymentAmount(input, totalInput);
            });
        });
    }
    
    validatePaymentAmount(paymentInput, totalInput) {
        const paymentAmount = parseFloat(paymentInput.value) || 0;
        const totalAmount = parseFloat(totalInput.value || totalInput.dataset.totalAmount) || 0;
        if (PAYMENT_VALIDATION_DEBUG) {
            console.debug('[payment-validation] validate', {
                input: paymentInput.name,
                paymentAmount,
                totalAmount
            });
        }
        
        // Remove any existing warnings
        this.removeWarning(paymentInput);
        
        if (paymentAmount > totalAmount) {
            this.showOverpaymentWarning(paymentInput, paymentAmount, totalAmount);
            
            // Auto-correct (optional - can be disabled)
            if (paymentInput.dataset.autoCorrect !== 'false') {
                if (PAYMENT_VALIDATION_DEBUG) {
                    console.debug('[payment-validation] auto-correct', {
                        input: paymentInput.name,
                        enforcedValue: totalAmount
                    });
                }
                paymentInput.value = totalAmount.toFixed(2);
            }
            
            return false;
        } else if (paymentAmount < 0) {
            this.showNegativeWarning(paymentInput);
            paymentInput.value = 0;
            return false;
        }
        
        return true;
    }
    
    showOverpaymentWarning(input, payment, total) {
        const warning = document.createElement('div');
        warning.className = 'payment-warning mt-2 flex items-start space-x-2 text-amber-600 dark:text-amber-400 animate-shake';
        
        // Create icon element
        const icon = document.createElement('i');
        icon.className = 'fas fa-exclamation-triangle mt-0.5';
        icon.setAttribute('aria-hidden', 'true');
        
        // Create text container
        const textContainer = document.createElement('div');
        textContainer.className = 'text-sm';
        
        // Create main text
        const mainText = document.createElement('strong');
        mainText.textContent = 'Dikkat! ';
        
        // Create message text
        const messageText = document.createTextNode(
            `Ödeme tutarı (${payment.toFixed(2)} ₺) toplam tutardan (${total.toFixed(2)} ₺) fazla olamaz.`
        );
        
        textContainer.appendChild(mainText);
        textContainer.appendChild(messageText);
        
        // Add auto-correct message if applicable
        if (input.dataset.autoCorrect !== 'false') {
            const br = document.createElement('br');
            const autoCorrectText = document.createElement('em');
            autoCorrectText.textContent = 'Otomatik düzeltildi.';
            textContainer.appendChild(br);
            textContainer.appendChild(autoCorrectText);
        }
        
        warning.appendChild(icon);
        warning.appendChild(textContainer);
        
        input.parentElement.appendChild(warning);
        
        // Add error border
        input.classList.add('border-amber-500', 'focus:ring-amber-500');
        
        // Shake animation
        input.classList.add('animate-shake');
        setTimeout(() => input.classList.remove('animate-shake'), 500);
    }
    
    showNegativeWarning(input) {
        const warning = document.createElement('div');
        warning.className = 'payment-warning mt-2 text-sm text-red-600 dark:text-red-400';
        
        // Create icon element
        const icon = document.createElement('i');
        icon.className = 'fas fa-times-circle mr-2';
        icon.setAttribute('aria-hidden', 'true');
        
        // Create message text
        const messageText = document.createTextNode('Ödeme tutarı negatif olamaz.');
        
        warning.appendChild(icon);
        warning.appendChild(messageText);
        
        input.parentElement.appendChild(warning);
        input.classList.add('border-red-500');
    }
    
    removeWarning(input) {
        const existing = input.parentElement.querySelector('.payment-warning');
        if (existing) {
            existing.remove();
        }
        
        input.classList.remove('border-amber-500', 'focus:ring-amber-500', 'border-red-500');
    }
    
    /**
     * Validate multi-fee payment total
     */
    static validateMultiFeeTotal(selectedFees, paymentAmount) {
        const total = selectedFees.reduce((sum, fee) => sum + parseFloat(fee.amount || 0), 0);
        
        if (paymentAmount > total) {
            return {
                valid: false,
                error: `Ödeme tutarı (${paymentAmount.toFixed(2)} ₺) seçilen ücretlerin toplamından (${total.toFixed(2)} ₺) fazla olamaz.`
            };
        }
        
        return { valid: true };
    }
    
    /**
     * Validate partial payment
     */
    static validatePartialPayment(totalAmount, paidSoFar, newPayment) {
        const remaining = totalAmount - paidSoFar;
        
        if (newPayment > remaining) {
            return {
                valid: false,
                error: `Kalan borç ${remaining.toFixed(2)} ₺. Daha fazla ödeme yapılamaz.`,
                suggestedAmount: remaining
            };
        }
        
        return { valid: true };
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    window.paymentValidation = new PaymentValidation();
});

window.PaymentValidation = PaymentValidation;

