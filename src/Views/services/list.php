<?php
/**
 * Services List View
 */
?>

<div class="space-y-8">
    <!-- Header Section -->
    <div>
        <?php ob_start(); ?>
        <a href="<?= base_url('/services/new') ?>" 
           class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-soft hover:shadow-medium transition-all duration-200">
            <i class="fas fa-plus mr-2"></i> 
            Yeni Hizmet
        </a>
        <?php $rightActionsHtml = ob_get_clean(); ?>
        <?php 
        $title = '<i class="fas fa-concierge-bell mr-3 text-primary-600"></i>Hizmetler';
        $subtitle = 'Hizmet bilgilerini yönetin ve takip edin';
        include __DIR__ . '/../partials/ui/list-header.php';
        ?>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Statistics Cards -->
    <?php if (isset($stats) && is_array($stats)): ?>
    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <i class="fas fa-concierge-bell text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Hizmet</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['total'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif Hizmet</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['active'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <i class="fas fa-ban text-gray-600 dark:text-gray-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pasif Hizmet</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $stats['inactive'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Services Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-list mr-2"></i>
                Hizmet Listesi
            </h2>
        </div>
        
        <div class="overflow-x-auto">
            <?php if (empty($services)): ?>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="mx-auto w-20 h-20 flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 mb-6">
                                        <i class="fas fa-concierge-bell text-3xl"></i>
                                    </div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Henüz hizmet yok</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md">İlk hizmetinizi ekleyerek başlayın. Hizmetler müşterilerinize sunduğunuz hizmet türlerini tanımlar.</p>
                                    <?php if (Auth::role() !== 'OPERATOR'): ?>
                                    <a href="<?= base_url('/services/new') ?>" 
                                       class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-medium hover:shadow-strong transition-all duration-200">
                                        <i class="fas fa-plus mr-2"></i>
                                        İlk Hizmeti Ekle
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <?php
                $headers = [
                    'id' => 'ID',
                    'name' => 'Hizmet Adı',
                    'duration' => 'Süre',
                    'fee' => 'Ücret',
                    'status' => ['label' => 'Durum', 'raw' => true],
                    'actions' => ['label' => '<div class="text-left">İşlemler</div>', 'raw' => true],
                ];
                $rows = [];
                foreach ($services as $service) {
                    $statusBadge = $service['is_active']
                        ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200">Aktif</span>'
                        : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200">Pasif</span>';

                    $actionsHtml = '<div class="flex items-center space-x-3">'
                        .'<form method="POST" action="'.base_url("/services/toggle/{$service['id']}").'" class="inline">'.CSRF::field().'<button type="submit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="'.($service['is_active'] ? 'Deaktif Et' : 'Aktif Et').'" aria-label="'.($service['is_active'] ? 'Hizmeti pasif yap' : 'Hizmeti aktif yap').'"><i class="fas fa-toggle-'.($service['is_active'] ? 'on' : 'off').' text-xl"></i></button></form>'
                        .'<a href="'.base_url("/services/edit/{$service['id']}").'" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" aria-label="Hizmeti düzenle" title="Düzenle"><i class="fas fa-edit"></i></a>'
                        .'<form method="POST" action="'.base_url("/services/delete/{$service['id']}").'" class="inline" onsubmit="return confirm(\'Bu hizmeti silmek istediğinizden emin misiniz?\');">'.CSRF::field().'<button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" aria-label="Hizmeti sil" title="Sil"><i class="fas fa-trash"></i></button></form>'
                        .'</div>';

                    $rows[] = [
                        'id' => '#'.(int)$service['id'],
                        'name' => e($service['name']),
                        'duration' => (isset($service['duration_min']) ? (int)$service['duration_min'] : 'N/A').' dk',
                        'fee' => number_format($service['default_fee'] ?? 0, 2).' ₺',
                        'status' => $statusBadge,
                        'actions' => $actionsHtml,
                    ];
                }
                include __DIR__ . '/../partials/ui/table.php';
                ?>
            <?php endif; ?>
        </div>
    </div>
</div>
