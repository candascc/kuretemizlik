/**
 * Global Search - UX-MED-001
 * 
 * Cross-module fast search with recent history
 * Ctrl+/ to activate
 */

class GlobalSearch {
    constructor() {
        this.isOpen = false;
        this.query = '';
        this.results = [];
        this.selectedIndex = 0;
        this.recentSearches = this.loadRecentSearches();
        this.searchModules = [
            {
                name: 'jobs',
                icon: 'fa-tasks',
                label: 'İşler',
                endpoint: '/api/search/jobs',
                fields: ['id', 'customer_name', 'address', 'status']
            },
            {
                name: 'customers',
                icon: 'fa-users',
                label: 'Müşteriler',
                endpoint: '/api/search/customers',
                fields: ['name', 'phone', 'email', 'address']
            },
            {
                name: 'fees',
                icon: 'fa-file-invoice-dollar',
                label: 'Ücretler',
                endpoint: '/api/search/fees',
                fields: ['customer_name', 'type', 'amount', 'status']
            },
            {
                name: 'recurring',
                icon: 'fa-repeat',
                label: 'Periyodik İşler',
                endpoint: '/api/search/recurring',
                fields: ['customer_name', 'title', 'rrule']
            }
        ];
        
        this.init();
    }
    
    init() {
        // Keyboard shortcut: Ctrl+/
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === '/') {
                e.preventDefault();
                this.toggle();
            }
            
            // Esc to close
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
        
