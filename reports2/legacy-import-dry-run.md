# Staging Dry-Run Raporu (Legacy → Yeni Sistem)

- **Tarih**: 2025-11-14 23:26 (+03)
- **Kaynak**: `C:/X/YAZLM~1/eskiler/.../app/db/app.sqlite`
- **Hedef (staging)**: `db/app.import.sqlite`
- **Komut**: `php bin/legacy-import.php --source=... --target=db/app.import.sqlite --truncate`

## 1. Uygulanan Adımlar

1. `db/app.sqlite` → `db/app.pre-dry-run.sqlite` yedeği alındı.
2. `db/app.sqlite` kopyalanarak `db/app.import.sqlite` staging dosyası oluşturuldu.
3. ETL scripti truncate + insert modunda çalıştırıldı (users→activity_log tabloları).
4. `scripts/analyze_sqlite.php --db=db/app.import.sqlite --profile=new` ile metrikler toplandı (`reports/legacy-import-staging.json`).
5. Staging dosyası geçici olarak `db/app.sqlite` olarak atanıp `phpunit tests/unit/ResidentAuthValidationTest.php` çalıştırıldı (6 test / 11 assertion başarı).
6. Orijinal `db/app.sqlite` dosyası `db/app.pre-dry-run.sqlite` yedeğinden geri yüklendi.

## 2. Satır Sayısı Karşılaştırması

| Tablo | Mevcut Sistem (önce) | Legacy Kaynağı | Staging Sonrası |
| --- | ---: | ---: | ---: |
| users | 11 | 25 | 25 |
| customers | 2 | 17 | 17 |
| addresses | 0 | 4 | 4 |
| services | 5 | 8 | 8 |
| jobs | 0 | 56 | 56 |
| job_payments | 0 | 26 | 26 |
| money_entries | 1 | 97 | 97 |
| rate_limits | 0 | 4 | 4 |
| activity_log | 241 | 3 198 | 3 198 |

> “Mevcut Sistem (önce)” değerleri `reports/legacy-data-quality-current.json` dosyasından alınmıştır.

## 3. Doğrulama & Uyarılar

- `scripts/analyze_sqlite.php` staging çıktısı `reports/legacy-import-staging.json` içinde saklandı.
- `addresses_missing_updated` uyarısı devam ediyor (schema drift → `addresses.updated_at` kolonunun eksik olması). Migration uygulanmadan önce schema güncellemesi şart.
- `appointments` ve `payments` tablolarında veri yok; ETL planında opsiyonel.
- Test senaryosu: `phpunit tests/unit/ResidentAuthValidationTest.php` staging verisi üzerinde sorunsuz geçti.

## 4. Sonraki Adımlar

1. Schema drift giderildikten sonra ETL scriptine `appointments`/`payments` dönüşümü ek opsiyonları eklenecek.
2. `db/app.import.sqlite` dosyası cutover öncesi final kaynak olarak saklanacak veya tekrar üretilecek.
3. Production cutover sırasında aynı komut dizisi uygulanacak ancak `db/app.sqlite` doğrudan güncellenecek; rollback için `db/backups/pre-transfer-*.sqlite` + `db/app.pre-cutover.sqlite` tutulacak.
4. Manuel test checklist’inde (login, job listesi, finans raporları) staging verisiyle smoke test yapılacak (cutover öncesi).

