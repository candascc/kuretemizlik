<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-home mr-3 text-primary-600"></i>
                Daire Yönetimi
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Binalara ait daire ve birim yönetimi</p>
        </div>
        <a href="<?= base_url('/units/new') ?>" 
           class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
            <i class="fas fa-plus mr-2"></i>
            Yeni Daire Ekle
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if (!$buildingId && $buildings): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bina Seç</label>
                    <select name="building_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Tüm Binalar</option>
                        <?php foreach ($buildings as $bld): ?>
                            <option value="<?= $bld['id'] ?>" <?= $buildingId == $bld['id'] ? 'selected' : '' ?>>
                                <?= e($bld['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ara</label>
                <input type="text" name="search" value="<?= e($search ?? '') ?>" 
                       placeholder="Daire no, sahip adı..." 
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-search mr-2"></i>Filtrele
                </button>
            </div>
        </form>
    </div>

    <!-- Units List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <?php if (empty($units)): ?>
            <div class="p-12 text-center">
                <i class="fas fa-home text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400 text-lg">Henüz daire kaydı yok</p>
                <a href="<?= base_url('/units/new') ?>" class="mt-4 inline-block px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">
                    İlk Daireyi Ekle
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bina</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Daire No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sahip</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Alan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aidat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durum</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($units as $unit): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= htmlspecialchars($unit['building_name'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium">
                                    <?= e($unit['unit_number']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= htmlspecialchars($unit['owner_name'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= htmlspecialchars($unit['net_area'] ?? '-') ?> m²
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= number_format($unit['monthly_fee'] ?? 0, 2) ?> ₺
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'inactive' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                        'empty' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                                    ];
                                    $statusTexts = [
                                        'active' => 'Aktif',
                                        'inactive' => 'Pasif',
                                        'empty' => 'Boş'
                                    ];
                                    $status = $unit['status'] ?? 'active';
                                    ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusColors[$status] ?? $statusColors['active'] ?>">
                                        <?= $statusTexts[$status] ?? 'Aktif' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="<?= base_url('/units/' . $unit['id']) ?>" class="text-primary-600 hover:text-primary-900 mr-3">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('/units/' . $unit['id'] . '/edit') ?>" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
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

