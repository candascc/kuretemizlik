<?php
/**
 * Test Results View
 */

$results = $results ?? [];
$status = $status ?? [];
$logContent = $logContent ?? '';
$runId = $runId ?? '';
?>
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Test Sonuçları</h1>
        <p class="text-gray-600 dark:text-gray-400">
            Run ID: <?= htmlspecialchars($runId) ?>
        </p>
    </div>

    <?php if (isset($status['status']) && $status['status'] === 'completed' && isset($status['data'])): ?>
        <?php $data = $status['data']; ?>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Toplam Test</h3>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= $data['total_tests'] ?? 0 ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Başarılı</h3>
                <p class="text-3xl font-bold text-green-600"><?= $data['passed'] ?? 0 ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Başarısız</h3>
                <p class="text-3xl font-bold text-red-600"><?= ($data['failed'] ?? 0) + ($data['errors'] ?? 0) ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Başarı Oranı</h3>
                <p class="text-3xl font-bold <?= ($data['success_rate'] ?? 0) >= 80 ? 'text-green-600' : 'text-yellow-600' ?>">
                    <?= number_format($data['success_rate'] ?? 0, 1) ?>%
                </p>
            </div>
        </div>

        <!-- Detailed Results -->
        <?php if (!empty($results)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Detaylı Sonuçlar</h2>
                <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded overflow-x-auto text-sm"><?= htmlspecialchars(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            </div>
        <?php endif; ?>

    <?php elseif (isset($status['status']) && $status['status'] === 'running'): ?>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 mb-6">
            <p class="text-yellow-800 dark:text-yellow-200">
                ⏳ Testler hala çalışıyor... Lütfen bekleyin.
            </p>
        </div>
    <?php else: ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6 mb-6">
            <p class="text-red-800 dark:text-red-200">
                ❌ Test sonuçları bulunamadı veya hata oluştu.
            </p>
        </div>
    <?php endif; ?>

    <!-- Log Content -->
    <?php if (!empty($logContent)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Test Log</h2>
            <pre class="bg-gray-100 dark:bg-gray-900 p-4 rounded overflow-x-auto text-sm max-h-96 overflow-y-auto"><?= htmlspecialchars($logContent) ?></pre>
        </div>
    <?php endif; ?>

    <div class="mt-6">
        <a 
            href="<?= base_url('/sysadmin/tests') ?>" 
            class="inline-block px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700"
        >
            ← Geri Dön
        </a>
    </div>
</div>










