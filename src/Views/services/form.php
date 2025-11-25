<?php 
$isEdit = !empty($service);
?>
<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="<?= base_url('/services') ?>" class="text-gray-400 hover:text-primary-600"><i class="fas fa-concierge-bell"></i></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-500"><?= $isEdit ? 'Düzenle' : 'Yeni Hizmet' ?></span></li>
        </ol>
    </nav>

    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-concierge-bell mr-3 text-primary-600"></i>
            <?= $isEdit ? 'Hizmeti Düzenle' : 'Yeni Hizmet' ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2"><?= $isEdit ? 'Hizmet bilgilerini güncelleyin' : 'Yeni bir hizmet kaydı oluşturun' ?></p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= $isEdit ? base_url("/services/update/{$service['id']}") : base_url('/services/create') ?>" 
              x-data="servicesForm()"
              @submit="handleFormSubmit($event)"
              role="form" aria-describedby="service-form-errors" novalidate data-validate="true">
            <?= CSRF::field() ?>

            <!-- SECTION 1: Temel Bilgiler -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-info-circle mr-2 text-primary-600"></i>
                        Temel Bilgiler
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Hizmet adı ve detayları</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="service_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-tag mr-2 text-primary-600"></i>
                    Hizmet Adı <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       id="service_name"
                       value="<?= $isEdit ? e($service['name']) : '' ?>" 
                       class="w-full px-4 py-3 border border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200" 
                       required 
                       placeholder="Örn: Genel Temizlik"
                       aria-label="Hizmet adı"
                       aria-required="true"
                       aria-invalid="false"
                       aria-describedby="service_name_error service_name_desc"
                       data-validate="required|min:3|max:100"
                       autocomplete="off">
                <p id="service_name_error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                <p id="service_name_desc" class="mt-1 text-sm text-gray-500 dark:text-gray-400 sr-only">Hizmet adını girin</p>
            </div>
            
            <div>
                <label for="duration_min" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-clock mr-2 text-primary-600"></i>
                    Süre (Dakika)
                </label>
                <input type="number" 
                       name="duration_min" 
                       id="duration_min"
                       value="<?= $isEdit ? htmlspecialchars($service['duration_min'] ?? '') : '' ?>" 
                       class="w-full px-4 py-3 border border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200" 
                       placeholder="Örn: 120"
                       aria-label="Tahmini tamamlanma süresi"
                       aria-describedby="duration_min_error duration_min_desc"
                       inputmode="numeric"
                       pattern="[0-9]*"
                       data-validate="min:0|numeric">
                <p id="duration_min_error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                <p id="duration_min_desc" class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tahmini tamamlanma süresi (dakika)</p>
            </div>
            
            <div>
                <label for="default_fee" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-lira-sign mr-2 text-primary-600"></i>
                    Varsayılan Ücret (₺)
                </label>
                <input type="number" 
                       step="0.01" 
                       name="default_fee" 
                       id="default_fee"
                       value="<?= $isEdit ? htmlspecialchars($service['default_fee'] ?? '') : '' ?>" 
                       class="w-full px-4 py-3 border border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200" 
                       placeholder="0.00"
                       aria-label="Varsayılan ücret"
                       aria-describedby="default_fee_error default_fee_desc"
                       inputmode="decimal"
                       data-validate="numeric|min:0">
                <p id="default_fee_error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                <p id="default_fee_desc" class="mt-1 text-sm text-gray-500 dark:text-gray-400">Bu hizmet için önerilen ücret</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-toggle-on mr-2 text-primary-600"></i>
                    Durum
                </label>
                <div class="mt-2">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_active" value="1" <?= $isEdit && $service['is_active'] ? 'checked' : 'checked' ?>
                               class="rounded border-2 border-gray-300 dark:border-gray-600 text-primary-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pasif hizmetler yeni işlerde seçilemez</p>
                </div>
            </div>
        </div>

        <?php if ($isEdit): ?>
        <!-- SECTION 2: Sözleşme Şablonu -->
        <?php
        // Service için sözleşme şablonu bilgisi
        // $contractTemplate controller'dan geliyor (ServiceController::edit)
        if (!isset($contractTemplate)) {
            // Fallback: Eğer controller'dan gelmediyse burada hesapla (geriye uyumluluk)
            require_once __DIR__ . '/../../Services/ContractTemplateService.php';
            require_once __DIR__ . '/../../Models/ContractTemplate.php';
            $templateService = new ContractTemplateService();
            $templateModel = new ContractTemplate();
            $serviceKey = $templateService->normalizeServiceName($service['name']);
            $contractTemplate = null;
            if ($serviceKey) {
                $contractTemplate = $templateModel->findByTypeAndServiceKey('cleaning_job', $serviceKey, false);
            }
        }
        ?>
        <div class="border-t border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-file-contract mr-2 text-purple-600"></i>
                    Hizmet Sözleşme Şablonu
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Bu hizmet için kullanılacak sözleşme şablonu</p>
            </div>
            <div class="p-6">
                <?php if ($contractTemplate): ?>
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
                        <div class="flex items-start">
                            <i class="fas fa-check-circle text-green-600 dark:text-green-400 mt-1 mr-3"></i>
                            <div class="flex-1">
                                <h3 class="text-sm font-semibold text-green-900 dark:text-green-100 mb-1">
                                    Özel Sözleşme Şablonu Mevcut
                                </h3>
                                <p class="text-sm text-green-700 dark:text-green-300 mb-2">
                                    <strong>Şablon Adı:</strong> <?= e($contractTemplate['name']) ?><br>
                                    <strong>Versiyon:</strong> <?= htmlspecialchars($contractTemplate['version'] ?? '1.0') ?><br>
                                    <strong>Durum:</strong> 
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $contractTemplate['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' ?>">
                                        <?= $contractTemplate['is_active'] ? 'Aktif' : 'Pasif' ?>
                                    </span>
                                </p>
                                <?php if (!empty($contractTemplate['template_text'])): ?>
                                    <p class="text-sm text-green-600 dark:text-green-400 mt-2">
                                        <strong>Önizleme:</strong> <?= htmlspecialchars(mb_substr($contractTemplate['template_text'], 0, 200)) ?><?= mb_strlen($contractTemplate['template_text']) > 200 ? '...' : '' ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <a href="<?= base_url("/services/{$service['id']}/contract-template/edit") ?>" 
                       class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors duration-200">
                        <i class="fas fa-edit mr-2"></i>
                        Şablonu Düzenle
                    </a>
                <?php else: ?>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-yellow-600 dark:text-yellow-400 mt-1 mr-3"></i>
                            <div class="flex-1">
                                <h3 class="text-sm font-semibold text-yellow-900 dark:text-yellow-100 mb-1">
                                    Özel Şablon Bulunamadı
                                </h3>
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                    Bu hizmet için henüz özel bir sözleşme şablonu oluşturulmamış. Genel temizlik sözleşmesi kullanılacaktır.
                                </p>
                            </div>
                        </div>
                    </div>
                    <a href="<?= base_url("/services/{$service['id']}/contract-template/edit") ?>" 
                       class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors duration-200">
                        <i class="fas fa-plus mr-2"></i>
                        Yeni Şablon Oluştur
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Form Actions -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-4 border-t border-gray-200 dark:border-gray-700">
            <a href="<?= base_url('/services') ?>" 
               class="px-6 py-3 rounded-lg bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 font-medium transition-all duration-200"
               aria-label="İptal ve geri dön">
                <i class="fas fa-times mr-2"></i>İptal
            </a>
            <button type="submit" 
                    :disabled="isSubmitting"
                    :class="{'opacity-50 cursor-not-allowed': isSubmitting}"
                    class="px-6 py-3 bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50 font-medium transition-colors duration-200"
                    style="color: white !important; background: #4f46e5 !important;"
                    aria-label="<?= $isEdit ? 'Hizmeti güncelle' : 'Yeni hizmet oluştur' ?>">
                <span x-show="!isSubmitting" style="color: white !important;">
                    <i class="fas fa-save mr-2" style="color: white !important;"></i>
                    <?= $isEdit ? 'Güncelle' : 'Kaydet' ?>
                </span>
                <span x-show="isSubmitting" style="color: white !important;">
                    <i class="fas fa-spinner fa-spin mr-2" style="color: white !important;"></i>
                    Kaydediliyor...
                </span>
            </button>
        </div>
    </form>
</div>

<script>
function servicesForm() {
    return {
        isSubmitting: false,
        
        handleFormSubmit(event) {
            if (this.isSubmitting) {
                event.preventDefault();
                return false;
            }
            
            this.isSubmitting = true;
            return true;
        }
    }
}
</script>
