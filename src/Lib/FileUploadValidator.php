<?php
/**
 * Secure File Upload Validator
 */

class FileUploadValidator
{
    private static $allowedMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/jpg',
        'image/png'
    ];
    
    private static $allowedExtensions = [
        'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'
    ];
    
    private static $maxFileSize = 10485760; // 10MB
    
    public static function validate($file, $options = [])
    {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Dosya yüklenemedi';
            return $errors;
        }
        
        // ===== ERR-013 FIX: Enhanced file size validation =====
        // Check file size
        $maxSize = $options['max_size'] ?? self::$maxFileSize;
        
        // File size must be positive and within limits
        if ($file['size'] <= 0) {
            $errors[] = 'Dosya boyutu geçersiz';
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = 'Dosya boyutu çok büyük. Maksimum ' . self::formatBytes($maxSize) . ' olabilir (Mevcut: ' . self::formatBytes($file['size']) . ')';
        }
        
        // Minimum file size check (empty files are suspicious)
        if ($file['size'] < 10) {
            $errors[] = 'Dosya boyutu çok küçük (şüpheli)';
        }
        // ===== ERR-013 FIX: End =====
        
        // ===== ERR-013 FIX: Enhanced file extension validation =====
        // Check file extension
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
        
        // Double extension check (e.g., file.php.jpg)
        if (preg_match('/\.(php|php3|php4|php5|phtml|phar|exe|bat|cmd|com|scr|vbs|js|jar|sh|py|rb|pl|cgi)\./i', $file['name'])) {
            $errors[] = 'Dosya adında tehlikeli uzantı tespit edildi';
        }
        
        $allowedExts = $options['allowed_extensions'] ?? self::$allowedExtensions;
        if (!in_array($extension, $allowedExts)) {
            $errors[] = 'Geçersiz dosya türü. İzin verilen türler: ' . implode(', ', $allowedExts);
        }
        // ===== ERR-013 FIX: End =====
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = $options['allowed_mimes'] ?? self::$allowedMimeTypes;
        if (!in_array($mimeType, $allowedMimes)) {
            $errors[] = 'Geçersiz dosya türü. MIME type: ' . $mimeType;
        }
        
        // Check for malicious content
        if (self::containsMaliciousContent($file['tmp_name'])) {
            $errors[] = 'Dosya güvenlik taramasından geçemedi';
        }
        
        return $errors;
    }
    
    public static function generateSecureFilename($originalName)
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $hash = md5(uniqid(rand(), true));
        return $hash . '.' . $extension;
    }
    
    public static function moveToSecureLocation($file, $destination)
    {
        $secureFilename = self::generateSecureFilename($file['name']);
        $fullPath = $destination . '/' . $secureFilename;
        
        // Ensure directory exists
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            // Set secure permissions
            chmod($fullPath, 0644);
            return $secureFilename;
        }
        
        return false;
    }
    
    private static function containsMaliciousContent($filePath)
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
        
        // For uploaded files, ensure it's in temp directory
        $tempDir = sys_get_temp_dir();
        $realFilePath = realpath($filePath);
        if ($realFilePath === false || strpos($realFilePath, $tempDir) !== 0) {
            // If not in temp dir, check if it's an uploaded file
            if (!is_uploaded_file($filePath)) {
                return true; // Not an uploaded file and not in temp = treat as malicious
            }
        }
        // ===== ERR-005 FIX: End =====
        
        $content = file_get_contents($filePath, false, null, 0, 2048); // Check first 2KB (artırıldı)
        
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
    
    private static function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    public static function getAllowedTypes()
    {
        return [
            'extensions' => self::$allowedExtensions,
            'mime_types' => self::$allowedMimeTypes,
            'max_size' => self::$maxFileSize
        ];
    }
}
