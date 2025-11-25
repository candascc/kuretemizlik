# Production'da 500 Hatası Debug Rehberi

## Hızlı Kontrol Listesi

### 1. Error Log Dosyasını Kontrol Et

Production sunucuda error log dosyasını bulun ve kontrol edin:

**Olası log dosya konumları:**
- `/var/log/apache2/error.log` (Apache)
- `/var/log/nginx/error.log` (Nginx)
- `logs/error.log` (uygulama içi)
- `logs/app.log` (uygulama içi)
- PHP error_log ayarı: `php -i | grep error_log`

**Kontrol komutu (SSH ile):**
```bash
# Son 50 satırı göster
tail -n 50 /var/log/apache2/error.log

# Veya uygulama log klasöründe
tail -n 50 logs/error.log
```

### 2. Geçici Olarak APP_DEBUG Aç (SADECE DEBUG İÇİN!)

**⚠️ ÖNEMLİ: Debug bittikten sonra mutlaka kapatın!**

Production'da geçici olarak debug açmak için:

**Yöntem A: .env.production dosyası oluştur**
```bash
# Proje kök dizininde
echo "APP_DEBUG=true" > .env.production
```

**Yöntem B: config.php'de geçici override**
`config/config.php` dosyasında 82. satırı geçici olarak:
```php
define('APP_DEBUG', true); // Geçici - debug sonrası kaldır!
```

**Yöntem C: Sunucu ortam değişkeni (en güvenli)**
```bash
# Apache .htaccess veya virtual host config
SetEnv APP_DEBUG true

# Nginx config
fastcgi_param APP_DEBUG true;
```

### 3. Dosya İzinlerini Kontrol Et

```bash
# PHP dosyaları okunabilir olmalı
chmod 644 src/**/*.php
chmod 644 config/*.php

# Klasörler erişilebilir olmalı
chmod 755 src
chmod 755 config

# Log klasörü yazılabilir olmalı
chmod 755 logs
chmod 666 logs/*.log 2>/dev/null || true
```

### 4. PHP Versiyonu ve Extension'ları Kontrol Et

```bash
# PHP versiyonu
php -v

# Gerekli extension'lar
php -m | grep -E "pdo|sqlite|json|mbstring"
```

**Gerekli extension'lar:**
- `pdo`
- `pdo_sqlite`
- `sqlite3`
- `json`
- `mbstring`
- `openssl`

### 5. Database Dosyası ve İzinleri

```bash
# SQLite dosyası var mı?
ls -la db/app.sqlite

# Yazma izni var mı?
chmod 664 db/app.sqlite
chmod 755 db/
```

### 6. Composer Autoload

```bash
# Production'da composer install çalıştırıldı mı?
cd /path/to/app
composer install --no-dev --optimize-autoloader
```

### 7. Cache Temizleme

```bash
# Cache klasörünü temizle
rm -rf var/cache/*
rm -rf cache/*
```

### 8. Company Scope Sorunu (Olası Neden)

Production'da `Auth::companyId()` `null` dönebilir. Kontrol edin:

**Test için geçici kod ekleyin** (`ContractController::index()` başına):
```php
error_log("DEBUG: Auth::companyId() = " . var_export(Auth::companyId(), true));
error_log("DEBUG: Auth::check() = " . var_export(Auth::check(), true));
```

### 9. SQLite Database Path Sorunu

Production'da `DB_PATH` farklı olabilir. Kontrol edin:

```php
// config/config.php'de
error_log("DEBUG: DB_PATH = " . DB_PATH);
error_log("DEBUG: DB file exists = " . (file_exists(DB_PATH) ? 'YES' : 'NO'));
```

### 10. PHP Error Reporting Aç

`config/config.php` dosyasının başına geçici olarak ekleyin:

```php
// Geçici debug - sonra kaldır!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

## En Olası Sorunlar (Bizim Kodumuzla İlgili)

### Sorun 1: Company Scope SQL Hatası

`fetchJobContractsWithService()` metodunda `scopeToCompany` kullanımı production'da farklı davranabilir.

**Çözüm:** `ContractController.php` dosyasında 259-266. satırları kontrol edin.

### Sorun 2: Service Model Company Scope

`Service::getActive()` çağrısı production'da company scope nedeniyle boş dönebilir.

**Çözüm:** Try-catch zaten var, ama log ekleyin.

### Sorun 3: Missing Lang Keys

Production'da lang dosyası eksik veya farklı olabilir.

**Kontrol:** `lang/tr.php` dosyasının production'da mevcut olduğundan emin olun.

## Hızlı Test Scripti

Production'da çalıştırın:

```php
<?php
// test_debug.php - Proje kök dizininde oluşturun
require 'config/config.php';

echo "APP_DEBUG: " . (defined('APP_DEBUG') ? (APP_DEBUG ? 'true' : 'false') : 'NOT DEFINED') . "\n";
echo "DB_PATH: " . (defined('DB_PATH') ? DB_PATH : 'NOT DEFINED') . "\n";
echo "DB exists: " . (file_exists(DB_PATH) ? 'YES' : 'NO') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Auth::check(): " . (Auth::check() ? 'YES' : 'NO') . "\n";
echo "Auth::companyId(): " . var_export(Auth::companyId(), true) . "\n";

// Test database connection
try {
    $db = Database::getInstance();
    $result = $db->fetch("SELECT 1 as test");
    echo "Database connection: OK\n";
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "\n";
}

// Test Service model
try {
    $service = new Service();
    $services = $service->getActive();
    echo "Service::getActive(): " . count($services) . " services found\n";
} catch (Exception $e) {
    echo "Service::getActive(): FAILED - " . $e->getMessage() . "\n";
}

// Test JobContract model
try {
    $jc = new JobContract();
    $count = $jc->count([]);
    echo "JobContract::count(): " . $count . "\n";
} catch (Exception $e) {
    echo "JobContract::count(): FAILED - " . $e->getMessage() . "\n";
}
```

Bu script'i `test_debug.php` olarak kaydedip production'da çalıştırın:
```
https://kuretemizlik.com/app/test_debug.php
```

## Production'da Güvenli Debug

1. **Error log'u kontrol edin** (en güvenli)
2. **Geçici olarak APP_DEBUG=true yapın** (sadece debug için)
3. **Hata mesajını görüntüleyin**
4. **APP_DEBUG=false yapın** (mutlaka!)
5. **Sorunu düzeltin**

## Olası Çözümler

### Çözüm 1: Company Scope Null Check

`ContractController::fetchJobContractsWithService()` metodunda:

```php
// Company scope - null check ekle
$companyId = Auth::companyId();
if ($companyId) {
    $sql .= " AND j.company_id = " . (int)$companyId;
} else {
    // Production'da company_id null olabilir, log'la
    error_log("WARNING: Auth::companyId() is null in ContractController::fetchJobContractsWithService()");
}
```

### Çözüm 2: Service Model Try-Catch İyileştirme

Zaten var ama log ekleyin:

```php
try {
    $serviceModel = new Service();
    $services = $serviceModel->getActive();
} catch (Exception $e) {
    error_log("Error fetching services: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $services = [];
}
```

## Sonraki Adımlar

1. ✅ Production error log'unu kontrol edin
2. ✅ `test_debug.php` script'ini çalıştırın
3. ✅ Hata mesajını paylaşın
4. ✅ Birlikte çözelim

