<?php
    $resident = $resident ?? [];
    $flash = $flash ?? [];
    $unit = $unit ?? [];
    $building = $building ?? [];
    $notifyEmail = (int)($resident['notify_email'] ?? 1) === 1;
    $notifySms = (int)($resident['notify_sms'] ?? 0) === 1;
    $phoneDisplay = Utils::formatPhone($resident['phone'] ?? '');
    $secondaryPhoneDisplay = Utils::formatPhone($resident['secondary_phone'] ?? '');
    $pendingVerifications = $pendingVerifications ?? [];
    $notificationCategories = $notificationCategories ?? [];
    $notificationPreferences = $notificationPreferences ?? [];

    $pendingByType = ['email' => null, 'phone' => null];
    foreach ($pendingVerifications as $verification) {
        if (($verification['status'] ?? '') === 'pending') {
            $pendingByType[$verification['verification_type']] = $verification;
        }
    }

    $maskEmail = static function (?string $email): string {
        if (!$email || strpos($email, '@') === false) {
            return '***@***';
        }
        [$local, $domain] = explode('@', $email, 2);
        $local = substr($local, 0, 1) . str_repeat('*', max(strlen($local) - 1, 1));
        $domainParts = explode('.', $domain);
        $domainMasked = implode('.', array_map(static function ($part) {
            return substr($part, 0, 1) . str_repeat('*', max(strlen($part) - 1, 1));
        }, $domainParts));
        return $local . '@' . $domainMasked;
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
        return '+** ' . str_repeat('*', max(strlen($digits) - 4, 3)) . $suffix;
    };
?>

