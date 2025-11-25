<?php
/**
 * Service Performance Report View
 */
?>

<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-concierge-bell mr-3 text-primary-600"></i>
                Hizmet Performans Raporu
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Hizmet bazlı istatistikler ve performans analizi</p>
        </div>
        <div class="flex gap-3">
            <button onclick="exportData('excel')" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg">
                <i class="fas fa-file-excel mr-2"></i>Excel
            </button>
            <button onclick="exportData('pdf')" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg">
                <i class="fas fa-file-pdf mr-2"></i>PDF
            </button>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/company-context.php'; ?>

    <!-- Date Filter -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <form method="GET" class="flex gap-4">
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="form-input">
            <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="form-input">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg">
                <i class="fas fa-filter mr-2"></i>Filtrele
            </button>
        </form>
    </div>

    <!-- Service Statistics -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Hizmet İstatistikleri</h2>
        
        <?php if (!empty($data)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Hizmet</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Toplam İş</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tamamlanan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">İptal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Toplam Gelir</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ortalama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tamamlanma Oranı</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php 
                        $grandTotal = 0;
                        foreach ($data as $service): 
                            $grandTotal += $service['total_revenue'];
                        ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <?= htmlspecialchars($service['name'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 text-center">
                                    <?= number_format($service['total_jobs'] ?? 0) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400 text-center">
                                    <?= number_format($service['completed_jobs'] ?? 0) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 text-center">
                                    <?= number_format($service['cancelled_jobs'] ?? 0) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-blue-600 dark:text-blue-400">
                                    <?= number_format($service['total_revenue'] ?? 0, 2) ?> ₺
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    <?= number_format($service['avg_job_value'] ?? 0, 2) ?> ₺
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center">
                                        <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                            <div class="bg-green-600 h-2 rounded-full" style="width: <?= min(100, $service['completion_rate'] ?? 0) ?>%"></div>
                                        </div>
                                        <span class="text-gray-600 dark:text-gray-400"><?= number_format($service['completion_rate'] ?? 0, 1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">TOPLAM</td>
                            <td colspan="3"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-600 dark:text-blue-400">
                                <?= number_format($grandTotal, 2) ?> ₺
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <i class="fas fa-inbox text-4xl mb-4"></i>
                <p>Seçilen dönemde veri bulunmamaktadır.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function exportData(format) {
    const url = new URL(window.location.href);
    url.searchParams.set('export', format);
    window.location.href = url.toString();
}
</script>

