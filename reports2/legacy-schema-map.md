# Legacy → Yeni Sistem Şema Haritası

Bu doküman, `eskiler/kuretemizlik.com1/kuretemizlik.com/app/db/app.sqlite` (legacy) ile `Alastyr_ftp/kuretemizlik.com/app/db/app.sqlite` (güncel) veritabanları arasındaki şema farklarını ve aktarım sırasında uygulanması gereken dönüşüm kurallarını özetler.

## 1. Legacy Şema Özeti

Başlıca tablolar:

- `users` – Roller yalnızca `ADMIN` ve `OPERATOR`.
- `customers`, `addresses`, `services`.
- `jobs` – Basit iş planlama; periyodik iş mantığı yok.
- `job_payments`, `money_entries`, `payments`.
- `appointments` – Minimal alan seti (`title`, `start_at`, `status`).
- `activity_log`, `rate_limits`.

Miras şema, tek müşteri/iş/finans odaklı; gayrimenkul, resident portal, personel, doküman yönetimi gibi modüller bulunmuyor.

## 2. Yeni Şema Kapsamı

Yeni sistemin temel eklemeleri:

- **Gayrimenkul & Aidat**: `buildings`, `units`, `management_fee_definitions`, `management_fees`, `online_payments`, `building_expenses`, `building_documents`, `building_reservations`, `building_meetings`, `meeting_*`, `survey_*`, `building_announcements`, `building_facilities`.
- **Resident Portal**: `resident_users`, `notification_preferences`, `notification_logs`, `resident_requests`, OTP/telemetri alanları (`rate_limits`, `notification_logs`).
- **Personel & Operasyon**: `staff`, `staff_attendance`, `staff_balances`, `staff_job_assignments`, `staff_payments`.
- **İletişim & Dosya Yönetimi**: `comments`, `comment_*`, `file_uploads`, `file_access_logs`, `email_queue`, `sms_queue`.
- **Periyodik İşler**: `recurring_jobs`, `recurring_job_occurrences`, `management_fee` ilişkileri.
- **Güvenlik & İzleme**: `slow_queries`, `migration_log`, `schema_migrations`.

Bu tablolar legacy kaynağında yer almadığı için aktarımda boş bırakılacak veya yeni sistem seed’leri kullanılacak.

## 3. Tablo Bazlı Eşleşmeler

| Domain | Legacy Tablo & Alanlar | Yeni Tablo & Ek Alanlar | Dönüşüm Notları |
| --- | --- | --- | --- |
| Kimlik | `users` (`role` {ADMIN,OPERATOR}) | `users` + `SITE_MANAGER`, `FINANCE`, `SUPPORT`, `SUPERADMIN`, 2FA alanları (`two_factor_secret`, `two_factor_required`, `two_factor_backup_codes`) | Roller birebir aktarılabilir; ekstra roller NULL. 2FA alanları NULL bırakılacak. |
| Müşteri | `customers` | `customers` (aynı yapı) | Direkt kopya. |
| Adres | `addresses` (`customer_id`, `label`, `line`, `city`) | `addresses` + `district`, `updated_at` zorunlu | Eksik kolonlar için `district=''`, `updated_at = created_at`. |
| Hizmet | `services` | `services` | Direkt kopya. |
| İş | `jobs` (temel alanlar) | `jobs` + `recurring_job_id`, `occurrence_id`, `reminder_sent`, `updated_at` zorunlu | Yeni kolonlar NULL/0; `updated_at = created_at` eğer boşsa. |
| Finans | `money_entries` | `money_entries` + `recurring_job_id`, `is_archived` | Yeni kolonlar NULL/0; referans kontrolleri korunur. |
| İş Ödemeleri | `job_payments` (timestamp yok) | `job_payments` + `created_at`, `updated_at` zorunlu | `created_at`/`updated_at` değerleri `paid_at` üzerinden set edilir. |
| Randevu | `appointments` (`start_at`, `end_at`, `status` {pending,confirmed,completed,cancelled}) | `appointments` + `appointment_date`, `start_time`, `end_time`, `priority`, `assigned_to`, `reminder_sent`, `status` {SCHEDULED,CONFIRMED,COMPLETED,CANCELLED,NO_SHOW} | `appointment_date = date(start_at)`, `start_time = time(start_at)`, `status` mapping: pending→SCHEDULED, confirmed→CONFIRMED, completed→COMPLETED, cancelled→CANCELLED. `priority='MEDIUM'`, `assigned_to=NULL`, `reminder_sent=0`. |
| Ödemeler | `payments` (serbest kullanım) | Yeni sistemde `money_entries`, `online_payments`, `management_fees` ile yönetiliyor | Legacy `payments` kayıtlarındaki `payment_method` → `money_entries.category` veya `online_payments.payment_method` alanlarına map edilecek; transaction bilgileri `notes`/`payment_data` içinde saklanacak. |
| Rate Limit | `rate_limits` | `rate_limits` | Aynı tablo; direkt kopya. |
| Aktivite Logu | `activity_log` | `activity_log` + fazladan indeksler | Direkt kopya. |

## 4. Periyodik & Finansal Yapılar

- Yeni sistemde periyodik işler `recurring_jobs` + `recurring_job_occurrences` ile tutuluyor. Legacy’de karşılığı olmadığı için aktarımda bu tablolar boş kalacak veya yeni özellikler için manuel veri girişi yapılacak.
- Aidat/rezident finans yapısı (`management_fees`, `online_payments`, `management_fee_definitions`) legacy verisiyle doldurulmayacak; sadece legacy `money_entries` ve `payments` aktarılacak.

## 5. Varsayılan Değer / Enum Uyum Tablosu

- `jobs.status`: değer seti aynı (`SCHEDULED`,`DONE`,`CANCELLED`).
- `jobs.payment_status`: `UNPAID`, `PARTIAL`, `PAID` → birebir.
- `appointments.status`: `pending→SCHEDULED`, `confirmed→CONFIRMED`, `completed→COMPLETED`, `cancelled→CANCELLED`. `NO_SHOW` legacy’de yok.
- `payments.payment_method`: `cash→cash`, `card→card`, `transfer→bank_transfer`, `check→check`.
- Yeni `online_payments.status` değerleri: tüm legacy kayıtları başlangıçta `completed` veya legacy statüsüne göre map edilecek.

## 6. Aktarım için Önceliklendirme

1. `users`, `customers`, `addresses`, `services`.
2. `jobs` → `job_payments` → `money_entries`.
3. `appointments`, `payments`.
4. Log/tablo takviyeleri (`activity_log`, `rate_limits`).

Ek tablolar (buildings, resident portal, staff, comments, file_uploads) yeni sistemde sıfırdan kullanılmaya devam edecek; aktarıma dahil edilmeyecek.

