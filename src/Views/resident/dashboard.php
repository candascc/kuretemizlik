<div class="space-y-8">
    <?php
        $metrics = $dashboardMetrics ?? [
            'pendingFees' => ['count' => count(array_filter($recentFees ?? [], fn($f) => ($f['status'] ?? '') !== 'paid')), 'outstanding' => 0],
            'openRequests' => count($pendingRequests ?? []),
            'announcements' => count($announcements ?? []),
            'meetings' => count($meetings ?? []),
        ];
        $pendingOutstanding = $metrics['pendingFees']['outstanding'] ?? 0;
        $verificationStatus = $verificationStatus ?? [];
        $pendingVerificationMap = $pendingVerificationMap ?? ['email' => null, 'phone' => null];
        $onboardingCards = $onboardingCards ?? [];

        $maskEmail = static function (?string $email): string {
            if (!$email || strpos($email, '@') === false) {
                return '***@***';
            }
            [$local, $domain] = explode('@', $email, 2);
            $localMasked = substr($local, 0, 1) . str_repeat('*', max(1, strlen($local) - 1));
            $domainParts = explode('.', $domain);
            $domainMasked = implode('.', array_map(static function ($part) {
                return substr($part, 0, 1) . str_repeat('*', max(1, strlen($part) - 1));
            }, $domainParts));
            return $localMasked . '@' . $domainMasked;
        };

        $maskPhone = static function (?string $phone): string {
            if (!$phone) {
                return '***';
            }
            $digits = preg_replace('/\D+/', '', $phone);
            if (strlen($digits) <= 4) {
                return str_repeat('*', strlen($digits));
            }
            $suffix = substr($digits, -4);
            return '+** ' . str_repeat('*', max(0, strlen($digits) - 4)) . $suffix;
        };

        $quickActions = [
            [
                'label' => 'Aidat Öde',
                'href' => base_url('/resident/fees'),
                'icon' => 'fa-credit-card',
                'description' => 'Kart veya havale ile ödeme yapın.',
                'badge' => null,
            ],
            [
                'label' => 'Talep Oluştur',
                'href' => base_url('/resident/create-request'),
                'icon' => 'fa-plus-circle',
                'description' => 'Destek veya bakım talebinizi iletin.',
                'badge' => null,
            ],
            [
                'label' => 'Duyurular',
                'href' => base_url('/resident/announcements'),
                'icon' => 'fa-bullhorn',
                'description' => 'Site yönetiminin son paylaşımlarını okuyun.',
                'badge' => $metrics['announcements']['count'] ?? ($metrics['announcements'] ?? 0),
            ],
            [
                'label' => 'Toplantılar',
                'href' => base_url('/resident/meetings'),
                'icon' => 'fa-calendar-alt',
                'description' => 'Yaklaşan toplantıları ve gündemi inceleyin.',
                'badge' => $metrics['meetings'] ?? 0,
            ],
        ];

        $kpiCards = [
            [
                'title' => 'Ödenmemiş aidat',
                'value' => $metrics['pendingFees']['count'] ?? 0,
                'description' => $pendingOutstanding > 0
                    ? 'Toplam bakiye: ₺' . number_format($pendingOutstanding, 2, ',', '.')
                    : 'Tebrikler! Ödenmemiş aidatınız yok.',
                'icon' => 'fa-wallet',
                'href' => base_url('/resident/fees'),
                'aria' => 'Bekleyen aidat sayısı ' . (int)($metrics['pendingFees']['count'] ?? 0),
            ],
            [
                'title' => 'Açık talepler',
                'value' => $metrics['openRequests'] ?? 0,
                'description' => ($metrics['openRequests'] ?? 0) > 0
                    ? 'İşlemde olan taleplerinizi takip edin.'
                    : 'Tüm talepleriniz kapatıldı.',
                'icon' => 'fa-screwdriver-wrench',
                'href' => base_url('/resident/requests'),
                'aria' => 'Açık talep sayısı ' . (int)($metrics['openRequests'] ?? 0),
            ],
            [
                'title' => 'Yaklaşan toplantılar',
                'value' => $metrics['meetings'] ?? 0,
                'description' => ($metrics['meetings'] ?? 0) > 0
                    ? 'Gündemi kontrol ederek hazırlık yapabilirsiniz.'
                    : 'Planlanmış toplantı bulunmuyor.',
                'icon' => 'fa-calendar-star',
                'href' => base_url('/resident/meetings'),
                'aria' => 'Yaklaşan toplantı sayısı ' . (int)($metrics['meetings'] ?? 0),
            ],
        ];
    ?>

    <section class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
            <div class="absolute -left-20 top-10 h-40 w-40 rounded-full bg-primary-100 opacity-50 blur-3xl dark:bg-primary-900/20"></div>
            <div class="absolute -right-24 bottom-0 h-48 w-48 rounded-full bg-sky-100 opacity-40 blur-3xl dark:bg-sky-900/20"></div>
        </div>
        <div class="relative grid gap-8 px-6 py-8 sm:px-10 lg:grid-cols-[1.4fr_0.8fr]">
            <div class="space-y-5">
                <p class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-primary-700 dark:bg-primary-900/30 dark:text-primary-200">
                    <i class="fas fa-house-user"></i>
                    Sakin Portalı
                </p>
                <div class="space-y-2">
                    <h1 class="text-3xl font-semibold text-gray-900 dark:text-white">Merhaba, <?= e($resident['name']) ?></h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <?= htmlspecialchars($building['name'] ?? '') ?> · <?= htmlspecialchars($unit['unit_number'] ?? '') ?>
                    </p>
                </div>

                <?php if (!empty($verificationStatus)): ?>
                    <div class="flex flex-wrap gap-2" role="list" aria-label="İletişim doğrulama durumu">
                        <?php foreach ($verificationStatus as $status): ?>
                            <?php
                                $state = $status['verified']
                                    ? 'verified'
                                    : (!empty($status['pending']) ? 'pending' : 'missing');
                                $badgeLabel = match ($state) {
                                    'verified' => ($status['label'] ?? 'İletişim') . ' doğrulandı',
                                    'pending' => ($status['label'] ?? 'İletişim') . ' doğrulaması bekliyor',
                                    default => ($status['label'] ?? 'İletişim') . ' doğrulanmadı',
                                };
                                $chip = [
                                    'label' => $badgeLabel,
                                    'state' => $state,
                                    'action_label' => !empty($status['pending']) ? 'Kodu gir' : 'Doğrula',
                                    'modal_target' => 'contactVerificationModal',
                                    'modal_context' => $status['key'] ?? '',
                                    'show_action' => !$status['verified'],
                                ];
                                include __DIR__ . '/../partials/ui/resident-verification-chip.php';
                            ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="grid gap-3 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                    <div class="flex items-center gap-2 rounded-2xl border border-gray-200 bg-white/70 px-3 py-2 dark:border-gray-700 dark:bg-gray-800/60">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-200">
                            <i class="fas fa-clock"></i>
                        </span>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Son giriş</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                <?= $resident['last_login_at'] ? Utils::formatDateTime($resident['last_login_at']) : 'İlk giriş' ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 rounded-2xl border border-gray-200 bg-white/70 px-3 py-2 dark:border-gray-700 dark:bg-gray-800/60">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-200">
                            <i class="fas fa-phone"></i>
                        </span>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">İletişim</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                <?= htmlspecialchars($maskEmail($resident['email'] ?? '') ?: $maskPhone($resident['phone'] ?? '')) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-4 rounded-2xl border border-gray-200 bg-white/80 p-5 dark:border-gray-700 dark:bg-gray-900/60">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Bağlantılar</p>
                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <a href="<?= base_url('/resident/fees') ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 font-medium text-primary-600 transition hover:border-primary-200 hover:bg-primary-50 dark:border-gray-700 dark:text-primary-300 dark:hover:bg-primary-900/20">
                            <i class="fas fa-credit-card"></i>
                            Aidatlarım
                        </a>
                        <a href="<?= base_url('/resident/requests') ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 font-medium text-primary-600 transition hover:border-primary-200 hover:bg-primary-50 dark:border-gray-700 dark:text-primary-300 dark:hover:bg-primary-900/20">
                            <i class="fas fa-screwdriver"></i>
                            Taleplerim
                        </a>
                    </div>
                </div>
                <div class="rounded-2xl border border-primary-100 bg-primary-50/80 p-4 text-xs text-primary-700 dark:border-primary-900/40 dark:bg-primary-900/10 dark:text-primary-200">
                    <p class="font-semibold">Hızlı ipucu</p>
                    <p class="mt-2 leading-5">İletişim doğrulamalarınızı tamamlayarak duyuru ve bilgilendirmeleri kaçırmazsınız. Bir tık ile kod talep edebilirsiniz.</p>
                </div>
            </aside>
        </div>
    </section>

    <?php if (!empty($onboardingCards)): ?>
        <section class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900/70" aria-label="Bana özel öneriler">
            <details class="group" <?= count($onboardingCards) <= 2 ? 'open' : '' ?>>
                <summary class="flex cursor-pointer items-center justify-between gap-4 text-sm font-semibold text-gray-700 transition hover:text-primary-600 dark:text-gray-200 dark:hover:text-primary-300">
                    <span class="inline-flex items-center gap-2">
                        <i class="fas fa-compass text-primary-500"></i>
                        Yapılacak öneriler (<?= count($onboardingCards) ?>)
                    </span>
                    <span class="flex items-center gap-2 text-xs font-medium text-gray-500 group-open:rotate-180 dark:text-gray-400">
                        <i class="fas fa-chevron-down"></i>
                        <span>Listeyi <?= count($onboardingCards) <= 2 ? 'gizle' : 'aç' ?></span>
                    </span>
                </summary>
                <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <?php foreach ($onboardingCards as $card): ?>
                        <?php
                            $requiresAction = !empty($card['requires_action']);
                            $cta = $card['cta'] ?? null;
                        ?>
                        <article class="rounded-2xl border border-gray-200 bg-white/90 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start gap-4">
                                <span class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-200">
                                    <i class="fas <?= htmlspecialchars($card['icon'] ?? 'fa-circle-info') ?> text-lg"></i>
                                </span>
                                <div class="flex-1 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                                            <?= htmlspecialchars($card['title'] ?? '') ?>
                                        </h2>
                                        <?php if (!$requiresAction): ?>
                                            <span class="inline-flex items-center gap-1 rounded-full border border-emerald-200 px-2 py-0.5 text-[11px] font-semibold text-emerald-600 dark:border-emerald-900/50 dark:text-emerald-300">
                                                <i class="fas fa-circle-check"></i>
                                                Tamamlandı
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm leading-6 text-gray-600 dark:text-gray-400">
                                        <?= htmlspecialchars($card['description'] ?? '') ?>
                                    </p>
                                    <?php if ($cta): ?>
                                        <div class="pt-2">
                                            <?php if (($cta['type'] ?? 'link') === 'modal'): ?>
                                                <button type="button"
                                                        data-modal-target="<?= htmlspecialchars($cta['target'] ?? 'contactVerificationModal') ?>"
                                                        class="inline-flex items-center gap-2 rounded-full border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700 transition hover:bg-primary-100 dark:border-primary-800 dark:bg-primary-900/20 dark:text-primary-200">
                                                    <i class="fas fa-shield-halved text-[11px]"></i>
                                                    <?= htmlspecialchars($cta['label'] ?? 'Detay') ?>
                                                </button>
                                            <?php elseif (($cta['type'] ?? 'link') === 'link'): ?>
                                                <a href="<?= htmlspecialchars($cta['url'] ?? '#') ?>"
                                                   class="inline-flex items-center gap-2 text-xs font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-300 dark:hover:text-primary-200">
                                                    <?= htmlspecialchars($cta['label'] ?? 'Detay') ?>
                                                    <i class="fas fa-arrow-right text-[10px]"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </details>
        </section>
    <?php endif; ?>

    <section class="grid grid-cols-1 gap-4 lg:grid-cols-3" aria-label="Kritik göstergeler" aria-live="polite">
        <?php foreach ($kpiCards as $card): ?>
            <?php
                $title = $card['title'];
                $value = (string)$card['value'];
                $description = $card['description'];
                $icon = $card['icon'];
                $href = $card['href'];
                $ariaLabel = $card['aria'];
                include __DIR__ . '/../partials/ui/resident-kpi-card.php';
            ?>
        <?php endforeach; ?>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2" aria-label="Destekleyici göstergeler">
        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-200">
                    <i class="fas fa-bullhorn"></i>
                </span>
                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Yeni duyurular</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white"><?= $metrics['announcements'] ?? 0 ?></p>
                    <p class="text-xs text-gray-600 dark:text-gray-300">
                        <?= ($metrics['announcements'] ?? 0) > 0
                            ? 'Site yönetiminden yeni paylaşımlar var.'
                            : 'Yeni duyuru yok, arşivi inceleyebilirsiniz.' ?>
                    </p>
                </div>
            </div>
        </article>
        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-200">
                    <i class="fas fa-shield-check"></i>
                </span>
                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Son giriş</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">
                        <?= $resident['last_login_at'] ? Utils::formatDateTime($resident['last_login_at']) : 'İlk giriş' ?>
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-300">Paylaşılan cihazları kullanıyorsanız çıkış yapmayı unutmayın.</p>
                </div>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2" aria-label="Son hareketler">
        <article class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <header class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Son aidatlar</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Ödemelerinizi buradan takip edebilirsiniz.</p>
                </div>
                <a href="<?= base_url('/resident/fees') ?>" class="text-xs font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-300">
                    Tümü
                </a>
            </header>
            <div class="p-6">
                <?php if (empty($recentFees)): ?>
                    <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">Henüz aidat kaydı bulunmamaktadır.</p>
                <?php else: ?>
                    <ul class="space-y-4">
                        <?php foreach ($recentFees as $fee): ?>
                            <?php
                                $statusLabel = match ($fee['status']) {
                                    'paid' => 'Ödendi',
                                    'overdue' => 'Gecikmiş',
                                    default => 'Bekliyor'
                                };
                                $statusIcon = match ($fee['status']) {
                                    'paid' => 'fa-circle-check',
                                    'overdue' => 'fa-triangle-exclamation',
                                    default => 'fa-hourglass-half'
                                };
                                $statusClass = match ($fee['status']) {
                                    'paid' => 'border-emerald-200 text-emerald-700 dark:border-emerald-800 dark:text-emerald-300',
                                    'overdue' => 'border-rose-200 text-rose-700 dark:border-rose-800 dark:text-rose-300',
                                    default => 'border-amber-200 text-amber-700 dark:border-amber-800 dark:text-amber-300'
                                };
                            ?>
                            <li class="flex items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white"><?= e($fee['fee_name']) ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= e($fee['period']) ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900 dark:text-white">₺<?= number_format($fee['total_amount'], 2) ?></p>
                                    <span class="mt-1 inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold <?= $statusClass ?>">
                                        <i class="fas <?= $statusIcon ?>" aria-hidden="true"></i>
                                        <span><?= $statusLabel ?></span>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <header class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Açık talepler</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Bakım ve destek taleplerinizin durumu.</p>
                </div>
                <a href="<?= base_url('/resident/requests') ?>" class="text-xs font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-300">
                    Tümü
                </a>
            </header>
            <div class="p-6">
                <?php if (empty($pendingRequests)): ?>
                    <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">Açık talep bulunmamaktadır.</p>
                <?php else: ?>
                    <ul class="space-y-4">
                        <?php foreach ($pendingRequests as $request): ?>
                            <?php
                                $priorityLabel = match ($request['priority']) {
                                    'urgent' => 'Acil',
                                    'high' => 'Yüksek',
                                    'low' => 'Düşük',
                                    default => ucfirst($request['priority'] ?? 'normal')
                                };
                                $priorityIcon = match ($request['priority']) {
                                    'urgent' => 'fa-fire',
                                    'high' => 'fa-up-long',
                                    'low' => 'fa-arrow-down',
                                    default => 'fa-circle-dot'
                                };
                                $priorityClass = match ($request['priority']) {
                                    'urgent' => 'border-rose-200 text-rose-700 dark:border-rose-800 dark:text-rose-300',
                                    'high' => 'border-amber-200 text-amber-700 dark:border-amber-800 dark:text-amber-300',
                                    'low' => 'border-blue-200 text-blue-700 dark:border-blue-800 dark:text-blue-300',
                                    default => 'border-gray-200 text-gray-600 dark:border-gray-700 dark:text-gray-300'
                                };
                            ?>
                            <li class="flex items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white"><?= e($request['subject']) ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= date('d.m.Y', strtotime($request['created_at'])) ?></p>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold <?= $priorityClass ?>">
                                    <i class="fas <?= $priorityIcon ?>" aria-hidden="true"></i>
                                    <span><?= $priorityLabel ?></span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </article>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800" aria-label="Hızlı işlemler">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Hızlı işlemler</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Sık kullanılan adımlara tek dokunuşla ulaşın.</p>
            </div>
        </div>
        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <?php foreach ($quickActions as $action): ?>
                <?php $actionData = $action; include __DIR__ . '/../partials/ui/resident-quick-action.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Recent Announcements -->
    <?php if (!empty($announcements)): ?>
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Son Duyurular</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <?php foreach (array_slice($announcements, 0, 3) as $announcement): ?>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 dark:text-white"><?= e($announcement['title']) ?></h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    <?= htmlspecialchars(Utils::truncateUtf8($announcement['content'] ?? '', 100)) ?>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    <?= date('d.m.Y H:i', strtotime($announcement['publish_date'])) ?>
                                </p>
                            </div>
                            <?php if ($announcement['priority'] > 0): ?>
                                <span class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    Önemli
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4">
                <a href="<?= base_url('/resident/announcements') ?>" class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                    Tüm duyuruları görüntüle →
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div id="contactVerificationModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="contact-verification-title">
        <div data-modal-overlay class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>
        <div class="relative mx-auto flex min-h-full items-start justify-center p-4 sm:p-8">
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-gray-900">
                <header class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div>
                        <h2 id="contact-verification-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                            İletişim Doğrulaması
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Güvenliği artırmak için e-posta ve telefon doğrulamalarını tamamlayın.
                        </p>
                    </div>
                    <button type="button" data-modal-close class="rounded-full p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:text-gray-400 dark:hover:bg-gray-800">
                        <span class="sr-only">Kapat</span>
                        <i class="fas fa-xmark text-lg"></i>
                    </button>
                </header>
                <div class="space-y-4 px-6 py-6">
                    <?php foreach ($verificationStatus as $status): ?>
                        <?php
                            $pending = $pendingVerificationMap[$status['key']] ?? null;
                            $state = $status['verified']
                                ? 'verified'
                                : ($pending ? 'pending' : 'missing');

                            $stateBadge = match ($state) {
                                'verified' => ['label' => 'Doğrulandı', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200', 'icon' => 'fa-circle-check'],
                                'pending'  => ['label' => 'Doğrulama bekliyor', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200', 'icon' => 'fa-hourglass-half'],
                                default    => ['label' => 'Doğrulanmadı', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200', 'icon' => 'fa-circle-exclamation'],
                            };

                            $contactDisplay = $status['key'] === 'phone'
                                ? Utils::formatPhone($status['contact'] ?? '')
                                : ($status['contact'] ?? '');
                        ?>
                        <section class="rounded-2xl border border-gray-200 bg-gray-50 p-5 transition dark:border-gray-700 dark:bg-gray-800" data-contact-section="<?= e($status['key']) ?>">
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        <?= e($status['label']) ?> doğrulaması
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <?= htmlspecialchars($contactDisplay ?: 'Bilgi bulunmuyor') ?>
                                    </p>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold <?= $stateBadge['class'] ?>">
                                    <i class="fas <?= $stateBadge['icon'] ?>"></i>
                                    <?= e($stateBadge['label']) ?>
                                </span>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                                <?php if ($state === 'verified'): ?>
                                    <p>
                                        <?= $status['key'] === 'email' ? 'E-posta doğrulaması' : 'Telefon doğrulaması' ?>
                                        <?= $status['last_verified_at'] ? ' ' . Utils::formatDateTime($status['last_verified_at']) . ' tarihinde ' : ' ' ?>
                                        başarıyla tamamlandı. Bilgileriniz güncel.
                                    </p>
                                <?php elseif ($state === 'pending' && $pending): ?>
                                    <p>
                                        Doğrulama kodu gönderildi:
                                        <strong>
                                            <?= $status['key'] === 'email'
                                                ? htmlspecialchars($maskEmail($pending['new_value'] ?? ''))
                                                : htmlspecialchars(Utils::formatPhone($pending['new_value'] ?? '')) ?>
                                        </strong>.
                                        Kod <span class="font-semibold"><?= Utils::formatDateTime($pending['expires_at'] ?? '') ?></span> tarihine kadar geçerlidir.
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="<?= base_url('/resident/profile/resend') ?>">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="verification_id" value="<?= (int)($pending['id'] ?? 0) ?>">
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-primary-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                                                <i class="fas fa-envelope"></i>
                                                Kodu yeniden gönder
                                            </button>
                                        </form>
                                        <a href="<?= base_url('/resident/profile') ?>#pending-verifications"
                                           class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:border-primary-400 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:border-gray-600 dark:text-gray-200 dark:hover:text-primary-300 dark:focus:ring-offset-gray-900">
                                            <i class="fas fa-keyboard"></i>
                                            Kodu gir
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <p>
                                        Şu anda doğrulama yapılmamış. Kod göndererek hesabınızı koruma altına alabilirsiniz.
                                    </p>
                                    <form method="POST" action="<?= base_url('/resident/profile/request') ?>" class="flex flex-wrap gap-2">
                                        <?= CSRF::field() ?>
                                        <input type="hidden" name="type" value="<?= e($status['key']) ?>">
                                        <input type="hidden" name="redirect" value="dashboard">
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-primary-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                                            <i class="fas fa-paper-plane"></i>
                                            Doğrulama kodu gönder
                                        </button>
                                        <a href="<?= base_url('/resident/profile') ?>"
                                           class="inline-flex items-center gap-2 text-xs font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-300">
                                            <i class="fas fa-user-shield"></i>
                                            Profilden güncelle
                                        </a>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('contactVerificationModal');
    if (!modal) {
        return;
    }

    const overlay = modal.querySelector('[data-modal-overlay]');
    const closeButtons = modal.querySelectorAll('[data-modal-close]');
    const openButtons = document.querySelectorAll('[data-modal-target="contactVerificationModal"]');
    const body = document.body;
    const quickActionLinks = document.querySelectorAll('[data-quick-action]');

    const pushTelemetry = (eventName, details = {}) => {
        window.appTelemetry = window.appTelemetry || [];
        window.appTelemetry.push({
            event: eventName,
            timestamp: Date.now(),
            ...details,
        });
    };

    const highlightSection = (context) => {
        const sections = modal.querySelectorAll('[data-contact-section]');
        sections.forEach((section) => {
            const isActive = context && section.dataset.contactSection === context;
            section.classList.toggle('ring-2', isActive);
            section.classList.toggle('ring-primary-500', isActive);
            section.classList.toggle('bg-primary-50', isActive);
            section.classList.toggle('dark:bg-primary-900/20', isActive);
            section.classList.toggle('bg-gray-50', !isActive);
            section.classList.toggle('dark:bg-gray-800', !isActive);
            if (isActive) {
                section.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    };

    const openModal = (context = '') => {
        modal.classList.remove('hidden');
        body.classList.add('overflow-hidden');
        highlightSection(context);
        pushTelemetry('resident_verification_modal_open', { context });
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        body.classList.remove('overflow-hidden');
        highlightSection('');
    };

    openButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const context = btn.dataset.modalContext || '';
            openModal(context);
            pushTelemetry('resident_verification_cta', { context });
        });
    });

    closeButtons.forEach((btn) => btn.addEventListener('click', closeModal));
    if (overlay) {
        overlay.addEventListener('click', closeModal);
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    quickActionLinks.forEach((link) => {
        link.addEventListener('click', () => {
            const label = link.dataset.actionLabel || link.textContent.trim();
            pushTelemetry('resident_quick_action', { label });
        });
    });
})();
</script>
