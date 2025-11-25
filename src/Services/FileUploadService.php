<?php
/**
 * Enhanced File Upload Service
 * Gelişmiş dosya yükleme servisi
 */

class FileUploadService
{
    private static $instance = null;
    private $db;
    private $config;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = [
            'max_file_size' => 50 * 1024 * 1024, // 50MB
            'allowed_types' => [
                'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
                'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
                'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
                'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv'],
                'audio' => ['mp3', 'wav', 'ogg', 'm4a']
            ],
            'upload_dirs' => [
                'documents' => 'storage/documents/',
                'images' => 'storage/images/',
                'contracts' => 'storage/contracts/',
                'jobs' => 'storage/jobs/',
                'temp' => 'storage/temp/'
            ],
            'scan_antivirus' => false, // Antivirus tarama (gelişmiş güvenlik)
            'generate_thumbnails' => true, // Resimler için thumbnail oluştur
            'watermark' => false, // Resimlere watermark ekle
            'compress_images' => true // Resimleri sıkıştır
        ];
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Çoklu dosya yükleme
     */
    public function uploadMultiple($files, $options = [])
    {
        $results = [];
        $errors = [];

        if (!is_array($files['name'])) {
            // Tek dosya
            return $this->uploadSingle($files, $options);
        }

        $fileCount = count($files['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            try {
                $result = $this->uploadSingle($file, $options);
                $results[] = $result;
            } catch (Exception $e) {
                $errors[] = "Dosya '{$file['name']}': " . $e->getMessage();
            }
        }

        return [
            'success' => count($errors) === 0,
            'files' => $results,
            'errors' => $errors,
            'total' => $fileCount,
            'successful' => count($results)
        ];
    }

    /**
     * Tek dosya yükleme
     */
    public function uploadSingle($file, $options = [])
    {
        // Dosya doğrulama
        $validation = $this->validateFile($file, $options);
        if (!$validation['valid']) {
            throw new Exception(implode(', ', $validation['errors']));
        }

        // Upload dizini belirle
        $uploadDir = $this->getUploadDirectory($options);
        $this->ensureDirectoryExists($uploadDir);

        // Güvenli dosya adı oluştur
        $filename = $this->generateSecureFilename($file['name'], $options);
        $filePath = $uploadDir . $filename;

        // Dosyayı taşı
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Dosya yüklenirken hata oluştu');
        }

        // Dosya işlemleri
        $processedFile = $this->processFile($filePath, $file, $options);

        // Veritabanına kaydet
        $fileData = $this->saveFileRecord($processedFile, $options);

        return [
            'id' => $fileData['id'],
            'filename' => $filename,
            'original_name' => $file['name'],
            'file_path' => $processedFile['path'],
            'file_size' => $processedFile['size'],
            'mime_type' => $processedFile['mime_type'],
            'thumbnail' => $processedFile['thumbnail'] ?? null,
            'uploaded_at' => $fileData['created_at']
        ];
    }

    /**
     * Dosya doğrulama
     */
    private function validateFile($file, $options = [])
    {
        $errors = [];
        $maxSize = $options['max_size'] ?? $this->config['max_file_size'];
        $allowedTypes = $options['allowed_types'] ?? null;

        // Temel kontroller
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($file['error']);
        }

        // ===== ERR-013 FIX: Enhanced file size validation =====
        // File size must be positive and within limits
        if ($file['size'] <= 0) {
            $errors[] = 'Dosya boyutu geçersiz';
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = 'Dosya boyutu çok büyük. Maksimum: ' . $this->formatBytes($maxSize) . ' (Mevcut: ' . $this->formatBytes($file['size']) . ')';
        }
        
        // Minimum file size check (empty files are suspicious)
        if ($file['size'] < 10) {
            $errors[] = 'Dosya boyutu çok küçük (şüpheli)';
        }
        // ===== ERR-013 FIX: End =====

        // ===== ERR-013 FIX: Enhanced file type validation =====
        // Dosya türü kontrolü
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Extension boş veya çok uzun olmamalı
        if (empty($extension) || strlen($extension) > 10) {
            $errors[] = 'Geçersiz dosya uzantısı';
        }
        
        // Dangerous extensions check
        $dangerousExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js', 'jar', 'sh', 'py', 'rb', 'pl', 'cgi'];
        if (in_array($extension, $dangerousExtensions, true)) {
            $errors[] = 'Bu dosya türü güvenlik nedeniyle yüklenemez: ' . $extension;
        }
        
        $mimeType = $this->getMimeType($file['tmp_name']);
        
        // MIME type boş olmamalı
        if (empty($mimeType) || $mimeType === false) {
            $errors[] = 'Dosya türü tespit edilemedi';
        }

        if ($allowedTypes) {
            if (!in_array($extension, $allowedTypes)) {
                $errors[] = 'Geçersiz dosya türü. İzin verilen türler: ' . implode(', ', $allowedTypes);
            }
        } else {
            $validType = false;
            foreach ($this->config['allowed_types'] as $category => $types) {
                if (in_array($extension, $types)) {
                    $validType = true;
                    break;
                }
            }
            if (!$validType) {
                $errors[] = 'Geçersiz dosya türü: ' . $extension;
            }
        }

        // MIME type kontrolü (extension ile uyumluluk)
        if (!$this->isValidMimeType($mimeType, $extension)) {
            $errors[] = 'Dosya türü ile içerik uyuşmuyor (MIME: ' . $mimeType . ', Extension: ' . $extension . ')';
        }
        
        // ===== ERR-013 FIX: Magic bytes validation (content-based file type detection) =====
        $detectedType = $this->detectFileTypeByMagicBytes($file['tmp_name']);
        if ($detectedType && $detectedType !== $extension) {
            // Allow some flexibility for similar types (e.g., jpg/jpeg)
            $similarTypes = [
                'jpg' => ['jpeg'],
                'jpeg' => ['jpg'],
                'tiff' => ['tif'],
                'tif' => ['tiff']
            ];
            $isSimilar = false;
            if (isset($similarTypes[$extension])) {
                $isSimilar = in_array($detectedType, $similarTypes[$extension], true);
            }
            if (!$isSimilar) {
                $errors[] = 'Dosya içeriği ile uzantı uyuşmuyor (Tespit edilen: ' . $detectedType . ', Uzantı: ' . $extension . ')';
            }
        }
        // ===== ERR-013 FIX: End =====
        
        // Double extension check (e.g., file.php.jpg)
        if (preg_match('/\.(php|php3|php4|php5|phtml|phar|exe|bat|cmd|com|scr|vbs|js|jar|sh|py|rb|pl|cgi)\./i', $file['name'])) {
            $errors[] = 'Dosya adında tehlikeli uzantı tespit edildi';
        }
        // ===== ERR-013 FIX: End =====

        // Zararlı içerik kontrolü
        if ($this->containsMaliciousContent($file['tmp_name'])) {
            $errors[] = 'Dosya güvenlik kontrolünden geçemedi';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Dosya işleme (thumbnail, sıkıştırma, vb.)
     */
    private function processFile($filePath, $originalFile, $options = [])
    {
        $result = [
            'path' => $filePath,
            'size' => filesize($filePath),
            'mime_type' => $this->getMimeType($filePath)
        ];

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Resim işlemleri
        if (in_array($extension, $this->config['allowed_types']['image'])) {
            // Thumbnail oluştur
            if ($this->config['generate_thumbnails']) {
                $thumbnail = $this->generateThumbnail($filePath, $options);
                if ($thumbnail) {
                    $result['thumbnail'] = $thumbnail;
                }
            }

            // Resim sıkıştırma
            if ($this->config['compress_images']) {
                $this->compressImage($filePath, $options);
                $result['size'] = filesize($filePath);
            }

            // Watermark ekle
            if ($this->config['watermark']) {
                $this->addWatermark($filePath, $options);
            }
        }

        return $result;
    }

    /**
     * Thumbnail oluştur
     */
    private function generateThumbnail($filePath, $options = [])
    {
        $thumbnailDir = dirname($filePath) . '/thumbnails/';
        $this->ensureDirectoryExists($thumbnailDir);

        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $thumbnailPath = $thumbnailDir . $filename . '_thumb.' . $extension;

        $maxWidth = $options['thumb_width'] ?? 300;
        $maxHeight = $options['thumb_height'] ?? 300;

        if ($this->createThumbnail($filePath, $thumbnailPath, $maxWidth, $maxHeight)) {
            return $thumbnailPath;
        }

        return null;
    }

    /**
     * Thumbnail oluşturma yardımcı fonksiyonu
     */
    private function createThumbnail($source, $destination, $maxWidth, $maxHeight)
    {
        $imageInfo = getimagesize($source);
        if (!$imageInfo) {
            return false;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];

        // Orijinal resmi yükle
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($source);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($source);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($source);
                break;
            default:
                return false;
        }

        if (!$sourceImage) {
            return false;
        }

        // Boyutları hesapla
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);

        // Thumbnail oluştur
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Kaydet
        $success = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($thumbnail, $destination, 85);
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($thumbnail, $destination, 8);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($thumbnail, $destination);
                break;
            case IMAGETYPE_WEBP:
                $success = imagewebp($thumbnail, $destination, 85);
                break;
        }

        imagedestroy($sourceImage);
        imagedestroy($thumbnail);

        return $success;
    }

    /**
     * Resim sıkıştırma
     */
    private function compressImage($filePath, $options = [])
    {
        $quality = $options['compression_quality'] ?? 85;
        $imageInfo = getimagesize($filePath);
        
        if (!$imageInfo) {
            return false;
        }

        $type = $imageInfo[2];
        $sourceImage = null;

        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($filePath);
                if ($sourceImage) {
                    imagejpeg($sourceImage, $filePath, $quality);
                }
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($filePath);
                if ($sourceImage) {
                    imagepng($sourceImage, $filePath, 9 - (int)($quality / 10));
                }
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($filePath);
                if ($sourceImage) {
                    imagewebp($sourceImage, $filePath, $quality);
                }
                break;
        }

        if ($sourceImage) {
            imagedestroy($sourceImage);
            return true;
        }

        return false;
    }

    /**
     * Watermark ekle
     */
    private function addWatermark($filePath, $options = [])
    {
        // Watermark implementasyonu burada yapılabilir
        // Şimdilik boş bırakıyoruz
        return true;
    }

    /**
     * Güvenli dosya adı oluştur
     */
    private function generateSecureFilename($originalName, $options = [])
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $prefix = $options['prefix'] ?? '';
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        
        return $prefix . $timestamp . '_' . $random . '.' . $extension;
    }

    /**
     * Upload dizini belirle
     * ===== PHASE 2: Absolute Path Resolution =====
     */
    private function getUploadDirectory($options = [])
    {
        $category = $options['category'] ?? 'documents';
        $baseDir = $this->config['upload_dirs'][$category] ?? $this->config['upload_dirs']['documents'];
        
        // Use APP_ROOT if defined, otherwise fallback to relative path
        if (defined('APP_ROOT')) {
            return APP_ROOT . '/' . ltrim($baseDir, '/');
        }
        return __DIR__ . '/../../' . $baseDir;
    }

    /**
     * Dizin oluştur
     * ===== PHASE 3: Directory Auto-Creation with Permissions =====
     */
    private function ensureDirectoryExists($dir)
    {
        if (!is_dir($dir)) {
            // ===== ERR-022 FIX: Replace error suppression with proper error handling =====
            try {
                if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                    throw new RuntimeException("Directory creation failed: {$dir}");
                }
                // Try to set permissions after creation
                if (!chmod($dir, 0775)) {
                    // Log warning but don't fail if chmod fails
                    if (class_exists('Logger')) {
                        Logger::warning("Failed to set directory permissions: {$dir}");
                    }
                }
            } catch (Exception $e) {
                if (class_exists('Logger')) {
                    Logger::error("Directory creation error: " . $e->getMessage());
                }
                throw $e;
            }
            // ===== ERR-022 FIX: End =====
        } else {
            // Ensure directory is writable
            if (!is_writable($dir)) {
                // ===== ERR-022 FIX: Replace error suppression with proper error handling =====
                try {
                    if (!chmod($dir, 0775)) {
                        if (class_exists('Logger')) {
                            Logger::warning("Failed to set directory permissions: {$dir}");
                        }
                    }
                } catch (Exception $e) {
                    if (class_exists('Logger')) {
                        Logger::warning("Error setting directory permissions: " . $e->getMessage());
                    }
                }
                // If still not writable and not production, try 0777
                $isProduction = defined('APP_DEBUG') && !APP_DEBUG;
                if (!is_writable($dir) && !$isProduction) {
                    try {
                        if (!chmod($dir, 0777)) {
                            if (class_exists('Logger')) {
                                Logger::warning("Failed to set directory permissions (777): {$dir}");
                            }
                        }
                    } catch (Exception $e) {
                        if (class_exists('Logger')) {
                            Logger::warning("Error setting directory permissions (777): " . $e->getMessage());
                        }
                    }
                }
                // ===== ERR-022 FIX: End =====
            }
        }
    }

    /**
     * Dosya kaydını veritabanına kaydet
     */
    private function saveFileRecord($fileData, $options = [])
    {
        $data = [
            'original_name' => $fileData['original_name'] ?? '',
            'filename' => basename($fileData['path']),
            'file_path' => $fileData['path'],
            'file_size' => $fileData['size'],
            'mime_type' => $fileData['mime_type'],
            'category' => $options['category'] ?? 'documents',
            'entity_type' => $options['entity_type'] ?? null,
            'entity_id' => $options['entity_id'] ?? null,
            'uploaded_by' => Auth::id(),
            'thumbnail_path' => $fileData['thumbnail'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Add company_id if available (multi-tenancy support)
        if (Auth::check() && method_exists('Auth', 'companyId')) {
            $companyId = Auth::companyId();
            if ($companyId) {
                $data['company_id'] = $companyId;
            }
        }

        return $this->db->insert('file_uploads', $data);
    }

    /**
     * MIME type al
     */
    private function getMimeType($filePath)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            return $mimeType;
        }

        return mime_content_type($filePath);
    }
    
    /**
     * Detect file type by magic bytes (file signature)
     * ===== ERR-013 FIX: Magic bytes validation =====
     */
    private function detectFileTypeByMagicBytes($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }
        
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return null;
        }
        
        $bytes = fread($handle, 12);
        fclose($handle);
        
        if (strlen($bytes) < 4) {
            return null;
        }
        
        // Magic bytes signatures
        $signatures = [
            // Images
            "\xFF\xD8\xFF" => 'jpg',
            "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" => 'png',
            "GIF87a" => 'gif',
            "GIF89a" => 'gif',
            "RIFF" => (substr($bytes, 8, 4) === "WEBP" ? 'webp' : null),
            // Documents
            "%PDF" => 'pdf',
            "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" => 'doc', // MS Office
            "PK\x03\x04" => (substr($bytes, 30, 4) === "word" ? 'docx' : (substr($bytes, 30, 4) === "xl" ? 'xlsx' : 'zip')),
            // Archives
            "Rar!\x1A\x07" => 'rar',
            "\x1F\x8B" => 'gz',
            // Audio/Video
            "\xFF\xFB" => 'mp3',
            "\xFF\xF3" => 'mp3',
            "\xFF\xF2" => 'mp3',
            "RIFF" => (substr($bytes, 8, 4) === "WAVE" ? 'wav' : null),
            "\x00\x00\x00\x20\x66\x74\x79\x70" => 'mp4',
        ];
        
        foreach ($signatures as $signature => $type) {
            if (strpos($bytes, $signature) === 0) {
                return $type;
            }
        }
        
        return null;
    }
    // ===== ERR-013 FIX: End =====

    /**
     * MIME type doğrulama
     */
    private function isValidMimeType($mimeType, $extension)
    {
        // ===== ERR-013 FIX: Enhanced MIME type validation =====
        $validMimes = [
            'jpg' => ['image/jpeg', 'image/jpg'],
            'jpeg' => ['image/jpeg', 'image/jpg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml', 'image/svg+xml;charset=utf-8'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'txt' => ['text/plain', 'text/plain; charset=utf-8'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            'rar' => ['application/x-rar-compressed', 'application/vnd.rar'],
            '7z' => ['application/x-7z-compressed'],
            'tar' => ['application/x-tar'],
            'gz' => ['application/gzip'],
            'mp4' => ['video/mp4'],
            'avi' => ['video/x-msvideo'],
            'mov' => ['video/quicktime'],
            'wmv' => ['video/x-ms-wmv'],
            'flv' => ['video/x-flv'],
            'mp3' => ['audio/mpeg', 'audio/mp3'],
            'wav' => ['audio/wav', 'audio/x-wav'],
            'ogg' => ['audio/ogg'],
            'm4a' => ['audio/mp4', 'audio/x-m4a']
        ];

        // Check if extension is in whitelist
        if (!isset($validMimes[$extension])) {
            Logger::warning('Invalid file extension', [
                'extension' => $extension,
                'mime_type' => $mimeType
            ]);
            return false;
        }

        // Check if MIME type matches extension
        $isValid = in_array($mimeType, $validMimes[$extension], true);
        if (!$isValid) {
            Logger::warning('MIME type mismatch', [
                'extension' => $extension,
                'mime_type' => $mimeType,
                'expected' => $validMimes[$extension]
            ]);
        }
        // ===== ERR-013 FIX: End =====

        return $isValid;
    }

    /**
     * Zararlı içerik kontrolü
     */
    private function containsMaliciousContent($filePath)
    {
        // ===== ERR-005 FIX: Validate file path before reading =====
        if (!is_string($filePath) || empty($filePath)) {
            return true; // Invalid path = treat as malicious
        }
        
        // Normalize and validate path
        $normalizedPath = InputSanitizer::filePath($filePath);
        if ($normalizedPath === null) {
            return true; // Path traversal detected = treat as malicious
        }
        
        // Ensure file exists and is readable
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return true; // Cannot read = treat as malicious
        }
        
        // Ensure file is within allowed directories (uploads directory)
        $uploadsDir = realpath(__DIR__ . '/../../uploads');
        $realFilePath = realpath($filePath);
        if ($realFilePath === false || strpos($realFilePath, $uploadsDir) !== 0) {
            return true; // File outside allowed directory = treat as malicious
        }
        // ===== ERR-005 FIX: End =====
        
        $content = file_get_contents($filePath, false, null, 0, 2048); // İlk 2KB (artırıldı)
        
        // ===== ERR-013 FIX: Enhanced malicious content detection =====
        $maliciousPatterns = [
            // PHP code execution
            '/<\?php/i',
            '/<\?=/i',
            '/<\?/i',
            '/eval\s*\(/i',
            '/base64_decode\s*\(/i',
            '/gzinflate\s*\(/i',
            '/str_rot13\s*\(/i',
            '/assert\s*\(/i',
            '/preg_replace\s*\([^)]*\/e/i',
            '/create_function\s*\(/i',
            // System commands
            '/system\s*\(/i',
            '/exec\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
            '/proc_open\s*\(/i',
            '/popen\s*\(/i',
            '/pcntl_exec\s*\(/i',
            // File operations
            '/file_get_contents\s*\([^)]*http/i',
            '/fopen\s*\([^)]*http/i',
            '/curl_exec\s*\(/i',
            '/include\s*\(/i',
            '/require\s*\(/i',
            // JavaScript/HTML injection
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i',
            // SQL injection patterns in files
            '/union\s+select/i',
            '/drop\s+table/i',
            '/delete\s+from/i',
            // Shell commands
            '/\/bin\/(sh|bash|zsh)/i',
            '/cmd\.exe/i',
            '/powershell/i',
        ];
        // ===== ERR-013 FIX: End =====

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                Logger::warning('Malicious content detected in uploaded file', [
                    'file' => basename($filePath),
                    'pattern' => $pattern
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Upload hata mesajları
     */
    private function getUploadErrorMessage($errorCode)
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'Dosya boyutu çok büyük',
            UPLOAD_ERR_FORM_SIZE => 'Form boyutu çok büyük',
            UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi',
            UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi',
            UPLOAD_ERR_NO_TMP_DIR => 'Geçici dizin bulunamadı',
            UPLOAD_ERR_CANT_WRITE => 'Dosya yazılamadı',
            UPLOAD_ERR_EXTENSION => 'Dosya yükleme uzantı tarafından durduruldu'
        ];

        return $messages[$errorCode] ?? 'Bilinmeyen hata';
    }

    /**
     * Byte formatı
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Dosya sil
     */
    public function deleteFile($fileId)
    {
        $file = $this->db->fetch('SELECT * FROM file_uploads WHERE id = ?', [$fileId]);
        if (!$file) {
            return false;
        }
        
        // Verify company access (multi-tenancy security)
        if (Auth::check() && method_exists('Auth', 'companyId')) {
            // SUPERADMIN can delete any file
            if (!Auth::isSuperAdmin()) {
                $userCompanyId = Auth::companyId();
                $fileCompanyId = $file['company_id'] ?? null;
                
                // If file has company_id, verify it matches user's company
                if ($fileCompanyId !== null && $fileCompanyId !== $userCompanyId) {
                    return false; // Access denied
                }
                
                // If file doesn't have company_id but user has one, deny access for security
                if ($fileCompanyId === null && $userCompanyId !== null) {
                    return false; // Legacy files without company_id are not accessible
                }
            }
        }

        // Fiziksel dosyayı sil
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }

        // Thumbnail'i sil
        if ($file['thumbnail_path'] && file_exists($file['thumbnail_path'])) {
            unlink($file['thumbnail_path']);
        }

        // Veritabanından sil
        return $this->db->delete('file_uploads', 'id = ?', [$fileId]);
    }

    /**
     * Dosya bilgilerini al
     */
    public function getFileInfo($fileId)
    {
        $file = $this->db->fetch('SELECT * FROM file_uploads WHERE id = ?', [$fileId]);
        if (!$file) {
            return null;
        }
        
        // Verify company access (multi-tenancy security)
        if (Auth::check() && method_exists('Auth', 'companyId')) {
            // SUPERADMIN can see any file
            if (!Auth::isSuperAdmin()) {
                $userCompanyId = Auth::companyId();
                $fileCompanyId = $file['company_id'] ?? null;
                
                // If file has company_id, verify it matches user's company
                if ($fileCompanyId !== null && $fileCompanyId !== $userCompanyId) {
                    return null; // Access denied
                }
                
                // If file doesn't have company_id but user has one, deny access for security
                if ($fileCompanyId === null && $userCompanyId !== null) {
                    return null; // Legacy files without company_id are not accessible
                }
            }
        }
        
        return $file;
    }

    /**
     * Dosya listesi
     */
    public function getFiles($filters = [], $limit = 50, $offset = 0)
    {
        $where = [];
        $params = [];

        // Add company scope (multi-tenancy security)
        if (Auth::check() && method_exists('Auth', 'companyId')) {
            // SUPERADMIN can see all files
            if (!Auth::isSuperAdmin()) {
                $companyId = Auth::companyId();
                if ($companyId !== null) {
                    $where[] = 'company_id = ?';
                    $params[] = $companyId;
                } else {
                    // User has no company, return empty result
                    $where[] = '1 = 0';
                }
            }
        }

        if (!empty($filters['category'])) {
            $where[] = 'category = ?';
            $params[] = $filters['category'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = 'entity_type = ?';
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['entity_id'])) {
            $where[] = 'entity_id = ?';
            $params[] = $filters['entity_id'];
        }

        if (!empty($filters['uploaded_by'])) {
            $where[] = 'uploaded_by = ?';
            $params[] = $filters['uploaded_by'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT * FROM file_uploads {$whereSql} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }
}
