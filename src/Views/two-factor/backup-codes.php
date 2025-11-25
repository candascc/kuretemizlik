<?php $this->layout('layout/base', ['title' => $title]) ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="text-center mb-6">
                <i class="fas fa-shield-alt text-green-500 text-4xl mb-4"></i>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    İki Faktörlü Kimlik Doğrulama Etkinleştirildi
                </h1>
                <p class="text-gray-600 dark:text-gray-300 mt-2">
                    Yedek kodlarınızı güvenli bir yerde saklayın
                </p>
            </div>
            
            <!-- Backup Codes -->
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6 mb-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 mr-2"></i>
                    <h2 class="text-lg font-semibold text-red-800 dark:text-red-200">
                        Yedek Kodlar
                    </h2>
                </div>
                
                <p class="text-sm text-red-700 dark:text-red-300 mb-4">
                    Bu kodları güvenli bir yerde saklayın. Her kod sadece bir kez kullanılabilir. 
                    Telefonunuzu kaybederseniz bu kodlarla giriş yapabilirsiniz.
                </p>
                
                <div class="grid grid-cols-2 gap-2 font-mono text-sm">
                    <?php foreach ($backup_codes as $i => $code): ?>
                        <div class="bg-white dark:bg-gray-800 p-2 rounded border text-center">
                            <?= $i + 1 ?>. <?= e($code) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Download and Continue -->
            <div class="space-y-4">
                <div class="flex space-x-4">
                    <a href="/two-factor/download-backup-codes" 
                       class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 text-center">
                        <i class="fas fa-download mr-2"></i>
                        Yedek Kodları İndir
                    </a>
                    <a href="/settings/security" 
                       class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 text-center">
                        <i class="fas fa-cog mr-2"></i>
                        Güvenlik Ayarları
                    </a>
                </div>
                
                <div class="text-center">
                    <button onclick="printBackupCodes()" 
                            class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 text-sm">
                        <i class="fas fa-print mr-1"></i>
                        Yazdır
                    </button>
                </div>
            </div>
            
            <!-- Security Tips -->
            <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                    Güvenlik İpuçları
                </h3>
                <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <li>• Yedek kodlarınızı güvenli bir yerde saklayın (şifreli not defteri, güvenli klasör)</li>
                    <li>• Kodları kimseyle paylaşmayın</li>
                    <li>• Telefonunuzu kaybederseniz yedek kodları kullanın</li>
                    <li>• Yeni yedek kodlar oluşturmak için güvenlik ayarlarından yapabilirsiniz</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function printBackupCodes() {
    const printWindow = window.open('', '_blank');
    const backupCodes = <?= json_encode($backup_codes) ?>;
    
    let content = `
        <html>
        <head>
            <title>2FA Yedek Kodları</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .codes { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 20px 0; }
                .code { border: 1px solid #ccc; padding: 10px; text-align: center; font-family: monospace; }
                .warning { background: #fef2f2; border: 1px solid #fecaca; padding: 15px; margin: 20px 0; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>İki Faktörlü Kimlik Doğrulama Yedek Kodları</h1>
                <p>Kullanıcı: <?= htmlspecialchars(Auth::user()['username']) ?></p>
                <p>Tarih: ${new Date().toLocaleString('tr-TR')}</p>
            </div>
            
            <div class="warning">
                <strong>Uyarı:</strong> Bu kodları güvenli bir yerde saklayın. Her kod sadece bir kez kullanılabilir.
            </div>
            
            <div class="codes">
    `;
    
    backupCodes.forEach((code, index) => {
        content += `<div class="code">${index + 1}. ${code}</div>`;
    });
    
    content += `
            </div>
            
            <div class="warning">
                <strong>Önemli:</strong> Bu dosyayı güvenli bir yerde saklayın ve başkalarıyla paylaşmayın.
            </div>
        </body>
        </html>
    `;
    
    printWindow.document.write(content);
    printWindow.document.close();
    printWindow.print();
}

// Clear backup codes from session after 5 minutes
setTimeout(() => {
    fetch('/two-factor/clear-backup-codes', { method: 'POST' });
}, 300000);
</script>
