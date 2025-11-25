/**
 * Date Input Enhancement with Quick Shortcuts
 * UX Quick Win: Faster date selection
 */

class DateShortcuts {
    constructor(input) {
        this.input = input;
        this.init();
    }
    
    init() {
        // Create shortcuts container
        const shortcuts = this.createShortcutsContainer();
        this.input.parentElement.insertBefore(shortcuts, this.input);
        
        // Add timezone indicator if needed
        this.addTimezoneWarning();
    }
    
    createShortcutsContainer() {
        const container = document.createElement('div');
        container.className = 'date-shortcuts flex gap-2 mb-2 flex-wrap';
        
        const shortcuts = [
            { label: 'Bugün', value: 'today', icon: 'fa-calendar-day' },
            { label: 'Yarın', value: 'tomorrow', icon: 'fa-calendar-plus' },
            { label: 'Pazartesi', value: 'next-monday', icon: 'fa-calendar-week' },
            { label: 'Gelecek Hafta', value: 'next-week', icon: 'fa-calendar' }
        ];
        
        shortcuts.forEach(shortcut => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md transition-colors duration-150 flex items-center gap-1.5';
            btn.innerHTML = `
                <i class="fas ${shortcut.icon} text-xs"></i>
                <span>${shortcut.label}</span>
            `;
            btn.addEventListener('click', () => this.applyShortcut(shortcut.value));
            container.appendChild(btn);
        });
        
        return container;
    }
    
    applyShortcut(shortcut) {
        const date = new Date();
        
        switch(shortcut) {
            case 'today':
                // Already today
                break;
                
            case 'tomorrow':
                date.setDate(date.getDate() + 1);
                break;
                
            case 'next-monday':
                const day = date.getDay();
                const daysUntilMonday = (8 - day) % 7 || 7;
                date.setDate(date.getDate() + daysUntilMonday);
                break;
                
            case 'next-week':
                date.setDate(date.getDate() + 7);
                break;
        }
        
        // Set value based on input type
        if (this.input.type === 'datetime-local') {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            this.input.value = `${year}-${month}-${day}T${hours}:${minutes}`;
        } else {
            this.input.value = date.toISOString().split('T')[0];
        }
        
        // Trigger change event
        this.input.dispatchEvent(new Event('change', { bubbles: true }));
        this.input.dispatchEvent(new Event('input', { bubbles: true }));
        
        // Visual feedback
        this.input.classList.add('bg-green-50', 'dark:bg-green-900/20');
        setTimeout(() => {
            this.input.classList.remove('bg-green-50', 'dark:bg-green-900/20');
        }, 500);
    }
    
    addTimezoneWarning() {
        const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const serverTimezone = 'Europe/Istanbul';
        
        if (userTimezone !== serverTimezone) {
            const warning = document.createElement('div');
            warning.className = 'timezone-warning text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1.5 mt-1';
            warning.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <span>Saat diliminiz: ${userTimezone} (Saatler Türkiye saati olarak kaydedilecek)</span>
            `;
            this.input.parentElement.appendChild(warning);
        }
    }
}

// Auto-enhance date and datetime-local inputs
document.addEventListener('DOMContentLoaded', () => {
    // Find all date/datetime inputs that should have shortcuts
    const dateInputs = document.querySelectorAll('input[type="date"], input[type="datetime-local"]');
    
    dateInputs.forEach(input => {
        // Skip if explicitly disabled
        if (input.dataset.noShortcuts === 'true') {
            return;
        }
        
        // Skip if shortcuts already added
        if (input.parentElement.querySelector('.date-shortcuts')) {
            return;
        }
        
        // Add shortcuts
        new DateShortcuts(input);
    });
});

// Export for manual usage
window.DateShortcuts = DateShortcuts;

