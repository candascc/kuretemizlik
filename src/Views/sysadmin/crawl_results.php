<?php
/**
 * Sysadmin Crawl Results View
 * 
 * PATH_CRAWL_SYSADMIN_WEB_V1: Display crawl results in HTML table
 */

$crawlResult = $crawlResult ?? [];
$currentUser = $currentUser ?? [];
$crawlRole = $crawlRole ?? 'SUPERADMIN';
$crawlUsername = $crawlUsername ?? '';

$baseUrl = $crawlResult['base_url'] ?? '';
$username = $crawlResult['username'] ?? '';
$totalCount = $crawlResult['total_count'] ?? 0;
$successCount = $crawlResult['success_count'] ?? 0;
$errorCount = $crawlResult['error_count'] ?? 0;
$allItems = $crawlResult['items'] ?? [];
$error = $crawlResult['error'] ?? null;

// Pagination support
require_once __DIR__ . '/../../Config/CrawlConfig.php';
$itemsPerPage = CrawlConfig::getItemsPerPage();
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, $currentPage); // Ensure page is at least 1
$totalPages = max(1, (int)ceil(count($allItems) / $itemsPerPage));
$currentPage = min($currentPage, $totalPages); // Ensure page doesn't exceed total pages

$offset = ($currentPage - 1) * $itemsPerPage;
$items = array_slice($allItems, $offset, $itemsPerPage);

// Available roles for selector - use passed value or fallback
$availableRoles = $availableRoles ?? ['SUPERADMIN', 'ADMIN', 'OPERATOR', 'SITE_MANAGER', 'FINANCE', 'SUPPORT'];
?>

<?php
// CRITICAL: Prevent infinite loops or recursive includes
if (defined('CRAWL_RESULTS_RENDERED')) {
    // Don't die() - just return empty to prevent infinite loop
    return;
}
define('CRAWL_RESULTS_RENDERED', true);

// CRITICAL: Validate required data - provide default values if missing
if (empty($crawlResult) || !is_array($crawlResult)) {
    $crawlResult = [
        'base_url' => '/app',
        'username' => $crawlUsername ?? 'unknown',
        'total_count' => 0,
        'success_count' => 0,
        'error_count' => 0,
        'items' => [],
        'error' => 'Crawl sonuçları bulunamadı.'
    ];
}
?>
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Crawl Test Sonuçları</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Rol: <span class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($crawlRole) ?></span>
                    | Kullanıcı: <span class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($crawlUsername) ?></span>
                    | Tarih: <?= date('Y-m-d H:i:s') ?>
                </p>
            </div>
            <div>
                <a href="<?= base_url('/sysadmin/crawl') ?>" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Yeni Test Başlat
                </a>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
            <p class="text-red-800 dark:text-red-200 font-semibold">Hata:</p>
            <p class="text-red-700 dark:text-red-300"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Özet</h2>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Test Rolü</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($crawlRole) ?></p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Toplam URL</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white"><?= $totalCount ?></p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Başarılı</p>
                <p class="text-lg font-semibold text-green-700 dark:text-green-300"><?= $successCount ?></p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Hatalı</p>
                <p class="text-lg font-semibold text-red-700 dark:text-red-300"><?= $errorCount ?></p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Başarı Oranı</p>
                <p class="text-lg font-semibold text-blue-700 dark:text-blue-300">
                    <?= $totalCount > 0 ? number_format(($successCount / $totalCount) * 100, 1) : 0 ?>%
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">URL Detayları</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                Sayfa <?= $currentPage ?> / <?= $totalPages ?> (Toplam: <?= count($allItems) ?>)
                </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">URL</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Marker</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Body Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Depth</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Not</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                Tarama sonucu bulunamadı.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <?php
                            $url = $item['url'] ?? '';
                            $status = $item['status'] ?? 0;
                            $hasError = $item['error_flag'] ?? $item['has_error'] ?? false;
                            $hasMarker = $item['has_marker'] ?? false;
                            $bodyLength = $item['body_length'] ?? 0;
                            $depth = $item['depth'] ?? 0;
                            $note = $item['note'] ?? '';
                            
                            // Status color
                            $statusClass = 'text-gray-700 dark:text-gray-300';
                            if ($status >= 500) {
                                $statusClass = 'text-red-700 dark:text-red-300 font-semibold';
                            } elseif ($status === 403) {
                                $statusClass = 'text-orange-700 dark:text-orange-300';
                            } elseif ($status === 404) {
                                $statusClass = 'text-yellow-700 dark:text-yellow-300';
                            } elseif ($status === 200) {
                                $statusClass = 'text-green-700 dark:text-green-300';
                            }
                            
                            // Row background
                            $rowClass = $hasError ? 'bg-red-50 dark:bg-red-900/10' : '';
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <code class="text-xs"><?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?></code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm <?= $statusClass ?>">
                                    <?= $status ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    <?php if ($hasMarker): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300">
                                            GLOBAL_R50_MARKER_1
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400 dark:text-gray-500">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    <?= number_format($bodyLength) ?> bytes
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                        <?= $depth ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    <?php if ($note): ?>
                                        <span class="text-orange-600 dark:text-orange-400"><?= htmlspecialchars($note) ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-400 dark:text-gray-500">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex justify-center items-center gap-2">
            <?php
            $baseUrl = base_url('/sysadmin/crawl');
            $roleParam = '?role=' . urlencode($crawlRole);
            ?>
            <?php if ($currentPage > 1): ?>
                <a href="<?= $baseUrl . $roleParam . '&page=' . ($currentPage - 1) ?>" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                    <i class="fas fa-chevron-left mr-1"></i> Önceki
                </a>
            <?php else: ?>
                <span class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 rounded-lg cursor-not-allowed">
                    <i class="fas fa-chevron-left mr-1"></i> Önceki
                </span>
            <?php endif; ?>
            
            <span class="px-4 py-2 text-gray-700 dark:text-gray-300">
                Sayfa <?= $currentPage ?> / <?= $totalPages ?>
            </span>
            
            <?php if ($currentPage < $totalPages): ?>
                <a href="<?= $baseUrl . $roleParam . '&page=' . ($currentPage + 1) ?>" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                    Sonraki <i class="fas fa-chevron-right ml-1"></i>
                </a>
            <?php else: ?>
                <span class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 rounded-lg cursor-not-allowed">
                    Sonraki <i class="fas fa-chevron-right ml-1"></i>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="mt-6 flex justify-end">
        <a href="<?= base_url('/app/') ?>" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>
            Dashboard'a Dön
        </a>
    </div>
</div>

