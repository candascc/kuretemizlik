# 🔐 Multi-Factor Authentication (MFA) Setup Guide

**ROUND 4 - STAGE 1: Real TOTP Implementation**

---

## 📋 GENEL BİLGİ

Bu doküman, sistemdeki Multi-Factor Authentication (MFA) implementasyonunu açıklar. MFA, TOTP (Time-Based One-Time Password, RFC 6238) standardını kullanarak Google Authenticator, Microsoft Authenticator ve benzeri uygulamalarla uyumlu çalışır.

---

## 🗄️ VERİTABANI YAPISI

### Mevcut Kolonlar (`users` tablosu)

MFA için gerekli kolonlar **zaten mevcut** (migration gerekmez):

- `two_factor_secret` (TEXT, nullable) - Base32 encoded TOTP secret
- `two_factor_backup_codes` (TEXT, nullable) - JSON array of recovery codes
- `two_factor_enabled_at` (TEXT, nullable) - MFA enable timestamp
- `two_factor_required` (INTEGER, default 0) - MFA enabled flag (0 = disabled, 1 = enabled)

---

## ⚙️ KONFİGÜRASYON

### `config/security.php`

```php
'mfa' => [
    'enabled' => env('SECURITY_MFA_ENABLED', false), // Global MFA toggle
    'methods' => explode(',', env('SECURITY_MFA_METHODS', 'totp')), // Available methods
    'required_for_roles' => explode(',', env('SECURITY_MFA_REQUIRED_ROLES', 'SUPERADMIN')), // Roles that require MFA
],
```

**Varsayılan Değerler:**
- `enabled`: `false` (MFA kapalı)
- `methods`: `['totp']` (TOTP destekleniyor)
- `required_for_roles`: `['SUPERADMIN']` (Sadece SUPERADMIN için zorunlu)

---

## 🔧 MfaService API

### Temel Metodlar

#### `isEnabled(): bool`
Global MFA'nın açık olup olmadığını kontrol eder.

#### `isRequiredForUser(array $user): bool`
Belirli bir kullanıcı için MFA'nın zorunlu olup olmadığını kontrol eder (rol bazlı).

#### `isEnabledForUser(array $user): bool`
Kullanıcının MFA'sının aktif olup olmadığını kontrol eder (DB'de `two_factor_required = 1` ve secret var mı).

#### `generateSecret(): string`
Yeni bir TOTP secret üretir (Base32 encoded).

#### `getOtpUri(array $user, string $secret): string`
QR code için `otpauth://` URI üretir (Google Authenticator uyumlu).

#### `verifyTotpCode(string $secret, string $code, ?int $timestamp = null): bool`
TOTP kodunu doğrular (RFC 6238, ±1 time step tolerance).

#### `verifyRecoveryCode(array $user, string $code): bool`
Recovery code'u doğrular ve kullanılan kodu listeden çıkarır.

#### `generateRecoveryCodes(): array`
10 adet recovery code üretir (XXXX-XXXX formatında).

#### `enableMfa(int $userId, string $secret, array $recoveryCodes): bool`
Kullanıcı için MFA'yı aktif eder.

#### `disableMfa(int $userId): bool`
Kullanıcı için MFA'yı devre dışı bırakır.

