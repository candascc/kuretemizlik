# 🔐 SSH Key Kapsamlı Rehber - cPanel ve GitHub

Bu doküman, SSH key'lerin nasıl çalıştığını, nerede oluşturulduğunu ve nereye eklendiğini detaylı olarak açıklar.

## 🎯 SSH Key Nedir ve Nasıl Çalışır?

### SSH Key'in Amacı

SSH (Secure Shell) key'leri, **sunucu ile GitHub arasında güvenli bağlantı** kurmak için kullanılır. Şifre girmeden otomatik olarak kimlik doğrulama yapar.

### SSH Key Nasıl Çalışır?

```
┌─────────────┐                    ┌─────────────┐
│   cPanel    │                    │   GitHub    │
│   Sunucu    │                    │             │
│             │                    │             │
│ Private Key │ ──────(şifreli)────→ │ Public Key │
│ (gizli)     │                    │ (açık)     │
└─────────────┘                    └─────────────┘
```

1. **Private Key** (Gizli): Sunucuda kalır, asla paylaşılmaz
2. **Public Key** (Açık): GitHub'a eklenir, herkes görebilir

**Çalışma Prensibi:**
- Sunucu private key ile bir mesaj imzalar
- GitHub public key ile imzayı doğrular
- Eğer eşleşirse, erişim izni verilir

---

## 📍 SSH Key Nerede Oluşturulur?

### ❌ YANLIŞ: Proje Dosyalarında

**SSH key'ler ASLA proje dosyalarında (.yaml, .yml, vb.) oluşturulmaz!**

- ❌ `.cpanel.yml` dosyasına eklenmez
- ❌ `.github/workflows/` dosyalarına eklenmez
- ❌ Proje klasörüne eklenmez
- ❌ Git'e commit edilmez (güvenlik riski!)

### ✅ DOĞRU: Sunucuda Oluşturulur

SSH key'ler **sunucu üzerinde** oluşturulur:

**Konum:** `/home/kullanici/.ssh/` dizini

