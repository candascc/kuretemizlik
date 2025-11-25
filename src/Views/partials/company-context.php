<?php
/**
 * Company context badge for dashboards/reports.
 * Only visible to SUPERADMIN users.
 *
 * Expected variables:
 * - $companyContext (array|null) => ['id' => int|null, 'name' => string]
 */

// Only show for SUPERADMIN
if (!Auth::isSuperAdmin()) {
    return;
}

// Get current company context
$currentCompanyId = isset($_GET['company_filter']) && $_GET['company_filter'] !== '' ? (int)$_GET['company_filter'] : null;
$companyName = 'Tüm Şirketler';

if (!empty($companyContext)) {
    $companyName = $companyContext['name'] ?? ('Şirket #' . ($companyContext['id'] ?? '?'));
    $currentCompanyId = $companyContext['id'] ?? $currentCompanyId;
} elseif ($currentCompanyId) {
    // Fetch company name from database
    try {
        $db = Database::getInstance();
        $tableExists = $db->fetch("SELECT name FROM sqlite_master WHERE type='table' AND name='companies'");
        if ($tableExists) {
            $company = $db->fetch("SELECT id, name FROM companies WHERE id = ?", [$currentCompanyId]);
            if ($company) {
                $companyName = $company['name'];
            }
        }
    } catch (Throwable $e) {
        // Ignore errors
    }
}

// Get all companies for dropdown
$companies = [];
try {
    $db = Database::getInstance();
    $tableExists = $db->fetch("SELECT name FROM sqlite_master WHERE type='table' AND name='companies'");
    if ($tableExists) {
        $companies = $db->fetchAll("SELECT id, name FROM companies WHERE is_active = 1 ORDER BY name");
    }
} catch (Throwable $e) {
    // Ignore errors
}

$currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
$urlParts = parse_url($currentUrl);
$baseUrl = $urlParts['path'] ?? '/';
$queryParams = [];
if (isset($urlParts['query'])) {
    parse_str($urlParts['query'], $queryParams);
}
?>

<div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 px-4 py-3 rounded-xl border border-primary-200 bg-primary-50 dark:bg-primary-900/20 dark:border-primary-800 text-primary-700 dark:text-primary-200 shadow-sm">
    <div class="flex items-center gap-3 flex-1">
        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary-600 text-white flex-shrink-0">
            <i class="fas fa-building"></i>
        </div>
        <div class="text-sm flex-1">
            <p class="text-xs uppercase tracking-wide text-primary-500 dark:text-primary-300 font-semibold mb-1">Şirket Bağlamı</p>
            <p class="font-semibold text-base"><?= e($companyName) ?></p>
        </div>
    </div>
    
    <?php if (!empty($companies)): ?>
        <div class="flex-shrink-0 w-full sm:w-auto">
            <form method="GET" action="<?= e($baseUrl) ?>" class="inline-block w-full sm:w-auto">
                <?php foreach ($queryParams as $key => $value): ?>
                    <?php if ($key !== 'company_filter'): ?>
                        <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <select name="company_filter" 
                        onchange="this.form.submit()" 
                        class="w-full sm:w-auto px-3 py-2 text-sm border border-primary-300 dark:border-primary-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                    <option value="">Tüm Şirketler</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= (int)$company['id'] ?>" <?= $currentCompanyId === (int)$company['id'] ? 'selected' : '' ?>>
                            <?= e($company['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    <?php else: ?>
        <span class="text-xs text-primary-500 dark:text-primary-200 flex-shrink-0">
            Filtreyi değiştirmek için adres çubuğuna <code class="px-1 py-0.5 bg-primary-100 dark:bg-primary-900 rounded">company_filter</code> parametresi ekleyin.
        </span>
    <?php endif; ?>
</div>

