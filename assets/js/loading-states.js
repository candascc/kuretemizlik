/**
 * Universal Loading States - UX-MED-006
 * 
 * Skeleton screens, progress bars, spinners
 * Consistent loading experience across the app
 */

class LoadingStates {
    constructor() {
        this.init();
    }
    
    init() {
        // Auto-enhance AJAX requests
        this.interceptFetch();
        
        // Auto-enhance tables with skeleton on load
        this.enhanceTables();
    }
    
    /**
     * Intercept fetch for automatic loading states
     */
    interceptFetch() {
        const originalFetch = window.fetch;
        
        window.fetch = async function(...args) {
            const url = args[0];
            
            // Show global loading indicator
            LoadingStates.showGlobalLoading();
            
            try {
                const response = await originalFetch.apply(this, args);
                return response;
            } finally {
                LoadingStates.hideGlobalLoading();
            }
        };
    }
    
    /**
     * Show global loading indicator (top bar)
     */
    static showGlobalLoading() {
        let indicator = document.getElementById('global-loading-indicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'global-loading-indicator';
            indicator.className = 'fixed top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary-600 via-blue-600 to-purple-600 z-50 loading-bar';
            document.body.appendChild(indicator);
        }
        
        indicator.style.display = 'block';
    }
    
    static hideGlobalLoading() {
        const indicator = document.getElementById('global-loading-indicator');
        if (indicator) {
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 300);
        }
    }
    
    /**
     * Enhance tables with skeleton screens
     */
    enhanceTables() {
        const tables = document.querySelectorAll('table[data-loading-skeleton]');
        
        tables.forEach(table => {
            // Store original content
            table.dataset.originalContent = table.innerHTML;
            
            // Create skeleton
            const skeleton = this.createTableSkeleton(table);
            table.dataset.skeleton = skeleton;
        });
    }
    
    createTableSkeleton(table) {
        const rows = table.querySelectorAll('tbody tr').length || 5;
        const cols = table.querySelectorAll('thead th').length || 4;
        
        let skeleton = '<tbody>';
        for (let i = 0; i < rows; i++) {
            skeleton += '<tr>';
            for (let j = 0; j < cols; j++) {
                skeleton += `
                    <td class="px-4 py-3">
                        <div class="skeleton-box h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                    </td>
                `;
            }
            skeleton += '</tr>';
        }
        skeleton += '</tbody>';
        
        return skeleton;
    }
    
    /**
     * Show skeleton for element
     */
    static showSkeleton(element) {
        if (element.dataset.skeleton) {
            element.innerHTML = element.dataset.skeleton;
        } else {
            element.classList.add('loading-skeleton');
        }
    }
    
    /**
     * Hide skeleton, restore content
     */
    static hideSkeleton(element) {
        if (element.dataset.originalContent) {
            element.innerHTML = element.dataset.originalContent;
        } else {
            element.classList.remove('loading-skeleton');
        }
    }
    
    /**
     * Show loading spinner on button
     */
    static buttonLoading(button, text = 'İşleniyor...') {
        button.dataset.originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `
            <i class="fas fa-spinner fa-spin mr-2"></i>
            ${text}
        `;
    }
    
    /**
     * Restore button
     */
    static buttonRestore(button) {
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
            button.disabled = false;
        }
    }
    
    /**
     * Show progress bar
     */
    static showProgress(container, percent = 0) {
        const progressBar = document.createElement('div');
        progressBar.className = 'loading-progress-bar w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden';
        progressBar.innerHTML = `
            <div class="bg-gradient-to-r from-primary-600 to-blue-600 h-full transition-all duration-300"
                 style="width: ${percent}%"></div>
        `;
        
        container.appendChild(progressBar);
        return progressBar;
    }
    
    /**
     * Update progress
     */
    static updateProgress(progressBar, percent) {
        const fill = progressBar.querySelector('div');
        if (fill) {
            fill.style.width = percent + '%';
        }
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    window.loadingStates = new LoadingStates();
});

window.LoadingStates = LoadingStates;

