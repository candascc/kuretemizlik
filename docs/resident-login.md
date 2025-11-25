# Sakin Portalı Giriş Akışı

## Genel Bakış

- Sakinler yalnızca **telefon numaraları** ile giriş başlatır.
- İlk girişte SMS doğrulama kodu zorunludur; kod doğrulandıktan sonra sakin kendi şifresini belirler.
- Şifre oluşturulmuş sakinler, telefon + şifre kombinasyonu ile doğrudan giriş yapabilir.
- Şifresi unutulan sakinler, telefon numarasıyla yeni bir doğrulama kodu alıp şifrelerini sıfırlar.
- Arayüz; telefon, OTP ve şifre alanlarında hatalı girişlerde alanı kırmızıya alır ve mesajı aynı satırda gösterir (erişilebilirlik için `aria-invalid` desteği eklenmiştir).

## Adım Adım Akış

### 1. Telefon Doğrulaması

1. Sakin `resident/login` sayfasında telefon numarasını girer.
2. Sistem sakini bulur ve hesabı aktifleştirir.
3. Eğer hesapta şifre bulunmuyorsa SMS ile doğrulama kodu gönderilir.
4. Doğrulama kodu 5 dakika geçerli ve 5 hatalı deneme hakkı vardır.

### 2. Şifre Oluşturma (İlk Giriş)

1. Kod doğrulandığında sakin şifre belirleme formuna yönlendirilir.
2. Şifre politikası:
   - Minimum 8 karakter
   - En az bir büyük harf (`A-ZÇĞİÖŞÜ`)
   - En az bir küçük harf (`a-zçğıöşü`)
   - En az bir rakam
3. Şifre başarıyla kaydedildiğinde sakin otomatik giriş yapar.

### 3. Şifre ile Giriş

1. Telefon numarası bulunan ve şifre oluşturan sakinlerde şifre alanı açılır.
2. 5 hatalı şifre denemesi sonrası sistem SMS doğrulamasına yönlendirir.
3. Başarısız denemelerde alan altındaki hata mesajı güncellenir, sonraki giriş denemesi için sakin yönlendirilir.

### 4. Şifreyi Unuttum

1. Sakin “Şifremi unuttum” seçeneği ile telefon numarasına doğrulama kodu ister.
2. Kod doğrulandıktan sonra yeni şifre belirlenir.

## Güvenlik Notları

- SMS yeniden gönderimi 60 saniye cooldown’a sahiptir (`ResidentOtpService::RESEND_COOLDOWN_SECONDS`).
- Aynı saat içinde 10’dan fazla kod isteği engellenir.
- OTP başarıyla kullanıldığında `resident_users` tablosundaki `otp_attempts`, `last_otp_sent_at`, `otp_context` alanları sıfırlanır.
- Her SMS gönderimi `ActivityLogger` tarafından `resident.login.code_sent` olayıyla kaydedilir; başarısız durumlarda `resident.login.code_failed` tetiklenir.
- SMS kuyruğu `sms_queue` tablosunda mock sağlayıcı ile anında işlenir; gerçek ortamda Netgsm/Twilio yapılandırması gerekir.

## Yönetici Kontrol Listesi

- Sakin telefon numaralarının benzersiz olduğundan emin olun.
- Şifre sıfırlama taleplerini takip etmek için SMS kuyruğunu (`sms_queue`) gözlemleyin.
- Telefon numarası değişikliklerinde sakin kaydını güncelleyin ve gerekirse şifreyi sıfırlayın.
- Gerektiğinde `activity_log` tablosunda `resident.login.*` kayıtlarını kontrol ederek OTP gönderim geçmişini izleyin.
- Test ortamında `SMS_ENABLED=true` olarak ayarlandığında gerçek sağlayıcılar devreye alınır; aksi halde mock gönderimler log dosyasına yazılır.


