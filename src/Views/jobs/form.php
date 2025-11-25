<?php 
// ROUND 29: Defensive variable initialization
$isEdit = !empty($job);
$customers = $customers ?? [];
$services = $services ?? [];
$statuses = $statuses ?? [
    'SCHEDULED' => 'Planlandı',
    'IN_PROGRESS' => 'Devam Ediyor',
    'DONE' => 'Tamamlandı',
    'CANCELLED' => 'İptal Edildi',
];
$prefill = $prefill ?? [];
$payments = $payments ?? [];
?>
<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="<?= base_url('/jobs') ?>" class="text-gray-400 hover:text-primary-600"><i class="fas fa-tasks"></i></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-500"><?= $isEdit ? 'Düzenle' : 'Yeni İş' ?></span></li>
        </ol>
    </nav>

    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-tasks mr-3 text-primary-600"></i>
            <?= $isEdit ? 'İşi Düzenle' : 'Yeni İş' ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2"><?= $isEdit ? 'İş bilgilerini güncelleyin' : 'Yeni bir iş kaydı oluşturun' ?></p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= $isEdit ? base_url("/jobs/update/{$job['id']}") : base_url('/jobs/create') ?>" 
              x-data="jobForm()" 
              @submit="handleFormSubmit($event)"
              data-auto-save="30000"
              id="jobForm"
              role="form" aria-describedby="job-form-errors" novalidate data-validate="true">
            <?= CSRF::field() ?>

            <!-- SECTION 1: Temel Bilgiler -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-info-circle mr-2 text-primary-600"></i>
                        Temel Bilgiler
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Müşteri ve hizmet bilgileri</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Customer Field -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-user mr-2 text-primary-600"></i>Müşteri <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="text" 
                                       x-model="customerQuery" 
                                       @input="searchCustomers" 
                                       @focus="if(customerQuery.length >= 2) showCustomerList = true"
                                       @blur="onCustomerInputBlur"
                                       @keydown.arrow-down="if(showCustomerList && customerResults.length > 0) { $event.preventDefault(); $refs.customerList.firstElementChild?.focus(); }"
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
                            </div>
                            <input type="hidden" name="customer_id" :value="customerId" required>
                            <div x-show="showCustomerList" 
                                 x-ref="customerList"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 class="absolute z-50 bg-white dark:bg-gray-800 border-2 border-primary-200 dark:border-primary-700 rounded-lg mt-2 w-full max-h-64 overflow-auto shadow-xl"
                                 style="display: none;"
                                 @mousedown="isInteractingWithCustomerList = true"
                                 @mouseup="isInteractingWithCustomerList = false"
                                 role="listbox"
                                 aria-label="Müşteri listesi">
                                <template x-for="(item, index) in customerResults" :key="item.id">
                                    <div class="px-4 py-3 hover:bg-primary-50 dark:hover:bg-primary-900/20 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-0 transition-colors focus:bg-primary-50 dark:focus:bg-primary-900/20 focus:outline-none" 
                                         @click="selectCustomer(item)"
                                         @keydown.enter="selectCustomer(item)"
                                         @keydown.arrow-down="if(index < customerResults.length - 1) $event.target.nextElementSibling?.focus()"
                                         @keydown.arrow-up="if(index > 0) $event.target.previousElementSibling?.focus()"
                                         @keydown.escape="showCustomerList = false"
                                         tabindex="0"
                                         role="option"
                                         :aria-selected="customerId === item.id">
                                        <div class="flex items-center">
                                            <i class="fas fa-user-circle text-primary-500 mr-3"></i>
                                            <span class="font-medium text-gray-900 dark:text-white" x-text="item.name"></span>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="isSearchingCustomers" class="px-4 py-3 text-center text-sm text-gray-500">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Aranıyor...
                                </div>
                                <div x-show="!isSearchingCustomers && customerResults.length === 0" class="px-4 py-3 text-center text-sm text-gray-500">
                                    <i class="fas fa-search mr-2"></i>Sonuç bulunamadı
                                </div>
                                <div class="px-4 py-3 border-t border-gray-200 bg-primary-50 dark:bg-primary-900/10">
                                    <button type="button"
                                            id="btn-new-customer"
                                            @mousedown.prevent.stop="openNewCustomerModal"
                                            @click.prevent.stop="openNewCustomerModal"
                                            onclick="return window.__openNewCustomerModal && window.__openNewCustomerModal(event)"
                                            class="w-full text-left text-primary-600 hover:text-primary-700 font-medium pointer-events-auto"
                                            style="z-index:9999">
                                        <i class="fas fa-plus mr-2"></i>Yeni Müşteri Ekle
                                    </button>
                                    <button type="button"
                                            x-show="nextCursor"
                                            @click="loadMoreCustomers"
                                            class="mt-2 w-full text-left text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white text-sm">
                                        <i class="fas fa-ellipsis-h mr-2"></i>Daha fazla yükle
                                    </button>
                                    <noscript>
                                        <a class="block text-primary-600 hover:text-primary-700 font-medium mt-2" href="<?= base_url('/customers/new') ?>?return=<?= urlencode(base_url('/jobs/new')) ?>&prefill[name]=">
                                            <i class="fas fa-external-link-alt mr-2"></i>Yeni Müşteri Ekle (No-JS)
                                        </a>
                                    </noscript>
                                </div>
                            </div>
                        </div>

                        <!-- Address Field -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-map-marker-alt mr-2 text-primary-600"></i>Adres
                            </label>
                            <div class="space-y-3">
                                <div class="relative">
                                    <select name="address_id" 
                                            x-model="addressId" 
                                            class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 appearance-none shadow-inner">
                                        <option value="">Adres seçiniz</option>
                                        <template x-for="addr in addresses" :key="addr.id">
                                            <option :value="addr.id" x-text="addr.label ? (addr.label + ' - ' + addr.line) : addr.line"></option>
                                        </template>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                                </div>
                                <button type="button" 
                                        @click="showNewAddressForm = !showNewAddressForm" 
                                        class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-primary-600 hover:text-primary-700 border-2 border-dashed border-primary-300 dark:border-primary-700 rounded-lg bg-white/60 dark:bg-primary-900/10 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>Yeni Adres Ekle
                                </button>
                            </div>
                            
                            <!-- New Address Form -->
                            <div x-show="showNewAddressForm" 
                                 x-transition
                                 class="mt-4 p-4 border-2 border-dashed border-primary-300 dark:border-primary-700 rounded-lg bg-primary-50/50 dark:bg-primary-900/10">
                                <div class="flex items-center mb-4">
                                    <i class="fas fa-map-marker-alt text-primary-600 mr-2"></i>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Yeni Adres</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Etiket</label>
                                        <input type="text" x-model="newAddress.label" name="new_address_label" placeholder="Ev, İş, vb." 
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Adres <span class="text-red-500">*</span></label>
                                        <input type="text" x-model="newAddress.line" name="new_address_line" placeholder="Tam adres bilgisi" 
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Şehir</label>
                                        <input type="text" x-model="newAddress.city" name="new_address_city" placeholder="Şehir" 
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                    </div>
                                </div>
                                <div class="flex justify-end gap-3 mt-4">
                                    <button type="button" @click="cancelNewAddress()" class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors">
                                        <i class="fas fa-times mr-1"></i>İptal
                                    </button>
                                    <button type="button" @click="saveNewAddress()" :disabled="!newAddress.line.trim()" 
                                            class="px-4 py-2 text-sm font-medium rounded-md bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                            style="color: white !important; background: #4f46e5 !important;">
                                        <i class="fas fa-save mr-1" style="color: white !important;"></i>
                                        <span style="color: white !important;">Kaydet</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Service Field -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-cogs mr-2 text-primary-600"></i>Hizmet <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select name="service_id" 
                                        x-model.number="serviceId" 
                                        @change="applyServiceDefaults()" 
                                        class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 appearance-none shadow-inner"
                                        required aria-required="true" aria-invalid="false"
                                        aria-describedby="service-error service-hint"
                                        data-validate="required|numeric|min:1">
                                    <option value="">Hizmet seçiniz</option>
                                    <?php foreach ($services as $s): ?>
                                        <option value="<?= $s['id'] ?>" 
                                                data-duration="<?= (int)($s['duration_min'] ?? 0) ?>" 
                                                data-fee="<?= (float)($s['default_fee'] ?? 0) ?>" 
                                                <?= ($isEdit && (int)($job['service_id'] ?? 0) === $s['id']) ? 'selected' : '' ?>>
                                            <?= e($s['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            </div>
                            <p id="service-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="service-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bir hizmet seçiniz</p>
                        </div>

                        <!-- Status Field -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-tasks mr-2 text-primary-600"></i>Durum
                            </label>
                            <div class="relative">
                                <select name="status" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white/70 dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 appearance-none shadow-inner">
                                    <?php foreach ($statuses as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= ($isEdit && ($job['status'] ?? '') === $value) ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Zaman ve Tarih -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-calendar-alt mr-2 text-purple-600"></i>
                        Zaman ve Tarih
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">İş başlangıç ve bitiş bilgileri</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Start Date/Time -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-play-circle mr-2 text-green-600"></i>Başlangıç <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="datetime-local" 
                                       name="start_at" 
                                       x-model="startAt" 
                                       required 
                                       @change="autoSetEnd()"
                                       aria-required="true" aria-invalid="false"
                                       aria-describedby="start_at-error start_at-hint"
                                       data-validate="required|datetime"
                                       data-no-shortcuts="true"
                                       placeholder="gg.aa.yyyy --:--"
                                       class="w-full px-4 py-3 pr-10 border-2 border-green-300 dark:border-green-700 rounded-lg bg-white/70 dark:bg-green-900/10 focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:text-white transition-all duration-200 shadow-inner">
                                <i class="fas fa-calendar-alt absolute right-3 top-1/2 -translate-y-1/2 text-green-500"></i>
                            </div>
                            <p id="start_at-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <div class="mt-1 flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                <i class="fas fa-info-circle text-green-600"></i>
                                <p id="start_at-hint" class="text-sm">Başlangıç tarih ve saatini seçiniz</p>
                            </div>
                        </div>

                        <!-- End Date/Time -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-stop-circle mr-2 text-red-600"></i>Bitiş <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-2 items-start">
                                <div class="relative flex-1">
                                    <input type="datetime-local" 
                                           name="end_at" 
                                           x-model="endAt" 
                                           required
                                           aria-required="true" aria-invalid="false"
                                           aria-describedby="end_at-error end_at-hint"
                                           data-validate="required|datetime"
                                           data-no-shortcuts="true"
                                           placeholder="gg.aa.yyyy --:--"
                                           class="w-full px-4 py-3 pr-10 border-2 border-red-300 dark:border-red-700 rounded-lg bg-white/70 dark:bg-red-900/10 focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:text-white transition-all duration-200 shadow-inner">
                                    <i class="fas fa-calendar-alt absolute right-3 top-1/2 -translate-y-1/2 text-red-500"></i>
                                </div>
                                <button type="button" 
                                        @click="quickEnd(60)" 
                                        class="px-3 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white/70 dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                                        title="Başlangıçtan 1 saat sonra">
                                    <i class="fas fa-clock text-sm text-gray-700 dark:text-gray-300"></i>
                                </button>
                                <button type="button" 
                                        @click="quickEnd(120)" 
                                        class="px-3 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white/70 dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                                        title="Başlangıçtan 2 saat sonra">
                                    <i class="fas fa-clock text-sm text-gray-700 dark:text-gray-300"></i>
                                </button>
                            </div>
                            <p id="end_at-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <div class="mt-1 flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                <i class="fas fa-info-circle text-red-600"></i>
                                <p id="end_at-hint" class="text-sm">Bitiş tarihi başlangıçtan sonra olmalıdır</p>
                            </div>
                            <p class="text-xs text-primary-600 dark:text-primary-400 mt-2 flex items-center" x-show="serviceDuration">
                                <i class="fas fa-info-circle mr-1"></i>
                                Tahmini süre: <span x-text="serviceDuration" class="font-medium ml-1"></span> dakika
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Ücret ve Ödeme -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-money-bill-wave mr-2 text-green-600"></i>
                        Ücret ve Ödeme
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Mali bilgiler ve ödeme detayları</p>
                </div>
                <div class="p-6">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-900/40 border-2 border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium text-blue-700 dark:text-blue-300">Toplam</span>
                                <i class="fas fa-calculator text-blue-600"></i>
                            </div>
                            <div class="relative">
                                <input type="number" 
                                       step="0.01" 
                                       name="total_amount" 
                                       x-model="totalAmount" 
                                       required 
                                       placeholder="0.00"
                                       aria-required="true" aria-invalid="false"
                                       aria-describedby="total_amount-error total_amount-hint"
                                       inputmode="decimal"
                                       data-validate="required|numeric|min:0.01"
                                       class="w-full bg-white/70 dark:bg-blue-900/20 border-2 border-blue-300 dark:border-blue-700 text-2xl font-bold text-blue-900 dark:text-blue-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 rounded-lg pr-10 px-3 shadow-inner transition-colors">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-blue-700 dark:text-blue-300 select-none">₺</span>
                            </div>
                            <p id="total_amount-error" class="field-error hidden text-sm text-red-600 mt-2"></p>
                            <p id="total_amount-hint" class="text-sm text-blue-700 dark:text-blue-300 mt-1 break-words">Pozitif bir tutar giriniz</p>
                        </div>

                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-900/40 border-2 border-green-200 dark:border-green-800 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium text-green-700 dark:text-green-300">Ödenen</span>
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                            <p class="text-2xl font-bold text-green-900 dark:text-green-100" x-text="formatMoney(alreadyPaid)"></p>
                        </div>

                        <div class="bg-gradient-to-br rounded-lg p-4 border-2" 
                             :class="remainingAmount() > 0 ? 'from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 border-orange-200 dark:border-orange-800' : 'from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/40 border-green-200 dark:border-green-800'">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium" :class="remainingAmount() > 0 ? 'text-orange-700 dark:text-orange-300' : 'text-green-700 dark:text-green-300'">Kalan</span>
                                <i class="fas fa-hourglass-half" :class="remainingAmount() > 0 ? 'text-orange-600' : 'text-green-600'"></i>
                            </div>
                            <p class="text-2xl font-bold" 
                               :class="remainingAmount() > 0 ? 'text-orange-900 dark:text-orange-100' : 'text-green-900 dark:text-green-100'"
                               x-text="formatMoney(remainingAmount())"></p>
                        </div>
                    </div>

                    <!-- Payment Details -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-plus-circle mr-2 text-primary-600"></i>Yeni Ödeme
                            </label>
                            <div class="relative">
                                <input type="number" 
                                       step="0.01" 
                                       name="payment_amount" 
                                       x-model="paymentAmount" 
                                       placeholder="0.00"
                                       aria-describedby="payment_amount-error payment_amount-hint"
                                       inputmode="decimal"
                                       data-validate="numeric|min:0"
                                       class="w-full pr-10 px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-primary-600">₺</span>
                                <p id="payment_amount-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                                <p id="payment_amount-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Varsa yeni ödeme tutarını giriniz</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-calendar mr-2 text-primary-600"></i>Ödeme Tarihi
                            </label>
                            <input type="date" 
                                   name="payment_date" 
                                   x-model="paymentDate"
                                   aria-describedby="payment_date-error payment_date-hint"
                                   data-validate="date"
                                   class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner">
                            <p id="payment_date-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="payment_date-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ödeme tarihini isteğe bağlı giriniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-comment mr-2 text-primary-600"></i>Ödeme Notu
                            </label>
                            <input type="text" 
                                   name="payment_note" 
                                   x-model="paymentNote" 
                                   placeholder="Not (opsiyonel)"
                                   class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner">
                        </div>
                    </div>

                    <!-- Summary Info -->
                    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-lg px-4 py-3 flex items-center justify-between">
                        <span class="font-medium text-blue-900 dark:text-blue-100">
                            <i class="fas fa-calculator mr-2"></i>Yeni ödeme sonrası kalan:
                        </span>
                        <span class="text-2xl font-bold text-blue-900 dark:text-blue-100" x-text="formatMoney(remainingAfterNew())"></span>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: Not -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-700 dark:to-slate-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-sticky-note mr-2 text-gray-600"></i>
                        Ek Bilgiler
                    </h2>
                </div>
                <div class="p-6">
                    <textarea name="note" 
                              rows="4" 
                              placeholder="İş hakkında notlar, özel talimatlar vb..."
                              class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 dark:bg-gray-700 dark:text-white transition-all duration-200 resize-none"><?= $isEdit ? e($job['note'] ?? '') : '' ?></textarea>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-between items-center border-t border-gray-200 dark:border-gray-700">
                <div class="flex gap-2">
                    <button type="button" 
                            @click="loadDraft()" 
                            class="px-4 py-2 text-sm rounded-lg bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/40 transition-colors">
                        <i class="fas fa-upload mr-1"></i>Taslağı Yükle
                    </button>
                    <button type="button" 
                            @click="clearDraft()" 
                            class="px-4 py-2 text-sm rounded-lg bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/40 transition-colors">
                        <i class="fas fa-trash mr-1"></i>Taslağı Sil
                    </button>
                </div>
                <div class="flex gap-4">
                    <a href="<?= base_url('/jobs') ?>" 
                       class="px-6 py-3 rounded-lg bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 font-medium transition-all duration-200">
                        <i class="fas fa-times mr-2"></i>İptal
                    </a>
                    <button type="submit" 
                            class="px-8 py-3 rounded-lg bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                            style="color: white !important; background: linear-gradient(to right, #4f46e5, #4338ca) !important;"
                            :disabled="isSubmitting"
                            :class="{'opacity-50 cursor-not-allowed': isSubmitting}">
                        <i class="fas fa-save mr-2" style="color: white !important;"></i><span style="color: white !important;"><?= $isEdit ? 'Güncelle' : 'Kaydet' ?></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- New Customer Modal -->
<div x-show="showNewCustomerModal" 
     id="newCustomerModal"
     x-transition
     class="fixed inset-0 z-50 overflow-y-auto" 
     role="dialog" aria-modal="true" aria-labelledby="newCustomerTitle"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="cancelNewCustomer()"></div>
        
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700">
                <h3 id="newCustomerTitle" class="text-lg font-semibold text-white flex items-center">
                    <i class="fas fa-user-plus mr-2"></i>
                    Yeni Müşteri Ekle
                </h3>
            </div>
            
            <div class="bg-white dark:bg-gray-800 px-6 py-4">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-user mr-2 text-primary-600"></i>Müşteri Adı <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               x-model="newCustomer.name" 
                               name="new_customer_name"
                               placeholder="Müşteri adını girin" 
                               class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                               autofocus>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-phone mr-2 text-primary-600"></i>Telefon Numarası
                        </label>
                        <input type="tel" 
                               x-model="newCustomer.phone" 
                               name="new_customer_phone"
                               placeholder="Telefon numarası (opsiyonel)" 
                               class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex justify-end gap-3">
                <button type="button" 
                        @click="cancelNewCustomer()" 
                        onclick="return window.__cancelNewCustomerFallback && window.__cancelNewCustomerFallback(event)"
                        class="px-4 py-2 rounded-lg border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <i class="fas fa-times mr-2"></i>İptal
                </button>
                <button type="button" 
                        id="btn-save-new-customer"
                        @click="saveNewCustomer()" 
                        onclick="return window.__saveNewCustomerFallback && window.__saveNewCustomerFallback(event)"
                        class="px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 transition-colors font-medium"
                        style="color: white !important; background: #4f46e5 !important;">
                    <i class="fas fa-save mr-2" style="color: white !important;"></i>
                    <span style="color: white !important;">Kaydet</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Absolute fallback opener in case Alpine handlers are not bound for any reason
window.__openNewCustomerModal = function(ev){
    try { ev && ev.preventDefault(); ev && ev.stopPropagation(); } catch(_){}
    try {
        const root = document.querySelector('form[x-data]');
        if (root && root.__x && root.__x.$data) {
            root.__x.$data.showNewCustomerModal = true;
            return false;
        }
    } catch(_){}
    // Fallback: force-display modal container if Alpine not active
    var modal = document.querySelector('div#newCustomerModal, [x-show=\"showNewCustomerModal\"]');
    if (modal) { modal.style.display = 'block'; }
    return false;
};

// Basic focus trap for modal
document.addEventListener('keydown', function(e){
    try {
        const modal = document.getElementById('newCustomerModal');
        if (!modal || modal.style.display === 'none') return;
        if (e.key === 'Escape') {
            window.__cancelNewCustomerFallback(e);
            return;
        }
        if (e.key !== 'Tab') return;
        const focusables = modal.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])');
        if (!focusables.length) return;
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) { last.focus(); e.preventDefault(); }
        else if (!e.shiftKey && document.activeElement === last) { first.focus(); e.preventDefault(); }
    } catch(_){}
});

// Absolute fallback saver (ensures click always handled)
window.__saveNewCustomerFallback = async function(ev){
    try { ev && ev.preventDefault(); ev && ev.stopPropagation(); } catch(_){}
    // If Alpine is active and method exists, let Alpine handle
    try {
        const root = document.querySelector('form[x-data]');
        if (root && root.__x && root.__x.$data && typeof root.__x.$data.saveNewCustomer === 'function') {
            return false;
        }
    } catch(_){}
    // Fallback path
    const nameEl = document.querySelector('input[name="new_customer_name"]');
    const phoneEl = document.querySelector('input[name="new_customer_phone"]');
    const name = (nameEl && nameEl.value || '').trim();
    const phone = (phoneEl && phoneEl.value || '').trim();
    if (!name) { alert('Müşteri adı zorunludur.'); return false; }
    try {
        const res = await fetch('<?= base_url('/api/customers') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= CSRF::get() ?>'
            },
            body: JSON.stringify({ name: name, phone: phone })
        });
        const data = await res.json().catch(() => ({}));
        if (data && data.success && data.data && data.data.id) {
            const hiddenId = document.querySelector('input[name="customer_id"]');
            const visibleInput = document.querySelector('input[placeholder="Müşteri adı yazın..."]');
            if (hiddenId) hiddenId.value = data.data.id;
            if (visibleInput) visibleInput.value = name;
            const modal = document.getElementById('newCustomerModal');
            if (modal) modal.style.display = 'none';
            return false;
        }
        alert((data && data.error) ? data.error : 'Müşteri oluşturulamadı');
    } catch(e) {
        alert('Bir hata oluştu: ' + e.message);
    }
    return false;
};

