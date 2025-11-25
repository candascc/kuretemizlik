<?php
$scopeLabels = $scope_labels ?? [
    'staff' => 'Personel',
    'resident_portal' => 'Sakin Portalı',
    'customer_portal' => 'Müşteri Portalı',
];
?>

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
                <?= htmlspecialchars($role['name'] ?? 'Rol') ?>
            </li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 flex items-center">
                <i class="fas fa-user-shield mr-2"></i>
                <?= htmlspecialchars($role['name'] ?? '') ?>
                <?php if ($role['is_system_role']): ?>
                    <span class="ml-3 px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full text-sm">
                        <i class="fas fa-lock mr-1"></i>Sistem Rolü
                    </span>
                <?php endif; ?>
                <span class="ml-3 px-3 py-1 bg-teal-100 dark:bg-teal-900 text-teal-800 dark:text-teal-200 rounded-full text-sm">
                    <i class="fas fa-globe mr-1"></i><?= htmlspecialchars($scopeLabels[$role['scope'] ?? 'staff'] ?? ucfirst($role['scope'] ?? 'staff')) ?>
                </span>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                <?= htmlspecialchars($role['description'] ?? '') ?>
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="<?= base_url('/admin/roles/' . $role['id'] . '/edit') ?>" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-edit mr-2"></i>Düzenle
            </a>
            <?php if (!$role['is_system_role']): ?>
                <form method="POST" action="<?= base_url('/admin/roles/' . $role['id'] . '/delete') ?>" 
                      onsubmit="return confirm('Bu rolü silmek istediğinize emin misiniz? Bu işlem geri alınamaz.');">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-trash mr-2"></i>Sil
                    </button>
                </form>
            <?php endif; ?>
        </div>
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

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Hiyerarşi Seviyesi</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                        <?= $role['hierarchy_level'] ?? 0 ?>
                    </p>
                </div>
                <i class="fas fa-layer-group text-purple-500 text-4xl opacity-50"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Atanan Kullanıcılar</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                        <?= $role['user_count'] ?? 0 ?>
                    </p>
                </div>
                <i class="fas fa-users text-blue-500 text-4xl opacity-50"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">İzinler</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                        <?= $role['permission_count'] ?? 0 ?>
                    </p>
                </div>
                <i class="fas fa-key text-green-500 text-4xl opacity-50"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Ana Rol</p>
                    <p class="text-lg font-bold text-gray-800 dark:text-gray-100">
                        <?= !empty($role['parent_role']) ? e($role['parent_role']) : 'Yok' ?>
                    </p>
                </div>
                <i class="fas fa-sitemap text-yellow-500 text-4xl opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Role Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Role Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
                <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                Rol Bilgileri
            </h2>
            <dl class="space-y-3">
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 pb-2">
                    <dt class="font-medium text-gray-600 dark:text-gray-400">ID:</dt>
                    <dd class="text-gray-800 dark:text-gray-200"><?= e($role['id']) ?></dd>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 pb-2">
                    <dt class="font-medium text-gray-600 dark:text-gray-400">İsim:</dt>
                    <dd class="text-gray-800 dark:text-gray-200 font-semibold"><?= e($role['name']) ?></dd>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 pb-2">
                    <dt class="font-medium text-gray-600 dark:text-gray-400">Oluşturuldu:</dt>
                    <dd class="text-gray-800 dark:text-gray-200"><?= date('d M Y H:i', strtotime($role['created_at'])) ?></dd>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 pb-2">
                    <dt class="font-medium text-gray-600 dark:text-gray-400">Güncellendi:</dt>
                    <dd class="text-gray-800 dark:text-gray-200"><?= date('d M Y H:i', strtotime($role['updated_at'])) ?></dd>
                </div>
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-700 pb-2">
                    <dt class="font-medium text-gray-600 dark:text-gray-400">Kapsam:</dt>
                    <dd class="text-gray-800 dark:text-gray-200">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200">
                            <?= htmlspecialchars($scopeLabels[$role['scope'] ?? 'staff'] ?? ucfirst($role['scope'] ?? 'staff')) ?>
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600 dark:text-gray-400">Tip:</dt>
                    <dd class="text-gray-800 dark:text-gray-200">
                        <?= $role['is_system_role'] ? 
                            '<span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded text-xs"><i class="fas fa-lock mr-1"></i>Sistem</span>' : 
                            '<span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded text-xs"><i class="fas fa-user mr-1"></i>Özel</span>' ?>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
                <i class="fas fa-bolt mr-2 text-yellow-500"></i>
                Hızlı İşlemler
            </h2>
            <div class="space-y-3">
                <a href="<?= base_url('/admin/roles/' . $role['id'] . '/permissions') ?>" 
                   class="block p-4 bg-purple-50 dark:bg-purple-900 hover:bg-purple-100 dark:hover:bg-purple-800 rounded-lg transition border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-purple-800 dark:text-purple-200">İzinleri Yönet</div>
                            <div class="text-sm text-purple-600 dark:text-purple-300">İzinleri ata veya kaldır</div>
                        </div>
                        <i class="fas fa-key text-purple-500 text-2xl"></i>
                    </div>
                </a>
                <a href="<?= base_url('/admin/roles/' . $role['id'] . '/users') ?>" 
                   class="block p-4 bg-blue-50 dark:bg-blue-900 hover:bg-blue-100 dark:hover:bg-blue-800 rounded-lg transition border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-blue-800 dark:text-blue-200">Kullanıcıları Görüntüle</div>
                            <div class="text-sm text-blue-600 dark:text-blue-300">Bu role sahip tüm kullanıcıları gör</div>
                        </div>
                        <i class="fas fa-users text-blue-500 text-2xl"></i>
                    </div>
                </a>
                <a href="<?= base_url('/admin/roles/' . $role['id'] . '/edit') ?>" 
                   class="block p-4 bg-green-50 dark:bg-green-900 hover:bg-green-100 dark:hover:bg-green-800 rounded-lg transition border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-green-800 dark:text-green-200">Rolü Düzenle</div>
                            <div class="text-sm text-green-600 dark:text-green-300">Rol ayrıntılarını güncelle</div>
                        </div>
                        <i class="fas fa-edit text-green-500 text-2xl"></i>
                    </div>
                </a>
                <a href="<?= base_url('/admin/roles/hierarchy') ?>" 
                   class="block p-4 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition border-l-4 border-gray-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-gray-200">Hiyerarşiyi Görüntüle</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">Rol hiyerarşi ağacını gör</div>
                        </div>
                        <i class="fas fa-sitemap text-gray-500 text-2xl"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Permissions List -->
    <?php if (isset($permissions) && !empty($permissions)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 flex items-center">
                    <i class="fas fa-shield-alt mr-2 text-green-500"></i>
                    Atanan İzinler (<?= count($permissions) ?>)
                </h2>
                <a href="<?= base_url('/admin/roles/' . $role['id'] . '/permissions') ?>" 
                   class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm">
                    <i class="fas fa-edit mr-2"></i>İzinleri Düzenle
                </a>
            </div>

            <?php 
            // Group permissions by category
            $grouped = [];
            foreach ($permissions as $perm) {
                $grouped[$perm['category']][] = $perm;
            }
            ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($grouped as $category => $perms): ?>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300 mb-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                            <?= ucfirst(str_replace('_', ' ', e($category))) ?>
                        </h3>
                        <ul class="space-y-1 text-sm">
                            <?php foreach ($perms as $perm): ?>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                    <span class="text-gray-600 dark:text-gray-400">
                                        <?= e($perm['name']) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Users with this Role -->
    <?php if (isset($users) && !empty($users)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
                <i class="fas fa-user-friends mr-2 text-blue-500"></i>
                Bu Role Sahip Kullanıcılar (<?= count($users) ?>)
            </h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kullanıcı Adı</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Son Giriş</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach (array_slice($users, 0, 10) as $user): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        <?= e($user['username']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= $user['is_active'] ? 
                                        '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Aktif</span>' : 
                                        '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Pasif</span>' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?= isset($user['last_login']) ? date('d M Y', strtotime($user['last_login'])) : 'Hiç' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="<?= base_url('/admin/users/' . $user['id']) ?>" 
                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        Görüntüle
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (count($users) > 10): ?>
                <div class="mt-4 text-center">
                    <a href="<?= base_url('/admin/roles/' . $role['id'] . '/users') ?>" 
                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                        Tüm <?= count($users) ?> kullanıcıyı görüntüle →
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

