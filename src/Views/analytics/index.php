<?php
/**
 * Analytics Index View
 */
?>

<div class="container-fluid px-4 py-4 mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            <i class="fas fa-analytics mr-2"></i>Analitik Dashboard
        </h1>
        
        <div class="flex gap-2">
            <select id="period" class="form-select rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="day" <?= $period === 'day' ? 'selected' : '' ?>>Günlük</option>
                <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Haftalık</option>
                <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Aylık</option>
                <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Yıllık</option>
            </select>
            
            <input type="date" id="date_from" value="<?= e($date_from) ?>" 
                   class="form-input rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <input type="date" id="date_to" value="<?= e($date_to) ?>" 
                   class="form-input rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            
            <button onclick="applyFilter()" class="btn btn-primary px-4 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700">
                <i class="fas fa-filter mr-2"></i>Filtrele
            </button>
        </div>
    </div>

    <?php if (isset($flash) && $flash): ?>
        <div class="mb-4 p-4 bg-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : 'red' ?>-700 rounded">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-2">Toplam Gelir</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                <?= number_format($kpis['total_revenue'] ?? 0, 2) ?> ₺
            </div>
            <div class="text-green-600 text-sm mt-2">
                <i class="fas fa-arrow-up mr-1"></i>Bu dönem
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-2">Toplam İş</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                <?= number_format($kpis['total_jobs'] ?? 0) ?>
            </div>
            <div class="text-blue-600 text-sm mt-2">
                <i class="fas fa-tasks mr-1"></i>İş sayısı
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-2">Ortalama İş Değeri</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                <?= number_format($kpis['average_job_value'] ?? 0, 2) ?> ₺
            </div>
            <div class="text-purple-600 text-sm mt-2">
                <i class="fas fa-chart-line mr-1"></i>Ortalama
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-2">Tamamlanma Oranı</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                <?= number_format($kpis['completion_rate'] ?? 0, 1) ?>%
            </div>
            <div class="text-orange-600 text-sm mt-2">
                <i class="fas fa-check-circle mr-1"></i>Başarı
            </div>
        </div>
    </div>

    <!-- Revenue Trends Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
            <i class="fas fa-chart-area mr-2"></i>Gelir Trendleri
        </h2>
        <canvas id="revenueChart" height="80"></canvas>
    </div>

    <!-- Job Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                <i class="fas fa-tasks mr-2"></i>İş Durumları
            </h2>
            <div class="space-y-3">
                <?php foreach ($job_stats ?? [] as $stat): ?>
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <span class="w-3 h-3 rounded-full mr-3 <?= 
                            $stat['status'] === 'DONE' ? 'bg-green-500' : 
                            ($stat['status'] === 'SCHEDULED' ? 'bg-blue-500' : 'bg-red-500')
                        ?>"></span>
                        <span class="font-medium text-gray-700 dark:text-gray-300">
                            <?= e($stat['status']) ?>
                        </span>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-gray-900 dark:text-gray-100">
                            <?= number_format($stat['count']) ?>
                        </div>
                        <div class="text-sm text-gray-500">
                            <?= number_format($stat['revenue'] ?? 0, 2) ?> ₺
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                <i class="fas fa-users mr-2"></i>Müşteri Metrikleri
            </h2>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Yeni Müşteriler</span>
                    <span class="text-2xl font-bold text-primary-600">
                        <?= number_format($customer_metrics['new_customers'] ?? 0) ?>
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Aktif Müşteriler</span>
                    <span class="text-2xl font-bold text-green-600">
                        <?= number_format($customer_metrics['active_customers'] ?? 0) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Trends Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
            <i class="fas fa-table mr-2"></i>Detaylı Gelir Trendi
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Dönem
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Gelir
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Gider
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Net
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($revenue_trends ?? [] as $trend): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                            <?= e($trend['period']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">
                            <?= number_format($trend['income'] ?? 0, 2) ?> ₺
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                            <?= number_format($trend['expense'] ?? 0, 2) ?> ₺
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold <?= 
                            ($trend['income'] - ($trend['expense'] ?? 0)) >= 0 ? 'text-green-600' : 'text-red-600'
                        ?>">
                            <?= number_format(($trend['income'] ?? 0) - ($trend['expense'] ?? 0), 2) ?> ₺
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function applyFilter() {
    const period = document.getElementById('period').value;
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;
    window.location.href = `<?= base_url('/analytics') ?>?period=${period}&date_from=${dateFrom}&date_to=${dateTo}`;
}

// Simple revenue chart with Chart.js (if available)
if (typeof Chart !== 'undefined') {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($revenue_trends ?? [], 'period')) ?>,
            datasets: [{
                label: 'Gelir',
                data: <?= json_encode(array_column($revenue_trends ?? [], 'income')) ?>,
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4
            }, {
                label: 'Gider',
                data: <?= json_encode(array_column($revenue_trends ?? [], 'expense')) ?>,
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
</script>

