# ROUND 50 – STAGE 1: PHP 8 Uyumluluk Süpürgesi

## PHP Lint Sonuçları

### Syntax Errors
✅ **Hiç syntax error yok** - Tüm PHP dosyaları parse edilebiliyor

### Runtime Fatal Error Riskleri

PHP 8'de optional parameter before required parameter **runtime fatal error** üretir (parse error değil). Bu yüzden static analysis ile tespit edilemez, runtime'da patlar.

## Tespit Edilen İmza Sorunları

### 1. SecurityStatsService::getRecentSecurityEvents (KRİTİK)

**Dosya:** `src/Services/SecurityStatsService.php:233`

**Mevcut İmza:**
```php
private function getRecentSecurityEvents(?int $companyId = null, int $limit = 20): array
```

**Sorun:** Optional parameter `$companyId = null` required parameter `$limit`'den önce

**PHP 8 Davranışı:** Fatal error: "Optional parameter $companyId declared before required parameter $limit"

**Çözüm:**
```php
private function getRecentSecurityEvents(int $limit = 20, ?int $companyId = null): array
```

**Not:** Bu fonksiyon daha önce düzeltilmiş olmalıydı ama hala yanlış sırada görünüyor. Kontrol edilmeli.

### 2. Diğer Potansiyel Sorunlar

Grep sonuçlarına göre, çoğu fonksiyon doğru sırada (required önce, optional sonra). Ancak şu pattern'ler kontrol edilmeli:

- `function method($optional = null, $required)` → ❌ Fatal error
- `function method($optional = null, $required = value)` → ✅ OK (ikisi de optional)
- `function method($required, $optional = null)` → ✅ OK (doğru sıra)

## Auto-Fix Tasarımı

### Pattern 1: Optional Before Required (Fatal Error)

**Tespit:**
```php
function method($optional = null, $required)
```

**Fix:**
```php
function method($required, $optional = null)
```

**Geriye Dönük Uyumluluk:**
- Eğer fonksiyon çağrıları named arguments kullanmıyorsa, parametre sırası değiştiği için breaking change olabilir
- Tüm çağrı noktaları kontrol edilmeli

### Pattern 2: Interface/Parent Class Uyumsuzluğu

**Tespit:**
- `implements` veya `extends` eden sınıflarda override edilen metod imzaları
- Parent/interface ile uyumsuz parametre sırası veya tip

**Fix:**
- Parent/interface imzası ile bire bir uyumlu hale getir
- Gerekirse parametreleri `?type` veya `= null` ile genişlet

## Uygulanacak Düzeltmeler

### 1. SecurityStatsService::getRecentSecurityEvents

**Değişiklik:**
- Parametre sırası: `getRecentSecurityEvents(int $limit = 20, ?int $companyId = null)`
- Tüm çağrı noktaları kontrol edilmeli

### 2. Diğer Kontroller

Grep sonuçlarına göre diğer fonksiyonlar doğru sırada görünüyor. Ancak runtime test ile doğrulanmalı.

## Sonraki Adım
STAGE 2: Global error handler & shutdown path hardening - index.php'deki `try/catch(Exception $e)` → `try/catch(Throwable $e)` değişikliği

