<?php
declare(strict_types=1);

/**
 * Yardımcı Fonksiyonlar
 * Phase 3.5: Added strict types declaration
 */
class Utils
{
    /**
     * Set no-cache headers for dynamic pages
     * Prevents browser from caching dynamic content
     * 
     * @return void
     */
    public static function setNoCacheHeaders(): void
    {
        if (!headers_sent()) {
            header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('ETag: "' . md5(time() . rand()) . '"');
        }
    }

    /**
     * Tarih formatla (Türkçe locale desteği ile)
     * Phase 3.5: Added type hints
     * 
     * @param string|DateTimeInterface|null $date
     * @param string $format
     * @return string
     */
    public static function formatDate($date, string $format = 'd.m.Y'): string
    {
        if (empty($date)) return '';
        
        try {
            $datetime = new DateTime($date);
            $formatted = $datetime->format($format);
            
            // Türkçe ay isimleri (tam)
            $months = [
                'January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart',
                'April' => 'Nisan', 'May' => 'Mayıs', 'June' => 'Haziran',
                'July' => 'Temmuz', 'August' => 'Ağustos', 'September' => 'Eylül',
                'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık'
            ];
            
            // Türkçe ay isimleri (kısa)
            $monthsShort = [
                'Jan' => 'Oca', 'Feb' => 'Şub', 'Mar' => 'Mar',
                'Apr' => 'Nis', 'May' => 'May', 'Jun' => 'Haz',
                'Jul' => 'Tem', 'Aug' => 'Ağu', 'Sep' => 'Eyl',
                'Oct' => 'Eki', 'Nov' => 'Kas', 'Dec' => 'Ara'
            ];
            
            // Türkçe gün isimleri (tam)
            $days = [
                'Monday' => 'Pazartesi', 'Tuesday' => 'Salı', 'Wednesday' => 'Çarşamba',
                'Thursday' => 'Perşembe', 'Friday' => 'Cuma', 'Saturday' => 'Cumartesi',
                'Sunday' => 'Pazar'
            ];
            
            // Türkçe gün isimleri (kısa)
            $daysShort = [
                'Mon' => 'Pzt', 'Tue' => 'Sal', 'Wed' => 'Çar',
                'Thu' => 'Per', 'Fri' => 'Cum', 'Sat' => 'Cmt',
                'Sun' => 'Paz'
            ];
            
            // Değiştirme
            $formatted = str_replace(array_keys($months), array_values($months), $formatted);
            $formatted = str_replace(array_keys($monthsShort), array_values($monthsShort), $formatted);
            $formatted = str_replace(array_keys($days), array_values($days), $formatted);
            $formatted = str_replace(array_keys($daysShort), array_values($daysShort), $formatted);
            
            return $formatted;
        } catch (Exception $e) {
            return $date;
        }
    }
    
    /**
     * Tarih-saat formatla (Türkçe locale desteği ile)
     * Phase 3.5: Added type hints
     * 
     * @param string|DateTimeInterface|null $datetime
     * @param string $format
     * @return string
     */
    public static function formatDateTime($datetime, string $format = 'd.m.Y H:i'): string
    {
        if (empty($datetime)) return '';
        
        try {
            $dt = new DateTime($datetime);
            $formatted = $dt->format($format);
            
            // Türkçe ay isimleri (tam)
            $months = [
                'January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart',
                'April' => 'Nisan', 'May' => 'Mayıs', 'June' => 'Haziran',
                'July' => 'Temmuz', 'August' => 'Ağustos', 'September' => 'Eylül',
                'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık'
            ];
            
            // Türkçe ay isimleri (kısa)
            $monthsShort = [
                'Jan' => 'Oca', 'Feb' => 'Şub', 'Mar' => 'Mar',
                'Apr' => 'Nis', 'May' => 'May', 'Jun' => 'Haz',
                'Jul' => 'Tem', 'Aug' => 'Ağu', 'Sep' => 'Eyl',
                'Oct' => 'Eki', 'Nov' => 'Kas', 'Dec' => 'Ara'
            ];
            
            // Türkçe gün isimleri (tam)
            $days = [
                'Monday' => 'Pazartesi', 'Tuesday' => 'Salı', 'Wednesday' => 'Çarşamba',
                'Thursday' => 'Perşembe', 'Friday' => 'Cuma', 'Saturday' => 'Cumartesi',
                'Sunday' => 'Pazar'
            ];
            
            // Türkçe gün isimleri (kısa)
            $daysShort = [
                'Mon' => 'Pzt', 'Tue' => 'Sal', 'Wed' => 'Çar',
                'Thu' => 'Per', 'Fri' => 'Cum', 'Sat' => 'Cmt',
                'Sun' => 'Paz'
            ];
            
            // Değiştirme
            $formatted = str_replace(array_keys($months), array_values($months), $formatted);
            $formatted = str_replace(array_keys($monthsShort), array_values($monthsShort), $formatted);
            $formatted = str_replace(array_keys($days), array_values($days), $formatted);
            $formatted = str_replace(array_keys($daysShort), array_values($daysShort), $formatted);
            
            return $formatted;
        } catch (Exception $e) {
            return $datetime;
        }
    }

