<div class="space-y-8">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Müşteriler</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Müşteri bilgilerini yönetin ve takip edin</p>
                </div>
                <!-- ===== KOZMOS_OPERATOR_READONLY: hide new customer button for operator (begin) -->
                <?php if (can('customers.create')): ?>
                <div class="flex items-center space-x-3">
                    <?php $label='Yeni Müşteri'; $href=base_url('/customers/new'); $variant='primary'; $icon='fa-user-plus'; include __DIR__ . '/../partials/ui/button.php'; ?>
                    <!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations for customers (begin) -->
                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="toggleBulkMode()" id="bulkToggleBtn" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-check-square mr-2"></i>
                            Toplu İşlem
                        </button>
                        <div id="bulkActions" class="hidden flex items-center space-x-2">
                            <button type="button" onclick="selectAllCustomers()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-200">
                                <i class="fas fa-check-double mr-1"></i>
                                Tümünü Seç
                            </button>
                            <button type="button" onclick="bulkDeleteCustomers()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors duration-200">
                                <i class="fas fa-trash mr-1"></i>
                                Toplu Sil
                            </button>
                            <button type="button" onclick="cancelBulkMode()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-200">
                                <i class="fas fa-times mr-1"></i>
                                İptal
                            </button>
                        </div>
                    </div>
                    <!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations for customers (end) -->
                </div>
                <?php endif; ?>
                <!-- ===== KOZMOS_OPERATOR_READONLY: hide new customer button for operator (end) -->
            </div>

            <!-- Filter Form -->
            <form method="GET" class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-search mr-2 text-primary-600"></i>
                            Müşteri Ara
                        </label>
                        <input type="text" 
                               name="search" 
                               value="<?= e($search ?? '') ?>" 
                               class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm" 
                               placeholder="İsim, telefon veya email ile ara...">
                    </div>
                    <?php if (Auth::canSwitchCompany()): ?>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-building mr-2 text-primary-600"></i>
                            Şirket
                        </label>
                        <select name="company_filter" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm">
                            <option value="">Tüm Şirketler</option>
                            <?php if (!empty($companies)): ?>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?= (int)$company['id'] ?>" <?= (($companyFilter ?? '') !== '' && (int)$companyFilter === (int)$company['id']) ? 'selected' : '' ?>>
                                        <?= e($company['name'] ?? ('Şirket #' . $company['id'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="flex items-end">
                        <button type="submit" 
                                class="w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 bg-gray-700 hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 text-sm">
                            <i class="fas fa-search mr-2"></i>
                            Ara
                        </button>
                    </div>
                </div>
            </form>

            <!-- Main Content -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
                <?php if (empty($customers)): ?>
                    <div class="p-16 text-center">
                        <div class="mx-auto w-20 h-20 flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 mb-6">
                            <i class="fas fa-users text-3xl"></i>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Henüz müşteri yok</div>
                        <div class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">İlk müşterinizi ekleyerek başlayın. Müşteri bilgilerini kaydedin ve işlerinizi takip edin.</div>
                        <!-- ===== KOZMOS_OPERATOR_READONLY: hide empty state button for operator (begin) -->
                        <?php if (can('customers.create')): ?>
                        <a href="<?= base_url('/customers/new') ?>" 
                           class="inline-flex items-center px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <i class="fas fa-user-plus mr-3"></i> 
                            İlk Müşteriyi Ekle
                        </a>
                        <?php endif; ?>
                        <!-- ===== KOZMOS_OPERATOR_READONLY: hide empty state button for operator (end) -->
                    </div>
                <?php else: ?>
                    <!-- Desktop Table View (component) -->
                    <div class="hidden lg:block overflow-x-auto">
                        <?php 
                        $headers = [];
                        if (Auth::role() !== 'OPERATOR') {
                            $headers['bulk'] = ['label' => '<input type="checkbox" id="selectAllCheckbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" onchange="toggleAllCustomers(this)">', 'raw' => true];
                        }
                        $headers['name'] = ['label' => 'Müşteri Adı', 'raw' => true];
                        $headers['phone'] = ['label' => 'Telefon', 'raw' => true];
                        $headers['email'] = ['label' => 'Email', 'raw' => true];
                        $headers['actions'] = ['label' => 'İşlemler', 'raw' => true];
                        $rows = [];
                        foreach ($customers as $customer) {
                            $r = [];
                            if (Auth::role() !== 'OPERATOR') {
                                $r['bulk'] = '<input type="checkbox" name="customer_ids[]" value="'.e($customer['id']).'" class="customer-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500">';
                            }
                            $r['name'] = '<div class="text-sm font-semibold text-gray-900 dark:text-white">'.e($customer['name']).'</div>';
                            $r['phone'] = '<div class="text-sm text-gray-600 dark:text-gray-300">'.e($customer['phone'] ?? '').'</div>';
                            $r['email'] = '<div class="text-sm text-gray-600 dark:text-gray-300">'.e($customer['email'] ?? '').'</div>';
                            if (Auth::role() !== 'OPERATOR') {
                                $actionsHtml = '<div class="flex space-x-2 sm:space-x-3">'
                                    .'<a href="'.base_url('/customers/show/'.$customer['id']).'" class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-150 p-1 rounded" title="Görüntüle"><i class="fas fa-eye text-sm"></i></a>'
                                    .'<a href="'.base_url('/customers/edit/'.$customer['id']).'" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-150 p-1 rounded" title="Düzenle"><i class="fas fa-edit text-sm"></i></a>'
                                    .'<form method="POST" action="'.base_url('/customers/delete/'.$customer['id']).'" class="inline">'.CSRF::field().'<button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors duration-150 p-1 rounded" title="Sil" onclick="return confirm(\'Bu müşteriyi silmek istediğinizden emin misiniz?\')"><i class="fas fa-trash text-sm"></i></button></form>'
                                    .'</div>';
                            } else {
                                $actionsHtml = '<div class="flex items-center justify-end"><a href="'.base_url('/customers/show/'.$customer['id']).'" class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-150 p-1 rounded" title="Görüntüle"><i class="fas fa-eye text-sm"></i></a></div>';
                            }
                            $r['actions'] = $actionsHtml;
                            $rows[] = $r;
                        }
                        include __DIR__ . '/../partials/ui/table.php';
                        ?>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="lg:hidden divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($customers as $customer): ?>
                            <div class="p-4 sm:p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                <!-- ===== KOZMOS_BULK_OPERATIONS: add mobile checkbox (begin) -->
                                <?php if (Auth::role() !== 'OPERATOR'): ?>
                                <div class="flex items-center justify-between mb-4">
                                    <input type="checkbox" name="customer_ids[]" value="<?= e($customer['id']) ?>" class="customer-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <div class="text-lg font-semibold text-gray-900 dark:text-white"><?= e($customer['name']) ?></div>
                                </div>
                                <?php else: ?>
                                <div class="flex items-center justify-between mb-4">
                                    <div class="text-lg font-semibold text-gray-900 dark:text-white"><?= e($customer['name']) ?></div>
                                </div>
                                <?php endif; ?>
                                <!-- ===== KOZMOS_BULK_OPERATIONS: add mobile checkbox (end) -->
                                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                                    <?php if (!empty($customer['phone'])): ?>
                                        <div class="flex items-center">
                                            <i class="fas fa-phone mr-3 text-primary-500 w-4"></i>
                                            <?= e($customer['phone']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($customer['email'])): ?>
                                        <div class="flex items-center">
                                            <i class="fas fa-envelope mr-3 text-primary-500 w-4"></i>
                                            <?= e($customer['email']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                    <a href="<?= base_url("/customers/show/{$customer['id']}") ?>" 
                                       class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-150" 
                                       title="Görüntüle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <!-- ===== KOZMOS_OPERATOR_READONLY: hide edit/delete buttons for operator (begin) -->
                                    <?php if (Auth::role() !== 'OPERATOR'): ?>
                                    <a href="<?= base_url("/customers/edit/{$customer['id']}") ?>" 
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-150" 
                                       title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="<?= base_url("/customers/delete/{$customer['id']}") ?>" class="inline">
                                        <?= CSRF::field() ?>
                                        <button type="submit" 
                                                class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors duration-150" 
                                                title="Sil" 
                                                onclick="return confirm('Bu müşteriyi silmek istediğinizden emin misiniz?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <!-- ===== KOZMOS_OPERATOR_READONLY: hide edit/delete buttons for operator (end) -->
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
</div>

<!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations JavaScript (begin) -->
<script>
function toggleBulkMode() {
    const bulkToggleBtn = document.getElementById('bulkToggleBtn');
    const bulkActions = document.getElementById('bulkActions');
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    
    if (bulkActions.classList.contains('hidden')) {
        bulkActions.classList.remove('hidden');
        bulkToggleBtn.classList.add('bg-primary-600', 'text-white');
        bulkToggleBtn.classList.remove('bg-white', 'text-gray-700');
        checkboxes.forEach(checkbox => checkbox.style.display = 'block');
    } else {
        bulkActions.classList.add('hidden');
        bulkToggleBtn.classList.remove('bg-primary-600', 'text-white');
        bulkToggleBtn.classList.add('bg-white', 'text-gray-700');
        checkboxes.forEach(checkbox => checkbox.style.display = 'none');
        checkboxes.forEach(checkbox => checkbox.checked = false);
    }
}

function cancelBulkMode() {
    const bulkActions = document.getElementById('bulkActions');
    const bulkToggleBtn = document.getElementById('bulkToggleBtn');
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    
    bulkActions.classList.add('hidden');
    bulkToggleBtn.classList.remove('bg-primary-600', 'text-white');
    bulkToggleBtn.classList.add('bg-white', 'text-gray-700');
    checkboxes.forEach(checkbox => {
        checkbox.style.display = 'none';
        checkbox.checked = false;
    });
}

function toggleAllCustomers(checkbox) {
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function selectAllCustomers() {
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = true);
}

function getSelectedCustomers() {
    const checkboxes = document.querySelectorAll('.customer-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function bulkDeleteCustomers() {
    const selectedIds = getSelectedCustomers();
    
    if (selectedIds.length === 0) {
        alert('Lütfen silinecek müşterileri seçin.');
        return;
    }
    
    if (!confirm(`${selectedIds.length} müşteriyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`)) {
        return;
    }
    
    // Form oluştur ve gönder
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('/customers/bulk-delete') ?>';
    
    // CSRF token ekle
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = '<?= CSRF::get() ?>';
    form.appendChild(csrfInput);
    
    // Seçili ID'leri ekle
    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'customer_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

// Initialize - hide checkboxes by default
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    checkboxes.forEach(checkbox => checkbox.style.display = 'none');
});
</script>
<!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations JavaScript (end) -->