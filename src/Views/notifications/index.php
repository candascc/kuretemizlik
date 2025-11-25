<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-bell mr-3 text-primary-600"></i>
                Bildirimler
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Tüm bildirimler ve sistem uyarıları</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="markAllRead()" 
                    class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-300">
                <i class="fas fa-check-double mr-2"></i>Tümünü Okundu İşaretle
            </button>
            <a href="<?= base_url('/settings/profile') ?>" 
               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                <i class="fas fa-cog mr-2"></i>Ayarlar
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <?php if (!empty($stats)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                            <?= $stats['total'] ?? 0 ?>
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <i class="fas fa-bell text-blue-600 dark:text-blue-300 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Okunmamış</p>
                        <p class="text-3xl font-bold text-primary-600 dark:text-primary-400 mt-1">
                            <?= $stats['unread'] ?? 0 ?>
                        </p>
                    </div>
                    <div class="p-3 bg-primary-100 dark:bg-primary-900 rounded-lg">
                        <i class="fas fa-exclamation-circle text-primary-600 dark:text-primary-300 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Kritik</p>
                        <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-1">
                            <?= $stats['critical'] ?? 0 ?>
                        </p>
                    </div>
                    <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-300 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Operasyon</p>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">
                            <?= $stats['ops'] ?? 0 ?>
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <i class="fas fa-tasks text-green-600 dark:text-green-300 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Notification Preferences -->
    <?php if (!empty($prefs)): ?>
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 p-6 rounded-xl shadow-soft border border-blue-200 dark:border-blue-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-sliders-h mr-2 text-primary-600"></i>Bildirim Tercihleri
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex items-center">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Kritik Bildirimler:</span>
                    <span class="ml-2 px-3 py-1 rounded-full text-xs font-semibold <?= $prefs['critical'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                        <?= $prefs['critical'] ? 'Kapalı' : 'Açık' ?>
                    </span>
                </div>
                <div class="flex items-center">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Operasyon Bildirimleri:</span>
                    <span class="ml-2 px-3 py-1 rounded-full text-xs font-semibold <?= $prefs['ops'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                        <?= $prefs['ops'] ? 'Kapalı' : 'Açık' ?>
                    </span>
                </div>
                <div class="flex items-center">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Sistem Bildirimleri:</span>
                    <span class="ml-2 px-3 py-1 rounded-full text-xs font-semibold <?= $prefs['system'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                        <?= $prefs['system'] ? 'Kapalı' : 'Açık' ?>
                    </span>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">
                <i class="fas fa-info-circle mr-1"></i>Tercihlerinizi değiştirmek için <a href="<?= base_url('/settings/profile') ?>" class="text-primary-600 hover:underline">Ayarlar</a> sayfasına gidin.
            </p>
        </div>
    <?php endif; ?>

    <!-- Notifications List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <?php if (empty($notifications)): ?>
            <div class="p-12 text-center">
                <i class="fas fa-bell-slash text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Bildirim Yok</h3>
                <p class="text-gray-600 dark:text-gray-400">Tüm bildirimler güncel, yeni bildirim gelmedi.</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($notifications as $notification): 
                    $typeColors = [
                        'critical' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
                        'ops' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
                        'system' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800'
                    ];
                    $iconColors = [
                        'critical' => 'text-red-600 dark:text-red-400',
                        'ops' => 'text-green-600 dark:text-green-400',
                        'system' => 'text-blue-600 dark:text-blue-400'
                    ];
                    $type = $notification['type'] ?? 'system';
                    $isRead = !empty($notification['read']);
                ?>
                    <div class="p-6 <?= !$isRead ? $typeColors[$type] : '' ?> border-l-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas <?= $notification['icon'] ?? 'fa-bell' ?> text-xl <?= $iconColors[$type] ?>"></i>
                            </div>
                            <div class="flex-1 ml-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <?php if (!$isRead): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800">
                                                <span class="w-1.5 h-1.5 mr-1.5 bg-primary-600 rounded-full animate-pulse"></span>
                                                Yeni
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($notification['meta'])): ?>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            <?= e($notification['meta']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="mt-2 text-gray-900 dark:text-white <?= !$isRead ? 'font-semibold' : '' ?>">
                                    <?= htmlspecialchars($notification['text'] ?? '') ?>
                                </p>
                                <?php if (!empty($notification['href'])): ?>
                                    <div class="mt-3">
                                        <a href="<?= e($notification['href']) ?>" 
                                           class="inline-flex items-center text-sm text-primary-600 hover:text-primary-800 font-medium">
                                            Detayları Gör <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function markAllRead() {
    if (!confirm('Tüm bildirimleri okundu işaretlemek istediğinizden emin misiniz?')) {
        return;
    }
    
    fetch('<?= base_url('/api/notifications/mark-all-read') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= CSRF::get() ?>'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Bir hata oluştu: ' + (data.message || 'Bilinmeyen hata'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu');
    });
}
</script>

