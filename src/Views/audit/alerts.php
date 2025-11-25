<?php
/**
 * Security Alerts View
 */
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-exclamation-triangle mr-3 text-yellow-500"></i>
                Güvenlik Uyarıları
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Sistem güvenlik uyarıları ve bildirimleri
                <?php if ($unread_count > 0): ?>
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        <?= $unread_count ?> okunmamış
                    </span>
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= base_url('/audit') ?>" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
            <i class="fas fa-arrow-left mr-2"></i>
            Geri Dön
        </a>
    </div>
    
    <!-- Alerts Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Güvenlik Uyarıları</h3>
                <?php if ($unread_count > 0): ?>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        <?= $unread_count ?> okunmamış uyarı
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (empty($alerts)): ?>
            <div class="text-center py-12">
                <i class="fas fa-shield-check text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Uyarı yok</h3>
                <p class="text-gray-500 dark:text-gray-400">Şu anda sistem güvenlik uyarısı bulunmuyor.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Durum
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Zaman
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Tip
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Mesaj
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Önem
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                İşlemler
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($alerts as $alert): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 <?= $alert['is_read'] ? '' : 'bg-yellow-50 dark:bg-yellow-900 bg-opacity-50' ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($alert['is_read']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Okundu
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            Okunmadı
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= date('d.m.Y H:i:s', strtotime($alert['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($alert['alert_type'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($alert['message'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $severityColors = [
                                        'low' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                        'critical' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                    ];
                                    $severity = strtolower($alert['severity'] ?? 'medium');
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $severityColors[$severity] ?? $severityColors['medium'] ?>">
                                        <?= ucfirst($severity) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if (!$alert['is_read']): ?>
                                        <form method="POST" action="<?= base_url('/audit/alerts/' . $alert['id'] . '/read') ?>" class="inline">
                                            <?= CSRF::field() ?>
                                            <button type="submit" 
                                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                                Okundu İşaretle
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">
                                            <?= !empty($alert['read_by_username']) ? 'Okuyan: ' . e($alert['read_by_username']) : '' ?>
                                            <?php if (!empty($alert['read_at'])): ?>
                                                (<?= date('d.m.Y H:i', strtotime($alert['read_at'])) ?>)
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

