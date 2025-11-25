<?php

declare(strict_types=1);

/**
 * Controller Helper
 * 
 * Common helper methods for controllers to reduce code duplication.
 * 
 * @package App\Lib
 * @author System
 * @version 1.0
 */

class ControllerHelper
{
    /**
     * Verify CSRF token and redirect on failure
     * 
     * @param string $redirectUrl URL to redirect to on failure
     * @param string $errorMessage Error message to flash
     * @return bool True if CSRF token is valid, false otherwise (redirects on failure)
     */
    public static function verifyCsrfOrRedirect(string $redirectUrl, string $errorMessage = 'Güvenlik hatası. Lütfen tekrar deneyin.'): bool
    {
        // ===== CRITICAL FIX: Ensure session is started with correct cookie path =====
        // Use SessionHelper for centralized session management
        SessionHelper::ensureStarted();
        // ===== CRITICAL FIX END =====
        
        if (!CSRF::verifyRequest()) {
            // ===== PRODUCTION FIX: Log CSRF failure details =====
            error_log("ControllerHelper::verifyCsrfOrRedirect() - CSRF verification failed");
            error_log("  Session ID: " . session_id());
            error_log("  Session Status: " . session_status());
            error_log("  Cookie Name: " . session_name());
            error_log("  Cookie Exists: " . (isset($_COOKIE[session_name()]) ? 'yes' : 'no'));
            error_log("  POST Token: " . substr($_POST['csrf_token'] ?? 'none', 0, 16));
            error_log("  Session Tokens: " . (isset($_SESSION['csrf_tokens']) ? count($_SESSION['csrf_tokens']) : 0));
            // ===== PRODUCTION FIX END =====
            
            Utils::flash('error', $errorMessage);
            redirect(base_url($redirectUrl));
            return false;
        }
        return true;
    }

    /**
     * Verify request method is POST and redirect if not
     * 
     * @param string $redirectUrl URL to redirect to if not POST
     * @return bool True if POST, false otherwise (redirects on failure)
     */
    public static function requirePostOrRedirect(string $redirectUrl): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url($redirectUrl));
            return false;
        }
        return true;
    }

    /**
     * Flash success message and redirect
     * 
     * @param string $message Success message
     * @param string $redirectUrl URL to redirect to
     * @return void
     */
    public static function flashSuccessAndRedirect(string $message, string $redirectUrl): void
    {
        Utils::flash('success', $message);
        redirect(base_url($redirectUrl));
    }

    /**
     * Flash error message and redirect
     * 
     * @param string $message Error message
     * @param string $redirectUrl URL to redirect to
     * @return void
     */
    public static function flashErrorAndRedirect(string $message, string $redirectUrl): void
    {
        Utils::flash('error', $message);
        redirect(base_url($redirectUrl));
    }

    /**
     * Handle exception with error logging and user-friendly message
     * 
     * @param Exception $e Exception to handle
     * @param string $context Context for error logging (e.g., "CustomerController::store()")
     * @param string $userMessage User-friendly error message
     * @param string|null $redirectUrl URL to redirect to (null = no redirect)
     * @return void
     */
    public static function handleException(Exception $e, string $context, string $userMessage, ?string $redirectUrl = null): void
    {
        error_log("{$context} error: " . $e->getMessage());
        $message = defined('APP_DEBUG') && APP_DEBUG ? $userMessage . ': ' . $e->getMessage() : $userMessage;
        Utils::flash('error', $message);
        
        if ($redirectUrl !== null) {
            redirect(base_url($redirectUrl));
        }
    }

    /**
     * Validate ID parameter and return integer or null
     * 
     * @param mixed $id ID to validate
     * @return int|null Validated ID or null if invalid
     */
    public static function validateId($id): ?int
    {
        if (!$id || !is_numeric($id)) {
            return null;
        }
        return (int)$id;
    }

    /**
     * Validate and sanitize pagination parameters
     * 
     * @param array $params GET parameters
     * @param int $defaultPage Default page number
     * @param int $defaultLimit Default limit
     * @return array ['page' => int, 'limit' => int, 'offset' => int]
     */
    public static function validatePagination(array $params, int $defaultPage = 1, int $defaultLimit = 20): array
    {
        require_once __DIR__ . '/../Constants/AppConstants.php';
        
        // If page is invalid, use default. InputSanitizer::int() returns null for invalid values.
        $pageValue = InputSanitizer::int($params['page'] ?? $defaultPage, AppConstants::MIN_PAGE, AppConstants::MAX_PAGE);
        $page = $pageValue ?? $defaultPage;
        
        // If limit is invalid, use default
        $limitValue = InputSanitizer::int($params['limit'] ?? $defaultLimit, 1, AppConstants::MAX_PAGE_SIZE);
        $limit = $limitValue ?? $defaultLimit;
        
        $offset = ($page - 1) * $limit;
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Validate and sanitize date range parameters
     * 
     * @param array $params GET parameters
     * @return array ['date_from' => string|null, 'date_to' => string|null]
     */
    public static function validateDateRange(array $params): array
    {
        require_once __DIR__ . '/../Constants/AppConstants.php';
        
        $dateFrom = InputSanitizer::date($params['date_from'] ?? '', AppConstants::DATE_FORMAT);
        $dateTo = InputSanitizer::date($params['date_to'] ?? '', AppConstants::DATE_FORMAT);
        
        return [
            'date_from' => $dateFrom ?: null,
            'date_to' => $dateTo ?: null
        ];
    }

    /**
     * Build WHERE clause and parameters for filtering
     * 
     * @param array $filters Filter conditions ['field' => 'value']
     * @param array $allowedFields Allowed field names for filtering
     * @return array ['where' => string, 'params' => array]
     */
    public static function buildWhereClause(array $filters, array $allowedFields): array
    {
        $where = [];
        $params = [];
        
        foreach ($filters as $field => $value) {
            if (!in_array($field, $allowedFields) || $value === null || $value === '') {
                continue;
            }
            
            if (is_array($value)) {
                $placeholders = implode(',', array_fill(0, count($value), '?'));
                $where[] = "{$field} IN ({$placeholders})";
                $params = array_merge($params, $value);
            } else {
                $where[] = "{$field} = ?";
                $params[] = $value;
            }
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        return [
            'where' => $whereClause,
            'params' => $params
        ];
    }
}

