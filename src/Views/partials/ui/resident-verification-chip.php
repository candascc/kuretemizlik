<?php
$chip = $chip ?? [];
$label = $chip['label'] ?? '';
$state = $chip['state'] ?? 'missing';
$actionLabel = $chip['action_label'] ?? 'Doğrula';
$modalTarget = $chip['modal_target'] ?? 'contactVerificationModal';
$modalContext = $chip['modal_context'] ?? '';
$showAction = $chip['show_action'] ?? false;

$palette = [
    'verified' => [
        'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',
        'icon' => 'fa-circle-check',
    ],
    'pending' => [
        'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200',
        'icon' => 'fa-hourglass-half',
    ],
    'missing' => [
        'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200',
        'icon' => 'fa-circle-exclamation',
    ],
];

$styles = $palette[$state] ?? $palette['missing'];
?>

<div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold <?= $styles['class'] ?>" role="listitem">
    <i class="fas <?= $styles['icon'] ?>" aria-hidden="true"></i>
    <span><?= e($label) ?></span>
    <?php if ($showAction): ?>
        <button type="button"
                data-modal-target="<?= e($modalTarget) ?>"
                data-modal-context="<?= e($modalContext) ?>"
                class="inline-flex items-center gap-1 rounded-full border border-current bg-white/70 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-current transition hover:bg-white dark:bg-white/10">
            <i class="fas fa-bolt" aria-hidden="true"></i>
            <?= e($actionLabel) ?>
        </button>
    <?php endif; ?>
</div>

