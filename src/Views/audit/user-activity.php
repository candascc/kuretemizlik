<?php
/**
 * User Activity View
 */
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-user-clock mr-3"></i>
                Kullanıcı Etkinliği
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kullanıcı: <span class="font-semibold"><?= e($user['username']) ?></span>
                (Son <?= $days ?> gün)
            </p>
        </div>
        <a href="<?= base_url('/audit') ?>" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
            <i class="fas fa-arrow-left mr-2"></i>
            Geri Dön
        </a>
    </div>
    
    <!-- User Info Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kullanıcı Adı</p>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                    <?= e($user['username']) ?>
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rol</p>
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                    <?= htmlspecialchars($user['role'] ?? 'N/A') ?>
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam Etkinlik</p>
                <p class="mt-1 text-lg font-semibold text-blue-600 dark:text-blue-400">
                    <?= count($activity) ?> kayıt
                </p>
            </div>
        </div>
    </div>
    
    <!-- Activity Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Etkinlik Geçmişi</h3>
        </div>
        
        <?php if (empty($activity)): ?>
            <div class="text-center py-12">
                <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Etkinlik bulunamadı</h3>
                <p class="text-gray-500 dark:text-gray-400">Bu kullanıcı için son <?= $days ?> gün içinde kayıt yok.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Zaman
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Kategori
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                İşlem
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                IP Adresi
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                İşlemler
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($activity as $log): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= date('d.m.Y H:i:s', strtotime($log['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php
                                        $categoryColors = [
                                            'AUTH' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            'DATA_ACCESS' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'DATA_MODIFICATION' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'SECURITY' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            'ADMIN' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
                                        ];
                                        $category = $log['category'] ?? $log['entity'] ?? 'UNKNOWN';
                                        echo $categoryColors[$category] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
                                        ?>">
                                        <?= e($category) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($log['action'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?php
                                    $meta = !empty($log['meta_json']) ? json_decode($log['meta_json'], true) : [];
                                    $ipAddress = $meta['ip'] ?? $log['ip_address'] ?? 'N/A';
                                    echo e($ipAddress);
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="<?= base_url('/audit/' . $log['id']) ?>" 
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                        Detaylar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

