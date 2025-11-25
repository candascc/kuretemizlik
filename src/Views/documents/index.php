<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bina Dokümanları</h1>
            <p class="text-gray-600 dark:text-gray-400">Bina ve daire dokümanlarını yönetin</p>
        </div>
        <a href="<?= base_url('/documents/upload') ?>" 
           class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg">
            <i class="fas fa-upload mr-2"></i>
            Doküman Yükle
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="building_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bina</label>
                <select name="building_id" id="building_id" 
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tüm Binalar</option>
                    <?php foreach ($buildings as $building): ?>
                        <option value="<?= $building['id'] ?>" <?= ($filters['building_id'] ?? '') == $building['id'] ? 'selected' : '' ?>>
                            <?= e($building['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="unit_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Daire</label>
                <select name="unit_id" id="unit_id" 
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tüm Daireler</option>
                    <?php foreach ($units as $unit): ?>
                        <option value="<?= $unit['id'] ?>" <?= ($filters['unit_id'] ?? '') == $unit['id'] ? 'selected' : '' ?>>
                            <?= e($unit['unit_number']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="document_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tip</label>
                <select name="document_type" id="document_type" 
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tüm Tipler</option>
                    <option value="contract" <?= ($filters['document_type'] ?? '') === 'contract' ? 'selected' : '' ?>>Sözleşme</option>
                    <option value="deed" <?= ($filters['document_type'] ?? '') === 'deed' ? 'selected' : '' ?>>Tapu</option>
                    <option value="permit" <?= ($filters['document_type'] ?? '') === 'permit' ? 'selected' : '' ?>>İzin</option>
                    <option value="invoice" <?= ($filters['document_type'] ?? '') === 'invoice' ? 'selected' : '' ?>>Fatura</option>
                    <option value="receipt" <?= ($filters['document_type'] ?? '') === 'receipt' ? 'selected' : '' ?>>Makbuz</option>
                    <option value="insurance" <?= ($filters['document_type'] ?? '') === 'insurance' ? 'selected' : '' ?>>Sigorta</option>
                    <option value="meeting_minutes" <?= ($filters['document_type'] ?? '') === 'meeting_minutes' ? 'selected' : '' ?>>Toplantı Tutanağı</option>
                    <option value="announcement" <?= ($filters['document_type'] ?? '') === 'announcement' ? 'selected' : '' ?>>Duyuru</option>
                    <option value="other" <?= ($filters['document_type'] ?? '') === 'other' ? 'selected' : '' ?>>Diğer</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-md">
                    Filtrele
                </button>
            </div>
        </form>
    </div>

    <!-- Documents List -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <?php if (empty($documents)): ?>
            <div class="p-8 text-center">
                <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">Henüz doküman yüklenmemiş</p>
                <a href="<?= base_url('/documents/upload') ?>" 
                   class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg">
                    <i class="fas fa-upload mr-2"></i>
                    İlk Dokümanı Yükleyin
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Doküman</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tip</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bina/Daire</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Boyut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarih</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($documents as $doc): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-file-alt text-2xl text-gray-400"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                <?= e($doc['title']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                <?= e($doc['file_name']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                        <?= ucfirst($doc['document_type']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($doc['building_name'] ?? 'N/A') ?>
                                    </div>
                                    <?php if ($doc['unit_id']): ?>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            Daire: <?= htmlspecialchars($doc['unit_number'] ?? 'N/A') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= $this->formatFileSize($doc['file_size']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        <?= $doc['is_public'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300' ?>">
                                        <?= $doc['is_public'] ? 'Herkese Açık' : 'Özel' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= date('d.m.Y H:i', strtotime($doc['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="<?= base_url('/documents/view/' . $doc['id']) ?>" 
                                           class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                                           title="Görüntüle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= base_url('/documents/download/' . $doc['id']) ?>" 
                                           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                           title="İndir">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <?php if ($doc['uploaded_by'] == Auth::id() || Auth::hasRole('admin')): ?>
                                            <a href="<?= base_url('/documents/edit/' . $doc['id']) ?>" 
                                               class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300"
                                               title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= base_url('/documents/delete/' . $doc['id']) ?>" 
                                               class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                               title="Sil"
                                               onclick="return confirm('Bu dokümanı silmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
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
                            <a href="?page=<?= $pagination['current_page'] - 1 ?>&building_id=<?= $filters['building_id'] ?? '' ?>&unit_id=<?= $filters['unit_id'] ?? '' ?>&document_type=<?= $filters['document_type'] ?? '' ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Önceki
                            </a>
                        <?php endif; ?>
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <a href="?page=<?= $pagination['current_page'] + 1 ?>&building_id=<?= $filters['building_id'] ?? '' ?>&unit_id=<?= $filters['unit_id'] ?? '' ?>&document_type=<?= $filters['document_type'] ?? '' ?>" 
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Sonraki
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                <span class="font-medium"><?= $pagination['start'] ?></span>
                                -
                                <span class="font-medium"><?= $pagination['end'] ?></span>
                                arası, toplam
                                <span class="font-medium"><?= $pagination['total'] ?></span>
                                kayıt
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                    <a href="?page=<?= $i ?>&building_id=<?= $filters['building_id'] ?? '' ?>&unit_id=<?= $filters['unit_id'] ?? '' ?>&document_type=<?= $filters['document_type'] ?? '' ?>" 
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

<script>
// Auto-update unit dropdown when building changes
document.getElementById('building_id').addEventListener('change', function() {
    const buildingId = this.value;
    const unitSelect = document.getElementById('unit_id');
    
    if (buildingId) {
        // Fetch units for selected building
        fetch(`<?= base_url('/api/units/by-building/') ?>${buildingId}`)
            .then(response => response.json())
            .then(data => {
                unitSelect.innerHTML = '<option value="">Tüm Daireler</option>';
                data.units.forEach(unit => {
                    unitSelect.innerHTML += `<option value="${unit.id}">${unit.unit_number}</option>`;
                });
            })
            .catch(error => console.error('Error:', error));
    } else {
        unitSelect.innerHTML = '<option value="">Tüm Daireler</option>';
    }
});
</script>

<?php
// Helper function for file size formatting
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>