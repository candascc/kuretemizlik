<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-calendar-check mr-3 text-primary-600"></i>
                Rezervasyon Detayları
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                <?= htmlspecialchars($reservation['building_name'] ?? '-') ?> - <?= htmlspecialchars($reservation['facility_name'] ?? '-') ?>
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="<?= base_url('/reservations') ?>" 
               class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-300">
                <i class="fas fa-arrow-left mr-2"></i>Geri
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Status Badge -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <?php
                $statusColors = [
                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                    'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                    'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                ];
                $statusTexts = [
                    'pending' => 'Bekliyor',
                    'approved' => 'Onaylandı',
                    'rejected' => 'Reddedildi',
                    'completed' => 'Tamamlandı',
                    'cancelled' => 'İptal'
                ];
                $status = $reservation['status'] ?? 'pending';
                ?>
                <span class="px-4 py-2 rounded-full text-sm font-semibold <?= $statusColors[$status] ?? $statusColors['pending'] ?>">
                    <?= $statusTexts[$status] ?? 'Bilinmiyor' ?>
                </span>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?= number_format($reservation['total_amount'] ?? 0, 2) ?> ₺
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Toplam Tutar</p>
            </div>
        </div>
    </div>

    <!-- Main Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Rezervasyon Bilgileri -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-info-circle mr-2 text-primary-600"></i>Rezervasyon Bilgileri
            </h2>
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Alan</label>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($reservation['facility_name'] ?? '-') ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Bina</label>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($reservation['building_name'] ?? '-') ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Başlangıç</label>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?= $reservation['start_date'] ? date('d.m.Y H:i', strtotime($reservation['start_date'])) : '-' ?>
                    </p>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Bitiş</label>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?= $reservation['end_date'] ? date('d.m.Y H:i', strtotime($reservation['end_date'])) : '-' ?>
                    </p>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Tip</label>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?= ($reservation['reservation_type'] ?? 'hourly') === 'hourly' ? 'Saatlik' : 'Günlük' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- İletişim Bilgileri -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-user mr-2 text-primary-600"></i>Rezerve Eden
            </h2>
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Ad Soyad</label>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($reservation['resident_name'] ?? '-') ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Telefon</label>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($reservation['resident_phone'] ?? '-') ?></p>
                </div>
                <?php if ($reservation['unit_number']): ?>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">Daire</label>
                        <p class="font-medium text-gray-900 dark:text-white"><?= e($reservation['unit_number']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Ödeme Bilgileri -->
    <?php if ($reservation['total_amount'] > 0): ?>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-money-bill-wave mr-2 text-primary-600"></i>Ödeme Bilgileri
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Toplam Tutar</label>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        <?= number_format($reservation['total_amount'] ?? 0, 2) ?> ₺
                    </p>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Depozito</label>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                        <?= number_format($reservation['deposit_amount'] ?? 0, 2) ?> ₺
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Notlar -->
    <?php if (!empty($reservation['notes'])): ?>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-sticky-note mr-2 text-primary-600"></i>Notlar
            </h2>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?= e($reservation['notes']) ?></p>
        </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <?php if ($status === 'pending'): ?>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-yellow-600 dark:text-yellow-400 mr-3"></i>
                    <p class="text-yellow-800 dark:text-yellow-300">Bu rezervasyon onay bekliyor</p>
                </div>
                <div class="flex space-x-3">
                    <form method="POST" action="<?= base_url('/reservations/approve/' . $reservation['id']) ?>" class="inline">
                        <?= CSRF::field() ?>
                        <button type="submit" 
                                onclick="return confirm('Bu rezervasyonu onaylamak istediğinizden emin misiniz?')"
                                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-check mr-2"></i>Onayla
                        </button>
                    </form>
                    <form method="POST" action="<?= base_url('/reservations/reject/' . $reservation['id']) ?>" class="inline">
                        <?= CSRF::field() ?>
                        <button type="submit" 
                                onclick="return confirm('Bu rezervasyonu reddetmek istediğinizden emin misiniz?')"
                                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-times mr-2"></i>Reddet
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

