<?php $this->layout('layout/base', ['title' => $title]) ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                İki Faktörlü Kimlik Doğrulama Kurulumu
            </h1>
            
            <div class="space-y-8">
                <!-- Step 1: Install Authenticator App -->
                <div class="border-l-4 border-blue-500 pl-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        1. Authenticator Uygulaması İndirin
                    </h2>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Google Authenticator, Microsoft Authenticator veya benzeri bir uygulama indirin.
                    </p>
                    <div class="flex space-x-4">
                        <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" 
                           target="_blank" 
                           class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <i class="fab fa-google-play mr-2"></i>
                            Google Play
                        </a>
                        <a href="https://apps.apple.com/app/google-authenticator/id388497605" 
                           target="_blank" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fab fa-apple mr-2"></i>
                            App Store
                        </a>
                    </div>
                </div>
                
                <!-- Step 2: Scan QR Code -->
                <div class="border-l-4 border-green-500 pl-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        2. QR Kodu Tarayın
                    </h2>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Authenticator uygulamanızla aşağıdaki QR kodu tarayın:
                    </p>
                    
                    <div class="text-center">
                        <img src="<?= e($qr_code_url) ?>" 
                             alt="2FA QR Code" 
                             class="mx-auto border-2 border-gray-300 dark:border-gray-600 rounded-lg">
                    </div>
                    
                    <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                            <strong>Manuel Giriş:</strong> QR kod çalışmıyorsa, aşağıdaki kodu manuel olarak girin:
                        </p>
                        <code class="block bg-white dark:bg-gray-800 p-2 rounded border text-sm font-mono">
                            <?= e($secret) ?>
                        </code>
                    </div>
                </div>
                
                <!-- Step 3: Verify Code -->
                <div class="border-l-4 border-yellow-500 pl-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        3. Doğrulama Kodunu Girin
                    </h2>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        Authenticator uygulamanızdan 6 haneli kodu girin:
                    </p>
                    
                    <form method="POST" action="/two-factor/verify" class="space-y-4">
                        <?= CSRF::field() ?>
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Doğrulama Kodu
                            </label>
                            <input type="text" 
                                   id="code" 
                                   name="code" 
                                   maxlength="6" 
                                   pattern="[0-9]{6}"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-center text-2xl font-mono tracking-widest"
                                   placeholder="123456"
                                   required>
                        </div>
                        
                        <div class="flex space-x-4">
                            <button type="submit" 
                                    class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Doğrula ve Etkinleştir
                            </button>
                            <a href="/settings/security" 
                               class="flex-1 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 text-center">
                                İptal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Security Notice -->
            <div class="mt-8 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex">
                    <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mt-1 mr-3"></i>
                    <div>
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            Güvenlik Uyarısı
                        </h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                            İki faktörlü kimlik doğrulama etkinleştirildikten sonra, giriş yapmak için hem şifrenizi hem de doğrulama kodunuzu gerekecek. 
                            Yedek kodlarınızı güvenli bir yerde saklayın.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-focus on code input
document.getElementById('code').focus();

// Auto-submit when 6 digits are entered
document.getElementById('code').addEventListener('input', function(e) {
    if (e.target.value.length === 6) {
        e.target.form.submit();
    }
});

// Only allow numbers
document.getElementById('code').addEventListener('keypress', function(e) {
    if (!/[0-9]/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Enter'].includes(e.key)) {
        e.preventDefault();
    }
});
</script>
