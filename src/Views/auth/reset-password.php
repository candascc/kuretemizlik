<?php 
$title = $title ?? 'Yeni Şifre Belirle';
view('layout/header', ['title' => $title]) 
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                <i class="fas fa-lock text-green-600 dark:text-green-400 text-xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                Yeni Şifre Belirle
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Merhaba <strong><?= e($username ?? '') ?></strong>, yeni şifrenizi belirleyin
            </p>
        </div>
        
        <!-- Flash Messages -->
        <?php include __DIR__ . '/../partials/flash.php'; ?>
        
        <form class="mt-8 space-y-6" method="POST" action="<?= base_url('/reset-password') ?>" id="resetForm">
            <?= CSRF::field() ?>
            <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
            
            <div class="space-y-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Yeni Şifre <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           minlength="8"
                           autocomplete="new-password"
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm dark:bg-gray-700"
                           placeholder="En az 8 karakter">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Şifre en az 8 karakter olmalıdır</p>
                </div>
                
                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Şifre Tekrar <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                           id="password_confirm" 
                           name="password_confirm" 
                           required 
                           minlength="8"
                           autocomplete="new-password"
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm dark:bg-gray-700"
                           placeholder="Şifrenizi tekrar girin">
                </div>
            </div>
            
            <div id="passwordMatch" class="hidden text-sm text-red-600 dark:text-red-400">
                <i class="fas fa-exclamation-circle mr-1"></i>Şifreler eşleşmiyor
            </div>
            
            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-check text-green-500 group-hover:text-green-400"></i>
                    </span>
                    Şifreyi Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetForm');
    const password = document.getElementById('password');
    const passwordConfirm = document.getElementById('password_confirm');
    const matchWarning = document.getElementById('passwordMatch');
    
    function checkPasswordMatch() {
        if (passwordConfirm.value && password.value !== passwordConfirm.value) {
            matchWarning.classList.remove('hidden');
            passwordConfirm.classList.add('border-red-500');
        } else {
            matchWarning.classList.add('hidden');
            passwordConfirm.classList.remove('border-red-500');
        }
    }
    
    password.addEventListener('input', checkPasswordMatch);
    passwordConfirm.addEventListener('input', checkPasswordMatch);
    
    form.addEventListener('submit', function(e) {
        if (password.value !== passwordConfirm.value) {
            e.preventDefault();
            alert('Şifreler eşleşmiyor!');
            return false;
        }
        
        if (password.value.length < 8) {
            e.preventDefault();
            alert('Şifre en az 8 karakter olmalıdır!');
            return false;
        }
    });
});
</script>

