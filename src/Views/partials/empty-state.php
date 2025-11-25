<?php
/**
 * Empty State Partial
 * Reusable empty state component for lists
 * 
 * @param string $icon Font Awesome icon class
 * @param string $title Empty state title
 * @param string $description Empty state description
 * @param string $actionUrl URL for the action button
 * @param string $actionText Text for the action button
 * @param string $icon Font Awesome icon for action button
 */
?>

<div class="empty-state text-center py-12 px-4">
    <div class="empty-state-icon mb-6">
        <i class="fas <?= $icon ?? 'fa-inbox' ?> text-6xl text-gray-300 dark:text-gray-600"></i>
    </div>
    
    <?php if (!empty($title)): ?>
    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
        <?= e($title) ?>
    </h3>
    <?php endif; ?>
    
    <?php if (!empty($description)): ?>
    <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
        <?= e($description) ?>
    </p>
    <?php endif; ?>
    
    <?php if (!empty($actionUrl) && !empty($actionText)): ?>
    <a href="<?= e($actionUrl) ?>" 
       class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg shadow-soft hover:shadow-medium transition-all duration-200">
        <i class="fas <?= $actionIcon ?? 'fa-plus' ?> mr-2"></i>
        <?= e($actionText) ?>
    </a>
    <?php endif; ?>
    
    <?php if (!empty($helpText)): ?>
    <div class="mt-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            <?= e($helpText) ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<style>
.empty-state {
    min-height: 300px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.empty-state-icon {
    opacity: 0.5;
}
</style>

