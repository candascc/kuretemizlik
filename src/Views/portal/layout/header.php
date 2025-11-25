<?php
    require_once __DIR__ . '/../../layout/partials/header-context.php';

    $portalNavigation = [
        ['key' => 'portal-dashboard', 'label' => __('dashboard'), 'icon' => 'fa-gauge', 'url' => '/portal/dashboard'],
        ['key' => 'portal-jobs', 'label' => __('jobs'), 'icon' => 'fa-briefcase', 'url' => '/portal/jobs'],
        ['key' => 'portal-invoices', 'label' => __('invoices'), 'icon' => 'fa-file-invoice', 'url' => '/portal/invoices'],
        ['key' => 'portal-booking', 'label' => __('book'), 'icon' => 'fa-calendar-plus', 'url' => '/portal/booking'],
    ];

    $portalActions = [
        ['label' => __('logout'), 'icon' => 'fa-sign-out-alt', 'url' => '/portal/logout'],
    ];

    $headerContext = build_portal_header_context([
        'brand' => [
            'label' => __('customer_portal'),
            'icon' => 'fas fa-home',
            'url' => base_url('/portal/dashboard'),
        ],
        'navigationItems' => $portalNavigation,
        'user' => [
            'actions' => $portalActions,
            'isAuthenticated' => true,
            'showProfile' => false,
        ],
        'ui' => [
            'showSearch' => false,
            'showStatusChips' => false,
            'showQuickActions' => false,
            'showNotifications' => false,
            'showSystemMenu' => false,
        ],
        'modeIcons' => [
            'portal' => 'fa-building',
        ],
    ]);
?>
<!DOCTYPE html>
<html lang="<?= Translator::getLocale() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Customer Portal' ?></title>
    <link rel="stylesheet" href="<?= Utils::asset('css/tailwind.css') ?>?v=<?= file_exists(__DIR__ . '/../../../../assets/css/tailwind.css') ? filemtime(__DIR__ . '/../../../../assets/css/tailwind.css') : time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('/assets/css/custom.css') ?>?v=<?= file_exists(__DIR__ . '/../../../../assets/css/custom.css') ? filemtime(__DIR__ . '/../../../../assets/css/custom.css') : time() ?>">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../../layout/partials/app-header.php'; ?>

    <?php if (has_flash('success')): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
                <i class="fas fa-check-circle mr-2"></i><?= get_flash('success') ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (has_flash('error')): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                <i class="fas fa-exclamation-circle mr-2"></i><?= get_flash('error') ?>
            </div>
        </div>
    <?php endif; ?>

