<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-users mr-3 text-primary-600"></i>
                Katılım Takibi
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                <?= htmlspecialchars($meeting['title'] ?? 'Toplantı') ?> - 
                <?= $meeting['meeting_date'] ? date('d.m.Y H:i', strtotime($meeting['meeting_date'])) : '' ?>
            </p>
        </div>
        <a href="<?= base_url('/meetings/' . $meeting['id']) ?>" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Geri
        </a>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Daire</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?= $totalUnits ?? 0 ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400">Katılım Sayısı</p>
            <p class="text-2xl font-bold text-green-600 mt-1"><?= $attendanceCount ?? 0 ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400">Katılım Oranı</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">
                <?= $totalUnits > 0 ? number_format(($attendanceCount / $totalUnits) * 100, 1) : 0 ?>%
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400">Yeter Sayı</p>
            <p class="text-2xl font-bold <?= ($meeting['quorum_reached'] ?? 0) ? 'text-green-600' : 'text-yellow-600' ?> mt-1">
                <?= ($meeting['quorum_reached'] ?? 0) ? 'Sağlandı' : 'Sağlanmadı' ?>
            </p>
        </div>
    </div>

    <!-- Attendance Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= base_url('/meetings/' . $meeting['id'] . '/attendance') ?>" id="attendanceForm">
            <?= CSRF::field() ?>
            
            <div class="p-6">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Katılım Kayıtları</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Her daire için katılımcı bilgilerini girin ve katılım durumunu işaretleyin
                    </p>
                </div>

                <div class="space-y-4 max-h-[600px] overflow-y-auto">
                    <?php foreach ($units as $unit): 
                        $existingAttendee = null;
                        foreach ($attendees as $att) {
                            if ($att['unit_id'] == $unit['id']) {
                                $existingAttendee = $att;
                                break;
                            }
                        }
                    ?>
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 
                                    <?= $existingAttendee && ($existingAttendee['attended'] ?? 0) ? 'bg-green-50 dark:bg-green-900/20' : '' ?>">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Daire
                                    </label>
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        <?= e($unit['unit_number']) ?>
                                    </p>
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Katılımcı Adı <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           name="attendees[<?= $unit['id'] ?>][name]" 
                                           value="<?= $existingAttendee ? e($existingAttendee['attendee_name']) : e($unit['owner_name']) ?>"
                                           required
                                           class="w-full px-3 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                    <input type="hidden" name="attendees[<?= $unit['id'] ?>][unit_id]" value="<?= $unit['id'] ?>">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Sahiplik
                                    </label>
                                    <select name="attendees[<?= $unit['id'] ?>][is_owner]"
                                            class="w-full px-3 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                        <option value="1" <?= ($existingAttendee && ($existingAttendee['is_owner'] ?? 1)) ? 'selected' : '' ?>>Sahip</option>
                                        <option value="0" <?= ($existingAttendee && !($existingAttendee['is_owner'] ?? 1)) ? 'selected' : '' ?>>Kiracı</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Vekil
                                    </label>
                                    <input type="text" 
                                           name="attendees[<?= $unit['id'] ?>][proxy_holder]" 
                                           value="<?= $existingAttendee ? htmlspecialchars($existingAttendee['proxy_holder'] ?? '') : '' ?>"
                                           placeholder="Vekalet veren kişi"
                                           class="w-full px-3 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Oylama Ağırlığı
                                    </label>
                                    <input type="number" 
                                           name="attendees[<?= $unit['id'] ?>][vote_weight]" 
                                           value="<?= $existingAttendee ? ($existingAttendee['vote_weight'] ?? 1.0) : 1.0 ?>"
                                           step="0.01" min="0" max="10"
                                           class="w-full px-3 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                </div>

                                <div class="md:col-span-1">
                                    <label class="flex items-center h-full pt-6">
                                        <input type="checkbox" 
                                               name="attendees[<?= $unit['id'] ?>][attended]" 
                                               value="1"
                                               <?= ($existingAttendee && ($existingAttendee['attended'] ?? 0)) ? 'checked' : '' ?>
                                               class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Katıldı</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-6 flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div>
                        <button type="button" onclick="toggleAllAttended(true)" 
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-check-double mr-2"></i>Tümünü İşaretle
                        </button>
                        <button type="button" onclick="toggleAllAttended(false)" 
                                class="ml-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-times mr-2"></i>Tümünü Temizle
                        </button>
                    </div>
                    <button type="submit" 
                            class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium">
                        <i class="fas fa-save mr-2"></i>Kaydet
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAllAttended(checked) {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][name*="[attended]"]');
    checkboxes.forEach(cb => {
        cb.checked = checked;
        const row = cb.closest('.border');
        if (checked) {
            row.classList.add('bg-green-50', 'dark:bg-green-900/20');
            row.classList.remove('bg-white', 'dark:bg-gray-800');
        } else {
            row.classList.remove('bg-green-50', 'dark:bg-green-900/20');
            row.classList.add('bg-white', 'dark:bg-gray-800');
        }
    });
}

// Toggle row color when checkbox changes
document.querySelectorAll('input[type="checkbox"][name*="[attended]"]').forEach(cb => {
    cb.addEventListener('change', function() {
        const row = this.closest('.border');
        if (this.checked) {
            row.classList.add('bg-green-50', 'dark:bg-green-900/20');
        } else {
            row.classList.remove('bg-green-50', 'dark:bg-green-900/20');
        }
    });
});
</script>

