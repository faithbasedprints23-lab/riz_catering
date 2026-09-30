<?php
$pageTitle = 'Operations';
$first = new DateTimeImmutable($month . '-01');
$days = (int) $first->format('t');
$offset = (int) $first->format('N') - 1;
$eventsByDay = [];
foreach ($events as $event) $eventsByDay[(int) date('j', strtotime($event['event_date']))][] = $event;
$previous = $first->modify('-1 month')->format('Y-m');
$next = $first->modify('+1 month')->format('Y-m');
?>
<section class="owner-module">
    <header class="module-heading"><div><span class="eyebrow section-eyebrow">Event schedule</span><h1>Operations</h1><p>Review event schedules from reservations. Tentative requests are visible but only confirmed events occupy the schedule.</p></div><a class="button button-secondary" href="<?= rc_url('orders', ['status' => 'Confirmed']) ?>">Confirmed orders</a></header>
    <div class="operation-view-tabs"><?php foreach (['day' => 'Day', 'week' => 'Week', 'month' => 'Month'] as $viewKey => $viewLabel): ?><a class="<?= $view === $viewKey ? 'active' : '' ?>" href="<?= rc_url('operations', ['view' => $viewKey, 'date' => $selectedDate, 'month' => $month]) ?>"><?= rc_e($viewLabel) ?></a><?php endforeach; ?><form method="get" action="<?= rc_e(rc_endpoint()) ?>"><input type="hidden" name="page" value="operations"><input type="hidden" name="view" value="<?= rc_e($view) ?>"><input type="date" name="date" value="<?= rc_e($selectedDate) ?>" aria-label="Choose event date"><button class="button button-secondary" type="submit">Go to date</button></form></div>
    <?php if ($view === 'month'): ?>
        <div class="calendar-heading"><a class="button button-secondary" href="<?= rc_url('operations', ['view' => 'month', 'month' => $previous]) ?>">← Previous</a><h2><?= rc_e($first->format('F Y')) ?></h2><a class="button button-secondary" href="<?= rc_url('operations', ['view' => 'month', 'month' => $next]) ?>">Next →</a></div>
        <div class="calendar-grid calendar-days"><span>Monday</span><span>Tuesday</span><span>Wednesday</span><span>Thursday</span><span>Friday</span><span>Saturday</span><span>Sunday</span></div>
        <div class="calendar-grid"><?php for ($i = 0; $i < $offset; $i++): ?><div class="calendar-cell outside"></div><?php endfor; ?><?php for ($day = 1; $day <= $days; $day++): ?><div class="calendar-cell"><strong><?= $day ?></strong><?php foreach ($eventsByDay[$day] ?? [] as $event): ?><button type="button" class="calendar-event <?= rc_e(strtolower($event['schedule_status'])) ?>" data-dialog-title="<?= rc_e($event['client_name']) ?> · <?= rc_e($event['schedule_status']) ?>" data-dialog-text="<?= rc_e($event['event_date'] . ' · ' . substr($event['start_time'], 0, 5) . '–' . substr($event['end_time'], 0, 5) . ' · ' . $event['package_name'] . ' · ' . $event['guest_count'] . ' guests · ' . $event['venue'] . ' · ' . $event['payment_status'] . ' · balance PHP ' . $event['balance_due']) ?>" data-dialog-href="<?= rc_e(rc_url('orders', ['q' => (int) $event['order_id']])) ?>"><?= rc_e(substr($event['start_time'], 0, 5)) ?> <?= rc_e($event['client_name']) ?></button><?php endforeach; ?></div><?php endfor; ?></div>
    <?php else: ?>
        <section class="surface"><div class="surface-heading"><h2><?= $view === 'day' ? rc_e(date('l, F j, Y', strtotime($selectedDate))) : 'Week of ' . rc_e(date('F j', strtotime($events[0]['event_date'] ?? $selectedDate))) ?></h2><span><?= count($events) ?> events</span></div>
            <?php if ($events): ?><div class="table-wrap"><table><thead><tr><th>Date / time</th><th>Customer</th><th>Package</th><th>Venue</th><th>Guests</th><th>Payment</th><th>Event status</th><th>Reservation</th></tr></thead><tbody><?php foreach ($events as $event): ?><tr>
                <td><?= rc_e($event['event_date']) ?><small><?= rc_e(substr($event['start_time'], 0, 5)) ?>–<?= rc_e(substr($event['end_time'], 0, 5)) ?></small></td><td><?= rc_e($event['client_name']) ?><small><?= rc_e($event['client_mobile']) ?></small></td><td><?= rc_e($event['package_name'] ?: 'Package') ?></td><td><?= rc_e($event['venue']) ?></td><td><?= (int) $event['guest_count'] ?></td><td><?= rc_e($event['payment_status']) ?><small>Due <?= rc_money(rc_cents($event['balance_due'])) ?></small></td><td><?= rc_e($event['schedule_status']) ?></td><td><a class="text-link" href="<?= rc_url('orders', ['q' => (int) $event['order_id']]) ?>">Open #RZ-<?= (int) $event['order_id'] ?></a></td>
            </tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><strong>No scheduled events in this range</strong>Reservations and event details will appear here.</div><?php endif; ?>
        </section>
    <?php endif; ?>
    <p class="muted">The system checks schedule overlap again when a reservation is confirmed. Event records remain linked to their reservation history.</p>
</section>
