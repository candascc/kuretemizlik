<?php
// Expected: $data, $dateFrom, $dateTo, $reportType
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">İş Raporları</h1>
        <form method="GET" class="flex items-center space-x-2">
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
            <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
            <select name="type" class="px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                <option value="summary" <?= $reportType==='summary'?'selected':'' ?>>Özet</option>
                <option value="by_status" <?= $reportType==='by_status'?'selected':'' ?>>Duruma Göre</option>
                <option value="by_customer" <?= $reportType==='by_customer'?'selected':'' ?>>Müşteriye Göre</option>
                <option value="by_service" <?= $reportType==='by_service'?'selected':'' ?>>Hizmete Göre</option>
                <option value="performance" <?= $reportType==='performance'?'selected':'' ?>>Performans</option>
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
            <div class="text-sm text-gray-500 dark:text-gray-400">Toplam İş</div>
            <div class="text-2xl font-semibold mt-1"><?php echo (int)($data['summary']['total'] ?? 0); ?></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Tamamlanan</div>
            <div class="text-2xl font-semibold mt-1 text-green-600"><?php echo (int)($data['summary']['done'] ?? 0); ?></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">İptal</div>
            <div class="text-2xl font-semibold mt-1 text-red-600"><?php echo (int)($data['summary']['cancelled'] ?? 0); ?></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">Gelir</div>
            <div class="text-2xl font-semibold mt-1 text-blue-600"><?php echo number_format($data['summary']['revenue'] ?? 0, 2); ?> ₺</div>
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
            <tbody class="text-sm">
                <?php foreach($data['rows'] as $r): ?>
                <tr class="border-t dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <?php foreach($r as $v): ?>
                        <td class="px-3 py-2"><?php echo is_numeric($v) ? number_format((float)$v, 2) : htmlspecialchars((string)$v); ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="text-gray-500 dark:text-gray-400">Veri bulunamadı.</div>
    <?php endif; ?>
</div>


