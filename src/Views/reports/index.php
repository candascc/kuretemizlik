<?php
/** @var array $dashboard */
$period = $dashboard['period'] ?? null;
$kpis = $dashboard['kpis'] ?? [];
$recentJobs = $dashboard['recent_jobs'] ?? [];
$topCustomers = $dashboard['top_customers'] ?? [];
?>
<!-- KUREAPP_R36_MARKER_REPORTS_VIEW_V1 -->
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Raporlar</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Operasyon ve finans performansını tek ekrandan takip edin.
            </p>
        </div>
        <?php if ($period): ?>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Son dönem: 
                <span class="font-medium">
                    <?= e($period['from']->format('d.m.Y')) ?> – <?= e($period['to']->format('d.m.Y')) ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Toplam Gelir (Son 30 Gün) -->
        <div class="text-center p-4 bg-green-50 dark:bg-green-900 rounded-lg">
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                ₺<?= number_format($kpis['total_income_30d'] ?? 0, 2) ?>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Son 30 Günde Toplam Gelir</div>
        </div>

        <!-- Tamamlanan İş (Son 30 Gün) -->
        <div class="text-center p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                <?= number_format($kpis['total_jobs_completed'] ?? 0, 0) ?>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Son 30 Günde Tamamlanan İş</div>
        </div>

        <!-- Aktif Müşteri -->
        <div class="text-center p-4 bg-purple-50 dark:bg-purple-900 rounded-lg">
            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                <?= number_format($kpis['active_customers'] ?? 0, 0) ?>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Aktif Müşteri Sayısı</div>
        </div>

        <!-- Bu Ay Net Kâr -->
        <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg">
            <div class="text-3xl font-bold <?= ($kpis['net_profit_month'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                ₺<?= number_format($kpis['net_profit_month'] ?? 0, 2) ?>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Bu Ay Net Kâr</div>
        </div>
    </div>

    <!-- Orta Bölüm: Recent Jobs / Top Customers -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Jobs -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Son İşler</h2>
            <?php if (empty($recentJobs)): ?>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Gösterilecek iş bulunamadı.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tarih</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Müşteri</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hizmet</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durum</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tutar</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($recentJobs as $job): ?>
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        <?= e(date('d.m.Y', strtotime($job['date'] ?? ''))) ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        <?= e($job['customer_name'] ?? '—') ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        <?= e($job['service_name'] ?? '—') ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <?php
                                        $status = $job['status'] ?? '';
                                        $statusClass = match($status) {
                                            'DONE' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'SCHEDULED' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                            'CANCELLED' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                        };
                                        $statusText = match($status) {
                                            'DONE' => 'Tamamlandı',
                                            'SCHEDULED' => 'Planlandı',
                                            'CANCELLED' => 'İptal',
                                            default => $status,
                                        };
                                        ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= $statusClass ?>">
                                            <?= e($statusText) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        ₺<?= number_format($job['amount'] ?? 0, 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Top Customers -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">En Aktif Müşteriler</h2>
            <?php if (empty($topCustomers)): ?>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Gösterilecek müşteri bulunamadı.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Müşteri</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İş Sayısı</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Toplam Gelir</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($topCustomers as $customer): ?>
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        <?= e($customer['name'] ?? '—') ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        <?= number_format($customer['job_count'] ?? 0, 0) ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">
                                        ₺<?= number_format($customer['total_revenue'] ?? 0, 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alt Bölüm: Rapor Link Kartları -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Finans Raporları -->
        <a href="<?= base_url('/reports/financial') ?>" 
           class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 hover:shadow-strong transition-all duration-200 group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                        Finans Raporları
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Gelir, gider ve karlılık analizi
                    </p>
                </div>
                <i class="fas fa-chart-line text-2xl text-primary-600 dark:text-primary-400"></i>
            </div>
        </a>

        <!-- İş Raporları -->
        <a href="<?= base_url('/reports/jobs') ?>" 
           class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 hover:shadow-strong transition-all duration-200 group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                        İş Raporları
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        İş performansı ve istatistikleri
                    </p>
                </div>
                <i class="fas fa-briefcase text-2xl text-primary-600 dark:text-primary-400"></i>
            </div>
        </a>

        <!-- Müşteri Raporları -->
        <a href="<?= base_url('/reports/customers') ?>" 
           class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 hover:shadow-strong transition-all duration-200 group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                        Müşteri Raporları
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Müşteri analizi ve aktivite
                    </p>
                </div>
                <i class="fas fa-users text-2xl text-primary-600 dark:text-primary-400"></i>
            </div>
        </a>

        <!-- Hizmet Raporları -->
        <a href="<?= base_url('/reports/services') ?>" 
           class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 hover:shadow-strong transition-all duration-200 group">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                        Hizmet Raporları
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Hizmet performans metrikleri
                    </p>
                </div>
                <i class="fas fa-cog text-2xl text-primary-600 dark:text-primary-400"></i>
            </div>
        </a>
    </div>
</div>
