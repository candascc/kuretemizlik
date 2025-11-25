<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-building mr-3 text-primary-600"></i>
                <?= e($building['name']) ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                <i class="fas fa-map-marker-alt mr-2"></i><?= htmlspecialchars($building['city'] ?? '') ?>, <?= htmlspecialchars($building['district'] ?? '') ?>
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="<?= base_url("/buildings/{$building['id']}/edit") ?>" 
               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-lg hover:shadow-xl transition-all">
                <i class="fas fa-edit mr-2"></i>Düzenle
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Enhanced Stats Cards -->
    <?php if (!empty($statistics)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Daireler -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 p-6 rounded-xl shadow-lg border border-blue-200 dark:border-blue-700 hover:shadow-xl transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-blue-700 dark:text-blue-200">Toplam Daire</p>
                        <p class="text-3xl font-bold text-blue-900 dark:text-blue-100 mt-1">
                            <?= $statistics['units']['total_units'] ?? 0 ?>
                        </p>
                        <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">
                            %<?= $statistics['units']['occupied_rate'] ?? 0 ?> dolu
                        </p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg">
                        <i class="fas fa-home text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Aidat Ödeme Oranı -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 p-6 rounded-xl shadow-lg border border-green-200 dark:border-green-700 hover:shadow-xl transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-green-700 dark:text-green-200">Ödeme Oranı</p>
                        <p class="text-3xl font-bold text-green-900 dark:text-green-100 mt-1">
                            %<?= $statistics['fees']['collection_rate'] ?? 0 ?>
                        </p>
                        <p class="text-xs text-green-600 dark:text-green-300 mt-1">
                            <?= number_format($statistics['fees']['paid_amount'] ?? 0, 0) ?> ₺ ödendi
                        </p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Geciken Aidatlar -->
            <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 p-6 rounded-xl shadow-lg border border-red-200 dark:border-red-700 hover:shadow-xl transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-red-700 dark:text-red-200">Geciken</p>
                        <p class="text-3xl font-bold text-red-900 dark:text-red-100 mt-1">
                            <?= number_format($statistics['fees']['overdue_amount'] ?? 0, 0) ?> ₺
                        </p>
                        <p class="text-xs text-red-600 dark:text-red-300 mt-1">
                            Acil takip gerekli
                        </p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg">
                        <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Aylık Giderler -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 p-6 rounded-xl shadow-lg border border-purple-200 dark:border-purple-700 hover:shadow-xl transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-purple-700 dark:text-purple-200">Bu Ay Gider</p>
                        <p class="text-3xl font-bold text-purple-900 dark:text-purple-100 mt-1">
                            <?= number_format($statistics['expenses']['this_month'] ?? 0, 0) ?> ₺
                        </p>
                        <?php if (isset($statistics['expenses']['last_month']) && $statistics['expenses']['last_month'] > 0): ?>
                            <?php 
                            $diff = $statistics['expenses']['this_month'] - $statistics['expenses']['last_month'];
                            $diffPercent = round(($diff / $statistics['expenses']['last_month']) * 100, 1);
                            ?>
                            <p class="text-xs <?= $diffPercent > 0 ? 'text-red-600' : 'text-green-600' ?> dark:text-purple-300 mt-1">
                                <?= $diffPercent > 0 ? '+' : '' ?><?= $diffPercent ?>% geçen aya göre
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg">
                        <i class="fas fa-receipt text-white text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Financial Charts -->
    <?php if (!empty($monthlyFees) && !empty($monthlyExpenses)): ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Aidat Tahsilat Trendi -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-chart-line mr-2 text-green-600"></i>Aylık Aidat Tahsilatı
                </h3>
                <canvas id="feesChart" height="300"></canvas>
            </div>

            <!-- Gider Trendi -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-chart-bar mr-2 text-red-600"></i>Aylık Gider Dağılımı
                </h3>
                <canvas id="expensesChart" height="300"></canvas>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabs Navigation -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
            <nav class="flex space-x-1 px-4 overflow-x-auto" aria-label="Tabs">
                <button onclick="showTab('units')" class="tab-button active px-4 py-3 text-sm font-medium text-primary-600 border-b-2 border-primary-600 whitespace-nowrap">
                    <i class="fas fa-home mr-2"></i>Daireler
                </button>
                <button onclick="showTab('fees')" class="tab-button px-4 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 whitespace-nowrap">
                    <i class="fas fa-money-bill-wave mr-2"></i>Aidatlar
                </button>
                <button onclick="showTab('expenses')" class="tab-button px-4 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 whitespace-nowrap">
                    <i class="fas fa-receipt mr-2"></i>Giderler
                </button>
                <button onclick="showTab('documents')" class="tab-button px-4 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 whitespace-nowrap">
                    <i class="fas fa-file-alt mr-2"></i>Dokümanlar
                </button>
                <button onclick="showTab('meetings')" class="tab-button px-4 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 whitespace-nowrap">
                    <i class="fas fa-calendar-check mr-2"></i>Toplantılar
                </button>
                <button onclick="showTab('announcements')" class="tab-button px-4 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 whitespace-nowrap">
                    <i class="fas fa-bullhorn mr-2"></i>Duyurular
                </button>
                <button onclick="showTab('surveys')" class="tab-button px-4 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 whitespace-nowrap">
                    <i class="fas fa-poll mr-2"></i>Anketler
                </button>
                <button onclick="showTab('facilities')" class="tab-button px-4 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 whitespace-nowrap">
                    <i class="fas fa-dumbbell mr-2"></i>Rezervasyon Alanları
                </button>
                <button onclick="showTab('reservations')" class="tab-button px-4 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300 whitespace-nowrap">
                    <i class="fas fa-calendar-check mr-2"></i>Rezervasyonlar
                </button>
            </nav>
        </div>

        <div class="p-6">
            <!-- Units Tab -->
            <div id="tab-units" class="tab-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daireler</h3>
                    <a href="<?= base_url("/units/new?building_id={$building['id']}") ?>" 
                       class="text-sm px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-plus mr-1"></i>Yeni Daire
                    </a>
                </div>
                <?php if (empty($building['units'])): ?>
                    <div class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <i class="fas fa-home text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">Henüz daire eklenmemiş</p>
                        <a href="<?= base_url("/units/new?building_id={$building['id']}") ?>" 
                           class="mt-4 inline-block px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">
                            İlk Daireyi Ekle
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Daire No</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Mal Sahibi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Telefon</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Aylık Aidat</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($building['units'] as $unit): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            <?= e($unit['unit_number']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?= e($unit['owner_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?= htmlspecialchars($unit['owner_phone'] ?? '-') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?= number_format($unit['monthly_fee'] ?? 0, 2) ?> ₺
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <a href="<?= base_url("/units/{$unit['id']}") ?>" 
                                               class="text-primary-600 hover:text-primary-800 dark:text-primary-400 font-medium">Detay</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Fees Tab -->
            <div id="tab-fees" class="tab-content hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aidatlar</h3>
                    <a href="<?= base_url("/management-fees/generate?building_id={$building['id']}") ?>" 
                       class="text-sm px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-plus mr-1"></i>Yeni Aidat
                    </a>
                </div>
                <?php if (empty($recentFees)): ?>
                    <div class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <i class="fas fa-money-bill-wave text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">Henüz aidat kaydı yok</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dönem</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Daire</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tutar</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Vade</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($recentFees as $fee): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            <?= htmlspecialchars($fee['period'] ?? '-') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?= htmlspecialchars($fee['unit_number'] ?? '-') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                            <?= number_format($fee['total_amount'] ?? 0, 2) ?> ₺
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?= $fee['due_date'] ? date('d.m.Y', strtotime($fee['due_date'])) : '-' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusColors = [
                                                'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                'partial' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                            ];
                                            $statusTexts = ['paid' => 'Ödendi', 'pending' => 'Bekliyor', 'overdue' => 'Gecikmiş', 'partial' => 'Kısmi'];
                                            $status = $fee['status'] ?? 'pending';
                                            ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusColors[$status] ?? $statusColors['pending'] ?>">
                                                <?= $statusTexts[$status] ?? $status ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <a href="<?= base_url("/management-fees/{$fee['id']}") ?>" 
                                               class="text-primary-600 hover:text-primary-800 dark:text-primary-400 font-medium">Detay</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Expenses Tab -->
            <div id="tab-expenses" class="tab-content hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Giderler</h3>
                    <a href="<?= base_url("/expenses/new?building_id={$building['id']}") ?>" 
                       class="text-sm px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-plus mr-1"></i>Yeni Gider
                    </a>
                </div>
                <?php if (empty($recentExpenses)): ?>
                    <div class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <i class="fas fa-receipt text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">Henüz gider kaydı yok</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tarih</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kategori</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Açıklama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tutar</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($recentExpenses as $expense): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?= $expense['expense_date'] ? date('d.m.Y', strtotime($expense['expense_date'])) : '-' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?= htmlspecialchars($expense['category'] ?? '-') ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            <?= htmlspecialchars(substr($expense['description'] ?? '-', 0, 50)) ?><?= strlen($expense['description'] ?? '') > 50 ? '...' : '' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                                            <?= number_format($expense['amount'] ?? 0, 2) ?> ₺
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusColors = [
                                                'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                            ];
                                            $statusTexts = ['approved' => 'Onaylandı', 'pending' => 'Bekliyor', 'rejected' => 'Reddedildi'];
                                            $status = $expense['approval_status'] ?? 'pending';
                                            ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusColors[$status] ?? $statusColors['pending'] ?>">
                                                <?= $statusTexts[$status] ?? $status ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <a href="<?= base_url("/expenses/{$expense['id']}") ?>" 
                                               class="text-primary-600 hover:text-primary-800 dark:text-primary-400 font-medium">Detay</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Documents Tab -->
            <div id="tab-documents" class="tab-content hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Dokümanlar</h3>
                    <a href="<?= base_url("/documents/upload?building_id={$building['id']}") ?>" 
                       class="text-sm px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-plus mr-1"></i>Yeni Doküman
                    </a>
                </div>
                <?php if (empty($recentDocuments)): ?>
                    <div class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <i class="fas fa-file-alt text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">Henüz doküman yok</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($recentDocuments as $doc): ?>
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all">
                                <div class="flex items-start justify-between mb-2">
                                    <i class="fas fa-file text-3xl text-primary-600"></i>
                                    <?php if (!empty($doc['is_public'])): ?>
                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Herkese Açık</span>
                                    <?php endif; ?>
                                </div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1"><?= htmlspecialchars($doc['title'] ?? '-') ?></h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                    <?= htmlspecialchars($doc['document_type'] ?? 'other') ?>
                                </p>
                                <div class="flex items-center justify-between text-xs text-gray-400">
                                    <span><?= date('d.m.Y', strtotime($doc['created_at'])) ?></span>
                                    <a href="<?= base_url("/documents/{$doc['id']}") ?>" class="text-primary-600 hover:text-primary-800">Görüntüle</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Meetings Tab -->
            <div id="tab-meetings" class="tab-content hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Toplantılar</h3>
                    <a href="<?= base_url("/meetings/new?building_id={$building['id']}") ?>" 
                       class="text-sm px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-plus mr-1"></i>Yeni Toplantı
                    </a>
                </div>
                <?php if (empty($upcomingMeetings)): ?>
                    <div class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <i class="fas fa-calendar-check text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">Henüz toplantı kaydı yok</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($upcomingMeetings as $meeting): ?>
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1"><?= htmlspecialchars($meeting['title'] ?? '-') ?></h4>
                                        <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                            <span><i class="fas fa-calendar mr-1"></i><?= $meeting['meeting_date'] ? date('d.m.Y H:i', strtotime($meeting['meeting_date'])) : '-' ?></span>
                                            <span><?= htmlspecialchars($meeting['meeting_type'] ?? 'regular') ?></span>
                                        </div>
                                    </div>
                                    <a href="<?= base_url("/meetings/{$meeting['id']}") ?>" class="text-primary-600 hover:text-primary-800 font-medium">Detay</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Announcements Tab -->
            <div id="tab-announcements" class="tab-content hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Duyurular</h3>
                    <a href="<?= base_url("/announcements/new?building_id={$building['id']}") ?>" 
                       class="text-sm px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-plus mr-1"></i>Yeni Duyuru
                    </a>
                </div>
                <?php if (empty($recentAnnouncements)): ?>
                    <div class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <i class="fas fa-bullhorn text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">Henüz duyuru yok</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recentAnnouncements as $ann): ?>
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1"><?= htmlspecialchars($ann['title'] ?? '-') ?></h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                            <?= htmlspecialchars(substr($ann['content'] ?? '', 0, 100)) ?>...
                                        </p>
                                        <div class="flex items-center space-x-4 text-xs text-gray-400">
                                            <span><?= date('d.m.Y', strtotime($ann['publish_date'])) ?></span>
                                            <span><?= htmlspecialchars($ann['announcement_type'] ?? 'info') ?></span>
                                        </div>
                                    </div>
                                    <a href="<?= base_url("/announcements/{$ann['id']}") ?>" class="text-primary-600 hover:text-primary-800 font-medium">Detay</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Surveys Tab -->
            <div id="tab-surveys" class="tab-content hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Anketler</h3>
                    <a href="<?= base_url("/surveys/create?building_id={$building['id']}") ?>" 
                       class="text-sm px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-plus mr-1"></i>Yeni Anket
                    </a>
                </div>
                <?php if (empty($activeSurveys)): ?>
                    <div class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <i class="fas fa-poll text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">Henüz anket yok</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($activeSurveys as $survey): ?>
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1"><?= htmlspecialchars($survey['title'] ?? '-') ?></h4>
                                        <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                            <span><?= htmlspecialchars($survey['survey_type'] ?? 'poll') ?></span>
                                            <span><?= $survey['response_count'] ?? 0 ?> cevap</span>
                                        </div>
                                    </div>
                                    <a href="<?= base_url("/surveys/{$survey['id']}") ?>" class="text-primary-600 hover:text-primary-800 font-medium">Detay</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Facilities Tab -->
            <div id="tab-facilities" class="tab-content hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rezervasyon Alanları</h3>
                    <a href="<?= base_url("/facilities/new?building_id={$building['id']}") ?>" 
                       class="text-sm px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-plus mr-1"></i>Yeni Alan
                    </a>
                </div>
                <?php if (empty($facilities)): ?>
                    <div class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <i class="fas fa-dumbbell text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">Henüz rezervasyon alanı yok</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($facilities as $facility): ?>
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all">
                                <div class="flex items-start justify-between mb-2">
                                    <h4 class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($facility['facility_name'] ?? '-') ?></h4>
                                    <?php if (!empty($facility['is_active'])): ?>
                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded">Aktif</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                    <i class="fas fa-users mr-1"></i>Kapasite: <?= $facility['capacity'] ?? '-' ?> kişi
                                </p>
                                <div class="flex items-center justify-between text-sm">
                                    <a href="<?= base_url("/facilities/{$facility['id']}/edit") ?>" class="text-primary-600 hover:text-primary-800">Düzenle</a>
                                    <span class="text-gray-400"><?= htmlspecialchars($facility['facility_type'] ?? '-') ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Reservations Tab -->
            <div id="tab-reservations" class="tab-content hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rezervasyonlar</h3>
                    <a href="<?= base_url("/reservations/new?building_id={$building['id']}") ?>" 
                       class="text-sm px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-plus mr-1"></i>Yeni Rezervasyon
                    </a>
                </div>
                <?php if (empty($recentReservations)): ?>
                    <div class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <i class="fas fa-calendar-check text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">Henüz rezervasyon yok</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tarih</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Alan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Daire</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($recentReservations as $reservation): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?= $reservation['start_date'] ? date('d.m.Y', strtotime($reservation['start_date'])) : '-' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?= htmlspecialchars($reservation['facility_name'] ?? '-') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?= htmlspecialchars($reservation['unit_number'] ?? '-') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusColors = [
                                                'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                            ];
                                            $statusTexts = ['approved' => 'Onaylandı', 'pending' => 'Bekliyor', 'rejected' => 'Reddedildi', 'cancelled' => 'İptal'];
                                            $status = $reservation['status'] ?? 'pending';
                                            ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusColors[$status] ?? $statusColors['pending'] ?>">
                                                <?= $statusTexts[$status] ?? $status ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <a href="<?= base_url("/reservations/{$reservation['id']}") ?>" 
                                               class="text-primary-600 hover:text-primary-800 dark:text-primary-400 font-medium">Detay</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active', 'border-primary-600', 'text-primary-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.remove('hidden');
    
    // Add active class to clicked button
    event.target.closest('.tab-button').classList.add('active', 'border-primary-600', 'text-primary-600');
    event.target.closest('.tab-button').classList.remove('border-transparent', 'text-gray-500');
}

// Initialize charts
<?php if (!empty($monthlyFees) && !empty($monthlyExpenses)): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Fees Chart - Line chart showing paid vs total amounts
    const feesCtx = document.getElementById('feesChart');
    if (feesCtx) {
        const feesData = <?= json_encode(array_values($monthlyFees)) ?>;
        new Chart(feesCtx, {
            type: 'line',
            data: {
                labels: feesData.map(d => d.month_name),
                datasets: [{
                    label: 'Toplam Aidat',
                    data: feesData.map(d => d.total_amount),
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Ödenen Tutar',
                    data: feesData.map(d => d.paid_amount),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' ₺';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(0) + ' ₺';
                            }
                        }
                    }
                }
            }
        });
    }

    // Expenses Chart - Bar chart showing monthly expenses
    const expensesCtx = document.getElementById('expensesChart');
    if (expensesCtx) {
        const expensesData = <?= json_encode(array_values($monthlyExpenses)) ?>;
        new Chart(expensesCtx, {
            type: 'bar',
            data: {
                labels: expensesData.map(d => d.month_name),
                datasets: [{
                    label: 'Onaylanan Giderler',
                    data: expensesData.map(d => d.approved_amount),
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    borderColor: 'rgb(34, 197, 94)',
                    borderWidth: 1
                }, {
                    label: 'Bekleyen Giderler',
                    data: expensesData.map(d => d.pending_amount),
                    backgroundColor: 'rgba(251, 191, 36, 0.8)',
                    borderColor: 'rgb(251, 191, 36)',
                    borderWidth: 1
                }, {
                    label: 'Reddedilen Giderler',
                    data: expensesData.map(d => d.rejected_amount),
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    borderColor: 'rgb(239, 68, 68)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' ₺';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(0) + ' ₺';
                            }
                        }
                    }
                }
            }
        });
    }
});
<?php endif; ?>
</script>
