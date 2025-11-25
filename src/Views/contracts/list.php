<div class="space-y-8">
            <!-- Header Section -->
            <div>
                <?php ob_start(); ?>
                <!-- ===== KOZMOS_OPERATOR_READONLY: hide new contract button for operator (begin) -->
                <?php if (Auth::role() !== 'OPERATOR'): ?>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="<?= base_url('/contracts/expiring') ?>" 
                       class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg shadow-soft hover:shadow-medium transition-all duration-200 focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-exclamation-triangle mr-2"></i> 
                        Süresi Yaklaşanlar
                    </a>
                    <a href="<?= base_url('/contracts/new') ?>" 
                       class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg shadow-soft hover:shadow-medium transition-all duration-200 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                       style="background:#2563eb; color:#fff;">
                        <i class="fas fa-file-contract mr-2"></i> 
                        Yeni Sözleşme
                    </a>
                    <!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations for contracts (begin) -->
                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="toggleBulkMode()" id="bulkToggleBtn" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-check-square mr-2"></i>
                            Toplu İşlem
                        </button>
                        <div id="bulkActions" class="hidden flex items-center space-x-2">
                            <button type="button" onclick="selectAllContracts()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-200">
                                <i class="fas fa-check-double mr-1"></i>
                                Tümünü Seç
                            </button>
                            <button type="button" onclick="bulkStatusUpdateContracts()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                                <i class="fas fa-edit mr-1"></i>
                                Durum Güncelle
                            </button>
                            <button type="button" onclick="bulkDeleteContracts()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors duration-200">
                                <i class="fas fa-trash mr-1"></i>
                                Toplu Sil
                            </button>
                            <button type="button" onclick="cancelBulkMode()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-200">
                                <i class="fas fa-times mr-1"></i>
                                İptal
                            </button>
                        </div>
                    </div>
                    <!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations for contracts (end) -->
                </div>
                <?php else: ?>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="<?= base_url('/contracts/expiring') ?>" 
                       class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg shadow-soft hover:shadow-medium transition-all duration-200 focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-exclamation-triangle mr-2"></i> 
                        Süresi Yaklaşanlar
                    </a>
                </div>
                <?php endif; ?>
                <!-- ===== KOZMOS_OPERATOR_READONLY: hide new contract button for operator (end) -->
                <?php $rightActionsHtml = ob_get_clean(); ?>

                <?php
                $title = '<i class="fas fa-file-contract mr-3 text-primary-600 dark:text-primary-400"></i>Sözleşmeler';
                $subtitle = 'Sözleşme yönetimi ve takibi';
                include __DIR__ . '/../partials/ui/list-header.php';
                ?>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="p-3 bg-primary-100 dark:bg-primary-900/20 rounded-lg">
                            <i class="fas fa-file-contract text-primary-600 dark:text-primary-400 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Toplam</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= e($stats['total'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
                            <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Aktif</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= e($stats['active'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900/20 rounded-lg">
                            <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Taslak</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= e($stats['draft'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 dark:bg-red-900/20 rounded-lg">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Süresi Yaklaşan</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= e($stats['expiring_soon'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Form (reusable) -->
            <?php
            // Prepare service options for dropdown
            $serviceOptions = ['' => __('contracts.admin.index.filter_services_all')];
            foreach ($services ?? [] as $service) {
                $serviceOptions[$service['id']] = e($service['name']);
            }
            
            $fields = [
                ['type' => 'select', 'name' => 'status', 'label' => __('contracts.admin.index.filter_status'), 'icon' => 'fas fa-filter', 'options' => $statuses, 'value' => $filters['status'] ?? ''],
                ['type' => 'select', 'name' => 'contract_type', 'label' => __('contracts.admin.index.filter_type'), 'icon' => 'fas fa-tag', 'options' => $types, 'value' => $filters['contract_type'] ?? ''],
                ['type' => 'text', 'name' => 'customer', 'label' => __('contracts.admin.index.filter_customer'), 'icon' => 'fas fa-user', 'value' => $filters['customer'] ?? '', 'placeholder' => __('contracts.admin.index.filter_customer_placeholder')],
                ['type' => 'date', 'name' => 'date_from', 'label' => __('contracts.admin.index.filter_date_from'), 'icon' => 'fas fa-calendar', 'value' => $filters['date_from'] ?? ''],
                ['type' => 'date', 'name' => 'date_to', 'label' => __('contracts.admin.index.filter_date_to'), 'icon' => 'fas fa-calendar', 'value' => $filters['date_to'] ?? ''],
                // Job contract specific filters
                ['type' => 'select', 'name' => 'job_contract_status', 'label' => __('contracts.admin.index.filter_job_status'), 'icon' => 'fas fa-file-contract', 'options' => $job_contract_statuses ?? [], 'value' => $filters['job_contract_status'] ?? ''],
                ['type' => 'select', 'name' => 'service_id', 'label' => __('contracts.admin.index.filter_service'), 'icon' => 'fas fa-cogs', 'options' => $serviceOptions, 'value' => $filters['service_id'] ?? ''],
                ['type' => 'date', 'name' => 'job_date_from', 'label' => __('contracts.admin.index.filter_job_date_from'), 'icon' => 'fas fa-calendar-alt', 'value' => $filters['job_date_from'] ?? ''],
                ['type' => 'date', 'name' => 'job_date_to', 'label' => __('contracts.admin.index.filter_job_date_to'), 'icon' => 'fas fa-calendar-alt', 'value' => $filters['job_date_to'] ?? ''],
            ];
            $method = 'GET';
            $action = '';
            $submitLabel = __('contracts.admin.index.filter_submit');
            include __DIR__ . '/../partials/ui/list-filters.php';
            ?>
            
            <!-- Job Contracts Only Filter -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="only_job_contracts" value="1" 
                           <?= ($filters['only_job_contracts'] ?? false) ? 'checked' : '' ?>
                           onchange="this.form.submit()"
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        <?= __('contracts.admin.index.filter_only_job_contracts') ?>
                    </span>
                </label>
            </div>

            <!-- Main Content -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
                <?php if (empty($contracts)): ?>
                    <div class="p-16 text-center">
                        <div class="mx-auto w-20 h-20 flex items-center justify-center rounded-full bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 mb-6">
                            <i class="fas fa-file-contract text-3xl"></i>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Henüz sözleşme yok</div>
                        <div class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">İlk sözleşmenizi oluşturarak başlayın. Müşterilerinizle sözleşmelerinizi yönetin ve takip edin.</div>
                        <a href="<?= base_url('/contracts/new') ?>" 
                           class="inline-flex items-center px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg shadow-soft hover:shadow-medium transition-all duration-200 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <i class="fas fa-file-contract mr-3"></i> 
                            İlk Sözleşmeyi Oluştur
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Desktop Table View (using reusable table component) -->
                    <div class="hidden lg:block overflow-x-auto">
                        <?php
                        $headers = [];
                        if (Auth::role() !== 'OPERATOR') {
                            $headers['select'] = [
                                'label' => '<input type="checkbox" id="selectAllCheckbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" onchange="toggleAllContracts(this)">',
                                'raw' => true
                            ];
                        }
                        $headers['number']   = ['label' => '<i class="fas fa-hashtag mr-2 text-primary-500"></i>'.__('contracts.admin.index.column_number'), 'raw' => true];
                        $headers['customer'] = ['label' => '<i class="fas fa-user mr-2 text-primary-500"></i>'.__('contracts.admin.index.column_customer'), 'raw' => true];
                        $headers['title']    = ['label' => '<i class="fas fa-file-alt mr-2 text-primary-500"></i>'.__('contracts.admin.index.column_title'), 'raw' => true];
                        $headers['type']     = ['label' => '<i class="fas fa-tag mr-2 text-primary-500"></i>'.__('contracts.admin.index.column_type'), 'raw' => true];
                        $headers['dates']    = ['label' => '<i class="fas fa-calendar mr-2 text-primary-500"></i>'.__('contracts.admin.index.column_dates'), 'raw' => true];
                        $headers['status']   = ['label' => '<i class="fas fa-info-circle mr-2 text-primary-500"></i>'.__('contracts.admin.index.column_status'), 'raw' => true];
                        $headers['actions']  = ['label' => '<div class="text-left"><i class="fas fa-cogs mr-2 text-primary-500"></i>'.__('contracts.admin.index.column_actions').'</div>', 'raw' => true];

                        $rows = [];
                        foreach ($contracts as $contract) {
                            $selectHtml = '';
                            if (Auth::role() !== 'OPERATOR') {
                                $selectHtml = '<input type="checkbox" name="contract_ids[]" value="'.(int)$contract['id'].'" class="contract-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500">';
                            }

                            // Contract number / Job ID
                            if (!empty($contract['is_job_contract'])) {
                                $numberHtml = '<div class="text-sm font-semibold text-gray-900 dark:text-white">'.e($contract['contract_number']).'</div>';
                                $numberHtml .= '<div class="text-xs text-gray-500 dark:text-gray-400">'.__('contracts.admin.index.job_id').': #'.$contract['job_id'].'</div>';
                            } else {
                                $numberHtml = '<div class="text-sm font-semibold text-gray-900 dark:text-white">'.e($contract['contract_number']).'</div>';
                            }

                            // Customer
                            $customerHtml = '<div class="text-sm font-semibold text-gray-900 dark:text-white">'.htmlspecialchars($contract['customer_name'] ?? '').'</div>';
                            if (!empty($contract['customer_phone'])) {
                                $customerHtml .= '<div class="text-sm text-gray-500 dark:text-gray-400">'.e($contract['customer_phone']).'</div>';
                            }

                            // Title with service name for job contracts
                            $titleHtml = '<div class="text-sm font-semibold text-gray-900 dark:text-white">'.e($contract['title']).'</div>';
                            if (!empty($contract['is_job_contract']) && !empty($contract['service_name'])) {
                                $titleHtml .= '<div class="text-xs text-blue-600 dark:text-blue-400"><i class="fas fa-cogs mr-1"></i>'.e($contract['service_name']).'</div>';
                            }
                            if (!empty($contract['description'])) {
                                $short = substr($contract['description'], 0, 50);
                                if (strlen($contract['description']) > 50) { $short .= '...'; }
                                $titleHtml .= '<div class="text-sm text-gray-500 dark:text-gray-400">'.e($short).'</div>';
                            }

                            // Type
                            $typeHtml = '<div class="text-sm text-gray-600 dark:text-gray-300">'.($types[$contract['contract_type']] ?? $contract['contract_type']).'</div>';

                            // Dates - for job contracts, show job date and contract dates
                            if (!empty($contract['is_job_contract'])) {
                                $datesHtml = '<div class="text-sm font-medium text-gray-900 dark:text-white">'.__('contracts.admin.index.job_date').': '.Utils::formatDateTime($contract['start_date'] ?? '', 'd.m.Y').'</div>';
                                if (!empty($contract['sms_sent_at'])) {
                                    $datesHtml .= '<div class="text-xs text-blue-600 dark:text-blue-400"><i class="fas fa-sms mr-1"></i>'.__('contracts.admin.index.sms_sent').': '.Utils::formatDateTime($contract['sms_sent_at'], 'd.m.Y H:i').'</div>';
                                }
                                if (!empty($contract['approved_at'])) {
                                    $datesHtml .= '<div class="text-xs text-green-600 dark:text-green-400"><i class="fas fa-check mr-1"></i>'.__('contracts.admin.index.approved').': '.Utils::formatDateTime($contract['approved_at'], 'd.m.Y H:i').'</div>';
                                }
                            } else {
                                $datesHtml = '<div class="text-sm text-gray-600 dark:text-gray-300">'.Utils::formatDate($contract['start_date'] ?? '').'</div>';
                                if (!empty($contract['end_date'])) {
                                    $datesHtml .= '<div class="text-sm text-gray-500 dark:text-gray-400">'.Utils::formatDate($contract['end_date']).'</div>';
                                }
                            }

                            // Job contract status mapping (if it's a job contract, use original status for display)
                            $displayStatus = $contract['status'];
                            if (!empty($contract['is_job_contract']) && isset($contract['job_contract_status'])) {
                                $displayStatus = $contract['job_contract_status'];
                            }
                            
                            $statusColors = [
                                'DRAFT' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                'PENDING' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
                                'SENT' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
                                'ACTIVE' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                'APPROVED' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                'SUSPENDED' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
                                'COMPLETED' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
                                'TERMINATED' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
                                'EXPIRED' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
                                'REJECTED' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                            ];
                            
                            // Status labels for job contracts
                            $statusLabels = [
                                'PENDING' => 'Onay bekliyor',
                                'SENT' => 'SMS gönderildi',
                                'APPROVED' => 'Onaylandı',
                                'EXPIRED' => 'Süresi doldu',
                                'REJECTED' => 'Reddedildi'
                            ];
                            
                            $colorClass = $statusColors[$displayStatus] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                            $statusLabel = !empty($contract['is_job_contract']) && isset($statusLabels[$displayStatus]) 
                                ? $statusLabels[$displayStatus] 
                                : ($statuses[$displayStatus] ?? $displayStatus);
                            $statusHtml = '<span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full '.$colorClass.'">'.$statusLabel.'</span>';

                            if (Auth::role() !== 'OPERATOR') {
                                // Check if this is a job contract
                                $isJobContract = !empty($contract['is_job_contract']);
                                $viewUrl = $isJobContract ? base_url("/contract/{$contract['job_contract_id']}") : base_url("/contracts/{$contract['id']}");
                                
                                $actionsHtml = '<div class="flex space-x-3">'
                                    .'<a href="'.$viewUrl.'" class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-150" title="'.__('contracts.admin.index.action_view').'"><i class="fas fa-eye"></i></a>';
                                
                                // Job contracts: add print button and job detail link
                                if ($isJobContract) {
                                    $actionsHtml .= '<a href="'.base_url("/contracts/{$contract['job_contract_id']}/print").'" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-150" title="'.__('contracts.admin.index.action_print').'" target="_blank"><i class="fas fa-print"></i></a>';
                                    $actionsHtml .= '<a href="'.base_url("/jobs/manage/{$contract['job_id']}").'" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 transition-colors duration-150" title="'.__('contracts.admin.index.action_job_detail').'"><i class="fas fa-briefcase"></i></a>';
                                } else {
                                    // Regular contracts: edit and delete
                                    $actionsHtml .= '<a href="'.base_url("/contracts/{$contract['id']}/edit").'" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-150" title="'.__('contracts.admin.index.action_edit').'"><i class="fas fa-edit"></i></a>'
                                        .'<form method="POST" action="'.base_url("/contracts/{$contract['id']}/delete").'" class="inline" onsubmit="return confirm(\''.__('contracts.admin.index.confirm_delete').'\')">'
                                        .CSRF::field()
                                        .'<button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors duration-150" title="'.__('contracts.admin.index.action_delete').'"><i class="fas fa-trash"></i></button>'
                                        .'</form>';
                                }
                                
                                $actionsHtml .= '</div>';
                            } else {
                                $viewUrl = !empty($contract['is_job_contract']) ? base_url("/contract/{$contract['job_contract_id']}") : base_url("/contracts/{$contract['id']}");
                                $actionsHtml = '<div class="flex items-center justify-end"><a href="'.$viewUrl.'" class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-150" title="'.__('contracts.admin.index.action_view').'"><i class="fas fa-eye"></i></a></div>';
                            }

                            $row = [];
                            if (Auth::role() !== 'OPERATOR') { $row['select'] = $selectHtml; }
                            $row['number'] = $numberHtml;
                            $row['customer'] = $customerHtml;
                            $row['title'] = $titleHtml;
                            $row['type'] = $typeHtml;
                            $row['dates'] = $datesHtml;
                            $row['status'] = $statusHtml;
                            $row['actions'] = $actionsHtml;
                            $rows[] = $row;
                        }

                        $dense = false;
                        include __DIR__ . '/../partials/ui/table.php';
                        ?>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="lg:hidden divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($contracts as $contract): ?>
                            <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="text-lg font-semibold text-gray-900 dark:text-white"><?= e($contract['contract_number']) ?></div>
                                    <?php
                                    // Job contract status mapping (if it's a job contract, use original status for display)
                                    $displayStatus = $contract['status'];
                                    if (!empty($contract['is_job_contract']) && isset($contract['job_contract_status'])) {
                                        $displayStatus = $contract['job_contract_status'];
                                    }
                                    
                                    $statusColors = [
                                        'DRAFT' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                        'PENDING' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
                                        'SENT' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
                                        'ACTIVE' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                        'APPROVED' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                        'SUSPENDED' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
                                        'COMPLETED' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
                                        'TERMINATED' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
                                        'EXPIRED' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
                                        'REJECTED' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                                    ];
                                    
                                    // Status labels for job contracts
                                    $statusLabels = [
                                        'PENDING' => 'Onay bekliyor',
                                        'SENT' => 'SMS gönderildi',
                                        'APPROVED' => 'Onaylandı',
                                        'EXPIRED' => 'Süresi doldu',
                                        'REJECTED' => 'Reddedildi'
                                    ];
                                    
                                    $colorClass = $statusColors[$displayStatus] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                    $statusLabel = !empty($contract['is_job_contract']) && isset($statusLabels[$displayStatus]) 
                                        ? $statusLabels[$displayStatus] 
                                        : ($statuses[$displayStatus] ?? $displayStatus);
                                    ?>
                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?= $colorClass ?>">
                                        <?= e($statusLabel) ?>
                                    </span>
                                </div>
                                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                                    <div class="flex items-center">
                                        <i class="fas fa-user mr-3 text-primary-500 w-4"></i>
                                        <?= e($contract['customer_name']) ?>
                                        <?php if ($contract['customer_phone']): ?>
                                            <span class="ml-2 text-gray-500 dark:text-gray-400">(<?= e($contract['customer_phone']) ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-file-alt mr-3 text-primary-500 w-4"></i>
                                        <?= e($contract['title']) ?>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-tag mr-3 text-primary-500 w-4"></i>
                                        <?= e($types[$contract['contract_type']] ?? $contract['contract_type']) ?>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar mr-3 text-primary-500 w-4"></i>
                                        <?= Utils::formatDate($contract['start_date']) ?>
                                        <?php if ($contract['end_date']): ?>
                                            <span class="ml-2">- <?= Utils::formatDate($contract['end_date']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($contract['description']): ?>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                            <?= htmlspecialchars(substr($contract['description'], 0, 100)) ?><?= strlen($contract['description']) > 100 ? '...' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (begin) -->
                                <?php 
                                $isJobContract = !empty($contract['is_job_contract']);
                                $viewUrl = $isJobContract ? base_url("/contract/{$contract['job_contract_id']}") : base_url("/contracts/{$contract['id']}");
                                ?>
                                <?php if (Auth::role() !== 'OPERATOR'): ?>
                                <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                    <a href="<?= e($viewUrl) ?>" 
                                       class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-150" 
                                       title="Görüntüle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (!$isJobContract): ?>
                                        <a href="<?= base_url("/contracts/{$contract['id']}/edit") ?>" 
                                           class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-150" 
                                           title="Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="<?= base_url("/contracts/{$contract['id']}/delete") ?>" class="inline" onsubmit="return confirm('Bu sözleşmeyi silmek istediğinizden emin misiniz?')">
                                            <?= CSRF::field() ?>
                                            <button type="submit" 
                                                    class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors duration-150" 
                                                    title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="<?= base_url("/jobs/manage/{$contract['job_id']}") ?>" 
                                           class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 transition-colors duration-150" 
                                           title="İş Detayı">
                                            <i class="fas fa-briefcase"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-600">
                                    <a href="<?= e($viewUrl) ?>" 
                                       class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-150" 
                                       title="Görüntüle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                                <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (end) -->
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                        <div class="bg-white dark:bg-gray-800 px-6 py-4 flex items-center justify-between border-t border-gray-200 dark:border-gray-700">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($pagination['current_page'] > 1): ?>
                                    <a href="?page=<?= e($pagination['current_page'] - 1) ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
                                        <i class="fas fa-chevron-left mr-2"></i>
                                        Önceki
                                    </a>
                                <?php endif; ?>
                                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                    <a href="?page=<?= e($pagination['current_page'] + 1) ?>" 
                                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
                                        Sonraki
                                        <i class="fas fa-chevron-right ml-2"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-semibold"><?= e($pagination['start']) ?></span>
                                        -
                                        <span class="font-semibold"><?= e($pagination['end']) ?></span>
                                        arası, toplam
                                        <span class="font-semibold"><?= e($pagination['total']) ?></span>
                                        sonuçtan
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-lg shadow-soft -space-x-px" aria-label="Pagination">
                                        <?php if ($pagination['current_page'] > 1): ?>
                                            <a href="?page=<?= e($pagination['current_page'] - 1) ?>" 
                                               class="relative inline-flex items-center px-3 py-2 rounded-l-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                                            <a href="?page=<?= e($i) ?>" 
                                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors duration-150 <?= $i === $pagination['current_page'] ? 'z-10 bg-primary-50 dark:bg-primary-900/20 border-primary-500 dark:border-primary-400 text-primary-600 dark:text-primary-400' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600' ?>">
                                                <?= e($i) ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                            <a href="?page=<?= e($pagination['current_page'] + 1) ?>" 
                                               class="relative inline-flex items-center px-3 py-2 rounded-r-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
</div>

<!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations JavaScript (begin) -->
<script>
function toggleBulkMode() {
    const bulkToggleBtn = document.getElementById('bulkToggleBtn');
    const bulkActions = document.getElementById('bulkActions');
    const checkboxes = document.querySelectorAll('.contract-checkbox');
    
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
    const checkboxes = document.querySelectorAll('.contract-checkbox');
    
    bulkActions.classList.add('hidden');
    bulkToggleBtn.classList.remove('bg-primary-600', 'text-white');
    bulkToggleBtn.classList.add('bg-white', 'text-gray-700');
    checkboxes.forEach(checkbox => {
        checkbox.style.display = 'none';
        checkbox.checked = false;
    });
}

function toggleAllContracts(checkbox) {
    const checkboxes = document.querySelectorAll('.contract-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function selectAllContracts() {
    const checkboxes = document.querySelectorAll('.contract-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = true);
}

function getSelectedContracts() {
    const checkboxes = document.querySelectorAll('.contract-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function bulkStatusUpdateContracts() {
    const selectedIds = getSelectedContracts();
    
    if (selectedIds.length === 0) {
        alert('Lütfen güncellenecek sözleşmeleri seçin.');
        return;
    }
    
    const status = prompt('Yeni durum girin (ACTIVE, EXPIRED, TERMINATED):');
    if (!status || !['ACTIVE', 'EXPIRED', 'TERMINATED'].includes(status.toUpperCase())) {
        alert('Geçerli bir durum girin: ACTIVE, EXPIRED, TERMINATED');
        return;
    }
    
    if (!confirm(`${selectedIds.length} sözleşmenin durumunu "${status.toUpperCase()}" olarak güncellemek istediğinizden emin misiniz?`)) {
        return;
    }
    
    // Form oluştur ve gönder
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('/contracts/bulk-status-update') ?>';
    
    // CSRF token ekle
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = '<?= CSRF::get() ?>';
    form.appendChild(csrfInput);
    
    // Status ekle
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'status';
    statusInput.value = status.toUpperCase();
    form.appendChild(statusInput);
    
    // Seçili ID'leri ekle
    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'contract_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

function bulkDeleteContracts() {
    const selectedIds = getSelectedContracts();
    
    if (selectedIds.length === 0) {
        alert('Lütfen silinecek sözleşmeleri seçin.');
        return;
    }
    
    if (!confirm(`${selectedIds.length} sözleşmeyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`)) {
        return;
    }
    
    // Form oluştur ve gönder
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('/contracts/bulk-delete') ?>';
    
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
        input.name = 'contract_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

// Initialize - hide checkboxes by default
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.contract-checkbox');
    checkboxes.forEach(checkbox => checkbox.style.display = 'none');
});
</script>
<!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations JavaScript (end) -->