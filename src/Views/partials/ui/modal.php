<?php
// Minimal modal. Usage: include and control via JS: toggle class 'hidden'
$id = $id ?? 'modal';
$title = $title ?? '';
$body = $body ?? '';
$footer = $footer ?? '';
?>
<div id="<?= e($id) ?>" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/40"></div>
  <div class="mx-auto mt-24 max-w-lg w-11/12 bg-white dark:bg-gray-800 rounded-2xl shadow-strong border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= e($title) ?></h3>
      <button class="text-gray-500 hover:text-gray-700 px-2" onclick="document.getElementById('<?= e($id) ?>').classList.add('hidden')"><i class="fas fa-times"></i></button>
    </div>
    <div class="p-5">
      <?= $body ?>
    </div>
    <div class="px-5 py-4 bg-gray-50 dark:bg-gray-700 flex items-center justify-end gap-2">
      <?= $footer ?>
    </div>
  </div>
  <script>
    (function(){
      const modal = document.getElementById('<?= $id ?>');
      if(!modal) return;
      modal.addEventListener('click', function(e){ if(e.target===modal){ modal.classList.add('hidden'); } });
    })();
  </script>
</div>


