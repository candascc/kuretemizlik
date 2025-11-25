<div class="space-y-8">
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Denetim Kayıtları
                </h1>
                <p class="text-gray-600 dark:text-gray-300 mt-2">
                    Sistem etkinliği ve güvenlik izleme
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="<?= base_url('/audit/export') ?>?<?= http_build_query($filters) ?>" 
                   class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    <i class="fas fa-download mr-2"></i>
                    Dışa Aktar
                </a>
                <a href="<?= base_url('/audit/compliance') ?>" 
                   class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-chart-line mr-2"></i>
                    Uyumluluk
                </a>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                    <i class="fas fa-list text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Toplam Kayıtlar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($summary['total_logs'] ?? 0) ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                    <i class="fas fa-users text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Aktif Kullanıcılar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($summary['active_users'] ?? 0) ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                    <i class="fas fa-calendar-day text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Bugün</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($summary['logs_today'] ?? 0) ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                    <i class="fas fa-shield-alt text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Güvenlik Olayları</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($summary['security_events_today'] ?? 0) ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-<?= Auth::hasRole('SUPERADMIN') && !empty($companies) ? '7' : '6' ?> gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Başlangıç Tarihi
                </label>
                <input type="date" 
                       name="date_from" 
                       value="<?= e($filters['date_from']) ?>"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Bitiş Tarihi
                </label>
                <input type="date" 
                       name="date_to" 
                       value="<?= e($filters['date_to']) ?>"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Kategori
                </label>
                <select name="category" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tüm Kategoriler</option>
                    <option value="AUTH" <?= $filters['category'] === 'AUTH' ? 'selected' : '' ?>>Kimlik Doğrulama</option>
                    <option value="DATA_ACCESS" <?= $filters['category'] === 'DATA_ACCESS' ? 'selected' : '' ?>>Veri Erişimi</option>
                    <option value="DATA_MODIFICATION" <?= $filters['category'] === 'DATA_MODIFICATION' ? 'selected' : '' ?>>Veri Değişikliği</option>
                    <option value="SECURITY" <?= $filters['category'] === 'SECURITY' ? 'selected' : '' ?>>Güvenlik</option>
                    <option value="ADMIN" <?= $filters['category'] === 'ADMIN' ? 'selected' : '' ?>>Yönetim</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Kullanıcı ID
                </label>
                <input type="number" 
                       name="user_id" 
                       value="<?= e($filters['user_id']) ?>"
                       placeholder="Kullanıcı ID"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
            
            <!-- STAGE 1 ROUND 2: IP Address Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    IP Adresi
                </label>
                <input type="text" 
                       name="ip_address" 
                       value="<?= e($filters['ip_address'] ?? '') ?>"
                       placeholder="192.168.1.1"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
            
            <!-- ROUND 3: Company Filter (SUPERADMIN only) -->
            <?php if (Auth::hasRole('SUPERADMIN') && !empty($companies)): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Şirket
                </label>
                <select name="company_id" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tüm Şirketler</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= e($company['id']) ?>" <?= (string)($filters['company_id'] ?? '') === (string)$company['id'] ? 'selected' : '' ?>>
                            <?= e($company['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="flex items-end">
                <button type="submit" 
                        class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-search mr-2"></i>
                    Filtrele
                </button>
            </div>
        </form>
    </div>
    
    <!-- Audit Logs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
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
                            Kullanıcı
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
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?= date('M j, Y H:i:s', strtotime($log['created_at'])) ?>
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
                                    echo $categoryColors[$log['category'] ?? $log['entity'] ?? ''] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
                                    ?>">
                                    <?= htmlspecialchars($log['category'] ?? $log['entity'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?= htmlspecialchars($log['action'] ?? 'N/A') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?= !empty($log['username']) ? e($log['username']) : 'Sistem' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <?php
                                // STAGE 1 ROUND 2: Prefer direct column, fallback to metadata
                                $ipAddress = $log['ip_address'] ?? null;
                                if (!$ipAddress && !empty($log['meta_json'])) {
                                    $meta = json_decode($log['meta_json'], true);
                                    $ipAddress = $meta['ip_address'] ?? $meta['ip'] ?? null;
                                }
                                echo e($ipAddress ?? 'N/A');
                                ?>
                            </td>
                            <?php if (Auth::hasRole('SUPERADMIN')): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?php
                                $companyName = $log['company_name'] ?? null;
                                $companyId = $log['company_id'] ?? null;
                                if ($companyName) {
                                    echo e($companyName);
                                } elseif ($companyId) {
                                    // Fallback: try to get from companies array
                                    if (!empty($companies)) {
                                        $company = array_filter($companies, fn($c) => (int)$c['id'] === (int)$companyId);
                                        $company = reset($company);
                                        echo e($company['name'] ?? "Company #{$companyId}");
                                    } else {
                                        echo "Company #{$companyId}";
                                    }
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <?php endif; ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="<?= base_url('/audit/' . $log['id']) ?>" 
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                    Detayları Gör
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (empty($logs)): ?>
            <div class="text-center py-12">
                <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Denetim kaydı bulunamadı</h3>
                <p class="text-gray-500 dark:text-gray-400">Filtreleri değiştirerek daha fazla sonuç görebilirsiniz.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
        <?php if ($pagination['has_more']): ?>
        <div class="mt-6 flex justify-center">
            <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['page'] + 1])) ?>" 
               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Daha Fazla Yükle
            </a>
        </div>
    <?php endif; ?>
</div>
