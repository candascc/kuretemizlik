<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-receipt mr-3 text-primary-600"></i>
                Bina Giderleri
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Binalara ait gider yönetimi ve onay süreçleri</p>
        </div>
        <a href="<?= base_url('/expenses/new') ?>" 
           class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
            <i class="fas fa-plus mr-2"></i>
            Yeni Gider Ekle
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kategori</label>
                <select name="category" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <option value="temizlik" <?= ($filters['category'] ?? '') == 'temizlik' ? 'selected' : '' ?>>Temizlik</option>
                    <option value="bakım" <?= ($filters['category'] ?? '') == 'bakım' ? 'selected' : '' ?>>Bakım</option>
                    <option value="güvenlik" <?= ($filters['category'] ?? '') == 'güvenlik' ? 'selected' : '' ?>>Güvenlik</option>
                    <option value="elektrik" <?= ($filters['category'] ?? '') == 'elektrik' ? 'selected' : '' ?>>Elektrik</option>
                    <option value="su" <?= ($filters['category'] ?? '') == 'su' ? 'selected' : '' ?>>Su</option>
                    <option value="diğer" <?= ($filters['category'] ?? '') == 'diğer' ? 'selected' : '' ?>>Diğer</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Durum</label>
                <select name="approval_status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <option value="pending" <?= ($filters['approval_status'] ?? '') == 'pending' ? 'selected' : '' ?>>Bekliyor</option>
                    <option value="approved" <?= ($filters['approval_status'] ?? '') == 'approved' ? 'selected' : '' ?>>Onaylandı</option>
                    <option value="rejected" <?= ($filters['approval_status'] ?? '') == 'rejected' ? 'selected' : '' ?>>Reddedildi</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-search mr-2"></i>Filtrele
                </button>
            </div>
        </form>
    </div>

    <!-- Expenses List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <?php if (empty($expenses ?? [])): ?>
            <div class="p-12 text-center">
                <i class="fas fa-receipt text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400 text-lg">Henüz gider kaydı yok</p>
                <a href="<?= base_url('/expenses/new') ?>" class="mt-4 inline-block px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">
                    İlk Gideri Ekle
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarih</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kategori</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Açıklama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tutar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durum</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach (($expenses ?? []) as $expense): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= $expense['expense_date'] ? date('d.m.Y', strtotime($expense['expense_date'])) : '-' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= htmlspecialchars($expense['category'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?= htmlspecialchars($expense['description'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold">
                                    <?= number_format($expense['amount'] ?? 0, 2) ?> ₺
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status = $expense['approval_status'] ?? 'pending';
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                    ];
                                    $statusTexts = [
                                        'pending' => 'Bekliyor',
                                        'approved' => 'Onaylandı',
                                        'rejected' => 'Reddedildi'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusColors[$status] ?? $statusColors['pending'] ?>">
                                        <?= $statusTexts[$status] ?? 'Bekliyor' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="<?= base_url('/expenses/' . $expense['id']) ?>" class="text-primary-600 hover:text-primary-900 mr-3">
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

