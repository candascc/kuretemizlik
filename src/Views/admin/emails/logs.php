<?php
/**
 * Email Logs View
 * This view is rendered via View::renderWithLayout, so it only contains the content
 */
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-envelope mr-2"></i>Email Logları
        </h1>
        <div class="flex gap-2">
            <a href="<?= base_url('/admin/emails/queue') ?>" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                <i class="fas fa-list mr-2"></i>Email Queue
            </a>
            <button onclick="refreshEmailLogs()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-sync-alt mr-2"></i>Yenile
            </button>
        </div>
    </div>

    <?php include __DIR__ . '/../../partials/flash.php'; ?>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Toplam</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['total'] ?? 0 ?></div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg shadow p-4">
            <div class="text-sm text-green-600 dark:text-green-400">Gönderildi</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400"><?= $stats['sent'] ?? 0 ?></div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg shadow p-4">
            <div class="text-sm text-red-600 dark:text-red-400">Başarısız</div>
            <div class="text-2xl font-bold text-red-600 dark:text-red-400"><?= $stats['failed'] ?? 0 ?></div>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow p-4">
            <div class="text-sm text-blue-600 dark:text-blue-400">Aktif Günler</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= $stats['days_active'] ?? 0 ?></div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="mb-4 flex gap-2">
        <select name="type" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option value="">Tüm Tipler</option>
            <option value="job_created" <?= $filters['type'] === 'job_created' ? 'selected' : '' ?>>İş Oluşturma</option>
            <option value="status_change" <?= $filters['type'] === 'status_change' ? 'selected' : '' ?>>Durum Değişikliği</option>
            <option value="payment_reminder" <?= $filters['type'] === 'payment_reminder' ? 'selected' : '' ?>>Ödeme Hatırlatıcısı</option>
            <option value="monthly_report" <?= $filters['type'] === 'monthly_report' ? 'selected' : '' ?>>Aylık Rapor</option>
        </select>
        <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option value="">Tüm Durumlar</option>
            <option value="sent" <?= $filters['status'] === 'sent' ? 'selected' : '' ?>>Gönderildi</option>
            <option value="failed" <?= $filters['status'] === 'failed' ? 'selected' : '' ?>>Başarısız</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-filter mr-2"></i>Filtrele
        </button>
        <a href="<?= base_url('/admin/emails/logs') ?>" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
            <i class="fas fa-times mr-2"></i>Temizle
        </a>
    </form>

    <!-- Email Logs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarih</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Alıcı</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Konu</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tip</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Müşteri</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-2 block"></i>
                            Henüz email logu yok
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?= date('d.m.Y H:i', strtotime($log['sent_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?= e($log['to_email']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <?= e($log['subject']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                    <?= e($log['type']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($log['status'] === 'sent'): ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                        <i class="fas fa-check mr-1"></i>Gönderildi
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                        <i class="fas fa-times mr-1"></i>Başarısız
                                    </span>
                                    <?php if ($log['error_message']): ?>
                                        <div class="text-xs text-red-600 dark:text-red-400 mt-1" title="<?= e($log['error_message']) ?>">
                                            <?= htmlspecialchars(mb_substr($log['error_message'], 0, 50)) ?>...
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if ($log['customer_name']): ?>
                                    <a href="<?= base_url('/customers/' . $log['customer_id']) ?>" class="text-blue-600 dark:text-blue-400 hover:underline">
                                        <?= e($log['customer_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                                <?php if (!empty($log['job_id'])): ?>
                                    <a href="<?= base_url('/jobs/' . $log['job_id']) ?>" class="block text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        İş #<?= $log['job_id'] ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (!empty($pagination) && $pagination['total'] > 1): ?>
        <div class="flex items-center justify-between mt-6">
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Toplam <?= $pagination['total_items'] ?> kayıt, Sayfa <?= $pagination['current'] ?> / <?= $pagination['total'] ?>
            </div>
            <div class="flex space-x-2">
                <?php if ($pagination['has_prev']): ?>
                    <a href="?page=<?= $pagination['prev_page'] ?>&type=<?= $filters['type'] ?>&status=<?= $filters['status'] ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-chevron-left"></i> Önceki
                    </a>
                <?php endif; ?>
                <?php if ($pagination['has_next']): ?>
                    <a href="?page=<?= $pagination['next_page'] ?>&type=<?= $filters['type'] ?>&status=<?= $filters['status'] ?>" class="btn btn-sm btn-secondary">
                        Sonraki <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Clear Old Logs -->
    <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
        <h3 class="font-medium text-yellow-800 dark:text-yellow-200 mb-2">
            <i class="fas fa-trash-alt mr-2"></i>Eski Logları Temizle
        </h3>
        <form method="POST" action="<?= base_url('/admin/emails/clear-logs') ?>" class="flex gap-2">
            <?= CSRF::field() ?>
            <select name="days" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                <option value="30">30 günden eski</option>
                <option value="60">60 günden eski</option>
                <option value="90" selected>90 günden eski</option>
                <option value="180">180 günden eski</option>
                <option value="365">1 yıldan eski</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700" onclick="return confirm('Eski loglar silinecek. Devam etmek istiyor musunuz?')">
                <i class="fas fa-trash mr-2"></i>Temizle
            </button>
        </form>
    </div>
    </div>
</div>

<script>
function refreshEmailLogs() {
    window.location.reload();
}
</script>

