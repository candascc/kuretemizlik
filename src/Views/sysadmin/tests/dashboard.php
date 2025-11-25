<?php
/**
 * Test Management Dashboard View
 */

$stats = $stats ?? [];
$recentRuns = $recentRuns ?? [];
$testSuites = $testSuites ?? [];
?>
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Test Yönetimi</h1>
        <p class="text-gray-600 dark:text-gray-400">
            Testleri çalıştırın, sonuçları görüntüleyin ve test geçmişini takip edin.
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Toplam Test Dosyası</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= $stats['total_files'] ?? 0 ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Toplam Test</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= $stats['total_tests'] ?? 0 ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Başarı Oranı</h3>
            <p class="text-3xl font-bold <?= ($stats['success_rate'] ?? 0) >= 80 ? 'text-green-600' : 'text-yellow-600' ?>">
                <?= number_format($stats['success_rate'] ?? 0, 1) ?>%
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Son Çalıştırma</h3>
            <p class="text-sm text-gray-900 dark:text-white">
                <?= $stats['last_run'] ? date('d.m.Y H:i', strtotime($stats['last_run'])) : 'Henüz çalıştırılmadı' ?>
            </p>
        </div>
    </div>

    <!-- Test Execution Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Test Çalıştır</h2>
        
        <form id="test-run-form" method="POST" action="<?= base_url('/sysadmin/tests/run') ?>" class="space-y-4">
            <?php if (class_exists('CSRF')): ?>
                <?= CSRF::field() ?>
            <?php endif; ?>
            
            <div>
                <label for="suite" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Test Suite
                </label>
                <select 
                    name="suite" 
                    id="suite" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                    <?php foreach ($testSuites as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>">
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center gap-4">
                <button 
                    type="submit" 
                    id="run-tests-btn"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Testleri Çalıştır
                </button>
                <span id="test-status" class="text-sm text-gray-600 dark:text-gray-400"></span>
            </div>
        </form>
    </div>

    <!-- Recent Test Runs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Son Test Çalıştırmaları</h2>
        
        <?php if (empty($recentRuns)): ?>
            <p class="text-gray-600 dark:text-gray-400">Henüz test çalıştırılmadı.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarih</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Test Sayısı</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Başarı Oranı</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($recentRuns as $run): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= $run['timestamp'] ? date('d.m.Y H:i', strtotime($run['timestamp'])) : '-' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= $run['total_tests'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 rounded <?= ($run['success_rate'] ?? 0) >= 80 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                        <?= number_format($run['success_rate'] ?? 0, 1) ?>%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a 
                                        href="<?= base_url('/sysadmin/tests/results/' . basename($run['file'], '.json')) ?>" 
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                    >
                                        Detaylar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('test-run-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('run-tests-btn');
    const status = document.getElementById('test-status');
    
    btn.disabled = true;
    status.textContent = 'Testler çalıştırılıyor...';
    status.className = 'text-sm text-blue-600 dark:text-blue-400';
    
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.data && data.data.status === 'running') {
            status.textContent = 'Testler başlatıldı. Sonuçlar hazır olduğunda görüntülenecek.';
            status.className = 'text-sm text-green-600 dark:text-green-400';
            // Poll for results
            pollTestStatus(data.data.run_id);
        } else {
            status.textContent = 'Hata: ' + (data.error || data.message || 'Bilinmeyen hata');
            status.className = 'text-sm text-red-600 dark:text-red-400';
            btn.disabled = false;
        }
    })
    .catch(error => {
        status.textContent = 'Hata: ' + (error.error || error.message || 'Bağlantı hatası');
        status.className = 'text-sm text-red-600 dark:text-red-400';
        btn.disabled = false;
    });
});

function pollTestStatus(runId, maxAttempts = 150) {
    const status = document.getElementById('test-status');
    const btn = document.getElementById('run-tests-btn');
    let attempts = 0;
    
    const interval = setInterval(() => {
        attempts++;
        
        // Timeout after max attempts (5 minutes)
        if (attempts >= maxAttempts) {
            clearInterval(interval);
            status.textContent = 'Test çalıştırma zaman aşımına uğradı. Lütfen manuel olarak kontrol edin.';
            status.className = 'text-sm text-yellow-600 dark:text-yellow-400';
            btn.disabled = false;
            return;
        }
        
        fetch('<?= base_url('/sysadmin/tests/status/') ?>' + encodeURIComponent(runId), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data) {
                    if (data.data.status === 'completed') {
                        clearInterval(interval);
                        status.textContent = 'Testler tamamlandı! Yönlendiriliyorsunuz...';
                        status.className = 'text-sm text-green-600 dark:text-green-400';
                        setTimeout(() => {
                            window.location.href = '<?= base_url('/sysadmin/tests/results/') ?>' + encodeURIComponent(runId);
                        }, 1000);
                    } else if (data.data.status === 'failed' || data.data.status === 'error') {
                        clearInterval(interval);
                        status.textContent = 'Test çalıştırma başarısız: ' + (data.data.message || 'Bilinmeyen hata');
                        status.className = 'text-sm text-red-600 dark:text-red-400';
                        btn.disabled = false;
                    } else {
                        // Still running, update status
                        status.textContent = `Testler çalışıyor... (${attempts * 2} saniye)`;
                    }
                } else {
                    clearInterval(interval);
                    status.textContent = 'Durum kontrolü başarısız: ' + (data.error || 'Bilinmeyen hata');
                    status.className = 'text-sm text-red-600 dark:text-red-400';
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Status check error:', error);
                // Don't stop polling on network errors, but log them
                if (attempts % 10 === 0) {
                    status.textContent = `Durum kontrol ediliyor... (${attempts * 2} saniye)`;
                }
            });
    }, 2000); // Poll every 2 seconds
}
</script>

