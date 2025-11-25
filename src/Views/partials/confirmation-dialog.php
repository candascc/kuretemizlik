<?php
/**
 * Modern Confirmation Dialog Component
 * Usage: Include this in base layout and use window.showConfirmation() to trigger
 */
?>

<!-- Modern Confirmation Dialog -->
<div x-data="confirmationDialog()" 
     x-show="show" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;"
     @keydown.escape="cancel()">
    
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="cancel()"></div>
        
        <!-- Dialog -->
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-strong transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-6 pt-6 pb-4 sm:p-8 sm:pb-6">
                <div class="sm:flex sm:items-start">
                    <!-- Icon -->
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-xl bg-red-100 dark:bg-red-900/20 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-lg"></i>
                    </div>
                    
                    <!-- Content -->
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2" x-text="title">
                            Onay Gerekli
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="message">
                                Bu işlemi gerçekleştirmek istediğinizden emin misiniz?
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 sm:px-8 sm:flex sm:flex-row-reverse gap-3">
                <button type="button" 
                        @click="confirm()"
                        class="w-full inline-flex justify-center items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-soft hover:shadow-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 sm:ml-3 sm:w-auto">
                    <i class="fas fa-check mr-2"></i>
                    <span x-text="confirmText">Evet, Sil</span>
                </button>
                <button type="button" 
                        @click="cancel()"
                        class="mt-3 w-full inline-flex justify-center items-center px-6 py-3 bg-white dark:bg-gray-600 hover:bg-gray-50 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-semibold rounded-lg border border-gray-300 dark:border-gray-500 shadow-soft hover:shadow-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 sm:mt-0 sm:w-auto">
                    <i class="fas fa-times mr-2"></i>
                    <span x-text="cancelText">İptal</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmationDialog() {
    return {
        show: false,
        title: 'Onay Gerekli',
        message: 'Bu işlemi gerçekleştirmek istediğinizden emin misiniz?',
        confirmText: 'Evet, Sil',
        cancelText: 'İptal',
        onConfirm: null,
        onCancel: null,
        
        init() {
            // Global function to show confirmation dialog
            window.showConfirmation = (options) => {
                this.title = options.title || 'Onay Gerekli';
                this.message = options.message || 'Bu işlemi gerçekleştirmek istediğinizden emin misiniz?';
                this.confirmText = options.confirmText || 'Evet, Sil';
                this.cancelText = options.cancelText || 'İptal';
                this.onConfirm = options.onConfirm || null;
                this.onCancel = options.onCancel || null;
                this.show = true;
                
                // Focus management
                this.$nextTick(() => {
                    const confirmButton = this.$el.querySelector('button[type="button"]:first-of-type');
                    if (confirmButton) {
                        confirmButton.focus();
                    }
                });
            };
        },
        
        confirm() {
            if (this.onConfirm && typeof this.onConfirm === 'function') {
                this.onConfirm();
            }
            this.show = false;
        },
        
        cancel() {
            if (this.onCancel && typeof this.onCancel === 'function') {
                this.onCancel();
            }
            this.show = false;
        }
    };
}
</script>
