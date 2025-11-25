<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-building mr-3 text-primary-600"></i>
                Bina Yönetimi
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Apartman, site ve plaza yönetimi</p>
        </div>
        <a href="<?= base_url('/buildings/new') ?>" 
           class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
            <i class="fas fa-plus mr-2"></i>
            Yeni Bina Ekle
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ara</label>
                <input type="text" name="search" value="<?= e($search ?? '') ?>" 
                       placeholder="Bina adı..." 
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bina Tipi</label>
                <select name="building_type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <option value="apartman" <?= ($buildingType ?? '') === 'apartman' ? 'selected' : '' ?>>Apartman</option>
                    <option value="site" <?= ($buildingType ?? '') === 'site' ? 'selected' : '' ?>>Site</option>
                    <option value="plaza" <?= ($buildingType ?? '') === 'plaza' ? 'selected' : '' ?>>Plaza</option>
                    <option value="rezidans" <?= ($buildingType ?? '') === 'rezidans' ? 'selected' : '' ?>>Rezidans</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Durum</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="active" <?= ($status ?? 'active') === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= ($status ?? '') === 'inactive' ? 'selected' : '' ?>>Pasif</option>
                    <option value="" <?= empty($status) ? 'selected' : '' ?>>Tümü</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-search mr-2"></i>Filtrele
                </button>
            </div>
        </div>
    </form>

    <!-- Buildings List -->
    <?php if (empty($buildings)): ?>
        <div class="bg-white dark:bg-gray-800 p-12 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 text-center">
            <i class="fas fa-building text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Henüz bina eklenmemiş</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Sisteme ilk binanızı ekleyerek başlayın</p>
            <a href="<?= base_url('/buildings/new') ?>" class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg">
                <i class="fas fa-plus mr-2"></i>İlk Bina Ekle
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($buildings as $building): ?>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 hover:shadow-strong transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                                <?= e($building['name']) ?>
                            </h3>
                            <span class="inline-block px-3 py-1 text-xs font-medium rounded-full 
                                <?= $building['building_type'] === 'apartman' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' ?>
                                <?= $building['building_type'] === 'site' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' ?>
                                <?= $building['building_type'] === 'plaza' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' ?>
                                <?= $building['building_type'] === 'rezidans' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : '' ?>">
                                <?= ucfirst($building['building_type']) ?>
                            </span>
                        </div>
                        <span class="px-3 py-1 text-xs font-medium rounded-full 
                            <?= $building['status'] === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' ?>">
                            <?= $building['status'] === 'active' ? 'Aktif' : 'Pasif' ?>
                        </span>
                    </div>

                    <div class="space-y-2 mb-4 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt mr-2 w-4"></i>
                            <?= htmlspecialchars($building['city'] ?? '') ?>, <?= htmlspecialchars($building['district'] ?? '') ?>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-home mr-2 w-4"></i>
                            <?= $building['unit_count'] ?? 0 ?> Daire
                        </div>
                        <?php if ($building['manager_name']): ?>
                            <div class="flex items-center">
                                <i class="fas fa-user-tie mr-2 w-4"></i>
                                <?= e($building['manager_name']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center space-x-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a href="<?= base_url("/buildings/{$building['id']}") ?>" 
                           class="flex-1 text-center px-4 py-2 bg-primary-50 hover:bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-200 rounded-lg transition-colors text-sm font-medium">
                            Detay
                        </a>
                        <a href="<?= base_url("/buildings/{$building['id']}/edit") ?>" 
                           class="flex-1 text-center px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-lg transition-colors text-sm font-medium">
                            Düzenle
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
            <?php include __DIR__ . '/../partials/pagination.php'; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

