<?php
/**
 * Sysadmin Crawl Progress View
 * 
 * Live progress monitoring page for crawl tests
 */

$testId = $testId ?? '';
$status = $status ?? null;
$currentUser = $currentUser ?? [];
$availableRoles = $availableRoles ?? [];
?>
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Crawl Test İlerlemesi</h1>
        <p class="text-gray-600 dark:text-gray-400">
            Test ID: <span class="font-mono text-sm"><?= htmlspecialchars($testId) ?></span>
        </p>
    </div>

    <div id="progress-container" class="space-y-6">
        <!-- Progress Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">İlerleme</h2>
                <div class="flex items-center gap-3">
                    <span id="status-badge" class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200">
                        Çalışıyor...
                    </span>
                    <button 
                        id="cancel-button"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors text-sm font-medium hidden"
                        onclick="cancelCrawl()"
                    >
                        <i class="fas fa-stop mr-2"></i>
                        Testi İptal Et
                    </button>
                </div>
            </div>
            
            <div class="mb-4">
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <span id="progress-text">0 / 0</span>
                    <span id="progress-percentage">0%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                    <div 
                        id="progress-bar" 
                        class="bg-primary-600 h-4 rounded-full transition-all duration-500 ease-out"
                        style="width: 0%"
                    ></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Başarılı</p>
                    <p id="success-count" class="text-2xl font-bold text-green-600 dark:text-green-400">0</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Hata</p>
                    <p id="error-count" class="text-2xl font-bold text-red-600 dark:text-red-400">0</p>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Geçen Süre</p>
                    <p id="elapsed-time" class="text-2xl font-bold text-blue-600 dark:text-blue-400">0s</p>
                </div>
            </div>
        </div>

        <!-- Current URL -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Şu An Taranan URL</h2>
            <p id="current-url" class="font-mono text-sm text-gray-600 dark:text-gray-400 break-all">
                Bekleniyor...
            </p>
        </div>

        <!-- Recent Items -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Son Taranan Sayfalar</h2>
            <div id="recent-items" class="space-y-2 max-h-96 overflow-y-auto">
                <p class="text-gray-500 dark:text-gray-400 text-sm">Henüz sayfa taranmadı...</p>
            </div>
        </div>
    </div>

    <div id="completed-container" class="hidden">
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-green-900 dark:text-green-200 mb-2">
                <i class="fas fa-check-circle mr-2"></i>
                Test Tamamlandı!
            </h2>
            <p class="text-green-800 dark:text-green-300 mb-4">
                Test başarıyla tamamlandı. Sonuçları görüntülemek için aşağıdaki butona tıklayın.
            </p>
            <a 
                id="view-results-link"
                href="<?= base_url('/sysadmin/crawl/results?testId=') ?><?= htmlspecialchars($testId) ?>"
                class="inline-flex items-center px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors"
            >
                <i class="fas fa-chart-line mr-2"></i>
                Sonuçları Görüntüle
            </a>
        </div>
    </div>

    <div id="error-container" class="hidden">
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-red-900 dark:text-red-200 mb-2">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Test Başarısız
            </h2>
            <p id="error-message" class="text-red-800 dark:text-red-300 mb-4">
            </p>
            <a 
                href="<?= base_url('/sysadmin/crawl') ?>"
                class="inline-flex items-center px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
            >
                <i class="fas fa-arrow-left mr-2"></i>
                Geri Dön
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const testId = '<?= htmlspecialchars($testId) ?>';
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const progressPercentage = document.getElementById('progress-percentage');
    const statusBadge = document.getElementById('status-badge');
    const successCount = document.getElementById('success-count');
    const errorCount = document.getElementById('error-count');
    const elapsedTime = document.getElementById('elapsed-time');
    const currentUrl = document.getElementById('current-url');
    const recentItems = document.getElementById('recent-items');
    const progressContainer = document.getElementById('progress-container');
    const completedContainer = document.getElementById('completed-container');
    const errorContainer = document.getElementById('error-container');
    const viewResultsLink = document.getElementById('view-results-link');
    const errorMessage = document.getElementById('error-message');

    let startTime = Date.now();
    let pollInterval = null;
    let retryCount = 0;
    const maxRetries = 5;
    const cancelButton = document.getElementById('cancel-button');

    function formatTime(seconds) {
        if (seconds < 60) {
            return seconds + 's';
        }
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return minutes + 'm ' + secs + 's';
    }

    function updateElapsedTime() {
        const elapsed = Math.floor((Date.now() - startTime) / 1000);
        elapsedTime.textContent = formatTime(elapsed);
    }

    function updateUI(statusData) {
        const progress = statusData.progress || {};
        const current = progress.current || 0;
        const total = progress.total || 0;
        const percentage = progress.percentage || 0;
        const successCountValue = progress.success_count || 0;
        const errorCountValue = progress.error_count || 0;
        const currentUrlValue = progress.current_url || '';
        const items = progress.items || [];

        // Update progress bar
        progressBar.style.width = percentage + '%';
        progressText.textContent = current + ' / ' + total;
        progressPercentage.textContent = percentage.toFixed(1) + '%';

        // Update counts
        successCount.textContent = successCountValue;
        errorCount.textContent = errorCountValue;

        // Update current URL
        currentUrl.textContent = currentUrlValue || 'Bekleniyor...';

        // Update recent items
        if (items && items.length > 0) {
            recentItems.innerHTML = items.slice(-10).reverse().map(item => {
                const statusClass = item.error_flag ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
                const statusIcon = item.error_flag ? 'fa-times-circle' : 'fa-check-circle';
                return `
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                        <span class="font-mono text-xs text-gray-700 dark:text-gray-300 flex-1 truncate">${item.url || ''}</span>
                        <div class="flex items-center gap-2 ml-2">
                            <i class="fas ${statusIcon} ${statusClass}"></i>
                            <span class="text-xs ${statusClass}">${item.status || 0}</span>
                        </div>
                    </div>
                `;
            }).join('');
        } else if (current > 0) {
            // If we have progress but no items yet, show a message
            recentItems.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Sayfalar taranıyor...</p>';
        } else {
            // No progress yet
            recentItems.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Henüz sayfa taranmadı...</p>';
        }
    }

    async function pollStatus() {
        try {
            const response = await fetch('<?= base_url('/sysadmin/crawl/status?testId=') ?>' + testId, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Status check failed');
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Status check failed');
            }

            retryCount = 0; // Reset retry count on success

            const status = data.status || {};
            const statusType = status.status || 'unknown';

            // Update status badge
            if (statusType === 'running') {
                statusBadge.textContent = 'Çalışıyor...';
                statusBadge.className = 'px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200';
                // Show cancel button when running
                if (cancelButton) {
                    cancelButton.classList.remove('hidden');
                }
            } else if (statusType === 'completed') {
                statusBadge.textContent = 'Tamamlandı';
                statusBadge.className = 'px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200';
                
                // Hide cancel button
                if (cancelButton) {
                    cancelButton.classList.add('hidden');
                }
                
                // Stop polling
                if (pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = null;
                }

                // Show completed message
                progressContainer.classList.add('hidden');
                completedContainer.classList.remove('hidden');

                // Update final UI
                updateUI(status);
            } else if (statusType === 'failed') {
                statusBadge.textContent = 'Başarısız';
                statusBadge.className = 'px-3 py-1 rounded-full text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200';
                
                // Hide cancel button
                if (cancelButton) {
                    cancelButton.classList.add('hidden');
                }
                
                // Stop polling
                if (pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = null;
                }

                // Show error message
                progressContainer.classList.add('hidden');
                errorContainer.classList.remove('hidden');
                const errorMsg = status.error || status.progress?.error || 'Test başarısız oldu.';
                errorMessage.textContent = errorMsg;
            } else if (statusType === 'unknown') {
                // Unknown status - might be starting up, keep polling but show warning
                statusBadge.textContent = 'Başlatılıyor...';
                statusBadge.className = 'px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-200';
                
                // Only show error if lock age is > 30 seconds and still unknown
                const elapsed = status.elapsed || 0;
                if (elapsed > 30) {
                    statusBadge.textContent = 'Başarısız';
                    statusBadge.className = 'px-3 py-1 rounded-full text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200';
                    
                    // Stop polling after 30 seconds of unknown
                    if (pollInterval) {
                        clearInterval(pollInterval);
                        pollInterval = null;
                    }
                    
                    progressContainer.classList.add('hidden');
                    errorContainer.classList.remove('hidden');
                    errorMessage.textContent = 'Test başlatılamadı veya durumu belirlenemedi.';
                }
            }

            // Update UI with progress
            updateUI(status);

        } catch (error) {
            console.error('Poll error:', error);
            retryCount++;

            if (retryCount >= maxRetries) {
                // Stop polling after max retries
                if (pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = null;
                }
                alert('Test durumu kontrol edilemedi. Lütfen sayfayı yenileyin.');
            }
        }
    }

    // Start polling every 2 seconds
    pollInterval = setInterval(pollStatus, 2000);

    // Update elapsed time every second
    setInterval(updateElapsedTime, 1000);

    // Initial poll
    pollStatus();
    updateElapsedTime();
});

// Cancel crawl function
async function cancelCrawl() {
    const testId = '<?= htmlspecialchars($testId) ?>';
    
    if (!confirm('Testi iptal etmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
        return;
    }
    
    const cancelButton = document.getElementById('cancel-button');
    if (cancelButton) {
        cancelButton.disabled = true;
        cancelButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> İptal Ediliyor...';
    }
    
    try {
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                         document.querySelector('input[name="csrf_token"]')?.value ||
                         '';
        
        const formData = new FormData();
        formData.append('testId', testId);
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }
        
        const response = await fetch('<?= base_url('/sysadmin/crawl/cancel') ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Test başarıyla iptal edildi.');
            window.location.href = '<?= base_url('/sysadmin/crawl') ?>';
        } else {
            alert('Hata: ' + (data.error || 'Test iptal edilemedi.'));
            if (cancelButton) {
                cancelButton.disabled = false;
                cancelButton.innerHTML = '<i class="fas fa-stop mr-2"></i> Testi İptal Et';
            }
        }
    } catch (error) {
        console.error('Cancel error:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        if (cancelButton) {
            cancelButton.disabled = false;
            cancelButton.innerHTML = '<i class="fas fa-stop mr-2"></i> Testi İptal Et';
        }
    }
}
</script>

