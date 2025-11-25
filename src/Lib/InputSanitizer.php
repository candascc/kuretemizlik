<?php
/**
 * Input Sanitizer
 * Centralized input cleaning and sanitization
 */
class InputSanitizer
{
    /**
     * Sanitize string input
     */
    public static function string(?string $value, int $maxLength = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        
        // Trim whitespace
        $value = trim($value);
        
        // Remove excessive whitespace
        $value = preg_replace('/\s+/', ' ', $value);
        
        // Limit length if specified
        if ($maxLength !== null && mb_strlen($value) > $maxLength) {
            $value = mb_substr($value, 0, $maxLength);
        }
        
        return $value;
    }
    
    /**
     * Sanitize integer input
     * ===== ERR-023 FIX: Add type hinting =====
     * @param mixed $value Value to sanitize (string, int, or null)
     * @param int|null $min Minimum value
     * @param int|null $max Maximum value
     * @return int|null Sanitized integer or null
     */
    public static function int(mixed $value, ?int $min = null, ?int $max = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        $int = filter_var($value, FILTER_VALIDATE_INT);
        
        if ($int === false) {
            return null;
        }
        
        if ($min !== null && $int < $min) {
            return $min;
        }
        
        if ($max !== null && $int > $max) {
            return $max;
        }
        
        return $int;
    }
    
