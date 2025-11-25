<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-calendar-alt mr-3 text-primary-600"></i>
                <?= htmlspecialchars($meeting['title'] ?? 'Toplantı Detay') ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                <?= htmlspecialchars($meeting['building_name'] ?? '-') ?> - 
                <?= $meeting['meeting_date'] ? date('d.m.Y H:i', strtotime($meeting['meeting_date'])) : '-' ?>
            </p>
        </div>
        <div class="flex space-x-3">
            <?php if (Auth::hasRole('admin') || Auth::hasRole('manager')): ?>
                <a href="<?= base_url('/meetings/' . $meeting['id'] . '/edit') ?>" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-edit mr-2"></i>Düzenle
                </a>
                <a href="<?= base_url('/meetings/' . $meeting['id'] . '/attendance') ?>" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-users mr-2"></i>Katılım Takibi
                </a>
            <?php endif; ?>
            <a href="<?= base_url('/meetings') ?>" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Geri
            </a>
        </div>
    </div>

    <!-- Status & Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Durum</p>
                    <p class="text-2xl font-bold mt-1">
                        <?php
                        $status = $meeting['status'] ?? 'scheduled';
                        $statusColors = [
                            'scheduled' => 'text-blue-600',
                            'completed' => 'text-green-600',
                            'cancelled' => 'text-red-600'
                        ];
                        $statusTexts = [
                            'scheduled' => 'Planlandı',
                            'completed' => 'Tamamlandı',
                            'cancelled' => 'İptal Edildi'
                        ];
                        ?>
                        <span class="<?= $statusColors[$status] ?? 'text-gray-600' ?>">
                            <?= $statusTexts[$status] ?? 'Planlandı' ?>
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
                    <p class="text-sm text-gray-600 dark:text-gray-400">Katılımcı Sayısı</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        <?= $meeting['attendance_count'] ?? 0 ?>
                    </p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                    <i class="fas fa-users text-green-600 dark:text-green-300 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Toplantı Tipi</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                        <?php
                        $types = [
                            'regular' => 'Olağan',
                            'extraordinary' => 'Olağanüstü',
                            'board' => 'Yönetim Kurulu'
                        ];
                        echo htmlspecialchars($types[$meeting['meeting_type']] ?? $meeting['meeting_type']);
                        ?>
                    </p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <i class="fas fa-calendar-check text-purple-600 dark:text-purple-300 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Yeter Sayı</p>
                    <p class="text-lg font-bold <?= ($meeting['quorum_reached'] ?? 0) ? 'text-green-600' : 'text-yellow-600' ?> mt-1">
                        <?= ($meeting['quorum_reached'] ?? 0) ? 'Sağlandı' : 'Sağlanmadı' ?>
                    </p>
                </div>
                <div class="p-3 <?= ($meeting['quorum_reached'] ?? 0) ? 'bg-green-100 dark:bg-green-900' : 'bg-yellow-100 dark:bg-yellow-900' ?> rounded-lg">
                    <i class="fas fa-<?= ($meeting['quorum_reached'] ?? 0) ? 'check' : 'exclamation' ?>-circle text-<?= ($meeting['quorum_reached'] ?? 0) ? 'green' : 'yellow' ?>-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Meeting Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Basic Info -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-info-circle mr-2 text-primary-600"></i>Toplantı Bilgileri
            </h2>
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">Tarih & Saat</label>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?= $meeting['meeting_date'] ? date('d.m.Y H:i', strtotime($meeting['meeting_date'])) : '-' ?>
                    </p>
                </div>
                <?php if (!empty($meeting['location'])): ?>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">Yer</label>
                        <p class="font-medium text-gray-900 dark:text-white">
                            <i class="fas fa-map-marker-alt mr-2 text-primary-600"></i>
                            <?= e($meeting['location']) ?>
                        </p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($meeting['description'])): ?>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-400">Açıklama</label>
                        <p class="text-gray-900 dark:text-white mt-1"><?= nl2br(e($meeting['description'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Agenda -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-list mr-2 text-primary-600"></i>Gündem
            </h2>
            <?php
            $agenda = [];
            if (!empty($meeting['agenda'])) {
                $agenda = json_decode($meeting['agenda'], true) ?: [];
            }
            ?>
            <?php if (!empty($agenda)): ?>
                <ol class="space-y-2">
                    <?php foreach ($agenda as $index => $item): ?>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-8 h-8 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-300 rounded-full flex items-center justify-center font-semibold mr-3">
                                <?= $index + 1 ?>
                            </span>
                            <span class="text-gray-900 dark:text-white pt-1"><?= e($item) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="text-gray-500 dark:text-gray-400">Gündem maddesi eklenmemiş</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Minutes -->
    <?php if (!empty($meeting['minutes'])): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-file-alt mr-2 text-primary-600"></i>Toplantı Tutanağı
            </h2>
            <div class="prose dark:prose-invert max-w-none">
                <?= nl2br(e($meeting['minutes'])) ?>
            </div>
            <?php if (!empty($meeting['minutes_document_path'])): ?>
                <div class="mt-4">
                    <a href="<?= base_url('/documents/' . $meeting['minutes_document_id'] . '/download') ?>" 
                       class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">
                        <i class="fas fa-download mr-2"></i>Tutanağı İndir
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif (Auth::hasRole('admin') || Auth::hasRole('manager')): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-file-alt mr-2 text-primary-600"></i>Toplantı Tutanağı
            </h2>
            <form method="POST" action="<?= base_url('/meetings/' . $meeting['id'] . '/minutes') ?>">
                <?= CSRF::field() ?>
                <textarea name="minutes" rows="10" required
                          placeholder="Toplantı tutanağını buraya yazın..."
                          class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"></textarea>
                <div class="mt-4 flex justify-end">
                    <button type="submit" 
                            class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium">
                        <i class="fas fa-save mr-2"></i>Tutanağı Kaydet
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Attendance Summary -->
    <?php if (!empty($attendees)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-users mr-2 text-primary-600"></i>Katılımcılar
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Daire</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Katılımcı</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Sahiplik</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Katılım</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Oylama Ağırlığı</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($attendees as $attendee): ?>
                            <tr>
                                <td class="px-4 py-3"><?= htmlspecialchars($attendee['unit_number'] ?? '-') ?></td>
                                <td class="px-4 py-3 font-medium"><?= e($attendee['attendee_name']) ?></td>
                                <td class="px-4 py-3">
                                    <?= ($attendee['is_owner'] ?? 0) ? 'Sahip' : 'Kiracı' ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($attendee['attended'] ?? 0): ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            <i class="fas fa-check mr-1"></i>Katıldı
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            Katılmadı
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3"><?= number_format($attendee['vote_weight'] ?? 1.0, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

