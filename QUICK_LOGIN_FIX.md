# Hızlı Login Sorunu Çözümü

## Sorun
Şifreyi sıfırladıktan sonra hala "Kullanıcı adı veya şifre hatalı" hatası alınıyor.

## Hızlı Çözüm Adımları

### Adım 1: Test Script'i Çalıştır
1. `test_login_detailed.php` dosyasını canlı sunucuya yükleyin
2. Tarayıcıdan açın: `https://yourdomain.com/test_login_detailed.php`
3. Kullanıcı adı: `candas`
4. Şifre: Sıfırladığınız şifre
5. "Login Testi Yap" butonuna tıklayın
6. Sonuçları inceleyin

### Adım 2: Log Dosyalarını Kontrol Et
Canlı sunucuda log dosyalarını kontrol edin:

```bash
# SSH ile bağlanın
tail -f logs/error.log
# veya
tail -f var/logs/app.log
```

Login denemesi yapın ve log'ları izleyin.

### Adım 3: CSRF Token Kontrolü
Login form'unda CSRF token olup olmadığını kontrol edin:

1. Login sayfasını açın: `https://yourdomain.com/login`
2. Sayfa kaynağını görüntüleyin (Ctrl+U veya F12)
3. `<input type="hidden" name="csrf_token"` arayın
4. Eğer yoksa, login form'unda CSRF token eksik demektir

### Adım 4: Session Kontrolü
Browser console'da (F12) şunu çalıştırın:

```javascript
document.cookie
```

`PHPSESSID` veya `temizlik_sess` cookie'sinin olup olmadığını kontrol edin.

### Adım 5: Password Hash'i Doğrudan Güncelle
Eğer hala çalışmıyorsa, SQL ile doğrudan güncelleyin:

```sql
-- Yeni bir şifre hash'i oluştur
-- Bu komutu PHP'de çalıştırın veya SQLite CLI kullanın

UPDATE users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    updated_at = datetime('now')
WHERE username = 'candas';

-- Bu hash 'password' şifresine karşılık gelir
-- Test için kullanın, sonra değiştirin
```

Veya PHP ile:

```php
<?php
// direct_password_update.php - TEK SEFERLİK
require_once 'config/config.php';
require_once 'src/Lib/Database.php';

$db = Database::getInstance();

// Yeni şifre
$newPassword = 'YourNewPassword123!';
$username = 'candas';

// Hash oluştur
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

// Güncelle
$stmt = $db->getPdo()->prepare("UPDATE users SET password_hash = ?, updated_at = datetime('now') WHERE username = ?");
$stmt->execute([$hash, $username]);

echo "✅ Şifre güncellendi!\n";
echo "Username: $username\n";
echo "Password: $newPassword\n";
echo "Hash: $hash\n";
?>
```

### Adım 6: Session Cookie Path Kontrolü
`index.php` dosyasının başında session cookie path'inin `/` olduğundan emin olun:

```php
ini_set('session.cookie_path', '/');
```

### Adım 7: APP_BASE Kontrolü
`config/config.php` dosyasında `APP_BASE` değerini kontrol edin:

```php
define('APP_BASE', '/app');
```

Eğer canlı sunucuda uygulama root'ta çalışıyorsa:

```php
define('APP_BASE', '');
```

## Yaygın Sorunlar ve Çözümleri

### Sorun 1: CSRF Token Hatası
**Belirti:** "Güvenlik hatası" veya "CSRF validation failed" log'ları

**Çözüm:**
- Login form'unda `<?= CSRF::field() ?>` olup olmadığını kontrol edin
- Session'ın çalıştığından emin olun
- Cookie'lerin engellenmediğinden emin olun

### Sorun 2: Session Cookie Path Hatası
**Belirti:** Her istekte yeni session oluşturuluyor

**Çözüm:**
- `index.php` başında `ini_set('session.cookie_path', '/');` olduğundan emin olun
- `.htaccess` dosyasında session ayarları olmadığından emin olun

### Sorun 3: Password Hash Güncellenmemiş
**Belirti:** Şifreyi sıfırladınız ama hala eski hash kullanılıyor

**Çözüm:**
- Veritabanını doğrudan kontrol edin:
```sql
SELECT username, password_hash, updated_at FROM users WHERE username = 'candas';
```
- `fix_user_password.php` script'ini kullanarak tekrar güncelleyin

### Sorun 4: Veritabanı Farklı Konumda
**Belirti:** Kullanıcılar görünüyor ama login çalışmıyor

**Çözüm:**
- `config/config.php` içinde `DB_PATH` değerini kontrol edin
- Gerçek veritabanı dosyasının o konumda olduğundan emin olun

## Debug Checklist

- [ ] `test_login_detailed.php` script'i çalıştırıldı ve sonuçlar incelendi
- [ ] Log dosyaları kontrol edildi
- [ ] CSRF token login form'unda mevcut
- [ ] Session cookie oluşturuluyor
- [ ] Password hash güncel
- [ ] `APP_BASE` doğru ayarlanmış
- [ ] Session cookie path `/` olarak ayarlanmış
- [ ] Veritabanı doğru konumda ve yazılabilir

## Test Sonrası
- `test_login_detailed.php` dosyasını silin
- `debug_login.php` dosyasını silin
- `fix_user_password.php` dosyasını silin
- Log dosyalarında hassas bilgi varsa temizleyin

