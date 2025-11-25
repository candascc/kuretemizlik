/**
 * Global App JavaScript Utilities
 * Performance optimized with lazy loading and caching
 */

// Performance monitoring
window.performanceMonitor = {
    start: function(name) {
        this.timers = this.timers || {};
        this.timers[name] = performance.now();
    },
    end: function(name) {
        if (this.timers && this.timers[name]) {
            const duration = performance.now() - this.timers[name];
            // Performance logging disabled in production
            // console.log(`${name} took ${duration.toFixed(2)}ms`);
            delete this.timers[name];
        }
    }
};

// Lazy loading utility
window.lazyLoad = function(selector, callback) {
    const elements = document.querySelectorAll(selector);
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                callback(entry.target);
                observer.unobserve(entry.target);
            }
        });
    });
    
    elements.forEach(el => observer.observe(el));
};

// Image lazy loading
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img[data-src]');
    
    if (images.length === 0) return;
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                
                // Add loading class
                img.classList.add('loading');
                
                // Set src and handle load
                const tempImg = new Image();
                tempImg.onload = function() {
                    img.src = img.dataset.src;
                    img.classList.remove('loading');
                    img.classList.add('loaded');
                    observer.unobserve(img);
                };
                tempImg.onerror = function() {
                    img.classList.remove('loading');
                    img.classList.add('error');
                    img.alt = 'Resim yüklenemedi';
                    observer.unobserve(img);
                };
                tempImg.src = img.dataset.src;
            }
        });
    }, {
        rootMargin: '50px' // Start loading 50px before image comes into view
    });
    
    images.forEach(img => imageObserver.observe(img));
});

// Debounce utility for performance
window.debounce = function(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

// Enhanced Loading States
window.LoadingStates = {
    button(button, loading) {
        if (!button) return;
        
        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            // SECURITY NOTE: innerHTML used for HTML content - consider using DOMPurify for user-generated content
            button.innerHTML = `
                <svg class="animate-spin h-4 w-4 inline mr-2" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                ${button.dataset.loadingText || 'Yükleniyor...'}
            `;
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || button.innerHTML;
        }
    },
    
    inline(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (element) {
            element.innerHTML = `
                <div class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                    <span class="ml-2 text-gray-600">Yükleniyor...</span>
                </div>
            `;
        }
    },
    
    skeleton(element, rows = 5) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (!element) return;
        
        let html = '<div class="animate-pulse space-y-3">';
        for (let i = 0; i < rows; i++) {
            html += `
                <div class="flex space-x-4">
                    <div class="rounded-full bg-gray-300 h-10 w-10"></div>
                    <div class="flex-1 space-y-2 py-1">
                        <div class="h-4 bg-gray-300 rounded w-3/4"></div>
                        <div class="h-4 bg-gray-300 rounded w-1/2"></div>
                    </div>
                </div>
            `;
        }
        html += '</div>';
        element.innerHTML = html;
    },
    
    overlay(show) {
        const overlay = document.getElementById('globalLoading');
        if (overlay) {
            if (show) {
                overlay.classList.remove('hidden');
            } else {
                overlay.classList.add('hidden');
            }
        }
    }
};

// Backwards compatibility
window.showLoading = function(element) {
    window.LoadingStates.inline(element);
};

window.hideLoading = function(element) {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }
    if (element) {
        element.innerHTML = '';
    }
};

// Skeleton loader (deprecated - use LoadingStates.skeleton instead)
window.createSkeletonLoader = function(rows = 5) {
    return window.LoadingStates.skeleton(null, rows);
};

// Form submission with loading state
window.submitFormWithLoading = function(formSelector, callback) {
    const form = document.querySelector(formSelector);
    if (!form) return;
    
    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
    const originalText = submitBtn ? submitBtn.textContent || submitBtn.value : '';
    
    form.addEventListener('submit', function(e) {
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Gönderiliyor...';
            if (submitBtn.tagName === 'INPUT') {
                submitBtn.value = 'Gönderiliyor...';
            }
        }
        
        // Show loading spinner
        showLoading(form);
        
        // Call callback if provided
        if (callback) {
            callback(e);
        }
    });
};