**Dosyalar:**
- `id_rsa` → Private key (gizli, asla paylaşılmaz)
- `id_rsa.pub` → Public key (GitHub'a eklenir)

---

## 🔧 SSH Key Oluşturma Yöntemleri

### Yöntem 1: cPanel Interface (Eğer Varsa)

**cPanel'de SSH Access bölümü varsa:**

1. cPanel > **Security** > **SSH Access**
2. **Manage SSH Keys** sekmesi
3. **Generate New Key** butonu
4. Key oluşturulur ve `/home/kullanici/.ssh/` dizinine kaydedilir

**Not:** Bazı hosting sağlayıcıları bu özelliği kapatabilir (güvenlik nedeniyle).

### Yöntem 2: cPanel Terminal (Önerilen)

**cPanel'de SSH Access yoksa, Terminal kullanın:**

1. cPanel > **Advanced** > **Terminal**
2. Terminal açılır
3. Şu komutları çalıştırın:

```bash
# SSH dizinine git
cd ~/.ssh

# SSH key oluştur (eğer yoksa)
ssh-keygen -t rsa -b 4096 -C "xerock@gmail.com" -f ~/.ssh/id_rsa

# Parola sorulduğunda Enter'a basın (parolasız key)

# Public key'i görüntüle
cat ~/.ssh/id_rsa.pub
```

4. Çıkan public key'i kopyalayın (baştan sona)

**Örnek çıktı:**
```
ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQC... xerock@gmail.com
```

### Yöntem 3: Lokal Bilgisayardan (Alternatif)

Eğer cPanel Terminal'e erişemiyorsanız:

1. Lokal bilgisayarınızda SSH key oluşturun
2. Public key'i cPanel'e yükleyin
3. GitHub'a ekleyin

**Adımlar:**
```bash
# Lokal'de key oluştur
ssh-keygen -t rsa -b 4096 -C "xerock@gmail.com"

# Public key'i görüntüle
cat ~/.ssh/id_rsa.pub

# Public key'i kopyala ve cPanel'e yükle
```

---

## 📤 SSH Key Nereye Eklenir?

### 1. Public Key → GitHub Hesabına

**GitHub'a eklenir, proje dosyalarına değil!**

**Adımlar:**
1. GitHub > **Settings** > **SSH and GPG keys**
2. **New SSH key** butonu
3. Public key'i yapıştırın
4. **Add SSH key** butonu

**Önemli:**
- ✅ GitHub hesabına eklenir
- ✅ Tüm repository'leriniz için geçerlidir
- ❌ Proje dosyalarına eklenmez
- ❌ `.cpanel.yml` dosyasına eklenmez

### 2. Private Key → Sunucuda Kalır

**Private key ASLA paylaşılmaz!**

- ✅ Sunucuda `/home/kullanici/.ssh/id_rsa` dizininde kalır
- ❌ GitHub'a eklenmez
- ❌ Proje dosyalarına eklenmez
- ❌ Git'e commit edilmez

---

## 🔄 Mevcut Durumunuz

### ✅ Repository Zaten Public

**Şu anda:**
- ✅ Repository public yapılmış
- ✅ HTTPS ile clone edilmiş
- ✅ Repository başarıyla oluşturulmuş

**SSH Key'e Gerek Var mı?**

**HAYIR!** Repository public olduğu için SSH key'e gerek yok.

**Ne Zaman SSH Key Gerekir?**

1. **Repository private yaparsanız** → SSH key gerekir
2. **Daha güvenli bağlantı isterseniz** → SSH key önerilir
3. **HTTPS authentication sorunları yaşarsanız** → SSH key çözüm olur

---

## 🛠️ SSH Key Kurulumu (İhtiyaç Durumunda)

### Adım 1: cPanel Terminal'de Key Oluştur

```bash
# Terminal'i aç
cd ~/.ssh

# Key oluştur (eğer yoksa)
ssh-keygen -t rsa -b 4096 -C "xerock@gmail.com" -f ~/.ssh/id_rsa

# Public key'i görüntüle
cat ~/.ssh/id_rsa.pub
```

### Adım 2: Public Key'i GitHub'a Ekle

1. GitHub > Settings > SSH and GPG keys
2. New SSH key
3. Public key'i yapıştır
4. Add SSH key

### Adım 3: Repository'yi SSH ile Clone Et

cPanel'de repository oluştururken:
- **HTTPS URL:** `https://github.com/candascc/kuretemizlik.git` ❌
- **SSH URL:** `git@github.com:candascc/kuretemizlik.git` ✅

---

## 📋 Özet: SSH Key Nerede?

| Öğe | Konum | Açıklama |
|-----|-------|----------|
| **Private Key** | `/home/cagdasya/.ssh/id_rsa` | Sunucuda, gizli |
| **Public Key** | `/home/cagdasya/.ssh/id_rsa.pub` | Sunucuda, GitHub'a eklenir |
| **GitHub'da Key** | GitHub Settings > SSH keys | Public key buraya eklenir |
| **Proje Dosyaları** | ❌ Yok | SSH key'ler proje dosyalarında değil |

---

## ❓ Sık Sorulan Sorular

### S: SSH key'i `.cpanel.yml` dosyasına eklemem gerekir mi?

**HAYIR!** SSH key'ler `.cpanel.yml` dosyasına eklenmez. Bu dosya sadece deployment komutlarını içerir.

### S: cPanel'de SSH Access bölümü yok, ne yapmalıyım?

**Çözüm:** cPanel Terminal kullanın:
1. cPanel > Advanced > Terminal
2. `ssh-keygen` komutu ile key oluşturun
3. Public key'i GitHub'a ekleyin

### S: Repository public, SSH key'e gerek var mı?

**HAYIR!** Public repository'ler için SSH key'e gerek yok. HTTPS ile clone edilebilir.

### S: Private repository için SSH key zorunlu mu?

**EVET!** Private repository'ler için SSH key veya Personal Access Token gerekir.

### S: SSH key'i proje dosyalarına commit etmem gerekir mi?

**ASLA!** Private key'i asla commit etmeyin. Bu büyük bir güvenlik riskidir!

---

## 🔐 Güvenlik Notları

### ✅ Yapılması Gerekenler

- ✅ Public key'i GitHub'a ekleyin
- ✅ Private key'i sunucuda güvenli tutun
- ✅ Key'leri parola ile koruyun (opsiyonel ama önerilir)

### ❌ Yapılmaması Gerekenler

- ❌ Private key'i GitHub'a eklemeyin
- ❌ Private key'i proje dosyalarına eklemeyin
- ❌ Private key'i Git'e commit etmeyin
- ❌ Private key'i paylaşmayın

---

## 📚 Ek Kaynaklar

- [GitHub SSH Key Guide](https://docs.github.com/en/authentication/connecting-to-github-with-ssh)
- [cPanel SSH Key Guide](https://docs.cpanel.net/knowledge-base/web-services/guide-to-git-host-git-repositories-on-a-cpanel-account/)
- [SSH Key Best Practices](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/githubs-ssh-key-fingerprints)

---

## ✅ Mevcut Durumunuz İçin Sonuç

**Şu anda:**
- ✅ Repository public
- ✅ HTTPS ile clone edilmiş
- ✅ Çalışıyor

**SSH Key'e gerek yok!** 

Eğer ileride repository'yi private yaparsanız veya daha güvenli bağlantı isterseniz, yukarıdaki adımları takip edebilirsiniz.

---

**Son Güncelleme:** 2025-11-25  
**Versiyon:** 1.0

