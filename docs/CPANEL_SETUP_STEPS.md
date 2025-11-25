# 📋 cPanel Kurulum Adımları - Hızlı Başlangıç

Bu doküman, cPanel'de GitHub repository'sini kurmak için adım adım talimatlar içerir.

## 🎯 Hızlı Kurulum (5 Dakika)

### 1️⃣ cPanel'e Giriş

1. cPanel hesabınıza giriş yapın
2. **"Files"** > **"Git™ Version Control"** tıklayın

### 2️⃣ Repository Clone Et

1. **"Create"** butonuna tıklayın
2. **"Clone a Repository"** toggle'ını **AÇIK** yapın
3. **Clone URL** alanına:
   ```
   https://github.com/candascc/kuretemizlik.git
   ```
4. **Repository Path** alanına (kullanıcı adınızı değiştirin):
   ```
   /home/KULLANICI_ADI/public_html/app
   ```
   > **Not:** `KULLANICI_ADI` yerine cPanel kullanıcı adınızı yazın. Path'i hosting sağlayıcınıza göre ayarlayın.

5. **Repository Name:** `kuretemizlik-app`
6. **"Create"** tıklayın

### 3️⃣ İlk Deployment

1. Repository listesinde **"Manage"** tıklayın
2. **"Pull or Deploy"** sekmesine gidin
3. **"Update from Remote"** tıklayın (GitHub'dan çeker)
4. **"Deploy HEAD Commit"** tıklayın (canlıya deploy eder)

### 4️⃣ Test Et

1. Canlı siteyi açın: `https://www.kuretemizlik.com/app`
2. Site çalışıyorsa ✅ başarılı!

---

## 🔄 Günlük Kullanım

### GitHub'a Push Yaptıktan Sonra:

1. cPanel > Git Version Control > Repository > **"Manage"**
2. **"Pull or Deploy"** sekmesi
3. **"Update from Remote"** (yeni değişiklikleri çeker)
4. **"Deploy HEAD Commit"** (canlıya deploy eder)

**Toplam süre:** ~30 saniye

---

## ⚙️ Path Ayarları

Eğer deployment path'i farklıysa, `.cpanel.yml` dosyasını düzenleyin:

```yaml
# Root'ta ise:
- export DEPLOYPATH=/home/$${CPANEL_USER}/public_html

# Alt dizinde ise (örn: /app):
- export DEPLOYPATH=/home/$${CPANEL_USER}/public_html/app

# Subdomain'de ise:
- export DEPLOYPATH=/home/$${CPANEL_USER}/subdomain.kuretemizlik.com
```

---

## 🔐 SSH Key (Private Repo için)

Eğer repository private ise:

1. cPanel > **Security** > **SSH Access** > **Manage SSH Keys**
2. **Generate New Key** veya mevcut key'i kullan
3. **Public Key**'i kopyala
4. GitHub > **Settings** > **SSH and GPG keys** > **New SSH key**
5. Key'i yapıştır ve kaydet

---

## ❓ Sık Sorulan Sorular

**S: Path'i nasıl bulurum?**
A: cPanel > File Manager'da dosyalarınızın bulunduğu dizini kontrol edin. Genellikle `/home/kullanici/public_html` veya `/home/kullanici/public_html/app` şeklindedir.

**S: "Host key verification failed" hatası alıyorum**
A: Repository oluştururken SSH key verification ekranında "Save and Continue" tıklayın.

**S: Deployment çalışmıyor**
A: 
1. `.cpanel.yml` dosyasındaki path'leri kontrol edin
2. cPanel error log'larını kontrol edin
3. Dosya izinlerini kontrol edin

**S: Otomatik deployment var mı?**
A: GitHub Actions bildirim gönderir, ama deployment için cPanel'den manuel olarak "Deploy HEAD Commit" yapmanız gerekir. Tam otomatik deployment için hosting sağlayıcınızın API'sini kullanmanız gerekir.

---

## 📞 Destek

- [cPanel Git Documentation](https://docs.cpanel.net/cpanel/files/git-version-control/)
- [Deployment Guide](docs/DEPLOYMENT_CPANEL.md)

---

**Hazırlayan:** Auto AI Assistant  
**Tarih:** 2025-01-XX

