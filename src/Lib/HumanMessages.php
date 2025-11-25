<?php
/**
 * Human-Friendly Messages - UX-MED-005
 * 
 * Converts technical errors to user-friendly messages
 * Provides contextual, helpful error messages
 */

class HumanMessages
{
    /**
     * Convert technical error to human-friendly message
     */
    public static function error($technicalError, $context = [])
    {
        // Database errors
        if (stripos($technicalError, 'SQLSTATE') !== false || stripos($technicalError, 'database') !== false) {
            return self::databaseError($technicalError, $context);
        }
        
        // Validation errors
        if (stripos($technicalError, 'validation') !== false || stripos($technicalError, 'required') !== false) {
            return self::validationError($technicalError, $context);
        }
        
        // Authentication errors
        if (stripos($technicalError, 'auth') !== false || stripos($technicalError, 'unauthorized') !== false || stripos($technicalError, 'credentials') !== false || stripos($technicalError, 'password') !== false) {
            return self::authError($technicalError, $context);
        }
        
        // File upload errors
        if (stripos($technicalError, 'file') !== false || stripos($technicalError, 'upload') !== false) {
            return self::fileError($technicalError, $context);
        }
        
        // Generic fallback
        return self::genericError($technicalError);
    }
    
    /**
     * Database error messages
     */
    private static function databaseError($error, $context)
    {
        if (stripos($error, 'foreign key constraint') !== false) {
            $entity = $context['entity'] ?? 'kayıt';
            return "Bu $entity başka kayıtlar tarafından kullanıldığı için silinemiyor. Önce bağlı kayıtları kaldırın.";
        }
        
        if (stripos($error, 'unique constraint') !== false || stripos($error, 'duplicate') !== false) {
            return "Bu değer zaten kullanılıyor. Lütfen farklı bir değer deneyin.";
        }
        
        if (stripos($error, 'not null constraint') !== false) {
            return "Gerekli alanlar boş bırakılamaz. Lütfen tüm zorunlu alanları doldurun.";
        }
        
        return "Veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.";
    }
    
    /**
     * Validation error messages
     */
    private static function validationError($error, $context)
    {
        $field = $context['field'] ?? 'Alan';
        
        if (stripos($error, 'required') !== false) {
            return "$field zorunludur.";
        }
        
        if (stripos($error, 'email') !== false) {
            return "Geçerli bir e-posta adresi girin.";
        }
        
        if (stripos($error, 'phone') !== false) {
            return "Geçerli bir telefon numarası girin (örn: 0555 123 4567).";
        }
        
        if (stripos($error, 'min') !== false) {
            $min = $context['min'] ?? '?';
            return "$field en az $min karakter olmalıdır.";
        }
        
        if (stripos($error, 'max') !== false) {
            $max = $context['max'] ?? '?';
            return "$field en fazla $max karakter olabilir.";
        }
        
        return "Girdiğiniz değer geçerli değil. Lütfen kontrol edin.";
    }
    
    /**
     * Authentication error messages
     */
    private static function authError($error, $context)
    {
        if (stripos($error, 'credentials') !== false || stripos($error, 'password') !== false) {
            return "Kullanıcı adı veya şifre hatalı. Lütfen bilgilerinizi kontrol edip tekrar deneyin.";
        }
        
        if (stripos($error, 'unauthorized') !== false || stripos($error, 'permission') !== false) {
            return "Bu işlem için yetkiniz bulunmuyor. Lütfen yöneticinizle iletişime geçin.";
        }
        
        if (stripos($error, 'session') !== false || stripos($error, 'expired') !== false) {
            return "Oturumunuz sonlandı. Lütfen tekrar giriş yapın.";
        }
        
        if (stripos($error, 'csrf') !== false || stripos($error, 'token') !== false) {
            return "Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyin ve tekrar deneyin.";
        }
        
        return "Kimlik doğrulama hatası. Lütfen tekrar giriş yapın.";
    }
    
    /**
     * File upload error messages
     */
    private static function fileError($error, $context)
    {
        if (stripos($error, 'size') !== false || stripos($error, 'too large') !== false) {
            $maxSize = $context['max_size'] ?? '5MB';
            return "Dosya çok büyük. Maksimum dosya boyutu: $maxSize";
        }
        
        if (stripos($error, 'type') !== false || stripos($error, 'extension') !== false) {
            $allowed = $context['allowed_types'] ?? 'izin verilen';
            return "Dosya türü desteklenmiyor. Sadece $allowed dosyaları yükleyebilirsiniz.";
        }
        
        return "Dosya yükleme hatası. Lütfen dosyayı kontrol edip tekrar deneyin.";
    }
    
    /**
     * Generic error message
     */
    private static function genericError($error)
    {
        // Remove technical stack traces
        $error = preg_replace('/in \/.*\.php on line \d+/i', '', $error);
        $error = preg_replace('/Stack trace:.*/is', '', $error);
        
        // If still technical, provide generic message
        if (strlen($error) > 200 || preg_match('/[{}();]/', $error)) {
            return "Bir hata oluştu. Lütfen tekrar deneyin veya destek ekibiyle iletişime geçin.";
        }
        
        return $error;
    }
    
    /**
     * Success messages
     */
    public static function success($action, $entity = 'kayıt')
    {
        $messages = [
            'create' => "🎉 $entity başarıyla oluşturuldu!",
            'update' => "✅ $entity güncellendi.",
            'delete' => "🗑️ $entity silindi.",
            'restore' => "♻️ $entity geri yüklendi.",
            'complete' => "✅ İşlem tamamlandı!",
            'save' => "💾 Kaydedildi.",
            'send' => "📧 Gönderildi.",
            'approve' => "👍 Onaylandı.",
            'reject' => "👎 Reddedildi."
        ];
        
        return $messages[$action] ?? "✅ İşlem başarılı.";
    }
    
    /**
     * Info messages (contextual help)
     */
    public static function info($key, $params = [])
    {
        $messages = [
            'no_results' => "Sonuç bulunamadı. Arama kriterlerinizi değiştirmeyi deneyin.",
            'empty_list' => "Henüz {entity} yok. Yeni {entity} ekleyerek başlayın.",
            'processing' => "İşleminiz işleniyor... Lütfen bekleyin.",
            'saved_draft' => "Taslak otomatik kaydedildi.",
            'offline_mode' => "İnternet bağlantınız yok. Çevrimdışı modda çalışıyorsunuz.",
            'sync_pending' => "{count} değişiklik senkronize edilmeyi bekliyor."
        ];
        
        $message = $messages[$key] ?? $key;
        
        // Replace placeholders
        foreach ($params as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }
        
        return $message;
    }
    
    /**
     * Warning messages
     */
    public static function warning($key, $params = [])
    {
        $messages = [
            'unsaved_changes' => "⚠️ Kaydedilmemiş değişiklikleriniz var. Çıkmak istediğinizden emin misiniz?",
            'permanent_action' => "⚠️ Bu işlem geri alınamaz. Devam etmek istediğinizden emin misiniz?",
            'data_loss' => "⚠️ Bu işlem veri kaybına neden olabilir.",
            'timezone_diff' => "⚠️ Saat diliminiz sistemden farklı. Tüm saatler Türkiye saati olarak kaydedilecek.",
            'conflict_detected' => "⚠️ Seçtiğiniz tarih/saatte çakışma var. Lütfen farklı bir zaman seçin.",
            'overpayment' => "⚠️ Ödeme tutarı toplam borçtan fazla olamaz."
        ];
        
        $message = $messages[$key] ?? $key;
        
        foreach ($params as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }
        
        return $message;
    }
}
