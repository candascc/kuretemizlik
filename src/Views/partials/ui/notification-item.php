<?php

// Expects $n array with keys: text, meta, href, key, type(critical|ops|system), icon, read

$n = $n ?? [];

$type = $n['type'] ?? 'ops';

$icon = $n['icon'] ?? ($type==='critical'?'fa-exclamation-triangle':($type==='system'?'fa-hdd':'fa-info-circle'));

$isRead = !empty($n['read']);

$readCls = $isRead ? 'notification-item--read' : '';

$typeClass = $type==='critical'?'notification-item--critical':($type==='system'?'notification-item--system':'notification-item--ops');

$href = $n['href'] ?? null;

$dataHref = $href ? ' data-href="' . e($href) . '"' : '';

$dataKey = htmlspecialchars($n['key'] ?? '');

?>

<div class="notification-item <?= $typeClass ?> <?= $readCls ?>" data-key="<?= $dataKey ?>" data-type="<?= e($type) ?>"<?= $dataHref ?> data-read="<?= $isRead ? 'true' : 'false' ?>" tabindex="0" role="button">

    <div class="notification-item__glow"></div>

    <?php if (!empty($n['key'])): ?>

    <button class="notification-item__mark-toggle" type="button" data-action="mark-unread" title="Okunmadı olarak işaretle">

        <i class="fas fa-rotate-left"></i>

    </button>

    <?php endif; ?>

    <div class="notification-item__content">

        <div class="notification-item__icon-wrapper">

            <div class="notification-item__icon-bg"></div>

            <i class="fas <?= e($icon) ?> notification-item__icon"></i>

        </div>

        <div class="notification-item__body">

            <div class="notification-item__header">

                <?php if (!$isRead): ?>

                <span class="notification-item__badge">

                    <span class="notification-item__badge-dot"></span>

                    <span>Yeni</span>

                </span>

                <?php endif; ?>

                <div class="notification-item__title" title="<?= htmlspecialchars($n['text'] ?? '') ?>">

                    <?= htmlspecialchars($n['text'] ?? '') ?>

                </div>

            </div>

            <?php if (!empty($n['meta'])): ?>

            <div class="notification-item__meta" title="<?= e($n['meta']) ?>">

                <?= e($n['meta']) ?>

            </div>

            <?php endif; ?>

        </div>

    </div>

</div>





