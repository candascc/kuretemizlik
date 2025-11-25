/**
 * Bottom Sheet Component
 * Mobile-friendly modal that slides up from bottom
 */

class BottomSheet {
    constructor(element) {
        this.element = element;
        this.backdrop = element.querySelector('.bottom-sheet-backdrop');
        this.content = element.querySelector('.bottom-sheet-content');
        this.closeBtn = element.querySelector('[data-close-sheet]');
        
        this.init();
    }
    
    init() {
        // Close button
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.close());
        }
        
        // Backdrop click
        if (this.backdrop) {
            this.backdrop.addEventListener('click', () => this.close());
        }
        
        // ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });
    }
    
    open() {
        this.element.classList.remove('hidden');
        this.element.classList.add('bottom-sheet-open');
        document.body.style.overflow = 'hidden';
        
        // Trigger focus trap
        const focusable = this.element.querySelector('button, a, input, select, textarea');
        if (focusable) {
            focusable.focus();
        }
    }
    
    close() {
        this.element.classList.remove('bottom-sheet-open');
        this.element.classList.add('hidden');
        document.body.style.overflow = '';
    }
    
    isOpen() {
        return this.element.classList.contains('bottom-sheet-open');
    }
    
    toggle() {
        if (this.isOpen()) {
            this.close();
        } else {
            this.open();
        }
    }
}

// Global instance management
window.BottomSheetManager = {
    instances: new Map(),
    
    init(sheetId) {
        const element = document.getElementById(sheetId);
        if (element) {
            const sheet = new BottomSheet(element);
            this.instances.set(sheetId, sheet);
            return sheet;
        }
        return null;
    },
    
    open(sheetId) {
        const sheet = this.instances.get(sheetId) || this.init(sheetId);
        if (sheet) {
            sheet.open();
        }
    },
    
    close(sheetId) {
        const sheet = this.instances.get(sheetId);
        if (sheet) {
            sheet.close();
        }
    },
    
    closeAll() {
        this.instances.forEach(sheet => sheet.close());
    }
};

// Auto-initialize bottom sheets
document.addEventListener('DOMContentLoaded', function() {
    const sheets = document.querySelectorAll('.bottom-sheet');
    sheets.forEach(sheet => {
        window.BottomSheetManager.init(sheet.id);
    });
});

// Helper functions
window.openBottomSheet = function(sheetId) {
    window.BottomSheetManager.open(sheetId);
};

window.closeBottomSheet = function(sheetId) {
    window.BottomSheetManager.close(sheetId);
};

