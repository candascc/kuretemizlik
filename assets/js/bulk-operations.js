/**
 * Universal Bulk Operations - UX-MED-003
 * 
 * Works with any data table, provides batch operations
 * Confirmation, progress tracking, error handling
 */

class BulkOperations {
    constructor() {
        this.selectedIds = [];
        this.currentModule = null;
        this.init();
    }
    
    init() {
        // Auto-enhance all tables with data-bulk-enabled
        this.enhanceTables();
    }
    
    enhanceTables() {
        const tables = document.querySelectorAll('table[data-bulk-enabled], .data-table');
        
        tables.forEach(table => {
            if (table.dataset.bulkEnhanced) return;
            table.dataset.bulkEnhanced = 'true';
            
            this.currentModule = table.dataset.module || this.detectModule();
            
            // Add master checkbox to header
            this.addMasterCheckbox(table);
            
            // Add checkboxes to rows
            this.addRowCheckboxes(table);
            
            // Add bulk action toolbar
            this.addBulkToolbar(table);
        });
    }
    
    detectModule() {
        const path = window.location.pathname;
        if (path.includes('/jobs')) return 'jobs';
        if (path.includes('/customers')) return 'customers';
        if (path.includes('/fees')) return 'fees';
        if (path.includes('/recurring')) return 'recurring';
        return 'generic';
    }
    
    addMasterCheckbox(table) {
        const thead = table.querySelector('thead tr');
        if (!thead) return;
        
        const th = document.createElement('th');
        th.className = 'px-4 py-3 text-left w-12';
        th.innerHTML = `
            <input type="checkbox" 
                   id="bulk-select-all"
                   onchange="window.bulkOperations.toggleSelectAll(this.checked)"
                   class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
        `;
        
        thead.prepend(th);
    }
    
