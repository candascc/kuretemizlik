/**
 * Form Auto-Save System
 * Visual indicators and unsaved changes warning
 */

class FormAutoSave {
    constructor() {
        this.forms = new Map();
        this.init();
    }

    init() {
        // Find all forms with data attribute
        document.querySelectorAll('form[data-auto-save]').forEach(form => {
            this.registerForm(form);
        });
    }

    registerForm(form) {
        const formId = form.id || 'form-' + Date.now();
        const autoSaveInterval = parseInt(form.dataset.autoSave) || 30000; // 30s default
        
        let isDirty = false;
        let lastSaved = null;
        let saveTimer = null;
        
        // Track changes
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                isDirty = true;
                this.showUnsavedIndicator(form);
                this.scheduleAutoSave(form);
            });
            
            input.addEventListener('input', () => {
                isDirty = true;
                this.showUnsavedIndicator(form);
            });
        });
        
        // Warn on page leave
        window.addEventListener('beforeunload', (e) => {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = 'Kaydedilmemiş değişiklikler var. Sayfadan ayrılmak istediğinize emin misiniz?';
                return e.returnValue;
            }
        });
        
        // Save on form submit
        form.addEventListener('submit', () => {
            isDirty = false;
            this.hideUnsavedIndicator(form);
        });
        
        this.forms.set(formId, {
            form,
            isDirty,
            lastSaved,
            saveTimer
        });
    }

    scheduleAutoSave(form) {
        const formId = form.id || Array.from(this.forms.keys()).find(id => 
            this.forms.get(id).form === form
        );
        
        if (!formId) return;
        
        const formData = this.forms.get(formId);
        if (formData.saveTimer) {
            clearTimeout(formData.saveTimer);
        }
        
        formData.saveTimer = setTimeout(() => {
            this.autoSave(form);
        }, 30000); // Auto-save after 30s of inactivity
    }

    async autoSave(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        // Save to localStorage as draft
        const formId = form.id || 'form-' + form.action;
        localStorage.setItem(`draft_${formId}`, JSON.stringify({
            data,
            timestamp: Date.now()
        }));
        
        this.showSaveIndicator(form, 'auto-saved');
    }

    showUnsavedIndicator(form) {
        let indicator = form.querySelector('.unsaved-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'unsaved-indicator fixed top-20 right-4 bg-yellow-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2 z-40';
            indicator.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <span>Kaydedilmemiş değişiklikler</span>
            `;
            document.body.appendChild(indicator);
        }
        indicator.classList.remove('hidden');
    }

    hideUnsavedIndicator(form) {
        const indicator = document.querySelector('.unsaved-indicator');
        if (indicator) {
            indicator.classList.add('hidden');
        }
    }

    showSaveIndicator(form, type = 'saved') {
        let indicator = form.querySelector(`.save-indicator.${type}`);
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = `save-indicator ${type} fixed top-20 right-4 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2 z-40`;
            if (type === 'auto-saved') {
                indicator.className += ' bg-blue-500';
                indicator.innerHTML = `
                    <i class="fas fa-check-circle"></i>
                    <span>Otomatik kaydedildi</span>
                `;
            } else {
                indicator.className += ' bg-green-500';
                indicator.innerHTML = `
                    <i class="fas fa-check-circle"></i>
                    <span>Kaydedildi</span>
                `;
            }
            document.body.appendChild(indicator);
        }
        
        indicator.classList.remove('hidden');
        setTimeout(() => {
            indicator.classList.add('hidden');
        }, 3000);
    }

    loadDraft(form) {
        const formId = form.id || 'form-' + form.action;
        const draft = localStorage.getItem(`draft_${formId}`);
        
        if (draft) {
            try {
                const { data, timestamp } = JSON.parse(draft);
                const hoursAgo = (Date.now() - timestamp) / (1000 * 60 * 60);
                
                if (hoursAgo < 24) { // Only load drafts < 24h old
                    if (confirm('Daha önce kaydedilmemiş bir taslak bulundu. Yüklemek ister misiniz?')) {
                        // Populate form with draft data
                        Object.entries(data).forEach(([key, value]) => {
                            const input = form.querySelector(`[name="${key}"]`);
                            if (input) {
                                if (input.type === 'checkbox' || input.type === 'radio') {
                                    input.checked = input.value === value;
                                } else {
                                    input.value = value;
                                }
                            }
                        });
                        
                        this.showSaveIndicator(form, 'loaded');
                        return true;
                    }
                }
            } catch (e) {
                console.error('Draft load error:', e);
            }
        }
        
        return false;
    }
}

// Initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.formAutoSave = new FormAutoSave();
    });
} else {
    window.formAutoSave = new FormAutoSave();
}

