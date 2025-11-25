<?php
$statusOptions = [
    'SCHEDULED' => 'Planlandı',
    'DONE' => 'Tamamlandı',
    'CANCELLED' => 'İptal',
];
$paymentBadgeMap = [
    'PAID' => ['bg-green-100 text-green-800', 'Tamamlandı'],
    'PARTIAL' => ['bg-yellow-100 text-yellow-800', 'Kısmi'],
    'UNPAID' => ['bg-red-100 text-red-800', 'Ödenmedi'],
];
$canBulkManage = RolePermissions::can('jobs.delete');
$canCreateJobs = RolePermissions::can('jobs.create');
$canViewJobs = RolePermissions::can('jobs.view');
$canEditJobs = RolePermissions::can('jobs.edit');
$showPast = !empty($filters['show_past']);
$queryBase = [];
foreach (['status', 'customer', 'date_from', 'date_to', 'recurring'] as $key) {
    if (!empty($filters[$key])) {
        $queryBase[$key] = $filters[$key];
    }
}
if (isset($_GET['company_filter']) && $_GET['company_filter'] !== '') {
    $queryBase['company_filter'] = (int)$_GET['company_filter'];
}
$queryWithPast = $queryBase;
$queryWithoutPast = $queryBase;
unset($queryWithPast['show_past'], $queryWithoutPast['show_past']);
$queryWithPast['show_past'] = 1;
$exportQuery = $queryBase;
if ($showPast) {
    $exportQuery['show_past'] = 1;
}
$showPastUrl = base_url('/jobs' . (empty($queryWithPast) ? '?show_past=1' : '?' . http_build_query($queryWithPast)));
$futureUrl = base_url('/jobs' . (empty($queryWithoutPast) ? '' : '?' . http_build_query($queryWithoutPast)));
$exportUrl = base_url('/jobs/export' . (empty($exportQuery) ? '' : '?' . http_build_query($exportQuery)));
?>
<div class="space-y-8">
            <div>
                <?php
                ob_start();
                ?>
                    <!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations for admin (begin) -->
                    <?php if ($canBulkManage): ?>
                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="toggleBulkMode()" id="bulkToggleBtn" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-check-square mr-2"></i>
                            Toplu İşlem
                        </button>
                        <div id="bulkActions" class="hidden flex items-center space-x-2">
                            <div id="selectionCounter" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-primary-700 bg-primary-50 border border-primary-200">
                                <i class="fas fa-check-circle mr-2"></i>
                                <span id="selectedCount">0</span> seçili
                            </div>
                            <button type="button" onclick="selectAllJobs()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-200" title="Ctrl+A">
                                <i class="fas fa-check-double mr-1"></i>
                                Tümünü Seç
                            </button>
                            <button type="button" onclick="bulkStatusUpdate()" id="bulkStatusBtn" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <i class="fas fa-edit mr-1"></i>
                                Durum Güncelle
                            </button>
                            <button type="button" onclick="bulkDelete()" id="bulkDeleteBtn" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <i class="fas fa-trash mr-1"></i>
                                Toplu Sil
                            </button>
                            <button type="button" onclick="cancelBulkMode()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-200">
                                <i class="fas fa-times mr-1"></i>
                                İptal
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations for admin (end) -->
                    
                    <!-- Export Button -->
                    <?php if ($canViewJobs): ?>
                    <a href="<?= $exportUrl ?>" 
                       class="inline-flex items-center px-4 py-2 rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors duration-200">
                        <i class="fas fa-file-export mr-2"></i>
                        Dışa Aktar
                    </a>
                    <?php endif; ?>

                    <?php if ($showPast): ?>
                    <a href="<?= $futureUrl ?>"
                       class="inline-flex items-center px-4 py-2 rounded-lg text-primary-700 bg-primary-50 border border-primary-300 hover:bg-primary-100 transition-colors duration-200"
                       title="Sadece bugün ve sonraki işleri göster">
                        <i class="fas fa-clock mr-2"></i>
                        Gelecek İşleri Göster
                    </a>
                    <?php else: ?>
                    <a href="<?= $showPastUrl ?>"
                       class="inline-flex items-center px-4 py-2 rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors duration-200"
                       title="Geçmiş işleri görüntüle">
                        <i class="fas fa-history mr-2"></i>
                        Geçmiş İşleri Göster
                    </a>
                    <?php endif; ?>
                    
                    <!-- ===== KOZMOS_OPERATOR_READONLY: hide new job button for operator (begin) -->
                    <?php if ($canCreateJobs): ?>
                    <div class="flex items-center gap-2">
                        <a href="<?= base_url('/jobs/wizard') ?>" class="inline-flex items-center px-6 py-3 rounded-lg text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 font-semibold transition-all duration-200 shadow-lg hover:shadow-xl">
                            <i class="fas fa-magic mr-2 text-yellow-300"></i> 
                            <span class="text-white font-bold">Yeni İş (Wizard)</span>
                        </a>
                        <a href="<?= base_url('/jobs/new') ?>" class="inline-flex items-center px-4 py-3 rounded-lg text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 border-2 border-gray-400 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600 font-medium transition-all duration-200" title="Klasik form">
                            <i class="fas fa-list mr-2 text-gray-600 dark:text-gray-400"></i>
                            <span class="hidden sm:inline text-gray-800 dark:text-gray-200">Klasik Form</span>
                        </a>
                    </div>
                    <?php endif; ?>
                    <!-- ===== KOZMOS_OPERATOR_READONLY: hide new job button for operator (end) -->
                <?php $rightActionsHtml = ob_get_clean(); ?>

                <?php
                if (!empty($recurringJobInfo)) {
                    $title = '<i class="fas fa-tasks mr-3 text-primary-600"></i>Periyodik İş #'.(int)$recurringJobInfo['id'].' - Oluşturulan İşler';
                    $subtitle = e($recurringJobInfo['customer_name'] ?? '').' için oluşturulan işler';
                } else {
                    $title = '<i class="fas fa-tasks mr-3 text-primary-600"></i>İşler';
                    $subtitle = 'Tüm işleri görüntüleyin ve yönetin';
                }
                include __DIR__ . '/../partials/ui/list-header.php';
                ?>
            </div>

            <?php
            $fields = [
                ['type' => 'select', 'name' => 'status', 'label' => 'Durum', 'icon' => 'fas fa-filter', 'options' => $statusOptions, 'value' => $filters['status'] ?? ''],
                ['type' => 'text', 'name' => 'customer', 'label' => 'Müşteri', 'icon' => 'fas fa-user', 'value' => $filters['customer'] ?? '', 'placeholder' => 'Müşteri adı'],
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
                        'value' => $filters['company_filter'] ?? ($_GET['company_filter'] ?? '')
                ];
            }
            $method = 'GET';
            $action = '';
            $submitLabel = 'Filtrele';
            $hiddenFields = [];
            if ($showPast) {
                $hiddenFields['show_past'] = 1;
            }
            include __DIR__ . '/../partials/ui/list-filters.php';
            unset($hiddenFields);
            ?>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
                <?php if (empty($jobs)): ?>
                    <div class="p-16 text-center">
                        <div class="mx-auto w-20 h-20 flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 mb-6">
                            <i class="fas fa-calendar-plus text-3xl"></i>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Henüz iş yok</div>
                        <div class="text-gray-500 dark:text-gray-400 mb-8 max-w-md mx-auto">Yeni bir iş ekleyerek başlayın ve müşterilerinize hizmet vermeye başlayın.</div>
                        <!-- ===== KOZMOS_OPERATOR_READONLY: hide empty state button for operator (begin) -->
                        <?php if ($canCreateJobs): ?>
                        <a href="<?= base_url('/jobs/new') ?>" class="inline-flex items-center px-6 py-3 rounded-lg text-white bg-primary-600 hover:bg-primary-700 font-medium transition-all duration-200 shadow-medium hover:shadow-strong">
                            <i class="fas fa-plus mr-2"></i> Yeni İş Ekle
                        </a>
                        <?php endif; ?>
                        <!-- ===== KOZMOS_OPERATOR_READONLY: hide empty state button for operator (end) -->
                    </div>
                <?php else: ?>
                    <div class="hidden lg:block overflow-x-auto">
                                    <?php
                        // Build headers for reusable table component
                        $headers = [];
                        if ($canBulkManage) {
                            $headers['select'] = [
                                'label' => '<input type="checkbox" id="selectAllCheckbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" onchange="toggleAllJobs(this)">',
                                'raw' => true
                            ];
                        }
                        $headers['date'] = ['label' => '<i class="fas fa-calendar mr-2"></i>Tarih', 'raw' => true];
                        $headers['customer'] = ['label' => '<i class="fas fa-user mr-2"></i>Müşteri', 'raw' => true];
                        $headers['service'] = ['label' => '<i class="fas fa-cogs mr-2"></i>Hizmet', 'raw' => true];
                        $headers['status'] = ['label' => '<i class="fas fa-tasks mr-2"></i>Durum', 'raw' => true];
                        $headers['payment'] = ['label' => '<i class="fas fa-money-bill mr-2"></i>Ödeme', 'raw' => true];
                        $headers['actions'] = ['label' => '<div class="text-right"><i class="fas fa-cog mr-2"></i>İşlemler</div>', 'raw' => true];

                        // Build rows
                        $rows = [];
                        foreach ($jobs as $job) {
                                        $jobRemaining = max(0, (float)($job['total_amount'] ?? 0) - (float)($job['amount_paid'] ?? 0));
                                        $paymentStatus = $job['payment_status'] ?? 'UNPAID';
                                        [$badgeClass, $badgeLabel] = $paymentBadgeMap[$paymentStatus] ?? ['bg-gray-100 text-gray-800', $paymentStatus];

                            $selectHtml = '';
                            if ($canBulkManage) {
                                $selectHtml = '<input type="checkbox" name="job_ids[]" value="'.(int)$job['id'].'" class="job-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500" style="display:none;" onchange="updateBulkButtonStates(); updateSelectionCounter();" data-job-id="'.(int)$job['id'].'">';
                            }

                            $dateHtml = '<div class="text-sm font-medium text-gray-900 dark:text-white">'.Utils::formatDateTime($job['start_at']).'</div>';
                            if (!empty($job['occurrence_id'])) {
                                $dateHtml .= '<div class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300"><i class="fas fa-redo mr-1"></i> Periyodik</div>';
                            }

                            $customerHtml = '<div class="text-sm font-medium text-gray-900 dark:text-white">'.e($job['customer_name']).'</div>';
                            if (!empty($job['address_line'])) {
                                $customerHtml .= '<div class="text-sm text-gray-500 dark:text-gray-400">'.e($job['address_line']).'</div>';
                            }

                            $serviceHtml = '<div class="text-sm text-gray-900 dark:text-white">'.e($job['service_name'] ?? 'Belirtilmemiş').'</div>';

                            // Status select (build as string to avoid nested PHP blocks)
                            $optionsHtml = '';
                            foreach ($statusOptions as $value => $label) {
                                $selected = ($job['status'] === $value) ? ' selected' : '';
                                $optionsHtml .= '<option value="'.$value.'"'.$selected.'>'.$label.'</option>'; 
                            }
                            $statusHtml = '<form method="POST" action="'.base_url("/jobs/status/{$job['id']}").'" class="inline" onsubmit="return maybeAskCancelReason(this)">'
                                . CSRF::field()
                                . '<select name="status" class="text-xs border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500" onchange="this.form.submit()">'
                                . $optionsHtml
                                . '</select><input type="hidden" name="cancel_reason" value=""></form>';

                            // Payment cell
                            if (!empty($job['recurring_job_id']) && !empty($job['pricing_model']) && in_array($job['pricing_model'], ['PER_MONTH', 'TOTAL_CONTRACT'])) {
                                $paymentHtml = '<div class="text-sm font-semibold text-gray-900 dark:text-white"><i class="fas fa-file-contract text-blue-500"></i> Sözleşme Dahil</div>';
                                if (!empty($job['recurring_job_id'])) {
                                    $paymentHtml .= '<div class="text-xs text-gray-500 dark:text-gray-400 mt-1"><a href="'.base_url('/recurring/'.$job['recurring_job_id']).'" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">Periyodik İş #'.e($job['recurring_job_id']).'</a></div>';
                                }
                                $paymentHtml .= '<span class="inline-flex mt-2 px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400"><i class="fas fa-info-circle mr-1"></i>Ödeme Periyodik İşten Takip Edilir</span>';
                            } else {
                                $paymentHtml = '<div class="text-sm font-semibold text-gray-900 dark:text-white">'.Utils::formatMoney($job['total_amount'] ?? 0).'</div>';
                                $paymentHtml .= '<div class="text-xs text-gray-500 dark:text-gray-400">Ödenen: '.Utils::formatMoney($job['amount_paid'] ?? 0).'</div>';
                                $paymentHtml .= '<div class="text-xs text-gray-500 dark:text-gray-400">Kalan: '.Utils::formatMoney($jobRemaining).'</div>';
                                $paymentHtml .= '<span class="inline-flex mt-2 px-2 py-1 text-xs font-semibold rounded-full '.$badgeClass.'">'.$badgeLabel.'</span>';
                            }

                            // Actions (simplified for component migration; original actions preserved on mobile)
                            $actionsHtml = '';
                            if ($canEditJobs) {
                                $actionsHtml = '<div class="flex items-center justify-end space-x-2">'
                                    .'<a href="'.base_url("/jobs/manage/{$job['id']}").'" class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-150 p-1 rounded" title="Yönet"><i class="fas fa-cog text-sm"></i></a>'
                                    .'<a href="'.base_url("/jobs/edit/{$job['id']}").'" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-150 p-1 rounded" title="Düzenle"><i class="fas fa-edit text-sm"></i></a>'
                                    .'</div>';
                            } else {
                                $actionsHtml = '<div class="text-right text-sm text-gray-400 dark:text-gray-500"><i class="fas fa-eye mr-1"></i>Görüntüleme</div>';
                            }

                            $row = [];
                            if ($canBulkManage) { $row['select'] = $selectHtml; }
                            $row['date'] = $dateHtml;
                            $row['customer'] = $customerHtml;
                            $row['service'] = $serviceHtml;
                            $row['status'] = $statusHtml;
                            $row['payment'] = $paymentHtml;
                            $row['actions'] = $actionsHtml;
                            $rows[] = $row;
                        }

                        $dense = false;
                        include __DIR__ . '/../partials/ui/table.php';
                        ?>
                    </div>

                    <div class="lg:hidden divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($jobs as $job): ?>
                            <?php
                                $jobRemaining = max(0, (float)($job['total_amount'] ?? 0) - (float)($job['amount_paid'] ?? 0));
                                $paymentStatus = $job['payment_status'] ?? 'UNPAID';
                                [$badgeClass, $badgeLabel] = $paymentBadgeMap[$paymentStatus] ?? ['bg-gray-100 text-gray-800', $paymentStatus];
                            ?>
                            <div class="p-4 sm:p-6 space-y-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                <!-- ===== KOZMOS_BULK_OPERATIONS: add mobile checkbox (begin) -->
                                <?php if ($canBulkManage): ?>
                                <div class="flex items-center justify-between">
                                    <input type="checkbox" name="job_ids[]" value="<?= $job['id'] ?>" class="job-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <div class="text-lg font-semibold text-gray-900 dark:text-white"><?= e($job['customer_name']) ?></div>
                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?= $job['status'] === 'DONE' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : ($job['status'] === 'CANCELLED' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400') ?>">
                                        <?= $job['status'] === 'DONE' ? 'Tamamlandı' : ($job['status'] === 'CANCELLED' ? 'İptal' : 'Planlandı') ?>
                                    </span>
                                </div>
                                <?php else: ?>
                                <div class="flex items-center justify-between">
                                    <div class="text-lg font-semibold text-gray-900 dark:text-white"><?= e($job['customer_name']) ?></div>
                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?= $job['status'] === 'DONE' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : ($job['status'] === 'CANCELLED' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400') ?>">
                                        <?= $job['status'] === 'DONE' ? 'Tamamlandı' : ($job['status'] === 'CANCELLED' ? 'İptal' : 'Planlandı') ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <!-- ===== KOZMOS_BULK_OPERATIONS: add mobile checkbox (end) -->
                                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar mr-2 text-primary-500"></i>
                                        <?= Utils::formatDateTime($job['start_at'], 'd.m.Y H:i') ?>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-cogs mr-2 text-primary-500"></i>
                                        <?= e($job['service_name'] ?? 'Belirtilmemiş') ?>
                                    </div>
                                </div>
                                <?php if (!empty($job['recurring_job_id']) && !empty($job['pricing_model']) && in_array($job['pricing_model'], ['PER_MONTH', 'TOTAL_CONTRACT'])): ?>
                                    <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Ücret Durumu</div>
                                        <div class="font-semibold text-blue-700 dark:text-blue-400">
                                            <i class="fas fa-file-contract mr-1"></i> Sözleşme Dahil
                                        </div>
                                        <?php if (!empty($job['recurring_job_id'])): ?>
                                            <div class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                                                <a href="<?= base_url("/recurring/{$job['recurring_job_id']}") ?>" class="hover:underline">
                                                    Periyodik İş #<?= $job['recurring_job_id'] ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <div class="mt-2">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                                <i class="fas fa-info-circle mr-1"></i>Ödeme Periyodik İşten Takip Edilir
                                            </span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="grid grid-cols-3 gap-3 text-sm">
                                        <div class="text-center p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Toplam</div>
                                            <div class="font-semibold text-gray-900 dark:text-white"><?= Utils::formatMoney($job['total_amount'] ?? 0) ?></div>
                                        </div>
                                        <div class="text-center p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Ödenen</div>
                                            <div class="font-semibold text-green-600 dark:text-green-400"><?= Utils::formatMoney($job['amount_paid'] ?? 0) ?></div>
                                        </div>
                                        <div class="text-center p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Kalan</div>
                                            <div class="font-semibold text-orange-600 dark:text-orange-400"><?= Utils::formatMoney($jobRemaining) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <?= Utils::formatDateTime($job['start_at'], 'H:i') ?> - <?= Utils::formatDateTime($job['end_at'], 'H:i') ?>
                                    </div>
                                </div>
                                <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (begin) -->
                                <?php if ($canEditJobs): ?>
                                <div class="flex items-center justify-end space-x-4 pt-2 border-t border-gray-200 dark:border-gray-600">
                                    <a href="<?= base_url("/jobs/manage/{$job['id']}") ?>" 
                                       class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-150" 
                                       title="Yönet">
                                        <i class="fas fa-cog"></i>
                                    </a>
                                    <a href="<?= base_url("/jobs/edit/{$job['id']}") ?>" 
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-150" 
                                       title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($job['status'] === 'DONE' && $paymentStatus !== 'PAID'): ?>
                                        <button class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 transition-colors duration-150" 
                                                title="Gelir oluştur" 
                                                onclick="openIncomeModal(<?= (int)$job['id'] ?>, '<?= e($job['customer_name']) ?>', '<?= e($job['service_name'] ?? '') ?>', <?= $jobRemaining ?>)">
                                            <i class="fas fa-money-bill"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="flex items-center justify-end pt-2 border-t border-gray-200 dark:border-gray-600">
                                    <div class="text-gray-400 dark:text-gray-500 text-sm">
                                        <i class="fas fa-eye mr-1"></i>
                                        Görüntüleme
                                    </div>
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

<!-- Sticky Bulk Selection Bar -->
<?php if ($canBulkManage): ?>
<div id="stickyBulkBar" class="fixed bottom-0 left-0 right-0 bg-primary-600 text-white shadow-2xl z-40 hidden">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <i class="fas fa-check-circle text-xl"></i>
            <span class="font-medium"><span id="stickyCount">0</span> öğe seçildi</span>
        </div>
        <div class="flex items-center space-x-2">
            <button onclick="selectAllJobs()" class="px-3 py-1.5 bg-primary-500 hover:bg-primary-400 rounded text-sm font-medium transition-colors">
                <i class="fas fa-check-double mr-1"></i>Tümünü Seç
            </button>
            <button onclick="bulkStatusUpdate()" id="stickyStatusBtn" class="px-4 py-1.5 bg-blue-500 hover:bg-blue-400 rounded text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                <i class="fas fa-edit mr-1"></i>Durum Güncelle
            </button>
            <button onclick="bulkDelete()" id="stickyDeleteBtn" class="px-4 py-1.5 bg-red-500 hover:bg-red-400 rounded text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                <i class="fas fa-trash mr-1"></i>Sil
            </button>
            <button onclick="cancelBulkMode()" class="px-3 py-1.5 bg-primary-500 hover:bg-primary-400 rounded text-sm font-medium transition-colors">
                <i class="fas fa-times mr-1"></i>İptal
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function maybeAskCancelReason(form) {
    const sel = form.querySelector('select[name="status"]');
    if (sel && sel.value === 'CANCELLED') {
        const reason = prompt('İptal nedeni (opsiyonel):');
        if (reason !== null) {
            form.querySelector('input[name="cancel_reason"]').value = reason;
        }
    }
    return true;
}
function openIncomeModal(jobId, customerName, serviceName, remaining) {
    document.getElementById('income_job_id').value = jobId;
    const info = `#${jobId} · ${customerName}${serviceName ? ' · ' + serviceName : ''}`;
    document.getElementById('income_job_info').textContent = info;
    const amountInput = document.getElementById('income_amount');
    if (amountInput && !isNaN(remaining) && remaining > 0) {
        amountInput.value = Number(remaining).toFixed(2);
    }
    const modal = document.getElementById('incomeModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeIncomeModal() {
    const modal = document.getElementById('incomeModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// ===== KOZMOS_BULK_OPERATIONS: bulk operations JavaScript (begin)
let lastCheckedIndex = -1;
let bulkModeActive = false;

function toggleBulkMode() {
    const bulkToggleBtn = document.getElementById('bulkToggleBtn');
    const bulkActions = document.getElementById('bulkActions');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    
    if (bulkActions.classList.contains('hidden')) {
        bulkActions.classList.remove('hidden');
        bulkActions.classList.add('flex');
        bulkToggleBtn.innerHTML = '<i class="fas fa-times mr-2"></i>İptal';
        bulkToggleBtn.onclick = cancelBulkMode;
        bulkModeActive = true;
        
        // Show checkboxes
        document.querySelectorAll('.job-checkbox').forEach(checkbox => {
            checkbox.style.display = 'block';
        });
        if (selectAllCheckbox) {
            selectAllCheckbox.style.display = 'block';
        }
        
        // Show sticky selection bar
        showStickySelectionBar();
        
        // Add keyboard shortcut
        document.addEventListener('keydown', handleBulkKeyboard);
    }
}

function showStickySelectionBar() {
    // Sticky bar already exists or will be created dynamically
    updateSelectionCounter();
}

function handleBulkKeyboard(e) {
    if (!bulkModeActive) return;
    
    // Ctrl+A: Select all
    if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
        e.preventDefault();
        selectAllJobs();
    }
    
    // Escape: Cancel bulk mode
    if (e.key === 'Escape') {
        cancelBulkMode();
    }
}

function handleRowClick(e, row) {
    if (!bulkModeActive) return;
    
    // Don't trigger on button/link clicks
    if (e.target.closest('a, button, input')) return;
    
    const checkbox = row.querySelector('.job-checkbox');
    if (!checkbox) return;
    
    // Shift+Click: Range selection
    if (e.shiftKey && lastCheckedIndex !== -1) {
        const rows = Array.from(document.querySelectorAll('.bulk-selectable'));
        const currentIndex = rows.indexOf(row);
        const start = Math.min(lastCheckedIndex, currentIndex);
        const end = Math.max(lastCheckedIndex, currentIndex);
        
        rows.slice(start, end + 1).forEach(r => {
            const cb = r.querySelector('.job-checkbox');
            if (cb) cb.checked = true;
        });
    } else {
        // Toggle this checkbox
        checkbox.checked = !checkbox.checked;
        lastCheckedIndex = Array.from(document.querySelectorAll('.bulk-selectable')).indexOf(row);
    }
    
    updateBulkButtonStates();
    updateSelectionCounter();
}

function updateSelectionCounter() {
    const count = getSelectedJobs().length;
    const counter = document.getElementById('selectedCount');
    if (counter) {
        counter.textContent = count;
    }
    
    // Show/hide sticky bar
    const stickyBar = document.getElementById('stickyBulkBar');
    if (stickyBar) {
        if (count > 0) {
            stickyBar.classList.remove('hidden');
            stickyBar.querySelector('#stickyCount').textContent = count;
        } else {
            stickyBar.classList.add('hidden');
        }
    }
    
    // Update sticky bar buttons
    const stickyStatusBtn = document.getElementById('stickyStatusBtn');
    const stickyDeleteBtn = document.getElementById('stickyDeleteBtn');
    if (stickyStatusBtn) stickyStatusBtn.disabled = count === 0;
    if (stickyDeleteBtn) stickyDeleteBtn.disabled = count === 0;
}

// Initialize selection counter on page load
document.addEventListener('DOMContentLoaded', function() {
    updateBulkButtonStates();
});

function cancelBulkMode() {
    const bulkToggleBtn = document.getElementById('bulkToggleBtn');
    const bulkActions = document.getElementById('bulkActions');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    
    bulkActions.classList.add('hidden');
    bulkActions.classList.remove('flex');
    bulkToggleBtn.innerHTML = '<i class="fas fa-check-square mr-2"></i>Toplu İşlem';
    bulkToggleBtn.onclick = toggleBulkMode;
    bulkModeActive = false;
    lastCheckedIndex = -1;
    
    // Hide checkboxes
    document.querySelectorAll('.job-checkbox').forEach(checkbox => {
        checkbox.style.display = 'none';
        checkbox.checked = false;
    });
    if (selectAllCheckbox) {
        selectAllCheckbox.style.display = 'none';
        selectAllCheckbox.checked = false;
    }
    
    // Hide sticky bar
    const stickyBar = document.getElementById('stickyBulkBar');
    if (stickyBar) {
        stickyBar.classList.add('hidden');
    }
    
    // Remove keyboard listener
    document.removeEventListener('keydown', handleBulkKeyboard);
}

function toggleAllJobs(checkbox) {
    const jobCheckboxes = document.querySelectorAll('.job-checkbox');
    jobCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkButtonStates();
    updateSelectionCounter();
}

function selectAllJobs() {
    const jobCheckboxes = document.querySelectorAll('.job-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    
    jobCheckboxes.forEach(cb => {
        cb.checked = true;
    });
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = true;
    }
    updateBulkButtonStates();
    updateSelectionCounter();
}

function getSelectedJobs() {
    const selectedJobs = [];
    document.querySelectorAll('.job-checkbox:checked').forEach(checkbox => {
        selectedJobs.push(checkbox.value);
    });
    return selectedJobs;
}

function updateBulkButtonStates() {
    const count = getSelectedJobs().length;
    const statusBtn = document.getElementById('bulkStatusBtn');
    const deleteBtn = document.getElementById('bulkDeleteBtn');
    
    if (statusBtn) {
        statusBtn.disabled = count === 0;
    }
    if (deleteBtn) {
        deleteBtn.disabled = count === 0;
    }
}

function bulkStatusUpdate() {
    const selectedJobs = getSelectedJobs();
    if (selectedJobs.length === 0) {
        alert('Lütfen en az bir iş seçin.');
        return;
    }
    
    const newStatus = prompt('Yeni durum seçin:\n1. SCHEDULED (Planlandı)\n2. DONE (Tamamlandı)\n3. CANCELLED (İptal)\n\nDurum kodu girin:', 'SCHEDULED');
    if (!newStatus) return;
    
    if (!['SCHEDULED', 'DONE', 'CANCELLED'].includes(newStatus)) {
        alert('Geçersiz durum kodu.');
        return;
    }
    
    if (confirm(`${selectedJobs.length} işin durumunu "${newStatus}" olarak güncellemek istediğinizden emin misiniz?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('/jobs/bulk-status-update') ?>';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= CSRF::get() ?>';
        form.appendChild(csrfToken);
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = newStatus;
        form.appendChild(statusInput);
        
        selectedJobs.forEach(jobId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'job_ids[]';
            input.value = jobId;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

function bulkDelete() {
    const selectedJobs = getSelectedJobs();
    if (selectedJobs.length === 0) {
        alert('Lütfen en az bir iş seçin.');
        return;
    }
    
    if (confirm(`${selectedJobs.length} işi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('/jobs/bulk-delete') ?>';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= CSRF::get() ?>';
        form.appendChild(csrfToken);
        
        selectedJobs.forEach(jobId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'job_ids[]';
            input.value = jobId;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Initialize - hide checkboxes by default
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.job-checkbox').forEach(checkbox => {
        checkbox.style.display = 'none';
    });
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.style.display = 'none';
    }
});
// ===== KOZMOS_BULK_OPERATIONS: bulk operations JavaScript (end)
</script>
