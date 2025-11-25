<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-receipt mr-3 text-primary-600"></i>
                Gider Detayları
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                <?= htmlspecialchars($expense['building_name'] ?? '-') ?>
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="<?= base_url('/expenses/' . $expense['id'] . '/edit') ?>" 
               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                <i class="fas fa-edit mr-2"></i>Düzenle
            </a>
            <a href="<?= base_url('/expenses') ?>" 
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
                <span class="px-4 py-2 rounded-full text-sm font-semibold
                    <?php 
                        if ($expense['approval_status'] === 'approved') {
                            echo 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
                        } elseif ($expense['approval_status'] === 'rejected') {
                            echo 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
                        } else {
                            echo 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
                        }
                    ?>">
                    <?php
                    if ($expense['approval_status'] === 'approved') {
                        echo '<i class="fas fa-check-circle mr-1"></i>Onaylandı';
                    } elseif ($expense['approval_status'] === 'rejected') {
                        echo '<i class="fas fa-times-circle mr-1"></i>Reddedildi';
                    } else {
                        echo '<i class="fas fa-clock mr-1"></i>Bekliyor';
                    }
                    ?>
                </span>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?= number_format($expense['amount'] ?? 0, 2) ?> ₺
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Toplam Gider</p>
            </div>
        </div>
    </div>

    <!-- Main Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Basic Information -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-info-circle mr-2 text-primary-600"></i>Temel Bilgiler
            </h2>
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Bina</label>
                    <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($expense['building_name'] ?? '-') ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Kategori</label>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?php
                        $categoryNames = [
                            'elektrik' => 'Elektrik',
                            'su' => 'Su',
                            'dogalgaz' => 'Doğalgaz',
                            'temizlik' => 'Temizlik',
                            'guvenlik' => 'Güvenlik',
                            'bakim' => 'Bakım-Onarım',
                            'vergi' => 'Vergi',
                            'sigorta' => 'Sigorta',
                            'diger' => 'Diğer'
                        ];
                        echo $categoryNames[$expense['category'] ?? 'diger'] ?? 'Diğer';
                        ?>
                    </p>
                </div>
                <?php if (!empty($expense['subcategory'])): ?>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">Alt Kategori</label>
                        <p class="font-medium text-gray-900 dark:text-white"><?= e($expense['subcategory']) ?></p>
                    </div>
                <?php endif; ?>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Gider Tarihi</label>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?= $expense['expense_date'] ? date('d.m.Y', strtotime($expense['expense_date'])) : '-' ?>
                    </p>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Periyodik Gider</label>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?= (!empty($expense['is_recurring'])) ? '<span class="text-green-600">Evet</span>' : '<span class="text-gray-500">Hayır</span>' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-credit-card mr-2 text-primary-600"></i>Ödeme Bilgileri
            </h2>
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Ödeme Yöntemi</label>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?php
                        $paymentMethods = [
                            'cash' => 'Nakit',
                            'transfer' => 'Havale/EFT',
                            'check' => 'Çek',
                            'card' => 'Kredi Kartı',
                            'other' => 'Diğer'
                        ];
                        echo $paymentMethods[$expense['payment_method'] ?? 'transfer'] ?? 'Diğer';
                        ?>
                    </p>
                </div>
                <?php if (!empty($expense['invoice_number'])): ?>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">Fatura No</label>
                        <p class="font-medium text-gray-900 dark:text-white"><?= e($expense['invoice_number']) ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($expense['vendor_name'])): ?>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">Firma/Üretici</label>
                        <p class="font-medium text-gray-900 dark:text-white"><?= e($expense['vendor_name']) ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($expense['vendor_tax_number'])): ?>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">Vergi No</label>
                        <p class="font-medium text-gray-900 dark:text-white"><?= e($expense['vendor_tax_number']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Description -->
    <?php if (!empty($expense['description'])): ?>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-align-left mr-2 text-primary-600"></i>Açıklama
            </h2>
            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?= e($expense['description']) ?></p>
        </div>
    <?php endif; ?>

    <!-- Timeline -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            <i class="fas fa-history mr-2 text-primary-600"></i>Geçmiş
        </h2>
        <div class="space-y-4">
            <div class="flex items-start">
                <div class="flex-shrink-0 w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                    <i class="fas fa-plus text-primary-600 dark:text-primary-300"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Gider Oluşturuldu</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <?= $expense['created_at'] ? date('d.m.Y H:i', strtotime($expense['created_at'])) : '-' ?>
                    </p>
                </div>
            </div>
            <?php if (!empty($expense['approved_at'])): ?>
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-green-600 dark:text-green-300"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Onaylandı</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <?= date('d.m.Y H:i', strtotime($expense['approved_at'])) ?>
                            <?php if (!empty($expense['approved_by_name'])): ?>
                                - <?= e($expense['approved_by_name']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php elseif (!empty($expense['rejected_at'])): ?>
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-times text-red-600 dark:text-red-300"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Reddedildi</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <?= date('d.m.Y H:i', strtotime($expense['rejected_at'])) ?>
                            <?php if (!empty($expense['rejected_by_name'])): ?>
                                - <?= e($expense['rejected_by_name']) ?>
                            <?php endif; ?>
                            <?php if (!empty($expense['rejection_reason'])): ?>
                                <br><span class="text-red-600 dark:text-red-400">Sebep: <?= e($expense['rejection_reason']) ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($expense['updated_at']) && $expense['updated_at'] !== $expense['created_at']): ?>
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-edit text-blue-600 dark:text-blue-300"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Son Güncelleme</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <?= date('d.m.Y H:i', strtotime($expense['updated_at'])) ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Buttons -->
    <?php if ($expense['approval_status'] === 'pending'): ?>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-yellow-600 dark:text-yellow-400 mr-3"></i>
                    <p class="text-yellow-800 dark:text-yellow-300">Bu gider onay bekliyor</p>
                </div>
                <div class="flex space-x-3">
                    <form method="POST" action="<?= base_url('/expenses/approve/' . $expense['id']) ?>" class="inline">
                        <?= CSRF::field() ?>
                        <button type="submit" 
                                onclick="return confirm('Bu gideri onaylamak istediğinizden emin misiniz?')"
                                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-check mr-2"></i>Onayla
                        </button>
                    </form>
                    <form method="POST" action="<?= base_url('/expenses/reject/' . $expense['id']) ?>" class="inline">
                        <?= CSRF::field() ?>
                        <button type="submit" 
                                onclick="return confirm('Bu gideri reddetmek istediğinizden emin misiniz?')"
                                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-times mr-2"></i>Reddet
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

