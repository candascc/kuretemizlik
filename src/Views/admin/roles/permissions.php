<div class="container mx-auto px-4 py-6">
    <!-- Breadcrumb -->
    <nav class="text-sm mb-4">
        <ol class="list-none p-0 inline-flex">
            <li class="flex items-center">
                <a href="<?= base_url('/') ?>" class="text-blue-600 hover:text-blue-800">Anasayfa</a>
                <i class="fas fa-chevron-right mx-2 text-gray-400 text-xs"></i>
            </li>
            <li class="flex items-center">
                <a href="<?= base_url('/admin/roles') ?>" class="text-blue-600 hover:text-blue-800">Roller</a>
                <i class="fas fa-chevron-right mx-2 text-gray-400 text-xs"></i>
            </li>
            <li class="flex items-center text-gray-500">
                İzinler: <?= htmlspecialchars($role['name'] ?? 'Rol') ?>
            </li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                <i class="fas fa-key mr-2"></i>İzinleri Yönet
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                Rol: <span class="font-semibold"><?= htmlspecialchars($role['name'] ?? '') ?></span>
                (Seviye <?= $role['hierarchy_level'] ?? 0 ?>)
            </p>
        </div>
        <a href="<?= base_url('/admin/roles/' . $role['id']) ?>" 
           class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
            <i class="fas fa-arrow-left mr-2"></i>Role Geri Dön
        </a>
    </div>

    <!-- Flash Messages -->
    <?php if (has_flash('success')): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <div><?= htmlspecialchars(get_flash('success') ?? '') ?></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (has_flash('error')): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <div><?= htmlspecialchars(get_flash('error') ?? '') ?></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Toplam İzinler</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                        <?= count($all_permissions ?? []) ?>
                    </p>
                </div>
                <i class="fas fa-th-list text-blue-500 text-3xl opacity-50"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Atanan</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                        <?= count($assigned_permissions ?? []) ?>
                    </p>
                </div>
                <i class="fas fa-check-circle text-green-500 text-3xl opacity-50"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Kategoriler</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                        <?= count($permissions_by_category ?? []) ?>
                    </p>
                </div>
                <i class="fas fa-folder text-yellow-500 text-3xl opacity-50"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Kapsama</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                        <?= count($all_permissions ?? []) > 0 ? round((count($assigned_permissions ?? []) / count($all_permissions ?? [])) * 100) : 0 ?>%
                    </p>
                </div>
                <i class="fas fa-chart-pie text-purple-500 text-3xl opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Permission Management Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <form method="POST" action="<?= base_url('/admin/roles/' . $role['id'] . '/permissions/update') ?>" id="permissionsForm">
            <!-- Quick Actions -->
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <button type="button" 
                            onclick="selectAll()" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                        <i class="fas fa-check-double mr-2"></i>Tümünü Seç
                    </button>
                    <button type="button" 
                            onclick="deselectAll()" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                        <i class="fas fa-times mr-2"></i>Tümünü Kaldır
                    </button>
                </div>
                <div class="flex items-center space-x-3">
                    <input type="text" 
                           id="searchPermissions" 
                           placeholder="İzin ara..." 
                           class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-sm"
                           onkeyup="filterPermissions()">
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Değişiklikleri Kaydet
                    </button>
                </div>
            </div>

            <!-- Permissions by Category -->
            <?php if (isset($permissions_by_category) && !empty($permissions_by_category)): ?>
                <?php foreach ($permissions_by_category as $category => $permissions): ?>
                    <div class="mb-6 permission-category">
                        <!-- Category Header -->
                        <div class="flex items-center justify-between mb-3 bg-gray-100 dark:bg-gray-700 px-4 py-3 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 flex items-center">
                                <i class="fas fa-folder-open mr-2 text-blue-500"></i>
                                <?= ucfirst(str_replace('_', ' ', e($category))) ?>
                                <span class="ml-3 text-sm font-normal text-gray-500 dark:text-gray-400">
                                    (<?= count($permissions) ?> izin)
                                </span>
                            </h3>
                            <button type="button" 
                                    onclick="toggleCategory('<?= e($category) ?>')" 
                                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                <i class="fas fa-chevron-down mr-1"></i>Aç/Kapat
                            </button>
                        </div>

                        <!-- Permissions Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-4 category-<?= e($category) ?>">
                            <?php foreach ($permissions as $permission): ?>
                                <div class="permission-item border border-gray-200 dark:border-gray-600 rounded-lg p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <label class="flex items-start cursor-pointer">
                                        <input type="checkbox" 
                                               name="permissions[]" 
                                               value="<?= htmlspecialchars($permission['id'] ?? '') ?>" 
                                               <?= isset($permission['id']) && in_array($permission['id'], $assigned_permissions ?? []) ? 'checked' : '' ?>
                                               class="mt-1 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 permission-checkbox">
                                        <div class="ml-3">
                                            <div class="font-medium text-gray-800 dark:text-gray-200 text-sm permission-name">
                                                <?= htmlspecialchars($permission['name'] ?? '') ?>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 permission-description">
                                                <?= htmlspecialchars($permission['description'] ?? '') ?>
                                            </div>
                                            <?php if (!empty($permission['is_system_permission'])): ?>
                                                <span class="inline-block mt-1 px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded">
                                                    <i class="fas fa-lock mr-1"></i>Sistem
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-key text-6xl mb-4 opacity-50"></i>
                    <p class="text-lg">Mevcut izin yok</p>
                </div>
            <?php endif; ?>

            <!-- Submit Button (bottom) -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="<?= base_url('/admin/roles/' . $role['id']) ?>" 
                   class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <i class="fas fa-times mr-2"></i>İptal
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-2"></i>Değişiklikleri Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function selectAll() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function deselectAll() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function toggleCategory(category) {
    const categoryElement = document.querySelector(`.category-${category}`);
    if (categoryElement) {
        categoryElement.style.display = categoryElement.style.display === 'none' ? 'grid' : 'none';
    }
}

function filterPermissions() {
    const searchTerm = document.getElementById('searchPermissions').value.toLowerCase();
    const permissionItems = document.querySelectorAll('.permission-item');
    
    permissionItems.forEach(item => {
        const name = item.querySelector('.permission-name').textContent.toLowerCase();
        const description = item.querySelector('.permission-description').textContent.toLowerCase();
        
        if (name.includes(searchTerm) || description.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Auto-save confirmation
document.getElementById('permissionsForm').addEventListener('submit', function(e) {
    const checkedCount = document.querySelectorAll('.permission-checkbox:checked').length;
    if (!confirm(`Bu role ${checkedCount} izin atamak istediğinize emin misiniz?`)) {
        e.preventDefault();
    }
});
</script>

