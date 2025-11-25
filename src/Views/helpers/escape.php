<?php
/**
 * View Helper Functions
 * Escape and sanitization helpers for views
 */

if (!function_exists('e')) {
    /**
     * Escape output for safe HTML display (XSS prevention)
     * 
     * @param mixed $value Value to escape
     * @return string Escaped string
     */
    function e($value): string {
        if ($value === null) {
            return '';
        }
        
        if (is_array($value)) {
            return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
        }
        
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
            }
            return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
        }
        
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('h')) {
    /**
     * Alias for e() - HTML escape
     * 
     * @param mixed $value Value to escape
     * @return string Escaped string
     */
    function h($value): string {
        return e($value);
    }
}

