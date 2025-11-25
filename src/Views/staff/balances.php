<?php $pageTitle = 'Personel Alacak/Verecek - ' . $staff['name'] . ' ' . $staff['surname']; ?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-balance-scale mr-3 text-orange-600"></i>
                Alacak/Verecek Takibi
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                <?= htmlspecialchars($staff['name'] . ' ' . $staff['surname']) ?> - Alacak ve verecek işlemleri
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= base_url('/staff') ?>" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Geri Dön
            </a>
        </div>
    </div>

    <!-- Balance Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/20">
                    <i class="fas fa-arrow-up text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Alacak</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        <?= number_format($totalReceivable, 2) ?> ₺
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 dark:bg-red-900/20">
                    <i class="fas fa-arrow-down text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Verecek</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        <?= number_format($totalPayable, 2) ?> ₺
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/20">
                    <i class="fas fa-balance-scale text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Net Bakiye</p>
                    <p class="text-2xl font-bold <?= ($netBalance >= 0) ? 'text-green-600' : 'text-red-600' ?>">
                        <?= number_format($netBalance, 2) ?> ₺
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Balance Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            <i class="fas fa-plus mr-2"></i>Yeni Alacak/Verecek Ekle
        </h2>
        
        <form method="POST" action="<?= base_url('/staff/add-balance/' . $staff['id']) ?>" class="space-y-4">
            <?= CSRF::field() ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="balance_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        İşlem Türü
                    </label>
                    <select name="balance_type" id="balance_type" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white">
                        <option value="receivable">Alacak (Bize Borçlu)</option>
                        <option value="payable">Verecek (Biz Borçluyuz)</option>
                    </select>
                </div>
                
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Tutar (₺)
                    </label>
                    <input type="number" name="amount" id="amount" step="0.01" min="0" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white"
                           placeholder="0.00">
                </div>
            </div>
            
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Açıklama
                </label>
                <input type="text" name="description" id="description" required
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white"
                       placeholder="İşlem açıklaması">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Vade Tarihi (Opsiyonel)
                    </label>
                    <input type="date" name="due_date" id="due_date"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Durum
                    </label>
                    <select name="status" id="status"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 dark:bg-gray-700 dark:text-white">
                        <option value="pending">Beklemede</option>
                        <option value="paid">Ödendi</option>
                        <option value="cancelled">İptal</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>Ekle
                </button>
            </div>
        </form>
    </div>

    <!-- Balance History -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Alacak/Verecek Geçmişi</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Tarih
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Tür
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Tutar
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Açıklama
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Vade
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Durum
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            İşlemler
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($balances)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-balance-scale text-4xl mb-4"></i>
                                <p class="text-lg">Henüz alacak/verecek kaydı yok</p>
                                <p class="text-sm mt-2">Yukarıdaki formu kullanarak ilk kaydınızı ekleyin</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($balances as $balance): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= date('d.m.Y', strtotime($balance['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?= $balance['balance_type'] === 'receivable' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' ?>">
                                        <i class="fas <?= $balance['balance_type'] === 'receivable' ? 'fa-arrow-up' : 'fa-arrow-down' ?> mr-1"></i>
                                        <?= $balance['balance_type'] === 'receivable' ? 'Alacak' : 'Verecek' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium
                                    <?= $balance['balance_type'] === 'receivable' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                                    <?= $balance['balance_type'] === 'receivable' ? '+' : '-' ?><?= number_format($balance['amount'], 2) ?> ₺
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <?= e($balance['description']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?= $balance['due_date'] ? date('d.m.Y', strtotime($balance['due_date'])) : '-' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?= $balance['status'] === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 
                                           ($balance['status'] === 'cancelled' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400' : 
                                           'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400') ?>">
                                        <?= $balance['status'] === 'paid' ? 'Ödendi' : 
                                           ($balance['status'] === 'cancelled' ? 'İptal' : 'Beklemede') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <?php if ($balance['status'] === 'pending'): ?>
                                            <button onclick="updateBalanceStatus(<?= $balance['id'] ?>, 'paid')"
                                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                    title="Ödendi Olarak İşaretle">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="updateBalanceStatus(<?= $balance['id'] ?>, 'cancelled')"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                title="İptal Et">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function updateBalanceStatus(balanceId, status) {
    const statusText = status === 'paid' ? 'ödendi olarak işaretlemek' : 'iptal etmek';
    
    if (confirm(`Bu kaydı ${statusText} istediğinizden emin misiniz?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('/staff/update-balance/') ?>' + balanceId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= CSRF::get() ?>';
        
        form.appendChild(statusInput);
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
