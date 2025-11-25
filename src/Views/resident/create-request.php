<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Yeni Talep</h1>
            <p class="text-gray-600 dark:text-gray-400">Yöneticiye talep gönderin</p>
        </div>
        <a href="<?= base_url('/resident/requests') ?>" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg">
            <i class="fas fa-arrow-left mr-2"></i>
            Geri Dön
        </a>
    </div>

    <!-- Request Form -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <form method="POST" class="space-y-6">
            <?= CSRF::field() ?>
            
            <div>
                <label for="request_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Talep Tipi</label>
                <select name="request_type" id="request_type" 
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white" 
                        required>
                    <option value="">Seçiniz</option>
                    <option value="maintenance">Bakım/Onarım</option>
                    <option value="complaint">Şikayet</option>
                    <option value="suggestion">Öneri</option>
                    <option value="question">Soru</option>
                    <option value="security">Güvenlik</option>
                    <option value="noise">Gürültü</option>
                    <option value="parking">Otopark</option>
                    <option value="other">Diğer</option>
                </select>
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori (Opsiyonel)</label>
                <input type="text" name="category" id="category" 
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                       placeholder="Örn: Asansör, Su, Elektrik, Temizlik...">
            </div>

            <div>
                <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Konu</label>
                <input type="text" name="subject" id="subject" 
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                       placeholder="Talep konusunu kısaca özetleyin" 
                       required>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Açıklama</label>
                <textarea name="description" id="description" rows="6" 
                          class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                          placeholder="Talep detaylarını açıklayın..." 
                          required></textarea>
            </div>

            <div>
                <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Öncelik</label>
                <select name="priority" id="priority" 
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white" 
                        required>
                    <option value="low">Düşük</option>
                    <option value="normal" selected>Normal</option>
                    <option value="high">Yüksek</option>
                    <option value="urgent">Acil</option>
                </select>
            </div>

            <div class="flex items-center justify-end space-x-3">
                <a href="<?= base_url('/resident/requests') ?>" 
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    İptal
                </a>
                <button type="submit" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Talep Gönder
                </button>
            </div>
        </form>
    </div>

    <!-- Help Info -->
    <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Talep Rehberi</h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    <ul class="list-disc list-inside space-y-1">
                        <li><strong>Bakım/Onarım:</strong> Asansör, su, elektrik gibi teknik sorunlar</li>
                        <li><strong>Şikayet:</strong> Komşu, gürültü, temizlik gibi sorunlar</li>
                        <li><strong>Öneri:</strong> Bina yönetimi hakkında önerileriniz</li>
                        <li><strong>Soru:</strong> Aidat, toplantı, duyuru hakkında sorular</li>
                        <li><strong>Güvenlik:</strong> Güvenlik ile ilgili endişeleriniz</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
