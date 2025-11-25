<?php $pageTitle = 'Personel Yönetimi'; ?>

<div class="space-y-8">
    <!-- Header -->
    <!-- ===== KOZMOS_OPERATOR_READONLY: hide new staff button for operator (begin) -->
    <?php if (Auth::role() !== 'OPERATOR') { ob_start(); ?>
    <div class="mt-4 sm:mt-0 flex items-center space-x-3">
        <a href="<?= base_url('/staff/create') ?>" 
           class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-200">
            <i class="fas fa-plus mr-2"></i>Yeni Personel Ekle
        </a>
        <!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations for staff (begin) -->
        <div class="flex items-center space-x-2">
            <button type="button" onclick="toggleBulkMode()" id="bulkToggleBtn" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors duration-200">
                <i class="fas fa-check-square mr-2"></i>
                Toplu İşlem
            </button>
            <div id="bulkActions" class="hidden flex items-center space-x-2">
                <button type="button" onclick="selectAllStaff()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-200">
                    <i class="fas fa-check-double mr-1"></i>
                    Tümünü Seç
                </button>
                <button type="button" onclick="bulkStatusUpdateStaff()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                    <i class="fas fa-edit mr-1"></i>
                    Durum Güncelle
                </button>
                <button type="button" onclick="bulkDeleteStaff()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors duration-200">
                    <i class="fas fa-trash mr-1"></i>
                    Toplu Sil
                </button>
                <button type="button" onclick="cancelBulkMode()" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-200">
                    <i class="fas fa-times mr-1"></i>
                    İptal
                </button>
            </div>
        </div>
        <!-- ===== KOZMOS_BULK_OPERATIONS: bulk operations for staff (end) -->
    </div>
    <?php $rightActionsHtml = ob_get_clean(); } else { $rightActionsHtml = ''; } ?>
    <!-- ===== KOZMOS_OPERATOR_READONLY: hide new staff button for operator (end) -->
    <?php 
    $title = '<i class="fas fa-users mr-3 text-primary-600"></i>Personel Yönetimi';
    $subtitle = 'Personel bilgileri, devam/devamsızlık ve maaş yönetimi';
    include __DIR__ . '/../partials/ui/list-header.php';
    ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/20">
                    <i class="fas fa-users text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Personel</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= count($staff) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/20">
                    <i class="fas fa-user-check text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif Personel</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        <?= count(array_filter($staff, fn($s) => $s['status'] === 'active')) ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900/20">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Bugün Çalışan</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="today-working">-</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/20">
                    <i class="fas fa-lira-sign text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aylık Maaş Toplamı</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="monthly-salary">-</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Personel Listesi</h2>
        </div>
        
        <!-- ===== KOZMOS_STAFF_FIX: add responsive wrapper (begin) -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <!-- ===== KOZMOS_BULK_OPERATIONS: add checkbox column (begin) -->
                        <?php if (Auth::role() !== 'OPERATOR'): ?>
                        <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <input type="checkbox" id="selectAllCheckbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" onchange="toggleAllStaff(this)">
                        </th>
                        <?php endif; ?>
                        <!-- ===== KOZMOS_BULK_OPERATIONS: add checkbox column (end) -->
                        <!-- ===== KOZMOS_STAFF_FIX: match jobs list header style (begin) -->
                        <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-user mr-2"></i>Personel
                        </th>
                        <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-briefcase mr-2"></i>Pozisyon
                        </th>
                        <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-calendar mr-2"></i>İşe Giriş
                        </th>
                        <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-lira-sign mr-2"></i>Maaş
                        </th>
                        <th class="px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-info-circle mr-2"></i>Durum
                        </th>
                        <th class="px-3 sm:px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-cog mr-2"></i>İşlemler
                        </th>
                        <!-- ===== KOZMOS_STAFF_FIX: match jobs list header style (end) -->
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($staff)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="mx-auto w-20 h-20 flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 mb-6">
                                        <i class="fas fa-users text-3xl"></i>
                                    </div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Henüz personel yok</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md">İlk personel kaydını ekleyerek başlayın.</p>
                                    <?php if (Auth::role() !== 'OPERATOR'): ?>
                                    <a href="<?= base_url('/staff/new') ?>" 
                                       class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                                        <i class="fas fa-user-plus mr-2"></i>
                                        İlk Personeli Ekle
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($staff as $person): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <!-- ===== KOZMOS_BULK_OPERATIONS: add staff checkbox (begin) -->
                                <?php if (Auth::role() !== 'OPERATOR'): ?>
                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="staff_ids[]" value="<?= $person['id'] ?>" class="staff-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                </td>
                                <?php endif; ?>
                                <!-- ===== KOZMOS_BULK_OPERATIONS: add staff checkbox (end) -->
                                <!-- ===== KOZMOS_STAFF_FIX: match jobs list cell style (begin) -->
                                <td class="px-3 sm:px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php if ($person['photo']): ?>
                                                <img class="h-10 w-10 rounded-full object-cover" 
                                                     src="<?= base_url($person['photo']) ?>" 
                                                     alt="<?= htmlspecialchars($person['name'] . ' ' . $person['surname']) ?>">
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
                                                    <i class="fas fa-user text-primary-600 dark:text-primary-400"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?= htmlspecialchars($person['name'] . ' ' . $person['surname']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                <?= htmlspecialchars($person['phone'] ?? '') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($person['position'] ?? 'Belirtilmemiş') ?>
                                    </div>
                                </td>
                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?= $person['hire_date'] ? date('d.m.Y', strtotime($person['hire_date'])) : '-' ?>
                                    </div>
                                </td>
                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        <?= number_format($person['salary'], 2, ',', '.') ?> ₺
                                    </div>
                                    <?php if ($person['hourly_rate'] > 0): ?>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <?= number_format($person['hourly_rate'], 2, ',', '.') ?> ₺/saat
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                                    <!-- ===== KOZMOS_STAFF_FIX: match jobs list cell style (end) -->
                                    <?php
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                        'inactive' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
                                        'terminated' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                                    ];
                                    $statusTexts = [
                                        'active' => 'Aktif',
                                        'inactive' => 'Pasif',
                                        'terminated' => 'İşten Ayrıldı'
                                    ];
                                    ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $statusColors[$person['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                        <?= $statusTexts[$person['status']] ?? ucfirst($person['status']) ?>
                                    </span>
                                </td>
                                <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (begin) -->
                                    <?php if (Auth::role() !== 'OPERATOR'): ?>
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="<?= base_url('/staff/edit/' . $person['id']) ?>" 
                                           class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-150 p-1 rounded" 
                                           title="Düzenle">
                                            <i class="fas fa-edit text-sm"></i>
                                        </a>
                                        <button onclick="deleteStaff(<?= $person['id'] ?>)" 
                                                class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors duration-150 p-1 rounded" 
                                                title="Sil">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </div>
                                    <?php else: ?>
                                    <div class="flex items-center justify-end">
                                        <div class="text-gray-400 dark:text-gray-500 text-sm">
                                            <i class="fas fa-eye mr-1"></i>
                                            Görüntüleme
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (end) -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Mobile View -->
        <div class="lg:hidden space-y-4 p-4">
            <?php if (empty($staff)): ?>
                <div class="p-16 text-center">
                    <div class="mx-auto w-20 h-20 flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 mb-6">
                        <i class="fas fa-users text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Henüz personel yok</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md">İlk personel kaydını ekleyerek başlayın.</p>
                    <?php if (Auth::role() !== 'OPERATOR'): ?>
                    <a href="<?= base_url('/staff/new') ?>" 
                       class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                        <i class="fas fa-user-plus mr-2"></i>
                        İlk Personeli Ekle
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($staff as $person): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <?php if ($person['photo']): ?>
                                        <img class="h-10 w-10 rounded-full object-cover" 
                                             src="<?= base_url($person['photo']) ?>" 
                                             alt="<?= htmlspecialchars($person['name'] . ' ' . $person['surname']) ?>">
                                    <?php else: ?>
                                        <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
                                            <i class="fas fa-user text-primary-600 dark:text-primary-400"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($person['name'] . ' ' . $person['surname']) ?>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <?= htmlspecialchars($person['phone'] ?? '') ?>
                                    </div>
                                </div>
                            </div>
                            <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (begin) -->
                            <?php if (Auth::role() !== 'OPERATOR'): ?>
                            <div class="flex items-center space-x-2">
                                <a href="<?= base_url('/staff/edit/' . $person['id']) ?>" 
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-150 p-1 rounded" 
                                   title="Düzenle">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                                <button onclick="deleteStaff(<?= $person['id'] ?>)" 
                                        class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors duration-150 p-1 rounded" 
                                        title="Sil">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </div>
                            <?php else: ?>
                            <div class="flex items-center justify-end">
                                <div class="text-gray-400 dark:text-gray-500 text-sm">
                                    <i class="fas fa-eye mr-1"></i>
                                    Görüntüleme
                                </div>
                            </div>
                            <?php endif; ?>
                            <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (end) -->
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="text-center p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Pozisyon</div>
                                <div class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($person['position'] ?? 'Belirtilmemiş') ?></div>
                            </div>
                            <div class="text-center p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Maaş</div>
                                <div class="font-semibold text-gray-900 dark:text-white"><?= number_format($person['salary'], 2, ',', '.') ?> ₺</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-calendar mr-1"></i>
                                <?= $person['hire_date'] ? date('d.m.Y', strtotime($person['hire_date'])) : '-' ?>
                            </div>
                            <?php
                            $statusColors = [
                                'active' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                'inactive' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
                                'terminated' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                            ];
                            $statusTexts = [
                                'active' => 'Aktif',
                                'inactive' => 'Pasif',
                                'terminated' => 'İşten Ayrıldı'
                            ];
                            ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $statusColors[$person['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                <?= $statusTexts[$person['status']] ?? ucfirst($person['status']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <!-- ===== KOZMOS_STAFF_FIX: add responsive wrapper (end) -->
    </div>
</div>

<script>
// Sayfa yüklendiğinde istatistikleri güncelle
document.addEventListener('DOMContentLoaded', function() {
    updateStats();
});

function updateStats() {
    // Bugün çalışan personel sayısı
    fetch('<?= base_url('/api/staff/today-working') ?>')
        .then(response => response.json())
        .then(data => {
            document.getElementById('today-working').textContent = data.count || 0;
        })
        .catch(error => console.error('Error:', error));

    // Aylık maaş toplamı
    fetch('<?= base_url('/api/staff/monthly-salary') ?>')
        .then(response => response.json())
        .then(data => {
            document.getElementById('monthly-salary').textContent = 
                new Intl.NumberFormat('tr-TR', { 
                    style: 'currency', 
                    currency: 'TRY' 
                }).format(data.total || 0);
        })
        .catch(error => console.error('Error:', error));
}

function deleteStaff(id) {
    if (confirm('Bu personeli silmek istediğinizden emin misiniz?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= base_url('/staff/delete/') ?>' + id;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= CSRF::get() ?>';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

// ===== KOZMOS_BULK_OPERATIONS: bulk operations JavaScript (begin)
function toggleBulkMode() {
    const bulkToggleBtn = document.getElementById('bulkToggleBtn');
    const bulkActions = document.getElementById('bulkActions');
    const checkboxes = document.querySelectorAll('.staff-checkbox');
    
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
    const checkboxes = document.querySelectorAll('.staff-checkbox');
    
    bulkActions.classList.add('hidden');
    bulkToggleBtn.classList.remove('bg-primary-600', 'text-white');
    bulkToggleBtn.classList.add('bg-white', 'text-gray-700');
    checkboxes.forEach(checkbox => {
        checkbox.style.display = 'none';
        checkbox.checked = false;
    });
}

function toggleAllStaff(checkbox) {
    const checkboxes = document.querySelectorAll('.staff-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function selectAllStaff() {
    const checkboxes = document.querySelectorAll('.staff-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = true);
}

function getSelectedStaff() {
    const checkboxes = document.querySelectorAll('.staff-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function bulkStatusUpdateStaff() {
    const selectedIds = getSelectedStaff();
    
    if (selectedIds.length === 0) {
        alert('Lütfen güncellenecek personeli seçin.');
        return;
    }
    
    const status = prompt('Yeni durum girin (ACTIVE, INACTIVE):');
    if (!status || !['ACTIVE', 'INACTIVE'].includes(status.toUpperCase())) {
        alert('Geçerli bir durum girin: ACTIVE, INACTIVE');
        return;
    }
    
    if (!confirm(`${selectedIds.length} personelin durumunu "${status.toUpperCase()}" olarak güncellemek istediğinizden emin misiniz?`)) {
        return;
    }
    
    // Form oluştur ve gönder
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('/staff/bulk-status-update') ?>';
    
    // CSRF token ekle
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = '<?= CSRF::get() ?>';
    form.appendChild(csrfInput);
    
    // Status ekle
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'status';
    statusInput.value = status.toUpperCase();
    form.appendChild(statusInput);
    
    // Seçili ID'leri ekle
    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'staff_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

function bulkDeleteStaff() {
    const selectedIds = getSelectedStaff();
    
    if (selectedIds.length === 0) {
        alert('Lütfen silinecek personeli seçin.');
        return;
    }
    
    if (!confirm(`${selectedIds.length} personeli silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`)) {
        return;
    }
    
    // Form oluştur ve gönder
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('/staff/bulk-delete') ?>';
    
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
        input.name = 'staff_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

// Initialize - hide checkboxes by default
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.staff-checkbox');
    checkboxes.forEach(checkbox => checkbox.style.display = 'none');
});
// ===== KOZMOS_BULK_OPERATIONS: bulk operations JavaScript (end)
</script>
