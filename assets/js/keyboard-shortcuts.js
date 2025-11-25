/**
 * Keyboard Shortcuts System
 * Modern, professional keyboard navigation
 */

// Guard against double definition
if (typeof window !== 'undefined' && !window.KeyboardShortcuts) {
class KeyboardShortcuts {
    constructor() {
        this.shortcuts = new Map();
        this.helpVisible = false;
        this.modalOpen = false;
        this.init();
    }

    init() {
        document.addEventListener('keydown', (e) => this.handleKeydown(e));
        this.registerDefaultShortcuts();
        
        // Help modal
        this.createHelpModal();
    }

    registerDefaultShortcuts() {
        // Global Search (Cmd/Ctrl + K) - Handled in handleKeydown

        // New Item (Cmd/Ctrl + N)
        this.register('n', (e) => {
            if (this.isModifierPressed(e)) {
                e.preventDefault();
                this.openQuickAdd();
            }
        }, { label: 'Yeni Kayıt', keys: ['Cmd/Ctrl', 'N'] });

        // Save (Cmd/Ctrl + S)
        this.register('s', (e) => {
            if (this.isModifierPressed(e)) {
                e.preventDefault();
                this.saveActiveForm();
            }
        }, { label: 'Kaydet', keys: ['Cmd/Ctrl', 'S'] });

        // Escape - Close modals
        this.register('Escape', () => {
            this.closeAllModals();
        }, { label: 'Modal Kapat', keys: ['Esc'] });

        // Focus Search (/)
        this.register('/', (e) => {
            if (!this.isInputFocused()) {
                e.preventDefault();
                this.focusSearch();
            }
        }, { label: 'Aramaya Odaklan', keys: ['/'] });

        // Navigation
        this.register('j', () => {
            if (!this.isInputFocused()) {
                this.nextItem();
            }
        }, { label: 'Sonraki', keys: ['J'] });

        // Navigation (only when not in input and no modifier)
        this.register('k', (e) => {
            if (!this.isInputFocused() && !e.metaKey && !e.ctrlKey) {
                this.prevItem();
            }
        }, { label: 'Önceki', keys: ['K'] });

        // Quick Navigation (G + Key)
        this.register('g', () => {
            this.gMode = true;
            setTimeout(() => { this.gMode = false; }, 1000);
        });

        this.register('h', () => {
            if (this.gMode) {
                window.location.href = '/';
            }
        }, { label: 'Ana Sayfa', keys: ['G', 'H'] });

        this.register('j', () => {
            if (this.gMode) {
                window.location.href = '/jobs';
            }
        }, { label: 'İşler', keys: ['G', 'J'] });

        this.register('c', () => {
            if (this.gMode) {
                window.location.href = '/customers';
            }
        }, { label: 'Müşteriler', keys: ['G', 'C'] });

        // Help Modal (Shift + ?)
        this.register('?', (e) => {
            if (e.shiftKey) {
                e.preventDefault();
                this.toggleHelp();
            }
        }, { label: 'Yardım', keys: ['Shift', '?'] });
    }

    register(key, handler, meta = {}) {
        const id = `${key}-${Date.now()}`;
        this.shortcuts.set(key.toLowerCase(), { handler, meta, id });
    }

    handleKeydown(e) {
        const key = e.key.toLowerCase();
        
        // Handle Cmd/Ctrl+K for global search first
        if (this.isModifierPressed(e) && key === 'k') {
            e.preventDefault();
            this.openGlobalSearch();
            return;
        }
        
        // Prevent shortcuts when typing in inputs/modals
        if (this.shouldIgnoreShortcut(e)) {
            return;
        }

        const shortcut = this.shortcuts.get(key);
        if (shortcut) {
            shortcut.handler(e);
        }

        // Handle G-mode navigation
        if (this.gMode && key !== 'g') {
            this.handleGMode(key);
        }
    }

    shouldIgnoreShortcut(e) {
        const target = e.target;
        const isInput = ['input', 'textarea', 'select'].includes(target.tagName.toLowerCase());
        const isContentEditable = target.contentEditable === 'true';
        
        // Allow Escape always
        if (e.key === 'Escape') {
            return false;
        }

        return isInput || isContentEditable || this.modalOpen;
    }

    isModifierPressed(e) {
        return e.metaKey || e.ctrlKey;
    }

    isInputFocused() {
        const active = document.activeElement;
        return ['input', 'textarea', 'select'].includes(active?.tagName?.toLowerCase());
    }

    openGlobalSearch() {
        // Create global search modal if it doesn't exist
        let modal = document.getElementById('globalSearchModal');
        if (!modal) {
            modal = this.createGlobalSearchModal();
        }
        
        modal.classList.remove('hidden');
        this.modalOpen = true;
        
        const input = modal.querySelector('input[type="search"]');
        if (input) {
            setTimeout(() => input.focus(), 100);
        }
    }

    createGlobalSearchModal() {
        const modal = document.createElement('div');
        modal.id = 'globalSearchModal';
        modal.className = 'fixed inset-0 z-50 flex items-start justify-center pt-20 hidden';
        
        // Alpine.js data
        const searchData = {
            query: '',
            results: [],
            isLoading: false,
            search: async function() {
                if (this.query.length < 2) {
                    this.results = [];
                    return;
                }
                
                this.isLoading = true;
                try {
                    const response = await fetch(`/app/api/global-search?q=${encodeURIComponent(this.query)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        this.results = data.results || [];
                    }
                } catch (error) {
                    console.error('Search error:', error);
                    this.results = [];
                } finally {
                    this.isLoading = false;
                }
            },
            close: () => {
                this.closeGlobalSearch();
            }
        };
        
        // Escape JSON string for Alpine
        const searchDataStr = JSON.stringify(searchData).replace(/"/g, '&quot;');
        
        modal.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl mx-4 border border-gray-200 dark:border-gray-700" 
                 onclick="event.stopPropagation()">
                <div x-data="{
                    query: '',
                    results: [],
                    isLoading: false,
                    async search() {
                        if (this.query.length < 2) {
                            this.results = [];
                            return;
                        }
                        this.isLoading = true;
                        try {
                            const response = await fetch('/app/api/global-search?q=' + encodeURIComponent(this.query), {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            const data = await response.json();
                            if (data.success) {
                                this.results = data.results || [];
                            }
                        } catch (error) {
                            console.error('Search error:', error);
                            this.results = [];
                        } finally {
                            this.isLoading = false;
                        }
                    },
                    close() {
                        window.shortcuts.closeGlobalSearch();
                    }
                }"
                x-init="
                    $watch('query', (value) => {
                        if (value.length >= 2) {
                            setTimeout(() => search(), 300);
                        } else {
                            results = [];
                        }
                    });
                ">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="relative">
                        <input type="search" 
                               x-model="query"
                               placeholder="Ne arıyorsunuz? (iş, müşteri, hizmet...)" 
                               class="w-full pl-10 pr-20 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-lg"
                               autofocus>
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center space-x-2">
                            <kbd class="px-2 py-1 text-xs font-semibold text-gray-500 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded">Esc</kbd>
                        </div>
                    </div>
                </div>
                <div class="p-4 max-h-96 overflow-y-auto">
                    <div x-show="!query" class="text-center text-gray-500 py-8">
                        <i class="fas fa-search text-4xl mb-4 text-gray-300"></i>
                        <p class="text-lg font-medium mb-2">Global Arama</p>
                        <p class="text-sm mb-6">İşler, müşteriler, hizmetler ve daha fazlasını arayın</p>
                        <div class="flex items-center justify-center space-x-4 text-xs text-gray-400">
                            <div class="flex items-center space-x-1">
                                <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">Ctrl</kbd>
                                <span>+</span>
                                <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">K</kbd>
                            </div>
                            <span>ile hızlı erişim</span>
                        </div>
                    </div>
                    <div x-show="isLoading" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-2xl text-primary-500"></i>
                        <p class="text-sm text-gray-500 mt-2">Aranıyor...</p>
                    </div>
                    <div x-show="query && !isLoading && results.length === 0 && query.length >= 2" class="text-center text-gray-500 py-8">
                        <i class="fas fa-search-minus text-3xl mb-3 text-gray-300"></i>
                        <p>"<span x-text="query" class="font-medium"></span>" için sonuç bulunamadı</p>
                    </div>
                    <div x-show="results.length > 0" class="space-y-1">
                        <template x-for="(item, index) in results" :key="index">
                            <a :href="item.url" 
                               class="block p-3 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors cursor-pointer group"
                               @click="close()">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center group-hover:bg-primary-200 dark:group-hover:bg-primary-900/50 transition-colors">
                                        <i :class="item.icon" class="text-primary-600 dark:text-primary-400"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 dark:text-white truncate" x-text="item.title"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 truncate" x-text="item.subtitle"></div>
                                    </div>
                                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-primary-500 transition-colors flex-shrink-0"></i>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
                </div>
            </div>
            <div class="fixed inset-0 bg-black bg-opacity-50 -z-10" onclick="window.shortcuts.closeGlobalSearch()"></div>
        `;
        
        document.body.appendChild(modal);
        
        // Initialize Alpine if available
        if (typeof Alpine !== 'undefined') {
            setTimeout(() => {
                Alpine.initTree(modal);
            }, 100);
        }
        
        return modal;
    }

    closeGlobalSearch() {
        const modal = document.getElementById('globalSearchModal');
        if (modal) {
            modal.classList.add('hidden');
            this.modalOpen = false;
        }
    }

    openQuickAdd() {
        // Context-aware quick add
        const path = window.location.pathname;
        
        if (path.includes('/jobs')) {
            window.location.href = '/jobs/new';
        } else if (path.includes('/customers')) {
            window.location.href = '/customers/new';
        } else if (path.includes('/finance')) {
            window.location.href = '/finance/new';
        } else {
            // Default: show quick add menu
            this.showQuickAddMenu();
        }
    }

    saveActiveForm() {
        const form = document.querySelector('form[method="POST"]:not([x-cloak])');
        if (form) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.click();
            }
        }
    }

    closeAllModals() {
        // Close all modals
        document.querySelectorAll('[id$="Modal"], .modal, [role="dialog"]').forEach(modal => {
            if (!modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
            }
        });
        this.modalOpen = false;
    }

    focusSearch() {
        const searchInput = document.querySelector('input[type="search"], input[placeholder*="ara"], input[name="search"]');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }

    nextItem() {
        // Navigate to next item in list
        const items = document.querySelectorAll('table tbody tr, .list-item');
        const current = document.activeElement.closest('tr, .list-item');
        
        if (current && items.length > 0) {
            const index = Array.from(items).indexOf(current);
            if (index < items.length - 1) {
                items[index + 1].querySelector('a, button')?.focus();
            }
        }
    }

    prevItem() {
        // Navigate to previous item in list
        const items = document.querySelectorAll('table tbody tr, .list-item');
        const current = document.activeElement.closest('tr, .list-item');
        
        if (current && items.length > 0) {
            const index = Array.from(items).indexOf(current);
            if (index > 0) {
                items[index - 1].querySelector('a, button')?.focus();
            }
        }
    }

    toggleHelp() {
        this.helpVisible = !this.helpVisible;
        const modal = document.getElementById('shortcutsHelp');
        if (modal) {
            modal.classList.toggle('hidden');
        }
    }

    createHelpModal() {
        const modal = document.createElement('div');
        modal.id = 'shortcutsHelp';
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center hidden';
        modal.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full mx-4 p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Klavye Kısayolları</h2>
                    <button onclick="window.shortcuts.toggleHelp()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Genel</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                                <span class="text-sm">Global Arama</span>
                                <kbd class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded">⌘K</kbd>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                                <span class="text-sm">Yeni Kayıt</span>
                                <kbd class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded">⌘N</kbd>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                                <span class="text-sm">Kaydet</span>
                                <kbd class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded">⌘S</kbd>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                                <span class="text-sm">Modal Kapat</span>
                                <kbd class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded">Esc</kbd>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Navigasyon</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                                <span class="text-sm">Aramaya Odaklan</span>
                                <kbd class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded">/</kbd>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                                <span class="text-sm">Sonraki / Önceki</span>
                                <kbd class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded">J / K</kbd>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                                <span class="text-sm">Ana Sayfa</span>
                                <kbd class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded">G H</kbd>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                                <span class="text-sm">Yardım</span>
                                <kbd class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded">Shift ?</kbd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="fixed inset-0 bg-black bg-opacity-50 -z-10" onclick="window.shortcuts.toggleHelp()"></div>
        `;
        document.body.appendChild(modal);
    }

    showQuickAddMenu() {
        // Show context menu for quick add
        console.log('Quick add menu');
    }

    handleGMode(key) {
        // Handle G-mode shortcuts
        this.gMode = false;
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.shortcuts) { window.shortcuts = new KeyboardShortcuts(); }
    });
} else {
    if (!window.shortcuts) { window.shortcuts = new KeyboardShortcuts(); }
}
}
