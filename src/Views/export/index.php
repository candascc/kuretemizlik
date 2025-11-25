<?php
$title = 'Export - Küre Temizlik';
$breadcrumb = [
    ['name' => 'Ana Sayfa', 'url' => base_url('/')],
    ['name' => 'Export', 'url' => base_url('/export')]
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Export</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Verilerinizi CSV ve Excel formatında export edin</p>
    </div>

    <!-- Export Options -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Customers Export -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-all duration-200">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                    <i class="fas fa-users text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Müşteriler</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Müşteri verilerini export et</p>
                </div>
            </div>
            <div class="space-y-3">
                <a href="<?= base_url('/export/customers?format=csv') ?>" 
                   class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-file-csv mr-2"></i>
                    CSV Export
                </a>
                <a href="<?= base_url('/export/customers?format=excel') ?>" 
                   class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-file-excel mr-2"></i>
                    Excel Export
                </a>
            </div>
        </div>

        <!-- Jobs Export -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-all duration-200">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
                    <i class="fas fa-tasks text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">İşler</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">İş verilerini export et</p>
                </div>
            </div>
            <div class="space-y-3">
                <a href="<?= base_url('/export/jobs?format=csv') ?>" 
                   class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-file-csv mr-2"></i>
                    CSV Export
                </a>
                <a href="<?= base_url('/export/jobs?format=excel') ?>" 
                   class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-file-excel mr-2"></i>
                    Excel Export
                </a>
            </div>
        </div>

        <!-- Finance Export -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6 hover:shadow-medium transition-all duration-200">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 p-3 bg-yellow-100 dark:bg-yellow-900/20 rounded-lg">
                    <i class="fas fa-chart-line text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Finans</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Finansal verileri export et</p>
                </div>
            </div>
            <div class="space-y-3">
                <a href="<?= base_url('/export/finance?format=csv') ?>" 
                   class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-file-csv mr-2"></i>
                    CSV Export
                </a>
                <a href="<?= base_url('/export/finance?format=excel') ?>" 
                   class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-file-excel mr-2"></i>
                    Excel Export
                </a>
            </div>
        </div>
    </div>

    <!-- Export History -->
    <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Son Export İşlemleri</h3>
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <i class="fas fa-history text-4xl mb-4"></i>
            <p>Henüz export işlemi yapılmamış</p>
        </div>
    </div>
</div>
