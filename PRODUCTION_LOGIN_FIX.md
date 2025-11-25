# Canlı Sunucuda Login Sorunu - Çözüm Kılavuzu

## Sorun
Canlı sunucuda login yaparken "kullanıcı adı veya şifre yanlış" hatası alınıyor, ama aynı dosyalar lokalde çalışıyor.

## Olası Nedenler

### 1. Veritabanı Farklı/Yeni
- Canlı sunucuda veritabanı yeni oluşturulmuş ve boş olabilir
- Lokaldeki kullanıcılar canlıda yok

### 2. Veritabanı Dosyası Kopyalanmamış
- Lokaldeki `db/app.db` dosyası canlıya kopyalanmamış olabilir

### 3. Password Hash Encoding Sorunu
- Password hash'leri farklı encoding ile kaydedilmiş olabilir

### 4. Dosya İzinleri
- Veritabanı dosyasına yazma izni yok

## Çözüm Adımları

### Adım 1: Debug Script Çalıştırma
1. `debug_login.php` dosyasını canlı sunucuya yükleyin
2. Tarayıcıdan erişin: `https://yourdomain.com/debug_login.php`
3. Raporu inceleyin ve sorunları tespit edin

### Adım 2A: Veritabanı Kopyalama (Önerilen)
Lokaldeki veritabanını canlıya kopyalayın:

```bash
# Lokalde (veritabanı dosyasının konumunu bulun)
# Genellikle: app/db/app.db

# FTP/SFTP ile canlı sunucuya yükleyin
# Canlı sunucuda: app/db/app.db konumuna yerleştirin

# Dosya izinlerini ayarlayın
chmod 664 db/app.db
chmod 775 db/
```

### Adım 2B: Yeni Kullanıcı Oluşturma (Alternatif)
Eğer veritabanı kopyalayamıyorsanız, yeni kullanıcı oluşturun:

1. Lokalde bir kullanıcının password hash'ini alın:
```sql
SELECT username, password_hash FROM users WHERE username = 'candas';
```

2. Canlı sunucuda veritabanına manuel olarak ekleyin veya şu script'i kullanın:

```php
<?php
// create_user.php - TEK SEFERLİK KULLANIM
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Lib/Database.php';

$db = Database::getInstance();

$username = 'candas';
$password = 'YourPasswordHere'; // Lokaldeki şifreyi girin
$role = 'ADMIN';

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->getPdo()->prepare("INSERT OR REPLACE INTO users (username, password_hash, role, is_active) VALUES (?, ?, ?, 1)");
$stmt->execute([$username, $passwordHash, $role]);

echo "Kullanıcı oluşturuldu!";
?>
```

### Adım 3: Dosya İzinleri Kontrolü
Canlı sunucuda dosya izinlerini kontrol edin:

```bash
# Veritabanı dizini
ls -la db/

# Veritabanı dosyası yazılabilir olmalı
chmod 664 db/app.db
chmod 775 db/
```

### Adım 4: Session Cookie Path Kontrolü
`index.php` dosyasında session cookie path'inin `/` olarak ayarlandığından emin olun:

```php
// index.php başında
ini_set('session.cookie_path', '/');
```

### Adım 5: Environment Variables Kontrolü
`env.local` dosyasının canlı sunucuda mevcut olduğundan ve doğru ayarlarla olduğundan emin olun:

```bash
# env.local dosyasını kontrol edin
cat env.local

# Gerekirse lokaldeki env.local'ı kopyalayın
```

### Adım 6: Log Dosyalarını İnceleme
Login denemelerini log dosyalarında kontrol edin:

```bash
# Log dosyalarını kontrol edin
tail -f logs/app.log
# veya
tail -f var/logs/app.log
```

## Hızlı Test

### Test 1: Kullanıcı Var mı?
```sql
SELECT COUNT(*) FROM users;
-- Sonuç 0'dan büyük olmalı
```

### Test 2: Password Hash Test
```sql
SELECT username, LENGTH(password_hash) as hash_length FROM users;
-- Hash uzunluğu genellikle 60 karakter olmalı
```

### Test 3: Login Fonksiyonu Test
`debug_login.php` dosyasını çalıştırın ve sonuçları inceleyin.

## Sık Karşılaşılan Sorunlar

### Sorun 1: "Kullanıcı bulunamadı"
**Çözüm:** Veritabanını kopyalayın veya yeni kullanıcı oluşturun.

### Sorun 2: "Password hash yok"
**Çözüm:** Kullanıcıların password hash'lerini manuel olarak güncelleyin.

### Sorun 3: "Veritabanı yazılamıyor"
**Çözüm:** Dosya izinlerini düzeltin: `chmod 664 db/app.db`

### Sorun 4: "Session başlatılamıyor"
**Çözüm:** Session save path'i kontrol edin ve yazılabilir olduğundan emin olun.

## Güvenlik Notu
- `debug_login.php` dosyasını sorun çözüldükten sonra MUTLAKA silin
- Production'da `APP_DEBUG` kapalı olmalı
- Şifreleri asla log dosyalarına yazmayın

## Sorun Devam Ederse

1. `debug_login.php` çıktısını kaydedin
2. Log dosyalarını kontrol edin
3. Veritabanı yedek alın
4. Lokaldeki veritabanını canlıya tam olarak kopyalayın

