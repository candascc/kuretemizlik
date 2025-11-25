/**
 * Keyboard Navigation Utilities
 * Provides keyboard shortcuts, focus management, and accessibility features
 */

// Focus Trap for Modals
class FocusTrap {
    constructor(element) {
        this.element = element;
        this.previousActiveElement = document.activeElement;
        this.handleKeyDown = this.handleKeyDown.bind(this);
    }
    
    activate() {
        this.element.addEventListener('keydown', this.handleKeyDown);
        this.focusableElements = this.element.querySelectorAll(
            'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        this.firstFocusable = this.focusableElements[0];
        this.lastFocusable = this.focusableElements[this.focusableElements.length - 1];
        
        // Focus first element
        if (this.firstFocusable) {
            this.firstFocusable.focus();
        }
    }
    
    deactivate() {
        this.element.removeEventListener('keydown', this.handleKeyDown);
        
        // Return focus to previous element
        if (this.previousActiveElement && this.previousActiveElement.focus) {
            this.previousActiveElement.focus();
        }
    }
    
    handleKeyDown(e) {
        // Trap Tab key
        if (e.key === 'Tab') {
            if (this.focusableElements.length === 0) {
                e.preventDefault();
                return;
            }
            
            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === this.firstFocusable) {
                    this.lastFocusable.focus();
                    e.preventDefault();
                }
            } else {
                // Tab
                if (document.activeElement === this.lastFocusable) {
                    this.firstFocusable.focus();
                    e.preventDefault();
                }
            }
        }
        
        // Close on Escape
        if (e.key === 'Escape') {
            const closeBtn = this.element.querySelector('[data-close-modal], [aria-label*="Kapat"], [aria-label*="Close"]');
            if (closeBtn) {
                closeBtn.click();
            }
        }
    }
}

// Keyboard Shortcuts Manager
class KeyboardShortcuts {
    constructor() {
        this.shortcuts = new Map();
        this.init();
    }
    
    init() {
        document.addEventListener('keydown', (e) => {
            const key = this.getKeyString(e);
            
            // Skip if typing in input
            if (this.isTyping(e.target)) {
                return;
            }
            
            // Check for registered shortcuts
            if (this.shortcuts.has(key)) {
                e.preventDefault();
                const handler = this.shortcuts.get(key);
                handler(e);
            }
        });
    }
    
    register(keys, handler, description = '') {
        this.shortcuts.set(keys, handler);
    }
    
    unregister(keys) {
        this.shortcuts.delete(keys);
    }
    
    getKeyString(e) {
        const parts = [];
        
        if (e.ctrlKey) parts.push('Ctrl');
        if (e.metaKey) parts.push('Meta');
        if (e.altKey) parts.push('Alt');
        if (e.shiftKey) parts.push('Shift');
        
        parts.push(e.key);
        
        return parts.join('+');
    }
    
    isTyping(element) {
        const tagName = element.tagName.toLowerCase();
        const isInput = tagName === 'input' || tagName === 'textarea';
        const isEditable = element.contentEditable === 'true';
        const isContentEditable = element.isContentEditable;
        
        return isInput || isEditable || isContentEditable;
    }
}

// Global instances
window.FocusTrap = FocusTrap;
window.KeyboardShortcuts = new KeyboardShortcuts();

// Register global shortcuts
window.KeyboardShortcuts.register('Ctrl+/', () => {
    alert('Klavye kısayolları:\n' +
          'Ctrl+/ - Kısayolları göster\n' +
          'Ctrl+K - Arama\n' +
          'Esc - Dialog/popup kapat\n' +
          'Tab/Shift+Tab - Navigasyon'
    );
}, 'Kısayolları göster');

window.KeyboardShortcuts.register('Ctrl+K', (e) => {
    e.preventDefault();
    // Focus search if exists
    const searchInput = document.querySelector('[data-search], input[type="search"]');
    if (searchInput) {
        searchInput.focus();
        searchInput.select();
    }
}, 'Arama');

// Auto-initialize focus traps for modals
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('[role="dialog"]');
    modals.forEach(modal => {
        const focusTrap = new FocusTrap(modal);
        focusTrap.activate();
    });
});

// Enhanced focus management
window.FocusManager = {
    // Save and restore focus
    saveFocus() {
        this.savedFocus = document.activeElement;
    },
    
    restoreFocus() {
        if (this.savedFocus && this.savedFocus.focus) {
            this.savedFocus.focus();
        }
    },
    
    // Focus first element in container
    focusFirst(container) {
        const focusable = container.querySelector(
            'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled])'
        );
        if (focusable) {
            focusable.focus();
        }
    },
    
    // Get all focusable elements
    getFocusableElements(container = document) {
        return container.querySelectorAll(
            'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
    }
};

