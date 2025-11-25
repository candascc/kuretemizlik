<div class="space-y-8">
        <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
            <h1 class="fluid-h1 text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-home mr-3 text-primary-600"></i>
                Ana Sayfa
            </h1>
            <p class="fluid-body text-gray-600 dark:text-gray-400 mt-1 sm:mt-2">Genel bakış ve önemli bilgiler</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?php if (!empty($recurringStats['pending_occurrences_count']) && $recurringStats['pending_occurrences_count'] > 0): ?>
                        <button onclick="generatePendingJobs()" 
                                id="generatePendingBtn"
                                class="inline-flex items-center px-3 py-2 sm:px-4 text-sm sm:text-base bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors duration-200 whitespace-nowrap">
                            <i class="fas fa-magic mr-1.5 sm:mr-2"></i>
                            <span class="hidden sm:inline">Eksik İşleri Oluştur</span>
                            <span class="sm:hidden">Eksik (<?= $recurringStats['pending_occurrences_count'] ?>)</span>
                            <span class="hidden sm:inline">(<?= $recurringStats['pending_occurrences_count'] ?>)</span>
                        </button>
                    <?php endif; ?>
                    <button onclick="location.reload()" 
                    class="inline-flex items-center px-3 py-2 sm:px-4 text-sm sm:text-base bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors duration-200 whitespace-nowrap">
                        <i class="fas fa-sync-alt mr-1.5 sm:mr-2"></i>
                        <span class="hidden sm:inline">Yenile</span>
                        <span class="sm:hidden">Yenile</span>
                    </button>
            </div>
        </div>
        
        <script>
        async function generatePendingJobs() {
            const btn = document.getElementById('generatePendingBtn');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Oluşturuluyor...';
            
            try {
                // Get CSRF token from meta tag or hidden input
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                                 document.querySelector('input[name="csrf_token"]')?.value || 
                                 '';
                
                const response = await fetch('<?= base_url('/api/recurring/check-now') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        csrf_token: csrfToken
                    })
                });
                
                if (!response.ok && response.status === 403) {
                    throw new Error('Güvenlik hatası. Lütfen sayfayı yenileyin ve tekrar deneyin.');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    const generated = data.summary.generated || 0;
                    const message = generated > 0 
                        ? `Harika! ✅ ${generated} adet eksik iş oluşturuldu.` 
                        : 'Tüm işler güncel! 🎯 Eksik iş bulunamadı.';
                    
                    Utils.showNotification(message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    const errorMsg = data.error || data.message || 'Bilinmeyen hata';
                    Utils.showNotification('Üzülmeyin! 😊 ' + errorMsg, 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                const errorMsg = error.message || 'Bilinmeyen bir hata oluştu';
                Utils.showNotification('Bir hata oluştu: ' + errorMsg, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
        
        // Helper notification function with dark mode support
        const Utils = {
            showNotification: function(message, type = 'info') {
                const colors = {
                    success: 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800',
                    error: 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800',
                    warning: 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800',
                    info: 'bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-800'
                };
                
                const icons = {
                    success: 'fa-check-circle',
                    error: 'fa-exclamation-circle',
                    warning: 'fa-exclamation-triangle',
                    info: 'fa-info-circle'
                };
                
                // Remove existing notifications of same type to avoid stacking
                const existing = document.querySelectorAll('.flash-notification');
                existing.forEach(n => {
                    n.style.transition = 'opacity 0.3s';
                    n.style.opacity = '0';
                    setTimeout(() => n.remove(), 300);
                });
                
                const notification = document.createElement('div');
                notification.className = `flash-notification fixed top-20 right-4 rounded-lg border shadow-lg px-6 py-4 ${colors[type] || colors.info} z-50 max-w-md`;
                notification.style.animation = 'slideInRight 0.3s ease-out';
                notification.innerHTML = `
                    <div class="flex items-center gap-3">
                        <i class="fas ${icons[type] || icons.info} text-lg"></i>
                        <span class="flex-1">${message}</span>
                        <button onclick="this.closest('.flash-notification').remove()" class="ml-2 opacity-70 hover:opacity-100">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                
                // Add animation styles if not already present
                if (!document.getElementById('notification-styles')) {
                    const style = document.createElement('style');
                    style.id = 'notification-styles';
                    style.textContent = `
                        @keyframes slideInRight {
                            from {
                                transform: translateX(100%);
                                opacity: 0;
                            }
                            to {
                                transform: translateX(0);
                                opacity: 1;
                            }
                        }
                    `;
                    document.head.appendChild(style);
                }
                
                document.body.appendChild(notification);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    notification.style.transition = 'opacity 0.5s, transform 0.3s';
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => notification.remove(), 500);
                }, 5000);
            }
        };
        </script>


        <!-- Yaklaşan Periyodik İş Oluşturma İşlemleri -->
        <?php if (!empty($recurringStats['pending_occurrences_count']) && $recurringStats['pending_occurrences_count'] > 0): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 mb-8">
            <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 border-b border-gray-200 dark:border-gray-700 rounded-t-xl">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-magic mr-2 text-purple-600"></i>
                            Yaklaşan Periyodik İş Oluşturma İşlemleri
                        </h2>
                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">Sistem tarafından oluşturulacak <?= $recurringStats['pending_occurrences_count'] ?> adet periyodik iş var</p>
                    </div>
                    <button onclick="generatePendingJobs()" 
                            id="generatePendingBtn"
                            class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors duration-200 whitespace-nowrap text-sm">
                        <i class="fas fa-magic mr-2"></i>
                        Şimdi Oluştur
                    </button>
                </div>
            </div>
            <div class="p-4 sm:p-6">
                <div class="bg-purple-50 dark:bg-purple-900/10 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-purple-600 dark:text-purple-400 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Otomatik periyodik iş oluşturma sistemi, planlanmış tarihlerde işleri otomatik olarak oluşturur. 
                                <?= $recurringStats['pending_occurrences_count'] ?> adet iş bekliyor.
                            </p>
                            <div class="mt-3 flex items-center gap-2 flex-wrap">
                                <a href="<?= base_url('/recurring') ?>" class="section-header-link">
                                    Periyodik İşleri Yönet
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards - Mobile-first responsive grid: 1 col (mobile) / 2 col (tablet) / 4 col (desktop) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-8">
            <!-- Periyodik İşler (Card component) -->
            <?php ob_start(); ?>
                <div class="flex flex-col h-full">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm font-semibold text-gray-600 dark:text-gray-400 tracking-wide mb-2">Aktif Periyodik İşler</p>
                            <p class="text-2xl sm:text-3xl md:text-4xl font-bold bg-gradient-to-r from-purple-600 to-purple-700 bg-clip-text text-transparent leading-tight"><?= $recurringStats['active_count'] ?? 0 ?></p>
                            <p class="text-[10px] sm:text-xs font-medium text-purple-600 dark:text-purple-400 mt-1.5 flex items-center gap-1"><i class="fas fa-calendar-alt"></i><span><?= $recurringStats['this_month_jobs'] ?? 0 ?> iş bu ay</span></p>
                        </div>
                        <div class="p-2 sm:p-3 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl sm:rounded-2xl shadow-lg shadow-purple-500/20 animate-pulse flex-shrink-0 ml-2"><i class="fas fa-sync-alt text-white text-base sm:text-xl md:text-2xl"></i></div>
                    </div>
                    <div class="mt-auto pt-3 flex items-center justify-between gap-2">
                        <a href="<?= base_url('/recurring') ?>" class="section-header-link">
                            Yönet
                        </a>
                        <?php if (!empty($recurringStats['pending_occurrences_count']) && $recurringStats['pending_occurrences_count'] > 0): ?>
                            <span class="text-[10px] sm:text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full whitespace-nowrap"><?= $recurringStats['pending_occurrences_count'] ?> bekleyen</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php $body = ob_get_clean(); $title=''; $subtitle=''; $actions=''; include __DIR__ . '/partials/ui/card.php'; ?>
            
            <!-- Bugünkü İşler (Card component) -->
            <?php ob_start(); ?>
                <div class="flex flex-col h-full">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm font-semibold text-gray-600 dark:text-gray-400 tracking-wide mb-2">Bugünkü İşler</p>
                            <p class="text-2xl sm:text-3xl md:text-4xl font-bold bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent leading-tight"><?= $stats['today_jobs'] ?? 0 ?></p>
                            <p class="text-[10px] sm:text-xs font-medium text-blue-600 dark:text-blue-400 mt-1.5 flex items-center gap-1"><i class="fas fa-clock"></i><span><?= date('d.m.Y') ?></span></p>
                        </div>
                        <div class="p-2 sm:p-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl sm:rounded-2xl shadow-lg shadow-blue-500/20 flex-shrink-0 ml-2"><i class="fas fa-calendar-day text-white text-base sm:text-xl md:text-2xl"></i></div>
                    </div>
                    <div class="mt-auto pt-3 text-right">
                        <a href="<?= base_url('/jobs?filter=today') ?>" class="section-header-link">
                            Tümünü Gör
                        </a>
                    </div>
                </div>
            <?php $body = ob_get_clean(); $title=''; $subtitle=''; $actions=''; include __DIR__ . '/partials/ui/card.php'; ?>
            
            <!-- Aktif Müşteriler (Card component) -->
            <?php ob_start(); ?>
                <div class="flex flex-col h-full">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm font-semibold text-gray-600 dark:text-gray-400 tracking-wide mb-2">Aktif Müşteriler</p>
                            <p class="text-2xl sm:text-3xl md:text-4xl font-bold bg-gradient-to-r from-green-600 to-green-700 bg-clip-text text-transparent leading-tight"><?= $stats['active_customers'] ?? 0 ?></p>
                            <p class="text-[10px] sm:text-xs font-medium text-green-600 dark:text-green-400 mt-1.5 flex items-center gap-1"><i class="fas fa-chart-line"></i><span>Bu ay</span></p>
                        </div>
                        <div class="p-2 sm:p-3 bg-gradient-to-br from-green-500 to-green-600 rounded-xl sm:rounded-2xl shadow-lg shadow-green-500/20 flex-shrink-0 ml-2"><i class="fas fa-users text-white text-base sm:text-xl md:text-2xl"></i></div>
                    </div>
                    <div class="mt-auto pt-3 text-right">
                        <a href="<?= base_url('/customers') ?>" class="section-header-link">
                            Tümünü Gör
                        </a>
                    </div>
                </div>
            <?php $body = ob_get_clean(); $title=''; $subtitle=''; $actions=''; include __DIR__ . '/partials/ui/card.php'; ?>
            
            <!-- Bu Hafta Gelir (Card component) -->
            <?php ob_start(); ?>
                <div class="flex flex-col h-full">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm font-semibold text-gray-600 dark:text-gray-400 tracking-wide mb-2">Bu Hafta Gelir</p>
                            <p class="text-2xl sm:text-3xl md:text-4xl font-bold bg-gradient-to-r from-yellow-600 to-yellow-700 bg-clip-text text-transparent leading-tight">₺<?= number_format($stats['week_income'] ?? 0, 0, ',', '.') ?></p>
                            <p class="text-[10px] sm:text-xs font-medium text-yellow-600 dark:text-yellow-400 mt-1.5 flex items-center gap-1"><i class="fas fa-coins"></i><span>Haftalık</span></p>
                        </div>
                        <div class="p-2 sm:p-3 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl sm:rounded-2xl shadow-lg shadow-yellow-500/20 flex-shrink-0 ml-2"><i class="fas fa-chart-line text-white text-base sm:text-xl md:text-2xl"></i></div>
                    </div>
                    <div class="mt-auto pt-3 text-right">
                        <a href="<?= base_url('/finance') ?>" class="section-header-link">
                            Detaylar
                        </a>
                    </div>
                </div>
            <?php $body = ob_get_clean(); $title=''; $subtitle=''; $actions=''; include __DIR__ . '/partials/ui/card.php'; ?>
        </div>

        <!-- Grafik ve İstatistikler - Mobile-first: 1 col (mobile) / 1 col (tablet) / 3 col (desktop) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8 mb-8">
            <!-- Haftalık Gelir Grafiği -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-chart-line mr-2 text-yellow-600"></i>
                        Haftalık Gelir Trendi
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Son 7 günün gelir analizi</p>
                </div>
                <div class="p-6">
                    <?php if (!empty($weeklyIncomeTrend) && count($weeklyIncomeTrend) > 0 && array_sum(array_column($weeklyIncomeTrend, 'income')) > 0): ?>
                        <?php 
                        $maxIncome = max(array_column($weeklyIncomeTrend, 'income'));
                        // Eğer tüm değerler 0 ise, görsel bir şey göstermek için max'ı 1 yap
                        if ($maxIncome == 0) {
                            $maxIncome = 1;
                        }
                        $maxHeight = 200; // Max height in pixels
                        ?>
                        <div class="h-64 flex flex-col justify-between">
                            <div class="grid grid-cols-7 gap-2 h-full items-end">
                                <?php foreach ($weeklyIncomeTrend as $day): ?>
                                    <div class="flex flex-col items-center justify-end" style="height: 100%;">
                                        <div class="w-full flex-1 flex items-end justify-center mb-2">
                                            <?php 
                                            $heightPercent = $maxIncome > 0 ? ($day['income'] / $maxIncome) * 100 : 0;
                                            $actualHeight = max(($heightPercent / 100) * $maxHeight, $day['income'] > 0 ? 15 : 0);
                                            ?>
                                            <div class="w-full bg-gradient-to-t from-yellow-500 to-orange-500 rounded-t-lg transition-all duration-300 hover:from-yellow-600 hover:to-orange-600 cursor-pointer" 
                                                 style="height: <?= $actualHeight ?>px;"
                                                 title="<?= $day['day'] ?>: ₺<?= number_format($day['income'], 0, ',', '.') ?>">
                                            </div>
                                        </div>
                                        <div class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= $day['day'] ?></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-500">₺<?= number_format($day['income'], 0, ',', '.') ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                    </div>
                    <?php else: ?>
                        <div class="h-64 flex items-center justify-center">
                            <div class="text-center">
                                <i class="fas fa-chart-line text-gray-400 dark:text-gray-600 text-4xl mb-4"></i>
                                <p class="text-gray-500 dark:text-gray-400">Son 7 günde gelir verisi bulunmuyor</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">₺<?= number_format($stats['week_income'] ?? 0, 0, ',', '.') ?> bu hafta</p>
                    </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Yaklaşan Randevular -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-calendar-alt mr-2 text-purple-600"></i>
                        Yaklaşan Randevular
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Önümüzdeki 3 gün</p>
                </div>
                <div class="p-6">
                    <?php if (!empty($upcomingAppointments) && count($upcomingAppointments) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($upcomingAppointments as $apt): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900 dark:text-white"><?= e($apt['title'] ?? 'Başlıksız') ?></h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400"><?= e($apt['customer_name'] ?? 'Bilinmeyen') ?></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            <i class="fas fa-calendar mr-1"></i>
                                            <?php 
                                            $apptDate = $apt['appointment_date'] ?? (!empty($apt['start_at']) ? date('Y-m-d', strtotime($apt['start_at'])) : '');
                                            $apptTime = $apt['start_time'] ?? (!empty($apt['start_at']) ? date('H:i', strtotime($apt['start_at'])) : '');
                                            ?>
                                            <?= $apptDate ? date('d.m.Y', strtotime($apptDate)) : '--' ?> 
                                            <?= $apptTime ? '• ' . $apptTime : '' ?>
                                        </p>
                                    </div>
                                    <a href="<?= base_url('/appointments/' . ($apt['id'] ?? '')) ?>" class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 ml-4">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="<?= base_url('/appointments') ?>" class="section-header-link">
                                Tümünü Gör
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-calendar-alt text-gray-400 dark:text-gray-600 text-4xl mb-4"></i>
                            <p class="text-gray-500 dark:text-gray-400">Yaklaşan randevu bulunmuyor</p>
                            <a href="<?= base_url('/appointments/new') ?>" class="inline-flex items-center mt-4 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Yeni Randevu
                            </a>
                    </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
            
        <!-- Bugünkü İşler Listesi -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 lg:gap-8 mb-8">
            <!-- Bugünkü İşler -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-calendar-day mr-2 text-blue-600"></i>
                        Bugünkü İşler
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= date('d.m.Y') ?> tarihli işleriniz</p>
                </div>
                <div class="p-6">
                    <?php if (!empty($todayJobs) && count($todayJobs) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($todayJobs as $job): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900 dark:text-white"><?= e($job['note'] ?? 'İş Adı Yok') ?></h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400"><?= e($job['customer_name'] ?? 'Müşteri Bilgisi Yok') ?></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-clock mr-1"></i>
                                            <?= !empty($job['start_at']) ? date('H:i', strtotime($job['start_at'])) : '--:--' ?>
                                            <?php if (!empty($job['service_name'])): ?>
                                                • <?= e($job['service_name']) ?>
                                            <?php endif; ?>
                                        </p>
                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            <?= ($job['status'] ?? '') === 'DONE' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                               (($job['status'] ?? '') === 'SCHEDULED' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                               'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') ?>">
                                            <?= ($job['status'] ?? '') === 'DONE' ? 'Tamamlandı' : (($job['status'] ?? '') === 'SCHEDULED' ? 'Planlandı' : 'İptal') ?>
                                        </span>
                                        <a href="<?= base_url("/jobs/show/" . ($job['id'] ?? '')) ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                                            <i class="fas fa-eye"></i>
                                        </a>
                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="<?= base_url('/jobs?filter=today') ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm font-medium">
                                Tüm Bugünkü İşleri Gör <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-calendar-day text-gray-400 dark:text-gray-600 text-4xl mb-4"></i>
                            <p class="text-gray-500 dark:text-gray-400">Bugün için iş bulunmuyor</p>
                            <a href="<?= base_url('/jobs/new') ?>" class="inline-flex items-center mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>Yeni İş Ekle
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Son Aktiviteler ve Bildirimler -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 lg:gap-8 mb-8">
            <!-- Son Aktiviteler -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-history mr-2 text-indigo-600"></i>
                        Son Aktiviteler
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Sistemdeki son değişiklikler</p>
                </div>
                <div class="p-6">
                    <?php if (!empty($recentActivities) && count($recentActivities) > 0): ?>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            <?php foreach (array_slice($recentActivities, 0, 15) as $activity): 
                                $bgColor = 'bg-gray-500';
                                switch($activity['color']) {
                                    case 'green': $bgColor = 'bg-green-500'; break;
                                    case 'blue': $bgColor = 'bg-blue-500'; break;
                                    case 'yellow': $bgColor = 'bg-yellow-500'; break;
                                    case 'purple': $bgColor = 'bg-purple-500'; break;
                                    case 'indigo': $bgColor = 'bg-indigo-500'; break;
                                }
                            ?>
                                <div class="flex items-start space-x-3">
                                    <?php 
                                    $iconBgColor = 'bg-gray-100 dark:bg-gray-700';
                                    $iconTextColor = 'text-gray-500';
                                    switch($activity['color']) {
                                        case 'green': 
                                            $iconBgColor = 'bg-green-100 dark:bg-green-900/30';
                                            $iconTextColor = 'text-green-600 dark:text-green-400';
                                            break;
                                        case 'blue': 
                                            $iconBgColor = 'bg-blue-100 dark:bg-blue-900/30';
                                            $iconTextColor = 'text-blue-600 dark:text-blue-400';
                                            break;
                                        case 'yellow': 
                                            $iconBgColor = 'bg-yellow-100 dark:bg-yellow-900/30';
                                            $iconTextColor = 'text-yellow-600 dark:text-yellow-400';
                                            break;
                                        case 'purple': 
                                            $iconBgColor = 'bg-purple-100 dark:bg-purple-900/30';
                                            $iconTextColor = 'text-purple-600 dark:text-purple-400';
                                            break;
                                        case 'indigo': 
                                            $iconBgColor = 'bg-indigo-100 dark:bg-indigo-900/30';
                                            $iconTextColor = 'text-indigo-600 dark:text-indigo-400';
                                            break;
                                    }
                                    ?>
                                    <div class="flex-shrink-0 w-10 h-10 <?= $iconBgColor ?> rounded-lg flex items-center justify-center mt-0.5">
                                        <i class="<?= $activity['icon'] ?? 'fas fa-circle' ?> <?= $iconTextColor ?> text-sm"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-900 dark:text-white"><?= e($activity['message']) ?></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            <?= e($activity['customer']) ?> • <?= e($activity['time_text']) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-history text-gray-400 dark:text-gray-600 text-4xl mb-4"></i>
                            <p class="text-gray-500 dark:text-gray-400">Son 24 saatte aktivite bulunmuyor</p>
                    </div>
                    <?php endif; ?>
                    </div>
                </div>

            <!-- Sistem Durumu -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-server mr-2 text-green-600"></i>
                        Sistem Durumu
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Sistem performansı ve durumu</p>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Veritabanı</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span class="text-sm text-green-600 dark:text-green-400">Çevrimiçi</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">API Durumu</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span class="text-sm text-green-600 dark:text-green-400">Aktif</span>
                            </div>
                    </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Cache</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                <span class="text-sm text-yellow-600 dark:text-yellow-400">Yükleniyor</span>
                    </div>
                </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Son Yedekleme</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400"><?= date('d.m.Y H:i') ?></span>
                    </div>
                    </div>
                </div>
        </div>
    </div>

</div>
