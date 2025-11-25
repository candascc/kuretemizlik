<?php
/**
 * Audit Log Detail View
 */
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-file-alt mr-3"></i>
                Denetim Kaydı Detayları
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Kayıt ID: #<?= e($log['id']) ?></p>
        </div>
        <a href="<?= base_url('/audit') ?>" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
            <i class="fas fa-arrow-left mr-2"></i>
            Geri Dön
        </a>
    </div>
    
    <!-- Main Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Temel Bilgiler</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Zaman</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <?= date('d.m.Y H:i:s', strtotime($log['created_at'])) ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kategori</dt>
                        <dd class="mt-1">
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
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">İşlem</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <?= htmlspecialchars($log['action'] ?? 'N/A') ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kullanıcı</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <?= !empty($log['username']) ? e($log['username']) : 'Sistem' ?>
                            <?php if (!empty($log['user_role'])): ?>
                                <span class="ml-2 text-xs text-gray-500">(<?= e($log['user_role']) ?>)</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
            </div>
            
            <!-- Network Information -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ağ Bilgileri</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Adresi</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <?php
                            $meta = !empty($log['meta_json']) ? json_decode($log['meta_json'], true) : [];
                            $ipAddress = $meta['ip'] ?? $log['ip_address'] ?? 'N/A';
                            echo e($ipAddress);
                            ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User Agent</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono text-xs">
                            <?php
                            $userAgent = $meta['user_agent'] ?? $log['user_agent'] ?? 'N/A';
                            echo htmlspecialchars(substr($userAgent, 0, 100)) . (strlen($userAgent) > 100 ? '...' : '');
                            ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
        
        <!-- Additional Details -->
        <?php if (!empty($log['description']) || !empty($log['meta_json'])): ?>
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ek Detaylar</h3>
                
                <?php if (!empty($log['description'])): ?>
                    <div class="mb-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Açıklama</dt>
                        <dd class="text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <?= nl2br(e($log['description'])) ?>
                        </dd>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($log['meta_json'])): ?>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Meta Veriler</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            <pre class="bg-gray-50 dark:bg-gray-700 p-3 rounded overflow-x-auto text-xs"><?= htmlspecialchars(json_encode(json_decode($log['meta_json'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                        </dd>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

