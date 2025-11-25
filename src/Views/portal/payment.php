<?php require __DIR__ . '/layout/header.php'; ?>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8"><?= __('payment') ?></h1>

    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4"><?= __('invoice_details') ?></h2>
        <dl class="grid grid-cols-2 gap-4">
            <dt class="text-gray-600"><?= __('invoice_number') ?>:</dt>
            <dd class="font-semibold">#<?= e($invoice['id']) ?></dd>
            
            <dt class="text-gray-600"><?= __('date') ?>:</dt>
            <dd><?= e(date('d/m/Y', strtotime($invoice['date']))) ?></dd>
            
            <dt class="text-gray-600"><?= __('amount') ?>:</dt>
            <dd class="text-2xl font-bold text-blue-600"><?= _c($invoice['amount']) ?></dd>
        </dl>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4"><?= __('select_payment_method') ?></h2>
        
        <form method="POST" action="<?= base_url('/portal/payment/process') ?>" class="space-y-8">
            <?= CSRF::field() ?>
            <input type="hidden" name="invoice_id" value="<?= e($invoice['id']) ?>">
            
            <!-- Payment Methods -->
            <div class="space-y-3">
                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="payment_method" value="card" checked class="mr-3">
                    <i class="fas fa-credit-card text-blue-600 text-2xl mr-3"></i>
                    <div>
                        <div class="font-semibold"><?= __('credit_card') ?></div>
                        <div class="text-sm text-gray-500"><?= __('pay_with_card') ?></div>
                    </div>
                </label>

                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="payment_method" value="transfer" class="mr-3">
                    <i class="fas fa-university text-green-600 text-2xl mr-3"></i>
                    <div>
                        <div class="font-semibold"><?= __('bank_transfer') ?></div>
                        <div class="text-sm text-gray-500"><?= __('transfer_to_account') ?></div>
                    </div>
                </label>

                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="payment_method" value="cash" class="mr-3">
                    <i class="fas fa-money-bill-wave text-yellow-600 text-2xl mr-3"></i>
                    <div>
                        <div class="font-semibold"><?= __('cash') ?></div>
                        <div class="text-sm text-gray-500"><?= __('pay_on_delivery') ?></div>
                    </div>
                </label>
            </div>

            <!-- Submit -->
            <div class="flex justify-between items-center pt-6 border-t">
                <a href="<?= base_url('/portal/invoices') ?>" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i><?= __('back') ?>
                </a>
                <button type="submit" class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold text-lg">
                    <i class="fas fa-lock mr-2"></i><?= __('pay') ?> <?= _c($invoice['amount']) ?>
                </button>
            </div>
        </form>
    </div>

    <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
        <div class="flex">
            <i class="fas fa-shield-alt text-blue-500 mr-3 mt-1"></i>
            <div class="text-sm text-blue-700">
                <p class="font-semibold mb-1"><?= __('secure_payment') ?></p>
                <p><?= __('payment_security_notice') ?></p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>

