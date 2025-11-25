<div class="space-y-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Yedekleme Durumu</h1>
        <a href="<?= base_url('/cron/backup_db.php') ?>" class="text-sm text-blue-600" target="_blank">Manuel ??ali?Ytir (tarayici)</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-md shadow p-4">
            <div class="text-sm text-gray-500">Veritabani Boyutu</div>
            <div class="text-lg font-semibold"><?= Utils::formatFileSize($dbSize) ?></div>
        </div>
        <div class="bg-white rounded-md shadow p-4">
            <div class="text-sm text-gray-500">Son Yedek</div>
            <div class="text-lg font-semibold">
                <?php if ($last): ?>
                    <?= e($last['file']) ?>
                <?php else: ?>
                    Yok
                <?php endif; ?>
            </div>
        </div>
        <div class="bg-white rounded-md shadow p-4">
            <div class="text-sm text-gray-500">Son Yedek Zamani</div>
            <div class="text-lg font-semibold">
                <?= $last ? Utils::formatDateTime(date('Y-m-d H:i', $last['mtime'])) : '-' ?>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-md shadow p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Yedek Listesi</h2>
        <?php if (empty($backups)): ?>
            <div class="text-sm text-gray-500">Yedek bulunamadi.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dosya</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Boyut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($backups as $b): ?>
                            <tr>
                                <td class="px-6 py-3 text-sm"><a class="text-blue-600" href="<?= base_url('/db/backups/' . $b['file']) ?>" target="_blank"><?= e($b['file']) ?></a></td>
                                <td class="px-6 py-3 text-sm"><?= Utils::formatFileSize($b['size']) ?></td>
                                <td class="px-6 py-3 text-sm"><?= Utils::formatDateTime(date('Y-m-d H:i', $b['mtime'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

