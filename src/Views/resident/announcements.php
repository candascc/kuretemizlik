<?php
$announcements = $announcements ?? [];
?>

<div class="max-w-4xl mx-auto px-3 sm:px-4 lg:px-6 py-6 space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500 mb-1">
                <a href="<?= base_url('/resident/dashboard') ?>" class="text-primary-600 hover:text-primary-500">
                    &larr; Dashboard'a dön
                </a>
            </p>
            <h1 class="fluid-h1 font-semibold text-slate-900 dark:text-white">Duyurular</h1>
            <p class="text-sm text-slate-600 dark:text-slate-300">
                Yönetim tarafından paylaşılan güncel duyuruları buradan takip edebilirsiniz.
            </p>
        </div>
    </div>

    <?php if (empty($announcements)): ?>
        <div class="bg-white/90 dark:bg-slate-900/80 border border-white/20 dark:border-slate-800 rounded-3xl shadow-soft px-6 py-8">
            <?= View::partial('partials/empty-state', [
                'title' => 'Duyuru bulunmuyor',
                'description' => 'Yeni bir duyuru yayınlandığında burada görünecektir.',
                'icon' => 'fa-solid fa-bullhorn'
            ]) ?>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($announcements as $announcement): ?>
                <?php
                    $priority = $announcement['priority'] ?? 'normal';
                    $priorityStyles = [
                        'high' => 'bg-rose-100 text-rose-700',
                        'urgent' => 'bg-red-200 text-red-800',
                        'low' => 'bg-emerald-100 text-emerald-700',
                        'normal' => 'bg-slate-100 text-slate-700',
                    ];
                    $chipClass = $priorityStyles[$priority] ?? $priorityStyles['normal'];
                ?>
                <article class="bg-white/90 dark:bg-slate-900/80 border border-white/20 dark:border-slate-800 rounded-3xl shadow-soft px-6 py-6 space-y-3" role="article" aria-labelledby="announcement-<?= $announcement['id'] ?? $announcement['title'] ?>">
                    <header class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 id="announcement-<?= $announcement['id'] ?? $announcement['title'] ?>" class="text-lg font-semibold text-slate-900 dark:text-white">
                                <?= htmlspecialchars($announcement['title'] ?? 'Duyuru') ?>
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Yayın tarihi: <?= Utils::formatDate($announcement['publish_date'] ?? null) ?>
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold <?= $chipClass ?>"
                              role="status"
                              aria-label="Öncelik: <?= htmlspecialchars(ucfirst($priority)) ?>">
                            <i class="fas fa-signal"></i>
                            <?= htmlspecialchars(ucfirst($priority)) ?>
                        </span>
                    </header>
                    <div class="text-sm text-slate-700 dark:text-slate-200 leading-relaxed">
                        <?= nl2br(htmlspecialchars(Utils::truncateUtf8($announcement['content'] ?? '', 240))) ?>
                    </div>
                    <?php if (!empty($announcement['attachments'])): ?>
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-sm">
                            <p class="font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                <i class="fas fa-paperclip mr-2"></i>Ekler
                            </p>
                            <ul class="space-y-2">
                                <?php foreach ((array)$announcement['attachments'] as $attachment): ?>
                                    <li>
                                        <a href="<?= htmlspecialchars($attachment['url'] ?? '#') ?>"
                                           class="text-primary-600 hover:text-primary-500" target="_blank" rel="noopener">
                                            <?= htmlspecialchars($attachment['name'] ?? 'Dosya') ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

