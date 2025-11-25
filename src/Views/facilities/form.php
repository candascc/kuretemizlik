<?php 
$isEdit = !empty($facility);
?>
<div class="space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-hotel mr-3 text-primary-600"></i>
            <?= $isEdit ? 'Alan Düzenle' : 'Yeni Alan Ekle' ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            <?= $isEdit ? 'Alan bilgilerini güncelleyin' : 'Ortak kullanım alanı kaydı oluşturun' ?>
        </p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= $isEdit ? base_url("/facilities/update/{$facility['id']}") : base_url('/facilities/create') ?>" 
              role="form" aria-describedby="facility-form-errors" novalidate>
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
                            <select name="building_id" required
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Bina Seçin</option>
                                <?php foreach ($buildings as $bld): ?>
                                    <option value="<?= $bld['id'] ?>" 
                                            <?= (($isEdit && $facility['building_id'] == $bld['id']) || (isset($buildingId) && $buildingId == $bld['id'])) ? 'selected' : '' ?>>
                                        <?= e($bld['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Alan Adı <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="facility_name" 
                                   value="<?= $isEdit ? htmlspecialchars($facility['facility_name'] ?? '') : '' ?>" 
                                   required placeholder="Örn: Çocuk Oyun Alanı"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Alan Tipi
                            </label>
                            <select name="facility_type"
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                <option value="pool" <?= ($isEdit && ($facility['facility_type'] ?? '') === 'pool') ? 'selected' : '' ?>>Havuz</option>
                                <option value="gym" <?= ($isEdit && ($facility['facility_type'] ?? '') === 'gym') ? 'selected' : '' ?>>Spor Salonu</option>
                                <option value="meeting" <?= ($isEdit && ($facility['facility_type'] ?? '') === 'meeting') ? 'selected' : '' ?>>Toplantı Salonu</option>
                                <option value="parking" <?= ($isEdit && ($facility['facility_type'] ?? '') === 'parking') ? 'selected' : '' ?>>Otopark</option>
                                <option value="playground" <?= ($isEdit && ($facility['facility_type'] ?? '') === 'playground') ? 'selected' : '' ?>>Oyun Alanı</option>
                                <option value="other" <?= ($isEdit && ($facility['facility_type'] ?? '') === 'other') ? 'selected' : '' ?>>Diğer</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kapasite
                            </label>
                            <input type="number" name="capacity" 
                                   value="<?= $isEdit ? ($facility['capacity'] ?? '') : '' ?>" 
                                   min="1" placeholder="Kişi sayısı"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Saatlik Ücret (₺)
                            </label>
                            <input type="number" name="hourly_rate" 
                                   value="<?= $isEdit ? ($facility['hourly_rate'] ?? '0') : '0' ?>" 
                                   min="0" step="0.01"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Günlük Ücret (₺)
                            </label>
                            <input type="number" name="daily_rate" 
                                   value="<?= $isEdit ? ($facility['daily_rate'] ?? '0') : '0' ?>" 
                                   min="0" step="0.01"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Maksimum İleride Rezervasyon (Gün)
                            </label>
                            <input type="number" name="max_advance_days" 
                                   value="<?= $isEdit ? ($facility['max_advance_days'] ?? '30') : '30' ?>" 
                                   min="1" max="365"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="requires_approval" id="requires_approval"
                                   <?= ($isEdit && !empty($facility['requires_approval'])) ? 'checked' : '' ?>
                                   class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <label for="requires_approval" class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Onay Gerekli
                            </label>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Açıklama
                        </label>
                        <textarea name="description" rows="4"
                                  placeholder="Alan hakkında detaylı bilgi..."
                                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"><?= $isEdit ? htmlspecialchars($facility['description'] ?? '') : '' ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 flex justify-end space-x-3">
                <a href="<?= base_url('/facilities') ?>" 
                   class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors">
                    İptal
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors font-medium"
                        style="color: white !important; background: #4f46e5 !important;">
                    <i class="fas fa-save mr-2" style="color: white !important;"></i>
                    <span style="color: white !important;"><?= $isEdit ? 'Güncelle' : 'Kaydet' ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

