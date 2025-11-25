<?php
$request = $request ?? [];
$timeline = $timeline ?? [];
$unit = $unit ?? [];
$building = $building ?? [];

$status = $request['status'] ?? 'open';
$priority = $request['priority'] ?? 'normal';

$statusMap = [
    'open' => ['label' => 'Açık', 'class' => 'bg-amber-100 text-amber-700'],
    'in_progress' => ['label' => 'Devam ediyor', 'class' => 'bg-sky-100 text-sky-700'],
    'resolved' => ['label' => 'Çözüldü', 'class' => 'bg-emerald-100 text-emerald-700'],
    'closed' => ['label' => 'Kapatıldı', 'class' => 'bg-slate-200 text-slate-700'],
    'rejected' => ['label' => 'Reddedildi', 'class' => 'bg-rose-100 text-rose-700'],
];

$priorityMap = [
    'urgent' => ['label' => 'Acil', 'class' => 'bg-red-200 text-red-800'],
    'high' => ['label' => 'Yüksek', 'class' => 'bg-rose-100 text-rose-700'],
    'normal' => ['label' => 'Normal', 'class' => 'bg-slate-100 text-slate-700'],
    'low' => ['label' => 'Düşük', 'class' => 'bg-emerald-100 text-emerald-700'],
];

$statusChip = $statusMap[$status] ?? $statusMap['open'];
$priorityChip = $priorityMap[$priority] ?? $priorityMap['normal'];

$createdAt = $request['created_at'] ?? null;
$updatedAt = $request['updated_at'] ?? $createdAt;
$resolvedAt = $request['resolved_at'] ?? null;
$isClosed = in_array($status, ['resolved', 'closed'], true);
$elapsedLabel = $isClosed ? 'Çözüm süresi' : 'Açık kalma süresi';
$elapsedValue = $isClosed
    ? Utils::diffForHumans($createdAt, $resolvedAt ?: $updatedAt, 2, false)
    : Utils::diffForHumans($createdAt, null, 1, true);
$lastUpdateValue = Utils::diffForHumans($updatedAt, null, 1, true);
?>

<div class="max-w-4xl mx-auto px-3 sm:px-4 lg:px-6 py-6 space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500 mb-1">
                <a href="<?= base_url('/resident/requests') ?>" class="text-primary-600 hover:text-primary-500">
                    &larr; Taleplerime dön
                </a>
            </p>
            <h1 class="fluid-h1 font-semibold text-slate-900 dark:text-white">
                <?= htmlspecialchars($request['subject'] ?? 'Talep Detayı') ?>
            </h1>
            <p class="text-sm text-slate-600 dark:text-slate-300">
                <?= htmlspecialchars($building['name'] ?? 'Bina') ?> · <?= htmlspecialchars($unit['unit_number'] ?? 'Daire') ?>
            </p>
        </div>
        <div class="flex flex-col items-end gap-2">
            <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                <i class="fas fa-hourglass-half"></i>
                <?= e($elapsedLabel) ?>: <?= e($elapsedValue) ?>
            </span>
            <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                <i class="fas fa-rotate-right"></i>
                Son güncelleme: <?= e($lastUpdateValue) ?>
            </span>
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold <?= $statusChip['class'] ?>">
                <i class="fas fa-circle-info"></i>
                <?= $statusChip['label'] ?>
            </span>
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold <?= $priorityChip['class'] ?>">
                <i class="fas fa-bolt"></i>
                <?= $priorityChip['label'] ?>
            </span>
        </div>
    </div>

    <div class="bg-white/90 dark:bg-slate-900/80 border border-white/20 dark:border-slate-800 rounded-3xl shadow-soft px-6 py-6 space-y-4">
        <header class="flex flex-wrap items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
            <span class="inline-flex items-center gap-2">
                <i class="fas fa-calendar"></i>
                <?= Utils::formatDateTime($createdAt) ?>
            </span>
            <span class="inline-flex items-center gap-2">
                <i class="fas fa-layer-group"></i>
                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $request['request_type'] ?? 'Diğer'))) ?>
            </span>
            <?php if (!empty($request['category'])): ?>
                <span class="inline-flex items-center gap-2">
                    <i class="fas fa-tag"></i>
                    <?= e($request['category']) ?>
                </span>
            <?php endif; ?>
        </header>
        <div class="text-sm text-slate-700 dark:text-slate-200 leading-relaxed">
            <?= nl2br(htmlspecialchars($request['description'] ?? '')) ?>
        </div>
        <?php if (!empty($request['response'])): ?>
            <div class="rounded-2xl bg-sky-50 dark:bg-sky-900/20 border border-sky-200/70 dark:border-sky-800/60 px-4 py-4">
                <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2 mb-2">
                    <i class="fas fa-reply"></i> Son Yönetici Yanıtı
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    <?= nl2br(e($request['response'])) ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white/90 dark:bg-slate-900/80 border border-white/20 dark:border-slate-800 rounded-3xl shadow-soft px-6 py-6">
        <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-4">
            Süreç Zaman Çizelgesi
        </h2>
        <?php if (empty($timeline)): ?>
            <p class="text-sm text-slate-500 dark:text-slate-400">Bu talep için kayıtlı bir aksiyon bulunmuyor.</p>
        <?php else: ?>
            <?php $previousTimestamp = null; ?>
            <ol class="relative border-l border-slate-200 dark:border-slate-700 space-y-4">
                <?php foreach ($timeline as $item): ?>
                    <?php
                        $timestamp = $item['timestamp'] ?? null;
                        $stepElapsed = ($previousTimestamp && $timestamp)
                            ? Utils::diffForHumans($previousTimestamp, $timestamp, 2, false)
                            : null;
                        $previousTimestamp = $timestamp ?? $previousTimestamp;
                    ?>
                    <li class="ml-4">
                        <div class="absolute w-3 h-3 bg-primary-500 rounded-full mt-1.5 -left-1.5 border border-white dark:border-slate-900"></div>
                        <time class="block text-xs text-slate-500 dark:text-slate-400">
                            <?= Utils::formatDateTime($timestamp) ?>
                        </time>
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                            <?= htmlspecialchars($item['label'] ?? '') ?>
                        </h3>
                        <?php if (!empty($item['description'])): ?>
                            <p class="text-sm text-slate-600 dark:text-slate-300">
                                <?= nl2br(e($item['description'])) ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($stepElapsed): ?>
                            <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                                Bu adıma kadar geçen süre: <?= e($stepElapsed) ?>
                            </p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</div>

