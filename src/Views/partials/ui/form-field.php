<?php
/**
 * Standard Form Field Component
 * UX-HIGH-001 Implementation
 * 
 * Provides consistent validation feedback, styling, and accessibility
 * across all forms in the application
 * 
 * Usage:
 * <?php echo renderFormField([
 *     'name' => 'email',
 *     'label' => 'Email Address',
 *     'type' => 'email',
 *     'value' => $user['email'] ?? '',
 *     'required' => true,
 *     'error' => $errors['email'] ?? null,
 *     'help' => 'We will never share your email'
 * ]); ?>
 */

function renderFormField($config) {
    $name = $config['name'] ?? '';
    $label = $config['label'] ?? ucfirst($name);
    $type = $config['type'] ?? 'text';
    $value = $config['value'] ?? '';
    $placeholder = $config['placeholder'] ?? '';
    $required = $config['required'] ?? false;
    $disabled = $config['disabled'] ?? false;
    $readonly = $config['readonly'] ?? false;
    $error = $config['error'] ?? null;
    $help = $config['help'] ?? null;
    $icon = $config['icon'] ?? null;
    $options = $config['options'] ?? []; // For select/radio/checkbox
    $attributes = $config['attributes'] ?? [];
    $wrapperClass = $config['wrapperClass'] ?? '';
    $labelClass = $config['labelClass'] ?? '';
    $inputClass = $config['inputClass'] ?? '';
    
    // Generate unique ID
    $id = $config['id'] ?? 'field-' . $name . '-' . uniqid();
    
    // Error state
    $hasError = !empty($error);
    
    // Build attribute string
    $attrString = '';
    foreach ($attributes as $key => $val) {
        $attrString .= ' ' . e($key) . '="' . e($val) . '"';
    }
    
    // Start output buffering
    ob_start();
    ?>
    
    <div class="form-field-wrapper <?= $wrapperClass ?> <?= $hasError ? 'has-error' : '' ?>" data-field="<?= e($name) ?>">
        
        <!-- Label -->
        <?php if ($label && $type !== 'hidden'): ?>
        <label for="<?= $id ?>" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 <?= $labelClass ?>">
            <?php if ($icon): ?>
                <i class="<?= e($icon) ?> mr-2 text-primary-600"></i>
            <?php endif; ?>
            <?= e($label) ?>
            <?php if ($required): ?>
                <span class="text-red-500 ml-1" title="Required">*</span>
            <?php endif; ?>
        </label>
        <?php endif; ?>
        
        <!-- Input Container (for icon support) -->
        <div class="relative">
            
            <?php if ($type === 'textarea'): ?>
                <!-- Textarea -->
                <textarea 
                    id="<?= $id ?>"
                    name="<?= e($name) ?>"
                    rows="<?= $config['rows'] ?? 4 ?>"
                    placeholder="<?= e($placeholder) ?>"
                    <?= $required ? 'required' : '' ?>
                    <?= $disabled ? 'disabled' : '' ?>
                    <?= $readonly ? 'readonly' : '' ?>
                    class="w-full px-4 py-3 border-2 <?= $hasError ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-primary-500' ?> rounded-lg focus:ring-2 focus:border-primary-500 dark:bg-gray-700 dark:text-white resize-none transition-all <?= $inputClass ?>"
                    aria-invalid="<?= $hasError ? 'true' : 'false' ?>"
                    aria-describedby="<?= $hasError ? $id . '-error' : ($help ? $id . '-help' : '') ?>"
                    <?= $attrString ?>><?= e($value) ?></textarea>
                    
            <?php elseif ($type === 'select'): ?>
                <!-- Select Dropdown -->
                <select 
                    id="<?= $id ?>"
                    name="<?= e($name) ?>"
                    <?= $required ? 'required' : '' ?>
                    <?= $disabled ? 'disabled' : '' ?>
                    class="w-full px-4 py-3 border-2 <?= $hasError ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-primary-500' ?> rounded-lg focus:ring-2 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all <?= $inputClass ?>"
                    aria-invalid="<?= $hasError ? 'true' : 'false' ?>"
                    aria-describedby="<?= $hasError ? $id . '-error' : ($help ? $id . '-help' : '') ?>"
                    <?= $attrString ?>>
                    <?php if ($placeholder): ?>
                        <option value=""><?= e($placeholder) ?></option>
                    <?php endif; ?>
                    <?php foreach ($options as $optValue => $optLabel): ?>
                        <option value="<?= e($optValue) ?>" <?= $value == $optValue ? 'selected' : '' ?>>
                            <?= e($optLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
            <?php elseif ($type === 'checkbox'): ?>
                <!-- Checkbox -->
                <div class="flex items-center">
                    <input 
                        type="checkbox"
                        id="<?= $id ?>"
                        name="<?= e($name) ?>"
                        value="<?= $config['checkbox_value'] ?? '1' ?>"
                        <?= $value ? 'checked' : '' ?>
                        <?= $required ? 'required' : '' ?>
                        <?= $disabled ? 'disabled' : '' ?>
                        class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 focus:ring-2 <?= $inputClass ?>"
                        aria-invalid="<?= $hasError ? 'true' : 'false' ?>"
                        aria-describedby="<?= $hasError ? $id . '-error' : ($help ? $id . '-help' : '') ?>"
                        <?= $attrString ?>>
                    <label for="<?= $id ?>" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                        <?= htmlspecialchars($config['checkbox_label'] ?? $label) ?>
                    </label>
                </div>
                
            <?php elseif ($type === 'radio'): ?>
                <!-- Radio Group -->
                <div class="space-y-3">
                    <?php foreach ($options as $optValue => $optLabel): ?>
                        <div class="flex items-center">
                            <input 
                                type="radio"
                                id="<?= $id . '-' . $optValue ?>"
                                name="<?= e($name) ?>"
                                value="<?= e($optValue) ?>"
                                <?= $value == $optValue ? 'checked' : '' ?>
                                <?= $required ? 'required' : '' ?>
                                <?= $disabled ? 'disabled' : '' ?>
                                class="w-5 h-5 text-primary-600 border-gray-300 focus:ring-primary-500 focus:ring-2 <?= $inputClass ?>"
                                <?= $attrString ?>>
                            <label for="<?= $id . '-' . $optValue ?>" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                                <?= e($optLabel) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else: ?>
                <!-- Standard Input (text, email, password, number, date, etc) -->
                <input 
                    type="<?= e($type) ?>"
                    id="<?= $id ?>"
                    name="<?= e($name) ?>"
                    value="<?= e($value) ?>"
                    placeholder="<?= e($placeholder) ?>"
                    <?= $required ? 'required' : '' ?>
                    <?= $disabled ? 'disabled' : '' ?>
                    <?= $readonly ? 'readonly' : '' ?>
                    class="w-full px-4 py-3 border-2 <?= $hasError ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:ring-primary-500' ?> rounded-lg focus:ring-2 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all <?= $inputClass ?>"
                    aria-invalid="<?= $hasError ? 'true' : 'false' ?>"
                    aria-describedby="<?= $hasError ? $id . '-error' : ($help ? $id . '-help' : '') ?>"
                    <?= $attrString ?>>
            <?php endif; ?>
            
        </div>
        
        <!-- Inline Error Message -->
        <?php if ($hasError): ?>
        <div id="<?= $id ?>-error" class="mt-2 flex items-start space-x-2 text-red-600 dark:text-red-400 animate-shake" role="alert">
            <i class="fas fa-exclamation-circle mt-0.5"></i>
            <span class="text-sm font-medium"><?= e($error) ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Help Text -->
        <?php if ($help && !$hasError): ?>
        <div id="<?= $id ?>-help" class="mt-2 flex items-start space-x-2 text-gray-600 dark:text-gray-400">
            <i class="fas fa-info-circle mt-0.5 text-xs"></i>
            <span class="text-xs"><?= e($help) ?></span>
        </div>
        <?php endif; ?>
        
    </div>
    
    <?php
    return ob_get_clean();
}

/**
 * Render validation errors summary (for top of form)
 */
function renderValidationSummary($errors) {
    if (empty($errors)) return '';
    
    ob_start();
    ?>
    <div class="bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-700 rounded-xl p-6 mb-6 animate-shake" role="alert">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-red-600 text-2xl mr-4 mt-1"></i>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-red-900 dark:text-red-100 mb-3">
                    Lütfen aşağıdaki hataları düzeltin:
                </h3>
                <ul class="list-disc list-inside space-y-1 text-red-800 dark:text-red-200">
                    <?php foreach ($errors as $field => $error): ?>
                        <li class="text-sm">
                            <strong><?= htmlspecialchars(ucfirst($field)) ?>:</strong> 
                            <?= e($error) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

<style>
/* Animation for error shake */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.animate-shake {
    animation: shake 0.5s ease-in-out;
}

/* Error state styling */
.form-field-wrapper.has-error input,
.form-field-wrapper.has-error textarea,
.form-field-wrapper.has-error select {
    animation: shake 0.5s ease-in-out;
}

/* Focus visible for accessibility */
.form-field-wrapper input:focus-visible,
.form-field-wrapper textarea:focus-visible,
.form-field-wrapper select:focus-visible {
    outline: 2px solid var(--primary-color, #3b82f6);
    outline-offset: 2px;
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
    .form-field-wrapper input::placeholder,
    .form-field-wrapper textarea::placeholder {
        color: rgba(255, 255, 255, 0.4);
    }
}
</style>

