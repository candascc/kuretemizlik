<div class="container mx-auto px-4 py-6">
    <!-- Breadcrumb -->
    <nav class="text-sm mb-4">
        <ol class="list-none p-0 inline-flex">
            <li class="flex items-center">
                <a href="<?= base_url('/') ?>" class="text-blue-600 hover:text-blue-800">Anasayfa</a>
                <i class="fas fa-chevron-right mx-2 text-gray-400 text-xs"></i>
            </li>
            <li class="flex items-center">
                <a href="<?= base_url('/admin/queue') ?>" class="text-blue-600 hover:text-blue-800">Kuyruk</a>
                <i class="fas fa-chevron-right mx-2 text-gray-400 text-xs"></i>
            </li>
            <li class="flex items-center text-gray-500">
                Başarısız İşler
            </li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                <i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>Başarısız İşler
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                Başarısız kuyruk işlerini gözden geçirin ve yönetin
            </p>
        </div>
        <div class="flex space-x-3">
            <form method="POST" action="<?= base_url('/admin/queue/clear-failed') ?>" 
                  onsubmit="return confirm('Tüm başarısız işleri temizlemek istediğinize emin misiniz?');">
                <button type="submit" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-trash mr-2"></i>Tümünü Temizle
                </button>
            </form>
            <a href="<?= base_url('/admin/queue') ?>" 
               class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Geri
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (has_flash('success')): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <div><?= htmlspecialchars(get_flash('success') ?? '') ?></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Toplam Başarısız</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                        <?= count($failedJobs ?? []) ?>
                    </p>
                </div>
                <i class="fas fa-times-circle text-red-500 text-4xl opacity-50"></i>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Tekrar Denenebilir</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                        <?php 
                        $retryable = 0;
                        if (isset($failedJobs)) {
                            foreach ($failedJobs as $job) {
                                if (($job['attempts'] ?? 0) < ($job['max_tries'] ?? 3)) {
                                    $retryable++;
                                }
                            }
                        }
                        echo $retryable;
                        ?>
                    </p>
                </div>
                <i class="fas fa-redo text-yellow-500 text-4xl opacity-50"></i>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Maks. Deneme</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                        <?php 
                        $maxRetries = 0;
                        if (isset($failedJobs)) {
                            foreach ($failedJobs as $job) {
                                if (($job['attempts'] ?? 0) >= ($job['max_tries'] ?? 3)) {
                                    $maxRetries++;
                                }
                            }
                        }
                        echo $maxRetries;
                        ?>
                    </p>
                </div>
                <i class="fas fa-ban text-purple-500 text-4xl opacity-50"></i>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Bugün</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                        <?php 
                        $today = 0;
                        $todayStart = strtotime('today');
                        if (isset($failedJobs)) {
                            foreach ($failedJobs as $job) {
                                if (($job['failed_at'] ?? 0) >= $todayStart) {
                                    $today++;
                                }
                            }
                        }
                        echo $today;
                        ?>
                    </p>
                </div>
                <i class="fas fa-calendar-day text-blue-500 text-4xl opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Failed Jobs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
            <i class="fas fa-list mr-2"></i>Başarısız İşler Listesi
        </h2>

        <?php if (isset($failedJobs) && !empty($failedJobs)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kuyruk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">İş</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Hata</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Denemeler</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Başarısız Tarihi</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($failedJobs as $job): ?>
                            <?php 
                            $payload = is_string($job['payload'] ?? '') ? json_decode($job['payload'], true) : ($job['payload'] ?? []);
                            $jobClass = $payload['job'] ?? 'Unknown';
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600 dark:text-gray-400">
                                    <?= htmlspecialchars(substr($job['id'] ?? '', 0, 8)) ?>...
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        <?= htmlspecialchars($job['queue'] ?? 'default') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    <?= e($jobClass) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-red-600 dark:text-red-400 max-w-xs truncate" title="<?= htmlspecialchars($job['error'] ?? '') ?>">
                                    <?= htmlspecialchars(substr($job['error'] ?? 'Hata mesajı yok', 0, 50)) ?>...
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="<?= ($job['attempts'] ?? 0) >= ($job['max_tries'] ?? 3) ? 'text-red-600' : 'text-yellow-600' ?>">
                                        <?= $job['attempts'] ?? 0 ?> / <?= $job['max_tries'] ?? 3 ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?= isset($job['failed_at']) ? date('M d, Y H:i', $job['failed_at']) : 'N/A' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <?php if (($job['attempts'] ?? 0) < ($job['max_tries'] ?? 3)): ?>
                                        <form method="POST" action="<?= base_url('/admin/queue/retry') ?>" class="inline">
                                            <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id'] ?? '') ?>">
                                            <button type="submit" 
                                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                    title="Tekrar Dene">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="<?= base_url('/admin/queue/delete') ?>" class="inline" 
                                          onsubmit="return confirm('Bu başarısız işi silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id'] ?? '') ?>">
                                        <button type="submit" 
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                title="İşi Sil">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <button onclick="showJobDetails('<?= htmlspecialchars($job['id'] ?? '') ?>')" 
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="Detayları Gör">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <i class="fas fa-check-circle text-6xl mb-4 opacity-50 text-green-500"></i>
                <p class="text-lg">Başarısız iş yok! 🎉</p>
                <p class="text-sm mt-2">Tüm kuyruk işleri başarıyla işleniyor.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function showJobDetails(jobId) {
    // Fetch job details via AJAX
    fetch(`<?= base_url('/admin/queue/job/') ?>${jobId}`)
        .then(response => response.json())
        .then(data => {
            alert(`İş Detayları:\n\nID: ${data.id}\nKuyruk: ${data.queue}\nYük: ${JSON.stringify(data.payload, null, 2)}\nHata: ${data.error}`);
        })
        .catch(error => {
            alert('İş detayları yüklenemedi');
            console.error(error);
        });
}
</script>

