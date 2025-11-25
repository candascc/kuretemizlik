<?php if (empty($entry)): ?>
    <div class="text-center py-8">
        <div class="text-gray-500">Kayıt bulunamadı.</div>
        <a href="<?= base_url('/finance') ?>" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
            <i class="fas fa-arrow-left mr-2"></i>
            Finansa Dön
        </a>
    </div>
<?php else: ?>
<div class="space-y-8">
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li>
                <a href="<?= base_url('/finance') ?>" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-money-bill"></i>
                    <span class="sr-only">Finans</span>
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500 font-medium">Kayıt #<?= $entry['id'] ?></span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                <?= $entry['kind'] === 'INCOME' ? 'Gelir' : 'Gider' ?> Kaydı #<?= $entry['id'] ?>
            </h2>
            <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                <div class="mt-2 flex items-center text-sm text-gray-500">
                    <i class="fas fa-calendar mr-1"></i>
                    <?= Utils::formatDate($entry['date']) ?>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500">
                    <i class="fas fa-tag mr-1"></i>
                    <?= e($entry['category']) ?>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500">
                    <i class="fas fa-money-bill mr-1"></i>
                    <?= Utils::formatMoney($entry['amount']) ?>
                </div>
            </div>
        </div>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (begin) -->
        <?php if (Auth::role() !== 'OPERATOR'): ?>
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <a href="<?= base_url("/finance/edit/{$entry['id']}") ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-edit mr-2"></i>
                Düzenle
            </a>
            <form method="POST" action="<?= base_url("/finance/delete/{$entry['id']}") ?>" class="inline" onsubmit="return confirm('Bu kaydı silmek istediğinizden emin misiniz?')">
                <?= CSRF::field() ?>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                    <i class="fas fa-trash mr-2"></i>
                    Sil
                </button>
            </form>
        </div>
        <?php endif; ?>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (end) -->
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Kayıt Bilgileri</h3>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tür</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $entry['kind'] === 'INCOME' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $entry['kind'] === 'INCOME' ? 'Gelir' : 'Gider' ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Kategori</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= e($entry['category']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Miktar</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-semibold <?= $entry['kind'] === 'INCOME' ? 'text-green-600' : 'text-red-600' ?>">
                            <?= Utils::formatMoney($entry['amount']) ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tarih</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= Utils::formatDate($entry['date']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Oluşturan</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($entry['created_by_name'] ?? 'Bilinmiyor') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Oluşturulma</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= Utils::formatDateTime($entry['created_at']) ?></dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6 space-y-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Detaylar</h3>

                <?php if (!empty($entry['note'])): ?>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Not</h4>
                        <div class="prose max-w-none">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap"><?= e($entry['note']) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($entry['job_id'])): ?>
                    <?php
                        $paymentStatus = $entry['payment_status'] ?? null;
                        $badgeClass = [
                            'PAID' => 'bg-green-100 text-green-800',
                            'PARTIAL' => 'bg-yellow-100 text-yellow-800',
                            'UNPAID' => 'bg-red-100 text-red-800',
                        ][$paymentStatus] ?? 'bg-gray-100 text-gray-800';
                        $statusLabel = [
                            'PAID' => 'Tamamlandı',
                            'PARTIAL' => 'Kısmi',
                            'UNPAID' => 'Ödenmedi',
                        ][$paymentStatus] ?? ($paymentStatus ?? 'Bilinmiyor');
                    ?>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Bağlı İş</h4>
                        <div class="bg-gray-50 rounded-md p-4 space-y-2">
                            <a href="<?= base_url("/jobs/show/{$entry['job_id']}") ?>" class="text-blue-600 hover:text-blue-900 font-medium block">
                                İş #<?= $entry['job_id'] ?>
                            </a>
                            <?php if (!empty($entry['customer_name'])): ?>
                                <div class="text-sm text-gray-600">Müşteri: <?= e($entry['customer_name']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($entry['service_name'])): ?>
                                <div class="text-sm text-gray-600">Hizmet: <?= e($entry['service_name']) ?></div>
                            <?php endif; ?>
                            <?php if ($jobRemaining !== null): ?>
                                <div class="text-sm text-gray-600">Kalan: <?= Utils::formatMoney($jobRemaining) ?></div>
                            <?php endif; ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $badgeClass ?>"><?= $statusLabel ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
