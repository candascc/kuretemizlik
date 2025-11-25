<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-money-bill-wave mr-3 text-primary-600"></i>
                Aidat Detay
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Aidat kaydı detayları</p>
        </div>
        <div class="flex space-x-3">
            <a href="<?= base_url('/management-fees') ?>" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                <i class="fas fa-arrow-left mr-2"></i>
                Geri Dön
            </a>
            <a href="<?= base_url("/management-fees/{$fee['id']}/payment") ?>" 
               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                <i class="fas fa-credit-card mr-2"></i>
                Ödeme Kaydet
            </a>
        </div>
    </div>

    <!-- Fee Details -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Info -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Temel Bilgiler</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Aidat Adı</label>
                            <p class="text-gray-900 dark:text-white"><?= htmlspecialchars($fee['fee_name'] ?? '') ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Dönem</label>
                            <p class="text-gray-900 dark:text-white"><?= htmlspecialchars($fee['period'] ?? '') ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Vade Tarihi</label>
                            <p class="text-gray-900 dark:text-white"><?= date('d.m.Y', strtotime($fee['due_date'] ?? '')) ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Durum</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?= ($fee['status'] ?? '') == 'paid' ? 'bg-green-100 text-green-800' : 
                                    (($fee['status'] ?? '') == 'partial' ? 'bg-yellow-100 text-yellow-800' : 
                                    (($fee['status'] ?? '') == 'overdue' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) ?>">
                                <?= ($fee['status'] ?? '') == 'paid' ? 'Ödendi' : 
                                    (($fee['status'] ?? '') == 'partial' ? 'Kısmi Ödeme' : 
                                    (($fee['status'] ?? '') == 'overdue' ? 'Gecikmiş' : 'Beklemede')) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Financial Info -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Mali Bilgiler</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Ana Tutar</label>
                            <p class="text-gray-900 dark:text-white"><?= number_format($fee['base_amount'] ?? 0, 2) ?> TL</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">İndirim</label>
                            <p class="text-gray-900 dark:text-white"><?= number_format($fee['discount_amount'] ?? 0, 2) ?> TL</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Gecikme Ücreti</label>
                            <p class="text-gray-900 dark:text-white"><?= number_format($fee['late_fee'] ?? 0, 2) ?> TL</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam Tutar</label>
                            <p class="text-xl font-bold text-gray-900 dark:text-white"><?= number_format($fee['total_amount'] ?? 0, 2) ?> TL</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Ödenen Tutar</label>
                            <p class="text-gray-900 dark:text-white"><?= number_format($fee['paid_amount'] ?? 0, 2) ?> TL</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Kalan Tutar</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                <?= number_format(($fee['total_amount'] ?? 0) - ($fee['paid_amount'] ?? 0), 2) ?> TL
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Info -->
            <?php if ($fee['payment_date'] || $fee['payment_method']): ?>
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ödeme Bilgileri</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if ($fee['payment_date']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Ödeme Tarihi</label>
                        <p class="text-gray-900 dark:text-white"><?= date('d.m.Y', strtotime($fee['payment_date'])) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($fee['payment_method']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Ödeme Yöntemi</label>
                        <p class="text-gray-900 dark:text-white"><?= e($fee['payment_method']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($fee['receipt_number']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Makbuz No</label>
                        <p class="text-gray-900 dark:text-white"><?= e($fee['receipt_number']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Notes -->
            <?php if ($fee['notes']): ?>
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notlar</h3>
                <p class="text-gray-700 dark:text-gray-300"><?= nl2br(e($fee['notes'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
