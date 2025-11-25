<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-poll mr-3 text-primary-600"></i>
                <?= htmlspecialchars($survey['title'] ?? 'Anket Detay') ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                <?= htmlspecialchars($survey['building_name'] ?? '-') ?> - 
                <?= $survey['start_date'] ? date('d.m.Y', strtotime($survey['start_date'])) : '-' ?> / 
                <?= $survey['end_date'] ? date('d.m.Y', strtotime($survey['end_date'])) : '-' ?>
            </p>
        </div>
        <div class="flex space-x-3">
            <?php if (Auth::hasRole('admin') || Auth::hasRole('manager')): ?>
                <a href="<?= base_url('/surveys/' . $survey['id'] . '/edit') ?>" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-edit mr-2"></i>Düzenle
                </a>
                <?php if ($survey['status'] === 'draft'): ?>
                    <form method="POST" action="<?= base_url('/surveys/publish/' . $survey['id']) ?>" class="inline">
                        <?= CSRF::field() ?>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-paper-plane mr-2"></i>Yayınla
                        </button>
                    </form>
                <?php endif; ?>
                <?php if ($survey['status'] === 'active'): ?>
                    <form method="POST" action="<?= base_url('/surveys/close/' . $survey['id']) ?>" class="inline">
                        <?= CSRF::field() ?>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-lock mr-2"></i>Kapat
                        </button>
                    </form>
                <?php endif; ?>
                <form method="POST" action="<?= base_url('/surveys/delete/' . $survey['id']) ?>" class="inline" 
                      onsubmit="return confirm('Bu anketi silmek istediğinizden emin misiniz?');">
                    <?= CSRF::field() ?>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-trash mr-2"></i>Sil
                    </button>
                </form>
            <?php endif; ?>
            <a href="<?= base_url('/surveys') ?>" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Geri
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/flash.php'; ?>

    <!-- Status & Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Durum</p>
                    <p class="text-2xl font-bold mt-1">
                        <?php
                        $status = $survey['status'] ?? 'draft';
                        $statusColors = [
                            'draft' => 'text-gray-600',
                            'active' => 'text-green-600',
                            'closed' => 'text-red-600'
                        ];
                        $statusTexts = [
                            'draft' => 'Taslak',
                            'active' => 'Aktif',
                            'closed' => 'Kapalı'
                        ];
                        ?>
                        <span class="<?= $statusColors[$status] ?? 'text-gray-600' ?>">
                            <?= $statusTexts[$status] ?? 'Taslak' ?>
                        </span>
                    </p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <i class="fas fa-info-circle text-blue-600 dark:text-blue-300 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Anket Tipi</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                        <?php
                        $types = [
                            'poll' => 'Anket',
                            'vote' => 'Oylama',
                            'feedback' => 'Geri Bildirim',
                            'complaint' => 'Şikayet'
                        ];
                        echo htmlspecialchars($types[$survey['survey_type']] ?? $survey['survey_type']);
                        ?>
                    </p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <i class="fas fa-poll text-purple-600 dark:text-purple-300 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Soru Sayısı</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        <?= $survey['question_count'] ?? 0 ?>
                    </p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <i class="fas fa-question-circle text-green-600 dark:text-green-300 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Cevap Sayısı</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        <?= $survey['response_count'] ?? 0 ?>
                    </p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                    <i class="fas fa-users text-yellow-600 dark:text-yellow-300 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Survey Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Basic Info -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-info-circle mr-2 text-primary-600"></i>Anket Bilgileri
            </h2>
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Açıklama</label>
                    <p class="text-gray-900 dark:text-white mt-1"><?= nl2br(htmlspecialchars($survey['description'] ?? 'Açıklama yok')) ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Özellikler</label>
                    <div class="mt-2 space-y-1">
                        <?php if ($survey['is_anonymous'] ?? 0): ?>
                            <span class="inline-block px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                <i class="fas fa-user-secret mr-1"></i>Anonim
                            </span>
                        <?php endif; ?>
                        <?php if ($survey['allow_multiple'] ?? 0): ?>
                            <span class="inline-block px-3 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                <i class="fas fa-check-double mr-1"></i>Çoklu Cevap
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-calendar-alt mr-2 text-primary-600"></i>Zaman Çizelgesi
            </h2>
            <div class="space-y-4">
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Başlangıç</label>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <i class="fas fa-play-circle mr-2 text-green-600"></i>
                        <?= $survey['start_date'] ? date('d.m.Y', strtotime($survey['start_date'])) : '-' ?>
                    </p>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Bitiş</label>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <i class="fas fa-stop-circle mr-2 text-red-600"></i>
                        <?= $survey['end_date'] ? date('d.m.Y', strtotime($survey['end_date'])) : '-' ?>
                    </p>
                </div>
                <?php 
                $now = time();
                $endDate = strtotime($survey['end_date'] ?? 'now');
                $daysRemaining = max(0, floor(($endDate - $now) / 86400));
                ?>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Kalan Süre</label>
                    <p class="font-medium <?= $daysRemaining > 3 ? 'text-gray-900 dark:text-white' : ($daysRemaining > 0 ? 'text-orange-600' : 'text-red-600') ?>">
                        <i class="fas fa-clock mr-2"></i>
                        <?= $daysRemaining > 0 ? $daysRemaining . ' gün' : 'Süresi dolmuş' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Questions -->
    <?php if (!empty($questions)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-question-circle mr-2 text-primary-600"></i>Anket Soruları
            </h2>
            <div class="space-y-4">
                <?php foreach ($questions as $index => $question): ?>
                    <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                <?= $index + 1 ?>. <?= e($question['question_text']) ?>
                                <?php if ($question['is_required'] ?? 0): ?>
                                    <span class="text-red-600 ml-1">*</span>
                                <?php endif; ?>
                            </h3>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                <?php
                                $qTypes = [
                                    'single' => 'Tek Seçim',
                                    'multiple' => 'Çoklu Seçim',
                                    'text' => 'Yazı',
                                    'rating' => 'Derecelendirme'
                                ];
                                echo $qTypes[$question['question_type']] ?? $question['question_type'];
                                ?>
                            </span>
                        </div>
                        <?php if (!empty($question['options'])): ?>
                            <?php 
                            $options = json_decode($question['options'], true);
                            if (is_array($options) && !empty($options)):
                            ?>
                                <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 ml-4 mt-2">
                                    <?php foreach ($options as $option): ?>
                                        <li><?= e($option) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-12 text-center">
            <i class="fas fa-question-circle text-6xl text-gray-400 mb-4"></i>
            <p class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Henüz Soru Yok</p>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Bu anket için henüz soru eklenmemiş</p>
        </div>
    <?php endif; ?>

    <!-- Responses Summary -->
    <?php if (!empty($responses)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-chart-bar mr-2 text-primary-600"></i>Cevap Özeti
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Daire</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Katılımcı</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tarih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($responses as $response): ?>
                            <tr>
                                <td class="px-4 py-3"><?= htmlspecialchars($response['unit_id'] ?? '-') ?></td>
                                <td class="px-4 py-3 font-medium"><?= htmlspecialchars($response['respondent_name'] ?? 'Anonim') ?></td>
                                <td class="px-4 py-3"><?= $response['created_at'] ? date('d.m.Y H:i', strtotime($response['created_at'])) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

