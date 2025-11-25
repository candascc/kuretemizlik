<?php
// Props:
// $title (string, required)
// $subtitle (string, optional)
// $rightActionsHtml (string, optional) raw HTML for right-aligned actions
// $breadcrumbs (array of ['href'=>..., 'label'=>...], optional)
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <?php if (!empty($breadcrumbs) && is_array($breadcrumbs)): ?>
        <nav class="text-sm text-gray-500 dark:text-gray-400 mb-1" aria-label="Breadcrumb">
            <ol class="inline-flex space-x-2">
                <?php foreach ($breadcrumbs as $idx => $bc): ?>
                    <?php if (!empty($bc['href'])): ?>
                        <li><a href="<?= e($bc['href']) ?>" class="hover:text-primary-600 dark:hover:text-primary-400"><?= htmlspecialchars($bc['label'] ?? '') ?></a></li>
                    <?php else: ?>
                        <li class="text-gray-600 dark:text-gray-300 font-medium"><?= htmlspecialchars($bc['label'] ?? '') ?></li>
                    <?php endif; ?>
                    <?php if ($idx < count($breadcrumbs)-1): ?><li>›</li><?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>

        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
            <?= $title ?>
        </h1>
        <?php if (!empty($subtitle)): ?>
        <p class="text-gray-600 dark:text-gray-400 mt-2"><?= e($subtitle) ?></p>
        <?php endif; ?>
    </div>
    <?php if (!empty($rightActionsHtml)): ?>
    <div class="flex items-center space-x-3">
        <?= $rightActionsHtml ?>
    </div>
    <?php endif; ?>
</div>


