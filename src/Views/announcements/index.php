<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-bullhorn mr-3 text-primary-600"></i>
                Duyuru Yönetimi
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Bina duyuruları ve bildirimler</p>
        </div>
        <a href="<?= base_url('/announcements/new') ?>" 
           class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
            <i class="fas fa-plus mr-2"></i>
            Yeni Duyuru
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bina</label>
                <select name="building_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tüm Binalar</option>
                    <?php foreach (($buildings ?? []) as $bld): ?>
                        <option value="<?= $bld['id'] ?>" <?= ($filters['building_id'] ?? '') == $bld['id'] ? 'selected' : '' ?>>
                            <?= e($bld['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tip</label>
                <select name="announcement_type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <option value="info" <?= ($filters['announcement_type'] ?? '') == 'info' ? 'selected' : '' ?>>Bilgi</option>
                    <option value="warning" <?= ($filters['announcement_type'] ?? '') == 'warning' ? 'selected' : '' ?>>Uyarı</option>
                    <option value="urgent" <?= ($filters['announcement_type'] ?? '') == 'urgent' ? 'selected' : '' ?>>Acil</option>
                    <option value="event" <?= ($filters['announcement_type'] ?? '') == 'event' ? 'selected' : '' ?>>Etkinlik</option>
                    <option value="maintenance" <?= ($filters['announcement_type'] ?? '') == 'maintenance' ? 'selected' : '' ?>>Bakım</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-search mr-2"></i>Filtrele
                </button>
            </div>
        </form>
    </div>

    <!-- Announcements List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <?php if (empty($announcements ?? [])): ?>
            <div class="p-12 text-center">
                <i class="fas fa-bullhorn text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400 text-lg">Henüz duyuru yok</p>
                <a href="<?= base_url('/announcements/new') ?>" class="mt-4 inline-block px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">
                    İlk Duyuruyu Oluştur
                </a>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach (($announcements ?? []) as $announcement): ?>
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <?php if ($announcement['is_pinned'] ?? 0): ?>
                                        <i class="fas fa-thumbtack text-primary-600"></i>
                                    <?php endif; ?>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($announcement['title'] ?? '-') ?>
                                    </h3>
                                    <?php
                                    $typeColors = [
                                        'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'urgent' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'event' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'maintenance' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
                                    ];
                                    $typeTexts = ['info' => 'Bilgi', 'warning' => 'Uyarı', 'urgent' => 'Acil', 'event' => 'Etkinlik', 'maintenance' => 'Bakım'];
                                    $type = $announcement['announcement_type'] ?? 'info';
                                    ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $typeColors[$type] ?? $typeColors['info'] ?>">
                                        <?= $typeTexts[$type] ?? 'Bilgi' ?>
                                    </span>
                                </div>
                                <p class="mt-2 text-gray-600 dark:text-gray-400">
                                    <?= htmlspecialchars(substr($announcement['content'] ?? '', 0, 150)) ?>...
                                </p>
                                <div class="mt-3 flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span><i class="fas fa-calendar mr-1"></i><?= $announcement['publish_date'] ? date('d.m.Y', strtotime($announcement['publish_date'])) : '-' ?></span>
                                    <?php if ($announcement['expire_date']): ?>
                                        <span><i class="fas fa-clock mr-1"></i>Bitiş: <?= date('d.m.Y', strtotime($announcement['expire_date'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                <?php if ($announcement['send_email'] ?? 0): ?>
                                    <i class="fas fa-envelope text-gray-400" title="E-posta gönderildi"></i>
                                <?php endif; ?>
                                <?php if ($announcement['send_sms'] ?? 0): ?>
                                    <i class="fas fa-sms text-gray-400" title="SMS gönderildi"></i>
                                <?php endif; ?>
                                <a href="#" onclick="if(confirm('Bu duyuruyu silmek istediğinize emin misiniz?')) { fetch('<?= base_url('/announcements/delete/' . $announcement['id']) ?>', {method: 'POST'}).then(() => location.reload()); } return false;" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

