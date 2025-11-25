<?php require __DIR__ . '/layout/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8"><?= __('my_jobs') ?></h1>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <?php if (!empty($jobs)): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= __('date') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= __('staff') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= __('status') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= __('notes') ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($jobs as $job): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= date('d/m/Y H:i', strtotime($job['job_date'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($job['staff_name'] ?? __('not_assigned')) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $job['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                       ($job['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') ?>">
                                    <?= __('job_' . $job['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= htmlspecialchars(substr($job['notes'] ?? '', 0, 50)) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex justify-between items-center">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?= $currentPage - 1 ?>" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-chevron-left"></i> <?= __('previous') ?>
                            </a>
                        <?php else: ?>
                            <span class="text-gray-400"><i class="fas fa-chevron-left"></i> <?= __('previous') ?></span>
                        <?php endif; ?>

                        <span class="text-gray-700">Page <?= $currentPage ?> of <?= $totalPages ?></span>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?= $currentPage + 1 ?>" class="text-blue-600 hover:text-blue-800">
                                <?= __('next') ?> <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="text-gray-400"><?= __('next') ?> <i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-inbox text-gray-400 text-6xl mb-4"></i>
                <p class="text-gray-500"><?= __('no_jobs_yet') ?></p>
                <a href="<?= base_url('/portal/booking') ?>" class="mt-4 inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <?= __('book_first_appointment') ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>

