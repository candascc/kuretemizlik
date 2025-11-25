<?php 
$isEdit = !empty($unit);
?>
<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-home mr-3 text-primary-600"></i>
                <?= $isEdit ? 'Daire Düzenle' : 'Yeni Daire Ekle' ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                <?= $isEdit ? 'Daire bilgilerini güncelleyin' : 'Yeni bir daire kaydı oluşturun' ?>
                <?php if (isset($building)): ?>
                    - <a href="<?= base_url('/buildings/' . $building['id']) ?>" 
                         class="text-primary-600 hover:text-primary-700 underline">
                        <?= e($building['name']) ?>
                    </a>
                <?php endif; ?>
            </p>
        </div>
        <?php if ($isEdit): ?>
            <a href="<?= base_url('/units/' . $unit['id']) ?>" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Geri
            </a>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= $isEdit ? base_url("/units/update/{$unit['id']}") : base_url('/units/create') ?>" role="form" aria-describedby="units-form-errors" novalidate data-validate="true">
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bina <span class="text-red-500">*</span>
                            </label>
                            <select name="building_id" required 
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                                    <?= isset($building) && $isEdit === false ? 'readonly' : '' ?>
                                    aria-required="true" aria-invalid="false" aria-describedby="building-error building-hint" data-validate="required|numeric|min:1"> 
                                <option value="">Bina Seçin</option>
                                <?php foreach ($buildings as $bld): ?>
                                    <option value="<?= $bld['id'] ?>" 
                                            <?= (($isEdit && $unit['building_id'] == $bld['id']) || (isset($building) && $building['id'] == $bld['id'])) ? 'selected' : '' ?>>
                                        <?= e($bld['name']) ?> (<?= e($bld['building_type']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p id="building-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="building-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bir bina seçiniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Daire No <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="unit_number" value="<?= $isEdit ? e($unit['unit_number']) : '' ?>" 
                                   required placeholder="12A, D:5, Blok A-12"
                                   aria-required="true" aria-invalid="false" aria-describedby="unit_number-error unit_number-hint" data-validate="required|min:1|max:20"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <p id="unit_number-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="unit_number-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Daire numarasını giriniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Daire Tipi <span class="text-red-500">*</span>
                            </label>
                            <select name="unit_type" required 
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white" aria-required="true" aria-invalid="false" aria-describedby="unit_type-error unit_type-hint" data-validate="required">
                                <option value="daire" <?= ($isEdit && $unit['unit_type'] === 'daire') ? 'selected' : '' ?>>Daire</option>
                                <option value="dubleks" <?= ($isEdit && $unit['unit_type'] === 'dubleks') ? 'selected' : '' ?>>Dubleks</option>
                                <option value="ofis" <?= ($isEdit && $unit['unit_type'] === 'ofis') ? 'selected' : '' ?>>Ofis</option>
                                <option value="dukkán" <?= ($isEdit && $unit['unit_type'] === 'dukkán') ? 'selected' : '' ?>>Dükkan</option>
                                <option value="depo" <?= ($isEdit && $unit['unit_type'] === 'depo') ? 'selected' : '' ?>>Depo</option>
                            </select>
                            <p id="unit_type-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="unit_type-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bir daire tipi seçiniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kat
                            </label>
                            <input type="number" name="floor_number" value="<?= $isEdit ? ($unit['floor_number'] ?? '') : '' ?>" 
                                   min="-2" max="100"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Net Alan (m²)
                            </label>
                            <input type="number" name="net_area" value="<?= $isEdit ? ($unit['net_area'] ?? '') : '' ?>" 
                                   step="0.01" min="0"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Oda Sayısı
                            </label>
                            <input type="text" name="room_count" value="<?= $isEdit ? htmlspecialchars($unit['room_count'] ?? '') : '' ?>" 
                                   placeholder="2+1, 3+1, Studio"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Aylık Aidat (₺) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="monthly_fee" value="<?= $isEdit ? ($unit['monthly_fee'] ?? '0') : '0' ?>" 
                                   required step="0.01" min="0"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Otopark Sayısı
                            </label>
                            <input type="number" name="parking_count" value="<?= $isEdit ? ($unit['parking_count'] ?? '0') : '0' ?>" 
                                   min="0"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Depo Sayısı
                            </label>
                            <input type="number" name="storage_count" value="<?= $isEdit ? ($unit['storage_count'] ?? '0') : '0' ?>" 
                                   min="0"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sahip Bilgileri -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-user mr-2 text-primary-600"></i>
                        Sahip Bilgileri
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Sahip Tipi <span class="text-red-500">*</span>
                            </label>
                            <select name="owner_type" required 
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                <option value="owner" <?= ($isEdit && $unit['owner_type'] === 'owner') ? 'selected' : '' ?>>Mal Sahibi</option>
                                <option value="tenant" <?= ($isEdit && $unit['owner_type'] === 'tenant') ? 'selected' : '' ?>>Kiracı</option>
                                <option value="empty" <?= ($isEdit && $unit['owner_type'] === 'empty') ? 'selected' : '' ?>>Boş</option>
                                <option value="company" <?= ($isEdit && $unit['owner_type'] === 'company') ? 'selected' : '' ?>>Şirket</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Ad Soyad <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="owner_name" value="<?= $isEdit ? e($unit['owner_name']) : '' ?>" 
                                   required
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Telefon
                            </label>
                            <input type="tel" name="owner_phone" value="<?= $isEdit ? htmlspecialchars($unit['owner_phone'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                E-posta
                            </label>
                            <input type="email" name="owner_email" value="<?= $isEdit ? htmlspecialchars($unit['owner_email'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                TC/Vergi No
                            </label>
                            <input type="text" name="owner_id_number" value="<?= $isEdit ? htmlspecialchars($unit['owner_id_number'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Adres
                            </label>
                            <input type="text" name="owner_address" value="<?= $isEdit ? htmlspecialchars($unit['owner_address'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kiracı Bilgileri -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-user-tie mr-2 text-primary-600"></i>
                        Kiracı Bilgileri (Opsiyonel)
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kiracı Adı
                            </label>
                            <input type="text" name="tenant_name" value="<?= $isEdit ? htmlspecialchars($unit['tenant_name'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kiracı Telefon
                            </label>
                            <input type="tel" name="tenant_phone" value="<?= $isEdit ? htmlspecialchars($unit['tenant_phone'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kiracı E-posta
                            </label>
                            <input type="email" name="tenant_email" value="<?= $isEdit ? htmlspecialchars($unit['tenant_email'] ?? '') : '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Sözleşme Başlangıç
                            </label>
                            <input type="date" name="tenant_contract_start" value="<?= $isEdit && $unit['tenant_contract_start'] ? date('Y-m-d', strtotime($unit['tenant_contract_start'])) : '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Sözleşme Bitiş
                            </label>
                            <input type="date" name="tenant_contract_end" value="<?= $isEdit && $unit['tenant_contract_end'] ? date('Y-m-d', strtotime($unit['tenant_contract_end'])) : '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ek Bilgiler -->
            <div>
                <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900/20 dark:to-slate-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-notes-medical mr-2 text-primary-600"></i>
                        Ek Bilgiler
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Durum <span class="text-red-500">*</span>
                            </label>
                            <select name="status" required 
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                <option value="active" <?= ($isEdit && ($unit['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Aktif</option>
                                <option value="inactive" <?= ($isEdit && ($unit['status'] ?? 'active') === 'inactive') ? 'selected' : '' ?>>Pasif</option>
                                <option value="sold" <?= ($isEdit && ($unit['status'] ?? 'active') === 'sold') ? 'selected' : '' ?>>Satıldı</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notlar
                            </label>
                            <textarea name="notes" rows="4" 
                                      class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"><?= $isEdit ? htmlspecialchars($unit['notes'] ?? '') : '' ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end space-x-3">
                <a href="<?= $isEdit ? base_url('/units/' . $unit['id']) : base_url('/units') ?>" 
                   class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
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