    /**
     * İki tarih arasındaki farkı insan okuyabilir biçimde döndür
     */
    public static function diffForHumans($from, $to = null, int $precision = 2, bool $withSuffix = true): string
    {
        if (empty($from)) {
            return '';
        }

        try {
            $start = $from instanceof DateTimeInterface
                ? (clone $from)
                : new DateTime((string)$from);
            $end = $to instanceof DateTimeInterface
                ? (clone $to)
                : ($to ? new DateTime((string)$to) : new DateTime());
        } catch (Exception $e) {
            return '';
        }

        $precision = max(1, $precision);
        $interval = $start->diff($end, false);

        $units = [
            ['value' => $interval->y, 'singular' => 'yıl', 'plural' => 'yıl'],
            ['value' => $interval->m, 'singular' => 'ay', 'plural' => 'ay'],
            ['value' => $interval->d, 'singular' => 'gün', 'plural' => 'gün'],
            ['value' => $interval->h, 'singular' => 'saat', 'plural' => 'saat'],
            ['value' => $interval->i, 'singular' => 'dakika', 'plural' => 'dakika'],
            ['value' => $interval->s, 'singular' => 'saniye', 'plural' => 'saniye'],
        ];

        // Phase 3.2: Optimize with array_filter and array_slice
        $parts = [];
        $filteredUnits = array_filter($units, function($unit) {
            return (int)$unit['value'] > 0;
        });
        
        foreach ($filteredUnits as $unit) {
            $value = (int)$unit['value'];
            $parts[] = $value . ' ' . ($value === 1 ? $unit['singular'] : $unit['plural']);
            if (count($parts) === $precision) {
                break;
            }
        }

        if (empty($parts)) {
            return $withSuffix ? 'az önce' : '0 dakika';
        }

        $text = implode(' ', $parts);
        if (!$withSuffix) {
            return $text;
        }

        return $text . ($interval->invert ? ' sonra' : ' önce');
    }
    
    /**
     * Para formatla
     * Phase 3.5: Added type hints
     * 
     * @param float|int|string $amount
     * @param string $currency
     * @return string
     */
    public static function formatMoney($amount, string $currency = '₺'): string
    {
        return number_format($amount, 2, ',', '.') . ' ' . $currency;
    }
    
