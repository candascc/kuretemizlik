# 🔗 GitHub - cPanel Entegrasyon Rehberi

Bu doküman, GitHub repository'niz ile cPanel hosting arasında otomatik deployment zinciri kurulumunu açıklar.

## 🎯 Sistem Mimarisi

```
┌─────────────────┐
│  Local Machine  │
│   (Geliştirme)  │
└────────┬────────┘
         │ git push
         ▼
┌─────────────────┐
│  GitHub Repo     │
│  (Kaynak Kod)   │
└────────┬────────┘
         │
         ├──► GitHub Actions (bildirim)
         │
         ▼
┌─────────────────┐
│  cPanel Git     │
│  Version Control│
└────────┬────────┘
         │ Pull & Deploy
         ▼
┌─────────────────┐
│  Canlı Website  │
│  (Production)   │
└─────────────────┘
```

## 📦 Kurulum Adımları

### 1. GitHub Repository Hazırlığı

✅ Repository oluşturuldu: `https://github.com/candascc/kuretemizlik.git`  
✅ İlk commit yapıldı  
✅ `.cpanel.yml` dosyası eklendi  
✅ GitHub Actions workflow eklendi

### 2. cPanel'de Repository Kurulumu

Detaylı adımlar için: [CPANEL_SETUP_STEPS.md](CPANEL_SETUP_STEPS.md)

**Özet:**
1. cPanel > Git Version Control > Create
2. Clone URL: `https://github.com/candascc/kuretemizlik.git`
3. Repository Path: `/home/KULLANICI_ADI/public_html/app`
4. Create

### 3. İlk Deployment

1. cPanel > Git Version Control > Manage
2. Pull or Deploy > Update from Remote
3. Pull or Deploy > Deploy HEAD Commit

---

## 🔄 Günlük Kullanım Akışı

### Senaryo: Yeni Özellik Eklendi

```bash
# 1. Lokal'de değişiklik yap
git add .
git commit -m "Yeni özellik eklendi"
git push origin master

# 2. GitHub Actions otomatik çalışır (bildirim)

# 3. cPanel'de deployment yap
#    - Git Version Control > Manage
#    - Pull or Deploy > Update from Remote
#    - Pull or Deploy > Deploy HEAD Commit
```

**Süre:** ~2 dakika (push + deployment)

---

## 📁 Dosya Yapısı

```
kuretemizlik.com/app/
├── .cpanel.yml                    # cPanel deployment config
├── .github/
│   └── workflows/
│       ├── ci.yml                # CI/CD tests
│       ├── tests.yml             # Test workflows
│       ├── ui-tests.yml          # UI tests
│       └── deploy.yml             # Deployment notification
├── docs/
│   ├── DEPLOYMENT_CPANEL.md      # Detaylı deployment rehberi
│   ├── CPANEL_SETUP_STEPS.md     # Hızlı kurulum adımları
│   └── GITHUB_CPANEL_INTEGRATION.md  # Bu dosya
└── ... (diğer proje dosyaları)
```

---

## ⚙️ Yapılandırma Dosyaları

### `.cpanel.yml`

cPanel deployment işlemlerini tanımlar:
- Deployment path
- Backup alma
- Dosya kopyalama
- İzin ayarları
- Cache temizleme

**Önemli:** Path'i hosting yapınıza göre düzenleyin!

### `.github/workflows/deploy.yml`

GitHub Actions workflow:
- Push yapıldığında bildirim gönderir
- Deployment adımlarını gösterir
- **Otomatik deployment yapmaz** (cPanel'den manuel)

---

## 🔐 Güvenlik

### 1. `.env` Dosyası

Production'da `.env` dosyası:
- ✅ Git'te yok (`.gitignore` içinde)
- ✅ Web erişiminden korunmalı (`.htaccess`)
- ✅ Doğru izinler (600)

### 2. SSH Keys

Private repository için:
- ✅ cPanel'de SSH key oluştur
- ✅ GitHub'a public key ekle
- ✅ Private key'i güvenli tut

### 3. Deployment Backup

Her deployment öncesi:
- ✅ Otomatik backup alınır (`.cpanel.yml` içinde)
- ✅ Backup'lar `../backups/` dizininde saklanır

---

## 🚨 Troubleshooting

### Problem: "Repository path restrictions"

**Çözüm:**
- Repository path'i şu dizinlerde olamaz: `.cpanel`, `etc`, `mail`, vb.
- `public_html` altında bir dizin kullanın

### Problem: "Deployment failed"

**Kontrol listesi:**
- [ ] `.cpanel.yml` path'leri doğru mu?
- [ ] Disk alanı yeterli mi?
- [ ] Dosya izinleri yeterli mi?
- [ ] cPanel error log'larını kontrol ettiniz mi?

### Problem: "Host key verification failed"

**Çözüm:**
1. Repository oluştururken SSH key verification ekranında
2. "Save and Continue" tıklayın

---

## 📊 Deployment İstatistikleri

Her deployment'da:
- ✅ Backup alınır
- ✅ Deployment log'u tutulur
- ✅ Commit bilgileri kaydedilir

Log dosyası: `../deployment.log`

---

## 🔄 Rollback (Geri Alma)

Eğer bir deployment hatalıysa:

1. cPanel > Git Version Control > Manage
2. History sekmesine gidin
3. Önceki commit'i seçin
4. Deploy HEAD Commit

Veya backup'tan geri yükleyin:
```bash
cd /home/kullanici/public_html/app/../backups
tar -xzf backup-YYYYMMDD-HHMMSS.tar.gz -C ../app
```

---

## 📚 Ek Kaynaklar

- [cPanel Git Documentation](https://docs.cpanel.net/cpanel/files/git-version-control/)
- [cPanel Deployment Guide](https://docs.cpanel.net/knowledge-base/web-services/guide-to-git-set-up-deployment/)
- [GitHub Actions Docs](https://docs.github.com/en/actions)

---

## ✅ Checklist

Kurulum tamamlandı mı?

- [ ] GitHub repository oluşturuldu
- [ ] `.cpanel.yml` dosyası eklendi
- [ ] GitHub Actions workflow eklendi
- [ ] cPanel'de repository clone edildi
- [ ] İlk deployment yapıldı
- [ ] Canlı site test edildi
- [ ] Dokümantasyon okundu

---

**Son Güncelleme:** 2025-01-XX  
**Versiyon:** 1.0  
**Hazırlayan:** Auto AI Assistant

