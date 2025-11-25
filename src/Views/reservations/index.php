<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-calendar-check mr-3 text-primary-600"></i>
                Rezervasyonlar
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Ortak kullanım alanı rezervasyonları</p>
        </div>
        <a href="<?= base_url('/reservations/new') ?>" 
           class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
            <i class="fas fa-plus mr-2"></i>
            Yeni Rezervasyon
        </a>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bina</label>
                <select name="building_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tüm Binalar</option>
                    <?php foreach ($buildings as $bld): ?>
                        <option value="<?= $bld['id'] ?>" <?= ($filters['building_id'] ?? '') == $bld['id'] ? 'selected' : '' ?>>
                            <?= e($bld['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Durum</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <option value="pending" <?= ($filters['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Bekliyor</option>
                    <option value="approved" <?= ($filters['status'] ?? '') == 'approved' ? 'selected' : '' ?>>Onaylandı</option>
                    <option value="rejected" <?= ($filters['status'] ?? '') == 'rejected' ? 'selected' : '' ?>>Reddedildi</option>
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

    <!-- Reservations List -->
    <?php if (empty($reservations)): ?>
        <div class="bg-white dark:bg-gray-800 p-12 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 text-center">
            <i class="fas fa-calendar-times text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Henüz rezervasyon yok</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">İlk rezervasyonu oluşturarak başlayın</p>
            <a href="<?= base_url('/reservations/new') ?>" class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg">
                <i class="fas fa-plus mr-2"></i>İlk Rezervasyonu Ekle
            </a>
        </div>
    <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Rezervasyon
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Alan
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Tarih
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Tutar
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Durum
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                İşlemler
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($reservations as $reservation): 
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                            ];
                            $statusTexts = [
                                'pending' => 'Bekliyor',
                                'approved' => 'Onaylandı',
                                'rejected' => 'Reddedildi',
                                'completed' => 'Tamamlandı',
                                'cancelled' => 'İptal'
                            ];
                            $status = $reservation['status'] ?? 'pending';
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($reservation['resident_name'] ?? '-') ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <?= $reservation['unit_number'] ? ('Daire ' . e($reservation['unit_number'])) : '-' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($reservation['facility_name'] ?? '-') ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <?= htmlspecialchars($reservation['building_name'] ?? '-') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?= $reservation['start_date'] ? date('d.m.Y', strtotime($reservation['start_date'])) : '-' ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <?= $reservation['end_date'] ? date('d.m.Y', strtotime($reservation['end_date'])) : '-' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?= number_format($reservation['total_amount'] ?? 0, 2) ?> ₺
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $statusColors[$status] ?? $statusColors['pending'] ?>">
                                        <?= $statusTexts[$status] ?? 'Bilinmiyor' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="<?= base_url('/reservations/' . $reservation['id']) ?>" 
                                       class="text-primary-600 hover:text-primary-900 mr-3">
                                        <i class="fas fa-eye mr-1"></i>Detay
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (!empty($pagination)): ?>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <?php include __DIR__ . '/../partials/pagination.php'; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

