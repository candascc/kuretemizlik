<?php
/**
 * Translation Engine
 * Multi-language support with lazy loading
 */

class Translator
{
    private static $instance = null;
    private static $currentLocale = 'tr';
    private static $fallbackLocale = 'tr';
    private static $translations = [];
    private static $loadedFiles = [];
    
    /**
     * Get translator instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Set current locale
     */
    public static function setLocale(string $locale): void
    {
        self::$currentLocale = $locale;
        
        // Store in session
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['locale'] = $locale;
        }
    }
    
    /**
     * Get current locale
     */
    public static function getLocale(): string
    {
        // Check session first
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['locale'])) {
            return $_SESSION['locale'];
        }
        
        return self::$currentLocale;
    }
    
    /**
     * Load translation file
     */
    private static function loadFile(string $locale, string $file = 'main'): void
    {
        $cacheKey = "{$locale}_{$file}";
        
        if (isset(self::$loadedFiles[$cacheKey])) {
            return; // Already loaded
        }
        
        $filePath = __DIR__ . "/../../lang/{$locale}.php";
        
        if (!file_exists($filePath)) {
            // Try fallback locale
            if ($locale !== self::$fallbackLocale) {
                $filePath = __DIR__ . "/../../lang/" . self::$fallbackLocale . ".php";
            }
        }
        
        if (file_exists($filePath)) {
            $translations = require $filePath;
            
            if (!isset(self::$translations[$locale])) {
                self::$translations[$locale] = [];
            }
            
            self::$translations[$locale] = array_merge(
                self::$translations[$locale],
                $translations
            );
            
            self::$loadedFiles[$cacheKey] = true;
        }
    }
    
    /**
     * Translate a key (supports dot notation for nested arrays)
     */
    public static function translate(string $key, array $replacements = [], ?string $locale = null): string
    {
        $locale = $locale ?? self::getLocale();
        
        // Load translations if not loaded
        self::loadFile($locale);
        
        // Get translation (support dot notation)
        $translation = self::getNestedValue(self::$translations[$locale] ?? [], $key);
        
        // Fallback to default locale
        if ($translation === null && $locale !== self::$fallbackLocale) {
            self::loadFile(self::$fallbackLocale);
            $translation = self::getNestedValue(self::$translations[self::$fallbackLocale] ?? [], $key);
        }
        
        // Fallback to key if not found
        if ($translation === null) {
            $translation = $key;
        }
        
        // Replace placeholders (support both :placeholder and {placeholder} formats)
        foreach ($replacements as $placeholder => $value) {
            $translation = str_replace([":{$placeholder}", "{{$placeholder}"], $value, $translation);
        }
        
        return $translation;
    }
    
    /**
     * Get nested value from array using dot notation
     */
    private static function getNestedValue(array $array, string $key)
    {
        if (strpos($key, '.') === false) {
            return $array[$key] ?? null;
        }
        
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Pluralization
     */
    public static function plural(string $key, int $count, array $replacements = [], ?string $locale = null): string
    {
        $locale = $locale ?? self::getLocale();
        
        // Load translations if not loaded
        self::loadFile($locale);
        
        $pluralKey = $key . ($count === 1 ? '_singular' : '_plural');
        
        return self::translate($pluralKey, array_merge(['count' => $count], $replacements), $locale);
    }
    
    /**
     * Check if translation exists
     */
    public static function has(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? self::getLocale();
        
        self::loadFile($locale);
        
        return isset(self::$translations[$locale][$key]);
    }
    
    /**
     * Get all translations for current locale
     */
    public static function all(?string $locale = null): array
    {
        $locale = $locale ?? self::getLocale();
        
        self::loadFile($locale);
        
        return self::$translations[$locale] ?? [];
    }
    
    /**
     * Get available locales
     */
    public static function getAvailableLocales(): array
    {
        $langDir = __DIR__ . '/../../lang';
        $locales = [];
        
        if (is_dir($langDir)) {
            $files = scandir($langDir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $locales[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }
        
        return $locales;
    }
    
    /**
     * Format date according to locale
     */
    public static function date($date, string $format = 'long', ?string $locale = null): string
    {
        $locale = $locale ?? self::getLocale();
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        
        if ($locale === 'tr') {
            $formats = [
                'short' => 'd.m.Y',
                'long' => 'd F Y',
                'full' => 'd F Y, l'
            ];
        } else {
            $formats = [
                'short' => 'm/d/Y',
                'long' => 'F d, Y',
                'full' => 'l, F d, Y'
            ];
        }
        
        return date($formats[$format] ?? $formats['long'], $timestamp);
    }
    
    /**
     * Format number according to locale
     */
    public static function number($number, int $decimals = 0, ?string $locale = null): string
    {
        $locale = $locale ?? self::getLocale();
        
        if ($locale === 'tr') {
            return number_format($number, $decimals, ',', '.');
        } else {
            return number_format($number, $decimals, '.', ',');
        }
    }
    
    /**
     * Format currency according to locale
     */
    public static function currency($amount, ?string $locale = null): string
    {
        $locale = $locale ?? self::getLocale();
        
        if ($locale === 'tr') {
            return self::number($amount, 2, $locale) . ' ₺';
        } else {
            return '$' . self::number($amount, 2, $locale);
        }
    }
}

// Global helper functions
if (!function_exists('__')) {
    function __(string $key, array $replacements = []): string {
        return Translator::translate($key, $replacements);
    }
}

if (!function_exists('_n')) {
    function _n(string $key, int $count, array $replacements = []): string {
        return Translator::plural($key, $count, $replacements);
    }
}

if (!function_exists('_d')) {
    function _d($date, string $format = 'long'): string {
        return Translator::date($date, $format);
    }
}

if (!function_exists('_c')) {
    function _c($amount): string {
        return Translator::currency($amount);
    }
}

