<?php $this->layout('layout/base', ['title' => $title]) ?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                <i class="fas fa-shield-alt text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                İki Faktörlü Kimlik Doğrulama
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Authenticator uygulamanızdan 6 haneli kodu girin
            </p>
        </div>
        
        <form class="mt-8 space-y-8" method="POST" action="/two-factor/process-login">
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
                       class="w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-center text-2xl font-mono tracking-widest"
                       placeholder="123456"
                       required
                       autocomplete="one-time-code">
            </div>
            
            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-check text-blue-500 group-hover:text-blue-400"></i>
                    </span>
                    Doğrula ve Giriş Yap
                </button>
            </div>
            
            <div class="text-center">
                <a href="/login" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Giriş sayfasına dön
                </a>
            </div>
        </form>
        
        <!-- Help Section -->
        <div class="mt-8 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
            <div class="flex">
                <i class="fas fa-info-circle text-yellow-600 dark:text-yellow-400 mt-1 mr-3"></i>
                <div>
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Yardım
                    </h3>
                    <div class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                        <p class="mb-2">• Authenticator uygulamanızdan 6 haneli kodu girin</p>
                        <p class="mb-2">• Telefonunuzu kaybettiyseniz yedek kodlarınızı kullanın</p>
                        <p>• Sorun yaşıyorsanız sistem yöneticisi ile iletişime geçin</p>
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

// Countdown timer for code refresh
let timeLeft = 30;
const timerElement = document.createElement('div');
timerElement.className = 'text-center text-sm text-gray-500 dark:text-gray-400 mt-2';
document.querySelector('form').appendChild(timerElement);

function updateTimer() {
    timerElement.textContent = `Kod ${timeLeft} saniye sonra yenilenecek`;
    timeLeft--;
    
    if (timeLeft < 0) {
        timeLeft = 30;
    }
}

setInterval(updateTimer, 1000);
updateTimer();
</script>
