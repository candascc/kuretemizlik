<?php 
$isEdit = !empty($entry);
$currentKind = $isEdit ? ($entry['kind'] ?? 'INCOME') : 'INCOME';
$currentJobId = $entry['job_id'] ?? ($job['id'] ?? $currentJobId ?? null);
$currentRecurringJobId = $entry['recurring_job_id'] ?? ($currentRecurringJobId ?? null);
$defaultAmount = $defaultAmount ?? '';
$currentIncomeSource = !empty($currentRecurringJobId) ? 'recurring' : 'job';
?>
<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="<?= base_url('/finance') ?>" class="text-gray-400 hover:text-primary-600"><i class="fas fa-money-bill"></i></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-500"><?= $isEdit ? 'Düzenle' : 'Yeni Kayıt' ?></span></li>
        </ol>
    </nav>

    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-money-bill-wave mr-3 text-primary-600"></i>
            <?= $isEdit ? 'Kaydı Düzenle' : 'Yeni Finans Kaydı' ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Gelir veya gider kaydı oluşturun</p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" 
              action="<?= $isEdit ? base_url("/finance/update/{$entry['id']}") : base_url('/finance/create') ?>"
              x-data="financeForm({ kind: '<?= $currentKind ?>', hasPresetJob: <?= $currentJobId ? 'true' : 'false' ?>, incomeSource: '<?= $currentIncomeSource ?>' })"
              x-init="init()"
              @submit="handleFormSubmit($event)"
              role="form" aria-describedby="finance-form-errors" novalidate data-validate="true">
            <?= CSRF::field() ?>

            <!-- SECTION 1: Kayıt Türü ve Bilgiler -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-chart-line mr-2 text-yellow-600"></i>
                        Kayıt Türü ve Bilgiler
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Gelir veya gider kaydı oluşturun</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <!-- Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-exchange-alt mr-2 text-primary-600"></i>Tür <span class="text-red-500">*</span>
                            </label>
                            <select name="kind" 
                                    x-model="kind" 
                                    @change="onKindChange()" 
                                    required
                                    aria-required="true"
                                    aria-invalid="false"
                                    aria-describedby="kind-error kind-hint"
                                    data-validate="required|in:INCOME,EXPENSE"
                                    class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 appearance-none shadow-inner">
                                <option value="INCOME" <?= $currentKind === 'INCOME' ? 'selected' : '' ?>>Gelir</option>
                                <option value="EXPENSE" <?= $currentKind === 'EXPENSE' ? 'selected' : '' ?>>Gider</option>
                            </select>
                            <p id="kind-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="kind-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gelir veya gider seçiniz</p>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-tags mr-2 text-primary-600"></i>Kategori <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="category" 
                                   value="<?= $isEdit ? e($entry['category']) : ($defaultCategory ?? '') ?>" 
                                   required 
                                   placeholder="Kategori adı"
                                   aria-required="true"
                                   aria-invalid="false"
                                   aria-describedby="category-error category-hint"
                                   data-validate="required|min:2|max:100"
                                   class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner">
                            <p id="category-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="category-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Kategori adı 2-100 karakter olmalıdır</p>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-lira-sign mr-2 text-green-600"></i>Miktar <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" 
                                       step="0.01" 
                                       name="amount" 
                                       value="<?= $isEdit ? e($entry['amount']) : ($defaultAmount !== '' ? e($defaultAmount) : '') ?>" 
                                       required 
                                       placeholder="0.00"
                                       aria-required="true"
                                       aria-invalid="false"
                                       aria-describedby="amount-error amount-hint"
                                       inputmode="decimal"
                                       data-validate="required|numeric|min:0.01"
                                       class="w-full px-4 py-3 pr-12 border-2 border-green-300 dark:border-green-700 rounded-lg bg-white/70 dark:bg-green-900/10 focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:text-white transition-all duration-200 shadow-inner">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">₺</span>
                                <p id="amount-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                                <p id="amount-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pozitif bir tutar giriniz (ör. 100.00)</p>
                            </div>
                        </div>

                        <!-- Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-calendar mr-2 text-primary-600"></i>Tarih <span class="text-red-500">*</span>
                            </label>
                            <input type="date" 
                                   name="date" 
                                   value="<?= $isEdit ? htmlspecialchars(substr($entry['date'], 0, 10)) : date('Y-m-d') ?>" 
                                   required
                                   aria-required="true"
                                   aria-invalid="false"
                                   aria-describedby="date-error date-hint"
                                   data-validate="required|date"
                                   class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white transition-all duration-200 shadow-inner">
                            <p id="date-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="date-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Tarih seçiniz</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Gelir Kaynağı (Sadece Gelir için) -->
            <div x-show="kind === 'INCOME'" 
                 x-transition
                 class="border-b border-gray-200 dark:border-gray-700"
                 style="display: none;">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-money-bill-wave mr-2 text-green-600"></i>
                        Gelir Kaynağı
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Gelir kaynağını seçin</p>
                </div>
                <div class="p-6">
                    <?php if (!empty($job)): ?>
                        <!-- Preset Job -->
                        <div class="bg-green-50 dark:bg-green-900/20 border-2 border-green-200 dark:border-green-800 rounded-lg p-4">
                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                            <input type="hidden" name="income_source" value="job">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-briefcase text-green-600 mr-2"></i>
                                        <span class="font-semibold text-gray-900 dark:text-white">İş #<?= $job['id'] ?></span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        <strong>Müşteri:</strong> <?= e($job['customer_name']) ?><br>
                                        <strong>Hizmet:</strong> <?= htmlspecialchars($job['service_name'] ?? '') ?><br>
                                        <strong>Tarih:</strong> <?= Utils::formatDateTime($job['end_at']) ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Kalan Tutar</div>
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                        <?= Utils::formatMoney(max(0, ($job['total_amount'] ?? 0) - ($job['amount_paid'] ?? 0))) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Income Source Selector -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-filter mr-2 text-primary-600"></i>Gelir Kaynağı <span class="text-red-500">*</span>
                            </label>
                            <select x-model="incomeSource" 
                                    @change="onIncomeSourceChange()"
                                    name="income_source"
                                    required
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                <option value="job" <?= $currentIncomeSource === 'job' ? 'selected' : '' ?>>İş Bazlı Gelir</option>
                                <option value="recurring" <?= $currentIncomeSource === 'recurring' ? 'selected' : '' ?>>Periyodik İş (Sözleşme Bazlı)</option>
                            </select>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                <span x-show="incomeSource === 'job'">Tek bir iş için gelir ekleyin</span>
                                <span x-show="incomeSource === 'recurring'" x-cloak>Aylık veya toplam sözleşme bazlı periyodik iş için gelir ekleyin</span>
                            </p>
                        </div>
                        
                        <!-- Job Selection (for job-based income) -->
                        <div x-show="incomeSource === 'job'" x-transition>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-list mr-2 text-primary-600"></i>İş Seçin <span class="text-red-500">*</span>
                            </label>
                            <select name="job_id" 
                                    x-ref="jobSelect" 
                                    :required="incomeSource === 'job'"
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                <option value="">İş seçiniz</option>
                                <?php foreach ($completedJobs ?? [] as $j): ?>
                                    <?php 
                                    $remaining = max(0, ($j['total_amount'] ?? 0) - ($j['amount_paid'] ?? 0));
                                    $isContractBased = !empty($j['recurring_job_id']) && !empty($j['pricing_model']) && in_array($j['pricing_model'], ['PER_MONTH', 'TOTAL_CONTRACT']);
                                    ?>
                                    <option value="<?= $j['id'] ?>" <?= ($currentJobId ?? null) == $j['id'] ? 'selected' : '' ?>>
                                        #<?= $j['id'] ?> - <?= e($j['customer_name']) ?> (<?= Utils::formatDateTime($j['end_at']) ?>) 
                                        <?php if ($isContractBased): ?>
                                            - Sözleşme Dahil
                                        <?php else: ?>
                                            - Kalan: <?= Utils::formatMoney($remaining) ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                Yalnızca ödeme bekleyen işler listelenir
                            </p>
                        </div>
                        
                        <!-- Recurring Job Selection (for contract-based income) -->
                        <div x-show="incomeSource === 'recurring'" x-transition x-cloak>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-sync-alt mr-2 text-primary-600"></i>Periyodik İş Seçin <span class="text-red-500">*</span>
                            </label>
                            <select name="recurring_job_id" 
                                    x-ref="recurringJobSelect" 
                                    :required="incomeSource === 'recurring'"
                                    @change="onRecurringJobSelected()"
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                <option value="">Periyodik iş seçiniz</option>
                                <?php if (!empty($recurringJobs) && is_array($recurringJobs)): ?>
                                    <?php foreach ($recurringJobs as $rj): ?>
                                        <?php
                                        $amountText = '';
                                        if (!empty($rj['pricing_model']) && $rj['pricing_model'] === 'PER_MONTH') {
                                            $amountText = 'Aylık: ' . Utils::formatMoney($rj['monthly_amount'] ?? 0);
                                        } elseif (!empty($rj['pricing_model']) && $rj['pricing_model'] === 'TOTAL_CONTRACT') {
                                            $amountText = 'Toplam: ' . Utils::formatMoney($rj['contract_total_amount'] ?? 0);
                                        }
                                        $customerName = !empty($rj['customer_name']) ? e($rj['customer_name']) : 'Müşteri Adı Yok';
                                        ?>
                                        <option value="<?= $rj['id'] ?>" 
                                                <?= ($currentRecurringJobId ?? null) == $rj['id'] ? 'selected' : '' ?>
                                                data-model="<?= htmlspecialchars($rj['pricing_model'] ?? '') ?>" 
                                                data-monthly="<?= $rj['monthly_amount'] ?? 0 ?>" 
                                                data-contract="<?= $rj['contract_total_amount'] ?? 0 ?>">
                                            #<?= $rj['id'] ?> - <?= $customerName ?> - <?= $amountText ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Henüz aktif sözleşme bazlı periyodik iş yok</option>
                                <?php endif; ?>
                            </select>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                Aylık veya toplam sözleşme bazlı aktif periyodik işler listelenir
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SECTION 3: Not -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-700 dark:to-slate-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-sticky-note mr-2 text-gray-600"></i>
                        Ek Bilgiler
                    </h2>
                </div>
                <div class="p-6">
                    <textarea name="note" 
                              rows="3" 
                              placeholder="Kayıt hakkında notlar (opsiyonel)"
                              class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 dark:bg-gray-700 dark:text-white transition-all duration-200 resize-none"><?= $isEdit ? htmlspecialchars($entry['note'] ?? '') : '' ?></textarea>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-4 border-t border-gray-200 dark:border-gray-700">
                <a href="<?= base_url('/finance') ?>" 
                   class="px-6 py-3 rounded-lg bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 font-medium transition-all duration-200">
                    <i class="fas fa-times mr-2"></i>İptal
                </a>
                <button type="submit" 
                        class="px-8 py-3 rounded-lg bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                        style="color: white !important; background: linear-gradient(to right, #4f46e5, #4338ca) !important;"
                        :disabled="isSubmitting"
                        :class="{'opacity-50 cursor-not-allowed': isSubmitting}">
                    <span x-show="!isSubmitting" style="color: white !important;">
                        <i class="fas fa-save mr-2" style="color: white !important;"></i><?= $isEdit ? 'Güncelle' : 'Kaydet' ?>
                    </span>
                    <span x-show="isSubmitting" style="color: white !important;">
                        <i class="fas fa-spinner fa-spin mr-2" style="color: white !important;"></i>Kaydediliyor...
                    </span>
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

function financeForm(initial) {
    return {
        isSubmitting: false,
        kind: initial.kind,
        hasPresetJob: initial.hasPresetJob,
        incomeSource: initial.incomeSource || 'job',
        init() {
            this.toggleJobRequirement();
        },
        handleFormSubmit(event) {
            // ===== PRODUCTION FIX: Prevent double submission =====
            if (this.isSubmitting) {
                event.preventDefault();
                return false;
            }
            
            // Set submitting state
            this.isSubmitting = true;
            
            // Allow form to submit normally
            return true;
        },
        onKindChange() {
            this.toggleJobRequirement();
        },
        onIncomeSourceChange() {
            // Clear both selects when switching
            if (this.$refs.jobSelect) {
                this.$refs.jobSelect.value = '';
            }
            if (this.$refs.recurringJobSelect) {
                this.$refs.recurringJobSelect.value = '';
            }
            this.toggleJobRequirement();
        },
        onRecurringJobSelected() {
            const select = this.$refs.recurringJobSelect;
            if (!select || !select.value) return;
            
            const option = select.options[select.selectedIndex];
            const model = option.getAttribute('data-model');
            const amountField = document.querySelector('input[name="amount"]');
            
            if (amountField) {
                if (model === 'PER_MONTH') {
                    const monthly = parseFloat(option.getAttribute('data-monthly')) || 0;
                    amountField.value = monthly.toFixed(2);
                } else if (model === 'TOTAL_CONTRACT') {
                    const contract = parseFloat(option.getAttribute('data-contract')) || 0;
                    amountField.value = contract.toFixed(2);
                }
            }
        },
        toggleJobRequirement() {
            if (this.hasPresetJob || this.kind !== 'INCOME') {
                return;
            }
            
            if (this.incomeSource === 'job' && this.$refs.jobSelect) {
                this.$refs.jobSelect.setAttribute('required', 'required');
                if (this.$refs.recurringJobSelect) {
                    this.$refs.recurringJobSelect.removeAttribute('required');
                    this.$refs.recurringJobSelect.value = '';
                }
            } else if (this.incomeSource === 'recurring' && this.$refs.recurringJobSelect) {
                this.$refs.recurringJobSelect.setAttribute('required', 'required');
                if (this.$refs.jobSelect) {
                    this.$refs.jobSelect.removeAttribute('required');
                    this.$refs.jobSelect.value = '';
                }
            }
        }
    }
}
</script>

