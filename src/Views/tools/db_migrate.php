<?php
/**
 * DB Migration Runner View
 * ROUND 7: Web tabanlı migration runner UI
 */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB Migration Runner - Küre Temizlik</title>
    <link rel="stylesheet" href="<?= Utils::asset('css/tailwind.css') ?>?v=<?= file_exists(__DIR__ . '/../../../assets/css/tailwind.css') ? filemtime(__DIR__ . '/../../../assets/css/tailwind.css') : time() ?>">
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">DB Migration Runner</h1>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    <strong>Hata:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($warning)): ?>
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded mb-4">
                    <strong>Uyarı:</strong> <?= htmlspecialchars($warning) ?>
                </div>
            <?php endif; ?>
            
            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded mb-6">
                <strong>⚠️ Önemli:</strong> Bu sayfa sadece migration çalıştırmak için kullanılmalıdır. 
                Production ortamında dikkatli kullanın. Migration'ları çalıştırmadan önce mutlaka veritabanı yedeği alın.
            </div>
            
            <?php if (isset($status)): ?>
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-2">Migration Durumu</h2>
                    <div class="bg-gray-50 border border-gray-200 rounded p-4">
                        <p><strong>Toplam Migration:</strong> <?= htmlspecialchars($status['total'] ?? 0) ?></p>
                        <p><strong>Çalıştırılmış:</strong> <?= htmlspecialchars($status['executed'] ?? 0) ?></p>
                        <p><strong>Bekleyen:</strong> <?= htmlspecialchars($status['pending'] ?? 0) ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($migrationResult)): ?>
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-2">Migration Sonucu</h2>
                    <?php if ($migrationResult['success']): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                            <strong>✅ Başarılı!</strong><br>
                            <p>Çalıştırılan migration sayısı: <?= htmlspecialchars($migrationResult['executed'] ?? 0) ?></p>
                            <?php if (isset($migrationResult['message'])): ?>
                                <p><?= htmlspecialchars($migrationResult['message']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                            <strong>❌ Hata!</strong><br>
                            <?php if (!empty($migrationResult['errors'])): ?>
                                <ul class="list-disc list-inside mt-2">
                                    <?php foreach ($migrationResult['errors'] as $error): ?>
                                        <li><?= htmlspecialchars($error['migration'] ?? 'Unknown') ?>: <?= htmlspecialchars($error['error'] ?? 'Unknown error') ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p><?= htmlspecialchars($migrationResult['message'] ?? 'Bilinmeyen hata') ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="flex gap-4">
                <form method="POST" action="/tools/db/migrate" class="flex-1">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            onclick="return confirm('Migration\'ları çalıştırmak istediğinizden emin misiniz? Veritabanı yedeği aldınız mı?');">
                        Migration'ları Çalıştır
                    </button>
                </form>
                
                <a href="/tools/db/migrate?action=status" 
                   class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-center focus:outline-none focus:shadow-outline">
                    Durumu Yenile
                </a>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-600">
                    <strong>Not:</strong> Bu sayfa sadece SUPERADMIN rolüne sahip kullanıcılar tarafından erişilebilir.
                    <?php if (!empty($config['db_migrations']['token'])): ?>
                        Ayrıca geçerli bir token parametresi gereklidir.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

