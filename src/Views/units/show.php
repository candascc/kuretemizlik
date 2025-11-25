<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-home mr-3 text-primary-600"></i>
                Daire <?= htmlspecialchars($unit['unit_number'] ?? '-') ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                <?= htmlspecialchars($unit['building_name'] ?? '-') ?> - 
                <?= htmlspecialchars($unit['owner_name'] ?? '-') ?>
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="<?= base_url('/units/' . $unit['id'] . '/edit') ?>" 
               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                <i class="fas fa-edit mr-2"></i>Düzenle
            </a>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Aylık Aidat</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        <?= number_format($unit['monthly_fee'] ?? 0, 2) ?> ₺
                    </p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <i class="fas fa-money-bill-wave text-blue-600 dark:text-blue-300 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Bakiye</p>
                    <p class="text-2xl font-bold <?= ($unit['debt_balance'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' ?> mt-1">
                        <?= number_format($unit['debt_balance'] ?? 0, 2) ?> ₺
                    </p>
                </div>
                <div class="p-3 <?= ($unit['debt_balance'] ?? 0) > 0 ? 'bg-red-100 dark:bg-red-900' : 'bg-green-100 dark:bg-green-900' ?> rounded-lg">
                    <i class="fas fa-<?= ($unit['debt_balance'] ?? 0) > 0 ? 'exclamation-triangle' : 'check-circle' ?> text-<?= ($unit['debt_balance'] ?? 0) > 0 ? 'red' : 'green' ?>-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Alan</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        <?= htmlspecialchars($unit['net_area'] ?? '-') ?> m²
                    </p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <i class="fas fa-ruler-combined text-purple-600 dark:text-purple-300 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Unit Details -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
        <!-- Owner Information -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-user mr-2 text-primary-600"></i>Sahip Bilgileri
            </h2>
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Ad Soyad</label>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($unit['owner_name'] ?? '-') ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Telefon</label>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($unit['owner_phone'] ?? '-') ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">E-posta</label>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($unit['owner_email'] ?? '-') ?></p>
                </div>
                <?php if ($unit['tenant_name']): ?>
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <label class="text-sm text-gray-600 dark:text-gray-400">Kiracı</label>
                        <p class="font-medium text-gray-900 dark:text-white"><?= e($unit['tenant_name']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Unit Information -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-info-circle mr-2 text-primary-600"></i>Daire Bilgileri
            </h2>
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Kat</label>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($unit['floor_number'] ?? '-') ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Oda Sayısı</label>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($unit['room_count'] ?? '-') ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Durum</label>
                    <p class="font-medium">
                        <?php
                        $statusColors = [
                            'active' => 'text-green-600 bg-green-100 dark:bg-green-900 dark:text-green-200',
                            'inactive' => 'text-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-gray-200',
                            'empty' => 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900 dark:text-yellow-200'
                        ];
                        $statusTexts = ['active' => 'Aktif', 'inactive' => 'Pasif', 'empty' => 'Boş'];
                        $status = $unit['status'] ?? 'active';
                        ?>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusColors[$status] ?? $statusColors['active'] ?>">
                            <?= $statusTexts[$status] ?? 'Aktif' ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee History -->
    <?php if (!empty($fees ?? [])): ?>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-history mr-2 text-primary-600"></i>Aidat Geçmişi
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Dönem</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tutar</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Vade</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Durum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($fees as $fee): ?>
                            <tr>
                                <td class="px-4 py-3"><?= htmlspecialchars($fee['period'] ?? '-') ?></td>
                                <td class="px-4 py-3 font-semibold"><?= number_format($fee['total_amount'] ?? 0, 2) ?> ₺</td>
                                <td class="px-4 py-3"><?= $fee['due_date'] ? date('d.m.Y', strtotime($fee['due_date'])) : '-' ?></td>
                                <td class="px-4 py-3">
                                    <?php
                                    $status = $fee['status'] ?? 'pending';
                                    $statusColors = [
                                        'paid' => 'bg-green-100 text-green-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'overdue' => 'bg-red-100 text-red-800'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full <?= $statusColors[$status] ?? 'bg-gray-100 text-gray-800' ?>">
                                        <?= $status === 'paid' ? 'Ödendi' : ($status === 'overdue' ? 'Gecikmiş' : 'Bekliyor') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

