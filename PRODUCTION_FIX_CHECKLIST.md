# Production 500 Hatası - Hızlı Çözüm Kontrol Listesi

## ✅ Yapılan Düzeltmeler

1. **Company Scope Güvenli Hale Getirildi**
   - `ContractController::fetchJobContractsWithService()` metodunda `Auth::companyId()` direkt kontrolü eklendi
   - `ContractController::countJobContracts()` metodunda aynı düzeltme yapıldı
   - Production'da `Auth::companyId()` null olursa güvenli fallback mekanizması eklendi

2. **Service Model Try-Catch İyileştirildi**
   - `Service::getActive()` çağrısı try-catch ile sarıldı
   - Hata durumunda boş array döndürülüyor

## 🔍 Production'da Kontrol Edilmesi Gerekenler

### 1. Error Log Kontrolü (ÖNCE BUNU YAPIN!)

**SSH ile sunucuya bağlanın ve şu komutu çalıştırın:**

```bash
# Apache error log
tail -n 50 /var/log/apache2/error.log

# Veya uygulama log klasörü
tail -n 50 /path/to/app/logs/error.log

# Veya PHP error log
tail -n 50 /var/log/php_errors.log
```

**En son hatayı bulun ve paylaşın!**

### 2. Test Debug Script Çalıştırın

Production'da `test_debug.php` dosyasını çalıştırın:

```
https://kuretemizlik.com/app/test_debug.php
```

Bu script sistem durumunu kontrol eder ve olası sorunları gösterir.

### 3. Geçici APP_DEBUG Açma (SADECE DEBUG İÇİN!)

**⚠️ ÖNEMLİ: Debug bittikten sonra mutlaka kapatın!**

Production'da geçici olarak debug açmak için:

**Yöntem A: .env.production dosyası oluştur**
```bash
cd /path/to/app
echo "APP_DEBUG=true" > .env.production
```

**Yöntem B: config.php'de geçici override (82. satır)**
```php
define('APP_DEBUG', true); // Geçici - debug sonrası kaldır!
```

**Yöntem C: .htaccess (Apache)**
```apache
SetEnv APP_DEBUG true
```

### 4. Dosya İzinleri Kontrolü

```bash
# PHP dosyaları
find src -type f -name "*.php" -exec chmod 644 {} \;
find config -type f -name "*.php" -exec chmod 644 {} \;

# Klasörler
find src -type d -exec chmod 755 {} \;
chmod 755 config

# Log klasörü
chmod 755 logs
chmod 666 logs/*.log 2>/dev/null || true
```

### 5. Database İzinleri

```bash
chmod 664 db/app.sqlite
chmod 755 db/
```

### 6. Composer Autoload

```bash
cd /path/to/app
composer install --no-dev --optimize-autoloader
```

### 7. Cache Temizleme

```bash
rm -rf var/cache/*
rm -rf cache/*
```

## 🎯 En Olası Sorunlar

### Sorun 1: Company Scope Null

**Belirti:** `Auth::companyId()` production'da `null` döner

**Çözüm:** ✅ Zaten düzeltildi - güvenli fallback eklendi

### Sorun 2: Service Model Company Scope

**Belirti:** `Service::getActive()` boş array döner veya hata verir

**Çözüm:** ✅ Try-catch eklendi, boş array döndürülüyor

### Sorun 3: Missing Lang Keys

**Belirti:** `__('contracts.admin.index.xxx')` undefined key hatası

**Kontrol:** `lang/tr.php` dosyasının production'da mevcut olduğundan emin olun

### Sorun 4: PHP Extension Eksik

**Kontrol:**
```bash
php -m | grep -E "pdo|sqlite|json|mbstring"
```

**Gerekli:** `pdo`, `pdo_sqlite`, `sqlite3`, `json`, `mbstring`

### Sorun 5: Database Path Sorunu

**Kontrol:** `config/config.php` içinde `DB_PATH` doğru mu?

Production'da mutlak yol gerekebilir:
```php
define('DB_PATH', '/full/path/to/app/db/app.sqlite');
```

## 📋 Hızlı Test Adımları

1. ✅ `test_debug.php` script'ini production'da çalıştırın
2. ✅ Error log'u kontrol edin
3. ✅ Geçici olarak `APP_DEBUG=true` yapın
4. ✅ `/contracts` sayfasını açın ve hata mesajını görüntüleyin
5. ✅ Hata mesajını paylaşın
6. ✅ Sorunu düzeltin
7. ✅ `APP_DEBUG=false` yapın (mutlaka!)

## 🔧 Acil Çözüm (Eğer Hala Çalışmıyorsa)

Eğer yukarıdakiler işe yaramazsa, `ContractController::index()` metodunun başına geçici log ekleyin:

```php
public function index()
{
    Auth::require();
    
    // Geçici debug log
    error_log("ContractController::index() - START");
    error_log("Auth::companyId() = " . var_export(Auth::companyId(), true));
    
    try {
        // ... mevcut kod
    } catch (Exception $e) {
        error_log("ContractController::index() - ERROR: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
}
```

Sonra error log'u kontrol edin ve hatayı görün.

## 📞 Sonraki Adım

1. Production error log'unu kontrol edin
2. `test_debug.php` çıktısını paylaşın
3. Hata mesajını paylaşın
4. Birlikte çözelim!

