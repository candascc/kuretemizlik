<?php
/**
 * Building Reports - Financial Report View
 */
?>

<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-money-bill-wave mr-3 text-primary-600"></i>
                Bina Finansal Raporu
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Apartman/Site finansal durumu</p>
        </div>
        <div class="flex gap-2">
            <?php if (isset($building)): ?>
                <div class="relative group">
                    <button class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg">
                        <i class="fas fa-file-export mr-2"></i>Dışa Aktar
                        <i class="fas fa-chevron-down ml-2 text-sm"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10">
                        <a href="<?= base_url('/building-reports/export-expenses?format=pdf&building_id=' . $building['id']) ?>" 
                           class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-t-lg">
                            <i class="fas fa-file-pdf mr-2 text-red-600"></i>PDF İndir
                        </a>
                        <a href="<?= base_url('/building-reports/export-expenses?format=excel&building_id=' . $building['id']) ?>" 
                           class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-file-excel mr-2 text-green-600"></i>Excel İndir
                        </a>
                        <a href="<?= base_url('/building-reports/export-expenses?format=csv&building_id=' . $building['id']) ?>" 
                           class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-b-lg">
                            <i class="fas fa-file-csv mr-2 text-blue-600"></i>CSV İndir
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg">
                <i class="fas fa-print mr-2"></i>Yazdır
            </button>
        </div>
    </div>

    <!-- Building Selector -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <form method="GET" class="flex gap-4">
            <select name="building_id" class="form-select flex-1" onchange="this.form.submit()">
                <option value="">Tüm Binalar</option>
                <?php foreach ($buildings ?? [] as $building): ?>
                    <option value="<?= $building['id'] ?>" <?= (isset($building['id']) && $_GET['building_id'] ?? null == $building['id']) ? 'selected' : '' ?>>
                        <?= e($building['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="year" class="form-select w-40" onchange="this.form.submit()">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= ($year ?? date('Y')) == $y ? 'selected' : '' ?>>
                        <?= $y ?>
                    </option>
                <?php endfor; ?>
            </select>
        </form>
    </div>

    <?php if (isset($building)): ?>
        <!-- Building Details -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                <?= e($building['name']) ?>
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-xl border border-blue-200 dark:border-blue-700">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                        <?= $stats['units']['total_units'] ?? 0 ?>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 font-medium">Toplam Daire</div>
                </div>
                <div class="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 rounded-xl border border-green-200 dark:border-green-700">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                        <?= $stats['units']['active_units'] ?? 0 ?>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 font-medium">Aktif Daire</div>
                </div>
                <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 rounded-xl border border-purple-200 dark:border-purple-700">
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                        %<?= $stats['units']['occupied_rate'] ?? 0 ?>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 font-medium">Doluluk Oranı</div>
                </div>
                <div class="text-center p-4 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/30 rounded-xl border border-orange-200 dark:border-orange-700">
                    <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                        <?= number_format($stats['fees']['paid_amount'] ?? 0, 0) ?> ₺
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 font-medium">Ödenen Aidat</div>
                </div>
            </div>
        </div>

        <!-- Monthly Expenses -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                Aylık Gider Özeti
            </h2>
            <?php if (!empty($monthlyExpenses)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ay</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Onaylanan</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Bekleyen</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Reddedilen</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Toplam</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kayıt Sayısı</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($monthlyExpenses as $expense): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        <?= e($expense['month_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400 text-right font-semibold">
                                        <?= number_format($expense['approved_amount'], 2) ?> ₺
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600 dark:text-yellow-400 text-right font-semibold">
                                        <?= number_format($expense['pending_amount'], 2) ?> ₺
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 text-right font-semibold">
                                        <?= number_format($expense['rejected_amount'], 2) ?> ₺
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 dark:text-blue-400 text-right font-bold">
                                        <?= number_format($expense['approved_amount'] + $expense['pending_amount'] + $expense['rejected_amount'], 2) ?> ₺
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                                        <?= $expense['total_count'] ?> kayıt
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p>Bu dönemde gider kaydı bulunmamaktadır.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- No Building Selected -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 text-center py-16">
            <i class="fas fa-building text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400 text-lg">Raporu görüntülemek için bir bina seçin</p>
        </div>
    <?php endif; ?>
</div>

