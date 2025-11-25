<?php
/**
 * CSRF Token Yönetimi
 */

class CSRF
{
    private const SESSION_KEY = 'csrf_tokens';
    private const TOKEN_TTL = 7200; // 2 saat
    private const MAX_TOKENS = 20;

    public static function generate(): string
    {
        self::ensureSession();
        
        // ===== PRODUCTION FIX: Don't close/restart session unnecessarily =====
        // Session zaten aktifse, yeniden başlatmaya gerek yok
        // Bu, session kaybını önler
        // ===== PRODUCTION FIX END =====
        
        $tokens = self::pruneTokens();

        $token = bin2hex(random_bytes(32));
        $tokens[$token] = ['created_at' => time()];

        while (count($tokens) > self::MAX_TOKENS) {
            array_shift($tokens);
        }

        $_SESSION[self::SESSION_KEY] = $tokens;
        
        // ===== PRODUCTION FIX: Don't close/restart session unnecessarily =====
        // Session PHP tarafından otomatik yazılacak, manuel kapatmaya gerek yok
        // ===== PRODUCTION FIX END =====
        
        self::debug('token generated', ['pool' => count($tokens)]);

        return $token;
    }

    public static function get(): string
    {
        self::ensureSession();
        
        // ===== PRODUCTION FIX: Don't prune on every get() call =====
        // Prune sadece gerektiğinde yapılmalı, her get() çağrısında değil
        // Bu, form render edilirken oluşturulan token'ın submit edilene kadar korunmasını sağlar
        $tokens = $_SESSION[self::SESSION_KEY] ?? [];
        if (!is_array($tokens)) {
            $tokens = [];
        }
        
        // Eğer token yoksa veya tüm token'lar expire olmuşsa yeni token oluştur
        if (empty($tokens)) {
            return self::generate();
        }
        
        // En son oluşturulan geçerli token'ı bul
        $now = time();
        $validTokens = [];
        foreach ($tokens as $token => $meta) {
            $created = (int) ($meta['created_at'] ?? $meta['created'] ?? 0);
            if ($created > 0 && ($now - $created) <= self::TOKEN_TTL) {
                $validTokens[$token] = $created;
            }
        }
        
        // Geçerli token varsa en son olanı döndür
        if (!empty($validTokens)) {
            arsort($validTokens); // En yeni token'ı al
            $lastToken = array_key_first($validTokens);
            return $lastToken;
        }
        
        // Geçerli token yoksa yeni oluştur ve prune yap
        self::pruneTokens();
        return self::generate();
    }

    public static function verify($token): bool
    {
        if (!is_string($token) || trim($token) === '') {
            self::debug('verify called with empty token');
            return false;
        }

        self::ensureSession();
        
        // ===== PRODUCTION FIX: Don't close/restart session if already active =====
        // Session zaten aktifse, yeniden başlatmaya gerek yok
        // Bu, session kaybını önler
        // ===== PRODUCTION FIX END =====
        
        // ===== PRODUCTION FIX: Check tokens before pruning =====
        // Önce mevcut token'ları kontrol et, sonra prune et
        // Bu, token'ın prune edilmeden önce kontrol edilmesini sağlar
        $tokens = $_SESSION[self::SESSION_KEY] ?? [];
        if (!is_array($tokens)) {
            $tokens = [];
        }

        // Token var mı kontrol et (prune etmeden önce)
        if (isset($tokens[$token])) {
            $meta = $tokens[$token];
            $created = (int) ($meta['created_at'] ?? $meta['created'] ?? 0);
            $now = time();
            
            // Token TTL içinde mi kontrol et
            if ($created > 0 && ($now - $created) <= self::TOKEN_TTL) {
                // Token geçerli, token'ı sil (one-time use) ve prune işlemini yap
                unset($tokens[$token]);
                $prunedTokens = self::pruneTokensInternal($tokens);
                $_SESSION[self::SESSION_KEY] = $prunedTokens;
                
                // ===== PRODUCTION FIX: Don't close/restart session unnecessarily =====
                // Session PHP tarafından otomatik yazılacak, manuel kapatmaya gerek yok
                // ===== PRODUCTION FIX END =====
                
                self::debug('token accepted', ['remaining' => count($prunedTokens), 'token_prefix' => substr($token, 0, 8)]);
                return true;
            } else {
                // Token süresi dolmuş
                self::debug('token expired', ['token_prefix' => substr($token, 0, 8), 'age' => $now - $created]);
                // Expired token'ı temizle
                $prunedTokens = self::pruneTokens();
                
                // ===== PRODUCTION FIX: Don't close/restart session unnecessarily =====
                // ===== PRODUCTION FIX END =====
                
                return false;
            }
        }

        // Token bulunamadı, prune işlemini yap ve log'la
        $prunedTokens = self::pruneTokens();
        
        // ===== PRODUCTION FIX: Don't close/restart session unnecessarily =====
        // ===== PRODUCTION FIX END =====
        
        $allTokens = $_SESSION[self::SESSION_KEY] ?? [];
        self::debug('token mismatch', [
            'token_prefix' => substr($token, 0, 8),
            'available_tokens' => count($allTokens),
            'available_prefixes' => array_map(function($t) { return substr($t, 0, 8); }, array_keys($allTokens))
        ]);
        return false;
    }

