<?php $title = $title ?? 'Login'; ?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                <i class="fas fa-lock text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                Hoş Geldiniz! 👋
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Güvenli giriş yaparak işlerinizi yönetin
            </p>
        </div>
        
        <!-- Flash Messages -->
        <?php include __DIR__ . '/../partials/flash.php'; ?>
        
        <form class="mt-8 space-y-8" method="POST" action="<?= base_url('/login') ?>" data-login-form>
            <?= CSRF::field() ?>
            <div class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Kullanıcı Adı
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required 
                           autocomplete="username"
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm dark:bg-gray-700"
                           placeholder="Kullanıcı adınızı girin">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Şifre
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="current-password"
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm dark:bg-gray-700"
                           placeholder="Şifrenizi girin">
                </div>
            </div>
            
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="remember" 
                           name="remember" 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700">
                    <label for="remember" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                        Beni hatırla
                    </label>
                </div>
                
                <div class="text-sm">
                    <a href="<?= base_url('/forgot-password') ?>" class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300">
                        Şifremi unuttum
                    </a>
                </div>
            </div>
            
            <div>
                <button type="submit"
                        data-login-submit
                        class="group relative w-full flex items-center justify-center gap-3 py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                    <i class="fas fa-sign-in-alt text-blue-200 group-hover:text-white transition-colors login-icon"></i>
                    <i class="fas fa-spinner fa-spin text-blue-200 hidden login-spinner" aria-hidden="true"></i>
                    <span class="login-label">Giriş Yap</span>
                </button>
            </div>
        </form>
        
    </div>
</div>

<script>
const usernameInput = document.getElementById('username');
const loginForm = document.querySelector('[data-login-form]');

if (usernameInput) {
    requestAnimationFrame(() => {
        try {
            usernameInput.focus({ preventScroll: true });
        } catch (err) {
            usernameInput.focus();
        }
    });
}

if (loginForm) {
    // Prevent service worker from interrupting form submission
    var isSubmitting = false;
    
    loginForm.addEventListener('submit', function(e) {
        // Mark that we're submitting to prevent service worker reload
        isSubmitting = true;
        
        const usernameValue = usernameInput ? usernameInput.value.trim() : '';
        const passwordValue = document.getElementById('password')?.value ?? '';

        if (!usernameValue || !passwordValue) {
            e.preventDefault();
            isSubmitting = false;
            alert('Lütfen tüm alanları doldurun 📝');
            return;
        }

        const submitBtn = loginForm.querySelector('[data-login-submit]');
        let icon, spinner, label; // Declare variables in outer scope for setTimeout
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.setAttribute('aria-busy', 'true');

            icon = submitBtn.querySelector('.login-icon');
            spinner = submitBtn.querySelector('.login-spinner');
            label = submitBtn.querySelector('.login-label');

            if (icon) icon.classList.add('hidden');
            if (spinner) spinner.classList.remove('hidden');
            if (label) label.textContent = 'Güvenli giriş yapılıyor...';
        }
        
        // Prevent service worker reload during submission
        window.__LOGIN_SUBMITTING = true;
        
        // ===== PRODUCTION FIX: Add timeout to prevent infinite loading =====
        // Eğer 10 saniye içinde cevap gelmezse, butonu tekrar aktif et
        setTimeout(function() {
            window.__LOGIN_SUBMITTING = false;
            if (submitBtn && submitBtn.disabled) {
                submitBtn.disabled = false;
                submitBtn.removeAttribute('aria-busy');
                if (icon) icon.classList.remove('hidden');
                if (spinner) spinner.classList.add('hidden');
                if (label) label.textContent = 'Giriş Yap';
                alert('Giriş işlemi zaman aşımına uğradı. Lütfen tekrar deneyin.');
            }
            isSubmitting = false;
        }, 10000); // 10 saniye timeout
        // ===== PRODUCTION FIX END =====
    });
    
    // Reset flag after form submission completes (success or error)
    window.addEventListener('beforeunload', function() {
        if (isSubmitting) {
            // Don't block navigation during login
            window.__LOGIN_SUBMITTING = false;
        }
    });
}
</script>
