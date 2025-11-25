<?php $isEdit = !empty($staff); ?>
<?php $pageTitle = $isEdit ? 'Personel Düzenle' : 'Yeni Personel Ekle'; ?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-user-<?= $isEdit ? 'edit' : 'plus' ?> mr-3 text-primary-600"></i>
                <?= $isEdit ? 'Personel Düzenle' : 'Yeni Personel Ekle' ?>
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                <?= $isEdit ? 'Personel bilgilerini güncelleyin' : 'Yeni personel bilgilerini girin' ?>
            </p>
        </div>
        <a href="<?= base_url('/staff') ?>" 
           class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-200">
            <i class="fas fa-arrow-left mr-2"></i>Geri Dön
        </a>
    </div>

    <form method="POST" action="<?= $isEdit ? base_url("/staff/update/{$staff['id']}") : base_url('/staff/create') ?>" 
          class="space-y-8" x-data="staffForm()" @submit="handleFormSubmit($event)" role="form" aria-describedby="staff-form-errors" novalidate data-validate="true">
        <?= CSRF::field() ?>

        <!-- SECTION 1: Kişisel Bilgiler -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-user mr-2 text-primary-600"></i>
                        Kişisel Bilgiler
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Temel kişisel bilgiler</p>
                </div>
                <div class="p-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Ad <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm"
                           value="<?= $isEdit ? e($staff['name']) : '' ?>"
                           placeholder="Adını girin"
                           aria-required="true" aria-invalid="false"
                           aria-describedby="name-error name-hint"
                           data-validate="required|min:2|max:100"
                           autocomplete="name">
                    <p id="name-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="name-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">En az 2 karakter giriniz</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Soyad <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="surname" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm"
                           value="<?= $isEdit ? e($staff['surname']) : '' ?>"
                           placeholder="Soyadını girin"
                           aria-required="true" aria-invalid="false"
                           aria-describedby="surname-error surname-hint"
                           data-validate="required|min:2|max:100"
                           autocomplete="family-name">
                    <p id="surname-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="surname-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">En az 2 karakter giriniz</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-phone mr-2 text-primary-600"></i>Telefon
                    </label>
                    <input type="tel" name="phone"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm"
                           value="<?= $isEdit ? e($staff['phone']) : '' ?>"
                           placeholder="Telefon numarası"
                           aria-describedby="phone-error phone-hint"
                           data-validate="phone|min:10|max:11"
                           inputmode="tel"
                           pattern="[0-9]{10,11}"
                           autocomplete="tel">
                    <p id="phone-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="phone-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">10-11 haneli sayı giriniz</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-envelope mr-2 text-primary-600"></i>E-posta
                    </label>
                    <input type="email" name="email"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm"
                           value="<?= $isEdit ? e($staff['email']) : '' ?>"
                           placeholder="E-posta adresi"
                           aria-describedby="email-error email-hint"
                           data-validate="email"
                           inputmode="email"
                           autocomplete="email">
                    <p id="email-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="email-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Geçerli bir e-posta adresi giriniz</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-id-card mr-2 text-primary-600"></i>TC Kimlik No
                    </label>
                    <input type="text" name="tc_number" maxlength="11"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm"
                           value="<?= $isEdit ? e($staff['tc_number']) : '' ?>"
                           placeholder="TC kimlik numarası"
                           aria-describedby="tc-error tc-hint"
                           data-validate="numeric|min:11|max:11"
                           inputmode="numeric"
                           pattern="[0-9]{11}">
                    <p id="tc-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="tc-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">11 haneli TC kimlik no giriniz</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-birthday-cake mr-2 text-primary-600"></i>Doğum Tarihi
                    </label>
                    <input type="date" name="birth_date"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm"
                           value="<?= $isEdit ? $staff['birth_date'] : '' ?>">
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-map-marker-alt mr-2 text-primary-600"></i>Adres
                </label>
                <textarea name="address" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm resize-none"
                          placeholder="Adres bilgisi"><?= $isEdit ? e($staff['address']) : '' ?></textarea>
                </div>
            </div>
        </div>

        <!-- SECTION 2: İş Bilgileri -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-briefcase mr-2 text-primary-600"></i>
                        İş Bilgileri
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Çalışma ve pozisyon bilgileri</p>
                </div>
                <div class="p-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-user-tie mr-2 text-primary-600"></i>Pozisyon
                    </label>
                    <input type="text" name="position"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm"
                           value="<?= $isEdit ? e($staff['position']) : '' ?>"
                           placeholder="Pozisyon (örn: Teknisyen, Usta)">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calendar-plus mr-2 text-primary-600"></i>İşe Giriş Tarihi <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="hire_date" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm"
                           value="<?= $isEdit ? $staff['hire_date'] : date('Y-m-d') ?>"
                           aria-required="true" aria-invalid="false"
                           aria-describedby="hire_date-error hire_date-hint"
                           data-validate="required|date">
                    <p id="hire_date-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="hire_date-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Geçerli bir tarih seçiniz</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-lira-sign mr-2 text-primary-600"></i>Aylık Maaş (₺)
                    </label>
                    <input type="number" name="salary" step="0.01" min="0"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm"
                           value="<?= $isEdit ? $staff['salary'] : '' ?>"
                           placeholder="0.00"
                           aria-describedby="salary-error salary-hint"
                           inputmode="decimal"
                           data-validate="numeric|min:0">
                    <p id="salary-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="salary-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Opsiyonel, 0 veya üzeri bir değer giriniz</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-clock mr-2 text-primary-600"></i>Saatlik Ücret (₺)
                    </label>
                    <input type="number" name="hourly_rate" step="0.01" min="0"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm"
                           value="<?= $isEdit ? $staff['hourly_rate'] : '' ?>"
                           placeholder="0.00"
                           aria-describedby="hourly-error hourly-hint"
                           inputmode="decimal"
                           data-validate="numeric|min:0">
                    <p id="hourly-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="hourly-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Opsiyonel, 0 veya üzeri bir değer giriniz</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-toggle-on mr-2 text-primary-600"></i>Durum
                    </label>
                    <select name="status"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm">
                        <option value="active" <?= $isEdit && $staff['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $isEdit && $staff['status'] === 'inactive' ? 'selected' : '' ?>>Pasif</option>
                        <option value="terminated" <?= $isEdit && $staff['status'] === 'terminated' ? 'selected' : '' ?>>İşten Ayrıldı</option>
                    </select>
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-sticky-note mr-2 text-primary-600"></i>Notlar
                </label>
                <textarea name="notes" rows="4"
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm resize-none"
                          placeholder="Personel hakkında notlar..."><?= $isEdit ? e($staff['notes']) : '' ?></textarea>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex justify-end space-x-4">
                <a href="<?= base_url('/staff') ?>" 
                   class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 font-medium">
                    <i class="fas fa-times mr-2"></i>İptal
                </a>
                <button type="submit" 
                        :disabled="isSubmitting"
                        class="px-6 py-3 bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200 font-medium"
                        style="color: white !important; background: #4f46e5 !important;">
                    <i x-show="!isSubmitting" class="fas fa-save mr-2" style="color: white !important;"></i>
                    <i x-show="isSubmitting" class="fas fa-spinner fa-spin mr-2" style="color: white !important;"></i>
                    <span x-text="isSubmitting ? 'Kaydediliyor...' : '<?= $isEdit ? 'Güncelle' : 'Kaydet' ?>'" style="color: white !important;"></span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function staffForm() {
    return {
        isSubmitting: false,
        
        handleFormSubmit(event) {
            if (this.isSubmitting) {
                event.preventDefault();
                return;
            }
            
            this.isSubmitting = true;
            this.showNotification('Personel bilgileri kaydediliyor...', 'info');
        },
        
        showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium transition-all duration-300 transform translate-x-full ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                'bg-blue-500'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    }
}
</script>
