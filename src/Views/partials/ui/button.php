<?php
// Usage: include with variables: $label, $href (optional), $variant ('primary','secondary','danger','ghost'), $icon (optional)
$label = $label ?? 'Buton';
$href = $href ?? null;
$variant = $variant ?? 'primary';
$icon = $icon ?? null;

$base = 'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold shadow-soft transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';
switch ($variant) {
    case 'secondary':
        $cls = $base . ' bg-gray-700 hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 text-white focus:ring-gray-500';
        break;
    case 'danger':
        $cls = $base . ' bg-red-600 hover:bg-red-700 text-white focus:ring-red-500';
        break;
    case 'ghost':
        $cls = $base . ' bg-transparent hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 shadow-none focus:ring-primary-500';
        break;
    default:
        $cls = $base . ' bg-primary-600 hover:bg-primary-700 text-white focus:ring-primary-500';
}
?>
<?php if ($href): ?>
<a href="<?= $href ?>" class="<?= $cls ?>">
    <?php if ($icon): ?><i class="fas <?= e($icon) ?> text-sm"></i><?php endif; ?>
    <span><?= e($label) ?></span>
</a>
<?php else: ?>
<button type="button" class="<?= $cls ?>">
    <?php if ($icon): ?><i class="fas <?= e($icon) ?> text-sm"></i><?php endif; ?>
    <span><?= e($label) ?></span>
</button>
<?php endif; ?>


