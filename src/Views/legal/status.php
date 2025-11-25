<?php
/**
 * System Status Page
 * ROUND 31: Created for legal pages hardening
 * Can be extended with monitoring integration later
 * ROUND 33: Added build tag for production fingerprinting
 */
$statusIcon = $status === 'healthy' || $status === 'operational' ? 'fa-check-circle' : 'fa-exclamation-triangle';
$statusColor = $status === 'healthy' || $status === 'operational' ? 'text-green-600' : 'text-yellow-600';
$buildTag = defined('KUREAPP_BUILD_TAG') ? KUREAPP_BUILD_TAG : 'UNKNOWN';
?>
<!-- BUILD: <?= htmlspecialchars($buildTag) ?> -->
<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="<?= base_url('/') ?>" class="text-gray-400 hover:text-primary-600"><i class="fas fa-home"></i></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-500">Sistem Durumu</span></li>
        </ol>
    </nav>

    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-server mr-3 text-primary-600"></i>
            Sistem Durumu
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Platform durumu ve sağlık bilgileri</p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Status Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center space-x-4 mb-6">
                <div class="flex-shrink-0">
                    <i class="fas <?= $statusIcon ?> <?= $statusColor ?> text-4xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($message) ?></h2>
                    <p class="text-gray-600 dark:text-gray-400">Durum: <span class="font-semibold"><?= htmlspecialchars($status) ?></span></p>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Sistem durumu bilgileri otomatik olarak güncellenmektedir. 
                    Detaylı sistem sağlık bilgileri için <a href="<?= base_url('/health') ?>" class="text-primary-600 hover:underline">/health</a> endpoint'ini kontrol edebilirsiniz.
                </p>
            </div>
        </div>
    </div>
</div>

