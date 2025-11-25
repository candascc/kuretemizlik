/**
 * Modern Dialogs Utility
 * Provides easy-to-use functions for modern confirmation and alert dialogs
 */

// Modern confirm function - Safe override with fallback
window.modernConfirm = function(message, title = 'Onay Gerekli') {
    return new Promise((resolve) => {
        if (typeof window.showConfirmation === 'function') {
            window.showConfirmation({
                title: title,
                message: message,
                confirmText: 'Evet',
                cancelText: 'İptal',
                onConfirm: () => resolve(true),
                onCancel: () => resolve(false)
            });
        } else {
            // Fallback to native confirm
            resolve(confirm(message));
        }
    });
};

// Only override native confirm if modern dialog system is available
if (typeof window.showConfirmation === 'function') {
    window.confirm = window.modernConfirm;
}

// Override the native alert() function with our modern dialog
window.alert = function(message, title = 'Bilgi', type = 'info') {
    return new Promise((resolve) => {
        window.showAlert({
            title: title,
            message: message,
            type: type,
            buttonText: 'Tamam',
            onClose: () => resolve()
        });
    });
};

// Utility functions for common dialog types
window.Dialogs = {
    // Confirmation dialogs
    confirmDelete: function(itemName = 'Bu öğe') {
        return window.confirm(
            `${itemName} silinecek. Bu işlem geri alınamaz. Devam etmek istediğinizden emin misiniz?`,
            'Silme Onayı'
        );
    },
    
    confirmAction: function(action, itemName = 'Bu öğe') {
        return window.confirm(
            `${itemName} için ${action} işlemi gerçekleştirilecek. Devam etmek istediğinizden emin misiniz?`,
            'İşlem Onayı'
        );
    },
    
    // Alert dialogs
    success: function(message, title = 'Başarılı') {
        return window.alert(message, title, 'success');
    },
    
    error: function(message, title = 'Hata') {
        return window.alert(message, title, 'error');
    },
    
    warning: function(message, title = 'Uyarı') {
        return window.alert(message, title, 'warning');
    },
    
    info: function(message, title = 'Bilgi') {
        return window.alert(message, title, 'info');
    }
};

// Helper function to replace old onclick confirm patterns
window.replaceConfirmPatterns = function() {
    // Find all elements with onclick containing confirm
    const elements = document.querySelectorAll('[onclick*="confirm("]');
    
    elements.forEach(element => {
        const onclick = element.getAttribute('onclick');
        if (onclick && onclick.includes('confirm(')) {
            // Extract the confirm message
            const match = onclick.match(/confirm\(['"`]([^'"`]+)['"`]\)/);
            if (match) {
                const message = match[1];
                
                // Replace the onclick with our modern dialog
                element.removeAttribute('onclick');
                element.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const confirmed = await window.confirm(message);
                    if (confirmed) {
                        // Execute the original action (form submission, etc.)
                        const form = element.closest('form');
                        if (form) {
                            form.submit();
                        } else {
                            // Handle other actions as needed
                            const href = element.getAttribute('href');
                            if (href) {
                                window.location.href = href;
                            }
                        }
                    }
                });
            }
        }
    });
};

// Auto-replace patterns when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.replaceConfirmPatterns();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = window.Dialogs;
}
