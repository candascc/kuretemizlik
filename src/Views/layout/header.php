<?php
    require_once __DIR__ . '/partials/header-context.php';

    $headerContext = build_app_header_context([
        'headerMeta' => $headerMeta ?? [],
        'notifications' => $notifications ?? [],
        'notificationCount' => $notificationCount ?? 0,
        'user' => [
            'username' => (class_exists('Auth') && Auth::check()) ? (Auth::user()['username'] ?? null) : null,
            'showProfile' => true,
        ],
    ]);

    $projectRoot = dirname(__DIR__, 3);
    $bundleCssPath = $projectRoot . '/assets/dist/app.bundle.css';
    $bundleJsPath = $projectRoot . '/assets/dist/app.bundle.js';
    $hasBundledAssets = is_file($bundleCssPath) && is_file($bundleJsPath);
?>
<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="csrf-token" content="<?= CSRF::get() ?>">
    <!-- Security headers should be set on the server (Apache/Nginx). Meta CSP is disabled here to avoid blocking CDN scripts/styles in dev. -->
    <!-- <meta http-equiv="Content-Security-Policy" content=""> -->
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta http-equiv="Permissions-Policy" content="camera=(), microphone=(), geolocation=()">
    <meta name="description" content="Küre Temizlik - Profesyonel temizlik hizmetleri ve iş takip sistemi">
    <meta name="keywords" content="temizlik, hizmet, iş takip, müşteri yönetimi, sözleşme">
    <meta name="author" content="Küre Temizlik">
    <meta name="theme-color" content="#2563eb">
    <!-- Mobile optimization -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="format-detection" content="telephone=yes">
    
    <!-- SEO Meta Tags -->
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
    <link rel="canonical" href="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= htmlspecialchars($title ?? 'Küre Temizlik - İş Takip Sistemi') ?>">
    <meta property="og:description" content="Profesyonel temizlik hizmetleri ve iş takip sistemi">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    
    <title><?= htmlspecialchars($title ?? 'Küre Temizlik - İş Takip Sistemi') ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧹</text></svg>">
    <link rel="apple-touch-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧹</text></svg>">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= base_url('/manifest.json') ?>">
    
    <!-- Tailwind CSS (config must be defined BEFORE CDN script) -->
    <script>
        // CRITICAL FIX: Wrap in IIFE with unique scope to prevent "already declared" errors
        // This ensures the script can be loaded multiple times without errors
        // Using unique function name to prevent any potential conflicts
        (function initConsoleSuppress_<?= md5(__FILE__ . __LINE__) ?>() {
            'use strict';
            // Suppress console warnings BEFORE loading Tailwind
            // Check if already initialized to prevent re-initialization
            if (typeof window.__consoleSuppressInitialized === 'undefined') {
                window.__consoleSuppressInitialized = true;
                window.originalWarn = console.warn;
                window.originalError = console.error;
                
                // Override console.warn only once
                console.warn = function(...args) {
                    const message = args.join(' ');
                    if (message.includes('cdn.tailwindcss.com should not be used in production') || 
                        message.includes('tailwindcss.com')) {
                        return;
                    }
                    if (window.originalWarn) {
                        window.originalWarn.apply(console, args);
                    }
                };
                
                // Override console.error only once
                console.error = function(...args) {
                    const message = args.join(' ');
                    if (message.includes('cdn.tailwindcss.com')) {
                        return;
                    }
                    if (window.originalError) {
                        window.originalError.apply(console, args);
                    }
                };
            }
        })();

        // Define Tailwind config BEFORE loading the CDN script (support both globals)
        try { if (typeof tailwind === 'undefined') { window.tailwind = {}; } } catch(e) { window.tailwind = {}; }
        var TW_CONFIG = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        secondary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        },
                        accent: {
                            50: '#fdf4ff',
                            100: '#fae8ff',
                            200: '#f5d0fe',
                            300: '#f0abfc',
                            400: '#e879f9',
                            500: '#d946ef',
                            600: '#c026d3',
                            700: '#a21caf',
                            800: '#86198f',
                            900: '#701a75',
                        },
                        neutral: {
                            50: '#fafafa',
                            100: '#f5f5f5',
                            200: '#e5e5e5',
                            300: '#d4d4d4',
                            400: '#a3a3a3',
                            500: '#737373',
                            600: '#525252',
                            700: '#404040',
                            800: '#262626',
                            900: '#171717',
                        }
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                        'display': ['Inter', 'system-ui', 'sans-serif'],
                    },
                    fontWeight: {
                        'normal': '400',
                        'medium': '500',
                        'semibold': '600',
                        'bold': '700',
                        'extrabold': '800',
                    },
                    fontSize: {
                        'xs': ['0.75rem', { lineHeight: '1rem', letterSpacing: '0.05em' }],
                        'sm': ['0.875rem', { lineHeight: '1.25rem', letterSpacing: '0.02em' }],
                        'base': ['1rem', { lineHeight: '1.5rem', letterSpacing: '0' }],
                        'lg': ['1.125rem', { lineHeight: '1.75rem', letterSpacing: '-0.01em' }],
                        'xl': ['1.25rem', { lineHeight: '1.75rem', letterSpacing: '-0.02em' }],
                        '2xl': ['1.5rem', { lineHeight: '2rem', letterSpacing: '-0.03em' }],
                        '3xl': ['1.875rem', { lineHeight: '2.25rem', letterSpacing: '-0.04em' }],
                    },
                    spacing: {
                        '18': '4.5rem',
                        '88': '22rem',
                        '128': '32rem',
                    },
                    borderRadius: {
                        'xl': '0.75rem',
                        '2xl': '1rem',
                        '3xl': '1.5rem',
                    },
                    boxShadow: {
                        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
                        'medium': '0 4px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                        'strong': '0 10px 40px -10px rgba(0, 0, 0, 0.15), 0 2px 10px -2px rgba(0, 0, 0, 0.05)',
                        'glow-sm': '0 0 10px rgba(37, 99, 235, 0.15)',
                        'glow-md': '0 0 20px rgba(37, 99, 235, 0.2)',
                        'glow-lg': '0 0 30px rgba(37, 99, 235, 0.25)',
                    },
                    backgroundImage: {
                        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                        'gradient-conic': 'conic-gradient(from 180deg at 50% 50%, var(--tw-gradient-stops))',
                        'gradient-mesh': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    },
                    backdropBlur: {
                        'xs': '2px',
                    }
                }
            }
        };
        // Expose for CDN parser
        try { tailwind.config = TW_CONFIG; } catch(e) { /* ignore */ }
        try { window.tailwind.config = TW_CONFIG; } catch(e) { /* ignore */ }
        // Sticky shadow for nav
        document.addEventListener('DOMContentLoaded', function(){
            const navEl = document.querySelector('nav[data-header]');
            if (!navEl) { return; }

            const updateNavState = () => {
                if (window.scrollY > 24) {
                    navEl.classList.add('is-compact');
                    navEl.style.boxShadow = '0 3px 12px rgba(15, 23, 42, 0.15)';
                } else {
                    navEl.classList.remove('is-compact');
                    navEl.style.boxShadow = 'none';
                }
            };

            updateNavState();
            window.addEventListener('scroll', updateNavState, { passive: true });

            const triggerSearch = () => {
                if (window.globalSearch && typeof window.globalSearch.open === 'function') {
                    window.globalSearch.open();
                }
            };

            const searchInput = document.getElementById('header-search-trigger');
            if (searchInput) {
                const openSearch = (event) => {
                    event.preventDefault();
                    triggerSearch();
                };
                ['click', 'focus'].forEach(evt => searchInput.addEventListener(evt, openSearch));
                searchInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        openSearch(event);
                    }
                    if (event.key === 'Escape') {
                        searchInput.blur();
                    }
                });
            }
        });
    </script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Preload critical resources -->
    <?php if ($hasBundledAssets): ?>
        <link rel="preload" href="<?= Utils::asset('dist/app.bundle.css') ?>?v=<?= Utils::assetVersion('dist/app.bundle.css') ?>" as="style">
        <link rel="preload" href="<?= Utils::asset('dist/app.bundle.js') ?>?v=<?= Utils::assetVersion('dist/app.bundle.js') ?>" as="script">
    <?php else: ?>
        <link rel="preload" href="<?= Utils::asset('js/app.js') ?>" as="script">
    <?php endif; ?>
    <link rel="preload" href="<?= Utils::asset('fonts/fa-solid-900.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= Utils::asset('fonts/fa-brands-400.woff2') ?>" as="font" type="font/woff2" crossorigin>
    
    <!-- Tailwind CSS (Local build - ROUND 23) -->
    <link rel="stylesheet" href="<?= Utils::asset('css/tailwind.css') ?>?v=<?= file_exists(__DIR__ . '/../../assets/css/tailwind.css') ? filemtime(__DIR__ . '/../../assets/css/tailwind.css') : time() ?>">
    
    <!-- Custom CSS -->
    <?php if ($hasBundledAssets): ?>
        <link rel="stylesheet" href="<?= Utils::asset('dist/app.bundle.css') ?>?v=<?= Utils::assetVersion('dist/app.bundle.css') ?>">
    <?php else: ?>
        <link rel="stylesheet" href="<?= Utils::asset('css/fontawesome.min.css') ?>?v=<?= Utils::assetVersion('css/fontawesome.min.css') ?>">
        <link rel="stylesheet" href="<?= Utils::asset('css/custom.css') ?>?v=<?= file_exists(__DIR__ . '/../../assets/css/custom.css') ? filemtime(__DIR__ . '/../../assets/css/custom.css') : time() ?>">
        <link rel="stylesheet" href="<?= Utils::asset('css/color-system.css') ?>?v=<?= Utils::assetVersion('css/color-system.css') ?>">
        <link rel="stylesheet" href="<?= Utils::asset('css/animations.css') ?>?v=<?= Utils::assetVersion('css/animations.css') ?>">
        <link rel="stylesheet" href="<?= Utils::asset('css/data-density.css') ?>?v=<?= Utils::assetVersion('css/data-density.css') ?>">
        <?php if (file_exists(__DIR__ . '/../../assets/css/mobile-dashboard.css')): ?>
            <link rel="stylesheet" href="<?= Utils::asset('css/mobile-dashboard.css') ?>?v=<?= Utils::assetVersion('css/mobile-dashboard.css') ?>">
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Print CSS -->
    <?php if (file_exists(__DIR__ . '/../../assets/css/print.css')): ?>
    <link rel="stylesheet" href="<?= Utils::asset('css/print.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/print.css') ?>" media="print">
    <?php endif; ?>
    
    <!-- Chart.js -->
    <script src="<?= Utils::asset('vendor/chart.umd.min.js') ?>"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
    
    <!-- Form Validation CSS -->
    <?php include __DIR__ . '/../partials/form-validation.php'; ?>
    
    <!-- Custom JavaScript -->
    <?php if ($hasBundledAssets): ?>
        <script src="<?= Utils::asset('dist/app.bundle.js') ?>?v=<?= Utils::assetVersion('dist/app.bundle.js') ?>" defer></script>
    <?php else: ?>
        <script src="<?= Utils::asset('js/app.js') ?>"></script>
        <script src="<?= Utils::asset('js/form-validator.js') ?>"></script>
        <script src="<?= Utils::asset('js/keyboard-navigation.js') ?>"></script>
        <script src="<?= Utils::asset('js/bottom-sheet.js') ?>"></script>
        <script src="<?= Utils::asset('js/modern-dialogs.js') ?>"></script>
        <script src="<?= Utils::asset('js/toast-system.js') ?>"></script>
        <script src="<?= Utils::asset('js/form-auto-save.js') ?>"></script>
        <script src="<?= Utils::asset('js/keyboard-shortcuts.js') ?>"></script>
        <script src="<?= Utils::asset('js/date-shortcuts.js') ?>"></script>
        <script src="<?= Utils::asset('js/button-loading.js') ?>"></script>
        <script src="<?= Utils::asset('js/timezone-handler.js') ?>"></script>
        <script src="<?= Utils::asset('js/keyboard-shortcuts-help.js') ?>"></script>
        <script src="<?= Utils::asset('js/global-search.js') ?>"></script>
        <script src="<?= Utils::asset('js/loading-states.js') ?>"></script>
        <script src="<?= Utils::asset('js/filter-persistence.js') ?>"></script>
        <script src="<?= Utils::asset('js/payment-validation.js') ?>"></script>
        <script src="<?= Utils::asset('js/success-feedback.js') ?>"></script>
    <?php endif; ?>
    
    <!-- Inline CSS moved to assets/css/custom.css -->