// Absolute fallback cancel for modal
window.__cancelNewCustomerFallback = function(ev){
    try { ev && ev.preventDefault(); ev && ev.stopPropagation(); } catch(_){}
    try {
        const root = document.querySelector('form[x-data]');
        if (root && root.__x && root.__x.$data && typeof root.__x.$data.cancelNewCustomer === 'function') {
            // Alpine will handle it
            return false;
        }
    } catch(_){}
    const modal = document.getElementById('newCustomerModal');
    if (modal) { modal.style.display = 'none'; }
    return false;
};

// Absolute fallback saver if Alpine is not active
(function(){
    const btn = document.getElementById('btn-save-new-customer');
    if (!btn) return;
    btn.addEventListener('click', async function(ev){
        // If Alpine is active, let Alpine handle it
        try {
            const root = document.querySelector('form[x-data]');
            if (root && root.__x && root.__x.$data && typeof root.__x.$data.saveNewCustomer === 'function') {
                return;
            }
        } catch(_) {}
        ev.preventDefault();
        ev.stopPropagation();
        // Fallback: read fields and call API directly
        const nameEl = document.querySelector('input[name="new_customer_name"]');
        const phoneEl = document.querySelector('input[name="new_customer_phone"]');
        const name = (nameEl && nameEl.value || '').trim();
        const phone = (phoneEl && phoneEl.value || '').trim();
        if (!name) { alert('Müşteri adı zorunludur.'); return; }
        try {
            const res = await fetch('<?= base_url('/api/customers') ?>', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= CSRF::get() ?>'
                },
                body: JSON.stringify({ name: name, phone: phone })
            });
            const data = await res.json().catch(() => ({}));
            if (data && data.success && data.data && data.data.id) {
                // Fill form fields
                const hiddenId = document.querySelector('input[name="customer_id"]');
                const visibleInput = document.querySelector('input[placeholder="Müşteri adı yazın..."]');
                if (hiddenId) hiddenId.value = data.data.id;
                if (visibleInput) visibleInput.value = name;
                // Hide modal
                const modal = document.getElementById('newCustomerModal');
                if (modal) modal.style.display = 'none';
            } else {
                alert((data && data.error) ? data.error : 'Müşteri oluşturulamadı');
            }
        } catch(e) {
            alert('Bir hata oluştu: ' + e.message);
        }
    });
})();

