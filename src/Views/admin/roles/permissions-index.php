<?php
/**
 * Permission Management Index View
 * Shows all permissions grouped by category
 */
?>

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-key mr-3"></i>
                İzin Yönetimi
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Sistem izinlerini yönetin ve yeni izinler oluşturun</p>
        </div>
        <div class="flex space-x-3">
            <a href="<?= base_url('/admin/roles') ?>" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>
                Rollere Dön
            </a>
            <a href="<?= base_url('/admin/roles/hierarchy') ?>" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                <i class="fas fa-sitemap mr-2"></i>
                Hiyerarşi
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <i class="fas fa-key text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam İzinler</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $statistics['total_permissions'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                <i class="fas fa-folder text-green-600 dark:text-green-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Kategoriler</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $statistics['categories'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                <i class="fas fa-users-cog text-yellow-600 dark:text-yellow-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Roller İzinli</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $statistics['roles_with_permissions'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                <i class="fas fa-user-shield text-purple-600 dark:text-purple-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Doğrudan İzinli</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $statistics['users_with_direct_permissions'] ?? 0 ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Permissions by Category -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Kategoriye Göre İzinler</h3>
    </div>
    
    <div class="p-6">
        <?php if (empty($permissions_by_category)): ?>
            <div class="text-center py-12">
                <i class="fas fa-key text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">Henüz izin tanımlanmamış.</p>
            </div>
        <?php else: ?>
            <?php foreach ($permissions_by_category as $category => $perms): ?>
                <div class="mb-8 last:mb-0">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-folder-open mr-2 text-blue-500"></i>
                        <?= e($category) ?>
                        <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                            (<?= count($perms) ?> izin)
                        </span>
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($perms as $permission): ?>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h5 class="font-medium text-gray-900 dark:text-white mb-1">
                                            <?= e($permission['name']) ?>
                                        </h5>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <?= htmlspecialchars($permission['description'] ?? 'Açıklama yok') ?>
                                        </p>
                                    </div>
                                    <div class="ml-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Aktif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Create Permission Form -->
<div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Yeni İzin Oluştur</h3>
    </div>
    
    <div class="p-6">
        <form method="POST" action="<?= base_url('/admin/roles/permissions') ?>">
            <?= CSRF::field() ?>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        İzin Adı <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           required
                           placeholder="örn: users.create"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500">Küçük harf, nokta ve alt çizgi kullanın</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Kategori <span class="text-red-500">*</span>
                    </label>
                    <select name="category" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Kategori Seçin</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                        <?php endforeach; ?>
                        <option value="CUSTOM">Yeni Kategori (CUSTOM)</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Açıklama <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="description" 
                           required
                           placeholder="Bu izin ne yapar?"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="submit" 
                        class="inline-flex items-center px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>
                    İzin Oluştur
                </button>
            </div>
        </form>
    </div>
</div>

