<?php
/** @var array $job */
/** @var array $recurringJob */
/** @var array $payments */
/** @var array $occurrences */
/** @var bool $isRecurring */
?>
<div class="space-y-8" x-data="jobManagement()">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li>
                <a href="<?= base_url('/jobs') ?>" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-tasks"></i>
                    <span class="sr-only">İşler</span>
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500 font-medium">İş Yönetimi #<?= e($job['id']) ?></span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
                İş Yönetimi #<?= e($job['id']) ?>
                <?php if ($isRecurring): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400 ml-2">
                        <i class="fas fa-sync-alt mr-1"></i>Periyodik
                    </span>
                <?php endif; ?>
            </h2>
            <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                <div class="mt-2 flex items-center text-sm text-gray-500">
                    <i class="fas fa-user mr-1"></i>
                    <?= e($job['customer_name']) ?>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500">
                    <i class="fas fa-calendar mr-1"></i>
                    <?= Utils::formatDateTime($job['start_at'], 'd.m.Y H:i') ?> - <?= Utils::formatDateTime($job['end_at'], 'H:i') ?>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500">
                    <i class="fas fa-money-bill mr-1"></i>
                    <?= Utils::formatMoney($job['total_amount']) ?>
                </div>
            </div>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <a href="<?= base_url("/jobs") ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>
                İşlere Dön
            </a>
            <?php if ($isRecurring): ?>
                <a href="<?= base_url("/recurring/{$recurringJob['id']}") ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Periyodik Ayarlar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status & Quick Actions -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= $job['status'] === 'DONE' ? 'bg-green-100 text-green-800' : ($job['status'] === 'CANCELLED' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                            <?= e($job['status'] === 'DONE' ? 'Tamamlandı' : ($job['status'] === 'CANCELLED' ? 'İptal' : 'Planlandı')) ?>
                        </span>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">İş Durumu</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Durumu değiştirmek için aşağıdaki butonları kullanın</p>
                    </div>
                </div>
                <div class="mt-4 sm:mt-0 flex space-x-3">
                    <form method="POST" action="<?= base_url("/jobs/status/{$job['id']}") ?>" class="inline">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="status" value="DONE">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                            <i class="fas fa-check mr-2"></i>Tamamlandı
                        </button>
                    </form>
                    <form method="POST" action="<?= base_url("/jobs/status/{$job['id']}") ?>" class="inline">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="status" value="CANCELLED">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                            <i class="fas fa-times mr-2"></i>İptal Et
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Job Details -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Basic Information -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-info-circle mr-2 text-primary-600"></i>İş Bilgileri
                    </h3>
                    
                    <form method="POST" action="<?= base_url("/jobs/update/{$job['id']}") ?>" class="space-y-4">
                        <?= CSRF::field() ?>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Müşteri</label>
                                <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <i class="fas fa-user text-primary-600 mr-2"></i>
                                    <span class="text-sm font-medium"><?= e($job['customer_name']) ?></span>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hizmet</label>
                                <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <i class="fas fa-cogs text-primary-600 mr-2"></i>
                                    <span class="text-sm font-medium"><?= htmlspecialchars($job['service_name'] ?? 'Belirtilmemiş') ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Başlangıç</label>
                                <input type="datetime-local" name="start_at" value="<?= date('Y-m-d\TH:i', strtotime($job['start_at'])) ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bitiş</label>
                                <input type="datetime-local" name="end_at" value="<?= date('Y-m-d\TH:i', strtotime($job['end_at'])) ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notlar</label>
                            <textarea name="note" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                      placeholder="İş notları..."><?= htmlspecialchars($job['note'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                                <i class="fas fa-save mr-2"></i>Güncelle
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Contract Information -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-2">
                        <i class="fas fa-file-contract mr-2 text-blue-600"></i><?= __('contracts.panel.section_title') ?>
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        <?= __('contracts.panel.section_subtext') ?>
                    </p>
                    
                    <!-- Flash Messages -->
                    <?php if (!empty($flash['success'])): ?>
                        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <p class="text-sm text-green-800 dark:text-green-300"><?= e($flash['success']) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($flash['error'])): ?>
                        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-sm text-red-800 dark:text-red-300"><?= e($flash['error']) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Contract Status -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('contracts.panel.status_label') ?></label>
                        <div class="flex items-center">
                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= $contractStatus['class'] ?>">
                                <?= e($contractStatus['label']) ?>
                            </span>
                            <?php if ($contractStatus['has_contract'] && $contract['approved_at']): ?>
                                <span class="ml-3 text-sm text-gray-500 dark:text-gray-400">
                                    <?= __('contracts.panel.status.APPROVED') ?>: <?= Utils::formatDateTime($contract['approved_at'], 'd.m.Y H:i') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- SMS Info (if contract exists and SMS was sent) -->
                    <?php if ($contractStatus['has_contract'] && !empty($contract['sms_sent_at'])): ?>
                        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-info-circle mr-2"></i>
                                <?= __('contracts.panel.last_sms_sent', ['date' => Utils::formatDateTime($contract['sms_sent_at'], 'd.m.Y H:i')]) ?>
                            </p>
                            <?php if (!empty($contract['sms_sent_count']) && $contract['sms_sent_count'] > 0): ?>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    <?= __('contracts.panel.total_sms_count', ['count' => (int)$contract['sms_sent_count']]) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Send SMS Button -->
                    <div class="mt-4">
                        <?php if ($contractStatus['has_contract'] && $contract['status'] !== 'APPROVED'): ?>
                            <form method="POST" action="<?= base_url("/jobs/{$job['id']}/contract/send-sms") ?>" class="inline">
                                <?= CSRF::field() ?>
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                    <i class="fas fa-sms mr-2"></i>
                                    <?= __('contracts.panel.resend_sms') ?>
                                </button>
                            </form>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <?= __('contracts.panel.sms_help_text') ?>
                            </p>
                        <?php elseif (!$contractStatus['has_contract']): ?>
                            <form method="POST" action="<?= base_url("/jobs/{$job['id']}/contract/send-sms") ?>" class="inline">
                                <?= CSRF::field() ?>
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                    <i class="fas fa-sms mr-2"></i>
                                    <?= __('contracts.panel.send_sms') ?>
                                </button>
                            </form>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <?= __('contracts.panel.sms_help_text') ?>
                            </p>
                        <?php else: ?>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <i class="fas fa-check-circle mr-2 text-green-600"></i>
                                <?= __('contracts.panel.status.APPROVED') ?>. Müşteriye SMS gönderilemez.
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($contractStatus['has_contract'] && !empty($contract['id'])): ?>
                            <?php
                                // TODO: İleride public_token (UUID) eklenip linkler daha güvenli hale getirilecek.
                                $publicLink = base_url('/contract/' . (int)$contract['id']);
                            ?>
                            <div class="mt-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('contracts.panel.public_link_label') ?></label>
                                <div class="flex items-center">
                                    <input type="text" readonly value="<?= e($publicLink) ?>" 
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-md bg-gray-50 dark:bg-gray-700 text-sm text-gray-700 dark:text-gray-300">
                                    <button type="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)" 
                                            class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-r-md hover:bg-gray-300 dark:hover:bg-gray-500">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <div class="mt-2 flex gap-2">
                                    <a href="<?= e($publicLink) ?>" target="_blank" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <i class="fas fa-external-link-alt mr-2"></i>
                                        <?= __('contracts.panel.view_contract') ?>
                                    </a>
                                    <a href="<?= base_url("/contracts/{$contract['id']}/print") ?>" target="_blank" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <i class="fas fa-print mr-2"></i>
                                        <?= __('contracts.panel.print_contract') ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <?php if (!empty($timelineEvents)): ?>
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg mt-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-clock mr-2 text-blue-600"></i>
                        <?= __('contracts.panel.timeline.title') ?>
                    </h3>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <?php foreach ($timelineEvents as $event): ?>
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/20">
                                        <i class="fas fa-<?= e($event['icon']) ?> text-blue-600 dark:text-blue-400"></i>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?= e($event['label']) ?>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        <?= Utils::formatDateTime($event['datetime'], 'd.m.Y H:i') ?> - <?= e($event['description']) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Financial Information -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-money-bill-wave mr-2 text-green-600"></i>Finansal Bilgiler
                    </h3>
                    
                    <?php 
                    // Check if this is a contract-based recurring job
                    // First check if recurring_job_id exists and get pricing_model from job or recurring job
                    $pricingModel = null;
                    if (!empty($job['recurring_job_id'])) {
                        // Try to get pricing_model from job record first (might be in JOIN)
                        if (!empty($job['pricing_model'])) {
                            $pricingModel = $job['pricing_model'];
                        } elseif (!empty($recurringJob) && !empty($recurringJob['pricing_model'])) {
                            $pricingModel = $recurringJob['pricing_model'];
                        }
                    }
                    $isContractBased = !empty($job['recurring_job_id']) && !empty($pricingModel) && 
                                       in_array($pricingModel, ['PER_MONTH', 'TOTAL_CONTRACT']);
                    ?>
                    
                    <?php if ($isContractBased): ?>
                        <!-- Sözleşme Bazlı İş Bilgisi -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 border-2 border-blue-200 dark:border-blue-800">
                            <div class="flex items-start">
                                <i class="fas fa-file-contract text-blue-600 text-2xl mr-4 mt-1"></i>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-2">Sözleşme Bazlı İş</h4>
                                    <p class="text-sm text-blue-800 dark:text-blue-400 mb-4">
                                        Bu iş, periyodik iş tanımına bağlıdır ve ödeme takibi periyodik iş üzerinden yapılır. 
                                        Ödemeleri finans bölümünden "Periyodik İş (Sözleşme Bazlı)" seçeneği ile ekleyebilirsiniz.
                                    </p>
                                    <?php 
                                    $pricingInfo = '';
                                    if (!empty($recurringJob) && !empty($pricingModel)) {
                                        if ($pricingModel === 'PER_MONTH') {
                                            $pricingInfo = 'Aylık Sabit Ücret: ' . Utils::formatMoney($recurringJob['monthly_amount'] ?? 0);
                                        } elseif ($pricingModel === 'TOTAL_CONTRACT') {
                                            $pricingInfo = 'Toplam Sözleşme: ' . Utils::formatMoney($recurringJob['contract_total_amount'] ?? 0);
                                        }
                                    }
                                    ?>
                                    <?php if ($pricingInfo): ?>
                                        <div class="mb-4 p-3 bg-blue-100 dark:bg-blue-900/40 rounded-lg">
                                            <div class="text-sm font-medium text-blue-900 dark:text-blue-300"><?= $pricingInfo ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($job['recurring_job_id'])): ?>
                                        <a href="<?= base_url("/recurring/{$job['recurring_job_id']}") ?>" 
                                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                            <i class="fas fa-external-link-alt mr-2"></i>
                                            Periyodik İş Detaylarına Git
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Normal İş Finansal Bilgiler -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                <div class="text-sm font-medium text-green-800 dark:text-green-400">Toplam Tutar</div>
                                <div class="text-2xl font-bold text-green-900 dark:text-green-300"><?= Utils::formatMoney($job['total_amount']) ?></div>
                            </div>
                            
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                <div class="text-sm font-medium text-blue-800 dark:text-blue-400">Ödenen</div>
                                <div class="text-2xl font-bold text-blue-900 dark:text-blue-300"><?= Utils::formatMoney($job['amount_paid'] ?? 0) ?></div>
                            </div>
                            
                            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                                <div class="text-sm font-medium text-orange-800 dark:text-orange-400">Kalan</div>
                                <div class="text-2xl font-bold text-orange-900 dark:text-orange-300"><?= Utils::formatMoney(max(0, $job['total_amount'] - ($job['amount_paid'] ?? 0))) ?></div>
                            </div>
                        </div>
                        
                        <!-- Payment Actions -->
                        <div class="flex space-x-3">
                            <a href="<?= base_url("/finance/from-job/{$job['id']}") ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-plus mr-2"></i>Ödeme Ekle
                            </a>
                            <a href="<?= base_url("/finance") ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-list mr-2"></i>Tüm Finanslar
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recurring Job Features (if applicable) -->
            <?php if ($isRecurring): ?>
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-sync-alt mr-2 text-purple-600"></i>Periyodik İş Özellikleri
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sıklık</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-white">
                                    <?= e($recurringJob['frequency']) ?> - Her <?= $recurringJob['interval'] ?> <?= $recurringJob['frequency'] === 'WEEKLY' ? 'hafta' : 'gün' ?>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Durum</label>
                                <div class="mt-1">
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold <?= $recurringJob['status'] === 'ACTIVE' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                        <?= $recurringJob['status'] === 'ACTIVE' ? 'Aktif' : 'Pasif' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex space-x-3">
                            <a href="<?= base_url("/recurring/{$recurringJob['id']}") ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700">
                                <i class="fas fa-cog mr-2"></i>Periyodik Ayarlar
                            </a>
                            <a href="<?= base_url("/recurring/{$recurringJob['id']}/edit") ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-edit mr-2"></i>Düzenle
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Sidebar -->
        <div class="space-y-8">
            <!-- Payment History -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-credit-card mr-2 text-blue-600"></i>Ödeme Geçmişi
                    </h3>
                    
                    <?php if (empty($payments)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-credit-card text-gray-400 text-2xl mb-2"></i>
                            <p class="text-sm text-gray-500">Henüz ödeme yok</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($payments as $payment): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white"><?= Utils::formatMoney($payment['amount']) ?></div>
                                        <div class="text-xs text-gray-500"><?= Utils::formatDateTime($payment['created_at'], 'd.m.Y H:i') ?></div>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?= htmlspecialchars($payment['description'] ?? 'Ödeme') ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                        <i class="fas fa-bolt mr-2 text-yellow-600"></i>Hızlı İşlemler
                    </h3>
                    
                    <div class="space-y-3">
                        <a href="<?= base_url("/customers/show/{$job['customer_id']}") ?>" class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-user mr-2"></i>Müşteri Detayı
                        </a>
                        
                        <a href="<?= base_url("/finance/from-job/{$job['id']}") ?>" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                            <i class="fas fa-plus mr-2"></i>Ödeme Ekle
                        </a>
                        
                        <?php if ($isRecurring): ?>
                            <a href="<?= base_url("/recurring/{$recurringJob['id']}") ?>" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700">
                                <i class="fas fa-sync-alt mr-2"></i>Periyodik Yönetim
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function jobManagement() {
    return {
        init() {
            // Initialize any specific functionality
        }
    };
}
</script>
