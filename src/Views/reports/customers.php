<?php
// Expected: $data, $dateFrom, $dateTo, $reportType, $summary
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Müşteri Raporları</h1>
        <form method="GET" class="flex items-center space-x-2">
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
            <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
            <select name="type" class="px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                <option value="summary" <?= $reportType==='summary'?'selected':'' ?>>Özet</option>
                <option value="top_customers" <?= $reportType==='top_customers'?'selected':'' ?>>En Değerli Müşteriler</option>
                <option value="new_customers" <?= $reportType==='new_customers'?'selected':'' ?>>Yeni Müşteriler</option>
                <option value="customer_activity" <?= $reportType==='customer_activity'?'selected':'' ?>>Müşteri Etkinliği</option>
            </select>
            <button class="px-4 py-2 bg-primary-600 text-white rounded-lg">Filtrele</button>
            <a href="?date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&type=<?= urlencode($reportType) ?>&export=excel" class="px-3 py-2 border rounded-lg">Excel</a>
            <a href="?date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&type=<?= urlencode($reportType) ?>&export=pdf" class="px-3 py-2 border rounded-lg">PDF</a>
        </form>
    </div>

    <?php include __DIR__ . '/../partials/company-context.php'; ?>

    <?php if (!empty($data['summary'])): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Toplam Müşteri</div>
            <div class="text-2xl font-semibold mt-1"><?php echo (int)($data['summary']['total'] ?? 0); ?></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Yeni Müşteriler</div>
            <div class="text-2xl font-semibold mt-1 text-green-600"><?php echo (int)($data['summary']['new'] ?? 0); ?></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Toplam İş</div>
            <div class="text-2xl font-semibold mt-1 text-blue-600"><?php echo (int)($data['summary']['total_jobs'] ?? 0); ?></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Toplam Gelir</div>
            <div class="text-2xl font-semibold mt-1 text-purple-600"><?php echo number_format($data['summary']['total_revenue'] ?? 0, 2); ?> ₺</div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($data['rows'])): ?>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border dark:border-gray-700 overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="text-left text-xs uppercase text-gray-500 dark:text-gray-300">
                    <?php foreach(array_keys($data['rows'][0]) as $h): ?>
                        <th class="px-3 py-2"><?php echo e($h); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data['rows'] as $row): ?>
                <tr class="border-t dark:border-gray-700">
                    <?php foreach($row as $val): ?>
                        <td class="px-3 py-2"><?php echo e($val); ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-12 border dark:border-gray-700 text-center">
        <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
        <p class="text-gray-600 dark:text-gray-400">Seçilen dönem için müşteri verisi bulunamadı</p>
    </div>
    <?php endif; ?>
</div>