<div class="max-w-3xl mx-auto space-y-6" aria-labelledby="resident-profile-heading">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 id="resident-profile-heading" class="text-2xl font-bold text-gray-900 dark:text-white">Profilim</h1>
            <p class="text-gray-600 dark:text-gray-400">İletişim bilgilerinizi ve tercihlerinizi güncelleyin.</p>
        </div>
        <a href="<?= base_url('/resident/dashboard') ?>"
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg">
            <i class="fas fa-arrow-left mr-2"></i>
            Ana Sayfa
        </a>
    </div>

    <?php if (!empty($flash['error'])): ?>
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-200 rounded-lg p-4" role="alert" aria-live="assertive" aria-atomic="true">
            <p class="text-sm font-medium"><?= e($flash['error']) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($flash['success'])): ?>
        <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-200 rounded-lg p-4" role="status" aria-live="polite" aria-atomic="true">
            <p class="text-sm font-medium"><?= e($flash['success']) ?></p>
        </div>
    <?php endif; ?>

    <!-- Personal Information -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6">
        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-id-card text-primary-500"></i>
                Kişisel Bilgiler
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Bu bilgiler yönetimle iletişime geçilmesi ve bildirimlerin gönderilmesi için kullanılır.
            </p>
        </div>

        <form method="POST" class="space-y-6" aria-describedby="contact-hint">
            <?= CSRF::field() ?>
            <input type="hidden" name="form_context" value="contact">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ad Soyad</label>
                    <input type="text" id="name" name="name"
                           value="<?= htmlspecialchars($resident['name'] ?? '') ?>"
                           required
                           class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bağlı Olduğunuz Daire</label>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-200">
                        <?= htmlspecialchars($unit['unit_number'] ?? 'Belirlenmedi') ?>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <?= htmlspecialchars($building['name'] ?? '') ?>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">E-posta</label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($resident['email'] ?? '') ?>"
                           required
                           aria-describedby="email-help email-error"
                           aria-invalid="false"
                           class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white">
                    <p id="email-help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Oturum ve bildirimler için kullanılacak. Değişiklikler SMS/E-posta kodu ile onaylanır.
                    </p>
                    <p id="email-error" class="mt-1 text-xs text-red-600 dark:text-red-400 hidden" aria-live="polite"></p>
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefon</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?= e($phoneDisplay) ?>"
                           inputmode="tel"
                           pattern="^(\+?\d[\d\s]{7,})$"
                           class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                           aria-describedby="phone-help phone-error"
                           aria-invalid="false"
                           placeholder="+90 5XX XXX XX XX">
                    <p id="phone-help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">SMS bildirimleri için gereklidir. Değişiklikler kod ile doğrulanır.</p>
                    <p id="phone-error" class="mt-1 text-xs text-red-600 dark:text-red-400 hidden" aria-live="polite"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="secondary_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        İkincil E-posta <span class="text-xs text-gray-400 dark:text-gray-500">(opsiyonel)</span>
                    </label>
                    <input type="email" id="secondary_email" name="secondary_email"
                           value="<?= htmlspecialchars($resident['secondary_email'] ?? '') ?>"
                           class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                           placeholder="ornek@ikinciadres.com">
                </div>
                <div>
                    <label for="secondary_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        İkincil Telefon <span class="text-xs text-gray-400 dark:text-gray-500">(opsiyonel)</span>
                    </label>
                    <input type="tel" id="secondary_phone" name="secondary_phone"
                           value="<?= e($secondaryPhoneDisplay) ?>"
                           class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                           placeholder="+90 5XX XXX XX XX">
                </div>
            </div>

            <fieldset class="space-y-3" id="contact-hint">
                <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    Bildirim Tercihleri
                    <span class="text-xs text-gray-400 dark:text-gray-500">(dilediğiniz zaman değiştirebilirsiniz)</span>
                </legend>
                <div class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                    <div>
                        <label for="notify_email" class="text-sm font-medium text-gray-700 dark:text-gray-200">E-posta Bildirimleri</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Aidat hatırlatmaları ve duyurular e-posta olarak gelir.</p>
                    </div>
                    <input type="checkbox" id="notify_email" name="notify_email" value="1"
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                           <?= $notifyEmail ? 'checked' : '' ?>>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                    <div>
                        <label for="notify_sms" class="text-sm font-medium text-gray-700 dark:text-gray-200">SMS Bildirimleri</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Acil duyurular ve doğrulama kodları SMS ile iletilir.</p>
                    </div>
                    <input type="checkbox" id="notify_sms" name="notify_sms" value="1"
                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                           <?= $notifySms ? 'checked' : '' ?>>
                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/40 space-y-3">
                    <div class="flex items-start justify-between flex-wrap gap-3">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Kategori Bazlı Tercihler</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Global tercihlere ek olarak hangi bildirimleri almak istediğinizi seçin.
                            </p>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Global tercih kapalıysa ilgili seçenekler devre dışı kalır.
                        </span>
                    </div>

                    <div class="space-y-3">
                        <?php foreach ($notificationCategories as $key => $meta): ?>
                            <?php
                                $categoryPref = $notificationPreferences[$key] ?? ['email' => $meta['default_email'] ?? true, 'sms' => $meta['default_sms'] ?? false];
                                $emailChecked = !empty($categoryPref['email']);
                                $smsChecked = !empty($categoryPref['sms']);
                                $supportsSms = !empty($meta['supports_sms']);
                                $emailDisabled = !$notifyEmail;
                                $smsDisabled = !$notifySms || !$supportsSms;
                            ?>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-start rounded-md bg-white dark:bg-gray-800/40 p-3 border border-gray-200 dark:border-gray-700" data-pref-row="<?= e($key) ?>" data-supports-sms="<?= $supportsSms ? 'true' : 'false' ?>">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100"><?= e($meta['label']) ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= e($meta['description']) ?></p>
                                </div>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input type="checkbox"
                                           name="pref_email_<?= e($key) ?>"
                                           value="1"
                                           data-pref-email="<?= e($key) ?>"
                                           class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                                           <?= $emailChecked ? 'checked' : '' ?>
                                           <?= $emailDisabled ? 'disabled aria-disabled="true"' : '' ?>>
                                    <span>E-posta</span>
                                </label>
                                <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <?php if ($supportsSms): ?>
                                        <input type="checkbox"
                                               name="pref_sms_<?= e($key) ?>"
                                               value="1"
                                               data-pref-sms="<?= e($key) ?>"
                                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                                               <?= $smsChecked ? 'checked' : '' ?>
                                               <?= $smsDisabled ? 'disabled aria-disabled="true"' : '' ?>>
                                        <span>SMS</span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">SMS desteği bulunmuyor</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </fieldset>

            <div class="flex items-center justify-end gap-3">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    <i class="fas fa-save mr-2"></i>
                    Kaydet
                </button>
            </div>
        </form>
    </div>

    <?php if ($pendingByType['email'] || $pendingByType['phone']): ?>
        <div id="pending-verifications" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 text-amber-900 dark:text-amber-100 rounded-lg p-6 space-y-4" aria-live="polite">
            <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide">
                <i class="fas fa-shield-halved"></i>
                Bekleyen Doğrulamalar
            </div>

            <?php foreach (['email', 'phone'] as $type): ?>
                <?php if ($pendingByType[$type]): ?>
                    <?php $pending = $pendingByType[$type]; ?>
                    <div class="rounded-lg bg-white/70 dark:bg-slate-900/50 border border-amber-200 dark:border-amber-700 p-4 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                    <?= $type === 'email' ? 'Yeni E-posta' : 'Yeni Telefon' ?>:
                                    <span class="font-semibold">
                                        <?= htmlspecialchars($type === 'email' ? $maskEmail($pending['new_value']) : $maskPhone($pending['new_value'])) ?>
                                    </span>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Kod geçerlilik süresi: <?= Utils::formatDateTime($pending['expires_at']) ?>
                                </p>
                            </div>
                            <form method="POST" action="<?= base_url('/resident/profile/resend') ?>">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="verification_id" value="<?= (int)$pending['id'] ?>">
                                <button type="submit" class="text-xs font-medium text-primary-700 hover:text-primary-500">
                                    Kodu yeniden gönder
                                </button>
                            </form>
                        </div>

                        <form method="POST" action="<?= base_url('/resident/profile/verify') ?>" class="space-y-2" aria-label="<?= $type === 'email' ? 'E-posta doğrulama' : 'Telefon doğrulama' ?>">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="verification_id" value="<?= (int)$pending['id'] ?>">
                            <label for="code-<?= (int)$pending['id'] ?>" class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                Doğrulama kodunu girin
                            </label>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <input id="code-<?= (int)$pending['id'] ?>" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6"
                                       class="sm:w-32 rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                                       placeholder="000000" required aria-required="true">
                                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-400">
                                    Doğrula
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Preferences Summary -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-bell text-primary-500"></i> Bildirim Tercihleri
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Yönetim duyuruları e-posta ve SMS ile gönderilebilir. Bekleyen tercihlerinizi doğrulamak için yöneticiyle iletişime geçebilirsiniz.
                </p>
            </div>
            <span class="inline-flex items-center gap-2 px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-200">
                <i class="fas fa-shield-check"></i>
                OTP ile korunan giriş
            </span>
        </div>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-600 dark:text-gray-300">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/40">
                <dt class="font-semibold text-gray-800 dark:text-gray-100">E-posta</dt>
                <dd class="mt-2 space-y-1">
                    <p><span class="font-medium">Birincil:</span> <?= htmlspecialchars($resident['email'] ?? '—') ?></p>
                    <p><span class="font-medium">İkincil:</span> <?= htmlspecialchars($resident['secondary_email'] ?? '—') ?></p>
                    <p><span class="font-medium">Aktif:</span> <?= $notifyEmail ? 'Evet' : 'Hayır' ?></p>
                </dd>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/40">
                <dt class="font-semibold text-gray-800 dark:text-gray-100">SMS</dt>
                <dd class="mt-2 space-y-1">
                    <p><span class="font-medium">Birincil:</span> <?= htmlspecialchars($phoneDisplay ?: '—') ?></p>
                    <p><span class="font-medium">İkincil:</span> <?= htmlspecialchars($secondaryPhoneDisplay ?: '—') ?></p>
                    <p><span class="font-medium">Aktif:</span> <?= $notifySms ? 'Evet' : 'Hayır' ?></p>
                </dd>
            </div>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/40 md:col-span-2">
                <dt class="font-semibold text-gray-800 dark:text-gray-100">Kategori Bazlı Tercihler</dt>
                <dd class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <?php foreach ($notificationCategories as $key => $meta): ?>
                        <?php
                            $pref = $notificationPreferences[$key] ?? ['email' => $meta['default_email'] ?? true, 'sms' => $meta['default_sms'] ?? false];
                            $emailStatus = !empty($pref['email']) && $notifyEmail ? 'Açık' : 'Kapalı';
                            $smsStatus = !empty($meta['supports_sms'])
                                ? ((!empty($pref['sms']) && $notifySms) ? 'Açık' : 'Kapalı')
                                : '—';
                        ?>
                        <div class="flex items-start justify-between rounded-md border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800/60 p-3">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100"><?= e($meta['label']) ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><?= e($meta['description']) ?></p>
                            </div>
                            <div class="text-xs text-right text-gray-600 dark:text-gray-300 space-y-1">
                                <p>E-posta: <span class="font-semibold"><?= $emailStatus ?></span></p>
                                <p>SMS: <span class="font-semibold"><?= $smsStatus ?></span></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </dd>
            </div>
        </dl>
    </div>

    <!-- Password -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-4">
        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-lock text-primary-500"></i> Şifre Yönetimi
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Güvenliği artırmak için güçlü ve benzersiz bir şifre belirleyin. Şifrenizi kimseyle paylaşmayın.
            </p>
        </div>

        <form method="POST" class="space-y-6" aria-describedby="password-hint">
            <?= CSRF::field() ?>
            <input type="hidden" name="form_context" value="password">

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mevcut Şifre</label>
                <input type="password" id="current_password" name="current_password"
                       class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                       autocomplete="current-password"
                       placeholder="<?= !empty($resident['password_set_at']) ? 'Mevcut şifrenizi girin' : 'İlk kez şifre oluşturuyorsanız boş bırakabilirsiniz' ?>">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Yeni Şifre</label>
                    <input type="password" id="new_password" name="new_password"
                           class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                           autocomplete="new-password"
                           placeholder="En az 8 karakter">
                    <p id="password-strength" class="mt-1 text-xs text-gray-500 dark:text-gray-400" aria-live="polite">Şifre gücü: —</p>
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Yeni Şifre (Tekrar)</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                           autocomplete="new-password"
                           placeholder="Yeni şifrenizi tekrar girin">
                </div>
            </div>

            <p id="password-hint" class="text-xs text-gray-500 dark:text-gray-400">
                Güçlü parola önerisi: En az 12 karakter, büyük/küçük harf, sayı ve sembol içeren kombinasyonlar kullanın.
            </p>

            <div class="flex items-center justify-end gap-3">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    <i class="fas fa-key mr-2"></i>
                    Şifreyi Güncelle
                </button>
            </div>
        </form>
    </div>

    <!-- Security Info -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-lock mt-1"></i>
            <div class="space-y-2 text-sm">
                <p class="font-medium">Güvenlik İpuçları</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Oturumu paylaşılan cihazlarda kapatmayı unutmayın.</li>
                    <li>Şüpheli bir hareket fark ederseniz yönetiminizle iletişime geçin.</li>
                    <li>Güncel telefon ve e-posta bilgileri doğrulama süreçleri için gereklidir.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const contactForm = document.querySelector('form input[name="form_context"][value="contact"]')?.closest('form');
        if (!contactForm) {
            return;
        }

        const emailInput = contactForm.querySelector('#email');
        const phoneInput = contactForm.querySelector('#phone');
        const emailError = document.querySelector('#email-error');
        const phoneError = document.querySelector('#phone-error');
        const notifyEmail = contactForm.querySelector('#notify_email');
        const notifySms = contactForm.querySelector('#notify_sms');
        const prefRows = contactForm.querySelectorAll('[data-pref-row]');

        const preferenceState = new Map();

        const captureState = (checkbox) => {
            if (!checkbox || !checkbox.name) {
                return;
            }
            preferenceState.set(checkbox.name, checkbox.checked);
        };

        const restoreState = (checkbox) => {
            if (!checkbox || !checkbox.name) {
                return;
            }
            if (preferenceState.has(checkbox.name)) {
                checkbox.checked = preferenceState.get(checkbox.name);
            }
        };

        const setDisabled = (element, disabled) => {
            element.disabled = disabled;
            element.setAttribute('aria-disabled', disabled.toString());
        };

        const togglePrefState = () => {
            const emailEnabled = notifyEmail ? notifyEmail.checked : true;
            const smsEnabled = notifySms ? notifySms.checked : true;

            prefRows.forEach((row) => {
                const emailCheckbox = row.querySelector('[data-pref-email]');
                const smsCheckbox = row.querySelector('[data-pref-sms]');
                if (emailCheckbox) {
                    if (!emailEnabled) {
                        captureState(emailCheckbox);
                    }
                    setDisabled(emailCheckbox, !emailEnabled);
                    if (emailEnabled) {
                        restoreState(emailCheckbox);
                    }
                    const emailContainer = emailCheckbox.closest('label');
                    if (emailContainer) {
                        emailContainer.classList.toggle('opacity-50', !emailEnabled);
                    }
                }
                if (smsCheckbox) {
                    const supportsSms = row.getAttribute('data-supports-sms') === 'true';
                    const shouldDisable = !smsEnabled || !supportsSms;
                    if (shouldDisable) {
                        captureState(smsCheckbox);
                    }
                    setDisabled(smsCheckbox, shouldDisable);
                    if (!shouldDisable) {
                        restoreState(smsCheckbox);
                    }
                    const smsContainer = smsCheckbox.closest('div');
                    if (smsContainer) {
                        smsContainer.classList.toggle('opacity-50', shouldDisable);
                    }
                }
            });
        };

        const setFieldError = (input, messageEl, message) => {
            if (!input || !messageEl) {
                return;
            }
            if (message) {
                messageEl.textContent = message;
                messageEl.classList.remove('hidden');
                input.setAttribute('aria-invalid', 'true');
                input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
            } else {
                messageEl.textContent = '';
                messageEl.classList.add('hidden');
                input.setAttribute('aria-invalid', 'false');
                input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
            }
        };

        const validateEmail = () => {
            if (!emailInput) {
                return true;
            }
            const value = emailInput.value.trim();
            if (!value) {
                setFieldError(emailInput, emailError, 'E-posta adresi boş olamaz.');
                return false;
            }
            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            setFieldError(emailInput, emailError, isValid ? '' : 'Lütfen geçerli bir e-posta adresi girin.');
            return isValid;
        };

        const validatePhone = () => {
            if (!phoneInput) {
                return true;
            }
            const value = phoneInput.value.trim();
            if (!value) {
                setFieldError(phoneInput, phoneError, 'Telefon numarası boş olamaz.');
                return false;
            }
            const digits = value.replace(/[^\d]/g, '');
            const isValid = digits.length >= 9;
            setFieldError(phoneInput, phoneError, isValid ? '' : 'Lütfen ülke kodu ile birlikte geçerli bir telefon girin.');
            return isValid;
        };

        emailInput?.addEventListener('input', validateEmail);
        emailInput?.addEventListener('blur', validateEmail);
        phoneInput?.addEventListener('input', validatePhone);
        phoneInput?.addEventListener('blur', validatePhone);

        notifyEmail?.addEventListener('change', togglePrefState);
        notifySms?.addEventListener('change', togglePrefState);

        prefRows.forEach((row) => {
            const emailCheckbox = row.querySelector('[data-pref-email]');
            const smsCheckbox = row.querySelector('[data-pref-sms]');
            if (emailCheckbox) {
                captureState(emailCheckbox);
                emailCheckbox.addEventListener('change', () => captureState(emailCheckbox));
            }
            if (smsCheckbox) {
                captureState(smsCheckbox);
                smsCheckbox.addEventListener('change', () => captureState(smsCheckbox));
            }
        });

        contactForm.addEventListener('submit', (event) => {
            const emailValid = validateEmail();
            const phoneValid = validatePhone();
            if (!emailValid || !phoneValid) {
                event.preventDefault();
            }
        });

        togglePrefState();
    })();
</script>

<script>
    (function () {
        const passwordInput = document.querySelector('#new_password');
        const strengthLabel = document.querySelector('#password-strength');
        if (!passwordInput || !strengthLabel) {
            return;
        }

        const evaluateStrength = (value) => {
            let score = 0;
            if (value.length >= 8) score++;
            if (/[A-Z]/.test(value)) score++;
            if (/[a-z]/.test(value)) score++;
            if (/\d/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value)) score++;

            if (score <= 1) {
                return {label: 'Zayıf', className: 'text-red-600 dark:text-red-400'};
            }
            if (score <= 3) {
                return {label: 'Orta', className: 'text-amber-600 dark:text-amber-300'};
            }
            return {label: 'Güçlü', className: 'text-emerald-600 dark:text-emerald-300'};
        };

        passwordInput.addEventListener('input', () => {
            const {label, className} = evaluateStrength(passwordInput.value);
            strengthLabel.textContent = `Şifre gücü: ${label}`;
            strengthLabel.className = `mt-1 text-xs ${className}`;
        });
    })();
</script>
