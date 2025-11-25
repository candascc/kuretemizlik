<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-exclamation-triangle mr-3 text-red-600"></i>
                Geciken Aidatlar
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Vade tarihi geçmiş ve ödenmemiş aidatlar</p>
        </div>
        <div class="flex space-x-3">
            <a href="<?= base_url('/management-fees/generate') ?>" 
               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Yeni Aidat Oluştur
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Geciken</p>
                    <p class="text-2xl font-bold text-red-600 mt-1"><?= $stats['total_overdue'] ?? 0 ?></p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-300 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Geciken Tutar</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">
                        <?= number_format($stats['total_amount'] ?? 0, 2) ?> ₺
                    </p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                    <i class="fas fa-money-bill-wave text-red-600 dark:text-red-300 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Ortalama Gecikme</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">
                        <?= $stats['avg_days_overdue'] ?? 0 ?> Gün
                    </p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-300 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Gecikme Cezası</p>
                    <p class="text-2xl font-bold text-orange-600 mt-1">
                        <?= number_format($stats['total_late_fee'] ?? 0, 2) ?> ₺
                    </p>
                </div>
                <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-lg">
                    <i class="fas fa-dollar-sign text-orange-600 dark:text-orange-300 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <form method="GET" action="<?= base_url('/management-fees/overdue') ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bina</label>
                <select name="building_id" 
                        class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <?php foreach ($buildings as $bld): ?>
                        <option value="<?= $bld['id'] ?>" <?= (isset($filters['building_id']) && $filters['building_id'] == $bld['id']) ? 'selected' : '' ?>>
                            <?= e($bld['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gecikme Süresi</label>
                <select name="days" 
                        class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <option value="7" <?= (isset($filters['days']) && $filters['days'] == 7) ? 'selected' : '' ?>>7 Günden Az</option>
                    <option value="30" <?= (isset($filters['days']) && $filters['days'] == 30) ? 'selected' : '' ?>>30 Günden Az</option>
                    <option value="60" <?= (isset($filters['days']) && $filters['days'] == 60) ? 'selected' : '' ?>>60+ Gün</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Minimum Tutar</label>
                <input type="number" name="min_amount" value="<?= $filters['min_amount'] ?? '' ?>" 
                       step="0.01" placeholder="Min tutar"
                       class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
            </div>

            <div class="flex items-end">
                <button type="submit" 
                        class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-filter mr-2"></i>Filtrele
                </button>
            </div>
        </form>
    </div>

    <!-- Overdue Fees List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Geciken Aidat Listesi</h2>
        </div>

        <?php if (!empty($overdueFees)): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Daire</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Dönem</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Vade Tarihi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gecikme (Gün)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tutar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ödenen</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kalan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gecikme Cezası</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($overdueFees as $fee): 
                            $dueDate = strtotime($fee['due_date']);
                            $daysOverdue = max(0, floor((time() - $dueDate) / 86400));
                            $remaining = $fee['total_amount'] - $fee['paid_amount'];
                        ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($fee['unit_number'] ?? '-') ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <?= htmlspecialchars($fee['building_name'] ?? '-') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white"><?= e($fee['period']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-900 dark:text-white">
                                        <?= date('d.m.Y', strtotime($fee['due_date'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?= $daysOverdue < 30 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $daysOverdue ?> Gün
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    <?= number_format($fee['total_amount'], 2) ?> ₺
                                </td>
                                <td class="px-6 py-4 text-green-600">
                                    <?= number_format($fee['paid_amount'], 2) ?> ₺
                                </td>
                                <td class="px-6 py-4 font-bold text-red-600">
                                    <?= number_format($remaining, 2) ?> ₺
                                </td>
                                <td class="px-6 py-4 text-orange-600">
                                    <?= number_format($fee['late_fee'], 2) ?> ₺
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <a href="<?= base_url('/management-fees/' . $fee['id'] . '/payment') ?>" 
                                           class="px-3 py-1 bg-primary-600 hover:bg-primary-700 text-white text-sm rounded-lg transition-colors">
                                            <i class="fas fa-money-bill-wave mr-1"></i>Ödeme
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-12 text-center">
                <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
                <p class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Geciken Aidat Yok!</p>
                <p class="text-gray-600 dark:text-gray-400">Tüm aidatlar zamanında ödendi.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

