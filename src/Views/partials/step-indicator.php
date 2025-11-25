<?php
/**
 * Step Indicator Partial
 * Multi-step form progress indicator
 * 
 * @param array $steps Array of steps with 'label' and 'status' keys
 * @param int $currentStep Current step index (0-based)
 */
$steps = $steps ?? [];
$currentStep = $currentStep ?? 0;
?>

<div class="step-indicator" role="tablist" aria-label="Form adımları">
    <?php foreach ($steps as $index => $step): ?>
        <?php
        $label = $step['label'] ?? "Adım " . ($index + 1);
        $status = $step['status'] ?? ($index < $currentStep ? 'completed' : ($index === $currentStep ? 'active' : 'pending'));
        
        $stepClasses = '';
        $iconClasses = '';
        $labelClasses = '';
        
        switch ($status) {
            case 'completed':
                $stepClasses = 'bg-primary-600 text-white border-primary-600';
                $iconClasses = 'fas fa-check';
                $labelClasses = 'text-primary-600 font-medium';
                break;
            case 'active':
                $stepClasses = 'bg-white text-primary-600 border-primary-600 ring-4 ring-primary-200';
                $iconClasses = 'fas fa-circle';
                $labelClasses = 'text-primary-600 font-semibold';
                break;
            case 'pending':
            default:
                $stepClasses = 'bg-white text-gray-400 border-gray-300';
                $iconClasses = 'fas fa-circle';
                $labelClasses = 'text-gray-500';
                break;
        }
        ?>
        
        <div class="step-item" role="tab" aria-selected="<?= $status === 'active' ? 'true' : 'false' ?>" aria-current="<?= $status === 'active' ? 'step' : 'false' ?>">
            <div class="step-connector <?= $index > 0 ? 'block' : 'hidden' ?>">
                <div class="connector-line <?= $steps[$index-1]['status'] === 'completed' ? 'bg-primary-600' : 'bg-gray-300' ?>"></div>
            </div>
            
            <div class="step-circle <?= $stepClasses ?>">
                <i class="<?= $iconClasses ?>"></i>
            </div>
            
            <div class="step-label <?= $labelClasses ?>">
                <?= e($label) ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.step-indicator {
    @apply flex items-center justify-between w-full max-w-2xl mx-auto;
}

.step-item {
    @apply flex flex-col items-center flex-1 relative;
}

.step-connector {
    @apply absolute top-5 left-0 w-full h-0.5;
}

.connector-line {
    @apply h-full transition-colors duration-300;
}

.step-circle {
    @apply w-10 h-10 rounded-full flex items-center justify-center border-2 relative z-10 transition-all duration-300;
}

.step-label {
    @apply mt-2 text-sm text-center max-w-24;
}

/* Mobile adjustments */
@media (max-width: 640px) {
    .step-label {
        @apply hidden;
    }
    
    .step-circle {
        @apply w-8 h-8;
    }
    
    .step-circle i {
        @apply text-sm;
    }
}
</style>

