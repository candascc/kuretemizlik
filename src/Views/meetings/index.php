<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-handshake mr-3 text-primary-600"></i>
                Toplantı Yönetimi
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Yönetim kurulu ve genel kurul toplantıları</p>
        </div>
        <a href="<?= base_url('/meetings/new') ?>" 
           class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
            <i class="fas fa-plus mr-2"></i>
            Yeni Toplantı
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
                <select name="meeting_type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <option value="regular" <?= ($filters['meeting_type'] ?? '') == 'regular' ? 'selected' : '' ?>>Olağan</option>
                    <option value="extraordinary" <?= ($filters['meeting_type'] ?? '') == 'extraordinary' ? 'selected' : '' ?>>Olağanüstü</option>
                    <option value="board" <?= ($filters['meeting_type'] ?? '') == 'board' ? 'selected' : '' ?>>Yönetim Kurulu</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Durum</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <option value="scheduled" <?= ($filters['status'] ?? '') == 'scheduled' ? 'selected' : '' ?>>Planlandı</option>
                    <option value="completed" <?= ($filters['status'] ?? '') == 'completed' ? 'selected' : '' ?>>Tamamlandı</option>
                    <option value="cancelled" <?= ($filters['status'] ?? '') == 'cancelled' ? 'selected' : '' ?>>İptal</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-search mr-2"></i>Filtrele
                </button>
            </div>
        </form>
    </div>

    <!-- Meetings List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <?php if (empty($meetings ?? [])): ?>
            <div class="p-12 text-center">
                <i class="fas fa-handshake text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400 text-lg">Henüz toplantı kaydı yok</p>
                <a href="<?= base_url('/meetings/new') ?>" class="mt-4 inline-block px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">
                    İlk Toplantıyı Ekle
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarih</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Başlık</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tip</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Katılım</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durum</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach (($meetings ?? []) as $meeting): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= $meeting['meeting_date'] ? date('d.m.Y H:i', strtotime($meeting['meeting_date'])) : '-' ?>
                                </td>
                                <td class="px-6 py-4 font-medium">
                                    <?= htmlspecialchars($meeting['title'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $types = ['regular' => 'Olağan', 'extraordinary' => 'Olağanüstü', 'board' => 'Yönetim Kurulu'];
                                    echo $types[$meeting['meeting_type'] ?? 'regular'] ?? 'Olağan';
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= $meeting['attendance_count'] ?? 0 ?> kişi
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status = $meeting['status'] ?? 'scheduled';
                                    $statusColors = [
                                        'scheduled' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                    ];
                                    $statusTexts = [
                                        'scheduled' => 'Planlandı',
                                        'completed' => 'Tamamlandı',
                                        'cancelled' => 'İptal'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusColors[$status] ?? $statusColors['scheduled'] ?>">
                                        <?= $statusTexts[$status] ?? 'Planlandı' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="<?= base_url('/meetings/' . $meeting['id']) ?>" class="text-primary-600 hover:text-primary-900">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

