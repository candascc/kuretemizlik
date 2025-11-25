<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Performans İzleme</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Sistem performansını izleyin ve veritabanı sorgularını optimize edin</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="refreshMetrics()" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                <i class="fas fa-sync-alt mr-2"></i>
                Yenile
            </button>
            <button onclick="optimizeSystem()" 
                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                <i class="fas fa-tools mr-2"></i>
                Optimize Et
            </button>
        </div>
    </div>

    <!-- Cache Statistics -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Önbellek İstatistikleri</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400"><?= $cache_stats['total_files'] ?></div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Toplam Dosyalar</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600 dark:text-green-400"><?= $cache_stats['valid_files'] ?></div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Geçerli Dosyalar</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-red-600 dark:text-red-400"><?= $cache_stats['expired_files'] ?></div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Süresi Dolmuş</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400"><?= $cache_stats['total_size_mb'] ?> MB</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Toplam Boyut</div>
            </div>
        </div>
        
        <div class="mt-6 flex space-x-3">
            <form method="POST" action="<?= base_url('/performance/cache') ?>" class="inline">
                <?= CSRF::field() ?>
                <input type="hidden" name="action" value="clear">
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200"
                        onclick="return confirm('Tüm önbelleği temizlemek istediğinize emin misiniz?')">
                    <i class="fas fa-trash mr-2"></i>
                    Tüm Önbelleği Temizle
                </button>
            </form>
            <form method="POST" action="<?= base_url('/performance/cache') ?>" class="inline">
                <?= CSRF::field() ?>
                <input type="hidden" name="action" value="clean_expired">
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                    <i class="fas fa-broom mr-2"></i>
                    Süresi Dolanları Temizle
                </button>
            </form>
        </div>
    </div>

    <!-- System Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Memory Usage -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Bellek Kullanımı</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Mevcut:</span>
                    <span class="font-medium"><?= $metrics['memory_usage']['current'] ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Tepe:</span>
                    <span class="font-medium"><?= $metrics['memory_usage']['peak'] ?></span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <?php 
                        $peakMb = (float)($metrics['memory_usage']['peak_mb'] ?? 0);
                        $currMb = (float)($metrics['memory_usage']['current_mb'] ?? 0);
                        $memPercent = $peakMb > 0 ? min(100, max(0, ($currMb / $peakMb) * 100)) : 0;
                    ?>
                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?= (int)$memPercent ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Disk Usage -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Disk Kullanımı</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Kullanılan:</span>
                    <span class="font-medium"><?= $metrics['disk_usage']['used'] ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Boş:</span>
                    <span class="font-medium"><?= $metrics['disk_usage']['free'] ?></span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: <?= $metrics['disk_usage']['percentage'] ?>%"></div>
                </div>
                <div class="text-center text-sm text-gray-600 dark:text-gray-400">
                    <?= $metrics['disk_usage']['percentage'] ?>% kullanıldı
                </div>
            </div>
        </div>
    </div>

    <!-- Slow Queries -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Yavaş Sorgular</h2>
        <?php if (empty($slow_queries)): ?>
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <i class="fas fa-check-circle text-4xl mb-4 text-green-500"></i>
                <p>Yavaş sorgu tespit edilmedi</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sorgu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Çalıştırma Süresi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Zaman</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach (array_slice($slow_queries, 0, 10) as $query): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded"><?= htmlspecialchars(substr($query['query'] ?? '', 0, 100)) ?>...</code>
                                    <?php if (isset($query['path'])): ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <i class="fas fa-route mr-1"></i><?= htmlspecialchars($query['method'] ?? 'GET') ?> <?= htmlspecialchars($query['path'] ?? '') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 font-medium">
                                    <?= number_format($query['duration_ms'] ?? 0, 2) ?>ms
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?= htmlspecialchars($query['occurred_at'] ?? 'N/A') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Performance Actions -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Performans İşlemleri</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <form method="POST" action="<?= base_url('/performance/optimize') ?>" class="inline">
                <?= CSRF::field() ?>
                <button type="submit" 
                        class="w-full inline-flex items-center justify-center px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                    <i class="fas fa-database mr-2"></i>
                    Veritabanını Optimize Et
                </button>
            </form>
            
            <form method="POST" action="<?= base_url('/performance/cache') ?>" class="inline">
                <?= CSRF::field() ?>
                <input type="hidden" name="action" value="optimize_indexes">
                <button type="submit" 
                        class="w-full inline-flex items-center justify-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                    <i class="fas fa-sort-amount-up mr-2"></i>
                    İndeksleri Optimize Et
                </button>
            </form>
            
            <button onclick="exportMetrics()" 
                    class="w-full inline-flex items-center justify-center px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                <i class="fas fa-download mr-2"></i>
                Metrikleri Dışa Aktar
            </button>
        </div>
    </div>
</div>

<script>
function refreshMetrics() {
    window.location.reload();
}

function optimizeSystem() {
    if (confirm('Bu işlem veritabanını optimize edecek ve önbelleği temizleyecek. Devam etmek istiyor musunuz?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('/performance/optimize') ?>';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= CSRF::get() ?>';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

function exportMetrics() {
    fetch('<?= base_url('/performance/metrics') ?>')
        .then(response => response.json())
        .then(data => {
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'performance-metrics-' + new Date().toISOString().split('T')[0] + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        })
        .catch(error => {
            alert('Metrikler dışa aktarılamadı: ' + error.message);
        });
}

// Auto-refresh every 30 seconds
setInterval(refreshMetrics, 30000);
</script>
