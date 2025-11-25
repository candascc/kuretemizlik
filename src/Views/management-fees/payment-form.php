<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-money-bill-wave mr-3 text-primary-600"></i>
                Ödeme Kaydet
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                Daire: <strong><?= htmlspecialchars($fee['unit_number'] ?? '-') ?></strong> - 
                Dönem: <strong><?= htmlspecialchars($fee['period'] ?? '-') ?></strong>
            </p>
        </div>
        <a href="<?= base_url('/management-fees/' . $fee['id']) ?>" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Geri
        </a>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Fee Info Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Aidat Bilgileri</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="text-sm text-gray-600 dark:text-gray-400">Toplam Tutar</label>
                <p class="text-xl font-bold text-gray-900 dark:text-white">
                    <?= number_format($fee['total_amount'] ?? 0, 2) ?> ₺
                </p>
            </div>
            <div>
                <label class="text-sm text-gray-600 dark:text-gray-400">Ödenen Tutar</label>
                <p class="text-xl font-bold text-green-600">
                    <?= number_format($fee['paid_amount'] ?? 0, 2) ?> ₺
                </p>
            </div>
            <div>
                <label class="text-sm text-gray-600 dark:text-gray-400">Kalan Tutar</label>
                <p class="text-xl font-bold text-red-600" id="remainingAmount">
                    <?= number_format(($fee['total_amount'] ?? 0) - ($fee['paid_amount'] ?? 0), 2) ?> ₺
                </p>
            </div>
        </div>
    </div>

    <!-- Payment Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= base_url('/management-fees/' . $fee['id'] . '/payment') ?>" id="paymentForm">
            <?= CSRF::field() ?>

            <div class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Ödeme Tutarı (₺) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="amount" id="amount" 
                           value="<?= ($fee['total_amount'] ?? 0) - ($fee['paid_amount'] ?? 0) ?>"
                           required step="0.01" min="0.01" 
                           max="<?= ($fee['total_amount'] ?? 0) - ($fee['paid_amount'] ?? 0) ?>"
                           class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                           onchange="updateRemaining()">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Maksimum ödeme: <?= number_format(($fee['total_amount'] ?? 0) - ($fee['paid_amount'] ?? 0), 2) ?> ₺
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Ödeme Yöntemi <span class="text-red-500">*</span>
                    </label>
                    <select name="payment_method" required
                            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        <option value="cash">Nakit</option>
                        <option value="transfer">Havale/EFT</option>
                        <option value="card">Kredi Kartı</option>
                        <option value="check">Çek</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Ödeme Tarihi <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="payment_date" 
                           value="<?= date('Y-m-d') ?>" 
                           required
                           class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Makbuz No (Opsiyonel)
                    </label>
                    <input type="text" name="receipt_number" 
                           placeholder="Makbuz numarası"
                           class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Notlar
                    </label>
                    <textarea name="notes" rows="3" 
                              placeholder="Ödeme ile ilgili ek notlar..."
                              class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-600 dark:text-blue-300 mt-1 mr-3"></i>
                        <div class="text-sm text-blue-800 dark:text-blue-200">
                            <p class="font-semibold mb-1">Önemli Bilgiler:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Ödeme kaydedildikten sonra aidat durumu otomatik güncellenecektir.</li>
                                <li>Eğer toplam tutar ödenirse, aidat durumu "Ödendi" olarak işaretlenecektir.</li>
                                <li>Kısmi ödemeler için durum "Kısmi" olarak kalacaktır.</li>
                                <li>Ödeme kaydı finans modülüne otomatik olarak aktarılacaktır.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end space-x-3">
                <a href="<?= base_url('/management-fees/' . $fee['id']) ?>" 
                   class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    İptal
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors font-medium"
                        style="color: white !important; background: #4f46e5 !important;">
                    <i class="fas fa-check mr-2" style="color: white !important;"></i>
                    <span style="color: white !important;">Ödemeyi Kaydet</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateRemaining() {
    const totalAmount = <?= $fee['total_amount'] ?? 0 ?>;
    const paidAmount = <?= $fee['paid_amount'] ?? 0 ?>;
    const paymentAmount = parseFloat(document.getElementById('amount').value) || 0;
    const remaining = totalAmount - paidAmount - paymentAmount;
    
    const remainingEl = document.getElementById('remainingAmount');
    remainingEl.textContent = remaining.toFixed(2) + ' ₺';
    remainingEl.className = remaining <= 0 ? 'text-xl font-bold text-green-600' : 'text-xl font-bold text-red-600';
}
</script>

