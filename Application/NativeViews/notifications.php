<?php
$pageTitle = 'Owner notifications';
$unreadOwnerNotifications = array_filter($ownerNotifications, static fn ($notification) => empty($notification['read_at']));
?>
<section class="owner-module">
    <header class="module-heading"><div><span class="eyebrow section-eyebrow">Administrative activity</span><h1>Notifications</h1><p>Review reservation, payment, feedback, and system notices addressed to your Admin account.</p></div><span class="module-total"><?= count($unreadOwnerNotifications) ?> unread</span></header>
    <?php if ($unreadOwnerNotifications): ?><form method="post" action="<?= rc_e(rc_endpoint()) ?>" class="sales-actions"><?= rc_csrf_field() ?><input type="hidden" name="action" value="mark-owner-read"><button class="button button-secondary" type="submit">Mark all as read</button></form><?php endif; ?>
    <section class="surface sales-section"><div class="surface-heading"><h2>Notification history</h2><span>Latest <?= count($ownerNotifications) ?> records · maximum 250</span></div>
        <?php if ($ownerNotifications): ?><?php foreach ($ownerNotifications as $notification): ?>
            <article class="notification-row <?= empty($notification['read_at']) ? 'unread' : '' ?>">
                <div><strong><?= rc_e($notification['notification_type']) ?></strong><p><?= rc_e($notification['message']) ?></p><time><?= rc_e($notification['created_at']) ?></time><?php if ($notification['order_id']): ?><small> · <a class="text-link" href="<?= rc_url('orders', ['q' => (int) $notification['order_id']]) ?>">Reservation #RZ-<?= (int) $notification['order_id'] ?></a><?php if ($notification['client_name']): ?> · <?= rc_e($notification['client_name']) ?> · <?= rc_e($notification['event_date']) ?><?php endif; ?></small><?php endif; ?></div>
                <?php if (empty($notification['read_at'])): ?><form method="post" action="<?= rc_e(rc_endpoint()) ?>"><?= rc_csrf_field() ?><input type="hidden" name="action" value="mark-owner-notification"><input type="hidden" name="notification_id" value="<?= (int) $notification['notification_id'] ?>"><button class="text-link" type="submit">Mark read</button></form><?php else: ?><span class="status status-completed">Read</span><?php endif; ?>
            </article>
        <?php endforeach; ?><?php else: ?><div class="empty-state"><strong>No notifications yet</strong>Important owner activity will appear here.</div><?php endif; ?>
    </section>
</section>
