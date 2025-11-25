<?php
$title = 'Dashboard Raporu - Küre Temizlik';
$breadcrumb = [
    ['name' => 'Ana Sayfa', 'url' => base_url('/')],
    ['name' => 'Raporlar', 'url' => base_url('/reports')],
    ['name' => 'Dashboard Raporu', 'url' => base_url('/reports/dashboard')]
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard Raporu</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Genel istatistikler ve performans özeti</p>
    </div>

    <?php include __DIR__ . '/../partials/company-context.php'; ?>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-users text-blue-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Müşteri</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['total_customers'] ?? '0' ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-tasks text-green-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam İş</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['total_jobs'] ?? '0' ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-dollar-sign text-yellow-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Gelir</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">₺<?= number_format($stats['total_revenue'] ?? 0, 2) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-percentage text-purple-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tamamlanma Oranı</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">%<?= $stats['completion_rate'] ?? '0' ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Monthly Revenue Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Aylık Gelir Trendi</h3>
            <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                <div class="text-center">
                    <i class="fas fa-chart-line text-4xl mb-2"></i>
                    <p>Grafik verisi yükleniyor...</p>
                </div>
            </div>
        </div>

        <!-- Job Status Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">İş Durumu Dağılımı</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Tamamlanan</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white"><?= $stats['completed_jobs'] ?? '0' ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Devam Eden</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white"><?= $stats['active_jobs'] ?? '0' ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Bekleyen</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white"><?= $stats['pending_jobs'] ?? '0' ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Son Aktiviteler</h3>
        <div class="space-y-3">
            <?php if (!empty($stats['recent_activities'])): ?>
                <?php foreach ($stats['recent_activities'] as $activity): ?>
                    <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex-shrink-0">
                            <i class="fas fa-circle text-blue-500 text-xs"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900 dark:text-white"><?= e($activity['description']) ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= $activity['created_at'] ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-history text-4xl mb-2"></i>
                    <p>Henüz aktivite bulunmuyor</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
