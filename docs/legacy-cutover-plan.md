# Legacy Veri Cutover Planı

Bu doküman legacy verilerinin (`eskiler/kuretemizlik.com1/.../app/db/app.sqlite`) üretim sistemine aktarılması için izlenecek adımları ve rollback prosedürünü tanımlar.

## 1. Ön Koşullar

1. **Şema Senkronizasyonu**  
   - `php scripts/run_migrations.php` çalıştırılarak mevcut DB şema driftleri giderilir (`addresses.updated_at` gibi eksik kolonlar doğrulanır).
2. **Erişim Kontrolleri**  
   - Yönetim paneli yazma işlemleri durdurulur (cron/job queue kapatılır).  
   - İş/finans girişleri yapan kullanıcılar bakıma alındığına dair bilgilendirilir.
3. **Araçlar**  
   - `bin/legacy-import.php` ve `scripts/analyze_sqlite.php` son sürümde.  
   - PowerShell/Command Prompt üzerinden `C:/X/YAZLM~1/...` kısayoluna erişim yetkisi mevcut.

## 2. Cutover Adımları

### 2.1 Zorunlu Yedekler

```powershell
# Uygulama DB mevcut durum yedeği
Copy-Item db/app.sqlite db/backups/pre-cutover-$(Get-Date -Format 'yyyyMMdd-HHmmss').sqlite
# Fail-safe (çalışma dizininde hızlı geri dönüş için)
Copy-Item db/app.sqlite db/app.pre-cutover.sqlite -Force
```

### 2.2 ETL Çalıştırma

```powershell
php bin/legacy-import.php `
  --source=C:/X/YAZLM~1/eskiler/kuretemizlik.com1/kuretemizlik.com/app/db/app.sqlite `
  --target=db/app.sqlite `
  --truncate
```

> Not: Cutover sırasında `--dry-run` kaldırılır, hedef doğrudan `db/app.sqlite` olur.

### 2.3 Doğrulama

1. **Otomatik Metikler**
   ```powershell
   php scripts/analyze_sqlite.php --db=db/app.sqlite --profile=new `
     | Set-Content reports/legacy-import-production.json
   ```
2. **Veritabanı bütünlüğü**  
   - `php db/verify_transfer.php` (ler).
3. **Testler**
   ```powershell
   php vendor/bin/phpunit tests/unit/ResidentAuthValidationTest.php
   php vendor/bin/phpunit tests/functional/ApiFeatureTest.php   # mümkünse
   ```
4. **Manuel Kontroller (MANUAL_TEST_CHECKLIST.md)**
   - Admin login
   - Job listesi / detayları
   - Money entries raporları
   - Resident login akışı

### 2.4 Onay & Yayın

- Tüm testler ≥%95 başarı, kritik hata yok, ortalama yanıt <200 ms (Lighthouse veya uygulama profili).
- `BUILD_PROGRESS.md` ve `MASTER_DELIVERY_SUMMARY.md` güncellenir.
- Bakım ekranı kaldırılır; kullanıcılar bilgilendirilir.

## 3. Rollback Prosedürü

1. Kullanıcı trafiği tekrar dondurulur.
2. Aşağıdaki komutla eski DB geri alınır:
   ```powershell
   Copy-Item db/app.pre-cutover.sqlite db/app.sqlite -Force
   ```
3. Gerekirse daha eski yedek seçeneği:
   ```powershell
   Copy-Item db/backups/pre-transfer-backup-20251105-215326.sqlite db/app.sqlite -Force
   ```
4. Uygulama cache / opcache temizlenir (`php public/clear-cache.php` veya `php bin/console cache:clear`).
5. Sorun raporu `BUILD_ERRORS.md` ve `ROLLBACK_PROCEDURES.md` belgelerine işlenir.

## 4. İzleme & Son Kontroller

- **Veri Tutarlılığı**: `reports/legacy-import-production.json` ile `reports/legacy-data-quality-legacy.json` satır sayıları karşılaştırılır.
- **Log Takibi**: `logs/app.log` ve `logs/queue.log` dosyaları ilk 24 saat boyunca izlenir.
- **Telemetri**: `ActivityLogger` / `Analytics` eventleri hatasız akıyor mu kontroll edilir.
- **Plan B**: Eğer kısmi veri transferi tespit edilirse ETL scripti `--truncate` + `--source` parametreleriyle tekrar çalıştırılır veya rollback edilir.

Bu plan, staging dry-run (2025-11-14) çıktıları doğrultusunda güncellenmiştir ve üretim cutover’ı sırasında birebir uygulanacaktır.

