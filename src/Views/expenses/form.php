<?php 
$isEdit = !empty($expense);
?>
<div class="space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-receipt mr-3 text-primary-600"></i>
            <?= $isEdit ? 'Gider Düzenle' : 'Yeni Gider Ekle' ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            <?= $isEdit ? 'Gider bilgilerini güncelleyin' : 'Bina gideri kaydı oluşturun' ?>
        </p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= $isEdit ? base_url("/expenses/update/{$expense['id']}") : base_url('/expenses/create') ?>" 
              enctype="multipart/form-data" role="form" aria-describedby="expenses-form-errors" novalidate data-validate="true">
            <?= CSRF::field() ?>

            <!-- Temel Bilgiler -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-info-circle mr-2 text-primary-600"></i>
                        Temel Bilgiler
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bina <span class="text-red-500">*</span>
                            </label>
                            <select name="building_id" required
                                    class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white shadow-inner">
                                
                                <option value="">Bina Seçin</option>
                                <?php foreach ($buildings as $bld): ?>
                                    <option value="<?= $bld['id'] ?>" 
                                            <?= (($isEdit && $expense['building_id'] == $bld['id']) || (isset($buildingId) && $buildingId == $bld['id'])) ? 'selected' : '' ?>>
                                        <?= e($bld['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kategori <span class="text-red-500">*</span>
                            </label>
                            <select name="category" required
                                    class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white shadow-inner">
                                <option value="elektrik" <?= ($isEdit && $expense['category'] === 'elektrik') ? 'selected' : '' ?>>Elektrik</option>
                                <option value="su" <?= ($isEdit && $expense['category'] === 'su') ? 'selected' : '' ?>>Su</option>
                                <option value="dogalgaz" <?= ($isEdit && $expense['category'] === 'dogalgaz') ? 'selected' : '' ?>>Doğalgaz</option>
                                <option value="temizlik" <?= ($isEdit && $expense['category'] === 'temizlik') ? 'selected' : '' ?>>Temizlik</option>
                                <option value="guvenlik" <?= ($isEdit && $expense['category'] === 'guvenlik') ? 'selected' : '' ?>>Güvenlik</option>
                                <option value="bakim" <?= ($isEdit && $expense['category'] === 'bakim') ? 'selected' : '' ?>>Bakım-Onarım</option>
                                <option value="vergi" <?= ($isEdit && $expense['category'] === 'vergi') ? 'selected' : '' ?>>Vergi</option>
                                <option value="sigorta" <?= ($isEdit && $expense['category'] === 'sigorta') ? 'selected' : '' ?>>Sigorta</option>
                                <option value="diger" <?= ($isEdit && $expense['category'] === 'diger') ? 'selected' : '' ?>>Diğer</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Alt Kategori
                            </label>
                            <input type="text" name="subcategory" value="<?= $isEdit ? htmlspecialchars($expense['subcategory'] ?? '') : '' ?>" 
                                   placeholder="Örn: Asansör Bakımı"
                                   aria-describedby="subcategory-hint"
                                   class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white shadow-inner">
                            <p id="subcategory-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Opsiyonel: alt kategori girilebilir</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tutar (₺) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="amount" value="<?= $isEdit ? ($expense['amount'] ?? '0') : '' ?>" 
                                   required step="0.01" min="0.01"
                                   aria-required="true" aria-invalid="false"
                                   aria-describedby="amount-error amount-hint"
                                   inputmode="decimal"
                                   data-validate="required|numeric|min:0.01"
                                   class="w-full px-4 py-3 border-2 border-green-300 dark:border-green-700 rounded-lg bg-white/70 dark:bg-green-900/10 focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:text-white shadow-inner">
                            <p id="amount-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="amount-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pozitif bir tutar giriniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Gider Tarihi <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="expense_date" 
                                   value="<?= $isEdit && $expense['expense_date'] ? date('Y-m-d', strtotime($expense['expense_date'])) : date('Y-m-d') ?>" 
                                   required
                                   aria-required="true" aria-invalid="false"
                                   aria-describedby="expense_date-error expense_date-hint"
                                   data-validate="required|date"
                                   class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white shadow-inner">
                            <p id="expense_date-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="expense_date-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gider tarihini seçiniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Ödeme Yöntemi
                            </label>
                            <select name="payment_method"
                                    class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white shadow-inner">
                                <option value="cash" <?= ($isEdit && ($expense['payment_method'] ?? '') === 'cash') ? 'selected' : '' ?>>Nakit</option>
                                <option value="transfer" <?= ($isEdit && ($expense['payment_method'] ?? '') === 'transfer') ? 'selected' : '' ?>>Havale/EFT</option>
                                <option value="card" <?= ($isEdit && ($expense['payment_method'] ?? '') === 'card') ? 'selected' : '' ?>>Kredi Kartı</option>
                                <option value="check" <?= ($isEdit && ($expense['payment_method'] ?? '') === 'check') ? 'selected' : '' ?>>Çek</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fatura Bilgileri -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-file-invoice mr-2 text-primary-600"></i>
                        Fatura/Tedarikçi Bilgileri
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Fatura No
                            </label>
                            <input type="text" name="invoice_number" value="<?= $isEdit ? htmlspecialchars($expense['invoice_number'] ?? '') : '' ?>" 
                                   placeholder="Fatura numarası"
                                   class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white shadow-inner">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tedarikçi Adı
                            </label>
                            <input type="text" name="vendor_name" value="<?= $isEdit ? htmlspecialchars($expense['vendor_name'] ?? '') : '' ?>" 
                                   placeholder="Tedarikçi/firma adı"
                                   class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white shadow-inner">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tedarikçi Vergi No
                            </label>
                            <input type="text" name="vendor_tax_number" value="<?= $isEdit ? htmlspecialchars($expense['vendor_tax_number'] ?? '') : '' ?>" 
                                   placeholder="Vergi numarası"
                                   class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white shadow-inner">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Fatura Dökümanı
                            </label>
                            <input type="file" name="receipt" accept="image/*,.pdf"
                                   class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white shadow-inner">
                            <?php if ($isEdit && !empty($expense['receipt_path'])): ?>
                                <p class="text-xs text-green-600 mt-1">
                                    <i class="fas fa-check mr-1"></i>Mevcut döküman: <?= htmlspecialchars(basename($expense['receipt_path'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Diğer Bilgiler -->
            <div class="p-6">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Açıklama
                        </label>
                        <textarea name="description" rows="4" 
                                  placeholder="Gider ile ilgili detaylı açıklama..."
                                  class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white shadow-inner"><?= $isEdit ? htmlspecialchars($expense['description'] ?? '') : '' ?></textarea>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_recurring" value="1" id="is_recurring"
                               <?= ($isEdit && ($expense['is_recurring'] ?? 0) == 1) ? 'checked' : '' ?>
                               class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <label for="is_recurring" class="ml-3 text-gray-700 dark:text-gray-300">
                            Tekrarlanan gider (Aylık/Periyodik)
                        </label>
                    </div>

                    <?php if (Auth::hasRole('admin') || Auth::hasRole('manager')): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Onay Durumu
                            </label>
                            <select name="approval_status"
                                    class="w-full px-4 py-3 border-2 border-primary-300 dark:border-primary-700 rounded-lg bg-white/70 dark:bg-primary-900/10 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white shadow-inner">
                                <option value="pending" <?= ($isEdit && ($expense['approval_status'] ?? 'pending') === 'pending') ? 'selected' : '' ?>>Beklemede</option>
                                <option value="approved" <?= ($isEdit && ($expense['approval_status'] ?? 'pending') === 'approved') ? 'selected' : '' ?>>Onaylandı</option>
                                <option value="rejected" <?= ($isEdit && ($expense['approval_status'] ?? 'pending') === 'rejected') ? 'selected' : '' ?>>Reddedildi</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end space-x-3">
                <a href="<?= $isEdit ? base_url('/expenses/' . $expense['id']) : base_url('/expenses') ?>" 
                   class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    İptal
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors font-medium"
                        style="color: white !important; background: #4f46e5 !important;">
                    <i class="fas fa-save mr-2" style="color: white !important;"></i>
                    <span style="color: white !important;"><?= $isEdit ? 'Güncelle' : 'Kaydet' ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