#### `startMfaChallenge(array $user, string $method = 'totp'): array`
MFA challenge başlatır (login flow'da kullanılır).

#### `verifyMfaCode(array $user, string $code, ?string $challengeId = null): array`
MFA kodunu doğrular (TOTP veya recovery code).

---

## 🔐 TOTP STANDARTI (RFC 6238)

### Özellikler:
- **Time Step**: 30 saniye
- **Code Length**: 6 haneli
- **Algorithm**: HMAC-SHA1
- **Tolerance**: ±1 time step (clock skew için)
- **Secret Length**: 20 bytes (160 bits), Base32 encoded

### Uyumluluk:
- ✅ Google Authenticator
- ✅ Microsoft Authenticator
- ✅ Authy
- ✅ 1Password
- ✅ LastPass Authenticator
- ✅ Diğer RFC 6238 uyumlu TOTP uygulamaları

---

## 📱 KULLANIM ÖRNEKLERİ

### MFA'yı Aktif Etme

```php
// 1. Secret üret
$secret = MfaService::generateSecret();

// 2. Recovery codes üret
$recoveryCodes = MfaService::generateRecoveryCodes();

// 3. OTP URI al (QR code için)
$otpUri = MfaService::getOtpUri($user, $secret);

// 4. MFA'yı aktif et
MfaService::enableMfa($userId, $secret, $recoveryCodes);
```

### TOTP Kodu Doğrulama

```php
// Kullanıcıdan gelen 6 haneli kodu doğrula
$isValid = MfaService::verifyTotpCode($secret, $code);

// veya MfaService::verifyMfaCode() kullan (recovery code desteği ile)
$result = MfaService::verifyMfaCode($user, $code);
if ($result['success']) {
    // MFA doğrulandı
    if ($result['used_recovery_code'] ?? false) {
        // Recovery code kullanıldı
    }
}
```

### Recovery Code Doğrulama

```php
$isValid = MfaService::verifyRecoveryCode($user, $code);
// Not: verifyRecoveryCode() kullanılan kodu otomatik olarak listeden çıkarır
```

---

## 🔄 LOGIN FLOW ENTEGRASYONU

### Mevcut Akış (AuthController):

1. Username/password doğrulama
2. Rate limit kontrolü
3. IP access control kontrolü
4. **MFA kontrolü** (eğer `isRequiredForUser()` true dönerse):
   - `MfaService::startMfaChallenge()` çağrılır
   - Kullanıcı `/mfa/verify` sayfasına yönlendirilir
   - Session'da `mfa_challenge` state'i saklanır
5. MFA doğrulandıktan sonra normal login akışı devam eder

### MFA Challenge Akışı:

1. Kullanıcı TOTP kodunu girer
2. `MfaService::verifyMfaCode()` çağrılır
3. TOTP veya recovery code doğrulanır
4. Başarılıysa session'da `mfa_challenge` temizlenir
5. Normal login akışına devam edilir

---

## 🛡️ GÜVENLİK NOTLARI

1. **Secret Storage**: TOTP secret'lar Base32 encoded olarak DB'de saklanır. Production'da secret'ları asla log'lamayın.

2. **Recovery Codes**: Recovery codes JSON array olarak DB'de saklanır. Her kullanıldığında listeden çıkarılır.

3. **Time Window**: TOTP doğrulama ±1 time step (30 saniye) tolerance ile yapılır (clock skew için).

4. **Challenge Expiration**: MFA challenge'ları 5 dakika sonra expire olur.

5. **Rate Limiting**: MFA doğrulama denemeleri rate limit'e tabidir (mevcut login rate limit mekanizması).

---

## 📊 AUDIT LOGGING

MFA ile ilgili tüm olaylar `AuditLogger` üzerinden loglanır:

- `MFA_ENABLED` - MFA aktif edildi
- `MFA_DISABLED` - MFA devre dışı bırakıldı
- `MFA_CHALLENGE_STARTED` - MFA challenge başlatıldı
- `MFA_CHALLENGE_PASSED` - MFA doğrulandı (TOTP)
- `MFA_CHALLENGE_FAILED` - MFA doğrulama başarısız
- `MFA_RECOVERY_CODE_USED` - Recovery code kullanıldı

---

## 🧪 TEST ETME

### Manuel Test:

1. MFA'yı aktif et (`MfaService::enableMfa()`)
2. QR code'u Google Authenticator'a ekle
3. Login yap
4. TOTP kodunu gir
5. Başarılı login doğrula

### Recovery Code Test:

1. MFA aktif kullanıcı ile login yap
2. Recovery code kullan
3. Kullanılan kodun listeden çıktığını doğrula

---

## 🔗 İLGİLİ DOSYALAR

- `src/Services/MfaService.php` - MFA servisi
- `src/Controllers/AuthController.php` - Login flow entegrasyonu
- `config/security.php` - MFA konfigürasyonu
- `src/Models/User.php` - User model (MFA kolonları)

---

**ROUND 4 - STAGE 1 Tamamlandı** ✅

