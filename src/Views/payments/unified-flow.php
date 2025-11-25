<?php
/**
 * Unified Payment Flow - UX-HIGH-004
 * 
 * Multi-fee selection, cart, instant confirmation
 * Replaces fragmented payment experience
 */

$customer_id = $customer_id ?? null;
$unpaidFees = $unpaidFees ?? [];
$paymentMethods = ['CASH' => 'Nakit', 'CARD' => 'Kredi Kartı', 'BANK' => 'Havale', 'OTHER' => 'Diğer'];
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8" x-data="unifiedPaymentFlow()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-cash-register mr-4 text-primary-600"></i>
                Ödeme Yap
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                Birden fazla ücreti tek seferde ödeyin
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- LEFT: Fee Selection -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Unpaid Fees List -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-primary-600 to-blue-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center justify-between">
                            <span><i class="fas fa-file-invoice-dollar mr-2"></i>Ödenmemiş Ücretler</span>
                            <span class="text-sm font-normal opacity-90">
                                <span x-text="fees.length"></span> ücret
                            </span>
                        </h2>
                    </div>

                    <div class="p-6">
                        <?php if (empty($unpaidFees)): ?>
                            <!-- Empty State -->
                            <div class="text-center py-12">
                                <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                    Tüm Ödemeler Tamamlandı!
                                </h3>
                                <p class="text-gray-600 dark:text-gray-400">
                                    Bekleyen ücret bulunmamaktadır.
                                </p>
                            </div>
                        <?php else: ?>
                            <!-- Fee Selection Grid -->
                            <div class="space-y-3">
                                <template x-for="(fee, index) in fees" :key="fee.id">
                                    <div class="border-2 rounded-lg p-4 transition-all hover:border-primary-400 cursor-pointer"
                                         :class="fee.selected ? 'border-primary-600 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700'"
                                         @click="toggleFee(index)">
                                        
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start space-x-4 flex-1">
                                                <!-- Checkbox -->
                                                <input type="checkbox" 
                                                       :checked="fee.selected"
                                                       @click.stop="toggleFee(index)"
                                                       class="w-5 h-5 mt-1 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                                
                                                <!-- Fee Info -->
                                                <div class="flex-1">
                                                    <div class="flex items-center space-x-2 mb-1">
                                                        <h3 class="font-semibold text-gray-900 dark:text-white" x-text="fee.type_label"></h3>
                                                        <span class="px-2 py-0.5 text-xs rounded-full"
                                                              :class="fee.overdue ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'">
                                                            <span x-show="fee.overdue">Gecikmiş</span>
                                                            <span x-show="!fee.overdue">Bekliyor</span>
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                                        <div><i class="fas fa-calendar mr-2"></i>Vade: <span x-text="fee.due_date_formatted"></span></div>
                                                        <div x-show="fee.description">
                                                            <i class="fas fa-info-circle mr-2"></i>
                                                            <span x-text="fee.description"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Amount -->
                                            <div class="text-right ml-4">
                                                <div class="text-2xl font-bold text-primary-600">
                                                    <span x-text="formatMoney(fee.amount)"></span> ₺
                                                </div>
                                                <div class="text-xs text-gray-500" x-show="fee.overdue">
                                                    +<span x-text="formatMoney(fee.late_fee || 0)"></span> ₺ gecikme
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Quick Select Buttons -->
                            <div class="mt-6 flex flex-wrap gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <button type="button" 
                                        @click="selectAll()"
                                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-all">
                                    <i class="fas fa-check-double mr-2"></i>
                                    Tümünü Seç
                                </button>
                                <button type="button"
                                        @click="selectNone()"
                                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-all">
                                    <i class="fas fa-times mr-2"></i>
                                    Seçimi Temizle
                                </button>
                                <button type="button"
                                        @click="selectOverdue()"
                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-all">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Sadece Gecikmiş
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Cart Summary (Sticky) -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden sticky top-8">
                    
                    <!-- Cart Header -->
                    <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Ödeme Sepeti
                        </h2>
                    </div>

                    <div class="p-6 space-y-6">
                        
                        <!-- Selected Fees -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                Seçili Ücretler
                            </h3>
                            
                            <div x-show="selectedFees.length === 0" class="text-center py-8 text-gray-400">
                                <i class="fas fa-inbox text-3xl mb-2"></i>
                                <p class="text-sm">Henüz ücret seçilmedi</p>
                            </div>

                            <div x-show="selectedFees.length > 0" class="space-y-2">
                                <template x-for="fee in selectedFees" :key="fee.id">
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-700 dark:text-gray-300" x-text="fee.type_label"></span>
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            <span x-text="formatMoney(fee.amount)"></span> ₺
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Total Calculation -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Ara Toplam</span>
                                <span class="font-medium" x-text="formatMoney(subtotal) + ' ₺'"></span>
                            </div>
                            <div class="flex justify-between text-sm" x-show="totalLateFee > 0">
                                <span class="text-red-600">Gecikme Ücreti</span>
                                <span class="font-medium text-red-600" x-text="formatMoney(totalLateFee) + ' ₺'"></span>
                            </div>
                            <div class="flex justify-between text-xl font-bold border-t border-gray-300 dark:border-gray-600 pt-3">
                                <span class="text-gray-900 dark:text-white">Toplam</span>
                                <span class="text-primary-600" x-text="formatMoney(total) + ' ₺'"></span>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-credit-card mr-2"></i>
                                Ödeme Yöntemi
                            </label>
                            <select x-model="paymentMethod" 
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                <?php foreach ($paymentMethods as $value => $label): ?>
                                    <option value="<?= $value ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Payment Note -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-sticky-note mr-2"></i>
                                Not (İsteğe bağlı)
                            </label>
                            <textarea x-model="paymentNote"
                                      rows="2"
                                      placeholder="Ödeme ile ilgili not..."
                                      class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white resize-none"></textarea>
                        </div>

                        <!-- Submit Button -->
                        <button type="button"
                                @click="submitPayment()"
                                :disabled="selectedFees.length === 0 || isSubmitting"
                                :class="selectedFees.length > 0 && !isSubmitting ? 'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700' : 'bg-gray-300 cursor-not-allowed'"
                                class="w-full py-4 text-white font-bold text-lg rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:scale-105 disabled:transform-none">
                                <i class="fas" :class="isSubmitting ? 'fa-spinner fa-spin' : 'fa-check-circle'"></i>
                                <span x-text="isSubmitting ? 'İşleniyor...' : 'Ödemeyi Tamamla'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div x-show="showSuccessModal" 
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @click="showSuccessModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-8 text-center"
             @click.stop>
            
            <!-- Success Animation -->
            <div class="mb-6">
                <div class="w-20 h-20 mx-auto bg-green-100 rounded-full flex items-center justify-center animate-bounce">
                    <i class="fas fa-check text-4xl text-green-600"></i>
                </div>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                Ödeme Başarılı!
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                <span x-text="formatMoney(total)"></span> ₺ ödeme başarıyla alındı.
            </p>

            <div class="space-y-3">
                <button type="button"
                        @click="downloadReceipt()"
                        class="w-full px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition-all">
                    <i class="fas fa-download mr-2"></i>
                    Makbuz İndir (PDF)
                </button>
                <button type="button"
                        @click="closeSuccessModal()"
                        class="w-full px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition-all">
                    Kapat
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function unifiedPaymentFlow() {
    return {
        fees: <?= json_encode($unpaidFees ?? []) ?>,
        paymentMethod: 'CASH',
        paymentNote: '',
        isSubmitting: false,
        showSuccessModal: false,
        paymentId: null,
        
        get selectedFees() {
            return this.fees.filter(f => f.selected);
        },
        
        get subtotal() {
            return this.selectedFees.reduce((sum, f) => sum + parseFloat(f.amount || 0), 0);
        },
        
        get totalLateFee() {
            return this.selectedFees.reduce((sum, f) => sum + parseFloat(f.late_fee || 0), 0);
        },
        
        get total() {
            return this.subtotal + this.totalLateFee;
        },
        
        toggleFee(index) {
            this.fees[index].selected = !this.fees[index].selected;
        },
        
        selectAll() {
            this.fees.forEach(f => f.selected = true);
        },
        
        selectNone() {
            this.fees.forEach(f => f.selected = false);
        },
        
        selectOverdue() {
            this.fees.forEach(f => f.selected = f.overdue);
        },
        
        formatMoney(amount) {
            return parseFloat(amount || 0).toFixed(2);
        },
        
        async submitPayment() {
            if (this.selectedFees.length === 0) return;
            
            this.isSubmitting = true;
            
            try {
                const response = await fetch('<?= base_url('/payments/unified-submit') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        fees: this.selectedFees.map(f => f.id),
                        payment_method: this.paymentMethod,
                        note: this.paymentNote,
                        total: this.total,
                        csrf_token: document.querySelector('meta[name="csrf-token"]')?.content || ''
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.paymentId = result.payment_id;
                    
                    // Success animation
                    if (typeof confetti !== 'undefined') {
                        confetti({
                            particleCount: 150,
                            spread: 80,
                            origin: { y: 0.6 }
                        });
                    }
                    
                    this.showSuccessModal = true;
                    
                    // Remove paid fees from list
                    this.fees = this.fees.filter(f => !f.selected);
                    
                } else {
                    alert('Ödeme hatası: ' + (result.error || 'Bilinmeyen hata'));
                }
                
            } catch (error) {
                console.error('Payment error:', error);
                alert('Bağlantı hatası: ' + error.message);
            } finally {
                this.isSubmitting = false;
            }
        },
        
        downloadReceipt() {
            if (this.paymentId) {
                window.open('<?= base_url('/payments/receipt/') ?>' + this.paymentId, '_blank');
            }
        },
        
        closeSuccessModal() {
            this.showSuccessModal = false;
            // Optionally redirect
            // window.location.href = '<?= base_url('/payments') ?>';
        }
    }
}
</script>

