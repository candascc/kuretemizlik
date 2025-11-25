<?php 
$isEdit = !empty($contract);
?>
<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="<?= base_url('/contracts') ?>" class="text-gray-400 hover:text-primary-600"><i class="fas fa-file-contract"></i></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-500"><?= $isEdit ? 'Düzenle' : 'Yeni Sözleşme' ?></span></li>
        </ol>
    </nav>

    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-file-contract mr-3 text-primary-600"></i>
            <?= $isEdit ? 'Sözleşmeyi Düzenle' : 'Yeni Sözleşme' ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2"><?= $isEdit ? 'Sözleşme bilgilerini güncelleyin' : 'Yeni bir sözleşme oluşturun' ?></p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= $isEdit ? base_url("/contracts/{$contract['id']}/update") : base_url('/contracts/create') ?>" 
              enctype="multipart/form-data" 
              x-data="contractForm()" 
              @submit="handleFormSubmit($event)">
            <?= CSRF::field() ?>

            <!-- SECTION 1: Temel Bilgiler -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-info-circle mr-2 text-primary-600"></i>
                        Temel Bilgiler
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Müşteri ve sözleşme bilgileri</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Müşteri Seçimi -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-user mr-2 text-primary-600"></i>Müşteri <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   x-model="customerQuery" 
                                   @input="searchCustomers" 
                                   @focus="if(customerQuery.length >= 2) showCustomerList = true"
                                   @blur="setTimeout(() => showCustomerList = false, 200)"
                                   @keydown.arrow-down="if(showCustomerList && customerResults.length > 0) { $event.preventDefault(); $refs.customerList.firstElementChild?.focus(); }"
                                   @keydown.escape="showCustomerList = false"
                                   placeholder="Müşteri adı yazın..." 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200" 
                                   autocomplete="off"
                                   role="combobox"
                                   aria-expanded="false"
                                   aria-haspopup="listbox"
                                   aria-autocomplete="list">
                            <i x-show="!isSearchingCustomers" class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <i x-show="isSearchingCustomers" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-primary-500"></i>
                        </div>
                        <input type="hidden" name="customer_id" :value="customerId" required>
                        <div x-show="showCustomerList" 
                             x-ref="customerList"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="absolute z-50 bg-white dark:bg-gray-800 border-2 border-primary-200 dark:border-primary-700 rounded-lg mt-2 w-full max-h-64 overflow-auto shadow-xl"
                             style="display: none;"
                             role="listbox"
                             aria-label="Müşteri listesi">
                            <div x-show="(customerResults || []).length === 0 && !isSearchingCustomers" class="px-4 py-3 text-gray-500 text-center">
                                <i class="fas fa-search mr-2"></i>Sonuç bulunamadı
                            </div>
                            <div x-show="(customerResults || []).length === 0 && isSearchingCustomers" class="px-4 py-3 text-gray-500 text-center">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Aranıyor...
                            </div>
                            <template x-for="(item, index) in (customerResults || [])" :key="'customer-' + (item?.id || index)">
                                <div @click="selectCustomer(item)" 
                                     @keydown.enter="selectCustomer(item)"
                                     @keydown.arrow-down="if(index < (customerResults || []).length - 1) $refs.customerList.children[index + 1]?.focus()"
                                     @keydown.arrow-up="if(index > 0) $refs.customerList.children[index - 1]?.focus()"
                                     class="px-4 py-3 hover:bg-primary-50 dark:hover:bg-primary-900/20 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-0 transition-colors focus:bg-primary-50 dark:focus:bg-primary-900/20 focus:outline-none" 
                                     tabindex="0"
                                     role="option"
                                     :aria-selected="false">
                                    <div class="flex items-center">
                                        <i class="fas fa-user-circle text-primary-500 mr-3"></i>
                                        <div>
                                            <span class="font-medium text-gray-900 dark:text-white" x-text="item?.name || ''"></span>
                                            <span x-show="item?.phone" class="text-sm text-gray-500 dark:text-gray-400 ml-2" x-text="item?.phone || ''"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="(customerResults || []).length > 0" class="px-4 py-2 border-t border-gray-100 dark:border-gray-700">
                                <button type="button" @click="openNewCustomerModal" class="w-full text-left text-primary-600 hover:text-primary-700 font-medium">
                                    <i class="fas fa-plus mr-2"></i>Yeni Müşteri Ekle
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sözleşme Numarası -->
                    <div>
                        <label for="contract_number" class="block text-sm font-medium text-gray-700 mb-2">
                            Sözleşme Numarası <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="contract_number" id="contract_number" required 
                               value="<?= htmlspecialchars($contract['contract_number'] ?? $contract_number ?? '') ?>" 
                               class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white" 
                               placeholder="Sözleşme numarası">
                        <?php if (!$isEdit && isset($contract_number)): ?>
                            <p class="text-xs text-green-600 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Otomatik oluşturulan numara: <strong><?= e($contract_number) ?></strong>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Sözleşme Tipi -->
                    <div>
                        <label for="contract_type" class="block text-sm font-medium text-gray-700 mb-2">Sözleşme Tipi</label>
                        <select name="contract_type" id="contract_type" class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                            <?php foreach ($types as $key => $label): ?>
                                <option value="<?= $key ?>" <?= ($contract['contract_type'] ?? 'CLEANING') === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Başlık -->
                    <div class="md:col-span-2">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Başlık <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" id="title" required 
                               value="<?= htmlspecialchars($contract['title'] ?? '') ?>" 
                               class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white" 
                               placeholder="Sözleşme başlığını giriniz">
                    </div>

                    <!-- Başlangıç Tarihi -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Başlangıç Tarihi <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" id="start_date" required 
                               value="<?= $contract['start_date'] ?? '' ?>" 
                               class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <!-- Bitiş Tarihi -->
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">Bitiş Tarihi</label>
                        <input type="date" name="end_date" id="end_date" 
                               value="<?= $contract['end_date'] ?? '' ?>" 
                               class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <!-- Toplam Tutar -->
                    <div>
                        <label for="total_amount" class="block text-sm font-medium text-gray-700 mb-2">Toplam Tutar</label>
                        <input type="number" name="total_amount" id="total_amount" step="0.01" 
                               value="<?= $contract['total_amount'] ?? '' ?>" 
                               class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white" 
                               placeholder="0.00">
                    </div>

                    <!-- Durum -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Durum</label>
                        <select name="status" id="status" class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?= $key ?>" <?= ($contract['status'] ?? 'DRAFT') === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Ödeme Koşulları -->
                    <div>
                        <label for="payment_terms" class="block text-sm font-medium text-gray-700 mb-2">Ödeme Koşulları</label>
                        <input type="text" name="payment_terms" id="payment_terms" 
                               value="<?= htmlspecialchars($contract['payment_terms'] ?? '') ?>" 
                               class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white" 
                               placeholder="Ödeme koşulları">
                    </div>

                    <!-- Otomatik Yenileme -->
                    <div class="flex items-center">
                        <input type="checkbox" name="auto_renewal" id="auto_renewal" value="1" 
                               <?= ($contract['auto_renewal'] ?? 0) ? 'checked' : '' ?> 
                               class="h-4 w-4 text-indigo-600 focus:ring-primary-500 border-2 border-gray-300 dark:border-gray-600 rounded">
                        <label for="auto_renewal" class="ml-2 block text-sm text-gray-900 dark:text-white">
                            Otomatik yenileme
                        </label>
                    </div>

                    <!-- Yenileme Süresi -->
                    <div>
                        <label for="renewal_period_days" class="block text-sm font-medium text-gray-700 mb-2">Yenileme Süresi (Gün)</label>
                        <input type="number" name="renewal_period_days" id="renewal_period_days" 
                               value="<?= $contract['renewal_period_days'] ?? '' ?>" 
                               class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white" 
                               placeholder="365">
                    </div>
                </div>

                <!-- Açıklama -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Açıklama</label>
                    <textarea name="description" id="description" rows="4" 
                              class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500" 
                              placeholder="Sözleşme hakkında detaylı bilgi..."><?= htmlspecialchars($contract['description'] ?? '') ?></textarea>
                </div>

                <!-- Notlar -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notlar</label>
                    <textarea name="notes" id="notes" rows="3" 
                              class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500" 
                              placeholder="İç notlar..."><?= htmlspecialchars($contract['notes'] ?? '') ?></textarea>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="<?= base_url('/contracts') ?>" class="px-4 py-2 border border-2 border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50">
                        İptal
                    </a>
                    <button type="submit" 
                            :disabled="isSubmitting" 
                            class="px-6 py-2 bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50 font-medium"
                            style="color: white !important; background: #4f46e5 !important;">
                        <i class="fas fa-save mr-2" style="color: white !important;"></i>
                        <span style="color: white !important;"><?= $isEdit ? 'Güncelle' : 'Oluştur' ?></span>
                    </button>
                </div>
            </form>
            
            <!-- Dosya Yükleme Bölümü -->
            <div class="mt-8 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-paperclip mr-2"></i>
                    Dosya Ekle
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dosya Seç</label>
                        <input type="file" name="contract_file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">Sadece JPG, PNG, GIF, WEBP ve PDF dosyaları yüklenebilir. Maksimum 10MB.</p>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-primary-600 mt-0.5 mr-2"></i>
                            <div class="text-sm text-blue-800">
                                <p class="font-medium">Dosya Yükleme Bilgisi:</p>
                                <ul class="mt-1 space-y-1 text-xs">
                                    <li>• Sözleşme oluşturulduktan sonra dosya yüklenebilir</li>
                                    <li>• Birden fazla dosya yüklemek için sözleşme detay sayfasını kullanın</li>
                                    <li>• Desteklenen formatlar: JPG, PNG, GIF, WEBP, PDF</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- New Customer Modal -->
