<div class="space-y-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900"><?= e($contract['title']) ?></h1>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (begin) -->
        <?php if (Auth::role() !== 'OPERATOR'): ?>
        <div class="flex space-x-3">
            <a href="<?= base_url("/contracts/{$contract['id']}/edit") ?>" class="inline-flex items-center px-4 py-2 rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                <i class="fas fa-edit mr-2"></i> Düzenle
            </a>
            <form method="POST" action="<?= base_url("/contracts/{$contract['id']}/delete") ?>" class="inline" onsubmit="return confirm('Bu sözleşmeyi silmek istediğinizden emin misiniz?')">
                <?= CSRF::field() ?>
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-white bg-red-600 hover:bg-red-700">
                    <i class="fas fa-trash mr-2"></i> Sil
                </button>
            </form>
        </div>
        <?php endif; ?>
        <!-- ===== KOZMOS_OPERATOR_READONLY: hide action buttons for operator (end) -->
    </div>

    <!-- ===== KOZMOS_OPERATOR_READONLY: hide status update form for operator (begin) -->
    <?php if (Auth::role() !== 'OPERATOR'): ?>
    <!-- Status Update Form -->
    <div class="bg-white rounded-md shadow p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Durum Güncelle</h3>
        <form method="POST" action="<?= base_url("/contracts/{$contract['id']}/status") ?>" class="flex items-center space-x-4">
            <?= CSRF::field() ?>
            <select name="status" class="border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $contract['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i> Güncelle
            </button>
        </form>
    </div>
    <?php endif; ?>
    <!-- ===== KOZMOS_OPERATOR_READONLY: hide status update form for operator (end) -->

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sözleşme Bilgileri -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-md shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Sözleşme Bilgileri</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Sözleşme Numarası</label>
                        <p class="mt-1 text-sm text-gray-900"><?= e($contract['contract_number']) ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Müşteri</label>
                        <p class="mt-1 text-sm text-gray-900"><?= e($contract['customer_name']) ?></p>
                        <?php if ($contract['customer_phone']): ?>
                            <p class="text-sm text-gray-500"><?= e($contract['customer_phone']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Sözleşme Tipi</label>
                        <p class="mt-1 text-sm text-gray-900"><?= $types[$contract['contract_type']] ?? $contract['contract_type'] ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Durum</label>
                        <?php
                        $statusColors = [
                            'DRAFT' => 'bg-gray-100 text-gray-800',
                            'ACTIVE' => 'bg-green-100 text-green-800',
                            'SUSPENDED' => 'bg-yellow-100 text-yellow-800',
                            'COMPLETED' => 'bg-blue-100 text-blue-800',
                            'TERMINATED' => 'bg-red-100 text-red-800'
                        ];
                        $colorClass = $statusColors[$contract['status']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $colorClass ?> mt-1">
                            <?= $statuses[$contract['status']] ?? $contract['status'] ?>
                        </span>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Başlangıç Tarihi</label>
                        <p class="mt-1 text-sm text-gray-900"><?= Utils::formatDate($contract['start_date']) ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Bitiş Tarihi</label>
                        <p class="mt-1 text-sm text-gray-900"><?= $contract['end_date'] ? Utils::formatDate($contract['end_date']) : 'Belirtilmemiş' ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Toplam Tutar</label>
                        <p class="mt-1 text-sm text-gray-900"><?= $contract['total_amount'] ? Utils::formatMoney($contract['total_amount']) : 'Belirtilmemiş' ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Ödeme Koşulları</label>
                        <p class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($contract['payment_terms'] ?? 'Belirtilmemiş') ?></p>
                    </div>
                </div>
                
                <?php if ($contract['description']): ?>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-500">Açıklama</label>
                        <p class="mt-1 text-sm text-gray-900"><?= nl2br(e($contract['description'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($contract['notes']): ?>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-500">Notlar</label>
                        <p class="mt-1 text-sm text-gray-900"><?= nl2br(e($contract['notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ödemeler -->
            <div class="bg-white rounded-md shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Ödemeler</h3>
                    <button onclick="document.getElementById('paymentModal').classList.remove('hidden')" class="inline-flex items-center px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i> Ödeme Ekle
                    </button>
                </div>
                
                <?php if (empty($payments)): ?>
                    <p class="text-gray-500 text-center py-4">Henüz ödeme kaydı yok.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tutar</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Yöntem</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?= Utils::formatMoney($payment['amount']) ?></td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?= Utils::formatDate($payment['payment_date']) ?></td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?= $payment_methods[$payment['payment_method']] ?? $payment['payment_method'] ?></td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <?php
                                            $paymentStatusColors = [
                                                'PENDING' => 'bg-yellow-100 text-yellow-800',
                                                'PAID' => 'bg-green-100 text-green-800',
                                                'OVERDUE' => 'bg-red-100 text-red-800'
                                            ];
                                            $paymentColorClass = $paymentStatusColors[$payment['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $paymentColorClass ?>">
                                                <?= $payment_statuses[$payment['status']] ?? $payment['status'] ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="editPayment(<?= $payment['id'] ?>)" class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" action="<?= base_url("/contracts/{$contract['id']}/payment/{$payment['id']}/delete") ?>" class="inline" onsubmit="return confirm('Bu ödemeyi silmek istediğinizden emin misiniz?')">
                                                    <?= CSRF::field() ?>
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Dosyalar -->
            <div class="bg-white rounded-md shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Dosyalar</h3>
                    <button onclick="document.getElementById('fileModal').classList.remove('hidden')" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <i class="fas fa-upload mr-2"></i> Dosya Yükle
                    </button>
                </div>
                
                <?php if (empty($attachments)): ?>
                    <p class="text-gray-500 text-center py-4">Henüz dosya yüklenmemiş.</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($attachments as $attachment): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                                <div class="flex items-center">
                                    <?php 
                                    $fileExt = strtolower(pathinfo($attachment['file_name'], PATHINFO_EXTENSION));
                                    $iconClass = 'fas fa-file';
                                    if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                        $iconClass = 'fas fa-image text-green-500';
                                    } elseif ($fileExt === 'pdf') {
                                        $iconClass = 'fas fa-file-pdf text-red-500';
                                    }
                                    ?>
                                    <i class="<?= $iconClass ?> mr-3"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?= e($attachment['file_name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= Utils::formatFileSize($attachment['file_size']) ?> • <?= e($attachment['mime_type']) ?></p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="<?= base_url('/' . $attachment['file_path']) ?>" target="_blank" class="text-blue-600 hover:text-blue-900" title="Görüntüle/İndir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('/' . $attachment['file_path']) ?>" download class="text-green-600 hover:text-green-900" title="İndir">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <form method="POST" action="<?= base_url("/contracts/{$contract['id']}/file/{$attachment['id']}/delete") ?>" class="inline" onsubmit="return confirm('Bu dosyayı silmek istediğinizden emin misiniz?')">
                                        <?= CSRF::field() ?>
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-8">
            <!-- Hızlı İşlemler -->
            <div class="bg-white rounded-md shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Hızlı İşlemler</h3>
                <div class="space-y-3">
                    <a href="<?= base_url("/customers/{$contract['customer_id']}") ?>" class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        <i class="fas fa-user mr-2"></i> Müşteri Detayı
                    </a>
                    <!-- ===== KOZMOS_OPERATOR_READONLY: hide new contract button for operator (begin) -->
                    <?php if (Auth::role() !== 'OPERATOR'): ?>
                    <a href="<?= base_url('/contracts/new') ?>" class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Yeni Sözleşme
                    </a>
                    <?php endif; ?>
                    <!-- ===== KOZMOS_OPERATOR_READONLY: hide new contract button for operator (end) -->
                </div>
            </div>

            <!-- Sözleşme Özeti -->
            <div class="bg-white rounded-md shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Sözleşme Özeti</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Oluşturulma:</span>
                        <span class="text-sm text-gray-900"><?= Utils::formatDateTime($contract['created_at']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Son Güncelleme:</span>
                        <span class="text-sm text-gray-900"><?= Utils::formatDateTime($contract['updated_at']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Oluşturan:</span>
                        <span class="text-sm text-gray-900"><?= htmlspecialchars($contract['created_by_user'] ?? 'Bilinmiyor') ?></span>
                    </div>
                    <?php if ($contract['auto_renewal']): ?>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Otomatik Yenileme:</span>
                            <span class="text-sm text-green-600">Aktif</span>
                        </div>
                        <?php if ($contract['renewal_period_days']): ?>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Yenileme Süresi:</span>
                                <span class="text-sm text-gray-900"><?= $contract['renewal_period_days'] ?> gün</span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Yeni Ödeme</h3>
            <form method="POST" action="<?= base_url("/contracts/{$contract['id']}/payment") ?>">
                <?= CSRF::field() ?>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tutar</label>
                        <input type="number" name="amount" step="0.01" required class="w-full border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ödeme Tarihi</label>
                        <input type="date" name="payment_date" required class="w-full border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ödeme Yöntemi</label>
                        <select name="payment_method" class="w-full border-gray-300 rounded-md">
                            <?php foreach ($payment_methods as $key => $label): ?>
                                <option value="<?= $key ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Durum</label>
                        <select name="status" class="w-full border-gray-300 rounded-md">
                            <?php foreach ($payment_statuses as $key => $label): ?>
                                <option value="<?= $key ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notlar</label>
                        <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-md"></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        İptal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- File Upload Modal -->
<div id="fileModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Dosya Yükle</h3>
            <form method="POST" action="<?= base_url("/contracts/{$contract['id']}/upload") ?>" enctype="multipart/form-data">
                <?= CSRF::field() ?>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dosya Seç</label>
                        <input type="file" name="file" required class="w-full border-gray-300 rounded-md">
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="document.getElementById('fileModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        İptal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Yükle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editPayment(paymentId) {
    // Bu fonksiyon ödeme düzenleme modalını açacak
    // Şimdilik basit bir alert gösterelim
    alert('Ödeme düzenleme özelliği yakında eklenecek. Payment ID: ' + paymentId);
}
</script>