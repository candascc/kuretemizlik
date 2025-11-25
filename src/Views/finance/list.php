<div class="space-y-8">
            <!-- Header Section -->
            <div>
                <?php 
                if (can('finance.collect')) { ob_start(); ?>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="<?= base_url('/finance/new') ?>" 
                       class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-plus mr-2"></i> 
                        Yeni Kayıt
                    </a>
                    <!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations for finance (begin) -->
                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="toggleBulkMode()" id="bulkToggleBtn" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-check-square mr-2"></i>
                            Toplu İşlem
                        </button>
                        <div id="bulkActions" class="hidden flex items-center space-x-2">
                            <button type="button" onclick="selectAllFinance()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-200">
                                <i class="fas fa-check-double mr-1"></i>
                                Tümünü Seç
                            </button>
                            <button type="button" onclick="bulkDeleteFinance()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors duration-200">
                                <i class="fas fa-trash mr-1"></i>
                                Toplu Sil
                            </button>
                            <button type="button" onclick="cancelBulkMode()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-200">
                                <i class="fas fa-times mr-1"></i>
                                İptal
                            </button>
                        </div>
                    </div>
                    <!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations for finance (end) -->
                </div>
                <?php $rightActionsHtml = ob_get_clean(); } else { $rightActionsHtml = ''; } ?>
                <!-- ===== KOZMOS_OPERATOR_READONLY: hide new finance button for operator (end) -->
                <?php 
                $title = '<i class="fas fa-file-invoice-dollar mr-3 text-primary-600"></i>Finans';
                $subtitle = 'Gelir ve gider yönetimi';
                include __DIR__ . '/../partials/ui/list-header.php';
                ?>
            </div>

            <!-- Filter Form -->
            <?php
            $fields = [
                ['type' => 'select', 'name' => 'kind', 'label' => 'Tür', 'icon' => 'fas fa-filter', 'options' => ['INCOME' => 'Gelir', 'EXPENSE' => 'Gider'], 'value' => $filters['kind'] ?? ''],
                ['type' => 'text', 'name' => 'category', 'label' => 'Kategori', 'icon' => 'fas fa-tag', 'value' => $filters['category'] ?? '', 'placeholder' => 'Kategori ara...'],
                ['type' => 'date', 'name' => 'date_from', 'label' => 'Başlangıç', 'icon' => 'fas fa-calendar', 'value' => $filters['date_from'] ?? ''],
                ['type' => 'date', 'name' => 'date_to', 'label' => 'Bitiş', 'icon' => 'fas fa-calendar', 'value' => $filters['date_to'] ?? ''],
            ];
            if (Auth::canSwitchCompany()) {
                $companyOptions = ['' => 'Tüm Şirketler'];
                if (!empty($companies)) {
                    foreach ($companies as $company) {
                        $companyOptions[$company['id']] = $company['name'] ?? ('Şirket #' . $company['id']);
                    }
                }
                $fields[] = [
                    'type' => 'select',
                    'name' => 'company_filter',
                    'label' => 'Şirket',
                    'icon' => 'fas fa-building',
                    'options' => $companyOptions,
                    'value' => $filters['company_filter'] ?? ''
                ];
            }
            $method = 'GET';
            $action = '';
            $submitLabel = 'Filtrele';
            include __DIR__ . '/../partials/ui/list-filters.php';
            ?>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-2 xl:grid-cols-3">
                <?php $summaryPeriods = ['today' => 'Bugün', 'week' => 'Bu Hafta', 'month' => 'Bu Ay']; ?>
                <?php foreach ($summaryPeriods as $key => $label): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-shadow duration-200">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-primary-100 dark:bg-primary-900/20 rounded-lg">
                                <i class="fas fa-calendar text-primary-600 dark:text-primary-400 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400"><?= $label ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <div class="text-lg font-bold text-green-600 dark:text-green-400"><?= Utils::formatMoney($summary[$key]['income']) ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Gelir</div>
                            </div>
                            <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <div class="text-lg font-bold text-red-600 dark:text-red-400"><?= Utils::formatMoney($summary[$key]['expense']) ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Gider</div>
                            </div>
                            <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <div class="text-lg font-bold <?= $summary[$key]['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>"><?= Utils::formatMoney($summary[$key]['profit']) ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Kar</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Main Content -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
                <?php if (empty($entries)): ?>
                    <div class="p-16 text-center">
                        <div class="mx-auto w-20 h-20 flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 mb-6">
                            <i class="fas fa-file-invoice-dollar text-3xl"></i>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Henüz finans kaydı yok</div>
                        <div class="text-gray-500 dark:text-gray-400 mb-6">Yeni bir gelir veya gider ekleyin.</div>
                        <!-- ===== KOZMOS_OPERATOR_READONLY: hide empty state button for operator (begin) -->
                        <?php if (Auth::role() !== 'OPERATOR'): ?>
                        <a href="<?= base_url('/finance/new') ?>" class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <i class="fas fa-plus mr-2"></i> 
                            Yeni Kayıt
                        </a>
                        <?php endif; ?>
                        <!-- ===== KOZMOS_OPERATOR_READONLY: hide empty state button for operator (end) -->
                    </div>
                <?php else: ?>
                    <!-- Desktop Table View -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <!-- ===== KOZMOS_BULK_OPERATIONS: add checkbox column (begin) -->
                                <?php if (Auth::role() !== 'OPERATOR'): ?>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <input type="checkbox" id="selectAllCheckbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" onchange="toggleAllFinance(this)">
                                </th>
                                <?php endif; ?>
                                <!-- ===== KOZMOS_BULK_OPERATIONS: add checkbox column (end) -->
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <i class="fas fa-calendar mr-2 text-primary-500"></i>Tarih
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <i class="fas fa-tag mr-2 text-primary-500"></i>Tür
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <i class="fas fa-folder mr-2 text-primary-500"></i>Kategori
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <i class="fas fa-briefcase mr-2 text-primary-500"></i>İş
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider hidden lg:table-cell">
                                    <i class="fas fa-credit-card mr-2 text-primary-500"></i>Ödeme Durumu
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <i class="fas fa-money-bill mr-2 text-primary-500"></i>Tutar
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <i class="fas fa-cog mr-2 text-primary-500"></i>İşlem
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($entries as $entryRow): ?>
                                <?php
                                    $jobRemaining = null;
                                    if (!empty($entryRow['total_amount']) || !empty($entryRow['amount_paid'])) {
                                        $jobRemaining = max(0, (float)($entryRow['total_amount'] ?? 0) - (float)($entryRow['amount_paid'] ?? 0));
                                    }
                                    $paymentStatus = $entryRow['payment_status'] ?? 'UNPAID';
                                    $badgeClass = [
                                        'PAID' => 'bg-green-100 text-green-800',
                                        'PARTIAL' => 'bg-yellow-100 text-yellow-800',
                                        'UNPAID' => 'bg-red-100 text-red-800',
                                    ][$paymentStatus] ?? 'bg-gray-100 text-gray-800';
                                    $statusLabel = [
                                        'PAID' => 'Tamamlandı',
                                        'PARTIAL' => 'Kısmi',
                                        'UNPAID' => 'Ödenmedi',
                                    ][$paymentStatus] ?? $paymentStatus;
                                ?>
                                <tr>
                                    <!-- ===== KOZMOS_BULK_OPERATIONS: add finance checkbox (begin) -->
                                    <?php if (Auth::role() !== 'OPERATOR'): ?>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" name="finance_ids[]" value="<?= $entryRow['id'] ?>" class="finance-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    </td>
                                    <?php endif; ?>
                                    <!-- ===== KOZMOS_BULK_OPERATIONS: add finance checkbox (end) -->
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= Utils::formatDate($entryRow['date']) ?></td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $entryRow['kind'] === 'INCOME' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $entryRow['kind'] === 'INCOME' ? 'Gelir' : 'Gider' ?>
                                        </span>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= e($entryRow['category']) ?></td>
                                    <td class="px-3 sm:px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        <?php if (!empty($entryRow['job_id'])): ?>
                                            <div class="space-y-1">
                                                <div>#<?= $entryRow['job_id'] ?> · <?= htmlspecialchars($entryRow['customer_name'] ?? '-') ?></div>
                                                <div class="lg:hidden">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $badgeClass ?>"><?= $statusLabel ?></span>
                                                </div>
                                            </div>
                                        <?php elseif (!empty($entryRow['recurring_job_id'])): ?>
                                            <div class="space-y-1">
                                                <div>
                                                    <i class="fas fa-sync-alt text-blue-500 mr-1"></i>
                                                    Periyodik İş #<?= $entryRow['recurring_job_id'] ?> · <?= htmlspecialchars($entryRow['recurring_customer_name'] ?? '-') ?>
                                                </div>
                                                <div class="lg:hidden">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                                        <i class="fas fa-file-contract mr-1"></i>Sözleşme Bazlı
                                                    </span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 text-sm text-gray-700 dark:text-gray-300 hidden lg:table-cell">
                                        <?php if (!empty($entryRow['job_id'])): ?>
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $badgeClass ?>"><?= $statusLabel ?></span>
                                                <?php if ($jobRemaining !== null): ?>
                                                    <span class="text-xs text-gray-500">Kalan: <?= Utils::formatMoney($jobRemaining) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif (!empty($entryRow['recurring_job_id'])): ?>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                                    <i class="fas fa-file-contract mr-1"></i>Sözleşme Bazlı
                                                </span>
                                                <?php if ($entryRow['recurring_pricing_model'] === 'PER_MONTH'): ?>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">Aylık Ödeme</span>
                                                <?php elseif ($entryRow['recurring_pricing_model'] === 'TOTAL_CONTRACT'): ?>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">Toplam Sözleşme</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400">İş bağlantısı yok</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium <?= $entryRow['kind'] === 'INCOME' ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' ?>"><?= Utils::formatMoney($entryRow['amount']) ?></td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (begin) -->
                                        <?php if (Auth::role() !== 'OPERATOR'): ?>
                                        <div class="flex items-center justify-end space-x-2">
                                            <a href="<?= base_url("/finance/show/{$entryRow['id']}") ?>" class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-150 p-1 rounded" title="Görüntüle"><i class="fas fa-eye text-sm"></i></a>
                                            <a href="<?= base_url("/finance/edit/{$entryRow['id']}") ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-150 p-1 rounded" title="Düzenle"><i class="fas fa-edit text-sm"></i></a>
                                            <form method="POST" action="<?= base_url("/finance/delete/{$entryRow['id']}") ?>" class="inline">
                                                <?= CSRF::field() ?>
                                                <button class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors duration-150 p-1 rounded" onclick="return confirm('Silinsin mi?')" title="Sil"><i class="fas fa-trash text-sm"></i></button>
                                            </form>
                                        </div>
                                        <?php else: ?>
                                        <div class="flex items-center justify-end">
                                            <a href="<?= base_url("/finance/show/{$entryRow['id']}") ?>" class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-150 p-1 rounded" title="Görüntüle"><i class="fas fa-eye text-sm"></i></a>
                                        </div>
                                        <?php endif; ?>
                                        <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (end) -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="md:hidden space-y-4 p-4">
                        <?php foreach ($entries as $entryRow): ?>
                            <?php
                                $jobRemaining = null;
                                if (!empty($entryRow['total_amount']) || !empty($entryRow['amount_paid'])) {
                                    $jobRemaining = max(0, (float)($entryRow['total_amount'] ?? 0) - (float)($entryRow['amount_paid'] ?? 0));
                                }
                                $paymentStatus = $entryRow['payment_status'] ?? 'UNPAID';
                                $badgeClass = [
                                    'PAID' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                    'PARTIAL' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
                                    'UNPAID' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
                                ][$paymentStatus] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
                                $statusLabel = [
                                    'PAID' => 'Tamamlandı',
                                    'PARTIAL' => 'Kısmi',
                                    'UNPAID' => 'Ödenmedi',
                                ][$paymentStatus] ?? $paymentStatus;
                            ?>
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-soft border border-gray-200 dark:border-gray-700 p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= e($entryRow['category']) ?></h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= $entryRow['kind'] === 'INCOME' ? 'Gelir' : 'Gider' ?></p>
                                    </div>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $badgeClass ?>">
                                        <?= $statusLabel ?>
                                    </span>
                                </div>
                                
                                <div class="space-y-2 mb-4">
                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-calendar mr-2 text-primary-500"></i>
                                        <?= Utils::formatDate($entryRow['date']) ?>
                                    </div>
                                    <?php if (!empty($entryRow['job_id'])): ?>
                                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-briefcase mr-2 text-primary-500"></i>
                                            İş #<?= $entryRow['job_id'] ?> · <?= htmlspecialchars($entryRow['customer_name'] ?? '-') ?>
                                        </div>
                                    <?php elseif (!empty($entryRow['recurring_job_id'])): ?>
                                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-sync-alt mr-2 text-blue-500"></i>
                                            Periyodik İş #<?= $entryRow['recurring_job_id'] ?> · <?= htmlspecialchars($entryRow['recurring_customer_name'] ?? '-') ?>
                                        </div>
                                        <div class="flex items-center text-sm">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                                <i class="fas fa-file-contract mr-1"></i>Sözleşme Bazlı
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-money-bill mr-2 text-primary-500"></i>
                                        <span class="font-semibold <?= $entryRow['kind'] === 'INCOME' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                                            <?= Utils::formatMoney($entryRow['amount']) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (begin) -->
                                <?php if (Auth::role() !== 'OPERATOR'): ?>
                                <div class="flex space-x-2">
                                    <a href="<?= base_url("/finance/{$entryRow['id']}") ?>" 
                                       class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
                                        <i class="fas fa-eye mr-2"></i>
                                        Görüntüle
                                    </a>
                                    <a href="<?= base_url("/finance/edit/{$entryRow['id']}") ?>" 
                                       class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
                                        <i class="fas fa-edit mr-2"></i>
                                        Düzenle
                                    </a>
                                    <form method="POST" action="<?= base_url("/finance/delete/{$entryRow['id']}") ?>" class="flex-1" onsubmit="return confirm('Bu kaydı silmek istediğinizden emin misiniz?')">
                                        <?= CSRF::field() ?>
                                        <button type="submit" 
                                                class="w-full inline-flex items-center justify-center px-3 py-2 border border-red-300 dark:border-red-600 rounded-md text-sm font-medium text-red-700 dark:text-red-400 bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors duration-150">
                                            <i class="fas fa-trash mr-2"></i>
                                            Sil
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <div class="flex space-x-2">
                                    <a href="<?= base_url("/finance/{$entryRow['id']}") ?>" 
                                       class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
                                        <i class="fas fa-eye mr-2"></i>
                                        Görüntüle
                                    </a>
                                </div>
                                <?php endif; ?>
                                <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (end) -->
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php include __DIR__ . '/../partials/pagination.php'; ?>
                <?php endif; ?>
            </div>
