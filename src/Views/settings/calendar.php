<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Takvim Ayarları</h1>
    <div class="flex flex-wrap gap-2">
        <a href="<?= base_url('/oauth/google') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="fab fa-google text-red-500 mr-2"></i> Google Takvimi Bağla
        </a>
        <a href="<?= base_url('/oauth/microsoft') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
            <i class="fab fa-microsoft text-blue-600 mr-2"></i> Microsoft Takvimi Bağla
        </a>
        <a href="<?= base_url('/calendar/sync?provider=google') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-700">
            <i class="fas fa-sync mr-2"></i> Google ile Senkronize Et
        </a>
        <a href="<?= base_url('/calendar/sync?provider=microsoft') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-700">
            <i class="fas fa-sync mr-2"></i> Microsoft ile Senkronize Et
        </a>
        <a href="<?= base_url('/calendar/feed.ics') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hoverbg-gray-700">
            <i class="fas fa-calendar-alt mr-2"></i> ICS Feed İndir
        </a>
    </div>
    <form method="POST" action="<?= base_url('/settings/calendar') ?>" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700">
        <?= CSRF::field() ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <label class="block">
                <span class="text-sm text-gray-700 dark:text-gray-300">Zaman Dilimi</span>
                <input type="text" name="timezone" value="<?= htmlspecialchars($prefs['timezone'] ?? 'Europe/Istanbul') ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" placeholder="Europe/Istanbul">
            </label>
            <label class="block">
                <span class="text-sm text-gray-700 dark:text-gray-300">Yoğunluk</span>
                <select name="calendar_density" class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    <?php $density = $prefs['calendar_density'] ?? 'comfortable'; ?>
                    <option value="comfortable" <?= $density==='comfortable'?'selected':'' ?>>Rahat</option>
                    <option value="dense" <?= $density==='dense'?'selected':'' ?>>Sıkışık</option>
                </select>
            </label>
            <label class="block">
                <span class="text-sm text-gray-700 dark:text-gray-300">Çalışma Başlangıcı</span>
                <input type="time" name="work_start" value="<?= htmlspecialchars($prefs['work_start'] ?? '09:00') ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            </label>
            <label class="block">
                <span class="text-sm text-gray-700 dark:text-gray-300">Çalışma Bitişi</span>
                <input type="time" name="work_end" value="<?= htmlspecialchars($prefs['work_end'] ?? '18:00') ?>" class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            </label>
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="weekend_shading" value="1" <?= (int)($prefs['weekend_shading'] ?? 1) ? 'checked' : '' ?> class="rounded border-gray-300">
                <span class="text-sm text-gray-700 dark:text-gray-300">Hafta sonlarını renklendir</span>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="inline-flex items-center gap-2"><input type="checkbox" name="calendar_reminders_email" value="1" <?= (int)($prefs['calendar_reminders_email'] ?? 1) ? 'checked' : '' ?> class="rounded border-gray-300"><span class="text-sm text-gray-700 dark:text-gray-300">E‑posta hatırlatıcı</span></label>
                <label class="inline-flex items-center gap-2"><input type="checkbox" name="calendar_reminders_sms" value="1" <?= (int)($prefs['calendar_reminders_sms'] ?? 0) ? 'checked' : '' ?> class="rounded border-gray-300"><span class="text-sm text-gray-700 dark:text-gray-300">SMS hatırlatıcı</span></label>
            </div>
        </div>
        <div class="mt-6">
            <button class="px-6 py-3 rounded-lg text-white bg-primary-600 hover:bg-primary-700">Kaydet</button>
        </div>
    </form>
</div>


