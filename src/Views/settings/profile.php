<div class="space-y-8">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Profil Ayarları</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Hesap bilgilerinizi yönetin</p>
                </div>
            </div>
    
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Profile Info -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Profil Bilgileri</h3>
                
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Kullanıcı Adı</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= e($user['username']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Rol</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $user['role'] === 'ADMIN' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>">
                                <?= $user['role'] === 'ADMIN' ? 'Yönetici' : 'Operatör' ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Durum</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $user['is_active'] ? 'Aktif' : 'Pasif' ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Kayıt Tarihi</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= Utils::formatDateTime($user['created_at']) ?></dd>
                    </div>
                </dl>
            </div>
        </div>
        
                <!-- Change Password -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700" id="password-section">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Şifre Değiştir</h3>
                
                <form method="POST" action="<?= base_url('/settings/password') ?>" class="space-y-4" data-password-form>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? CSRF::get(), ENT_QUOTES, 'UTF-8') ?>">
                    
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Mevcut Şifre</label>
                        <input type="password" name="current_password" id="current_password" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700">Yeni Şifre</label>
                        <input type="password" name="new_password" id="new_password" required minlength="6"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <p class="mt-1 text-sm text-gray-500">En az 6 karakter olmalıdır</p>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Yeni Şifre (Tekrar)</label>
                        <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-medium text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <i class="fas fa-save mr-2"></i>
                            Şifreyi Değiştir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Security Notice -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Güvenlik Uyarısı</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>İlk giriş yaptığınızda şifrenizi değiştirmeniz önerilir. Güçlü bir şifre kullanın ve şifrenizi kimseyle paylaşmayın.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Şifreler eşleşmiyor');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('input', validatePassword);
    confirmPassword.addEventListener('input', validatePassword);
});
</script>
</div>