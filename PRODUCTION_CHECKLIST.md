# Production Deployment Checklist

## ✅ Hazırlık Adımları

### 1. Dosya İzinleri
```bash
# Canlı sunucuda çalıştırın:
chmod 775 db/
chmod 664 db/app.sqlite
chmod 775 logs/
chmod 775 cache/
chmod 775 uploads/
```

### 2. Environment Dosyası
- `env.production` dosyasını `env.local` olarak kopyalayın
- `APP_DEBUG=false` olduğundan emin olun
- `APP_BASE=/app` olduğundan emin olun
- Şifreleri production için güçlü değerlerle değiştirin

### 3. Veritabanı
- Lokaldeki `db/app.sqlite` dosyasını canlıya kopyalayın
- Dosya izinlerini kontrol edin (664)
- Veritabanının çalıştığını test edin

### 4. Debug/Test Dosyalarını Silin
Şu dosyaları canlı sunucudan silin:
- `debug_login.php`
- `test_login_detailed.php`
- `fix_user_password.php`
- `fix_candas_password.php`
- `check_all_syntax.php`
- `test_*.php` (tüm test dosyaları)

### 5. .htaccess Kontrolü
- `.htaccess` dosyası mevcut ve doğru ayarlı olmalı
- Hassas dosyaların erişimi engellenmiş olmalı

### 6. Session Cookie Path
- Sistem `/app` klasöründe çalışıyorsa session cookie path `/app` olmalı
- Düzeltmeler yapıldı (index.php ve config.php güncellendi)

### 7. APP_BASE Ayarları
- `config/config.php` içinde `APP_BASE=/app` olmalı
- `base_url()` fonksiyonu düzeltildi

## 🔍 Test Adımları

### 1. Login Testi
1. `https://yourdomain.com/app/login` adresine gidin
2. Kullanıcı adı ve şifre ile giriş yapın
3. Session cookie'nin doğru set edildiğini kontrol edin

### 2. Session Kontrolü
Browser console'da (F12):
```javascript
document.cookie
// temizlik_sess cookie'sinin path=/app olduğunu kontrol edin
```

### 3. CSRF Token Kontrolü
- Login form'unda CSRF token var mı?
- Form submit edildiğinde CSRF hatası alıyor musunuz?

### 4. Redirect Kontrolü
- Login sonrası dashboard'a yönlendirme çalışıyor mu?
- Tüm internal linkler doğru çalışıyor mu?

## 🚨 Yaygın Sorunlar ve Çözümleri

### Sorun 1: Session Cookie Path Yanlış
**Belirti:** Her istekte yeni session oluşturuluyor, login yapılamıyor

**Çözüm:** 
- `index.php` dosyasında session cookie path `/app` olarak ayarlı olmalı
- Config dosyasında da `/app` ayarlı olmalı

### Sorun 2: CSRF Token Hatası
**Belirti:** "Güvenlik hatası" mesajı alıyorsunuz

**Çözüm:**
- Session cookie'nin doğru set edildiğinden emin olun
- Browser'da cookie'lerin engellenmediğinden emin olun
- Login form'unda `<?= CSRF::field() ?>` olduğundan emin olun

### Sorun 3: Redirect Döngüsü
**Belirti:** Login sonrası sürekli login sayfasına yönlendiriliyor

**Çözüm:**
- Session'ın doğru kaydedildiğinden emin olun
- `base_url()` fonksiyonunun doğru çalıştığından emin olun

### Sorun 4: 404 Hataları
**Belirti:** Sayfalar bulunamıyor

**Çözüm:**
- `.htaccess` dosyasının çalıştığından emin olun
- `APP_BASE=/app` ayarının doğru olduğundan emin olun
- RewriteEngine'in aktif olduğundan emin olun

## 📝 Yapılan Düzeltmeler

1. ✅ Session cookie path `/app` olarak ayarlandı
2. ✅ `base_url()` fonksiyonu düzeltildi
3. ✅ Session cookie kontrol mekanizması güncellendi
4. ✅ Config dosyasında cookie path dinamik yapıldı

## ⚠️ ÖNEMLİ UYARILAR

1. **APP_DEBUG=false** olmalı (production'da)
2. Debug dosyalarını silin
3. Güçlü şifreler kullanın
4. Veritabanı yedeklerini alın
5. .htaccess'in doğru çalıştığını test edin

## 🎯 Canlıya Yükleme Sonrası

1. Login testi yapın
2. Tüm önemli sayfaları test edin
3. Session'ların çalıştığını kontrol edin
4. Log dosyalarını kontrol edin
5. Hata mesajlarını kontrol edin

