<?php
$flash = $flash ?? [];
$flow = $flowState ?? ['step' => 'phone'];
$step = $flow['step'] ?? 'phone';
$phoneRaw = $flow['phone'] ?? '';
$phoneDisplay = $flow['phone_display'] ?? ($phoneRaw ? Utils::formatPhone($phoneRaw) : '');
$maskedContact = $flow['masked_contact'] ?? null;
$expiresAt = $flow['expires_at'] ?? null;
$expiresText = $expiresAt ? Utils::formatDateTime($expiresAt, 'H:i') : null;
$resendCooldown = $flow['resend_cooldown'] ?? ResidentOtpService::RESEND_COOLDOWN_SECONDS;
$resendAvailableAt = $flow['resend_available_at'] ?? null;
$resendTimestamp = $resendAvailableAt ? strtotime($resendAvailableAt) : null;
$secondsUntilResend = $resendTimestamp ? max(0, $resendTimestamp - time()) : 0;
$phoneError = $flash['phone_error'] ?? null;
$passwordError = $flash['password_error'] ?? null;
$otpError = $flash['otp_error'] ?? null;
$setPasswordError = $flash['set_password_error'] ?? null;

$stepTitle = match ($step) {
    'password' => 'Şifrenizi girin',
    'otp' => 'Telefonunuza gönderilen kodu girin',
    'set_password' => 'Şifrenizi belirleyin',
    default => 'Telefon numaranızla giriş yapın',
};
?>
<!DOCTYPE html>
<html lang="tr" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Sakin Portalı') ?></title>
    
    <!-- Critical CSS for Login Page - Performance: Inline critical styles for faster FCP -->
    <style>
        /* Critical CSS: Above-the-fold layout and typography */
        body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;margin:0;padding:0;background:#f3f4f6;min-height:100vh}
        .min-h-screen{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:3rem 1rem}
        .flex{display:flex}
        .justify-center{justify-content:center}
        .items-center{align-items:center}
        .mb-6{margin-bottom:1.5rem}
        .block{display:block}
        .h-28{height:7rem}
        .w-28{width:7rem}
        .rounded-full{border-radius:9999px}
        .overflow-hidden{overflow:hidden}
        .h-full{height:100%}
        .w-full{width:100%}
        .object-cover{object-fit:cover}
        .grid{display:grid}
        .gap-8{gap:2rem}
        .rounded-3xl{border-radius:1.5rem}
        .bg-white{background-color:#fff}
        .p-8{padding:2rem}
        .shadow-xl{box-shadow:0 20px 25px -5px rgba(0,0,0,0.1),0 10px 10px -5px rgba(0,0,0,0.04)}
        .ring-1{box-shadow:0 0 0 1px rgba(0,0,0,0.05)}
        .ring-gray-100{box-shadow:0 0 0 1px rgba(243,244,246,1)}
        .max-w-4xl{max-width:56rem}
        .w-full{width:100%}
        @media(min-width:1024px){.lg\:grid-cols-\[1\.05fr_0\.95fr\]{grid-template-columns:1.05fr 0.95fr}.lg\:gap-12{gap:3rem}}
        @media(min-width:640px){.sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}}
    </style>
    
    <link rel="stylesheet" href="<?= Utils::asset('css/tailwind.css') ?>?v=<?= file_exists(__DIR__ . '/../../../assets/css/tailwind.css') ? filemtime(__DIR__ . '/../../../assets/css/tailwind.css') : time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full">
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 via-white to-gray-100 dark:from-gray-900 dark:via-gray-950 dark:to-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-4xl">
            <div class="flex justify-center mb-6">
                <span class="block h-28 w-28 rounded-full overflow-hidden">
                    <img src="<?= Utils::asset('img/logokureapp.png') ?>" width="120" height="120" alt="Küre Temizlik Logosu"
                         class="h-full w-full object-cover transform origin-center scale-[1.08]"
                         loading="eager" decoding="async">
                </span>
            </div>

            <div class="relative grid gap-8 rounded-3xl bg-white p-8 shadow-xl ring-1 ring-gray-100 dark:bg-gray-900 dark:ring-gray-800 lg:grid-cols-[1.05fr_0.95fr] lg:gap-12">
                <div class="flex flex-col gap-6">
                    <header class="space-y-3 text-center sm:text-left">
                        <p class="inline-flex items-center justify-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-primary-700 dark:bg-primary-900/30 dark:text-primary-200">
                            <i class="fas fa-building-user"></i>
                            Sakin Portalı
                        </p>
                        <h1 class="text-3xl font-semibold text-gray-900 dark:text-white"><?= e($stepTitle) ?></h1>
                        <p class="text-sm leading-6 text-gray-600 dark:text-gray-400">
                            Yönetim tarafından tanımlanan telefon numaranızla giriş yapabilir, ilk girişinizde hızlıca şifrenizi belirleyebilirsiniz.
                        </p>
                    </header>

                    <?php if (!empty($flash['error'])): ?>
                        <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
                            <i class="fas fa-circle-exclamation mt-0.5"></i>
                            <div><?= e($flash['error']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($flash['success'])): ?>
                        <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                            <i class="fas fa-circle-check mt-0.5"></i>
                            <div><?= e($flash['success']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($flash['info'])): ?>
                        <div class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-200">
                            <i class="fas fa-info-circle mt-0.5"></i>
                            <div><?= e($flash['info']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($step === 'phone'): ?>
                        <form method="POST"
                              action="<?= base_url('/resident/login') ?>"
                              class="space-y-6"
                              data-login-form
                              data-default-action="<?= base_url('/resident/login') ?>">
                            <?= CSRF::field() ?>
                            <div class="space-y-2">
                                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Telefon Numaranız</label>
                                <input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    value="<?= e($phoneRaw) ?>"
                                    autocomplete="tel"
                                    inputmode="tel"
                                    data-auto-focus
                                    data-phone-input
                                    data-server-invalid="<?= $phoneError ? 'true' : 'false' ?>"
                                    aria-invalid="<?= $phoneError ? 'true' : 'false' ?>"
                                    class="mt-1 block w-full rounded-xl border <?= $phoneError ? 'border-red-300 focus:border-red-500 focus:ring-red-500 dark:border-red-600' : 'border-gray-200 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700' ?> bg-white px-4 py-3 text-base text-gray-900 placeholder:text-gray-400 shadow-sm transition dark:bg-gray-800 dark:text-gray-100"
                                    placeholder="+90 5XX XXX XX XX">
                                <?php if ($phoneError): ?>
                                    <p class="mt-2 text-xs font-medium text-red-600 dark:text-red-300"><?= e($phoneError) ?></p>
                                <?php endif; ?>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 <?= $phoneError ? 'hidden' : '' ?>"
                                   data-phone-helper
                                   data-default-message="Telefon numaranızın yönetim tarafından sisteme tanımlı olması gerekir.">
                                    Telefon numaranızın yönetim tarafından sisteme tanımlı olması gerekir.
                                </p>
                                <p class="mt-2 hidden text-xs font-medium text-red-600 dark:text-red-300"
                                   data-phone-client-error
                                   aria-live="polite"></p>
                            </div>

                            <div class="space-y-3">
                                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 via-blue-500 to-sky-400 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/40 ring-1 ring-blue-500/40 transition hover:from-blue-500 hover:via-sky-400 hover:to-sky-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900">
                                    <i class="fas fa-arrow-right-to-bracket"></i>
                                    Devam Et
                                </button>
                                <button type="button"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-primary-200 bg-white py-3 text-sm font-semibold text-primary-600 shadow-sm transition hover:bg-primary-50 hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-200 focus:ring-offset-2 focus:ring-offset-white dark:border-primary-900/40 dark:bg-gray-900 dark:text-primary-200 dark:hover:bg-primary-900/10 dark:focus:ring-offset-gray-900"
                                        data-forgot-trigger
                                        data-forgot-action="<?= base_url('/resident/login/forgot') ?>">
                                    <i class="fas fa-key"></i>
                                    Şifremi Unuttum
                                </button>
                            </div>
                        </form>
                    <?php elseif ($step === 'password'): ?>
                        <div class="rounded-2xl border border-gray-200 bg-gray-50/80 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-300">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Telefon</p>
                            <p><?= htmlspecialchars($phoneDisplay ?: $phoneRaw) ?></p>
                        </div>

                        <form method="POST" action="<?= base_url('/resident/login/password') ?>" class="space-y-6">
                            <?= CSRF::field() ?>
                            <div class="space-y-2">
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Şifreniz</label>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    data-auto-focus
                                    aria-invalid="<?= $passwordError ? 'true' : 'false' ?>"
                                    class="mt-1 block w-full rounded-xl border <?= $passwordError ? 'border-red-300 focus:border-red-500 focus:ring-red-500 dark:border-red-600' : 'border-gray-200 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700' ?> bg-white px-4 py-3 text-base text-gray-900 placeholder:text-gray-400 shadow-sm transition dark:bg-gray-800 dark:text-gray-100"
                                    placeholder="••••••••">
                                <?php if ($passwordError): ?>
                                    <p class="mt-2 text-xs font-medium text-red-600 dark:text-red-300"><?= e($passwordError) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="space-y-3">
                                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary-600 via-primary-500 to-primary-400 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/40 ring-1 ring-primary-500/40 transition hover:from-primary-500 hover:via-primary-400 hover:to-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900">
                                    <i class="fas fa-arrow-right-to-bracket"></i>
                                    Giriş Yap
                                </button>
                                <button type="submit"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-primary-200 bg-white py-3 text-sm font-semibold text-primary-600 shadow-sm transition hover:bg-primary-50 hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-200 focus:ring-offset-2 focus:ring-offset-white dark:border-primary-900/40 dark:bg-gray-900 dark:text-primary-200 dark:hover:bg-primary-900/10 dark:focus:ring-offset-gray-900"
                                        formaction="<?= base_url('/resident/login/forgot') ?>"
                                        formmethod="post">
                                    <i class="fas fa-key"></i>
                                    Şifremi Unuttum
                                </button>
                            </div>
                        </form>

                        <form method="POST" action="<?= base_url('/resident/login/cancel') ?>" class="text-center">
                            <?= CSRF::field() ?>
                            <button type="submit" class="text-xs font-semibold text-gray-500 transition hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-300">
                                <i class="fas fa-rotate-left"></i>
                                Farklı bir telefon numarası dene
                            </button>
                        </form>
                    <?php elseif ($step === 'otp'): ?>
                        <div class="rounded-2xl border border-primary-200 bg-primary-50/80 p-4 text-sm text-primary-700 dark:border-primary-900/40 dark:bg-primary-900/10 dark:text-primary-200">
                            <p class="font-semibold">Kod gönderilen numara</p>
                            <p><?= htmlspecialchars($maskedContact ?? $phoneDisplay ?? $phoneRaw) ?></p>
                            <?php if ($expiresText): ?>
                                <p class="mt-1 text-xs opacity-80">
                                    Kod <?= e($expiresText) ?> saatine kadar geçerlidir.
                                </p>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="<?= base_url('/resident/login/otp') ?>" class="space-y-6">
                            <?= CSRF::field() ?>
                            <div class="space-y-2">
                                <label for="otp-code" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Doğrulama Kodu</label>
                                <input
                                    id="otp-code"
                                    name="code"
                                    type="text"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    maxlength="<?= ResidentOtpService::OTP_LENGTH ?>"
                                    data-otp-input
                                    data-auto-focus
                                    aria-invalid="<?= $otpError ? 'true' : 'false' ?>"
                                    class="mt-1 block w-full rounded-xl border <?= $otpError ? 'border-red-300 focus:border-red-500 focus:ring-red-500 dark:border-red-600' : 'border-gray-200 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700' ?> bg-white px-4 py-3 text-center text-xl tracking-widest text-gray-900 placeholder:text-gray-300 shadow-sm transition focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:text-gray-100"
                                    placeholder="<?= str_repeat('•', ResidentOtpService::OTP_LENGTH) ?>">
                                <p class="text-xs <?= $otpError ? 'font-medium text-red-600 dark:text-red-300' : 'text-gray-500 dark:text-gray-400' ?>">
                                    <?= $otpError ? e($otpError) : 'Sadece rakam kullanın.' ?>
                                </p>
                            </div>

                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary-600 via-primary-500 to-primary-400 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/40 ring-1 ring-primary-500/40 transition hover:from-primary-500 hover:via-primary-400 hover:to-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900">
                                <i class="fas fa-circle-check"></i>
                                Girişi Tamamla
                            </button>
                        </form>

                        <form method="POST" action="<?= base_url('/resident/login/resend') ?>" class="mt-4 text-center" data-resend-form>
                            <?= CSRF::field() ?>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 text-xs font-semibold text-primary-600 transition hover:text-primary-500 disabled:cursor-not-allowed disabled:text-gray-400 dark:text-primary-300 dark:hover:text-primary-200"
                                    data-resend-button
                                    data-resend-at="<?= $resendTimestamp ? (int)$resendTimestamp * 1000 : '' ?>"
                                    <?= $secondsUntilResend > 0 ? 'disabled' : '' ?>>
                                <i class="fas fa-redo-alt"></i>
                                Kodu yeniden gönder
                                <span data-resend-countdown class="ml-1 hidden">(<?= $secondsUntilResend ?>)</span>
                            </button>
                        </form>

                        <form method="POST" action="<?= base_url('/resident/login/cancel') ?>" class="text-center">
                            <?= CSRF::field() ?>
                            <button type="submit" class="text-xs font-semibold text-gray-500 transition hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-300">
                                <i class="fas fa-rotate-left"></i>
                                Farklı bir telefon numarası dene
                            </button>
                        </form>
                    <?php elseif ($step === 'set_password'): ?>
                        <div class="rounded-2xl border border-primary-200 bg-primary-50/80 p-4 text-sm text-primary-700 dark:border-primary-900/40 dark:bg-primary-900/10 dark:text-primary-200">
                            <p class="font-semibold">Şifrenizi belirleyin</p>
                            <p>Şifre belirledikten sonra telefon numaranız ve şifrenizle giriş yapabilirsiniz.</p>
                        </div>

                        <form method="POST" action="<?= base_url('/resident/login/set-password') ?>" class="space-y-6">
                            <?= CSRF::field() ?>
                            <div class="space-y-2">
                                <label for="new-password" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Yeni Şifre</label>
                                <input
                                    id="new-password"
                                    name="password"
                                    type="password"
                                    autocomplete="new-password"
                                    data-auto-focus
                                    aria-invalid="<?= $setPasswordError ? 'true' : 'false' ?>"
                                    class="mt-1 block w-full rounded-xl border <?= $setPasswordError ? 'border-red-300 focus:border-red-500 focus:ring-red-500 dark:border-red-600' : 'border-gray-200 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700' ?> bg-white px-4 py-3 text-base text-gray-900 placeholder:text-gray-400 shadow-sm transition focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:text-gray-100"
                                    placeholder="En az 8 karakter">
                                <?php if ($setPasswordError): ?>
                                    <p class="mt-2 text-xs font-medium text-red-600 dark:text-red-300"><?= e($setPasswordError) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="space-y-2">
                                <label for="new-password-confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Şifre Tekrar</label>
                                <input
                                    id="new-password-confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-base text-gray-900 placeholder:text-gray-400 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                    placeholder="Şifrenizi tekrar girin">
                            </div>

                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary-600 via-primary-500 to-primary-400 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/40 ring-1 ring-primary-500/40 transition hover:from-primary-500 hover:via-primary-400 hover:to-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900">
                                <i class="fas fa-save"></i>
                                Şifreyi Kaydet ve Giriş Yap
                            </button>
                        </form>

                        <form method="POST" action="<?= base_url('/resident/login/cancel') ?>" class="text-center">
                            <?= CSRF::field() ?>
                            <button type="submit" class="text-xs font-semibold text-gray-500 transition hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-300">
                                <i class="fas fa-rotate-left"></i>
                                Farklı bir telefon numarası dene
                            </button>
                        </form>
                    <?php endif; ?>

                    <p class="text-xs text-gray-500 dark:text-gray-400">Telefon numaranız güncel değilse site yönetimi ile iletişime geçin.</p>
                </div>

                <aside class="flex flex-col justify-between gap-6 rounded-2xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-900/70">
                    <div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">İlk giriş adımları</p>
                            <p>Telefonunuzu girin, doğrulama kodunu onaylayın ve şifrenizi oluşturun.</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Şifreli giriş</p>
                            <p>Şifre belirledikten sonra doğrudan telefon ve şifre ile giriş yapabilirsiniz.</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">Kod ulaşmıyorsa</p>
                            <p>SMS engelleme ayarlarınızı kontrol edin. <?= $resendCooldown ?> saniye sonra kodu yeniden gönderebilirsiniz.</p>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-primary-200 bg-primary-50 p-5 text-sm text-primary-700 shadow-sm dark:border-primary-900/40 dark:bg-primary-900/20 dark:text-primary-200">
                        <p class="font-semibold">Destek</p>
                        <p class="mt-2 leading-6">Telefon numaranız güncel değilse site yönetimi ile iletişime geçin. Kod gelmezse <?= $resendCooldown ?> saniye sonra yeniden deneyebilirsiniz.</p>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const autoFocus = document.querySelector('[data-auto-focus]');
        if (autoFocus) {
            try {
                autoFocus.focus({ preventScroll: true });
            } catch (err) {
                autoFocus.focus();
            }
        }

        const otpInput = document.querySelector('[data-otp-input]');
        if (otpInput) {
            otpInput.addEventListener('input', (event) => {
                const cleaned = event.target.value.replace(/\D/g, '').slice(0, <?= ResidentOtpService::OTP_LENGTH ?>);
                event.target.value = cleaned;
            });
        }

        const loginForm = document.querySelector('[data-login-form]');
        if (loginForm) {
            const phoneInput = loginForm.querySelector('[data-phone-input]');
            const forgotButton = loginForm.querySelector('[data-forgot-trigger]');
            const helper = loginForm.querySelector('[data-phone-helper]');
            const clientError = loginForm.querySelector('[data-phone-client-error]');
            const defaultAction = loginForm.dataset.defaultAction || loginForm.getAttribute('action');
            const forgotAction = forgotButton ? forgotButton.dataset.forgotAction : null;

            const clearClientError = () => {
                if (clientError) {
                    clientError.textContent = '';
                    clientError.classList.add('hidden');
                }
                if (helper) {
                    helper.classList.remove('hidden');
                }
                if (phoneInput) {
                    phoneInput.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500', 'dark:border-red-600');
                    phoneInput.classList.add('border-gray-200', 'dark:border-gray-700');
                    phoneInput.setAttribute('aria-invalid', 'false');
                }
            };

            if (phoneInput) {
                phoneInput.addEventListener('input', () => {
                    if (phoneInput.value.trim() !== '') {
                        clearClientError();
                    }
                });
            }

            if (forgotButton && phoneInput && forgotAction) {
                forgotButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    const value = phoneInput.value.trim();
                    if (value === '') {
                        if (clientError) {
                            clientError.textContent = 'Lütfen önce telefon numaranızı girin.';
                            clientError.classList.remove('hidden');
                        }
                        if (helper) {
                            helper.classList.add('hidden');
                        }
                        phoneInput.classList.remove('border-gray-200', 'dark:border-gray-700');
                        phoneInput.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500', 'dark:border-red-600');
                        phoneInput.setAttribute('aria-invalid', 'true');
                        phoneInput.focus();
                        return;
                    }

                    clearClientError();
                    loginForm.setAttribute('action', forgotAction);
                    loginForm.submit();
                    loginForm.setAttribute('action', defaultAction);
                });
            }
        }

        const resendButton = document.querySelector('[data-resend-button]');
        const countdownSpan = document.querySelector('[data-resend-countdown]');
        if (resendButton && resendButton.hasAttribute('disabled') && resendButton.dataset.resendAt) {
            let remaining = Math.max(0, Math.floor((parseInt(resendButton.dataset.resendAt, 10) - Date.now()) / 1000));
            if (countdownSpan) {
                countdownSpan.classList.remove('hidden');
                countdownSpan.textContent = `(${remaining})`;
            }

            const interval = setInterval(() => {
                remaining -= 1;
                if (remaining <= 0) {
                    clearInterval(interval);
                    resendButton.disabled = false;
                    if (countdownSpan) {
                        countdownSpan.classList.add('hidden');
                    }
                    return;
                }
                if (countdownSpan) {
                    countdownSpan.textContent = `(${remaining})`;
                }
            }, 1000);
        }
    })();
    </script>
</body>
</html>













