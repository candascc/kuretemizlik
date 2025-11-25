<?php if (empty($job)): ?>
    <div class="text-center py-8">
        <div class="text-gray-500">İş bulunamadı.</div>
        <a href="<?= base_url('/jobs') ?>" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
            <i class="fas fa-arrow-left mr-2"></i>
            İşlere Dön
        </a>
    </div>
<?php else: ?>
<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li>
                <a href="<?= base_url('/jobs') ?>" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-tasks"></i>
                    <span class="sr-only">İşler</span>
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-500 font-medium">İş #<?= $job['id'] ?></span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                İş #<?= $job['id'] ?>
            </h2>
            <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                <div class="mt-2 flex items-center text-sm text-gray-500">
                    <i class="fas fa-calendar mr-1"></i>
                    <?= Utils::formatDateTime($job['start_at'], 'd.m.Y H:i') ?> - <?= Utils::formatDateTime($job['end_at'], 'H:i') ?>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500">
                    <i class="fas fa-user mr-1"></i>
                    <?= e($job['customer_name']) ?>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500">
                    <i class="fas fa-tag mr-1"></i>
                    <?= e($job['service_name'] ?? 'Belirtilmemiş') ?>
                </div>
            </div>
        </div>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (begin) -->
        <?php if (Auth::role() !== 'OPERATOR'): ?>
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <a href="<?= base_url("/jobs/edit/{$job['id']}") ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-edit mr-2"></i>
                Düzenle
            </a>
            <?php if (empty($job['occurrence_id'])): ?>
            <form method="POST" action="<?= base_url("/jobs/convert-to-recurring/{$job['id']}") ?>" class="inline" onsubmit="return confirm('Bu işi periyodik işe dönüştürmek istediğinize emin misiniz? İş bilgileri periyodik iş tanımına aktarılacaktır.')">
                <?= CSRF::field() ?>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-redo mr-2"></i>
                    Periyodiğe Dönüştür
                </button>
            </form>
            <?php endif; ?>
            <form method="POST" action="<?= base_url("/jobs/delete/{$job['id']}") ?>" class="inline" onsubmit="return confirm('Bu işi silmek istediğinizden emin misiniz?')">
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

    <!-- Job Details -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Basic Info -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">İş Bilgileri</h3>
                
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Durum</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $job['status'] === 'DONE' ? 'bg-green-100 text-green-800' : ($job['status'] === 'CANCELLED' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                <?= $job['status'] === 'DONE' ? 'Tamamlandı' : ($job['status'] === 'CANCELLED' ? 'İptal' : 'Planlandı') ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Müşteri</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="<?= base_url("/customers/show/{$job['customer_id']}") ?>" class="text-blue-600 hover:text-blue-900">
                                <?= e($job['customer_name']) ?>
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Hizmet</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= e($job['service_name'] ?? 'Belirtilmemiş') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Adres</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php if ($job['address_line']): ?>
                                <?= e($job['address_line']) ?>
                                <?php if ($job['address_city']): ?>
                                    <br><span class="text-gray-500"><?= e($job['address_city']) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-500">Belirtilmemiş</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Başlangıç</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= Utils::formatDateTime($job['start_at']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Bitiş</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= Utils::formatDateTime($job['end_at']) ?></dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Notes & Actions -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Notlar</h3>
                
                <?php if ($job['note']): ?>
                    <div class="prose max-w-none">
                        <p class="text-sm text-gray-700 whitespace-pre-wrap"><?= e($job['note']) ?></p>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-500">Not yok.</p>
                <?php endif; ?>

                <!-- Status Actions -->
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Durum Değiştir</h4>
                    <form method="POST" action="<?= base_url("/jobs/status/{$job['id']}") ?>" class="inline" onsubmit="return maybeAskCancelReason(this)">
                        <?= CSRF::field() ?>
                        <select name="status" class="border-gray-300 rounded-md text-sm"
                                onchange="this.form.submit()">
                            <option value="SCHEDULED" <?= $job['status']==='SCHEDULED'?'selected':'' ?>>Planlandı</option>
                            <option value="DONE" <?= $job['status']==='DONE'?'selected':'' ?>>Tamamlandı</option>
                            <option value="CANCELLED" <?= $job['status']==='CANCELLED'?'selected':'' ?>>İptal</option>
                        </select>
                        <input type="hidden" name="cancel_reason" value="">
                    </form>
                </div>

                <!-- Income Action -->
                <?php if ($job['status'] === 'DONE'): ?>
                    <div class="mt-4">
                        <a href="<?= base_url("/finance/from-job/{$job['id']}") ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                            <i class="fas fa-money-bill mr-2"></i>
                            Gelir Oluştur
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function maybeAskCancelReason(form) {
    const sel = form.querySelector('select[name="status"]');
    if (sel && sel.value === 'CANCELLED') {
        const reason = prompt('İptal nedeni (opsiyonel):');
        if (reason !== null) {
            form.querySelector('input[name="cancel_reason"]').value = reason;
        }
    }
    return true;
}
</script>
<?php endif; ?>