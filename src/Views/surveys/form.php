<?php 
$isEdit = !empty($survey);
$questions = $questions ?? [];
?>
<div class="space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <i class="fas fa-poll mr-3 text-primary-600"></i>
            <?= $isEdit ? 'Anket Düzenle' : 'Yeni Anket Oluştur' ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            <?= $isEdit ? 'Anket bilgilerini ve sorularını güncelleyin' : 'Sakinler için yeni bir anket oluşturun' ?>
        </p>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
        <form method="POST" action="<?= $isEdit ? base_url("/surveys/update/{$survey['id']}") : base_url('/surveys/create') ?>" role="form">
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
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Bina Seçin</option>
                                <?php foreach ($buildings as $bld): ?>
                                    <option value="<?= $bld['id'] ?>" 
                                            <?= (($isEdit && $survey['building_id'] == $bld['id']) || (isset($buildingId) && $buildingId == $bld['id'])) ? 'selected' : '' ?>>
                                        <?= e($bld['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Anket Tipi <span class="text-red-500">*</span>
                            </label>
                            <select name="survey_type" required
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                <option value="poll" <?= ($isEdit && $survey['survey_type'] === 'poll') ? 'selected' : '' ?>>Anket</option>
                                <option value="vote" <?= ($isEdit && $survey['survey_type'] === 'vote') ? 'selected' : '' ?>>Oylama</option>
                                <option value="feedback" <?= ($isEdit && $survey['survey_type'] === 'feedback') ? 'selected' : '' ?>>Geri Bildirim</option>
                                <option value="complaint" <?= ($isEdit && $survey['survey_type'] === 'complaint') ? 'selected' : '' ?>>Şikayet</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Başlık <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" value="<?= $isEdit ? e($survey['title']) : '' ?>" 
                                   required placeholder="Anket başlığı"
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Açıklama
                            </label>
                            <textarea name="description" rows="4"
                                      placeholder="Anket açıklaması..."
                                      class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"><?= $isEdit ? e($survey['description']) : '' ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Başlangıç Tarihi <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="start_date" 
                                   value="<?= $isEdit && $survey['start_date'] ? $survey['start_date'] : date('Y-m-d') ?>" 
                                   required
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bitiş Tarihi <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="end_date" 
                                   value="<?= $isEdit && $survey['end_date'] ? $survey['end_date'] : date('Y-m-d', strtotime('+7 days')) ?>" 
                                   required
                                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Durum
                            </label>
                            <select name="status"
                                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                                <option value="draft" <?= ($isEdit && $survey['status'] === 'draft') ? 'selected' : '' ?>>Taslak</option>
                                <option value="active" <?= ($isEdit && $survey['status'] === 'active') ? 'selected' : '' ?>>Aktif</option>
                                <option value="closed" <?= ($isEdit && $survey['status'] === 'closed') ? 'selected' : '' ?>>Kapalı</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seçenekler -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-cog mr-2 text-primary-600"></i>
                        Anket Seçenekleri
                    </h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_anonymous" value="1" 
                                   <?= ($isEdit && ($survey['is_anonymous'] ?? 0) == 1) ? 'checked' : '' ?>
                                   class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="ml-3 text-gray-700 dark:text-gray-300">Anonim anket (kişi bilgileri gizli)</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="allow_multiple" value="1" 
                                   <?= ($isEdit && ($survey['allow_multiple'] ?? 0) == 1) ? 'checked' : '' ?>
                                   class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="ml-3 text-gray-700 dark:text-gray-300">Çoklu cevap izin ver</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Sorular -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center justify-between">
                        <span><i class="fas fa-question-circle mr-2 text-primary-600"></i>Anket Soruları</span>
                        <button type="button" onclick="addQuestion()" class="text-sm px-3 py-1 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">
                            <i class="fas fa-plus mr-1"></i>Soru Ekle
                        </button>
                    </h2>
                </div>
                <div class="p-6">
                    <div id="questions-container" class="space-y-4">
                        <?php if (!empty($questions)): ?>
                            <?php foreach ($questions as $index => $question): ?>
                                <div class="question-item p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="flex items-start justify-between mb-3">
                                        <h3 class="font-semibold text-gray-900 dark:text-white">Soru <?= $index + 1 ?></h3>
                                        <button type="button" onclick="removeQuestion(this)" class="text-red-600 hover:text-red-700">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="questions[<?= $index ?>][question_id]" value="<?= $question['id'] ?? '' ?>">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                                        <div class="md:col-span-2">
                                            <input type="text" name="questions[<?= $index ?>][question_text]" 
                                                   value="<?= htmlspecialchars($question['question_text'] ?? '') ?>"
                                                   placeholder="Soru metni" required
                                                   class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg">
                                        </div>
                                        <div>
                                            <select name="questions[<?= $index ?>][question_type]" required
                                                    class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                                                    onchange="toggleOptions(this)">
                                                <option value="single" <?= ($question['question_type'] ?? 'single') === 'single' ? 'selected' : '' ?>>Tek Seçim</option>
                                                <option value="multiple" <?= ($question['question_type'] ?? '') === 'multiple' ? 'selected' : '' ?>>Çoklu Seçim</option>
                                                <option value="text" <?= ($question['question_type'] ?? '') === 'text' ? 'selected' : '' ?>>Yazı</option>
                                                <option value="rating" <?= ($question['question_type'] ?? '') === 'rating' ? 'selected' : '' ?>>Derecelendirme</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="flex items-center">
                                                <input type="checkbox" name="questions[<?= $index ?>][is_required]" value="1" 
                                                       <?= ($question['is_required'] ?? 1) ? 'checked' : '' ?>
                                                       class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Zorunlu</span>
                                            </label>
                                        </div>
                                    </div>
                                    <?php 
                                    $displayOptions = in_array($question['question_type'] ?? '', ['single', 'multiple', 'rating']);
                                    $currentOptions = !empty($question['options']) ? json_decode($question['options'], true) : [];
                                    ?>
                                    <div class="options-field" style="display: <?= $displayOptions ? 'block' : 'none' ?>;">
                                        <textarea name="questions[<?= $index ?>][options]" rows="3"
                                                  placeholder="Her satıra bir seçenek yazın (sadece tek seçim, çoklu seçim ve derecelendirme için)"
                                                  class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"><?= !empty($currentOptions) ? implode("\n", $currentOptions) : '' ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">
                        <i class="fas fa-info-circle mr-1"></i>
                        Henüz soru eklenmemiş. "Soru Ekle" butonuna tıklayarak soruları ekleyebilirsiniz.
                    </p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end space-x-3">
                <a href="<?= $isEdit ? base_url('/surveys/' . $survey['id']) : base_url('/surveys') ?>" 
                   class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    İptal
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors font-medium"
                        style="color: white !important; background: #4f46e5 !important;">
                    <i class="fas fa-save mr-2" style="color: white !important;"></i>
                    <span style="color: white !important;"><?= $isEdit ? 'Güncelle' : 'Oluştur' ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let questionIndex = <?= !empty($questions) ? count($questions) : 0 ?>;

function addQuestion() {
    const container = document.getElementById('questions-container');
    const questionHtml = `
        <div class="question-item p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900/30">
            <div class="flex items-start justify-between mb-3">
                <h3 class="font-semibold text-gray-900 dark:text-white">Yeni Soru</h3>
                <button type="button" onclick="removeQuestion(this)" class="text-red-600 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                <div class="md:col-span-2">
                    <input type="text" name="questions[${questionIndex}][question_text]" 
                           placeholder="Soru metni" required
                           class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg">
                </div>
                <div>
                    <select name="questions[${questionIndex}][question_type]" required
                            class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg"
                            onchange="toggleOptions(this)">
                        <option value="single">Tek Seçim</option>
                        <option value="multiple">Çoklu Seçim</option>
                        <option value="text">Yazı</option>
                        <option value="rating">Derecelendirme</option>
                    </select>
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="questions[${questionIndex}][is_required]" value="1" 
                               checked
                               class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Zorunlu</span>
                    </label>
                </div>
            </div>
            <div class="options-field" style="display: block;">
                <textarea name="questions[${questionIndex}][options]" rows="3"
                          placeholder="Her satıra bir seçenek yazın (sadece tek seçim, çoklu seçim ve derecelendirme için)"
                          class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg"></textarea>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', questionHtml);
    questionIndex++;
}

function removeQuestion(button) {
    button.closest('.question-item').remove();
}

function toggleOptions(select) {
    const questionItem = select.closest('.question-item');
    const optionsField = questionItem.querySelector('.options-field');
    const questionType = select.value;
    
    if (questionType === 'single' || questionType === 'multiple' || questionType === 'rating') {
        optionsField.style.display = 'block';
    } else {
        optionsField.style.display = 'none';
    }
}
</script>

