<?php
/**
 * Unified Form Base Component
 * Provides consistent, beautiful, and functional form design across the site
 * 
 * Usage:
 * <?php include __DIR__ . '/form-base.php'; ?>
 * <?php
 *   FormBase::render([
 *     'title' => 'Form Başlığı',
 *     'subtitle' => 'Form açıklaması',
 *     'formAction' => base_url('/action'),
 *     'fields' => [...],
 *     'submitText' => 'Kaydet',
 *     'cancelUrl' => base_url('/list')
 *   ]);
 * ?>
 */

class FormBase {
    private static $fieldIdCounter = 0;
    
    public static function render($config) {
        $title = $config['title'] ?? 'Form';
        $subtitle = $config['subtitle'] ?? '';
        $icon = $config['icon'] ?? 'fa-edit';
        $formAction = $config['formAction'] ?? '#';
        $method = $config['method'] ?? 'POST';
        $fields = $config['fields'] ?? [];
        $sections = $config['sections'] ?? [];
        $submitText = $config['submitText'] ?? 'Kaydet';
        $submitIcon = $config['submitIcon'] ?? 'fa-save';
        $cancelUrl = $config['cancelUrl'] ?? base_url('/');
        $cancelText = $config['cancelText'] ?? 'İptal';
        $breadcrumb = $config['breadcrumb'] ?? [];
        $additionalFields = $config['additionalFields'] ?? '';
        $onSubmit = $config['onSubmit'] ?? '';
        $alpineData = $config['alpineData'] ?? '';
        $showFlash = $config['showFlash'] ?? true;
        
        // Build breadcrumb
        $breadcrumbHtml = '';
        if (!empty($breadcrumb)) {
            $breadcrumbHtml = '<nav class="flex" aria-label="Breadcrumb">';
            $breadcrumbHtml .= '<ol class="flex items-center space-x-4">';
            foreach ($breadcrumb as $item) {
                if ($item === 'LAST') {
                    $breadcrumbHtml .= '<li><span class="text-gray-500 dark:text-gray-400 font-medium">' . end($breadcrumb) . '</span></li>';
                } else {
                    $breadcrumbHtml .= '<li><a href="' . (is_array($item) ? $item['url'] : '#') . '" class="text-gray-400 dark:text-gray-500 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200"><i class="fas ' . (is_array($item) ? $item['icon'] : 'fa-circle') . '"></i></a></li>';
                }
            }
            $breadcrumbHtml .= '</ol></nav>';
        }
        
        $formOpening = '<form method="' . e($method) . '" action="' . e($formAction) . '"';
        if ($alpineData) {
            $formOpening .= ' x-data="' . e($alpineData) . '"';
        }
        if ($onSubmit) {
            $formOpening .= ' onsubmit="' . e($onSubmit) . '"';
        }
        $formOpening .= ' class="space-y-6" enctype="multipart/form-data">';
        ?>
        
        <div class="space-y-8">
            <?php if ($breadcrumbHtml): ?>
                <?= $breadcrumbHtml ?>
            <?php endif; ?>
            
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        <?php if ($icon): ?>
                            <i class="fas <?= e($icon) ?> mr-3 text-primary-600"></i>
                        <?php endif; ?>
                        <?= e($title) ?>
                    </h1>
                    <?php if ($subtitle): ?>
                        <p class="text-gray-600 dark:text-gray-400 mt-2"><?= e($subtitle) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($showFlash): ?>
                <?php include __DIR__ . '/flash.php'; ?>
            <?php endif; ?>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 overflow-hidden">
                <?= $formOpening ?>
                <?= CSRF::field() ?>
                
                <?php if (!empty($sections)): ?>
                    <?php foreach ($sections as $section): ?>
                        <div class="border-b border-gray-200 dark:border-gray-700 last:border-0">
                            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                    <?php if (!empty($section['icon'])): ?>
                                        <i class="fas <?= e($section['icon']) ?> mr-2 text-primary-600"></i>
                                    <?php endif; ?>
                                    <?= e($section['title']) ?>
                                </h2>
                                <?php if (!empty($section['description'])): ?>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= e($section['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                    <?php foreach ($section['fields'] as $field): ?>
                                        <?= self::renderField($field) ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($fields as $field): ?>
                                <?= self::renderField($field) ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?= $additionalFields ?>
                
                <!-- Submit Button -->
                <div class="flex justify-end gap-4 px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                    <a href="<?= e($cancelUrl) ?>" 
                       class="px-6 py-3 rounded-lg bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 font-medium transition-all duration-200">
                        <i class="fas fa-times mr-2"></i><?= e($cancelText) ?>
                    </a>
                    <button type="submit" 
                            x-data="{ submitting: false }"
                            @click="submitting = true"
                            :disabled="submitting"
                            :class="{'opacity-50 cursor-not-allowed': submitting}"
                            class="px-8 py-3 rounded-lg text-white bg-primary-600 hover:bg-primary-700 font-medium transition-all duration-200 shadow-medium hover:shadow-strong disabled:opacity-50"
                            aria-label="<?= e($submitText) ?>">
                        <span x-show="!submitting">
                            <i class="fas <?= e($submitIcon) ?> mr-2"></i>
                            <?= e($submitText) ?>
                        </span>
                        <span x-show="submitting">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Kaydediliyor...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </form>
    <?php
    }
    
    private static function renderField($field) {
        $type = $field['type'] ?? 'text';
        $name = $field['name'] ?? '';
        $label = $field['label'] ?? '';
        $value = $field['value'] ?? '';
        $placeholder = $field['placeholder'] ?? '';
        $required = $field['required'] ?? false;
        $icon = $field['icon'] ?? '';
        $help = $field['help'] ?? '';
        $readonly = $field['readonly'] ?? false;
        $disabled = $field['disabled'] ?? false;
        $class = $field['class'] ?? '';
        $attrs = $field['attrs'] ?? '';
        
        $fieldId = 'field_' . (++self::$fieldIdCounter);
        $colSpan = $field['colSpan'] ?? 1;
        
        // Calculate grid column span
        $gridClass = $colSpan === 'full' ? 'md:col-span-2' : '';
        
        ob_start();
        ?>
        <div class="<?= $gridClass ?>">
            <?php if ($label): ?>
                <label for="<?= $fieldId ?>" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <?php if ($icon): ?>
                        <i class="fas <?= e($icon) ?> mr-2 text-primary-600"></i>
                    <?php endif; ?>
                    <?= e($label) ?>
                    <?php if ($required): ?>
                        <span class="text-red-500">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>
            
            <?php
            switch ($type) {
                case 'text':
                case 'email':
                case 'tel':
                case 'number':
                case 'date':
                case 'datetime-local':
                case 'time':
                case 'password':
                    $inputType = $type === 'datetime-local' ? 'datetime-local' : $type;
                    ?>
                    <input type="<?= $inputType ?>" 
                           id="<?= $fieldId ?>"
                           name="<?= e($name) ?>" 
                           value="<?= e($value) ?>" 
                           <?php if ($placeholder): ?>placeholder="<?= e($placeholder) ?>"<?php endif; ?>
                           <?php if ($required): ?>required<?php endif; ?>
                           <?php if ($readonly): ?>readonly<?php endif; ?>
                           <?php if ($disabled): ?>disabled<?php endif; ?>
                           <?= $attrs ?>
                           class="form-input w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm <?= $class ?>">
                    <?php
                    break;
                    
                case 'textarea':
                    ?>
                    <textarea id="<?= $fieldId ?>"
                              name="<?= e($name) ?>" 
                              rows="<?= $field['rows'] ?? 4 ?>"
                              <?php if ($placeholder): ?>placeholder="<?= e($placeholder) ?>"<?php endif; ?>
                              <?php if ($required): ?>required<?php endif; ?>
                              <?php if ($readonly): ?>readonly<?php endif; ?>
                              <?php if ($disabled): ?>disabled<?php endif; ?>
                              <?= $attrs ?>
                              class="form-input w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm resize-none <?= $class ?>"><?= e($value) ?></textarea>
                    <?php
                    break;
                    
                case 'select':
                    $options = $field['options'] ?? [];
                    $multiple = $field['multiple'] ?? false;
                    ?>
                    <div class="relative">
                        <select id="<?= $fieldId ?>"
                                name="<?= e($name) . ($multiple ? '[]' : '') ?>" 
                                <?php if ($required): ?>required<?php endif; ?>
                                <?php if ($readonly): ?>readonly disabled<?php endif; ?>
                                <?php if ($disabled): ?>disabled<?php endif; ?>
                                <?php if ($multiple): ?>multiple<?php endif; ?>
                                <?= $attrs ?>
                                class="form-input w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm appearance-none <?= $class ?>">
                            <?php if (!empty($field['placeholder_option'])): ?>
                                <option value=""><?= e($field['placeholder_option']) ?></option>
                            <?php endif; ?>
                            <?php foreach ($options as $optionValue => $optionLabel): ?>
                                <option value="<?= e($optionValue) ?>" <?= ($value == $optionValue) ? 'selected' : '' ?>>
                                    <?= e($optionLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </div>
                    </div>
                    <?php
                    break;
                    
                case 'checkbox':
                    $checked = $value ? 'checked' : '';
                    ?>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               id="<?= $fieldId ?>"
                               name="<?= e($name) ?>" 
                               value="1"
                               <?= $checked ?>
                               <?php if ($disabled): ?>disabled<?php endif; ?>
                               <?= $attrs ?>
                               class="form-checkbox h-5 w-5 text-primary-600 rounded border-gray-300 dark:border-gray-600 focus:ring-primary-500 <?= $class ?>">
                        <span class="ml-3 text-sm text-gray-700 dark:text-gray-300"><?= e($label) ?></span>
                    </label>
                    <?php
                    break;
                    
                case 'radio':
                    $radios = $field['radios'] ?? [];
                    ?>
                    <div class="space-y-2">
                        <?php foreach ($radios as $radio): ?>
                            <label class="inline-flex items-center cursor-pointer mr-4">
                                <input type="radio" 
                                       name="<?= e($name) ?>" 
                                       value="<?= e($radio['value']) ?>"
                                       <?= ($value == $radio['value']) ? 'checked' : '' ?>
                                       <?php if ($required && $radio === reset($radios)): ?>required<?php endif; ?>
                                       <?php if ($disabled): ?>disabled<?php endif; ?>
                                       <?= $attrs ?>
                                       class="form-radio h-5 w-5 text-primary-600 border-gray-300 dark:border-gray-600 focus:ring-primary-500 <?= $class ?>">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300"><?= e($radio['label']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php
                    break;
                    
                case 'hidden':
                    ?>
                    <input type="hidden" 
                           id="<?= $fieldId ?>"
                           name="<?= e($name) ?>" 
                           value="<?= e($value) ?>"
                           <?= $attrs ?>
                           class="<?= $class ?>">
                    <?php
                    break;
                    
                case 'file':
                    ?>
                    <input type="file" 
                           id="<?= $fieldId ?>"
                           name="<?= e($name) ?>" 
                           <?php if ($required): ?>required<?php endif; ?>
                           <?php if ($disabled): ?>disabled<?php endif; ?>
                           <?php if (!empty($field['accept'])): ?>accept="<?= e($field['accept']) ?>"<?php endif; ?>
                           <?php if (!empty($field['multiple'])): ?>multiple<?php endif; ?>
                           <?= $attrs ?>
                           class="block w-full text-sm text-gray-900 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 dark:file:bg-primary-900/20 file:text-primary-700 dark:file:text-primary-400 hover:file:bg-primary-100 dark:hover:file:bg-primary-900/30 <?= $class ?>">
                    <?php
                    break;
            }
            ?>
            
            <?php if ($help): ?>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    <?= e($help) ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

