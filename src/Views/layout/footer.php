    <?php
        $companyFilterParam = isset($_GET['company_filter']) && $_GET['company_filter'] !== '' ? (int)$_GET['company_filter'] : null;
        $tz = date_default_timezone_get();
        try {
            $now = new DateTime('now', new DateTimeZone($tz));
        } catch (Exception $e) {
            $now = new DateTime('now');
        }
        $environment = getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'production');
    ?>
    <footer class="mt-16 bg-gradient-to-r from-primary-900 via-primary-800 to-primary-900 text-white">
        <div class="border-b border-white/10">
            <!-- Mobile: Accordion structure, Desktop: Grid layout -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
                <!-- Mobile Accordion / Desktop Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 lg:gap-10">
                    <!-- Brand Section - Always visible -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/15 shadow-soft">
                                <i class="fas fa-broom text-white text-xl" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h3 class="text-base sm:text-lg font-semibold">Küre Temizlik</h3>
                                <p class="text-xs sm:text-sm text-white/70">Çoklu şirket yönetimi ve saha operasyonları için tek panel.</p>
                            </div>
                        </div>
                        <p class="text-xs sm:text-sm text-white/70 leading-relaxed">
                            Sistem yöneticileri ve şirket ekipleri için tasarlanmış modern iş takip platformu.
                            Tüm operasyonları tek bakışta yönetin, performansı anlık izleyin.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <a href="mailto:destek@kuretemizlik.com" class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 transition-colors text-xs sm:text-sm min-h-[44px]">
                                <i class="fas fa-life-ring text-xs sm:text-sm" aria-hidden="true"></i>Destek Talebi
                            </a>
                            <a href="<?= base_url('/admin/migrations') ?>" class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 transition-colors text-xs sm:text-sm min-h-[44px]">
                                <i class="fas fa-server text-xs sm:text-sm" aria-hidden="true"></i>Migrasyon Durumu
                            </a>
                        </div>
                    </div>

                    <!-- Navigation Section - Accordion on mobile -->
                    <details class="footer-accordion group sm:contents">
                        <summary class="cursor-pointer list-none sm:list-none flex items-center justify-between sm:block mb-3 sm:mb-3 min-h-[44px] sm:min-h-0">
                            <h4 class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-white/80">Navigasyon</h4>
                            <i class="fas fa-chevron-down sm:hidden text-white/60 transition-transform duration-300 group-open:rotate-180"></i>
                        </summary>
                        <ul class="space-y-3 sm:space-y-2 mt-3 sm:mt-0">
                            <li><a href="<?= base_url('/') ?>" class="block text-xs sm:text-sm text-white/70 hover:text-white transition-colors py-1.5 sm:py-0 min-h-[44px] sm:min-h-0 flex items-center">Ana Dashboard</a></li>
                            <li><a href="<?= base_url('/calendar') ?>" class="block text-xs sm:text-sm text-white/70 hover:text-white transition-colors py-1.5 sm:py-0 min-h-[44px] sm:min-h-0 flex items-center">Takvim</a></li>
                            <li><a href="<?= base_url('/jobs') ?>" class="block text-xs sm:text-sm text-white/70 hover:text-white transition-colors py-1.5 sm:py-0 min-h-[44px] sm:min-h-0 flex items-center">İşler</a></li>
                            <li><a href="<?= base_url('/finance') ?>" class="block text-xs sm:text-sm text-white/70 hover:text-white transition-colors py-1.5 sm:py-0 min-h-[44px] sm:min-h-0 flex items-center">Finans</a></li>
                            <li><a href="<?= base_url('/buildings') ?>" class="block text-xs sm:text-sm text-white/70 hover:text-white transition-colors py-1.5 sm:py-0 min-h-[44px] sm:min-h-0 flex items-center">Apartman Yönetimi</a></li>
                        </ul>
                    </details>

                    <!-- Resources Section - Accordion on mobile -->
                    <details class="footer-accordion group sm:contents">
                        <summary class="cursor-pointer list-none sm:list-none flex items-center justify-between sm:block mb-3 sm:mb-3 min-h-[44px] sm:min-h-0">
                            <h4 class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-white/80">Kaynaklar</h4>
                            <i class="fas fa-chevron-down sm:hidden text-white/60 transition-transform duration-300 group-open:rotate-180"></i>
                        </summary>
                        <ul class="space-y-3 sm:space-y-2 mt-3 sm:mt-0">
                            <li><a href="<?= base_url('/PHASE_5_DOCUMENTATION.md') ?>" class="block text-xs sm:text-sm text-white/70 hover:text-white transition-colors py-1.5 sm:py-0 min-h-[44px] sm:min-h-0 flex items-center" target="_blank" rel="noopener">Teknik Dokümantasyon</a></li>
                            <li><a href="<?= base_url('/FINAL_REPORT.md') ?>" class="block text-xs sm:text-sm text-white/70 hover:text-white transition-colors py-1.5 sm:py-0 min-h-[44px] sm:min-h-0 flex items-center" target="_blank" rel="noopener">Proje Özeti</a></li>
                            <li><a href="<?= base_url('/SECURITY_AUDIT_CHECKLIST.md') ?>" class="block text-xs sm:text-sm text-white/70 hover:text-white transition-colors py-1.5 sm:py-0 min-h-[44px] sm:min-h-0 flex items-center" target="_blank" rel="noopener">Güvenlik Kontrolü</a></li>
                            <li><a href="<?= base_url('/MANUAL_TEST_CHECKLIST.md') ?>" class="block text-xs sm:text-sm text-white/70 hover:text-white transition-colors py-1.5 sm:py-0 min-h-[44px] sm:min-h-0 flex items-center" target="_blank" rel="noopener">Manuel Testler</a></li>
                            <li><a href="<?= base_url('/UX_IMPLEMENTATION_GUIDE.md') ?>" class="block text-xs sm:text-sm text-white/70 hover:text-white transition-colors py-1.5 sm:py-0 min-h-[44px] sm:min-h-0 flex items-center" target="_blank" rel="noopener">UX Kılavuzu</a></li>
                        </ul>
                    </details>

                    <!-- System Info Section - Accordion on mobile -->
                    <details class="footer-accordion group sm:contents">
                        <summary class="cursor-pointer list-none sm:list-none flex items-center justify-between sm:block mb-3 sm:mb-3 min-h-[44px] sm:min-h-0">
                            <h4 class="text-xs sm:text-sm font-semibold uppercase tracking-wide text-white/80">Sistem Bilgileri</h4>
                            <i class="fas fa-chevron-down sm:hidden text-white/60 transition-transform duration-300 group-open:rotate-180"></i>
                        </summary>
                        <div class="space-y-3 mt-3 sm:mt-0">
                            <ul class="space-y-3 sm:space-y-2">
                                <li class="flex items-center justify-between gap-3">
                                    <span class="text-xs sm:text-sm">Çoklu Şirket Filtre</span>
                                    <span class="footer-status-chip">
                                        <?= $companyFilterParam ? ('#'.$companyFilterParam) : 'Tümü' ?>
                                    </span>
                                </li>
                                <li class="flex items-center justify-between gap-3">
                                    <span class="text-xs sm:text-sm">Ortam</span>
                                    <span class="footer-status-chip"><?= e($environment) ?></span>
                                </li>
                                <li class="flex items-center justify-between gap-3">
                                    <span class="text-xs sm:text-sm">Zaman Dilimi</span>
                                    <span class="footer-status-chip"><?= e($tz) ?></span>
                                </li>
                                <li class="flex items-center justify-between gap-3">
                                    <span class="text-xs sm:text-sm">Sunucu Saati</span>
                                    <span class="footer-status-chip"><?= $now->format('d.m.Y H:i') ?></span>
                                </li>
                            </ul>
                            <p class="text-[10px] sm:text-xs text-white/50 pt-2">
                                &copy; <?= date('Y') ?> Temizlik İş Takip Enterprise. Tüm hakları saklıdır.
                            </p>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-xs sm:text-xs text-white/80">
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <span id="sb-cache-foot" class="footer-status-chip text-xs sm:text-xs">Cache: —</span>
                <span id="sb-db-foot" class="footer-status-chip text-xs sm:text-xs">DB: —</span>
                <span id="sb-disk-foot" class="footer-status-chip text-xs sm:text-xs">Disk: —</span>
                <span id="sb-queue-foot" class="footer-status-chip text-xs sm:text-xs">Queue: —</span>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <a href="mailto:destek@kuretemizlik.com?subject=Geri%20Bildirim" class="inline-flex items-center gap-1 text-white/80 hover:text-white transition-colors text-xs sm:text-xs min-h-[44px] sm:min-h-0">
                    <i class="fas fa-comment-dots text-xs sm:text-sm"></i>Geri Bildirim Gönder
                </a>
                <span class="hidden sm:inline" aria-hidden="true">•</span>
                <span class="text-xs sm:text-xs">Performans hedefi &lt; 200ms | Test kapsamı ≥ %90</span>
            </div>
        </div>
    </footer>
    <?php include __DIR__ . '/partials/global-footer.php'; ?>
</body>
</html>

