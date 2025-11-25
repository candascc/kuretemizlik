<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <div class="flex-1 min-w-0">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Genel Bakış</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Bugün - <?= Utils::formatDate(date('Y-m-d'), 'd F Y, l') ?></p>
        </div>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide new job button for operator (begin) -->
        <?php if (Auth::role() !== 'OPERATOR'): ?>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="<?= base_url('/jobs/new') ?>" 
               class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                <i class="fas fa-plus mr-2"></i>
                Yeni İş
            </a>
        </div>
        <?php endif; ?>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide new job button for operator (end) -->
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-4 sm:gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Today's Jobs -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-4 sm:p-6 hover:shadow-medium transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-primary-100 dark:bg-primary-900/20 rounded-lg">
                    <i class="fas fa-tasks text-primary-600 dark:text-primary-400 text-xl"></i>
                </div>
                <div class="ml-4 min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 truncate">Bugünkü İşler</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['today']['jobs'] ?? $stats['today_jobs'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <!-- Today's Income -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-4 sm:p-6 hover:shadow-medium transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
                    <i class="fas fa-arrow-up text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4 min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 truncate">Bugünkü Gelir</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= Utils::formatMoney($stats['today']['income'] ?? 0) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Today's Expense -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-4 sm:p-6 hover:shadow-medium transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-red-100 dark:bg-red-900/20 rounded-lg">
                    <i class="fas fa-arrow-down text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <div class="ml-4 min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 truncate">Bugünkü Gider</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= Utils::formatMoney($stats['today']['expense'] ?? 0) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Today's Profit -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-4 sm:p-6 hover:shadow-medium transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
                    <i class="fas fa-chart-line text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <div class="ml-4 min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 truncate">Bugünkü Kar</p>
                    <?php $todayProfit = $stats['today']['profit'] ?? 0; ?>
                    <p class="text-2xl font-bold <?= $todayProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                        <?= Utils::formatMoney($todayProfit) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Weekly & Monthly Stats -->
    <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-2">
        <!-- Weekly Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
            <div class="flex items-center mb-4 sm:mb-6">
                <div class="flex-shrink-0 p-2 bg-blue-100 dark:bg-blue-900/20 rounded-lg mr-3">
                    <i class="fas fa-calendar-week text-blue-600 dark:text-blue-400"></i>
                </div>
                <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">Bu Hafta</h3>
            </div>
            <div class="grid grid-cols-3 gap-2 sm:gap-4">
                <div class="text-center p-3 sm:p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="text-lg sm:text-2xl font-bold text-green-600 dark:text-green-400"><?= Utils::formatMoney($stats['week']['income'] ?? $stats['week_income'] ?? 0) ?></div>
                    <div class="text-xs sm:text-sm font-semibold text-gray-600 dark:text-gray-400">Gelir</div>
                </div>
                <div class="text-center p-3 sm:p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <div class="text-lg sm:text-2xl font-bold text-red-600 dark:text-red-400"><?= Utils::formatMoney($stats['week']['expense'] ?? 0) ?></div>
                    <div class="text-xs sm:text-sm font-semibold text-gray-600 dark:text-gray-400">Gider</div>
                </div>
                <div class="text-center p-3 sm:p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <?php $weekProfit = $stats['week']['profit'] ?? 0; ?>
                    <div class="text-lg sm:text-2xl font-bold <?= $weekProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                        <?= Utils::formatMoney($weekProfit) ?>
                    </div>
                    <div class="text-xs sm:text-sm font-semibold text-gray-600 dark:text-gray-400">Kar</div>
                </div>
            </div>
        </div>
        
        <!-- Monthly Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
            <div class="flex items-center mb-4 sm:mb-6">
                <div class="flex-shrink-0 p-2 bg-purple-100 dark:bg-purple-900/20 rounded-lg mr-3">
                    <i class="fas fa-calendar text-purple-600 dark:text-purple-400"></i>
                </div>
                <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">Bu Ay</h3>
            </div>
            <div class="grid grid-cols-3 gap-2 sm:gap-4">
                <div class="text-center p-3 sm:p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="text-lg sm:text-2xl font-bold text-green-600 dark:text-green-400"><?= Utils::formatMoney($stats['month']['income']) ?></div>
                    <div class="text-xs sm:text-sm font-semibold text-gray-600 dark:text-gray-400">Gelir</div>
                </div>
                <div class="text-center p-3 sm:p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <div class="text-lg sm:text-2xl font-bold text-red-600 dark:text-red-400"><?= Utils::formatMoney($stats['month']['expense']) ?></div>
                    <div class="text-xs sm:text-sm font-semibold text-gray-600 dark:text-gray-400">Gider</div>
                </div>
                <div class="text-center p-3 sm:p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <div class="text-lg sm:text-2xl font-bold <?= $stats['month']['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                        <?= Utils::formatMoney($stats['month']['profit']) ?>
                    </div>
                    <div class="text-xs sm:text-sm font-semibold text-gray-600 dark:text-gray-400">Kar</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Today's Jobs -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <div class="p-4 sm:p-6">
            <div class="flex items-center mb-4 sm:mb-6">
                <div class="flex-shrink-0 p-2 bg-primary-100 dark:bg-primary-900/20 rounded-lg mr-3">
                    <i class="fas fa-calendar-day text-primary-600 dark:text-primary-400"></i>
                </div>
                <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">Bugünkü İşler</h3>
            </div>
            
            <?php if (empty($todayJobs)): ?>
                <div class="text-center py-12">
                    <div class="mx-auto w-20 h-20 bg-primary-50 dark:bg-primary-900/20 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-calendar-check text-3xl text-primary-600 dark:text-primary-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Bugün için planlanmış iş yok</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Bugün için yeni bir iş ekleyerek başlayın.</p>
                    <!-- ===== KOZMOS_OPERATOR_READONLY: hide new job button for operator (begin) -->
                    <?php if (Auth::role() !== 'OPERATOR'): ?>
                    <a href="<?= base_url('/jobs/new') ?>" 
                       class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-plus mr-2"></i>
                        Yeni İş Ekle
                    </a>
                    <?php endif; ?>
                    <!-- ===== KOZMOS_OPERATOR_READONLY: hide new job button for operator (end) -->
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    <i class="fas fa-clock mr-2 text-primary-500"></i>
                                    <span class="hidden sm:inline">Saat</span>
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    <i class="fas fa-user mr-2 text-primary-500"></i>
                                    Müşteri
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider hidden md:table-cell">
                                    <i class="fas fa-cogs mr-2 text-primary-500"></i>
                                    Hizmet
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    <i class="fas fa-info-circle mr-2 text-primary-500"></i>
                                    <span class="hidden sm:inline">Durum</span>
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    <i class="fas fa-cogs mr-2 text-primary-500"></i>
                                    <span class="hidden sm:inline">İşlemler</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($todayJobs as $job): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                        <div class="flex flex-col">
                                            <span class="text-xs sm:text-sm"><?= Utils::formatDateTime($job['start_at'], 'H:i') ?></span>
                                            <span class="text-xs sm:text-sm"><?= Utils::formatDateTime($job['end_at'], 'H:i') ?></span>
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white truncate max-w-[150px] sm:max-w-none"><?= e($job['customer_name']) ?></div>
                                        <?php if ($job['customer_phone']): ?>
                                            <div class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 truncate max-w-[150px] sm:max-w-none"><?= e($job['customer_phone']) ?></div>
                                        <?php endif; ?>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 md:hidden mt-1">
                                            <i class="fas fa-cogs mr-1"></i><?= htmlspecialchars($job['service_name'] ?? 'Belirtilmemiş') ?>
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300 hidden md:table-cell">
                                        <?= htmlspecialchars($job['service_name'] ?? 'Belirtilmemiş') ?>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 sm:px-3 py-1 text-xs font-semibold rounded-full <?= $job['status'] === 'DONE' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : ($job['status'] === 'CANCELLED' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400') ?>">
                                            <span class="hidden sm:inline"><?= $job['status'] === 'DONE' ? 'Tamamlandı' : ($job['status'] === 'CANCELLED' ? 'İptal' : 'Planlandı') ?></span>
                                            <span class="sm:hidden"><?= $job['status'] === 'DONE' ? '✓' : ($job['status'] === 'CANCELLED' ? '✗' : '○') ?></span>
                                        </span>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <!-- ===== KOZMOS_OPERATOR_READONLY: hide edit buttons for operator (begin) -->
                                        <?php if (Auth::role() !== 'OPERATOR'): ?>
                                        <div class="flex space-x-2 sm:space-x-3">
                                            <a href="<?= base_url("/jobs/edit/{$job['id']}") ?>" 
                                               class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-150 p-1 rounded" 
                                               title="Düzenle">
                                                <i class="fas fa-edit text-sm sm:text-base"></i>
                                            </a>
                                            <?php if ($job['status'] === 'DONE'): ?>
                                                <a href="<?= base_url("/finance/from-job/{$job['id']}") ?>" 
                                                   class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 transition-colors duration-150 p-1 rounded" 
                                                   title="Gelir Oluştur">
                                                    <i class="fas fa-money-bill text-sm sm:text-base"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <div class="text-gray-400 dark:text-gray-500 text-sm">
                                            <i class="fas fa-eye mr-1"></i>
                                            Görüntüleme
                                        </div>
                                        <?php endif; ?>
                                        <!-- ===== KOZMOS_OPERATOR_READONLY: hide edit buttons for operator (end) -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Upcoming Jobs -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-soft">
                        <i class="fas fa-calendar-alt text-white text-sm sm:text-lg"></i>
                    </div>
                </div>
                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">Yaklaşan İşler</h3>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Gelecek 3 gün içindeki planlanmış işler</p>
                </div>
            </div>
        </div>
        <div class="p-4 sm:p-6">
            <?php if (empty($upcomingJobs)): ?>
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-calendar-times text-2xl text-gray-400 dark:text-gray-500"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Yaklaşan İş Yok</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">Gelecek 3 gün için planlanmış iş bulunmuyor.</p>
                    <!-- ===== KOZMOS_OPERATOR_READONLY: hide new job button for operator (begin) -->
                    <?php if (Auth::role() !== 'OPERATOR'): ?>
                    <a href="<?= base_url('/jobs/new') ?>" 
                       class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-plus mr-2"></i>
                        Yeni İş Ekle
                    </a>
                    <?php endif; ?>
                    <!-- ===== KOZMOS_OPERATOR_READONLY: hide new job button for operator (end) -->
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    <i class="fas fa-calendar mr-2 text-primary-500"></i>
                                    <span class="hidden sm:inline">Tarih</span>
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    <i class="fas fa-clock mr-2 text-primary-500"></i>
                                    <span class="hidden sm:inline">Saat</span>
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    <i class="fas fa-user mr-2 text-primary-500"></i>
                                    Müşteri
                                </th>
                                <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider hidden md:table-cell">
                                    <i class="fas fa-cogs mr-2 text-primary-500"></i>
                                    Hizmet
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach (array_slice($upcomingJobs, 0, 5) as $job): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                        <div class="flex flex-col">
                                            <span class="text-xs sm:text-sm"><?= Utils::formatDate($job['start_at'], 'd.m') ?></span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400"><?= Utils::formatDate($job['start_at'], 'Y') ?></span>
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                        <div class="flex flex-col">
                                            <span class="text-xs sm:text-sm"><?= Utils::formatDateTime($job['start_at'], 'H:i') ?></span>
                                            <span class="text-xs sm:text-sm"><?= Utils::formatDateTime($job['end_at'], 'H:i') ?></span>
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white truncate max-w-[150px] sm:max-w-none"><?= e($job['customer_name']) ?></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 md:hidden mt-1">
                                            <i class="fas fa-cogs mr-1"></i><?= htmlspecialchars($job['service_name'] ?? 'Belirtilmemiş') ?>
                                        </div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300 hidden md:table-cell">
                                        <?= htmlspecialchars($job['service_name'] ?? 'Belirtilmemiş') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($upcomingJobs) > 5): ?>
                    <div class="mt-6 text-center">
                        <a href="<?= base_url('/jobs') ?>" 
                           class="inline-flex items-center px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <i class="fas fa-list mr-2"></i>
                            Tümünü Görüntüle (<?= count($upcomingJobs) ?> iş)
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
</div>
<script>
// Background recurring generator (idempotent, silent)
try {
  fetch('<?= base_url('/api/recurring/preview') ?>?frequency=DAILY&interval=1&start_date=<?= json_encode(date('Y-m-d')) ?>&limit=0').catch(()=>{});
} catch (e) {}
</script>