<?php
/**
 * Error Messages - Centralized error message management
 * Provides consistent, user-friendly error messages throughout the application
 */

class ErrorMessages
{
    private static $messages = [
        // Validation Messages
        'VALIDATION_REQUIRED' => 'Bu alan zorunludur',
        'VALIDATION_EMAIL' => 'Geçerli bir e-posta adresi giriniz',
        'VALIDATION_PHONE' => 'Geçerli bir telefon numarası giriniz (10-11 hane)',
        'VALIDATION_MIN' => 'Minimum {min} karakter olmalıdır',
        'VALIDATION_MAX' => 'Maksimum {max} karakter olmalıdır',
        'VALIDATION_URL' => 'Geçerli bir URL giriniz',
        'VALIDATION_NUMERIC' => 'Sadece sayı girebilirsiniz',
        'VALIDATION_DECIMAL' => 'Geçerli bir ondalık sayı giriniz',
        'VALIDATION_PATTERN' => 'Geçersiz format',
        
        // Business Logic Errors
        'CUSTOMER_NOT_FOUND' => 'Müşteri bulunamadı',
        'JOB_NOT_FOUND' => 'İş bulunamadı',
        'JOB_CREATE_FAILED' => 'İş oluşturulamadı: {reason}',
        'JOB_UPDATE_FAILED' => 'İş güncellenemedi: {reason}',
        'JOB_DELETE_FAILED' => 'İş silinemedi: {reason}',
        
        'PERMISSION_DENIED' => 'Bu işlem için yetkiniz yok',
        'DUPLICATE_ENTRY' => '{field} zaten kayıtlı',
        
        'INVALID_DATE' => 'Geçersiz tarih formatı',
        'INVALID_TIME' => 'Geçersiz saat formatı',
        'DATE_PAST' => 'Tarih geçmişte olamaz',
        'DATE_FUTURE' => 'Tarih gelecekte olamaz',
        
        'INVALID_STATUS' => 'Geçersiz durum',
        'INVALID_AMOUNT' => 'Geçersiz tutar',
        
        // File Upload Errors
        'FILE_TOO_LARGE' => 'Dosya çok büyük (Maksimum: {max}MB)',
        'FILE_TYPE_NOT_ALLOWED' => 'Bu dosya türüne izin verilmiyor',
        'FILE_UPLOAD_FAILED' => 'Dosya yüklenemedi',
        
        // Authentication Errors
        'LOGIN_FAILED' => 'Kullanıcı adı veya şifre hatalı',
        'LOGIN_REQUIRED' => 'Bu sayfaya erişmek için giriş yapmalısınız',
        'SESSION_EXPIRED' => 'Oturumunuzun süresi doldu. Lütfen tekrar giriş yapın',
        'PASSWORD_MISMATCH' => 'Şifreler eşleşmiyor',
        'PASSWORD_WEAK' => 'Şifre yeterince güçlü değil',
        
        // System Errors
        'NETWORK_ERROR' => 'İnternet bağlantısı hatası. Lütfen tekrar deneyin',
        'SERVER_ERROR' => 'Sunucu hatası. Lütfen daha sonra tekrar deneyin',
        'DATABASE_ERROR' => 'Veritabanı hatası oluştu',
        'OPERATION_FAILED' => 'İşlem başarısız oldu',
        'OPERATION_SUCCESS' => 'İşlem başarıyla tamamlandı',
        
        // Generic
        'GENERIC_ERROR' => 'Bir hata oluştu. Lütfen tekrar deneyin',
        'NOT_FOUND' => 'Aradığınız kayıt bulunamadı',
        'UNAUTHORIZED' => 'Yetkisiz erişim',
        'FORBIDDEN' => 'Bu işlemi yapmaya yetkiniz yok',
    ];
    
    /**
     * Get error message by code
     * 
     * @param string $code Error code
     * @param array $params Parameters to replace in message
     * @return string Error message
     */
    public static function get($code, $params = [])
    {
        $message = self::$messages[$code] ?? self::$messages['GENERIC_ERROR'];
        
        // Replace parameters
        foreach ($params as $key => $value) {
            $message = str_replace("{{$key}}", htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $message);
        }
        
        return $message;
    }
    
    /**
     * Check if error code exists
     * 
     * @param string $code Error code
     * @return bool
     */
    public static function exists($code)
    {
        return isset(self::$messages[$code]);
    }
    
    /**
     * Get all messages
     * 
     * @return array All error messages
     */
    public static function all()
    {
        return self::$messages;
    }
    
    /**
     * Add custom message
     * 
     * @param string $code Message code
     * @param string $message Message text
     */
    public static function add($code, $message)
    {
        self::$messages[$code] = $message;
    }
    
    /**
     * Format error response
     * 
     * @param string $code Error code
     * @param array $params Parameters
     * @param int $status HTTP status code
     * @return array
     */
    public static function response($code, $params = [], $status = 400)
    {
        return [
            'success' => false,
            'error' => $code,
            'message' => self::get($code, $params),
            'status' => $status
        ];
    }
    
    /**
     * Format success response
     * 
     * @param string $message Success message
     * @param mixed $data Data to return
     * @return array
     */
    public static function success($message = null, $data = [])
    {
        return [
            'success' => true,
            'message' => $message ?? self::get('OPERATION_SUCCESS'),
            'data' => $data
        ];
    }
}

