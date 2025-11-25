<?php
/** @var array $items */
?>
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Periyodik İşler</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Otomatik tekrarlanan işlerinizi yönetin</p>
        </div>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide new recurring button for operator (begin) -->
        <?php if (Auth::role() !== 'OPERATOR'): ?>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="<?= base_url('/recurring/new') ?>" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Yeni Periyodik İş
            </a>
        </div>
        <?php endif; ?>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide new recurring button for operator (end) -->
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-sync-alt text-blue-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white"><?= count($items) ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-play-circle text-green-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktif</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white"><?= count(array_filter($items, fn($item) => $item['status'] === 'ACTIVE')) ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-pause-circle text-yellow-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pasif</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white"><?= count(array_filter($items, fn($item) => $item['status'] === 'PAUSED')) ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-ban text-red-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">İptal</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white"><?= count(array_filter($items, fn($item) => $item['status'] === 'CANCELLED')) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ara</label>
                <input type="text" id="search" placeholder="Müşteri adı, hizmet..." 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div class="sm:w-48">
                <label for="status-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durum</label>
                <select id="status-filter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <option value="ACTIVE">Aktif</option>
                    <option value="PAUSED">Pasif</option>
                    <option value="CANCELLED">İptal</option>
                </select>
            </div>
            <div class="sm:w-48">
                <label for="frequency-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sıklık</label>
                <select id="frequency-filter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <option value="DAILY">Günlük</option>
                    <option value="WEEKLY">Haftalık</option>
                    <option value="MONTHLY">Aylık</option>
                </select>
            </div>
            <?php if (Auth::canSwitchCompany()): ?>
            <form method="GET" class="sm:w-48">
                <label for="company-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şirket</label>
                <select id="company-filter" name="company_filter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white" onchange="this.form.submit()">
                    <option value="">Tüm Şirketler</option>
                    <?php if (!empty($companies)): ?>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?= (int)$company['id'] ?>" <?= (isset($_GET['company_filter']) && (int)$_GET['company_filter'] === (int)$company['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($company['name'] ?? ('Şirket #' . $company['id'])) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-user mr-1"></i>Müşteri
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-sync-alt mr-1"></i>Sıklık
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-calendar mr-1"></i>Başlangıç
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-cogs mr-1"></i>Hizmet
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-info-circle mr-1"></i>Durum
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-cogs mr-1"></i>İşlemler
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="recurring-table-body">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-sync-alt text-4xl text-gray-400 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Henüz periyodik iş yok</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-4">Otomatik tekrarlanan işlerinizi oluşturmaya başlayın</p>
                                    <!-- ===== KOZMOS_OPERATOR_READONLY: hide empty state button for operator (begin) -->
                                    <?php if (Auth::role() !== 'OPERATOR'): ?>
                                    <a href="<?= base_url('/recurring/new') ?>" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                                        <i class="fas fa-plus mr-2"></i>
                                        İlk Periyodik İşi Oluştur
                                    </a>
                                    <?php endif; ?>
                                    <!-- ===== KOZMOS_OPERATOR_READONLY: hide empty state button for operator (end) -->
                                </div>
                            </td>
                        </tr>
                    <?php else: foreach ($items as $rj): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" data-customer="<?= strtolower(htmlspecialchars($rj['customer_name'] ?? '')) ?>" data-service="<?= strtolower(htmlspecialchars($rj['service_name'] ?? '')) ?>" data-status="<?= $rj['status'] ?>" data-frequency="<?= $rj['frequency'] ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/20 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-primary-600 dark:text-primary-400 text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?= htmlspecialchars($rj['customer_name'] ?? ('#'.(int)$rj['customer_id'])) ?>
                                        </div>
                                        <?php if (!empty($rj['customer_phone'])): ?>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                <i class="fas fa-phone mr-1"></i><?= e($rj['customer_phone']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                        <?= e($rj['frequency']) ?><?= !empty($rj['interval']) && (int)$rj['interval'] !== 1 ? ' x'.(int)$rj['interval'] : '' ?>
                                    </span>
                                </div>
                                <?php if ($rj['frequency'] === 'WEEKLY' && !empty($rj['byweekday'])): ?>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        <?= implode(', ', json_decode($rj['byweekday'])) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    <?= htmlspecialchars(substr($rj['start_date'],0,10)) ?>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    @ <?= htmlspecialchars(str_pad($rj['byhour'], 2, '0', STR_PAD_LEFT) . ':' . str_pad($rj['byminute'], 2, '0', STR_PAD_LEFT)) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($rj['service_name'] ?? '-') ?>
                                </div>
                                <?php 
                                $pricingModel = $rj['pricing_model'] ?? 'PER_JOB';
                                if ($pricingModel === 'PER_JOB' && !empty($rj['default_total_amount'])): 
                                ?>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <?= Utils::formatMoney($rj['default_total_amount']) ?>
                                    </div>
                                <?php elseif ($pricingModel === 'PER_MONTH' && !empty($rj['monthly_amount'])): ?>
                                    <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                                        <i class="fas fa-calendar-alt mr-1"></i>Aylık: <?= Utils::formatMoney($rj['monthly_amount']) ?>
                                    </div>
                                <?php elseif ($pricingModel === 'TOTAL_CONTRACT' && !empty($rj['contract_total_amount'])): ?>
                                    <div class="text-xs text-purple-600 dark:text-purple-400 font-medium">
                                        <i class="fas fa-file-contract mr-1"></i>Sözleşme: <?= Utils::formatMoney($rj['contract_total_amount']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($rj['jobs_count']) && (int)$rj['jobs_count'] > 0): ?>
                                    <div class="mt-1">
                                        <a href="<?= base_url('/jobs?recurring=' . $rj['id']) ?>" class="text-xs text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300">
                                            <i class="fas fa-link mr-1"></i><?= (int)$rj['jobs_count'] ?> iş oluşturuldu
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold <?= $rj['status']==='ACTIVE'?'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400':($rj['status']==='CANCELLED'?'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400':'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400') ?>">
                                    <?= $rj['status']==='ACTIVE'?'Aktif':($rj['status']==='CANCELLED'?'İptal':'Pasif') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end items-center space-x-1">
                                    <?php $isCancelled = ($rj['status'] ?? '') === 'CANCELLED'; ?>
                                    <?php $isActive = ($rj['status'] ?? '') === 'ACTIVE'; ?>
                                    
                                    <!-- Durum Toggle -->
                                    <?php if (!$isCancelled): ?>
                                        <form method="POST" action="<?= base_url('/recurring/' . $rj['id'] . '/toggle') ?>" class="inline">
                                            <?= CSRF::field() ?>
                                            <button type="submit" class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors" 
                                                    title="<?= $isActive ? 'Pasif Yap' : 'Aktif Yap' ?>"
                                                    aria-label="<?= $isActive ? 'Periyodik işi pasif yap' : 'Periyodik işi aktif yap' ?>">
                                                <i class="fas <?= $isActive ? 'fa-pause-circle' : 'fa-play-circle' ?>"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="p-2 text-gray-400 dark:text-gray-600 cursor-not-allowed" title="İptal edilmiş iş">
                                            <i class="fas fa-ban"></i>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <!-- Hızlı Oluştur -->
                                    <?php if ($isActive): ?>
                                        <form method="POST" action="<?= base_url('/recurring/' . $rj['id'] . '/generate-now') ?>" class="inline" onsubmit="return confirm('Şimdi oluşumları tetiklemek istediğinizden emin misiniz?')">
                                            <?= CSRF::field() ?>
                                            <button type="submit" 
                                                    class="p-2 text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors" 
                                                    title="Şimdi Oluştur"
                                                    aria-label="Oluşumları şimdi oluştur">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <!-- Detay -->
                                    <a href="<?= base_url('/recurring/' . $rj['id']) ?>" 
                                       class="p-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" 
                                       title="Detay"
                                       aria-label="Periyodik iş detaylarını görüntüle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <!-- Düzenle -->
                                    <?php if (Auth::role() !== 'OPERATOR'): ?>
                                        <a href="<?= base_url('/recurring/' . $rj['id'] . '/edit') ?>" 
                                           class="p-2 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" 
                                           title="Düzenle"
                                           aria-label="Periyodik işi düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <!-- İptal Et -->
                                    <?php if (!$isCancelled): ?>
                                        <form method="POST" action="<?= base_url('/recurring/' . $rj['id'] . '/cancel') ?>" class="inline" onsubmit="return confirm('Bu periyodik işi iptal etmek istediğinizden emin misiniz? Geçmiş işler korunur, sadece gelecek oluşumlar durdurulur. Bu işlem geri alınamaz.');">
                                            <?= CSRF::field() ?>
                                            <button type="submit" 
                                                    class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" 
                                                    title="Sözleşmeyi İptal Et"
                                                    aria-label="Sözleşmeyi iptal et (geri alınamaz)">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="p-2 text-gray-400 dark:text-gray-600 cursor-not-allowed" title="Zaten iptal edilmiş" aria-label="Bu periyodik iş zaten iptal edilmiş">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const statusFilter = document.getElementById('status-filter');
    const frequencyFilter = document.getElementById('frequency-filter');
    const tableBody = document.getElementById('recurring-table-body');
    const rows = tableBody.querySelectorAll('tr[data-customer]');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        const frequencyValue = frequencyFilter.value;

        rows.forEach(row => {
            const customer = row.dataset.customer;
            const service = row.dataset.service;
            const status = row.dataset.status;
            const frequency = row.dataset.frequency;

            const matchesSearch = !searchTerm || customer.includes(searchTerm) || service.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;
            const matchesFrequency = !frequencyValue || frequency === frequencyValue;

            if (matchesSearch && matchesStatus && matchesFrequency) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Debounced filter function
    let filterTimeout;
    const debouncedFilterTable = () => {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(filterTable, 300);
    };
    
    searchInput.addEventListener('input', debouncedFilterTable);
    statusFilter.addEventListener('change', filterTable);
    frequencyFilter.addEventListener('change', filterTable);
});
</script>
</div>
