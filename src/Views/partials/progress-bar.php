<?php
/**
 * Progress Bar Partial
 * Reusable progress indicator component
 * 
 * @param int|float $progress Progress percentage (0-100)
 * @param string $label Progress label text
 * @param string $size Size variant (sm, md, lg)
 * @param string $color Color variant (primary, success, warning, danger)
 */
$progress = $progress ?? 0;
$label = $label ?? '';
$size = $size ?? 'md';
$color = $color ?? 'primary';

// Size classes
$heightClasses = [
    'sm' => 'h-1',
    'md' => 'h-2',
    'lg' => 'h-3'
];

// Color classes
$barColorClasses = [
    'primary' => 'bg-primary-600',
    'success' => 'bg-green-600',
    'warning' => 'bg-yellow-600',
    'danger' => 'bg-red-600',
    'info' => 'bg-blue-600'
];

$textColorClasses = [
    'primary' => 'text-primary-600',
    'success' => 'text-green-600',
    'warning' => 'text-yellow-600',
    'danger' => 'text-red-600',
    'info' => 'text-blue-600'
];
?>

<div class="progress-bar-container">
    <?php if (!empty($label)): ?>
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            <?= e($label) ?>
        </span>
        <span class="text-sm font-semibold <?= $textColorClasses[$color] ?>">
            <?= round($progress) ?>%
        </span>
    </div>
    <?php endif; ?>
    
    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden <?= $heightClasses[$size] ?>">
        <div 
            class="progress-fill <?= $barColorClasses[$color] ?> transition-all duration-500 ease-out rounded-full h-full"
            style="width: <?= min(100, max(0, $progress)) ?>%"
            role="progressbar"
            aria-valuenow="<?= $progress ?>"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-label="<?= !empty($label) ? e($label) : 'Progress' ?>"
        >
            <?php if ($size === 'lg' && $progress >= 10): ?>
            <span class="progress-text text-xs text-white font-medium px-2 flex items-center h-full">
                <?= round($progress) ?>%
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.progress-bar-container {
    @apply w-full;
}

.progress-fill {
    background: linear-gradient(90deg, currentColor, currentColor);
    background-size: 100% 100%;
}
</style>

