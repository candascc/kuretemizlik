<div class="space-y-8">
            <!-- Header Section -->
            <div>
                <?php if (Auth::role() !== 'OPERATOR') { ob_start(); ?>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="<?= base_url('/appointments/new') ?>" 
                       class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-plus mr-2"></i> 
                        Yeni Randevu
                    </a>
                </div>
                <?php $rightActionsHtml = ob_get_clean(); } else { $rightActionsHtml = ''; } ?>
                <?php 
                $title = '<i class="fas fa-calendar-day mr-3 text-primary-600"></i>Randevular';
                $subtitle = 'Randevu yönetimi ve takibi';
                include __DIR__ . '/../partials/ui/list-header.php';
                ?>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                            <i class="fas fa-calendar-day text-blue-600 dark:text-blue-400 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Bugün</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['today'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
                            <i class="fas fa-calendar-week text-green-600 dark:text-green-400 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Bu Hafta</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['this_week'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
                            <i class="fas fa-calendar-alt text-purple-600 dark:text-purple-400 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Bu Ay</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['this_month'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="p-3 bg-indigo-100 dark:bg-indigo-900/20 rounded-lg">
                            <i class="fas fa-calendar text-indigo-600 dark:text-indigo-400 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Toplam</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['total'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Form -->
            <?php
            $fields = [
                ['type' => 'select', 'name' => 'status', 'label' => 'Durum', 'icon' => 'fas fa-filter', 'options' => $statuses, 'value' => $filters['status'] ?? ''],
                ['type' => 'text', 'name' => 'customer', 'label' => 'Müşteri', 'icon' => 'fas fa-user', 'value' => $filters['customer'] ?? '', 'placeholder' => 'Müşteri ara...'],
                ['type' => 'date', 'name' => 'date_from', 'label' => 'Başlangıç Tarihi', 'icon' => 'fas fa-calendar', 'value' => $filters['date_from'] ?? ''],
                ['type' => 'date', 'name' => 'date_to', 'label' => 'Bitiş Tarihi', 'icon' => 'fas fa-calendar', 'value' => $filters['date_to'] ?? ''],
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

            <!-- Main Content -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
                <?php if (empty($appointments)): ?>
                    <div class="p-16 text-center">
                        <div class="mx-auto w-20 h-20 flex items-center justify-center rounded-full bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 mb-6">
                            <i class="fas fa-calendar-plus text-3xl"></i>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Henüz randevu yok</div>
                        <div class="text-gray-500 dark:text-gray-400 mb-6">Yeni bir randevu ekleyin.</div>
                        <!-- ===== KOZMOS_OPERATOR_READONLY: hide empty state button for operator (begin) -->
                        <?php if (Auth::role() !== 'OPERATOR'): ?>
                        <a href="<?= base_url('/appointments/new') ?>" class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg shadow-soft hover:shadow-medium transition-all duration-200 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <i class="fas fa-plus mr-2"></i> 
                            Yeni Randevu
                        </a>
                        <?php endif; ?>
                        <!-- ===== KOZMOS_OPERATOR_READONLY: hide empty state button for operator (end) -->
                    </div>
                <?php else: ?>
                    <!-- Desktop Table View -->
                    <div class="hidden md:block overflow-x-auto">
                        <?php
                        $headers = [
                            'date' => ['label' => '<i class="fas fa-calendar mr-2 text-primary-500"></i>Tarih', 'raw' => true],
                            'customer' => ['label' => '<i class="fas fa-user mr-2 text-primary-500"></i>Müşteri', 'raw' => true],
                            'title' => ['label' => '<i class="fas fa-heading mr-2 text-primary-500"></i>Başlık', 'raw' => true],
                            'service' => ['label' => '<i class="fas fa-briefcase mr-2 text-primary-500"></i>Hizmet', 'raw' => true],
                            'status' => ['label' => '<i class="fas fa-info-circle mr-2 text-primary-500"></i>Durum', 'raw' => true],
                            'actions' => ['label' => '<div class="text-left"><i class="fas fa-cogs mr-2 text-primary-500"></i>İşlemler</div>', 'raw' => true],
                        ];
                        $rows = [];
                        foreach ($appointments as $appointment) {
                            $dateHtml = '<div class="font-semibold">'.Utils::formatDate($appointment['appointment_date']).'</div>'
                                .'<div class="text-gray-500 dark:text-gray-400">'.e($appointment['start_time']).' - '.htmlspecialchars($appointment['end_time'] ?? 'Belirtilmemiş').'</div>';
                            $customerHtml = '<div class="font-semibold">'.e($appointment['customer_name']).'</div>';
                            if (!empty($appointment['customer_phone'])) { $customerHtml .= '<div class="text-gray-500 dark:text-gray-400">'.e($appointment['customer_phone']).'</div>'; }
                            $titleHtml = '<div class="font-semibold">'.e($appointment['title']).'</div>';
                            if (!empty($appointment['description'])) {
                                $short = substr($appointment['description'], 0, 50);
                                if (strlen($appointment['description']) > 50) { $short .= '...'; }
                                $titleHtml .= '<div class="text-gray-500 dark:text-gray-400">'.e($short).'</div>';
                            }
                            $serviceHtml = htmlspecialchars($appointment['service_name'] ?? 'Belirtilmemiş');
                            $statusColors = [
                                'SCHEDULED' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
                                'CONFIRMED' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
                                'COMPLETED' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                'CANCELLED' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
                                'NO_SHOW' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
                            ];
                            $colorClass = $statusColors[$appointment['status']] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
                            $statusHtml = '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full '.$colorClass.'">'.($statuses[$appointment['status']] ?? $appointment['status']).'</span>';
                            if (Auth::role() !== 'OPERATOR') {
                                $actionsHtml = '<div class="flex items-center space-x-2">'
                                    .'<a href="'.base_url("/appointments/{$appointment['id']}").'" class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-150 p-1 rounded" title="Görüntüle"><i class="fas fa-eye text-sm"></i></a>'
                                    .'<a href="'.base_url("/appointments/{$appointment['id']}/edit").'" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-150 p-1 rounded" title="Düzenle"><i class="fas fa-edit text-sm"></i></a>'
                                    .'<form method="POST" action="'.base_url("/appointments/{$appointment['id']}/delete").'" class="inline" onsubmit="return confirm(\'Bu randevuyu silmek istediğinizden emin misiniz?\')">'.CSRF::field().'<button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors duration-150 p-1 rounded" title="Sil"><i class="fas fa-trash text-sm"></i></button></form>'
                                    .'</div>';
                            } else {
                                $actionsHtml = '<div class="flex items-center"><a href="'.base_url("/appointments/{$appointment['id']}").'" class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-150 p-1 rounded" title="Görüntüle"><i class="fas fa-eye text-sm"></i></a></div>';
                            }
                            $rows[] = [
                                'date' => $dateHtml,
                                'customer' => $customerHtml,
                                'title' => $titleHtml,
                                'service' => $serviceHtml,
                                'status' => $statusHtml,
                                'actions' => $actionsHtml,
                            ];
                        }
                        include __DIR__ . '/../partials/ui/table.php';
                        ?>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="md:hidden space-y-4 p-4">
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-soft border border-gray-200 dark:border-gray-700 p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= e($appointment['title']) ?></h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= e($appointment['customer_name']) ?></p>
                                    </div>
                                    <?php
                                    $statusColors = [
                                        'SCHEDULED' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
                                        'CONFIRMED' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
                                        'COMPLETED' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                        'CANCELLED' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
                                        'NO_SHOW' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
                                    ];
                                    $colorClass = $statusColors[$appointment['status']] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
                                    ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $colorClass ?>">
                                        <?= $statuses[$appointment['status']] ?? $appointment['status'] ?>
                                    </span>
                                </div>
                                
                                <div class="space-y-2 mb-4">
                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-calendar mr-2 text-primary-500"></i>
                                        <?= Utils::formatDate($appointment['appointment_date']) ?>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-clock mr-2 text-primary-500"></i>
                                        <?= $appointment['start_time'] ?> - <?= $appointment['end_time'] ?? 'Belirtilmemiş' ?>
                                    </div>
                                    <?php if ($appointment['customer_phone']): ?>
                                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-phone mr-2 text-primary-500"></i>
                                            <?= e($appointment['customer_phone']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <i class="fas fa-briefcase mr-2 text-primary-500"></i>
                                        <?= htmlspecialchars($appointment['service_name'] ?? 'Belirtilmemiş') ?>
                                    </div>
                                </div>
                                
                                <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (begin) -->
                                <?php if (Auth::role() !== 'OPERATOR'): ?>
                                <div class="flex space-x-2">
                                    <a href="<?= base_url("/appointments/{$appointment['id']}") ?>" 
                                       class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
                                        <i class="fas fa-eye mr-2"></i>
                                        Görüntüle
                                    </a>
                                    <a href="<?= base_url("/appointments/{$appointment['id']}/edit") ?>" 
                                       class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150">
                                        <i class="fas fa-edit mr-2"></i>
                                        Düzenle
                                    </a>
                                    <form method="POST" action="<?= base_url("/appointments/{$appointment['id']}/delete") ?>" class="flex-1" onsubmit="return confirm('Bu randevuyu silmek istediğinizden emin misiniz?')">
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
                                    <a href="<?= base_url("/appointments/{$appointment['id']}") ?>" 
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

                    <!-- Pagination -->
                    <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($pagination['current_page'] > 1): ?>
                                    <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Önceki
                                    </a>
                                <?php endif; ?>
                                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                    <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Sonraki
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        <span class="font-medium"><?= $pagination['start'] ?></span>
                                        -
                                        <span class="font-medium"><?= $pagination['end'] ?></span>
                                        arası, toplam
                                        <span class="font-medium"><?= $pagination['total'] ?></span>
                                        sonuçtan
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <?php if ($pagination['current_page'] > 1): ?>
                                            <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                                            <a href="?page=<?= $i ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?= $i === $pagination['current_page'] ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?>">
                                                <?= $i ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                            <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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