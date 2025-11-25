# Test ve Yayın Kontrol Listesi

## Lighthouse (Chrome)
- PWA: Installable, Offline, Service Worker kontrolleri
- Performans: p95 < 300ms hedefleri için kritik sayfalar
- A11y / Best Practices ≥ 90

## E2E Senaryoları
1) Offline Form Kuyruğu
   - Uçuş moduna al → yeni iş/ödeme oluştur → 202 queued yanıtı
   - Online ol → kuyruk otomatik çalışsın → kayıtlar DB’de oluşsun
2) Push Bildirimi
   - Web Push aboneliği → test bildirimi gönder → tıkla → doğru sayfa
3) SW Güncelleme UX
   - Yeni sürüm deploy → “Yeni sürüm hazır” banner’ı → Güncelle → reload
4) Deep Link
   - `/app/jobs/:id` linki ile uygulama odaklanıp ilgili sayfa açılsın

## Mağaza Hazırlığı
- Android: keystore, `aab` üretimi, listing (ikon, screenshot, gizlilik)
- iOS: Signing, Archive/Distribute, App Privacy

## Crash/Analytics (opsiyonel)
- Sentry/Capacitor App/Analytics SDK’ları

## Ortam Değişkenleri
- `VAPID_PUBLIC` (Web Push public key)
- Backend’de VAPID private key ve FCM/APNs ayarları


