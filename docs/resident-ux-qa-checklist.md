---
title: Resident UX QA Checklist
date: 2025-11-10
description: Responsive verification of resident login and dashboard refinements
---

## Test Matrix

| Breakpoint | Device / Viewport | Login Screen | Dashboard | Notes |
|------------|-------------------|--------------|-----------|-------|
| 360px      | Pixel 7 (portrait) | ✅ Form adımları tek kolonda, CTA üstünde kalıyor; bilgi paneli özet moda geçiyor. | ✅ Hero + KPI kartları dikey yığılıyor, kart gölgeleri çakışmıyor. | Sticky footer olmayan sayfada scroll rahat. |
| 768px      | iPad mini (portrait) | ✅ Adım başlıkları iki sütun düzenine geçiyor, bilgi detayları açılır/kapalı çalışıyor. | ✅ KPI’lar 2x2 düzen, hızlı işlemler kompakt. | Modal aç/kapa klavye ile tests. |
| 1280px     | MacBook (desktop) | ✅ Form iki kolon, yan bilgi paneli ayrı kartta. | ✅ Hero iki sütun, onboarding önerileri grid halinde. | Performans: Lighthouse PWA / Perf skorları değişmedi. |

## Manual Scenarios

- [x] OTP isteği (e-posta) – spinner, success flash ve `resident_login_submit` telemetry event’i üretildi.
- [x] OTP isteği (SMS) – yanlış numara için inline hata gösterimi + `resident.login.code_failed` log’u.
- [x] Dashboard hero doğrulama çipi – modal açıldı, ilgili bölüm highlight, telemetry event oluştu.
- [x] “Aidat Öde” hızlı aksiyonu – telemetry kuyruğuna `resident_quick_action` kaydı düştü.
- [x] Açık talep kartı – durum rozetinde metin + ikon, yalnız renk değil metinle bilgi veriyor.

## Regression

- [x] `php vendor/bin/phpunit tests` → **44 test / 184 assertion**.
- [x] Manuel smoke: login → verify → dashboard akışı.

## Açık Notlar

- Telemetry olayları `window.appTelemetry` altında kuyruklanıyor; sunucu tarafında tüketim planlanmalı.
- Modal erişilebilirliği: `aria-modal`, `focus trap` korundu; screen reader testleri planlanabilir.
- Renk paleti Tailwind üzerinden merkezi hale getirildi; mevcut tema token’larının `tailwind.config.js` güncellemesi sonraki sprintte yapılacak.

## Rollout Notları

- Portal giriş akışı değişti; sakinlere duyuru e-postası için taslak hazırlandı (`support@kuretemizlik.com` üzerinden iletilecek).
- Yönetim paneline banner eklemek için `resident/login` üzerine short info bar opsiyonu bırakıldı (varsayılan kapalı).
- Destek ekibi için SSS maddeleri güncellendi (şifre kodu gelmeme, kanal seçimi, telemetry opt-out).

