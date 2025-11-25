<?php
/**
 * Enhanced Empty State Component - UI-POLISH-001
 * 
 * Beautiful, engaging empty states with illustrations and clear CTAs
 * 
 * Usage:
 * <?php echo renderEmptyState([
 *     'type' => 'jobs',
 *     'title' => 'Henüz iş yok',
 *     'message' => 'İlk işinizi oluşturarak başlayın',
 *     'action_url' => '/jobs/wizard',
 *     'action_text' => 'Yeni İş Oluştur'
 * ]); ?>
 */

function renderEmptyState($config) {
    $type = $config['type'] ?? 'default';
    $title = $config['title'] ?? 'Henüz içerik yok';
    $message = $config['message'] ?? 'Burası şu an boş görünüyor';
    $actionUrl = $config['action_url'] ?? null;
    $actionText = $config['action_text'] ?? 'Ekle';
    $illustration = $config['illustration'] ?? null;
    
    // Icon mapping
    $icons = [
        'jobs' => 'fa-tasks',
        'customers' => 'fa-users',
        'payments' => 'fa-money-bill-wave',
        'recurring' => 'fa-repeat',
        'reports' => 'fa-chart-bar',
        'notifications' => 'fa-bell',
        'search' => 'fa-search',
        'filter' => 'fa-filter',
        'calendar' => 'fa-calendar',
        'default' => 'fa-inbox'
    ];
    
    $icon = $icons[$type] ?? $icons['default'];
    
    // Color schemes
    $colors = [
        'jobs' => 'from-blue-400 to-indigo-500',
        'customers' => 'from-green-400 to-emerald-500',
        'payments' => 'from-yellow-400 to-amber-500',
        'recurring' => 'from-purple-400 to-pink-500',
        'reports' => 'from-red-400 to-rose-500',
        'default' => 'from-gray-400 to-slate-500'
    ];
    
    $gradient = $colors[$type] ?? $colors['default'];
    
    ob_start();
    ?>
    
    <div class="empty-state text-center py-16 px-6">
        
        <!-- Illustration / Icon -->
        <?php if ($illustration): ?>
            <img src="<?= e($illustration) ?>" 
                 alt="Empty state illustration"
                 class="w-64 h-64 mx-auto mb-8 opacity-75">
        <?php else: ?>
            <div class="w-32 h-32 mx-auto mb-8 bg-gradient-to-br <?= $gradient ?> rounded-full flex items-center justify-center shadow-xl opacity-90">
                <i class="fas <?= $icon ?> text-6xl text-white"></i>
            </div>
        <?php endif; ?>
        
        <!-- Title -->
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
            <?= e($title) ?>
        </h3>
        
        <!-- Message -->
        <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">
            <?= e($message) ?>
        </p>
        
        <!-- Actions -->
        <?php if ($actionUrl): ?>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="<?= e($actionUrl) ?>" 
               class="inline-flex items-center px-8 py-4 bg-gradient-to-r <?= $gradient ?> text-white font-bold text-lg rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                <i class="fas fa-plus mr-3"></i>
                <?= e($actionText) ?>
            </a>
            
            <?php if ($config['secondary_action_url'] ?? null): ?>
            <a href="<?= e($config['secondary_action_url']) ?>" 
               class="inline-flex items-center px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-lg border-2 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                <i class="fas fa-book mr-2"></i>
                <?= htmlspecialchars($config['secondary_action_text'] ?? 'Daha Fazla Bilgi') ?>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Tips / Quick Start -->
        <?php if ($config['tips'] ?? null): ?>
        <div class="mt-12 max-w-2xl mx-auto">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-6 text-left">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                    Hızlı Başlangıç
                </h4>
                <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <?php foreach ($config['tips'] as $tip): ?>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-600 mr-2 mt-1"></i>
                            <span><?= e($tip) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    
    <?php
    return ob_get_clean();
}

/**
 * Pre-defined empty states for common scenarios
 */
function emptyStateJobs() {
    return renderEmptyState([
        'type' => 'jobs',
        'title' => 'Henüz iş oluşturulmamış',
        'message' => 'İlk işinizi oluşturarak başlayın. Wizard size adım adım rehberlik edecek.',
        'action_url' => base_url('/jobs/wizard'),
        'action_text' => 'İlk İşi Oluştur',
        'secondary_action_url' => base_url('/jobs/new'),
        'secondary_action_text' => 'Klasik Form Kullan',
        'tips' => [
            'Wizard kullanarak 70% daha hızlı iş oluşturabilirsiniz',
            'Müşteri araması ile hızlı seçim yapabilirsiniz',
            'Periyodik işler için tekrar ayarlayabilirsiniz'
        ]
    ]);
}

function emptyStateCustomers() {
    return renderEmptyState([
        'type' => 'customers',
        'title' => 'Müşteri listesi boş',
        'message' => 'İlk müşterinizi ekleyerek CRM sisteminizi kullanmaya başlayın.',
        'action_url' => base_url('/customers/new'),
        'action_text' => 'İlk Müşteriyi Ekle',
        'tips' => [
            'Müşteri bilgileri tek yerden yönetilebilir',
            'Birden fazla adres eklenebilir',
            'İş geçmişi otomatik takip edilir'
        ]
    ]);
}

function emptyStatePayments() {
    return renderEmptyState([
        'type' => 'payments',
        'title' => 'Ödeme kaydı yok',
        'message' => 'Ücretler oluşturulduktan sonra ödemeler burada görünecek.',
        'action_url' => base_url('/fees'),
        'action_text' => 'Ücretleri Görüntüle'
    ]);
}

function emptyStateSearchResults() {
    return renderEmptyState([
        'type' => 'search',
        'title' => 'Sonuç bulunamadı',
        'message' => 'Arama kriterlerinizi değiştirmeyi veya filtreleri temizlemeyi deneyin.',
        'action_url' => '#',
        'action_text' => 'Filtreleri Temizle',
        'tips' => [
            'Daha genel terimler kullanın',
            'Yazım hatası olup olmadığını kontrol edin',
            'Farklı modüllerde arama yapın'
        ]
    ]);
}