    public static function verifyRequest(): bool
    {
        // ===== PRODUCTION FIX: Prevent double verification =====
        // Eğer bu request için zaten verify edilmişse, tekrar verify etme
        // Bu, token'ın bir kez kullanıldıktan sonra silinmesi nedeniyle
        // ikinci verify çağrısında mismatch olmasını önler
        // NOT: Session-based cache kullanıyoruz, static değişken yerine
        // çünkü static değişken request'ler arasında kalabilir (güvenlik riski)
        // Use SessionHelper for centralized session management
        SessionHelper::ensureStarted();
        
        $requestId = $_SERVER['REQUEST_TIME_FLOAT'] . '_' . ($_SERVER['REQUEST_URI'] ?? '') . '_' . ($_SERVER['REQUEST_METHOD'] ?? '');
        $cacheKey = 'csrf_verified_' . md5($requestId);
        
        // Aynı request için zaten verify edilmişse, sonucu döndür
        if (isset($_SESSION[$cacheKey])) {
            $cachedResult = $_SESSION[$cacheKey];
            $cachedToken = $_SESSION[$cacheKey . '_token'] ?? null;
            
            // Token'ı kontrol et
            $headerToken = self::extractHeaderToken();
            $requestToken = self::extractRequestToken();
            $currentToken = $headerToken ?? $requestToken;
            
            // Eğer aynı token ise, zaten verify edilmiş demektir
            if ($currentToken === $cachedToken && $cachedResult === true) {
                self::debug('request already verified (session cached)');
                return true;
            }
        }
        // ===== PRODUCTION FIX: End =====
        
        $headerToken = self::extractHeaderToken();
        if ($headerToken !== null && self::verify($headerToken)) {
            // Cache'e kaydet
            $_SESSION[$cacheKey] = true;
            $_SESSION[$cacheKey . '_token'] = $headerToken;
            self::debug('request verified via header');
            return true;
        }

        $requestToken = self::extractRequestToken();
        if ($requestToken !== null && self::verify($requestToken)) {
            // Cache'e kaydet
            $_SESSION[$cacheKey] = true;
            $_SESSION[$cacheKey . '_token'] = $requestToken;
            self::debug('request verified via payload');
            return true;
        }

        // Cache'e false kaydet (aynı request için tekrar kontrol etme)
        $_SESSION[$cacheKey] = false;
        
        self::debug('request verification failed', [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'n/a',
            'has_header' => $headerToken !== null,
            'has_payload' => $requestToken !== null,
            'session_id' => session_id(),
        ]);

        return false;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::get(), ENT_QUOTES, 'UTF-8') . '">';
    }

    private static function ensureSession(): void
    {
        // Use SessionHelper for centralized session management
        SessionHelper::ensureStarted();
    }

    /**
     * @return array<string, array{created_at:int}>
     */
    private static function pruneTokens(): array
    {
        $tokens = $_SESSION[self::SESSION_KEY] ?? [];
        if (!is_array($tokens)) {
            $tokens = [];
        }
        
        return self::pruneTokensInternal($tokens);
    }
    
    /**
     * Internal prune method that doesn't read from session (for atomic operations)
     * @param array<string, array{created_at:int}> $tokens
     * @return array<string, array{created_at:int}>
     */
    private static function pruneTokensInternal(array $tokens): array
    {
        $now = time();
        $filtered = [];

        foreach ($tokens as $token => $meta) {
            $created = (int) ($meta['created_at'] ?? $meta['created'] ?? 0);
            // ===== PRODUCTION FIX: Keep tokens that are within TTL =====
            // Token'ları TTL içindeyse tut (2 saat = 7200 saniye)
            if ($created > 0 && ($now - $created) <= self::TOKEN_TTL) {
                $filtered[$token] = ['created_at' => $created];
            }
        }

        // ===== PRODUCTION FIX: Only update session if tokens changed =====
        // Session'ı gereksiz yere güncellemeyi önle (session locking sorunlarını azaltır)
        if (count($filtered) !== count($tokens)) {
            $_SESSION[self::SESSION_KEY] = $filtered;
        }

        return $filtered;
    }

    private static function extractHeaderToken(): ?string
    {
        $candidates = [
            $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null,
            $_SERVER['HTTP_X_XSRF_TOKEN'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate)) {
                $trimmed = trim($candidate);
                if ($trimmed !== '') {
                    return $trimmed;
                }
            }
        }

        return null;
    }

    private static function extractRequestToken(): ?string
    {
        $sources = [
            $_POST['csrf_token'] ?? null,
            $_GET['csrf_token'] ?? null,
        ];

        foreach ($sources as $source) {
            if (is_string($source)) {
                $trimmed = trim($source);
                if ($trimmed !== '') {
                    return $trimmed;
                }
            }
        }

        return null;
    }

    private static function debug(string $message, array $context = []): void
    {
        // ===== PRODUCTION FIX: Always log CSRF debug in production for troubleshooting =====
        // Production'da CSRF sorunlarını debug etmek için her zaman log'la
        $shouldLog = defined('APP_DEBUG') && APP_DEBUG;
        
        // Production'da CSRF hatalarını her zaman log'la (geçici debug için)
        if (!$shouldLog && (stripos($message, 'failed') !== false || stripos($message, 'mismatch') !== false)) {
            $shouldLog = true;
        }

        if (!$shouldLog) {
            return;
        }

        $payload = '';
        if (!empty($context)) {
            $encoded = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded !== false) {
                $payload = ' ' . $encoded;
            }
        }

        error_log('[csrf] ' . $message . $payload);
    }
}
