# 🚀 cPanel Deployment Rehberi

Bu doküman, GitHub ile cPanel hosting arasında otomatik deployment zinciri kurulumunu açıklar.

## 📋 İçindekiler

1. [Genel Bakış](#genel-bakış)
2. [cPanel'de Repository Kurulumu](#cpanelde-repository-kurulumu)
3. [Otomatik Deployment Yapılandırması](#otomatik-deployment-yapılandırması)
4. [Manuel Deployment](#manuel-deployment)
5. [Troubleshooting](#troubleshooting)

---

## 🎯 Genel Bakış

Bu sistem şu şekilde çalışır:

```
GitHub Repository
    ↓ (git push)
GitHub Actions (bildirim)
    ↓
cPanel Git Version Control
    ↓ (Pull & Deploy)
Canlı Website
```

**Avantajlar:**
- ✅ GitHub'a push yapıldığında otomatik bildirim
- ✅ cPanel üzerinden tek tıkla deployment
- ✅ Deployment geçmişi takibi
- ✅ Rollback imkanı
- ✅ Güvenli deployment (backup dahil)

---

## 📦 cPanel'de Repository Kurulumu

### Adım 1: cPanel'e Giriş Yapın

1. cPanel hesabınıza giriş yapın
2. **"Files"** bölümünde **"Git™ Version Control"** seçeneğine tıklayın

### Adım 2: Repository Oluşturun

1. **"Create"** butonuna tıklayın
2. **"Clone a Repository"** seçeneğini **etkinleştirin**
3. **Clone URL** alanına GitHub repository URL'inizi girin:
   ```
   https://github.com/candascc/kuretemizlik.git
   ```
   veya SSH kullanıyorsanız:
   ```
   git@github.com:candascc/kuretemizlik.git
   ```

4. **Repository Path** alanına deployment yapılacak yolu girin:
   ```
   /home/kullanici_adi/public_html/app
   ```
   > **Not:** `kullanici_adi` yerine cPanel kullanıcı adınızı yazın. Path'i hosting sağlayıcınıza göre ayarlayın.

5. **Repository Name** alanına bir isim verin (örn: `kuretemizlik-app`)

6. **"Create"** butonuna tıklayın

### Adım 3: SSH Key Yapılandırması (Private Repository için)

Eğer repository private ise:

1. cPanel'de **"Security"** > **"SSH Access"** > **"Manage SSH Keys"**
2. Yeni bir SSH key oluşturun veya mevcut key'i kullanın
3. Public key'i GitHub hesabınıza ekleyin:
   - GitHub > Settings > SSH and GPG keys > New SSH key

Detaylı bilgi: [cPanel SSH Key Guide](https://docs.cpanel.net/knowledge-base/web-services/guide-to-git-host-git-repositories-on-a-cpanel-account/)

---

## ⚙️ Otomatik Deployment Yapılandırması

### `.cpanel.yml` Dosyası

Repository'nizde `.cpanel.yml` dosyası zaten mevcut. Bu dosya deployment işlemlerini tanımlar:

```yaml
deployment:
  tasks:
    - export DEPLOYPATH=/home/${CPANEL_USER}/public_html/app
    - # Backup, copy files, set permissions, etc.
```

**Önemli:** `.cpanel.yml` dosyasındaki `DEPLOYPATH` değerini hosting yapınıza göre düzenleyin.

### Deployment Path Ayarları

`.cpanel.yml` dosyasında deployment path'i düzenleyin:

```yaml
# Eğer app root'ta ise:
- export DEPLOYPATH=/home/$${CPANEL_USER}/public_html

# Eğer app alt dizinde ise:
- export DEPLOYPATH=/home/$${CPANEL_USER}/public_html/app
```

---

## 🔄 Manuel Deployment

### Yöntem 1: cPanel Interface Üzerinden

1. cPanel > **Git Version Control** > Repository listesinde **"Manage"** butonuna tıklayın
2. **"Pull or Deploy"** sekmesine gidin
3. **"Update from Remote"** butonuna tıklayın (GitHub'dan son değişiklikleri çeker)
4. **"Deploy HEAD Commit"** butonuna tıklayın (canlı siteye deploy eder)

### Yöntem 2: SSH ile (Eğer SSH erişiminiz varsa)

```bash
# Repository dizinine gidin
cd /home/kullanici_adi/public_html/app

# GitHub'dan son değişiklikleri çekin
git pull origin master

# Deployment yapın (eğer .cpanel.yml varsa)
# cPanel otomatik olarak deployment hook'unu çalıştırır
```

---

## 🔔 Otomatik Bildirim (GitHub Actions)

GitHub'a push yapıldığında, `.github/workflows/deploy.yml` dosyası çalışır ve deployment için hazır olduğunuzu bildirir.

**Workflow tetiklenme koşulları:**
- `master` veya `main` branch'ine push yapıldığında
- Manuel olarak `workflow_dispatch` ile

**Workflow ne yapar:**
- ✅ Deployment için hazır olduğunu bildirir
- ✅ GitHub Actions summary'de adımları gösterir
- ⚠️ **Otomatik deployment yapmaz** - cPanel'den manuel olarak deploy etmeniz gerekir

---

## 🚨 Troubleshooting

### Problem: "Host key verification failed"

**Çözüm:**
1. cPanel > Git Version Control > Repository > Manage
2. SSH host key verification ekranında "Save and Continue" tıklayın

### Problem: "Repository path restrictions"

**Çözüm:**
- Repository path'i şu dizinlerde olamaz:
  - `.cpanel`, `etc`, `mail`, `ssl`, `tmp`, `logs`, vb.
- `public_html` altında bir dizin kullanın

### Problem: "Deployment failed"

**Kontrol edin:**
1. `.cpanel.yml` dosyasındaki path'ler doğru mu?
2. Dosya izinleri yeterli mi?
3. Disk alanı yeterli mi?
4. cPanel error log'larını kontrol edin

### Problem: "Permission denied"

**Çözüm:**
```bash
# SSH ile (eğer erişiminiz varsa)
chmod 755 /home/kullanici_adi/public_html/app
chmod 644 /home/kullanici_adi/public_html/app/*
```

### Problem: "Composer not found"

**Çözüm:**
- `.cpanel.yml` dosyasında composer path'ini düzenleyin:
  ```yaml
  - /usr/local/bin/php /usr/local/bin/composer install
  ```
- Veya composer kurulumunu kaldırın (vendor zaten git'te ise)

---

## 📝 Deployment Checklist

Her deployment öncesi:

- [ ] GitHub'a push yapıldı
- [ ] GitHub Actions workflow başarılı
- [ ] cPanel'de "Update from Remote" yapıldı
- [ ] "Deploy HEAD Commit" yapıldı
- [ ] Canlı site test edildi
- [ ] Hata log'ları kontrol edildi

---

## 🔐 Güvenlik Notları

1. **`.env` dosyası:** Production'da `.env` dosyası web erişiminden korunmalıdır
2. **SSH Keys:** Private repository için SSH key'leri güvenli tutun
3. **Backup:** Her deployment öncesi otomatik backup alınır (`.cpanel.yml` içinde)
4. **Permissions:** Hassas dosyalar için doğru izinler ayarlanır

---

## 📚 Ek Kaynaklar

- [cPanel Git Version Control Documentation](https://docs.cpanel.net/cpanel/files/git-version-control/)
- [cPanel Deployment Guide](https://docs.cpanel.net/knowledge-base/web-services/guide-to-git-set-up-deployment/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)

---

## 🆘 Destek

Sorun yaşarsanız:
1. cPanel error log'larını kontrol edin
2. GitHub Actions log'larını kontrol edin
3. `.cpanel.yml` dosyasını gözden geçirin
4. Hosting sağlayıcınızın desteğine başvurun

---

**Son Güncelleme:** 2025-01-XX  
**Versiyon:** 1.0

