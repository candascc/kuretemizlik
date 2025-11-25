<?php 
$isEdit = !empty($job);
?>
<!-- KUREAPP_R36_MARKER_JOBS_VIEW_V1 -->
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
              x-data="jobForm()" x-init="init()"
              data-csrf="<?= CSRF::get() ?>"
              data-api-base="<?= base_url('') ?>"
              data-initial-customer-id="<?= $isEdit ? (int)($job['customer_id'] ?? 0) : ((int)($_GET['prefill']['customer_id'] ?? 0)) ?>"
              data-initial-customer-name="<?= $isEdit ? htmlspecialchars($job['customer_name'] ?? '') : htmlspecialchars($_GET['prefill']['customer_name'] ?? '') ?>"
              data-initial-address-id="<?= $isEdit ? (int)($job['address_id'] ?? 0) : ((int)($_GET['prefill']['address_id'] ?? 0)) ?>"
              data-initial-service-id="<?= $isEdit ? (int)($job['service_id'] ?? 0) : ((int)($_GET['prefill']['service_id'] ?? 0)) ?>"
              data-initial-start-at="<?= $isEdit ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($job['start_at']))) : htmlspecialchars($_GET['prefill']['start_at'] ?? '') ?>"
              data-initial-end-at="<?= $isEdit ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($job['end_at']))) : htmlspecialchars($_GET['prefill']['end_at'] ?? '') ?>"
              data-initial-total-amount="<?= $isEdit ? number_format((float)($job['total_amount'] ?? 0), 2, '.', '') : htmlspecialchars($_GET['prefill']['total_amount'] ?? '') ?>"
              data-initial-already-paid="<?= $isEdit ? (float)($job['amount_paid'] ?? 0) : 0 ?>"
              data-initial-payment-amount="<?= htmlspecialchars($_GET['prefill']['payment_amount'] ?? '') ?>"
              data-initial-payment-date="<?= htmlspecialchars($_GET['prefill']['payment_date'] ?? date('Y-m-d')) ?>"
              data-initial-payment-note="<?= htmlspecialchars($_GET['prefill']['payment_note'] ?? '') ?>"
              @submit.prevent="isSubmitting = true" 
              onsubmit="return validateForm(this)">
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
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
                                       placeholder="Müşteri adı yazın..." 
                                       class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200" 
                                       autocomplete="off">
                                <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <input type="hidden" name="customer_id" :value="customerId" required>
                            <div x-show="showCustomerList" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 class="absolute z-50 bg-white dark:bg-gray-800 border-2 border-primary-200 dark:border-primary-700 rounded-lg mt-2 w-full max-h-64 overflow-auto shadow-xl"
                                 @mousedown="isInteractingWithCustomerList = true"
                                 @mouseup="isInteractingWithCustomerList = false"
                                 style="display: none;">
                                <template x-for="item in customerResults" :key="item.id">
                                    <div class="px-4 py-3 hover:bg-primary-50 dark:hover:bg-primary-900/20 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-0 transition-colors" @click="selectCustomer(item)">
                                        <div class="flex items-center">
                                            <i class="fas fa-user-circle text-primary-500 mr-3"></i>
                                            <span class="font-medium text-gray-900 dark:text-white" x-text="item.name"></span>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="customerResults.length === 0" class="px-4 py-3 text-center text-sm text-gray-500">
                                    <i class="fas fa-search mr-2"></i>Sonuç bulunamadı
                                </div>
                                <div class="px-4 py-3 border-t border-gray-200 bg-primary-50 dark:bg-primary-900/10">
                                    <button type="button" id="btn-new-customer" data-new-customer="1" @mousedown.prevent.stop="openNewCustomerModal" @click.prevent.stop="openNewCustomerModal" class="w-full text-left text-primary-600 hover:text-primary-700 font-medium">
                                        <i class="fas fa-plus mr-2"></i>Yeni Müşteri Ekle
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
                                            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 appearance-none">
                                        <option value="">Adres seçiniz</option>
                                        <template x-for="addr in addresses" :key="addr.id">
                                            <option :value="addr.id" x-text="addr.label ? (addr.label + ' - ' + addr.line) : addr.line"></option>
                                        </template>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                                </div>
                                <button type="button" 
                                        @click="showNewAddressForm = !showNewAddressForm" 
                                        class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-primary-600 hover:text-primary-700 border-2 border-dashed border-primary-300 dark:border-primary-700 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
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
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Etiket</label>
                                        <input type="text" x-model="newAddress.label" placeholder="Ev, İş, vb." 
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Adres <span class="text-red-500">*</span></label>
                                        <input type="text" x-model="newAddress.line" required placeholder="Tam adres bilgisi" 
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Şehir</label>
                                        <input type="text" x-model="newAddress.city" placeholder="Şehir" 
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                    </div>
                                </div>
                                <div class="flex justify-end gap-3 mt-4">
                                    <button type="button" @click="cancelNewAddress()" class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors">
                                        <i class="fas fa-times mr-1"></i>İptal
                                    </button>
                                    <button type="button" @click="saveNewAddress()" :disabled="!newAddress.line.trim()" 
                                            class="px-4 py-2 text-sm font-medium rounded-md bg-primary-600 text-white hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                        <i class="fas fa-save mr-1"></i>Kaydet
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
                                        class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 appearance-none">
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
                        </div>

                        <!-- Status Field -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-tasks mr-2 text-primary-600"></i>Durum
                            </label>
                            <div class="relative">
                                <select name="status" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 appearance-none">
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <!-- Start Date/Time -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-play-circle mr-2 text-green-600"></i>Başlangıç <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-2">
                                <input type="datetime-local" 
                                       name="start_at" 
                                       x-model="startAt" 
                                       required 
                                       @change="autoSetEnd()"
                                       class="flex-1 px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                <button type="button" 
                                        @click="quickStart(60)" 
                                        class="px-3 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                        title="1 saat sonra">
                                    <i class="fas fa-clock text-sm"></i>
                                </button>
                                <button type="button" 
                                        @click="quickStart(120)" 
                                        class="px-3 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                        title="2 saat sonra">
                                    <i class="fas fa-clock text-sm"></i>
                                </button>
                            </div>
                        </div>

                        <!-- End Date/Time -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-stop-circle mr-2 text-red-600"></i>Bitiş <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" 
                                   name="end_at" 
                                   x-model="endAt" 
                                   required
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
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
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-900/40 border-2 border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium text-blue-700 dark:text-blue-300">Toplam</span>
                                <i class="fas fa-calculator text-blue-600"></i>
                            </div>
                            <div class="flex items-baseline">
                                <input type="number" 
                                       step="0.01" 
                                       name="total_amount" 
                                       x-model="totalAmount" 
                                       required 
                                       placeholder="0.00"
                                       class="flex-1 bg-transparent border-0 text-2xl font-bold text-blue-900 dark:text-blue-100 focus:ring-2 focus:ring-blue-500 rounded px-2">
                                <span class="text-blue-700 dark:text-blue-300 ml-2">₺</span>
                            </div>
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
                                       class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">₺</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-calendar mr-2 text-primary-600"></i>Ödeme Tarihi
                            </label>
                            <input type="date" 
                                   name="payment_date" 
                                   x-model="paymentDate"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-comment mr-2 text-primary-600"></i>Ödeme Notu
                            </label>
                            <input type="text" 
                                   name="payment_note" 
                                   x-model="paymentNote" 
                                   placeholder="Not (opsiyonel)"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
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
                              class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 dark:bg-gray-700 dark:text-white transition-all duration-200 resize-none"><?= $isEdit ? htmlspecialchars($job['note'] ?? '') : '' ?></textarea>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-4 border-t border-gray-200 dark:border-gray-700">
                <a href="<?= base_url('/jobs') ?>" 
                   class="px-6 py-3 rounded-lg bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 font-medium transition-all duration-200">
                    <i class="fas fa-times mr-2"></i>İptal
                </a>
                <button type="submit" 
                        class="px-8 py-3 rounded-lg text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                        :disabled="isSubmitting"
                        :class="{'opacity-50 cursor-not-allowed': isSubmitting}">
                    <i class="fas fa-save mr-2"></i><?= $isEdit ? 'Güncelle' : 'Kaydet' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- New Customer Modal -->
