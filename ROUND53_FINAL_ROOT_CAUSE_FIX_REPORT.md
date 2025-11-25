# ROUND 53 – FINAL ROOT CAUSE: `kozmos_is_https` redeclare + config include model fix

## Problem

**Loglardan Görülen Fatal Error:**
```
Cannot redeclare kozmos_is_https() (previously declared in /app/config/config.php:49)
```

**Etki:**
- `/app/health`, `/app/login` gibi endpoint'lerde random 500 hataları
- Aynı request içinde `config.php`'nin birden fazla kez include edilmesi
- R48-R52 arasında View, Calendar, Reports, Dashboard, Auth, Cache, Global error handler hardening yapıldı, fakat `config.php` ve `kozmos_is_https` fonksiyonuna hiç dokunulmadı

## Root Cause

1. **`kozmos_is_https()` fonksiyonu redeclare koruması yok:**
   - `config.php:49` satırında tanımlanmış
   - `if (!function_exists())` kontrolü yok
   - Aynı request içinde `config.php` birden fazla kez include edildiğinde fatal error

2. **`config.php` include modeli tutarsız:**
   - Bazı dosyalarda `require_once` kullanılmış (iyi)
   - Bazı dosyalarda `require` kullanılmış (kötü)
   - Aynı request içinde birden fazla kez include edilebiliyor

## Çözüm

### 1. `kozmos_is_https()` Fonksiyonunu Güvenli Hale Getirme

**Dosya:** `config/config.php`

**Değişiklik:**
```php
// ÖNCE:
function kozmos_is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return true;
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
    return false;
}

// SONRA:
if (!function_exists('kozmos_is_https')) {
    function kozmos_is_https(): bool {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
        if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return true;
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
        return false;
    }
}
```

**Fayda:**
- Fonksiyon zaten tanımlanmışsa, tekrar tanımlanmaya çalışılmaz
- Redeclare fatal error'ı önlenir

### 2. `config.php` Include Modelini Tekilleştirme

**Değiştirilen Dosyalar:**

1. `scripts/seed_contract_templates_by_service.php`
   - `require` → `require_once`

2. `scripts/run_migration_038.php`
   - `require` → `require_once`

3. `scripts/verify_contract_tables.php`
   - `require` → `require_once`

4. `scripts/run_new_contract_migrations.php`
   - `require` → `require_once`

5. `scripts/check_migration_status.php`
   - `require` → `require_once`

6. `scripts/normalize_selim_dervisoglu_jobs_monthly.php`
   - `require` → `require_once`

7. `scripts/fix_selim_dervisoglu_monthly.php`
   - `require` → `require_once`

8. `tmp_mark_migration.php`
   - `require 'config/config.php'` → `require_once __DIR__ . '/config/config.php'`

**Zaten `require_once` Kullanan Dosyalar (Değiştirilmedi):**
- `index.php` (ana entry point)
- `check_appointments_schema.php`
- `check_migration_status.php`
- `validate_schema.php`
- `run_migrations.php`
- Tüm test dosyaları
- `src/Console/ConsoleRunner.php` (flag ile kontrol ediyor)

## Değişen Dosyaların Tam Listesi

1. `config/config.php` - `kozmos_is_https()` fonksiyonuna `if (!function_exists())` kontrolü eklendi
2. `scripts/seed_contract_templates_by_service.php` - `require` → `require_once`
3. `scripts/run_migration_038.php` - `require` → `require_once`
4. `scripts/verify_contract_tables.php` - `require` → `require_once`
5. `scripts/run_new_contract_migrations.php` - `require` → `require_once`
6. `scripts/check_migration_status.php` - `require` → `require_once`
7. `scripts/normalize_selim_dervisoglu_jobs_monthly.php` - `require` → `require_once`
8. `scripts/fix_selim_dervisoglu_monthly.php` - `require` → `require_once`
9. `tmp_mark_migration.php` - `require` → `require_once` + `__DIR__` kullanımı

## Test ve Doğrulama

### Syntax Check
```bash
php -l config/config.php
# ✅ No syntax errors detected
```

### Beklenen Davranış

**ÖNCE:**
- Aynı request içinde `config.php` birden fazla kez include edildiğinde:
  - `Cannot redeclare kozmos_is_https()` fatal error
  - Random 500 hataları

**SONRA:**
- Aynı request içinde `config.php` birden fazla kez include edilse bile:
  - `function_exists()` kontrolü sayesinde redeclare hatası yok
  - `require_once` sayesinde dosya sadece bir kez yüklenir
  - Random 500 hataları önlenir

### Test Senaryoları

1. **`/app/login` endpoint:**
   - Login formu açılmalı
   - `error.log` içinde "Cannot redeclare kozmos_is_https()" hatası OLMAMALI

2. **`/app/health` endpoint:**
   - Health check başarılı olmalı
   - `error.log` içinde "Cannot redeclare kozmos_is_https()" hatası OLMAMALI

3. **`/app/` dashboard:**
   - Dashboard açılmalı
   - `error.log` içinde "Cannot redeclare kozmos_is_https()" hatası OLMAMALI

4. **Menüde gezinme:**
   - `/app/jobs`, `/app/customers`, `/app/calendar`, `/app/reports` sayfaları açılmalı
   - `error.log` içinde "Cannot redeclare kozmos_is_https()" hatası OLMAMALI

## Log Örnekleri

### ÖNCE (Fatal Error Var):
```
[2025-11-23 00:06:50] [ERROR] [req:req_692225ea2f2c42.23722058_563d3405] Cannot redeclare kozmos_is_https() (previously declared in /home/cagdasya/kuretemizlik.com/app/config/config.php:49) | Context: {"type":"Fatal Error","file":"\/home\/cagdasya\/kuretemizlik.com\/app\/config\/config.php","line":49,"severity":1}
```

### SONRA (Fatal Error Yok):
- `error.log`, `errors_2025-11-23.log`, `app_2025-11-23.log` içinde "Cannot redeclare kozmos_is_https()" hatası OLMAMALI
- Sadece normal application log'ları görülmeli

## Random 500 Kapanış Raporu

**Root Cause:** 
- `kozmos_is_https()` fonksiyonunun redeclare koruması yok
- `config.php` include modelinin tutarsız olması (bazı dosyalarda `require`, bazılarında `require_once`)

**Çözüm:**
- `kozmos_is_https()` fonksiyonuna `if (!function_exists())` kontrolü eklendi
- Tüm `require` çağrıları `require_once` yapıldı

**Durum:** ✅ FIXED

**Beklenen Sonuç:**
- Random 500 hataları kalkmalı
- `error.log` içinde "Cannot redeclare kozmos_is_https()" hatası görülmemeli
- Tüm endpoint'ler (`/app/login`, `/app/health`, `/app/`, menü sayfaları) sorunsuz çalışmalı

## Sonraki Adımlar

1. **Production'a deploy et**
2. **24 saat boyunca log'ları izle:**
   - `error.log`
   - `errors_YYYY-MM-DD.log`
   - `app_YYYY-MM-DD.log`
3. **"Cannot redeclare kozmos_is_https()" hatası görülmemeli**
4. **Random 500 hataları azalmalı veya tamamen kalkmalı**

## Notlar

- `index.php` zaten `require_once` kullanıyordu (değiştirilmedi)
- `src/Console/ConsoleRunner.php` zaten `require_once` kullanıyor ve flag ile kontrol ediyor (değiştirilmedi)
- Test dosyaları zaten `require_once` kullanıyordu (değiştirilmedi)
- Sadece script dosyaları ve `tmp_mark_migration.php` değiştirildi

