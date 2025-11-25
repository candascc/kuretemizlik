<div class="space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-magic mr-3 text-primary-600"></i>
            Aylık Aidat Oluştur
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Seçilen bina veya tüm binalar için aylık aidatlar oluşturun</p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= base_url('/management-fees/generate') ?>" id="generateForm">
            <?= CSRF::field() ?>

            <!-- Bina Seçimi -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-building mr-2 text-primary-600"></i>
                        Bina Seçimi
                    </h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="flex items-center mb-4">
                                <input type="radio" name="generate_type" value="all" checked 
                                       onclick="toggleBuildingSelect(false)"
                                       class="w-5 h-5 text-primary-600 border-gray-300 focus:ring-primary-500">
                                <span class="ml-3 text-gray-700 dark:text-gray-300 font-medium">Tüm Aktif Binalar</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="generate_type" value="single" 
                                       onclick="toggleBuildingSelect(true)"
                                       class="w-5 h-5 text-primary-600 border-gray-300 focus:ring-primary-500">
                                <span class="ml-3 text-gray-700 dark:text-gray-300 font-medium">Tek Bina Seç</span>
                            </label>
                        </div>

                        <div id="buildingSelect" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bina Seçin <span class="text-red-500">*</span>
                            </label>
                            <select name="building_id" 
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Bina Seçin</option>
                                <?php foreach ($buildings as $bld): ?>
                                    <option value="<?= $bld['id'] ?>" 
                                            <?= (isset($buildingId) && $buildingId == $bld['id']) ? 'selected' : '' ?>>
                                        <?= e($bld['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dönem Bilgileri -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-calendar mr-2 text-primary-600"></i>
                        Dönem Bilgileri
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Dönem <span class="text-red-500">*</span>
                            </label>
                            <input type="month" name="period" 
                                   value="<?= date('Y-m') ?>" 
                                   required
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Aidatın hangi ay için oluşturulacağını seçin (YYYY-MM)</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Vade Tarihi <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="due_date" 
                                   value="<?= date('Y-m-15') ?>" 
                                   required
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Gecikme Ceza Oranı (%)
                            </label>
                            <input type="number" name="late_fee_rate" 
                                   value="5" step="0.1" min="0" max="100"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Vade tarihinden sonra her ay için uygulanacak gecikme cezası yüzdesi</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                İndirim Tutarı (₺)
                            </label>
                            <input type="number" name="default_discount" 
                                   value="0" step="0.01" min="0"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Tüm dairelere uygulanacak varsayılan indirim (isteğe bağlı)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Önizleme -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-eye mr-2 text-primary-600"></i>
                        Önizleme
                    </h2>
                </div>
                <div class="p-6">
                    <div id="preview" class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                        <p class="text-gray-600 dark:text-gray-400">Önizleme için form bilgilerini doldurun ve "Önizleme Göster" butonuna tıklayın</p>
                    </div>
                    <button type="button" onclick="loadPreview()" 
                            class="mt-4 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg">
                        <i class="fas fa-eye mr-2"></i>Önizleme Göster
                    </button>
                </div>
            </div>

            <!-- Seçenekler -->
            <div class="p-6">
                <div class="space-y-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="override_existing" value="1" 
                               class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <span class="ml-3 text-gray-700 dark:text-gray-300">Mevcut aidatları güncelle (aynı dönem için)</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="send_notifications" value="1" checked
                               class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <span class="ml-3 text-gray-700 dark:text-gray-300">Daire sahiplerine bildirim gönder (e-posta/SMS)</span>
                    </label>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end space-x-3">
                <a href="<?= base_url('/management-fees') ?>" 
                   class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    İptal
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium">
                    <i class="fas fa-magic mr-2"></i>Aidat Oluştur
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleBuildingSelect(show) {
    const select = document.getElementById('buildingSelect');
    const selectElement = select.querySelector('select[name="building_id"]');
    if (show) {
        select.style.display = 'block';
        selectElement.required = true;
    } else {
        select.style.display = 'none';
        selectElement.required = false;
        selectElement.value = '';
    }
}

function loadPreview() {
    const form = document.getElementById('generateForm');
    const formData = new FormData(form);
    
    // AJAX request for preview
    fetch('<?= base_url("/management-fees/preview") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const preview = document.getElementById('preview');
        if (data.success) {
            preview.innerHTML = `
                <div class="space-y-2">
                    <p class="font-semibold">Önizleme:</p>
                    <p>Bina Sayısı: ${data.buildings_count || 0}</p>
                    <p>Daire Sayısı: ${data.units_count || 0}</p>
                    <p>Toplam Tutar: ${data.total_amount || 0} ₺</p>
                </div>
            `;
        } else {
            preview.innerHTML = `<p class="text-red-600">${data.message || 'Önizleme yüklenirken hata oluştu'}</p>`;
        }
    })
    .catch(error => {
        const preview = document.getElementById('preview');
        preview.innerHTML = `<p class="text-red-600">Önizleme yüklenirken hata oluştu</p>`;
    });
}
</script>

