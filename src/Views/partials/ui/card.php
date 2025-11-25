<?php
// Slots: $title, $subtitle, $actions (html), $body (html)
$title = $title ?? '';
$subtitle = $subtitle ?? '';
$actions = $actions ?? '';
?>
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 h-full flex flex-col">
    <?php if ($title || $subtitle || $actions): ?>
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <div>
            <?php if ($title): ?><h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= e($title) ?></h3><?php endif; ?>
            <?php if ($subtitle): ?><p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= e($subtitle) ?></p><?php endif; ?>
        </div>
        <div class="flex items-center gap-2">
            <?= $actions ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="p-4 sm:p-6 flex-1 flex flex-col">
        <?= $body ?? '' ?>
    </div>
</div>


