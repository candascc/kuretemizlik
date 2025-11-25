/**
 * Mobile Table Cards - RESPONSIVE-001
 * 
 * Convert all data tables to card views on mobile
 * Maintains functionality, improves readability
 */

class MobileTableCards {
    constructor() {
        this.breakpoint = 640; // STANDARDIZED: Mobile breakpoint (< 640px)
        this.init();
    }
    
    init() {
        if (window.innerWidth < this.breakpoint) {
            this.convertAllTables();
        }
        
        // Reconvert on resize
        window.addEventListener('resize', () => {
            if (window.innerWidth < this.breakpoint) {
                this.convertAllTables();
            } else {
                this.restoreAllTables();
            }
        });
    }
    
    convertAllTables() {
        const tables = document.querySelectorAll('table:not(.no-mobile-cards)');
        
        tables.forEach(table => {
            if (table.dataset.mobileConverted === 'true') return;
            
            this.convertTable(table);
        });
    }
    
    convertTable(table) {
        table.dataset.mobileConverted = 'true';
        
        // Hide table on mobile - STANDARDIZED: sm breakpoint (640px)
        table.classList.add('hidden', 'sm:table');
        
        // Create card container
        const cardContainer = document.createElement('div');
        cardContainer.className = 'mobile-table-cards block sm:hidden space-y-4';
        cardContainer.dataset.tableId = table.id || 'table-' + Date.now();
        
        // Get headers
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => ({
            text: th.textContent.trim(),
            classList: th.className
        }));
        
        // Get rows
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach((row, rowIndex) => {
            const card = this.createCard(row, headers, rowIndex);
            cardContainer.appendChild(card);
        });
        
        // Insert card container before table
        table.parentElement.insertBefore(cardContainer, table);
    }
    
    createCard(row, headers, rowIndex) {
        const card = document.createElement('div');
        card.className = 'bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden';
        
        const cells = row.querySelectorAll('td');
        
        let cardContent = '';
        
        cells.forEach((cell, cellIndex) => {
            const header = headers[cellIndex]?.text || '';
            const content = cell.innerHTML;
            
            // Skip empty cells
            if (!content.trim()) return;
            
            // Special handling for actions column
            if (header.toLowerCase().includes('action') || header.toLowerCase().includes('işlem')) {
                cardContent += `
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex flex-wrap gap-2">
                            ${content}
                        </div>
                    </div>
                `;
                return;
            }
            
            // Special handling for status badges
            if (header.toLowerCase().includes('status') || header.toLowerCase().includes('durum')) {
                cardContent += `
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">${header}</div>
                        <div>${content}</div>
                    </div>
                `;
                return;
            }
            
            // Regular fields
            cardContent += `
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex justify-between items-start">
                    <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">${header}:</span>
                    <span class="text-sm text-gray-900 dark:text-white text-right ml-4">${content}</span>
                </div>
            `;
        });
        
        card.innerHTML = cardContent;
        
        // Add click handler if row is clickable
        if (row.onclick || row.dataset.href) {
            card.classList.add('cursor-pointer', 'hover:shadow-lg', 'transition-shadow');
            card.onclick = row.onclick || (() => {
                if (row.dataset.href) window.location.href = row.dataset.href;
            });
        }
        
        return card;
    }
    
    restoreAllTables() {
        // Remove card containers
        const cardContainers = document.querySelectorAll('.mobile-table-cards');
        cardContainers.forEach(container => container.remove());
        
        // Show tables
        const tables = document.querySelectorAll('table[data-mobile-converted="true"]');
        tables.forEach(table => {
            table.classList.remove('hidden', 'md:table');
            table.dataset.mobileConverted = 'false';
        });
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    window.mobileTableCards = new MobileTableCards();
});

window.MobileTableCards = MobileTableCards;

