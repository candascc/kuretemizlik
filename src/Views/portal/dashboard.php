<?php require __DIR__ . '/layout/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">
        <?= __('welcome') ?>, <?= htmlspecialchars($customer['name'] ?? '') ?>! 👋
    </h1>

    <!-- Flash Messages -->
    <?php $flash = Utils::getFlash(); ?>
    <?php if (!empty($flash) && is_array($flash)): ?>
        <?php if (!empty($flash['success'])): ?>
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-sm font-medium text-green-800 dark:text-green-300">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= e($flash['success']) ?>
                </p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($flash['info'])): ?>
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p class="text-sm font-medium text-blue-800 dark:text-blue-300">
                    <i class="fas fa-info-circle mr-2"></i>
                    <?= e($flash['info']) ?>
                </p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($flash['error'])): ?>
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-sm font-medium text-red-800 dark:text-red-300">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= e($flash['error']) ?>
                </p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Pending Contracts Alert -->
    <?php if (!empty($pendingContractsCount) && $pendingContractsCount > 0): ?>
        <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-300">
                        Bekleyen Sözleşmeleriniz Var
                    </h3>
                    <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-400">
                        <?php if ($pendingContractsCount == 1): ?>
                            Onaylamanız gereken 1 adet sözleşme bulunmaktadır.
                        <?php else: ?>
                            Onaylamanız gereken <?= $pendingContractsCount ?> adet sözleşme bulunmaktadır.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($latestPendingContract)): ?>
                        <div class="mt-3">
                            <a href="<?= base_url('/contract/' . (int)$latestPendingContract['id']) ?>" 
                               class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-md transition-colors duration-150">
                                <i class="fas fa-file-contract mr-2"></i>
                                Sözleşmeyi Görüntüle ve Onayla
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <i class="fas fa-briefcase text-white text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate"><?= __('total_jobs') ?></dt>
                            <dd class="text-3xl font-semibold text-gray-900"><?= $stats['total_jobs'] ?? 0 ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <i class="fas fa-check-circle text-white text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate"><?= __('completed') ?></dt>
                            <dd class="text-3xl font-semibold text-gray-900"><?= $stats['completed_jobs'] ?? 0 ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                        <i class="fas fa-clock text-white text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate"><?= __('pending') ?></dt>
                            <dd class="text-3xl font-semibold text-gray-900"><?= $stats['pending_jobs'] ?? 0 ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                        <i class="fas fa-file-invoice-dollar text-white text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate"><?= __('unpaid_invoices') ?></dt>
                            <dd class="text-3xl font-semibold text-gray-900"><?= $unpaidCount ?? 0 ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-orange-500 rounded-md p-3">
                        <i class="fas fa-file-contract text-white text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Bekleyen Sözleşmeler</dt>
                            <dd class="text-3xl font-semibold text-gray-900"><?= $pendingContractsCount ?? 0 ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <a href="<?= base_url('/portal/booking') ?>" class="block p-6 bg-blue-600 hover:bg-blue-700 rounded-lg shadow text-white transition">
            <div class="flex items-center">
                <i class="fas fa-calendar-plus text-3xl mr-4"></i>
                <div>
                    <h3 class="text-lg font-semibold"><?= __('book_appointment') ?></h3>
                    <p class="text-sm opacity-90"><?= __('schedule_new_service') ?></p>
                </div>
            </div>
        </a>

        <a href="<?= base_url('/portal/jobs') ?>" class="block p-6 bg-green-600 hover:bg-green-700 rounded-lg shadow text-white transition">
            <div class="flex items-center">
                <i class="fas fa-list text-3xl mr-4"></i>
                <div>
                    <h3 class="text-lg font-semibold"><?= __('view_jobs') ?></h3>
                    <p class="text-sm opacity-90"><?= __('track_your_services') ?></p>
                </div>
            </div>
        </a>

        <a href="<?= base_url('/portal/invoices') ?>" class="block p-6 bg-purple-600 hover:bg-purple-700 rounded-lg shadow text-white transition">
            <div class="flex items-center">
                <i class="fas fa-receipt text-3xl mr-4"></i>
                <div>
                    <h3 class="text-lg font-semibold"><?= __('invoices') ?></h3>
                    <p class="text-sm opacity-90"><?= __('view_and_pay') ?></p>
                </div>
            </div>
        </a>
    </div>

    <!-- Recent Jobs -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4"><?= __('recent_jobs') ?></h2>
        <?php if (!empty($recentJobs)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= __('date') ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= __('status') ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= __('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($recentJobs as $job): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= date('d/m/Y H:i', strtotime($job['job_date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?= $job['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                           ($job['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') ?>">
                                        <?= __('job_' . $job['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="<?= base_url('/portal/jobs?id=' . $job['id']) ?>" class="text-blue-600 hover:text-blue-900">
                                        <?= __('view_details') ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-8"><?= __('no_jobs_yet') ?></p>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>

