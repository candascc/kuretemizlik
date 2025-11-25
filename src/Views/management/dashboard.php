<?php
$summary = $summary ?? [];
$portalStats = $portalStats ?? [];
$pendingPortalInvites = $pendingPortalInvites ?? [];
$topOutstandingUnits = $topOutstandingUnits ?? [];
$upcomingMeetings = $upcomingMeetings ?? [];
$upcomingReservations = $upcomingReservations ?? [];
$recentAnnouncements = $recentAnnouncements ?? [];

$formatMoney = fn ($amount) => Utils::formatMoney((float)$amount);
$formatInitials = static function (?string $text, string $fallback = 'NA'): string {
    if (!$text) {
        return $fallback;
    }
    $parts = preg_split('/\s+/u', trim($text));
    $first = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1));
    $second = mb_strtoupper(mb_substr($parts[1] ?? '', 0, 1));
    if (!$second && isset($parts[0]) && mb_strlen($parts[0]) > 1) {
        $second = mb_strtoupper(mb_substr($parts[0], 1, 1));
    }
    $initials = trim($first . $second);
    return $initials !== '' ? $initials : mb_strtoupper(mb_substr($parts[0], 0, 2));
};
$formatDateTime = static fn (?string $date) => $date ? Utils::formatDateTime($date) : '—';

if (!function_exists('dashboard_sanitize_summary')) {
    function dashboard_sanitize_summary(array $summary, string $key, $default = 0)
    {
        return $summary[$key] ?? $default;
    }
}
?>

