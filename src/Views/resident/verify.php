<?php
$flash = $flash ?? [];
$channel = $pending['channel'] ?? 'email';
$masked = $pending['masked_contact'] ?? '***';
$expiresAt = $pending['expires_at'] ?? null;
$expiresText = $expiresAt ? Utils::formatDateTime($expiresAt, 'H:i') : '';
?>
<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
                Doğrulama Kodunu Girin
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                <?= $channel === 'sms'
                    ? 'SMS ile gönderdik: ' . e($masked)
                    : 'E-posta ile gönderdik: ' . e($masked) ?>
            </p>
            <?php if ($expiresText): ?>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                    Kod <?= e($expiresText) ?> saatine kadar geçerlidir.
                </p>
            <?php endif; ?>
        </div>

        <?php if (!empty($flash['error'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                <p class="text-sm text-red-700"><?= e($flash['error']) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($flash['success'])): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-md">
                <p class="text-sm text-green-700"><?= e($flash['success']) ?></p>
            </div>
        <?php endif; ?>

        <form class="space-y-6" method="POST" action="<?= base_url('/resident/verify') ?>">
            <?= CSRF::field() ?>
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Doğrulama Kodu</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" required
                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-lg dark:bg-gray-700 dark:text-white tracking-widest text-center uppercase"
                       placeholder="• • • • • •" maxlength="6">
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Kod 6 hanelidir. Harf veya boşluk kullanmayın.
                </p>
            </div>

            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-md border border-transparent bg-primary-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                <i class="fas fa-unlock"></i>
                Girişi Tamamla
            </button>
        </form>

        <form method="POST" action="<?= base_url('/resident/verify/resend') ?>" class="text-center">
            <?= CSRF::field() ?>
            <button type="submit"
                    class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                <i class="fas fa-redo-alt"></i>
                Kodu yeniden gönder
            </button>
        </form>

        <div class="text-center text-sm text-gray-500 dark:text-gray-400">
            Yanlış yöntem mi seçtiniz? <a href="<?= base_url('/resident/login') ?>" class="text-primary-600 hover:text-primary-500">Geri dön</a>
        </div>
    </div>
</div>

