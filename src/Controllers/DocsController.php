<?php

require_once __DIR__ . '/../Lib/Auth.php';
require_once __DIR__ . '/../Lib/View.php';

class DocsController
{
    /**
     * Ensure the user is authenticated before accessing internal docs.
     */
    private function requireStaff(): void
    {
        Auth::require();
    }

    private function renderStaticDoc(array $doc): void
    {
        echo View::renderWithLayout('docs/static_doc', [
            'title' => $doc['title'],
            'updatedAt' => $doc['updated_at'],
            'sections' => $doc['sections'],
            'resources' => $doc['resources'] ?? [],
        ]);
    }

    public function manualTestChecklist(): void
    {
        $this->requireStaff();
        $doc = [
            'title' => 'Manual Test Checklist',
            'updated_at' => '2025-11-25',
            'sections' => [
                [
                    'heading' => 'Giriş & Yetkilendirme',
                    'items' => [
                        'Admin, Superadmin ve Support rollerinin login/logout döngüsü.',
                        'Çoklu sekme ve session izolasyonu kontrolü.',
                        'CSRF token yenileme ve form gönderimleri.',
                    ],
                ],
                [
                    'heading' => 'Crawl & İzleme',
                    'items' => [
                        'Sysadmin > Crawl sayfasından her rol için test başlatma.',
                        'Crawl sonuç ekranında login hatası, JSON parse uyarısı veya 403 bulunmadığını doğrulama.',
                        'Kritik /app route’larının marker kontrolü.',
                    ],
                ],
                [
                    'heading' => 'Finans & Raporlama',
                    'items' => [
                        'Reports > Financial sayfasında summary/daily/monthly/by_category sekmelerini görüntüleme.',
                        'Export -> PDF/Excel işlemlerinin 200 döndürdüğünü kontrol etme.',
                        'Security dashboard metriklerinin render edildiğini ve PDO hatası oluşmadığını doğrulama.',
                    ],
                ],
                [
                    'heading' => 'Bildirim & Email',
                    'items' => [
                        'Admin > Emails > Queue ve Logs sayfalarının hatasız açılması.',
                        'Queue içerisindeki failed kayıtları yeniden kuyruğa alma testi.',
                        'Notification center (header & mobile) açılır kapanır davranışının doğrulanması.',
                    ],
                ],
            ],
            'resources' => [
                ['label' => 'Otomasyon Scriptleri', 'href' => base_url('/support/automation')],
                ['label' => 'QA Slack Kanalı', 'href' => 'https://slack.com/app_redirect?channel=qa'],
            ],
        ];

        $this->renderStaticDoc($doc);
    }

    public function uxImplementationGuide(): void
    {
        $this->requireStaff();
        $doc = [
            'title' => 'UX Implementation Guide',
            'updated_at' => '2025-11-25',
            'sections' => [
                [
                    'heading' => 'Design Tokens',
                    'items' => [
                        'Tailwind custom palette (primary / secondary / accent) kullanılmalı.',
                        'Typography: Inter / fallback sans-serif, 1.25rem ana başlık, 1rem body.',
                        'Karanlık modda minimum kontrast oranı 4.5:1 tutulmalı.',
                    ],
                ],
                [
                    'heading' => 'Bileşen Kuralları',
                    'items' => [
                        'Kartlar 16px radius, shadow-soft, dark mode varyantları içerir.',
                        'CTA butonlarında gradient veya solid primary ton; ikon + label kombinasyonu.',
                        'Form alanlarında en az 44px yükseklik, focus ring rengi primary-500.',
                    ],
                ],
                [
                    'heading' => 'Erişilebilirlik',
                    'items' => [
                        'Aria-label ve role attribute’ları dropdown/dialog bileşenlerinde zorunlu.',
                        'Klavye navigasyonu: modal ve command palette için focus trap uygulanmalı.',
                        'Animasyonlar 200ms altında tutulmalı, motion preference desteklenmeli.',
                    ],
                ],
            ],
        ];

        $this->renderStaticDoc($doc);
    }

    public function deploymentChecklist(): void
    {
        $this->requireStaff();
        $doc = [
            'title' => 'Deployment Checklist',
            'updated_at' => '2025-11-25',
            'sections' => [
                        [
                            'heading' => 'Ön Hazırlık',
                            'items' => [
                                'Master branch için CI pipeline ve PHPUnit + PHPStan level 8 sonuçlarını doğrula.',
                                'ENV dosyasında yeni secret / feature flag gereksinimi var mı kontrol et.',
                                'DB migration dosyalarını sıralı ve idempotent olacak şekilde gözden geçir.',
                            ],
                        ],
                        [
                            'heading' => 'Canlıya Alım',
                            'items' => [
                                'Maintenance mode aktif et, snapshot + veritabanı yedeği al.',
                                'composer install --no-dev && php artisan migrate (veya eşdeğer script).',
                                'Queue/cron servislerini yeniden başlat, cache/OPcache temizliği yap.',
                            ],
                        ],
                        [
                            'heading' => 'Sonrası',
                            'items' => [
                                'Health check endpoint ( /app/status ) 200 dönüyor mu kontrol et.',
                                'Admin > Monitoring panelinde son 15 dakikalık log ve metricleri takip et.',
                                'Rollback planını dokümante et ve release notlarını Slack + Confluence üzerinde paylaş.',
                            ],
                        ],
                    ],
        ];

        $this->renderStaticDoc($doc);
    }
}


