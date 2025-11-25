<?php
/**
 * Company context badge for header (SUPERADMIN only).
 * Compact version designed for header integration.
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

<div class="inline-flex items-center gap-2">
    <div class="flex items-center gap-1.5">
        <i class="fas fa-building text-white/90 text-xs"></i>
        <span class="text-white/90 font-medium text-xs"><?= htmlspecialchars(mb_strlen($companyName) > 20 ? mb_substr($companyName, 0, 20) . '...' : $companyName) ?></span>
    </div>
    
    <?php if (!empty($companies)): ?>
        <form method="GET" action="<?= e($baseUrl) ?>" class="inline-block">
            <?php foreach ($queryParams as $key => $value): ?>
                <?php if ($key !== 'company_filter'): ?>
                    <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <select name="company_filter" 
                    onchange="this.form.submit()" 
                    class="px-2 py-1 text-xs border border-white/30 rounded bg-white/15 text-white focus:ring-2 focus:ring-white/50 focus:border-white/50 transition-colors appearance-none cursor-pointer"
                    style="background-image: url('data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22white%22%3E%3Cpath d=%22M7 10l5 5 5-5z%22/%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 0.25rem center; background-size: 1em 1em; padding-right: 1.75rem;">
                <option value="">Tümü</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?= (int)$company['id'] ?>" <?= $currentCompanyId === (int)$company['id'] ? 'selected' : '' ?> style="background-color: #1e293b; color: white;">
                        <?= e($company['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

