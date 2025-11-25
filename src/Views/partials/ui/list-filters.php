<?php
// Props:
// $method (GET|POST), $action (string|null), $fields (array), $submitLabel (string)
// Field schema per item:
// ['type'=>'select','name'=>'status','label'=>'Durum','icon'=>'fas fa-filter','options'=>['KEY'=>'Label'], 'value'=>'']
// ['type'=>'text'|'date','name'=>'customer','label'=>'Müşteri','icon'=>'fas fa-user','value'=>'']
$method = strtoupper($method ?? 'GET');
$action = $action ?? '';
$fields = $fields ?? [];
$submitLabel = $submitLabel ?? 'Filtrele';
?>
<form method="<?= $method ?>" action="<?= e($action) ?>" class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
        <?php foreach ($fields as $field): ?>
            <?php
            $type = $field['type'] ?? 'text';
            $name = $field['name'] ?? '';
            $label = $field['label'] ?? ucfirst($name);
            $icon = $field['icon'] ?? '';
            $value = $field['value'] ?? '';
            ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <?php if ($icon): ?><i class="<?= e($icon) ?> mr-2 text-primary-600"></i><?php endif; ?>
                    <?= e($label) ?>
                </label>
                <?php if ($type === 'select'): ?>
                    <div class="relative">
                        <select name="<?= e($name) ?>" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 appearance-none text-sm">
                            <option value="">Tümü</option>
                            <?php foreach (($field['options'] ?? []) as $optVal => $optLabel): ?>
                                <option value="<?= e($optVal) ?>" <?= ((string)$value === (string)$optVal) ? 'selected' : '' ?>><?= e($optLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                        </div>
                    </div>
                <?php else: ?>
                    <input type="<?= $type === 'date' ? 'date' : 'text' ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" 
                           class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm" 
                           placeholder="<?= htmlspecialchars($field['placeholder'] ?? '') ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="sm:col-span-2 lg:col-span-1">
            <button class="w-full px-4 sm:px-6 py-2 sm:py-3 rounded-lg text-white bg-gray-700 hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 font-medium transition-all duration-200 text-sm">
                <i class="fas fa-search mr-2"></i><?= e($submitLabel) ?>
            </button>
        </div>
    </div>
    <?php
    $hiddenFields = $hiddenFields ?? [];
    foreach ($hiddenFields as $hiddenName => $hiddenValue): ?>
        <input type="hidden" name="<?= e($hiddenName) ?>" value="<?= e($hiddenValue) ?>">
    <?php endforeach; ?>
    <?php if (!empty($method) && strtoupper($method) === 'POST'): ?>
        <?= CSRF::field() ?>
    <?php endif; ?>
</form>


