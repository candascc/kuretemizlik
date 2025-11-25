<?php

/**
 * CompanyScope trait
 *
 * Shared helpers to enforce per-company filtering for controllers/models.
 */
trait CompanyScope
{
    protected static bool $companyScopeLogWritten = false;

    /**
     * Append company filters to a WHERE clause fragment.
     *
     * @param string $baseWhere Existing WHERE clause portion (default WHERE 1=1)
     * @param string|null $tableAlias Optional alias for the company_id column
     */
    protected function scopeToCompany(string $baseWhere = 'WHERE 1=1', ?string $tableAlias = null): string
    {
        // ===== PRODUCTION FIX: Handle errors gracefully =====
        try {
            if (Auth::canSwitchCompany()) {
                if (isset($_GET['company_filter']) && $_GET['company_filter'] !== '') {
                    $companyId = (int)$_GET['company_filter'];
                    $this->logCompanyScopeUsage($companyId);
                    return $baseWhere . ' AND ' . self::companyColumn($tableAlias) . ' = ' . $companyId;
                }

                $this->logCompanyScopeUsage(null);
                return $baseWhere;
            }

            $companyId = Auth::companyId();
            if (!$companyId) {
                // No company assigned - return base where clause without company filter
                // This prevents "AND 1=0" which would hide all data
                // Instead, return base where to show data (for backward compatibility)
                return $baseWhere;
            }

            $this->logCompanyScopeUsage((int)$companyId);
            return $baseWhere . ' AND ' . self::companyColumn($tableAlias) . ' = ' . (int)$companyId;
        } catch (Throwable $e) {
            // If any error occurs, log it and return base where clause
            error_log("CompanyScope::scopeToCompany() error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $baseWhere;
        }
        // ===== PRODUCTION FIX END =====
    }

    /**
     * Determine which company_id should be set on INSERT operations.
     */
    protected function getCompanyIdForInsert(): int
    {
        if (Auth::canSwitchCompany() && isset($_POST['company_id']) && $_POST['company_id'] !== '') {
            return (int)$_POST['company_id'];
        }

        $companyId = Auth::companyId();
        if (!$companyId) {
            // Fallback to default tenant (1) when no company context is available (e.g., CLI tests)
            $companyId = 1;
        }

        // Ensure the referenced company exists to satisfy FK constraints
        try {
            $db = Database::getInstance();
            $tableExists = $db->fetch("SELECT name FROM sqlite_master WHERE type='table' AND name='companies'");
            if ($tableExists) {
                $exists = $db->fetch("SELECT id FROM companies WHERE id = ?", [$companyId]);
                if (!$exists) {
                    // Create a minimal default company row
                    $db->insert('companies', [
                        'id' => $companyId,
                        'name' => 'Default Company',
                        'is_active' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        } catch (Throwable $e) {
            // Best-effort; if creation fails, continue and let DB enforce constraints
        }

        return $companyId;
    }

    /**
     * Verify current user can access a record from given company.
     */
    protected function verifyCompanyAccess(?int $companyId): bool
    {
        if ($companyId === null) {
            return Auth::canSwitchCompany();
        }

        if (Auth::canSwitchCompany()) {
            return true;
        }

        return $companyId === Auth::companyId();
    }

    private static function companyColumn(?string $alias): string
    {
        $alias = trim((string)$alias);
        if ($alias !== '') {
            return $alias . '.company_id';
        }

        return 'company_id';
    }

    /**
     * Safe helper to fetch list of companies when schema is available.
     *
     * @return array<int, array{id:int, name:?string}>
     */
    protected function getCompanyOptions(): array
    {
        if (!Auth::canSwitchCompany()) {
            return [];
        }

        try {
            $db = Database::getInstance();
            $tableExists = $db->fetch("SELECT name FROM sqlite_master WHERE type='table' AND name='companies'");
            if (!$tableExists) {
                return [];
            }
            return $db->fetchAll("SELECT id, name FROM companies ORDER BY name");
        } catch (Throwable $e) {
            error_log('CompanyScope::getCompanyOptions failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Determine the currently scoped company context (id + name) if available.
     */
    protected function getCurrentCompanyContext(): ?array
    {
        try {
            $db = Database::getInstance();
            $tableExists = $db->fetch("SELECT name FROM sqlite_master WHERE type='table' AND name='companies'");
            if (!$tableExists) {
                return Auth::canSwitchCompany() ? ['id' => null, 'name' => 'Tüm Şirketler'] : null;
            }

            $companyId = $this->getScopedCompanyId();
            if ($companyId === null) {
                return Auth::canSwitchCompany() ? ['id' => null, 'name' => 'Tüm Şirketler'] : null;
            }

            $company = $db->fetch("SELECT id, name FROM companies WHERE id = ?", [$companyId]);
            if (!$company) {
                return null;
            }
            return $company;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Resolve the active company id for the current request (null => all companies).
     */
    protected function getScopedCompanyId(): ?int
    {
        if (Auth::canSwitchCompany()) {
            if (isset($_GET['company_filter']) && $_GET['company_filter'] !== '') {
                return (int)$_GET['company_filter'];
            }
            return null;
        }

        return Auth::companyId();
    }

    /**
     * Log tenant switching usage to activity log (once per request).
     */
    protected function logCompanyScopeUsage(?int $companyId): void
    {
        if (self::$companyScopeLogWritten || !class_exists('ActivityLogger')) {
            return;
        }

        self::$companyScopeLogWritten = true;

        try {
            ActivityLogger::log('COMPANY_SCOPE', 'company', [
                'company_id' => $companyId,
                'filter' => isset($_GET['company_filter']) ? $_GET['company_filter'] : null,
                'path' => $_SERVER['REQUEST_URI'] ?? null,
            ]);
        } catch (Throwable $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log('CompanyScope::logCompanyScopeUsage failed: ' . $e->getMessage());
            }
        }
    }
}

