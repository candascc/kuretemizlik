# 🚀 İlk Deployment Rehberi - cPanel

Bu rehber, cPanel'de ilk deployment'ı nasıl yapacağınızı adım adım açıklar.

## 📋 Durum Kontrolü

Şu anda görüyorsunuz:
- ✅ Repository başarıyla oluşturuldu
- ✅ GitHub'dan kodlar çekildi
- ✅ HEAD Commit görünüyor: `3cb8086`
- ⚠️ **Last Deployment Information: Not available** ← Bu normal, henüz deployment yapılmadı

## 🎯 İlk Deployment Adımları

### Adım 1: "Pull or Deploy" Sekmesine Geçin

1. cPanel'de **"Pull or Deploy"** sekmesine tıklayın
2. Bu sekmede iki buton göreceksiniz:
   - **"Update from Remote"** - GitHub'dan son değişiklikleri çeker
   - **"Deploy HEAD Commit"** - Canlı siteye deploy eder

### Adım 2: Update from Remote (Opsiyonel)

Eğer GitHub'da yeni değişiklikler varsa:

1. **"Update from Remote"** butonuna tıklayın
2. Bu işlem GitHub'dan son commit'leri çeker
3. Genellikle hızlıdır (birkaç saniye)

**Not:** Eğer zaten en son commit'i görüyorsanız (3cb8086), bu adımı atlayabilirsiniz.

### Adım 3: Deploy HEAD Commit (ÖNEMLİ!)

1. **"Deploy HEAD Commit"** butonuna tıklayın
2. cPanel `.cpanel.yml` dosyasındaki komutları çalıştıracak:
   - Backup alınacak
   - Dosyalar `/home/cagdasya/kuretemizlik.com/app` dizinine kopyalanacak
   - İzinler ayarlanacak
   - Cache temizlenecek

3. Deployment tamamlandığında:
   - **"Last Deployment Information"** bölümü dolacak
   - Son deployment tarihi görünecek
   - Deployed commit bilgileri görünecek

### Adım 4: Deployment Sonrası Kontrol

1. **"Temel Bilgiler"** sekmesine geri dönün
2. **"Last Deployment Information"** bölümünü kontrol edin:
   - ✅ Last Deployed on: Tarih görünmeli
   - ✅ Last Deployed SHA: Commit hash görünmeli
   - ✅ Author: Yazar bilgisi görünmeli
   - ✅ Commit Date: Tarih görünmeli

3. Canlı siteyi test edin:
   - `https://www.kuretemizlik.com/app` adresine gidin
   - Site çalışıyor mu kontrol edin

---

## ⚠️ Olası Hatalar ve Çözümleri

### Hata 1: "Deployment failed"

**Kontrol listesi:**
- [ ] `.cpanel.yml` dosyası repository'de var mı?
- [ ] Deployment path doğru mu? (`/home/cagdasya/kuretemizlik.com/app`)
- [ ] Deployment path'ine yazma izni var mı?

**Çözüm:**
1. cPanel > File Manager ile deployment path'ini kontrol edin
2. Path doğruysa, dizin izinlerini kontrol edin (755 olmalı)
3. `.cpanel.yml` dosyasını kontrol edin

### Hata 2: "Permission denied"

**Sebep:** Deployment path'ine yazma izni yok.

**Çözüm:**
1. cPanel > File Manager
2. `/home/cagdasya/kuretemizlik.com/app` dizinine gidin
3. Dizin izinlerini kontrol edin (755 olmalı)
4. Gerekirse izinleri düzenleyin

### Hata 3: "Path not found"

**Sebep:** `.cpanel.yml` dosyasındaki path yanlış.

**Çözüm:**
1. cPanel > File Manager ile gerçek path'i bulun
2. `.cpanel.yml` dosyasını düzenleyin
3. GitHub'a push edin
4. cPanel'de "Update from Remote" yapın
5. Tekrar "Deploy HEAD Commit" yapın

---

## 📝 Deployment Sonrası Yapılacaklar

### 1. `.env` Dosyası Kontrolü

`.env` dosyası Git'te yok (güvenlik için). Production'da manuel oluşturmanız gerekiyor:

1. cPanel > File Manager
2. `/home/cagdasya/kuretemizlik.com/app` dizinine gidin
3. `env.production.example` dosyasını kopyalayın
4. `.env` olarak yeniden adlandırın
5. Production ayarlarını düzenleyin:
   - `APP_DEBUG=false`
   - `APP_BASE=/app`
   - Database path, secrets, vb.

### 2. Dosya İzinleri Kontrolü

Aşağıdaki dizinlerin yazılabilir olduğundan emin olun:
- `db/` → 775
- `logs/` → 775
- `cache/` → 775
- `uploads/` → 775

### 3. Veritabanı Kontrolü

- `db/app.sqlite` dosyası var mı?
- Yoksa lokaldeki veritabanını production'a kopyalayın

### 4. Site Testi

- `https://www.kuretemizlik.com/app` adresine gidin
- Login sayfası açılıyor mu?
- Hata var mı kontrol edin

---

## 🔄 Sonraki Deployment'lar

İlk deployment'tan sonra, her GitHub'a push yaptığınızda:

1. cPanel > Git Version Control > Manage
2. **"Pull or Deploy"** sekmesi
3. **"Update from Remote"** (opsiyonel, yeni değişiklikler varsa)
4. **"Deploy HEAD Commit"** (canlıya deploy)

**Süre:** ~30 saniye

---

## ✅ Başarı Kriterleri

Deployment başarılıysa:

- ✅ "Last Deployment Information" bölümü dolu
- ✅ Canlı site çalışıyor
- ✅ Dosyalar doğru yerde
- ✅ Hata yok

---

## 🆘 Destek

Sorun yaşarsanız:

1. cPanel error log'larını kontrol edin
2. Deployment path'ini kontrol edin
3. Dosya izinlerini kontrol edin
4. `.cpanel.yml` dosyasını kontrol edin

---

**Son Güncelleme:** 2025-11-25  
**Versiyon:** 1.0

