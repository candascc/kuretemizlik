<?php 
$isEdit = !empty($template);
?>
<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="<?= base_url('/services') ?>" class="text-gray-400 hover:text-primary-600"><i class="fas fa-concierge-bell"></i></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><a href="<?= base_url("/services/edit/{$service['id']}") ?>" class="text-gray-400 hover:text-primary-600"><?= e($service['name']) ?></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-500">Sözleşme Şablonu</span></li>
        </ol>
    </nav>

    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-file-contract mr-3 text-purple-600"></i>
            Hizmet Sözleşme Şablonu
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            <strong><?= e($service['name']) ?></strong> hizmeti için sözleşme şablonu <?= $isEdit ? 'düzenleme' : 'oluşturma' ?>
        </p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Info Box -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mt-1 mr-3"></i>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                    Sözleşme Şablonu Hakkında
                </h3>
                <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                    Bu şablon, bu hizmet için oluşturulan tüm işler kapsamında müşterilere gönderilecek sözleşme metninin temelini oluşturur.
                </p>
                <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                    <strong>Kullanılabilir değişkenler:</strong>
                </p>
                <div class="flex flex-wrap gap-2 mt-2">
                    <?php foreach ($placeholders as $placeholder): ?>
                        <code class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs font-mono">
                            <?= e($placeholder) ?>
                        </code>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= base_url("/services/{$service['id']}/contract-template/update") ?>" 
              x-data="contractTemplateForm()"
              @submit="handleFormSubmit($event)"
              role="form" aria-describedby="template-form-errors" novalidate data-validate="true">
            <?= CSRF::field() ?>

            <!-- SECTION 1: Temel Bilgiler -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-info-circle mr-2 text-purple-600"></i>
                        Şablon Bilgileri
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="template_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-tag mr-2 text-purple-600"></i>
                                Şablon Adı <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="template_name"
                                   value="<?= $isEdit ? e($template['name']) : htmlspecialchars($service['name'] . ' Hizmet Sözleşmesi') ?>" 
                                   class="w-full px-4 py-3 border border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200" 
                                   required 
                                   placeholder="Örn: Ev Temizliği Hizmet Sözleşmesi"
                                   aria-label="Şablon adı"
                                   aria-required="true"
                                   data-validate="required|min:3|max:200"
                                   autocomplete="off">
                            <p class="field-error hidden text-sm text-red-600 mt-1"></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-toggle-on mr-2 text-purple-600"></i>
                                Durum
                            </label>
                            <div class="mt-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="is_active" value="1" <?= $isEdit && $template['is_active'] ? 'checked' : 'checked' ?>
                                           class="rounded border-2 border-gray-300 dark:border-gray-600 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                                </label>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pasif şablonlar yeni işlerde kullanılmaz</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Sözleşme Metni -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-file-alt mr-2 text-purple-600"></i>
                        Sözleşme Metni
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Sözleşme metnini aşağıya yazın. Değişkenler otomatik olarak doldurulacaktır.</p>
                </div>
                <div class="p-6">
                    <div>
                        <label for="template_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-align-left mr-2 text-purple-600"></i>
                            Sözleşme Metni <span class="text-red-500">*</span>
                        </label>
                        <textarea name="template_text" 
                                  id="template_text"
                                  rows="20"
                                  class="w-full px-4 py-3 border border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 font-mono text-sm"
                                  required
                                  placeholder="Sözleşme metnini buraya yazın...&#10;&#10;Örnek:&#10;{customer_name} ile {job_date} tarihli temizlik hizmeti sözleşmesi..."
                                  aria-label="Sözleşme metni"
                                  aria-required="true"
                                  data-validate="required|min:10"><?= $isEdit ? e($template['template_text']) : '' ?></textarea>
                        <p class="field-error hidden text-sm text-red-600 mt-1"></p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Minimum 10 karakter. Değişkenler otomatik olarak doldurulacaktır.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-4 border-t border-gray-200 dark:border-gray-700">
                <a href="<?= base_url("/services/edit/{$service['id']}") ?>" 
                   class="px-6 py-3 rounded-lg bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 font-medium transition-all duration-200"
                   aria-label="İptal ve geri dön">
                    <i class="fas fa-times mr-2"></i>İptal
                </a>
                <button type="submit" 
                        :disabled="isSubmitting"
                        :class="{'opacity-50 cursor-not-allowed': isSubmitting}"
                        class="px-6 py-3 bg-purple-600 rounded-lg hover:bg-purple-700 disabled:opacity-50 font-medium transition-colors duration-200 text-white"
                        aria-label="<?= $isEdit ? 'Şablonu güncelle' : 'Yeni şablon oluştur' ?>">
                    <span x-show="!isSubmitting">
                        <i class="fas fa-save mr-2"></i>
                        Sözleşme Şablonunu Kaydet
                    </span>
                    <span x-show="isSubmitting">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        Kaydediliyor...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function contractTemplateForm() {
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

