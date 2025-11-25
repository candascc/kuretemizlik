<div class="space-y-8">
    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li><a href="<?= base_url('/customers') ?>" class="text-gray-400 hover:text-primary-600"><i class="fas fa-users"></i></a></li>
            <li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-500">Müşteri Detayı</span></li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-user mr-3 text-primary-600"></i>
                <?= e($customer['name']) ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Müşteri detayları ve bilgileri</p>
        </div>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide edit button for operator (begin) -->
        <?php if (Auth::role() !== 'OPERATOR'): ?>
        <a href="<?= base_url("/customers/edit/{$customer['id']}") ?>" class="inline-flex items-center px-4 py-2 rounded-lg text-white bg-primary-600 hover:bg-primary-700 transition-colors">
            <i class="fas fa-edit mr-2"></i>Düzenle
        </a>
        <?php endif; ?>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide edit button for operator (end) -->
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-md shadow p-4">
            <div class="text-sm text-gray-500">Telefon</div>
            <div class="text-gray-900"><?= e($customer['phone'] ?? '-') ?></div>
            <div class="mt-3 text-sm text-gray-500">Email</div>
            <div class="text-gray-900"><?= e($customer['email'] ?? '-') ?></div>
        </div>
        <div class="bg-white rounded-md shadow p-4">
            <div class="text-sm text-gray-500">Toplam İş</div>
            <div class="text-gray-900 font-semibold"><?= (int)$jobCount ?></div>
        </div>
        <div class="bg-white rounded-md shadow p-4">
            <div class="text-sm text-gray-500">Finans Özeti</div>
            <div class="text-sm flex justify-between"><span>Gelir</span><span class="text-green-700 font-semibold"><?= Utils::formatMoney($totals['income']) ?></span></div>
            <div class="text-sm flex justify-between"><span>Gider</span><span class="text-red-700 font-semibold"><?= Utils::formatMoney($totals['expense']) ?></span></div>
            <div class="text-sm flex justify-between"><span>Kar</span><span class="<?= $totals['profit']>=0?'text-green-700':'text-red-700' ?> font-semibold"><?= Utils::formatMoney($totals['profit']) ?></span></div>
        </div>
    </div>

    <!-- Adresler Bölümü -->
    <?php if (!empty($customer['addresses'])): ?>
    <div class="bg-white rounded-md shadow p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Adresler</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($customer['addresses'] as $address): ?>
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-900">
                            <?= !empty($address['label']) ? e($address['label']) : 'Adres' ?>
                        </h3>
                        <span class="text-xs text-gray-500">
                            <?= Utils::formatDate($address['created_at']) ?>
                        </span>
                    </div>
                    <div class="text-sm text-gray-700">
                        <div class="font-medium"><?= e($address['line']) ?></div>
                        <?php if (!empty($address['city'])): ?>
                            <div class="text-gray-500"><?= e($address['city']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-md shadow p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-gray-900">Son İşler</h2>
            <!-- ===== KOZMOS_OPERATOR_READONLY: hide new job button for operator (begin) -->
            <?php if (Auth::role() !== 'OPERATOR'): ?>
            <a href="<?= base_url('/jobs/new') ?>" class="text-sm text-blue-600 hover:text-blue-800"><i class="fas fa-plus mr-1"></i>Yeni İş Oluştur</a>
            <?php endif; ?>
            <!-- ===== KOZMOS_OPERATOR_READONLY: hide new job button for operator (end) -->
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hizmet</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($jobs as $j): ?>
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-900"><?= Utils::formatDateTime($j['start_at']) ?></td>
                        <td class="px-6 py-3 text-sm text-gray-700"><?= e($j['service_name'] ?? '-') ?></td>
                        <td class="px-6 py-3 text-sm">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $j['status']==='DONE'?'bg-green-100 text-green-800':($j['status']==='CANCELLED'?'bg-red-100 text-red-800':'bg-yellow-100 text-yellow-800') ?>">
                                <?= $j['status']==='DONE'?'Tamamlandı':($j['status']==='CANCELLED'?'İptal':'Planlandı') ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Adresler -->
    <div class="bg-white rounded-md shadow p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-gray-900">Adresler</h2>
            <!-- ===== KOZMOS_OPERATOR_READONLY: hide address add form for operator (begin) -->
            <?php if (Auth::role() !== 'OPERATOR'): ?>
            <form method="POST" action="<?= base_url("/customers/add-address/{$customer['id']}") ?>" class="flex gap-2 items-end">
                <?= CSRF::field() ?>
                <div>
                    <label class="block text-sm text-gray-600">Etiket</label>
                    <input type="text" name="label" class="mt-1 border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Adres Satırı</label>
                    <input type="text" name="line" class="mt-1 border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Şehir</label>
                    <input type="text" name="city" class="mt-1 border-gray-300 rounded-md">
                </div>
                <button class="px-3 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700">Ekle</button>
            </form>
            <?php endif; ?>
            <!-- ===== KOZMOS_OPERATOR_READONLY: hide address add form for operator (end) -->
        </div>
        <div class="divide-y">
            <?php foreach ($customer['addresses'] as $addr): ?>
                <!-- ===== KOZMOS_OPERATOR_READONLY: hide address edit/delete forms for operator (begin) -->
                <?php if (Auth::role() !== 'OPERATOR'): ?>
                <form method="POST" action="<?= base_url("/customers/address-update/{$addr['id']}") ?>" class="py-3 grid grid-cols-1 md:grid-cols-5 gap-2 items-end">
                    <?= CSRF::field() ?>
                    <div>
                        <label class="block text-sm text-gray-600">Etiket</label>
                        <input type="text" name="label" value="<?= e($addr['label'] ?? '') ?>" class="mt-1 border-gray-300 rounded-md w-full">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600">Adres Satırı</label>
                        <input type="text" name="line" value="<?= e($addr['line']) ?>" class="mt-1 border-gray-300 rounded-md w-full" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600">Şehir</label>
                        <input type="text" name="city" value="<?= e($addr['city'] ?? '') ?>" class="mt-1 border-gray-300 rounded-md w-full">
                    </div>
                    <div class="flex gap-2">
                        <button class="px-3 py-2 rounded-md text-white bg-green-600 hover:bg-green-700">Kaydet</button>
                        <form method="POST" action="<?= base_url("/customers/address-delete/{$addr['id']}") ?>" onsubmit="return confirm('Silinsin mi?')">
                            <?= CSRF::field() ?>
                            <button class="px-3 py-2 rounded-md text-white bg-red-600 hover:bg-red-700">Sil</button>
                        </form>
                    </div>
                </form>
                <?php else: ?>
                <!-- Operator için sadece görüntüleme -->
                <div class="py-3 grid grid-cols-1 md:grid-cols-3 gap-2 items-end">
                    <div>
                        <label class="block text-sm text-gray-600">Etiket</label>
                        <div class="mt-1 text-sm text-gray-900"><?= e($addr['label'] ?? 'Adres') ?></div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600">Adres</label>
                        <div class="mt-1 text-sm text-gray-900"><?= e($addr['line']) ?></div>
                        <?php if (!empty($addr['city'])): ?>
                        <div class="text-sm text-gray-500"><?= e($addr['city']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <!-- ===== KOZMOS_OPERATOR_READONLY: hide address edit/delete forms for operator (end) -->
            <?php endforeach; ?>
            <?php if (empty($customer['addresses'])): ?>
                <div class="text-sm text-gray-500 py-2">Adres yok.</div>
            <?php endif; ?>
        </div>
    </div>
</div>