    /**
     * Sanitize float input
     * ===== ERR-023 FIX: Add type hinting =====
     * @param mixed $value Value to sanitize (string, float, or null)
     * @param float|null $min Minimum value
     * @param float|null $max Maximum value
     * @return float|null Sanitized float or null
     */
    public static function float(mixed $value, ?float $min = null, ?float $max = null): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);
        
        if ($float === false) {
            return null;
        }
        
        if ($min !== null && $float < $min) {
            return $min;
        }
        
        if ($max !== null && $float > $max) {
            return $max;
        }
        
        return $float;
    }
    
    /**
     * Sanitize email
     */
    public static function email(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        $email = filter_var(trim($value), FILTER_SANITIZE_EMAIL);
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        
        return $email ?: null;
    }
    
    /**
     * Sanitize phone number
     */
    public static function phone(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^\d+]/', '', trim($value));
        
        // Limit length (international format: max 20 chars)
        if (mb_strlen($phone) > 20) {
            return null;
        }
        
        return $phone ?: null;
    }
    
    /**
     * Sanitize URL
     */
    public static function url(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        $url = filter_var(trim($value), FILTER_SANITIZE_URL);
        $url = filter_var($url, FILTER_VALIDATE_URL);
        
        return $url ?: null;
    }
    
    /**
     * Sanitize date string
     */
    public static function date(?string $value, string $format = 'Y-m-d'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        try {
            $date = DateTime::createFromFormat($format, trim($value));
            if ($date && $date->format($format) === trim($value)) {
                return $date->format($format);
            }
        } catch (Exception $e) {
            // Invalid date
        }
        
        return null;
    }
    
    /**
     * Sanitize array of values
     */
    public static function array(array $values, callable $sanitizer = null): array
    {
        if ($sanitizer === null) {
            $sanitizer = [self::class, 'string'];
        }
        
        return array_map($sanitizer, $values);
    }
    
    /**
     * Remove potential XSS from string
     */
    public static function xss(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Use htmlspecialchars for output, but keep clean version for storage
        return strip_tags($value);
    }
    
    /**
     * Sanitize file path - prevent path traversal attacks
     * 
     * @param string|null $value File path to sanitize
     * @param array $allowedPaths Whitelist of allowed base paths
     * @return string|null Sanitized file path or null if invalid
     */
    public static function filePath(?string $value, array $allowedPaths = []): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        
        // Normalize path separators
        $value = str_replace('\\', '/', $value);
        
        // Remove path traversal attempts
        $value = preg_replace('#\.\./|\.\.\\\\#', '', $value);
        $value = preg_replace('#//+#', '/', $value);
        
        // Remove leading/trailing slashes
        $value = trim($value, '/\\');
        
        // If whitelist provided, validate against it
        if (!empty($allowedPaths)) {
            $isAllowed = false;
            foreach ($allowedPaths as $allowedPath) {
                $normalizedAllowed = str_replace('\\', '/', $allowedPath);
                $normalizedAllowed = trim($normalizedAllowed, '/\\');
                if (strpos($value, $normalizedAllowed) === 0) {
                    $isAllowed = true;
                    break;
                }
            }
            if (!$isAllowed) {
                return null;
            }
        }
        
        return $value ?: null;
    }
    
    /**
     * Sanitize array input with specific sanitizer for each key
     * 
     * @param array $values Array to sanitize
     * @param array $sanitizers Map of key => sanitizer method
     * @return array Sanitized array
     */
    public static function arrayWithKeys(array $values, array $sanitizers = []): array
    {
        $sanitized = [];
        
        foreach ($values as $key => $value) {
            // If specific sanitizer defined for this key, use it
            if (isset($sanitizers[$key]) && is_callable($sanitizers[$key])) {
                $sanitized[$key] = call_user_func($sanitizers[$key], $value);
            } elseif (isset($sanitizers[$key]) && is_string($sanitizers[$key])) {
                // String method name
                if (method_exists(self::class, $sanitizers[$key])) {
                    $sanitized[$key] = self::{$sanitizers[$key]}($value);
                } else {
                    $sanitized[$key] = self::string($value);
                }
            } elseif (is_array($value)) {
                // Recursive sanitization for nested arrays
                $sanitized[$key] = self::arrayWithKeys($value, $sanitizers);
            } else {
                // Default string sanitization
                $sanitized[$key] = self::string($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validate API key or secret from environment variable
     * 
     * @param string|null $value API key/secret value
     * @param int $minLength Minimum length (default: 16)
     * @param string $name Variable name for error messages
     * @return string|null Validated value or null if invalid
     * @throws Exception If value is invalid and required
     */
    public static function apiKey(?string $value, int $minLength = 16, string $name = 'API_KEY'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Remove whitespace
        $value = trim($value);
        
        // Check minimum length
        if (strlen($value) < $minLength) {
            if (class_exists('Logger')) {
                Logger::warning("API key validation failed: {$name} is too short (minimum {$minLength} characters)");
            }
            return null;
        }
        
        // Check for common insecure patterns
        $insecurePatterns = [
            'test',
            'demo',
            'example',
            'changeme',
            'password',
            'secret',
            'key',
            '123456',
            'admin'
        ];
        
        $lowerValue = strtolower($value);
        foreach ($insecurePatterns as $pattern) {
            if (strpos($lowerValue, $pattern) !== false && strlen($value) < 32) {
                // Only flag if it's a short key containing insecure patterns
                if (class_exists('Logger')) {
                    Logger::warning("API key validation warning: {$name} contains potentially insecure pattern");
                }
                // Don't reject, just log warning
            }
        }
        
        return $value;
    }
    
    /**
     * Get and validate environment variable for API key/secret
     * 
     * @param string $key Environment variable name
     * @param int $minLength Minimum length (default: 16)
     * @param bool $required Whether the key is required (throws exception if missing)
     * @return string|null Validated value or null if not required and missing
     * @throws Exception If required and missing/invalid
     */
    public static function getEnvApiKey(string $key, int $minLength = 16, bool $required = false): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        
        if ($value === null || $value === '') {
            if ($required) {
                throw new Exception("Required environment variable {$key} is not set. Please configure in env.local file.");
            }
            return null;
        }
        
        $validated = self::apiKey($value, $minLength, $key);
        
        if ($validated === null && $required) {
            throw new Exception("Environment variable {$key} is invalid (minimum {$minLength} characters required).");
        }
        
        return $validated;
    }
}

