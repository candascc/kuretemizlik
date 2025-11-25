<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Lib/HeaderManager.php';
require_once __DIR__ . '/../src/Lib/Utils.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!class_exists('CSRF')) {
    class CSRF {
        public static function get(): string { return 'snapshot-token'; }
    }
}
if (!class_exists('Auth')) {
    class Auth {
        public static function check(): bool { return true; }
        public static function role(): ?string { return 'ADMIN'; }
        public static function user(): array { return ['username' => 'snapshot.admin']; }
    }
}
if (!class_exists('SuperAdmin')) {
    class SuperAdmin {
        public static function isSuperAdmin(): bool { return true; }
    }
}
if (!class_exists('View')) {
    class View {
        public static function partial(string $name) { return ''; }
    }
}

$baseDir = __DIR__ . '/../snapshots';
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0777, true);
}

$modes = ['operations', 'management'];
foreach ($modes as $mode) {
    HeaderManager::bootstrap();
    HeaderManager::rememberMode($mode, false);
    $modeMeta = HeaderManager::getModeMeta($mode);
    $theme = $modeMeta['theme'];
    $nav = HeaderManager::getNavigationItems('ADMIN', $mode);
    $actions = HeaderManager::getQuickActions('ADMIN', $mode);

    $navCount = count($nav);
    $actionCount = count($actions);
    ob_start();
    ?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Header Snapshot - <?= htmlspecialchars($modeMeta['label']) ?></title>
    <link rel="stylesheet" href="../assets/css/custom.css">
</head>
<body class="<?= htmlspecialchars($theme['class']) ?>" style="padding:2rem;background:#0f172a;color:white;font-family:Inter, sans-serif;">
    <h1><?= htmlspecialchars($modeMeta['label']) ?> Header Snapshot</h1>
    <p>Navigation entries: <?= $navCount ?> | Quick actions: <?= $actionCount ?></p>
    <section>
        <h2>Navigasyon</h2>
        <ul>
            <?php foreach ($nav as $item): ?>
                <li>
                    <strong><?= htmlspecialchars($item['label']) ?></strong>
                    <?php if (!empty($item['children'])): ?>
                        <ul>
                            <?php foreach ($item['children'] as $child): ?>
                                <li><?= htmlspecialchars($child['label']) ?> (<?= htmlspecialchars($child['url']) ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section>
        <h2>Hızlı Eylemler</h2>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
            <?php foreach ($actions as $action): ?>
                <span class="quick-action-btn <?= htmlspecialchars($theme['quick_action']) ?>">
                    <?= htmlspecialchars($action['label']) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </section>
    <p style="margin-top:2rem;font-size:0.9rem;color:#cbd5f5;">Bu çıktı CLI ile oluşturulmuştur (<?= date('c') ?>).</p>
</body>
</html><?php
    $html = ob_get_clean();
    file_put_contents($baseDir . '/header-' . $mode . '.html', $html);
    file_put_contents($baseDir . '/debug-' . $mode . '.txt', print_r([
        'mode' => $mode,
        'available_modes' => HeaderManager::getModes(),
        'nav' => $nav,
        'actions' => $actions,
    ], true));
}

echo "Snapshots generated in snapshots/ directory\n";
