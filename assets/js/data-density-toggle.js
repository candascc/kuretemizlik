/**
 * Data Density Toggle - UI-POLISH-004
 * 
 * Switch between compact and comfortable views
 * User preference persisted
 */

class DataDensityToggle {
    constructor() {
        this.density = localStorage.getItem('data_density') || 'comfortable';
        this.init();
    }
    
    init() {
        // Apply saved density
        this.applyDensity(this.density);
        
        // Create toggle button
        this.createToggleButton();
    }
    
    createToggleButton() {
        // Find suitable location (header or settings area)
        const header = document.querySelector('header .flex.items-center');
        
        if (!header) return;
        
        const toggle = document.createElement('button');
        toggle.id = 'density-toggle';
        toggle.type = 'button';
        toggle.className = 'p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors';
        toggle.title = 'Görünüm yoğunluğu';
        toggle.innerHTML = `
            <i class="fas ${this.density === 'compact' ? 'fa-compress' : 'fa-expand'} text-lg"></i>
        `;
        
        toggle.onclick = () => this.toggle();
        
        header.appendChild(toggle);
    }
    
    toggle() {
        this.density = this.density === 'compact' ? 'comfortable' : 'compact';
        this.applyDensity(this.density);
        this.saveDensity();
        
        // Update button icon
        const btn = document.getElementById('density-toggle');
        if (btn) {
            const icon = btn.querySelector('i');
            icon.className = `fas ${this.density === 'compact' ? 'fa-compress' : 'fa-expand'} text-lg`;
        }
        
        // Show toast
        const message = this.density === 'compact' ? 'Kompakt görünüm aktif' : 'Konforlu görünüm aktif';
        if (typeof Utils !== 'undefined' && Utils.showNotification) {
            Utils.showNotification(message, 'info');
        }
    }
    
    applyDensity(density) {
        document.body.classList.remove('density-compact', 'density-comfortable');
        document.body.classList.add(`density-${density}`);
    }
    
    saveDensity() {
        localStorage.setItem('data_density', this.density);
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    window.dataDensityToggle = new DataDensityToggle();
});

window.DataDensityToggle = DataDensityToggle;

