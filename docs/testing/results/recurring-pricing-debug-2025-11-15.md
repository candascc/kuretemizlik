# Recurring Pricing Debug Log – 2025-11-15

## Adımlar
1. `/recurring/new` sayfasını açın ve tarayıcı konsolunu açın (`Ctrl+Shift+J`).
2. Konsolda `[payment-validation]` ile başlayan satırları arayın. Form yüklendiğinde yalnızca bilgi amaçlı “inspecting input” mesajları görünür; `skip (ancestor data-payment-skip)` mesajı tüm amount alanlarının artık validator tarafından izlenmediğini doğrular.
3. `PER_JOB` seçiliyken iş başı tutara 1500 girin. Konsolda herhangi bir `auto-correct` mesajı görülmez ve alan değeri değişmez.
4. `PER_MONTH` modeline geçip 5000 girin. Konsolda yine yalnızca “skip” mesajı görünür; turuncu “Dikkat” uyarısı çıkmaz, değer korunur.
5. `TOTAL_CONTRACT` modelinde 25000 girin; aynı davranış gözlemlenir.
6. Kayıt işlemini tamamladıktan sonra server logunda `[RecurringPricing][store]` satırı görünür (APP_DEBUG açıkken). `normalized` alanında girilen tutarların kaybolmadığı doğrulanır.

## Sonuç
- PaymentValidation artık recurring formundaki üç amount alanını tamamen atlıyor (ancestor `data-payment-skip` kontrolü).
- Backend logları normalize edilen değerlerin 0’a zorlanmadığını doğruluyor.

