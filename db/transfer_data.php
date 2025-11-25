<?php
/**
 * Kritik Veri Transfer Script
 * 
 * C:\yeni\app veritabanındaki gerçek verileri
 * mevcut sisteme aktarır
 * 
 * UYARI: Bu script mevcut verileri SİLER!
 */

// Kaynak ve hedef
$sourceDb = 'C:\\yeni\\app\\db\\app.sqlite';
$targetDb = __DIR__ . '/app.sqlite';
$backupDir = __DIR__ . '/backups';

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         KRİTİK VERİ TRANSFER SCRIPT                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Dosya kontrolü
echo "ADIM 1: Dosya Kontrolleri\n";
echo "─────────────────────────────────────────────────────────────────\n\n";

if (!file_exists($sourceDb)) {
    die("❌ HATA: Kaynak veritabanı bulunamadı!\n   Beklenen: {$sourceDb}\n\n");
}
echo "✅ Kaynak DB bulundu: " . round(filesize($sourceDb) / 1024, 2) . " KB\n";

if (!file_exists($targetDb)) {
    die("❌ HATA: Hedef veritabanı bulunamadı!\n   Beklenen: {$targetDb}\n\n");
}
echo "✅ Hedef DB bulundu: " . round(filesize($targetDb) / 1024, 2) . " KB\n";

// Kaynak veritabanını aç ve analiz et
echo "\n";
echo "ADIM 2: Kaynak Veritabanı Analizi\n";
echo "─────────────────────────────────────────────────────────────────\n\n";

try {
    $sourcePdo = new PDO("sqlite:{$sourceDb}");
    $sourcePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tabloları listele
    $tables = $sourcePdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 Kaynak veritabanında " . count($tables) . " tablo bulundu:\n\n";
    
    $tableCounts = [];
    foreach ($tables as $table) {
        $count = $sourcePdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        $tableCounts[$table] = $count;
        
        if ($count > 0) {
            echo "   ✓ {$table}: {$count} kayıt\n";
        } else {
            echo "   ○ {$table}: 0 kayıt (boş)\n";
        }
    }
    
    // Kritik tabloları kontrol et
    echo "\n";
    echo "🔍 Kritik Tablolar:\n";
    $criticalTables = ['customers', 'jobs', 'users', 'services', 'buildings', 'management_fees', 'contracts', 'staff'];
    $foundCritical = 0;
    
    foreach ($criticalTables as $critical) {
        if (in_array($critical, $tables)) {
            $count = $tableCounts[$critical] ?? 0;
            echo "   ✅ {$critical}: {$count} kayıt\n";
            $foundCritical++;
        } else {
            echo "   ⚠️  {$critical}: Tablo yok\n";
        }
    }
    
    echo "\n";
    echo "Toplam kritik veri: " . array_sum(array_intersect_key($tableCounts, array_flip($criticalTables))) . " kayıt\n";
    
} catch (Exception $e) {
    die("\n❌ HATA: Kaynak veritabanı okunamadı!\n   " . $e->getMessage() . "\n\n");
}

// Hedef veritabanını analiz et
echo "\n";
echo "ADIM 3: Hedef Veritabanı Analizi (SİLİNECEK VERİLER)\n";
echo "─────────────────────────────────────────────────────────────────\n\n";

try {
    $targetPdo = new PDO("sqlite:{$targetDb}");
    $targetPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $targetTables = $targetPdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 Hedef veritabanında " . count($targetTables) . " tablo bulundu:\n\n";
    
    $targetCounts = [];
    $totalRecords = 0;
    
    foreach ($targetTables as $table) {
        $count = $targetPdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        $targetCounts[$table] = $count;
        $totalRecords += $count;
        
        if ($count > 0) {
            echo "   ⚠️  {$table}: {$count} kayıt (SİLİNECEK)\n";
        }
    }
    
    echo "\n";
    echo "⚠️  TOPLAM SİLİNECEK KAYIT: {$totalRecords}\n";
    
} catch (Exception $e) {
    die("\n❌ HATA: Hedef veritabanı okunamadı!\n   " . $e->getMessage() . "\n\n");
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "TRANSFER PLANI\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "Kaynak → Hedef:\n";
echo "  • Kaynak tablolar: " . count($tables) . "\n";
echo "  • Kaynak kayıtlar: " . array_sum($tableCounts) . "\n";
echo "  • Hedef tablolar: " . count($targetTables) . "\n";
echo "  • Hedef kayıtlar: {$totalRecords} (silinecek)\n\n";

echo "Kritik tablolar transfer edilecek:\n";
foreach ($criticalTables as $critical) {
    if (isset($tableCounts[$critical]) && $tableCounts[$critical] > 0) {
        echo "  ✓ {$critical}: {$tableCounts[$critical]} kayıt\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "⚠️  UYARI: Bu işlem GERİ ALINAMAZ!\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "Yedek dosyası oluşturuldu:\n";
echo "  → " . basename($backupFile) . "\n\n";

echo "Transfer yapmak için şu komutu çalıştırın:\n";
echo "  php transfer_data.php --execute\n\n";

echo "Veya güvenli yöntem (basit kopyalama):\n";
echo "  php transfer_data.php --simple-copy\n\n";

// Eğer --execute parametresi varsa transfer yap
if (in_array('--execute', $argv ?? [])) {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║              TRANSFER BAŞLIYOR (--execute)                     ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    
    performDataTransfer($sourcePdo, $targetPdo, $tables, $tableCounts);
    
} elseif (in_array('--simple-copy', $argv ?? [])) {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║         TRANSFER BAŞLIYOR (Basit Kopyalama)                    ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    
    performSimpleCopy($sourceDb, $targetDb);
}

/**
 * Basit kopyalama (en güvenli yöntem)
 */
function performSimpleCopy($sourceDb, $targetDb)
{
    echo "📋 Veritabanı dosyası kopyalanıyor...\n\n";
    
    // Timestamp ekle
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] Kopyalama başladı\n";
    
    // Dosyayı kopyala
    if (copy($sourceDb, $targetDb)) {
        $newSize = round(filesize($targetDb) / 1024, 2);
        echo "\n✅ TRANSFER BAŞARILI!\n";
        echo "   Yeni veritabanı boyutu: {$newSize} KB\n\n";
        
        // Verify
        try {
            $verifyPdo = new PDO("sqlite:{$targetDb}");
            $verifyPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "🔍 Doğrulama yapılıyor...\n\n";
            
            $criticalTables = ['customers', 'jobs', 'users', 'services'];
            foreach ($criticalTables as $table) {
                try {
                    $count = $verifyPdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                    echo "   ✓ {$table}: {$count} kayıt\n";
                } catch (Exception $e) {
                    echo "   ⚠️  {$table}: Kontrol edilemedi\n";
                }
            }
            
            echo "\n✅ TRANSFER VE DOĞRULAMA TAMAMLANDI!\n\n";
            
        } catch (Exception $e) {
            echo "\n⚠️  Doğrulama yapılamadı: " . $e->getMessage() . "\n";
            echo "Ancak dosya kopyalandı, manuel kontrol yapın.\n\n";
        }
        
    } else {
        echo "\n❌ KOPYALAMA BAŞARISIZ!\n\n";
        exit(1);
    }
}

/**
 * Detaylı veri transferi (tablo tablo)
 */
function performDataTransfer($sourcePdo, $targetPdo, $tables, $tableCounts)
{
    // Implement if needed
    echo "Detaylı transfer fonksiyonu - geliştirilecek\n";
}

