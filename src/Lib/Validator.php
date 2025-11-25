<?php
/**
 * Basit Doğrulayıcı Sınıf
 */

class Validator
{
    private $errors = [];
    private $data = [];

    public function __construct($data)
    {
        $this->data = $this->normalize($data);
    }

    private function normalize($value)
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalize($item);
            }
            return $normalized;
        }

        if (is_string($value)) {
            // Trim and remove null bytes
            $value = trim($value);
            $value = str_replace("\0", '', $value);
            return $value;
        }

        return $value;
    }
    
    /**
     * Sanitize string for safe output (prevent XSS)
     */
    public function sanitize($field, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value) && is_string($value)) {
            // Remove HTML tags and encode entities for safe storage
            // Note: This is for sanitization, not validation
            $this->data[$field] = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
        }
        return $this;
    }
    
    /**
     * Validate and sanitize URL
     */
    public function url($field, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value)) {
            $url = filter_var($value, FILTER_VALIDATE_URL);
            if (!$url) {
                $this->errors[$field] = $message ?: "$field geçerli bir URL olmalıdır";
            } else {
                $this->data[$field] = $url;
            }
        }
        return $this;
    }
    
    /**
     * Validate positive number
     */
    public function positive($field, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value)) {
            $num = is_numeric($value) ? (float)$value : null;
            if ($num === null || $num <= 0) {
                $this->errors[$field] = $message ?: "$field pozitif bir sayı olmalıdır";
            }
        }
        return $this;
    }
    
    /**
     * Validate date is in the future
     */
    public function futureDate($field, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value)) {
            try {
                $date = new DateTime($value);
                $now = new DateTime();
                if ($date <= $now) {
                    $this->errors[$field] = $message ?: "$field gelecekte bir tarih olmalıdır";
                }
            } catch (Exception $e) {
                $this->errors[$field] = $message ?: "$field geçerli bir tarih olmalıdır";
            }
        }
        return $this;
    }
    
    /**
     * Validate date is in the past
     */
    public function pastDate($field, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value)) {
            try {
                $date = new DateTime($value);
                $now = new DateTime();
                if ($date >= $now) {
                    $this->errors[$field] = $message ?: "$field geçmişte bir tarih olmalıdır";
                }
            } catch (Exception $e) {
                $this->errors[$field] = $message ?: "$field geçerli bir tarih olmalıdır";
            }
        }
        return $this;
    }
    
    /**
     * Validate datetime format and ensure end is after start
     */
    public function datetimeAfter($field, $afterField, $message = null)
    {
        $value = $this->data[$field] ?? null;
        $afterValue = $this->data[$afterField] ?? null;
        
        if ($this->hasValue($value) && $this->hasValue($afterValue)) {
            try {
                $date = new DateTime($value);
                $afterDate = new DateTime($afterValue);
                
                if ($date <= $afterDate) {
                    $this->errors[$field] = $message ?: "$field, $afterField tarihinden sonra olmalıdır";
                }
            } catch (Exception $e) {
                $this->errors[$field] = $message ?: "Geçersiz tarih formatı";
            }
        }
        return $this;
    }
    
    /**
     * Validate alphanumeric string (with optional spaces and special chars)
     */
    public function alphaNum($field, $allowSpaces = false, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value) && is_string($value)) {
            $pattern = $allowSpaces ? '/^[a-zA-Z0-9\s]+$/' : '/^[a-zA-Z0-9]+$/';
            if (!preg_match($pattern, $value)) {
                $this->errors[$field] = $message ?: "$field sadece harf, rakam" . ($allowSpaces ? " ve boşluk" : "") . " içerebilir";
            }
        }
        return $this;
    }

    private function hasValue($value)
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return $value !== '';
        }

        if (is_array($value)) {
            return !empty(array_filter($value, function ($item) {
                return $this->hasValue($item);
            }));
        }

        return true;
    }

    private function length($value)
    {
        if (!is_string($value)) {
            $value = (string) $value;
        }

        return function_exists('mb_strlen')
            ? mb_strlen($value, 'UTF-8')
            : strlen($value);
    }

    public function required($field, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if (!$this->hasValue($value)) {
            $this->errors[$field] = $message ?: "$field alanı zorunludur";
        }
        return $this;
    }

    public function email($field, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?: "$field geçerli bir email adresi olmalıdır";
        }
        return $this;
    }

    public function password($field, array $options = [], $message = null)
    {
        $value = $this->data[$field] ?? null;
        if (!$this->hasValue($value)) {
            return $this;
        }

        $min = $options['min'] ?? 8;
        $requireUpper = $options['require_upper'] ?? true;
        $requireLower = $options['require_lower'] ?? true;
        $requireNumber = $options['require_number'] ?? true;
        $requireSymbol = $options['require_symbol'] ?? false;

        if ($this->length($value) < $min) {
            $this->errors[$field] = $message ?: "Şifre en az {$min} karakter olmalıdır";
            return $this;
        }

        if ($requireUpper && !preg_match('/[A-ZÇĞİÖŞÜ]/u', $value)) {
            $this->errors[$field] = $message ?: 'Şifre en az bir büyük harf içermelidir';
            return $this;
        }

        if ($requireLower && !preg_match('/[a-zçğıöşü]/u', $value)) {
            $this->errors[$field] = $message ?: 'Şifre en az bir küçük harf içermelidir';
            return $this;
        }

        if ($requireNumber && !preg_match('/\d/', $value)) {
            $this->errors[$field] = $message ?: 'Şifre en az bir rakam içermelidir';
            return $this;
        }

        if ($requireSymbol && !preg_match('/[\p{P}\p{S}]/u', $value)) {
            $this->errors[$field] = $message ?: 'Şifre en az bir özel karakter içermelidir';
            return $this;
        }

        return $this;
    }

    public function phone($field, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value)) {
            $digits = preg_replace('/[^0-9]/', '', $value);
            if (strlen($digits) < 10) {
                $this->errors[$field] = $message ?: "$field geçerli bir telefon numarası olmalıdır";
            }
        }
        return $this;
    }

    public function min($field, $min, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value) && $this->length($value) < $min) {
            $this->errors[$field] = $message ?: "$field en az $min karakter olmalıdır";
        }
        return $this;
    }

    public function max($field, $max, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value) && $this->length($value) > $max) {
            $this->errors[$field] = $message ?: "$field en fazla $max karakter olabilir";
        }
        return $this;
    }

    public function numeric($field, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value) && !is_numeric($value)) {
            $this->errors[$field] = $message ?: "$field sayısal bir değer olmalıdır";
        }
        return $this;
    }

    public function date($field, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value)) {
            $date = DateTime::createFromFormat('Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                $this->errors[$field] = $message ?: "$field geçerli bir tarih olmalıdır (YYYY-MM-DD)";
            }
        }
        return $this;
    }

    public function datetime($field, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value)) {
            $normalized = null;
            $formats = ['Y-m-d H:i', 'Y-m-d\TH:i'];
            foreach ($formats as $format) {
                $dt = DateTime::createFromFormat($format, $value);
                if ($dt && $dt->format($format) === $value) {
                    $normalized = $dt->format('Y-m-d H:i');
                    break;
                }
            }

            if ($normalized === null) {
                $this->errors[$field] = $message ?: "$field geçerli bir tarih-saat olmalıdır (YYYY-MM-DD HH:MM)";
            } else {
                $this->data[$field] = $normalized;
            }
        }
        return $this;
    }

    public function in($field, $values, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value) && !in_array($value, $values, true)) {
            $this->errors[$field] = $message ?: "$field geçerli bir değer olmalıdır";
        }
        return $this;
    }

    public function same($field, $otherField, $message = null)
    {
        $value = $this->data[$field] ?? null;
        $other = $this->data[$otherField] ?? null;
        if ($value !== $other) {
            $otherLabel = $this->humanizeField($otherField);
            $fieldLabel = $this->humanizeField($field);
            $this->errors[$field] = $message ?: "$fieldLabel alanı $otherLabel ile aynı olmalıdır.";
        }
        return $this;
    }

    private function humanizeField(string $field): string
    {
        $map = [
            'confirm_password' => 'Şifre onayı',
            'new_password' => 'Yeni şifre',
            'current_password' => 'Mevcut şifre',
        ];
        if (isset($map[$field])) {
            return $map[$field];
        }
        return ucwords(str_replace(['_', '-'], ' ', $field));
    }

    /**
     * Table and field name validator - only allows alphanumeric and underscore
     * Prevents SQL injection by validating identifier names
     * 
     * @param string $name Identifier name to validate
     * @return string|null Validated name or null if invalid
     */
    private function validateIdentifier($name)
    {
        // Ensure input is string
        if (!is_string($name)) {
            return null;
        }
        
        // Maximum length check to prevent DoS
        if (strlen($name) > 64) {
            return null;
        }
        
        // Only allow alphanumeric characters and underscore
        // Must start with a letter or underscore
        if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $name)) {
            return null;
        }
        
        // Additional check: ensure no SQL keywords
        $sqlKeywords = ['select', 'insert', 'update', 'delete', 'drop', 'create', 'alter', 'exec', 'execute', 'union', 'script'];
        if (in_array(strtolower($name), $sqlKeywords, true)) {
            return null;
        }
        
        return $name;
    }

    public function unique($field, $table, $column = null, $excludeId = null, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value)) {
            // Validate table and field names to prevent SQL injection
            $table = $this->validateIdentifier($table);
            $columnName = $column ? $this->validateIdentifier($column) : $this->validateIdentifier($field);
            
            if (!$table || !$columnName) {
                $this->errors[$field] = $message ?: "Geçersiz tablo veya alan adı";
                return $this;
            }
            
            // Additional safety: check against whitelist of known tables (expanded for building management)
            $allowedTables = [
                'users', 'customers', 'staff', 'jobs', 'services', 'addresses',
                'buildings', 'units', 'management_fees', 'building_expenses', 
                'building_documents', 'building_meetings', 'building_announcements',
                'building_surveys', 'building_facilities', 'building_reservations',
                'resident_users', 'resident_requests', 'online_payments',
                'recurring_jobs', 'recurring_job_occurrences', 'companies',
                'job_payments', 'money_entries', 'staff_payments'
            ];
            if (!in_array($table, $allowedTables, true)) {
                $this->errors[$field] = $message ?: "Geçersiz tablo adı";
                return $this;
            }
            
            // ===== ERR-007 FIX: Validate table and column names =====
            $sanitizedTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            $sanitizedColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $columnName);
            if ($sanitizedTable !== $table || $sanitizedColumn !== $columnName) {
                $this->errors[$field] = $message ?: "Geçersiz tablo veya alan adı";
                return $this;
            }
            // ===== ERR-007 FIX: End =====
            
            $db = Database::getInstance();
            $sql = "SELECT id FROM `{$sanitizedTable}` WHERE `{$sanitizedColumn}` = ?";
            $params = [$value];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $existing = $db->fetch($sql, $params);
            if ($existing) {
                $this->errors[$field] = $message ?: "$field zaten kullanılmaktadır";
            }
        }
        return $this;
    }

    public function fails()
    {
        return !empty($this->errors);
    }

    public function errors()
    {
        return $this->errors;
    }

    public function firstError()
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    public function get($field, $default = null)
    {
        return $this->data[$field] ?? $default;
    }

    public function all()
    {
        return $this->data;
    }
    
    /**
     * Get validated data (only fields that passed validation)
     */
    public function validated()
    {
        $validated = [];
        foreach ($this->data as $field => $value) {
            // Only include fields that don't have errors
            if (!isset($this->errors[$field])) {
                $validated[$field] = $value;
            }
        }
        return $validated;
    }
    
    /**
     * Validate file upload
     */
    public function file($field, $options = [], $message = null)
    {
        $file = $_FILES[$field] ?? null;
        
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            if (isset($options['required']) && $options['required']) {
                $this->errors[$field] = $message ?: "$field dosyası gerekli";
            }
            return $this;
        }
        
        // Check upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[$field] = $message ?: "Dosya yüklenirken hata oluştu";
            return $this;
        }
        
        // Check file size
        if (isset($options['max_size'])) {
            $maxSize = $options['max_size'] * 1024 * 1024; // Convert MB to bytes
            if ($file['size'] > $maxSize) {
                $this->errors[$field] = $message ?: "Dosya boyutu en fazla " . $options['max_size'] . " MB olabilir";
                return $this;
            }
        }
        
        // Check file type
        if (isset($options['allowed_types'])) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $options['allowed_types'], true)) {
                $this->errors[$field] = $message ?: "Dosya türü geçersiz. İzin verilen türler: " . implode(', ', $options['allowed_types']);
                return $this;
            }
        }
        
        return $this;
    }
    
    public function confirmed($field, $confirmationField, $message = null)
    {
        $value = $this->data[$field] ?? null;
        $confirmation = $this->data[$confirmationField] ?? null;
        
        if ($this->hasValue($value) && $value !== $confirmation) {
            $this->errors[$field] = $message ?: "$field alanı doğrulama ile eşleşmiyor";
        }
        return $this;
    }
    
    public function different($field, $otherField, $message = null)
    {
        $value = $this->data[$field] ?? null;
        $other = $this->data[$otherField] ?? null;
        
        if ($this->hasValue($value) && $value === $other) {
            $this->errors[$field] = $message ?: "$field alanı $otherField alanından farklı olmalıdır";
        }
        return $this;
    }
    
    public function exists($field, $table, $column = null, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value)) {
            // Validate table and field names to prevent SQL injection
            $table = $this->validateIdentifier($table);
            $column = $column ? $this->validateIdentifier($column) : $this->validateIdentifier($field);
            
            if (!$table || !$column) {
                $this->errors[$field] = $message ?: "Geçersiz tablo veya alan adı";
                return $this;
            }
            
            // Additional safety: check against whitelist of known tables
            $allowedTables = [
                'users', 'customers', 'staff', 'jobs', 'services', 'addresses',
                'buildings', 'units', 'management_fees', 'building_expenses', 
                'building_documents', 'building_meetings', 'building_announcements',
                'building_surveys', 'building_facilities', 'building_reservations',
                'resident_users', 'resident_requests', 'online_payments',
                'recurring_jobs', 'recurring_job_occurrences', 'companies',
                'job_payments', 'money_entries', 'staff_payments'
            ];
            if (!in_array($table, $allowedTables, true)) {
                $this->errors[$field] = $message ?: "Geçersiz tablo adı";
                return $this;
            }
            
            // ===== ERR-007 FIX: Validate table and column names =====
            $sanitizedTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            $sanitizedColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
            if ($sanitizedTable !== $table || $sanitizedColumn !== $column) {
                $this->errors[$field] = $message ?: "Geçersiz tablo veya alan adı";
                return $this;
            }
            // ===== ERR-007 FIX: End =====
            
            $db = Database::getInstance();
            $existing = $db->fetch("SELECT id FROM `{$sanitizedTable}` WHERE `{$sanitizedColumn}` = ?", [$value]);
            
            if (!$existing) {
                $this->errors[$field] = $message ?: "$field bulunamadı";
            }
        }
        return $this;
    }
    
    public function integer($field, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field] = $message ?: "$field tam sayı olmalıdır";
        }
        return $this;
    }
    
    public function regex($field, $pattern, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value) && !preg_match($pattern, $value)) {
            $this->errors[$field] = $message ?: "$field geçersiz format";
        }
        return $this;
    }
    
    public function alpha($field, $allowSpaces = false, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value) && is_string($value)) {
            $pattern = $allowSpaces ? '/^[a-zA-Z\s]+$/' : '/^[a-zA-Z]+$/';
            if (!preg_match($pattern, $value)) {
                $this->errors[$field] = $message ?: "$field sadece harf" . ($allowSpaces ? " ve boşluk" : "") . " içerebilir";
            }
        }
        return $this;
    }
    
    public function string($field, $minLength = null, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value) && !is_string($value)) {
            $this->errors[$field] = $message ?: "$field metin olmalıdır";
        } elseif ($this->hasValue($value) && $minLength && $this->length($value) < $minLength) {
            $this->errors[$field] = $message ?: "$field en az $minLength karakter olmalıdır";
        }
        return $this;
    }
    
    public function notIn($field, array $values, $message = null)
    {
        $value = $this->data[$field] ?? null;
        if ($this->hasValue($value) && in_array($value, $values, true)) {
            $this->errors[$field] = $message ?: "$field alanı izin verilmeyen bir değer içeriyor";
        }
        return $this;
    }
    
    public function firstErrorField()
    {
        return !empty($this->errors) ? key($this->errors) : null;
    }
    
    /**
     * Sanitize HTML output (prevent XSS)
     */
    public function sanitizeHtml($value)
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate IBAN
     */
    public function validateIBAN($value)
    {
        $value = strtoupper(str_replace(' ', '', $value));
        
        // IBAN must be between 15 and 34 characters
        if (strlen($value) < 15 || strlen($value) > 34) {
            return false;
        }
        
        // IBAN format: 2 letters (country code) + 2 digits (check digits) + up to 30 alphanumeric
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $value)) {
            return false;
        }
        
        // Move first 4 characters to end
        $rearranged = substr($value, 4) . substr($value, 0, 4);
        
        // Convert letters to numbers (A=10, B=11, etc.)
        $numeric = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numeric .= (ord($char) - ord('A') + 10);
            } else {
                $numeric .= $char;
            }
        }
        
        // Calculate mod 97
        return (int)bcmod($numeric, '97') === 1;
    }
    
    /**
     * Validate Turkish phone number
     */
    public function validateTurkishPhone($value)
    {
        // Remove spaces, dashes, parentheses
        $value = preg_replace('/[\s\-\(\)]/', '', $value);
        
        // Turkish mobile: 05XX XXX XX XX (10 digits) or +90 5XX XXX XX XX (13 chars)
        // Format: +90XXXXXXXXXX or 05XXXXXXXXX
        $pattern = '/^(\+90[0-9]{10}|0[0-9]{10})$/';
        return preg_match($pattern, $value);
    }
    
    /**
     * Validate Turkish tax number
     */
    public function validateTaxNumber($value)
    {
        // Remove spaces and convert to string
        $value = str_replace(' ', '', (string)$value);
        
        // Must be 10 or 11 digits
        if (!preg_match('/^[0-9]{10}$/', $value) && !preg_match('/^[0-9]{11}$/', $value)) {
            return false;
        }
        
        // 10-digit tax number (TC Kimlik No algorithm)
        if (strlen($value) === 10) {
            // First digit cannot be 0
            if ($value[0] === '0') {
                return false;
            }
            
            // Calculate check digits
            $sumOdd = 0;
            $sumEven = 0;
            
            for ($i = 0; $i < 9; $i++) {
                if ($i % 2 === 0) {
                    $sumOdd += (int)$value[$i];
                } else {
                    $sumEven += (int)$value[$i];
                }
            }
            
            $check1 = ($sumOdd * 7 - $sumEven) % 10;
            if ($check1 < 0) $check1 += 10;
            
            $check2 = ($sumOdd + $sumEven + $check1) % 10;
            
            return (int)$value[9] === $check2;
        }
        
        // 11-digit tax number (Vergi Numarası algorithm)
        if (strlen($value) === 11) {
            // First digit cannot be 0
            if ($value[0] === '0') {
                return false;
            }
            
            // Calculate check digit
            $sum = 0;
            for ($i = 0; $i < 10; $i++) {
                $digit = (int)$value[$i];
                $weight = (10 - $i) % 10;
                if ($weight === 0) $weight = 10;
                $sum += $digit * $weight;
            }
            
            $check = $sum % 11;
            if ($check < 2) {
                $checkDigit = 0;
            } else {
                $checkDigit = 11 - $check;
            }
            
            return (int)$value[10] === $checkDigit;
        }
        
        return false;
    }
    
}