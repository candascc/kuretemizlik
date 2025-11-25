<?php
$title = 'System Monitoring - Küre Temizlik';
$breadcrumb = [
    ['name' => 'Ana Sayfa', 'url' => base_url('/')],
    ['name' => 'Ayarlar', 'url' => base_url('/settings/profile')],
    ['name' => 'Monitoring', 'url' => base_url('/settings/monitoring')]
];

// Get health check results
require_once __DIR__ . '/../../Lib/HealthCheck.php';
require_once __DIR__ . '/../../Lib/Metrics.php';
require_once __DIR__ . '/../../Lib/PerformanceMonitor.php';
require_once __DIR__ . '/../../Lib/Logger.php';
require_once __DIR__ . '/../../Lib/LogLevel.php';

$health = HealthCheck::quick();
$logStats = Logger::getStatistics();
$recentLogs = Logger::getRecentLogs(20);
?>

<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-chart-line mr-3"></i>
                Sistem İzleme
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Sistem performansını ve durumunu izleyin
            </p>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- System Information Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- PHP Version -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <i class="fab fa-php text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">PHP Sürümü</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        <?= PHP_VERSION ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Server Time -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <i class="fas fa-clock text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Sunucu Zamanı</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        <?= date('H:i:s') ?>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <?= date('d.m.Y') ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Memory Usage -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <i class="fas fa-memory text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Bellek Kullanımı</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        <?= number_format(memory_get_usage(true) / 1024 / 1024, 2) ?> MB
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Limit: <?= ini_get('memory_limit') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Database Information -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-database mr-2"></i>
            Veritabanı Bilgileri
        </h2>
        
        <?php
        $dbPath = DB_PATH;
        $dbSize = file_exists($dbPath) ? filesize($dbPath) : 0;
        ?>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Veritabanı Boyutu</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">
                    <?= number_format($dbSize / 1024 / 1024, 2) ?> MB
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Veritabanı Yolu</p>
                <p class="text-sm text-gray-900 dark:text-white break-all">
                    <?= e($dbPath) ?>
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Son Güncelleme</p>
                <p class="text-sm text-gray-900 dark:text-white">
                    <?= file_exists($dbPath) ? date('d.m.Y H:i:s', filemtime($dbPath)) : 'N/A' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Server Information -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-server mr-2"></i>
            Sunucu Bilgileri
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">İşletim Sistemi</p>
                <p class="text-sm text-gray-900 dark:text-white"><?= PHP_OS ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Web Sunucusu</p>
                <p class="text-sm text-gray-900 dark:text-white"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Maksimum Yüklenebilir Dosya Boyutu</p>
                <p class="text-sm text-gray-900 dark:text-white"><?= ini_get('upload_max_filesize') ?></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Maksimum POST Boyutu</p>
                <p class="text-sm text-gray-900 dark:text-white"><?= ini_get('post_max_size') ?></p>
            </div>
        </div>
    </div>

    <!-- System Health Status -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-heartbeat mr-2"></i>
            Sistem Durumu
        </h2>
        
        <div class="space-y-4">
            <!-- Database Connection -->
            <?php try {
                $db = Database::getInstance();
                $db->fetch("SELECT 1");
                $dbStatus = true;
            } catch (Exception $e) {
                $dbStatus = false;
            } ?>
            
            <div class="flex items-center justify-between p-4 rounded-lg <?= $dbStatus ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' ?>">
                <div class="flex items-center">
                    <i class="fas <?= $dbStatus ? 'fa-check-circle text-green-600' : 'fa-times-circle text-red-600' ?> mr-3 text-xl"></i>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Veritabanı Bağlantısı</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <?= $dbStatus ? 'Aktif ve çalışıyor' : 'Bağlantı hatası' ?>
                        </p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-medium <?= $dbStatus ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' ?>">
                    <?= $dbStatus ? 'OK' : 'HATA' ?>
                </span>
            </div>

            <!-- Session Status -->
            <div class="flex items-center justify-between p-4 rounded-lg bg-green-50 dark:bg-green-900/20">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3 text-xl"></i>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Oturum Yönetimi</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Oturum aktif: <?= session_status() === PHP_SESSION_ACTIVE ? 'Evet' : 'Hayır' ?>
                        </p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    OK
                </span>
            </div>

            <!-- Cache Status -->
            <?php try {
                Cache::get('test_key');
                $cacheStatus = true;
            } catch (Exception $e) {
                $cacheStatus = false;
            } ?>
            
            <div class="flex items-center justify-between p-4 rounded-lg <?= $cacheStatus ? 'bg-green-50 dark:bg-green-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20' ?>">
                <div class="flex items-center">
                    <i class="fas <?= $cacheStatus ? 'fa-check-circle text-green-600' : 'fa-exclamation-circle text-yellow-600' ?> mr-3 text-xl"></i>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Önbellek Sistemi</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <?= $cacheStatus ? 'Aktif ve çalışıyor' : 'Önbellek kullanılamıyor' ?>
                        </p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-medium <?= $cacheStatus ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' ?>">
                    <?= $cacheStatus ? 'OK' : 'UYARI' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- PHP Configuration -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-cog mr-2"></i>
            PHP Yapılandırması
        </h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ayar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Değer</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Max Execution Time</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= ini_get('max_execution_time') ?> s</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Memory Limit</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= ini_get('memory_limit') ?></td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Post Max Size</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= ini_get('post_max_size') ?></td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Upload Max Size</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= ini_get('upload_max_filesize') ?></td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Display Errors</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= ini_get('display_errors') ? 'On' : 'Off' ?></td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Error Reporting</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= error_reporting() ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Auto-refresh every 30 seconds
setTimeout(() => {
    window.location.reload();
}, 30000);
</script>