<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-6 space-y-6">
    <div class="dashboard-hero space-y-5">
        <div class="dashboard-hero__context">
            <div class="dashboard-hero__badges">
                <span class="badge-chip"><i class="fas fa-bolt"></i> Canlı Yönetim</span>
                <span class="badge-chip"><i class="fas fa-shield-alt"></i> Şeffaf Finans</span>
                <span class="badge-chip"><i class="fas fa-mobile-alt"></i> Mobil Odaklı</span>
            </div>
            <h1 class="fluid-h1 font-bold text-slate-900 dark:text-white tracking-tight">Yönetim &amp; Sakin Portalı Özeti</h1>
            <p class="text-sm md:text-base text-slate-600 dark:text-slate-400 max-w-3xl leading-relaxed">
                Finansal akışlar, sakin portalı etkileşimi ve operasyonel süreçler tek ekranda. Yönetim kurulu ve sakin iletişimini mobil cihazlardan anlık takip edin.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 md:gap-3">
            <a href="<?= base_url('/management-fees') ?>" class="quick-action-btn variant-management">
                <i class="fas fa-money-bill-wave"></i>
                <span>Aidat Yönetimi</span>
            </a>
            <a href="<?= base_url('/meetings/create') ?>" class="quick-action-btn variant-management">
                <i class="fas fa-calendar-plus"></i>
                <span>Toplantı Planla</span>
            </a>
            <a href="<?= base_url('/announcements') ?>" class="quick-action-btn variant-management">
                <i class="fas fa-bullhorn"></i>
                <span>Duyurular</span>
            </a>
            <a href="<?= base_url('/management/residents') ?>" class="quick-action-btn variant-management">
                <i class="fas fa-users"></i>
                <span>Sakin Portalı</span>
            </a>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <span class="stat-chip"><i class="fas fa-building text-xs"></i> <span class="font-semibold"><?= (int)dashboard_sanitize_summary($summary, 'buildings_active') ?></span> aktif bina</span>
            <span class="stat-chip"><i class="fas fa-home text-xs"></i> <span class="font-semibold">%<?= dashboard_sanitize_summary($summary, 'occupancy_rate') ?></span> doluluk</span>
            <span class="stat-chip pill-indicator--warning"><i class="fas fa-chart-line text-xs"></i> <span class="stat-money font-semibold"><?= number_format((float)dashboard_sanitize_summary($summary, 'fees_outstanding'), 2, ',', '.') ?><span class="stat-currency">₺</span></span> bekleyen</span>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="kpi-card kpi-card--indigo">
            <div class="kpi-label text-indigo-600 dark:text-indigo-400">Aktif Bina</div>
            <div class="kpi-value font-bold text-indigo-700 dark:text-indigo-300">
                <?= (int)dashboard_sanitize_summary($summary, 'buildings_active') ?>
            </div>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 sm:mt-1.5">
                Toplam <?= (int)dashboard_sanitize_summary($summary, 'buildings_total') ?> bina yönetiliyor
            </p>
        </div>

        <div class="kpi-card kpi-card--emerald">
            <div class="kpi-label text-emerald-600 dark:text-emerald-400">Daire Doluluk</div>
            <div class="kpi-value font-bold text-emerald-700 dark:text-emerald-300">
                <?= dashboard_sanitize_summary($summary, 'occupancy_rate') ?>%
            </div>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 sm:mt-1.5">
                <?= (int)dashboard_sanitize_summary($summary, 'units_total') ?> dairenin doluluk oranı
            </p>
        </div>

        <div class="kpi-card kpi-card--rose">
            <div class="kpi-label text-rose-600 dark:text-rose-400">Bekleyen Aidat</div>
            <div class="kpi-value-money font-bold text-rose-700 dark:text-rose-300">
                <span class="kpi-amount"><?= number_format((float)dashboard_sanitize_summary($summary, 'fees_outstanding'), 2, ',', '.') ?></span>
                <span class="kpi-currency">₺</span>
            </div>
            <p class="text-xs sm:text-sm text-rose-700/80 dark:text-rose-300/80 flex items-center gap-1 sm:gap-1.5 mt-1 sm:mt-1.5">
                <i class="fas fa-clock text-[10px] sm:text-xs"></i>
                <span>Geciken: <span class="font-semibold"><?= $formatMoney(dashboard_sanitize_summary($summary, 'fees_overdue')) ?></span></span>
            </p>
        </div>

        <div class="kpi-card kpi-card--sky">
            <div class="kpi-label text-sky-600 dark:text-sky-400">Tahsilat Oranı</div>
            <div class="kpi-value font-bold text-sky-700 dark:text-sky-300">
                <?= dashboard_sanitize_summary($summary, 'collection_rate') ?>%
            </div>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 sm:mt-1.5">
                Toplam tahsilat: <span class="font-semibold text-slate-700 dark:text-slate-300"><?= $formatMoney(dashboard_sanitize_summary($summary, 'fees_collected')) ?></span>
            </p>
        </div>

        <div class="kpi-card kpi-card--emerald-alt col-span-2 xl:col-span-1">
            <div class="kpi-label text-emerald-600 dark:text-emerald-400">Portal Etkileşimi</div>
            <div class="kpi-value font-bold text-emerald-700 dark:text-emerald-300">
                <?= (int)($portalStats['logged_in_recent'] ?? 0) ?>
            </div>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 sm:mt-1.5">
                Aktif: <span class="font-semibold text-slate-700 dark:text-slate-300"><?= (int)($portalStats['active'] ?? 0) ?></span> / Toplam <span class="font-semibold text-slate-700 dark:text-slate-300"><?= (int)($portalStats['total'] ?? 0) ?></span>
            </p>
            <a href="<?= base_url('/management/residents') ?>" class="text-xs sm:text-sm font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 mt-2 sm:mt-2.5 transition-colors inline-flex items-center gap-1 sm:gap-1.5 group">
                Sakin portalını aç <i class="fas fa-arrow-right text-[10px] sm:text-xs group-hover:translate-x-0.5 transition-transform"></i>
            </a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-3xl bg-white/90 dark:bg-slate-900/80 backdrop-blur border border-white/20 dark:border-slate-800 shadow-soft">
            <div class="flex items-center justify-between border-b border-white/20 dark:border-slate-800 px-5 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                    En Yüksek Aidat Borcu
                </h2>
                <span class="text-xs text-slate-400 dark:text-slate-500">Top 5</span>
            </div>
            <?php if (!empty($topOutstandingUnits)): ?>
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead class="bg-slate-50/80 dark:bg-slate-900/60 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Bina / Daire</th>
                            <th class="px-5 py-3 text-left font-semibold">Dönem</th>
                            <th class="px-5 py-3 text-right font-semibold">Bakiye</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                        <?php foreach ($topOutstandingUnits as $row): ?>
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-900/50 transition-colors">
                            <td class="px-5 py-3">
                                <div class="font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($row['building_name'] ?? '—') ?></div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Daire <?= htmlspecialchars($row['unit_number'] ?? '-') ?></div>
                            </td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                                <?= htmlspecialchars($row['period'] ?? '-') ?>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-rose-600 dark:text-rose-300">
                                <?= $formatMoney($row['balance'] ?? 0) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mobile-card-stack md:hidden">
                <?php foreach ($topOutstandingUnits as $row): ?>
                <?php
                    $buildingName = $row['building_name'] ?? '—';
                    $unitNumber = $row['unit_number'] ?? '-';
                    $period = $row['period'] ?? '-';
                    $balance = $formatMoney($row['balance'] ?? 0);
                    $initials = $formatInitials($buildingName, 'BD');
                ?>
                <div class="mobile-card mobile-card--rose">
                    <div class="mobile-card__header">
                        <div class="mobile-card__avatar"><?= e($initials) ?></div>
                        <div class="mobile-card__headline">
                            <span class="mobile-card__name"><?= e($buildingName) ?></span>
                            <span class="mobile-card__subtitle"><i class="fas fa-door-open"></i>Daire <?= e($unitNumber) ?></span>
                        </div>
                    </div>
                    <div class="mobile-card__timestamp-wrapper">
                        <span class="pill-indicator pill-indicator--warning mobile-card__timestamp"><i class="fas fa-coins"></i><?= $balance ?></span>
                    </div>
                    <div class="mobile-card__tags">
                        <span class="mobile-card__tag"><i class="fas fa-calendar"></i><?= e($period) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="px-5 py-6">
                <?= View::partial('partials/empty-state', [
                    'title' => 'Bekleyen aidat bulunmuyor',
                    'description' => 'Tüm aidat ödemeleri güncel görünüyor.',
                    'icon' => 'fa-solid fa-circle-check'
                ]) ?>
            </div>
            <?php endif; ?>
        </section>

        <section class="rounded-3xl bg-white/90 dark:bg-slate-900/80 backdrop-blur border border-white/20 dark:border-slate-800 shadow-soft">
            <div class="flex items-center justify-between border-b border-white/20 dark:border-slate-800 px-5 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                    Yaklaşan Toplantılar
                </h2>
                <a href="<?= base_url('/meetings') ?>" class="section-header-link">
                    Tümünü Gör
                </a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 hidden md:block">
                <?php if (!empty($upcomingMeetings)): ?>
                    <?php foreach ($upcomingMeetings as $meeting): ?>
                    <div class="px-5 py-4 flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($meeting['title'] ?? 'Toplantı') ?></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                <?= Utils::formatDateTime($meeting['meeting_date'] ?? '') ?>
                                <?php if (!empty($meeting['location'])): ?>
                                    · <?= e($meeting['location']) ?>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                <?= htmlspecialchars($meeting['building_name'] ?? '') ?>
                            </div>
                        </div>
                        <a href="<?= base_url('/meetings') ?>" class="text-xs text-indigo-500 hover:text-indigo-600">
                            Detay
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="px-5 py-6">
                        <?= View::partial('partials/empty-state', [
                            'title' => 'Yaklaşan toplantı yok',
                            'description' => 'Takvimde planlanmış yeni toplantı bulunmuyor.',
                            'icon' => 'fa-solid fa-handshake'
                        ]) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mobile-card-grid md:hidden">
                <?php if (!empty($upcomingMeetings)): ?>
                    <?php foreach ($upcomingMeetings as $meeting): ?>
                    <?php
                        $title = $meeting['title'] ?? 'Toplantı';
                        $building = $meeting['building_name'] ?? '';
                        $location = $meeting['location'] ?? null;
                        $formattedDate = $formatDateTime($meeting['meeting_date'] ?? null);
                        $initials = $formatInitials($title, 'MT');
                    ?>
                    <div class="mobile-card mobile-card--sky">
                        <div class="mobile-card__header">
                            <div class="mobile-card__avatar"><?= e($initials) ?></div>
                            <div class="mobile-card__headline">
                                <span class="mobile-card__name"><?= e($title) ?></span>
                                <?php if ($building): ?>
                                <span class="mobile-card__subtitle"><i class="fas fa-building"></i><?= e($building) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mobile-card__timestamp-wrapper">
                            <span class="pill-indicator mobile-card__timestamp"><i class="fas fa-calendar"></i><?= $formattedDate ?></span>
                        </div>
                        <div class="mobile-card__tags">
                            <?php if ($location): ?>
                            <span class="mobile-card__tag"><i class="fas fa-location-dot"></i><?= e($location) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="mobile-card__footer">
                            <a href="<?= base_url('/meetings') ?>" class="section-header-link">Detayları Gör</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="mobile-card mobile-card--sky">
                        <?= View::partial('partials/empty-state', [
                            'title' => 'Yaklaşan toplantı yok',
                            'description' => 'Takvimde planlanmış yeni toplantı bulunmuyor.',
                            'icon' => 'fa-solid fa-handshake'
                        ]) ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-3xl bg-white/90 dark:bg-slate-900/80 backdrop-blur border border-white/20 dark:border-slate-800 shadow-soft">
            <div class="flex items-center justify-between border-b border-white/20 dark:border-slate-800 px-5 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                    Yaklaşan Rezervasyonlar
                </h2>
                <a href="<?= base_url('/reservations') ?>" class="section-header-link">
                    Rezervasyonlar
                </a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 hidden md:block">
                <?php if (!empty($upcomingReservations)): ?>
                    <?php foreach ($upcomingReservations as $reservation): ?>
                    <div class="px-5 py-4 flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900 dark:text-white">
                                <?= htmlspecialchars($reservation['facility_name'] ?? 'Tesis') ?>
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                <?= Utils::formatDateTime($reservation['start_date'] ?? '') ?>
                                <?php if (!empty($reservation['end_date'])): ?>
                                    - <?= Utils::formatDateTime($reservation['end_date']) ?>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                <?= htmlspecialchars($reservation['resident_name'] ?? 'Sakin belirtilmedi') ?>
                                · <?= htmlspecialchars($reservation['building_name'] ?? '') ?>
                            </div>
                        </div>
                        <a href="<?= base_url('/reservations') ?>" class="text-xs text-indigo-500 hover:text-indigo-600">
                            Yönet
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="px-5 py-6">
                        <?= View::partial('partials/empty-state', [
                            'title' => 'Yaklaşan rezervasyon yok',
                            'description' => 'Tesis rezervasyonları için yeni kayıt bulunmuyor.',
                            'icon' => 'fa-solid fa-calendar-day'
                        ]) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mobile-card-grid md:hidden">
                <?php if (!empty($upcomingReservations)): ?>
                    <?php foreach ($upcomingReservations as $reservation): ?>
                    <?php
                        $facility = $reservation['facility_name'] ?? 'Tesis';
                        $resident = $reservation['resident_name'] ?? 'Sakin';
                        $building = $reservation['building_name'] ?? '';
                        $start = $formatDateTime($reservation['start_date'] ?? null);
                        $end = !empty($reservation['end_date']) ? $formatDateTime($reservation['end_date']) : null;
                        $initials = $formatInitials($facility, 'TZ');
                    ?>
                    <div class="mobile-card mobile-card--emerald">
                        <div class="mobile-card__header">
                            <div class="mobile-card__avatar"><?= e($initials) ?></div>
                            <div class="mobile-card__headline">
                                <span class="mobile-card__name"><?= e($facility) ?></span>
                                <span class="mobile-card__subtitle"><i class="fas fa-user"></i><?= e($resident) ?></span>
                            </div>
                        </div>
                        <div class="mobile-card__timestamp-wrapper">
                            <span class="pill-indicator mobile-card__timestamp"><i class="fas fa-calendar-check"></i><?= $start ?></span>
                        </div>
                        <div class="mobile-card__tags">
                            <?php if ($end): ?>
                            <span class="mobile-card__tag"><i class="fas fa-hourglass-half"></i><?= $end ?></span>
                            <?php endif; ?>
                            <?php if ($building): ?>
                            <span class="mobile-card__tag"><i class="fas fa-building"></i><?= e($building) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="mobile-card__footer">
                            <a href="<?= base_url('/reservations') ?>" class="section-header-link">Rezervasyonu Yönet</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="mobile-card mobile-card--emerald">
                        <?= View::partial('partials/empty-state', [
                            'title' => 'Yaklaşan rezervasyon yok',
                            'description' => 'Tesis rezervasyonları için yeni kayıt bulunmuyor.',
                            'icon' => 'fa-solid fa-calendar-day'
                        ]) ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="rounded-3xl bg-white/90 dark:bg-slate-900/80 backdrop-blur border border-white/20 dark:border-slate-800 shadow-soft">
            <div class="flex items-center justify-between border-b border-white/20 dark:border-slate-800 px-5 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                    Son Duyurular
                </h2>
                <a href="<?= base_url('/announcements') ?>" class="section-header-link">
                    Duyurular
                </a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800 hidden md:block">
                <?php if (!empty($recentAnnouncements)): ?>
                    <?php foreach ($recentAnnouncements as $announcement): ?>
                    <div class="px-5 py-4 flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900 dark:text-white">
                                <?= htmlspecialchars($announcement['title'] ?? 'Duyuru') ?>
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                <?= Utils::formatDate($announcement['publish_date'] ?? '') ?>
                                · <?= htmlspecialchars($announcement['building_name'] ?? '') ?>
                            </div>
                        </div>
                        <a href="<?= base_url('/announcements') ?>" class="text-xs text-indigo-500 hover:text-indigo-600">
                            Oku
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="px-5 py-6">
                        <?= View::partial('partials/empty-state', [
                            'title' => 'Son duyuru bulunmuyor',
                            'description' => 'Yeni bir duyuru yayımlandığında burada göreceksiniz.',
                            'icon' => 'fa-solid fa-bullhorn'
                        ]) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mobile-card-grid md:hidden">
                <?php if (!empty($recentAnnouncements)): ?>
                    <?php foreach ($recentAnnouncements as $announcement): ?>
                    <?php
                        $title = $announcement['title'] ?? 'Duyuru';
                        $building = $announcement['building_name'] ?? '';
                        $publishDate = $announcement['publish_date'] ?? null;
                        $formattedDate = $publishDate ? Utils::formatDate($publishDate) : '—';
                        $initials = $formatInitials($title, 'DN');
                    ?>
                    <div class="mobile-card mobile-card--amber">
                        <div class="mobile-card__header">
                            <div class="mobile-card__avatar"><?= e($initials) ?></div>
                            <div class="mobile-card__headline">
                                <span class="mobile-card__name"><?= e($title) ?></span>
                                <?php if ($building): ?>
                                <span class="mobile-card__subtitle"><i class="fas fa-building"></i><?= e($building) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mobile-card__timestamp-wrapper">
                            <span class="pill-indicator mobile-card__timestamp"><i class="fas fa-calendar-day"></i><?= $formattedDate ?></span>
                        </div>
                        <div class="mobile-card__footer">
                            <a href="<?= base_url('/announcements') ?>" class="section-header-link">Duyuruyu Aç</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="mobile-card mobile-card--amber">
                        <?= View::partial('partials/empty-state', [
                            'title' => 'Son duyuru bulunmuyor',
                            'description' => 'Yeni bir duyuru yayımlandığında burada göreceksiniz.',
                            'icon' => 'fa-solid fa-bullhorn'
                        ]) ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <section class="rounded-3xl bg-white/90 dark:bg-slate-900/80 backdrop-blur border border-white/20 dark:border-slate-800 shadow-soft">
            <div class="flex items-center justify-between border-b border-white/20 dark:border-slate-800 px-5 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                    Portal Davetleri
                </h2>
                <a href="<?= base_url('/management/residents') ?>#portal-overview" class="section-header-link">
                    Sakin Portalı
                </a>
            </div>
            <div class="hidden md:block p-4 space-y-3">
                <?php if (!empty($pendingPortalInvites)): ?>
                    <?php foreach ($pendingPortalInvites as $invite): ?>
                    <?php
                        $name = $invite['name'] ?? 'Sakin';
                        $email = $invite['email'] ?? '';
                        $building = $invite['building_name'] ?? '—';
                        $unit = $invite['unit_number'] ?? '—';
                        $sentAt = $formatDateTime($invite['created_at'] ?? null);
                        $initials = $formatInitials($name, 'DV');
                    ?>
                    <div class="portal-invite-card group">
                        <div class="flex items-start gap-4">
                            <div class="portal-invite-avatar portal-invite-avatar--sky flex-shrink-0">
                                <?= e($initials) ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-white mb-1">
                                            <?= e($name) ?>
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                            <i class="fas fa-envelope text-[10px]"></i>
                                            <span class="truncate"><?= e($email) ?></span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="pill-indicator pill-indicator--success inline-flex items-center gap-1.5">
                                            <i class="fas fa-paper-plane text-[10px]"></i>
                                            <span class="text-xs font-medium"><?= $sentAt ?></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="pill-indicator pill-indicator--info inline-flex items-center gap-1.5">
                                        <i class="fas fa-building text-[10px]"></i>
                                        <span class="text-xs"><?= e($building) ?> · Daire <?= e($unit) ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="px-5 py-6">
                        <?= View::partial('partials/empty-state', [
                            'title' => 'Bekleyen portal daveti yok',
                            'description' => 'Tüm sakinler daveti tamamlamış görünüyor.',
                            'icon' => 'fa-solid fa-envelope-open-text'
                        ]) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mobile-card-grid md:hidden">
                <?php if (!empty($pendingPortalInvites)): ?>
                    <?php foreach ($pendingPortalInvites as $invite): ?>
                    <?php
                        $name = $invite['name'] ?? 'Sakin';
                        $email = $invite['email'] ?? '';
                        $unit = ($invite['building_name'] ?? '—') . ' · Daire ' . ($invite['unit_number'] ?? '—');
                        $sentAt = $formatDateTime($invite['created_at'] ?? null);
                        $initials = $formatInitials($name, 'DV');
                    ?>
                    <div class="mobile-card mobile-card--sky">
                        <div class="mobile-card__header">
                            <div class="mobile-card__avatar"><?= e($initials) ?></div>
                            <div class="mobile-card__headline">
                                <span class="mobile-card__name"><?= e($name) ?></span>
                                <?php if ($email): ?>
                                <span class="mobile-card__subtitle"><i class="fas fa-envelope"></i><?= e($email) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mobile-card__timestamp-wrapper">
                            <span class="pill-indicator pill-indicator--success mobile-card__timestamp"><i class="fas fa-paper-plane"></i><?= $sentAt ?></span>
                        </div>
                        <div class="mobile-card__tags">
                            <span class="mobile-card__tag"><i class="fas fa-building"></i><?= e($unit) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="mobile-card mobile-card--sky">
                        <?= View::partial('partials/empty-state', [
                            'title' => 'Bekleyen portal daveti yok',
                            'description' => 'Tüm sakinler daveti tamamlamış görünüyor.',
                            'icon' => 'fa-solid fa-envelope-open-text'
                        ]) ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

