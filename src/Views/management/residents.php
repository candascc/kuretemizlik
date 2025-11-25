<?php
$portalStats = $portalStats ?? [];
$recentPortalLogins = $recentPortalLogins ?? [];
$pendingVerifications = $pendingVerifications ?? [];
$requestStats = $requestStats ?? [];
$recentRequests = $recentRequests ?? [];
$residents = $residents ?? [];
$filters = $filters ?? ['building_id' => null, 'search' => ''];
$pagination = $pagination ?? ['page' => 1, 'pages' => 1, 'per_page' => 25, 'total' => count($residents)];
$buildings = $buildings ?? [];
$alerts = $alerts ?? [];
$notificationPreferenceStats = $notificationPreferenceStats ?? [];

$formatCount = static fn($value) => number_format((int)($value ?? 0));
$formatDate = static fn($datetime) => $datetime ? Utils::formatDateTime($datetime) : '—';
$formatInitials = static function (?string $name): string {
    if (!$name) {
        return 'SN';
    }
    $parts = preg_split('/\s+/u', trim($name));
    $first = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1));
    $second = mb_strtoupper(mb_substr($parts[1] ?? '', 0, 1));
    if (!$second && isset($parts[0]) && mb_strlen($parts[0]) > 1) {
        $second = mb_strtoupper(mb_substr($parts[0], 1, 1));
    }
    return trim($first . $second) ?: mb_strtoupper(mb_substr($parts[0], 0, 2));
};
?>

