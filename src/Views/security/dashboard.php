<?php
$title = $title ?? 'Security Dashboard';
$stats = $stats ?? [];
$companies = $companies ?? [];
$selected_company_id = $selected_company_id ?? null;
$date_from = $date_from ?? date('Y-m-d H:i:s', strtotime('-24 hours'));
$date_to = $date_to ?? date('Y-m-d H:i:s');
$user_role = $user_role ?? 'ADMIN';
$flash = Utils::getFlash();
?>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-shield-alt"></i>
                Security Dashboard
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Güvenlik olayları ve istatistikleri
            </p>
        </div>
        
        <!-- Flash Messages -->
        <?php if (!empty($flash['error'])): ?>
            <div class="mb-6 rounded-md bg-red-50 dark:bg-red-900/20 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">
                            <?= htmlspecialchars($flash['error']) ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <form method="GET" action="<?= base_url('/security/dashboard') ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <?php if ($user_role === 'SUPERADMIN' && !empty($companies)): ?>
                    <div>
                        <label for="company_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Şirket
                        </label>
                        <select 
                            name="company_id" 
                            id="company_id"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Tüm Şirketler</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?= (int)($company['id'] ?? 0) ?>" <?= ($selected_company_id == $company['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($company['name'] ?? 'N/A') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Başlangıç Tarihi
                    </label>
                    <input 
                        type="datetime-local" 
                        name="date_from" 
                        id="date_from"
                        value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($date_from))) ?>"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Bitiş Tarihi
                    </label>
                    <input 
                        type="datetime-local" 
                        name="date_to" 
                        id="date_to"
                        value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($date_to))) ?>"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                
                <div class="flex items-end">
                    <button 
                        type="submit" 
                        class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg">
                        <i class="fas fa-filter mr-2"></i>
                        Filtrele
                    </button>
                </div>
            </form>
        </div>
        
        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <!-- Failed Logins (24h) -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Başarısız Girişler (24s)</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            <?= number_format($stats['failed_logins_24h'] ?? 0) ?>
                        </p>
                    </div>
                    <div class="h-12 w-12 bg-red-100 dark:bg-red-900/50 rounded-full flex items-center justify-center">
                        <i class="fas fa-times-circle text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    7 gün: <?= number_format($stats['failed_logins_7d'] ?? 0) ?>
                </p>
            </div>
            
            <!-- Rate Limit Exceeded (24h) -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Rate Limit Aşıldı (24s)</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            <?= number_format($stats['rate_limit_exceeded_24h'] ?? 0) ?>
                        </p>
                    </div>
                    <div class="h-12 w-12 bg-yellow-100 dark:bg-yellow-900/50 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    7 gün: <?= number_format($stats['rate_limit_exceeded_7d'] ?? 0) ?>
                </p>
            </div>
            
            <!-- Security Anomalies (24h) -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Güvenlik Anomalileri (24s)</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            <?= number_format($stats['security_anomalies_24h'] ?? 0) ?>
                        </p>
                    </div>
                    <div class="h-12 w-12 bg-orange-100 dark:bg-orange-900/50 rounded-full flex items-center justify-center">
                        <i class="fas fa-shield-alt text-orange-600 dark:text-orange-400 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    7 gün: <?= number_format($stats['security_anomalies_7d'] ?? 0) ?>
                </p>
            </div>
            
            <!-- MFA Events (24h) -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">MFA Olayları (24s)</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            <?= number_format($stats['mfa_events_24h']['total'] ?? 0) ?>
                        </p>
                    </div>
                    <div class="h-12 w-12 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                        <i class="fas fa-key text-blue-600 dark:text-blue-400 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Başarılı: <?= number_format($stats['mfa_events_24h']['challenge_passed'] ?? 0) ?> | 
                    Başarısız: <?= number_format($stats['mfa_events_24h']['challenge_failed'] ?? 0) ?>
                </p>
            </div>
            
            <!-- Active MFA Users -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Aktif MFA Kullanıcıları</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            <?= number_format($stats['active_mfa_users'] ?? 0) ?>
                        </p>
                    </div>
                    <div class="h-12 w-12 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-shield text-green-600 dark:text-green-400 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    MFA aktif kullanıcı sayısı
                </p>
            </div>
            
            <!-- MFA Breakdown (7d) -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">MFA Olayları (7g)</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            <?= number_format($stats['mfa_events_7d']['total'] ?? 0) ?>
                        </p>
                    </div>
                    <div class="h-12 w-12 bg-purple-100 dark:bg-purple-900/50 rounded-full flex items-center justify-center">
                        <i class="fas fa-chart-line text-purple-600 dark:text-purple-400 text-xl"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Etkinleştirildi: <?= number_format($stats['mfa_events_7d']['enabled'] ?? 0) ?> | 
                    Devre Dışı: <?= number_format($stats['mfa_events_7d']['disabled'] ?? 0) ?>
                </p>
            </div>
        </div>
        
        <!-- Recent Security Events Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Son Güvenlik Olayları
            </h2>
            
            <?php if (!empty($stats['recent_security_events'])): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Tarih
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Olay
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Kullanıcı
                                </th>
                                <?php if ($user_role === 'SUPERADMIN'): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Şirket
                                    </th>
                                <?php endif; ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    IP Adresi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($stats['recent_security_events'] as $event): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($event['created_at'] ?? 'N/A') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= getActionBadgeClass($event['action'] ?? '') ?>">
                                            <?= htmlspecialchars(formatAction($event['action'] ?? '')) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($event['username'] ?? 'N/A') ?>
                                        <?php if ($event['role']): ?>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                (<?= htmlspecialchars($event['role']) ?>)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($user_role === 'SUPERADMIN'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?= htmlspecialchars($event['company_name'] ?? 'N/A') ?>
                                        </td>
                                    <?php endif; ?>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono">
                                        <?= htmlspecialchars($event['ip_address'] ?? 'N/A') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                    Son 24 saatte güvenlik olayı bulunamadı.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Helper functions for view
function formatAction(string $action): string
{
    $mapping = [
        'LOGIN_FAILED' => 'Başarısız Giriş',
        'LOGIN_SUCCESS' => 'Başarılı Giriş',
        'LOGIN_RATE_LIMIT_EXCEEDED' => 'Rate Limit Aşıldı',
        'PORTAL_LOGIN_FAILED' => 'Portal Başarısız Giriş',
        'PORTAL_LOGIN_SUCCESS' => 'Portal Başarılı Giriş',
        'RESIDENT_LOGIN_FAILED' => 'Sakin Başarısız Giriş',
        'RESIDENT_LOGIN_SUCCESS' => 'Sakin Başarılı Giriş',
        'SECURITY_ANOMALY_DETECTED' => 'Güvenlik Anomalisi',
        'MFA_ENABLED' => 'MFA Etkinleştirildi',
        'MFA_DISABLED' => 'MFA Devre Dışı',
        'MFA_CHALLENGE_STARTED' => 'MFA Challenge Başlatıldı',
        'MFA_CHALLENGE_PASSED' => 'MFA Doğrulandı',
        'MFA_CHALLENGE_FAILED' => 'MFA Doğrulama Başarısız',
        'IP_BLOCKED' => 'IP Engellendi',
        'IP_ALLOWED' => 'IP İzin Verildi',
    ];
    
    return $mapping[$action] ?? $action;
}

function getActionBadgeClass(string $action): string
{
    if (strpos($action, 'FAILED') !== false || strpos($action, 'BLOCKED') !== false) {
        return 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200';
    }
    if (strpos($action, 'SUCCESS') !== false || strpos($action, 'PASSED') !== false || strpos($action, 'ENABLED') !== false) {
        return 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200';
    }
    if (strpos($action, 'ANOMALY') !== false || strpos($action, 'RATE_LIMIT') !== false) {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200';
    }
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
}
?>


