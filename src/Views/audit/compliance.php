<?php
$stats = $data['stats'] ?? [];
$topUsers = $data['top_users'] ?? [];
$categoryBreakdown = $data['category_breakdown'] ?? [];
$period = $data['period'] ?? '30';
$dateFrom = $data['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $data['date_to'] ?? date('Y-m-d');
?>

<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-shield-alt mr-3"></i>
                Uyumluluk Raporu
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Sistem uyumluluk ve güvenlik durumu
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="<?= base_url('/audit') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                Geri
            </a>
        </div>
    </div>

    <!-- Period Selector -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <form method="GET" action="<?= base_url('/audit/compliance') ?>" class="flex items-center space-x-4">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Rapor Dönemi:
            </label>
            <select name="period" onchange="this.form.submit()" 
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                <option value="7" <?= $period == '7' ? 'selected' : '' ?>>Son 7 Gün</option>
                <option value="30" <?= $period == '30' ? 'selected' : '' ?>>Son 30 Gün</option>
                <option value="90" <?= $period == '90' ? 'selected' : '' ?>>Son 90 Gün</option>
                <option value="365" <?= $period == '365' ? 'selected' : '' ?>>Son 1 Yıl</option>
            </select>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                <?= date('d.m.Y', strtotime($dateFrom)) ?> - <?= date('d.m.Y', strtotime($dateTo)) ?>
            </span>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <i class="fas fa-list-alt text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Olay</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($stats['total_events'] ?? 0) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <i class="fas fa-users text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif Kullanıcı</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['unique_users'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                    <i class="fas fa-shield-alt text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Güvenlik Olayı</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($stats['security_events'] ?? 0) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <i class="fas fa-lock text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Kimlik Doğrulama</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($stats['auth_events'] ?? 0) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-chart-bar mr-2"></i>
                Kategori Dağılımı
            </h2>
            
            <div class="space-y-4">
                <?php if (empty($categoryBreakdown)): ?>
                    <p class="text-center text-gray-500 dark:text-gray-400 py-8">
                        Kategori verisi yok
                    </p>
                <?php else: ?>
                    <?php foreach ($categoryBreakdown as $category): ?>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-circle text-blue-500 mr-3 text-xs"></i>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?= e($category['category']) ?>
                                </span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <?php
                                    $maxCount = max(array_column($categoryBreakdown, 'count'));
                                    $percentage = $maxCount > 0 ? ($category['count'] / $maxCount) * 100 : 0;
                                    ?>
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?= $percentage ?>%"></div>
                                </div>
                                <span class="text-sm font-bold text-gray-900 dark:text-white w-12 text-right">
                                    <?= number_format($category['count']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-user-friends mr-2"></i>
                En Aktif Kullanıcılar
            </h2>
            
            <div class="space-y-3">
                <?php if (empty($topUsers)): ?>
                    <p class="text-center text-gray-500 dark:text-gray-400 py-8">
                        Kullanıcı aktivitesi yok
                    </p>
                <?php else: ?>
                    <?php foreach (array_slice($topUsers, 0, 10) as $index => $user): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">
                                    <?= $index + 1 ?>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($user['username'] ?? 'Bilinmeyen') ?>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                    <?= number_format($user['activity_count'] ?? 0) ?>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">etkinlik</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Data Changes Statistics -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-exchange-alt mr-2"></i>
            Veri Değişiklikleri
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Veri Değişikliği</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    <?= number_format($stats['data_changes'] ?? 0) ?>
                </p>
            </div>
            
            <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Günlük Ortalama</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    <?php
                    $days = (int)$period;
                    $dailyAvg = $days > 0 ? ($stats['data_changes'] ?? 0) / $days : 0;
                    echo number_format($dailyAvg, 1);
                    ?>
                </p>
            </div>
            
            <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Güvenlik Oranı</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    <?php
                    $total = $stats['total_events'] ?? 1;
                    $security = $stats['security_events'] ?? 0;
                    echo number_format(($security / $total) * 100, 1);
                    ?>%
                </p>
            </div>
        </div>
    </div>

    <!-- Export Button -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Rapor Dışa Aktarma
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Uyumluluk raporunu CSV veya JSON formatında dışa aktarın
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="<?= base_url("/audit/export?format=csv&date_from=$dateFrom&date_to=$dateTo") ?>" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition">
                    <i class="fas fa-file-csv mr-2"></i>
                    CSV İndir
                </a>
                <a href="<?= base_url("/audit/export?format=json&date_from=$dateFrom&date_to=$dateTo") ?>" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                    <i class="fas fa-file-code mr-2"></i>
                    JSON İndir
                </a>
            </div>
        </div>
    </div>
</div>
