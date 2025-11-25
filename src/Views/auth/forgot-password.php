<?php 
$title = $title ?? 'Şifre Sıfırlama';
view('layout/header', ['title' => $title]) 
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                <i class="fas fa-key text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                Şifre Sıfırlama
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Kullanıcı adınızı girerek şifre sıfırlama bağlantısı alabilirsiniz
            </p>
        </div>
        
        <!-- Flash Messages -->
        <?php include __DIR__ . '/../partials/flash.php'; ?>
        
        <form class="mt-8 space-y-6" method="POST" action="<?= base_url('/forgot-password') ?>">
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
                           autofocus
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm dark:bg-gray-700"
                           placeholder="Kullanıcı adınızı girin">
                </div>
            </div>
            
            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-paper-plane text-blue-500 group-hover:text-blue-400"></i>
                    </span>
                    Şifre Sıfırlama Bağlantısı Gönder
                </button>
            </div>
            
            <div class="text-center">
                <a href="<?= base_url('/login') ?>" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300">
                    <i class="fas fa-arrow-left mr-1"></i>Giriş sayfasına dön
                </a>
            </div>
        </form>
    </div>
</div>

