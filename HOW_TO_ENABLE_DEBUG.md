# APP_DEBUG Nasıl Aktif Edilir?

## Yöntem 1: env.local Dosyasını Düzenle (Önerilen)

1. `env.local` dosyasını açın (proje kök dizininde)
2. Şu satırı bulun:
   ```
   APP_DEBUG=false
   ```
3. Şu şekilde değiştirin:
   ```
   APP_DEBUG=true
   ```
4. Dosyayı kaydedin
5. Sayfayı yenileyin (hard refresh: Ctrl+F5 veya Cmd+Shift+R)

## Yöntem 2: .env Dosyası Oluştur (Alternatif)

Eğer `env.local` dosyası yoksa veya çalışmıyorsa:

1. Proje kök dizininde `.env` dosyası oluşturun
2. İçine şunu yazın:
   ```
   APP_DEBUG=true
   ```
3. Dosyayı kaydedin
4. Sayfayı yenileyin

## Yöntem 3: Sunucu Ortam Değişkeni (Production'da ÖNERİLMEZ)

**⚠️ UYARI: Production ortamında APP_DEBUG=true yapmayın!**

Eğer sunucu ortam değişkenlerini ayarlayabiliyorsanız:
- `APP_DEBUG=true` olarak ayarlayın

## APP_DEBUG Aktif Olduğunda Ne Olur?

✅ **Aktif olduğunda (true)**:
- Detaylı hata mesajları gösterilir
- Stack trace (hata izi) gösterilir
- Hata dosyası ve satır numarası gösterilir
- Debug bilgileri loglanır

❌ **Kapalı olduğunda (false)**:
- Sadece genel hata mesajı gösterilir: "Bir hata oluştu. Lütfen daha sonra tekrar deneyin."
- Detaylı bilgiler gizlenir (güvenlik için)

## Kontrol Etme

APP_DEBUG'un aktif olup olmadığını kontrol etmek için:

1. Tarayıcıda sayfayı açın
2. Bir hata oluştuğunda:
   - **APP_DEBUG=true ise**: Detaylı hata mesajı, dosya adı, satır numarası ve stack trace görünür
   - **APP_DEBUG=false ise**: Sadece genel hata mesajı görünür

## Önemli Notlar

- ⚠️ **Production'da APP_DEBUG=false olmalı!** (Güvenlik için)
- ✅ **Development/Local ortamda APP_DEBUG=true olabilir**
- 🔄 Değişiklikten sonra sayfayı **hard refresh** yapın (Ctrl+F5)

## Şu Anki Durum

`env.local` dosyasında `APP_DEBUG=true` olarak ayarlandı. Sayfayı yenilediğinizde detaylı hata mesajlarını görebilirsiniz.

