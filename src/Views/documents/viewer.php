<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                <i class="fas fa-file-alt mr-3 text-primary-600"></i>
                <?= htmlspecialchars($document['title'] ?? 'Döküman') ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                <?php if (!empty($document['building_name'])): ?>
                    Bina: <strong><?= e($document['building_name']) ?></strong>
                <?php endif; ?>
                <?php if (!empty($document['unit_number'])): ?>
                    - Daire: <strong><?= e($document['unit_number']) ?></strong>
                <?php endif; ?>
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="<?= base_url('/documents/' . $document['id'] . '/download') ?>" 
               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                <i class="fas fa-download mr-2"></i>İndir
            </a>
            <a href="<?= base_url('/documents') ?>" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Geri
            </a>
        </div>
    </div>

    <!-- Document Info Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="text-sm text-gray-600 dark:text-gray-400">Döküman Tipi</label>
                <p class="font-semibold text-gray-900 dark:text-white mt-1">
                    <?php
                    $types = [
                        'contract' => 'Sözleşme',
                        'deed' => 'Tapu',
                        'permit' => 'İzin Belgeleri',
                        'invoice' => 'Fatura',
                        'receipt' => 'Makbuz',
                        'insurance' => 'Sigorta',
                        'meeting_minutes' => 'Toplantı Tutanağı',
                        'announcement' => 'Duyuru',
                        'other' => 'Diğer'
                    ];
                    echo htmlspecialchars($types[$document['document_type']] ?? $document['document_type']);
                    ?>
                </p>
            </div>

            <div>
                <label class="text-sm text-gray-600 dark:text-gray-400">Dosya Adı</label>
                <p class="font-semibold text-gray-900 dark:text-white mt-1 break-all">
                    <i class="fas fa-file mr-2 text-primary-600"></i>
                    <?= htmlspecialchars($document['file_name'] ?? '') ?>
                </p>
            </div>

            <div>
                <label class="text-sm text-gray-600 dark:text-gray-400">Dosya Boyutu</label>
                <p class="font-semibold text-gray-900 dark:text-white mt-1">
                    <?= !empty($document['file_size']) ? formatBytes($document['file_size']) : '-' ?>
                </p>
            </div>

            <div>
                <label class="text-sm text-gray-600 dark:text-gray-400">Yüklenme Tarihi</label>
                <p class="font-semibold text-gray-900 dark:text-white mt-1">
                    <?= $document['created_at'] ? date('d.m.Y H:i', strtotime($document['created_at'])) : '-' ?>
                </p>
            </div>

            <?php if (!empty($document['description'])): ?>
                <div class="md:col-span-2">
                    <label class="text-sm text-gray-600 dark:text-gray-400">Açıklama</label>
                    <p class="text-gray-900 dark:text-white mt-1"><?= nl2br(e($document['description'])) ?></p>
                </div>
            <?php endif; ?>

            <div>
                <label class="text-sm text-gray-600 dark:text-gray-400">Durum</label>
                <p class="mt-1">
                    <?php if (!empty($document['is_public'])): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <i class="fas fa-eye mr-1"></i>Herkese Açık
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                            <i class="fas fa-lock mr-1"></i>Özel
                        </span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Document Preview -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Döküman Önizleme</h2>
        
        <?php
        $filePath = $document['file_path'] ?? '';
        $mimeType = $document['mime_type'] ?? '';
        $fileName = $document['file_name'] ?? '';
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $fullPath = base_path('storage/documents/' . $filePath);
        ?>

        <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
            <!-- Image Preview -->
            <div class="text-center">
                <img src="<?= base_url('/documents/' . $document['id'] . '/preview') ?>" 
                     alt="<?= e($document['title']) ?>"
                     class="max-w-full h-auto mx-auto rounded-lg shadow-lg"
                     style="max-height: 80vh;">
            </div>

        <?php elseif ($extension === 'pdf'): ?>
            <!-- PDF Preview -->
            <div class="w-full" style="height: 80vh;">
                <iframe src="<?= base_url('/documents/' . $document['id'] . '/preview') ?>" 
                        class="w-full h-full border border-gray-300 dark:border-gray-600 rounded-lg">
                </iframe>
            </div>

        <?php else: ?>
            <!-- Generic File Preview -->
            <div class="text-center py-12">
                <div class="inline-block p-8 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
                    <?php
                    $icons = [
                        'doc' => 'fa-file-word',
                        'docx' => 'fa-file-word',
                        'xls' => 'fa-file-excel',
                        'xlsx' => 'fa-file-excel',
                        'txt' => 'fa-file-alt',
                        'zip' => 'fa-file-archive',
                        'rar' => 'fa-file-archive'
                    ];
                    $icon = $icons[$extension] ?? 'fa-file';
                    ?>
                    <i class="fas <?= $icon ?> text-6xl text-gray-400 dark:text-gray-500"></i>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Bu dosya tipi tarayıcıda önizlenemez.
                </p>
                <a href="<?= base_url('/documents/' . $document['id'] . '/download') ?>" 
                   class="inline-flex items-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-download mr-2"></i>Dosyayı İndir
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Actions -->
    <?php if (Auth::hasRole('admin') || Auth::hasRole('manager')): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">İşlemler</h2>
            <div class="flex space-x-3">
                <a href="<?= base_url('/documents/' . $document['id'] . '/edit') ?>" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-edit mr-2"></i>Düzenle
                </a>
                <form method="POST" action="<?= base_url('/documents/' . $document['id'] . '/delete') ?>" 
                      onsubmit="return confirm('Bu dökümanı silmek istediğinizden emin misiniz?')" 
                      class="inline">
                    <?= CSRF::field() ?>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-trash mr-2"></i>Sil
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>

