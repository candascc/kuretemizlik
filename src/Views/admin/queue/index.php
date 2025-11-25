<?php
/**
 * Queue Management Dashboard
 */
?>

<!DOCTYPE html>
<html lang="tr" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Queue Management - Temizlik İş Takip' ?></title>
    
    <!-- Tailwind CSS (Local build - ROUND 23) -->
    <link rel="stylesheet" href="<?= Utils::asset('css/tailwind.css') ?>?v=<?= file_exists(__DIR__ . '/../../../assets/css/tailwind.css') ? filemtime(__DIR__ . '/../../../assets/css/tailwind.css') : time() ?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        .dark {
            color-scheme: dark;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900">

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Kuyruk Yönetimi</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Arka plan iş işlemlerini izleyin ve yönetin</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="pushTestJob()" 
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-plus mr-2"></i>
                        Test İşi Ekle
                    </button>
                    <a href="<?= base_url('/admin/queue/failed') ?>" 
                       class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Başarısız İşler
                    </a>
                    <a href="<?= base_url('/admin/queue/export') ?>" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-download mr-2"></i>
                        Veri Dışa Aktar
                    </a>
                </div>
            </div>
        </div>

        <!-- Health Status -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <i class="fas fa-heartbeat text-green-600 dark:text-green-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Kuyruk Durumu</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" id="queueStatus">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                <?= $healthStatus['status'] === 'healthy' ? 'Sağlıklı' : 'Sorunlu' ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <i class="fas fa-tasks text-blue-600 dark:text-blue-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam İşler</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" id="totalJobs">
                            <?= $healthStatus['total_jobs'] ?? 0 ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Bekleyen İşler</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" id="pendingJobs">
                            <?= array_sum(array_column($stats, 'pending')) ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Başarısız İşler</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" id="failedJobs">
                            <?= $healthStatus['total_failed'] ?? 0 ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Queue Statistics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Queue Overview -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Kuyruk Özeti</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($stats as $queue => $stat): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white"><?= ucfirst($queue) ?> Kuyruğu</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <?= $stat['pending'] ?? 0 ?> bekliyor, 
                                    <?= $stat['processing'] ?? 0 ?> işleniyor, 
                                    <?= $stat['failed'] ?? 0 ?> başarısız
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    <?= $stat['total'] ?? 0 ?>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">toplam</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Performans Metrikleri</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($performanceMetrics as $queue => $metrics): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white"><?= ucfirst($queue) ?> Kuyruğu</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Başarı Oranı: <?= $metrics['success_rate'] ?? 0 ?>%
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    <?= $metrics['success_rate'] ?? 0 ?>%
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">başarı oranı</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts and Recommendations -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Alerts -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Uyarılar</h3>
                </div>
                <div class="p-6">
                    <?php if (empty($alerts)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
                        <p class="text-gray-600 dark:text-gray-400">Şu anda uyarı yok</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($alerts as $alert): ?>
                        <div class="flex items-start p-3 bg-<?= $alert['severity'] === 'warning' ? 'yellow' : 'red' ?>-50 dark:bg-<?= $alert['severity'] === 'warning' ? 'yellow' : 'red' ?>-900 rounded-lg">
                            <i class="fas fa-<?= $alert['severity'] === 'warning' ? 'exclamation-triangle' : 'times-circle' ?> text-<?= $alert['severity'] === 'warning' ? 'yellow' : 'red' ?>-500 mt-1 mr-3"></i>
                            <div>
                                <p class="font-medium text-<?= $alert['severity'] === 'warning' ? 'yellow' : 'red' ?>-800 dark:text-<?= $alert['severity'] === 'warning' ? 'yellow' : 'red' ?>-200">
                                    <?= e($alert['message']) ?>
                                </p>
                                <p class="text-sm text-<?= $alert['severity'] === 'warning' ? 'yellow' : 'red' ?>-600 dark:text-<?= $alert['severity'] === 'warning' ? 'yellow' : 'red' ?>-300">
                                    Kuyruk: <?= e($alert['queue']) ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recommendations -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Öneriler</h3>
                </div>
                <div class="p-6">
                    <?php if (empty($recommendations)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-thumbs-up text-green-500 text-4xl mb-4"></i>
                        <p class="text-gray-600 dark:text-gray-400">Şu anda öneri yok</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($recommendations as $recommendation): ?>
                        <div class="flex items-start p-3 bg-blue-50 dark:bg-blue-900 rounded-lg">
                            <i class="fas fa-lightbulb text-blue-500 mt-1 mr-3"></i>
                            <div>
                                <p class="font-medium text-blue-800 dark:text-blue-200">
                                    <?= e($recommendation['message']) ?>
                                </p>
                                <p class="text-sm text-blue-600 dark:text-blue-300">
                                    Öncelik: <?= ucfirst($recommendation['priority']) ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Failed Jobs -->
        <?php if (!empty($failedJobs)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Son Başarısız İşler</h3>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İş ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kuyruk</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hata</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Başarısız Tarihi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach (array_slice($failedJobs, 0, 5) as $job): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-white">
                                    <?= htmlspecialchars(substr($job['id'], 0, 20)) ?>...
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= e($job['queue']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <?= htmlspecialchars(substr($job['error'] ?? 'Bilinmeyen hata', 0, 50)) ?>...
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= date('Y-m-d H:i:s', $job['failed_at']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form action="<?= base_url('/admin/queue/retry') ?>" method="POST" class="inline">
                                        <input type="hidden" name="job_id" value="<?= e($job['id']) ?>">
                                        <button type="submit" class="text-green-600 hover:text-green-900 mr-3">Tekrar Dene</button>
                                    </form>
                                    <form action="<?= base_url('/admin/queue/delete') ?>" method="POST" class="inline" onsubmit="return confirm('Emin misiniz?')">
                                        <input type="hidden" name="job_id" value="<?= e($job['id']) ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">Sil</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-center">
                    <a href="<?= base_url('/admin/queue/failed') ?>" class="text-blue-600 hover:text-blue-800">
                        Tüm başarısız işleri görüntüle →
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function pushTestJob() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('/admin/queue/push-test') ?>';
    
    const jobType = document.createElement('select');
    jobType.name = 'job_type';
    jobType.className = 'px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white';
    
    const emailOption = document.createElement('option');
    emailOption.value = 'SendEmailJob';
    emailOption.textContent = 'E-posta Gönder İşi';
    jobType.appendChild(emailOption);
    
    const reportOption = document.createElement('option');
    reportOption.value = 'GenerateReportJob';
    reportOption.textContent = 'Rapor Oluştur İşi';
    jobType.appendChild(reportOption);
    
    const queue = document.createElement('select');
    queue.name = 'queue';
    queue.className = 'px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white';
    
    const defaultOption = document.createElement('option');
    defaultOption.value = 'default';
    defaultOption.textContent = 'Varsayılan Kuyruk';
    queue.appendChild(defaultOption);
    
    const highOption = document.createElement('option');
    highOption.value = 'high';
    highOption.textContent = 'Yüksek Öncelik';
    queue.appendChild(highOption);
    
    const lowOption = document.createElement('option');
    lowOption.value = 'low';
    lowOption.textContent = 'Düşük Öncelik';
    queue.appendChild(lowOption);
    
    const submit = document.createElement('button');
    submit.type = 'submit';
    submit.className = 'px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md';
    submit.textContent = 'İşi Ekle';
    
    form.appendChild(jobType);
    form.appendChild(queue);
    form.appendChild(submit);
    
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
    modal.innerHTML = `
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Test İşi Ekle</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">İş Tipi</label>
                        ${jobType.outerHTML}
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kuyruk</label>
                        ${queue.outerHTML}
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-md">İptal</button>
                    ${submit.outerHTML}
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    form.style.display = 'none';
    modal.querySelector('.flex').appendChild(form);
}

// Auto-refresh every 30 seconds
setInterval(function() {
    fetch('<?= base_url('/admin/queue/stats') ?>')
        .then(response => response.json())
        .then(data => {
            // Update stats
            document.getElementById('totalJobs').textContent = Object.values(data).reduce((sum, stat) => sum + (stat.total || 0), 0);
            document.getElementById('pendingJobs').textContent = Object.values(data).reduce((sum, stat) => sum + (stat.pending || 0), 0);
            document.getElementById('failedJobs').textContent = Object.values(data).reduce((sum, stat) => sum + (stat.failed || 0), 0);
        });
}, 30000);
</script>

<!-- Footer -->
<footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12">
    <div class="max-w-7xl mx-auto px-4 py-6">
        <p class="text-center text-gray-500 dark:text-gray-400 text-sm">
            &copy; 2025 Temizlik İş Takip - Enterprise Edition
        </p>
    </div>
</footer>
</body>
</html>
