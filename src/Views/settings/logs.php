<div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Loglar</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Sistem loglarını görüntüleyin</p>
                </div>
                <a href="<?= base_url('/settings/activity-csv') ?>" class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                    <i class="fas fa-file-csv mr-2"></i>Activity CSV İndir
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">PHP Error Log (son 200 satır)</h2>
                <?php if (empty($errorLines)): ?>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Log bulunamadı.</div>
                <?php else: ?>
                    <pre class="text-xs bg-gray-50 dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600 overflow-auto max-h-[600px] text-gray-900 dark:text-gray-100"><?= htmlspecialchars(implode("\n", $errorLines)) ?></pre>
                <?php endif; ?>
            </div>
</div>

