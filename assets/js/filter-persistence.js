/**
 * Filter Persistence - UX-MED-007
 * 
 * Save and restore filter states across sessions
 * Filter presets for quick access
 */

class FilterPersistence {
    constructor() {
        this.storageKey = 'app_filters';
        this.filters = this.loadFilters();
        this.presets = this.loadPresets();
        this.init();
    }
    
    init() {
        // Auto-enhance filter forms
        this.enhanceFilterForms();
        
        // Restore filters on page load
        this.restoreFilters();
    }
    
    enhanceFilterForms() {
        const filterForms = document.querySelectorAll('form[data-persist-filters], .filter-form');
        
        filterForms.forEach(form => {
            const pageKey = this.getPageKey();
            
            // Add "Save as Preset" button
            const saveBtn = document.createElement('button');
            saveBtn.type = 'button';
            saveBtn.className = 'px-3 py-2 text-sm bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-all';
            saveBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Preset Kaydet';
            saveBtn.onclick = () => this.showSavePresetModal(form);
            
            // Add "Load Preset" dropdown
            const loadBtn = this.createPresetDropdown(pageKey);
            
            // Add "Clear" button
            const clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'px-3 py-2 text-sm text-red-600 hover:text-red-700 dark:text-red-400 font-medium';
            clearBtn.innerHTML = '<i class="fas fa-eraser mr-2"></i>Temizle';
            clearBtn.onclick = () => this.clearFilters(form);
            
            // Find button container or create one
            let btnContainer = form.querySelector('.filter-actions, .form-actions');
            if (!btnContainer) {
                btnContainer = document.createElement('div');
                btnContainer.className = 'filter-actions flex items-center space-x-2 mt-4';
                form.appendChild(btnContainer);
            }
            
            btnContainer.prepend(clearBtn);
            btnContainer.prepend(loadBtn);
            btnContainer.prepend(saveBtn);
            
            // Auto-save on change
            form.addEventListener('change', () => {
                this.saveFilters(form, pageKey);
            });
        });
    }
    
    createPresetDropdown(pageKey) {
        const container = document.createElement('div');
        container.className = 'relative';
        
        const presets = this.getPresetsForPage(pageKey);
        
        if (presets.length === 0) {
            return document.createTextNode('');
        }
        
        const dropdown = document.createElement('select');
        dropdown.className = 'px-3 py-2 text-sm border-2 border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white';
        dropdown.innerHTML = `
            <option value="">Preset Seç...</option>
            ${presets.map(preset => `
                <option value="${preset.id}">${preset.name}</option>
            `).join('')}
        `;
        
        dropdown.onchange = (e) => {
            if (e.target.value) {
                this.loadPreset(e.target.value);
            }
        };
        
        container.appendChild(dropdown);
        return container;
    }
    
    getPageKey() {
        const path = window.location.pathname;
        return path.replace(/\//g, '_').replace(/[^a-zA-Z0-9_]/g, '') || 'index';
    }
    
    saveFilters(form, pageKey = null) {
        pageKey = pageKey || this.getPageKey();
        
        const formData = new FormData(form);
        const filters = {};
        
        for (const [key, value] of formData.entries()) {
            if (value) filters[key] = value;
        }
        
        this.filters[pageKey] = {
            filters: filters,
            timestamp: Date.now()
        };
        
        sessionStorage.setItem(this.storageKey, JSON.stringify(this.filters));
    }
    
    restoreFilters() {
        const pageKey = this.getPageKey();
        const saved = this.filters[pageKey];
        
        if (!saved || !saved.filters) return;
        
        // Restore form values
        Object.keys(saved.filters).forEach(key => {
            const input = document.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = saved.filters[key] === 'on' || saved.filters[key] === '1';
                } else {
                    input.value = saved.filters[key];
                }
                
                // Trigger change event for reactive frameworks
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }
    
    clearFilters(form) {
        // Reset form
        form.reset();
        
        // Clear saved filters
        const pageKey = this.getPageKey();
        delete this.filters[pageKey];
        sessionStorage.setItem(this.storageKey, JSON.stringify(this.filters));
        
        // Trigger submit to reload without filters
        form.submit();
    }
    
    loadFilters() {
        try {
            return JSON.parse(sessionStorage.getItem(this.storageKey) || '{}');
        } catch {
            return {};
        }
    }
    
    // Presets (saved in localStorage for persistence across sessions)
    showSavePresetModal(form) {
        const name = prompt('Preset adı:');
        if (!name) return;
        
        const pageKey = this.getPageKey();
        const formData = new FormData(form);
        const filters = {};
        
        for (const [key, value] of formData.entries()) {
            if (value) filters[key] = value;
        }
        
        const preset = {
            id: Date.now().toString(),
            name: name,
            page: pageKey,
            filters: filters,
            created: Date.now()
        };
        
        this.presets.push(preset);
        this.savePresets();
        
        alert(`Preset "${name}" kaydedildi!`);
        location.reload(); // Reload to show new preset
    }
    
    loadPreset(presetId) {
        const preset = this.presets.find(p => p.id === presetId);
        if (!preset) return;
        
        // Apply filters to form
        Object.keys(preset.filters).forEach(key => {
            const input = document.querySelector(`[name="${key}"]`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = preset.filters[key] === 'on' || preset.filters[key] === '1';
                } else {
                    input.value = preset.filters[key];
                }
            }
        });
        
        // Submit form
        const form = document.querySelector('form[data-persist-filters], .filter-form');
        if (form) form.submit();
    }
    
    getPresetsForPage(pageKey) {
        return this.presets.filter(p => p.page === pageKey);
    }
    
    loadPresets() {
        try {
            return JSON.parse(localStorage.getItem('filter_presets') || '[]');
        } catch {
            return [];
        }
    }
    
    savePresets() {
        localStorage.setItem('filter_presets', JSON.stringify(this.presets));
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    window.filterPersistence = new FilterPersistence();
});

window.FilterPersistence = FilterPersistence;

