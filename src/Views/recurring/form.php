<?php
/** @var array|null $item */
$isEdit = isset($item);
?>
<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="<?= base_url('/recurring') ?>" class="text-gray-400 hover:text-primary-600"><i class="fas fa-sync-alt"></i></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-500"><?= $isEdit ? 'Düzenle' : 'Yeni Periyodik İş' ?></span></li>
        </ol>
    </nav>

    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-sync-alt mr-3 text-primary-600"></i>
            <?= $isEdit ? 'Periyodik İş Düzenle' : 'Yeni Periyodik İş' ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2"><?= $isEdit ? 'Periyodik iş bilgilerini güncelleyin' : 'Yeni bir periyodik iş oluşturun' ?></p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

<?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
<script>window.PAYMENT_VALIDATION_DEBUG = true;</script>
<?php endif; ?>

    <!-- Form Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= $isEdit ? base_url('/recurring/' . $item['id'] . '/update') : base_url('/recurring/create') ?>" 
              x-data="recForm()" 
              @submit.prevent="if(handleFormSubmit($event)) { $el.submit(); }"
              role="form" aria-describedby="recurring-form-errors" novalidate data-validate="true" data-payment-skip="true">
            <?= CSRF::field() ?>

            <!-- SECTION 1: Müşteri Bilgileri -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-user mr-2 text-primary-600"></i>
                        Müşteri Bilgileri
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Müşteri seçimi ve bilgileri</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-user mr-2 text-primary-600"></i>Müşteri <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" 
                               x-model="customerQuery" 
                               @input="searchCustomers" 
                               @focus="if(customerQuery && customerQuery.length >= 2) showCustomerList = true"
                               @blur="setTimeout(() => showCustomerList = false, 200)"
                               @keydown.arrow-down="if(showCustomerList && customerResults && customerResults.length > 0) { $event.preventDefault(); $refs.customerList.firstElementChild?.focus(); }"
                               @keydown.escape="showCustomerList = false"
                               placeholder="Müşteri adı yazın..."
                               class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner" 
                               autocomplete="off"
                               role="combobox"
                               aria-expanded="false"
                               aria-haspopup="listbox"
                               aria-autocomplete="list">
                        <i x-show="!isSearchingCustomers" class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <i x-show="isSearchingCustomers" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-primary-500"></i>
                        <input type="hidden" name="customer_id" :value="customer_id" required aria-required="true" aria-invalid="false" data-validate="required|numeric|min:1">
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
                    
                    <!-- Selected Customer Info -->
                    <div x-show="selectedCustomer.id" class="mt-3 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-user text-primary-600 dark:text-primary-400 mr-2"></i>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white dark:text-white" x-text="selectedCustomer.name"></div>
                                <div class="text-sm text-gray-500 dark:text-gray-400" x-text="selectedCustomer.phone || 'Telefon yok'"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Adres
                    </label>
                    <select name="address_id" x-model="selectedAddressId" 
                            class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner">
                        <option value="">Adres seçin...</option>
                        <template x-for="address in customerAddresses" :key="address.id">
                            <option :value="address.id" x-text="address.label + ' - ' + address.line"></option>
                        </template>
                    </select>
                    <div x-show="selectedCustomer && selectedCustomer.id && customerAddresses && customerAddresses.length === 0" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>Bu müşterinin kayıtlı adresi yok
                    </div>
                </div>
            </div>
        </div>

        <!-- Hizmet Seçimi -->
        <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-white mb-4">
                <i class="fas fa-cogs mr-2 text-primary-600"></i>Hizmet Bilgileri
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Hizmet
                    </label>
                    <select name="service_id" x-model="selectedServiceId" 
                            @change="autoSave()"
                            class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner">
                        <option value="">Hizmet seçin...</option>
                        <template x-for="service in services" :key="service.id">
                            <option :value="service.id" x-text="service.name"></option>
                        </template>
                    </select>
                </div>
                
                <!-- Ücretlendirme Modeli -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calculator mr-2 text-primary-600"></i>
                        Ücretlendirme Modeli <span class="text-red-500">*</span>
                    </label>
                    <select name="pricing_model" x-model="pricingModel" 
                            @change="onPricingModelChange()"
                            class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner" aria-required="true" aria-invalid="false" aria-describedby="pricing_model-error pricing_model-hint" data-validate="required|in:PER_JOB,PER_MONTH,TOTAL_CONTRACT">
                    <p id="pricing_model-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="pricing_model-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ücretlendirme modelini seçiniz</p>
                        <option value="PER_JOB">Her İş Başına Ücret</option>
                        <option value="PER_MONTH">Aylık Sabit Ücret</option>
                        <option value="TOTAL_CONTRACT">Toplam Sözleşme Tutarı</option>
                    </select>
                </div>
            </div>
            
            <!-- Her İş Başına Ücret -->
            <div x-show="pricingModel === 'PER_JOB'" x-transition class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-lira-sign mr-2 text-primary-600"></i>
                    İş Başına Tutar
                    </label>
                    <input type="number" step="0.01" name="default_total_amount" x-model.number="default_total_amount" 
                       @blur="autoSave()"
                       class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner" aria-describedby="default_total_amount-error default_total_amount-hint" aria-required="true" inputmode="decimal" data-validate="required|numeric|min:0.01">
                <p id="default_total_amount-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                <p id="default_total_amount-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pozitif bir iş başı tutar giriniz</p>
                       placeholder="0.00">
            </div>
            
            <!-- Aylık Sabit Ücret -->
            <div x-show="pricingModel === 'PER_MONTH'" x-transition class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-calendar-alt mr-2 text-primary-600"></i>
                    Aylık Ücret <span class="text-red-500">*</span>
                </label>
                <input type="number" step="0.01" name="monthly_amount" x-model.number="monthly_amount" 
                       @blur="autoSave()"
                       class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner" 
                       aria-required="true" 
                       inputmode="decimal" 
                       data-validate="required|numeric|min:0.01"
                       placeholder="0.00">
                <p id="monthly_amount-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pozitif bir tutar giriniz</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    Bu modelde her işe tutar yazılmaz, ödeme aylık bazda sözleşme üzerinden takip edilir
                </p>
                </div>
            
            <!-- Toplam Sözleşme Tutarı -->
            <div x-show="pricingModel === 'TOTAL_CONTRACT'" x-transition class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-file-contract mr-2 text-primary-600"></i>
                    Toplam Sözleşme Tutarı <span class="text-red-500">*</span>
                </label>
                <input type="number" step="0.01" name="contract_total_amount" x-model.number="contract_total_amount" 
                       @blur="autoSave()"
                       class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner" 
                       aria-required="true" 
                       inputmode="decimal" 
                       data-validate="required|numeric|min:0.01"
                       placeholder="0.00">
                <p id="contract_total_amount-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pozitif bir sözleşme tutarı giriniz</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    Bu modelde her işe tutar yazılmaz, ödeme sözleşme bazında takip edilir
                </p>
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Not: Bitiş tarihi zorunludur
                </p>
            </div>
        </div>

        <!-- Periyodik Ayarlar -->
        <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-white mb-4">
                <i class="fas fa-sync-alt mr-2 text-primary-600"></i>Periyodik Ayarlar
            </h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Sıklık <span class="text-red-500">*</span>
                    </label>
                    <select name="frequency" x-model="frequency" 
                            @change="autoSave()"
                            class="w-full px-4 py-3 border border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200" aria-required="true" aria-invalid="false" aria-describedby="frequency-error frequency-hint" data-validate="required|in:DAILY,WEEKLY,MONTHLY">
                    <p id="frequency-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="frequency-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Tekrar sıklığını seçiniz</p>
                        <option value="DAILY">Günlük</option>
                        <option value="WEEKLY">Haftalık</option>
                        <option value="MONTHLY">Aylık</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Aralık (Her X günde/haftada bir) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" min="1" name="interval" x-model.number="interval" 
                           @change="autoSave()"
                           class="w-full px-4 py-3 border border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200" required aria-required="true" aria-invalid="false" aria-describedby="interval-error interval-hint" inputmode="numeric" data-validate="required|numeric|min:1">
                    <p id="interval-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="interval-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Tekrar aralığını belirtiniz</p>
                </div>
                
                <div x-show="frequency==='WEEKLY'" x-transition class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calendar-week mr-2 text-primary-600"></i>
                        Haftanın Günleri <span class="text-red-500">*</span>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="d in weekdays" :key="d">
                            <label class="inline-flex items-center gap-2 px-4 py-2 border-2 border-primary-300 dark:border-primary-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors bg-white/70 dark:bg-primary-900/10">
                                <input type="checkbox" :value="d" name="byweekday[]" x-model="byweekday" @change="autoSave()" class="rounded border-2 border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"> 
                                <span class="text-sm font-medium" x-text="d"></span>
                            </label>
                        </template>
                    </div>
                </div>
                
                <div x-show="frequency==='MONTHLY'" x-transition class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calendar-day mr-2 text-primary-600"></i>
                        Ayın Günü <span class="text-red-500">*</span>
                    </label>
                    <input type="number" min="1" max="31" name="bymonthday" x-model.number="bymonthday" 
                           @change="autoSave()"
                           class="w-full px-4 py-3 border border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200" 
                           placeholder="1-31 arası bir sayı girin" required aria-required="true" aria-invalid="false" aria-describedby="bymonthday-error bymonthday-hint" inputmode="numeric" data-validate="required|numeric|min:1|max:31">
                    <p id="bymonthday-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="bymonthday-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Aylık tekrar günü</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        Ayın belirtilen gününde iş oluşturulur (31. gün kısa aylarda son gün olarak işlenir)
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Başlangıç Tarihi <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="start_date" x-model="start_date" 
                           @change="autoSave()"
                            class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner" required aria-required="true" aria-invalid="false" aria-describedby="start_date-error start_date-hint" data-validate="required|date">
                    <p id="start_date-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="start_date-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Başlangıç tarihini seçiniz</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Bitiş Tarihi (Opsiyonel)
                    </label>
                    <input type="date" name="end_date" x-model="end_date" 
                           @change="autoSave()"
                           class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner" aria-describedby="end_date-error end_date-hint" data-validate="date">
                    <p id="end_date-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                    <p id="end_date-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Opsiyonel: bitiş tarihi</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Saat <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <input type="number" min="0" max="23" name="byhour" x-model.number="byhour" 
                               @change="autoSave()"
                               class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner" 
                               placeholder="Saat" required aria-required="true" aria-invalid="false" aria-describedby="byhour-error byhour-hint" inputmode="numeric" data-validate="required|numeric|min:0|max:23">
                        <p id="byhour-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                        <p id="byhour-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">0-23 arasında saat</p>
                        <input type="number" min="0" max="59" name="byminute" x-model.number="byminute" 
                               @change="autoSave()"
                               class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner" 
                               placeholder="Dakika" required aria-required="true" aria-invalid="false" aria-describedby="byminute-error byminute-hint" inputmode="numeric" data-validate="required|numeric|min:0|max:59">
                        <p id="byminute-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                        <p id="byminute-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">0-59 arasında dakika</p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Süre (Dakika) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" min="15" step="15" name="duration_min" x-model.number="duration_min" 
                           @change="autoSave()"
                           class="w-full px-4 py-3 border border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200" 
                           required 
                           aria-required="true" 
                           inputmode="numeric" 
                           data-validate="required|numeric|min:15">
                    <p id="duration_min-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Dakika cinsinden (en az 15)</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calendar-check mr-2 text-primary-600"></i>
                        Tatil Politikası
                        <span class="text-gray-500 text-xs ml-1 cursor-help" title="Resmi tatillerde iş oluşturulsun mu?">
                            <i class="fas fa-info-circle"></i>
                        </span>
                    </label>
                    <select name="holiday_policy" x-model="holiday_policy" 
                            @change="autoSave()"
                            class="w-full px-4 py-3 border border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        <option value="SKIP">Tatillerde Atla</option>
                        <option value="INCLUDE">Tatillerde Dahil Et</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Exclusions (Hariç Tutulan Tarihler) -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-calendar-times mr-2 text-red-600"></i>
                    Hariç Tutulan Tarihler
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Bu tarihlerde iş oluşturulmayacak</p>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex gap-2">
                        <input type="date" x-model="newExclusionDate" 
                               class="flex-1 px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                               placeholder="Tarih seçin">
                        <button type="button" @click="addExclusion()" 
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Ekle
                        </button>
                    </div>
                    <div x-show="exclusions.length > 0" class="space-y-2">
                        <template x-for="(date, index) in exclusions" :key="index">
                            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-3 rounded-lg border border-red-200 dark:border-red-800">
                                <div class="flex items-center">
                                    <i class="fas fa-calendar-times text-red-600 mr-2"></i>
                                    <span class="text-gray-900 dark:text-white font-medium" x-text="formatDate(date)"></span>
                                </div>
                                <button type="button" @click="removeExclusion(index)" 
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div x-show="exclusions.length === 0" class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                        <i class="fas fa-info-circle mr-1"></i>Henüz hariç tutulan tarih yok
                    </div>
                    <!-- Hidden inputs for form submission -->
                    <template x-for="(date, index) in exclusions" :key="'hidden-' + index">
                        <input type="hidden" :name="'exclusions[' + index + ']'" :value="date">
                    </template>
                </div>
            </div>
        </div>

        <!-- Notlar -->
        <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-white mb-4">
                <i class="fas fa-sticky-note mr-2 text-primary-600"></i>Notlar
            </h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Varsayılan Not
                </label>
                <textarea name="default_notes" x-model="default_notes" 
                          @blur="autoSave()"
                          class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner" 
                          rows="3" placeholder="Bu periyodik iş için varsayılan not..."></textarea>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <a href="<?= base_url('/recurring') ?>" class="px-4 py-2 rounded-lg border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Geri Dön
                </a>
                <div class="flex gap-3">
                    <button type="button" @click="loadPreview()" 
                            :disabled="isLoadingPreview"
                            class="px-6 py-2 rounded-lg border-2 border-primary-500 text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-eye mr-2" :class="{'fa-spinner fa-spin': isLoadingPreview}"></i>
                        Önizle
                    </button>
                    <button type="submit" 
                            :disabled="isSubmitting"
                            class="px-6 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                            style="color: white !important; background: #4f46e5 !important;">
                        <template x-if="isSubmitting">
                            <span class="flex items-center" style="color: white !important;">
                                <i class="fas fa-spinner fa-spin mr-2" style="color: white !important;"></i>
                                İşleniyor...
                            </span>
                        </template>
                        <template x-if="!isSubmitting">
                            <span class="flex items-center" style="color: white !important;">
                                <i class="fas fa-save mr-2" style="color: white !important;"></i>
                                <?= $isEdit ? 'Güncelle' : 'Kaydet' ?>
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Preview Modal -->
<div x-show="showPreview" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;"
     @click.self="showPreview = false">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showPreview = false"></div>
        
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-eye mr-2 text-primary-600"></i>
                        Oluşturulacak İşler Önizlemesi
                    </h3>
                    <button @click="showPreview = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="px-6 py-4 max-h-96 overflow-y-auto">
                <div x-show="isLoadingPreview" class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-primary-500 text-2xl mb-2"></i>
                    <p class="text-gray-600 dark:text-gray-400">Önizleme oluşturuluyor...</p>
                </div>
                
                <div x-show="!isLoadingPreview && previewError" class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-red-500 text-2xl mb-2"></i>
                    <p class="text-red-600 dark:text-red-400" x-text="previewError"></p>
                </div>
                
                <div x-show="!isLoadingPreview && !previewError && previewData.length === 0" class="text-center py-8">
                    <i class="fas fa-info-circle text-gray-400 text-2xl mb-2"></i>
                    <p class="text-gray-600 dark:text-gray-400">Önizlenecek iş bulunamadı</p>
                </div>
                
                <div x-show="!isLoadingPreview && !previewError && previewData.length > 0">
                    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        İlk <span class="font-semibold" x-text="previewData.length"></span> iş gösteriliyor
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <i class="fas fa-calendar mr-1"></i>Tarih
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <i class="fas fa-clock mr-1"></i>Başlangıç
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <i class="fas fa-clock mr-1"></i>Bitiş
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <i class="fas fa-hourglass-half mr-1"></i>Süre
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(item, index) in previewData" :key="index">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white" x-text="formatDate(item.date)"></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" x-text="formatTime(item.start_at)"></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" x-text="formatTime(item.end_at)"></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" x-text="calculateDuration(item.start_at, item.end_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex justify-end">
                    <button @click="showPreview = false" 
                            class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                        <i class="fas fa-times mr-2"></i>Kapat
                    </button>
                </div>
            </div>
        </div>
    </div>
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
function recForm() {
    return {
        // Form data
        customer_id: <?= $isEdit ? (int)$item['customer_id'] : 'null' ?>,
        address_id: <?= $isEdit && $item['address_id'] ? (int)$item['address_id'] : 'null' ?>,
        service_id: <?= $isEdit && $item['service_id'] ? (int)$item['service_id'] : 'null' ?>,
        frequency: <?= $isEdit ? json_encode($item['frequency']) : "'WEEKLY'" ?>,
        interval: <?= $isEdit ? (int)$item['interval'] : 1 ?>,
        byweekday: <?= $isEdit ? json_encode(RecurringJob::decodeJsonList($item['byweekday'] ?? [])) : json_encode(['MON']) ?>,
        start_date: <?= $isEdit ? json_encode(substr($item['start_date'],0,10)) : json_encode(date('Y-m-d')) ?>,
        end_date: <?= $isEdit && !empty($item['end_date']) ? json_encode(substr($item['end_date'],0,10)) : 'null' ?>,
        byhour: <?= $isEdit && isset($item['byhour']) ? (int)$item['byhour'] : 9 ?>,
        byminute: <?= $isEdit && isset($item['byminute']) ? (int)$item['byminute'] : 0 ?>,
        duration_min: <?= $isEdit && isset($item['duration_min']) ? (int)$item['duration_min'] : 60 ?>,
        default_total_amount: <?= $isEdit ? (float)$item['default_total_amount'] : 0 ?>,
        default_notes: <?= $isEdit && !empty($item['default_notes']) ? json_encode($item['default_notes']) : "''" ?>,
        exclusions: <?= $isEdit ? json_encode(RecurringJob::decodeJsonList($item['exclusions'] ?? [])) : '[]' ?>,
        holiday_policy: <?= $isEdit && !empty($item['holiday_policy']) ? json_encode($item['holiday_policy']) : "'SKIP'" ?>,
        bymonthday: <?= $isEdit && isset($item['bymonthday']) ? (int)$item['bymonthday'] : 1 ?>,
        pricingModel: <?= $isEdit && isset($item['pricing_model']) ? json_encode($item['pricing_model']) : "'PER_JOB'" ?>,
        monthly_amount: <?= $isEdit && isset($item['monthly_amount']) && $item['monthly_amount'] ? (float)$item['monthly_amount'] : 0 ?>,
        contract_total_amount: <?= $isEdit && isset($item['contract_total_amount']) && $item['contract_total_amount'] ? (float)$item['contract_total_amount'] : 0 ?>,
        
        // UI state
        customerQuery: '',
        showCustomerList: false,
        isSearchingCustomers: false,
        customerResults: [],
        selectedCustomer: { id: null, name: '', phone: '' },
        customerAddresses: [],
        selectedAddressId: <?= $isEdit && $item['address_id'] ? (int)$item['address_id'] : 'null' ?>,
        services: [],
        selectedServiceId: <?= $isEdit && $item['service_id'] ? (int)$item['service_id'] : 'null' ?>,
        weekdays: ['MON','TUE','WED','THU','FRI','SAT','SUN'],
        searchTimeout: null,
        newExclusionDate: '',
        
        // Preview state
        previewData: [],
        showPreview: false,
        isLoadingPreview: false,
        previewError: null,
        
        // Auto-save
        autoSaveTimeout: null,
        
        // New customer modal
        showNewCustomerModal: false,
        newCustomer: {
            name: '',
            phone: '',
            email: '',
            notes: ''
        },
        
        init() {
            this.loadServices();
            this.loadDraft();
            
            // Initialize customerQuery if editing
            <?php if ($isEdit && isset($item['customer_name'])): ?>
            this.customerQuery = '<?= addslashes($item['customer_name']) ?>';
            this.selectedCustomer = {
                id: <?= $item['customer_id'] ?>,
                name: '<?= addslashes($item['customer_name']) ?>',
                phone: '<?= addslashes($item['customer_phone'] ?? '') ?>'
            };
            // Load addresses for edit mode
            if (this.customer_id) {
                this.loadCustomerAddresses();
            }
            <?php else: ?>
            if (this.customer_id) {
                this.loadCustomerData();
            }
            <?php endif; ?>
            
            // Set up auto-save listeners
            this.$watch('frequency', () => this.autoSave());
            this.$watch('interval', () => this.autoSave());
            this.$watch('byweekday', () => this.autoSave());
            this.$watch('start_date', () => this.autoSave());
            this.$watch('end_date', () => this.autoSave());
            this.$watch('byhour', () => this.autoSave());
            this.$watch('byminute', () => this.autoSave());
            this.$watch('duration_min', () => this.autoSave());
            this.$watch('default_total_amount', () => this.autoSave());
            this.$watch('exclusions', () => this.autoSave(), { deep: true });
            this.$watch('holiday_policy', () => this.autoSave());
            this.$watch('bymonthday', () => this.autoSave());
            this.$watch('pricingModel', () => this.autoSave());
            this.$watch('monthly_amount', () => this.autoSave());
            this.$watch('contract_total_amount', () => this.autoSave());
        },
        
        onPricingModelChange() {
            this.autoSave();
        },
        
        isSubmitting: false,
        
        handleFormSubmit(event) {
            // Prevent double submission
            if (this.isSubmitting) {
                event.preventDefault();
                return false;
            }
            
            // Client-side validation
            if (!this.customer_id) {
                event.preventDefault();
                this.showNotification('Müşteri seçimi zorunludur', 'error');
                return false;
            }
            
            if (!this.start_date) {
                event.preventDefault();
                this.showNotification('Başlangıç tarihi zorunludur', 'error');
                return false;
            }
            
            if (this.frequency === 'WEEKLY' && (!this.byweekday || this.byweekday.length === 0)) {
                event.preventDefault();
                this.showNotification('Haftalık tekrar için en az bir gün seçmelisiniz', 'error');
                return false;
            }
            
            if (this.frequency === 'MONTHLY' && (!this.bymonthday || this.bymonthday < 1 || this.bymonthday > 31)) {
                event.preventDefault();
                this.showNotification('Aylık tekrar için ayın günü 1-31 arasında olmalıdır', 'error');
                return false;
            }
            
            // Pricing model validations
            if (this.pricingModel === 'PER_JOB' && (!this.default_total_amount || this.default_total_amount <= 0)) {
                event.preventDefault();
                this.showNotification('İş başı ücret modeli için pozitif bir tutar zorunludur', 'error');
                return false;
            }

            if (this.pricingModel === 'PER_MONTH' && (!this.monthly_amount || this.monthly_amount <= 0)) {
                event.preventDefault();
                this.showNotification('Aylık ücret modeli için aylık tutar zorunludur', 'error');
                return false;
            }
            
            if (this.pricingModel === 'TOTAL_CONTRACT') {
                if (!this.contract_total_amount || this.contract_total_amount <= 0) {
                    event.preventDefault();
                    this.showNotification('Toplam sözleşme modeli için sözleşme tutarı zorunludur', 'error');
                    return false;
                }
                if (!this.end_date) {
                    event.preventDefault();
                    this.showNotification('Toplam sözleşme modeli için bitiş tarihi zorunludur', 'error');
                    return false;
                }
            }
            
            // Set submitting state
            this.isSubmitting = true;
            
            // Clear draft on successful submission
            this.clearDraft();
            
            // Form will submit normally
            return true;
        },
        
        // Exclusions management
        addExclusion() {
            if (!this.newExclusionDate) return;
            
            // Check if already exists
            if (this.exclusions.includes(this.newExclusionDate)) {
                this.showNotification('Bu tarih zaten hariç tutulanlar listesinde', 'error');
                return;
            }
            
            // Check if date is in the past
            const selectedDate = new Date(this.newExclusionDate);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                this.showNotification('Geçmiş tarih eklenemez', 'error');
                return;
            }
            
            this.exclusions.push(this.newExclusionDate);
            this.newExclusionDate = '';
            this.showNotification('Tarih hariç tutulanlar listesine eklendi', 'success');
        },
        
        removeExclusion(index) {
            this.exclusions.splice(index, 1);
            this.showNotification('Tarih listeden kaldırıldı', 'success');
        },
        
        // Preview functionality
        async loadPreview() {
            if (!this.start_date) {
                this.showNotification('Başlangıç tarihi gereklidir', 'error');
                return;
            }
            
            this.isLoadingPreview = true;
            this.previewError = null;
            
            try {
                const payload = {
                    frequency: this.frequency,
                    interval: this.interval,
                    byweekday: this.byweekday,
                    byhour: this.byhour,
                    byminute: this.byminute,
                    duration_min: this.duration_min,
                    start_date: this.start_date,
                    end_date: this.end_date || null,
                    timezone: 'Europe/Istanbul',
                    exclusions: this.exclusions,
                    limit: 20
                };
                
                // Add bymonthday for MONTHLY frequency
                if (this.frequency === 'MONTHLY' && this.bymonthday) {
                    payload.bymonthday = this.bymonthday;
                }
                
                const response = await fetch('<?= base_url('/api/recurring-preview') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('input[name="csrf_token"]')?.value || ''
                    },
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.previewData = data.data || [];
                    this.showPreview = true;
                } else {
                    this.previewError = data.message || 'Önizleme oluşturulamadı';
                }
            } catch (error) {
                console.error('Preview error:', error);
                this.previewError = 'Önizleme yüklenirken bir hata oluştu';
            } finally {
                this.isLoadingPreview = false;
            }
        },
        
        // Format helpers
        formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr + 'T00:00:00');
            const days = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
            const months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
            return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()} (${days[date.getDay()]})`;
        },
        
        formatTime(datetimeStr) {
            if (!datetimeStr) return '';
            const parts = datetimeStr.split(' ');
            return parts.length > 1 ? parts[1] : '';
        },
        
        calculateDuration(startAt, endAt) {
            if (!startAt || !endAt) return '';
            const start = new Date(startAt);
            const end = new Date(endAt);
            const diffMinutes = Math.round((end - start) / 1000 / 60);
            const hours = Math.floor(diffMinutes / 60);
            const minutes = diffMinutes % 60;
            if (hours > 0) {
                return minutes > 0 ? `${hours}s ${minutes}d` : `${hours}s`;
            }
            return `${minutes}d`;
        },
        
        // Auto-save functionality
        autoSave() {
            clearTimeout(this.autoSaveTimeout);
            this.autoSaveTimeout = setTimeout(() => {
                const draft = {
                    customer_id: this.customer_id,
                    service_id: this.selectedServiceId,
                    frequency: this.frequency,
                    interval: this.interval,
                    byweekday: this.byweekday,
                    start_date: this.start_date,
                    end_date: this.end_date,
                    byhour: this.byhour,
                    byminute: this.byminute,
                    duration_min: this.duration_min,
                    default_total_amount: this.default_total_amount,
                    default_notes: this.default_notes,
                    exclusions: this.exclusions,
                    holiday_policy: this.holiday_policy,
                    bymonthday: this.bymonthday,
                    pricingModel: this.pricingModel,
                    monthly_amount: this.monthly_amount,
                    contract_total_amount: this.contract_total_amount
                };
                localStorage.setItem('recurring_job_draft', JSON.stringify(draft));
                // Silently save - no notification to avoid annoyance
            }, 5000); // Save after 5 seconds of inactivity
        },
        
        loadDraft() {
            if (<?= $isEdit ? 'true' : 'false' ?>) return; // Don't load draft in edit mode
            
            try {
                const draft = localStorage.getItem('recurring_job_draft');
                if (draft) {
                    const data = JSON.parse(draft);
                    
                    // Confirm with user
                    if (confirm('Kaydedilmemiş bir taslak bulundu. Yüklemek ister misiniz?')) {
                        if (data.customer_id) this.customer_id = data.customer_id;
                        if (data.service_id) this.selectedServiceId = data.service_id;
                        if (data.frequency) this.frequency = data.frequency;
                        if (data.interval) this.interval = data.interval;
                        if (data.byweekday) this.byweekday = data.byweekday;
                        if (data.start_date) this.start_date = data.start_date;
                        if (data.end_date) this.end_date = data.end_date;
                        if (data.byhour !== undefined) this.byhour = data.byhour;
                        if (data.byminute !== undefined) this.byminute = data.byminute;
                        if (data.duration_min) this.duration_min = data.duration_min;
                        if (data.default_total_amount !== undefined) this.default_total_amount = data.default_total_amount;
                        if (data.default_notes) this.default_notes = data.default_notes;
                        if (data.exclusions) this.exclusions = data.exclusions;
                        if (data.holiday_policy) this.holiday_policy = data.holiday_policy;
                        if (data.bymonthday) this.bymonthday = data.bymonthday;
                    }
                }
            } catch (error) {
                console.error('Error loading draft:', error);
            }
        },
        
        clearDraft() {
            localStorage.removeItem('recurring_job_draft');
        },
        
        async loadServices() {
            // ROUND 29: Enhanced error handling for services JSON loading
            try {
                const response = await fetch('<?= base_url('/api/services') ?>');
                
                // ROUND 29: Check content-type before parsing JSON
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    // ROUND 29: Single, clean error message (no duplicate logging)
                    const errorMsg = 'Hizmetler yüklenemedi: Server returned non-JSON response';
                    console.error(errorMsg, { 
                        status: response.status, 
                        contentType: contentType 
                    });
                    // Show user-friendly error
                    if (typeof this.showError === 'function') {
                        this.showError('Hizmetler yüklenemedi. Lütfen sayfayı yenileyin.');
                    }
                    // Set empty services array to prevent form breakage
                    this.services = [];
                    return;
                }
                
                // ROUND 29: Parse JSON with proper error handling
                let data;
                try {
                    const text = await response.text();
                    // Additional check: ensure text is valid JSON
                    if (text.trim().startsWith('<')) {
                        // HTML response detected
                        console.error('Hizmetler yüklenemedi: Server returned HTML instead of JSON');
                        this.services = [];
                        if (typeof this.showError === 'function') {
                            this.showError('Hizmetler yüklenemedi. Lütfen sayfayı yenileyin.');
                        }
                        return;
                    }
                    data = JSON.parse(text);
                } catch (parseError) {
                    // ROUND 29: Single, clean error message for JSON parse errors
                    console.error('Hizmetler yüklenemedi: JSON parse error - server returned non-JSON response');
                    this.services = [];
                    if (typeof this.showError === 'function') {
                        this.showError('Hizmetler yüklenemedi. Lütfen sayfayı yenileyin.');
                    }
                    return;
                }
                
                // ROUND 29: Normalize response format
                if (data.success && Array.isArray(data.data)) {
                    this.services = data.data;
                } else if (Array.isArray(data)) {
                    // Handle case where API returns array directly
                    this.services = data;
                } else if (data.success === false) {
                    // API returned error in JSON format
                    console.error('Hizmetler yüklenemedi:', data.error || 'Unknown error');
                    this.services = [];
                    if (typeof this.showError === 'function') {
                        this.showError(data.error || 'Hizmetler yüklenemedi. Lütfen sayfayı yenileyin.');
                    }
                } else {
                    // Invalid response format
                    console.error('Hizmetler yüklenemedi: Invalid response format', data);
                    this.services = [];
                    if (typeof this.showError === 'function') {
                        this.showError('Hizmetler yüklenemedi. Lütfen sayfayı yenileyin.');
                    }
                }
            } catch (error) {
                // ROUND 29: Single, clean error message (no duplicate logging)
                console.error('Hizmetler yüklenemedi:', error instanceof Error ? error.message : String(error));
                
                // Set empty services array to prevent form breakage
                this.services = [];
                
                // Show user-friendly error
                if (typeof this.showError === 'function') {
                    this.showError('Hizmetler yüklenemedi. Lütfen sayfayı yenileyin.');
                }
            }
        },
        
        async loadCustomerData() {
            if (!this.customer_id) return;
            
            try {
                // Load customer details
                const customerResponse = await fetch(`<?= base_url('/api/customers') ?>/${this.customer_id}`);
                const customerData = await customerResponse.json();
                if (customerData.success) {
                    this.selectedCustomer = customerData.data;
                    this.customerSearch = customerData.data.name;
                }
                
                // Load customer addresses
                await this.loadCustomerAddresses();
            } catch (error) {
                console.error('Müşteri verileri yüklenemedi:', error);
                // Ensure customerAddresses is always an array
                this.customerAddresses = [];
            }
        },
        
        searchCustomers() {
            if (!this.customerQuery || this.customerQuery.length < 2) {
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
            this.customer_id = customer.id;
            this.customerQuery = customer.name;
            this.showCustomerList = false;
            this.customerResults = [];
            this.loadCustomerAddresses();
        },
        
        async loadCustomerAddresses() {
            if (!this.customer_id) {
                this.customerAddresses = [];
                return;
            }
            
            try {
                const response = await fetch(`<?= base_url('/api/customers') ?>/${this.customer_id}/addresses`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                const data = await response.json();
                if (data.success && Array.isArray(data.data)) {
                    this.customerAddresses = data.data;
                } else {
                    this.customerAddresses = [];
                }
            } catch (error) {
                console.error('Adresler yüklenemedi:', error);
                // Always ensure customerAddresses is an array, even on error
                this.customerAddresses = [];
            }
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
    };
}
</script>
