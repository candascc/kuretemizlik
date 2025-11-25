/**
 * Modern Toast Notification System
 * Beautiful, non-intrusive notifications
 */

class ToastSystem {
    constructor() {
        this.container = null;
        this.toasts = [];
        this.init();
    }

    init() {
        this.createContainer();
        window.toast = this; // Global access
    }

    createContainer() {
        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        this.container.className = 'fixed top-4 right-4 z-50 space-y-2 max-w-md';
        document.body.appendChild(this.container);
    }

    show(message, type = 'info', duration = 5000, actions = []) {
        const id = 'toast-' + Date.now();
        const toast = this.createToast(id, message, type, actions);
        
        this.container.appendChild(toast);
        this.toasts.push({ id, element: toast });

        // Animate in
        setTimeout(() => {
            toast.classList.add('toast-enter');
        }, 10);

        // Auto dismiss
        if (duration > 0) {
            const timer = setTimeout(() => {
                this.hide(id);
            }, duration);

            // Pause on hover
            toast.addEventListener('mouseenter', () => {
                clearTimeout(timer);
            });

            toast.addEventListener('mouseleave', () => {
                setTimeout(() => this.hide(id), duration);
            });
        }

        return id;
    }

    createToast(id, message, type, actions) {
        const toast = document.createElement('div');
        toast.id = id;
        toast.className = `toast toast-${type} bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-4 transform transition-all duration-300 translate-x-full`;
        
        const icons = {
            success: 'fa-check-circle text-green-500',
            error: 'fa-exclamation-circle text-red-500',
            warning: 'fa-exclamation-triangle text-yellow-500',
            info: 'fa-info-circle text-blue-500'
        };

        const colors = {
            success: 'border-l-4 border-green-500',
            error: 'border-l-4 border-red-500',
            warning: 'border-l-4 border-yellow-500',
            info: 'border-l-4 border-blue-500'
        };

        toast.className += ' ' + colors[type];

        // SECURITY NOTE: innerHTML used for HTML content - consider using DOMPurify for user-generated content
        toast.innerHTML = `
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <i class="fas ${icons[type]} text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">${this.escapeHtml(message)}</p>
                    ${actions.length > 0 ? this.renderActions(actions, id) : ''}
                </div>
                <button onclick="window.toast.hide('${id}')" 
                        class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            ${duration > 0 ? '<div class="toast-progress mt-2 h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden"><div class="toast-progress-bar h-full bg-primary-500" style="animation: toast-progress ' + duration + 'ms linear"></div></div>' : ''}
        `;

        // Add animation
        const style = document.createElement('style');
        if (!document.getElementById('toast-styles')) {
            style.id = 'toast-styles';
            style.textContent = `
                @keyframes toast-enter {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes toast-exit {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
                @keyframes toast-progress {
                    from { width: 100%; }
                    to { width: 0%; }
                }
                .toast-enter {
                    animation: toast-enter 0.3s ease-out forwards;
                }
                .toast-exit {
                    animation: toast-exit 0.3s ease-out forwards;
                }
            `;
            document.head.appendChild(style);
        }

        return toast;
    }

    renderActions(actions, toastId) {
        return `
            <div class="mt-3 flex space-x-2">
                ${actions.map(action => `
                    <button onclick="${action.handler.replace('$id', toastId)}" 
                            class="text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                        ${action.label}
                    </button>
                `).join('')}
            </div>
        `;
    }

    hide(id) {
        const toast = document.getElementById(id);
        if (toast) {
            toast.classList.remove('toast-enter');
            toast.classList.add('toast-exit');
            
            setTimeout(() => {
                toast.remove();
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 300);
        }
    }

    success(message, duration = 5000, actions = []) {
        return this.show(message, 'success', duration, actions);
    }

    error(message, duration = 7000, actions = []) {
        return this.show(message, 'error', duration, actions);
    }

    warning(message, duration = 6000, actions = []) {
        return this.show(message, 'warning', duration, actions);
    }

    info(message, duration = 5000, actions = []) {
        return this.show(message, 'info', duration, actions);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.toastSystem = new ToastSystem();
    });
} else {
    window.toastSystem = new ToastSystem();
}

// Helper for Utils.showNotification compatibility
if (typeof Utils === 'undefined') {
    window.Utils = {
        showNotification: function(message, type = 'info') {
            if (window.toastSystem) {
                window.toastSystem.show(message, type);
            } else {
                // Debug logging disabled in production
                // console.log(`[${type.toUpperCase()}] ${message}`);
            }
        }
    };
}

