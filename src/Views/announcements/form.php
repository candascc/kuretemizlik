<?php 
$isEdit = !empty($announcement);
?>
<div class="space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-bullhorn mr-3 text-primary-600"></i>
            <?= $isEdit ? 'Duyuru Düzenle' : 'Yeni Duyuru Oluştur' ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            <?= $isEdit ? 'Duyuru bilgilerini güncelleyin' : 'Sakinlere yeni bir duyuru yayınlayın' ?>
        </p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= $isEdit ? base_url("/announcements/update/{$announcement['id']}") : base_url('/announcements/create') ?>" role="form" aria-describedby="announcements-form-errors" novalidate data-validate="true">
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
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white" aria-required="true" aria-invalid="false" aria-describedby="building-error building-hint" data-validate="required|numeric|min:1">
                                <option value="">Bina Seçin</option>
                                <?php foreach ($buildings as $bld): ?>
                                    <option value="<?= $bld['id'] ?>" 
                                            <?= (($isEdit && $announcement['building_id'] == $bld['id']) || (isset($buildingId) && $buildingId == $bld['id'])) ? 'selected' : '' ?>>
                                        <?= e($bld['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p id="building-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="building-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bir bina seçiniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Duyuru Tipi <span class="text-red-500">*</span>
                            </label>
                            <select name="announcement_type" required
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white" aria-required="true" aria-invalid="false" aria-describedby="type-error type-hint" data-validate="required|in:info,warning,urgent,event,maintenance">
                                <option value="info" <?= ($isEdit && $announcement['announcement_type'] === 'info') ? 'selected' : '' ?>>Bilgilendirme</option>
                                <option value="warning" <?= ($isEdit && $announcement['announcement_type'] === 'warning') ? 'selected' : '' ?>>Uyarı</option>
                                <option value="urgent" <?= ($isEdit && $announcement['announcement_type'] === 'urgent') ? 'selected' : '' ?>>Acil</option>
                                <option value="event" <?= ($isEdit && $announcement['announcement_type'] === 'event') ? 'selected' : '' ?>>Etkinlik</option>
                                <option value="maintenance" <?= ($isEdit && $announcement['announcement_type'] === 'maintenance') ? 'selected' : '' ?>>Bakım</option>
                            </select>
                            <p id="type-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="type-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Duyuru tipini seçiniz</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Başlık <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" value="<?= $isEdit ? e($announcement['title']) : '' ?>" 
                                   required placeholder="Duyuru başlığı"
                                   aria-required="true" aria-invalid="false" aria-describedby="title-error title-hint" data-validate="required|min:3|max:150"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <p id="title-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="title-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">3-150 karakter arası başlık giriniz</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                İçerik <span class="text-red-500">*</span>
                            </label>
                            <textarea name="content" rows="8" required
                                      placeholder="Duyuru içeriğini buraya yazın..."
                                      aria-required="true" aria-invalid="false" aria-describedby="content-error content-hint" data-validate="required|min:10"
                                      class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"><?= $isEdit ? e($announcement['content']) : '' ?></textarea>
                            <p id="content-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="content-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">En az 10 karakter içerik giriniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Yayınlanma Tarihi <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" name="publish_date" 
                                   value="<?= $isEdit && $announcement['publish_date'] ? date('Y-m-d\TH:i', strtotime($announcement['publish_date'])) : date('Y-m-d\TH:i') ?>" 
                                   required
                                   aria-required="true" aria-invalid="false" aria-describedby="publish_date-error publish_date-hint" data-validate="required|datetime"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <p id="publish_date-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="publish_date-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Yayınlanma tarihini seçiniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Son Geçerlilik Tarihi
                            </label>
                            <input type="datetime-local" name="expire_date" 
                                   value="<?= $isEdit && $announcement['expire_date'] ? date('Y-m-d\TH:i', strtotime($announcement['expire_date'])) : '' ?>" 
                                   aria-describedby="expire_date-error expire_date-hint" data-validate="datetime"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <p id="expire_date-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="expire_date-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Opsiyonel: son geçerlilik tarihi</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Öncelik
                            </label>
                            <select name="priority"
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                <option value="0" <?= ($isEdit && ($announcement['priority'] ?? 0) == 0) ? 'selected' : '' ?>>Normal</option>
                                <option value="1" <?= ($isEdit && ($announcement['priority'] ?? 0) == 1) ? 'selected' : '' ?>>Yüksek</option>
                                <option value="2" <?= ($isEdit && ($announcement['priority'] ?? 0) == 2) ? 'selected' : '' ?>>Acil</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seçenekler -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-cog mr-2 text-primary-600"></i>
                        Seçenekler
                    </h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_pinned" value="1" 
                                   <?= ($isEdit && ($announcement['is_pinned'] ?? 0) == 1) ? 'checked' : '' ?>
                                   class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="ml-3 text-gray-700 dark:text-gray-300">Sayfada sabitle (pin)</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="send_email" value="1" 
                                   <?= ($isEdit && ($announcement['send_email'] ?? 0) == 1) ? 'checked' : '' ?>
                                   class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="ml-3 text-gray-700 dark:text-gray-300">E-posta ile gönder</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="send_sms" value="1" 
                                   <?= ($isEdit && ($announcement['send_sms'] ?? 0) == 1) ? 'checked' : '' ?>
                                   class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="ml-3 text-gray-700 dark:text-gray-300">SMS ile gönder</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end space-x-3">
                <a href="<?= $isEdit ? base_url('/announcements/' . $announcement['id']) : base_url('/announcements') ?>" 
                   class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    İptal
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors font-medium"
                        style="color: white !important; background: #4f46e5 !important;">
                    <i class="fas fa-save mr-2" style="color: white !important;"></i>
                    <span style="color: white !important;"><?= $isEdit ? 'Güncelle' : 'Yayınla' ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

