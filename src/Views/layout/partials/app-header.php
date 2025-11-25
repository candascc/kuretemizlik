<?php

require_once __DIR__ . '/../../../Lib/Utils.php';

$headerContext = $headerContext ?? [];
$variant = $headerContext['variant'] ?? 'app';
$brand = $headerContext['brand'] ?? ['label' => 'Küre Temizlik', 'icon' => 'fas fa-broom', 'url' => base_url('/')];
$mode = $headerContext['mode'] ?? [];
$modeTheme = $mode['theme'] ?? [
    'navGradient' => 'from-primary-600 to-primary-700',
    'navClass' => 'mode-operations',
    'quickVariant' => 'variant-operations',
    'accent' => 'text-white',
];
$headerMetaChips = $headerContext['headerMetaChips'] ?? [];
$statusWidgets = $headerContext['statusWidgets'] ?? [];
$quickActions = $headerContext['quickActions'] ?? [];
$navigationItems = $headerContext['navigationItems'] ?? [];
$contextLinks = $headerContext['contextLinks'] ?? [];
$paths = $headerContext['paths'] ?? ['currentRaw' => '', 'current' => ''];
$user = $headerContext['user'] ?? [];
$systemMenu = $headerContext['systemMenu'] ?? [];
$ui = $headerContext['ui'] ?? [
    'showSearch' => true,
    'showStatusChips' => true,
    'showQuickActions' => false,
    'showModeSwitcher' => true,
    'showNotifications' => true,
    'showSystemMenu' => true,
];

$modeIcons = $mode['icons'] ?? [];
$availableModes = $mode['available'] ?? [];
$currentMode = $mode['current'] ?? null;

$isAuthenticated = $user['isAuthenticated'] ?? false;
$username = $user['username'] ?? null;
$logoutUrl = $user['logoutUrl'] ?? base_url('/logout');
$loginUrl = $user['loginUrl'] ?? base_url('/login');
$isSuperAdmin = $user['isSuperAdmin'] ?? false;
$customActions = $user['actions'] ?? [];
$notificationsInfo = $user['notifications'] ?? ['enabled' => false, 'count' => 0, 'items' => []];
$notificationsEnabled = $notificationsInfo['enabled'] ?? false;
$notificationCount = (int)($notificationsInfo['count'] ?? 0);
$navGradientClass = $modeTheme['navGradient'] ?? 'from-primary-600 to-primary-700';
$navThemeClass = $modeTheme['navClass'] ?? 'mode-operations';
$quickVariantClass = $modeTheme['quickVariant'] ?? 'variant-operations';
$accentClass = $modeTheme['accent'] ?? 'text-white';
$navGradientStyle = $headerContext['navGradientStyle'] ?? ($modeTheme['inlineGradient'] ?? null);
$navGradientStyleAttr = $navGradientStyle ? ' style="' . e($navGradientStyle) . ' will-change: transform;"' : ' style="will-change: transform;"';
$currentPathRaw = $paths['currentRaw'] ?? '';

$brandIcon = $brand['icon'] ?? null;
$brandLabel = $brand['label'] ?? 'Küre Temizlik';
$brandLogo = $brand['logo'] ?? null;
$brandLogoPath = $brand['logo_path'] ?? null;
$brandLogoVersion = ($brandLogo && $brandLogoPath) ? Utils::assetVersion($brandLogoPath) : null;
$brandLogoSrc = $brandLogo ? $brandLogo . ($brandLogoVersion ? '?v=' . $brandLogoVersion : '') : null;
$brandLogoFallback = $brand['logo_fallback'] ?? null;
$brandLogoFallbackPath = $brand['logo_fallback_path'] ?? null;
$brandLogoFallbackVersion = ($brandLogoFallback && $brandLogoFallbackPath) ? Utils::assetVersion($brandLogoFallbackPath) : null;
$brandLogoFallbackSrc = $brandLogoFallback ? $brandLogoFallback . ($brandLogoFallbackVersion ? '?v=' . $brandLogoFallbackVersion : '') : null;
$brandLogoExtension = $brandLogoPath ? strtolower(pathinfo($brandLogoPath, PATHINFO_EXTENSION)) : null;
$isBrandLogoPdf = $brandLogoExtension === 'pdf';

