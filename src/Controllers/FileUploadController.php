<?php
/**
 * Enhanced File Upload Controller
 * Gelişmiş dosya yükleme kontrolcüsü
 */

class FileUploadController
{
    private $fileUploadService;
    private $db;

    public function __construct()
    {
        $this->fileUploadService = FileUploadService::getInstance();
        $this->db = Database::getInstance();
    }

    /**
     * Dosya yükleme formu
     */
    public function uploadForm()
    {
        Auth::require();

        $category = $_GET['category'] ?? 'documents';
        $entityType = $_GET['entity_type'] ?? null;
        $entityId = $_GET['entity_id'] ?? null;

        echo View::renderWithLayout('file-upload/form', [
            'title' => 'Dosya Yükle',
            'category' => $category,
            'entityType' => $entityType,
            'entityId' => $entityId,
            'maxFileSize' => $this->fileUploadService->getMaxFileSize(),
            'allowedTypes' => $this->fileUploadService->getAllowedTypes($category)
        ]);
    }

    /**
     * AJAX dosya yükleme
     */
    public function upload()
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            return View::json(['success' => false, 'error' => 'CSRF doğrulaması başarısız'], 403);
        }

        try {
            $category = $_POST['category'] ?? 'documents';
            $entityType = $_POST['entity_type'] ?? null;
            $entityId = $_POST['entity_id'] ?? null;
            $multiple = !empty($_POST['multiple']);

            $options = [
                'category' => $category,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'max_size' => $_POST['max_size'] ?? null,
                'allowed_types' => $_POST['allowed_types'] ? explode(',', $_POST['allowed_types']) : null,
                'generate_thumbnails' => !empty($_POST['generate_thumbnails']),
                'compress_images' => !empty($_POST['compress_images'])
            ];

            if ($multiple) {
                $result = $this->fileUploadService->uploadMultiple($_FILES['files'], $options);
            } else {
                $result = $this->fileUploadService->uploadSingle($_FILES['file'], $options);
            }

            // Access log
            if (isset($result['id'])) {
                $this->logFileAccess($result['id'], 'upload');
            } elseif (isset($result['files'])) {
                foreach ($result['files'] as $file) {
                    $this->logFileAccess($file['id'], 'upload');
                }
            }

            ResponseFormatter::success($result);

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
        }
    }

    /**
     * Chunked upload (büyük dosyalar için)
     */
    public function uploadChunk()
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            return View::json(['success' => false, 'error' => 'CSRF doğrulaması başarısız'], 403);
        }

        try {
            $chunkNumber = (int)($_POST['chunk'] ?? 0);
            $totalChunks = (int)($_POST['chunks'] ?? 1);
            $filename = $_POST['filename'] ?? '';
            $sessionId = $_POST['session_id'] ?? session_id();

            if (empty($_FILES['file'])) {
                throw new Exception('Dosya parçası bulunamadı');
            }

            $chunkDir = __DIR__ . '/../../storage/temp/chunks/' . $sessionId . '/';
            if (!is_dir($chunkDir)) {
                mkdir($chunkDir, 0755, true);
            }

            $chunkFile = $chunkDir . $filename . '.part' . $chunkNumber;
            move_uploaded_file($_FILES['file']['tmp_name'], $chunkFile);

            // Progress güncelle
            $this->updateUploadProgress($sessionId, $filename, $chunkNumber, $totalChunks);

            // Son parça ise birleştir
            if ($chunkNumber == $totalChunks - 1) {
                $finalFile = $this->combineChunks($sessionId, $filename, $totalChunks);
                $this->processFinalFile($finalFile, $sessionId);
            }

            ResponseFormatter::success([
                'chunk' => $chunkNumber,
                'total' => $totalChunks,
                'progress' => round(($chunkNumber + 1) / $totalChunks * 100, 2)
            ]);

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
        }
    }

    /**
     * Upload progress sorgula
     */
    public function getProgress()
    {
        Auth::require();

        $sessionId = $_GET['session_id'] ?? session_id();
        $progress = $this->db->fetch(
            'SELECT * FROM file_upload_progress WHERE session_id = ? ORDER BY created_at DESC LIMIT 1',
            [$sessionId]
        );

        if (!$progress) {
            ResponseFormatter::error('Upload progress bulunamadı', 404);
            return;
        }

        ResponseFormatter::success($progress);
        return;
    }

    /**
     * Dosya indir
     */
    public function download($id)
    {
        Auth::require();

        $file = $this->fileUploadService->getFileInfo($id);
        if (!$file || !file_exists($file['file_path'])) {
            ResponseFormatter::error('Dosya bulunamadı', 404);
            return;
        }

        // Access log
        $this->logFileAccess($id, 'download');

        // Dosyayı indir
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
        header('Content-Length: ' . $file['file_size']);
        readfile($file['file_path']);
        exit;
    }

    /**
     * Dosya görüntüle
     */
    public function view($id)
    {
        Auth::require();

        $file = $this->fileUploadService->getFileInfo($id);
        if (!$file || !file_exists($file['file_path'])) {
            ResponseFormatter::error('Dosya bulunamadı', 404);
            return;
        }

        // Access log
        $this->logFileAccess($id, 'view');

        // Dosyayı göster
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Length: ' . $file['file_size']);
        readfile($file['file_path']);
        exit;
    }

    /**
     * Thumbnail göster
     */
    public function thumbnail($id)
    {
        Auth::require();

        $file = $this->fileUploadService->getFileInfo($id);
        if (!$file) {
            ResponseFormatter::error('Dosya bulunamadı', 404);
            return;
        }

        $thumbnailPath = $file['thumbnail_path'];
        if (!$thumbnailPath || !file_exists($thumbnailPath)) {
            // Thumbnail yoksa varsayılan ikon göster
            $this->showDefaultThumbnail($file['mime_type']);
            return;
        }

        header('Content-Type: ' . mime_content_type($thumbnailPath));
        header('Content-Length: ' . filesize($thumbnailPath));
        readfile($thumbnailPath);
        exit;
    }

    /**
     * Dosya sil
     */
    public function delete($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            return View::json(['success' => false, 'error' => 'CSRF doğrulaması başarısız'], 403);
        }

        try {
            $file = $this->fileUploadService->getFileInfo($id);
            if (!$file) {
                throw new Exception('Dosya bulunamadı');
            }

            // Yetki kontrolü
            if ($file['uploaded_by'] != Auth::id() && !Auth::hasRole('admin')) {
                throw new Exception('Bu dosyayı silme yetkiniz yok');
            }

            $result = $this->fileUploadService->deleteFile($id);
            if (!$result) {
                throw new Exception('Dosya silinemedi');
            }

            // Access log
            $this->logFileAccess($id, 'delete');

            ResponseFormatter::success(['message' => 'Dosya başarıyla silindi']);
            return;

        } catch (Exception $e) {
            ResponseFormatter::error($e->getMessage(), 400);
            return;
        }
    }

    /**
     * Dosya listesi
     */
    public function list()
    {
        Auth::require();

        $filters = [
            'category' => $_GET['category'] ?? null,
            'entity_type' => $_GET['entity_type'] ?? null,
            'entity_id' => $_GET['entity_id'] ?? null,
            'uploaded_by' => $_GET['uploaded_by'] ?? null
        ];

        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);

        $files = $this->fileUploadService->getFiles($filters, $limit, $offset);

        ResponseFormatter::success([
            'files' => $files,
            'total' => count($files),
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Chunk'ları birleştir
     */
    private function combineChunks($sessionId, $filename, $totalChunks)
    {
        $chunkDir = __DIR__ . '/../../storage/temp/chunks/' . $sessionId . '/';
        $finalFile = $chunkDir . $filename;

        $handle = fopen($finalFile, 'wb');
        if (!$handle) {
            throw new Exception('Final dosya oluşturulamadı');
        }

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFile = $chunkDir . $filename . '.part' . $i;
            if (file_exists($chunkFile)) {
                $chunkHandle = fopen($chunkFile, 'rb');
                if ($chunkHandle) {
                    stream_copy_to_stream($chunkHandle, $handle);
                    fclose($chunkHandle);
                    unlink($chunkFile);
                }
            }
        }

        fclose($handle);
        return $finalFile;
    }

    /**
     * Final dosyayı işle
     */
    private function processFinalFile($filePath, $sessionId)
    {
        $options = [
            'category' => 'documents',
            'generate_thumbnails' => true,
            'compress_images' => true
        ];

        $file = [
            'name' => basename($filePath),
            'type' => mime_content_type($filePath),
            'tmp_name' => $filePath,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($filePath)
        ];

        $result = $this->fileUploadService->uploadSingle($file, $options);

        // Temp dosyayı sil
        unlink($filePath);
        rmdir(dirname($filePath));

        // Progress'i tamamlandı olarak işaretle
        $this->db->update('file_upload_progress', [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s')
        ], 'session_id = ?', [$sessionId]);

        return $result;
    }

    /**
     * Upload progress güncelle
     */
    private function updateUploadProgress($sessionId, $filename, $chunkNumber, $totalChunks)
    {
        $progress = $this->db->fetch(
            'SELECT * FROM file_upload_progress WHERE session_id = ? AND filename = ?',
            [$sessionId, $filename]
        );

        if ($progress) {
            $this->db->update('file_upload_progress', [
                'uploaded_size' => $chunkNumber + 1,
                'status' => $chunkNumber == $totalChunks - 1 ? 'processing' : 'uploading'
            ], 'id = ?', [$progress['id']]);
        } else {
            $this->db->insert('file_upload_progress', [
                'session_id' => $sessionId,
                'filename' => $filename,
                'total_size' => $totalChunks,
                'uploaded_size' => $chunkNumber + 1,
                'status' => 'uploading'
            ]);
        }
    }

    /**
     * Dosya erişim logu
     */
    private function logFileAccess($fileId, $action)
    {
        $this->db->insert('file_access_logs', [
            'file_id' => $fileId,
            'user_id' => Auth::id(),
            'action' => $action,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }

    /**
     * Varsayılan thumbnail göster
     */
    private function showDefaultThumbnail($mimeType)
    {
        $iconPath = __DIR__ . '/../../public/assets/icons/';
        
        if (strpos($mimeType, 'image/') === 0) {
            $iconFile = $iconPath . 'image.png';
        } elseif (strpos($mimeType, 'application/pdf') === 0) {
            $iconFile = $iconPath . 'pdf.png';
        } elseif (strpos($mimeType, 'text/') === 0) {
            $iconFile = $iconPath . 'text.png';
        } else {
            $iconFile = $iconPath . 'file.png';
        }

        if (file_exists($iconFile)) {
            header('Content-Type: image/png');
            readfile($iconFile);
        } else {
            // SVG icon oluştur
            $svg = '<svg width="64" height="64" xmlns="http://www.w3.org/2000/svg">
                <rect width="64" height="64" fill="#e5e7eb"/>
                <text x="32" y="40" text-anchor="middle" font-family="Arial" font-size="24" fill="#6b7280">📄</text>
            </svg>';
            header('Content-Type: image/svg+xml');
            echo $svg;
        }
        exit;
    }
}
