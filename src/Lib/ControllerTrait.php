<?php
declare(strict_types=1);

/**
 * Controller Trait
 * 
 * Phase 4.1: Code Duplication Reduction - Common controller patterns
 * 
 * Provides reusable methods for common controller operations to reduce code duplication.
 * This trait centralizes common patterns like model finding, POST/CSRF validation,
 * flash messages, exception handling, and pagination/date range validation.
 * 
 * @package App\Lib
 * @since Phase 4.1
 */
trait ControllerTrait
{
    /**
     * Find model by ID or return null with error handling
     * 
     * Validates the ID, attempts to find the model record, and handles errors gracefully.
     * If a redirect URL is provided, automatically redirects with an error message on failure.
     * 
     * @param object $model Model instance (must have find() method)
     * @param mixed $id ID to find (will be validated and converted to int)
     * @param string $notFoundMessage Error message to display if record not found
     * @param string|null $redirectUrl URL to redirect to if not found (null = no redirect, just return null)
     * @return array|null Model data array if found, null otherwise
     * 
     * @example
     * $job = $this->findOrFail($this->jobModel, $id, 'İş bulunamadı.', '/jobs');
     * if (!$job) {
     *     return; // Already redirected with error message
     * }
     */
    protected function findOrFail($model, $id, string $notFoundMessage = 'Kayıt bulunamadı', ?string $redirectUrl = null): ?array
    {
        $id = ControllerHelper::validateId($id);
        if (!$id) {
            if ($redirectUrl !== null) {
                ControllerHelper::flashErrorAndRedirect('Geçersiz ID.', $redirectUrl);
            }
            return null;
        }
        
        $record = $model->find($id);
        if (!$record) {
            if ($redirectUrl !== null) {
                ControllerHelper::flashErrorAndRedirect($notFoundMessage, $redirectUrl);
            }
            return null;
        }
        
        return $record;
    }
    
    /**
     * Require POST method or redirect
     * 
     * @param string $redirectUrl URL to redirect to if not POST
     * @return bool True if POST, false otherwise (redirects on failure)
     */
    protected function requirePost(string $redirectUrl): bool
    {
        return ControllerHelper::requirePostOrRedirect($redirectUrl);
    }
    
    /**
     * Verify CSRF token or redirect
     * 
     * Validates the CSRF token for the current request. If validation fails,
     * flashes an error message and redirects to the specified URL. This prevents
     * CSRF attacks by ensuring state-changing operations include valid tokens.
     * 
     * @param string $redirectUrl URL to redirect to if CSRF validation fails
     * @param string $errorMessage Error message to flash on validation failure
     * @return bool True if CSRF token is valid, false otherwise (redirects on failure)
     * 
     * @example
     * if (!$this->verifyCsrf('/jobs', 'Güvenlik hatası')) {
     *     return; // Already redirected with error message
     * }
     */
    protected function verifyCsrf(string $redirectUrl, string $errorMessage = 'Güvenlik hatası. Lütfen tekrar deneyin.'): bool
    {
        return ControllerHelper::verifyCsrfOrRedirect($redirectUrl, $errorMessage);
    }
    
    /**
     * Require POST and CSRF verification
     * 
     * Combines POST method validation and CSRF token verification in a single call.
     * This is a convenience method for endpoints that require both validations.
     * If either check fails, redirects with an appropriate error message.
     * 
     * @param string $redirectUrl URL to redirect to if validation fails
     * @param string $csrfErrorMessage Error message to flash on CSRF failure
     * @return bool True if both POST and CSRF checks pass, false otherwise (redirects on failure)
     * 
     * @example
     * if (!$this->requirePostAndCsrf('/jobs')) {
     *     return; // Already redirected
     * }
     */
    protected function requirePostAndCsrf(string $redirectUrl, string $csrfErrorMessage = 'Güvenlik hatası. Lütfen tekrar deneyin.'): bool
    {
        if (!$this->requirePost($redirectUrl)) {
            return false;
        }
        return $this->verifyCsrf($redirectUrl, $csrfErrorMessage);
    }
    
    /**
     * Flash success message and redirect
     * 
     * Stores a success message in the session flash storage and redirects to the
     * specified URL. The message will be available on the next page load.
     * 
     * @param string $message Success message to display
     * @param string $redirectUrl URL to redirect to after flashing message
     * @return void
     * 
     * @example
     * $this->flashSuccess('İş başarıyla oluşturuldu.', '/jobs');
     */
    protected function flashSuccess(string $message, string $redirectUrl): void
    {
        ControllerHelper::flashSuccessAndRedirect($message, $redirectUrl);
    }
    