function jobForm() {
    return {
        isSubmitting: false,
        isInteractingWithCustomerList: false,
        
        // Customer selection
        customerId: <?= $isEdit ? (int)$job['customer_id'] : 'null' ?>,
        customerQuery: <?= $isEdit ? json_encode($job['customer_name'] ?? '') : "''" ?>,
        customerResults: [],
        showCustomerList: false,
        isSearchingCustomers: false,
        nextCursor: null, // ROUND 13: Fix Alpine nextCursor error (pagination cursor for customer search)
        
        // Address management
        addressId: <?= $isEdit && !empty($job['address_id']) ? (int)$job['address_id'] : 'null' ?>,
        addresses: [],
        showNewAddressForm: false,
        newAddress: { label: '', line: '', city: '' },
        
        // Customer modal
        showNewCustomerModal: false,
        newCustomer: { name: '', phone: '' },
        
        // Service and timing
        serviceId: <?= $isEdit && !empty($job['service_id']) ? (int)$job['service_id'] : 'null' ?>,
        serviceDuration: 0,
        startAt: '<?= $isEdit ? date('Y-m-d\TH:i', strtotime($job['start_at'])) : '' ?>',
        endAt: '<?= $isEdit ? date('Y-m-d\TH:i', strtotime($job['end_at'])) : '' ?>',
        
        // Payment
        totalAmount: '<?= $isEdit ? number_format((float)$job['total_amount'], 2, '.', '') : '' ?>',
        totalAmountTouched: <?= $isEdit ? 'true' : 'false' ?>,
        alreadyPaid: <?= $isEdit ? (float)($job['amount_paid'] ?? 0) : 0 ?>,
        paymentAmount: '',
        paymentDate: '<?= date('Y-m-d') ?>',
        paymentNote: '',
        
        // Customer search with debounce
        searchTimeout: null,
        
        // Auto-save functionality
        autoSaveTimeout: null,
        lastSavedData: null,
        
        async searchCustomers() {
            // Clear previous timeout
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
            
            const q = this.customerQuery ? this.customerQuery.trim() : '';
            if (q.length < 2) { 
                this.customerResults = []; 
                this.showCustomerList = false; 
                this.nextCursor = null; // ROUND 13: Reset cursor on new search
                return; 
            }
            
            // Debounce search requests
            this.searchTimeout = setTimeout(async () => {
                this.isSearchingCustomers = true;
                try {
                    const res = await fetch('<?= base_url('/api/search-customers') ?>?q=' + encodeURIComponent(q) + '&limit=20');
                    const data = await res.json();
                    if (data.success) {
                        this.customerResults = (data.data || []).slice(0, 20);
                        this.nextCursor = data.nextCursor || null; // ROUND 13: Set cursor from API response
                        this.showCustomerList = true;
                    } else {
                        console.error('Customer search failed:', data.error);
                        this.customerResults = [];
                        this.showCustomerList = false;
                        this.nextCursor = null; // ROUND 13: Reset cursor on error
                    }
                } catch (e) {
                    console.error('Customer search error:', e);
                    this.customerResults = [];
                    this.showCustomerList = false;
                    this.nextCursor = null; // ROUND 13: Reset cursor on error
                } finally {
                    this.isSearchingCustomers = false;
                }
            }, 300); // 300ms debounce
        },
        
        async loadMoreCustomers() {
            if (!this.nextCursor) return; // ROUND 13: Graceful check for nextCursor
            try {
                this.isSearchingCustomers = true;
                const res = await fetch('<?= base_url('/api/search-customers') ?>?q=' + encodeURIComponent(this.customerQuery.trim()) + '&limit=20&cursor=' + encodeURIComponent(this.nextCursor));
                const data = await res.json();
                if (data.success) {
                    const more = data.data || [];
                    this.customerResults = this.customerResults.concat(more).slice(0, 100);
                    this.nextCursor = data.nextCursor || null; // ROUND 13: Update cursor
                } else {
                    this.nextCursor = null; // ROUND 13: Reset cursor if no more results
                }
            } catch(e) {
                console.error('Customer load more error:', e);
                this.nextCursor = null; // ROUND 13: Reset cursor on error
            } finally {
                this.isSearchingCustomers = false;
            }
        },
        
        selectCustomer(item) {
            this.customerId = item.id; 
            this.customerQuery = item.name; 
            this.showCustomerList = false; 
            this.loadAddresses();
        },
        
        onCustomerInputBlur() {
            const self = this;
            setTimeout(function(){
                if (!self.isInteractingWithCustomerList) {
                    self.showCustomerList = false;
                }
            }, 150);
        },
        
        async loadAddresses() {
            if (!this.customerId) { 
                this.addresses = []; 
                this.addressId = null; 
                return; 
            }
            try {
                const res = await fetch('<?= base_url('/api/customer-addresses/') ?>' + this.customerId);
                const data = await res.json();
                if (data.success) {
                    this.addresses = data.data || [];
                }
            } catch (e) {
                console.error('Error loading addresses:', e);
            }
        },
        
        async saveNewAddress() {
            // Validate required fields
            if (!this.newAddress.line.trim()) {
                this.showNotification('Adres bilgisi zorunludur', 'error');
                return;
            }
            if (!this.customerId) {
                this.showNotification('Önce bir müşteri seçmelisiniz', 'error');
                return;
            }
            
            try {
                const res = await fetch(`<?= base_url('/api/customers') ?>/${this.customerId}/addresses`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= CSRF::get() ?>'
                    },
                    body: JSON.stringify(this.newAddress)
                });
                const data = await res.json();
                if (data.success) {
                    const newAddr = {
                        id: data.data.address_id,
                        label: this.newAddress.label,
                        line: this.newAddress.line,
                        city: this.newAddress.city
                    };
                    this.addresses.push(newAddr);
                    this.addressId = newAddr.id;
                    this.cancelNewAddress();
                    // Show success message
                    this.showNotification('Adres başarıyla eklendi!', 'success');
                } else {
                    this.showNotification(data.error || 'Adres eklenemedi', 'error');
                }
            } catch (e) {
                console.error('Address save error:', e);
                this.showNotification('Bir hata oluştu: ' + e.message, 'error');
            }
        },
        
        cancelNewAddress() {
            this.showNewAddressForm = false;
            this.newAddress = { label: '', line: '', city: '' };
        },
        
        openNewCustomerModal() {
            this.showNewCustomerModal = true;
            this.newCustomer = { name: '', phone: '' };
        },
        
        async saveNewCustomer() {
            // Validate required fields
            if (!this.newCustomer.name.trim()) {
                this.showNotification('Müşteri adı zorunludur.', 'error');
                return;
            }
            
            try {
                const res = await fetch('<?= base_url('/api/customers') ?>', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= CSRF::get() ?>'
                    },
                    body: JSON.stringify(this.newCustomer)
                });
                const data = await res.json();
                if (data.success) {
                    try { console.info('customer:create:success', { id: data.data?.id }); } catch(_){}
                    this.customerId = data.data.id;
                    this.customerQuery = this.newCustomer.name;
                    this.showCustomerList = false;
                    this.showNewCustomerModal = false;
                    this.loadAddresses();
                    this.showNotification('Müşteri başarıyla oluşturuldu!', 'success');
                } else {
                    try { console.error('customer:create:fail', data); } catch(_){}
                    this.showNotification(data.error || 'Müşteri oluşturulamadı', 'error');
                }
            } catch (e) {
                console.error('Customer creation error:', e);
                this.showNotification('Bir hata oluştu: ' + e.message, 'error');
            }
        },
        
        cancelNewCustomer() {
            this.showNewCustomerModal = false;
            this.newCustomer = { name: '', phone: '' };
        },
        
        applyServiceDefaults() {
            const sel = document.querySelector('select[name="service_id"]');
            const opt = sel ? sel.options[sel.selectedIndex] : null;
            if (!opt) return;
            const dur = parseInt(opt.getAttribute('data-duration') || '0', 10);
            const fee = parseFloat(opt.getAttribute('data-fee') || '0');
            this.serviceDuration = dur || 0;
            if (!this.totalAmountTouched && fee) {
                this.totalAmount = fee.toFixed(2);
            }
            if (this.startAt && dur) this.endAt = this.addMinutes(this.startAt, dur);
        },
        
        autoSetEnd() {
            if (this.startAt && this.serviceDuration) this.endAt = this.addMinutes(this.startAt, this.serviceDuration);
        },
        
        quickStart(mins) {
            const now = new Date();
            now.setMinutes(now.getMinutes() + mins);
            this.startAt = this.toInputValue(now);
            this.autoSetEnd();
        },
        
        quickEnd(mins) {
            if (!this.startAt) {
                this.showNotification('Önce başlangıç tarihini seçiniz', 'error');
                return;
            }
            this.endAt = this.addMinutes(this.startAt, mins);
        },
        
        addMinutes(dtLocal, mins) {
            const d = new Date(dtLocal);
            d.setMinutes(d.getMinutes() + mins);
            return this.toInputValue(d);
        },
        
        toInputValue(d) {
            const pad = (n) => String(n).padStart(2, '0');
            const y = d.getFullYear();
            const m = pad(d.getMonth()+1);
            const day = pad(d.getDate());
            const h = pad(d.getHours());
            const mi = pad(d.getMinutes());
            return `${y}-${m}-${day}T${h}:${mi}`;
        },
        
        parseNumber(value) {
            const num = parseFloat((value || '').toString().replace(',', '.'));
            return isNaN(num) ? 0 : num;
        },
        
        remainingAmount() {
            const total = this.parseNumber(this.totalAmount);
            const paid = this.parseNumber(this.alreadyPaid);
            return Math.max(total - paid, 0);
        },
        
        remainingAfterNew() {
            const total = this.parseNumber(this.totalAmount);
            const paid = this.parseNumber(this.alreadyPaid);
            const upcoming = this.parseNumber(this.paymentAmount);
            return Math.max(total - (paid + upcoming), 0);
        },
        
        formatMoney(value) {
            return this.parseNumber(value).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        
        // Notification system
        showNotification(message, type = 'info') {
            // Create notification element
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
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        },
        
        // Auto-save form data
        autoSave() {
            if (this.autoSaveTimeout) {
                clearTimeout(this.autoSaveTimeout);
            }
            
            this.autoSaveTimeout = setTimeout(() => {
                const formData = {
                    customerId: this.customerId,
                    serviceId: this.serviceId,
                    startAt: this.startAt,
                    endAt: this.endAt,
                    totalAmount: this.totalAmount,
                    note: document.querySelector('textarea[name="note"]')?.value || ''
                };
                
                // Only save if data has changed and has meaningful content
                if (JSON.stringify(formData) !== JSON.stringify(this.lastSavedData)) {
                    // Only save if at least customer or service is selected
                    if (formData.customerId || formData.serviceId || formData.startAt || formData.endAt || formData.totalAmount) {
                        const previousCustomerId = this.lastSavedData?.customerId;
                        localStorage.setItem('jobFormDraft', JSON.stringify(formData));
                        this.lastSavedData = formData;
                        // Only show notification for very significant changes (customer selection)
                        if (formData.customerId && !previousCustomerId) {
                            this.showNotification('Müşteri seçildi - form kaydedildi', 'info');
                        }
                    }
                }
            }, 5000); // Auto-save after 5 seconds of inactivity
        },
        
        // Load draft data
        loadDraft() {
            const draft = localStorage.getItem('jobFormDraft');
            if (draft && !<?= $isEdit ? 'true' : 'false' ?>) {
                try {
                    const data = JSON.parse(draft);
                    if (data.customerId) this.customerId = data.customerId;
                    if (data.serviceId) this.serviceId = data.serviceId;
                    if (data.startAt) this.startAt = data.startAt;
                    if (data.endAt) this.endAt = data.endAt;
                    if (data.totalAmount) this.totalAmount = data.totalAmount;
                    if (data.note) document.querySelector('textarea[name="note"]').value = data.note;
                    
                    this.lastSavedData = data;
                    this.showNotification('Kaydedilen taslak yüklendi', 'info');
                } catch (e) {
                    console.error('Error loading draft:', e);
                }
            }
        },
        
        // Clear draft data
        clearDraft() {
            localStorage.removeItem('jobFormDraft');
            this.lastSavedData = null;
        },
        
        // Handle form submission
        handleFormSubmit(event) {
            if (this.isSubmitting) {
                event.preventDefault();
                return false;
            }
            
            // Validate form
            if (!validateForm(event.target)) {
                event.preventDefault();
                return false;
            }
            
            // Set submitting state
            this.isSubmitting = true;
            
            // Clear draft on successful submission
            this.clearDraft();
            
            // Show loading notification
            this.showNotification('Form gönderiliyor...', 'info');
            
            // Allow form to submit normally
            return true;
        },
        
        init() {
            if (this.customerId) this.loadAddresses();
            if (this.serviceId) this.applyServiceDefaults();
            
            // Load draft data
            this.loadDraft();
            
            // Set up auto-save listeners only for important fields
            const importantFields = [
                'input[name="customer_id"]',
                'select[name="service_id"]',
                'input[name="start_at"]',
                'input[name="end_at"]',
                'input[name="total_amount"]',
                'textarea[name="note"]'
            ];
            
            importantFields.forEach(selector => {
                const element = document.querySelector(selector);
                if (element) {
                    element.addEventListener('change', () => this.autoSave());
                    element.addEventListener('blur', () => this.autoSave());
                }
            });
        }
    }
}

