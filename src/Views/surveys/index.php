<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-poll mr-3 text-primary-600"></i>
                Anket Yönetimi
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Bina sakinleri için anketler ve oylamalar</p>
        </div>
        <a href="<?= base_url('/surveys/create') ?>" 
           class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
            <i class="fas fa-plus mr-2"></i>Yeni Anket Oluştur
        </a>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <form method="GET" action="<?= base_url('/surveys') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bina</label>
                <select name="building_id" 
                        class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <?php foreach ($buildings as $bld): ?>
                        <option value="<?= $bld['id'] ?>" <?= (isset($filters['building_id']) && $filters['building_id'] == $bld['id']) ? 'selected' : '' ?>>
                            <?= e($bld['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Durum</label>
                <select name="status" 
                        class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <option value="draft" <?= (isset($filters['status']) && $filters['status'] === 'draft') ? 'selected' : '' ?>>Taslak</option>
                    <option value="active" <?= (isset($filters['status']) && $filters['status'] === 'active') ? 'selected' : '' ?>>Aktif</option>
                    <option value="closed" <?= (isset($filters['status']) && $filters['status'] === 'closed') ? 'selected' : '' ?>>Kapalı</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Anket Tipi</label>
                <select name="survey_type" 
                        class="w-full px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Tümü</option>
                    <option value="poll" <?= (isset($filters['survey_type']) && $filters['survey_type'] === 'poll') ? 'selected' : '' ?>>Anket</option>
                    <option value="vote" <?= (isset($filters['survey_type']) && $filters['survey_type'] === 'vote') ? 'selected' : '' ?>>Oylama</option>
                    <option value="feedback" <?= (isset($filters['survey_type']) && $filters['survey_type'] === 'feedback') ? 'selected' : '' ?>>Geri Bildirim</option>
                    <option value="complaint" <?= (isset($filters['survey_type']) && $filters['survey_type'] === 'complaint') ? 'selected' : '' ?>>Şikayet</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-filter mr-2"></i>Filtrele
                </button>
            </div>
        </form>
    </div>

    <!-- Surveys List -->
    <?php if (!empty($surveys)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <?php foreach ($surveys as $survey): 
                $isActive = ($survey['status'] === 'active');
                $isPast = ($survey['end_date'] && strtotime($survey['end_date']) < time());
                $responseCount = $survey['response_count'] ?? 0;
            ?>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden 
                            hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <?php
                                    $typeColors = [
                                        'poll' => 'bg-blue-100 text-blue-800',
                                        'vote' => 'bg-green-100 text-green-800',
                                        'feedback' => 'bg-purple-100 text-purple-800',
                                        'complaint' => 'bg-red-100 text-red-800'
                                    ];
                                    $typeTexts = [
                                        'poll' => 'Anket',
                                        'vote' => 'Oylama',
                                        'feedback' => 'Geri Bildirim',
                                        'complaint' => 'Şikayet'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $typeColors[$survey['survey_type']] ?? 'bg-gray-100 text-gray-800' ?>">
                                        <?= $typeTexts[$survey['survey_type']] ?? $survey['survey_type'] ?>
                                    </span>
                                    <?php if (!$isActive): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            <?= $survey['status'] === 'closed' ? 'Kapalı' : 'Taslak' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                                    <?= e($survey['title']) ?>
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                    <?= htmlspecialchars($survey['description'] ?? '') ?>
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2 mb-4 text-sm">
                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-building mr-2 text-primary-600"></i>
                                <?= htmlspecialchars($survey['building_name'] ?? '-') ?>
                            </div>
                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-users mr-2 text-primary-600"></i>
                                <?= $responseCount ?> cevap
                            </div>
                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-calendar mr-2 text-primary-600"></i>
                                <?= date('d.m.Y', strtotime($survey['start_date'])) ?> - 
                                <?= date('d.m.Y', strtotime($survey['end_date'])) ?>
                            </div>
                        </div>

                        <div class="flex space-x-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="<?= base_url('/surveys/' . $survey['id'] . '/results') ?>" 
                               class="flex-1 px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm rounded-lg transition-colors text-center">
                                <i class="fas fa-chart-bar mr-1"></i>Sonuçlar
                            </a>
                            <a href="<?= base_url('/surveys/' . $survey['id']) ?>" 
                               class="px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg transition-colors">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-12 text-center">
            <i class="fas fa-poll text-6xl text-gray-400 mb-4"></i>
            <p class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Henüz Anket Yok</p>
            <p class="text-gray-600 dark:text-gray-400 mb-6">İlk anketi oluşturmak için butona tıklayın</p>
            <a href="<?= base_url('/surveys/create') ?>" 
               class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Yeni Anket Oluştur
            </a>
        </div>
    <?php endif; ?>
</div>