    /**
     * JSON response
     * Phase 3.5: Added type hints
     * 
     * @param mixed $data
     * @param int $status
     * @return void
     */
    public static function jsonResponse($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Redirect
     * Phase 3.5: Added type hints
     * 
     * @param string $url
     * @param int $status
     * @return void
     */
    public static function redirect(string $url, int $status = 302): void
    {
        // ===== PRODUCTION FIX: Prevent redirect caching =====
        // Clear any output buffers
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Prevent caching of redirect response
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Set response code and redirect
        http_response_code($status);
        header('Location: ' . $url);
        
        // Ensure session is written before redirect (if session is active)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        exit;
    }
    
    /**
     * Flash message
     * ===== CRITICAL FIX: Ensure session is started before accessing $_SESSION =====
     * Phase 3.5: Added type hints
     * 
     * @param string $type
     * @param string $message
     * @return void
     */
    public static function flash(string $type, string $message): void
    {
        // ===== CRITICAL FIX: Ensure session is started =====
        // Use SessionHelper for centralized session management
        if (!SessionHelper::ensureStarted()) {
            // If session can't be started, can't store flash message
            return;
        }
        // ===== CRITICAL FIX END =====
        
        $_SESSION['flash'][$type] = $message;
    }
    
    /**
     * Flash message al
     * ===== CRITICAL FIX: Ensure session is started before accessing $_SESSION =====
     * Phase 3.5: Added type hints
     * 
     * @param string|null $type
     * @return string|array|null
     */
    public static function getFlash(?string $type = null)
    {
        // ===== CRITICAL FIX: Ensure session is started =====
        if (session_status() === PHP_SESSION_NONE) {
            $cookiePath = defined('APP_BASE') && APP_BASE !== '' ? APP_BASE : '/app';
            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            
            // Use SessionHelper for centralized session management
            if (!SessionHelper::ensureStarted()) {
                // If session can't be started, return empty
                return $type ? null : [];
            }
        }
        // ===== CRITICAL FIX END =====
        
        if ($type) {
            $message = $_SESSION['flash'][$type] ?? null;
            unset($_SESSION['flash'][$type]);
            return $message;
        }
        
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }
    
    /**
     * Input sanitize
     * Phase 3.5: Added type hints
     * 
     * @param mixed $input
     * @return mixed
     */
    public static function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Güvenli URL oluştur
     * Phase 3.5: Added type hints
     * 
     * @param string|null $url
     * @param string $default
     * @return string
     */
    public static function sanitizeUrl(?string $url, string $default = ''): string
    {
        $url = trim((string)$url);
        if ($url === '') {
            return $default;
        }

        $filtered = filter_var($url, FILTER_SANITIZE_URL);
        if (!$filtered) {
            return $default;
        }

        $parts = @parse_url($filtered);
        if ($parts === false || empty($parts['host'])) {
            return $default;
        }

        $scheme = strtolower($parts['scheme'] ?? 'http');
        if (!in_array($scheme, ['http', 'https'], true)) {
            $scheme = 'http';
        }

        $host = strtolower($parts['host']);
        if (!preg_match('/^(?:[a-z0-9-]+\.)*[a-z0-9-]+$/i', $host)) {
            return $default;
        }

        $port = '';
        if (isset($parts['port'])) {
            $portNumber = (int)$parts['port'];
            if ($portNumber > 0 && $portNumber < 65536) {
                $port = ':' . $portNumber;
            }
        }

        $path = $parts['path'] ?? '/';
        $path = '/' . ltrim($path, '/');
        $path = preg_replace('/[^A-Za-z0-9\-._~\/]/', '', $path);
        if ($path === '') {
            $path = '/';
        }

        $query = '';
        if (isset($parts['query']) && $parts['query'] !== '') {
            $queryString = preg_replace('/[^A-Za-z0-9\-._~=&%]/', '', $parts['query']);
            if ($queryString !== '') {
                $query = '?' . $queryString;
            }
        }

        return $scheme . '://' . $host . $port . $path . $query;
    }
    
    /**
     * Host başlığını güvenli hale getir
     */
    public static function sanitizeHostHeader(?string $host, string $fallback = 'localhost', ?callable $onInvalid = null): string
    {
        $host = trim((string)$host);

        if ($host === '') {
            return self::sanitizeHostHeader($fallback, 'localhost', $onInvalid);
        }

        if (strpos($host, '://') !== false) {
            $parsedHost = parse_url($host, PHP_URL_HOST);
            $parsedPort = parse_url($host, PHP_URL_PORT);
            $host = ($parsedHost ?? '') . ($parsedPort ? ':' . $parsedPort : '');
        }

        $parts = explode(':', $host, 2);
        $hostname = strtolower($parts[0]);

        if (!preg_match('/^(?:[a-z0-9-]+\.)*[a-z0-9-]+$/i', $hostname)) {
            if ($onInvalid) {
                $onInvalid($host);
            }
            return self::sanitizeHostHeader($fallback, 'localhost', $onInvalid);
        }

        $port = '';
        if (isset($parts[1])) {
            $portNumber = filter_var($parts[1], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 65535],
            ]);

            if ($portNumber === false) {
                if ($onInvalid) {
                    $onInvalid($host);
                }
                return self::sanitizeHostHeader($fallback, 'localhost', $onInvalid);
            }

            $port = ':' . $portNumber;
        }

