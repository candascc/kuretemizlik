/**
 * Keyboard Shortcuts Help Modal
 * UX-HIGH-002 Enhancement
 * 
 * Press ? to show all available keyboard shortcuts
 */

class KeyboardShortcutsHelp {
    constructor() {
        this.shortcuts = [
            {
                category: 'Genel',
                items: [
                    { keys: ['Ctrl', 'K'], description: 'Komut paleti' },
                    { keys: ['?'], description: 'Klavye kısayolları (bu pencere)' },
                    { keys: ['Esc'], description: 'Modal/dialog kapat' },
                    { keys: ['Ctrl', '/'], description: 'Global arama' }
                ]
            },
            {
                category: 'Navigasyon',
                items: [
                    { keys: ['G', 'H'], description: 'Ana sayfa' },
                    { keys: ['G', 'D'], description: 'Dashboard' },
                    { keys: ['G', 'J'], description: 'İşler' },
                    { keys: ['G', 'C'], description: 'Müşteriler' },
                    { keys: ['G', 'R'], description: 'Raporlar' },
                    { keys: ['G', 'S'], description: 'Ayarlar' }
                ]
            },
            {
                category: 'İşlemler',
                items: [
                    { keys: ['N'], description: 'Yeni (context-aware)' },
                    { keys: ['Ctrl', 'S'], description: 'Kaydet' },
                    { keys: ['Ctrl', 'Enter'], description: 'Form gönder' },
                    { keys: ['Ctrl', 'E'], description: 'Düzenle' },
                    { keys: ['Delete'], description: 'Sil (onay ile)' }
                ]
            },
            {
                category: 'Liste İşlemleri',
                items: [
                    { keys: ['J'], description: 'Sonraki öğe' },
                    { keys: ['K'], description: 'Önceki öğe' },
                    { keys: ['Space'], description: 'Öğe seç' },
                    { keys: ['Ctrl', 'A'], description: 'Tümünü seç' },
                    { keys: ['F'], description: 'Filtreler' }
                ]
            }
        ];
        
        this.init();
    }
    
    init() {
        // Listen for ? key
        document.addEventListener('keydown', (e) => {
            // ? key (shift + /)
            if (e.key === '?' && !this.isInputFocused()) {
                e.preventDefault();
                this.show();
            }
        });
        
        // Create modal if it doesn't exist
        if (!document.getElementById('keyboard-shortcuts-modal')) {
            this.createModal();
        }
    }
    
    isInputFocused() {
        const active = document.activeElement;
        return active && (
            active.tagName === 'INPUT' ||
            active.tagName === 'TEXTAREA' ||
            active.isContentEditable
        );
    }
    
    createModal() {
        const modal = document.createElement('div');
        modal.id = 'keyboard-shortcuts-modal';
        modal.className = 'fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50';
        modal.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-auto"
                 onclick="event.stopPropagation()">
                
                <!-- Header -->
                <div class="sticky top-0 bg-gradient-to-r from-primary-600 to-blue-600 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white flex items-center">
                            <i class="fas fa-keyboard mr-3"></i>
                            Klavye Kısayolları
                        </h2>
                        <p class="text-primary-100 text-sm mt-1">Hızlı erişim için kısayolları kullanın</p>
                    </div>
                    <button onclick="window.keyboardShortcutsHelp.hide()" 
                            class="text-white hover:text-primary-200 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="shortcuts-content">
                        <!-- Dynamically filled -->
                    </div>
                    
                    <!-- Tips -->
                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-lightbulb text-blue-600 text-xl mt-1"></i>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">💡 İpucu</h4>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <kbd class="kbd">Ctrl</kbd> + <kbd class="kbd">K</kbd> ile komut paletini açarak 
                                    tüm işlemlere hızlıca erişebilirsiniz.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close on background click
        modal.addEventListener('click', () => this.hide());
        
        // Close on Esc
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('flex')) {
                this.hide();
            }
        });
        
        // Render content
        this.renderShortcuts();
    }
    
    renderShortcuts() {
        const container = document.getElementById('shortcuts-content');
        if (!container) return;
        
        container.innerHTML = this.shortcuts.map(category => `
            <div class="space-y-3">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                    <span class="w-2 h-2 bg-primary-600 rounded-full mr-2"></span>
                    ${category.category}
                </h3>
                <div class="space-y-2">
                    ${category.items.map(item => `
                        <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <span class="text-sm text-gray-700 dark:text-gray-300">${item.description}</span>
                            <div class="flex items-center space-x-1">
                                ${item.keys.map(key => `
                                    <kbd class="kbd">${key}</kbd>
                                `).join('<span class="text-gray-400 mx-1">+</span>')}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `).join('');
    }
    
    show() {
        const modal = document.getElementById('keyboard-shortcuts-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }
    
    hide() {
        const modal = document.getElementById('keyboard-shortcuts-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    window.keyboardShortcutsHelp = new KeyboardShortcutsHelp();
});

// Export
window.KeyboardShortcutsHelp = KeyboardShortcutsHelp;

