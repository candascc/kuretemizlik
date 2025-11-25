<?php
/**
 * Bottom Sheet Partial
 * Mobile-friendly modal component
 * 
 * @param string $id Unique ID for the bottom sheet
 * @param string $title Title of the bottom sheet
 * @param string $content Content to display
 * @param bool $showCloseButton Show close button
 */
?>

<div id="<?= htmlspecialchars($id ?? 'bottom-sheet') ?>" class="bottom-sheet hidden fixed inset-0 z-50 overflow-hidden">
    <!-- Backdrop -->
    <div class="bottom-sheet-backdrop absolute inset-0 bg-black bg-opacity-50 transition-opacity duration-300"></div>
    
    <!-- Content -->
    <div class="bottom-sheet-content absolute bottom-0 left-0 right-0 bg-white dark:bg-gray-800 rounded-t-xl shadow-2xl transform transition-transform duration-300 ease-out max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="bottom-sheet-header flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <?= !empty($title) ? e($title) : '' ?>
            </h3>
            
            <?php if ($showCloseButton ?? true): ?>
            <button 
                data-close-sheet 
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                aria-label="Kapat"
            >
                <i class="fas fa-times text-xl"></i>
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Body -->
        <div class="bottom-sheet-body overflow-y-auto flex-1 p-4">
            <?= $content ?? '' ?>
        </div>
        
        <!-- Footer (optional) -->
        <?php if (!empty($footer)): ?>
        <div class="bottom-sheet-footer p-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
            <?= $footer ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.bottom-sheet {
    display: none;
}

.bottom-sheet.bottom-sheet-open {
    display: block;
}

.bottom-sheet-content {
    transform: translateY(100%);
}

.bottom-sheet-open .bottom-sheet-content {
    transform: translateY(0);
}

/* Ensure content doesn't go behind safe area on iOS */
@supports (padding: env(safe-area-inset-bottom)) {
    .bottom-sheet-content {
        padding-bottom: env(safe-area-inset-bottom);
    }
}
</style>

