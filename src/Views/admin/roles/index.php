<?php
/**
 * Role Management Index View
 */

$scopeLabels = $scope_labels ?? [
    'staff' => 'Personel',
    'resident_portal' => 'Sakin Portalı',
    'customer_portal' => 'Müşteri Portalı',
];
?>

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-users-cog mr-3"></i>
                Rol Yönetimi
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Kullanıcı rolleri ve izinlerini yönetin</p>
        </div>
        <div class="flex space-x-3">
            <a href="<?= base_url('/admin/roles/create') ?>" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                <i class="fas fa-plus mr-2"></i>
                Rol Oluştur
            </a>
            <a href="<?= base_url('/admin/roles/export') ?>" 
               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                <i class="fas fa-download mr-2"></i>
                Dışa Aktar
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <i class="fas fa-users text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Roller</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $statistics['total_roles'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                <i class="fas fa-layer-group text-green-600 dark:text-green-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Hiyerarşi Seviyeleri</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $statistics['hierarchy_levels'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                <i class="fas fa-sitemap text-yellow-600 dark:text-yellow-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ana Roller</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $statistics['parent_roles'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                <i class="fas fa-crown text-purple-600 dark:text-purple-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Maks. Hiyerarşi</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $statistics['max_hierarchy_level'] ?? 0 ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Roles Table -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Tüm Roller</h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Açıklama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hiyerarşi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kullanıcılar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kapsam</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tip</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($roles as $role): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-8 w-8">
                                <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                    <i class="fas fa-user-tag text-blue-600 dark:text-blue-400 text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?= e($role['name']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 dark:text-white"><?= e($role['description']) ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            Seviye <?= $role['hierarchy_level'] ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        <?= $role['user_count'] ?? 0 ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php $scopeKey = $role['scope'] ?? 'staff'; ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200">
                            <?= htmlspecialchars($scopeLabels[$scopeKey] ?? ucfirst(str_replace('_', ' ', $scopeKey))) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($role['is_system_role']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                Sistem
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                Özel
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="<?= base_url('/admin/roles/' . $role['id']) ?>" 
                               class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?= base_url('/admin/roles/' . $role['id'] . '/edit') ?>" 
                               class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?= base_url('/admin/roles/' . $role['id'] . '/permissions') ?>" 
                               class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                <i class="fas fa-key"></i>
                            </a>
                            <?php if (!$role['is_system_role']): ?>
                            <form method="POST" action="<?= base_url('/admin/roles/' . $role['id'] . '/delete') ?>" 
                                  class="inline" 
                                  onsubmit="return confirm('Bu rolü silmek istediğinizden emin misiniz?')">
                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Actions -->
<div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
    <a href="<?= base_url('/admin/roles/hierarchy') ?>" 
       class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-shadow duration-200">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <i class="fas fa-sitemap text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Rol Hiyerarşisi</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Rol hiyerarşi ağacını görüntüle</p>
            </div>
        </div>
    </a>
    
    <a href="<?= base_url('/admin/roles/permissions') ?>" 
       class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-shadow duration-200">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                <i class="fas fa-key text-green-600 dark:text-green-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">İzinler</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Sistem izinlerini yönet</p>
            </div>
        </div>
    </a>
    
    <a href="<?= base_url('/admin/roles/export') ?>" 
       class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-shadow duration-200">
        <div class="flex items-center">
            <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                <i class="fas fa-download text-purple-600 dark:text-purple-400 text-xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Veri Dışa Aktar</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Roller ve izinleri dışa aktar</p>
            </div>
        </div>
    </a>
</div>
