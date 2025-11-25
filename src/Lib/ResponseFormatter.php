<?php
/**
 * Response Formatter
 * Standardizes API and JSON responses
 */
class ResponseFormatter
{
    /**
     * Control whether json() terminates execution (useful for tests).
     */
    private static bool $autoTerminate = true;

    /**
     * Success response
     */
    public static function success($data = null, string $message = 'İşlem başarılı', int $statusCode = 200): void
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        self::json($payload, $statusCode);
    }
    
    /**
     * Error response
     */
    public static function error(string $message, $errors = [], int $statusCode = 400): void
    {
        if (is_int($errors)) {
            $statusCode = $errors;
            $errors = [];
        }

        $payload = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        self::json($payload, $statusCode);
    }
    
    /**
     * Paginated response
     */
    public static function paginated(array $data, int $total, int $page, int $perPage, string $message = 'Veriler başarıyla getirildi'): void
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage),
                'has_next' => ($page * $perPage) < $total,
                'has_prev' => $page > 1
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];

        self::json($payload);
    }
    
    /**
     * Send JSON response
     */
    public static function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (self::$autoTerminate) {
            exit;
        }
    }
    
    /**
     * Validation error response
     */
    public static function validationError(array $errors, string $message = 'Validasyon hatası'): void
    {
        self::error($message, $errors, 422);
    }
    
    /**
     * Unauthorized error response
     */
    public static function unauthorized(string $message = 'Yetkisiz erişim'): void
    {
        self::error($message, [], 401);
    }
    
    /**
     * Forbidden error response
     */
    public static function forbidden(string $message = 'Bu işlem için yetkiniz yok'): void
    {
        self::error($message, [], 403);
    }
    
    /**
     * Not found error response
     */
    public static function notFound(string $message = 'Kayıt bulunamadı'): void
    {
        self::error($message, [], 404);
    }
    
    /**
     * Server error response
     */
    public static function serverError(string $message = 'Sunucu hatası'): void
    {
        $errorCode = APP_DEBUG ? 500 : 500;
        self::error($message, [], $errorCode);
    }
    
    /**
     * Rate limit error response
     */
    public static function rateLimitExceeded(string $message = 'Çok fazla istek gönderdiniz. Lütfen daha sonra tekrar deneyin.'): void
    {
        self::error($message, [], 429);
    }

    /**
     * Enable or disable automatic termination after emitting JSON (primarily for testing).
     */
    public static function setAutoTerminate(bool $enable): void
    {
        self::$autoTerminate = $enable;
    }
}

