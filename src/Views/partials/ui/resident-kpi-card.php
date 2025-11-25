<?php
$title = $title ?? '';
$value = $value ?? '';
$description = $description ?? '';
$icon = $icon ?? 'fa-circle-info';
$href = $href ?? null;
$ariaLabel = $ariaLabel ?? null;
?>

<article class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
    <div class="flex items-start justify-between gap-3">
        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"><?= e($title) ?></p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white" <?= $ariaLabel ? 'aria-label="' . e($ariaLabel) . '"' : '' ?>>
                <?= htmlspecialchars((string)$value) ?>
            </p>
            <?php if ($description): ?>
                <p class="text-sm text-gray-600 dark:text-gray-300"><?= e($description) ?></p>
            <?php endif; ?>
        </div>
        <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-200">
            <i class="fas <?= e($icon) ?>"></i>
        </span>
    </div>
    <?php if ($href): ?>
        <a href="<?= e($href) ?>"
           class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-primary-600 transition hover:text-primary-500 dark:text-primary-300 dark:hover:text-primary-200">
            Detayları görüntüle
            <i class="fas fa-arrow-right text-xs"></i>
        </a>
    <?php endif; ?>
</article>