        // Create UI if doesn't exist
        if (!document.getElementById('global-search-modal')) {
            this.createUI();
        }
    }
    
    createUI() {
        const modal = document.createElement('div');
        modal.id = 'global-search-modal';
        modal.className = 'fixed inset-0 z-50 hidden items-center justify-start pt-20 px-4 bg-black/50';
        modal.innerHTML = `
            <div class="w-full max-w-3xl mx-auto bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden"
                 onclick="event.stopPropagation()">
                
                <!-- Search Input -->
                <div class="relative">
                    <i class="fas fa-search absolute left-6 top-1/2 transform -translate-y-1/2 text-gray-400 text-xl"></i>
                    <input type="search" 
                           id="global-search-input"
                           placeholder="Herhangi bir şey ara... (İşler, müşteriler, ücretler, vb.)"
                           class="w-full pl-16 pr-6 py-5 text-lg border-b-2 border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:outline-none focus:border-primary-600"
                           autocomplete="off">
                    <span class="absolute right-6 top-1/2 transform -translate-y-1/2 text-xs text-gray-400">
                        <kbd class="kbd">Esc</kbd> kapat
                    </span>
                </div>

                <!-- Results -->
                <div id="global-search-results" class="max-h-96 overflow-y-auto">
                    <!-- Dynamically filled -->
                </div>

                <!-- Footer Tips -->
                <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-3 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                    <div class="flex items-center space-x-4">
                        <span><kbd class="kbd">↑</kbd><kbd class="kbd">↓</kbd> Gezin</span>
                        <span><kbd class="kbd">Enter</kbd> Seç</span>
                    </div>
                    <div>
                        <i class="fas fa-lightbulb mr-1"></i>
                        Modül filtresi: jobs:, customers:, fees:
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close on background click
        modal.addEventListener('click', () => this.close());
        
        // Setup input handler
        const input = document.getElementById('global-search-input');
        input.addEventListener('input', (e) => {
            this.query = e.target.value;
            this.search();
        });
        
        // Keyboard navigation
        input.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.selectNext();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.selectPrev();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                this.selectCurrent();
            }
        });
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        const modal = document.getElementById('global-search-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            this.isOpen = true;
            
            // Focus input
            setTimeout(() => {
                document.getElementById('global-search-input')?.focus();
            }, 100);
            
            // Show recent searches
            this.showRecentSearches();
        }
    }
    
    close() {
        const modal = document.getElementById('global-search-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            this.isOpen = false;
            this.query = '';
            this.results = [];
            this.selectedIndex = 0;
            
            // Clear input
            const input = document.getElementById('global-search-input');
            if (input) input.value = '';
        }
    }
    
    async search() {
        if (!this.query || this.query.length < 2) {
            this.showRecentSearches();
            return;
        }
        
        // Check for module filter (e.g., "jobs:query")
        let targetModules = this.searchModules;
        let searchQuery = this.query;
        
        const moduleMatch = this.query.match(/^(\w+):(.+)/);
        if (moduleMatch) {
            const moduleName = moduleMatch[1];
            searchQuery = moduleMatch[2];
            targetModules = this.searchModules.filter(m => m.name.startsWith(moduleName));
        }
        
        // Show loading
        this.renderResults([{
            type: 'loading',
            content: '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-primary-600"></i></div>'
        }]);
        
        try {
            // Search all modules in parallel
            const searches = targetModules.map(module => 
                this.searchModule(module, searchQuery)
            );
            
            const allResults = await Promise.all(searches);
            
            // Flatten and sort results
            this.results = allResults
                .flat()
                .sort((a, b) => (b.score || 0) - (a.score || 0))
                .slice(0, 20); // Top 20 results
            
            this.renderResults(this.results);
            
            // Save to recent
            this.saveRecentSearch(this.query);
            
        } catch (error) {
            console.error('Search error:', error);
            this.renderResults([{
                type: 'error',
                content: '<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Arama hatası</div>'
            }]);
        }
    }
    
    async searchModule(module, query) {
        try {
            const response = await fetch(`${module.endpoint}?q=${encodeURIComponent(query)}`, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            });
            
            if (!response.ok) return [];
            
            const data = await response.json();
            return (data.results || []).map(item => ({
                ...item,
                module: module.name,
                moduleLabel: module.label,
                moduleIcon: module.icon
            }));
        } catch (error) {
            console.error(`Search error in ${module.name}:`, error);
            return [];
        }
    }
    
    renderResults(results) {
        const container = document.getElementById('global-search-results');
        if (!container) return;
        
        if (results.length === 0) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-search text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400">Sonuç bulunamadı</p>
                </div>
            `;
            return;
        }
        
        // Group by module
        const grouped = {};
        results.forEach(result => {
            if (result.type === 'loading' || result.type === 'error') {
                container.innerHTML = result.content;
                return;
            }
            
            const module = result.moduleLabel || 'Diğer';
            if (!grouped[module]) grouped[module] = [];
            grouped[module].push(result);
        });
        
        // Render grouped results
        let html = '';
        Object.keys(grouped).forEach(moduleName => {
            const items = grouped[moduleName];
            html += `
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-2 bg-gray-50 dark:bg-gray-700/50 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">
                        <i class="fas ${items[0].moduleIcon} mr-2"></i>
                        ${moduleName} (${items.length})
                    </div>
                    ${items.map((item, idx) => `
                        <div class="search-result-item px-6 py-4 hover:bg-primary-50 dark:hover:bg-primary-900/20 cursor-pointer transition-colors ${idx === this.selectedIndex ? 'bg-primary-50 dark:bg-primary-900/20' : ''}"
                             data-url="${item.url || '#'}"
                             onclick="window.location.href='${item.url || '#'}'">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900 dark:text-white mb-1">
                                        ${this.highlightQuery(item.title || item.name, this.query)}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        ${item.subtitle || item.description || ''}
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400 ml-4"></i>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    showRecentSearches() {
        if (this.recentSearches.length === 0) {
            document.getElementById('global-search-results').innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-search text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400">Aramaya başlayın...</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">
                        İşler, müşteriler, ücretler ve daha fazlasını arayın
                    </p>
                </div>
            `;
            return;
        }
        
        const html = `
            <div class="p-6">
                <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-3">
                    <i class="fas fa-history mr-2"></i>
                    Son Aramalar
                </div>
                <div class="space-y-2">
                    ${this.recentSearches.map(search => `
                        <button type="button"
                                onclick="document.getElementById('global-search-input').value = '${search}'; document.getElementById('global-search-input').dispatchEvent(new Event('input'));"
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors text-gray-700 dark:text-gray-300">
                            <i class="fas fa-clock mr-2 text-gray-400"></i>
                            ${search}
                        </button>
                    `).join('')}
                </div>
            </div>
        `;
        
        document.getElementById('global-search-results').innerHTML = html;
    }
    
    highlightQuery(text, query) {
        if (!query) return text;
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-800">$1</mark>');
    }
    
    selectNext() {
        const items = document.querySelectorAll('.search-result-item');
        if (items.length === 0) return;
        
        this.selectedIndex = (this.selectedIndex + 1) % items.length;
        this.updateSelection();
    }
    
    selectPrev() {
        const items = document.querySelectorAll('.search-result-item');
        if (items.length === 0) return;
        
        this.selectedIndex = (this.selectedIndex - 1 + items.length) % items.length;
        this.updateSelection();
    }
    
    updateSelection() {
        const items = document.querySelectorAll('.search-result-item');
        items.forEach((item, idx) => {
            if (idx === this.selectedIndex) {
                item.classList.add('bg-primary-50', 'dark:bg-primary-900/20');
                item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                item.classList.remove('bg-primary-50', 'dark:bg-primary-900/20');
            }
        });
    }
    
    selectCurrent() {
        const items = document.querySelectorAll('.search-result-item');
        if (items[this.selectedIndex]) {
            const url = items[this.selectedIndex].dataset.url;
            if (url && url !== '#') {
                window.location.href = url;
            }
        }
    }
    
    loadRecentSearches() {
        try {
            return JSON.parse(localStorage.getItem('global_search_recent') || '[]');
        } catch {
            return [];
        }
    }
    
    saveRecentSearch(query) {
        if (!query || query.length < 2) return;
        
        this.recentSearches = [
            query,
            ...this.recentSearches.filter(s => s !== query)
        ].slice(0, 5); // Keep last 5
        
        localStorage.setItem('global_search_recent', JSON.stringify(this.recentSearches));
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    window.globalSearch = new GlobalSearch();
});

window.GlobalSearch = GlobalSearch;

