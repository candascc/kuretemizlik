<?php 
$isEdit = !empty($building);
?>
<div class="space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-building mr-3 text-primary-600"></i>
            <?= $isEdit ? 'Bina Düzenle' : 'Yeni Bina Ekle' ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2"><?= $isEdit ? 'Bina bilgilerini güncelleyin' : 'Yeni bir bina kaydı oluşturun' ?></p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= $isEdit ? base_url("/buildings/update/{$building['id']}") : base_url('/buildings/create') ?>" role="form" aria-describedby="buildings-form-errors" novalidate data-validate="true">
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
                                Bina Adı <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="<?= $isEdit ? e($building['name']) : '' ?>" 
                                   required aria-required="true" aria-invalid="false" aria-describedby="name-error name-hint" data-validate="required|min:3|max:100" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <p id="name-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="name-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">3-100 karakter arası bir isim giriniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bina Tipi <span class="text-red-500">*</span>
                            </label>
                            <select name="building_type" required class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white" aria-required="true" aria-invalid="false" aria-describedby="building_type-error building_type-hint" data-validate="required">
                                <option value="apartman" <?= ($isEdit && $building['building_type'] === 'apartman') ? 'selected' : '' ?>>Apartman</option>
                            <p id="building_type-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="building_type-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bina tipini seçiniz</p>
                                <option value="site" <?= ($isEdit && $building['building_type'] === 'site') ? 'selected' : '' ?>>Site</option>
                                <option value="plaza" <?= ($isEdit && $building['building_type'] === 'plaza') ? 'selected' : '' ?>>Plaza</option>
                                <option value="rezidans" <?= ($isEdit && $building['building_type'] === 'rezidans') ? 'selected' : '' ?>>Rezidans</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Adres <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="address_line" value="<?= $isEdit ? e($building['address_line']) : '' ?>" 
                                   required aria-required="true" aria-invalid="false" aria-describedby="address_line-error address_line-hint" data-validate="required|min:5|max:200" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <p id="address_line-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="address_line-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Adres satırı giriniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                İlçe
                            </label>
                            <input type="text" name="district" value="<?= $isEdit ? htmlspecialchars($building['district'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Şehir <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="city" value="<?= $isEdit ? e($building['city']) : 'İstanbul' ?>" 
                                   required aria-required="true" aria-invalid="false" aria-describedby="city-error city-hint" data-validate="required|min:2|max:50" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <p id="city-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="city-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Şehir adı giriniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Posta Kodu
                            </label>
                            <input type="text" name="postal_code" value="<?= $isEdit ? htmlspecialchars($building['postal_code'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Toplam Daire Sayısı <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="total_units" value="<?= $isEdit ? $building['total_units'] : '' ?>" 
                                   required min="1" aria-required="true" aria-invalid="false" aria-describedby="total_units-error total_units-hint" inputmode="numeric" data-validate="required|numeric|min:1" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <p id="total_units-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="total_units-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Toplam daire sayısını giriniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Toplam Kat Sayısı
                            </label>
                            <input type="number" name="total_floors" value="<?= $isEdit ? ($building['total_floors'] ?? '') : '' ?>" 
                                   min="1" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yönetici Bilgileri -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-user-tie mr-2 text-primary-600"></i>
                        Yönetici Bilgileri
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Yönetici Adı
                            </label>
                            <input type="text" name="manager_name" value="<?= $isEdit ? htmlspecialchars($building['manager_name'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Yönetici Telefon
                            </label>
                            <input type="tel" name="manager_phone" value="<?= $isEdit ? htmlspecialchars($building['manager_phone'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Yönetici Email
                            </label>
                            <input type="email" name="manager_email" value="<?= $isEdit ? htmlspecialchars($building['manager_email'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Finansal Bilgiler -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Aidat Günü (Ayın Kaçı)
                        </label>
                        <input type="number" name="monthly_maintenance_day" value="<?= $isEdit ? ($building['monthly_maintenance_day'] ?? 1) : 1 ?>" 
                               min="1" max="28" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Durum
                        </label>
                        <select name="status" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <option value="active" <?= ($isEdit && ($building['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Aktif</option>
                            <option value="inactive" <?= ($isEdit && ($building['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Pasif</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                <a href="<?= $isEdit ? base_url("/buildings/{$building['id']}") : base_url('/buildings') ?>" 
                   class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                    İptal
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors font-medium"
                        style="color: white !important; background: #4f46e5 !important;">
                    <i class="fas fa-save mr-2" style="color: white !important;"></i>
                    <span style="color: white !important;"><?= $isEdit ? 'Güncelle' : 'Kaydet' ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