?>
<nav data-header class="sticky top-0 z-[9998] header-shell bg-gradient-to-r <?= e($navGradientClass) ?> <?= e($navThemeClass) ?>"<?= $navGradientStyleAttr ?>>
    <div class="header-top border-b border-white/10">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-4">
            <?php if ((!empty($headerMetaChips)) || (!empty($statusWidgets) && ($ui['showStatusChips'] ?? true)) || ($isSuperAdmin)): ?>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between text-white/80 text-[11px] sm:text-xs mb-3">
                <div class="flex flex-wrap items-center gap-2">
                    <?php foreach ($headerMetaChips as $chip): ?>
                        <span class="status-chip bg-white/12 border border-white/20 text-white px-2 py-1 rounded-lg"><?= e($chip) ?></span>
                    <?php endforeach; ?>
                    <?php if ($isSuperAdmin): ?>
                        <?php include __DIR__ . '/../../partials/company-context-header.php'; ?>
                    <?php endif; ?>
                </div>
                <?php if (($ui['showStatusChips'] ?? true) && !empty($statusWidgets)): ?>
                <div class="flex flex-wrap items-center gap-2 md:justify-end">
                    <?php foreach ($statusWidgets as $widget): ?>
                        <?php $widgetClass = trim('status-chip inline-flex items-center gap-1 ' . ($widget['class'] ?? '')); ?>
                        <?php $isModeBadge = strpos($widgetClass, 'mode-badge') !== false; ?>
                        <span class="<?= e($widgetClass) ?><?= $isModeBadge ? ' hidden sm:inline-flex' : '' ?>"<?= !empty($widget['id']) ? ' id="' . e($widget['id']) . '"' : '' ?>>
                            <?php if (!empty($widget['icon'])): ?><i class="fas <?= e($widget['icon']) ?> text-white/90 text-xs" aria-hidden="true"></i><?php endif; ?>
                            <span><?= htmlspecialchars($widget['text'] ?? '') ?></span>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="flex flex-col sm:hidden text-white/80 text-[11px] mb-2">
                <div class="flex items-center justify-end gap-2">
                    <!-- Dark Mode Toggle Button (Mobile) -->
                    <button id="dark-mode-toggle-mobile" onclick="toggleDarkMode()" class="inline-flex items-center justify-center p-2 text-white hover:bg-white/15 rounded-lg transition-colors duration-200" title="Karanlık mod" aria-label="Karanlık mod">
                        <i id="dark-mode-icon-mobile" class="fas fa-moon text-lg" aria-hidden="true"></i>
                    </button>
                    
                    <?php if (!empty($user['showNotifications']) && $user['showNotifications'] === true || (($ui['showNotifications'] ?? true) && $notificationsEnabled)): ?>
                    <div class="relative" id="notif-root-mobile">
                        <button id="notif-button-mobile"
                                type="button"
                                class="relative inline-flex items-center justify-center p-2 text-white hover:bg-white/15 rounded-lg transition-colors duration-200 focus:ring-2 focus:ring-white/60 focus:outline-none"
                                title="Bildirimler"
                                aria-label="Bildirimler"
                                aria-haspopup="dialog"
                                aria-expanded="false"
                                aria-controls="notif-menu-mobile">
                            <i class="fas fa-bell text-lg" aria-hidden="true"></i>
                            <span id="notif-badge-mobile" class="absolute -top-1 -right-1 flex items-center justify-center w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full border-2 border-primary-600 <?= $notificationCount > 0 ? '' : 'hidden' ?>">
                                <?= $notificationCount > 0 ? ($notificationCount > 9 ? '9+' : $notificationCount) : '' ?>
                            </span>
                        </button>
                        <div id="notif-menu-mobile"
                             data-variant="mobile"
                             class="notification-panel hidden absolute right-0 mt-3 w-[calc(100vw-1.5rem)] max-w-96 z-[10000]"
                             role="dialog"
                             aria-modal="true"
                             aria-labelledby="notif-title-mobile">
                            <div class="notification-panel__scrim" data-notif-dismiss aria-hidden="true"></div>
                            <div class="notification-panel__backdrop">
                                <div class="notification-panel__border-glow"></div>
                                <div class="notification-panel__header">
                                    <div class="notification-panel__header-content">
                                        <div class="notification-panel__title" id="notif-title-mobile">
                                            <i class="fas fa-bell notification-panel__title-icon"></i>
                                            <span>Bildirimler</span>
                                            <span class="notification-panel__count-badge <?= $notificationCount > 0 ? '' : 'hidden' ?>" id="notif-count-chip-mobile">
                                                <?= $notificationCount > 0 ? ($notificationCount > 99 ? '99+' : $notificationCount) : '' ?>
                                            </span>
                                        </div>
                                        <div class="notification-panel__actions">
                                            <button id="notif-mark-all-mobile" type="button" class="notification-panel__action-btn" title="Tümünü okundu say">
                                                <i class="fas fa-check-double"></i>
                                            </button>
                                            <button id="notif-mute-mobile" type="button" class="notification-panel__action-btn" title="Sessize al">
                                                <i class="fas fa-volume-mute"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div id="notif-list-mobile" class="notification-panel__list" role="list">
                                    <?php if (!empty($notificationsInfo['items'])): ?>
                                        <?php foreach ($notificationsInfo['items'] as $n): ?>
                                            <?php $n_local = $n; include __DIR__ . '/../../partials/ui/notification-item.php'; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="notification-panel__empty">
                                            <div class="notification-panel__empty-icon">
                                                <i class="fas fa-bell-slash"></i>
                                            </div>
                                            <div class="notification-panel__empty-text">Yeni bildirim yok</div>
                                            <div class="notification-panel__empty-subtext">Tüm bildirimleriniz burada görünecek</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-panel__footer">
                                    <a href="<?= base_url('/notifications') ?>" class="notification-panel__footer-link">
                                        <span>Tümünü Gör</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($isAuthenticated && $username): ?>
                    <div class="inline-flex items-center bg-white/15 px-3 py-1 rounded-md">
                        <i class="fas fa-user text-white text-xs mr-1" aria-hidden="true"></i>
                        <span class="text-white text-xs"><?= e($username) ?></span>
                    </div>
                    <a href="<?= e($logoutUrl) ?>"
                       class="inline-flex items-center justify-center p-2 bg-white/15 text-white rounded-md hover:bg-white/25 transition-all duration-200"
                       aria-label="Çıkış">
                        <i class="fas fa-sign-out-alt text-xs"></i>
                    </a>
                    <?php elseif (!$isAuthenticated): ?>
                    <a href="<?= e($loginUrl) ?>"
                       class="inline-flex items-center px-3 py-1.5 bg-white/15 hover:bg-white/25 text-white text-xs rounded-md transition-all duration-200">
                        <i class="fas fa-sign-in-alt mr-1 text-xs" aria-hidden="true"></i>
                        <span>Giriş</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-[auto,1fr,auto] items-start lg:items-center">
                <div class="flex items-center gap-3 min-w-0 w-full sm:w-auto">
                    <a href="<?= htmlspecialchars($brand['url'] ?? base_url('/')) ?>" class="flex items-center gap-3 group shrink-0" aria-label="<?= e($brandLabel) ?>">
                        <!-- Su yeşili ve altın renkli uyumlu gradient border - Logo eksiksiz görünecek, beyaz alan minimize -->
                        <span class="relative inline-flex items-center justify-center rounded-full p-[2px] sm:p-[2.5px] bg-gradient-to-br from-teal-400 via-cyan-400 to-amber-400 shadow-lg ring-2 ring-teal-400/30 transition-all duration-200 group-hover:shadow-xl group-hover:ring-teal-400/50 group-hover:from-teal-500 group-hover:via-cyan-500 group-hover:to-amber-500">
                            <span class="relative h-[5.625rem] w-[5.625rem] sm:h-[6.5625rem] sm:w-[6.5625rem] rounded-full bg-white dark:bg-gray-800 flex items-center justify-center overflow-hidden shadow-[inset_0_0_8px_rgba(0,0,0,0.08),inset_0_0_4px_rgba(20,184,166,0.15)] backdrop-blur-[0.5px]">
                                <?php if ($brandLogoSrc && $isBrandLogoPdf): ?>
                                    <object data="<?= e($brandLogoSrc) ?>#toolbar=0&navpanes=0&scrollbar=0"
                                            type="application/pdf"
                                            class="h-full w-full transform origin-center scale-[1.05]"
                                            style="pointer-events:none;border:0;filter:drop-shadow(0_0_2px_rgba(0,0,0,0.1));"
                                            aria-label="<?= e($brandLabel) ?> Logosu">
                                        <?php if ($brandLogoFallbackSrc): ?>
                                            <!-- Performance: WebP support with fallback -->
                                            <picture>
                                                <source srcset="<?= str_replace('.png', '.webp', e($brandLogoFallbackSrc)) ?>" type="image/webp">
                                                <img src="<?= e($brandLogoFallbackSrc) ?>" width="32" height="32" alt="<?= e($brandLabel) ?> Logosu"
                                                     class="h-full w-full object-contain transform origin-center scale-[1.05]"
                                                     loading="eager" decoding="async"
                                                     style="filter:drop-shadow(0_0_2px_rgba(0,0,0,0.1));">
                                            </picture>
                                        <?php else: ?>
                                            <span class="sr-only"><?= e($brandLabel) ?> Logosu</span>
                                        <?php endif; ?>
                                    </object>
                                <?php elseif ($brandLogoSrc): ?>
                                    <!-- Performance: WebP support with fallback -->
                                    <picture>
                                        <source srcset="<?= str_replace(['.png', '.jpg', '.jpeg'], '.webp', e($brandLogoSrc)) ?>" type="image/webp">
                                        <img src="<?= e($brandLogoSrc) ?>" width="32" height="32" alt="<?= e($brandLabel) ?> Logosu"
                                             class="h-full w-full object-contain transform origin-center scale-[1.05]"
                                             loading="eager" decoding="async"
                                             style="max-width: 100%; max-height: 100%;filter:drop-shadow(0_0_2px_rgba(0,0,0,0.1));">
                                    </picture>
                                <?php elseif (!empty($brandIcon)): ?>
                                    <i class="<?= e($brandIcon) ?> text-primary-600 dark:text-primary-400 text-2xl" aria-hidden="true"></i>
                                <?php else: ?>
                                    <i class="fas fa-house-user text-primary-600 dark:text-primary-400 text-2xl" aria-hidden="true"></i>
                                <?php endif; ?>
                            </span>
                        </span>
                    </a>
                    <?php if (($ui['showModeSwitcher'] ?? true) && is_array($availableModes) && count($availableModes) > 1): ?>
                    <div class="flex-1 flex justify-end sm:hidden">
                        <div class="inline-flex items-center gap-1 rounded-full border border-white/25 bg-white/10 backdrop-blur-sm p-1 text-[11px] mode-switcher">
                            <?php foreach ($availableModes as $modeKey => $meta): ?>
                                <?php
                                    $isActiveMode = ($modeKey === $currentMode);
                                    $modeUrl = class_exists('HeaderManager') ? HeaderManager::getModeToggleUrl($modeKey) : '#';
                                    $modeLabel = $meta['short_label'] ?? ucfirst($modeKey);
                                    $modeDescription = $meta['description'] ?? '';
                                ?>
                                <a href="<?= e($modeUrl) ?>"
                                   class="mode-switcher__option <?= $isActiveMode ? 'is-active' : '' ?>"
                                   <?php if ($modeDescription): ?>title="<?= e($modeDescription) ?>"<?php endif; ?>
                                   role="tab"
                                   aria-selected="<?= $isActiveMode ? 'true' : 'false' ?>">
                                    <span><?= e($modeLabel) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (($ui['showSearch'] ?? false) || (!empty($quickActions) && ($ui['showQuickActions'] ?? true))): ?>
                <div class="flex flex-col gap-3 w-full">
                    <?php if ($ui['showSearch'] ?? false): ?>
                    <div class="relative group header-search">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-white/60"></i>
                        <input id="header-search-trigger"
                               type="search"
                               readonly
                               placeholder="Hızlı arama (Ctrl + /)"
                               class="w-full header-search-input pl-11 pr-20 py-2.5 rounded-xl bg-white/15 border border-white/20 text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/50 focus:bg-white/20 cursor-pointer transition-all duration-200"
                               aria-label="Hızlı arama">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] uppercase tracking-wider text-white/70 px-2 py-1 bg-white/10 rounded-md border border-white/20">Ctrl + /</span>
                    </div>
                    <?php endif; ?>

                    <?php if (($ui['showQuickActions'] ?? true) && !empty($quickActions)): ?>
                    <div class="header-actions mt-2">
                        <div class="header-actions__primary quick-actions-row flex flex-wrap items-center gap-2 text-xs sm:text-sm">
                            <?php foreach ($quickActions as $action): ?>
                                <?php
                                    $actionUrl = $action['url'] ?? '#';
                                    if (!preg_match('~^https?://~i', $actionUrl)) {
                                        $actionUrl = base_url($actionUrl);
                                    }
                                    $actionClasses = trim('quick-action-btn ' . ($action['variant'] ?? $quickVariantClass));
                                ?>
                                <a href="<?= e($actionUrl) ?>" class="<?= e($actionClasses) ?>">
                                    <?php if (!empty($action['icon'])): ?><i class="fas <?= e($action['icon']) ?> text-xs sm:text-sm"></i><?php endif; ?>
                                    <span><?= htmlspecialchars($action['label'] ?? '') ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php if (($ui['showModeSwitcher'] ?? true) && is_array($availableModes) && count($availableModes) > 1): ?>
                            <div class="header-actions__secondary mode-switcher hidden sm:inline-flex items-center gap-1 rounded-full border border-white/25 bg-white/10 backdrop-blur-sm p-1 text-xs sm:text-sm">
                                <?php foreach ($availableModes as $modeKey => $meta): ?>
                                    <?php
                                        $isActiveMode = ($modeKey === $currentMode);
                                        $modeUrl = class_exists('HeaderManager') ? HeaderManager::getModeToggleUrl($modeKey) : '#';
                                        $modeLabel = $meta['short_label'] ?? ucfirst($modeKey);
                                        $modeDescription = $meta['description'] ?? '';
                                        $modeIcon = $modeIcons[$modeKey] ?? null;
                                    ?>
                                    <a href="<?= e($modeUrl) ?>"
                                       class="mode-switcher__option <?= $isActiveMode ? 'is-active' : '' ?>"
                                       <?php if ($modeDescription): ?>title="<?= e($modeDescription) ?>"<?php endif; ?>
                                       role="tab"
                                       aria-selected="<?= $isActiveMode ? 'true' : 'false' ?>">
                                        <?php if ($modeIcon): ?><i class="fas <?= e($modeIcon) ?>"></i><?php endif; ?>
                                        <span><?= e($modeLabel) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="flex flex-wrap items-center justify-end gap-3 text-white">
                    <div class="flex items-center gap-3 w-full lg:w-auto justify-end order-1 lg:order-none">
                        <!-- Dark Mode Toggle Button (Desktop) -->
                        <button id="dark-mode-toggle" onclick="toggleDarkMode()" class="hidden sm:inline-flex items-center justify-center p-2 text-white hover:bg-white/15 rounded-lg transition-colors duration-200" title="Karanlık mod" aria-label="Karanlık mod">
                            <i id="dark-mode-icon" class="fas fa-moon text-lg" aria-hidden="true"></i>
                        </button>
                        
                        <?php if (!empty($user['showNotifications']) && $user['showNotifications'] === true || (($ui['showNotifications'] ?? true) && $notificationsEnabled)): ?>
                        <div class="relative hidden sm:block" id="notif-root">
                            <button id="notif-button"
                                    type="button"
                                    class="relative inline-flex items-center justify-center p-2 text-white hover:bg-white/15 rounded-lg transition-colors duration-200 focus:ring-2 focus:ring-white/60 focus:outline-none"
                                    title="Bildirimler"
                                    aria-label="Bildirimler"
                                    aria-haspopup="dialog"
                                    aria-expanded="false"
                                    aria-controls="notif-menu">
                                <i class="fas fa-bell text-lg" aria-hidden="true"></i>
                                <span id="notif-badge" class="absolute -top-1 -right-1 flex items-center justify-center w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full border-2 border-primary-600 <?= $notificationCount > 0 ? '' : 'hidden' ?>">
                                    <?= $notificationCount > 0 ? ($notificationCount > 9 ? '9+' : $notificationCount) : '' ?>
                                </span>
                            </button>
                            <div id="notif-menu"
                                 data-variant="desktop"
                                 class="notification-panel hidden absolute right-0 mt-3 w-96 max-w-[calc(100vw-1.5rem)] sm:max-w-96 z-[10000]"
                                 role="dialog"
                                 aria-modal="false"
                                 aria-labelledby="notif-title-desktop">
                                <!-- Glassmorphism backdrop with gradient border -->
                                <div class="notification-panel__scrim" data-notif-dismiss aria-hidden="true"></div>
                                <div class="notification-panel__backdrop">
                                    <!-- Animated gradient border -->
                                    <div class="notification-panel__border-glow"></div>
                                    
                                    <!-- Header -->
                                    <div class="notification-panel__header">
                                        <div class="notification-panel__header-content">
                                            <div class="notification-panel__title" id="notif-title-desktop">
                                                <i class="fas fa-bell notification-panel__title-icon"></i>
                                                <span>Bildirimler</span>
                                                <span class="notification-panel__count-badge <?= $notificationCount > 0 ? '' : 'hidden' ?>" id="notif-count-chip">
                                                    <?= $notificationCount > 0 ? ($notificationCount > 99 ? '99+' : $notificationCount) : '' ?>
                                                </span>
                                            </div>
                                            <div class="notification-panel__actions">
                                                <button id="notif-mark-all" type="button" class="notification-panel__action-btn" title="Tümünü okundu say">
                                                    <i class="fas fa-check-double"></i>
                                                </button>
                                                <button id="notif-mute" type="button" class="notification-panel__action-btn" title="Sessize al">
                                                    <i class="fas fa-volume-mute"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Notification List -->
                                    <div id="notif-list" class="notification-panel__list" role="list">
                                        <?php if (!empty($notificationsInfo['items'])): ?>
                                            <?php foreach ($notificationsInfo['items'] as $n): ?>
                                                <?php $n_local = $n; include __DIR__ . '/../../partials/ui/notification-item.php'; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="notification-panel__empty">
                                                <div class="notification-panel__empty-icon">
                                                    <i class="fas fa-bell-slash"></i>
                                                </div>
                                                <div class="notification-panel__empty-text">Yeni bildirim yok</div>
                                                <div class="notification-panel__empty-subtext">Tüm bildirimleriniz burada görünecek</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Footer -->
                                    <div class="notification-panel__footer">
                                        <a href="<?= base_url('/notifications') ?>" class="notification-panel__footer-link">
                                            <span>Tümünü Gör</span>
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($customActions)): ?>
                            <?php foreach ($customActions as $action): ?>
                                <?php
                                    $actionUrl = $action['url'] ?? '#';
                                    if (!preg_match('~^https?://~i', $actionUrl)) {
                                        $actionUrl = base_url($actionUrl);
                                    }
                                ?>
                                <a href="<?= e($actionUrl) ?>" class="inline-flex items-center px-3 py-1.5 bg-white/15 hover:bg-white/25 text-white text-sm rounded-md transition-all duration-200">
                                    <?php if (!empty($action['icon'])): ?><i class="fas <?= e($action['icon']) ?> mr-1.5 text-xs" aria-hidden="true"></i><?php endif; ?>
                                    <span><?= htmlspecialchars($action['label'] ?? '') ?></span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($user['showProfile']) && $user['showProfile'] === false): ?>
                            <!-- no profile chip -->
                        <?php elseif ($isAuthenticated && $username): ?>
                        <div class="hidden sm:flex items-center space-x-2 text-sm">
                            <div class="inline-flex items-center bg-white/15 px-3 py-1 rounded-md">
                                <i class="fas fa-user text-white text-xs mr-1" aria-hidden="true"></i>
                                <span class="text-white"><?= e($username) ?></span>
                            </div>
                            <a href="<?= e($logoutUrl) ?>"
                               class="inline-flex items-center px-3 py-1.5 bg-white/15 hover:bg-white/25 text-white text-sm rounded-md transition-all duration-200"
                               title="Çıkış" aria-label="Çıkış">
                                <i class="fas fa-sign-out-alt mr-1.5 text-xs" aria-hidden="true"></i>
                                <span class="hidden lg:inline">Çıkış</span>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if (!$isAuthenticated): ?>
                            <a href="<?= e($loginUrl) ?>"
                               class="inline-flex items-center px-3 py-1.5 bg-white/15 hover:bg-white/25 text-white text-sm rounded-md transition-all duration-200">
                                <i class="fas fa-sign-in-alt mr-1.5 text-xs" aria-hidden="true"></i>
                                <span class="hidden sm:inline">Giriş</span>
                            </a>
                        <?php endif; ?>

                        <?php if (($ui['showSystemMenu'] ?? true) && $isSuperAdmin && !empty($systemMenu)): ?>
                        <div class="relative" data-dropdown="system" style="z-index: 1000;">
                            <button id="system-menu-button"
                                    onclick="return window.toggleDropdown('system', event);" 
                                    class="hover:bg-white/15 px-3 py-1.5 rounded-md text-sm font-medium text-white whitespace-nowrap transition-all duration-200 flex items-center"
                                    title="Sistem Yönetimi">
                                <i class="fas fa-server text-xs" aria-hidden="true"></i>
                            </button>
                            <div id="dropdown-system" style="display: none; position: fixed; z-index: 99999;" class="mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5" onclick="event.stopPropagation();">
                                <div class="py-1">
                                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase bg-gray-50">
                                        Sistem Yönetimi
                                    </div>
                                    <?php foreach ($systemMenu as $item): ?>
                                    <a href="<?= htmlspecialchars($item['url'] ?? '#') ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <?php if (!empty($item['icon'])): ?><i class="fas <?= htmlspecialchars($item['icon']) ?> mr-2"></i><?php endif; ?>
                                        <?= htmlspecialchars($item['label'] ?? '') ?>
                                    </a>
                                    <?php endforeach; ?>
                                    <?php 
                                    // PATH_CRAWL_SYSADMIN_WEB_V1: Add sysadmin crawl link to system menu
                                    // PATH_CRAWL_ADMIN_V1: Add admin crawl link to system menu
                                    $currentUsername = $username ?? '';
                                    $isSystemAdmin = ($isSuperAdmin || $currentUsername === 'candas');
                                    if ($isSystemAdmin): 
                                    ?>
                                    <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                                    <a href="<?= base_url('/sysadmin/crawl') ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-search mr-2"></i>
                                        Sistem Tarama (Sysadmin Crawl)
                                    </a>
                                    <a href="<?= base_url('/sysadmin/admin-crawl') ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-user-shield mr-2"></i>
                                        Admin Tarama (Admin Crawl)
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6" style="position: relative; overflow: visible;">
        <div class="flex items-center justify-between h-14" style="position: relative; overflow: visible;">
            <div class="sm:hidden flex items-center">
                <button onclick="toggleMobileMenu()" 
                        class="text-white hover:bg-white hover:bg-opacity-10 p-2 rounded transition-all duration-200" aria-label="Mobil menüyü aç/kapat">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <div class="hidden sm:flex items-center space-x-1 flex-1 overflow-x-auto" style="scrollbar-width: none; -ms-overflow-style: none; overflow-y: visible;">
                <style>.hide-scrollbar::-webkit-scrollbar { display: none; }</style>
                <div class="flex items-center space-x-1 hide-scrollbar" style="overflow: visible;">
                    <?php foreach ($navigationItems as $navPosition => $item): ?>
                        <?php
                            $itemLabel = $item['label'] ?? '';
                            $itemIcon = $item['icon'] ?? null;
                            $itemUrl = $item['url'] ?? null;
                            $itemKey = $item['key'] ?? ('item-' . md5($itemLabel));
                            $itemKey = strtolower(preg_replace('/[^a-zA-Z0-9\-]+/', '-', $itemKey));
                            if ($itemKey === '') {
                                $itemKey = 'nav-item-' . $navPosition;
                            }
                            $children = $item['children'] ?? [];
                            $hasChildren = !empty($children);
                            $isActiveItem = false;
                            if ($itemUrl) {
                                $isActiveItem = class_exists('HeaderManager') ? HeaderManager::isActive($currentPathRaw, $itemUrl) : ($item['active'] ?? false);
                            }
                            if (!$isActiveItem && $hasChildren && class_exists('HeaderManager')) {
                                foreach ($children as $childCandidate) {
                                    if (!empty($childCandidate['url']) && HeaderManager::isActive($currentPathRaw, $childCandidate['url'])) {
                                        $isActiveItem = true;
                                        break;
                                    }
                                }
                            }
                            $linkBaseClass = $isActiveItem ? 'bg-white bg-opacity-20 text-white shadow-inner' : 'text-white hover:bg-white hover:bg-opacity-10';
                        ?>
                        <?php if ($hasChildren): ?>
                            <div class="relative" data-dropdown="<?= e($itemKey) ?>" style="z-index: 1000;">
                                <button id="nav-<?= e($itemKey) ?>"
                                        onclick="return window.toggleDropdown('<?= e($itemKey) ?>', event);"
                                        class="<?= $linkBaseClass ?> px-3 py-2 rounded-md text-sm font-medium whitespace-nowrap transition-all duration-200 flex items-center gap-1.5">
                                    <?php if ($itemIcon): ?><i class="fas <?= e($itemIcon) ?> mr-1"></i><?php endif; ?>
                                    <?= e($itemLabel) ?>
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </button>
                                <div id="dropdown-<?= e($itemKey) ?>" style="display: none; position: fixed; z-index: 99999;" class="mt-2 w-64 rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 py-2" onclick="event.stopPropagation();">
                                    <?php
                                        $groupedChildren = [];
                                        foreach ($children as $childItem) {
                                            $groupIdentifier = $childItem['group'] ?? '__default';
                                            $groupedChildren[$groupIdentifier][] = $childItem;
                                        }
                                        $groupIndex = 0;
                                    ?>
                                    <?php foreach ($groupedChildren as $groupName => $groupChildren): ?>
                                        <?php $groupIndex++; ?>
                                        <?php if ($groupIndex > 1): ?>
                                            <div class="border-t border-gray-200 dark:border-gray-700 my-1 mx-3"></div>
                                        <?php endif; ?>
                                        <?php if ($groupName !== '__default'): ?>
                                            <div class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"><?= e($groupName) ?></div>
                                        <?php endif; ?>
                                        <?php foreach ($groupChildren as $child): ?>
                                            <?php
                                                $childLabel = $child['label'] ?? '';
                                                $childIcon = $child['icon'] ?? null;
                                                $childUrlRaw = $child['url'] ?? '#';
                                                $childUrl = $childUrlRaw;
                                                if (!preg_match('~^https?://~i', $childUrl)) {
                                                    $childUrl = base_url($childUrl);
                                                }
                                                $childActive = $child['active'] ?? false;
                                                if (!$childActive && class_exists('HeaderManager') && !empty($child['url'])) {
                                                    $childActive = HeaderManager::isActive($currentPathRaw, $child['url']);
                                                }
                                            ?>
                                            <a href="<?= e($childUrl) ?>"
                                               class="flex items-center gap-2 px-4 py-2 text-sm transition-colors duration-150 <?= $childActive ? 'bg-primary-50 text-primary-700 font-semibold dark:bg-primary-900/20 dark:text-primary-200' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' ?>">
                                                <?php if ($childIcon): ?><i class="fas <?= e($childIcon) ?> w-4 text-primary-500 dark:text-primary-300"></i><?php endif; ?>
                                                <span><?= e($childLabel) ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php
                                $itemUrlOut = $itemUrl ?? '#';
                                if ($itemUrlOut !== '#' && !preg_match('~^https?://~i', $itemUrlOut)) {
                                    $itemUrlOut = base_url($itemUrlOut);
                                }
                            ?>
                            <a href="<?= e($itemUrlOut) ?>"
                               class="<?= $linkBaseClass ?> px-3 py-2 rounded-md text-sm font-medium whitespace-nowrap transition-all duration-200 flex items-center gap-1.5"
                               aria-current="<?= $isActiveItem ? 'page' : 'false' ?>">
                                <?php if ($itemIcon): ?><i class="fas <?= e($itemIcon) ?> mr-1"></i><?php endif; ?>
                                <?= e($itemLabel) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="mobile-menu" style="display: none;" class="sm:hidden mobile-nav border-t border-white/20">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <?php $navIndex = 0; $navTotal = count($navigationItems); ?>
            <?php foreach ($navigationItems as $mobileIndex => $item): ?>
                <?php
                    $navIndex++;
                    $itemLabel = $item['label'] ?? '';
                    $itemIcon = $item['icon'] ?? null;
                    $itemUrlRaw = $item['url'] ?? null;
                    $itemKey = $item['key'] ?? ('mobile-item-' . $mobileIndex);
                    $itemKey = strtolower(preg_replace('/[^a-zA-Z0-9\-]+/', '-', $itemKey));
                    $itemUrlOut = $itemUrlRaw;
                    if ($itemUrlOut && !preg_match('~^https?://~i', $itemUrlOut)) {
                        $itemUrlOut = base_url($itemUrlOut);
                    }
                    $children = $item['children'] ?? [];
                    $hasChildren = !empty($children);
                    $itemActive = $item['active'] ?? false;
                    if (!$itemActive && $itemUrlRaw && class_exists('HeaderManager')) {
                        $itemActive = HeaderManager::isActive($currentPathRaw, $itemUrlRaw);
                    }
                ?>
                <?php if ($hasChildren): ?>
                    <?php
                        $groupedChildren = [];
                        $isSectionActive = false;
                        foreach ($children as $childItem) {
                            $groupKey = $childItem['group'] ?? '__default';
                            $groupedChildren[$groupKey][] = $childItem;
                            if (!$isSectionActive && !empty($childItem['url']) && class_exists('HeaderManager')) {
                                $isSectionActive = HeaderManager::isActive($currentPathRaw, $childItem['url']);
                            } elseif (!$isSectionActive && !empty($childItem['active'])) {
                                $isSectionActive = true;
                            }
                        }
                    ?>
                    <details class="mobile-nav__section" id="section-<?= e($itemKey) ?>"<?= $isSectionActive ? ' open' : '' ?>>
                        <summary class="mobile-nav__section-trigger flex items-center justify-between gap-2 px-3 py-2 rounded-lg bg-white/10 text-white text-sm">
                            <span class="inline-flex items-center gap-2">
                                <?php if ($itemIcon): ?><i class="fas <?= e($itemIcon) ?>"></i><?php endif; ?>
                                <?= e($itemLabel) ?>
                            </span>
                            <i class="fas fa-chevron-down text-xs opacity-70"></i>
                        </summary>
                        <div class="mobile-nav__section-body mt-1 space-y-1">
                            <?php if ($itemUrlRaw): ?>
                                <a href="<?= e($itemUrlOut) ?>"
                                   class="mobile-nav__parent-link <?= $itemActive ? 'mobile-nav__parent-link--active' : '' ?>">
                                    <i class="fas fa-arrow-up-right-from-square"></i>
                                    <span><?= e($itemLabel) ?></span>
                                </a>
                            <?php endif; ?>
                            <?php foreach ($groupedChildren as $groupName => $groupChildren): ?>
                                <?php if ($groupName !== '__default'): ?>
                                    <div class="mobile-nav__group-heading px-3 text-[11px] uppercase tracking-wide text-white/70 mt-2">
                                        <?= e($groupName) ?>
                                    </div>
                                <?php endif; ?>
                                <?php foreach ($groupChildren as $child): ?>
                                    <?php
                                        $childLabel = $child['label'] ?? '';
                                        $childIcon = $child['icon'] ?? null;
                                        $childUrlRaw = $child['url'] ?? '#';
                                        $childUrlOut = $childUrlRaw;
                                        if (!preg_match('~^https?://~i', $childUrlOut)) {
                                            $childUrlOut = base_url($childUrlOut);
                                        }
                                        $childActive = $child['active'] ?? false;
                                        if (!$childActive && !empty($child['url']) && class_exists('HeaderManager')) {
                                            $childActive = HeaderManager::isActive($currentPathRaw, $child['url']);
                                        }
                                    ?>
                                    <a href="<?= e($childUrlOut) ?>"
                                       class="mobile-nav__sublink <?= $childActive ? 'is-active' : '' ?>">
                                        <?php if ($childIcon): ?><i class="fas <?= e($childIcon) ?>"></i><?php endif; ?>
                                        <span><?= e($childLabel) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </details>
                    <?php if ($navIndex < $navTotal): ?>
                        <div class="mobile-nav__divider"></div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($itemUrlRaw): ?>
                        <a href="<?= e($itemUrlOut) ?>"
                           class="mobile-nav__link <?= $itemActive ? 'mobile-nav__link--active' : '' ?>">
                            <?php if ($itemIcon): ?><i class="fas <?= e($itemIcon) ?>"></i><?php endif; ?>
                            <span><?= e($itemLabel) ?></span>
                        </a>
                    <?php else: ?>
                        <div class="mobile-nav__link mobile-nav__link--static">
                            <?php if ($itemIcon): ?><i class="fas <?= e($itemIcon) ?>"></i><?php endif; ?>
                            <span><?= e($itemLabel) ?></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!empty($customActions) && !$isAuthenticated): ?>
                <?php foreach ($customActions as $action): ?>
                    <?php
                        $actionUrl = $action['url'] ?? '#';
                        if (!preg_match('~^https?://~i', $actionUrl)) {
                            $actionUrl = base_url($actionUrl);
                        }
                    ?>
                    <a href="<?= e($actionUrl) ?>" class="mobile-nav__link">
                        <?php if (!empty($action['icon'])): ?><i class="fas <?= e($action['icon']) ?>"></i><?php endif; ?>
                        <span><?= htmlspecialchars($action['label'] ?? '') ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</nav>











