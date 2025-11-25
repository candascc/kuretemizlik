<?php require __DIR__ . '/layout/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8"><?= __('invoices') ?></h1>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <?php if (!empty($invoices)): ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= __('date') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= __('amount') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= __('status') ?></th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($invoices as $invoice): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= date('d/m/Y', strtotime($invoice['date'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                <?= _c($invoice['amount']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $invoice['status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= __($invoice['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <?php if ($invoice['status'] !== 'paid'): ?>
                                    <a href="<?= base_url('/portal/payment?invoice_id=' . $invoice['id']) ?>" 
                                       class="text-green-600 hover:text-green-900 mr-3">
                                        <i class="fas fa-credit-card"></i> <?= __('pay_now') ?>
                                    </a>
                                <?php endif; ?>
                                <button onclick="downloadInvoice(<?= $invoice['id'] ?>)" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-download"></i> <?= __('download') ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-file-invoice text-gray-400 text-6xl mb-4"></i>
                <p class="text-gray-500"><?= __('no_invoices') ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function downloadInvoice(id) {
    window.location.href = '<?= base_url('/portal/invoice/download/') ?>' + id;
}
</script>

<?php require __DIR__ . '/layout/footer.php'; ?>

