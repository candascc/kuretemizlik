<?php
// Expected keys: $data (summary arrays), $dateFrom, $dateTo, $reportType
?>
<!-- KUREAPP_R36_MARKER_REPORTS_VIEW_V1 -->
<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Finans Raporları</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Gelir, gider ve karlılığı analiz edin</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="exportReport('pdf')" 
                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                <i class="fas fa-file-pdf mr-2"></i>
                PDF Dışa Aktar
            </button>
            <button onclick="exportReport('excel')" 
                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                <i class="fas fa-file-excel mr-2"></i>
                Excel Dışa Aktar
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Başlangıç Tarihi</label>
                <input type="date" name="date_from" value="<?= e($dateFrom) ?>" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bitiş Tarihi</label>
                <input type="date" name="date_to" value="<?= e($dateTo) ?>" 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rapor Türü</label>
                <select name="type" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="summary" <?= e($reportType) === 'summary' ? 'selected' : '' ?>>Özet</option>
                    <option value="daily" <?= e($reportType) === 'daily' ? 'selected' : '' ?>>Günlük</option>
                    <option value="monthly" <?= e($reportType) === 'monthly' ? 'selected' : '' ?>>Aylık</option>
                    <option value="by_category" <?= e($reportType) === 'by_category' ? 'selected' : '' ?>>Kategoriye Göre</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" 
                        class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                    <i class="fas fa-search mr-2"></i>
                    Rapor Oluştur
                </button>
            </div>
        </form>
    </div>

    <!-- Report Content -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <?php if ($reportType === 'summary'): ?>
            <!-- Financial Summary -->
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Finansal Özet</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
                <div class="text-center p-4 bg-green-50 dark:bg-green-900 rounded-lg">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">₺<?= number_format($data['total_income'], 2) ?></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Toplam Gelir</div>
                    <div class="text-xs text-gray-500 dark:text-gray-500"><?= $data['income_count'] ?> kayıt</div>
                </div>
                <div class="text-center p-4 bg-red-50 dark:bg-red-900 rounded-lg">
                    <div class="text-3xl font-bold text-red-600 dark:text-red-400">₺<?= number_format($data['total_expense'], 2) ?></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Toplam Gider</div>
                    <div class="text-xs text-gray-500 dark:text-gray-500"><?= $data['expense_count'] ?> kayıt</div>
                </div>
                <div class="text-center p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">₺<?= number_format($data['net_profit'], 2) ?></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Net Kâr</div>
                </div>
                <div class="text-center p-4 bg-purple-50 dark:bg-purple-900 rounded-lg">
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400"><?= $data['profit_margin'] ?>%</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Kâr Marjı</div>
                </div>
            </div>

        <?php elseif ($reportType === 'daily'): ?>
            <!-- Daily Financial Data -->
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Günlük Finansal Veriler</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarih</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gelir</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gider</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Net Kâr</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?= $row['date'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400 font-medium">₺<?= number_format($row['income'], 2) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 font-medium">₺<?= number_format($row['expense'], 2) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?= $row['net_profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                                    ₺<?= number_format($row['net_profit'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($reportType === 'monthly'): ?>
            <!-- Monthly Financial Data -->
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Aylık Finansal Veriler</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ay</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gelir</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gider</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Net Kâr</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?= $row['month'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400 font-medium">₺<?= number_format($row['income'], 2) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 font-medium">₺<?= number_format($row['expense'], 2) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?= $row['net_profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                                    ₺<?= number_format($row['net_profit'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($reportType === 'by_category'): ?>
            <!-- Financial by Category -->
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Kategoriye Göre Finansal Veriler</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tür</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Adet</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Toplam Tutar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ortalama</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?= htmlspecialchars($row['category'] ?? '') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= ($row['type'] ?? '') === 'INCOME' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' ?>">
                                        <?= ($row['type'] ?? '') === 'INCOME' ? 'Gelir' : 'Gider' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"><?= $row['count'] ?? 0 ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?= ($row['type'] ?? '') === 'INCOME' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                                    ₺<?= number_format($row['total_amount'] ?? 0, 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">₺<?= number_format($row['avg_amount'] ?? 0, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function exportReport(format) {
    const url = new URL(window.location);
    url.searchParams.set('export', format);
    window.open(url.toString(), '_blank');
}
</script>