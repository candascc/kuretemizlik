# Capacitor Kurulum ve Paketleme Kılavuzu

Bu dizin, mobil paketleme süreci için yönergeleri içerir. Platform dizinleri (`android/`, `ios/`) CLI ile oluşturulacaktır.

## Önkoşullar

- Node 18+ ve npm/yarn
- Java 17 (Android), Android Studio, SDK/NDK
- Xcode (iOS), Apple geliştirici hesabı

## Kurulum

```bash
npm i -g @capacitor/cli
cd app
capacitor init "Küre Temizlik" "com.kuretemizlik.app" --web-dir .
capacitor add android
capacitor add ios
```

> Not: `capacitor.config.json` dosyası repo içinde hazırdır. Gerekirse `appId` ve `appName` güncellenebilir.

## Build ve Senkronizasyon

```bash
cd app
# web çıktısı PHP tarafından servis edildiği için ekstra build gerekmez
capacitor sync
capacitor open android   # Android Studio ile aç
capacitor open ios       # Xcode ile aç
```

## Push Bildirimleri

- Android: FCM `google-services.json` dosyasını `android/app/` içine ekleyin ve Gradle eklentilerini etkinleştirin.
- iOS: APNs sertifikaları/provisioning; Push ve Background Modes izinlerini Xcode proje ayarlarından açın.

## Deep Link

- Bildirimler `data.url` alanı ile açılır. Örn: `/app/jobs/123`.
- Android App Links ve iOS Universal Links isteğe bağlı olarak yapılandırılabilir.

## İmzalama ve Release

- Android: Keystore oluşturun, `release` imzalamayı Android Studio’da yapılandırın. `aab` üretin.
- iOS: Signing & Capabilities altında Team/Certificates ayarlayın; `Archive` → `Distribute App`.

## Hata Giderme

- Beyaz ekran: `server.androidScheme/iosScheme` HTTPS ve SW kayıt yolunun doğru olduğundan emin olun.
- Push çalışmıyor: İzinler ve FCM/APNs yapılandırmasını kontrol edin; konsolda hataları izleyin.


