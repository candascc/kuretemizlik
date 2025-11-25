<?php $paymentConfirmation = $paymentConfirmation ?? null; ?>

<?php if ($paymentConfirmation): ?>
    <div id="paymentSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/70 p-4" role="dialog" aria-modal="true" aria-labelledby="payment-success-title">
        <div class="max-w-lg w-full rounded-2xl bg-white shadow-2xl dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-300">Ödeme Başarılı</p>
                    <h2 id="payment-success-title" class="text-xl font-bold text-gray-900 dark:text-white">Makbuzunuz hazır</h2>
                </div>
                <button type="button" data-close-modal class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:text-gray-500 dark:hover:bg-gray-800">
                    <span class="sr-only">Kapat</span>
                    <i class="fas fa-xmark text-lg"></i>
                </button>
            </div>
            <div class="space-y-4 px-6 py-6 text-sm text-gray-700 dark:text-gray-200">
                <div class="flex items-center gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm dark:bg-emerald-900/70">
                        <i class="fas fa-circle-check text-lg"></i>
                    </span>
                    <div>
                        <p class="font-semibold">Ödemeniz kaydedildi.</p>
                        <p class="text-xs opacity-80">Makbuz detayları aşağıda yer alıyor. PDF olarak kaydedebilirsiniz.</p>
                    </div>
                </div>

                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2" aria-label="Ödeme özeti">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tutar</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">₺<?= number_format($paymentConfirmation['amount'] ?? 0, 2, ',', '.') ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Yöntem</dt>
                        <dd class="font-semibold"><?= htmlspecialchars($paymentConfirmation['method_label'] ?? ucfirst((string)($paymentConfirmation['method'] ?? ''))) ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">İşlem No</dt>
                        <dd class="font-mono text-sm text-gray-900 dark:text-gray-100"><?= htmlspecialchars($paymentConfirmation['reference'] ?? '—') ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tarih</dt>
                        <dd><?= Utils::formatDateTime($paymentConfirmation['timestamp'] ?? date('Y-m-d H:i:s')) ?></dd>
                    </div>
                </dl>

                <?php if (!empty($paymentConfirmation['notes'])): ?>
                    <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-3 text-xs italic text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <?= nl2br(e($paymentConfirmation['notes'])) ?>
                    </div>
                <?php endif; ?>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <button type="button" data-print-receipt class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800 sm:w-auto">
                        <i class="fas fa-file-pdf"></i>
                        PDF Makbuz Oluştur
                    </button>
                    <button type="button" data-close-modal class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 sm:w-auto">
                        Tamamlandı
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Aidatlarım</h1>
            <p class="text-gray-600 dark:text-gray-400">Aidat durumunuzu görüntüleyin ve ödeme yapın</p>
        </div>
        <a href="<?= base_url('/resident/dashboard') ?>" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg">
            <i class="fas fa-arrow-left mr-2"></i>
            Geri Dön
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
        <form method="GET" class="flex flex-wrap gap-4">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Durum</label>
                <select name="status" id="status" class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Bekleyen</option>
                    <option value="partial" <?= ($filters['status'] ?? '') === 'partial' ? 'selected' : '' ?>>Kısmi</option>
                    <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Ödenmiş</option>
                    <option value="overdue" <?= ($filters['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Gecikmiş</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-md">
                    Filtrele
                </button>
            </div>
        </form>
    </div>

    <?php
        $pagination = $pagination ?? ['total' => 0, 'per_page' => 20, 'current_page' => 1, 'total_pages' => 1, 'offset' => 0];
        $start = $pagination['total'] > 0 ? ($pagination['offset'] + 1) : 0;
        $end = $pagination['total'] > 0 ? min($pagination['offset'] + $pagination['per_page'], $pagination['total']) : 0;
    ?>

    <!-- Fees List -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <?php if (empty($fees)): ?>
            <div class="p-8 text-center">
                <i class="fas fa-receipt text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">Henüz aidat kaydı bulunmamaktadır</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aidat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dönem</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tutar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ödenen</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kalan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Vade</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($fees as $fee): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?= e($fee['fee_name']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?= e($fee['period']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        ₺<?= number_format($fee['total_amount'], 2) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        ₺<?= number_format($fee['paid_amount'], 2) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        ₺<?= number_format($fee['total_amount'] - $fee['paid_amount'], 2) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        <?= $fee['status'] === 'paid' ? 'bg-green-100 text-green-800' : 
                                           ($fee['status'] === 'overdue' ? 'bg-red-100 text-red-800' : 
                                           ($fee['status'] === 'partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) ?>">
                                        <?= ucfirst($fee['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?= date('d.m.Y', strtotime($fee['due_date'])) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($fee['status'] !== 'paid'): ?>
                                        <a href="<?= base_url('/resident/pay-fee/' . $fee['id']) ?>" 
                                           class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                                            Öde
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">Ödendi</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <a href="?page=<?= $pagination['current_page'] - 1 ?>&status=<?= $filters['status'] ?? '' ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Önceki
                            </a>
                        <?php endif; ?>
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <a href="?page=<?= $pagination['current_page'] + 1 ?>&status=<?= $filters['status'] ?? '' ?>" 
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Sonraki
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <?php if ($pagination['total'] > 0): ?>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-medium"><?= $start ?></span>
                                    -
                                    <span class="font-medium"><?= $end ?></span>
                                    arası, toplam
                                    <span class="font-medium"><?= $pagination['total'] ?></span>
                                    kayıt
                                </p>
                            <?php else: ?>
                                <p class="text-sm text-gray-700 dark:text-gray-300">Toplam 0 kayıt</p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                    <a href="?page=<?= $i ?>&status=<?= $filters['status'] ?? '' ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border text-sm font-medium
                                       <?= $i === $pagination['current_page'] ? 
                                           'z-10 bg-primary-50 border-primary-500 text-primary-600 dark:bg-primary-900 dark:border-primary-400 dark:text-primary-300' : 
                                           'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($paymentConfirmation): ?>
    <script>
        (function () {
            const modal = document.getElementById('paymentSuccessModal');
            if (!modal) {
                return;
            }

            const closeButtons = modal.querySelectorAll('[data-close-modal]');
            const printButton = modal.querySelector('[data-print-receipt]');

            const closeModal = () => {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            };

            document.body.classList.add('overflow-hidden');

            closeButtons.forEach((button) => button.addEventListener('click', closeModal));

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });

            if (printButton) {
                printButton.addEventListener('click', () => {
                    window.print();
                });
            }
        })();
    </script>
<?php endif; ?>