<div x-show="showNewCustomerModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeNewCustomerModal()"></div>
        
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-6 py-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Yeni Müşteri Ekle</h3>
                    <button @click="closeNewCustomerModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-user mr-2 text-primary-600"></i>Müşteri Adı <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               x-model="newCustomer.name" 
                               x-ref="newCustomerName"
                               placeholder="Müşteri adı"
                               class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-phone mr-2 text-primary-600"></i>Telefon
                        </label>
                        <input type="tel" 
                               x-model="newCustomer.phone" 
                               placeholder="Telefon numarası"
                               class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-envelope mr-2 text-primary-600"></i>Email
                        </label>
                        <input type="email" 
                               x-model="newCustomer.email" 
                               placeholder="Email adresi"
                               class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-sticky-note mr-2 text-primary-600"></i>Notlar
                        </label>
                        <textarea x-model="newCustomer.notes" 
                                  placeholder="Müşteri hakkında notlar"
                                  rows="3"
                                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 resize-none"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            @click="closeNewCustomerModal()"
                            class="px-4 py-2 rounded-lg border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-times mr-2"></i>İptal
                    </button>
                    <button type="button" 
                            @click="saveNewCustomer()"
                            class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function contractForm() {
    return {
        // Customer search
        customerQuery: '',
        customerResults: [],
        showCustomerList: false,
        isSearchingCustomers: false,
        customerId: null,
        selectedCustomer: null,
        searchTimeout: null,
        
        // New customer modal
        showNewCustomerModal: false,
        newCustomer: {
            name: '',
            phone: '',
            email: '',
            notes: ''
        },
        
        // Form state
        isSubmitting: false,
        
        init() {
            // Initialize form data if editing
            <?php if ($isEdit && isset($contract['customer_id'])): ?>
            this.customerId = <?= $contract['customer_id'] ?>;
            this.selectedCustomer = {
                id: <?= $contract['customer_id'] ?>,
                name: '<?= addslashes($contract['customer_name'] ?? '') ?>',
                phone: '<?= addslashes($contract['customer_phone'] ?? '') ?>'
            };
            this.customerQuery = this.selectedCustomer.name;
            <?php endif; ?>
        },
        
        searchCustomers() {
            if (this.customerQuery.length < 2) {
                this.customerResults = [];
                this.showCustomerList = false;
                return;
            }
            
            this.isSearchingCustomers = true;
            this.showCustomerList = true;
            
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                fetch('<?= base_url('/api/search-customers') ?>?q=' + encodeURIComponent(this.customerQuery))
                    .then(response => response.json())
                    .then(data => {
                        this.customerResults = data.data || [];
                        this.isSearchingCustomers = false;
                    })
                    .catch(error => {
                        console.error('Error searching customers:', error);
                        this.isSearchingCustomers = false;
                    });
            }, 300);
        },
        
        selectCustomer(customer) {
            this.selectedCustomer = customer;
            this.customerId = customer.id;
            this.customerQuery = customer.name;
            this.showCustomerList = false;
            this.customerResults = [];
        },
        
        openNewCustomerModal() {
            this.showNewCustomerModal = true;
            this.showCustomerList = false;
            this.$nextTick(() => {
                this.$refs.newCustomerName?.focus();
            });
        },
        
        closeNewCustomerModal() {
            this.showNewCustomerModal = false;
            this.newCustomer = { name: '', phone: '', email: '', notes: '' };
        },
        
        async saveNewCustomer() {
            if (!this.newCustomer.name.trim()) {
                alert('Müşteri adı zorunludur.');
                return;
            }
            
            try {
                const response = await fetch('<?= base_url('/api/customers') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('input[name="csrf_token"]').value
                    },
                    body: JSON.stringify(this.newCustomer)
                });
                
                if (response.ok) {
                    const customer = await response.json();
                    this.selectCustomer(customer);
                    this.closeNewCustomerModal();
                    this.showNotification('Müşteri başarıyla oluşturuldu!', 'success');
                } else {
                    const error = await response.json();
                    this.showNotification(error.message || 'Müşteri oluşturulamadı.', 'error');
                }
            } catch (error) {
                console.error('Error creating customer:', error);
                this.showNotification('Müşteri oluşturulamadı.', 'error');
            }
        },
        
        handleFormSubmit(event) {
            if (this.isSubmitting) {
                event.preventDefault();
                return;
            }
            
            this.isSubmitting = true;
            this.showNotification('Sözleşme kaydediliyor...', 'info');
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