<!-- Dropdown Toggle Script -->
    <script>
        // Global dropdown handler
        window.toggleDropdown = function(name, event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            const dropdown = document.getElementById('dropdown-' + name);
            if (!dropdown) { return false; }
            const isVisible = dropdown.style.display !== 'none' && dropdown.style.display !== '';
            // Close all first
            document.querySelectorAll('[id^="dropdown-"]').forEach(function(el){ el.style.display='none'; });
            if (!isVisible) {
                let button = null;
                if (event && event.target) {
                    button = event.target.closest('button');
                }
                if (!button) {
                    // Fallback to known buttons
                    const idMap = { settings: 'settings-menu-button', system: 'system-menu-button', buildings: 'buildings-menu-button' };
                    const btnId = idMap[name];
                    if (btnId) { button = document.getElementById(btnId); }
                }
                if (button) {
                    const rect = button.getBoundingClientRect();
                    dropdown.style.position = 'fixed';
                    dropdown.style.top = (rect.bottom + window.scrollY) + 'px';
                    dropdown.style.left = (rect.left + window.scrollX) + 'px';
                    dropdown.style.zIndex = '9999';
                }
                dropdown.style.display = 'block';
            } else {
                dropdown.style.display = 'none';
            }
            return false;
        };
        
        // Mobile menu toggle
        window.toggleMobileMenu = function() {
            console.log('toggleMobileMenu called');
            const menu = document.getElementById('mobile-menu');
            if (menu) {
                const isVisible = menu.style.display !== 'none';
                menu.style.display = isVisible ? 'none' : 'block';
                console.log('Mobile menu toggled:', !isVisible);
            }
        };
        
        // Dark mode helpers
            function setThemeMode(mode){
                const html = document.documentElement; const body=document.body;
                html.classList.remove('dark'); body.classList.remove('dark');
                if(mode==='dark'){ html.classList.add('dark'); body.classList.add('dark'); }
                localStorage.setItem('themeMode', mode);
                const icon = document.getElementById('dark-mode-icon');
                if(icon){ icon.className = 'fas text-sm ' + (mode==='dark' ? 'fa-sun' : 'fa-moon'); }
            }
            window.toggleDarkMode = function(){
                const cur = localStorage.getItem('themeMode') || 'light';
                const next = cur==='light' ? 'dark' : 'light';
                setThemeMode(next);
            };
        
        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM ready - initializing...');
            
            // Load dark mode from localStorage or system preference
            const stored = localStorage.getItem('themeMode');
            if(stored){ setThemeMode(stored); }
            else {
                const prefers = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                setThemeMode(prefers ? 'dark' : 'light');
            }
            
            // Close dropdowns when clicking outside (with delay to avoid immediate closing)
            document.addEventListener('click', function(event) {
                const clickedDropdown = event.target.closest('[id^="dropdown-"]');
                const clickedButton = event.target.closest('[data-dropdown]');
                
                if (!clickedDropdown && !clickedButton) {
                    setTimeout(function() {
                        document.querySelectorAll('[id^="dropdown-"]').forEach(function(el) {
                            el.style.display = 'none';
                        });
                    }, 10);
                }
            });
            
            // Add dropdown-menu class to all dropdown menus
            document.querySelectorAll('[id^="dropdown-"]').forEach(function(el) {
                el.classList.add('dropdown-menu');
            });
            
            console.log('Initialization complete');
        });
    </script>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 flex flex-col transition-colors duration-300">
    <?php
        $currentPathRaw = $_SERVER['REQUEST_URI'] ?? '';
        $currentPathRaw = str_replace(APP_BASE, '', $currentPathRaw);
        $currentPathRaw = trim($currentPathRaw, '/');
        $currentPathSegments = $currentPathRaw === '' ? [] : explode('/', $currentPathRaw);
        $currentPath = $currentPathSegments[0] ?? '';
        $companyFilterParam = isset($_GET['company_filter']) && $_GET['company_filter'] !== '' ? (int)$_GET['company_filter'] : null;
    ?>
    <!-- Navigation -->
    <?php include __DIR__ . '/partials/app-header.php'; ?>
    <?php if (false): ?>
    <div class="bg-primary-900/10 border-t border-white/10">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-2">
            <nav class="module-subnav flex gap-2 overflow-x-auto text-xs sm:text-sm text-white/80" aria-label="Alt gezinme">
                <?php foreach ($contextLinks as $link): ?>
                    <a href="<?= $link['url'] ?>" class="module-subnav-link <?= !empty($link['active']) ? 'is-active' : '' ?>">
                        <?php if (!empty($link['icon'])): ?><i class="fas <?= $link['icon'] ?> mr-1.5"></i><?php endif; ?>
                        <?= e($link['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
    <?php endif; ?>

    <!-- Global Flash Messages (fixed position under nav) -->
    <div class="fixed top-26 left-0 right-0 z-40">
        <div class="max-w-3xl mx-auto px-3 sm:px-4">
            <?= View::partial('flash'); ?>
        </div>
    </div>

    <!-- Enhanced Breadcrumb -->
    <?php if (isset($breadcrumb) && !empty($breadcrumb)): ?>
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-3">
        <nav class="flex" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                <li>
                    <div>
                            <a href="<?= base_url('/') ?>" class="text-gray-400 hover:text-blue-600 transition-colors duration-200 flex items-center">
                                <i class="fas fa-home text-sm"></i>
                            <span class="sr-only">Ana Sayfa</span>
                        </a>
                    </div>
                </li>
                <?php foreach ($breadcrumb as $index => $item): ?>
                    <li>
                        <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-300 mx-2 text-xs"></i>
                            <?php if ($index === count($breadcrumb) - 1): ?>
                                    <span class="text-gray-900 font-medium text-sm"><?= e($item['title']) ?></span>
                                <?php else: ?>
                                    <a href="<?= $item['url'] ?>" class="text-gray-500 hover:text-blue-600 transition-colors duration-200 text-sm"><?= e($item['title']) ?></a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        </div>
    </div>
    <?php else: ?>
    <!-- Auto-generated breadcrumb for common pages -->
    <?php 
    $currentPath = $_SERVER['REQUEST_URI'] ?? '';
    $currentPath = str_replace(APP_BASE, '', $currentPath);
    $currentPath = trim($currentPath, '/');
    $pathSegments = explode('/', $currentPath);
    $breadcrumbs = [];
    
    if (!empty($currentPath)) {
        $breadcrumbs[] = ['title' => 'Ana Sayfa', 'url' => base_url('/')];
        
        $pageNames = [
            'calendar' => 'Takvim',
            'jobs' => 'İşler',
            'customers' => 'Müşteriler',
            'finance' => 'Finans',
            'appointments' => 'Randevular',
            'contracts' => 'Sözleşmeler',
            'settings' => 'Ayarlar',
            'admin' => 'Admin',
            'services' => 'Hizmetler',
            'staff' => 'Personel',
            'reports' => 'Raporlar',
            'audit' => 'Audit'
        ];
        
        $currentPage = $pathSegments[0] ?? '';
        if (isset($pageNames[$currentPage])) {
            $breadcrumbs[] = ['title' => $pageNames[$currentPage], 'url' => base_url('/' . $currentPage)];
            
            // Sub-pages
            if (isset($pathSegments[1])) {
                $subPageNames = [
                    'new' => 'Yeni Ekle',
                    'create' => 'Yeni Oluştur',
                    'edit' => 'Düzenle',
                    'show' => 'Detay',
                    'profile' => 'Profil',
                    'attendance' => 'Yoklama',
                    'users' => 'Kullanıcı Yönetimi',
                    'monitoring' => 'Sistem İzleme',
                    'customer' => 'Müşteri Raporu',
                    'compliance' => 'Uyumluluk Raporu',
                    'security' => 'Güvenlik',
                    'logs' => 'Loglar',
                    'backup' => 'Yedekleme'
                ];
                
                // Admin sub-pages
                if ($currentPage === 'admin') {
                    $adminPages = [
                        'roles' => 'Roller',
                        'cache' => 'Önbellek',
                        'queue' => 'Kuyruk'
                    ];
                    
                    if (isset($adminPages[$pathSegments[1]])) {
                        $breadcrumbs[] = ['title' => $adminPages[$pathSegments[1]], 'url' => base_url('/admin/' . $pathSegments[1])];
                        
                        // Admin sub-sub-pages (e.g., /admin/roles/create)
                        if (isset($pathSegments[2]) && isset($subPageNames[$pathSegments[2]])) {
                            $breadcrumbs[] = ['title' => $subPageNames[$pathSegments[2]], 'url' => ''];
                        }
                    }
                } elseif ($currentPage === 'staff' && $pathSegments[1] === 'attendance') {
                    $breadcrumbs[] = ['title' => 'Yoklama', 'url' => ''];
                } elseif ($currentPage === 'settings' && isset($subPageNames[$pathSegments[1]])) {
                    $breadcrumbs[] = ['title' => $subPageNames[$pathSegments[1]], 'url' => ''];
                } elseif ($currentPage === 'reports' && $pathSegments[1] === 'customer') {
                    $breadcrumbs[] = ['title' => 'Müşteri Raporu', 'url' => ''];
                } elseif ($currentPage === 'audit' && $pathSegments[1] === 'compliance') {
                    $breadcrumbs[] = ['title' => 'Uyumluluk Raporu', 'url' => ''];
                } else {
                    $subPage = $pathSegments[1];
                    if (isset($subPageNames[$subPage])) {
                        $breadcrumbs[] = ['title' => $subPageNames[$subPage], 'url' => ''];
                    }
                }
            }
        }
    }
    
    if (!empty($breadcrumbs)): ?>
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-3">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <?php foreach ($breadcrumbs as $index => $item): ?>
                        <li>
                            <div class="flex items-center">
                                <?php if ($index > 0): ?>
                                    <i class="fas fa-chevron-right text-gray-300 mx-2 text-xs"></i>
                                <?php endif; ?>
                                <?php if ($index === count($breadcrumbs) - 1 || empty($item['url'])): ?>
                                    <span class="text-gray-900 font-medium text-sm"><?= e($item['title']) ?></span>
                            <?php else: ?>
                                    <a href="<?= $item['url'] ?>" class="text-gray-500 hover:text-blue-600 transition-colors duration-200 text-sm"><?= e($item['title']) ?></a>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
    </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    
    
    <!-- Skip to main content link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:bg-primary-600 focus:text-white focus:p-2 focus:z-50 focus:rounded-br-md">
        Ana içeriğe atla
    </a>

    <!-- New UI sidebar -->
    <?php if (defined('APP_NEW_UI') && APP_NEW_UI): ?>
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <?php endif; ?>

    <!-- Main Content -->
    <main id="main-content" class="py-4 sm:py-6 bg-gray-50 dark:bg-gray-900 flex-1 <?php if (defined('APP_NEW_UI') && APP_NEW_UI) echo 'app-content with-sidebar'; ?>" role="main">
        <!-- ARIA live regions for global form status and errors -->
        <div aria-live="polite" aria-atomic="true" class="sr-only" id="aria-status-region"></div>
        <div aria-live="assertive" aria-atomic="true" class="sr-only" id="aria-error-region"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            <?php if ($flash = Utils::getFlash()): ?>
                <div class="mb-6">
                    <?php foreach ($flash as $type => $message): ?>
                        <div class="rounded-md p-4 mb-4 <?= $type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas <?= $type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium"><?= e($message) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Page Content -->
            <div class="fade-in">
                <?= $content ?? '' ?>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include __DIR__ . '/../partials/footer.php'; ?>
    
    <!-- Floating Action Button (Mobile) -->
    <?php
        $reqPath = $_SERVER['REQUEST_URI'] ?? '';
        $reqPath = str_replace(APP_BASE, '', $reqPath);
        $reqPath = trim($reqPath, '/');
        $segments = $reqPath === '' ? [] : explode('/', $reqPath);
        $isFormPage = false;
        if (!empty($segments)) {
            $first = $segments[0];
            $second = $segments[1] ?? null;
            $formSections = ['jobs','customers','finance','appointments','contracts'];
            $formActions = ['new','edit','create'];
            $isFormPage = in_array($first, $formSections, true) && ($second !== null && in_array($second, $formActions, true));
        }
    ?>
    <!-- ===== KOZMOS_OPERATOR_READONLY: hide floating action button for operator (begin) -->
    <?php if (!$isFormPage && (!class_exists('Auth') || !Auth::check() || Auth::role() !== 'OPERATOR')): ?>
    <div class="fixed bottom-6 right-6 md:hidden">
        <div x-data="{ showActions: false }" class="relative">
            <!-- Action Menu -->
            <div x-show="showActions" x-cloak class="absolute bottom-16 right-0 mb-2 space-y-2">
                <a href="<?= base_url('/jobs/new') ?>" 
                   class="block bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 transition-colors"
                   aria-label="Yeni iş oluştur">
                    <i class="fas fa-plus"></i>
                </a>
                <a href="<?= base_url('/customers/new') ?>" 
                   class="block bg-green-600 text-white p-3 rounded-full shadow-lg hover:bg-green-700 transition-colors"
                   aria-label="Yeni müşteri ekle">
                    <i class="fas fa-user-plus"></i>
                </a>
                <a href="<?= base_url('/finance/new') ?>" 
                   class="block bg-purple-600 text-white p-3 rounded-full shadow-lg hover:bg-purple-700 transition-colors"
                   aria-label="Yeni finans kaydı ekle">
                    <i class="fas fa-money-bill"></i>
                </a>
            </div>
            
            <!-- Main FAB -->
            <button @click="showActions = !showActions" 
                    class="bg-blue-600 text-white p-4 rounded-full shadow-lg hover:bg-blue-700 transition-colors"
                    aria-label="Hızlı işlemler menüsünü aç"
                    :aria-expanded="showActions">
                <i class="fas fa-plus text-xl"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>
    <!-- ===== KOZMOS_OPERATOR_READONLY: hide floating action button for operator (end) -->
    
    <?php include __DIR__ . '/partials/global-footer.php'; ?>
</body>
</html>




