<?php
/** @var array $recurringJob */
/** @var array $occurrences */
/** @var array $stats */
?>
<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li>
                <a href="<?= base_url('/recurring') ?>" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-sync-alt"></i>
                    <span class="sr-only">Periyodik İşler</span>
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500 font-medium">Periyodik İş #<?= $recurringJob['id'] ?></span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
                Periyodik İş #<?= $recurringJob['id'] ?>
            </h2>
            <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                <div class="mt-2 flex items-center text-sm text-gray-500">
                    <i class="fas fa-user mr-1"></i>
                    <?= e($recurringJob['customer_name']) ?>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500">
                    <i class="fas fa-calendar mr-1"></i>
                    <?= e($recurringJob['frequency']) ?> - Her <?= $recurringJob['interval'] ?> <?= $recurringJob['frequency'] === 'WEEKLY' ? 'hafta' : 'gün' ?>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500">
                    <i class="fas fa-clock mr-1"></i>
                    <?= str_pad($recurringJob['byhour'], 2, '0', STR_PAD_LEFT) ?>:<?= str_pad($recurringJob['byminute'], 2, '0', STR_PAD_LEFT) ?>
                </div>
            </div>
        </div>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (begin) -->
        <?php if (Auth::role() !== 'OPERATOR'): ?>
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <a href="<?= base_url("/recurring/{$recurringJob['id']}/edit") ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-edit mr-2"></i>
                Düzenle
            </a>
            <form method="POST" action="<?= base_url("/recurring/{$recurringJob['id']}/toggle") ?>" class="inline">
                <?= CSRF::field() ?>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white <?= $recurringJob['status'] === 'ACTIVE' ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' ?>">
                    <i class="fas <?= $recurringJob['status'] === 'ACTIVE' ? 'fa-pause' : 'fa-play' ?> mr-2"></i>
                    <?= $recurringJob['status'] === 'ACTIVE' ? 'Pasif Yap' : 'Aktif Yap' ?>
                </button>
            </form>
        </div>
        <?php endif; ?>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (end) -->
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-calendar-check text-blue-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Toplam Oluşum</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white"><?= $stats['total_occurrences'] ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Tamamlanan</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white"><?= $stats['completed'] ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock text-yellow-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Planlanan</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white"><?= $stats['planned'] ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-money-bill text-green-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Toplam Gelir</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white"><?= Utils::formatMoney($stats['total_revenue']) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ücret Modeli Bilgisi -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-calculator text-blue-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Ücretlendirme Modeli</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                <?php
                                $pricingModel = $recurringJob['pricing_model'] ?? 'PER_JOB';
                                switch ($pricingModel) {
                                    case 'PER_JOB':
                                        echo 'Her İş Başına: ' . Utils::formatMoney($recurringJob['default_total_amount'] ?? 0);
                                        break;
                                    case 'PER_MONTH':
                                        echo 'Aylık Sabit Ücret: ' . Utils::formatMoney($recurringJob['monthly_amount'] ?? 0);
                                        break;
                                    case 'TOTAL_CONTRACT':
                                        echo 'Toplam Sözleşme: ' . Utils::formatMoney($recurringJob['contract_total_amount'] ?? 0);
                                        if (!empty($recurringJob['end_date'])) {
                                            echo '<br><span class="text-xs text-gray-500">(' . Utils::formatDate($recurringJob['start_date'], 'd.m.Y') . ' - ' . Utils::formatDate($recurringJob['end_date'], 'd.m.Y') . ')</span>';
                                        }
                                        break;
                                    default:
                                        echo 'Her İş Başına: ' . Utils::formatMoney($recurringJob['default_total_amount'] ?? 0);
                                }
                                ?>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-ban text-red-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Atlandı</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white"><?= $stats['skipped'] ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-orange-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Çakışma</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white"><?= $stats['conflict'] ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-credit-card text-blue-500"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Ödenen</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-white"><?= Utils::formatMoney($stats['total_paid']) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Operations -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Toplu İşlemler</h3>
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Saat Değiştir -->
                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Saat Değiştir</h4>
                    <form method="POST" action="<?= base_url("/recurring/{$recurringJob['id']}/update-time") ?>" onsubmit="return confirm('Tüm gelecek oluşumların saatini değiştirmek istediğinizden emin misiniz?')">
                        <?= CSRF::field() ?>
                        <div class="flex space-x-2">
                            <input type="time" name="new_time" value="<?= str_pad($recurringJob['byhour'], 2, '0', STR_PAD_LEFT) ?>:<?= str_pad($recurringJob['byminute'], 2, '0', STR_PAD_LEFT) ?>" class="flex-1 text-sm border-gray-300 rounded-md">
                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tehlikeli İşlemler -->
                <div class="border border-red-200 dark:border-red-800 rounded-lg p-6 bg-red-50 dark:bg-red-900/10">
                    <h3 class="text-lg font-semibold text-red-900 dark:text-red-300 mb-4 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Tehlikeli İşlemler
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Sözleşme İptal -->
                        <?php if ($recurringJob['status'] !== 'CANCELLED'): ?>
                        <div class="border border-red-300 dark:border-red-700 rounded-lg p-4 bg-white dark:bg-gray-800">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">Sözleşmeyi İptal Et</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                Geçmiş tamamlanmış işler korunur. Gelecek oluşumlar ve planlanmış işler silinir. Periyodik iş tanımı arşivde kalır.
                            </p>
                            <form method="POST" action="<?= base_url("/recurring/{$recurringJob['id']}/cancel") ?>" onsubmit="return confirm('Bu periyodik işi iptal etmek istediğinizden emin misiniz?\\n\\nGeçmiş tamamlanmış işler korunur.\\nGelecek oluşumlar ve planlanmış işler silinir.\\n\\nBu işlem geri alınamaz!')">
                                <?= CSRF::field() ?>
                                <button type="submit" class="w-full px-3 py-2 bg-orange-600 text-white text-sm rounded-md hover:bg-orange-700 transition-colors">
                                    <i class="fas fa-ban mr-1"></i>Sözleşmeyi İptal Et
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="border border-gray-300 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800 opacity-60">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Sözleşme Zaten İptal Edilmiş</h4>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Bu periyodik iş zaten iptal edilmiş durumda.</p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Periyodik İşi Tamamen Sil (Sadece ADMIN) -->
                        <?php if (Auth::role() === 'ADMIN'): ?>
                        <div class="border border-red-400 dark:border-red-800 rounded-lg p-4 bg-red-100 dark:bg-red-900/20">
                            <h4 class="text-sm font-medium text-red-900 dark:text-red-300 mb-1">Periyodik İşi Tamamen Sil</h4>
                            <p class="text-xs text-red-700 dark:text-red-400 mb-3">
                                ⚠️ Tüm veriler silinir: Periyodik iş tanımı, tüm oluşumlar, oluşturulmuş işler. Finans kayıtları arşivlenir (raporlama için). Bu işlem geri alınamaz!
                            </p>
                            <form method="POST" action="<?= base_url("/recurring/{$recurringJob['id']}/delete") ?>">
                                <?= CSRF::field() ?>
                                <button type="button" onclick="if (confirm('⚠️ DİKKAT: Bu işlem geri alınamaz!\\n\\nTAMAMEN SİLİNECEK:\\n- Periyodik iş tanımı\\n- Tüm oluşumlar (geçmiş + gelecek)\\n- Oluşturulmuş işler\\n\\nARŞİVLENECEK:\\n- Finans kayıtları\\n\\nBu işlemi yapmak istediğinizden emin misiniz?\\n\\nBu işlem yalnızca test verileri veya yanlış kayıtlar için kullanılmalıdır!')) { this.closest('form').submit(); }" class="w-full px-3 py-2 bg-red-700 text-white text-sm rounded-md hover:bg-red-800 transition-colors font-semibold">
                                    <i class="fas fa-trash-alt mr-1"></i>Tamamen Sil (ADMIN)
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent & Upcoming Occurrences -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Occurrences -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Son Oluşumlar</h3>
                
                <?php if (empty($recentOccurrences)): ?>
                    <div class="text-center py-4">
                        <div class="text-gray-500">Henüz oluşum yok.</div>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($recentOccurrences as $occurrence): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?= Utils::formatDate($occurrence['scheduled_date'], 'd.m.Y') ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?= Utils::formatDateTime($occurrence['scheduled_start_at'], 'H:i') ?> - <?= Utils::formatDateTime($occurrence['scheduled_end_at'], 'H:i') ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($occurrence['job_id'])): ?>
                                        <div class="text-xs mt-1">
                                            <a href="<?= base_url("/jobs/manage/{$occurrence['job_id']}") ?>" 
                                               class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                                                <i class="fas fa-link mr-1 text-xs"></i>
                                                İş #<?= $occurrence['job_id'] ?>
                                                <?php if (isset($occurrence['job_status'])): ?>
                                                    <span class="ml-1">
                                                        <?php if ($occurrence['job_status'] === 'DONE'): ?>
                                                            <i class="fas fa-check-circle text-green-600"></i>
                                                        <?php elseif ($occurrence['job_status'] === 'CANCELLED'): ?>
                                                            <i class="fas fa-times-circle text-red-600"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-clock text-yellow-600"></i>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-xs text-gray-400 mt-1">
                                            <i class="fas fa-hourglass-half mr-1"></i>Henüz iş oluşturulmadı
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $occurrence['status'] === 'GENERATED' ? 'bg-green-100 text-green-800' : ($occurrence['status'] === 'SKIPPED' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                        <?= $occurrence['status'] === 'GENERATED' ? 'Oluşturuldu' : ($occurrence['status'] === 'SKIPPED' ? 'Atlandı' : 'Planlandı') ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Occurrences -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Yaklaşan Oluşumlar</h3>
                
                <?php if (empty($upcomingOccurrences)): ?>
                    <div class="text-center py-4">
                        <div class="text-gray-500">Yaklaşan oluşum yok.</div>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($upcomingOccurrences as $occurrence): ?>
                            <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?= Utils::formatDate($occurrence['scheduled_date'], 'd.m.Y') ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?= Utils::formatDateTime($occurrence['scheduled_start_at'], 'H:i') ?> - <?= Utils::formatDateTime($occurrence['scheduled_end_at'], 'H:i') ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <form method="POST" action="<?= base_url("/recurring/{$recurringJob['id']}/generate-single") ?>" class="inline">
                                        <?= CSRF::field() ?>
                                        <input type="hidden" name="occurrence_id" value="<?= $occurrence['id'] ?>">
                                        <button type="submit" class="text-xs px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                            Şimdi Oluştur
                                        </button>
                                    </form>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Planlandı
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
