<?php 
$isEdit = !empty($entry);
$currentKind = $isEdit ? ($entry['kind'] ?? 'INCOME') : 'INCOME';
$currentJobId = $entry['job_id'] ?? ($job['id'] ?? null);
$defaultAmount = $defaultAmount ?? '';
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
              x-data="financeForm({ kind: '<?= $currentKind ?>', hasPresetJob: <?= $currentJobId ? 'true' : 'false' ?> })"
              x-init="init()"
              @submit="isSubmitting = true">
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-exchange-alt mr-2 text-primary-600"></i>Tür <span class="text-red-500">*</span>
                            </label>
                            <select name="kind" 
                                    x-model="kind" 
                                    @change="onKindChange()" 
                                    required
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 appearance-none">
                                <option value="INCOME" <?= $currentKind === 'INCOME' ? 'selected' : '' ?>>Gelir</option>
                                <option value="EXPENSE" <?= $currentKind === 'EXPENSE' ? 'selected' : '' ?>>Gider</option>
                            </select>
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
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
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
                                       class="w-full px-4 py-3 pr-12 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">₺</span>
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
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: İş Bilgisi (Sadece Gelir için) -->
            <div x-show="kind === 'INCOME'" 
                 x-transition
                 class="border-b border-gray-200 dark:border-gray-700"
                 style="display: none;">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-briefcase mr-2 text-green-600"></i>
                        İş Bilgisi
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Gelir ile ilişkilendirilecek işi seçin</p>
                </div>
                <div class="p-6">
                    <?php if (!empty($job)): ?>
                        <!-- Preset Job -->
                        <div class="bg-green-50 dark:bg-green-900/20 border-2 border-green-200 dark:border-green-800 rounded-lg p-4">
                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
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
                        <!-- Job Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-list mr-2 text-primary-600"></i>İş Seçin <span class="text-red-500">*</span>
                            </label>
                            <select name="job_id" 
                                    x-ref="jobSelect" 
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                <option value="">İş seçiniz</option>
                                <?php foreach ($completedJobs ?? [] as $j): ?>
                                    <?php $remaining = max(0, ($j['total_amount'] ?? 0) - ($j['amount_paid'] ?? 0)); ?>
                                    <option value="<?= $j['id'] ?>" <?= ($currentJobId ?? null) == $j['id'] ? 'selected' : '' ?>>
                                        #<?= $j['id'] ?> - <?= e($j['customer_name']) ?> (<?= Utils::formatDateTime($j['end_at']) ?>) - Kalan: <?= Utils::formatMoney($remaining) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                Yalnızca ödeme bekleyen işler listelenir
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
                        class="px-8 py-3 rounded-lg text-white bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                        :disabled="isSubmitting"
                        :class="{'opacity-50 cursor-not-allowed': isSubmitting}">
                    <span x-show="!isSubmitting">
                        <i class="fas fa-save mr-2"></i><?= $isEdit ? 'Güncelle' : 'Kaydet' ?>
                    </span>
                    <span x-show="isSubmitting">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Kaydediliyor...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function financeForm(initial) {
    return {
        isSubmitting: false,
        kind: initial.kind,
        hasPresetJob: initial.hasPresetJob,
        init() {
            this.toggleJobRequirement();
        },
        onKindChange() {
            this.toggleJobRequirement();
        },
        toggleJobRequirement() {
            const jobSelect = this.$refs.jobSelect;
            if (!jobSelect || this.hasPresetJob) {
                return;
            }
            if (this.kind === 'INCOME') {
                jobSelect.setAttribute('required', 'required');
            } else {
                jobSelect.removeAttribute('required');
                jobSelect.value = '';
            }
        }
    }
}
</script>


