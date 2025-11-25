<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-clipboard-check mr-3 text-primary-600"></i>
                Onay Bekleyen Giderler
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Onayınızı bekleyen bina giderleri</p>
        </div>
        <a href="<?= base_url('/expenses') ?>" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Tüm Giderler
        </a>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Bekleyen Onay</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1"><?= $stats['pending'] ?? 0 ?></p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-300 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Tutar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        <?= number_format($stats['total_amount'] ?? 0, 2) ?> ₺
                    </p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <i class="fas fa-money-bill-wave text-blue-600 dark:text-blue-300 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Bu Ay Onaylanan</p>
                    <p class="text-2xl font-bold text-green-600 mt-1"><?= $stats['approved_this_month'] ?? 0 ?></p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-300 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Expenses -->
    <?php if (!empty($pendingExpenses)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Onay Bekleyen Giderler (<?= count($pendingExpenses) ?>)</h2>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($pendingExpenses as $expense): ?>
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        <?= e($expense['category']) ?>
                                        <?php if (!empty($expense['subcategory'])): ?>
                                            <span class="text-gray-500 dark:text-gray-400">- <?= e($expense['subcategory']) ?></span>
                                        <?php endif; ?>
                                    </h3>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        Beklemede
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                    <div>
                                        <label class="text-sm text-gray-600 dark:text-gray-400">Bina</label>
                                        <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($expense['building_name'] ?? '-') ?></p>
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-600 dark:text-gray-400">Tutar</label>
                                        <p class="font-bold text-gray-900 dark:text-white"><?= number_format($expense['amount'], 2) ?> ₺</p>
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-600 dark:text-gray-400">Tarih</label>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            <?= date('d.m.Y', strtotime($expense['expense_date'])) ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-600 dark:text-gray-400">Ekleyen</label>
                                        <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($expense['created_by_name'] ?? '-') ?></p>
                                    </div>
                                </div>

                                <?php if (!empty($expense['description'])): ?>
                                    <div class="mb-3">
                                        <label class="text-sm text-gray-600 dark:text-gray-400">Açıklama</label>
                                        <p class="text-gray-900 dark:text-white"><?= nl2br(e($expense['description'])) ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($expense['vendor_name']) || !empty($expense['invoice_number'])): ?>
                                    <div class="flex space-x-4 text-sm">
                                        <?php if (!empty($expense['vendor_name'])): ?>
                                            <span class="text-gray-600 dark:text-gray-400">
                                                <i class="fas fa-building mr-1"></i>Tedarikçi: 
                                                <strong><?= e($expense['vendor_name']) ?></strong>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($expense['invoice_number'])): ?>
                                            <span class="text-gray-600 dark:text-gray-400">
                                                <i class="fas fa-file-invoice mr-1"></i>Fatura No: 
                                                <strong><?= e($expense['invoice_number']) ?></strong>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="ml-6 flex flex-col space-y-2">
                                <form method="POST" action="<?= base_url('/expenses/' . $expense['id'] . '/approve') ?>" class="inline">
                                    <?= CSRF::field() ?>
                                    <button type="submit" 
                                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                                        <i class="fas fa-check mr-2"></i>Onayla
                                    </button>
                                </form>

                                <form method="POST" action="<?= base_url('/expenses/' . $expense['id'] . '/reject') ?>" 
                                      onsubmit="return confirm('Bu gideri reddetmek istediğinizden emin misiniz?')" 
                                      class="inline">
                                    <?= CSRF::field() ?>
                                    <button type="submit" 
                                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                                        <i class="fas fa-times mr-2"></i>Reddet
                                    </button>
                                </form>

                                <a href="<?= base_url('/expenses/' . $expense['id']) ?>" 
                                   class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors text-center">
                                    <i class="fas fa-eye mr-2"></i>Detay
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-12 text-center">
            <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
            <p class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Onay Bekleyen Gider Yok!</p>
            <p class="text-gray-600 dark:text-gray-400">Tüm giderler onaylandı veya reddedildi.</p>
        </div>
    <?php endif; ?>
</div>