// Enhanced form validation
function validateForm(form) {
    const customerId = form.querySelector('input[name="customer_id"]').value;
    const startAt = form.querySelector('input[name="start_at"]').value;
    const endAt = form.querySelector('input[name="end_at"]').value;
    const totalAmount = form.querySelector('input[name="total_amount"]').value;
    const serviceId = form.querySelector('select[name="service_id"]').value;
    
    // Skip validation for modal inputs that are not part of main form
    const modalInputs = form.querySelectorAll('input[name^="new_"]');
    modalInputs.forEach(input => {
        input.removeAttribute('required');
    });
    
    
    // Clear previous error states
    form.querySelectorAll('.form-error').forEach(el => el.classList.remove('form-error'));
    
    let isValid = true;
    let errorMessage = '';
    
    if (!customerId) {
        errorMessage = 'Lütfen bir müşteri seçin.';
        const customerField = form.querySelector('input[name="customer_id"]').closest('div');
        if (customerField) customerField.classList.add('form-error');
        isValid = false;
    }
    
    if (!serviceId) {
        errorMessage = 'Lütfen bir hizmet seçin.';
        form.querySelector('select[name="service_id"]').classList.add('form-error');
        isValid = false;
    }
    
    if (!startAt) {
        errorMessage = 'Lütfen başlangıç tarihini girin.';
        form.querySelector('input[name="start_at"]').classList.add('form-error');
        isValid = false;
    }
    
    if (!endAt) {
        errorMessage = 'Lütfen bitiş tarihini girin.';
        form.querySelector('input[name="end_at"]').classList.add('form-error');
        isValid = false;
    }
    
    if (!totalAmount || parseFloat(totalAmount) <= 0) {
        errorMessage = 'Lütfen geçerli bir toplam tutar girin.';
        form.querySelector('input[name="total_amount"]').classList.add('form-error');
        isValid = false;
    }
    
    if (startAt && endAt) {
        const startDate = new Date(startAt);
        const endDate = new Date(endAt);
        
        if (endDate <= startDate) {
            errorMessage = 'Bitiş tarihi başlangıç tarihinden sonra olmalıdır.';
            form.querySelector('input[name="end_at"]').classList.add('form-error');
            isValid = false;
        }
    }
    
    if (!isValid) {
        showFormNotification(errorMessage, 'error');
        // Scroll to first error
        const firstError = form.querySelector('.form-error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    } else {
        // Clear draft on successful validation
        localStorage.removeItem('jobFormDraft');
    }
    
    return isValid;
}

// Form notification system
function showFormNotification(message, type = 'info') {
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
    }, 5000);
}
</script>
