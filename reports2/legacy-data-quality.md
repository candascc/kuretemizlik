# Legacy Veri Kalitesi & Uyum Analizi

Son güncelleme: 2025-11-14 23:16 (+03)  
Ham çıktı dosyaları:

- `reports/legacy-data-quality-legacy.json`
- `reports/legacy-data-quality-current.json`

## 1. Legacy (eski sistem) özet metrikleri

| Tablo | Kayıt |
| --- | ---: |
| users | 25 |
| customers | 17 |
| addresses | 4 |
| services | 8 |
| jobs | 56 |
| job_payments | 26 |
| money_entries | 97 |
| appointments | 0 |
| payments | (tablo yok) |
| rate_limits | 4 |
| activity_log | 3 198 |

### Kalite bulguları

- **Payments tablosu yok**: gerçek ödemeler `money_entries` + `job_payments` üzerinden tutulmuş. ETL’de legacy `payments` scripti olmadığı için finans kayıtları bu iki tablodan beslenmeli.
- **Adres kapsamı düşük (4 kayıt)**: 17 müşterinin yalnızca 4 adresi var; yeni sistemde `addresses.customer_id` zorunlu olduğundan eksik adresler seed edilmeden job/address FK’leri boşa düşebilir.
- **Appointments tablosu boş**: randevu sistemi kullanılmamış → yeni sistem `appointments` modülüne veri taşınmayacak.
- **Status dağılımı**: 36 iş `DONE`, 20 iş `SCHEDULED`, `CANCELLED` kaydı yok; yeni sistem whitelisti ile uyumlu.
- **Tutar ve tarih anomalisi yok**: `jobs_amount_mismatch`, `jobs_missing_start/end` sıfır.
- **Uyarılar**:
  - `appointments` tablosunda `start_at` kolonu bulunmadığı için script uyarı verdi; mimari olarak legacy randevu modeli farklı. ETL’de appointment migrasyonu opsiyonel.
  - Payments dağılımı üretilemedi (tablo bulunmadı).

## 2. Güncel Sistem (hedef) metrikleri

| Tablo | Kayıt |
| --- | ---: |
| users | 11 |
| customers | 2 |
| addresses | 0 |
| services | 5 |
| jobs | 0 |
| job_payments | 0 |
| money_entries | 1 |
| buildings | 13 |
| units | 14 |
| management_fees | 3 |
| resident_users | 12 |
| staff | 1 |

### Uyum tespitleri

- `addresses` tablosunda `updated_at` kolonu olmadığı için script uyarı verdi ⇒ mevcut şema `addresses` (yeni) tablosunu henüz migration ile güncellemiş olsa da, üretimdeki DB’de kolon eksik; ETL öncesi schema drift giderilmeli (`db/migrations` çalıştırılmalı).
- Jobs/finans tabloları gerçek veri içermiyor; ETL sonrası legacy kayıtları bu tablolara yüklenecek.
- Yönetim/aida modülleri (`buildings`, `units`, `management_fees`) halihazırda gerçek veriye sahip → legacy iş/müşteri verisi bu modüllerle FK çakışması yaratmayacak (ID aralıkları farklı).

## 3. Riskler & Eylemler

1. **Adres Eksikliği**  
   - Legacy’de yalnızca 4 adres bulundu; jobs/address_id değerleri ile yeni sistemdeki zorunlu `addresses` satırları uyuşmalı. ETL sırasında eksik adresler için geçici “Legacy Default Address” kayıtları oluşturulmalı veya job’lar `address_id` NULL olacak şekilde taşınmalı.

2. **Payments Tablosu Olmaması**  
   - Finans akışı `payments` tablosuna değil `money_entries` + `job_payments` birleşimine dayanıyor. ETL scripti legacy `payments` yerine bu iki kaynaktan `online_payments` veya `money_entries` karşılıklarını üretmeli; JSON raporunda bu eksiklik kayıt altına alındı.

3. **Schema Drift (addresses.updated_at)**  
   - Hedef DB’de migration eksikliği olduğundan `addresses` tablosu yeni kolon setini taşımıyor. ETL’den önce `php scripts/run_migrations.php` ile drift giderilmeli, aksi halde yeni veriler insert sırasında hata verir.

4. **Low Customer/Job Counts in Target**  
   - Kişisel/geliştirme seed’leri sadece 2 müşteri içeriyor; legacy aktarımı sonrası `customers` + `jobs` tabloları 17/56 kayda çıkacak. Kayıt ID çakışmalarını önlemek için ETL, hedef tablolarda `DELETE/TRUNCATE` + `sqlite_sequence` reset uygulamalı.

5. **Activity Log Volume**  
   - Legacy `activity_log` tablosu 3k kayıt içeriyor; taşınması zorunlu değil ancak audit gereksinimi varsa import sırası en sona alınmalı ve `actor_id` eşleşmeleri kontrol edilmeli.

## 4. Önerilen Kontroller (ETL öncesi)

- `reports/legacy-data-quality-legacy.json` ve `reports/legacy-data-quality-current.json` dosyaları CI pipeline’ında artefact olarak saklanmalı.
- Address şema güncellemesi uygulandıktan sonra script tekrar çalıştırılarak `warnings` bölümünün boş olduğundan emin olun.
- Legacy `appointments` kullanılmadığı için ETL planından çıkarılabilir; plan dosyasında opsiyonel olarak işaretlenmeli.
- ETL scripti, `payments` kaynaklı metrikleri `job_payments` + `money_entries` kombinasyonundan türetecek şekilde güncellenecek (Aşama 3).

Bu rapor Aşama 2 kapsamındaki veri profilleme ve uyum kontrollerini belgelemektedir. JSON çıktıları ileride kıyaslama için kullanılacaktır.

