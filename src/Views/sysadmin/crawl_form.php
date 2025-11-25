<?php
/**
 * Sysadmin Crawl Form View
 * 
 * Form page to start crawl tests
 */

$currentUser = $currentUser ?? [];
$availableRoles = $availableRoles ?? ['SUPERADMIN', 'ADMIN', 'OPERATOR', 'SITE_MANAGER', 'FINANCE', 'SUPPORT'];
$defaultRole = $defaultRole ?? 'SUPERADMIN';
?>
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Crawl Test Yönetimi</h1>
        <p class="text-gray-600 dark:text-gray-400">
            Web uygulamanızın sayfalarını otomatik olarak tarayın ve hataları tespit edin.
        </p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Yeni Test Başlat</h2>
        
        <form id="crawl-form" method="POST" action="<?= base_url('/sysadmin/crawl/start') ?>" class="space-y-4">
            <?php if (class_exists('CSRF')): ?>
                <?= CSRF::field() ?>
            <?php endif; ?>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Test Edilecek Rol
                </label>
                <select 
                    name="role" 
                    id="role" 
                    required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                    <?php foreach ($availableRoles as $role): ?>
                        <option value="<?= htmlspecialchars($role) ?>" <?= $role === $defaultRole ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Seçilen rolün yetkileriyle sayfalar taranacak.
                </p>
            </div>

            <div class="flex items-center gap-4">
                <button 
                    type="submit" 
                    id="start-button"
                    class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <i class="fas fa-play mr-2"></i>
                    Test Başlat
                </button>
                <a 
                    href="<?= base_url('/sysadmin/crawl') ?>" 
                    class="px-6 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition-colors"
                >
                    <i class="fas fa-refresh mr-2"></i>
                    Yenile
                </a>
            </div>
        </form>
    </div>

    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-200 mb-2">
            <i class="fas fa-info-circle mr-2"></i>
            Bilgi
        </h3>
        <ul class="text-blue-800 dark:text-blue-300 space-y-1 text-sm">
            <li>• Test arka planda çalışır, sayfanız bloke olmaz.</li>
            <li>• Test ilerlemesini canlı olarak takip edebilirsiniz.</li>
            <li>• Test tamamlandığında detaylı sonuçlar gösterilir.</li>
            <li>• Aynı anda sadece bir test çalışabilir.</li>
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('crawl-form');
    const startButton = document.getElementById('start-button');
    let isSubmitting = false;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (isSubmitting) {
            return;
        }

        isSubmitting = true;
        startButton.disabled = true;
        startButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Başlatılıyor...';

        try {
            const formData = new FormData(form);
            
            // Get CSRF token from meta tag or form field
            let csrfToken = null;
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                csrfToken = csrfMeta.getAttribute('content');
            } else {
                const csrfField = form.querySelector('input[name="csrf_token"]');
                if (csrfField) {
                    csrfToken = csrfField.value;
                }
            }
            
            // Add CSRF token to headers if available
            const headers = {
                'X-Requested-With': 'XMLHttpRequest'
            };
            if (csrfToken) {
                headers['X-CSRF-Token'] = csrfToken;
            }
            
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: headers
            });

            // Get response text first to check for errors
            const responseText = await response.text();
            
            // Check if response is valid JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text (first 500 chars):', responseText.substring(0, 500));
                alert('Sunucudan geçersiz yanıt alındı. Lütfen tekrar deneyin.');
                startButton.disabled = false;
                startButton.innerHTML = '<i class="fas fa-play mr-2"></i> Test Başlat';
                isSubmitting = false;
                return;
            }

            if (data.success) {
                // Redirect to progress page
                window.location.href = '<?= base_url('/sysadmin/crawl/progress?testId=') ?>' + data.testId;
            } else {
                alert('Hata: ' + (data.error || 'Test başlatılamadı.'));
                startButton.disabled = false;
                startButton.innerHTML = '<i class="fas fa-play mr-2"></i> Test Başlat';
                isSubmitting = false;
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            startButton.disabled = false;
            startButton.innerHTML = '<i class="fas fa-play mr-2"></i> Test Başlat';
            isSubmitting = false;
        }
    });
});
</script>

