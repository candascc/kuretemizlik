<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Lib/HeaderManager.php';

final class HeaderManagerTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_GET = [];
        $_COOKIE = [];
        HeaderManager::bootstrap();
    }

    public function testNavigationFiltersByRoleAndMode(): void
    {
        HeaderManager::rememberMode('operations', false);
        $nav = HeaderManager::getNavigationItems('OPERATOR', 'operations');
        $keys = array_column($nav, 'key');
        $this->assertContains('jobs', $keys);
        $this->assertNotContains('settings', $keys);

        HeaderManager::rememberMode('management', false);
        $navManagement = HeaderManager::getNavigationItems('ADMIN', 'management');
        $labelsManagement = array_column($navManagement, 'label');
        $this->assertSame(
            ['Yönetim Hizmetleri', 'Varlıklar', 'Finans', 'İletişim', 'Sakin Yönetimi', 'Ayarlar'],
            $labelsManagement,
            'Yönetim modunda ana bölümler ayrı menüler olarak görünmelidir'
        );
    }

    public function testModePreferenceFallsBackToCookie(): void
    {
        $_COOKIE['app_header_mode'] = 'site';
        HeaderManager::bootstrap();
        $mode = HeaderManager::getCurrentMode();
        $this->assertSame('management', $mode);
    }

    public function testContextLinksGeneration(): void
    {
        HeaderManager::rememberMode('management', false);
        $segments = ['buildings'];
        $links = HeaderManager::getContextLinks($segments, null, 'management', 'SITE_MANAGER');
        $labels = array_column($links, 'label');
        $this->assertContains('Binalar', $labels);
        $buildingLink = array_values(array_filter($links, fn($link) => $link['key'] === 'buildings'));
        $this->assertNotEmpty($buildingLink);
        $this->assertTrue($buildingLink[0]['active']);
    }

    public function testResidentContextLinksIncludePortal(): void
    {
        HeaderManager::rememberMode('management', false);
        $segments = ['management', 'residents'];
        $links = HeaderManager::getContextLinks($segments, null, 'management', 'SITE_MANAGER');
        $labels = array_column($links, 'label');
        $this->assertContains('Sakin Yönetimi', $labels);
        $this->assertContains('Portal Durumu', $labels);
        $this->assertContains('Sakin Talepleri', $labels);

        $firstLink = $links[0] ?? null;
        $this->assertNotNull($firstLink);
        $this->assertEquals('Sakin Yönetimi', $firstLink['label']);
        $this->assertTrue($firstLink['active']);
    }

    public function testManagementSectionMenusExposeExpectedChildren(): void
    {
        HeaderManager::rememberMode('management', false);
        $nav = HeaderManager::getNavigationItems('ADMIN', 'management');
        $assets = array_values(array_filter($nav, fn ($item) => ($item['key'] ?? null) === 'management-assets'));
        $this->assertNotEmpty($assets, 'Varlıklar menüsü bulunamadı');
        $assetLabels = array_column($assets[0]['children'] ?? [], 'label');
        $this->assertContains('Binalar', $assetLabels);
        $this->assertContains('Daireler', $assetLabels);
        $this->assertContains('Tesis & Rezervasyon', $assetLabels);

        $finance = array_values(array_filter($nav, fn ($item) => ($item['key'] ?? null) === 'management-finance'));
        $this->assertNotEmpty($finance, 'Finans menüsü bulunamadı');
        $financeLabels = array_column($finance[0]['children'] ?? [], 'label');
        $this->assertContains('Aidat Yönetimi', $financeLabels);
        $this->assertContains('Gider Yönetimi', $financeLabels);
        $this->assertContains('Finansal Raporlar', $financeLabels);

        $communications = array_values(array_filter($nav, fn ($item) => ($item['key'] ?? null) === 'management-communications'));
        $this->assertNotEmpty($communications, 'İletişim menüsü bulunamadı');
        $commLabels = array_column($communications[0]['children'] ?? [], 'label');
        $this->assertContains('Doküman Merkezi', $commLabels);
        $this->assertContains('Toplantılar', $commLabels);
        $this->assertContains('Duyurular', $commLabels);
        $this->assertContains('Anketler', $commLabels);

        $resident = array_values(array_filter($nav, fn ($item) => ($item['key'] ?? null) === 'management-residents'));
        $this->assertNotEmpty($resident, 'Sakin Yönetimi menüsü bulunamadı');
        $residentLabels = array_column($resident[0]['children'] ?? [], 'label');
        $this->assertContains('Sakinler', $residentLabels);
        $this->assertContains('Portal Durumu', $residentLabels);
        $this->assertContains('Sakin Talepleri', $residentLabels);
    }

    public function testManagementServicesChildrenRespectRoleFiltering(): void
    {
        HeaderManager::rememberMode('management', false);

        $managerNav = HeaderManager::getNavigationItems('SITE_MANAGER', 'management');
        $managerFinance = array_values(array_filter($managerNav, fn ($item) => ($item['key'] ?? null) === 'management-finance'));
        $this->assertNotEmpty($managerFinance);
        $managerFinanceLabels = array_column($managerFinance[0]['children'] ?? [], 'label');
        $this->assertContains('Aidat Yönetimi', $managerFinanceLabels);

        $managerResidents = array_values(array_filter($managerNav, fn ($item) => ($item['key'] ?? null) === 'management-residents'));
        $this->assertNotEmpty($managerResidents);
        $managerResidentLabels = array_column($managerResidents[0]['children'] ?? [], 'label');
        $this->assertContains('Sakinler', $managerResidentLabels);

        $operatorNav = HeaderManager::getNavigationItems('OPERATOR', 'management');
        $this->assertNotEmpty($operatorNav);
        $operatorKeys = array_column($operatorNav, 'key');
        $this->assertContains('management-assets', $operatorKeys);
        $this->assertNotContains('management-finance', $operatorKeys, 'Operatör rolü finans menüsünü görmemeli');
        $this->assertNotContains('management-residents', $operatorKeys, 'Operatör rolü sakin yönetimi menüsünü görmemeli');
    }

    public function testHeaderPartialDoesNotRenderContextStrip(): void
    {
        $headerContext = [
            'variant' => 'app',
            'brand' => ['url' => base_url('/')],
            'mode' => [
                'current' => 'operations',
                'available' => [],
                'theme' => [
                    'navGradient' => 'from-primary-600 to-primary-700',
                    'navClass' => 'mode-operations',
                    'quickVariant' => 'variant-operations',
                    'contextBg' => 'bg-primary-900/10',
                    'accent' => 'text-white',
                ],
            ],
            'headerMetaChips' => [],
            'statusWidgets' => [],
            'quickActions' => [],
            'navigationItems' => [],
            'contextLinks' => [
                ['url' => '/management/residents', 'label' => 'Sakinler', 'icon' => 'fa-users', 'active' => true],
            ],
            'paths' => ['currentRaw' => '/management/residents'],
            'user' => [
                'isAuthenticated' => false,
                'showNotifications' => false,
            ],
            'ui' => [
                'showSearch' => false,
                'showStatusChips' => false,
                'showQuickActions' => false,
                'showModeSwitcher' => false,
                'showNotifications' => false,
                'showSystemMenu' => false,
            ],
        ];

        ob_start();
        include __DIR__ . '/../src/Views/layout/partials/app-header.php';
        $html = ob_get_clean();

        $this->assertStringContainsString('<nav data-header', $html);
        $this->assertStringNotContainsString('module-subnav', $html, 'Context strip markup module-subnav should not render');
    }
}
