<?php
$title = $title ?? 'MFA Yönetimi';
$user = $user ?? null;
$mfaEnabled = $mfaEnabled ?? false;
$mfaSecret = $mfaSecret ?? null;
$qrCodeUri = $qrCodeUri ?? null;
$recovery_codes = $recovery_codes ?? [];
$show_recovery_codes = $show_recovery_codes ?? false;
$csrfToken = $csrfToken ?? CSRF::generate();
$flash = Utils::getFlash();
?>
<?php include __DIR__ . '/../partials/header.php'; ?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-shield-alt"></i>
                MFA Yönetimi
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kullanıcı: <strong><?= htmlspecialchars($user['username'] ?? 'N/A') ?></strong>
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
        
        <?php if (!empty($flash['success'])): ?>
            <div class="mb-6 rounded-md bg-green-50 dark:bg-green-900/20 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">
                            <?= htmlspecialchars($flash['success']) ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- MFA Status Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    MFA Durumu
                </h2>
                <?php if ($mfaEnabled): ?>
                    <span class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200 rounded-full text-sm font-medium">
                        <i class="fas fa-check-circle mr-1"></i>
                        Aktif
                    </span>
                <?php else: ?>
                    <span class="px-3 py-1 bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded-full text-sm font-medium">
                        <i class="fas fa-times-circle mr-1"></i>
                        Pasif
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if ($mfaEnabled && $qrCodeUri): ?>
                <!-- QR Code Display -->
                <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">
                        QR Kod
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Kullanıcı bu QR kodu Google Authenticator, Microsoft Authenticator veya benzeri bir TOTP uygulaması ile tarayarak MFA'yı aktif edebilir.
                    </p>
                    
                    <!-- QR Code (using qrcode.js library) - ROUND 5: Larger QR code -->
                    <div class="flex justify-center mb-4">
                        <div id="qrcode" class="p-6 bg-white rounded-lg border-2 border-gray-200 dark:border-gray-600 shadow-lg" style="min-width: 320px; min-height: 320px;"></div>
                    </div>
                    
                    <!-- ROUND 5: Security warning -->
                    <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                    <strong>Önemli:</strong> QR kodunu ve recovery code'ları güvenli bir yere kaydedin. Bu bilgiler sadece bir kez gösterilir.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Manual Entry -->
                    <div class="mt-4 p-3 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Manuel Giriş (QR kod çalışmazsa):
                        </p>
                        <div class="flex items-center justify-between">
                            <code class="text-xs font-mono text-gray-900 dark:text-white break-all">
                                <?= htmlspecialchars($mfaSecret ?? '') ?>
                            </code>
                            <button 
                                onclick="copySecret()" 
                                class="ml-2 px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                                <i class="fas fa-copy"></i> Kopyala
                            </button>
                        </div>
                    </div>
                    
                    <!-- OTP URI -->
                    <div class="mt-3 p-3 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                            OTP URI:
                        </p>
                        <code class="text-xs font-mono text-gray-900 dark:text-white break-all block">
                            <?= htmlspecialchars($qrCodeUri) ?>
                        </code>
                    </div>
                    
                    <!-- ROUND 5: Recovery Codes Display (first-time only) -->
                    <?php if ($show_recovery_codes && !empty($recovery_codes)): ?>
                        <div class="mt-6 p-4 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 rounded-lg">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                                </div>
                                <div class="ml-3 flex-1">
                                    <h3 class="text-lg font-semibold text-red-900 dark:text-red-200 mb-2">
                                        Recovery Codes
                                    </h3>
                                    <p class="text-sm text-red-800 dark:text-red-300 mb-4">
                                        <strong>Önemli:</strong> Bu recovery code'ları güvenli bir yere kaydedin. TOTP uygulamanıza erişemediğinizde bu kodlarla giriş yapabilirsiniz. Bu kodlar sadece bir kez gösterilir.
                                    </p>
                                    
                                    <!-- Recovery Codes List -->
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded border border-red-200 dark:border-red-700 mb-4">
                                        <div class="grid grid-cols-2 gap-2 font-mono text-sm">
                                            <?php foreach ($recovery_codes as $code): ?>
                                                <div class="p-2 bg-gray-50 dark:bg-gray-700 rounded text-center">
                                                    <?= htmlspecialchars($code) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="flex flex-wrap gap-2">
                                        <button 
                                            onclick="copyRecoveryCodes()" 
                                            class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
                                            <i class="fas fa-copy mr-2"></i>
                                            Tümünü Kopyala
                                        </button>
                                        <a 
                                            href="<?= base_url('/settings/download-recovery-codes?user_id=' . ($user['id'] ?? 0)) ?>" 
                                            class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg">
                                            <i class="fas fa-download mr-2"></i>
                                            TXT Olarak İndir
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                İşlemler
            </h2>
            
            <?php if (!$mfaEnabled): ?>
                <!-- Enable MFA -->
                <form method="POST" action="<?= base_url('/settings/enable-user-mfa') ?>" class="mb-4">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="user_id" value="<?= (int)($user['id'] ?? 0) ?>">
                    <button 
                        type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition">
                        <i class="fas fa-shield-alt mr-2"></i>
                        MFA'yı Etkinleştir
                    </button>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        MFA'yı etkinleştirdikten sonra kullanıcıya QR kodu gösterilecektir.
                    </p>
                </form>
            <?php else: ?>
                <!-- Disable MFA -->
                <form method="POST" action="<?= base_url('/settings/disable-user-mfa') ?>" onsubmit="return confirm('MFA\'yı devre dışı bırakmak istediğinizden emin misiniz?');">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="user_id" value="<?= (int)($user['id'] ?? 0) ?>">
                    <button 
                        type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition">
                        <i class="fas fa-times-circle mr-2"></i>
                        MFA'yı Devre Dışı Bırak
                    </button>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        MFA'yı devre dışı bıraktığınızda kullanıcının secret ve recovery code'ları silinecektir.
                    </p>
                </form>
            <?php endif; ?>
            
            <!-- Back Button -->
            <div class="mt-6">
                <a 
                    href="<?= base_url('/settings/users') ?>" 
                    class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kullanıcı Listesine Dön
                </a>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
    // Generate QR Code - ROUND 5: Larger QR code (320x320)
    <?php if ($mfaEnabled && $qrCodeUri): ?>
    QRCode.toCanvas(document.getElementById('qrcode'), '<?= htmlspecialchars($qrCodeUri, ENT_QUOTES) ?>', {
        width: 320,
        margin: 3,
        color: {
            dark: '#000000',
            light: '#FFFFFF'
        }
    }, function (error) {
        if (error) {
            console.error('QR Code generation error:', error);
            document.getElementById('qrcode').innerHTML = '<p class="text-red-600 dark:text-red-400 p-4">QR kod oluşturulamadı. Manuel giriş kullanın.</p>';
        }
    });
    <?php endif; ?>
    
    // Copy secret to clipboard
    function copySecret() {
        const secret = '<?= htmlspecialchars($mfaSecret ?? '', ENT_QUOTES) ?>';
        navigator.clipboard.writeText(secret).then(function() {
            alert('Secret kopyalandı!');
        }, function(err) {
            console.error('Copy failed:', err);
            alert('Kopyalama başarısız. Lütfen manuel olarak kopyalayın.');
        });
    }
    
    // ROUND 5: Copy recovery codes to clipboard
    function copyRecoveryCodes() {
        const codes = <?= json_encode($recovery_codes ?? []) ?>;
        const codesText = codes.join('\n');
        navigator.clipboard.writeText(codesText).then(function() {
            alert('Recovery code\'lar kopyalandı!');
        }, function(err) {
            console.error('Copy failed:', err);
            alert('Kopyalama başarısız. Lütfen manuel olarak kopyalayın.');
        });
    }
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>

