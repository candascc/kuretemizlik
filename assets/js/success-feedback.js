/**
 * Success Feedback System - UI-POLISH-002
 * 
 * Celebratory feedback for important actions
 * Confetti, animations, micro-interactions
 */

class SuccessFeedback {
    constructor() {
        this.init();
    }
    
    init() {
        // Load confetti library if not already loaded
        this.loadConfettiLibrary();
        
        // Listen for success events
        this.setupEventListeners();
    }
    
    loadConfettiLibrary() {
        if (typeof confetti !== 'undefined') return;
        
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js';
        script.async = true;
        document.head.appendChild(script);
    }
    
    setupEventListeners() {
        // Listen for custom success events
        document.addEventListener('app:success', (e) => {
            this.celebrate(e.detail);
        });
        
        // Intercept form submissions that succeed
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.dataset.celebrateOnSuccess) {
                // Will trigger celebration after successful response
                form.dataset.pendingCelebration = form.dataset.celebrateOnSuccess;
            }
        });
    }
    
    /**
     * Trigger celebration
     */
    celebrate(options = {}) {
        const {
            type = 'default',
            message = 'Başarılı!',
            intensity = 'medium',
            sound = false
        } = options;
        
        // Show confetti
        this.showConfetti(type, intensity);
        
        // Show success toast
        this.showSuccessToast(message, type);
        
        // Play sound (if enabled)
        if (sound) {
            this.playSuccessSound();
        }
        
        // Micro-interaction on trigger element
        if (options.element) {
            this.addMicroInteraction(options.element);
        }
    }
    
    showConfetti(type, intensity) {
        if (typeof confetti === 'undefined') return;
        
        const configs = {
            minimal: {
                particleCount: 50,
                spread: 40,
                origin: { y: 0.6 }
            },
            medium: {
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            },
            celebration: {
                particleCount: 200,
                spread: 120,
                origin: { y: 0.6 },
                colors: ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b']
            },
            fireworks: {
                particleCount: 100,
                spread: 360,
                ticks: 50,
                gravity: 0,
                decay: 0.94,
                startVelocity: 30,
                colors: ['#FFD700', '#FF6347', '#00CED1']
            }
        };
        
        const config = configs[intensity] || configs.medium;
        
        // Special types
        if (type === 'job_created' || type === 'payment_complete') {
            // Celebration
            confetti(configs.celebration);
        } else if (type === 'milestone') {
            // Fireworks
            const duration = 3 * 1000;
            const end = Date.now() + duration;
            
            const interval = setInterval(() => {
                if (Date.now() > end) {
                    clearInterval(interval);
                    return;
                }
                
                confetti({
                    ...configs.fireworks,
                    origin: {
                        x: Math.random(),
                        y: Math.random() - 0.2
                    }
                });
            }, 250);
        } else {
            // Default
            confetti(config);
        }
    }
    
    showSuccessToast(message, type) {
        // Use existing toast system if available
        if (typeof Utils !== 'undefined' && Utils.showNotification) {
            Utils.showNotification(message, 'success');
            return;
        }
        
        // Fallback: Create toast
        const toast = document.createElement('div');
        toast.className = 'fixed top-20 right-4 z-50 bg-green-600 text-white px-6 py-4 rounded-lg shadow-2xl flex items-center space-x-3 animate-slide-in-right';
        toast.innerHTML = `
            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                <i class="fas fa-check text-xl"></i>
            </div>
            <div>
                <div class="font-bold">${message}</div>
                ${type ? `<div class="text-xs text-green-100">${type}</div>` : ''}
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Auto-remove
        setTimeout(() => {
            toast.classList.add('animate-slide-out-right');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    playSuccessSound() {
        // Simple success sound using Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (error) {
            console.log('Audio playback not supported');
        }
    }
    
    addMicroInteraction(element) {
        // Success pulse animation
        element.classList.add('animate-success-pulse');
        setTimeout(() => {
            element.classList.remove('animate-success-pulse');
        }, 600);
    }
    
    /**
     * Static helper: Quick celebrate
     */
    static celebrate(message = 'Başarılı!', type = 'default') {
        const feedback = new SuccessFeedback();
        feedback.celebrate({ message, type });
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    window.successFeedback = new SuccessFeedback();
});

// Export
window.SuccessFeedback = SuccessFeedback;

// Convenience global function
window.celebrate = (msg, type) => SuccessFeedback.celebrate(msg, type);