</div>

<!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations JavaScript (begin) -->
<script>
function toggleBulkMode() {
    const bulkToggleBtn = document.getElementById('bulkToggleBtn');
    const bulkActions = document.getElementById('bulkActions');
    const checkboxes = document.querySelectorAll('.finance-checkbox');
    
    if (bulkActions.classList.contains('hidden')) {
        bulkActions.classList.remove('hidden');
        bulkToggleBtn.classList.add('bg-primary-600', 'text-white');
        bulkToggleBtn.classList.remove('bg-white', 'text-gray-700');
        checkboxes.forEach(checkbox => checkbox.style.display = 'block');
    } else {
        bulkActions.classList.add('hidden');
        bulkToggleBtn.classList.remove('bg-primary-600', 'text-white');
        bulkToggleBtn.classList.add('bg-white', 'text-gray-700');
        checkboxes.forEach(checkbox => checkbox.style.display = 'none');
        checkboxes.forEach(checkbox => checkbox.checked = false);
    }
}

function cancelBulkMode() {
    const bulkActions = document.getElementById('bulkActions');
    const bulkToggleBtn = document.getElementById('bulkToggleBtn');
    const checkboxes = document.querySelectorAll('.finance-checkbox');
    
    bulkActions.classList.add('hidden');
    bulkToggleBtn.classList.remove('bg-primary-600', 'text-white');
    bulkToggleBtn.classList.add('bg-white', 'text-gray-700');
    checkboxes.forEach(checkbox => {
        checkbox.style.display = 'none';
        checkbox.checked = false;
    });
}

function toggleAllFinance(checkbox) {
    const checkboxes = document.querySelectorAll('.finance-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function selectAllFinance() {
    const checkboxes = document.querySelectorAll('.finance-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = true);
}

function getSelectedFinance() {
    const checkboxes = document.querySelectorAll('.finance-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function bulkDeleteFinance() {
    const selectedIds = getSelectedFinance();
    
    if (selectedIds.length === 0) {
        alert('Lütfen silinecek kayıtları seçin.');
        return;
    }
    
    if (!confirm(`${selectedIds.length} finans kaydını silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`)) {
        return;
    }
    
    // Form oluştur ve gönder
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('/finance/bulk-delete') ?>';
    
    // CSRF token ekle
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = '<?= CSRF::get() ?>';
    form.appendChild(csrfInput);
    
    // Seçili ID'leri ekle
    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'finance_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

// Initialize - hide checkboxes by default
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.finance-checkbox');
    checkboxes.forEach(checkbox => checkbox.style.display = 'none');
});
</script>
<!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations JavaScript (end) -->
