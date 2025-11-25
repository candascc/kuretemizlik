<?php
$dateFrom = htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8');
$dateTo = htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<div class="space-y-8" data-page="audit-role-summary">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-user-shield text-primary-500"></i>
                Rol Bazlı Aktivite Özeti
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                Seçilen tarih aralığında her rolün gerçekleştirdiği işlem yoğunluğunu ve anormallikleri görüntüleyin.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="<?= base_url('/audit/roles/export?date_from=' . $dateFrom . '&date_to=' . $dateTo . '&format=csv') ?>"
               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                <i class="fas fa-download mr-2"></i>CSV Dışa Aktar
            </a>
            <a href="<?= base_url('/audit/roles/export?date_from=' . $dateFrom . '&date_to=' . $dateTo . '&format=json') ?>"
               class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg">
                <i class="fas fa-file-code mr-2"></i>JSON Dışa Aktar
            </a>
        </div>
    </div>

    <form method="get" class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Başlangıç</label>
            <input type="date" name="date_from" value="<?= $dateFrom ?>"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bitiş</label>
            <input type="date" name="date_to" value="<?= $dateTo ?>"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">
                <i class="fas fa-sync mr-2"></i>Filtrele
            </button>
        </div>
    </form>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-users"></i> Rol Aktivite Dağılımı
                </h2>
                <span class="text-sm text-gray-500 dark:text-gray-400"><?= count($summary) ?> rol</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Rol</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Olay Sayısı</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aktif Kullanıcı</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Son Aktivite</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($summary)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                                    Kayıt bulunamadı.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($summary as $row): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                        <?= e($row['role']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <?= number_format($row['total_events']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <?= number_format($row['unique_users']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        <?= date('d M Y H:i', strtotime($row['last_seen'] ?? $row['first_seen'] ?? 'now')) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-stream"></i> En Fazla İşlem
                </h2>
                <span class="text-sm text-gray-500 dark:text-gray-400"><?= count($action_matrix) ?> satır</span>
            </div>
            <div class="p-4 space-y-4 max-h-[420px] overflow-y-auto">
                <?php if (empty($action_matrix)): ?>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Veri bulunamadı.</p>
                <?php else: ?>
                    <?php foreach ($action_matrix as $row): ?>
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    <?= e($row['role']) ?>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    <?= e($row['action']) ?>
                                </p>
                            </div>
                            <span class="text-sm font-semibold text-primary-600 dark:text-primary-300">
                                <?= number_format($row['total_events']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-triangle-exclamation text-red-500"></i>
                Olası Anomali Kayıtları
            </h2>
            <span class="text-sm text-gray-500 dark:text-gray-400"><?= count($anomalies) ?> kayıt</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Rol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">İşlem</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Beklenen Roller</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Olay</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Zaman Aralığı</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($anomalies)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                                Belirlenen kriterlere göre anomali bulunamadı.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($anomalies as $row): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                    <?= e($row['role']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <p class="font-medium"><?= e($row['action']) ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($row['entity'] ?? '') ?></p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <span class="text-xs text-gray-500">İzin verilen:</span>
                                    <div class="text-sm">
                                        <?= htmlspecialchars(implode(', ', $row['allowed_roles'] ?? [])) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= number_format($row['total_events'] ?? 0) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                    <?= date('d M H:i', strtotime($row['first_seen'])) ?> – <?= date('d M H:i', strtotime($row['last_seen'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

