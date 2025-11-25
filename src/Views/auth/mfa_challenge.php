<?php
$title = $title ?? 'İki Faktörlü Doğrulama';
$user = $user ?? null;
$challengeId = $challengeId ?? null;
$flash = Utils::getFlash();
?>
<!DOCTYPE html>
<html lang="tr" class="h-full bg-gray-50 dark:bg-gray-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    
    <!-- Tailwind CSS (Local build - ROUND 23) -->
    <link rel="stylesheet" href="<?= Utils::asset('css/tailwind.css') ?>?v=<?= file_exists(__DIR__ . '/../../../assets/css/tailwind.css') ? filemtime(__DIR__ . '/../../../assets/css/tailwind.css') : time() ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .dark-mode-auto {
                color-scheme: dark;
            }
        }
    </style>
</head>
<body class="h-full dark-mode-auto">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                    <i class="fas fa-shield-alt text-blue-600 dark:text-blue-400 text-2xl"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                    İki Faktörlü Doğrulama
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                    Güvenliğiniz için TOTP kodunuzu girin
                </p>
            </div>
            
            <!-- Flash Messages -->
            <?php if (!empty($flash['error'])): ?>
                <div class="rounded-md bg-red-50 dark:bg-red-900/20 p-4">
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
            
            <?php if (!empty($flash['info'])): ?>
                <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                <?= htmlspecialchars($flash['info']) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form class="mt-8 space-y-6" method="POST" action="<?= base_url('/mfa/verify') ?>" id="mfa-form">
                <?= CSRF::field() ?>
                <?php if ($challengeId): ?>
                    <input type="hidden" name="challenge_id" value="<?= htmlspecialchars($challengeId) ?>">
                <?php endif; ?>
                
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="mfa_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            TOTP Kodu
                        </label>
                        <input 
                            type="text" 
                            id="mfa_code" 
                            name="mfa_code" 
                            required 
                            autocomplete="one-time-code"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            maxlength="6"
                            placeholder="000000"
                            class="appearance-none relative block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center text-2xl tracking-widest font-mono sm:text-sm dark:bg-gray-700"
                            style="min-height: 44px;" 
                            autofocus>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                            Google Authenticator, Microsoft Authenticator veya benzeri uygulamadan 6 haneli kodu girin
                        </p>
                        <!-- ROUND 5: Better error message display -->
                        <?php if (!empty($flash['error'])): ?>
                            <p class="mt-2 text-xs text-red-600 dark:text-red-400 text-center font-medium">
                                <?= htmlspecialchars($flash['error']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center">
                        <input 
                            id="remember" 
                            name="remember" 
                            type="checkbox" 
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                        <label for="remember" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                            Beni hatırla
                        </label>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <button 
                        type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600"
                        style="min-height: 44px;">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-shield-alt text-blue-500 group-hover:text-blue-400"></i>
                        </span>
                        Doğrula ve Giriş Yap
                    </button>
                    
                    <div class="text-center">
                        <button 
                            type="button" 
                            onclick="showRecoveryCode()" 
                            class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                            <i class="fas fa-key mr-1"></i>
                            Recovery code kullan
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <a 
                            href="<?= base_url('/login') ?>" 
                            class="text-sm text-gray-600 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Geri dön
                        </a>
                    </div>
                </div>
            </form>
            
            <!-- Recovery Code Modal (hidden by default) -->
            <div id="recovery-code-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Recovery Code ile Giriş
                        </h3>
                        <form method="POST" action="<?= base_url('/mfa/verify') ?>" id="recovery-form">
                            <?= CSRF::field() ?>
                            <?php if ($challengeId): ?>
                                <input type="hidden" name="challenge_id" value="<?= htmlspecialchars($challengeId) ?>">
                            <?php endif; ?>
                            <div class="mb-4">
                                <label for="recovery_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Recovery Code
                                </label>
                                <input 
                                    type="text" 
                                    id="recovery_code" 
                                    name="mfa_code" 
                                    required 
                                    placeholder="XXXX-XXXX"
                                    class="appearance-none relative block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center font-mono uppercase sm:text-sm dark:bg-gray-700"
                                    autofocus>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    MFA kurulumunda aldığınız recovery code'u girin
                                </p>
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button 
                                    type="button" 
                                    onclick="hideRecoveryCode()" 
                                    class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500">
                                    İptal
                                </button>
                                <button 
                                    type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    Doğrula
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-submit on 6 digits entered
        document.getElementById('mfa_code')?.addEventListener('input', function(e) {
            const value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            e.target.value = value;
            
            if (value.length === 6) {
                // Auto-submit after short delay
                setTimeout(() => {
                    document.getElementById('mfa-form')?.submit();
                }, 300);
            }
        });
        
        // Recovery code modal
        function showRecoveryCode() {
            document.getElementById('recovery-code-modal')?.classList.remove('hidden');
            document.getElementById('recovery_code')?.focus();
        }
        
        function hideRecoveryCode() {
            document.getElementById('recovery-code-modal')?.classList.add('hidden');
            document.getElementById('mfa_code')?.focus();
        }
        
        // Close modal on outside click
        document.getElementById('recovery-code-modal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                hideRecoveryCode();
            }
        });
    </script>
</body>
</html>

