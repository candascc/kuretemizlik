<?php
// Props: $headers (array of [key=>label] or [key=>['label'=>'','raw'=>bool]]), $rows (array of assoc), $dense (bool)
$headers = $headers ?? [];
$rows = $rows ?? [];
$dense = $dense ?? false;
?>
<div class="table-responsive">
  <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
    <thead class="bg-gray-50 dark:bg-gray-700">
      <tr>
        <?php foreach ($headers as $key => $label): ?>
          <?php $raw = is_array($label) && !empty($label['raw']); $lbl = is_string($label)?$label:($label['label']??$key); ?>
          <th class="px-6 <?= $dense ? 'py-2' : 'py-3' ?> text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
            <?php if ($raw) { echo $lbl; } else { echo e($lbl); } ?>
          </th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
      <?php foreach ($rows as $row): ?>
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
          <?php foreach ($headers as $key => $_): ?>
            <?php $raw = is_array($_) && !empty($_['raw']); $val = $row[$key] ?? ''; ?>
            <td class="px-6 <?= $dense ? 'py-2' : 'py-4' ?> whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
              <?php if ($raw) { echo $val; } else { echo e($val); } ?>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>


