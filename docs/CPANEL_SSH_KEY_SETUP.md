# 🔐 cPanel SSH Key Kurulumu - GitHub Private Repository

Bu rehber, cPanel'de GitHub private repository'sine erişmek için SSH key kurulumunu açıklar.

## 🎯 Sorun

cPanel'de repository oluştururken şu hatayı alıyorsunuz:
```
fatal: could not read Username for 'https://github.com': No such device or address
```

**Sebep:** Repository private veya HTTPS ile clone ederken authentication gerekiyor.

## ✅ Çözüm: SSH Key Kullanımı

### Adım 1: cPanel'de SSH Key Oluşturun

1. cPanel hesabınıza giriş yapın
2. **"Security"** bölümünde **"SSH Access"** seçeneğine tıklayın
3. **"Manage SSH Keys"** sekmesine gidin
4. **"Generate New Key"** butonuna tıklayın
5. Formu doldurun:
   - **Key Name:** `github-cpanel` (veya istediğiniz bir isim)
   - **Key Password:** Boş bırakın (parolasız key önerilir)
   - **Key Type:** `RSA` (varsayılan)
   - **Key Size:** `2048` veya `4096` (4096 daha güvenli)
6. **"Generate Key"** butonuna tıklayın

### Adım 2: SSH Key'i Yetkilendirin

1. **"Public Keys"** bölümünde oluşturduğunuz key'i bulun
2. Key'in yanındaki **"Authorize"** butonuna tıklayın
3. Onaylayın

**Önemli:** Key'i yetkilendirmeden GitHub'a ekleyemezsiniz!

### Adım 3: Public Key'i Kopyalayın

1. **"Public Keys"** bölümünde key'inizin yanındaki **"View/Download"** butonuna tıklayın
2. Açılan pencerede **tüm içeriği kopyalayın** (baştan sona)
   - Örnek format:
   ```
   ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQC... [email protected]
   ```
3. Bu key'i bir yere kaydedin (sonraki adımda kullanacağız)

### Adım 4: Public Key'i GitHub'a Ekleyin

1. GitHub hesabınıza giriş yapın: https://github.com
2. Sağ üst köşede profil resminize tıklayın
3. **"Settings"** seçeneğine tıklayın
4. Sol menüden **"SSH and GPG keys"** seçeneğine tıklayın
5. **"New SSH key"** butonuna tıklayın
6. Formu doldurun:
   - **Title:** `cPanel Hosting` (veya istediğiniz bir isim)
   - **Key:** Adım 3'te kopyaladığınız public key'i yapıştırın
7. **"Add SSH key"** butonuna tıklayın
8. GitHub şifrenizi girmeniz istenebilir (onaylayın)

### Adım 5: GitHub Repository SSH URL'sini Alın

1. GitHub'da repository'nize gidin: https://github.com/candascc/kuretemizlik
2. Yeşil **"Code"** butonuna tıklayın
3. **"SSH"** sekmesini seçin
4. SSH URL'sini kopyalayın:
   ```
   git@github.com:candascc/kuretemizlik.git
   ```
   (HTTPS değil, SSH URL'si olmalı!)

### Adım 6: cPanel'de Repository Oluşturun (SSH ile)

1. cPanel > **Git Version Control** > **Create**
2. **"Clone a Repository"** toggle'ını **AÇIK** yapın
3. **Clone URL** alanına **SSH URL'sini** yapıştırın:
   ```
   git@github.com:candascc/kuretemizlik.git
   ```
   ⚠️ **ÖNEMLİ:** HTTPS değil, SSH URL'si kullanın!
4. **Repository Path** alanına path'i girin:
   ```
   /home/cagdasya/repositories/kuretemizlik
   ```
5. **Repository Name:** `kuretemizlik-app`
6. **"Create"** butonuna tıklayın

### Adım 7: SSH Host Key Verification

İlk kez SSH ile bağlanırken, cPanel SSH host key verification isteyebilir:

1. **"Show Host Identification Information"** butonuna tıklayın
2. GitHub'ın SSH key bilgilerini kontrol edin:
   - GitHub'ın resmi SSH key'leri: https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/githubs-ssh-key-fingerprints
3. **"Save and Continue"** butonuna tıklayın

**GitHub SSH Key Fingerprints:**
- **RSA:** `SHA256:nThbg6kXUpJWGl7E1IGOCspRomTxdCARLviKw6E5SY8`
- **ECDSA:** `SHA256:p2QAMXNIC1TJYWeIOttrVc98/R1BUFWu3/LiyKgUfQM`
- **Ed25519:** `SHA256:+DiY3wvvV6TuJJhbpZisF/zLDA0zPMSvHdkr4UvCOqU`

---

## 🔄 Alternatif Çözüm: Repository'yi Public Yapmak

Eğer SSH key kurulumu zor geliyorsa, repository'yi public yapabilirsiniz:

### GitHub'da Repository'yi Public Yapma

1. GitHub'da repository'nize gidin
2. **"Settings"** sekmesine tıklayın
3. Sayfanın en altına scroll edin
4. **"Danger Zone"** bölümünde **"Change visibility"** seçeneğine tıklayın
5. **"Make public"** seçeneğini seçin
6. Repository adını yazıp onaylayın

**Not:** Public repository'ler herkes tarafından görülebilir. Kodunuz hassassa SSH key kullanın.

---

## ✅ Test

SSH key kurulumu başarılıysa:

1. cPanel'de repository oluşturulmalı
2. Hata mesajı gelmemeli
3. Repository listesinde görünmeli

---

## 🚨 Troubleshooting

### Problem: "Host key verification failed"

**Çözüm:**
1. SSH host key verification ekranında
2. GitHub'ın resmi key'lerini kontrol edin
3. "Save and Continue" tıklayın

### Problem: "Permission denied (publickey)"

**Kontrol listesi:**
- [ ] SSH key cPanel'de yetkilendirildi mi?
- [ ] Public key GitHub'a eklendi mi?
- [ ] SSH URL'si kullanıldı mı? (HTTPS değil)
- [ ] Key'in tamamı kopyalandı mı? (baştan sona)

**Çözüm:**
1. cPanel'de key'i tekrar kontrol edin
2. GitHub'da key'in eklendiğini doğrulayın
3. SSH URL'sini kullandığınızdan emin olun

### Problem: "Could not read Username"

**Sebep:** Hala HTTPS URL'si kullanılıyor.

**Çözüm:**
- SSH URL'si kullanın: `git@github.com:candascc/kuretemizlik.git`
- HTTPS değil: `https://github.com/candascc/kuretemizlik.git` ❌

---

## 📚 Ek Kaynaklar

- [cPanel SSH Key Guide](https://docs.cpanel.net/knowledge-base/web-services/guide-to-git-host-git-repositories-on-a-cpanel-account/)
- [GitHub SSH Key Guide](https://docs.github.com/en/authentication/connecting-to-github-with-ssh)
- [GitHub SSH Key Fingerprints](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/githubs-ssh-key-fingerprints)

---

## ✅ Checklist

SSH key kurulumu:

- [ ] cPanel'de SSH key oluşturuldu
- [ ] SSH key yetkilendirildi
- [ ] Public key GitHub'a eklendi
- [ ] GitHub SSH URL'si alındı
- [ ] cPanel'de SSH URL ile repository oluşturuldu
- [ ] SSH host key verification yapıldı
- [ ] Repository başarıyla clone edildi

---

**Son Güncelleme:** 2025-01-XX  
**Versiyon:** 1.0

