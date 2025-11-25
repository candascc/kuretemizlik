<?php
    require_once __DIR__ . '/partials/header-context.php';

    $headerVariant = $headerVariant ?? 'app';
    $hideHeader = $hideHeader ?? false;
    $hideFooter = $hideFooter ?? false;

    if ($headerVariant === 'resident') {
        $residentHeaderOptions = $residentHeaderOptions ?? [];
        $headerContext = build_resident_header_context($residentHeaderOptions);
    } else {
        $headerContext = build_app_header_context([
            'headerMeta' => $headerMeta ?? [],
            'notifications' => $notifications ?? [],
            'notificationCount' => $notificationCount ?? 0,
            'user' => [
                'username' => (class_exists('Auth') && Auth::check()) ? (Auth::user()['username'] ?? null) : ($__u ?? null),
                'showProfile' => true,
            ],
        ]);
    }

    $projectRoot = dirname(__DIR__, 3);
    $bundleCssPath = $projectRoot . '/assets/dist/app.bundle.css';
    $bundleJsPath = $projectRoot . '/assets/dist/app.bundle.js';
    $hasBundledAssets = is_file($bundleCssPath) && is_file($bundleJsPath);
    
    // Performance: Detect dashboard page for critical CSS
    $currentPath = $_SERVER['REQUEST_URI'] ?? '';
    $currentPath = str_replace(APP_BASE, '', $currentPath);
    $currentPath = trim($currentPath, '/');
    $isDashboardPage = ($currentPath === '' || $currentPath === 'dashboard' || strpos($currentPath, 'dashboard') === 0);
