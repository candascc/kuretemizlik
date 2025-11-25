<div class="space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-calendar-plus mr-3 text-primary-600"></i>
            Yeni Rezervasyon
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Ortak kullanım alanı rezervasyonu oluşturun</p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= base_url('/reservations/create') ?>" 
              role="form" aria-describedby="reservation-form-errors" novalidate>
            <?= CSRF::field() ?>

            <!-- Temel Bilgiler -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-info-circle mr-2 text-primary-600"></i>
                        Temel Bilgiler
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bina <span class="text-red-500">*</span>
                            </label>
                            <select name="building_id" required id="building_id"
                                    onchange="loadFacilities(this.value)"
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Bina Seçin</option>
                                <?php foreach ($buildings as $bld): ?>
                                    <option value="<?= $bld['id'] ?>" <?= (isset($buildingId) && $buildingId == $bld['id']) ? 'selected' : '' ?>>
                                        <?= e($bld['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Alan <span class="text-red-500">*</span>
                            </label>
                            <select name="facility_id" required id="facility_id"
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Alan Seçin</option>
                                <?php foreach ($facilities as $fac): ?>
                                    <option value="<?= $fac['id'] ?>" <?= (isset($facilityId) && $facilityId == $fac['id']) ? 'selected' : '' ?>>
                                        <?= e($fac['facility_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Daire (Opsiyonel)
                            </label>
                            <select name="unit_id" id="unit_id"
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Daire Seçin</option>
                                <?php foreach ($units as $unit): ?>
                                    <option value="<?= $unit['id'] ?>">
                                        <?= e($unit['unit_number']) ?> - <?= htmlspecialchars($unit['owner_name'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Rezervasyon Tipi
                            </label>
                            <select name="reservation_type"
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                <option value="hourly">Saatlik</option>
                                <option value="daily">Günlük</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Rezerve Eden <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="resident_name" required
                                   placeholder="Ad Soyad"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Telefon
                            </label>
                            <input type="tel" name="resident_phone"
                                   placeholder="0(5XX) XXX XX XX"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarih ve Saat -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-calendar-alt mr-2 text-primary-600"></i>
                        Tarih ve Saat
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Başlangıç Tarihi <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="start_date" required
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Başlangıç Saati <span class="text-red-500">*</span>
                            </label>
                            <input type="time" name="start_time" required
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bitiş Tarihi <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="end_date" required
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bitiş Saati <span class="text-red-500">*</span>
                            </label>
                            <input type="time" name="end_time" required
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notlar -->
            <div>
                <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-sticky-note mr-2 text-primary-600"></i>
                        Ek Bilgiler
                    </h2>
                </div>
                <div class="p-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Notlar
                        </label>
                        <textarea name="notes" rows="4"
                                  placeholder="Rezervasyon hakkında özel notlar..."
                                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 flex justify-end space-x-3">
                <a href="<?= base_url('/reservations') ?>" 
                   class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors">
                    İptal
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors font-medium"
                        style="color: white !important; background: #4f46e5 !important;">
                    <i class="fas fa-save mr-2" style="color: white !important;"></i>
                    <span style="color: white !important;">Rezerve Et</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function loadFacilities(buildingId) {
    if (!buildingId) {
        document.getElementById('facility_id').innerHTML = '<option value="">Alan Seçin</option>';
        return;
    }
    
    fetch(`<?= base_url('/api/buildings') ?>/${buildingId}/facilities`)
        .then(response => response.json())
        .then(data => {
            const facilitySelect = document.getElementById('facility_id');
            facilitySelect.innerHTML = '<option value="">Alan Seçin</option>';
            
            if (data.success && data.facilities) {
                data.facilities.forEach(facility => {
                    const option = document.createElement('option');
                    option.value = facility.id;
                    option.textContent = facility.facility_name;
                    facilitySelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading facilities:', error);
        });
}
</script>

