# 📍 cPanel Repository Path Seçimi Rehberi

Bu doküman, cPanel'de repository path'i seçerken hangi yöntemi kullanmanız gerektiğini açıklar.

## 🎯 İki Seçenek

### ✅ ÖNERİLEN: Ayrı Repository Dizini

**Yapı:**
```
/home/kullanici/
├── repositories/
│   └── kuretemizlik/          ← Repository (web'den erişilemez)
│       ├── .git/
│       └── (tüm dosyalar)
│
└── kuretemizlik.com/
    └── app/                    ← Canlı site (web root)
        └── (deployed files)
```

**cPanel'de Repository Path:**
```
/home/KULLANICI_ADI/repositories/kuretemizlik
```

**`.cpanel.yml` Deployment Path:**
```yaml
- export DEPLOYPATH=/home/$${CPANEL_USER}/kuretemizlik.com/app
```

**Avantajlar:**
- ✅ `.git` klasörü web'den erişilemez (güvenlik)
- ✅ Repository ve canlı site ayrı
- ✅ Rollback kolay
- ✅ Backup otomatik
- ✅ Daha profesyonel yapı

---

### ⚠️ ALTERNATİF: Direkt Web Root (Önerilmez)

**Yapı:**
```
/home/kullanici/kuretemizlik.com/
└── app/                        ← Repository ve canlı site aynı yerde
    ├── .git/                   ← ⚠️ Web'den erişilebilir!
    └── (tüm dosyalar)
```

**cPanel'de Repository Path:**
```
/home/KULLANICI_ADI/kuretemizlik.com/app
```

**Dezavantajlar:**
- ❌ `.git` klasörü web'den erişilebilir (güvenlik riski)
- ❌ Deployment gerekmez ama `.git` görünür
- ❌ Daha az güvenli

**Not:** Eğer bu yöntemi kullanırsanız, `.htaccess` ile `.git` klasörünü engellemelisiniz:
```apache
# .htaccess içine ekleyin
<DirectoryMatch "^\.git">
    Require all denied
</DirectoryMatch>
```

---

## 📋 Kurulum Adımları (Önerilen Yöntem)

### 1. Repository Dizini Oluşturun

cPanel > File Manager ile:
```
/home/KULLANICI_ADI/repositories/kuretemizlik
```
dizini oluşturun (boş olmalı).

### 2. cPanel'de Repository Oluşturun

1. cPanel > Git Version Control > Create
2. **Clone a Repository:** Açık
3. **Clone URL:** `https://github.com/candascc/kuretemizlik.git`
4. **Repository Path:** `/home/KULLANICI_ADI/repositories/kuretemizlik`
5. **Repository Name:** `kuretemizlik-app`
6. Create

### 3. `.cpanel.yml` Dosyasını Kontrol Edin

`.cpanel.yml` dosyasında deployment path doğru olmalı:
```yaml
- export DEPLOYPATH=/home/$${CPANEL_USER}/kuretemizlik.com/app
```

Eğer path farklıysa (örn: `public_html/app`), düzenleyin:
```yaml
- export DEPLOYPATH=/home/$${CPANEL_USER}/public_html/app
```

### 4. İlk Deployment

1. Repository > Manage > Pull or Deploy
2. Update from Remote
3. Deploy HEAD Commit

---

## 🔍 Path'inizi Nasıl Bulursunuz?

### Yöntem 1: cPanel File Manager

1. cPanel > File Manager
2. Canlı site dosyalarınızın bulunduğu dizine gidin
3. Üst kısımdaki path'i kopyalayın
4. Bu path'i `.cpanel.yml` dosyasındaki `DEPLOYPATH` olarak kullanın

### Yöntem 2: SSH ile

```bash
# SSH ile bağlanın
ssh kullanici@sunucu

# Mevcut dizini kontrol edin
pwd

# Veya dosyaların nerede olduğunu bulun
find ~ -name "index.php" -type f
```

### Yöntem 3: cPanel Terminal

1. cPanel > Advanced > Terminal
2. `pwd` komutu ile mevcut dizini görün
3. `ls -la` ile dosyaları listeleyin

---

## ⚙️ Path Örnekleri

### Örnek 1: Standart cPanel Yapısı
```
Repository: /home/kullanici/repositories/kuretemizlik
Deploy:     /home/kullanici/public_html/app
```

### Örnek 2: Domain Root Yapısı
```
Repository: /home/kullanici/repositories/kuretemizlik
Deploy:     /home/kullanici/kuretemizlik.com/app
```

### Örnek 3: Subdomain Yapısı
```
Repository: /home/kullanici/repositories/kuretemizlik
Deploy:     /home/kullanici/app.kuretemizlik.com
```

---

## ✅ Checklist

Kurulum öncesi kontrol:

- [ ] Canlı site dosyalarının path'ini biliyorum
- [ ] Repository için ayrı bir dizin oluşturacağım (önerilen)
- [ ] `.cpanel.yml` dosyasındaki `DEPLOYPATH` doğru
- [ ] cPanel'de repository oluşturuldu
- [ ] İlk deployment yapıldı
- [ ] Canlı site test edildi

---

## 🚨 Sık Yapılan Hatalar

### Hata 1: "Repository path restrictions"

**Sebep:** Repository path'i cPanel'in yasakladığı dizinlerde (`.cpanel`, `etc`, `mail`, vb.)

**Çözüm:** `public_html` veya `repositories` gibi izin verilen dizinlerde oluşturun.

### Hata 2: "Deployment path not found"

**Sebep:** `.cpanel.yml` dosyasındaki `DEPLOYPATH` yanlış.

**Çözüm:** Path'i cPanel File Manager'dan kontrol edip düzenleyin.

### Hata 3: "Permission denied"

**Sebep:** Deployment path'ine yazma izni yok.

**Çözüm:** cPanel File Manager'dan dizin izinlerini kontrol edin (755 olmalı).

---

## 📞 Destek

Sorun yaşarsanız:
1. cPanel error log'larını kontrol edin
2. Path'leri tekrar kontrol edin
3. [DEPLOYMENT_CPANEL.md](DEPLOYMENT_CPANEL.md) dokümantasyonunu okuyun

---

**Son Güncelleme:** 2025-01-XX  
**Versiyon:** 1.0

