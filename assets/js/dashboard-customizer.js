/**
 * Dashboard Customizer - UX-MED-002
 * 
 * Drag-drop widget arrangement, show/hide, save preferences
 * Uses SortableJS for drag-drop
 */

class DashboardCustomizer {
    constructor() {
        this.isCustomizing = false;
        this.widgets = [];
        this.init();
    }
    
    init() {
        // Load SortableJS library
        this.loadSortableLibrary();
        
        // Create customize button
        this.createCustomizeButton();
        
        // Load user preferences
        this.loadPreferences();
    }
    
    loadSortableLibrary() {
        if (typeof Sortable !== 'undefined') return;
        
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
        script.async = true;
        script.onload = () => this.enableDragDrop();
        document.head.appendChild(script);
    }
    
    createCustomizeButton() {
        // Find dashboard container
        const dashboard = document.querySelector('.dashboard, [data-page="dashboard"]');
        if (!dashboard) return;
        
        // Add customize button to header
        const header = dashboard.querySelector('h1')?.parentElement || dashboard;
        
        const btn = document.createElement('button');
        btn.id = 'customize-dashboard-btn';
        btn.type = 'button';
        btn.className = 'px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-all shadow-md hover:shadow-lg';
        btn.innerHTML = '<i class="fas fa-cog mr-2"></i>Paneli Özelleştir';
        btn.onclick = () => this.toggleCustomizeMode();
        
        if (header) {
            header.appendChild(btn);
        }
    }
    
    toggleCustomizeMode() {
        this.isCustomizing = !this.isCustomizing;
        
        const dashboard = document.querySelector('.dashboard-widgets, .dashboard');
        const btn = document.getElementById('customize-dashboard-btn');
        
        if (this.isCustomizing) {
            dashboard?.classList.add('customizing');
            if (btn) {
                btn.innerHTML = '<i class="fas fa-save mr-2"></i>Kaydet';
                btn.classList.remove('bg-primary-600', 'hover:bg-primary-700');
                btn.classList.add('bg-green-600', 'hover:bg-green-700');
            }
            
            this.showCustomizePanel();
            this.makeWidgetsEditable();
        } else {
            dashboard?.classList.remove('customizing');
            if (btn) {
                btn.innerHTML = '<i class="fas fa-cog mr-2"></i>Paneli Özelleştir';
                btn.classList.add('bg-primary-600', 'hover:bg-primary-700');
                btn.classList.remove('bg-green-600', 'hover:bg-green-700');
            }
            
            this.hideCustomizePanel();
            this.savePreferences();
        }
    }
    
    showCustomizePanel() {
        const panel = document.createElement('div');
        panel.id = 'customize-panel';
        panel.className = 'fixed right-0 top-20 bottom-0 w-80 bg-white dark:bg-gray-800 shadow-2xl border-l border-gray-200 dark:border-gray-700 p-6 overflow-y-auto z-40 animate-slide-in-right';
        panel.innerHTML = `
            <div class="mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-sliders-h mr-2 text-primary-600"></i>
                    Panel Ayarları
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Widget'ları sürükleyerek yerleştirin
                </p>
            </div>

            <!-- Widget Visibility Toggles -->
            <div class="space-y-3 mb-6">
                <h4 class="font-semibold text-gray-900 dark:text-white text-sm mb-3">Görünür Widget'lar</h4>
                <div id="widget-toggles" class="space-y-2">
                    <!-- Dynamically filled -->
                </div>
            </div>

            <!-- Layout Options -->
            <div class="mb-6">
                <h4 class="font-semibold text-gray-900 dark:text-white text-sm mb-3">Düzen</h4>
                <div class="space-y-2">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="layout" value="grid" checked class="mr-2">
                        <span class="text-sm">Grid (Izgara)</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="layout" value="list" class="mr-2">
                        <span class="text-sm">Liste</span>
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-2">
                <button type="button" onclick="window.dashboardCustomizer.resetToDefaults()"
                        class="w-full px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-all">
                    <i class="fas fa-undo mr-2"></i>
                    Varsayılana Sıfırla
                </button>
                <button type="button" onclick="window.dashboardCustomizer.toggleCustomizeMode()"
                        class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-all">
                    <i class="fas fa-times mr-2"></i>
                    İptal
                </button>
            </div>
        `;
        
        document.body.appendChild(panel);
        
        // Fill widget toggles
        this.renderWidgetToggles();
    }
    
    hideCustomizePanel() {
        const panel = document.getElementById('customize-panel');
        if (panel) {
            panel.classList.add('animate-slide-out-right');
            setTimeout(() => panel.remove(), 300);
        }
    }
    