?>
<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    
    <?php if ($isDashboardPage): ?>
    <!-- Critical CSS for Dashboard - Performance: Inline critical styles for faster FCP -->
    <style>
        /* Critical CSS: Above-the-fold dashboard layout */
        body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;margin:0;padding:0;background:#f9fafb;min-height:100vh}
        .h-full{height:100%}
        .flex{display:flex}
        .flex-col{flex-direction:column}
        .bg-gray-50{background-color:#f9fafb}
        .py-4{padding-top:1rem;padding-bottom:1rem}
        .max-w-7xl{max-width:80rem}
        .mx-auto{margin-left:auto;margin-right:auto}
        .px-4{padding-left:1rem;padding-right:1rem}
        .grid{display:grid}
        .gap-6{gap:1.5rem}
        .bg-white{background-color:#fff}
        .rounded-xl{border-radius:0.75rem}
        .shadow{box-shadow:0 1px 3px 0 rgba(0,0,0,0.1),0 1px 2px 0 rgba(0,0,0,0.06)}
        .p-6{padding:1.5rem}
        @media(min-width:640px){.sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}.sm\:py-6{padding-top:1.5rem;padding-bottom:1.5rem}}
        @media(min-width:1024px){.lg\:px-8{padding-left:2rem;padding-right:2rem}.lg\:grid-cols-3{grid-template-columns:repeat(3,1fr)}}
    </style>
    <?php endif; ?>
    <meta name="csrf-token" content="<?= CSRF::get() ?>">
    <!-- Cache Control Meta Tags -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
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
    <?php
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
    $host = filter_var($_SERVER['HTTP_HOST'] ?? '', FILTER_SANITIZE_URL);
    $uri = e($_SERVER['REQUEST_URI'] ?? '');
    $canonicalUrl = $protocol . '://' . $host . $uri;
    ?>
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= e($title ?? 'Küre Temizlik - İş Takip Sistemi') ?>">
    <meta property="og:description" content="Profesyonel temizlik hizmetleri ve iş takip sistemi">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    
    <title><?= e($title ?? 'Küre Temizlik - İş Takip Sistemi') ?></title>
    
    <!-- Favicon - Performance: Added dimensions to prevent layout shift -->
    <link rel="icon" type="image/png" href="<?= Utils::asset('img/logokureapp.png') ?>" sizes="32x32">
    <link rel="apple-touch-icon" href="<?= Utils::asset('img/logokureapp.png') ?>" sizes="180x180">
    
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
        // Sticky shadow for nav - Optimized with requestAnimationFrame and passive listeners
        // Performance: Moved to IIFE to avoid blocking, but kept in head for early initialization
        (function() {
            function initNavScroll() {
                const navEl = document.querySelector('nav[data-header]');
                if (!navEl) { return; }

                let ticking = false;
                const updateNavState = () => {
                    if (window.scrollY > 24) {
                        navEl.classList.add('is-compact');
                        navEl.style.boxShadow = '0 3px 12px rgba(15, 23, 42, 0.15)';
                    } else {
                        navEl.classList.remove('is-compact');
                        navEl.style.boxShadow = 'none';
                    }
                    ticking = false;
                };

                const onScroll = () => {
                    if (!ticking) {
                        window.requestAnimationFrame(updateNavState);
                        ticking = true;
                    }
                };

                updateNavState();
                window.addEventListener('scroll', onScroll, { passive: true });

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
                    ['click', 'focus'].forEach(evt => searchInput.addEventListener(evt, openSearch, { passive: false }));
                    searchInput.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            openSearch(event);
                        }
                        if (event.key === 'Escape') {
                            searchInput.blur();
                        }
                    });
                }
            }
            
            // Performance: Initialize on DOMContentLoaded or immediately if DOM already loaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initNavScroll);
            } else {
                initNavScroll();
            }
        })();
    </script>
    <!-- Google Fonts - Optimized with font-display: swap for better LCP -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
    
    <!-- Preload critical resources -->
    <?php if ($hasBundledAssets): ?>
        <!-- ===== PRODUCTION FIX: Remove preload for CSS to avoid QUIC errors ===== -->
        <!-- Preload removed for CSS to prevent QUIC protocol errors -->
        <link rel="preload" href="<?= Utils::asset('dist/app.bundle.js') ?>?v=<?= Utils::assetVersion('dist/app.bundle.js') ?>" as="script">
    <?php else: ?>
        <link rel="preload" href="<?= Utils::asset('js/app.js') ?>" as="script">
    <?php endif; ?>
    <!-- ===== PRODUCTION FIX: Font preload optional to reduce warnings ===== -->
    <!-- Font preloads removed to reduce console warnings (fonts will still load normally) -->
    
    <!-- Tailwind CSS (Local build - ROUND 23) -->
    <link rel="stylesheet" href="<?= Utils::asset('css/tailwind.css') ?>?v=<?= file_exists(__DIR__ . '/../../assets/css/tailwind.css') ? filemtime(__DIR__ . '/../../assets/css/tailwind.css') : time() ?>">
    
    <!-- Custom CSS -->
    <?php if ($hasBundledAssets): ?>
        <link rel="stylesheet" href="<?= Utils::asset('dist/app.bundle.css') ?>?v=<?= Utils::assetVersion('dist/app.bundle.css') ?>">
        <link rel="stylesheet" href="<?= Utils::asset('css/custom.css') ?>?v=<?= file_exists(__DIR__ . '/../../assets/css/custom.css') ? filemtime(__DIR__ . '/../../assets/css/custom.css') : time() ?>">
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
    
    <!-- Chart.js - Lazy load only when needed (charts on page) -->
    <script src="<?= Utils::asset('vendor/chart.umd.min.js') ?>" defer></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
    
    <!-- Form Validation CSS -->
    <?php include __DIR__ . '/../partials/form-validation.php'; ?>
    
    <!-- Custom JavaScript -->
    <?php if ($hasBundledAssets): ?>
        <script src="<?= Utils::asset('dist/app.bundle.js') ?>?v=<?= Utils::assetVersion('dist/app.bundle.js') ?>" defer></script>
    <?php else: ?>
        <!-- Core scripts (critical for initial render) -->
        <script src="<?= Utils::asset('js/app.js') ?>?v=<?= Utils::assetVersion('js/app.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/form-validator.js') ?>?v=<?= Utils::assetVersion('js/form-validator.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/toast-system.js') ?>?v=<?= Utils::assetVersion('js/toast-system.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/button-loading.js') ?>?v=<?= Utils::assetVersion('js/button-loading.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/loading-states.js') ?>?v=<?= Utils::assetVersion('js/loading-states.js') ?>" defer></script>
        
        <!-- Feature-specific scripts (can be lazy loaded) -->
        <script src="<?= Utils::asset('js/keyboard-navigation.js') ?>?v=<?= Utils::assetVersion('js/keyboard-navigation.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/bottom-sheet.js') ?>?v=<?= Utils::assetVersion('js/bottom-sheet.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/modern-dialogs.js') ?>?v=<?= Utils::assetVersion('js/modern-dialogs.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/form-auto-save.js') ?>?v=<?= Utils::assetVersion('js/form-auto-save.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/keyboard-shortcuts.js') ?>?v=<?= Utils::assetVersion('js/keyboard-shortcuts.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/date-shortcuts.js') ?>?v=<?= Utils::assetVersion('js/date-shortcuts.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/timezone-handler.js') ?>?v=<?= Utils::assetVersion('js/timezone-handler.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/keyboard-shortcuts-help.js') ?>?v=<?= Utils::assetVersion('js/keyboard-shortcuts-help.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/global-search.js') ?>?v=<?= Utils::assetVersion('js/global-search.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/filter-persistence.js') ?>?v=<?= Utils::assetVersion('js/filter-persistence.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/payment-validation.js') ?>?v=<?= Utils::assetVersion('js/payment-validation.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/success-feedback.js') ?>?v=<?= Utils::assetVersion('js/success-feedback.js') ?>" defer></script>
        <script src="<?= Utils::asset('js/mobile-table-cards.js') ?>?v=<?= Utils::assetVersion('js/mobile-table-cards.js') ?>" defer></script>
    <?php endif; ?>
    
    <!-- Inline CSS (Legacy - Continued) -->
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol"; }
        [x-cloak] { display: none !important; }
        
        nav[data-header] .header-top { transition: padding 0.3s ease; }
        nav[data-header] .logo-title { transition: transform 0.3s ease, font-size 0.3s ease; }
        nav[data-header].is-compact .header-top { padding-top: 0.45rem !important; padding-bottom: 0.45rem !important; }
        nav[data-header].is-compact .logo-title { font-size: 0.95rem; }
        nav[data-header].is-compact .quick-action-btn { padding-top: 0.45rem; padding-bottom: 0.45rem; }

        .quick-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.6rem 1.1rem;
            min-height: 44px;
            min-width: 44px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.17);
            border: 1px solid rgba(255, 255, 255, 0.26);
            color: rgba(255, 255, 255, 0.98);
            font-weight: 600;
            letter-spacing: 0.01em;
            transition: all 0.2s ease;
        }

        .quick-action-btn:hover,
        .quick-action-btn:focus {
            background: rgba(255, 255, 255, 0.28);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.25);
        }

        .quick-action-btn:focus-visible {
            outline: 3px solid rgba(255, 255, 255, 0.65);
            outline-offset: 3px;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(15, 23, 42, 0.35);
            backdrop-filter: blur(8px);
            color: rgba(255, 255, 255, 0.96);
            font-weight: 600;
        }

        .footer-status-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            border: 1px solid rgba(15, 23, 42, 0.12);
            background: rgba(15, 23, 42, 0.04);
            color: #0f172a;
            font-weight: 600;
            font-size: 0.75rem;
            letter-spacing: 0.02em;
            line-height: 1.1;
        }

        .dark .footer-status-chip {
            border-color: rgba(148, 163, 184, 0.5);
            background: rgba(148, 163, 184, 0.12);
            color: rgba(226, 232, 240, 0.95);
        }

        footer.footer-container {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            margin-top: auto !important;
            position: relative !important;
            clear: both !important;
            flex-shrink: 0 !important;
            z-index: 1 !important;
        }

        footer.footer-container > .footer-inner {
            width: 100% !important;
            max-width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
            box-sizing: border-box !important;
        }

        @media (min-width: 640px) {
            footer.footer-container > .footer-inner {
                max-width: 80rem !important;
                margin-left: auto !important;
                margin-right: auto !important;
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
        }

        @media (min-width: 1024px) {
            footer.footer-container > .footer-inner {
                padding-left: 2rem !important;
                padding-right: 2rem !important;
            }
        }

        .footer-container .footer-grid {
            width: 100% !important;
            max-width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            box-sizing: border-box !important;
        }

        @media (max-width: 639px) {
            .footer-container .footer-grid {
                gap: 1.5rem !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            .footer-container .footer-grid > div {
                width: 100% !important;
                max-width: 100% !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
        }

        .footer-meta {
            width: 100% !important;
        }

        .footer-metrics .footer-status-chip {
            border-color: rgba(37, 99, 235, 0.35) !important;
            background: rgba(37, 99, 235, 0.12) !important;
            color: rgba(15, 23, 42, 0.95) !important;
        }

        .dark .footer-metrics .footer-status-chip {
            border-color: rgba(148, 163, 184, 0.55) !important;
            background: rgba(148, 163, 184, 0.18) !important;
            color: rgba(226, 232, 240, 0.98) !important;
        }

        .header-search-input {
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.12);
        }

        .header-search-input:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        .module-subnav {
            scrollbar-width: none;
        }

        .module-subnav::-webkit-scrollbar {
            display: none;
        }

        .module-subnav-link {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.75rem;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.82);
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .module-subnav-link:hover {
            background: rgba(255, 255, 255, 0.24);
            color: #ffffff;
        }

        .module-subnav-link.is-active {
            background: #ffffff;
            color: #1d4ed8;
            box-shadow: 0 4px 12px rgba(29, 78, 216, 0.25);
        }

        .module-subnav-link.is-active i {
            color: #1d4ed8;
        }

        .notif-item {
            margin-bottom: 0.5rem;
            border-radius: 0.75rem;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .notif-item:hover {
            background: rgba(37, 99, 235, 0.08);
            transform: translateY(-1px);
        }

        .notif-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: rgba(255, 255, 255, 0.85);
            color: #1d4ed8;
        }

        .dark .notif-pill {
            background: rgba(37, 99, 235, 0.2);
            color: #bfdbfe;
        }

        /* Ensure Font Awesome loads properly */
        .fas, .far, .fal, .fad, .fab {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "Font Awesome 6 Brands", "FontAwesome" !important;
            font-weight: 900;
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
            line-height: 1;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .far {
            font-weight: 400;
        }
        .fal {
            font-weight: 300;
        }
        .fab {
            font-family: "Font Awesome 6 Brands", "FontAwesome" !important;
        }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        /* Flash message animations */
        @keyframes slideInDown { 
            from { 
                opacity: 0; 
                transform: translateY(-20px); 
            } 
            to { 
                opacity: 1; 
                transform: translateY(0); 
            } 
        }
        
        /* Loading states */
        .loading { opacity: 0.6; pointer-events: none; }
        .loading::after { 
            content: ''; 
            position: absolute; 
            top: 50%; 
            left: 50%; 
            width: 20px; 
            height: 20px; 
            margin: -10px 0 0 -10px; 
            border: 2px solid #f3f3f3; 
            border-top: 2px solid #3498db; 
            border-radius: 50%; 
            animation: spin 1s linear infinite; 
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        /* Form validation */
        .form-error { 
            border-color: #ef4444 !important; 
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }
        .form-success { 
            border-color: #10b981 !important; 
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
        }
        
        /* Form field error states */
        .form-error input,
        .form-error select,
        .form-error textarea {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }
        
        /* Form error label */
        .form-error label {
            color: #ef4444 !important;
        }
        
        /* Skeleton loading */
        .skeleton { 
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); 
            background-size: 200% 100%; 
            animation: loading 1.5s infinite; 
        }
        @keyframes loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        
        /* Dropdown animations */
        .dropdown-enter {
            animation: dropdownFadeIn 0.15s ease-out;
        }
        
        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .mobile-stack { flex-direction: column !important; }
            .mobile-full { width: 100% !important; }
            .mobile-text-sm { font-size: 0.875rem !important; }
            .mobile-p-2 { padding: 0.5rem !important; }
            
            /* Prevent horizontal overflow */
            .overflow-x-auto {
                -webkit-overflow-scrolling: touch;
            }
            
            /* Better table responsiveness */
            table {
                min-width: 100%;
            }
            
            /* Calendar mobile improvements */
            .calendar-grid {
                font-size: 0.75rem;
            }
            
            /* Better touch targets */
            button, a {
                min-height: 44px;
                min-width: 44px;
            }
        }
        
        
        /* Touch targets */
        .touch-target { min-height: 44px; min-width: 44px; }
        
        /* Enhanced focus states */
        .focus-ring:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
            border-color: #3b82f6;
        }
        
        .focus-ring-dark:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
            border-color: #3b82f6;
        }
        
        /* Enhanced hover effects */
        .hover-lift:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .hover-scale:hover {
            transform: scale(1.02);
        }
        
        /* Performance optimizations */
        .will-change-transform { will-change: transform; }
        .will-change-opacity { will-change: opacity; }
        
        /* Lazy loading placeholder */
        .lazy-placeholder { 
            background: #f3f4f6; 
            min-height: 200px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        
        /* Typography hierarchy */
        .text-display {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.025em;
        }
        
        .text-heading-1 {
            font-size: 2rem;
            font-weight: 600;
            line-height: 1.3;
            letter-spacing: -0.025em;
        }
        
        .text-heading-2 {
            font-size: 1.5rem;
            font-weight: 600;
            line-height: 1.4;
            letter-spacing: -0.025em;
        }
        
        .text-heading-3 {
            font-size: 1.25rem;
            font-weight: 600;
            line-height: 1.4;
        }
        
        .text-body-large {
            font-size: 1.125rem;
            font-weight: 400;
            line-height: 1.6;
        }
        
        .text-body {
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.6;
        }
        
        .text-body-small {
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.5;
        }
        
        .text-caption {
            font-size: 0.75rem;
            font-weight: 500;
            line-height: 1.4;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        /* Custom icon utilities */
        .icon-sm { font-size: 0.875rem; }
        .icon-md { font-size: 1rem; }
        .icon-lg { font-size: 1.25rem; }
        .icon-xl { font-size: 1.5rem; }
        .icon-2xl { font-size: 2rem; }
        
        .icon-primary { color: #3b82f6; }
        .icon-secondary { color: #22c55e; }
        .icon-accent { color: #d946ef; }
        .icon-success { color: #10b981; }
        .icon-warning { color: #f59e0b; }
        .icon-error { color: #ef4444; }
        .icon-gray { color: #6b7280; }
        
        /* Icon animations */
        .icon-spin { animation: spin 1s linear infinite; }
        .icon-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        .icon-bounce { animation: bounce 1s infinite; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        
        @keyframes bounce {
            0%, 100% {
                transform: translateY(-25%);
                animation-timing-function: cubic-bezier(0.8,0,1,1);
            }
            50% {
                transform: none;
                animation-timing-function: cubic-bezier(0,0,0.2,1);
            }
        }
        
        /* Text readability improvements */
        .text-readable {
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .text-contrast-high {
            color: #1f2937;
        }
        
        .text-contrast-medium {
            color: #4b5563;
        }
        
        .text-contrast-low {
            color: #6b7280;
        }
        
        
        /* Link readability */
        .link-readable {
            color: #2563eb;
            text-decoration: underline;
            text-underline-offset: 2px;
            text-decoration-thickness: 1px;
        }
        
        .link-readable:hover {
            color: #1d4ed8;
            text-decoration-thickness: 2px;
        }
        
        /* Responsive grid - mobile-first approach */
        .grid.grid-cols-3 {
            display: grid !important;
            grid-template-columns: 1fr !important; /* Mobile: 1 column */
        }
        
        @media (min-width: 640px) {
            .grid.grid-cols-3 {
                grid-template-columns: repeat(2, 1fr) !important; /* Tablet: 2 columns */
            }
        }
        
        @media (min-width: 1024px) {
            .grid.grid-cols-3 {
                grid-template-columns: repeat(3, 1fr) !important; /* Desktop: 3 columns */
            }
        }
        
        .grid.grid-cols-3 > div {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
    </style>
    <style type="text/tailwindcss">
        @layer components {
            .responsive-table {
                @apply overflow-x-auto;
            }

            .responsive-table table {
                @apply min-w-full divide-y divide-gray-200 dark:divide-gray-700;
            }

            .responsive-table th {
                @apply px-3 sm:px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider;
            }

            .responsive-table td {
                @apply px-3 sm:px-6 py-4 whitespace-nowrap text-sm;
            }

            .responsive-table .action-buttons {
                @apply flex space-x-2 sm:space-x-3;
            }

            .responsive-table .action-button {
                @apply p-1 rounded transition-colors duration-150;
            }

            .responsive-form {
                @apply p-4 sm:p-6 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700;
            }

            .responsive-form .form-grid {
                @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4;
            }

            .responsive-form .form-input {
                @apply w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white transition-all duration-200 text-sm;
            }

            .responsive-form .form-input:focus {
                @apply outline-none;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.35);
                border-color: #3b82f6;
            }

            .responsive-form .form-label {
                @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2;
            }

            .responsive-form .form-button {
                @apply w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg shadow-soft hover:shadow-medium transition-all duration-200 text-sm;
            }

            .responsive-form .form-button:focus {
                @apply outline-none;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
            }

            .responsive-card {
                @apply bg-white dark:bg-gray-800 rounded-xl shadow-soft border border-gray-200 dark:border-gray-700 p-4 sm:p-6;
            }

            .responsive-card-header {
                @apply flex items-center mb-4 sm:mb-6;
            }

            .responsive-card-icon {
                @apply flex-shrink-0 p-2 bg-primary-100 dark:bg-primary-900/20 rounded-lg mr-3;
            }

            .responsive-card-title {
                @apply text-lg sm:text-xl font-bold text-gray-900 dark:text-white;
            }

            .responsive-grid {
                @apply grid grid-cols-1 gap-4 sm:gap-6;
            }

            .responsive-grid-2 {
                @apply grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6;
            }

            .responsive-grid-3 {
                @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6;
            }

            .responsive-grid-4 {
                @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6;
            }

            .responsive-text-xs {
                @apply text-xs sm:text-sm;
            }

            .responsive-text-sm {
                @apply text-sm sm:text-base;
            }

            .responsive-text-base {
                @apply text-base sm:text-lg;
            }

            .responsive-text-lg {
                @apply text-lg sm:text-xl;
            }

            .responsive-text-xl {
                @apply text-xl sm:text-2xl;
            }

            .responsive-p {
                @apply p-4 sm:p-6;
            }

            .responsive-px {
                @apply px-3 sm:px-6;
            }

            .responsive-py {
                @apply py-4 sm:py-6;
            }

            .responsive-mb {
                @apply mb-4 sm:mb-6;
            }

            .responsive-mt {
                @apply mt-4 sm:mt-6;
            }

            .responsive-button {
                @apply inline-flex items-center px-4 sm:px-6 py-2 sm:py-3 text-sm font-semibold rounded-lg shadow-soft hover:shadow-medium transition-all duration-200;
            }

            .responsive-button:focus {
                @apply outline-none;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.45);
            }

            .responsive-button-primary {
                @apply responsive-button bg-primary-600 hover:bg-primary-700 text-white;
            }

            .responsive-button-primary:focus {
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.55);
            }

            .responsive-button-secondary {
                @apply responsive-button bg-gray-700 hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 text-white;
            }

            .responsive-button-secondary:focus {
                box-shadow: 0 0 0 2px rgba(107, 114, 128, 0.55);
            }

            .responsive-icon {
                @apply text-sm sm:text-base;
            }

            .responsive-icon-sm {
                @apply text-xs sm:text-sm;
            }

            .responsive-icon-lg {
                @apply text-base sm:text-lg;
            }
        }
    </style>
    
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
            
            // Store original parent reference if not already stored
            if (!dropdown._originalParent) {
                dropdown._originalParent = dropdown.parentElement;
            }
            
            // Close all first
            document.querySelectorAll('[id^="dropdown-"]').forEach(function(el){ 
                el.style.display='none';
                // Return to original parent if moved to body
                if (el._originalParent && el.parentElement === document.body && el._originalParent !== document.body) {
                    el._originalParent.appendChild(el);
                }
            });
            
            if (!isVisible) {
                let button = null;
                if (event && event.target) {
                    button = event.target.closest('button');
                }
                if (!button) {
                    // Fallback to known buttons
                    const idMap = {
                        settings: 'nav-settings',
                        operations: 'nav-operations',
                        reports: 'nav-reports',
                        'management-services': 'nav-management-services',
                        system: 'system-menu-button'
                    };
                    const btnId = idMap[name];
                    if (btnId) { button = document.getElementById(btnId); }
                }
                if (button) {
                    const rect = button.getBoundingClientRect();
                    // Move dropdown to body to escape stacking context issues
                    if (dropdown.parentElement && dropdown.parentElement !== document.body) {
                        document.body.appendChild(dropdown);
                    }
                    
                    // Set initial position
                    dropdown.style.position = 'fixed';
                    dropdown.style.zIndex = '99999';
                    dropdown.style.display = 'block';
                    
                    // Calculate position - for fixed positioning, use getBoundingClientRect() directly (viewport coordinates)
                    let top = rect.bottom + 8; // 8px margin below button
                    let left = rect.left;
                    
                    // Get dropdown dimensions after it's displayed
                    const dropdownRect = dropdown.getBoundingClientRect();
                    const viewportWidth = window.innerWidth;
                    const viewportHeight = window.innerHeight;
                    
                    // Adjust if dropdown goes off right edge - align to right edge of button
                    if (left + dropdownRect.width > viewportWidth) {
                        left = rect.right - dropdownRect.width;
                        // If still off screen, align to viewport right
                        if (left < 0) {
                            left = viewportWidth - dropdownRect.width - 8;
                        }
                    }
                    
                    // Adjust if dropdown goes off bottom edge - show above button instead
                    if (top + dropdownRect.height > viewportHeight) {
                        top = rect.top - dropdownRect.height - 8;
                        // If still off screen, align to viewport top
                        if (top < 0) {
                            top = 8;
                        }
                    }
                    
                    // Adjust if dropdown goes off left edge
                    if (left < 0) {
                        left = 8;
                    }
                    
                    // Apply final position
                    dropdown.style.top = top + 'px';
                    dropdown.style.left = left + 'px';
                } else {
                    dropdown.style.display = 'block';
                }
            } else {
                dropdown.style.display = 'none';
            }
            return false;
        };
        
        // Mobile menu toggle
        window.toggleMobileMenu = function() {
            // Performance: Removed console.log for production
            const menu = document.getElementById('mobile-menu');
            if (menu) {
                const isVisible = menu.style.display !== 'none';
                menu.style.display = isVisible ? 'none' : 'block';
            }
        };
        
        // Dark mode helpers
            function setThemeMode(mode){
                const html = document.documentElement; const body=document.body;
                html.classList.remove('dark'); body.classList.remove('dark');
                if(mode==='dark'){ html.classList.add('dark'); body.classList.add('dark'); }
                localStorage.setItem('themeMode', mode);
                // Update both desktop and mobile icons
                const icon = document.getElementById('dark-mode-icon');
                const iconMobile = document.getElementById('dark-mode-icon-mobile');
                const iconClass = 'fas text-lg ' + (mode==='dark' ? 'fa-sun' : 'fa-moon');
                if(icon){ icon.className = iconClass; }
                if(iconMobile){ iconMobile.className = iconClass; }
            }
            window.toggleDarkMode = function(){
                const cur = localStorage.getItem('themeMode') || 'light';
                const next = cur==='light' ? 'dark' : 'light';
                setThemeMode(next);
            };
        
        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            // Performance: Removed console.log for production
            
            // Load dark mode from localStorage or system preference
            const stored = localStorage.getItem('themeMode');
            if(stored){ 
                setThemeMode(stored); 
            } else {
                const prefers = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                const initialMode = prefers ? 'dark' : 'light';
                setThemeMode(initialMode);
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
        });
    </script>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 flex flex-col transition-colors duration-300">
    <?php
        $currentPathRaw = $headerContext['paths']['currentRaw'] ?? '';
        $currentPath = $headerContext['paths']['current'] ?? '';
        $currentPathSegments = $currentPathRaw === '' ? [] : explode('/', $currentPathRaw);
        $companyFilterParam = isset($_GET['company_filter']) && $_GET['company_filter'] !== '' ? (int)$_GET['company_filter'] : null;
    ?>
    <?php if (!$hideHeader): ?>
        <?php include __DIR__ . '/partials/app-header.php'; ?>
    <?php endif; ?>
<?php if (false) { ?><?php } ?>
    
    
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
                <?= $content ?>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <?php if (!$hideFooter): ?>
        <?php include __DIR__ . '/../partials/footer.php'; ?>
    <?php endif; ?>
    
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
    
    <!-- Scripts -->
    <script>
        // Auto-hide flash messages
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessages = document.querySelectorAll('.flash-message, .bg-green-50.border.border-green-200, .bg-red-50.border.border-red-200');
            flashMessages.forEach(function(message) {
                setTimeout(function() {
                    message.style.transition = 'opacity 0.5s';
                    message.style.opacity = '0';
                    setTimeout(function() {
                        message.remove();
                    }, 500);
                }, 10000); // 10 saniye olarak artırıldı
            });
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuButton = event.target.closest('[onclick*="toggleMobileMenu"]');
            
            if (mobileMenu && !mobileMenu.contains(event.target) && !mobileMenuButton) {
                mobileMenu.style.display = 'none';
            }
        });
        
        // Global loading functions
        window.showGlobalLoading = function() {
            const overlay = document.getElementById('globalLoading');
            if (overlay) {
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
            }
        };
        
        window.hideGlobalLoading = function() {
            const overlay = document.getElementById('globalLoading');
            if (overlay) {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
            }
        };
    </script>

    <!-- Global Loading Overlay -->
    <div id="globalLoading" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-strong p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
            <span class="text-gray-700 dark:text-gray-300">Yükleniyor...</span>
        </div>
    </div>

    <?php include __DIR__ . '/partials/global-footer.php'; ?>
</body>
</html>
