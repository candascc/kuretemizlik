<?php
/**
 * Transfer Doğrulama Script
 */

$targetDb = __DIR__ . '/app.sqlite';

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║            VERİTABANI İÇERİK DOĞRULAMA                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

try {
    $pdo = new PDO("sqlite:{$targetDb}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Veritabanı başarıyla açıldı\n\n";
    
    // Tüm tabloları listele
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 TABLO VE KAYIT SAYILARI:\n";
    echo "─────────────────────────────────────────────────────────────────\n\n";
    
    $totalRecords = 0;
    $criticalData = [];
    
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
            $totalRecords += $count;
            
            if ($count > 0) {
                echo "   ✓ {$table}: " . str_pad($count, 6, " ", STR_PAD_LEFT) . " kayıt\n";
                
                // Kritik tabloları işaretle
                $critical = ['customers', 'jobs', 'users', 'services', 'buildings', 'management_fees', 'contracts', 'staff', 'addresses'];
                if (in_array($table, $critical)) {
                    $criticalData[$table] = $count;
                }
            }
        } catch (Exception $e) {
            echo "   ⚠️  {$table}: Okunamadı\n";
        }
    }
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "ÖZET\n";
    echo "═══════════════════════════════════════════════════════════════════\n\n";
    
    echo "Toplam Tablo: " . count($tables) . "\n";
    echo "Toplam Kayıt: {$totalRecords}\n\n";
    
    echo "🔑 KRİTİK VERİLER:\n";
    echo "─────────────────────────────────────────────────────────────────\n\n";
    
    foreach ($criticalData as $table => $count) {
        $emoji = $count > 0 ? "✅" : "⚠️ ";
        echo "   {$emoji} " . str_pad($table, 20) . ": {$count} kayıt\n";
    }
    
    echo "\n";
    
    // Veri örnekleri göster
    echo "📝 VERİ ÖRNEKLERİ (İlk Kayıtlar):\n";
    echo "─────────────────────────────────────────────────────────────────\n\n";
    
    if (isset($criticalData['customers']) && $criticalData['customers'] > 0) {
        $customers = $pdo->query("SELECT id, name, email FROM customers LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        echo "Müşteriler:\n";
        foreach ($customers as $c) {
            echo "   • ID:{$c['id']} - {$c['name']}";
            if ($c['email']) echo " ({$c['email']})";
            echo "\n";
        }
        echo "\n";
    }
    
    if (isset($criticalData['users']) && $criticalData['users'] > 0) {
        $users = $pdo->query("SELECT id, username, role FROM users LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        echo "Kullanıcılar:\n";
        foreach ($users as $u) {
            echo "   • ID:{$u['id']} - {$u['username']} ({$u['role']})\n";
        }
        echo "\n";
    }
    
    if (isset($criticalData['jobs']) && $criticalData['jobs'] > 0) {
        $jobs = $pdo->query("SELECT id, status, total_amount FROM jobs LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        echo "İşler:\n";
        foreach ($jobs as $j) {
            echo "   • ID:{$j['id']} - Durum: {$j['status']}, Tutar: {$j['total_amount']} TL\n";
        }
        echo "\n";
    }
    
    echo "═══════════════════════════════════════════════════════════════════\n";
    
    if ($totalRecords > 0) {
        echo "✅ VERİTABANI DOLU VE ÇALIŞIR DURUMDA!\n";
    } else {
        echo "⚠️  VERİTABANI BOŞ!\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n\n";
    exit(1);
}