    addRowCheckboxes(table) {
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const id = row.dataset.id || row.querySelector('[data-id]')?.dataset.id;
            if (!id) return;
            
            const td = document.createElement('td');
            td.className = 'px-4 py-3';
            td.innerHTML = `
                <input type="checkbox" 
                       data-row-id="${id}"
                       onchange="window.bulkOperations.toggleRow('${id}', this.checked)"
                       class="bulk-checkbox w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
            `;
            
            row.prepend(td);
        });
    }
    
    addBulkToolbar(table) {
        // Create toolbar container
        const toolbar = document.createElement('div');
        toolbar.id = 'bulk-toolbar';
        toolbar.className = 'hidden fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border-2 border-primary-600 px-6 py-4 z-40 animate-slide-in-up';
        toolbar.innerHTML = `
            <div class="flex items-center space-x-6">
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    <span id="selected-count">0</span> öğe seçildi
                </div>
                
                <div class="flex items-center space-x-3">
                    ${this.getBulkActionsHTML()}
                </div>
                
                <button type="button" 
                        onclick="window.bulkOperations.clearSelection()"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        `;
        
        table.parentElement.appendChild(toolbar);
    }
    
    getBulkActionsHTML() {
        const actions = {
            'jobs': [
                { action: 'update-status', label: 'Durum Güncelle', icon: 'fa-edit', class: 'bg-blue-600 hover:bg-blue-700' },
                { action: 'assign-staff', label: 'Personel Ata', icon: 'fa-user-plus', class: 'bg-green-600 hover:bg-green-700' },
                { action: 'export', label: 'Dışa Aktar', icon: 'fa-download', class: 'bg-gray-600 hover:bg-gray-700' },
                { action: 'delete', label: 'Sil', icon: 'fa-trash', class: 'bg-red-600 hover:bg-red-700' }
            ],
            'customers': [
                { action: 'export', label: 'Dışa Aktar', icon: 'fa-download', class: 'bg-blue-600 hover:bg-blue-700' },
                { action: 'bulk-email', label: 'Toplu Email', icon: 'fa-envelope', class: 'bg-green-600 hover:bg-green-700' },
                { action: 'delete', label: 'Sil', icon: 'fa-trash', class: 'bg-red-600 hover:bg-red-700' }
            ],
            'fees': [
                { action: 'mark-paid', label: 'Ödendi İşaretle', icon: 'fa-check', class: 'bg-green-600 hover:bg-green-700' },
                { action: 'send-reminder', label: 'Hatırlatma Gönder', icon: 'fa-bell', class: 'bg-yellow-600 hover:bg-yellow-700' },
                { action: 'export', label: 'Dışa Aktar', icon: 'fa-download', class: 'bg-blue-600 hover:bg-blue-700' }
            ],
            'recurring': [
                { action: 'pause', label: 'Duraklat', icon: 'fa-pause', class: 'bg-yellow-600 hover:bg-yellow-700' },
                { action: 'resume', label: 'Devam Ettir', icon: 'fa-play', class: 'bg-green-600 hover:bg-green-700' },
                { action: 'delete', label: 'Sil', icon: 'fa-trash', class: 'bg-red-600 hover:bg-red-700' }
            ],
            'generic': [
                { action: 'export', label: 'Dışa Aktar', icon: 'fa-download', class: 'bg-blue-600 hover:bg-blue-700' },
                { action: 'delete', label: 'Sil', icon: 'fa-trash', class: 'bg-red-600 hover:bg-red-700' }
            ]
        };
        
        const moduleActions = actions[this.currentModule] || actions['generic'];
        
        return moduleActions.map(action => `
            <button type="button"
                    onclick="window.bulkOperations.performAction('${action.action}')"
                    class="${action.class} text-white px-4 py-2 rounded-lg font-medium transition-all shadow-md hover:shadow-lg">
                <i class="fas ${action.icon} mr-2"></i>
                ${action.label}
            </button>
        `).join('');
    }
    
    toggleSelectAll(checked) {
        const checkboxes = document.querySelectorAll('.bulk-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = checked;
            this.toggleRow(cb.dataset.rowId, checked);
        });
    }
    
    toggleRow(id, checked) {
        if (checked) {
            if (!this.selectedIds.includes(id)) {
                this.selectedIds.push(id);
            }
        } else {
            this.selectedIds = this.selectedIds.filter(i => i !== id);
        }
        
        this.updateToolbar();
    }
    
    updateToolbar() {
        const toolbar = document.getElementById('bulk-toolbar');
        const countEl = document.getElementById('selected-count');
        
        if (toolbar && countEl) {
            countEl.textContent = this.selectedIds.length;
            
            if (this.selectedIds.length > 0) {
                toolbar.classList.remove('hidden');
            } else {
                toolbar.classList.add('hidden');
            }
        }
    }
    
    clearSelection() {
        this.selectedIds = [];
        document.querySelectorAll('.bulk-checkbox, #bulk-select-all').forEach(cb => {
            cb.checked = false;
        });
        this.updateToolbar();
    }
    
    async performAction(action) {
        if (this.selectedIds.length === 0) return;
        
        // Confirmation
        const confirmMsg = this.getConfirmationMessage(action);
        if (!confirm(confirmMsg)) return;
        
        // Show progress
        this.showProgressModal(action);
        
        try {
            const response = await fetch(`/api/${this.currentModule}/bulk-${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ids: this.selectedIds,
                    csrf_token: document.querySelector('meta[name="csrf-token"]')?.content
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccessResult(result);
                setTimeout(() => location.reload(), 2000);
            } else {
                this.showErrorResult(result);
            }
        } catch (error) {
            console.error('Bulk operation failed:', error);
            alert('İşlem başarısız: ' + error.message);
        } finally {
            this.hideProgressModal();
        }
    }
    
    getConfirmationMessage(action) {
        const count = this.selectedIds.length;
        const messages = {
            'delete': `${count} öğeyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!`,
            'update-status': `${count} işin durumunu güncellemek istediğinizden emin misiniz?`,
            'mark-paid': `${count} ücreti ödendi olarak işaretlemek istediğinizden emin misiniz?`,
            'export': `${count} kaydı dışa aktarmak istediğinizden emin misiniz?`
        };
        
        return messages[action] || `${count} öğe üzerinde işlem yapmak istediğinizden emin misiniz?`;
    }
    
    showProgressModal(action) {
        const modal = document.createElement('div');
        modal.id = 'bulk-progress-modal';
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50';
        modal.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-md w-full">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4">
                        <i class="fas fa-spinner fa-spin text-5xl text-primary-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        İşlem Yapılıyor...
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        <span id="bulk-progress-text">${this.selectedIds.length} öğe işleniyor</span>
                    </p>
                    <div class="progress-bar">
                        <div id="bulk-progress-fill" class="progress-fill progress-fill-info" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    hideProgressModal() {
        const modal = document.getElementById('bulk-progress-modal');
        if (modal) modal.remove();
    }
    
    showSuccessResult(result) {
        const count = result.affected || this.selectedIds.length;
        
        if (typeof celebrate !== 'undefined') {
            celebrate(`${count} öğe başarıyla işlendi!`, 'bulk_operation');
        }
    }
    
    showErrorResult(result) {
        alert('Hata: ' + (result.error || 'İşlem başarısız'));
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    window.bulkOperations = new BulkOperations();
});

window.BulkOperations = BulkOperations;

