<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-file-contract mr-3 text-primary-600"></i>
                Süresi Yaklaşan Sözleşmeler
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Önümüzdeki <?= htmlspecialchars((string)($days ?? 30)) ?> gün içinde bitecek sözleşmeler</p>
        </div>
        <a href="<?= base_url('/contracts') ?>" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">
            Tüm Sözleşmeler
        </a>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <?php if (empty($contracts)): ?>
            <div class="p-12 text-center">
                <i class="fas fa-search text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <div class="text-gray-600 dark:text-gray-400">Yakında süresi bitecek sözleşme bulunamadı.</div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Müşteri</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Başlangıç</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bitiş</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durum</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($contracts as $c): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap font-medium"><?= htmlspecialchars($c['customer_name'] ?? '-') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= !empty($c['start_date']) ? date('d.m.Y', strtotime($c['start_date'])) : '-' ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= !empty($c['end_date']) ? date('d.m.Y', strtotime($c['end_date'])) : '-' ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($c['status'] ?? '-') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="<?= base_url('/contracts/show/' . ($c['id'] ?? '')) ?>" class="text-primary-600 hover:text-primary-900 mr-3"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>


