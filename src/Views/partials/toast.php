<?php
/**
 * Toast Notification Component
 * Usage: include __DIR__ . '/partials/toast.php' with $toast data
 */

$toast = $toast ?? null;
?>

<?php if ($toast): ?>
<div x-data="{ show: true }" 
     x-show="show" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-90 translate-x-full"
     x-transition:enter-end="opacity-100 transform scale-100 translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform scale-100 translate-x-0"
     x-transition:leave-end="opacity-0 transform scale-90 translate-x-full"
     class="fixed top-4 right-4 z-50 max-w-sm w-full"
     @keydown.escape="show = false">
    <div class="rounded-xl shadow-strong border backdrop-blur-sm p-4 <?= $toast['type'] === 'success' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : ($toast['type'] === 'error' ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800') ?>">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center <?= $toast['type'] === 'success' ? 'bg-green-100 dark:bg-green-800' : ($toast['type'] === 'error' ? 'bg-red-100 dark:bg-red-800' : 'bg-blue-100 dark:bg-blue-800') ?>">
                    <i class="fas <?= $toast['type'] === 'success' ? 'fa-check text-green-600 dark:text-green-400' : ($toast['type'] === 'error' ? 'fa-exclamation text-red-600 dark:text-red-400' : 'fa-info text-blue-600 dark:text-blue-400') ?> text-sm"></i>
                </div>
            </div>
            <div class="ml-3 w-0 flex-1">
                <p class="text-sm font-semibold <?= $toast['type'] === 'success' ? 'text-green-800 dark:text-green-200' : ($toast['type'] === 'error' ? 'text-red-800 dark:text-red-200' : 'text-blue-800 dark:text-blue-200') ?>">
                    <?= e($toast['message']) ?>
                </p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
                <button @click="show = false" 
                        class="inline-flex items-center justify-center w-6 h-6 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                        aria-label="Kapat">
                    <i class="fas fa-times text-xs text-gray-500 dark:text-gray-400"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
