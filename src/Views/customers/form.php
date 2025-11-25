<?php 
$isEdit = !empty($customer);
?>
<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="<?= base_url('/customers') ?>" class="text-gray-400 hover:text-primary-600"><i class="fas fa-users"></i></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-500"><?= $isEdit ? 'Düzenle' : 'Yeni Müşteri' ?></span></li>
        </ol>
    </nav>

    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-user-plus mr-3 text-primary-600"></i>
            <?= $isEdit ? 'Müşteriyi Düzenle' : 'Yeni Müşteri' ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2"><?= $isEdit ? 'Müşteri bilgilerini güncelleyin' : 'Yeni bir müşteri kaydı oluşturun' ?></p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= $isEdit ? base_url("/customers/update/{$customer['id']}") : base_url('/customers/create') ?>" x-data="customerForm()" @submit="handleFormSubmit($event)" role="form" aria-describedby="form-errors" novalidate data-validate="true">
            <?= CSRF::field() ?>

            <!-- SECTION 1: Temel Bilgiler -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-user mr-2 text-primary-600"></i>
                        Temel Bilgiler
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Müşteri iletişim bilgileri</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-user mr-2 text-primary-600"></i>İsim <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   value="<?= $isEdit ? e($customer['name']) : '' ?>" 
                                   required 
                                   placeholder="Müşteri adı"
                                   aria-label="Müşteri adı"
                                   aria-required="true"
                                   aria-invalid="false"
                                   aria-describedby="name-error name-hint"
                                   data-validate="required|min:3|max:100"
                                   autocomplete="name"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                            <p id="name-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="name-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">En az 3 karakter giriniz</p>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-phone mr-2 text-primary-600"></i>Telefon
                            </label>
                            <input type="tel" 
                                   name="phone" 
                                   value="<?= $isEdit ? e($customer['phone'] ?? '') : '' ?>" 
                                   placeholder="Telefon numarası"
                                   aria-label="Telefon numarası"
                                   aria-describedby="phone-error phone-hint"
                                   data-validate="phone|min:10|max:11"
                                   inputmode="tel"
                                   pattern="[0-9]{10,11}"
                                   autocomplete="tel"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                            <p id="phone-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="phone-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">10-11 haneli sayı giriniz</p>
                        </div>

                        <!-- Email -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-envelope mr-2 text-primary-600"></i>Email
                            </label>
                            <input type="email" 
                                   name="email" 
                                   value="<?= $isEdit ? e($customer['email'] ?? '') : '' ?>" 
                                   placeholder="Email adresi"
                                   aria-describedby="email-error email-hint"
                                   data-validate="email"
                                   inputmode="email"
                                   autocomplete="email"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                            <p id="email-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="email-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Geçerli bir e-posta adresi giriniz</p>
                        </div>

                        <!-- Notes -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-sticky-note mr-2 text-primary-600"></i>Not
                            </label>
                            <textarea name="notes" 
                                      rows="4" 
                                      placeholder="Müşteri hakkında notlar..."
                                      class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 resize-none"><?= $isEdit ? e($customer['notes'] ?? '') : '' ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Adresler -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                <i class="fas fa-map-marker-alt mr-2 text-green-600"></i>
                                Adresler
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Müşteri adres bilgileri</p>
                        </div>
                        <button type="button" 
                                @click="addAddress()" 
                                class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 transition-all duration-200 shadow-md hover:shadow-lg">
                            <i class="fas fa-plus mr-2"></i>
                            Adres Ekle
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-4" x-show="addresses.length > 0">
                        <template x-for="(address, index) in addresses" :key="index">
                            <div class="bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-700 dark:to-blue-900/20 border-2 border-gray-200 dark:border-gray-600 rounded-lg p-6 hover:shadow-lg transition-all duration-200">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/20 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-map-marker-alt text-primary-600"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Adres <span x-text="index + 1"></span></h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400" x-show="address.label"><span x-text="address.label"></span></p>
                                        </div>
                                    </div>
                                    <button type="button" 
                                            @click="removeAddress(index)" 
                                            class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-150 p-2 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Etiket</label>
                                        <input type="text" 
                                               :name="`addresses[${index}][label]`" 
                                               x-model="address.label" 
                                               placeholder="Ev, İş, vb." 
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-600 dark:text-white transition-all duration-200">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Adres <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" 
                                               :name="`addresses[${index}][line]`" 
                                               x-model="address.line" 
                                               required 
                                               placeholder="Tam adres bilgisi" 
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-600 dark:text-white transition-all duration-200">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Şehir</label>
                                        <input type="text" 
                                               :name="`addresses[${index}][city]`" 
                                               x-model="address.city" 
                                               placeholder="Şehir" 
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-600 dark:text-white transition-all duration-200">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty State -->
                    <div x-show="addresses.length === 0" 
                         class="text-center py-12 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <i class="fas fa-map-marker-alt text-2xl text-gray-400"></i>
                        </div>
                        <p class="text-lg font-medium text-gray-900 dark:text-white mb-2">Henüz adres eklenmemiş</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Yukarıdaki "Adres Ekle" butonuna tıklayarak adres ekleyebilirsiniz.</p>
                        <button type="button" 
                                @click="addAddress()" 
                                class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg text-white bg-primary-600 hover:bg-primary-700 transition-all duration-200">
                            <i class="fas fa-plus mr-2"></i>
                            İlk Adresi Ekle
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-4 border-t border-gray-200 dark:border-gray-700">
                <a href="<?= base_url('/customers') ?>" 
                   class="px-6 py-3 rounded-lg bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 font-medium transition-all duration-200">
                    <i class="fas fa-times mr-2"></i>İptal
                </a>
                <button type="submit" 
                        class="px-8 py-3 rounded-lg bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                        style="color: white !important; background: linear-gradient(to right, #4f46e5, #4338ca) !important;"
                        :disabled="isSubmitting"
                        :class="{'opacity-50 cursor-not-allowed': isSubmitting}">
                    <i class="fas fa-save mr-2" style="color: white !important;"></i>
                    <span style="color: white !important;"><?= $isEdit ? 'Güncelle' : 'Kaydet' ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if Alpine.js is available
    if (typeof Alpine === 'undefined') {
        console.warn('Alpine.js is not loaded. Form may not work correctly.');
    }
});

function customerForm() {
    return {
        isSubmitting: false,
        addresses: <?= $isEdit ? json_encode($customer['addresses'] ?? []) : '[]' ?>,
        
        addAddress() {
            this.addresses.push({ label: '', line: '', city: '' });
        },
        
        removeAddress(index) {
            if (confirm('Bu adresi silmek istediğinizden emin misiniz?')) {
                this.addresses.splice(index, 1);
                this.showNotification('Adres silindi', 'info');
            }
        },
        
        // Notification system
        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        },
        
        init() {
            if (<?= $isEdit ? 'true' : 'false' ?> && this.addresses.length === 0) {
                <?php if ($isEdit && !empty($customer['addresses'])): ?>
                this.addresses = <?= json_encode($customer['addresses']) ?>;
                <?php endif; ?>
            }
        }
    }
}
</script>

