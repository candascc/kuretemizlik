<?php
    $companyFilterParam = isset($_GET['company_filter']) && $_GET['company_filter'] !== ''
        ? (int) $_GET['company_filter']
        : null;
    $environment = getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'production');
    $timezone = date_default_timezone_get();
    try {
        $now = new DateTime('now', new DateTimeZone($timezone));
    } catch (Exception $e) {
        $now = new DateTime('now');
    }
    $appVersion = $_ENV['APP_VERSION']
        ?? getenv('APP_VERSION')
        ?? (defined('APP_VERSION') ? APP_VERSION : 'v1');
    $supportEmail = 'destek@kuretemizlik.com';
    $supportPhone = '+90 850 000 0000';

    $isAuthAvailable = class_exists('Auth');
    $isAuthenticated = $isAuthAvailable && Auth::check();
    $activeUser = $isAuthenticated ? Auth::user() : null;
    $currentRole = $isAuthenticated
        ? (method_exists('Auth', 'role') ? Auth::role() : ($activeUser['role'] ?? null))
        : null;
    $roleDefinition = ($currentRole && class_exists('Roles')) ? Roles::get($currentRole) : null;
    $roleScope = $roleDefinition['scope'] ?? ($activeUser['scope'] ?? ($isAuthenticated ? 'staff' : 'guest'));
    $roleLabel = $roleDefinition['label'] ?? ($currentRole ?: ($isAuthenticated ? 'Kullanıcı' : 'Ziyaretçi'));
    $roleCapabilities = ($currentRole && class_exists('Roles')) ? Roles::capabilities($currentRole) : [];
    $roleDescription = $roleDefinition['description'] ?? null;

    $scopeLabels = [
        'staff' => 'Yönetim Paneli',
        'resident_portal' => 'Sakin Portalı',
        'customer_portal' => 'Müşteri Portalı',
        'guest' => 'Genel Erişim',
    ];
    $activeScopeLabel = $scopeLabels[$roleScope] ?? ucwords(str_replace('_', ' ', $roleScope));

    $startsWith = static function (string $haystack, string $needle): bool {
        if ($needle === '') {
            return true;
        }
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    };

    $canAccess = static function (?array $requirements) use ($roleCapabilities, $isAuthenticated, $startsWith): bool {
        if ($requirements === null || $requirements === []) {
            $requirements = ['authenticated'];
        }

        foreach ($requirements as $requirement) {
            if ($requirement === null || $requirement === '') {
                continue;
            }

            if ($requirement === 'public') {
                return true;
            }

            if ($requirement === 'guest') {
                return !$isAuthenticated;
            }

            if (($requirement === 'authenticated' || $requirement === '*') && $isAuthenticated) {
                return true;
            }

            if (in_array($requirement, $roleCapabilities, true)) {
                return true;
            }

            if (substr($requirement, -1) === '*') {
                $prefix = rtrim($requirement, '*');
                foreach ($roleCapabilities as $capability) {
                    if ($startsWith($capability, $prefix)) {
                        return true;
                    }
                }
            }

            foreach ($roleCapabilities as $capability) {
                if (substr($capability, -1) === '*') {
                    $prefix = rtrim($capability, '*');
                    if ($startsWith($requirement, $prefix)) {
                        return true;
                    }
                }
            }
        }

        return false;
    };

    $filterLinks = static function (array $links) use ($canAccess): array {
        $visible = [];
        foreach ($links as $link) {
            $requirements = $link['permissions'] ?? ['authenticated'];
            if ($canAccess($requirements)) {
                $visible[] = $link;
            }
        }
        return $visible;
    };

    $buildAttributeString = static function (array $link): string {
        $attributes = $link['attributes'] ?? [];
        if (!is_array($attributes)) {
            return '';
        }

        $buffer = '';
        foreach ($attributes as $attr => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            $buffer .= ' ' . htmlspecialchars((string) $attr, ENT_QUOTES, 'UTF-8') .
                '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }
        return $buffer;
    };

    $navConfigs = [
        'staff' => [
            'title' => 'Operasyon Modülleri',
            'links' => [
                ['label' => 'Ana Dashboard', 'href' => base_url('/'), 'icon' => 'fas fa-chart-pie', 'permissions' => ['authenticated']],
                ['label' => 'Takvim', 'href' => base_url('/calendar'), 'icon' => 'fas fa-calendar-alt', 'permissions' => ['operations.plan', 'operations.assign', 'operations.execute']],
                ['label' => 'İşler', 'href' => base_url('/jobs'), 'icon' => 'fas fa-list-check', 'permissions' => ['jobs.view', 'jobs.create', 'jobs.edit']],
                ['label' => 'Müşteriler', 'href' => base_url('/customers'), 'icon' => 'fas fa-users', 'permissions' => ['customers.view', 'customers.manage']],
                ['label' => 'Finans', 'href' => base_url('/finance'), 'icon' => 'fas fa-wallet', 'permissions' => ['finance.authorize', 'finance.collect', 'finance.report', 'management.finance']],
                ['label' => 'Binalar & Sakinler', 'href' => base_url('/management/residents'), 'icon' => 'fas fa-building', 'permissions' => ['management.residents', 'management.assets']],
                ['label' => 'Raporlama', 'href' => base_url('/reports/financial'), 'icon' => 'fas fa-chart-line', 'permissions' => ['reports.view', 'reports.financial', 'reports.jobs', 'reports.customers']],
                ['label' => 'Ayarlar', 'href' => base_url('/settings'), 'icon' => 'fas fa-sliders', 'permissions' => ['system.*', 'management.access', 'tenant.switch']],
            ],
        ],
        'resident_portal' => [
            'title' => 'Portal Modülleri',
            'links' => [
                ['label' => 'Portal Ana Sayfa', 'href' => base_url('/resident/dashboard'), 'icon' => 'fas fa-house', 'permissions' => ['resident.dashboard']],
                ['label' => 'Aidat & Ödemeler', 'href' => base_url('/resident/fees'), 'icon' => 'fas fa-credit-card', 'permissions' => ['resident.finance']],
                ['label' => 'Taleplerim', 'href' => base_url('/resident/requests'), 'icon' => 'fas fa-inbox', 'permissions' => ['resident.requests']],
                ['label' => 'Duyurular', 'href' => base_url('/resident/announcements'), 'icon' => 'fas fa-bullhorn', 'permissions' => ['resident.management', 'resident.dashboard']],
                ['label' => 'Toplantılar', 'href' => base_url('/resident/meetings'), 'icon' => 'fas fa-people-roof', 'permissions' => ['resident.management']],
                ['label' => 'Profil & Güvenlik', 'href' => base_url('/resident/profile'), 'icon' => 'fas fa-id-badge', 'permissions' => ['resident.dashboard']],
            ],
        ],
        'customer_portal' => [
            'title' => 'Müşteri Modülleri',
            'links' => [
                ['label' => 'Durum Panosu', 'href' => base_url('/portal/dashboard'), 'icon' => 'fas fa-chart-area', 'permissions' => ['portal.dashboard']],
                ['label' => 'Hizmetlerim', 'href' => base_url('/portal/jobs'), 'icon' => 'fas fa-broom', 'permissions' => ['portal.jobs']],
                ['label' => 'Faturalar & Teklifler', 'href' => base_url('/portal/invoices'), 'icon' => 'fas fa-file-invoice-dollar', 'permissions' => ['portal.invoices']],
                ['label' => 'Rezervasyon Talebi', 'href' => base_url('/portal/booking'), 'icon' => 'fas fa-calendar-plus', 'permissions' => ['portal.jobs', 'portal.dashboard']],
                ['label' => 'Analizler', 'href' => base_url('/portal/analytics'), 'icon' => 'fas fa-chart-pie', 'permissions' => ['portal.analytics']],
            ],
        ],
        'guest' => [
            'title' => 'Platform Hakkında',
            'links' => [
                ['label' => 'Kurumsal Web', 'href' => 'https://kuretemizlik.com', 'icon' => 'fas fa-globe', 'permissions' => ['public'], 'attributes' => ['target' => '_blank', 'rel' => 'noopener']],
                ['label' => 'Demo Talep Et', 'href' => 'mailto:' . $supportEmail . '?subject=Küre%20Temizlik%20Demo%20Talebi', 'icon' => 'fas fa-handshake', 'permissions' => ['public']],
                ['label' => 'Giriş Yap', 'href' => base_url('/login'), 'icon' => 'fas fa-right-to-bracket', 'permissions' => ['public']],
            ],
        ],
    ];

    $resourceConfigs = [
        'staff' => [
            'title' => 'Kaynaklar',
            'links' => [
                ['label' => 'Proje Özeti', 'href' => base_url('/FINAL_REPORT.md'), 'icon' => 'fas fa-file-alt', 'permissions' => ['system.*', 'analytics.view'], 'attributes' => ['target' => '_blank', 'rel' => 'noopener']],
                ['label' => 'Güvenlik Kontrolü', 'href' => base_url('/SECURITY_AUDIT_CHECKLIST.md'), 'icon' => 'fas fa-shield-alt', 'permissions' => ['security.*', 'system.*'], 'attributes' => ['target' => '_blank', 'rel' => 'noopener']],
                ['label' => 'Manuel Test Listesi', 'href' => base_url('/MANUAL_TEST_CHECKLIST.md'), 'icon' => 'fas fa-clipboard-check', 'permissions' => ['reports.view', 'operations.*'], 'attributes' => ['target' => '_blank', 'rel' => 'noopener']],
                ['label' => 'UX Kılavuzu', 'href' => base_url('/UX_IMPLEMENTATION_GUIDE.md'), 'icon' => 'fas fa-drafting-compass', 'permissions' => ['operations.*', 'management.access'], 'attributes' => ['target' => '_blank', 'rel' => 'noopener']],
                ['label' => 'Deploy Checklist', 'href' => base_url('/DEPLOYMENT_CHECKLIST.md'), 'icon' => 'fas fa-rocket', 'permissions' => ['system.*', 'operations.*'], 'attributes' => ['target' => '_blank', 'rel' => 'noopener']],
            ],
        ],
        'resident_portal' => [
            'title' => 'Sakin Destek',
            'links' => [
                ['label' => 'Duyuru Arşivi', 'href' => base_url('/resident/announcements'), 'icon' => 'fas fa-broadcast-tower', 'permissions' => ['resident.management', 'resident.dashboard']],
                ['label' => 'Aidat Ödeme', 'href' => base_url('/resident/pay-fee'), 'icon' => 'fas fa-coins', 'permissions' => ['resident.finance']],
                ['label' => 'Talep Oluşturma Rehberi', 'href' => base_url('/resident/create-request'), 'icon' => 'fas fa-circle-question', 'permissions' => ['resident.requests']],
                ['label' => 'Oturum Güvenliği', 'href' => base_url('/resident/profile'), 'icon' => 'fas fa-user-shield', 'permissions' => ['resident.dashboard']],
            ],
        ],
        'customer_portal' => [
            'title' => 'Müşteri Yardım',
            'links' => [
                ['label' => 'Fatura Yardımı', 'href' => base_url('/portal/invoices'), 'icon' => 'fas fa-file-invoice', 'permissions' => ['portal.invoices']],
                ['label' => 'Servis Durumu', 'href' => base_url('/portal/jobs'), 'icon' => 'fas fa-swatchbook', 'permissions' => ['portal.jobs']],
                ['label' => 'Ödeme Kanalları', 'href' => base_url('/portal/payment'), 'icon' => 'fas fa-credit-card', 'permissions' => ['portal.invoices', 'portal.dashboard']],
                ['label' => 'Destek Merkezi', 'href' => 'mailto:' . $supportEmail . '?subject=Müşteri%20Portal%20Destek', 'icon' => 'fas fa-life-ring', 'permissions' => ['public']],
            ],
        ],
        'guest' => [
            'title' => 'Bilgi',
            'links' => [
                ['label' => 'Gizlilik Politikası', 'href' => base_url('/privacy-policy'), 'icon' => 'fas fa-shield-halved', 'permissions' => ['public']],
                ['label' => 'Kullanım Şartları', 'href' => base_url('/terms-of-use'), 'icon' => 'fas fa-scale-balanced', 'permissions' => ['public']],
                ['label' => 'İletişim', 'href' => 'mailto:' . $supportEmail, 'icon' => 'fas fa-envelope-open-text', 'permissions' => ['public']],
            ],
        ],
    ];

    $activeNavConfig = $navConfigs[$roleScope] ?? $navConfigs['staff'];
    $moduleLinks = $filterLinks($activeNavConfig['links']);
    if (!$moduleLinks && $roleScope !== 'staff') {
        $moduleLinks = $filterLinks($navConfigs['staff']['links']);
    }

    $activeResourceConfig = $resourceConfigs[$roleScope] ?? $resourceConfigs['staff'];
    $resourceLinks = $filterLinks($activeResourceConfig['links']);
    if (!$resourceLinks && $roleScope !== 'staff') {
        $resourceLinks = $filterLinks($resourceConfigs['guest']['links']);
        $activeResourceConfig = $resourceConfigs['guest'];
    }

    $heroContext = [
        'eyebrow' => 'Küre Temizlik Platformu',
        'title' => 'Operasyonlarınızı tek panelden yönetin',
        'body' => 'Randevular, saha ekipleri, finans ve raporlamayı tek çalışma alanında toplayan modern iş takip sistemi. Gerçek zamanlı metrikler ve otomasyonlarla performansınız her an görünür.',
        'ctaEyebrow' => 'Hızlı aksiyon',
        'ctaTitle' => 'Yeni görevleri dakikalar içinde planlayın',
        'ctaCopy' => 'Yoğun dönemlerde bile ekipleri aynı sayfada tutun. Merkezi takvim, iş talimatı ve onay akışlarını tek tıklamayla başlatın.',
    ];

    if ($roleScope === 'resident_portal') {
        $heroContext = [
            'eyebrow' => 'Sakin Portalı',
            'title' => 'Aidat, talepler ve duyurular tek yerde',
            'body' => 'Site yönetimiyle olan tüm iletişiminizi tek panelden yürütün. Aidatlarınızı takip edin, bakım talepleri açın ve toplu duyuruları kaçırmayın.',
            'ctaEyebrow' => 'Portal aksiyonları',
            'ctaTitle' => 'Talep ve ödemelerinizi anında yönetin',
            'ctaCopy' => 'Destek kayıtları oluşturun, mevcut taleplerinizi izleyin ve online ödeme adımlarını güvenle tamamlayın.',
        ];
    } elseif ($roleScope === 'customer_portal') {
        $heroContext = [
            'eyebrow' => 'Müşteri Portalı',
            'title' => 'Tüm sözleşmeleriniz için tek kontrol merkezi',
            'body' => 'Çok lokasyonlu hizmetlerin durumunu izleyin, faturalarınızı görüntüleyin ve yeni rezervasyon taleplerini hızla iletin.',
            'ctaEyebrow' => 'Müşteri kısayolları',
            'ctaTitle' => 'Servis durumlarını anında öğrenin',
            'ctaCopy' => 'Aktif projeleri takip edin, ödeme sürecinizi doğrulayın ve yeni rezervasyonları birkaç adımda planlayın.',
        ];
    } elseif (!$isAuthenticated) {
        $heroContext = [
            'eyebrow' => 'Küre Temizlik',
            'title' => 'Profesyonel temizlikte uçtan uca çözüm',
            'body' => 'İş takibinden saha koordinasyonuna kadar tüm süreçlerinizi dijitalleştiriyoruz. Daha fazlası için bize ulaşın veya demo isteyin.',
            'ctaEyebrow' => 'Hemen başlayın',
            'ctaTitle' => 'Platforma giriş yapın',
            'ctaCopy' => 'Mevcut hesabınızla giriş yaparak yönetim paneline ulaşın ya da destek ekibimizle iletişime geçin.',
        ];
    }

    $ctaCandidates = [
        'staff' => [
            ['label' => 'Yeni İş Oluştur', 'href' => base_url('/jobs/new'), 'icon' => 'fas fa-plus', 'permissions' => ['jobs.create']],
            ['label' => 'İş Listesini Aç', 'href' => base_url('/jobs'), 'icon' => 'fas fa-list-check', 'permissions' => ['jobs.view']],
            ['label' => 'Takvimi Görüntüle', 'href' => base_url('/calendar'), 'icon' => 'fas fa-calendar-alt', 'permissions' => ['operations.plan', 'operations.execute']],
        ],
        'resident_portal' => [
            ['label' => 'Yeni Talep Aç', 'href' => base_url('/resident/create-request'), 'icon' => 'fas fa-paper-plane', 'permissions' => ['resident.requests']],
            ['label' => 'Taleplerimi Gör', 'href' => base_url('/resident/requests'), 'icon' => 'fas fa-list', 'permissions' => ['resident.requests']],
            ['label' => 'Aidat Öde', 'href' => base_url('/resident/pay-fee'), 'icon' => 'fas fa-credit-card', 'permissions' => ['resident.finance']],
        ],
        'customer_portal' => [
            ['label' => 'Yeni Rezervasyon Talebi', 'href' => base_url('/portal/booking'), 'icon' => 'fas fa-calendar-plus', 'permissions' => ['portal.jobs', 'portal.dashboard']],
            ['label' => 'Servis Listem', 'href' => base_url('/portal/jobs'), 'icon' => 'fas fa-broom', 'permissions' => ['portal.jobs']],
            ['label' => 'Faturaları Aç', 'href' => base_url('/portal/invoices'), 'icon' => 'fas fa-receipt', 'permissions' => ['portal.invoices']],
        ],
        'guest' => [
            ['label' => 'Giriş Yap', 'href' => base_url('/login'), 'icon' => 'fas fa-right-to-bracket', 'permissions' => ['public']],
            ['label' => 'Demo Talep Et', 'href' => base_url('/contact'), 'icon' => 'fas fa-magic', 'permissions' => ['public']],
        ],
    ];

    $ctaSet = $ctaCandidates[$roleScope] ?? $ctaCandidates['staff'];
    $primaryCta = null;
    foreach ($ctaSet as $candidate) {
        if ($canAccess($candidate['permissions'])) {
            $primaryCta = $candidate;
            break;
        }
    }
    if (!$primaryCta && $roleScope !== 'guest') {
        $primaryCta = [
            'label' => 'Destek ile Görüş',
            'href' => 'mailto:' . $supportEmail,
            'icon' => 'fas fa-headset',
            'permissions' => ['public'],
        ];
    }

    $secondaryCtaLabels = [
        'resident_portal' => 'Portal Destek',
        'customer_portal' => 'Müşteri Başvurusu',
        'guest' => 'İletişime Geç',
    ];
    $secondaryCtaLabel = $secondaryCtaLabels[$roleScope] ?? 'Destek Talebi';

    $summaryItems = [
        ['label' => 'Aktif Rol', 'value' => $roleLabel, 'show' => $isAuthenticated],
        ['label' => 'Kapsam', 'value' => $activeScopeLabel, 'show' => true],
        ['label' => 'Sunucu Saati', 'value' => $now->format('d.m.Y H:i'), 'show' => true],
        ['label' => 'Zaman Dilimi', 'value' => $timezone, 'show' => true],
    ];
    if ($roleScope === 'staff') {
        $summaryItems[] = [
            'label' => 'Çoklu Şirket Filtre',
            'value' => $companyFilterParam ? ('#' . $companyFilterParam) : 'Tümü',
            'show' => true,
        ];
    }
    if ($activeUser && isset($activeUser['username'])) {
        $summaryItems[] = [
            'label' => 'Aktif Kullanıcı',
            'value' => $activeUser['username'],
            'show' => true,
        ];
    }
    $summaryItems[] = ['label' => 'Performans Hedefi', 'value' => '< 200 ms', 'show' => true];
?>
<footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 w-full footer-container">
    <div class="footer-inner px-3 sm:px-4 lg:px-8 py-8 sm:py-10 w-full">
        <section class="footer-hero grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] mb-10">
            <div class="rounded-2xl bg-gradient-to-br from-primary-50 via-white to-white dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 border border-primary-100/60 dark:border-slate-800 p-6 lg:p-7 shadow-lg shadow-primary-900/5">
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-primary-600 text-white shadow-lg shadow-primary-900/25">
                        <i class="fas fa-broom text-xl" aria-hidden="true"></i>
                    </span>
                    <div>
                        <p class="text-sm uppercase tracking-wide text-primary-700 dark:text-primary-200 font-semibold">
                            <?= htmlspecialchars($heroContext['eyebrow'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <h3 class="text-2xl font-semibold text-slate-900 dark:text-white">
                            <?= htmlspecialchars($heroContext['title'], ENT_QUOTES, 'UTF-8') ?>
                        </h3>
                        <?php if ($roleDescription): ?>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <?= e($roleDescription) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="mt-4 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    <?= htmlspecialchars($heroContext['body'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div class="flex flex-wrap gap-3 mt-5">
                    <span class="footer-status-chip text-xs font-semibold">
                        Rol: <?= e($roleLabel) ?>
                    </span>
                    <?php if ($roleScope === 'staff'): ?>
                        <span class="footer-status-chip text-xs font-semibold">
                            Filtre: <?= $companyFilterParam ? '#' . htmlspecialchars((string) $companyFilterParam, ENT_QUOTES, 'UTF-8') : 'Tümü' ?>
                        </span>
                    <?php endif; ?>
                    <span class="footer-status-chip text-xs font-semibold">
                        Ortam: <?= e($environment) ?>
                    </span>
                    <span class="footer-status-chip text-xs font-semibold">
                        Sürüm: <?= e($appVersion) ?>
                    </span>
                </div>
            </div>
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 p-6 flex flex-col justify-between shadow-lg shadow-slate-900/10 footer-cta-card">
                <div>
                    <p class="text-xs uppercase tracking-wide text-primary-600 dark:text-primary-300 font-semibold">
                        <?= htmlspecialchars($heroContext['ctaEyebrow'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <h4 class="text-xl font-semibold text-slate-900 dark:text-white mt-2">
                        <?= htmlspecialchars($heroContext['ctaTitle'], ENT_QUOTES, 'UTF-8') ?>
                    </h4>
                    <p class="text-sm text-slate-600 dark:text-slate-300 mt-3">
                        <?= htmlspecialchars($heroContext['ctaCopy'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
                <div class="mt-5 flex flex-col sm:flex-row gap-3">
                    <?php if ($primaryCta): ?>
                        <a href="<?= htmlspecialchars($primaryCta['href'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center justify-center rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold px-5 py-3 transition-colors">
                            <i class="<?= htmlspecialchars($primaryCta['icon'], ENT_QUOTES, 'UTF-8') ?> mr-2 text-xs" aria-hidden="true"></i>
                            <?= htmlspecialchars($primaryCta['label'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endif; ?>
                    <a href="mailto:<?= e($supportEmail) ?>" class="inline-flex items-center justify-center rounded-xl border border-primary-200 text-primary-700 dark:text-primary-200 hover:border-primary-400 text-sm font-semibold px-5 py-3 transition-colors">
                        <i class="fas fa-headset mr-2 text-xs" aria-hidden="true"></i>
                        <?= e($secondaryCtaLabel) ?>
                    </a>
                </div>
                <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                    Talep formunuz SLA kapsamına göre ilgili ekip liderine otomatik olarak yönlendirilir.
                </p>
            </div>
        </section>

        <div class="footer-grid grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400 font-semibold mb-4">
                    <?= htmlspecialchars($activeNavConfig['title'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <?php if ($moduleLinks): ?>
                    <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
                        <?php foreach ($moduleLinks as $link): ?>
                            <?php $attrString = $buildAttributeString($link); ?>
                            <li>
                                <a href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>"<?= $attrString ?> class="flex items-center gap-2 hover:text-primary-600 transition-colors">
                                    <i class="<?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?> text-xs" aria-hidden="true"></i>
                                    <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Bu rol için ek bağlantı bulunmuyor.</p>
                <?php endif; ?>
            </div>

            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400 font-semibold mb-4">
                    <?= htmlspecialchars($activeResourceConfig['title'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <?php if ($resourceLinks): ?>
                    <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
                        <?php foreach ($resourceLinks as $link): ?>
                            <?php $attrString = $buildAttributeString($link); ?>
                            <li>
                                <a href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>"<?= $attrString ?> class="flex items-center gap-2 hover:text-primary-600 transition-colors">
                                    <i class="<?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?> text-xs" aria-hidden="true"></i>
                                    <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Yardımcı bağlantılar bu rol için kısıtlandı.</p>
                <?php endif; ?>
            </div>

            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400 font-semibold mb-4">Destek & İletişim</p>
                <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                    <li class="flex items-start gap-3">
                        <i class="fas fa-envelope text-primary-600 dark:text-primary-300 text-xs mt-1" aria-hidden="true"></i>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 block">E-posta</span>
                            <a href="mailto:<?= e($supportEmail) ?>" class="font-medium hover:text-primary-600 transition-colors">
                                <?= e($supportEmail) ?>
                            </a>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <i class="fas fa-phone text-primary-600 dark:text-primary-300 text-xs mt-1" aria-hidden="true"></i>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 block">Çağrı Merkezi</span>
                            <a href="tel:+908500000000" class="font-medium hover:text-primary-600 transition-colors">
                                <?= e($supportPhone) ?>
                            </a>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <i class="fas fa-clock text-primary-600 dark:text-primary-300 text-xs mt-1" aria-hidden="true"></i>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 block">Destek Saatleri</span>
                            Hafta içi 09:00 – 18:00
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <i class="fas fa-location-dot text-primary-600 dark:text-primary-300 text-xs mt-1" aria-hidden="true"></i>
                        <div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 block">Ofis</span>
                            İstanbul / Maslak Teknoloji Ofisi
                        </div>
                    </li>
                </ul>
            </div>

            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400 font-semibold mb-4">Sistem Özeti</p>
                <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                    <?php foreach ($summaryItems as $item): ?>
                        <?php if (!($item['show'] ?? true)) { continue; } ?>
                        <li class="flex items-center justify-between gap-3">
                            <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="footer-status-chip"><?= htmlspecialchars($item['value'], ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-800 mt-10 pt-6 footer-meta">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        &copy; <?= date('Y') ?> Küre Temizlik. Tüm hakları saklıdır.
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Operasyonel mükemmelik programı kapsamında düzenli güvenlik taramaları ve yük testleri uygulanır.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-sm text-slate-600 dark:text-slate-300 footer-links">
                    <a href="<?= base_url('/privacy-policy') ?>" class="hover:text-primary-600 transition-colors">Gizlilik Politikası</a>
                    <span class="hidden md:inline" aria-hidden="true">•</span>
                    <a href="<?= base_url('/terms-of-use') ?>" class="hover:text-primary-600 transition-colors">Kullanım Şartları</a>
                    <span class="hidden md:inline" aria-hidden="true">•</span>
                    <a href="<?= base_url('/status') ?>" class="hover:text-primary-600 transition-colors">Sistem Durumu</a>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 md:gap-3 mt-4 text-xs text-slate-600 dark:text-slate-300 footer-metrics" aria-live="polite">
                <span id="sb-cache-foot" class="footer-status-chip">Cache: ölçülüyor...</span>
                <span id="sb-db-foot" class="footer-status-chip">DB: ölçülüyor...</span>
                <span id="sb-disk-foot" class="footer-status-chip">Disk: ölçülüyor...</span>
                <span id="sb-queue-foot" class="footer-status-chip">Queue: ölçülüyor...</span>
            </div>
        </div>
    </div>
</footer>
