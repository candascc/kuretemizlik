<?php
$title = $title ?? 'Internal Documentation';
$updatedAt = $updatedAt ?? date('Y-m-d');
$sections = $sections ?? [];
$resources = $resources ?? [];
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <header class="mb-8">
            <p class="text-sm uppercase tracking-wider text-blue-600 dark:text-blue-300 font-semibold">
                İç Dokümantasyon
            </p>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                <?= e($title) ?>
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                Son güncelleme: <strong><?= e($updatedAt) ?></strong>
            </p>
        </header>

        <div class="space-y-6">
            <?php foreach ($sections as $section): ?>
                <section class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-clipboard-check text-blue-600 dark:text-blue-300"></i>
                        <?= e($section['heading'] ?? 'Başlık') ?>
                    </h2>
                    <?php if (!empty($section['items'])): ?>
                        <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                            <?php foreach ($section['items'] as $item): ?>
                                <li><?= e($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($resources)): ?>
            <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">
                    Ek Kaynaklar
                </h3>
                <ul class="space-y-2 text-blue-700 dark:text-blue-300">
                    <?php foreach ($resources as $resource): ?>
                        <li>
                            <a href="<?= e($resource['href']) ?>"
                               class="inline-flex items-center gap-2 hover:underline"
                               target="_blank" rel="noopener">
                                <i class="fas fa-external-link-alt text-xs"></i>
                                <?= e($resource['label'] ?? $resource['href']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>


