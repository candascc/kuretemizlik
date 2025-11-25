# Mobile Marketing Asset Kit – Küre Temizlik (2025-11-08)

## 1. Ana Mesajlar
- **Şeffaf Yönetişim**: Sakin portalı tüm aidat, gider ve duyuruları mobilde izleyebiliyor.
- **Site & Temizlik Operasyonu Bir Arada**: Tek app; saha ekipleri + apartman yönetimi.
- **Mobilde Güvenli Finans**: Ödemeler, rezervasyonlar ve toplantı kararları parmak ucunda.
- **Türkiye’ye Özel**: Türkçe arayüz, yerel mevzuata uygun raporlar, 7/24 destek.

## 2. Kampanya Slogan Örnekleri
| Kullanım | Metin | Alt Mesaj |
| --- | --- | --- |
| Banner / Landing | “Sitenizi Cebinizden Yönetin” | Aidat, bakım, sakin portali tek uygulamada |
| Video Intro | “Bir Temizlik Şirketinden Fazlası” | Yönetim kurulu ve sakinleri aynı platformda buluşturun |
| Paid Ads | “Aidatlar %100 Şeffaf, Sakinler Mutlu” | Portal ve finansal raporlar live |

## 3. Landing Sayfası İçeriği (Öneri)
- **Hero**: Mobil cihaz mockup + ana slogan + CTA “Mobil uygulamayı keşfet”
- **Öne Çıkan Modüller** (ikon + kısa açıklama):
  - Sakin Portalı
  - Gelir/Gider Takibi
  - Rezervasyon Yönetimi
  - Gerçek zamanlı Bildirimler
- **Şeffaflık Vurgusu**: Dashboard kartı + “Sakinleriniz tüm işlemleri anlık kontrol eder” copy’si
- **Güven & Destek**: 7/24 destek hattı, veri güvenliği notu
- **CTA Bloğu**: “Demo talep et” + “App’i indir” (PWA install yönergesi)

## 4. Görsel/GIF Planı
| Ekran | Format | İçerik Notu |
| --- | --- | --- |
| Dashboard Mobil | PNG (1284×2778) | Yeni `dashboard-hero` + KPI kartları |
| Residents Mobil | PNG (1284×2778) | Portal logins + davet kartları |
| Finans Trend (Desktop) | PNG (1920×1080) | Top outstanding table + mobile cards overlay |
| Video Walkthrough | MP4 (60s) | Giriş → Dashboard → Residents → Rezervasyon |
| Animated GIF | 800×600 | Portal daveti gönderme micro flow |

> Not: Gerçek çekimler için Chrome DevTools “Capture screenshot” veya `pwsh scripts/capture_viewport.ps1` (hazırlanacak) kullanılabilir. Reklam kampanyası öncesi gerçek mock verilerle update edilmeli.

## 5. CTA & Copy Bank (TR/EN)
- **TR**: “Tek ekranda şeffaf yönetim”, “Sakinleriniz aidat durumunu mobilde görüyor.”, “Rezervasyonların kontrolü artık sizde.”
- **EN (opsiyonel)**: “Transparent property management in your pocket.”, “Resident portal that drives trust and retention.”

## 6. Sosyal Medya Post Taslakları
- **LinkedIn Carousel (3 slide)**:
  1. Sorun: “Aidatlar ve sakin beklentileri arasında kaybolan site yönetimi?”
  2. Çözüm: Dashboard + mobil kare
  3. CTA: Demo linki / telefon
- **Instagram Reels**: 15 sn. – mobil arayüz kaydırması + voice-over
- **Twitter Thread**: 3 tweet; PWA, şeffaflık, destek.

## 7. Kampanya CTA Butonları
- “Demo Talep Et” → `/contact?utm_campaign=mobile-launch`
- “Mobil Versiyonu Kur” → JS ile `window.addEventListener('beforeinstallprompt')` tetiklenerek PWA install modal
- “Sakin Portalını Aç” → `/portal/login` (mock oturumla)

## 8. Ölçümleme
- Google Analytics event: `cta_click` (`label`: demo / install / portal)
- Lighthouse PWA skor raporu (publish edilecek)
- UTM parametre check listesi:
  - `?utm_source=meta&utm_medium=paid&utm_campaign=mobile`
  - `?utm_source=instagram&utm_medium=organic&utm_campaign=mobile`

## 9. Dosya Teslim (Hazırlanacak klasör yapısı)
```\nassets/marketing/\n  ├─ screenshots/\n  │   ├─ dashboard-mobile.png\n  │   ├─ residents-mobile.png\n  │   └─ finance-desktop.png\n  ├─ video/\n  │   └─ mobile-walkthrough.mp4\n  └─ copy/\n      ├─ landing-hero.txt\n      ├─ slogans.txt\n      └─ social-captions.txt\n```\n

