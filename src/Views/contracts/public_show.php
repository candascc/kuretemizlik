<?php
/** @var array $contract */
/** @var array $job */
/** @var array $customer */
/** @var array $statusInfo */
?>

<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            <?= __('contracts.public.title') ?>
        </h1>
        <?php
            $jobDate = Utils::formatDateTime($job['start_at'], 'd.m.Y');
            $jobAddress = htmlspecialchars($job['address_line'] ?? 'Belirtilmemiş');
        ?>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
            <?= __('contracts.public.subtitle', ['date' => $jobDate, 'address' => $jobAddress]) ?>
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            <?= __('contracts.public.intro') ?>
        </p>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($flash) && is_array($flash)): ?>
        <?php if (!empty($flash['success'])): ?>
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-sm font-medium text-green-800 dark:text-green-300">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= e($flash['success']) ?>
                </p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($flash['error'])): ?>
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-sm font-medium text-red-800 dark:text-red-300">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= e($flash['error']) ?>
                </p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($flash['info'])): ?>
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p class="text-sm font-medium text-blue-800 dark:text-blue-300">
                    <i class="fas fa-info-circle mr-2"></i>
                    <?= e($flash['info']) ?>
                </p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Status Badge -->
    <div class="mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold <?= $statusInfo['class'] ?> dark:<?= str_replace('text-', 'text-', $statusInfo['class']) ?>">
                    <?= e($statusInfo['label']) ?>
                </span>
                <?php if ($statusInfo['message']): ?>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400"><?= e($statusInfo['message']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Portal Login Link (if not logged in) -->
            <?php if (!isset($_SESSION['portal_customer_id'])): ?>
                <a href="<?= base_url('/portal/login') ?>" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-150 shadow-sm">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    <?= __('contracts.public.portal_login') ?>
                </a>
            <?php else: ?>
                <a href="<?= base_url('/portal/dashboard') ?>" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition-colors duration-150 shadow-sm">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    <?= __('contracts.public.go_to_dashboard') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Job Summary -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                İş Özeti
            </h2>
        </div>
        <div class="px-6 py-4">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tarih</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        <?= Utils::formatDateTime($job['start_at'], 'd.m.Y H:i') ?> - <?= Utils::formatDateTime($job['end_at'], 'H:i') ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Adres</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        <?= htmlspecialchars($job['address_line'] ?? 'Belirtilmemiş') ?>
                        <?php if (!empty($job['address_city'])): ?>
                            <br><span class="text-gray-500 dark:text-gray-400"><?= e($job['address_city']) ?></span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ücret</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                        <?= Utils::formatMoney($job['total_amount'] ?? 0) ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Müşteri</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        <?= htmlspecialchars($customer['name'] ?? 'Bilinmeyen Müşteri') ?>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Contract Text -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-file-contract mr-2 text-blue-600"></i>
                <?= __('contracts.public.contract_title') ?>
            </h2>
        </div>
        <div class="px-6 py-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                <?= __('contracts.public.contract_note') ?>
            </p>
            <div class="prose max-w-none dark:prose-invert">
                <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                    <?= nl2br(htmlspecialchars($contract['contract_text'] ?? 'Sözleşme metni bulunamadı.')) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Approval Form -->
    <?php if ($statusInfo['can_approve']): ?>
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-check-circle mr-2 text-green-600"></i>
                    Sözleşme Onayı
                </h2>
            </div>
            <div class="px-6 py-6">
                <form method="POST" action="<?= base_url("/contract/{$contract['id']}/approve") ?>" class="space-y-6">
                    <?= CSRF::field() ?>
                    
                    <!-- Accept Terms Checkbox -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="accept_terms" name="accept_terms" type="checkbox" value="1" required
                                   class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="accept_terms" class="font-medium text-gray-700 dark:text-gray-300">
                                <?= __('contracts.public.checkbox_label') ?>
                            </label>
                            <p class="text-gray-500 dark:text-gray-400 mt-1">
                                <?= __('contracts.public.intro') ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- OTP Code Input -->
                    <div>
                        <label for="otp_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <?= __('contracts.public.otp_label') ?>
                        </label>
                        <input type="text" 
                               id="otp_code" 
                               name="otp_code" 
                               required 
                               maxlength="6" 
                               pattern="\d{6}"
                               placeholder="<?= __('contracts.public.otp_placeholder') ?>"
                               class="block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-lg text-center tracking-widest"
                               autocomplete="one-time-code">
                        <?php
                            // Mask phone number for display (e.g., 5** *** ** 34)
                            $phone = $customer['phone'] ?? '';
                            $maskedPhone = $phone ? substr($phone, 0, 1) . str_repeat('*', max(0, strlen($phone) - 3)) . substr($phone, -2) : '';
                        ?>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            <?= __('contracts.public.otp_help', ['phone' => $maskedPhone]) ?>
                        </p>
                        <?php if (!isset($_SESSION['portal_customer_id'])): ?>
                            <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                                <i class="fas fa-info-circle mr-1"></i>
                                <?= __('contracts.public.otp_help_portal') ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-check mr-2"></i>
                            <?= __('contracts.public.submit') ?>
                        </button>
                    </div>
                    
                    <!-- KVKK Note -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <?= __('contracts.public.kvkk_note') ?>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    <?php elseif ($contract['status'] === 'APPROVED'): ?>
        <!-- Already Approved Message -->
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-600 text-2xl mr-4"></i>
                <div>
                    <h3 class="text-lg font-semibold text-green-900 dark:text-green-300 mb-1">
                        <?= __('contracts.public.messages.success') ?>
                    </h3>
                    <?php
                        $approvedDate = Utils::formatDateTime($contract['approved_at'], 'd.m.Y H:i');
                        $jobDate = Utils::formatDateTime($job['start_at'], 'd.m.Y');
                    ?>
                    <p class="text-sm text-green-800 dark:text-green-400 mb-2">
                        <?= __('contracts.public.messages.success_detail', ['date' => $jobDate]) ?>
                    </p>
                    <p class="text-xs text-green-700 dark:text-green-500">
                        <?= __('contracts.public.messages.success_detail_contact') ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Auto-format OTP input (6 digits only)
document.getElementById('otp_code')?.addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
});
</script>

