<?php
$meetings = $meetings ?? [];
?>

<div class="max-w-4xl mx-auto px-3 sm:px-4 lg:px-6 py-6 space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500 mb-1">
                <a href="<?= base_url('/resident/dashboard') ?>" class="text-primary-600 hover:text-primary-500">
                    &larr; Dashboard'a dön
                </a>
            </p>
            <h1 class="fluid-h1 font-semibold text-slate-900 dark:text-white">Yaklaşan Toplantılar</h1>
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Site yönetimi tarafından planlanan toplantıların tarih ve detaylarını buradan takip edebilirsiniz.
            </p>
        </div>
    </div>

    <?php if (empty($meetings)): ?>
        <div class="bg-white/90 dark:bg-slate-900/80 border border-white/20 dark:border-slate-800 rounded-3xl shadow-soft px-6 py-8">
            <?= View::partial('partials/empty-state', [
                'title' => 'Planlanmış toplantı yok',
                'description' => 'Yeni bir toplantı planlandığında burada görebilirsiniz.',
                'icon' => 'fa-solid fa-handshake'
            ]) ?>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($meetings as $meeting): ?>
                <article class="bg-white/90 dark:bg-slate-900/80 border border-white/20 dark:border-slate-800 rounded-3xl shadow-soft px-6 py-6 space-y-4" role="article" aria-labelledby="meeting-<?= $meeting['id'] ?? $meeting['title'] ?>">
                    <header class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 id="meeting-<?= $meeting['id'] ?? $meeting['title'] ?>" class="text-lg font-semibold text-slate-900 dark:text-white">
                                <?= htmlspecialchars($meeting['title'] ?? 'Toplantı') ?>
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <?= Utils::formatDateTime($meeting['meeting_date'] ?? null) ?>
                                <?php if (!empty($meeting['location'])): ?>
                                    · <?= e($meeting['location']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-700"
                              role="status"
                              aria-label="Toplantı durumu: <?= htmlspecialchars(strtoupper($meeting['status'] ?? 'scheduled')) ?>">
                            <i class="fas fa-users"></i>
                            <?= htmlspecialchars(strtoupper($meeting['status'] ?? 'scheduled')) ?>
                        </span>
                    </header>
                    <?php if (!empty($meeting['description'])): ?>
                        <div class="text-sm text-slate-700 dark:text-slate-200 leading-relaxed">
                            <?= nl2br(e($meeting['description'])) ?>
                        </div>
                    <?php endif; ?>
                    <footer class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                        <?php if (!empty($meeting['organizer_name'])): ?>
                            <span class="inline-flex items-center gap-2">
                                <i class="fas fa-user-tie"></i>
                                <?= e($meeting['organizer_name']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($meeting['meeting_type'])): ?>
                            <span class="inline-flex items-center gap-2">
                                <i class="fas fa-layer-group"></i>
                                <?= htmlspecialchars(ucfirst($meeting['meeting_type'])) ?>
                            </span>
                        <?php endif; ?>
                    </footer>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

