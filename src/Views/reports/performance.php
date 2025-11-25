<?php
$title = 'Performans Raporu - Küre Temizlik';
$breadcrumb = [
    ['name' => 'Ana Sayfa', 'url' => base_url('/')],
    ['name' => 'Raporlar', 'url' => base_url('/reports')],
    ['name' => 'Performans Raporu', 'url' => base_url('/reports/performance')]
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Performans Raporu</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">İş tamamlama oranları ve verimlilik analizi</p>
    </div>

    <?php include __DIR__ . '/../partials/company-context.php'; ?>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tamamlanma Oranı</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">%<?= $data['completion_rate'] ?? '0' ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-clock text-blue-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ortalama Süre</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $data['avg_duration'] ?? '0' ?> dk</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-star text-yellow-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Müşteri Memnuniyeti</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $data['satisfaction_rate'] ?? '0' ?>/5</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-users text-purple-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif Personel</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $data['active_staff'] ?? '0' ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Completion Rate Trend -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tamamlanma Oranı Trendi</h3>
            <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                <div class="text-center">
                    <i class="fas fa-chart-line text-4xl mb-2"></i>
                    <p>Grafik verisi yükleniyor...</p>
                </div>
            </div>
        </div>

        <!-- Staff Performance -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Personel Performansı</h3>
            <div class="space-y-3">
                <?php if (!empty($data['staff_performance'])): ?>
                    <?php foreach ($data['staff_performance'] as $staff): ?>
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white"><?= e($staff['name']) ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><?= $staff['completed_jobs'] ?> iş tamamlandı</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">%<?= $staff['completion_rate'] ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><?= $staff['avg_rating'] ?>/5</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-user-tie text-2xl mb-2"></i>
                        <p>Personel verisi bulunamadı</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Service Performance -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Hizmet Performansı</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hizmet</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Toplam İş</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tamamlanan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Oran</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ort. Süre</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (!empty($data['service_performance'])): ?>
                        <?php foreach ($data['service_performance'] as $service): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    <?= e($service['name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?= $service['total_jobs'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?= $service['completed_jobs'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    %<?= $service['completion_rate'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?= $service['avg_duration'] ?> dk
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-chart-bar text-2xl mb-2"></i>
                                <p>Hizmet verisi bulunamadı</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Monthly Performance Summary -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Aylık Performans Özeti</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="text-3xl font-bold text-green-600 mb-2"><?= $data['monthly_completed'] ?? '0' ?></div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Bu Ay Tamamlanan</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-blue-600 mb-2"><?= $data['monthly_revenue'] ?? '0' ?></div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Bu Ay Gelir (₺)</div>
            </div>
            <div class="text-center">
                <div class="text-3xl font-bold text-purple-600 mb-2"><?= $data['monthly_efficiency'] ?? '0' ?>%</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Verimlilik Oranı</div>
            </div>
        </div>
    </div>
</div>
