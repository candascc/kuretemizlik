<?php
if (!$data || !isset($data['customer'])) {
    echo '<div class="p-8 text-center"><p class="text-red-600">Müşteri bulunamadı</p></div>';
    return;
}

$customer = $data['customer'];
$jobs = $data['jobs'] ?? [];
$totalSpent = $data['total_spent'] ?? 0;
$jobStats = $data['job_stats'] ?? [];
?>

<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-user-circle mr-3"></i>
                Müşteri Raporu
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                <?= e($customer['name']) ?>
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="<?= base_url('/customers/show/' . $customer['id']) ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <i class="fas fa-user mr-2"></i>
                Müşteri Detayı
            </a>
            <a href="<?= base_url('/customers') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                Geri
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/company-context.php'; ?>

    <!-- Customer Information -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-info-circle mr-2"></i>
            Müşteri Bilgileri
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ad Soyad</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white"><?= e($customer['name']) ?></p>
            </div>
            <?php if (!empty($customer['phone'])): ?>
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Telefon</p>
                <p class="text-lg text-gray-900 dark:text-white">
                    <i class="fas fa-phone mr-2"></i>
                    <?= e($customer['phone']) ?>
                </p>
            </div>
            <?php endif; ?>
            <?php if (!empty($customer['email'])): ?>
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Email</p>
                <p class="text-lg text-gray-900 dark:text-white">
                    <i class="fas fa-envelope mr-2"></i>
                    <?= e($customer['email']) ?>
                </p>
            </div>
            <?php endif; ?>
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Kayıt Tarihi</p>
                <p class="text-lg text-gray-900 dark:text-white">
                    <i class="fas fa-calendar mr-2"></i>
                    <?= date('d.m.Y', strtotime($customer['created_at'])) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <i class="fas fa-shopping-cart text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam İş</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $jobStats['total_jobs'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tamamlanan</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $jobStats['completed_jobs'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                    <i class="fas fa-times-circle text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">İptal Edilen</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $jobStats['cancelled_jobs'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <i class="fas fa-lira-sign text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Harcama</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white"><?= number_format($totalSpent, 2) ?> ₺</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Statistics -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-chart-bar mr-2"></i>
            İş İstatistikleri
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ortalama İş Değeri</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    <?= isset($jobStats['avg_job_value']) && $jobStats['avg_job_value'] > 0 
                        ? number_format($jobStats['avg_job_value'], 2) . ' ₺' 
                        : 'N/A' ?>
                </p>
            </div>
            
            <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tamamlanma Oranı</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    <?php 
                    $totalJobs = $jobStats['total_jobs'] ?? 0;
                    $completedJobs = $jobStats['completed_jobs'] ?? 0;
                    echo $totalJobs > 0 ? number_format(($completedJobs / $totalJobs) * 100, 1) . '%' : '0%';
                    ?>
                </p>
            </div>
            
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/20 rounded-lg">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif İş</p>
                <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">
                    <?= ($jobStats['total_jobs'] ?? 0) - ($jobStats['completed_jobs'] ?? 0) - ($jobStats['cancelled_jobs'] ?? 0) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Job History -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-briefcase mr-2"></i>
                İş Geçmişi
            </h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarih</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hizmet</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tutar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ödeme</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($jobs)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-4"></i>
                                <p>Bu müşteri için henüz iş kaydı yok</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= date('d.m.Y', strtotime($job['start_at'])) ?>
                                    <br>
                                    <span class="text-xs text-gray-500"><?= date('H:i', strtotime($job['start_at'])) ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($job['service_name'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'SCHEDULED' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'IN_PROGRESS' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'COMPLETED' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'CANCELLED' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    ];
                                    $statusLabels = [
                                        'SCHEDULED' => 'Planlı',
                                        'IN_PROGRESS' => 'Devam Ediyor',
                                        'COMPLETED' => 'Tamamlandı',
                                        'CANCELLED' => 'İptal',
                                    ];
                                    $color = $statusColors[$job['status']] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                    $label = $statusLabels[$job['status']] ?? $job['status'];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $color ?>">
                                        <?= $label ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <?= number_format($job['total_amount'] ?? 0, 2) ?> ₺
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $paymentColors = [
                                        'PAID' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'PARTIAL' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'UNPAID' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    ];
                                    $paymentLabels = [
                                        'PAID' => 'Ödendi',
                                        'PARTIAL' => 'Kısmi',
                                        'UNPAID' => 'Ödenmedi',
                                    ];
                                    $pColor = $paymentColors[$job['payment_status']] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                    $pLabel = $paymentLabels[$job['payment_status']] ?? $job['payment_status'];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $pColor ?>">
                                        <?= $pLabel ?>
                                    </span>
                                    <?php if ($job['payment_status'] === 'PARTIAL'): ?>
                                        <br><span class="text-xs text-gray-500">
                                            (<?= number_format($job['amount_paid'] ?? 0, 2) ?> ₺)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="<?= base_url('/jobs/show/' . $job['id']) ?>" 
                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
