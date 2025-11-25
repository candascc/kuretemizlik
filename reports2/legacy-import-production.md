# Legacy Veri Cutover Raporu (Prod)

- **Tarih**: 2025-11-15 00:34 (+03)
- **Kaynak**: `C:/X/YAZLM~1/eskiler/kuretemizlik.com1/kuretemizlik.com/app/db/app.sqlite`
- **Hedef**: `db/app.sqlite`
- **Komut**: `php bin/legacy-import.php --source=... --target=db/app.sqlite --truncate`

## 1. Ön Adımlar
- `php scripts/run_migrations.php` (schema drift kontrolü – yeni migration çıkmadı).
- Yedekler:
  - `db/backups/pre-cutover-20251114-233327.sqlite`
  - `db/app.pre-cutover.sqlite`

## 2. Import Sonuçları

| Tablo | Legacy | Prod (son) |
| --- | ---:| ---:|
| users | 25 | 25 |
| customers | 17 | 17 |
| addresses | 4 | 4 |
| services | 8 | 8 |
| jobs | 56 | 56 |
| job_payments | 26 | 26 |
| money_entries | 97 | 97 |
| rate_limits | 4 | 4 |
| activity_log | 3 198 | 3 198 |

> Ayrıntılı metrikler: `reports/legacy-import-production.json`

## 3. Doğrulama
- `php db/verify_transfer.php` → tablo/kayıt sayıları ve örnek kayıtlar doğrulandı.
- `php scripts/analyze_sqlite.php --db=db/app.sqlite --profile=new` → JSON raporu oluşturuldu (uyarı: `addresses` tablosu halen `updated_at` kolonu içermiyor; bilinen drift).
- Otomatik testler:
  - `php vendor/bin/phpunit tests/unit/ResidentAuthValidationTest.php` (6 test / 11 assertion, PASS) – import öncesi, sonrası ve doğrulama sonrası olmak üzere çoklu kez çalıştırıldı.

## 4. Rollback
- `db/app.pre-cutover.sqlite` hızlı dönüş için hazır.
- Daha eski güvenli yedek: `db/backups/pre-transfer-backup-20251105-215326.sqlite`.
- Rollback talimatları `docs/legacy-cutover-plan.md` / `ROLLBACK_PROCEDURES.md`.

## 5. Açık Noktalar
- `addresses` tablosu yeni şemadaki `updated_at` kolonunu içermiyor (mevcut nitelik). Migration gereksinimi belirlenirse ETL yeniden çalıştırılmadan önce uygulanmalı.
- `appointments` / `payments` tabloları legacy kaynakta kullanılmadığı için boş bırakıldı.

## 6. Sonuç
- Legacy veriler prod ortama eksiksiz taşındı, tüm kritik tablolar hizalandı, testler ve doğrulama raporları temiz.
- Sistem gerçek müşteri verileriyle çalışır durumda; rollback planı hazır, raporlar `reports/` ve `BUILD_PROGRESS.md` dosyalarında kaydedildi.

