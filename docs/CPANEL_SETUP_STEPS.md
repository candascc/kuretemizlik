# 📋 cPanel Kurulum Adımları - Hızlı Başlangıç

Bu doküman, cPanel'de GitHub repository'sini kurmak için adım adım talimatlar içerir.

## 🎯 Hızlı Kurulum (5 Dakika)

### 1️⃣ cPanel'e Giriş

1. cPanel hesabınıza giriş yapın
2. **"Files"** > **"Git™ Version Control"** tıklayın

### 2️⃣ SSH Key Kurulumu (ÖNEMLİ!)

Eğer repository private ise veya "could not read Username" hatası alıyorsanız:

**Detaylı rehber:** [CPANEL_SSH_KEY_SETUP.md](CPANEL_SSH_KEY_SETUP.md)

**Hızlı özet:**
1. cPanel > Security > SSH Access > Manage SSH Keys
2. Generate New Key (parolasız)
3. Key'i yetkilendir (Authorize)
4. Public key'i kopyala
5. GitHub > Settings > SSH and GPG keys > New SSH key
6. Key'i ekle

### 3️⃣ Repository Clone Et

1. **"Create"** butonuna tıklayın
2. **"Clone a Repository"** toggle'ını **AÇIK** yapın
3. **Clone URL** alanına:
   
   **SSH URL kullanın (önerilen):**
   ```
   git@github.com:candascc/kuretemizlik.git
   ```
   
   **VEYA Public repository ise HTTPS:**
   ```
   https://github.com/candascc/kuretemizlik.git
   ```
   
   ⚠️ **ÖNEMLİ:** Private repository için mutlaka SSH URL kullanın!

4. **Repository Path** alanına (kullanıcı adınızı değiştirin):
   ```
   /home/KULLANICI_ADI/repositories/kuretemizlik
   ```
   > **Not:** `KULLANICI_ADI` yerine cPanel kullanıcı adınızı yazın. Önerilen: ayrı repository dizini kullanın.

5. **Repository Name:** `kuretemizlik-app`
6. **"Create"** tıklayın
7. SSH host key verification ekranında **"Save and Continue"** tıklayın

### 4️⃣ İlk Deployment

1. Repository listesinde **"Manage"** tıklayın
2. **"Pull or Deploy"** sekmesine gidin
3. **"Update from Remote"** tıklayın (GitHub'dan çeker)
4. **"Deploy HEAD Commit"** tıklayın (canlıya deploy eder)

### 5️⃣ Test Et

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

Eğer repository private ise veya "could not read Username" hatası alıyorsanız:

**Detaylı rehber:** [CPANEL_SSH_KEY_SETUP.md](CPANEL_SSH_KEY_SETUP.md)

**Hızlı adımlar:**
1. cPanel > Security > SSH Access > Manage SSH Keys
2. Generate New Key (parolasız)
3. Key'i yetkilendir (Authorize)
4. Public key'i kopyala
5. GitHub > Settings > SSH and GPG keys > New SSH key
6. Key'i ekle
7. Repository oluştururken **SSH URL** kullan: `git@github.com:candascc/kuretemizlik.git`

---

## ❓ Sık Sorulan Sorular

**S: Path'i nasıl bulurum?**
A: cPanel > File Manager'da dosyalarınızın bulunduğu dizini kontrol edin. Genellikle `/home/kullanici/public_html` veya `/home/kullanici/public_html/app` şeklindedir.

**S: "could not read Username" hatası alıyorum**
A: Repository private ise SSH key kurmanız gerekir. [CPANEL_SSH_KEY_SETUP.md](CPANEL_SSH_KEY_SETUP.md) rehberini takip edin. Veya repository'yi public yapın.

**S: "Host key verification failed" hatası alıyorum**
A: Repository oluştururken SSH key verification ekranında "Save and Continue" tıklayın. GitHub'ın resmi SSH key'lerini kontrol edin.

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

