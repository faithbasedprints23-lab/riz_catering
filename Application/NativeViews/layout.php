<?php
$isSignedIn = is_array($user ?? null);
$role = $isSignedIn ? (string) ($user['role'] ?? '') : '';
$isOwnerAuthPage = in_array($currentPage ?? '', ['owner-login', 'install'], true);
$isOwner = $role === 'Admin' && !$isOwnerAuthPage;
$appBasePath = rc_app_base_path();
$assetBasePath = $appBasePath === '' || $appBasePath === '.' ? '' : $appBasePath;
$activeOwnerPage = in_array($currentPage ?? '', ['customers', 'users', 'audit', 'password', 'notifications'], true) ? 'settings' : ($currentPage ?? '');
$ownerNavigation = [
    'owner' => ['Dashboard', '⌂'], 'orders' => ['Orders', '▤'], 'operations' => ['Operations', '▦'],
    'feedback-admin' => ['Feedback', '☆'], 'menu-admin' => ['Menu', '≋'], 'packages-admin' => ['Packages', '▣'],
    'sales' => ['Sales', '₱'], 'settings' => ['Settings', '⚙'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= rc_e(($pageTitle ?? 'Riz Catering') . ' · Riz Catering') ?></title>
    <link rel="stylesheet" href="<?= rc_e($assetBasePath) ?>/native.css?v=20260930-responsive-packages">
    <link rel="stylesheet" href="<?= rc_e($assetBasePath) ?>/customer.css?v=20260926-brand">
    <script>try{const t=localStorage.getItem('riz-theme');if(t==='dark'||t==='light')document.documentElement.dataset.theme=t}catch(e){}</script>
    <script src="<?= rc_e($assetBasePath) ?>/native.js?v=20260930-package-empty-state" defer></script>
</head>
<body class="riz-app-body <?= $isOwner ? 'owner-account-body' : '' ?>">
<?php if ($isOwner): ?>
    <header class="owner-topbar">
        <a href="<?= rc_url('owner') ?>" class="owner-brand"><span class="owner-brand-mark">R</span><span>Riz Catering<small>OWNER PORTAL</small></span></a>
        <div class="owner-topbar-tools"><span class="owner-current-date"><?= rc_e(date('l, F j, Y')) ?></span><a class="owner-notification-link" href="<?= rc_url('notifications') ?>" aria-label="Owner notifications"><span aria-hidden="true">🔔</span> Notifications<?php if (!empty($navUnreadNotifications)): ?><b><?= (int) $navUnreadNotifications ?></b><?php endif; ?></a>
            <button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch color theme">☾ <span>Dark mode</span></button>
            <span class="owner-user-chip"><span class="owner-avatar"><?= rc_e(mb_strtoupper(mb_substr((string) $user['full_name'], 0, 1))) ?></span><?= rc_e($user['full_name']) ?></span>
            <form method="post" action="<?= rc_e(rc_endpoint()) ?>" class="nav-logout-form"><?= rc_csrf_field() ?><input type="hidden" name="action" value="logout"><button class="owner-signout" type="submit">Sign out</button></form>
        </div>
    </header>
    <div class="owner-app-shell"><aside class="owner-sidebar" aria-label="Owner navigation"><span class="owner-sidebar-label">MANAGE BUSINESS</span><nav><?php foreach ($ownerNavigation as $page => [$label, $icon]): ?><a class="owner-side-link <?= $activeOwnerPage === $page ? 'active' : '' ?>" href="<?= rc_url($page) ?>"><span class="owner-side-icon" aria-hidden="true"><?= rc_e($icon) ?></span><span><?= rc_e($label) ?></span><?php if ($page === 'orders' && !empty($ownerPendingCount)): ?><b class="owner-side-badge"><?= (int) $ownerPendingCount ?></b><?php endif; ?></a><?php endforeach; ?></nav><div class="owner-sidebar-foot"><strong><?= rc_e($businessName ?? 'Riz Catering Services') ?></strong><span>Owner management portal</span></div></aside>
        <main class="owner-main"><?php if ($flash): ?><div class="flash flash-<?= rc_e($flash['type']) ?>" role="status"><span><?= rc_e($flash['message']) ?></span><button class="flash-close" type="button" aria-label="Dismiss message">×</button></div><?php endif; ?><?= $content ?></main>
    </div>
<?php else: ?>
    <?php if ($isOwnerAuthPage): ?><header class="owner-topbar"><a href="<?= rc_url('owner-login') ?>" class="owner-brand"><span class="owner-brand-mark">R</span><span>Riz Catering<small>OWNER PORTAL</small></span></a><button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch color theme">☾ <span>Dark mode</span></button><a class="text-link" href="<?= rc_url('home') ?>">Customer website</a></header>
    <?php else: ?><header class="riz-navbar"><div class="riz-nav-container"><a href="<?= rc_url('home') ?>" class="riz-brand">Riz Catering</a><nav class="riz-nav-links" aria-label="Main navigation"><a href="<?= rc_url('menu') ?>">Menu</a><a href="<?= rc_url('reservation') ?>">Order / Reserve</a><a href="<?= rc_url('feedback') ?>">Feedback</a><?php if ($role === 'Customer'): ?><a href="<?= rc_url('dashboard') ?>">My dashboard</a><a class="customer-notification-link" href="<?= rc_url('dashboard') ?>#customer-notifications" aria-label="Notifications<?= !empty($navUnreadNotifications) ? ', ' . (int) $navUnreadNotifications . ' unread' : '' ?>">🔔<?php if (!empty($navUnreadNotifications)): ?><b><?= min(99, (int) $navUnreadNotifications) ?><?= (int) $navUnreadNotifications > 99 ? '+' : '' ?></b><?php endif; ?></a><?php else: ?><a href="<?= rc_url('login') ?>">Customer Sign In</a><a href="<?= rc_url('register') ?>">Create account</a><?php endif; ?><button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch color theme">☾ <span>Dark mode</span></button><?php if ($isSignedIn): ?><form method="post" action="<?= rc_e(rc_endpoint()) ?>" class="nav-logout-form"><?= rc_csrf_field() ?><input type="hidden" name="action" value="logout"><button class="riz-logout-btn" type="submit">Sign out</button></form><?php endif; ?></nav></div></header><?php endif; ?>
    <main class="riz-content-container"><?php if ($flash): ?><div class="flash flash-<?= rc_e($flash['type']) ?>" role="status"><span><?= rc_e($flash['message']) ?></span><button class="flash-close" type="button" aria-label="Dismiss message">×</button></div><?php endif; ?><?= $content ?></main>
<?php endif; ?>
<dialog class="app-dialog" id="app-dialog" aria-labelledby="dialog-content"><div id="dialog-content"></div><div class="dialog-actions"><button class="button button-secondary" id="dialog-cancel" type="button">Cancel</button><button class="button" id="dialog-confirm" type="button">Continue</button></div></dialog>
</body></html>