    /**
     * Flash error message and redirect
     * 
     * Stores an error message in the session flash storage and redirects to the
     * specified URL. The message will be available on the next page load.
     * 
     * @param string $message Error message to display
     * @param string $redirectUrl URL to redirect to after flashing message
     * @return void
     * 
     * @example
     * $this->flashError('İş bulunamadı.', '/jobs');
     */
    protected function flashError(string $message, string $redirectUrl): void
    {
        ControllerHelper::flashErrorAndRedirect($message, $redirectUrl);
    }
    
    /**
     * Handle exception with error logging and user-friendly message
     * 
     * Centralized exception handling that logs the full exception details for debugging
     * while displaying a user-friendly message to the end user. Optionally redirects
     * to a specified URL after handling the exception.
     * 
     * @param Exception $e Exception to handle
     * @param string $context Context for error logging (e.g., "CustomerController::store()")
     * @param string $userMessage User-friendly error message to display
     * @param string|null $redirectUrl URL to redirect to after handling (null = no redirect)
     * @return void
     * 
     * @example
     * try {
     *     // ... operation ...
     * } catch (Exception $e) {
     *     $this->handleException($e, 'JobController::update()', 'İş güncellenemedi', '/jobs');
     * }
     */
    protected function handleException(Exception $e, string $context, string $userMessage, ?string $redirectUrl = null): void
    {
        ControllerHelper::handleException($e, $context, $userMessage, $redirectUrl);
    }
    
    /**
     * Validate pagination parameters
     * 
     * Validates and sanitizes pagination parameters from GET request, ensuring
     * they are within acceptable bounds. Returns validated page, limit, and
     * calculated offset for database queries.
     * 
     * @param array $params GET parameters containing 'page' and optionally 'limit'
     * @param int $defaultPage Default page number if not provided (default: 1)
     * @param int $defaultLimit Default items per page if not provided (default: 20)
     * @return array Associative array with keys: 'page' (int), 'limit' (int), 'offset' (int)
     * 
     * @example
     * $pagination = $this->validatePagination($_GET);
     * $items = $model->paginate($pagination['limit'], $pagination['offset']);
     */
    protected function validatePagination(array $params, int $defaultPage = 1, int $defaultLimit = 20): array
    {
        return ControllerHelper::validatePagination($params, $defaultPage, $defaultLimit);
    }
    
    /**
     * Validate date range parameters
     * 
     * Validates and sanitizes date range parameters from GET request, ensuring
     * they are in the correct format. Returns validated date strings or null
     * if not provided or invalid.
     * 
     * @param array $params GET parameters containing 'date_from' and 'date_to'
     * @return array Associative array with keys: 'date_from' (string|null), 'date_to' (string|null)
     * 
     * @example
     * $dateRange = $this->validateDateRange($_GET);
     * if ($dateRange['date_from'] && $dateRange['date_to']) {
     *     $items = $model->getByDateRange($dateRange['date_from'], $dateRange['date_to']);
     * }
     */
    protected function validateDateRange(array $params): array
    {
        return ControllerHelper::validateDateRange($params);
    }
    
    /**
     * Build WHERE clause and parameters for filtering
     * 
     * Safely builds a WHERE clause from filter conditions, only including fields
     * that are in the allowed fields whitelist. This prevents SQL injection by
     * restricting which fields can be used in queries.
     * 
     * @param array $filters Filter conditions as associative array ['field' => 'value']
     * @param array $allowedFields Whitelist of allowed field names for filtering
     * @return array Associative array with keys: 'where' (string SQL WHERE clause), 'params' (array of parameter values)
     * 
     * @example
     * $filters = ['status' => 'active', 'company_id' => 1];
     * $allowed = ['status', 'company_id', 'created_at'];
     * $where = $this->buildWhereClause($filters, $allowed);
     * // Returns: ['where' => 'WHERE status = ? AND company_id = ?', 'params' => ['active', 1]]
     */
    protected function buildWhereClause(array $filters, array $allowedFields): array
    {
        return ControllerHelper::buildWhereClause($filters, $allowedFields);
    }
}

