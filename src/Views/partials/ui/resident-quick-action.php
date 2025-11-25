<?php
$action = $action ?? [];
$label = $action['label'] ?? ($label ?? '');
$href = $action['href'] ?? ($href ?? '#');
$icon = $action['icon'] ?? ($icon ?? 'fa-circle-chevron-right');
$description = $action['description'] ?? ($description ?? '');
$badge = isset($action['badge']) ? $action['badge'] : ($badge ?? null);
?>

<a href="<?= e($href) ?>"
   class="flex items-start gap-3 rounded-2xl border border-gray-200 bg-white/90 px-4 py-4 transition hover:border-primary-200 hover:shadow dark:border-gray-700 dark:bg-gray-900/70"
   aria-label="<?= e($label) ?>"
   data-quick-action
   data-action-label="<?= e($label) ?>">
    <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-200">
        <i class="fas <?= e($icon) ?>"></i>
    </span>
    <div class="flex-1 space-y-1">
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-gray-900 dark:text-white"><?= e($label) ?></span>
            <?php if (!empty($badge)): ?>
                <span class="inline-flex items-center gap-1 rounded-full bg-primary-100 px-2 py-0.5 text-[11px] font-semibold text-primary-700 dark:bg-primary-900/30 dark:text-primary-200">
                    <i class="fas fa-sparkles" aria-hidden="true"></i>
                    <?= (int)$badge ?>
                </span>
            <?php endif; ?>
        </div>
        <?php if ($description): ?>
            <p class="text-xs text-gray-500 dark:text-gray-400"><?= e($description) ?></p>
        <?php endif; ?>
    </div>
    <i class="fas fa-arrow-right text-xs text-gray-300 transition group-hover:text-primary-400 dark:text-gray-600"></i>
</a>