<div x-show="showNewCustomerModal" id="newCustomerModal"
     x-transition
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="cancelNewCustomer()"></div>
        
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700">
                <h3 class="text-lg font-semibold text-white flex items-center">
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
                               required
                               placeholder="Müşteri adını girin" 
                               class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-phone mr-2 text-primary-600"></i>Telefon Numarası
                        </label>
                        <input type="tel" 
                               x-model="newCustomer.phone" 
                               placeholder="Telefon numarası (opsiyonel)" 
                               class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex justify-end gap-3">
                <button type="button" 
                        @click="cancelNewCustomer()" 
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

<?php $jobFormJsPath = __DIR__ . '/../../assets/js/job-form.js'; $jobFormJsVer = file_exists($jobFormJsPath) ? filemtime($jobFormJsPath) : time(); ?>
<script src="<?= base_url('/assets/js/job-form.js') ?>?v=<?= $jobFormJsVer ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Fallback: Define Alpine component inline if external JS failed (static served JS cannot execute PHP tags)
	if (typeof jobForm === 'undefined') {
		console.warn('jobForm.js not loaded, using inline initialization');
		window.jobForm = function() {
			return {
				isSubmitting: false,
				isInteractingWithCustomerList: false,
				// Core state
				customerId: <?= $isEdit ? (int)($job['customer_id'] ?? 0) : (isset($prefill['customer_id']) ? (int)$prefill['customer_id'] : 'null') ?>,
				customerQuery: <?= $isEdit ? json_encode($job['customer_name'] ?? '') : (isset($prefill['customer_name']) ? json_encode($prefill['customer_name']) : "''") ?>,
				customerResults: [],
				showCustomerList: false,
				nextCursor: null, // ROUND 12: Fix Alpine nextCursor error
				addresses: [],
				addressId: <?= $isEdit && !empty($job['address_id']) ? (int)$job['address_id'] : (isset($prefill['address_id']) && $prefill['address_id'] ? (int)$prefill['address_id'] : 'null') ?>,
				initialAddressId: <?= $isEdit && !empty($job['address_id']) ? (int)$job['address_id'] : (isset($prefill['address_id']) && $prefill['address_id'] ? (int)$prefill['address_id'] : 'null') ?>,
				showNewCustomerModal: false,
				newCustomer: { name: '', phone: '' },
				showNewAddressForm: false,
				newAddress: { label: '', line: '', city: '' },

				// Search customers
				async searchCustomers() {
					const q = this.customerQuery ? this.customerQuery.trim() : '';
					if (q.length < 2) {
						this.customerResults = [];
						this.showCustomerList = false;
						this.nextCursor = null; // Reset cursor on new search
						return;
					}
					try {
						const res = await fetch('<?= base_url('/api/search-customers') ?>?q=' + encodeURIComponent(q));
						const data = await res.json();
						if (data && data.success) {
							this.customerResults = data.data || [];
							this.nextCursor = data.nextCursor || null; // ROUND 12: Set cursor from API response
							this.showCustomerList = true;
						} else {
							this.nextCursor = null;
						}
					} catch (e) {
						console.error('customer search failed', e);
						this.nextCursor = null;
					}
				},

				// Load more customers (pagination) - ROUND 12: Fix nextCursor error
				async loadMoreCustomers() {
					if (!this.nextCursor) return;
					const q = this.customerQuery ? this.customerQuery.trim() : '';
					if (q.length < 2) return;
					try {
						const res = await fetch('<?= base_url('/api/search-customers') ?>?q=' + encodeURIComponent(q) + '&limit=20&cursor=' + encodeURIComponent(this.nextCursor));
						const data = await res.json();
						if (data && data.success) {
							const more = data.data || [];
							this.customerResults = this.customerResults.concat(more).slice(0, 100); // Max 100 results
							this.nextCursor = data.nextCursor || null; // Update cursor
						} else {
							this.nextCursor = null;
						}
					} catch (e) {
						console.error('Load more customers error:', e);
						this.nextCursor = null;
					}
				},

				selectCustomer(item) {
					this.customerId = item.id;
					this.customerQuery = item.name;
					this.showCustomerList = false;
					this.loadAddresses();
				},

				onCustomerInputBlur() {
					// Avoid closing list when interacting with the dropdown
					const self = this;
					setTimeout(function(){
						if (!self.isInteractingWithCustomerList) {
							self.showCustomerList = false;
						}
					}, 150);
				},

				async loadAddresses() {
					if (!this.customerId) { this.addresses = []; this.addressId = null; return; }
					try {
						const res = await fetch('<?= base_url('/api/customer-addresses/') ?>' + this.customerId);
						const data = await res.json();
						if (data && data.success) {
							this.addresses = data.data || [];
							if (this.initialAddressId) this.addressId = this.initialAddressId;
						}
					} catch (e) {
						console.error('loadAddresses failed', e);
					}
				},

				openNewCustomerModal() {
					this.showNewCustomerModal = true;
					this.newCustomer = { name: this.customerQuery || '', phone: '' };
				},

				cancelNewCustomer() {
					this.showNewCustomerModal = false;
					this.newCustomer = { name: '', phone: '' };
				},

				async saveNewCustomer() {
					if (!this.newCustomer.name || !this.newCustomer.name.trim()) {
						alert('Müşteri adı zorunludur.');
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
						if (data && data.success && data.data && data.data.id) {
							this.customerId = data.data.id;
							this.customerQuery = this.newCustomer.name;
							this.showCustomerList = false;
							this.showNewCustomerModal = false;
							await this.loadAddresses();
						} else {
							alert((data && data.error) ? data.error : 'Müşteri oluşturulamadı');
						}
					} catch (e) {
						alert('Bir hata oluştu');
					}
				},

				// Address helpers
				cancelNewAddress() {
					this.showNewAddressForm = false;
					this.newAddress = { label: '', line: '', city: '' };
				},

				async saveNewAddress() {
					if (!this.newAddress.line || !this.newAddress.line.trim() || !this.customerId) return;
					try {
						const res = await fetch(`<?= base_url('/api/customers') ?>/${this.customerId}/addresses`, {
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
								'X-CSRF-Token': '<?= CSRF::get() ?>'
							},
							body: JSON.stringify({
								label: this.newAddress.label,
								line: this.newAddress.line,
								city: this.newAddress.city
							})
						});
						const data = await res.json();
						if (data && data.success) {
							const newAddr = {
								id: data.data.address_id,
								label: this.newAddress.label,
								line: this.newAddress.line,
								city: this.newAddress.city
							};
							this.addresses.push(newAddr);
							this.addressId = newAddr.id;
							this.cancelNewAddress();
						} else {
							alert((data && data.error) ? data.error : 'Adres eklenemedi');
						}
					} catch (e) {
						alert('Bir hata oluştu');
					}
				},

				init() {
					if (this.customerId) this.loadAddresses();
				}
			}
		}

		// Re-initialize Alpine for this form so @click handlers become active
		try {
			if (window.Alpine && typeof window.Alpine.initTree === 'function') {
				const root = document.querySelector('form[x-data]') || document.body;
				window.Alpine.initTree(root);
			}
		} catch (e) {
			console.warn('Alpine re-init skipped:', e);
		}

		// As a last resort, bind vanilla click to open modal
		const btn = document.getElementById('btn-new-customer');
		if (btn) {
			btn.addEventListener('click', function(ev){
				ev.preventDefault();
				const modal = document.getElementById('newCustomerModal');
				if (modal) { modal.style.display = 'block'; }
			});
		}
	}

	// Universal binding: ensure the modal opens even if Alpine wiring fails partially
	(function(){
		const modal = document.getElementById('newCustomerModal');
		if (!modal) return;
		const open = function(ev){
			try { ev && ev.preventDefault(); ev && ev.stopPropagation(); } catch(_){}
			// Try Alpine state first
			try {
				const root = document.querySelector('form[x-data]');
				if (root && root.__x && root.__x.$data) {
					root.__x.$data.showNewCustomerModal = true;
					return;
				}
			} catch(_){}
			// Fallback: force display if Alpine not active
			modal.style.display = 'block';
		};
		// Delegate so it works even if the button appears later
		document.addEventListener('pointerdown', function(e){
			const t = e.target.closest('#btn-new-customer');
			if (t) { open(e); }
		}, true);
		document.addEventListener('click', function(e){
			const t = e.target.closest('#btn-new-customer');
			if (t) { open(e); }
		}, true);
		// Close overlay on backdrop click
		modal.addEventListener('click', function(e){
			if (e.target === modal) { modal.style.display = 'none'; }
		});
	})();
});
</script>


