<?php
/**
 * Validation Rules
 * Centralized validation rules for consistent validation
 */
class ValidationRules
{
    /**
     * Validate Turkish phone number
     */
    public static function turkishPhone(?string $phone): bool
    {
        if (empty($phone)) {
            return false;
        }
        
        // Remove spaces, dashes, parentheses
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $phone);
        
        // Turkish phone patterns: 05XX XXX XX XX or +905XX XXX XX XX
        return preg_match('/^(\+90)?5\d{9}$/', $cleaned);
    }
    
    /**
     * Validate Turkish TC number
     */
    public static function turkishTcNumber(?string $tc): bool
    {
        if (empty($tc) || strlen($tc) !== 11) {
            return false;
        }
        
        // TC number validation algorithm
        if (!ctype_digit($tc)) {
            return false;
        }
        
        $digits = str_split($tc);
        
        // First digit cannot be 0
        if ($digits[0] === '0') {
            return false;
        }
        
        // Checksum validation
        $sumOdd = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
        $sumEven = $digits[1] + $digits[3] + $digits[5] + $digits[7];
        
        $check1 = ($sumOdd * 7 - $sumEven) % 10;
        $check2 = ($sumOdd + $sumEven + $digits[9]) % 10;
        
        return ($check1 == $digits[9] && $check2 == $digits[10]);
    }
    
    /**
     * Validate Turkish date format (DD.MM.YYYY)
     */
    public static function turkishDate(?string $date): bool
    {
        if (empty($date)) {
            return false;
        }
        
        if (!preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $date, $matches)) {
            return false;
        }
        
        $day = (int)$matches[1];
        $month = (int)$matches[2];
        $year = (int)$matches[3];
        
        return checkdate($month, $day, $year);
    }
    
    /**
     * Validate money amount
     */
    public static function moneyAmount($amount): bool
    {
        $amount = (float)$amount;
        return $amount >= 0 && $amount <= 999999999.99; // Max 999M with 2 decimals
    }
    
    /**
     * Validate email
     */
    public static function email(?string $email): bool
    {
        if (empty($email)) {
            return false;
        }
        
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Sanitize string for SQL
     */
    public static function sanitizeString(?string $input, int $maxLength = 255): string
    {
        if ($input === null) {
            return '';
        }
        
        $input = trim($input);
        $input = mb_substr($input, 0, $maxLength, 'UTF-8');
        $input = strip_tags($input);
        
        return $input;
    }
    
    /**
     * Validate required field
     */
    public static function required($value): bool
    {
        if ($value === null) {
            return false;
        }
        
        if (is_string($value)) {
            return trim($value) !== '';
        }
        
        if (is_array($value)) {
            return count($value) > 0;
        }
        
        return $value !== false && $value !== 0;
    }
}

