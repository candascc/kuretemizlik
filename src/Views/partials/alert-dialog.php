<?php
/**
 * Modern Alert Dialog Component
 * Usage: Include this in base layout and use window.showAlert() to trigger
 */
?>

<!-- Modern Alert Dialog -->
<div x-data="alertDialog()" 
     x-show="show" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;"
     @keydown.escape="close()">
    
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="close()"></div>
        
        <!-- Dialog -->
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-strong transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-6 pt-6 pb-4 sm:p-8 sm:pb-6">
                <div class="sm:flex sm:items-start">
                    <!-- Icon -->
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-xl sm:mx-0 sm:h-10 sm:w-10"
                         :class="iconClass">
                        <i class="fas text-lg" :class="iconName"></i>
                    </div>
                    
                    <!-- Content -->
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-xl font-bold mb-2" :class="titleClass" x-text="title">
                            Bilgi
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="message">
                                Mesaj burada görünecek.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 sm:px-8 sm:flex sm:flex-row-reverse">
                <button type="button" 
                        @click="close()"
                        class="w-full inline-flex justify-center items-center px-6 py-3 font-semibold rounded-lg shadow-soft hover:shadow-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 sm:ml-3 sm:w-auto"
                        :class="buttonClass">
                    <i class="fas fa-check mr-2"></i>
                    <span x-text="buttonText">Tamam</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function alertDialog() {
    return {
        show: false,
        title: 'Bilgi',
        message: 'Mesaj burada görünecek.',
        type: 'info', // info, success, warning, error
        buttonText: 'Tamam',
        onClose: null,
        
        get iconClass() {
            const classes = {
                'info': 'bg-blue-100 dark:bg-blue-900/20',
                'success': 'bg-green-100 dark:bg-green-900/20',
                'warning': 'bg-yellow-100 dark:bg-yellow-900/20',
                'error': 'bg-red-100 dark:bg-red-900/20'
            };
            return classes[this.type] || classes['info'];
        },
        
        get iconName() {
            const icons = {
                'info': 'fa-info-circle text-blue-600 dark:text-blue-400',
                'success': 'fa-check-circle text-green-600 dark:text-green-400',
                'warning': 'fa-exclamation-triangle text-yellow-600 dark:text-yellow-400',
                'error': 'fa-times-circle text-red-600 dark:text-red-400'
            };
            return icons[this.type] || icons['info'];
        },
        
        get titleClass() {
            const classes = {
                'info': 'text-blue-900 dark:text-blue-100',
                'success': 'text-green-900 dark:text-green-100',
                'warning': 'text-yellow-900 dark:text-yellow-100',
                'error': 'text-red-900 dark:text-red-100'
            };
            return classes[this.type] || classes['info'];
        },
        
        get buttonClass() {
            const classes = {
                'info': 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
                'success': 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500',
                'warning': 'bg-yellow-600 hover:bg-yellow-700 text-white focus:ring-yellow-500',
                'error': 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500'
            };
            return classes[this.type] || classes['info'];
        },
        
        init() {
            // Global function to show alert dialog
            window.showAlert = (options) => {
                this.title = options.title || 'Bilgi';
                this.message = options.message || 'Mesaj burada görünecek.';
                this.type = options.type || 'info';
                this.buttonText = options.buttonText || 'Tamam';
                this.onClose = options.onClose || null;
                this.show = true;
                
                // Focus management
                this.$nextTick(() => {
                    const button = this.$el.querySelector('button[type="button"]');
                    if (button) {
                        button.focus();
                    }
                });
            };
        },
        
        close() {
            if (this.onClose && typeof this.onClose === 'function') {
                this.onClose();
            }
            this.show = false;
        }
    };
}
</script>
