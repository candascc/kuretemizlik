<?php require __DIR__ . '/layout/header.php'; ?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8"><?= __('book_appointment') ?></h1>

    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="<?= base_url('/portal/booking/process') ?>" class="space-y-8">
            <?= CSRF::field() ?>
            <!-- Service Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('select_service') ?></label>
                <select name="service_id" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- <?= __('select') ?> --</option>
                    <?php if (isset($services)): ?>
                        <?php foreach ($services as $service): ?>
                            <option value="<?= e($service['id']) ?>">
                                <?= e($service['name']) ?> - <?= _c($service['price']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Date Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('select_date') ?></label>
                <input type="date" name="date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Time Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('select_time') ?></label>
                <select name="time" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- <?= __('select') ?> --</option>
                    <option value="09:00:00">09:00</option>
                    <option value="11:00:00">11:00</option>
                    <option value="13:00:00">13:00</option>
                    <option value="15:00:00">15:00</option>
                    <option value="17:00:00">17:00</option>
                </select>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('notes') ?> (<?= __('optional') ?>)</label>
                <textarea name="notes" rows="4" 
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="<?= __('additional_requests') ?>"></textarea>
            </div>

            <!-- Submit -->
            <div class="flex justify-between items-center pt-6 border-t">
                <a href="<?= base_url('/portal/dashboard') ?>" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i><?= __('back') ?>
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                    <i class="fas fa-check mr-2"></i><?= __('confirm_booking') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>

