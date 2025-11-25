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
                Hiyerarşi
            </li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                <i class="fas fa-sitemap mr-2"></i>Rol Hiyerarşisi
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                Rol ilişkilerini ve yetki seviyelerini görselleştirin
            </p>
        </div>
        <a href="<?= base_url('/admin/roles') ?>" 
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-arrow-left mr-2"></i>Rollere Dön
        </a>
    </div>

    <!-- Hierarchy Visualization -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-6">
            <i class="fas fa-chart-line mr-2"></i>Yetki Seviyeleri
        </h2>

        <?php if (isset($roles) && !empty($roles)): ?>
            <!-- Sort roles by hierarchy level (descending) -->
            <?php 
            usort($roles, function($a, $b) {
                return $b['hierarchy_level'] - $a['hierarchy_level'];
            });
            
            $maxLevel = $roles[0]['hierarchy_level'] ?? 100;
            ?>

            <div class="space-y-4">
                <?php foreach ($roles as $index => $role): ?>
                    <?php 
                    $widthPercent = ($role['hierarchy_level'] / $maxLevel) * 100;
                    $colors = [
                        'bg-red-500', 'bg-orange-500', 'bg-yellow-500', 
                        'bg-green-500', 'bg-blue-500', 'bg-purple-500'
                    ];
                    $color = $colors[$index % count($colors)];
                    ?>
                    
                    <div class="flex items-center">
                        <!-- Level Indicator -->
                        <div class="w-16 text-right mr-4">
                            <span class="text-2xl font-bold text-gray-700 dark:text-gray-300">
                                <?= $role['hierarchy_level'] ?>
                            </span>
                        </div>

                        <!-- Role Bar -->
                        <div class="flex-1">
                            <div class="relative">
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden h-16">
                                    <div class="<?= $color ?> h-full flex items-center px-4 text-white font-semibold transition-all duration-500" 
                                         style="width: <?= $widthPercent ?>%">
                                        <div class="flex items-center justify-between w-full">
                                            <div>
                                                <div class="flex items-center">
                                                    <?= e($role['name']) ?>
                                                    <?php if ($role['is_system_role']): ?>
                                                        <span class="ml-2 px-2 py-1 bg-white bg-opacity-20 rounded text-xs">
                                                            <i class="fas fa-lock"></i> Sistem
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-xs opacity-90">
                                                    <?= e($role['description']) ?>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm">
                                                    <i class="fas fa-users mr-1"></i><?= $role['user_count'] ?? 0 ?> kullanıcı
                                                </div>
                                                <div class="text-xs opacity-90">
                                                    <i class="fas fa-key mr-1"></i><?= $role['permission_count'] ?? 0 ?> izin
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Parent Role Indicator -->
                                <?php if (!empty($role['parent_role'])): ?>
                                    <div class="absolute -top-2 right-0 text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 px-2 py-1 rounded border border-gray-300 dark:border-gray-600">
                                        <i class="fas fa-arrow-up mr-1"></i>Devralır: <?= e($role['parent_role']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="ml-4 flex space-x-2">
                            <a href="<?= base_url('/admin/roles/' . $role['id']) ?>" 
                               class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                               title="Detayları Gör">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?= base_url('/admin/roles/' . $role['id'] . '/edit') ?>" 
                               class="px-3 py-2 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-800 transition"
                               title="Rolü Düzenle">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <i class="fas fa-sitemap text-6xl mb-4 opacity-50"></i>
                <p class="text-lg">Rol bulunamadı</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Role Relationships -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Inheritance Tree -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
                <i class="fas fa-code-branch mr-2"></i>Devir Alma Ağacı
            </h2>
            
            <div class="space-y-2">
                <?php if (isset($roles)): ?>
                    <?php foreach ($roles as $role): ?>
                        <?php if (empty($role['parent_role'])): // Root roles ?>
                            <div class="border-l-4 border-blue-500 pl-4 py-2">
                                <div class="font-semibold text-gray-800 dark:text-gray-200">
                                    <?= e($role['name']) ?>
                                </div>
                                <!-- Find child roles -->
                                <?php foreach ($roles as $childRole): ?>
                                    <?php if ($childRole['parent_role'] === $role['name']): ?>
                                        <div class="ml-6 mt-2 border-l-4 border-gray-300 dark:border-gray-600 pl-4 py-1">
                                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                                <i class="fas fa-arrow-right mr-1 text-gray-400"></i>
                                                <?= e($childRole['name']) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
                <i class="fas fa-chart-bar mr-2"></i>Hiyerarşi İstatistikleri
            </h2>
            
            <?php if (isset($roles)): ?>
                <?php 
                $totalRoles = count($roles);
                $systemRoles = count(array_filter($roles, fn($r) => $r['is_system_role']));
                $customRoles = $totalRoles - $systemRoles;
                $avgLevel = $totalRoles > 0 ? round(array_sum(array_column($roles, 'hierarchy_level')) / $totalRoles) : 0;
                $totalUsers = array_sum(array_column($roles, 'user_count'));
                ?>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900 rounded">
                        <span class="text-gray-700 dark:text-gray-300">Toplam Roller</span>
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= $totalRoles ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900 rounded">
                        <span class="text-gray-700 dark:text-gray-300">Sistem Rolleri</span>
                        <span class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"><?= $systemRoles ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900 rounded">
                        <span class="text-gray-700 dark:text-gray-300">Özel Roller</span>
                        <span class="text-2xl font-bold text-green-600 dark:text-green-400"><?= $customRoles ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900 rounded">
                        <span class="text-gray-700 dark:text-gray-300">Ortalama Seviye</span>
                        <span class="text-2xl font-bold text-purple-600 dark:text-purple-400"><?= $avgLevel ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-indigo-50 dark:bg-indigo-900 rounded">
                        <span class="text-gray-700 dark:text-gray-300">Toplam Kullanıcılar</span>
                        <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400"><?= $totalUsers ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

