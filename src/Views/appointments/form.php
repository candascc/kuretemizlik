<?php 
$isEdit = !empty($appointment);
?>
<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="<?= base_url('/appointments') ?>" class="text-gray-400 hover:text-primary-600"><i class="fas fa-calendar"></i></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-500"><?= $isEdit ? 'Düzenle' : 'Yeni Randevu' ?></span></li>
        </ol>
    </nav>

    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-calendar mr-3 text-primary-600"></i>
            <?= $isEdit ? 'Randevuyu Düzenle' : 'Yeni Randevu' ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2"><?= $isEdit ? 'Randevu bilgilerini güncelleyin' : 'Yeni bir randevu oluşturun' ?></p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= $isEdit ? base_url("/appointments/{$appointment['id']}/update") : base_url('/appointments/create') ?>" 
              x-data="appointmentForm()" 
              @submit.prevent="handleFormSubmit($event)"
              role="form" aria-describedby="appointment-form-errors" novalidate data-validate="true">
            <?= CSRF::field() ?>

            <!-- SECTION 1: Temel Bilgiler -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-info-circle mr-2 text-primary-600"></i>
                        Temel Bilgiler
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Müşteri ve randevu bilgileri</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Müşteri Seçimi -->
                        <div class="md:col-span-2">
                            <label for="customer_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-user mr-2 text-primary-600"></i>Müşteri <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-2">
                                <select name="customer_id" id="customer_id" required x-model="customerId" class="flex-1 px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200" aria-required="true" aria-invalid="false" aria-describedby="customer-error customer-hint" data-validate="required|numeric|min:1">
                                    <option value="">Müşteri seçiniz</option>
                                <p id="customer-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                                <p id="customer-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bir müşteri seçiniz</p>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?= $customer['id'] ?>" <?= ($appointment['customer_id'] ?? '') == $customer['id'] ? 'selected' : '' ?>>
                                            <?= e($customer['name']) ?>
                                            <?php if ($customer['phone']): ?>
                                                - <?= e($customer['phone']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" @click="showNewCustomerForm = !showNewCustomerForm; if(showNewCustomerForm) $nextTick(() => $refs.newCustomerName?.focus())" class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 whitespace-nowrap font-medium transition-colors duration-200">
                                    <i class="fas fa-plus mr-2"></i>Yeni Müşteri
                                </button>
                            </div>
                        
                        <!-- Yeni Müşteri Formu -->
                        <div x-show="showNewCustomerForm" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 transform scale-100"
                             x-transition:leave-end="opacity-0 transform scale-95"
                             class="mt-6 p-6 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 shadow-soft">
                            <div class="flex items-center mb-4">
                                <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-user-plus text-white text-sm"></i>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Yeni Müşteri Ekle</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-user mr-2 text-primary-600"></i>Müşteri Adı <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           name="new_customer_name"
                                           x-model="newCustomer.name" 
                                           x-ref="newCustomerName"
                                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-600 dark:text-white transition-all duration-200" 
                                           placeholder="Müşteri adı">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-phone mr-2 text-primary-600"></i>Telefon
                                    </label>
                                    <input type="text" 
                                           x-model="newCustomer.phone" 
                                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-600 dark:text-white transition-all duration-200" 
                                           placeholder="Telefon numarası">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-envelope mr-2 text-primary-600"></i>Email
                                    </label>
                                    <input type="email" 
                                           x-model="newCustomer.email" 
                                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-600 dark:text-white transition-all duration-200" 
                                           placeholder="Email adresi">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-sticky-note mr-2 text-primary-600"></i>Notlar
                                    </label>
                                    <input type="text" 
                                           x-model="newCustomer.notes" 
                                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-600 dark:text-white transition-all duration-200" 
                                           placeholder="Notlar">
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 mt-6">
                                <button type="button" 
                                        @click="cancelNewCustomer()" 
                                        class="px-6 py-3 text-sm font-semibold rounded-lg bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                    <i class="fas fa-times mr-2"></i>
                                    İptal
                                </button>
                                <button type="button" 
                                        @click="saveNewCustomer()" 
                                        :disabled="!newCustomer.name.trim()"
                                        class="px-6 py-3 text-sm font-semibold rounded-lg bg-primary-600 hover:bg-primary-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                    <i class="fas fa-save mr-2"></i>
                                    Kaydet
                                </button>
                            </div>
                        </div>

                        <!-- Hizmet Seçimi -->
                        <div>
                            <label for="service_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-concierge-bell mr-2 text-primary-600"></i>Hizmet
                            </label>
                            <select name="service_id" id="service_id" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200" aria-describedby="service-error service-hint" data-validate="numeric|min:1">
                                <option value="">Hizmet seçiniz</option>
                            <p id="service-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="service-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Opsiyonel: hizmet seçebilirsiniz</p>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?= $service['id'] ?>" <?= ($appointment['service_id'] ?? '') == $service['id'] ? 'selected' : '' ?>>
                                        <?= e($service['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Atanan Kişi -->
                        <div>
                            <label for="assigned_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-user-tie mr-2 text-primary-600"></i>Atanan Kişi
                            </label>
                            <select name="assigned_to" id="assigned_to" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                <option value="">Atanan kişi seçiniz</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= ($appointment['assigned_to'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                        <?= e($user['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Başlık -->
                        <div class="md:col-span-2">
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-heading mr-2 text-primary-600"></i>Başlık <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" id="title" required 
                                   value="<?= htmlspecialchars($appointment['title'] ?? '') ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200" 
                                   placeholder="Randevu başlığını giriniz"
                                   aria-label="Randevu başlığı"
                                   aria-required="true" aria-invalid="false"
                                   aria-describedby="title-error title-hint"
                                   data-validate="required|min:3|max:100">
                            <p id="title-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="title-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">3-100 karakter arası bir başlık giriniz</p>
                        </div>

                        <!-- Randevu Tarihi -->
                        <div>
                            <label for="appointment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-calendar-alt mr-2 text-primary-600"></i>Randevu Tarihi <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="appointment_date" id="appointment_date" required 
                                   value="<?= $appointment['appointment_date'] ?? '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                                   aria-label="Randevu tarihi"
                                   aria-required="true" aria-invalid="false"
                                   aria-describedby="date-error date-hint"
                                   data-validate="required|date">
                            <p id="date-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="date-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Geçerli bir tarih seçiniz</p>
                        </div>

                        <!-- Başlangıç Saati -->
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-clock mr-2 text-primary-600"></i>Başlangıç Saati <span class="text-red-500">*</span>
                            </label>
                            <input type="time" name="start_time" id="start_time" required 
                                   value="<?= $appointment['start_time'] ?? '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                                   aria-label="Başlangıç saati"
                                   aria-required="true" aria-invalid="false"
                                   aria-describedby="start_time-error start_time-hint"
                                   data-validate="required|time">
                            <p id="start_time-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="start_time-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Geçerli bir saat seçiniz</p>
                        </div>

                        <!-- Bitiş Saati -->
                        <div>
                            <label for="end_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-clock mr-2 text-primary-600"></i>Bitiş Saati
                            </label>
                            <input type="time" name="end_time" id="end_time" 
                                   value="<?= $appointment['end_time'] ?? '' ?>" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                                   aria-label="Bitiş saati"
                                   aria-describedby="end_time-error end_time-hint"
                                   data-validate="time">
                            <p id="end_time-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="end_time-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Opsiyonel: bitiş saati seçebilirsiniz</p>
                        </div>

                        <!-- Durum -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-info-circle mr-2 text-primary-600"></i>Durum
                            </label>
                            <select name="status" id="status" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                <?php foreach ($statuses as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= ($appointment['status'] ?? 'SCHEDULED') === $key ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Öncelik -->
                        <div>
                            <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-exclamation-circle mr-2 text-primary-600"></i>Öncelik
                            </label>
                            <select name="priority" id="priority" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                <?php foreach ($priorities as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= ($appointment['priority'] ?? 'MEDIUM') === $key ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

            <!-- SECTION 2: Randevu Detayları -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-file-alt mr-2 text-purple-600"></i>
                        Randevu Detayları
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Ek bilgiler ve notlar</p>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Açıklama -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-align-left mr-2 text-purple-600"></i>Açıklama
                        </label>
                        <textarea name="description" id="description" rows="4" 
                                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 resize-none" 
                                  placeholder="Randevu hakkında detaylı bilgi..."
                                  aria-label="Açıklama"><?= htmlspecialchars($appointment['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Notlar -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-sticky-note mr-2 text-purple-600"></i>Notlar
                        </label>
                        <textarea name="notes" id="notes" rows="3" 
                                  class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 resize-none" 
                                  placeholder="İç notlar..."
                                  aria-label="Notlar"><?= htmlspecialchars($appointment['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-6 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row justify-end gap-4">
                    <a href="<?= base_url('/appointments') ?>" 
                       class="px-6 py-3 text-center border-2 border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200 font-medium">
                        <i class="fas fa-times mr-2"></i>İptal
                    </a>
                    <button type="submit" 
                            :disabled="isSubmitting" 
                            class="px-6 py-3 bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium transition-colors duration-200 shadow-medium hover:shadow-strong"
                            style="color: white !important; background: #4f46e5 !important;">
                        <i class="fas mr-2" :class="isSubmitting ? 'fa-spinner fa-spin' : 'fa-save'" style="color: white !important;"></i>
                        <span x-show="!isSubmitting" style="color: white !important;"><?= $isEdit ? 'Güncelle' : 'Oluştur' ?></span>
                        <span x-show="isSubmitting" style="color: white !important;">İşleniyor...</span>
                    </button>
                </div>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
function appointmentForm() {
    return {
        isSubmitting: false,
        customerId: <?= $isEdit ? (int)($appointment['customer_id'] ?? 0) : 0 ?>,
        showNewCustomerForm: false,
        newCustomer: {
            name: '',
            phone: '',
            email: '',
            notes: ''
        },
        
        async saveNewCustomer() {
            if (!this.newCustomer.name.trim()) return;
            
            try {
                const res = await fetch('<?= base_url('/api/customers') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.newCustomer)
                });
                const data = await res.json();
                if (data.success) {
                    // Yeni müşteriyi select'e ekle
                    const select = document.getElementById('customer_id');
                    const option = document.createElement('option');
                    option.value = data.data.id;
                    option.textContent = this.newCustomer.name + (this.newCustomer.phone ? ' - ' + this.newCustomer.phone : '');
                    option.selected = true;
                    select.appendChild(option);
                    
                    // Seçimi güncelle
                    this.customerId = data.data.id;
                    
                    // Formu temizle ve kapat
                    this.cancelNewCustomer();
                    
                    // Başarı mesajı
                    alert('Müşteri başarıyla eklendi!');
                } else {
                    alert(data.error || 'Müşteri eklenemedi');
                }
            } catch (e) {
                alert('Bir hata oluştu');
            }
        },
        
        cancelNewCustomer() {
            this.showNewCustomerForm = false;
            this.newCustomer = {
                name: '',
                phone: '',
                email: '',
                notes: ''
            };
        },
        
        handleFormSubmit(event) {
            // Always prevent default first
            event.preventDefault();
            
            if (this.isSubmitting) {
                return false;
            }
            
            // Remove required from modal inputs to prevent validation errors
            const modalInputs = event.target.querySelectorAll('[x-model^="newCustomer"]');
            modalInputs.forEach(input => {
                input.removeAttribute('required');
            });
            
            // Validate required fields
            const customerSelect = document.getElementById('customer_id');
            if (!customerSelect || !customerSelect.value) {
                alert('Lütfen bir müşteri seçin');
                return false;
            }
            
            // Set submitting state
            this.isSubmitting = true;
            
            // Manually submit the form
            const form = event.target;
            if (form && form.tagName === 'FORM') {
                // Create a new form submission
                const formData = new FormData(form);
                const action = form.action;
                const method = form.method || 'POST';
                
                // Submit using native form submission
                const submitForm = document.createElement('form');
                submitForm.method = method;
                submitForm.action = action;
                submitForm.style.display = 'none';
                
                // Copy all form data
                for (let [key, value] of formData.entries()) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value instanceof File ? value.name : value;
                    submitForm.appendChild(input);
                }
                
                document.body.appendChild(submitForm);
                submitForm.submit();
            }
            
            return false;
        }
    }
}

// Hizmet seçildiğinde süreyi otomatik hesapla
const serviceSelect = document.getElementById('service_id');
if (serviceSelect) {
    serviceSelect.addEventListener('change', function() {
        const serviceId = this.value;
        if (serviceId) {
            const startTimeEl = document.getElementById('start_time');
            const endTimeEl = document.getElementById('end_time');
            if (startTimeEl && endTimeEl && startTimeEl.value) {
                const start = new Date('2000-01-01 ' + startTimeEl.value);
                start.setMinutes(start.getMinutes() + 60); // Varsayılan 1 saat
                const endTime = start.toTimeString().slice(0, 5);
                endTimeEl.value = endTime;
            }
        }
    });
}

// Başlangıç saati değiştiğinde bitiş saatini güncelle
const startTimeEl = document.getElementById('start_time');
if (startTimeEl) {
    startTimeEl.addEventListener('change', function() {
        const startTime = this.value;
        if (startTime) {
            const endTimeEl = document.getElementById('end_time');
            if (endTimeEl) {
                const start = new Date('2000-01-01 ' + startTime);
                start.setMinutes(start.getMinutes() + 60); // Varsayılan 1 saat
                const endTime = start.toTimeString().slice(0, 5);
                endTimeEl.value = endTime;
            }
        }
    });
}
</script>