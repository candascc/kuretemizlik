<?php 
// Eğer controller 'flash' verisini sağladıysa onu kullan; yoksa session'dan çek
if (!isset($flash)) {
    $flash = Utils::getFlash();
}
?>
<?php if (!empty($flash)): ?>
    <div class="space-y-3 mt-2">
        <?php foreach ($flash as $type => $message): ?>
            <?php
            $bgClasses = [
                'success' => 'bg-green-50 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-300 dark:border-green-800',
                'error' => 'bg-red-50 text-red-800 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800',
                'warning' => 'bg-yellow-50 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-300 dark:border-yellow-800',
                'info' => 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800',
            ];
            $iconClasses = [
                'success' => 'fa-check-circle',
                'error' => 'fa-exclamation-circle',
                'warning' => 'fa-exclamation-triangle',
                'info' => 'fa-info-circle',
            ];
            $class = $bgClasses[$type] ?? $bgClasses['info'];
            $icon = $iconClasses[$type] ?? $iconClasses['info'];
            ?>
            <div id="flash-<?= $type ?>" class="flash-message flex items-start gap-3 rounded-lg border shadow-lg px-4 py-3 <?= $class ?>" style="animation: slideInDown 0.3s ease-out;">
                <i class="fas <?= $icon ?> mt-0.5"></i>
                <div class="flex-1 text-sm leading-5">
                    <?= e($message) ?>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-current opacity-70 hover:opacity-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
        // Auto-hide flash messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessages = document.querySelectorAll('.flash-message');
            flashMessages.forEach(function(message) {
                setTimeout(function() {
                    message.style.transition = 'opacity 0.5s ease-out';
                    message.style.opacity = '0';
                    setTimeout(function() {
                        if (message.parentElement) {
                            message.remove();
                        }
                    }, 500);
                }, 5000); // 5 seconds
            });
        });
    </script>
<?php endif; ?>
