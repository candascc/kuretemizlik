<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Güvenlik Ayarları
            </h1>
            <p class="text-gray-600 dark:text-gray-300 mt-2">
                Hesabınızın güvenliğini yönetin
            </p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Two-Factor Authentication -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-shield-alt text-blue-500 text-xl mr-3"></i>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        İki Faktörlü Kimlik Doğrulama
                    </h2>
                </div>
                
                <?php if ($two_factor_enabled): ?>
                    <div class="space-y-4">
                        <div class="flex items-center text-green-600 dark:text-green-400">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span class="font-medium">2FA Etkin</span>
                        </div>
                        
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            <p>Yedek kodlar: <strong><?= e($backup_codes_count) ?></strong> adet</p>
                            <p>Etkinleştirme tarihi: <strong><?= e(date('d.m.Y H:i', strtotime($user['two_factor_enabled_at'] ?? ''))) ?></strong></p>
                        </div>
                        
                        <div class="space-y-2">
                            <a href="/two-factor/backup-codes" 
                               class="block w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-center">
                                <i class="fas fa-key mr-2"></i>
                                Yedek Kodları Görüntüle
                            </a>
                            
                            <form method="POST" action="/settings/regenerate-backup-codes" class="block">
                                <?= CSRF::field() ?>
                                <button type="submit" 
                                        class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700">
                                    <i class="fas fa-refresh mr-2"></i>
                                    Yeni Yedek Kodlar Oluştur
                                </button>
                            </form>
                            
                            <form method="POST" action="/settings/disable-2fa" class="block">
                                <?= CSRF::field() ?>
                                <div class="mb-2">
                                    <label for="disable_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Şifre (2FA'yı devre dışı bırakmak için)
                                    </label>
                                    <input type="password" 
                                           id="disable_password" 
                                           name="password" 
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white">
                                </div>
                                <button type="submit" 
                                        class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700"
                                        onclick="return confirm('2FA\'yı devre dışı bırakmak istediğinizden emin misiniz?')">
                                    <i class="fas fa-times mr-2"></i>
                                    2FA'yı Devre Dışı Bırak
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <div class="flex items-center text-red-600 dark:text-red-400">
                            <i class="fas fa-times-circle mr-2"></i>
                            <span class="font-medium">2FA Devre Dışı</span>
                        </div>
                        
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Hesabınızı daha güvenli hale getirmek için iki faktörlü kimlik doğrulamayı etkinleştirin.
                        </p>
                        
                        <form method="POST" action="/settings/enable-2fa">
                            <?= CSRF::field() ?>
                            <button type="submit" 
                                    class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                <i class="fas fa-shield-alt mr-2"></i>
                                2FA'yı Etkinleştir
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Password Security -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-lock text-green-500 text-xl mr-3"></i>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Şifre Güvenliği
                    </h2>
                </div>
                
                <div class="space-y-4">
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        <p>Son şifre değişikliği: <strong><?= date('d.m.Y H:i', strtotime($user['updated_at'] ?? '')) ?></strong></p>
                    </div>
                    
                    <a href="/settings/profile" 
                       class="block w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-center">
                        <i class="fas fa-key mr-2"></i>
                        Şifre Değiştir
                    </a>
                </div>
            </div>
            
            <!-- Session Security -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-clock text-purple-500 text-xl mr-3"></i>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Oturum Güvenliği
                    </h2>
                </div>
                
                <div class="space-y-4">
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        <p>Oturum süresi: <strong><?= SESSION_TIMEOUT / 60 ?> dakika</strong></p>
                        <p>Son giriş: <strong><?= date('d.m.Y H:i', $_SESSION['login_time'] ?? time()) ?></strong></p>
                    </div>
                    
                    <a href="/logout" 
                       class="block w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-center">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Tüm Oturumları Sonlandır
                    </a>
                </div>
            </div>
            
            <!-- Security Tips -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-lightbulb text-yellow-500 text-xl mr-3"></i>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Güvenlik İpuçları
                    </h2>
                </div>
                
                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                        <p>Güçlü ve benzersiz şifreler kullanın</p>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                        <p>2FA'yı mutlaka etkinleştirin</p>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                        <p>Yedek kodlarınızı güvenli bir yerde saklayın</p>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                        <p>Şüpheli aktiviteleri hemen bildirin</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Status -->
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Güvenlik Durumu
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold <?= $two_factor_enabled ? 'text-green-600' : 'text-red-600' ?>">
                        <?= e($two_factor_enabled ? '✓' : '✗') ?>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">2FA</p>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">✓</div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Güçlü Şifre</p>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">✓</div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Güvenli Oturum</p>
                </div>
            </div>
        </div>
    </div>

</div>
