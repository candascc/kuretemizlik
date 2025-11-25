<?php 
$isEdit = !empty($meeting);
?>
<div class="space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-calendar-alt mr-3 text-primary-600"></i>
            <?= $isEdit ? 'Toplantı Düzenle' : 'Yeni Toplantı Planla' ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            <?= $isEdit ? 'Toplantı bilgilerini güncelleyin' : 'Yeni bir toplantı planlayın' ?>
        </p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= $isEdit ? base_url("/meetings/update/{$meeting['id']}") : base_url('/meetings/create') ?>" id="meetingForm" role="form" aria-describedby="meetings-form-errors" novalidate>
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
                            <select name="building_id" required id="building_id"
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white" aria-required="true" aria-invalid="false" aria-describedby="building-error building-hint" data-validate="required|numeric|min:1">
                                <option value="">Bina Seçin</option>
                                <?php foreach ($buildings as $bld): ?>
                                    <option value="<?= $bld['id'] ?>" 
                                            <?= (($isEdit && $meeting['building_id'] == $bld['id']) || (isset($buildingId) && $buildingId == $bld['id'])) ? 'selected' : '' ?>>
                                        <?= e($bld['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p id="building-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="building-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bir bina seçiniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Toplantı Tipi <span class="text-red-500">*</span>
                            </label>
                            <select name="meeting_type" required
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white" aria-required="true" aria-invalid="false" aria-describedby="meeting_type-error meeting_type-hint" data-validate="required|in:regular,extraordinary,board">
                                <option value="regular" <?= ($isEdit && $meeting['meeting_type'] === 'regular') ? 'selected' : '' ?>>Olağan Toplantı</option>
                                <option value="extraordinary" <?= ($isEdit && $meeting['meeting_type'] === 'extraordinary') ? 'selected' : '' ?>>Olağanüstü Toplantı</option>
                                <option value="board" <?= ($isEdit && $meeting['meeting_type'] === 'board') ? 'selected' : '' ?>>Yönetim Kurulu</option>
                            </select>
                            <p id="meeting_type-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="meeting_type-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Toplantı tipini seçiniz</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Toplantı Başlığı <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" value="<?= $isEdit ? e($meeting['title']) : '' ?>" 
                                   required placeholder="Örn: 2025 Yılı 1. Olağan Genel Kurul Toplantısı"
                                   aria-required="true" aria-invalid="false" aria-describedby="title-error title-hint" data-validate="required|min:3|max:150"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <p id="title-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="title-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">3-150 karakter arası başlık giriniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Toplantı Tarihi <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" name="meeting_date" 
                                   value="<?= $isEdit && $meeting['meeting_date'] ? date('Y-m-d\TH:i', strtotime($meeting['meeting_date'])) : '' ?>" 
                                   required
                                   aria-required="true" aria-invalid="false" aria-describedby="meeting_date-error meeting_date-hint" data-validate="required|datetime"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                            <p id="meeting_date-error" class="field-error hidden text-sm text-red-600 mt-1"></p>
                            <p id="meeting_date-hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">Toplantı tarihini seçiniz</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Toplantı Yeri
                            </label>
                            <input type="text" name="location" value="<?= $isEdit ? htmlspecialchars($meeting['location'] ?? '') : '' ?>" 
                                   placeholder="Örn: Bina Yönetim Ofisi"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Açıklama
                            </label>
                            <textarea name="description" rows="4" 
                                      placeholder="Toplantı hakkında detaylı bilgi..."
                                      class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"><?= $isEdit ? htmlspecialchars($meeting['description'] ?? '') : '' ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gündem Maddeleri -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-list mr-2 text-primary-600"></i>
                        Gündem Maddeleri
                    </h2>
                </div>
                <div class="p-6">
                    <div id="agendaItems">
                        <?php if ($isEdit && !empty($meeting['agenda'])): 
                            $agenda = json_decode($meeting['agenda'], true) ?: [];
                            foreach ($agenda as $index => $item): ?>
                                <div class="flex items-start gap-3 mb-3 agenda-item">
                                    <input type="text" name="agenda[]" value="<?= e($item) ?>" 
                                           placeholder="Gündem maddesi"
                                           class="flex-1 px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                    <button type="button" onclick="removeAgendaItem(this)" 
                                            class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; 
                        endif; ?>
                    </div>
                    <button type="button" onclick="addAgendaItem()" 
                            class="mt-3 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">
                        <i class="fas fa-plus mr-2"></i>Gündem Maddesi Ekle
                    </button>
                </div>
            </div>

            <!-- Durum -->
            <div class="p-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Durum <span class="text-red-500">*</span>
                    </label>
                    <select name="status" required
                            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        <option value="scheduled" <?= ($isEdit && ($meeting['status'] ?? 'scheduled') === 'scheduled') ? 'selected' : '' ?>>Planlandı</option>
                        <option value="completed" <?= ($isEdit && ($meeting['status'] ?? 'scheduled') === 'completed') ? 'selected' : '' ?>>Tamamlandı</option>
                        <option value="cancelled" <?= ($isEdit && ($meeting['status'] ?? 'scheduled') === 'cancelled') ? 'selected' : '' ?>>İptal Edildi</option>
                    </select>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end space-x-3">
                <a href="<?= $isEdit ? base_url('/meetings/' . $meeting['id']) : base_url('/meetings') ?>" 
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

<script>
let agendaIndex = <?= $isEdit && !empty($meeting['agenda']) ? count(json_decode($meeting['agenda'], true) ?: []) : 0 ?>;

function addAgendaItem() {
    const container = document.getElementById('agendaItems');
    const div = document.createElement('div');
    div.className = 'flex items-start gap-3 mb-3 agenda-item';
    div.innerHTML = `
        <input type="text" name="agenda[]" placeholder="Gündem maddesi" 
               class="flex-1 px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
        <button type="button" onclick="removeAgendaItem(this)" 
                class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
    agendaIndex++;
}

function removeAgendaItem(btn) {
    btn.closest('.agenda-item').remove();
}
</script>