        return $hostname . $port;
    }

    /**
     * İstek URI'ını temizle
     */
    public static function sanitizeRequestUri(?string $uri): string
    {
        $uri = trim((string)$uri);

        if ($uri === '') {
            return '/';
        }

        if ($uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        $parts = @parse_url($uri);
        $path = '/' . ltrim($parts['path'] ?? '/', '/');
        $path = preg_replace('/[^A-Za-z0-9\\-._~!$&\'()*+,;=:@\\/]/', '', $path);

        if ($path === '') {
            $path = '/';
        }

        $query = '';
        if (isset($parts['query']) && $parts['query'] !== '') {
            $queryString = preg_replace('/[^A-Za-z0-9\\-._~!$&\'()*+,;=:@\\/?]/', '', $parts['query']);
            if ($queryString !== '') {
                $query = '?' . $queryString;
            }
        }

        return $path . $query;
    }
    
    /**
     * URL olu�Ytur
     */
    public static function url($path = '')
    {
        return base_url($path);
    }
    
    /**
     * Asset URL
     * Phase 3.5: Added type hints
     * 
     * @param string $path
     * @return string
     */
    public static function asset(string $path): string
    {
        return base_url('/assets/' . ltrim($path, '/'));
    }
    
    /**
     * Asset sürüm numarası
     */
    public static function assetVersion(string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/');
        $assetRoot = dirname(__DIR__, 2) . '/assets/';
        $fullPath = $assetRoot . $relativePath;

        if (is_file($fullPath)) {
            return (string) filemtime($fullPath);
        }

        return '0';
    }
    
    /**
     * Sayfalama
     * Phase 3.5: Added type hints
     * 
     * @param int $total
     * @param int $perPage
     * @param int $currentPage
     * @return array
     */
    public static function paginate(int $total, int $perPage = 20, int $currentPage = 1): array
    {
        $totalPages = ceil($total / $perPage);
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset = ($currentPage - 1) * $perPage;
        
        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'offset' => $offset,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null,
        ];
    }
    
    /**
     * UTF-8 güvenli metin kısaltma
     */
    public static function truncateUtf8(?string $text, int $maxLength = 120, string $suffix = '…'): string
    {
        if ($text === null) {
            return '';
        }
        
        $trimmed = trim($text);
        if ($trimmed === '') {
            return '';
        }
        
        if (!function_exists('mb_strlen') || !function_exists('mb_strimwidth')) {
            return strlen($trimmed) > $maxLength
                ? substr($trimmed, 0, $maxLength) . '...'
                : $trimmed;
        }
        
        if (mb_strlen($trimmed, 'UTF-8') <= $maxLength) {
            return $trimmed;
        }
        
        $excerpt = rtrim(mb_strimwidth($trimmed, 0, $maxLength, '', 'UTF-8'));
        return $excerpt . $suffix;
    }

    /**
     * Telefon numarasını normalize et (+ ülke kodu ile)
     */
    public static function normalizePhone(?string $phone, string $defaultCountry = 'TR'): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return null;
        }

        // Remove leading international prefix markers
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($defaultCountry === 'TR') {
            if (str_starts_with($digits, '0') && strlen($digits) === 11) {
                $digits = '90' . substr($digits, 1);
            }

            if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
                $digits = '90' . $digits;
            }

            if (!str_starts_with($digits, '90') && strlen($digits) === 9) {
                $digits = '90' . $digits;
            }
        }

        if ($defaultCountry === 'TR' && str_starts_with($digits, '9') && !str_starts_with($digits, '90')) {
            $digits = '90' . $digits;
        }

        if (!str_starts_with($digits, '+')) {
            $digits = '+' . $digits;
        }

        return $digits;
    }

    /**
     * Telefon numarasını kullanıcıya gösterim için formatla
     */
    public static function formatPhone(?string $phone): string
    {
        $normalized = self::normalizePhone($phone ?? '');
        if ($normalized === null) {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $normalized);
        if ($digits === '') {
            return '';
        }

        // Separate country code and local number (assume last 10 digits local)
        $localLength = 10;
        if (strlen($digits) <= $localLength) {
            return '+' . $digits;
        }

        $country = substr($digits, 0, strlen($digits) - $localLength);
        $local = substr($digits, -$localLength);
        $localParts = [
            substr($local, 0, 3),
            substr($local, 3, 3),
            substr($local, 6, 2),
            substr($local, 8, 2),
        ];

        return '+' . $country . ' ' . implode(' ', array_filter($localParts, fn($part) => $part !== false));
    }

    /**
     * Kullanıcı girdisinden parasal değeri normalize et
     */
    public static function normalizeMoney(string $value): float
    {
        $raw = trim($value);
        if ($raw === '') {
            return 0.0;
        }

        // Remove non-breaking spaces
        $raw = str_replace("\u{00A0}", ' ', $raw);
        $raw = str_replace(' ', '', $raw);

        $hasComma = strpos($raw, ',') !== false;
        $hasDot = strpos($raw, '.') !== false;

        if ($hasComma && $hasDot) {
            // assume dot separators, comma decimal
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif ($hasComma) {
            $raw = str_replace(',', '.', $raw);
        }

        $raw = preg_replace('/[^0-9.\-]/', '', $raw);
        if ($raw === '' || $raw === '.' || $raw === '-.' || $raw === '-') {
            return 0.0;
        }

        $amount = (float)$raw;
        return round($amount, 2);
    }
    
    /**
     * Türkçe karakterleri temizle
     * Phase 3.5: Added type hints
     * 
     * @param string $text
     * @return string
     */
    public static function slug(string $text): string
    {
        $turkish = ['ç','ğ','ı','ö','ş','ü','Ç','Ğ','İ','I','Ö','Ş','Ü'];
        $english = ['c','g','i','o','s','u','c','g','i','i','o','s','u'];

        $text = str_replace($turkish, $english, $text);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);

        return trim($text, '-');
    }
    
    /**
     * Dosya boyutu formatla
     * Phase 3.5: Added type hints
     * 
     * @param int|float $bytes
     * @return string
     */
    public static function formatFileSize($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Rastgele string
     * Phase 3.5: Added type hints
     * 
     * @param int $length
     * @return string
     */
    public static function randomString(int $length = 10): string
    {
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }
    
    /**
     * Array'den de�Yer al
     */
    public static function arrayGet($array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }
        
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            
            $array = $array[$segment];
        }
        
        return $array;
    }
    
    /**
     * Safe exception message for user display
     * Sanitizes exception messages to prevent XSS and information disclosure
     * 
     * @param Throwable|Exception $exception
     * @param string|null $fallbackMessage
     * @return string Safe message for display
     */
    public static function safeExceptionMessage($exception, $fallbackMessage = null)
    {
        // In debug mode, show sanitized actual message
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $message = $exception instanceof Throwable ? $exception->getMessage() : '';
            // Strip potentially dangerous content but keep basic info
            $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            // Truncate to prevent overly long error messages
            return mb_substr($message, 0, 200);
        }
        
        // In production, use user-friendly generic message
        return $fallbackMessage ?? (class_exists('HumanMessages') 
            ? HumanMessages::error('server') 
            : 'Bir hata oluştu. Lütfen tekrar deneyin.');
    }
}
