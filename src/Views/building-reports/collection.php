<?php
/**
 * Building Reports - Collection Report View
 */
?>

<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-receipt mr-3 text-primary-600"></i>
                Tahsilat Raporu
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Eksik ödemeler ve tahsilat durumu</p>
        </div>
        <div class="flex gap-2">
            <?php if (isset($building)): ?>
                <div class="relative group">
                    <button class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg">
                        <i class="fas fa-file-export mr-2"></i>Dışa Aktar
                        <i class="fas fa-chevron-down ml-2 text-sm"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10">
                        <a href="<?= base_url('/building-reports/export-fees?format=pdf&building_id=' . $building['id'] . '&status=overdue') ?>" 
                           class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-t-lg">
                            <i class="fas fa-file-pdf mr-2 text-red-600"></i>PDF İndir
                        </a>
                        <a href="<?= base_url('/building-reports/export-fees?format=excel&building_id=' . $building['id'] . '&status=overdue') ?>" 
                           class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-file-excel mr-2 text-green-600"></i>Excel İndir
                        </a>
                        <a href="<?= base_url('/building-reports/export-fees?format=csv&building_id=' . $building['id'] . '&status=overdue') ?>" 
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
        </form>
    </div>

    <?php if (isset($building)): ?>
        <!-- Building Info -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                <?= e($building['name']) ?>
            </h2>
            <p class="text-gray-600 dark:text-gray-400">
                <i class="fas fa-map-marker-alt mr-2"></i><?= htmlspecialchars($building['address_line'] ?? '') ?>, <?= htmlspecialchars($building['district'] ?? '') ?>, <?= htmlspecialchars($building['city'] ?? '') ?>
            </p>
        </div>

        <!-- Overdue Fees -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                Geciken Ödemeler
            </h2>
            
            <?php if (!empty($overdueFees)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Daire</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Dönem</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tutar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Vade Tarihi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gün</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Durum</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php 
                            $totalOverdue = 0;
                            foreach ($overdueFees as $fee): 
                                $totalOverdue += ($fee['total_amount'] - $fee['paid_amount']);
                                $daysOverdue = floor((time() - strtotime($fee['due_date'])) / (60 * 60 * 24));
                            ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        Daire <?= htmlspecialchars($fee['unit_number'] ?? 'N/A') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?= htmlspecialchars($fee['period'] ?? 'N/A') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-red-600 dark:text-red-400">
                                        <?= number_format($fee['total_amount'] - $fee['paid_amount'], 2) ?> ₺
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?= date('d.m.Y', strtotime($fee['due_date'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 py-1 rounded <?= $daysOverdue > 30 ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800' ?>">
                                            <?= $daysOverdue ?> gün
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 py-1 rounded bg-red-100 text-red-800">
                                            Gecikmiş
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">
                                    TOPLAM
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600 dark:text-red-400">
                                    <?= number_format($totalOverdue, 2) ?> ₺
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-green-600 dark:text-green-400">
                    <i class="fas fa-check-circle text-4xl mb-4"></i>
                    <p class="text-lg font-semibold">Tüm ödemeler zamanında yapılmış!</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Geciken ödeme bulunmamaktadır.</p>
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