    renderWidgetToggles() {
        const container = document.getElementById('widget-toggles');
        if (!container) return;
        
        const widgets = document.querySelectorAll('[data-widget-id]');
        
        container.innerHTML = '';
        widgets.forEach(widget => {
            const id = widget.dataset.widgetId;
            const name = widget.dataset.widgetName || id;
            const visible = !widget.classList.contains('hidden');
            
            const toggle = document.createElement('label');
            toggle.className = 'flex items-center justify-between cursor-pointer p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded';
            toggle.innerHTML = `
                <span class="text-sm text-gray-700 dark:text-gray-300">${name}</span>
                <input type="checkbox" 
                       ${visible ? 'checked' : ''}
                       onchange="window.dashboardCustomizer.toggleWidgetVisibility('${id}', this.checked)"
                       class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
            `;
            
            container.appendChild(toggle);
        });
    }
    
    enableDragDrop() {
        if (typeof Sortable === 'undefined') {
            console.error('Sortable library not loaded');
            return;
        }
        
        const widgetContainer = document.querySelector('.dashboard-widgets, .grid');
        if (!widgetContainer) return;
        
        new Sortable(widgetContainer, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            handle: '.widget-header, .drag-handle',
            onEnd: (evt) => {
                // Widget order changed
                this.updateWidgetOrder();
            }
        });
    }
    
    makeWidgetsEditable() {
        const widgets = document.querySelectorAll('[data-widget-id]');
        
        widgets.forEach(widget => {
            widget.classList.add('widget-editable');
            
            // Add drag handle if not exists
            if (!widget.querySelector('.drag-handle')) {
                const handle = document.createElement('div');
                handle.className = 'drag-handle absolute top-2 right-2 cursor-move text-gray-400 hover:text-gray-600';
                handle.innerHTML = '<i class="fas fa-grip-vertical"></i>';
                widget.style.position = 'relative';
                widget.appendChild(handle);
            }
        });
    }
    
    toggleWidgetVisibility(widgetId, visible) {
        const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
        if (widget) {
            if (visible) {
                widget.classList.remove('hidden');
            } else {
                widget.classList.add('hidden');
            }
        }
    }
    
    updateWidgetOrder() {
        // Get current order
        const widgets = document.querySelectorAll('[data-widget-id]');
        this.widgets = Array.from(widgets).map((w, index) => ({
            id: w.dataset.widgetId,
            order: index + 1,
            visible: !w.classList.contains('hidden'),
            size: w.dataset.widgetSize || 'full'
        }));
    }
    
    async savePreferences() {
        this.updateWidgetOrder();
        
        const layoutType = document.querySelector('input[name="layout"]:checked')?.value || 'grid';
        
        try {
            const response = await fetch('/api/dashboard/preferences', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    widget_config: this.widgets,
                    layout_type: layoutType,
                    csrf_token: document.querySelector('meta[name="csrf-token"]')?.content
                })
            });
            
            if (response.ok) {
                if (typeof Utils !== 'undefined') {
                    Utils.showNotification('Panel ayarları kaydedildi!', 'success');
                }
            }
        } catch (error) {
            console.error('Failed to save preferences:', error);
        }
    }
    
    async loadPreferences() {
        try {
            const response = await fetch('/api/dashboard/preferences');
            if (response.ok) {
                const data = await response.json();
                this.applyPreferences(data);
            }
        } catch (error) {
            console.error('Failed to load preferences:', error);
        }
    }
    
    applyPreferences(preferences) {
        if (!preferences || !preferences.widget_config) return;
        
        const config = preferences.widget_config;
        
        // Apply visibility and order
        config.forEach(widget => {
            const el = document.querySelector(`[data-widget-id="${widget.id}"]`);
            if (el) {
                if (!widget.visible) {
                    el.classList.add('hidden');
                }
                el.style.order = widget.order;
            }
        });
    }
    
    async resetToDefaults() {
        if (!confirm('Panel ayarlarını varsayılana sıfırlamak istediğinizden emin misiniz?')) {
            return;
        }
        
        try {
            const response = await fetch('/api/dashboard/preferences/reset', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: document.querySelector('meta[name="csrf-token"]')?.content
                })
            });
            
            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Reset failed:', error);
        }
    }
}

// Auto-initialize on dashboard page
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.dashboard, [data-page="dashboard"]')) {
        window.dashboardCustomizer = new DashboardCustomizer();
    }
});

window.DashboardCustomizer = DashboardCustomizer;

