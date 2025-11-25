/**
 * Timezone Handler - UX-CRIT-002 Implementation
 * 
 * Auto-detects user timezone and shows warnings if different from server
 * Prevents timezone confusion in job scheduling
 */

class TimezoneHandler {
    constructor() {
        this.serverTimezone = 'Europe/Istanbul';
        this.userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        this.init();
    }
    
    init() {
        // Check if user timezone differs
        if (this.isDifferent()) {
            this.showGlobalWarning();
        }
        
        // Enhance all datetime inputs
        this.enhanceDateTimeInputs();
        
        // Add live server clock
        this.addServerClock();
    }
    
    isDifferent() {
        return this.userTimezone !== this.serverTimezone;
    }
    
    showGlobalWarning() {
        // Create persistent warning banner
        const banner = document.createElement('div');
        banner.className = 'fixed top-0 left-0 right-0 bg-amber-500 text-white px-4 py-3 z-50 flex items-center justify-between';
        banner.style.display = 'none'; // Will be shown by Alpine
        // SECURITY NOTE: innerHTML used for HTML content - consider using DOMPurify for user-generated content
        banner.innerHTML = `
            <div class="flex items-center space-x-3">
                <i class="fas fa-exclamation-triangle text-xl"></i>
                <div>
                    <strong>Saat Dilimi Farkı Tespit Edildi</strong>
                    <p class="text-sm opacity-90">
                        Sizin saat diliminiz: ${this.userTimezone} • 
                        Sistem saati: ${this.serverTimezone} (Türkiye) • 
                        Tüm saatler Türkiye saati olarak kaydedilecek
                    </p>
                </div>
            </div>
            <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        `;
        
        // Show banner only if not dismissed
        const dismissedKey = 'timezone_warning_dismissed';
        if (!localStorage.getItem(dismissedKey)) {
            document.body.prepend(banner);
            banner.style.display = 'flex';
            
            // Add permanent dismiss option
            banner.querySelector('button').addEventListener('click', () => {
                localStorage.setItem(dismissedKey, 'true');
            });
        }
    }
    
    enhanceDateTimeInputs() {
        const datetimeInputs = document.querySelectorAll('input[type="datetime-local"], input[type="time"]');
        
        datetimeInputs.forEach(input => {
            // Skip if already enhanced
            if (input.dataset.timezoneEnhanced) return;
            input.dataset.timezoneEnhanced = 'true';
            
            // Add timezone indicator
            const indicator = document.createElement('div');
            indicator.className = 'text-xs text-gray-600 dark:text-gray-400 mt-1 flex items-center space-x-2';
            // SECURITY NOTE: innerHTML used for HTML content
            indicator.innerHTML = `
                <i class="fas fa-clock text-primary-600"></i>
                <span>Türkiye Saati (UTC+3)</span>
            `;
            
            // Insert after input
            if (input.parentElement) {
                input.parentElement.appendChild(indicator);
            }
            
            // Add timezone conversion info if different
            if (this.isDifferent()) {
                const warning = document.createElement('div');
                warning.className = 'text-xs text-amber-600 dark:text-amber-400 mt-1 flex items-center space-x-1';
                warning.innerHTML = `
                    <i class="fas fa-info-circle"></i>
                    <span>Sizin saat diliminiz farklı (${this.userTimezone})</span>
                `;
                input.parentElement.appendChild(warning);
            }
        });
    }
    
    addServerClock() {
        // Find suitable location for live clock (header or near datetime inputs)
        const header = document.querySelector('header') || document.querySelector('.header');
        
        if (header) {
            const clock = document.createElement('div');
            clock.id = 'server-time-clock';
            clock.className = 'text-xs text-gray-600 dark:text-gray-400 flex items-center space-x-2';
            clock.innerHTML = `
                <i class="fas fa-globe text-primary-600"></i>
                <span>Türkiye: <strong id="server-time-display">--:--</strong></span>
            `;
            
            // Append to header (you may want to adjust placement)
            // For now, adding to body for visibility
            const clockContainer = document.createElement('div');
            clockContainer.className = 'fixed bottom-4 right-4 bg-white dark:bg-gray-800 px-4 py-2 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-40';
            clockContainer.appendChild(clock);
            document.body.appendChild(clockContainer);
            
            // Update clock every second
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
        }
    }
    
    updateClock() {
        const display = document.getElementById('server-time-display');
        if (display) {
            const now = new Date();
            const turkeyTime = now.toLocaleTimeString('tr-TR', {
                timeZone: this.serverTimezone,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            display.textContent = turkeyTime;
        }
    }
    
    /**
     * Convert local datetime-local input to server timezone
     * (if needed in the future for explicit conversion)
     */
    convertToServerTime(localDatetime) {
        if (!this.isDifferent()) {
            return localDatetime; // No conversion needed
        }
        
        // Parse local datetime
        const dt = new Date(localDatetime);
        
        // Get server time equivalent
        const serverTime = dt.toLocaleString('sv-SE', {
            timeZone: this.serverTimezone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        
        return serverTime.replace(' ', 'T');
    }
}

// Auto-initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.timezoneHandler = new TimezoneHandler();
});

// Export for manual usage
window.TimezoneHandler = TimezoneHandler;

