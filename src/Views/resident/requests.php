<?php
    $pagination = $pagination ?? ['total' => 0, 'per_page' => 20, 'current_page' => 1, 'total_pages' => 1, 'offset' => 0];
    $start = $pagination['total'] > 0 ? ($pagination['offset'] + 1) : 0;
    $end = $pagination['total'] > 0 ? min($pagination['offset'] + $pagination['per_page'], $pagination['total']) : 0;

    $activeStatus = $activeStatus ?? ($filters['status'] ?? '');
    $statusSummary = $statusSummary ?? ['total' => count($requests ?? []), 'by_status' => []];
    $statusCounts = array_merge([
        'open' => 0,
        'in_progress' => 0,
        'resolved' => 0,
        'closed' => 0,
    ], $statusSummary['by_status'] ?? []);
    $totalCount = $statusSummary['total'] ?? array_sum($statusCounts);

    $segmentDefinitions = [
        '' => [
            'label' => 'Tümü',
            'description' => 'Tüm talepler',
            'accent' => 'border-slate-200 dark:border-slate-700',
            'indicator' => 'bg-slate-500',
        ],
        'open' => [
            'label' => 'Açık',
            'description' => 'Yanıt bekleyenler',
            'accent' => 'border-amber-200 dark:border-amber-500/60',
            'indicator' => 'bg-amber-500',
        ],
        'in_progress' => [
            'label' => 'İşlemde',
            'description' => 'Üzerinde çalışılıyor',
            'accent' => 'border-sky-200 dark:border-sky-500/60',
            'indicator' => 'bg-sky-500',
        ],
        'resolved' => [
            'label' => 'Çözüldü',
            'description' => 'Çözüm bekliyor',
            'accent' => 'border-emerald-200 dark:border-emerald-500/60',
            'indicator' => 'bg-emerald-500',
        ],
        'closed' => [
            'label' => 'Kapalı',
            'description' => 'Tamamlanan kayıtlar',
            'accent' => 'border-slate-200 dark:border-slate-600',
            'indicator' => 'bg-slate-500',
        ],
    ];

    $countsBySegment = [
        '' => $totalCount,
        'open' => $statusCounts['open'] ?? 0,
        'in_progress' => $statusCounts['in_progress'] ?? 0,
        'resolved' => $statusCounts['resolved'] ?? 0,
        'closed' => $statusCounts['closed'] ?? 0,
    ];

    $statusLabels = [
        'open' => ['label' => 'Açık', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200', 'dot' => 'bg-amber-400'],
        'in_progress' => ['label' => 'İşlemde', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-200', 'dot' => 'bg-sky-400'],
        'resolved' => ['label' => 'Çözüldü', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200', 'dot' => 'bg-emerald-400'],
        'closed' => ['label' => 'Kapalı', 'class' => 'bg-slate-200 text-slate-800 dark:bg-slate-900/40 dark:text-slate-200', 'dot' => 'bg-slate-400'],
    ];

    $priorityStyles = [
        'urgent' => ['label' => 'Acil', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200', 'icon' => 'fa-fire'],
        'high' => ['label' => 'Yüksek', 'class' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-200', 'icon' => 'fa-triangle-exclamation'],
        'normal' => ['label' => 'Normal', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200', 'icon' => 'fa-gauge'],
        'low' => ['label' => 'Düşük', 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-900/40 dark:text-slate-200', 'icon' => 'fa-feather'],
    ];

    $typeStyles = [
        'maintenance' => ['label' => 'Bakım', 'icon' => 'fa-screwdriver-wrench', 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-900/40 dark:text-slate-300'],
        'complaint' => ['label' => 'Şikayet', 'icon' => 'fa-comment-dots', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200'],
        'suggestion' => ['label' => 'Öneri', 'icon' => 'fa-lightbulb', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200'],
        'question' => ['label' => 'Soru', 'icon' => 'fa-circle-question', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-200'],
        'security' => ['label' => 'Güvenlik', 'icon' => 'fa-shield-halved', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200'],
        'noise' => ['label' => 'Gürültü', 'icon' => 'fa-volume-high', 'class' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-200'],
        'parking' => ['label' => 'Otopark', 'icon' => 'fa-square-parking', 'class' => 'bg-lime-100 text-lime-700 dark:bg-lime-900/40 dark:text-lime-200'],
        'other' => ['label' => 'Diğer', 'icon' => 'fa-ellipsis', 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-900/40 dark:text-slate-200'],
    ];

    $baseQuery = $_GET ?? [];
    unset($baseQuery['page']);
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-gray-900 dark:text-white">Taleplerim</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Site yönetimine ilettiğiniz tüm istekleri buradan takip edebilirsiniz.
            </p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
            <a href="<?= base_url('/resident/create-request') ?>"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                <i class="fas fa-plus"></i>
                Yeni Talep
            </a>
            <a href="<?= base_url('/resident/dashboard') ?>"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                <i class="fas fa-arrow-left"></i>
                Panele Dön
            </a>
        </div>
    </div>

    <!-- Segmented status filter -->
    <section class="overflow-x-auto rounded-2xl border border-gray-200 bg-white/95 p-4 shadow dark:border-gray-700 dark:bg-gray-900">
        <nav class="flex min-w-max gap-3" aria-label="Talep durumu filtreleri">
            <?php foreach ($segmentDefinitions as $key => $definition): ?>
                <?php
                    $isActive = ($key === '' && $activeStatus === '') || ($key !== '' && $activeStatus === $key);
                    $query = $baseQuery;
                    if ($key === '') {
                        unset($query['status']);
                    } else {
                        $query['status'] = $key;
                    }
                    $segmentUrl = base_url('/resident/requests' . (!empty($query) ? '?' . http_build_query($query) : ''));
                    $count = $countsBySegment[$key] ?? 0;
                ?>
                <a href="<?= $segmentUrl ?>"
                   role="tab"
                   aria-selected="<?= $isActive ? 'true' : 'false' ?>"
                   class="group relative flex min-w-[12rem] flex-col gap-1 rounded-xl border <?= $definition['accent'] ?> px-4 py-3 transition <?= $isActive ? 'bg-primary-50 text-primary-700 shadow-sm dark:bg-primary-900/20 dark:text-primary-200' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800' ?>">
                    <span class="flex items-center justify-between text-sm font-semibold">
                        <span class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full <?= $definition['indicator'] ?>"></span>
                            <?= e($definition['label']) ?>
                        </span>
                        <span class="inline-flex h-6 min-w-[2.5rem] items-center justify-center rounded-full bg-gray-100 px-2 text-xs font-semibold text-gray-600 transition group-hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:group-hover:bg-gray-700">
                            <?= $count ?>
                        </span>
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400"><?= e($definition['description']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <!-- Requests List -->
    <section class="space-y-4">
        <?php if (empty($requests)): ?>
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white/70 p-8 text-center shadow-sm dark:border-gray-600 dark:bg-gray-900">
                <i class="fas fa-clipboard-list text-4xl text-gray-400 dark:text-gray-600"></i>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                    Henüz bir talep oluşturmadınız. Yönetimle paylaşmak istediğiniz konu için yeni talep açabilirsiniz.
                </p>
                <a href="<?= base_url('/resident/create-request') ?>"
                   class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <i class="fas fa-plus"></i>
                    İlk Talebinizi Oluşturun
                </a>
            </div>
        <?php else: ?>
            <ul class="grid gap-4" role="list">
                <?php foreach ($requests as $request): ?>
                    <?php
                        $statusKey = $request['status'] ?? 'open';
                        $statusMeta = $statusLabels[$statusKey] ?? $statusLabels['open'];
                        $priorityKey = $request['priority'] ?? 'normal';
                        $priorityMeta = $priorityStyles[$priorityKey] ?? $priorityStyles['normal'];
                        $typeKey = $request['request_type'] ?? 'other';
                        $typeMeta = $typeStyles[$typeKey] ?? [
                            'label' => ucwords(str_replace('_', ' ', $typeKey)),
                            'icon' => 'fa-clipboard-list',
                            'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-900/40 dark:text-slate-200',
                        ];

                        $createdAt = $request['created_at'] ?? null;
                        $updatedAt = $request['updated_at'] ?? $createdAt;
                        $resolvedAt = $request['resolved_at'] ?? null;
                        $isClosed = in_array($statusKey, ['resolved', 'closed'], true);
                        $resolutionTarget = $resolvedAt ?: ($isClosed ? $updatedAt : null);

                        $elapsed = $isClosed
                            ? Utils::diffForHumans($createdAt, $resolutionTarget, 2, false)
                            : Utils::diffForHumans($createdAt, null, 1, true);
                        $elapsedLabel = $isClosed ? 'Çözüm süresi' : 'Açık kalma süresi';
                        $lastUpdate = Utils::diffForHumans($updatedAt, null, 1, true);
                        $descriptionSnippet = Utils::truncateUtf8($request['description'] ?? '', 160);
                    ?>
                    <li>
                        <article class="group relative flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white/95 p-5 shadow-sm transition hover:border-primary-200 hover:shadow-lg dark:border-gray-700 dark:bg-gray-900">
                            <header class="flex flex-wrap items-start gap-3">
                                <div class="flex-1">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($request['subject'] ?? 'Talep') ?>
                                    </h2>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                            <i class="fas <?= $typeMeta['icon'] ?>"></i>
                                            <?= e($typeMeta['label']) ?>
                                        </span>
                                        <?php if (!empty($request['category'])): ?>
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                <i class="fas fa-tag"></i>
                                                <?= e($request['category']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="inline-flex items-center gap-1.5 text-xs">
                                            <i class="fas fa-calendar"></i>
                                            <?= Utils::formatDateTime($createdAt) ?>
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 text-xs">
                                            <i class="fas fa-rotate-right"></i>
                                            <?= e($lastUpdate) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2 text-xs font-semibold">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 <?= $statusMeta['class'] ?>">
                                        <span class="h-2 w-2 rounded-full <?= $statusMeta['dot'] ?>"></span>
                                        <?= $statusMeta['label'] ?>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 <?= $priorityMeta['class'] ?>">
                                        <i class="fas <?= $priorityMeta['icon'] ?>"></i>
                                        <?= $priorityMeta['label'] ?>
                                    </span>
                                </div>
                            </header>

                            <div class="text-sm text-gray-700 dark:text-gray-200">
                                <?= e($descriptionSnippet) ?>
                                <?php if (mb_strlen($request['description'] ?? '') > mb_strlen($descriptionSnippet)): ?>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">...</span>
                                <?php endif; ?>
                            </div>

                            <footer class="flex flex-wrap items-center gap-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-200">
                                    <i class="fas fa-hourglass-half"></i>
                                    <?= e($elapsedLabel) ?>: <?= e($elapsed) ?>
                                </span>
                                <a href="<?= base_url('/resident/request-detail/' . $request['id']) ?>"
                                   class="ml-auto inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 transition hover:text-primary-700 dark:text-primary-300 dark:hover:text-primary-200">
                                    Detayı Gör
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </footer>
                        </article>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        <?php if ($pagination['total'] > 0): ?>
                            <span class="font-semibold text-gray-900 dark:text-gray-100"><?= $start ?></span>
                            –
                            <span class="font-semibold text-gray-900 dark:text-gray-100"><?= $end ?></span>
                            arası, toplam
                            <span class="font-semibold text-gray-900 dark:text-gray-100"><?= $pagination['total'] ?></span>
                            kayıt
                        <?php else: ?>
                            Toplam 0 kayıt
                        <?php endif; ?>
                    </p>
                    <nav class="flex items-center justify-center gap-2">
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <?php
                                $pageQuery = $baseQuery;
                                if ($activeStatus !== '') {
                                    $pageQuery['status'] = $activeStatus;
                                }
                                if ($i > 1) {
                                    $pageQuery['page'] = $i;
                                } else {
                                    unset($pageQuery['page']);
                                }
                                $pageUrl = base_url('/resident/requests' . (!empty($pageQuery) ? '?' . http_build_query($pageQuery) : ''));
                            ?>
                            <a href="<?= $pageUrl ?>"
                               class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border px-3 text-sm font-medium transition <?= $i === $pagination['current_page']
                                   ? 'border-primary-500 bg-primary-50 text-primary-700 shadow-sm dark:border-primary-400 dark:bg-primary-900/30 dark:text-primary-200'
                                   : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
