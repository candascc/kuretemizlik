<?php
$scopeOptions = $scope_labels ?? [
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
                Düzenle: <?= htmlspecialchars($role['name'] ?? 'Rol') ?>
            </li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">
            <i class="fas fa-edit mr-2"></i>Rolü Düzenle: <?= htmlspecialchars($role['name'] ?? '') ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            Rol detaylarını, hiyerarşiyi ve sistem ayarlarını güncelleyin
        </p>
    </div>

    <!-- Flash Messages -->
    <?php if (has_flash('error')): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <div>
                    <?php if (is_array(get_flash('error'))): ?>
                        <ul class="list-disc list-inside">
                            <?php foreach (get_flash('error') as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <?= htmlspecialchars(get_flash('error') ?? '') ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($role['is_system_role']): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <div>
                    <strong>Sistem Rolü:</strong> Bu bir sistem rolüdür. Düzenleme yetenekleri sınırlıdır.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Edit Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <form method="POST" action="<?= base_url('/admin/roles/' . $role['id'] . '/update') ?>" class="space-y-8">
            <!-- Role Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Rol Adı <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       required 
                       <?= $role['is_system_role'] ? 'readonly' : '' ?>
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white <?= $role['is_system_role'] ? 'bg-gray-100 dark:bg-gray-600 cursor-not-allowed' : '' ?>"
                       value="<?= e($role['name']) ?>">
                <?php if ($role['is_system_role']): ?>
                    <p class="mt-1 text-sm text-yellow-600 dark:text-yellow-400">
                        <i class="fas fa-lock mr-1"></i>Sistem rolü adları değiştirilemez
                    </p>
                <?php else: ?>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Büyük harf ve alt çizgi kullanın (örn: TAKIM_LIDERI)
                    </p>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Açıklama <span class="text-red-500">*</span>
                </label>
                <textarea id="description" 
                          name="description" 
                          required 
                          rows="3" 
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"><?= e($role['description']) ?></textarea>
            </div>

            <!-- Hierarchy Level -->
            <div>
                <label for="hierarchy_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Hiyerarşi Seviyesi <span class="text-red-500">*</span>
                </label>
                <input type="number" 
                       id="hierarchy_level" 
                       name="hierarchy_level" 
                       required 
                       min="0" 
                       max="100" 
                       value="<?= e($role['hierarchy_level']) ?>"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    0-100 (Daha yüksek sayı = Daha yüksek yetki)
                </p>
            </div>

            <!-- Scope -->
            <?php $isSystem = !empty($role['is_system_role']); ?>
            <div>
                <label for="scope" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Kapsam
                </label>
                <select id="scope"
                        name="scope"
                        <?= $isSystem ? 'disabled' : '' ?>
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white <?= $isSystem ? 'bg-gray-100 dark:bg-gray-600 cursor-not-allowed' : '' ?>">
                    <?php $selectedScope = $_POST['scope'] ?? ($role['scope'] ?? 'staff'); ?>
                    <?php foreach ($scopeOptions as $value => $label): ?>
                        <option value="<?= e($value) ?>"
                                <?= ($selectedScope === $value) ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isSystem): ?>
                    <input type="hidden" name="scope" value="<?= htmlspecialchars($role['scope'] ?? 'staff') ?>">
                    <p class="mt-1 text-sm text-yellow-600 dark:text-yellow-400">
                        Sistem rolleri için kapsam değiştirilemez.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Parent Role -->
            <div>
                <label for="parent_role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Ana Rol (İsteğe Bağlı)
                </label>
                <select id="parent_role" 
                        name="parent_role" 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                    <option value="">-- Yok --</option>
                    <?php if (isset($roles)): ?>
                        <?php foreach ($roles as $r): ?>
                            <?php if ($r['id'] !== $role['id']): // Can't be parent of itself ?>
                                <option value="<?= e($r['name']) ?>" 
                                        <?= ($role['parent_role'] === $r['name']) ? 'selected' : '' ?>>
                                    <?= e($r['name']) ?> (Seviye <?= $r['hierarchy_level'] ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- System Role -->
            <?php if ($isSystem): ?>
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="is_system_role" 
                           checked 
                           disabled
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded opacity-50 cursor-not-allowed">
                    <label for="is_system_role" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        Sistem Rolü (Değiştirilemez)
                    </label>
                </div>
            <?php else: ?>
                <div class="flex items-center">
                    <input type="checkbox"
                           id="is_system_role"
                           name="is_system_role"
                           value="1"
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                           <?= isset($_POST['is_system_role']) ? 'checked' : '' ?>>
                    <label for="is_system_role" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        Sistem rolü olarak işaretle
                    </label>
                </div>
            <?php endif; ?>

            <!-- Stats Section -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Rol İstatistikleri</h3>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            <?= $role['user_count'] ?? 0 ?>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Kullanıcılar</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            <?= $role['permission_count'] ?? 0 ?>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">İzinler</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            Seviye <?= $role['hierarchy_level'] ?>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Hiyerarşi</div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="<?= base_url('/admin/roles') ?>" 
                   class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <i class="fas fa-times mr-2"></i>İptal
                </a>
                <div class="space-x-3">
                    <a href="<?= base_url('/admin/roles/' . $role['id'] . '/permissions') ?>" 
                       class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition inline-block">
                        <i class="fas fa-key mr-2"></i>İzinleri Yönet
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Rolü Güncelle
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Quick Actions -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="<?= base_url('/admin/roles/' . $role['id']) ?>" 
           class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition border-l-4 border-blue-500">
            <div class="flex items-center">
                <i class="fas fa-eye text-blue-500 text-2xl mr-3"></i>
                <div>
                    <div class="font-semibold text-gray-800 dark:text-gray-200">Detayları Görüntüle</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Tam rol bilgilerini gör</div>
                </div>
            </div>
        </a>
        <a href="<?= base_url('/admin/roles/' . $role['id'] . '/permissions') ?>" 
           class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition border-l-4 border-purple-500">
            <div class="flex items-center">
                <i class="fas fa-key text-purple-500 text-2xl mr-3"></i>
                <div>
                    <div class="font-semibold text-gray-800 dark:text-gray-200">İzinler</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Rol izinlerini yönet</div>
                </div>
            </div>
        </a>
        <a href="<?= base_url('/admin/roles/' . $role['id'] . '/users') ?>" 
           class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition border-l-4 border-green-500">
            <div class="flex items-center">
                <i class="fas fa-users text-green-500 text-2xl mr-3"></i>
                <div>
                    <div class="font-semibold text-gray-800 dark:text-gray-200">Kullanıcılar</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Atanan kullanıcıları görüntüle</div>
                </div>
            </div>
        </a>
    </div>

</div>

