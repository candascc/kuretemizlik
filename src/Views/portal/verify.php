<?php
$flash = $flash ?? Utils::getFlash();
$channel = $pending['channel'] ?? 'email';
$masked = $pending['masked_contact'] ?? '***';
$expiresAt = $pending['expires_at'] ?? null;
$expiresText = $expiresAt ? Utils::formatDateTime($expiresAt, 'H:i') : '';
?>
<!DOCTYPE html>
<html lang="<?= Translator::getLocale() ?>" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Doğrulama' ?></title>
    <link rel="stylesheet" href="<?= Utils::asset('css/tailwind.css') ?>?v=<?= file_exists(__DIR__ . '/../../../assets/css/tailwind.css') ? filemtime(__DIR__ . '/../../../assets/css/tailwind.css') : time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Doğrulama Kodunu Girin
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    <?= $channel === 'sms'
                        ? 'SMS ile gönderildi: ' . e($masked)
                        : 'E-posta ile gönderildi: ' . e($masked) ?>
                </p>
                <?php if ($expiresText): ?>
                    <p class="mt-1 text-xs text-gray-500">
                        Kod <?= e($expiresText) ?> saatine kadar geçerlidir.
                    </p>
                <?php endif; ?>
            </div>

            <?php if (!empty($flash['error'])): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4">
                    <p class="text-sm font-medium"><?= e($flash['error']) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($flash['success'])): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-4">
                    <p class="text-sm font-medium"><?= e($flash['success']) ?></p>
                </div>
            <?php endif; ?>

            <form class="space-y-6" method="POST" action="<?= base_url('/portal/verify') ?>">
                <?= CSRF::field() ?>
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">Doğrulama Kodu</label>
                    <input id="code"
                           name="code"
                           inputmode="numeric"
                           autocomplete="one-time-code"
                           maxlength="6"
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-lg tracking-widest text-center"
                           placeholder="• • • • • •">
                    <p class="mt-1 text-xs text-gray-500">Kod 6 hanelidir; boşluk veya harf kullanmayın.</p>
                </div>

                <button type="submit"
                        class="w-full inline-flex justify-center items-center gap-2 rounded-md border border-transparent bg-blue-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fas fa-unlock"></i>
                    Girişi Tamamla
                </button>
            </form>

            <form method="POST" action="<?= base_url('/portal/verify/resend') ?>" class="text-center">
                <?= CSRF::field() ?>
                <button type="submit"
                        class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fas fa-redo-alt"></i>
                    Kodu yeniden gönder
                </button>
            </form>

            <div class="text-center text-sm text-gray-500">
                Yanlış yöntem mi seçtiniz?
                <a href="<?= base_url('/portal/login') ?>" class="text-blue-600 hover:text-blue-500">Tekrar deneyin</a>
            </div>
        </div>
    </div>
</body>
</html>

