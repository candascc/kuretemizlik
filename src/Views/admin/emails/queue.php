<?php
/**
 * Email Queue View
 * This view is rendered via View::renderWithLayout, so it only contains the content
 */
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            <i class="fas fa-list mr-2"></i>Email Queue
        </h1>
        <a href="<?= base_url('/admin/emails/logs') ?>" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            <i class="fas fa-envelope mr-2"></i>Email Logları
        </a>
    </div>

    <?php include __DIR__ . '/../../partials/flash.php'; ?>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Toplam</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['total'] ?? 0 ?></div>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg shadow p-4">
            <div class="text-sm text-yellow-600 dark:text-yellow-400">Bekliyor</div>
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"><?= $stats['pending'] ?? 0 ?></div>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow p-4">
            <div class="text-sm text-blue-600 dark:text-blue-400">Gönderiliyor</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= $stats['sending'] ?? 0 ?></div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg shadow p-4">
            <div class="text-sm text-green-600 dark:text-green-400">Gönderildi</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400"><?= $stats['sent'] ?? 0 ?></div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg shadow p-4">
            <div class="text-sm text-red-600 dark:text-red-400">Başarısız</div>
            <div class="text-2xl font-bold text-red-600 dark:text-red-400"><?= $stats['failed'] ?? 0 ?></div>
        </div>
    </div>

    <!-- Pending Emails -->
    <?php if (!empty($pending)): ?>
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-clock mr-2"></i>Bekleyen Emailler (<?= count($pending) ?>)
            </h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Oluşturma</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Alıcı</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Konu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Durum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Deneme</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($pending as $email): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= date('d.m.Y H:i', strtotime($email['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= e($email['to_email']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <?= e($email['subject']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'sending' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                    ];
                                    $statusLabels = [
                                        'pending' => 'Bekliyor',
                                        'sending' => 'Gönderiliyor',
                                        'failed' => 'Başarısız'
                                    ];
                                    $color = $statusColors[$email['status']] ?? 'bg-gray-100 text-gray-800';
                                    $label = $statusLabels[$email['status']] ?? $email['status'];
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full <?= $color ?>">
                                        <?= $label ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php
                                    $retryCount = isset($email['retry_count']) ? (int)$email['retry_count'] : 0;
                                    $maxRetries = isset($email['max_retries']) ? (int)$email['max_retries'] : 0;
                                    ?>
                                    <?= $retryCount ?> / <?= $maxRetries ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if ($email['status'] === 'failed'): ?>
                                        <form method="POST" action="<?= base_url('/admin/emails/retry') ?>" class="inline">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="id" value="<?= $email['id'] ?>">
                                            <button type="submit" class="text-blue-600 dark:text-blue-400 hover:underline">
                                                <i class="fas fa-redo mr-1"></i>Yeniden Dene
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($email['error_message']): ?>
                                        <div class="text-xs text-red-600 dark:text-red-400 mt-1" title="<?= e($email['error_message']) ?>">
                                            <?= htmlspecialchars(mb_substr($email['error_message'], 0, 30)) ?>...
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recently Sent -->
    <?php if (!empty($recentlySent)): ?>
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-check-circle mr-2"></i>Son Gönderilenler (<?= count($recentlySent) ?>)
            </h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gönderim</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Alıcı</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Konu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tip</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($recentlySent as $email): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= date('d.m.Y H:i', strtotime($email['sent_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= e($email['to_email']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <?= e($email['subject']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                        <?= e($email['type']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Info Box -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <h3 class="font-medium text-blue-800 dark:text-blue-200 mb-2">
            <i class="fas fa-info-circle mr-2"></i>Queue İşleme
        </h3>
        <p class="text-sm text-blue-700 dark:text-blue-300">
            Queue'daki emailler otomatik olarak işlenir. Manuel işlemek için:
        </p>
        <code class="block mt-2 p-2 bg-blue-100 dark:bg-blue-900 rounded text-sm">
            php app/src/Commands/ProcessEmailQueue.php
        </code>
        <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
            Veya cron job olarak her 5 dakikada bir çalıştırın: <code>*/5 * * * * cd /path/to/app && php src/Commands/ProcessEmailQueue.php</code>
        </p>
    </div>
    </div>
</div>