// AJAX form submission
window.submitFormAjax = function(formSelector, options = {}) {
    const form = document.querySelector(formSelector);
    if (!form) return;
    
    const defaultOptions = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        onSuccess: function(response) {
            showToast('success', 'İşlem başarılı!');
        },
        onError: function(error) {
            showToast('error', 'Bir hata oluştu!');
        }
    };
    
    const config = { ...defaultOptions, ...options };
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const data = new URLSearchParams(formData);
        
        fetch(form.action || window.location.href, {
            method: config.method,
            headers: config.headers,
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                config.onSuccess(data);
            } else {
                config.onError(data);
            }
        })
        .catch(error => {
            config.onError(error);
        });
    });
};

// Toast notification system
window.showToast = function(type, message, duration = 5000) {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 max-w-sm w-full`;
    toast.innerHTML = `
        <div class="rounded-lg shadow-lg p-4 ${type === 'success' ? 'bg-green-50 border border-green-200' : type === 'error' ? 'bg-red-50 border border-red-200' : 'bg-blue-50 border border-blue-200'}">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas ${type === 'success' ? 'fa-check-circle text-green-400' : type === 'error' ? 'fa-exclamation-circle text-red-400' : 'fa-info-circle text-blue-400'}"></i>
                </div>
                <div class="ml-3 w-0 flex-1">
                    <p class="text-sm font-medium ${type === 'success' ? 'text-green-800' : type === 'error' ? 'text-red-800' : 'text-blue-800'}">
                        ${message}
                    </p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button onclick="this.parentElement.parentElement.parentElement.parentElement.remove()" class="inline-flex ${type === 'success' ? 'text-green-400 hover:text-green-500' : type === 'error' ? 'text-red-400 hover:text-red-500' : 'text-blue-400 hover:text-blue-500'}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after duration
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, duration);
};

// Form validation helpers
window.validateForm = function(form) {
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('form-error');
            isValid = false;
        } else {
            input.classList.remove('form-error');
        }
    });
    
    return isValid;
};

// Loading state helpers
window.setLoading = function(element, loading = true) {
    if (loading) {
        element.classList.add('loading');
        element.disabled = true;
    } else {
        element.classList.remove('loading');
        element.disabled = false;
    }
};

// Confirmation dialog - Updated to work with modern dialogs
window.confirmAction = function(message, callback) {
    // Use modern dialog system if available, fallback to native confirm
    if (typeof window.showConfirmation === 'function') {
        window.showConfirmation({
            title: 'Onay Gerekli',
            message: message,
            confirmText: 'Evet',
            cancelText: 'İptal',
            onConfirm: callback,
            onCancel: function() {}
        });
    } else {
        // Fallback to native confirm
        if (confirm(message)) {
            callback();
        }
    }
};

// Auto-hide flash messages
document.addEventListener('DOMContentLoaded', function() {
    const flashMessages = document.querySelectorAll('.flash-message, .bg-green-50.border.border-green-200, .bg-red-50.border.border-red-200');
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s ease';
            message.style.opacity = '0';
            setTimeout(() => {
                if (message.parentElement) {
                    message.remove();
                }
            }, 500);
        }, 5000);
    });
});

// Mobile menu auto-close on link click
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuLinks = document.querySelectorAll('.mobile-menu-link');
    mobileMenuLinks.forEach(link => {
        link.addEventListener('click', () => {
            // Close mobile menu if open
            const mobileMenu = document.querySelector('[x-data*="mobileMenuOpen"]');
            if (mobileMenu && mobileMenu._x_dataStack && mobileMenu._x_dataStack[0]) {
                mobileMenu._x_dataStack[0].mobileMenuOpen = false;
            }
        });
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[type="search"], input[placeholder*="ara"], input[placeholder*="search"]');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal, [x-show*="Modal"]');
        modals.forEach(modal => {
            if (modal.style.display !== 'none') {
                modal.style.display = 'none';
            }
        });
    }
});

// Smooth scroll for anchor links
document.addEventListener('DOMContentLoaded', function() {
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
