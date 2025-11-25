<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Doküman Yükle</h1>
            <p class="text-gray-600 dark:text-gray-400">Bina veya daire dokümanı yükleyin</p>
        </div>
        <a href="<?= base_url('/documents') ?>" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg">
            <i class="fas fa-arrow-left mr-2"></i>
            Geri Dön
        </a>
    </div>

    <!-- Upload Form -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <?= CSRF::field() ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="building_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bina *</label>
                    <select name="building_id" id="building_id" 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white" 
                            required>
                        <option value="">Bina Seçin</option>
                        <?php foreach ($buildings as $building): ?>
                            <option value="<?= $building['id'] ?>" <?= ($buildingId ?? '') == $building['id'] ? 'selected' : '' ?>>
                                <?= e($building['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="unit_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Daire (Opsiyonel)</label>
                    <select name="unit_id" id="unit_id" 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Daire Seçin (Opsiyonel)</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?= $unit['id'] ?>" <?= ($unitId ?? '') == $unit['id'] ? 'selected' : '' ?>>
                                <?= e($unit['unit_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label for="document_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Doküman Tipi *</label>
                <select name="document_type" id="document_type" 
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white" 
                        required>
                    <option value="">Tip Seçin</option>
                    <option value="contract">Sözleşme</option>
                    <option value="deed">Tapu</option>
                    <option value="permit">İzin</option>
                    <option value="invoice">Fatura</option>
                    <option value="receipt">Makbuz</option>
                    <option value="insurance">Sigorta</option>
                    <option value="meeting_minutes">Toplantı Tutanağı</option>
                    <option value="announcement">Duyuru</option>
                    <option value="other">Diğer</option>
                </select>
            </div>

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Başlık *</label>
                <input type="text" name="title" id="title" 
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                       placeholder="Doküman başlığını girin" 
                       required>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama</label>
                <textarea name="description" id="description" rows="3" 
                          class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                          placeholder="Doküman hakkında açıklama..."></textarea>
            </div>

            <div>
                <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dosya *</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                    <div class="space-y-1 text-center">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400"></i>
                        <div class="flex text-sm text-gray-600 dark:text-gray-400">
                            <label for="file" class="relative cursor-pointer bg-white dark:bg-gray-700 rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                <span>Dosya seçin</span>
                                <input id="file" name="file" type="file" class="sr-only" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" required>
                            </label>
                            <p class="pl-1">veya sürükleyip bırakın</p>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF (Max: 100MB)
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center">
                <input id="is_public" name="is_public" type="checkbox" 
                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 rounded">
                <label for="is_public" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                    Herkese açık (sakinler de görebilir)
                </label>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="<?= base_url('/documents') ?>" 
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    İptal
                </a>
                <button type="submit" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <i class="fas fa-upload mr-2"></i>
                    Yükle
                </button>
            </div>
        </form>
    </div>

    <!-- Upload Info -->
    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Yükleme Bilgileri</h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    <ul class="list-disc list-inside space-y-1">
                        <li>Maksimum dosya boyutu: 100MB</li>
                        <li>Desteklenen formatlar: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF</li>
                        <li>Dosyalar güvenli şekilde saklanır ve sadece yetkili kişiler erişebilir</li>
                        <li>Herkese açık işaretlenen dokümanlar sakinler tarafından da görüntülenebilir</li>
                    </ul>
                </div>
            </div>
        </div>
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
                unitSelect.innerHTML = '<option value="">Daire Seçin (Opsiyonel)</option>';
                data.units.forEach(unit => {
                    unitSelect.innerHTML += `<option value="${unit.id}">${unit.unit_number}</option>`;
                });
            })
            .catch(error => console.error('Error:', error));
    } else {
        unitSelect.innerHTML = '<option value="">Daire Seçin (Opsiyonel)</option>';
    }
});

// File input change handler
document.getElementById('file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Auto-fill title if empty
        if (!document.getElementById('title').value) {
            document.getElementById('title').value = file.name.replace(/\.[^/.]+$/, "");
        }
        
        // Show file info
        const fileInfo = document.createElement('div');
        fileInfo.className = 'mt-2 text-sm text-gray-600 dark:text-gray-400';
        fileInfo.innerHTML = `
            <strong>Seçilen dosya:</strong> ${file.name}<br>
            <strong>Boyut:</strong> ${(file.size / 1024 / 1024).toFixed(2)} MB
        `;
        
        // Remove previous file info
        const existingInfo = document.querySelector('.file-info');
        if (existingInfo) {
            existingInfo.remove();
        }
        
        fileInfo.className += ' file-info';
        document.getElementById('file').parentNode.appendChild(fileInfo);
    }
});

// Drag and drop functionality
const dropZone = document.querySelector('.border-dashed');
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
    dropZone.classList.add('border-primary-400', 'bg-primary-50');
}

function unhighlight(e) {
    dropZone.classList.remove('border-primary-400', 'bg-primary-50');
}

dropZone.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
        document.getElementById('file').files = files;
        document.getElementById('file').dispatchEvent(new Event('change'));
    }
}
</script>