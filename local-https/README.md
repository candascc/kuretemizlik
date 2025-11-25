# Local HTTPS Setup

Bu klasör, yerel ortamınızda `https://kuretemizlik.local` adresini sertifikalı olarak çalıştırmanız için gerekli dosyaları içerir.

## 1. Sertifika Oluşturma

```ps1
cd C:\X\Yazılım\Alastyr_ftp\kuretemizlik.com\app
php scripts/local-https/setup.php
```

Çıktıda `local-https/certs/` altında:

- `kure-local-rootCA.pem` : Güvenilir Root sertifikası  
- `kuretemizlik.local.crt / .key / .pfx` : Sunucu sertifikası

### Sertifikayı Güvenilir Hale Getirme

**Windows**
1. `kure-local-rootCA.pem` dosyasına çift tıklayın.  
2. “Yerel Makine” seçeneğini işaretleyin.  
3. Mağaza olarak “Güvenilen Kök Sertifika Yetkilileri”ni seçin.

**macOS**
```bash
sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain local-https/certs/kure-local-rootCA.pem
```

**Linux (Ubuntu/Debian)**
```bash
sudo cp local-https/certs/kure-local-rootCA.pem /usr/local/share/ca-certificates/kure-local-rootCA.crt
sudo update-ca-certificates
```

## 2. Docker ile HTTPS Sunucusu

```bash
docker-compose -f docker-compose.https.yml up --build
```

Sunucu `https://kuretemizlik.local:8443/app/resident/login` üzerinden erişilebilir.

## 3. hosts Kaydı

```text
127.0.0.1   kuretemizlik.local
```
Bu satırı `C:\Windows\System32\drivers\etc\hosts` (veya `/etc/hosts`) dosyasına ekleyin.

## 4. Netgsm SMS Testi

`env.local` dosyasındaki Netgsm kimlik bilgileri günceldir. Sertifika güvenilir hale getirildikten sonra şifre sıfırlama akışını test edebilirsiniz:
1. Telefon: `0539 264 37 17`
2. “Devam Et” → “Şifremi Unuttum” → OTP paneli

Yeni kod talebi yapıldığında loglarda `resident.password_reset.requested` ve `resident.login.code_sent` olayları görünür; SMS Netgsm üzerinden gönderilir.