<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-6 space-y-6">
    <div class="dashboard-hero space-y-5" id="portal-actions">
        <div class="dashboard-hero__badges">
            <span class="badge-chip"><i class="fas fa-user-shield"></i> Sakin Portalı</span>
            <span class="badge-chip"><i class="fas fa-coins"></i> Şeffaf Muhasebe</span>
            <span class="badge-chip"><i class="fas fa-mobile-alt"></i> Mobil Yönetim</span>
        </div>
        <div class="dashboard-hero__context">
            <h1 class="fluid-h1 font-semibold text-slate-900 dark:text-white">Sakin Yönetimi &amp; Portal</h1>
            <p class="text-sm md:text-base text-slate-600 dark:text-slate-300 max-w-3xl">
                Portal erişimleri, sakin deneyimi ve talepler tek panelde. Aidat, duyuru ve bakım süreçlerini mobil üzerinden anında başlatın.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 md:gap-3">
            <a href="<?= base_url('/portal/login') ?>" class="quick-action-btn variant-management text-xs sm:text-sm px-3 sm:px-4 py-2 sm:py-2.5" target="_blank" rel="noopener">
                <i class="fas fa-door-open"></i>
                <span class="hidden sm:inline">Portal Girişini Aç</span>
                <span class="sm:hidden">Portal</span>
            </a>
            <a href="<?= base_url('/management-fees') ?>" class="quick-action-btn variant-management text-xs sm:text-sm px-3 sm:px-4 py-2 sm:py-2.5">
                <i class="fas fa-money-bill-wave"></i>
                <span class="hidden sm:inline">Aidat Gönder</span>
                <span class="sm:hidden">Aidat</span>
            </a>
            <a href="<?= base_url('/announcements/create') ?>" class="quick-action-btn variant-management text-xs sm:text-sm px-3 sm:px-4 py-2 sm:py-2.5">
                <i class="fas fa-bullhorn"></i>
                <span class="hidden sm:inline">Duyuru Gönder</span>
                <span class="sm:hidden">Duyuru</span>
            </a>
            <a href="#resident-requests" class="quick-action-btn variant-management text-xs sm:text-sm px-3 sm:px-4 py-2 sm:py-2.5">
                <i class="fas fa-comments"></i>
                <span class="hidden sm:inline">Talepleri Yönet</span>
                <span class="sm:hidden">Talepler</span>
            </a>
            <a href="#pending-verifications" class="quick-action-btn variant-management text-xs sm:text-sm px-3 sm:px-4 py-2 sm:py-2.5">
                <i class="fas fa-envelope-open-text"></i>
                <span class="hidden sm:inline">Davet Gönder</span>
                <span class="sm:hidden">Davet</span>
            </a>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="stat-chip"><i class="fas fa-users"></i> <?= $formatCount($portalStats['total'] ?? 0) ?> toplam sakin</span>
            <span class="stat-chip"><i class="fas fa-check-circle"></i> <?= $formatCount($portalStats['verified'] ?? 0) ?> doğrulanmış</span>
            <span class="stat-chip pill-indicator--warning"><i class="fas fa-bell"></i> <?= $formatCount($requestStats['open'] ?? 0) ?> açık talep</span>
        </div>
    </div>

    <?php if (!empty($alerts)): ?>
    <div class="rounded-2xl border border-amber-200 dark:border-amber-600 bg-amber-50/90 dark:bg-amber-900/40 px-4 py-3 text-sm text-amber-800 dark:text-amber-100 shadow-soft">
        <ul class="list-disc list-inside space-y-1">
            <?php foreach ($alerts as $alert): ?>
                <?php
                    $message = is_array($alert) ? ($alert['message'] ?? '') : $alert;
                    $reference = is_array($alert) ? ($alert['reference'] ?? null) : null;
                ?>
                <li>
                    <?= e($message) ?>
                    <?php if ($reference): ?>
                        <span class="ml-2 inline-flex items-center gap-1 text-xs font-semibold text-amber-900/80 dark:text-amber-100/80">
                            <i class="fas fa-hashtag"></i> Ref: <?= e($reference) ?>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="get" class="grid gap-4 grid-cols-1 md:grid-cols-[2fr,2fr,auto] items-end bg-white/80 dark:bg-slate-900/70 border border-white/30 dark:border-slate-800 rounded-2xl px-3 sm:px-4 py-4 backdrop-blur shadow-soft">
        <div>
            <label for="resident-search" class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Arama</label>
            <input
                id="resident-search"
                name="search"
                type="search"
                value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/60 px-3 py-2.5 text-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                placeholder="İsim, email veya daire numarası..."
            >
        </div>
        <div>
            <label for="resident-building" class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Bina</label>
            <select
                id="resident-building"
                name="building_id"
                class="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/60 px-3 py-2.5 text-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-400"
            >
                <option value="">Tümü</option>
                <?php foreach ($buildings as $building): ?>
                    <option value="<?= (int)($building['id'] ?? 0) ?>"<?= ((int)($filters['building_id'] ?? 0) === (int)($building['id'] ?? 0)) ? ' selected' : '' ?>>
                        <?= htmlspecialchars($building['name'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full md:w-auto">
            <button type="submit" class="quick-action-btn variant-management px-4 py-2.5 w-full sm:w-auto justify-center">
                <i class="fas fa-filter"></i>
                <span>Filtrele</span>
            </button>
            <a href="<?= base_url('/management/residents') ?>" class="quick-action-btn quick-action-btn--reset px-4 py-2.5 w-full sm:w-auto justify-center text-center" style="background: linear-gradient(135deg, rgba(100, 116, 139, 0.5), rgba(148, 163, 184, 0.45)) !important; border: 1.5px solid rgba(100, 116, 139, 0.7) !important; color: #1e293b !important; box-shadow: 0 2px 4px rgba(100, 116, 139, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.7) !important; font-weight: 500 !important;">
                <i class="fas fa-rotate-left"></i>
                <span>Sıfırla</span>
            </a>
        </div>
    </form>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-2 xl:grid-cols-4" id="portal-overview">
        <div class="kpi-card">
            <div class="kpi-label text-emerald-500">Toplam Sakin</div>
            <div class="fluid-kpi font-bold text-slate-900 dark:text-white"><?= $formatCount($portalStats['total'] ?? 0) ?></div>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Aktif: <?= $formatCount($portalStats['active'] ?? 0) ?> · Pasif: <?= $formatCount($portalStats['inactive'] ?? 0) ?>
            </p>
        </div>
        <div class="kpi-card">
            <div class="kpi-label text-sky-500">Portal Kullanımı</div>
            <div class="fluid-kpi font-bold text-sky-600 dark:text-sky-300"><?= $formatCount($portalStats['logged_in'] ?? 0) ?></div>
            <p class="text-sm text-slate-500 dark:text-slate-400">Son giriş yapan sakin sayısı</p>
        </div>
        <div class="kpi-card">
            <div class="kpi-label text-indigo-500">Email Doğrulaması</div>
            <div class="fluid-kpi font-bold text-indigo-600 dark:text-indigo-300"><?= $formatCount($portalStats['verified'] ?? 0) ?></div>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Doğrulanmış · Bekleyen: <?= $formatCount($portalStats['unverified'] ?? 0) ?>
            </p>
        </div>
        <div class="kpi-card">
            <div class="kpi-label text-rose-500">Talepler</div>
            <div class="fluid-kpi font-bold text-rose-600 dark:text-rose-300"><?= $formatCount($requestStats['open'] ?? 0) ?></div>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Toplam: <?= $formatCount($requestStats['total'] ?? 0) ?> · Çözümde: <?= $formatCount($requestStats['in_progress'] ?? 0) ?>
            </p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section id="pending-verifications" class="rounded-3xl bg-white/90 dark:bg-slate-900/80 backdrop-blur border border-white/20 dark:border-slate-800 shadow-soft overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0 border-b border-white/20 dark:border-slate-800 px-4 sm:px-5 py-3 sm:py-4">
                <div>
                    <h2 class="text-xs sm:text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Son Portal Girişleri</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">En son giriş yapan 8 sakin</p>
                </div>
                <a href="<?= base_url('/resident/dashboard') ?>" class="section-header-link self-start sm:self-auto">Sakin Paneline Git</a>
            </div>
            <?php if (!empty($recentPortalLogins)): ?>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 hidden md:block">
                <?php foreach ($recentPortalLogins as $resident): ?>
                <div class="px-5 py-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($resident['name'] ?? 'Sakin') ?></div>
                        <div class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($resident['email'] ?? '') ?></div>
                        <div class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                            <?= htmlspecialchars(($resident['building_name'] ?? '—') . ' · Daire ' . ($resident['unit_number'] ?? '—')) ?>
                        </div>
                    </div>
                    <div class="text-xs text-right text-slate-500 dark:text-slate-400">
                        Son giriş:<br><span class="font-semibold text-slate-600 dark:text-slate-300"><?= $formatDate($resident['last_login_at'] ?? null) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mobile-card-grid md:hidden">
                <?php foreach ($recentPortalLogins as $resident): ?>
                <?php
                    $initials = $formatInitials($resident['name'] ?? null);
                    $lastLogin = $formatDate($resident['last_login_at'] ?? null);
                ?>
                <div class="mobile-card mobile-card--sky">
                    <div class="mobile-card__header">
                        <div class="mobile-card__avatar"><?= e($initials) ?></div>
                        <div class="mobile-card__headline">
                            <span class="mobile-card__name"><?= htmlspecialchars($resident['name'] ?? 'Sakin') ?></span>
                            <span class="mobile-card__subtitle"><i class="fas fa-at"></i><?= htmlspecialchars($resident['email'] ?? '') ?></span>
                        </div>
                    </div>
                    <div class="mobile-card__timestamp-wrapper">
                        <span class="pill-indicator pill-indicator--success mobile-card__timestamp"><i class="fas fa-arrow-right-to-bracket"></i> <?= $lastLogin ?></span>
                    </div>
                    <div class="mobile-card__tags">
                        <span class="mobile-card__tag"><i class="fas fa-building"></i><?= htmlspecialchars(($resident['building_name'] ?? '—') . ' · Daire ' . ($resident['unit_number'] ?? '—')) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="px-5 py-6">
                <?= View::partial('partials/empty-state', [
                    'title' => 'Portal giriş kaydı yok',
                    'description' => 'Henüz portala giriş yapan sakin kaydı bulunmuyor.',
                    'icon' => 'fa-solid fa-circle-user'
                ]) ?>
            </div>
            <?php endif; ?>
        </section>

        <section class="rounded-3xl bg-white/90 dark:bg-slate-900/80 backdrop-blur border border-white/20 dark:border-slate-800 shadow-soft overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0 border-b border-white/20 dark:border-slate-800 px-4 sm:px-5 py-3 sm:py-4">
                <div>
                    <h2 class="text-xs sm:text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Email Doğrulaması Beklenenler</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">En son davet gönderilen sakinler</p>
                </div>
                <button class="text-xs text-indigo-500 hover:text-indigo-600 self-start sm:self-auto whitespace-nowrap" type="button" onclick="window.dispatchEvent(new CustomEvent('residents:resend-invite'))">
                    Daveti Yeniden Gönder
                </button>
            </div>
        <?php if (!empty($pendingVerifications)): ?>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 hidden md:block">
                <?php foreach ($pendingVerifications as $resident): ?>
                <div class="px-5 py-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($resident['name'] ?? 'Sakin') ?></div>
                        <div class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($resident['email'] ?? '') ?></div>
                        <div class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                            Davet gönderildi: <?= $formatDate($resident['created_at'] ?? null) ?>
                        </div>
                    </div>
                    <div class="text-xs text-right text-slate-500 dark:text-slate-400">
                        <?= htmlspecialchars(($resident['building_name'] ?? '—') . ' · Daire ' . ($resident['unit_number'] ?? '—')) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mobile-card-grid md:hidden">
                <?php foreach ($pendingVerifications as $resident): ?>
                <?php
                    $initials = $formatInitials($resident['name'] ?? null);
                    $invitedAt = $formatDate($resident['created_at'] ?? null);
                ?>
                <div class="mobile-card mobile-card--amber">
                    <div class="mobile-card__header">
                        <div class="mobile-card__avatar"><?= e($initials) ?></div>
                        <div class="mobile-card__headline">
                            <span class="mobile-card__name"><?= htmlspecialchars($resident['name'] ?? 'Sakin') ?></span>
                            <span class="mobile-card__subtitle"><i class="fas fa-at"></i><?= htmlspecialchars($resident['email'] ?? '') ?></span>
                        </div>
                    </div>
                    <div class="mobile-card__timestamp-wrapper">
                        <span class="pill-indicator pill-indicator--success mobile-card__timestamp"><i class="fas fa-envelope"></i> <?= $invitedAt ?></span>
                    </div>
                    <div class="mobile-card__tags">
                        <span class="mobile-card__tag"><i class="fas fa-building"></i><?= htmlspecialchars(($resident['building_name'] ?? '—') . ' · Daire ' . ($resident['unit_number'] ?? '—')) ?></span>
                    </div>
                    <div class="mobile-card__footer">
                        <button type="button" onclick="window.dispatchEvent(new CustomEvent('residents:resend-invite'))">Daveti Yeniden Gönder</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
        <div class="px-5 py-6">
            <?= View::partial('partials/empty-state', [
                'title' => 'Doğrulama bekleyen davet yok',
                'description' => 'Tüm davetli sakinler e-postalarını doğrulamış görünüyor.',
                'icon' => 'fa-solid fa-badge-check'
            ]) ?>
        </div>
        <?php endif; ?>
        </section>
    </div>

    <?php if (!empty($notificationPreferenceStats)): ?>
    <section class="rounded-3xl bg-white/90 dark:bg-slate-900/80 backdrop-blur border border-white/20 dark:border-slate-800 shadow-soft">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0 border-b border-white/20 dark:border-slate-800 px-4 sm:px-5 py-3 sm:py-4">
            <div>
                <h2 class="text-xs sm:text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Bildirim Tercihi Dağılımı</h2>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 hidden sm:block">Sakinlerin e-posta ve SMS bildirim tercihleri kategori bazında</p>
            </div>
            <span class="text-xs text-slate-500 dark:text-slate-400 self-start sm:self-auto"><?= $formatCount($notificationPreferenceStats[array_key_first($notificationPreferenceStats)]['total_residents'] ?? 0) ?> aktif sakin</span>
        </div>
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <div class="inline-block min-w-full align-middle px-4 sm:px-0">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-xs sm:text-sm">
                    <thead class="bg-slate-50/80 dark:bg-slate-900/60 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="px-3 sm:px-5 py-2 sm:py-3 text-left font-semibold whitespace-nowrap">Kategori</th>
                            <th class="px-3 sm:px-5 py-2 sm:py-3 text-left font-semibold whitespace-nowrap">E-posta</th>
                            <th class="px-3 sm:px-5 py-2 sm:py-3 text-left font-semibold whitespace-nowrap">SMS</th>
                            <th class="px-3 sm:px-5 py-2 sm:py-3 text-left font-semibold whitespace-nowrap hidden sm:table-cell">Not</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        <?php foreach ($notificationPreferenceStats as $key => $stat): ?>
                            <?php
                                $emailEnabled = $formatCount($stat['email_enabled'] ?? 0);
                                $emailEligible = $formatCount($stat['email_eligible'] ?? 0);
                                $smsEnabled = $formatCount($stat['sms_enabled'] ?? 0);
                                $smsEligible = $formatCount($stat['sms_eligible'] ?? 0);
                                $supportsSms = !empty($stat['supports_sms']);
                            ?>
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-900/50 transition-colors">
                                <td class="px-3 sm:px-5 py-2 sm:py-3">
                                    <div class="font-semibold text-slate-900 dark:text-white text-xs sm:text-sm"><?= htmlspecialchars($stat['label'] ?? ucfirst($key)) ?></div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 hidden sm:block"><?= htmlspecialchars($stat['description'] ?? '') ?></div>
                                </td>
                                <td class="px-3 sm:px-5 py-2 sm:py-3">
                                    <span class="inline-flex items-center gap-1 sm:gap-2 px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full bg-emerald-100/70 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-200 text-xs font-semibold whitespace-nowrap">
                                        <i class="fas fa-envelope text-[10px] sm:text-xs"></i>
                                        <span><?= $emailEnabled ?> / <?= $emailEligible ?></span>
                                    </span>
                                </td>
                                <td class="px-3 sm:px-5 py-2 sm:py-3">
                                    <?php if ($supportsSms): ?>
                                        <span class="inline-flex items-center gap-1 sm:gap-2 px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full bg-sky-100/70 dark:bg-sky-500/20 text-sky-700 dark:text-sky-200 text-xs font-semibold whitespace-nowrap">
                                            <i class="fas fa-sms text-[10px] sm:text-xs"></i>
                                            <span><?= $smsEnabled ?> / <?= $smsEligible ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 dark:text-slate-500">SMS yok</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 sm:px-5 py-2 sm:py-3 text-xs text-slate-500 dark:text-slate-400 hidden sm:table-cell">
                                    <?php if (!empty($stat['explicit_email_off'])): ?>
                                        <?= $formatCount($stat['explicit_email_off']) ?> sakin e-postayı kapattı
                                    <?php else: ?>
                                        — 
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="rounded-3xl bg-white/90 dark:bg-slate-900/80 backdrop-blur border border-white/20 dark:border-slate-800 shadow-soft" id="resident-requests">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0 border-b border-white/20 dark:border-slate-800 px-4 sm:px-5 py-3 sm:py-4">
            <h2 class="text-xs sm:text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Sakin Talepleri</h2>
            <a href="<?= base_url('/resident/requests') ?>" class="section-header-link self-start sm:self-auto">Tüm Talepleri Gör</a>
        </div>
        <?php if (!empty($recentRequests)): ?>
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                <thead class="bg-slate-50/80 dark:bg-slate-900/60 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Sakin / Talep</th>
                        <th class="px-5 py-3 text-left font-semibold">Tür / Öncelik</th>
                        <th class="px-5 py-3 text-left font-semibold">Durum</th>
                        <th class="px-5 py-3 text-right font-semibold">Oluşturulma</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                    <?php foreach ($recentRequests as $request): ?>
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-900/50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($request['subject'] ?? 'Talep') ?></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                <?= htmlspecialchars(($request['resident_name'] ?? 'Anonim') . ' · ' . ($request['building_name'] ?? '—') . ' · Daire ' . ($request['unit_number'] ?? '—')) ?>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                            <?= htmlspecialchars(ucfirst($request['request_type'] ?? '-')) ?>
                            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-<?= htmlspecialchars($request['priority'] === 'urgent' ? 'rose' : ($request['priority'] === 'high' ? 'amber' : 'slate')) ?>-100/70 text-<?= htmlspecialchars($request['priority'] === 'urgent' ? 'rose' : ($request['priority'] === 'high' ? 'amber' : 'slate')) ?>-600 ml-2">
                                <i class="fas fa-exclamation-circle"></i>
                                <?= htmlspecialchars(ucfirst($request['priority'] ?? 'normal')) ?>
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $request['status'] ?? 'open'))) ?>
                        </td>
                        <td class="px-5 py-3 text-right text-slate-600 dark:text-slate-300">
                            <?= $formatDate($request['created_at'] ?? null) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mobile-card-stack md:hidden">
            <?php foreach ($recentRequests as $request): ?>
            <?php
                $priority = $request['priority'] ?? 'normal';
                $accentClass = 'mobile-card--emerald';
                if ($priority === 'urgent') {
                    $accentClass = 'mobile-card--rose';
                } elseif ($priority === 'high') {
                    $accentClass = 'mobile-card--amber';
                }
                $initials = $formatInitials($request['resident_name'] ?? null);
                $statusLabel = htmlspecialchars(ucwords(str_replace('_', ' ', $request['status'] ?? 'open')));
                $priorityLabel = htmlspecialchars(ucfirst($priority));
                $createdAt = $formatDate($request['created_at'] ?? null);
                $residentName = htmlspecialchars($request['resident_name'] ?? 'Anonim');
                $buildingLabel = htmlspecialchars(($request['building_name'] ?? '—') . ' · Daire ' . ($request['unit_number'] ?? '—'));
            ?>
            <div class="mobile-card <?= $accentClass ?>">
                <div class="mobile-card__header">
                    <div class="mobile-card__avatar"><?= e($initials) ?></div>
                    <div class="mobile-card__headline">
                        <span class="mobile-card__name"><?= htmlspecialchars($request['subject'] ?? 'Talep') ?></span>
                        <span class="mobile-card__subtitle"><i class="fas fa-user"></i><?= $residentName ?></span>
                    </div>
                </div>
                <div class="mobile-card__timestamp-wrapper">
                    <span class="pill-indicator mobile-card__timestamp<?= $priority === 'urgent' ? ' pill-indicator--warning' : '' ?>">
                        <i class="fas fa-exclamation-circle"></i> <?= $priorityLabel ?>
                    </span>
                </div>
                <div class="mobile-card__tags">
                    <span class="mobile-card__tag"><i class="fas fa-building"></i><?= $buildingLabel ?></span>
                    <span class="mobile-card__tag"><i class="fas fa-layer-group"></i><?= $statusLabel ?></span>
                    <span class="mobile-card__tag"><i class="fas fa-clock"></i><?= $createdAt ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="px-5 py-6">
            <?= View::partial('partials/empty-state', [
                'title' => 'Bekleyen sakin talebi yok',
                'description' => 'Yeni bir talep oluşturulduğunda burada listelenecek.',
                'icon' => 'fa-solid fa-circle-info'
            ]) ?>
        </div>
        <?php endif; ?>
    </section>

    <section class="rounded-3xl bg-white/90 dark:bg-slate-900/80 backdrop-blur border border-white/20 dark:border-slate-800 shadow-soft">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-0 border-b border-white/20 dark:border-slate-800 px-4 sm:px-5 py-3 sm:py-4">
            <div>
                <h2 class="text-xs sm:text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Sakin Listesi (İlk 25)</h2>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 hidden sm:block">Detaylı liste için sakin panelini kullanabilirsiniz.</p>
            </div>
            <a href="<?= base_url('/resident/dashboard') ?>" class="section-header-link self-start sm:self-auto">
                Sakin Paneline Git
            </a>
        </div>
        <?php if (!empty($residents)): ?>
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                <thead class="bg-slate-50/80 dark:bg-slate-900/60 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Sakin</th>
                        <th class="px-5 py-3 text-left font-semibold">Portal Durumu</th>
                        <th class="px-5 py-3 text-left font-semibold">İletişim</th>
                        <th class="px-5 py-3 text-right font-semibold">Son Aktivite</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                    <?php foreach ($residents as $resident): ?>
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-900/50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($resident['name'] ?? 'Sakin') ?></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                <?= htmlspecialchars(($resident['building_name'] ?? '—') . ' · Daire ' . ($resident['unit_number'] ?? '—')) ?>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full <?= ($resident['is_active'] ?? 0) ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' ?>">
                                    <i class="fas <?= ($resident['is_active'] ?? 0) ? 'fa-check-circle' : 'fa-ban' ?>"></i>
                                    <?= ($resident['is_active'] ?? 0) ? 'Aktif' : 'Pasif' ?>
                                </span>
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full <?= ($resident['email_verified'] ?? 0) ? 'bg-sky-100 text-sky-600' : 'bg-amber-100 text-amber-600' ?>">
                                    <i class="fas <?= ($resident['email_verified'] ?? 0) ? 'fa-badge-check' : 'fa-hourglass-half' ?>"></i>
                                    <?= ($resident['email_verified'] ?? 0) ? 'Doğrulandı' : 'Doğrulama Bekliyor' ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                            <div><?= htmlspecialchars($resident['email'] ?? '—') ?></div>
                            <div class="text-xs text-slate-400 dark:text-slate-500"><?= htmlspecialchars($resident['phone'] ?? 'Telefon belirtilmemiş') ?></div>
                        </td>
                        <td class="px-5 py-3 text-right text-slate-600 dark:text-slate-300">
                            <?= $formatDate($resident['last_login_at'] ?? null) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mobile-card-stack md:hidden">
            <?php foreach ($residents as $resident): ?>
            <?php
                $initials = $formatInitials($resident['name'] ?? null);
                $isActive = (bool)($resident['is_active'] ?? 0);
                $accentClass = $isActive ? 'mobile-card--emerald' : 'mobile-card--rose';
                $statusIcon = $isActive ? 'fa-check' : 'fa-ban';
                $statusLabel = $isActive ? 'Aktif' : 'Pasif';
                $verificationLabel = ($resident['email_verified'] ?? 0) ? 'Email doğrulandı' : 'Doğrulama bekliyor';
                $verificationIcon = ($resident['email_verified'] ?? 0) ? 'fa-badge-check' : 'fa-hourglass-half';
                $buildingLabel = htmlspecialchars(($resident['building_name'] ?? '—') . ' · Daire ' . ($resident['unit_number'] ?? '—'));
                $email = htmlspecialchars($resident['email'] ?? '—');
                $phone = htmlspecialchars($resident['phone'] ?? 'Telefon belirtilmemiş');
                $lastLogin = $formatDate($resident['last_login_at'] ?? null);
            ?>
            <div class="mobile-card <?= $accentClass ?>">
                <div class="mobile-card__header">
                    <div class="mobile-card__avatar"><?= e($initials) ?></div>
                    <div class="mobile-card__headline">
                        <span class="mobile-card__name"><?= htmlspecialchars($resident['name'] ?? 'Sakin') ?></span>
                        <span class="mobile-card__subtitle"><i class="fas fa-building"></i><?= $buildingLabel ?></span>
                    </div>
                </div>
                <div class="mobile-card__timestamp-wrapper">
                    <span class="pill-indicator mobile-card__timestamp<?= $isActive ? '' : ' pill-indicator--warning' ?>">
                        <i class="fas <?= $statusIcon ?>"></i> <?= $statusLabel ?>
                    </span>
                </div>
                <div class="mobile-card__tags">
                    <span class="mobile-card__tag"><i class="fas fa-envelope"></i><?= $email ?></span>
                    <span class="mobile-card__tag"><i class="fas fa-phone"></i><?= $phone ?></span>
                    <span class="mobile-card__tag"><i class="fas <?= $verificationIcon ?>"></i><?= $verificationLabel ?></span>
                    <span class="mobile-card__tag"><i class="fas fa-clock"></i><?= $lastLogin ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="px-5 py-6">
            <?= View::partial('partials/empty-state', [
                'title' => 'Tanımlı sakin bulunmuyor',
                'description' => 'Yeni sakin oluşturduğunuzda burada listelenecek.',
                'icon' => 'fa-solid fa-people-roof'
            ]) ?>
        </div>
        <?php endif; ?>
    </section>

    <?php if (($pagination['pages'] ?? 1) > 1): ?>
    <nav class="flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-0 border border-white/30 dark:border-slate-800 rounded-2xl bg-white/70 dark:bg-slate-900/60 backdrop-blur px-3 sm:px-4 py-3 text-xs sm:text-sm text-slate-600 dark:text-slate-300 shadow-soft">
        <div class="text-center sm:text-left">
            <span class="block sm:inline">Sayfa <?= (int)($pagination['page'] ?? 1) ?> / <?= (int)($pagination['pages'] ?? 1) ?></span>
            <span class="hidden sm:inline"> · </span>
            <span class="block sm:inline">Toplam <?= $formatCount($pagination['total'] ?? 0) ?> kayıt</span>
        </div>
        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap justify-center">
            <?php
            $currentPage = (int)($pagination['page'] ?? 1);
            $pages = (int)($pagination['pages'] ?? 1);
            $queryBase = $_GET;
            ?>
            <a
                class="quick-action-btn px-2.5 sm:px-3 py-1.5 sm:py-2 <?= $currentPage <= 1 ? 'opacity-50 pointer-events-none' : '' ?>"
                href="<?= $currentPage <= 1 ? '#' : htmlspecialchars(base_url('/management/residents') . '?' . http_build_query(array_merge($queryBase, ['page' => $currentPage - 1]))) ?>"
                aria-label="Önceki sayfa"
            >
                <i class="fas fa-chevron-left text-xs sm:text-sm"></i>
            </a>
            <?php for ($i = max(1, $currentPage - 2); $i <= min($pages, $currentPage + 2); $i++): ?>
                <?php
                    $link = htmlspecialchars(base_url('/management/residents') . '?' . http_build_query(array_merge($queryBase, ['page' => $i])));
                ?>
                <a
                    class="quick-action-btn px-2.5 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm min-w-[2rem] sm:min-w-[2.5rem] justify-center <?= $i === $currentPage ? 'variant-management' : '' ?>"
                    href="<?= $link ?>"
                ><?= $i ?></a>
            <?php endfor; ?>
            <a
                class="quick-action-btn px-2.5 sm:px-3 py-1.5 sm:py-2 <?= $currentPage >= $pages ? 'opacity-50 pointer-events-none' : '' ?>"
                href="<?= $currentPage >= $pages ? '#' : htmlspecialchars(base_url('/management/residents') . '?' . http_build_query(array_merge($queryBase, ['page' => $currentPage + 1]))) ?>"
                aria-label="Sonraki sayfa"
            >
                <i class="fas fa-chevron-right text-xs sm:text-sm"></i>
            </a>
        </div>
    </nav>
    <?php endif; ?>
</div>

